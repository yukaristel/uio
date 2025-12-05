<?php
/**
 * PROSES CRUD BAHAN BAKU (Harga Total) - FIXED
 * Step 16/64 (25.0%)
 * 
 * PERUBAHAN:
 * - Fix bug variabel $bahan_id di createBahan()
 * - Fix query WHERE clause di deleteBahan() (ganti 'id' ke 'bahan_id')
 * - Tambahkan pengecekan stock_opname di deleteBahan()
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
    $total_harga_awal = floatval($_POST['total_harga_awal']);
    
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
        // FIX: Gunakan variabel yang benar dari insert_id
        $bahan_id = $result['insert_id'];
        
        // Jika stok_tersedia > 0, catat stock movement
        if ($stok_tersedia > 0) {
            $sql_movement = "INSERT INTO stock_movement 
                (bahan_id, jenis_pergerakan, jumlah, satuan, harga_per_satuan, total_nilai, 
                stok_sebelum, stok_sesudah, referensi_type, keterangan, user_id) 
                VALUES (?, 'masuk', ?, ?, ?, ?, 0, ?, 'manual', 'Stok awal bahan baku', ?)";
            
            execute($sql_movement, [
                $bahan_id, // FIX: gunakan $bahan_id bukan $id
                $stok_tersedia, 
                $satuan, 
                $harga_beli_per_satuan, 
                $total_harga_awal, 
                $stok_tersedia, 
                $_SESSION['user_id']
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
    
    // FIX: Cek apakah bahan digunakan di resep (ganti WHERE id ke bahan_id)
    $cek_resep = fetchOne("SELECT COUNT(*) as total FROM resep_menu WHERE bahan_id = ?", [$id]);
    if ($cek_resep && $cek_resep['total'] > 0) {
        $_SESSION['error'] = 'Bahan ini digunakan di ' . $cek_resep['total'] . ' resep menu! Tidak dapat dihapus.';
        header('Location: ../index.php?page=list_bahan');
        exit;
    }
    
    // FIX: Cek apakah ada history pembelian (ganti WHERE id ke bahan_id)
    $cek_pembelian = fetchOne("SELECT COUNT(*) as total FROM pembelian_bahan WHERE bahan_id = ?", [$id]);
    if ($cek_pembelian && $cek_pembelian['total'] > 0) {
        $_SESSION['error'] = 'Bahan ini memiliki ' . $cek_pembelian['total'] . ' history pembelian! Tidak dapat dihapus.';
        header('Location: ../index.php?page=list_bahan');
        exit;
    }
    
    // FIX: Cek apakah ada stock movement (ganti WHERE id ke bahan_id)
    $cek_movement = fetchOne("SELECT COUNT(*) as total FROM stock_movement WHERE bahan_id = ?", [$id]);
    if ($cek_movement && $cek_movement['total'] > 0) {
        $_SESSION['error'] = 'Bahan ini memiliki ' . $cek_movement['total'] . ' history pergerakan stok! Tidak dapat dihapus.';
        header('Location: ../index.php?page=list_bahan');
        exit;
    }
    
    // FIX: Tambahkan pengecekan stock opname
    $cek_opname = fetchOne("SELECT COUNT(*) as total FROM stock_opname WHERE bahan_id = ?", [$id]);
    if ($cek_opname && $cek_opname['total'] > 0) {
        $_SESSION['error'] = 'Bahan ini memiliki ' . $cek_opname['total'] . ' history stock opname! Tidak dapat dihapus.';
        header('Location: ../index.php?page=list_bahan');
        exit;
    }
    
    // Jika lolos semua pengecekan, hapus bahan
    try {
        $sql = "DELETE FROM bahan_baku WHERE id = ?";
        $result = execute($sql, [$id]);
        
        if ($result['success']) {
            $_SESSION['success'] = 'Bahan baku berhasil dihapus!';
        } else {
            $_SESSION['error'] = 'Gagal menghapus bahan baku!';
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Tidak dapat menghapus bahan: masih ada data terkait di sistem!';
    }
    
    header('Location: ../index.php?page=list_bahan');
    exit;
}
?>