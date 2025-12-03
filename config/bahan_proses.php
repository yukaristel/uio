<?php
/**
 * PROSES CRUD BAHAN BAKU (Harga Total)
 * Step 16/64 (25.0%)
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
        createBahan();
        break;
    case 'update':
        updateBahan();
        break;
    case 'delete':
        deleteBahan();
        break;
    default:
        header('Location: ../index.php?page=list_bahan');
        exit;
}

/**
 * CREATE - Tambah Bahan Baku
 */
function createBahan() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../index.php?page=tambah_bahan');
        exit;
    }
    
    $kode_bahan = strtoupper(trim($_POST['kode_bahan']));
    $nama_bahan = trim($_POST['nama_bahan']);
    $satuan = $_POST['satuan'];
    $stok_tersedia = floatval($_POST['stok_tersedia']);
    $stok_minimum = floatval($_POST['stok_minimum']);
    $total_harga_awal = floatval($_POST['total_harga_awal']); // UBAH: input total harga
    
    // Validasi
    if (empty($kode_bahan) || empty($nama_bahan) || empty($satuan)) {
        $_SESSION['error'] = 'Kode bahan, nama bahan, dan satuan harus diisi!';
        header('Location: ../index.php?page=tambah_bahan');
        exit;
    }
    
    // Validasi: Jika ada stok awal, harus ada harga
    if ($stok_tersedia > 0 && $total_harga_awal <= 0) {
        $_SESSION['error'] = 'Jika stok awal > 0, total harga harus diisi!';
        header('Location: ../index.php?page=tambah_bahan');
        exit;
    }
    
    // Hitung harga per satuan
    $harga_beli_per_satuan = $stok_tersedia > 0 ? $total_harga_awal / $stok_tersedia : 0;
    
    // Cek kode duplikat
    $cek = fetchOne("SELECT id FROM bahan_baku WHERE kode_bahan = ?", [$kode_bahan]);
    if ($cek) {
        $_SESSION['error'] = 'Kode bahan sudah digunakan!';
        header('Location: ../index.php?page=tambah_bahan');
        exit;
    }
    
    $sql = "INSERT INTO bahan_baku (kode_bahan, nama_bahan, satuan, stok_tersedia, stok_minimum, harga_beli_per_satuan) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $result = execute($sql, [$kode_bahan, $nama_bahan, $satuan, $stok_tersedia, $stok_minimum, $harga_beli_per_satuan]);
    
    if ($result['success']) {
        // Jika stok_tersedia > 0, catat stock movement
        if ($stok_tersedia > 0) {
            $bahan_id = $result['insert_id'];
            
            $sql_movement = "INSERT INTO stock_movement 
                (bahan_id, jenis_pergerakan, jumlah, satuan, harga_per_satuan, total_nilai, 
                stok_sebelum, stok_sesudah, referensi_type, keterangan, user_id) 
                VALUES (?, 'masuk', ?, ?, ?, ?, 0, ?, 'manual', 'Stok awal bahan baku', ?)";
            
            execute($sql_movement, [
                $bahan_id, $stok_tersedia, $satuan, $harga_beli_per_satuan, 
                $total_harga_awal, $stok_tersedia, $_SESSION['user_id']
            ]);
        }
        
        $_SESSION['success'] = 'Bahan baku berhasil ditambahkan!';
        header('Location: ../index.php?page=list_bahan');
    } else {
        $_SESSION['error'] = 'Gagal menambahkan bahan baku!';
        header('Location: ../index.php?page=tambah_bahan');
    }
    exit;
}

/**
 * UPDATE - Edit Bahan Baku
 */
function updateBahan() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../index.php?page=list_bahan');
        exit;
    }
    
    $id = $_POST['id'];
    $kode_bahan = strtoupper(trim($_POST['kode_bahan']));
    $nama_bahan = trim($_POST['nama_bahan']);
    $satuan = $_POST['satuan'];
    $stok_minimum = floatval($_POST['stok_minimum']);
    // Harga tidak diupdate di form edit, hanya via pembelian
    
    // Validasi
    if (empty($kode_bahan) || empty($nama_bahan) || empty($satuan)) {
        $_SESSION['error'] = 'Kode bahan, nama bahan, dan satuan harus diisi!';
        header('Location: ../index.php?page=edit_bahan&id=' . $id);
        exit;
    }
    
    // Cek kode duplikat (selain id sendiri)
    $cek = fetchOne("SELECT id FROM bahan_baku WHERE kode_bahan = ? AND id != ?", [$kode_bahan, $id]);
    if ($cek) {
        $_SESSION['error'] = 'Kode bahan sudah digunakan!';
        header('Location: ../index.php?page=edit_bahan&id=' . $id);
        exit;
    }
    
    // Note: stok_tersedia dan harga tidak diupdate di sini, hanya via pembelian/transaksi/opname
    $sql = "UPDATE bahan_baku 
            SET kode_bahan = ?, nama_bahan = ?, satuan = ?, stok_minimum = ? 
            WHERE id = ?";
    $result = execute($sql, [$kode_bahan, $nama_bahan, $satuan, $stok_minimum, $id]);
    
    if ($result['success']) {
        $_SESSION['success'] = 'Bahan baku berhasil diupdate!';
        header('Location: ../index.php?page=list_bahan');
    } else {
        $_SESSION['error'] = 'Gagal mengupdate bahan baku!';
        header('Location: ../index.php?page=edit_bahan&id=' . $id);
    }
    exit;
}

/**
 * DELETE - Hapus Bahan Baku
 */
function deleteBahan() {
    $id = isset($_GET['id']) ? $_GET['id'] : 0;
    
    if ($id == 0) {
        $_SESSION['error'] = 'ID bahan tidak valid!';
        header('Location: ../index.php?page=list_bahan');
        exit;
    }
    
    // Cek apakah bahan digunakan di resep
    $cek_resep = fetchOne("SELECT COUNT(*) as total FROM resep_menu WHERE bahan_id = ?", [$id]);
    if ($cek_resep['total'] > 0) {
        $_SESSION['error'] = 'Bahan ini digunakan di resep menu! Tidak dapat dihapus.';
        header('Location: ../index.php?page=list_bahan');
        exit;
    }
    
    // Cek apakah ada history pembelian
    $cek_pembelian = fetchOne("SELECT COUNT(*) as total FROM pembelian_bahan WHERE bahan_id = ?", [$id]);
    if ($cek_pembelian['total'] > 0) {
        $_SESSION['warning'] = 'Bahan ini memiliki history pembelian. Sebaiknya tidak dihapus.';
        header('Location: ../index.php?page=list_bahan');
        exit;
    }
    
    $sql = "DELETE FROM bahan_baku WHERE id = ?";
    $result = execute($sql, [$id]);
    
    if ($result['success']) {
        $_SESSION['success'] = 'Bahan baku berhasil dihapus!';
    } else {
        $_SESSION['error'] = 'Gagal menghapus bahan baku!';
    }
    
    header('Location: ../index.php?page=list_bahan');
    exit;
}
?>