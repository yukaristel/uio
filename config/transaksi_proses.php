<?php
/**
 * PROSES TRANSAKSI PENJUALAN + STOCK MOVEMENT + JURNAL AKUNTANSI OTOMATIS
 * 
 * FLOW:
 * 1. Validasi stok bahan mencukupi
 * 2. Insert transaksi_penjualan
 * 3. Insert detail_transaksi
 * 4. Update stok bahan (kurangi stok)
 * 5. Catat stock_movement (keluar)
 * 6. Catat jurnal akuntansi (penjualan + HPP) - OTOMATIS
 * 7. Generate saldo dari transaksi
 */

session_start();
require_once 'database.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Anda harus login!';
    header('Location: ../index.php?page=login');
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'create':
        createTransaksi();
        break;
    default:
        header('Location: ../index.php?page=buat_transaksi');
        exit;
}

/**
 * CREATE TRANSAKSI PENJUALAN
 */
function createTransaksi() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../index.php?page=buat_transaksi');
        exit;
    }
    
    // Ambil data form
    $menu_items = isset($_POST['menu_id']) ? $_POST['menu_id'] : [];
    $jumlah_items = isset($_POST['jumlah']) ? $_POST['jumlah'] : [];
    $metode_pembayaran = $_POST['metode_pembayaran'];
    
    $uang_bayar = isset($_POST['uang_bayar_value']) ? floatval($_POST['uang_bayar_value']) : 0;

    // Validasi minimal 1 item
    if (empty($menu_items)) {
        $_SESSION['error'] = 'Pilih minimal 1 menu!';
        header('Location: ../index.php?page=buat_transaksi');
        exit;
    }
    
    // START TRANSACTION
    $conn = getConnection();
    try {
        $conn->begin_transaction();
        
        // 1. Validasi stok & hitung total
        $items = [];
        $total_harga = 0;
        $total_modal = 0;
        
        foreach ($menu_items as $index => $menu_id) {
            $jumlah = intval($jumlah_items[$index]);
            
            if ($jumlah <= 0) continue;
            
            // Ambil data menu
            $menu = fetchOne("SELECT * FROM menu_makanan WHERE id = ?", [$menu_id]);
            if (!$menu) {
                throw new Exception("Menu ID $menu_id tidak ditemukan!");
            }
            
            if ($menu['status'] != 'tersedia') {
                throw new Exception("Menu {$menu['nama_menu']} tidak tersedia!");
            }
            
            // Cek stok bahan mencukupi
            $stok_cukup = cekStokBahan($menu_id, $jumlah);
            if (!$stok_cukup['success']) {
                throw new Exception($stok_cukup['message']);
            }
            
            // Hitung subtotal
            $subtotal = $menu['harga_jual'] * $jumlah;
            $subtotal_modal = $menu['harga_modal'] * $jumlah;
            
            $items[] = [
                'menu_id' => $menu_id,
                'menu' => $menu,
                'jumlah' => $jumlah,
                'harga_satuan' => $menu['harga_jual'],
                'harga_modal_satuan' => $menu['harga_modal'],
                'subtotal' => $subtotal,
                'subtotal_modal' => $subtotal_modal
            ];
            
            $total_harga += $subtotal;
            $total_modal += $subtotal_modal;
        }
        
        if (empty($items)) {
            throw new Exception('Tidak ada item yang valid!');
        }
        
        $total_keuntungan = $total_harga - $total_modal;
        
        // Validasi uang bayar
        if ($metode_pembayaran == 'tunai') {
            // TUNAI: Harus >= total
            if ($uang_bayar < $total_harga) {
                throw new Exception('Uang bayar kurang! Total: ' . formatRupiah($total_harga));
            }
        } else {
            // NON-TUNAI: Minimal harus ada input
            if ($uang_bayar <= 0) {
                throw new Exception('Masukkan nominal uang yang diterima!');
            }
        }
        
        $uang_kembali = ($metode_pembayaran == 'tunai') ? ($uang_bayar - $total_harga) : 0;
        
        // 2. Generate nomor transaksi
        $no_transaksi = generateNoTransaksi('TRX');
        
        // 3. Insert transaksi_penjualan
        $sql_transaksi = "INSERT INTO transaksi_penjualan 
            (no_transaksi, tanggal_transaksi, total_harga, total_modal, total_keuntungan, 
            metode_pembayaran, uang_bayar, uang_kembali, user_id) 
            VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?)";
        
        $result = execute($sql_transaksi, [
            $no_transaksi, $total_harga, $total_modal, $total_keuntungan,
            $metode_pembayaran, $uang_bayar, $uang_kembali, $_SESSION['user_id']
        ]);
        
        if (!$result['success']) {
            throw new Exception('Gagal insert transaksi!');
        }
        
        $transaksi_id = $result['insert_id'];
        
        // 4. Insert detail_transaksi & kurangi stok
        foreach ($items as $item) {
            // Insert detail
            $sql_detail = "INSERT INTO detail_transaksi 
                (transaksi_id, menu_id, jumlah, harga_satuan, harga_modal_satuan, subtotal, subtotal_modal) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $result_detail = execute($sql_detail, [
                $transaksi_id, $item['menu_id'], $item['jumlah'], 
                $item['harga_satuan'], $item['harga_modal_satuan'],
                $item['subtotal'], $item['subtotal_modal']
            ]);
            
            if (!$result_detail['success']) {
                throw new Exception('Gagal insert detail transaksi!');
            }
            
            // Kurangi stok bahan
            kurangiStokBahan($transaksi_id, $item['menu_id'], $item['jumlah']);
        }
        
        // 5. Catat jurnal akuntansi OTOMATIS (PENGGANTI kas_umum)
        // TUNAI: pakai total_harga (karena ada kembalian)
        // NON-TUNAI: pakai uang_bayar (karena bisa ada potongan/diskon)
        $jumlah_pendapatan = ($metode_pembayaran == 'tunai') ? $total_harga : $uang_bayar;
        catatJurnalAkuntansi($transaksi_id, $no_transaksi, $metode_pembayaran, $jumlah_pendapatan, $total_modal);
        
        // COMMIT TRANSACTION
        $conn->commit();
        
        $_SESSION['success'] = 'Transaksi berhasil! No: ' . $no_transaksi;
        header('Location: ../index.php?page=struk_transaksi&id=' . $transaksi_id);
        
    } catch (Exception $e) {
        // ROLLBACK jika error
        $conn->rollback();
        $_SESSION['error'] = 'Transaksi gagal: ' . $e->getMessage();
        header('Location: ../index.php?page=buat_transaksi');
    }
    
    exit;
}

