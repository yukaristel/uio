<?php
/**
 * PROSES KAS UMUM (Manual Transaction & Rekonsiliasi)
 * Step 24/64 (37.5%)
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
        createTransaksiKas();
        break;
    case 'rekonsiliasi':
        rekonsiliasi();
        break;
    default:
        header('Location: ../index.php?page=list_transaksi_kas');
        exit;
}

/**
 * CREATE - Tambah Transaksi Kas Manual
 * (Untuk gaji, operasional, investasi, dll)
 */
function createTransaksiKas() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../index.php?page=tambah_transaksi_kas');
        exit;
    }
    
    $jenis_transaksi = $_POST['jenis_transaksi'];
    $kategori = $_POST['kategori'];
    $nominal = floatval($_POST['nominal']);
    $keterangan = trim($_POST['keterangan']);
    $tanggal_transaksi = $_POST['tanggal_transaksi'] . ' ' . date('H:i:s');
    
    // Validasi
    if (empty($jenis_transaksi) || empty($kategori) || $nominal <= 0) {
        $_SESSION['error'] = 'Semua field harus diisi dengan benar!';
        header('Location: ../index.php?page=tambah_transaksi_kas');
        exit;
    }
    
    // Validasi kategori manual
    $kategori_manual = ['gaji', 'operasional', 'investasi', 'lainnya'];
    if (!in_array($kategori, $kategori_manual)) {
        $_SESSION['error'] = 'Kategori tidak valid untuk transaksi manual!';
        header('Location: ../index.php?page=tambah_transaksi_kas');
        exit;
    }
    
    // Get saldo terakhir
    $saldo_sebelum = getSaldoKasTerakhir();
    
    // Validasi saldo cukup untuk pengeluaran
    if ($jenis_transaksi == 'keluar') {
        if ($saldo_sebelum < $nominal) {
            $_SESSION['error'] = 'Saldo kas tidak cukup! Saldo: ' . formatRupiah($saldo_sebelum) . 
                               ', Dibutuhkan: ' . formatRupiah($nominal);
            header('Location: ../index.php?page=tambah_transaksi_kas');
            exit;
        }
    }
    
    // Hitung saldo sesudah
    if ($jenis_transaksi == 'masuk') {
        $saldo_sesudah = $saldo_sebelum + $nominal;
    } else {
        $saldo_sesudah = $saldo_sebelum - $nominal;
    }
    
    // START TRANSACTION
    $conn = getConnection();
    try {
        $conn->begin_transaction();
        
        // Generate nomor kas
        $no_kas = generateNoKas();
        
        // Insert kas_umum
        $sql = "INSERT INTO kas_umum 
            (no_transaksi_kas, tanggal_transaksi, jenis_transaksi, kategori, nominal, 
            saldo_sebelum, saldo_sesudah, referensi_type, keterangan, user_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'manual', ?, ?)";
        
        $result = execute($sql, [
            $no_kas, $tanggal_transaksi, $jenis_transaksi, $kategori, $nominal,
            $saldo_sebelum, $saldo_sesudah, $keterangan, $_SESSION['user_id']
        ]);
        
        if (!$result['success']) {
            throw new Exception('Gagal insert transaksi kas!');
        }
        
        // Update saldo_kas harian
        updateSaldoKasHarian($jenis_transaksi, $nominal);
        
        // COMMIT
        $conn->commit();
        
        $_SESSION['success'] = 'Transaksi kas berhasil dicatat! No: ' . $no_kas . 
                               ', Saldo: ' . formatRupiah($saldo_sesudah);
        header('Location: ../index.php?page=list_transaksi_kas');
        
    } catch (Exception $e) {
        // ROLLBACK
        $conn->rollback();
        $_SESSION['error'] = 'Gagal catat transaksi kas: ' . $e->getMessage();
        header('Location: ../index.php?page=tambah_transaksi_kas');
    }
    
    exit;
}

/**
 * REKONSILIASI - Sesuaikan Saldo Kas dengan Kas Fisik
 */
