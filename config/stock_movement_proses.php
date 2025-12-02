<?php
/**
 * PROSES STOCK MOVEMENT MANUAL
 * (Untuk bahan rusak, tumpah, hilang, expired)
 * Step 22/64 (34.4%)
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
        createMovement();
        break;
    default:
        header('Location: ../index.php?page=list_movement');
        exit;
}

/**
 * CREATE - Catat Movement Manual (Rusak/Tumpah/Hilang/Expired)
 */
function createMovement() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../index.php?page=tambah_movement');
        exit;
    }
    
    $bahan_id = intval($_POST['bahan_id']);
    $jenis_pergerakan = $_POST['jenis_pergerakan'];
    $jumlah = floatval($_POST['jumlah']);
    $keterangan = trim($_POST['keterangan']);
    
    // Validasi
    if ($bahan_id == 0 || $jumlah <= 0) {
        $_SESSION['error'] = 'Bahan dan jumlah harus diisi dengan benar!';
        header('Location: ../index.php?page=tambah_movement');
        exit;
    }
    
    // Validasi jenis pergerakan (hanya yang manual)
    $jenis_manual = ['rusak', 'tumpah', 'hilang', 'expired'];
    if (!in_array($jenis_pergerakan, $jenis_manual)) {
        $_SESSION['error'] = 'Jenis pergerakan tidak valid!';
        header('Location: ../index.php?page=tambah_movement');
        exit;
    }
    
    // Ambil data bahan
    $bahan = fetchOne("SELECT * FROM bahan_baku WHERE id = ?", [$bahan_id]);
    if (!$bahan) {
        $_SESSION['error'] = 'Bahan tidak ditemukan!';
        header('Location: ../index.php?page=tambah_movement');
        exit;
    }
    
    // Validasi stok cukup
    if ($bahan['stok_tersedia'] < $jumlah) {
        $_SESSION['error'] = 'Stok tidak cukup! Tersedia: ' . $bahan['stok_tersedia'] . ' ' . $bahan['satuan'];
        header('Location: ../index.php?page=tambah_movement');
        exit;
    }
    
    // START TRANSACTION
    $conn = getConnection();
    try {
        $conn->begin_transaction();
        
        // Hitung nilai kerugian
        $total_nilai = $jumlah * $bahan['harga_beli_per_satuan'];
        
        // Stok baru
        $stok_sebelum = $bahan['stok_tersedia'];
        $stok_sesudah = $stok_sebelum - $jumlah;
        
        // Update stok bahan
        $sql_update = "UPDATE bahan_baku SET stok_tersedia = ? WHERE id = ?";
        $result_update = execute($sql_update, [$stok_sesudah, $bahan_id]);
        
        if (!$result_update['success']) {
            throw new Exception('Gagal update stok bahan!');
        }
        
        // Insert stock movement
        $sql_movement = "INSERT INTO stock_movement 
            (bahan_id, jenis_pergerakan, jumlah, satuan, harga_per_satuan, total_nilai, 
            stok_sebelum, stok_sesudah, referensi_type, keterangan, user_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'manual', ?, ?)";
        
        $result_movement = execute($sql_movement, [
            $bahan_id, $jenis_pergerakan, $jumlah, $bahan['satuan'],
            $bahan['harga_beli_per_satuan'], $total_nilai,
            $stok_sebelum, $stok_sesudah, $keterangan, $_SESSION['user_id']
        ]);
        
        if (!$result_movement['success']) {
            throw new Exception('Gagal catat stock movement!');
        }
        
        // COMMIT
        $conn->commit();
        
        // Alert kerugian
        $_SESSION['warning'] = 'Stock movement berhasil dicatat! Kerugian: ' . formatRupiah($total_nilai) . 
                               ' (' . $jumlah . ' ' . $bahan['satuan'] . ' ' . $bahan['nama_bahan'] . ')';
        header('Location: ../index.php?page=list_movement');
        
    } catch (Exception $e) {
        // ROLLBACK
        $conn->rollback();
        $_SESSION['error'] = 'Gagal catat movement: ' . $e->getMessage();
        header('Location: ../index.php?page=tambah_movement');
    }
    
    exit;
}
?>