/**
 * CEK STOK BAHAN MENCUKUPI
 */
function cekStokBahan($menu_id, $jumlah_porsi) {
    // Ambil resep menu
    $resep = fetchAll("SELECT r.*, b.nama_bahan, b.stok_tersedia, b.satuan as satuan_bahan 
                       FROM resep_menu r 
                       JOIN bahan_baku b ON r.bahan_id = b.id 
                       WHERE r.menu_id = ?", [$menu_id]);
    
    if (empty($resep)) {
        return [
            'success' => false,
            'message' => 'Menu belum memiliki resep!'
        ];
    }
    
    foreach ($resep as $bahan) {
        // Konversi jumlah bahan ke satuan di database
        $jumlah_konversi = konversiSatuan(
            $bahan['jumlah_bahan'],
            $bahan['satuan'],
            $bahan['satuan_bahan']
        );
        
        // Total bahan yang dibutuhkan
        $jumlah_dibutuhkan = $jumlah_konversi * $jumlah_porsi;
        
        // Cek stok
        if ($bahan['stok_tersedia'] < $jumlah_dibutuhkan) {
            return [
                'success' => false,
                'message' => "Stok {$bahan['nama_bahan']} tidak cukup! " .
                           "Dibutuhkan: " . number_format($jumlah_dibutuhkan, 2) . " {$bahan['satuan_bahan']}, " .
                           "Tersedia: " . number_format($bahan['stok_tersedia'], 2) . " {$bahan['satuan_bahan']}"
            ];
        }
    }
    
    return ['success' => true];
}

/**
 * KURANGI STOK BAHAN + CATAT STOCK MOVEMENT
 */
function kurangiStokBahan($transaksi_id, $menu_id, $jumlah_porsi) {
    // Ambil resep menu dengan JOIN ke bahan_baku
    $resep = fetchAll("SELECT r.*, 
                              b.id as bahan_id,
                              b.kode_bahan,
                              b.nama_bahan,
                              b.satuan as satuan_bahan,
                              b.stok_tersedia,
                              b.harga_beli_per_satuan
                       FROM resep_menu r 
                       JOIN bahan_baku b ON r.bahan_id = b.id 
                       WHERE r.menu_id = ?", [$menu_id]);
    
    if (empty($resep)) {
        throw new Exception("Menu belum memiliki resep!");
    }
    
    foreach ($resep as $bahan) {
        // Konversi jumlah bahan ke satuan database
        $jumlah_konversi = konversiSatuan(
            $bahan['jumlah_bahan'],
            $bahan['satuan'], // satuan dari resep_menu
            $bahan['satuan_bahan'] // satuan dari bahan_baku
        );
        
        // Total yang dipakai
        $jumlah_pakai = $jumlah_konversi * $jumlah_porsi;
        
        // Stok sebelum dan sesudah
        $stok_sebelum = $bahan['stok_tersedia'];
        $stok_sesudah = $stok_sebelum - $jumlah_pakai;
        
        // Update stok bahan
        $sql_update = "UPDATE bahan_baku SET stok_tersedia = ? WHERE id = ?";
        $result_update = execute($sql_update, [$stok_sesudah, $bahan['bahan_id']]);
        
        if (!$result_update['success']) {
            throw new Exception("Gagal update stok bahan: " . $bahan['nama_bahan']);
        }
        
        // Catat stock movement
        $total_nilai = $jumlah_pakai * $bahan['harga_beli_per_satuan'];
        $satuan_movement = $bahan['satuan_bahan'];
        
        if (empty($satuan_movement)) {
            throw new Exception("Satuan bahan '{$bahan['nama_bahan']}' tidak valid!");
        }
        
        $keterangan = "Penjualan - Menu ID: $menu_id (x$jumlah_porsi porsi)";
        
        $sql_movement = "INSERT INTO stock_movement 
            (bahan_id, jenis_pergerakan, jumlah, satuan, harga_per_satuan, total_nilai, 
            stok_sebelum, stok_sesudah, referensi_type, referensi_id, keterangan, user_id) 
            VALUES (?, 'keluar', ?, ?, ?, ?, ?, ?, 'penjualan', ?, ?, ?)";
        
        $result_movement = execute($sql_movement, [
            $bahan['bahan_id'], 
            $jumlah_pakai, 
            $satuan_movement,
            $bahan['harga_beli_per_satuan'], 
            $total_nilai,
            $stok_sebelum, 
            $stok_sesudah, 
            $transaksi_id, 
            $keterangan, 
            $_SESSION['user_id']
        ]);
        
        if (!$result_movement['success']) {
            throw new Exception("Gagal catat stock movement: " . $bahan['nama_bahan']);
        }
    }
}

/**
 * CATAT JURNAL AKUNTANSI (PENGGANTI kas_umum)
 * Otomatis insert ke tabel transaksi untuk laporan keuangan
 * 
 * PARAMETER ke-4 ($jumlah_pendapatan):
 * - TUNAI: Menggunakan total_harga (karena ada kembalian, pendapatan = harga menu)
 * - NON-TUNAI: Menggunakan uang_bayar (karena pendapatan = uang yang diterima)
 */
function catatJurnalAkuntansi($transaksi_id, $no_transaksi, $metode_pembayaran, $jumlah_pendapatan, $total_modal) {
    // 1. Tentukan akun kas berdasarkan metode pembayaran
    $akun_kas = [
        'tunai' => '1.1.01.01',  // Kas Tunai
        'qris'  => '1.1.01.02',  // Kas QRIS
        'gopay' => '1.1.01.03',  // Kas GoPay
        'grab'  => '1.1.01.04'   // Kas Grab
    ];
    
    $rekening_kas = isset($akun_kas[$metode_pembayaran]) ? $akun_kas[$metode_pembayaran] : '1.1.01.01';
    
    // 2. Jurnal Penjualan (Kas Masuk)
    // TUNAI: Pendapatan = total_harga (karena uang bayar bisa lebih, ada kembalian)
    // NON-TUNAI: Pendapatan = uang yang diterima (karena bisa berbeda dari total)
    $sql_penjualan = "INSERT INTO transaksi 
        (tgl_transaksi, rekening_debet, rekening_kredit, keterangan_transaksi, jumlah, id_user)
        VALUES (NOW(), ?, '4.1.01.01', ?, ?, ?)";
    
    $keterangan_penjualan = "Penjualan $no_transaksi - pembayaran " . strtoupper($metode_pembayaran);
    
    $result_penjualan = execute($sql_penjualan, [
        $rekening_kas,
        $keterangan_penjualan,
        $jumlah_pendapatan,  // TUNAI: total_harga | NON-TUNAI: uang_bayar
        $_SESSION['user_id']
    ]);
    
    if (!$result_penjualan['success']) {
        throw new Exception('Gagal catat jurnal penjualan!');
    }
    
    // 3. Jurnal HPP (Harga Pokok Penjualan)
    $sql_hpp = "INSERT INTO transaksi 
        (tgl_transaksi, rekening_debet, rekening_kredit, keterangan_transaksi, jumlah, id_user)
        VALUES (NOW(), '5.1.01.01', '1.2.01.00', ?, ?, ?)";
    
    $keterangan_hpp = "HPP untuk penjualan $no_transaksi";
    
    $result_hpp = execute($sql_hpp, [
        $keterangan_hpp,
        $total_modal,
        $_SESSION['user_id']
    ]);
    
    if (!$result_hpp['success']) {
        throw new Exception('Gagal catat jurnal HPP!');
    }
    
    return true;
}
?>