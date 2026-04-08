<?php
/**
 * APLIKASI RUMAH MAKAN - INDEX.PHP
 * File utama untuk routing aplikasi dengan Sidebar Collapsible
 * Step 1/64 (1.6%)
 */

session_start();
require_once 'config/database.php';

// Cek apakah user sudah login
$is_logged_in = isset($_SESSION['user_id']);
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Jika belum login dan bukan halaman login, redirect ke login
if (!$is_logged_in && $current_page != 'login') {
    header('Location: index.php?page=login');
    exit;
}

// Jika sudah login dan akses halaman login, redirect ke dashboard
if ($is_logged_in && $current_page == 'login') {
    header('Location: index.php?page=dashboard');
    exit;
}

// Ambil role user untuk access control
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'karyawan';

// VALIDASI AKSES ROLE SEBELUM RENDER - Mencegah headers already sent
if ($is_logged_in) {
    // Daftar halaman yang boleh diakses berdasarkan role
    $admin_pages = [
        'dashboard', 'list_karyawan', 'tambah_karyawan', 'edit_karyawan',
        'list_kategori', 'tambah_kategori', 'edit_kategori',
        'list_bahan', 'tambah_bahan', 'edit_bahan', 'pembelian_bahan', 'history_pembelian',
        'list_menu', 'tambah_menu', 'edit_menu', 'detail_menu', 'resep_menu', 'tambah_resep',
        'list_transaksi', 'buat_transaksi', 'detail_transaksi', 'struk_transaksi',
        'list_movement', 'tambah_movement', 'detail_movement', 'laporan_movement',
        'list_opname', 'tambah_opname', 'detail_opname', 'approval_opname', 'history_opname','generate_stock',
        'dashboard_kas', 'list_transaksi_kas', 'tambah_transaksi_kas', 'detail_transaksi_kas', 'rekonsiliasi_kas', 'history_saldo', 'generate_kas',
        'laporan_harian', 'laporan_bulanan', 'laporan_stok', 'laporan_menu', 'laporan_opname', 'laporan_kas',
        'profile', 'pos'
    ];

    $karyawan_pages = [
        'dashboard', 
        'list_menu', 'detail_menu',
        'list_bahan',
        'list_transaksi', 'buat_transaksi', 'detail_transaksi', 'struk_transaksi',
        'list_opname', 'tambah_opname', 'detail_opname',
        'laporan_harian', 'laporan_stok',
        'profile', 'pos'
    ];

    // Cek akses halaman
    $allowed = false;
    if ($user_role == 'admin') {
        $allowed = in_array($current_page, $admin_pages);
    } else {
        $allowed = in_array($current_page, $karyawan_pages);
    }

    // Jika tidak punya akses, redirect ke dashboard SEBELUM ada output
    if (!$allowed) {
        $_SESSION['error'] = 'Anda tidak memiliki akses ke halaman tersebut!';
        header('Location: index.php?page=dashboard');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Rumah Makan - <?php echo ucfirst(str_replace('_', ' ', $current_page)); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style/css.css">
    
    <!-- Chart.js untuk grafik (optional) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Define toggleDropdown function BEFORE body loads -->
    <script>
        // Toggle Dropdown Function - Must be defined before HTML uses it
        function toggleDropdown(event, id) {
            event.preventDefault();
            const submenu = document.getElementById(id);
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            const parentItem = event.currentTarget;
            
            // Jika sidebar collapsed, buka dulu sidebar
            if (sidebar && sidebar.classList.contains('collapsed')) {
                sidebar.classList.remove('collapsed');
                if (mainContent) {
                    mainContent.classList.remove('expanded');
                }
                localStorage.setItem('sidebarCollapsed', false);
            }
            
            // Toggle submenu
            const isActive = submenu.classList.contains('active');
            
            // Close all submenus
            document.querySelectorAll('.sidebar-submenu').forEach(sm => {
                sm.classList.remove('active');
            });
            document.querySelectorAll('.sidebar-dropdown > a').forEach(item => {
                item.classList.remove('active');
            });
            
            // Open clicked submenu if it was closed
            if (!isActive) {
                submenu.classList.add('active');
                parentItem.classList.add('active');
            }
        }
    </script>
</head>
<body>
    <?php
    if ($is_logged_in) {
        // Tampilkan sidebar berdasarkan role
        if ($user_role == 'admin') {
            include 'modul/layouts/navbar.php';
        } else {
            include 'modul/layouts/navbar_karyawan.php';
        }
        
        // Main content wrapper
        echo '<div class="main-content">';
        
        // Header dengan alerts
        include 'modul/layouts/header.php';
        
        // Content area
        echo '<div id="content" class="container-fluid">';
        include 'modul/layouts/content.php';
        echo '</div>';
        
        // Footer
        include 'modul/layouts/footer.php';
        
        echo '</div>'; // End main-content
    } else {
        // Jika belum login, tampilkan halaman login
        include 'modul/auth/login.php';
    }
    ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (untuk AJAX optional) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Sidebar Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle Sidebar
            const toggleBtn = document.querySelector('.sidebar-toggle-btn');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function(e) {
                    e.stopPropagation(); // Prevent sidebar click event
                    sidebar.classList.toggle('collapsed');
                    if (mainContent) {
                        mainContent.classList.toggle('expanded');
                    }
                    
                    // Simpan state di localStorage
                    const isCollapsed = sidebar.classList.contains('collapsed');
                    localStorage.setItem('sidebarCollapsed', isCollapsed);
                });
            }

            // Click sidebar saat collapsed untuk expand
            if (sidebar) {
                sidebar.addEventListener('click', function(e) {
                    if (this.classList.contains('collapsed')) {
                        // Jangan expand jika klik menu item
                        if (!e.target.closest('.sidebar-item') && !e.target.closest('.sidebar-subitem')) {
                            this.classList.remove('collapsed');
                            if (mainContent) {
                                mainContent.classList.remove('expanded');
                            }
                            localStorage.setItem('sidebarCollapsed', false);
                        }
                    }
                });
            }

            // Load saved state
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed && sidebar && mainContent) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
            }
        });
    </script>
    
    <!-- Custom JS -->
    <script>
        // Auto hide alert after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-hide');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Konfirmasi delete
        function confirmDelete(message = 'Apakah Anda yakin ingin menghapus data ini?') {
            return confirm(message);
        }

        // Konfirmasi approve
        function confirmApprove(message = 'Apakah Anda yakin ingin menyetujui data ini?') {
            return confirm(message);
        }

        // Format number to Rupiah
        function formatRupiah(number) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(number);
        }

        // Print function
        function printPage() {
            window.print();
        }
    </script>
</body>
</html>