<?php
/**
 * PROSES CRUD KATEGORI MENU
 * Step 15/64 (23.4%)
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
        createKategori();
        break;
    case 'update':
        updateKategori();
        break;
    case 'delete':
        deleteKategori();
        break;
    default:
        header('Location: ../index.php?page=list_kategori');
        exit;
}

/**
 * CREATE - Tambah Kategori
 */
function createKategori() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../index.php?page=tambah_kategori');
        exit;
    }
    
    $nama_kategori = trim($_POST['nama_kategori']);
    $deskripsi = trim($_POST['deskripsi']);
    
    // Validasi
    if (empty($nama_kategori)) {
        $_SESSION['error'] = 'Nama kategori harus diisi!';
        header('Location: ../index.php?page=tambah_kategori');
        exit;
    }
    
    // Cek duplikat
    $cek = fetchOne("SELECT id FROM kategori_menu WHERE nama_kategori = ?", [$nama_kategori]);
    if ($cek) {
        $_SESSION['error'] = 'Kategori dengan nama tersebut sudah ada!';
        header('Location: ../index.php?page=tambah_kategori');
        exit;
    }
    
    $sql = "INSERT INTO kategori_menu (nama_kategori, deskripsi) VALUES (?, ?)";
    $result = execute($sql, [$nama_kategori, $deskripsi]);
    
    if ($result['success']) {
        $_SESSION['success'] = 'Kategori berhasil ditambahkan!';
        header('Location: ../index.php?page=list_kategori');
    } else {
        $_SESSION['error'] = 'Gagal menambahkan kategori!';
        header('Location: ../index.php?page=tambah_kategori');
    }
    exit;
}

/**
 * UPDATE - Edit Kategori
 */
function updateKategori() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../index.php?page=list_kategori');
        exit;
    }
    
    $id = $_POST['id'];
    $nama_kategori = trim($_POST['nama_kategori']);
    $deskripsi = trim($_POST['deskripsi']);
    
    // Validasi
    if (empty($nama_kategori)) {
        $_SESSION['error'] = 'Nama kategori harus diisi!';
        header('Location: ../index.php?page=edit_kategori&id=' . $id);
        exit;
    }
    
    // Cek duplikat (selain id sendiri)
    $cek = fetchOne("SELECT id FROM kategori_menu WHERE nama_kategori = ? AND id != ?", [$nama_kategori, $id]);
    if ($cek) {
        $_SESSION['error'] = 'Kategori dengan nama tersebut sudah ada!';
        header('Location: ../index.php?page=edit_kategori&id=' . $id);
        exit;
    }
    
    $sql = "UPDATE kategori_menu SET nama_kategori = ?, deskripsi = ? WHERE id = ?";
    $result = execute($sql, [$nama_kategori, $deskripsi, $id]);
    
    if ($result['success']) {
        $_SESSION['success'] = 'Kategori berhasil diupdate!';
        header('Location: ../index.php?page=list_kategori');
    } else {
        $_SESSION['error'] = 'Gagal mengupdate kategori!';
        header('Location: ../index.php?page=edit_kategori&id=' . $id);
    }
    exit;
}

/**
 * DELETE - Hapus Kategori
 */
function deleteKategori() {
    $id = isset($_GET['id']) ? $_GET['id'] : 0;
    
    if ($id == 0) {
        $_SESSION['error'] = 'ID kategori tidak valid!';
        header('Location: ../index.php?page=list_kategori');
        exit;
    }
    
    // Cek apakah kategori punya menu
    $cek = fetchOne("SELECT COUNT(*) as total FROM menu_makanan WHERE kategori_id = ?", [$id]);
    if ($cek['total'] > 0) {
        $_SESSION['error'] = 'Kategori ini masih memiliki menu! Hapus menu terlebih dahulu.';
        header('Location: ../index.php?page=list_kategori');
        exit;
    }
    
    $sql = "DELETE FROM kategori_menu WHERE id = ?";
    $result = execute($sql, [$id]);
    
    if ($result['success']) {
        $_SESSION['success'] = 'Kategori berhasil dihapus!';
    } else {
        $_SESSION['error'] = 'Gagal menghapus kategori!';
    }
    
    header('Location: ../index.php?page=list_kategori');
    exit;
}
?>