<?php
/**
 * PROSES CRUD MENU MAKANAN
 * Step 18/64 (28.1%)
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
        createMenu();
        break;
    case 'update':
        updateMenu();
        break;
    case 'delete':
        deleteMenu();
        break;
    case 'update_status':
        updateStatus();
        break;
    default:
        header('Location: ../index.php?page=list_menu');
        exit;
}

/**
 * CREATE - Tambah Menu
 */
function createMenu() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../index.php?page=tambah_menu');
        exit;
    }
    
    $kode_menu = strtoupper(trim($_POST['kode_menu']));
    $nama_menu = trim($_POST['nama_menu']);
    $kategori_id = intval($_POST['kategori_id']);
    $harga_jual = floatval($_POST['harga_jual']);
    $status = $_POST['status'];
    
    // Validasi
    if (empty($kode_menu) || empty($nama_menu) || $kategori_id == 0 || $harga_jual <= 0) {
        $_SESSION['error'] = 'Semua field harus diisi dengan benar!';
        header('Location: ../index.php?page=tambah_menu');
        exit;
    }
    
    // Cek kode duplikat
    $cek = fetchOne("SELECT id FROM menu_makanan WHERE kode_menu = ?", [$kode_menu]);
    if ($cek) {
        $_SESSION['error'] = 'Kode menu sudah digunakan!';
        header('Location: ../index.php?page=tambah_menu');
        exit;
    }
    
    // Handle upload foto
    $foto_menu = null;
    if (isset($_FILES['foto_menu']) && $_FILES['foto_menu']['error'] == 0) {
        $foto_menu = uploadFoto($_FILES['foto_menu']);
        if (!$foto_menu) {
            $_SESSION['error'] = 'Gagal upload foto! Format harus JPG/PNG/JPEG, max 2MB.';
            header('Location: ../index.php?page=tambah_menu');
            exit;
        }
    }
    
    $sql = "INSERT INTO menu_makanan 
            (kode_menu, nama_menu, kategori_id, harga_jual, status, foto_menu) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $result = execute($sql, [$kode_menu, $nama_menu, $kategori_id, $harga_jual, $status, $foto_menu]);
    
    if ($result['success']) {
        $menu_id = $result['insert_id'];
        $_SESSION['success'] = 'Menu berhasil ditambahkan! Silakan tambahkan resep (komposisi bahan).';
        header('Location: ../index.php?page=resep_menu&id=' . $menu_id);
    } else {
        $_SESSION['error'] = 'Gagal menambahkan menu!';
        header('Location: ../index.php?page=tambah_menu');
    }
    exit;
}

/**
 * UPDATE - Edit Menu
 */
function updateMenu() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../index.php?page=list_menu');
        exit;
    }
    
    $id = intval($_POST['id']);
    $kode_menu = strtoupper(trim($_POST['kode_menu']));
    $nama_menu = trim($_POST['nama_menu']);
    $kategori_id = intval($_POST['kategori_id']);
    $harga_jual = floatval($_POST['harga_jual']);
    $status = $_POST['status'];
    
    // Validasi
    if (empty($kode_menu) || empty($nama_menu) || $kategori_id == 0 || $harga_jual <= 0) {
        $_SESSION['error'] = 'Semua field harus diisi dengan benar!';
        header('Location: ../index.php?page=edit_menu&id=' . $id);
        exit;
    }
    
    // Cek kode duplikat
    $cek = fetchOne("SELECT id FROM menu_makanan WHERE kode_menu = ? AND id != ?", [$kode_menu, $id]);
    if ($cek) {
        $_SESSION['error'] = 'Kode menu sudah digunakan!';
        header('Location: ../index.php?page=edit_menu&id=' . $id);
        exit;
    }
    
    // Ambil data lama
    $menu_lama = fetchOne("SELECT * FROM menu_makanan WHERE id = ?", [$id]);
    
    // Handle upload foto baru
    $foto_menu = $menu_lama['foto_menu'];
    if (isset($_FILES['foto_menu']) && $_FILES['foto_menu']['error'] == 0) {
        $foto_baru = uploadFoto($_FILES['foto_menu']);
        if ($foto_baru) {
            // Hapus foto lama
            if ($foto_menu && file_exists('../uploads/menu/' . $foto_menu)) {
                unlink('../uploads/menu/' . $foto_menu);
            }
            $foto_menu = $foto_baru;
        }
    }
    
    $sql = "UPDATE menu_makanan 
            SET kode_menu = ?, nama_menu = ?, kategori_id = ?, harga_jual = ?, status = ?, foto_menu = ? 
            WHERE id = ?";
    
    $result = execute($sql, [$kode_menu, $nama_menu, $kategori_id, $harga_jual, $status, $foto_menu, $id]);
    
    if ($result['success']) {
        // Recalculate HPP jika harga jual berubah
        hitungUlangHPP($id);
        
        $_SESSION['success'] = 'Menu berhasil diupdate!';
        header('Location: ../index.php?page=list_menu');
    } else {
        $_SESSION['error'] = 'Gagal mengupdate menu!';
        header('Location: ../index.php?page=edit_menu&id=' . $id);
    }
    exit;
}

