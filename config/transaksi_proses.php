<?php
/**
 * PROSES TRANSAKSI PENJUALAN + STOCK MOVEMENT + KAS OTOMATIS
 * Step 20/64 (31.3%) - PART 1
 * 
 * FLOW:
 * 1. Validasi stok bahan mencukupi
 * 2. Insert transaksi_penjualan
 * 3. Insert detail_transaksi
 * 4. Update stok bahan (kurangi stok)
 * 5. Catat stock_movement (keluar)
 * 6. Catat kas_umum (masuk)
 * 7. Update saldo_kas harian
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
    $uang_bayar = isset($_POST['uang_bayar']) ? floatval($_POST['uang_bayar']) : 0;
    
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
        
        // Validasi uang bayar (jika tunai)
        if ($metode_pembayaran == 'tunai') {
            if ($uang_bayar < $total_harga) {
                throw new Exception('Uang bayar kurang! Total: ' . formatRupiah($total_harga));
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
        
        // 5. Catat kas masuk OTOMATIS
        $no_kas = generateNoKas();
        $saldo_sebelum = getSaldoKasTerakhir();
        $saldo_sesudah = $saldo_sebelum + $total_harga;
        
        $sql_kas = "INSERT INTO kas_umum 
            (no_transaksi_kas, tanggal_transaksi, jenis_transaksi, kategori, nominal, 
            saldo_sebelum, saldo_sesudah, referensi_type, referensi_id, keterangan, user_id) 
            VALUES (?, NOW(), 'masuk', 'penjualan', ?, ?, ?, 'penjualan', ?, ?, ?)";
        
        $keterangan_kas = "Penjualan - $no_transaksi";
        $result_kas = execute($sql_kas, [
            $no_kas, $total_harga, $saldo_sebelum, $saldo_sesudah,
            $transaksi_id, $keterangan_kas, $_SESSION['user_id']
        ]);
        
        if (!$result_kas['success']) {
            throw new Exception('Gagal catat kas!');
        }
        
        // 6. Update saldo_kas harian
        updateSaldoKasHarian('masuk', $total_harga);
        
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
 * TRANSAKSI PROSES - PART 2 (Helper Functions)
 * Step 21/64 (32.8%)
 */

/**
 * KURANGI STOK BAHAN + CATAT STOCK MOVEMENT
 */
function kurangiStokBahan($transaksi_id, $menu_id, $jumlah_porsi) {
    // Ambil resep menu
    $resep = fetchAll("SELECT r.*, b.* 
                       FROM resep_menu r 
                       JOIN bahan_baku b ON r.bahan_id = b.id 
                       WHERE r.menu_id = ?", [$menu_id]);
    
    foreach ($resep as $bahan) {
        // Konversi jumlah bahan ke satuan database
        $jumlah_konversi = konversiSatuan(
            $bahan['jumlah_bahan'],
            $bahan['satuan'],
            $bahan['satuan_bahan']
        );
        
        // Total yang dipakai
        $jumlah_pakai = $jumlah_konversi * $jumlah_porsi;
        
        // Stok sebelum
        $stok_sebelum = $bahan['stok_tersedia'];
        $stok_sesudah = $stok_sebelum - $jumlah_pakai;
        
        // Update stok bahan
        $sql_update = "UPDATE bahan_baku SET stok_tersedia = ? WHERE id = ?";
        execute($sql_update, [$stok_sesudah, $bahan['id']]);
        
        // Catat stock movement
        $total_nilai = $jumlah_pakai * $bahan['harga_beli_per_satuan'];
        
        $sql_movement = "INSERT INTO stock_movement 
            (bahan_id, jenis_pergerakan, jumlah, satuan, harga_per_satuan, total_nilai, 
            stok_sebelum, stok_sesudah, referensi_type, referensi_id, keterangan, user_id) 
            VALUES (?, 'keluar', ?, ?, ?, ?, ?, ?, 'penjualan', ?, ?, ?)";
        
        $keterangan = "Penjualan - Menu ID: $menu_id (x$jumlah_porsi porsi)";
        
        execute($sql_movement, [
            $bahan['id'], $jumlah_pakai, $bahan['satuan_bahan'], 
            $bahan['harga_beli_per_satuan'], $total_nilai,
            $stok_sebelum, $stok_sesudah, $transaksi_id, 
            $keterangan, $_SESSION['user_id']
        ]);
    }
}

/**
 * GET SALDO KAS TERAKHIR
 */
function getSaldoKasTerakhir() {
    $result = fetchOne("SELECT saldo_sesudah FROM kas_umum ORDER BY created_at DESC, id DESC LIMIT 1");
    
    if (!$result) {
        // Jika belum ada transaksi kas, return 0
        return 0;
    }
    
    return $result['saldo_sesudah'];
}

/**
 * UPDATE SALDO KAS HARIAN
 */
function updateSaldoKasHarian($jenis, $nominal) {
    $today = date('Y-m-d');
    
    // Cek apakah sudah ada record hari ini
    $cek = fetchOne("SELECT * FROM saldo_kas WHERE tanggal = ?", [$today]);
    
    if ($cek) {
        // Update existing
        if ($jenis == 'masuk') {
            $sql = "UPDATE saldo_kas 
                    SET total_masuk = total_masuk + ?, 
                        saldo_akhir = saldo_akhir + ? 
                    WHERE tanggal = ?";
        } else {
            $sql = "UPDATE saldo_kas 
                    SET total_keluar = total_keluar + ?, 
                        saldo_akhir = saldo_akhir - ? 
                    WHERE tanggal = ?";
        }
        execute($sql, [$nominal, $nominal, $today]);
    } else {
        // Insert new - ambil saldo akhir kemarin
        $kemarin = date('Y-m-d', strtotime('-1 day'));
        $saldo_kemarin = fetchOne("SELECT saldo_akhir FROM saldo_kas WHERE tanggal <= ? ORDER BY tanggal DESC LIMIT 1", [$kemarin]);
        
        if (!$saldo_kemarin) {
            // Jika tidak ada data kemarin, ambil dari kas_umum
            $saldo_awal = getSaldoKasTerakhir();
            if ($jenis == 'masuk') {
                $saldo_awal = $saldo_awal - $nominal; // Karena nominal ini akan ditambahkan
            }
        } else {
            $saldo_awal = $saldo_kemarin['saldo_akhir'];
        }
        
        if ($jenis == 'masuk') {
            $total_masuk = $nominal;
            $total_keluar = 0;
            $saldo_akhir = $saldo_awal + $nominal;
        } else {
            $total_masuk = 0;
            $total_keluar = $nominal;
            $saldo_akhir = $saldo_awal - $nominal;
        }
        
        $sql = "INSERT INTO saldo_kas (tanggal, saldo_awal, total_masuk, total_keluar, saldo_akhir) 
                VALUES (?, ?, ?, ?, ?)";
        execute($sql, [$today, $saldo_awal, $total_masuk, $total_keluar, $saldo_akhir]);
    }
}
?>