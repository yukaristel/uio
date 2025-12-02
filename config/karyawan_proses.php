<?php
/**
 * PROSES CRUD KARYAWAN
 * Step 14/64 (21.9%)
 */

session_start();
require_once 'database.php';

// Cek apakah user sudah login dan admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    $_SESSION['error'] = 'Anda tidak memiliki akses!';
    header('Location: ../index.php');
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'create':
        createKaryawan();
        break;
    case 'update':
        updateKaryawan();
        break;
    case 'delete':
        deleteKaryawan();
        break;
    default:
        header('Location: ../index.php?page=list_karyawan');
        exit;
}

/**
 * CREATE - Tambah Karyawan
 */
function createKaryawan() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../index.php?page=tambah_karyawan');
        exit;
    }
    
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $role = $_POST['role'];
    
    // Validasi
    if (empty($username) || empty($password) || empty($nama_lengkap) || empty($role)) {
        $_SESSION['error'] = 'Semua field harus diisi!';
        header('Location: ../index.php?page=tambah_karyawan');
        exit;
    }
    
    // Cek username sudah ada
    $cek = fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
    if ($cek) {
        $_SESSION['error'] = 'Username sudah digunakan!';
        header('Location: ../index.php?page=tambah_karyawan');
        exit;
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert
    $sql = "INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)";
    $result = execute($sql, [$username, $password_hash, $nama_lengkap, $role]);
    
    if ($result['success']) {
        $_SESSION['success'] = 'Karyawan berhasil ditambahkan!';
        header('Location: ../index.php?page=list_karyawan');
    } else {
        $_SESSION['error'] = 'Gagal menambahkan karyawan!';
        header('Location: ../index.php?page=tambah_karyawan');
    }
    exit;
}

/**
 * UPDATE - Edit Karyawan
 */
function updateKaryawan() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../index.php?page=list_karyawan');
        exit;
    }
    
    $id = $_POST['id'];
    $username = trim($_POST['username']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $role = $_POST['role'];
    $password = trim($_POST['password']);
    
    // Validasi
    if (empty($username) || empty($nama_lengkap) || empty($role)) {
        $_SESSION['error'] = 'Username, nama lengkap, dan role harus diisi!';
        header('Location: ../index.php?page=edit_karyawan&id=' . $id);
        exit;
    }
    
    // Cek username sudah digunakan user lain
    $cek = fetchOne("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $id]);
    if ($cek) {
        $_SESSION['error'] = 'Username sudah digunakan user lain!';
        header('Location: ../index.php?page=edit_karyawan&id=' . $id);
        exit;
    }
    
    // Jika password diisi, update dengan password baru
    if (!empty($password)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET username = ?, password = ?, nama_lengkap = ?, role = ? WHERE id = ?";
        $result = execute($sql, [$username, $password_hash, $nama_lengkap, $role, $id]);
    } else {
        // Update tanpa ubah password
        $sql = "UPDATE users SET username = ?, nama_lengkap = ?, role = ? WHERE id = ?";
        $result = execute($sql, [$username, $nama_lengkap, $role, $id]);
    }
    
    if ($result['success']) {
        $_SESSION['success'] = 'Data karyawan berhasil diupdate!';
        header('Location: ../index.php?page=list_karyawan');
    } else {
        $_SESSION['error'] = 'Gagal mengupdate data karyawan!';
        header('Location: ../index.php?page=edit_karyawan&id=' . $id);
    }
    exit;
}

/**
 * DELETE - Hapus Karyawan
 */
function deleteKaryawan() {
    $id = isset($_GET['id']) ? $_GET['id'] : 0;
    
    if ($id == 0) {
        $_SESSION['error'] = 'ID karyawan tidak valid!';
        header('Location: ../index.php?page=list_karyawan');
        exit;
    }
    
    // Tidak boleh hapus diri sendiri
    if ($id == $_SESSION['user_id']) {
        $_SESSION['error'] = 'Anda tidak bisa menghapus akun Anda sendiri!';
        header('Location: ../index.php?page=list_karyawan');
        exit;
    }
    
    // Cek apakah karyawan punya transaksi
    $cek_transaksi = fetchOne("SELECT COUNT(*) as total FROM transaksi_penjualan WHERE user_id = ?", [$id]);
    if ($cek_transaksi['total'] > 0) {
        $_SESSION['warning'] = 'Karyawan ini memiliki history transaksi. Sebaiknya tidak dihapus atau ganti status menjadi non-aktif.';
        header('Location: ../index.php?page=list_karyawan');
        exit;
    }
    
    $sql = "DELETE FROM users WHERE id = ?";
    $result = execute($sql, [$id]);
    
    if ($result['success']) {
        $_SESSION['success'] = 'Karyawan berhasil dihapus!';
    } else {
        $_SESSION['error'] = 'Gagal menghapus karyawan!';
    }
    
    header('Location: ../index.php?page=list_karyawan');
    exit;
}
?>