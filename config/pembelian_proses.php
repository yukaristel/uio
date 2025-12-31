<?php
/**
 * PROSES PEMBELIAN BAHAN + WEIGHTED AVERAGE + JURNAL COA
 * Modified: Menggunakan tabel transaksi dengan COA
 */

session_start();
require_once 'database.php';

// Cek login dan akses admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    $_SESSION['error'] = 'Anda tidak memiliki akses!';
    header('Location: ../index.php');
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'create':
        createPembelian();
        break;
    default:
        header('Location: ../index.php?page=pembelian_bahan');
        exit;
}

/**
 * CREATE - Proses Pembelian Bahan dengan Weighted Average + Jurnal COA
 */
function createPembelian() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../index.php?page=pembelian_bahan');
        exit;
    }
    
    $bahan_id = intval($_POST['bahan_id']);
    $jumlah_beli = floatval($_POST['jumlah_beli']);
    $total_harga_beli = floatval($_POST['total_harga_beli']);
    $supplier = trim($_POST['supplier']);
    $tanggal_beli = $_POST['tanggal_beli'];
    $metode_bayar = isset($_POST['metode_bayar']) ? $_POST['metode_bayar'] : 'tunai'; // tunai/bank/utang
    
    // Validasi
    if ($bahan_id == 0 || $jumlah_beli <= 0 || $total_harga_beli <= 0 || empty($tanggal_beli)) {
        $_SESSION['error'] = 'Semua field harus diisi dengan benar!';
        header('Location: ../index.php?page=pembelian_bahan');
        exit;
    }
    
    // Hitung harga per satuan dari total
    $harga_beli_satuan = $total_harga_beli / $jumlah_beli;
    
    // Ambil data bahan
    $bahan = fetchOne("SELECT * FROM bahan_baku WHERE id = ?", [$bahan_id]);
    if (!$bahan) {
        $_SESSION['error'] = 'Bahan baku tidak ditemukan!';
        header('Location: ../index.php?page=pembelian_bahan');
        exit;
    }
    
    // Tentukan akun kredit berdasarkan metode bayar
    $akun_kredit = '';
    $keterangan_metode = '';
    switch ($metode_bayar) {
        case 'tunai':
            $akun_kredit = '1.1.01.01'; // Kas Tunai
            $keterangan_metode = 'Tunai';
            break;
        case 'bank':
            $akun_kredit = '1.1.02.01'; // Bank Mandiri
            $keterangan_metode = 'Transfer Bank';
            break;
        case 'utang':
            $akun_kredit = '2.1.01.01'; // Utang Supplier
            $keterangan_metode = 'Utang';
            break;
        default:
            $akun_kredit = '1.1.01.01';
            $keterangan_metode = 'Tunai';
    }
    
    // Jika bayar tunai/bank, cek saldo
    if ($metode_bayar != 'utang') {
        $saldo = getSaldoAkun($akun_kredit);
        if ($saldo < $total_harga_beli) {
            $_SESSION['error'] = 'Saldo tidak cukup! Saldo: ' . formatRupiah($saldo) . ', Dibutuhkan: ' . formatRupiah($total_harga_beli);
            header('Location: ../index.php?page=pembelian_bahan');
            exit;
        }
    }
    
    // START TRANSACTION
    try {
        $conn = getConnection();
        $conn->begin_transaction();
        
        // 1. Insert pembelian
        $sql_pembelian = "INSERT INTO pembelian_bahan 
            (bahan_id, jumlah_beli, harga_beli_satuan, total_harga, supplier, tanggal_beli, user_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $result = execute($sql_pembelian, [
            $bahan_id, $jumlah_beli, $harga_beli_satuan, $total_harga_beli, 
            $supplier, $tanggal_beli, $_SESSION['user_id']
        ]);
        
        if (!$result['success']) {
            throw new Exception('Gagal insert pembelian');
        }
        
        $pembelian_id = $result['insert_id'];
        
        // 2. Hitung Weighted Average
        $stok_lama = $bahan['stok_tersedia'];
        $harga_lama = $bahan['harga_beli_per_satuan'];
        
        $nilai_lama = $stok_lama * $harga_lama;
        $nilai_baru = $total_harga_beli;
        $total_nilai = $nilai_lama + $nilai_baru;
        $total_stok = $stok_lama + $jumlah_beli;
        
        // Hitung harga rata-rata baru
        if ($total_stok > 0) {
            $harga_rata_baru = $total_nilai / $total_stok;
        } else {
            $harga_rata_baru = $harga_beli_satuan;
        }
        
        // 3. Update bahan_baku (stok & harga weighted average)
        $sql_update_bahan = "UPDATE bahan_baku 
            SET stok_tersedia = ?, harga_beli_per_satuan = ? 
            WHERE id = ?";
        
        $result_update = execute($sql_update_bahan, [$total_stok, $harga_rata_baru, $bahan_id]);
        if (!$result_update['success']) {
            throw new Exception('Gagal update bahan');
        }
        
        // 4. Catat stock movement
        $sql_movement = "INSERT INTO stock_movement 
            (bahan_id, jenis_pergerakan, jumlah, satuan, harga_per_satuan, total_nilai, 
            stok_sebelum, stok_sesudah, referensi_type, referensi_id, keterangan, user_id) 
            VALUES (?, 'masuk', ?, ?, ?, ?, ?, ?, 'pembelian', ?, ?, ?)";
        
        $keterangan_movement = "Pembelian dari " . ($supplier ?: 'Supplier');
        $result_movement = execute($sql_movement, [
            $bahan_id, $jumlah_beli, $bahan['satuan'], $harga_beli_satuan, $total_harga_beli,
            $stok_lama, $total_stok, $pembelian_id, $keterangan_movement, $_SESSION['user_id']
        ]);
        
        if (!$result_movement['success']) {
            throw new Exception('Gagal catat stock movement');
        }
        
        // 5. JURNAL: Catat transaksi ke tabel transaksi (menggunakan COA)
        // Debet: Persediaan Bahan Baku (1.2.01.00)
        // Kredit: Kas/Bank/Utang (tergantung metode bayar)
        
        $keterangan_jurnal = "Pembelian " . $bahan['nama_bahan'] . " (" . 
                            number_format($jumlah_beli, 2) . " " . $bahan['satuan'] . ") - " . 
                            $keterangan_metode . 
                            ($supplier ? " dari " . $supplier : "");
        
        $sql_transaksi = "INSERT INTO transaksi 
            (tgl_transaksi, rekening_debet, rekening_kredit, keterangan_transaksi, jumlah, id_user, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $result_transaksi = execute($sql_transaksi, [
            $tanggal_beli,
            '1.2.01.00',  // Debet: Persediaan Bahan Baku
            $akun_kredit,  // Kredit: Kas/Bank/Utang
            $keterangan_jurnal,
            $total_harga_beli,
            $_SESSION['user_id']
        ]);
        
        if (!$result_transaksi['success']) {
            throw new Exception('Gagal catat jurnal transaksi');
        }
        
        // COMMIT
        $conn->commit();
        
        $_SESSION['success'] = 'Pembelian berhasil! Stok: ' . number_format($total_stok, 2) . ' ' . $bahan['satuan'] . 
                               ', Harga baru: ' . formatRupiah($harga_rata_baru) . '/' . $bahan['satuan'];
        header('Location: ../index.php?page=list_bahan');
        
    } catch (Exception $e) {
        // ROLLBACK jika error
        $conn->rollback();
        $_SESSION['error'] = 'Gagal proses pembelian: ' . $e->getMessage();
        header('Location: ../index.php?page=pembelian_bahan');
    }
    
    exit;
}

/**
 * Helper: Get Saldo Akun dari Transaksi
 */
function getSaldoAkun($kode_akun) {
    // Cek jenis mutasi akun
    $akun = fetchOne("SELECT jenis_mutasi FROM chart_of_accounts WHERE kode_akun = ?", [$kode_akun]);
    if (!$akun) return 0;
    
    $jenis_mutasi = $akun['jenis_mutasi'];
    
    // Hitung saldo berdasarkan jenis mutasi
    if ($jenis_mutasi == 'Debet') {
        // Saldo = Total Debet - Total Kredit
        $total_debet = fetchOne("SELECT COALESCE(SUM(jumlah), 0) as total FROM transaksi WHERE rekening_debet = ?", [$kode_akun]);
        $total_kredit = fetchOne("SELECT COALESCE(SUM(jumlah), 0) as total FROM transaksi WHERE rekening_kredit = ?", [$kode_akun]);
        
        $saldo = $total_debet['total'] - $total_kredit['total'];
    } else {
        // Kredit: Saldo = Total Kredit - Total Debet
        $total_kredit = fetchOne("SELECT COALESCE(SUM(jumlah), 0) as total FROM transaksi WHERE rekening_kredit = ?", [$kode_akun]);
        $total_debet = fetchOne("SELECT COALESCE(SUM(jumlah), 0) as total FROM transaksi WHERE rekening_debet = ?", [$kode_akun]);
        
        $saldo = $total_kredit['total'] - $total_debet['total'];
    }
    
    return $saldo;
}
?>