/**
 * DELETE - Hapus Menu
 */
function deleteMenu() {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id == 0) {
        $_SESSION['error'] = 'ID menu tidak valid!';
        header('Location: ../index.php?page=list_menu');
        exit;
    }
    
    // Cek apakah menu pernah dijual
    $cek = fetchOne("SELECT COUNT(*) as total FROM detail_transaksi WHERE menu_id = ?", [$id]);
    if ($cek['total'] > 0) {
        $_SESSION['warning'] = 'Menu ini memiliki history penjualan. Sebaiknya ubah status menjadi "Tidak Tersedia" daripada menghapus.';
        header('Location: ../index.php?page=list_menu');
        exit;
    }
    
    // Ambil data menu
    $menu = fetchOne("SELECT foto_menu FROM menu_makanan WHERE id = ?", [$id]);
    
    // Hapus foto jika ada
    if ($menu && $menu['foto_menu'] && file_exists('../uploads/menu/' . $menu['foto_menu'])) {
        unlink('../uploads/menu/' . $menu['foto_menu']);
    }
    
    // Hapus menu (resep akan terhapus otomatis karena ON DELETE CASCADE)
    $sql = "DELETE FROM menu_makanan WHERE id = ?";
    $result = execute($sql, [$id]);
    
    if ($result['success']) {
        $_SESSION['success'] = 'Menu berhasil dihapus!';
    } else {
        $_SESSION['error'] = 'Gagal menghapus menu!';
    }
    
    header('Location: ../index.php?page=list_menu');
    exit;
}

/**
 * UPDATE STATUS - Ubah status menu (tersedia/habis/tidak_tersedia)
 */
function updateStatus() {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    
    if ($id == 0 || empty($status)) {
        $_SESSION['error'] = 'Parameter tidak valid!';
        header('Location: ../index.php?page=list_menu');
        exit;
    }
    
    $sql = "UPDATE menu_makanan SET status = ? WHERE id = ?";
    $result = execute($sql, [$status, $id]);
    
    if ($result['success']) {
        $_SESSION['success'] = 'Status menu berhasil diubah!';
    } else {
        $_SESSION['error'] = 'Gagal mengubah status menu!';
    }
    
    header('Location: ../index.php?page=list_menu');
    exit;
}

/**
 * HELPER: Upload Foto Menu
 */
function uploadFoto($file) {
    $target_dir = "../uploads/menu/";
    
    // Buat folder jika belum ada
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png'];
    
    // Validasi ekstensi
    if (!in_array($file_extension, $allowed_ext)) {
        return false;
    }
    
    // Validasi ukuran (max 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        return false;
    }
    
    // Generate nama file unik
    $new_filename = 'menu_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $new_filename;
    }
    
    return false;
}

/**
 * HELPER: Hitung Ulang HPP Menu
 */
function hitungUlangHPP($menu_id) {
    // Hitung total biaya dari resep
    $sql = "SELECT SUM(biaya_bahan) as total_hpp 
            FROM resep_menu 
            WHERE menu_id = ?";
    
    $result = fetchOne($sql, [$menu_id]);
    $hpp = $result ? $result['total_hpp'] : 0;
    
    // Ambil harga jual
    $menu = fetchOne("SELECT harga_jual FROM menu_makanan WHERE id = ?", [$menu_id]);
    $harga_jual = $menu ? $menu['harga_jual'] : 0;
    
    // Hitung margin
    $margin = $harga_jual - $hpp;
    
    // Update menu
    $sql_update = "UPDATE menu_makanan 
                   SET harga_modal = ?, margin_keuntungan = ? 
                   WHERE id = ?";
    execute($sql_update, [$hpp, $margin, $menu_id]);
}
?>