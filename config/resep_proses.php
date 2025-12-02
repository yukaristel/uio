<?php
/**
 * PROSES RESEP MENU (Komposisi Bahan dengan Konversi Satuan)
 * Step 19/64 (29.7%)
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
        createResep();
        break;
    case 'delete':
        deleteResep();
        break;
    case 'recalculate':
        recalculateHPP();
        break;
    default:
        header('Location: ../index.php?page=list_menu');
        exit;
}

/**
 * CREATE - Tambah Bahan ke Resep dengan Konversi Satuan
 */
function createResep() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../index.php?page=list_menu');
        exit;
    }
    
    $menu_id = intval($_POST['menu_id']);
    $bahan_id = intval($_POST['bahan_id']);
    $jumlah_bahan = floatval($_POST['jumlah_bahan']);
    $satuan_resep = $_POST['satuan'];
    
    // Validasi
    if ($menu_id == 0 || $bahan_id == 0 || $jumlah_bahan <= 0 || empty($satuan_resep)) {
        $_SESSION['error'] = 'Semua field harus diisi dengan benar!';
        header('Location: ../index.php?page=resep_menu&id=' . $menu_id);
        exit;
    }
    
    // Ambil data bahan
    $bahan = fetchOne("SELECT * FROM bahan_baku WHERE id = ?", [$bahan_id]);
    if (!$bahan) {
        $_SESSION['error'] = 'Bahan tidak ditemukan!';
        header('Location: ../index.php?page=resep_menu&id=' . $menu_id);
        exit;
    }
    
    $satuan_bahan = $bahan['satuan'];
    $harga_bahan = $bahan['harga_beli_per_satuan'];
    
    // Validasi satuan bisa dikonversi
    if (!validasiSatuan($satuan_bahan, $satuan_resep)) {
        $_SESSION['error'] = "Tidak bisa konversi {$satuan_bahan} ke {$satuan_resep}! Pilih satuan yang sesuai.";
        header('Location: ../index.php?page=resep_menu&id=' . $menu_id);
        exit;
    }
    
    // Cek duplikat bahan di resep
    $cek = fetchOne("SELECT id FROM resep_menu WHERE menu_id = ? AND bahan_id = ?", [$menu_id, $bahan_id]);
    if ($cek) {
        $_SESSION['error'] = 'Bahan ini sudah ada di resep! Edit jumlahnya jika ingin mengubah.';
        header('Location: ../index.php?page=resep_menu&id=' . $menu_id);
        exit;
    }
    
    // Konversi jumlah resep ke satuan bahan
    $jumlah_konversi = konversiSatuan($jumlah_bahan, $satuan_resep, $satuan_bahan);
    
    if ($jumlah_konversi === false) {
        $_SESSION['error'] = 'Gagal konversi satuan!';
        header('Location: ../index.php?page=resep_menu&id=' . $menu_id);
        exit;
    }
    
    // Hitung biaya bahan
    $biaya_bahan = $jumlah_konversi * $harga_bahan;
    
    // Insert resep
    $sql = "INSERT INTO resep_menu (menu_id, bahan_id, jumlah_bahan, satuan, biaya_bahan) 
            VALUES (?, ?, ?, ?, ?)";
    
    $result = execute($sql, [$menu_id, $bahan_id, $jumlah_bahan, $satuan_resep, $biaya_bahan]);
    
    if ($result['success']) {
        // Hitung ulang HPP menu
        hitungUlangHPP($menu_id);
        
        $_SESSION['success'] = 'Bahan berhasil ditambahkan ke resep! Biaya: ' . formatRupiah($biaya_bahan);
        header('Location: ../index.php?page=resep_menu&id=' . $menu_id);
    } else {
        $_SESSION['error'] = 'Gagal menambahkan bahan ke resep!';
        header('Location: ../index.php?page=resep_menu&id=' . $menu_id);
    }
    exit;
}

/**
 * DELETE - Hapus Bahan dari Resep
 */
function deleteResep() {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $menu_id = isset($_GET['menu_id']) ? intval($_GET['menu_id']) : 0;
    
    if ($id == 0 || $menu_id == 0) {
        $_SESSION['error'] = 'Parameter tidak valid!';
        header('Location: ../index.php?page=list_menu');
        exit;
    }
    
    $sql = "DELETE FROM resep_menu WHERE id = ?";
    $result = execute($sql, [$id]);
    
    if ($result['success']) {
        // Hitung ulang HPP menu
        hitungUlangHPP($menu_id);
        
        $_SESSION['success'] = 'Bahan berhasil dihapus dari resep!';
    } else {
        $_SESSION['error'] = 'Gagal menghapus bahan dari resep!';
    }
    
    header('Location: ../index.php?page=resep_menu&id=' . $menu_id);
    exit;
}

/**
 * RECALCULATE - Hitung Ulang HPP semua menu (update harga weighted average)
 */
function recalculateHPP() {
    $menu_id = isset($_GET['menu_id']) ? intval($_GET['menu_id']) : 0;
    
    if ($menu_id == 0) {
        // Recalculate semua menu
        $menus = fetchAll("SELECT id FROM menu_makanan");
        foreach ($menus as $menu) {
            hitungUlangHPP($menu['id']);
        }
        $_SESSION['success'] = 'HPP semua menu berhasil dihitung ulang!';
        header('Location: ../index.php?page=list_menu');
    } else {
        // Recalculate satu menu
        hitungUlangHPP($menu_id);
        $_SESSION['success'] = 'HPP menu berhasil dihitung ulang!';
        header('Location: ../index.php?page=resep_menu&id=' . $menu_id);
    }
    exit;
}

/**
 * HELPER: Hitung Ulang HPP Menu
 */
function hitungUlangHPP($menu_id) {
    // Hitung ulang biaya setiap bahan di resep berdasarkan harga weighted average terbaru
    $resep_list = fetchAll("SELECT r.*, b.harga_beli_per_satuan, b.satuan as satuan_bahan 
                            FROM resep_menu r 
                            JOIN bahan_baku b ON r.bahan_id = b.id 
                            WHERE r.menu_id = ?", [$menu_id]);
    
    $total_hpp = 0;
    
    foreach ($resep_list as $resep) {
        // Konversi jumlah resep ke satuan bahan
        $jumlah_konversi = konversiSatuan(
            $resep['jumlah_bahan'], 
            $resep['satuan'], 
            $resep['satuan_bahan']
        );
        
        // Hitung biaya dengan harga terbaru
        $biaya_baru = $jumlah_konversi * $resep['harga_beli_per_satuan'];
        
        // Update biaya di resep
        execute("UPDATE resep_menu SET biaya_bahan = ? WHERE id = ?", [$biaya_baru, $resep['id']]);
        
        $total_hpp += $biaya_baru;
    }
    
    // Ambil harga jual
    $menu = fetchOne("SELECT harga_jual FROM menu_makanan WHERE id = ?", [$menu_id]);
    $harga_jual = $menu ? $menu['harga_jual'] : 0;
    
    // Hitung margin
    $margin = $harga_jual - $total_hpp;
    
    // Update menu
    execute("UPDATE menu_makanan SET harga_modal = ?, margin_keuntungan = ? WHERE id = ?", 
            [$total_hpp, $margin, $menu_id]);
    
    return $total_hpp;
}
?>