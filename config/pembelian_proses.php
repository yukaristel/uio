<?php
/**
 * PROSES PEMBELIAN BAHAN + WEIGHTED AVERAGE
 * Step 17/64 (26.6%)
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
 * CREATE - Proses Pembelian Bahan dengan Weighted Average
 */
function createPembelian() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../index.php?page=pembelian_bahan');
        exit;
    }
    
    $bahan_id = intval($_POST['bahan_id']);
    $jumlah_beli = floatval($_POST['jumlah_beli']);
    $harga_beli_satuan = floatval($_POST['harga_beli_satuan']);
    $supplier = trim($_POST['supplier']);
    $tanggal_beli = $_POST['tanggal_beli'];
    
    // Validasi
    if ($bahan_id == 0 || $jumlah_beli <= 0 || $harga_beli_satuan <= 0 || empty($tanggal_beli)) {
        $_SESSION['error'] = 'Semua field harus diisi dengan benar!';
        header('Location: ../index.php?page=pembelian_bahan');
        exit;
    }
    
    // Ambil data bahan
    $bahan = fetchOne("SELECT * FROM bahan_baku WHERE id = ?", [$bahan_id]);
    if (!$bahan) {
        $_SESSION['error'] = 'Bahan baku tidak ditemukan!';
        header('Location: ../index.php?page=pembelian_bahan');
        exit;
    }
    
    $total_harga = $jumlah_beli * $harga_beli_satuan;
    
    // Cek saldo kas cukup
    $saldo_kas = getSaldoKasTerakhir();
    if ($saldo_kas < $total_harga) {
        $_SESSION['error'] = 'Saldo kas tidak cukup! Saldo: ' . formatRupiah($saldo_kas) . ', Dibutuhkan: ' . formatRupiah($total_harga);
        header('Location: ../index.php?page=pembelian_bahan');
        exit;
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
            $bahan_id, $jumlah_beli, $harga_beli_satuan, $total_harga, 
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
        $nilai_baru = $jumlah_beli * $harga_beli_satuan;
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
        
        $keterangan = "Pembelian dari " . ($supplier ?: 'Supplier');
        $result_movement = execute($sql_movement, [
            $bahan_id, $jumlah_beli, $bahan['satuan'], $harga_beli_satuan, $total_harga,
            $stok_lama, $total_stok, $pembelian_id, $keterangan, $_SESSION['user_id']
        ]);
        
        if (!$result_movement['success']) {
            throw new Exception('Gagal catat stock movement');
        }
        
        // 5. Catat kas keluar OTOMATIS
        $no_kas = generateNoKas();
        $saldo_sebelum = $saldo_kas;
        $saldo_sesudah = $saldo_sebelum - $total_harga;
        
        $sql_kas = "INSERT INTO kas_umum 
            (no_transaksi_kas, tanggal_transaksi, jenis_transaksi, kategori, nominal, 
            saldo_sebelum, saldo_sesudah, referensi_type, referensi_id, keterangan, user_id) 
            VALUES (?, NOW(), 'keluar', 'pembelian_bahan', ?, ?, ?, 'pembelian', ?, ?, ?)";
        
        $keterangan_kas = "Pembelian " . $bahan['nama_bahan'] . " (" . $jumlah_beli . " " . $bahan['satuan'] . ")";
        $result_kas = execute($sql_kas, [
            $no_kas, $total_harga, $saldo_sebelum, $saldo_sesudah, 
            $pembelian_id, $keterangan_kas, $_SESSION['user_id']
        ]);
        
        if (!$result_kas['success']) {
            throw new Exception('Gagal catat kas');
        }
        
        // 6. Update saldo_kas harian
        updateSaldoKasHarian('keluar', $total_harga);
        
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
 * Helper: Get Saldo Kas Terakhir
 */
function getSaldoKasTerakhir() {
    $result = fetchOne("SELECT saldo_sesudah FROM kas_umum ORDER BY created_at DESC, id DESC LIMIT 1");
    return $result ? $result['saldo_sesudah'] : 0;
}

/**
 * Helper: Update Saldo Kas Harian
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
        $saldo_kemarin = fetchOne("SELECT saldo_akhir FROM saldo_kas WHERE tanggal = ? ORDER BY tanggal DESC LIMIT 1", [$kemarin]);
        $saldo_awal = $saldo_kemarin ? $saldo_kemarin['saldo_akhir'] : getSaldoKasTerakhir();
        
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