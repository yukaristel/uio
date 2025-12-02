<?php
/**
 * CONTENT ROUTING
 * Step 8/64 (12.5%)
 */

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$user_role = $_SESSION['role'];

// Daftar halaman yang boleh diakses berdasarkan role
$admin_pages = [
    'dashboard', 'list_karyawan', 'tambah_karyawan', 'edit_karyawan',
    'list_kategori', 'tambah_kategori', 'edit_kategori',
    'list_bahan', 'tambah_bahan', 'edit_bahan', 'pembelian_bahan', 'history_pembelian',
    'list_menu', 'tambah_menu', 'edit_menu', 'detail_menu', 'resep_menu', 'tambah_resep',
    'list_transaksi', 'buat_transaksi', 'detail_transaksi', 'struk_transaksi',
    'list_movement', 'tambah_movement', 'detail_movement', 'laporan_movement',
    'list_opname', 'tambah_opname', 'detail_opname', 'approval_opname', 'history_opname',
    'dashboard_kas', 'list_transaksi_kas', 'tambah_transaksi_kas', 'detail_transaksi_kas', 'rekonsiliasi_kas', 'history_saldo',
    'laporan_harian', 'laporan_bulanan', 'laporan_stok', 'laporan_menu', 'laporan_opname', 'laporan_kas',
    'profile'
];

$karyawan_pages = [
    'dashboard', 
    'list_menu', 'detail_menu',
    'list_bahan',
    'list_transaksi', 'buat_transaksi', 'detail_transaksi', 'struk_transaksi',
    'list_opname', 'tambah_opname', 'detail_opname',
    'laporan_harian', 'laporan_stok',
    'profile'
];

// Cek akses halaman
$allowed = false;
if ($user_role == 'admin') {
    $allowed = in_array($page, $admin_pages);
} else {
    $allowed = in_array($page, $karyawan_pages);
}

// Jika tidak punya akses, redirect ke dashboard
if (!$allowed) {
    $_SESSION['error'] = 'Anda tidak memiliki akses ke halaman tersebut!';
    header('Location: index.php?page=dashboard');
    exit;
}

// Include file halaman yang diminta
$file_path = __DIR__ . '/../' . str_replace('_', '/', $page) . '.php';

// Mapping untuk file yang tidak sesuai pattern
$page_mapping = [
    // Dashboard
    'dashboard' => 'dashboard/dashboard.php',
    
    // Karyawan
    'list_karyawan' => 'karyawan/list_karyawan.php',
    'tambah_karyawan' => 'karyawan/tambah_karyawan.php',
    'edit_karyawan' => 'karyawan/edit_karyawan.php',
    
    // Kategori
    'list_kategori' => 'kategori/list_kategori.php',
    'tambah_kategori' => 'kategori/tambah_kategori.php',
    'edit_kategori' => 'kategori/edit_kategori.php',
    
    // Bahan Baku
    'list_bahan' => 'bahan_baku/list_bahan.php',
    'tambah_bahan' => 'bahan_baku/tambah_bahan.php',
    'edit_bahan' => 'bahan_baku/edit_bahan.php',
    'pembelian_bahan' => 'bahan_baku/pembelian_bahan.php',
    'history_pembelian' => 'bahan_baku/history_pembelian.php',
    
    // Menu
    'list_menu' => 'menu/list_menu.php',
    'tambah_menu' => 'menu/tambah_menu.php',
    'edit_menu' => 'menu/edit_menu.php',
    'detail_menu' => 'menu/detail_menu.php',
    'resep_menu' => 'menu/resep_menu.php',
    'tambah_resep' => 'menu/tambah_resep.php',
    
    // Transaksi
    'list_transaksi' => 'transaksi/list_transaksi.php',
    'buat_transaksi' => 'transaksi/buat_transaksi.php',
    'detail_transaksi' => 'transaksi/detail_transaksi.php',
    'struk_transaksi' => 'transaksi/struk_transaksi.php',
    
    // Stock Movement
    'list_movement' => 'stock_movement/list_movement.php',
    'tambah_movement' => 'stock_movement/tambah_movement.php',
    'detail_movement' => 'stock_movement/detail_movement.php',
    'laporan_movement' => 'stock_movement/laporan_movement.php',
    
    // Stock Opname
    'list_opname' => 'stock_opname/list_opname.php',
    'tambah_opname' => 'stock_opname/tambah_opname.php',
    'detail_opname' => 'stock_opname/detail_opname.php',
    'approval_opname' => 'stock_opname/approval_opname.php',
    'history_opname' => 'stock_opname/history_opname.php',
    
    // Kas Umum
    'dashboard_kas' => 'kas_umum/dashboard_kas.php',
    'list_transaksi_kas' => 'kas_umum/list_transaksi_kas.php',
    'tambah_transaksi_kas' => 'kas_umum/tambah_transaksi_kas.php',
    'detail_transaksi_kas' => 'kas_umum/detail_transaksi_kas.php',
    'rekonsiliasi_kas' => 'kas_umum/rekonsiliasi_kas.php',
    'history_saldo' => 'kas_umum/history_saldo.php',
    
    // Laporan
    'laporan_harian' => 'laporan/laporan_harian.php',
    'laporan_bulanan' => 'laporan/laporan_bulanan.php',
    'laporan_stok' => 'laporan/laporan_stok.php',
    'laporan_menu' => 'laporan/laporan_menu.php',
    'laporan_opname' => 'laporan/laporan_opname.php',
    'laporan_kas' => 'laporan/laporan_kas.php',
    
    // Profile
    'profile' => 'auth/profile.php'
];

// Cek apakah halaman ada di mapping
if (isset($page_mapping[$page])) {
    $file_path = __DIR__ . '/../' . $page_mapping[$page];
}

// Include file jika ada
if (file_exists($file_path)) {
    include $file_path;
} else {
    echo '<div class="alert alert-danger">';
    echo '<i class="bi bi-exclamation-triangle"></i> ';
    echo 'Halaman tidak ditemukan: ' . htmlspecialchars($page);
    echo '</div>';
    echo '<a href="index.php?page=dashboard" class="btn btn-primary">';
    echo '<i class="bi bi-house"></i> Kembali ke Dashboard';
    echo '</a>';
}
?>