function rekonsiliasi() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../index.php?page=rekonsiliasi_kas');
        exit;
    }
    
    $saldo_fisik = floatval($_POST['saldo_fisik']);
    $keterangan = trim($_POST['keterangan']);
    
    // Validasi
    if ($saldo_fisik < 0) {
        $_SESSION['error'] = 'Saldo fisik tidak valid!';
        header('Location: ../index.php?page=rekonsiliasi_kas');
        exit;
    }
    
    // Get saldo sistem
    $saldo_sistem = getSaldoKasTerakhir();
    
    // Hitung selisih
    $selisih = $saldo_fisik - $saldo_sistem;
    
    // Jika tidak ada selisih
    if ($selisih == 0) {
        $_SESSION['success'] = 'Saldo kas sudah sesuai! Tidak ada selisih.';
        header('Location: ../index.php?page=rekonsiliasi_kas');
        exit;
    }
    
    // START TRANSACTION
    $conn = getConnection();
    try {
        $conn->begin_transaction();
        
        // Generate nomor kas
        $no_kas = generateNoKas();
        
        // Tentukan jenis transaksi
        if ($selisih > 0) {
            // Kas fisik lebih besar (ada pemasukan yang tidak tercatat)
            $jenis_transaksi = 'masuk';
            $kategori = 'lainnya';
            $nominal = abs($selisih);
            $keterangan_full = "Rekonsiliasi Kas - Selisih lebih: " . formatRupiah($selisih) . ". " . $keterangan;
        } else {
            // Kas fisik lebih kecil (ada pengeluaran yang tidak tercatat / kehilangan)
            $jenis_transaksi = 'keluar';
            $kategori = 'lainnya';
            $nominal = abs($selisih);
            $keterangan_full = "Rekonsiliasi Kas - Selisih kurang: " . formatRupiah(abs($selisih)) . ". " . $keterangan;
        }
        
        // Insert transaksi adjustment
        $sql = "INSERT INTO kas_umum 
            (no_transaksi_kas, tanggal_transaksi, jenis_transaksi, kategori, nominal, 
            saldo_sebelum, saldo_sesudah, referensi_type, keterangan, user_id) 
            VALUES (?, NOW(), ?, ?, ?, ?, ?, 'manual', ?, ?)";
        
        $result = execute($sql, [
            $no_kas, $jenis_transaksi, $kategori, $nominal,
            $saldo_sistem, $saldo_fisik, $keterangan_full, $_SESSION['user_id']
        ]);
        
        if (!$result['success']) {
            throw new Exception('Gagal insert transaksi adjustment!');
        }
        
        // Update saldo_kas harian
        updateSaldoKasHarian($jenis_transaksi, $nominal);
        
        // COMMIT
        $conn->commit();
        
        if ($selisih > 0) {
            $_SESSION['success'] = 'Rekonsiliasi berhasil! Selisih lebih: ' . formatRupiah($selisih) . 
                                   '. Saldo disesuaikan ke: ' . formatRupiah($saldo_fisik);
        } else {
            $_SESSION['warning'] = 'Rekonsiliasi berhasil! Selisih kurang: ' . formatRupiah(abs($selisih)) . 
                                   '. Saldo disesuaikan ke: ' . formatRupiah($saldo_fisik);
        }
        
        header('Location: ../index.php?page=rekonsiliasi_kas');
        
    } catch (Exception $e) {
        // ROLLBACK
        $conn->rollback();
        $_SESSION['error'] = 'Gagal rekonsiliasi: ' . $e->getMessage();
        header('Location: ../index.php?page=rekonsiliasi_kas');
    }
    
    exit;
}

/**
 * HELPER: Get Saldo Kas Terakhir
 */
function getSaldoKasTerakhir() {
    $result = fetchOne("SELECT saldo_sesudah FROM kas_umum ORDER BY created_at DESC, id DESC LIMIT 1");
    return $result ? $result['saldo_sesudah'] : 0;
}

/**
 * HELPER: Update Saldo Kas Harian
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
        // Insert new
        $kemarin = date('Y-m-d', strtotime('-1 day'));
        $saldo_kemarin = fetchOne("SELECT saldo_akhir FROM saldo_kas WHERE tanggal <= ? ORDER BY tanggal DESC LIMIT 1", [$kemarin]);
        
        $saldo_awal = $saldo_kemarin ? $saldo_kemarin['saldo_akhir'] : getSaldoKasTerakhir();
        if ($jenis == 'masuk') {
            $saldo_awal = $saldo_awal - $nominal;
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