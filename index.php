<?php
/**
 * APLIKASI RUMAH MAKAN - INDEX.PHP
 * File utama untuk routing aplikasi
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
</head>
<body>
    <?php
    if ($is_logged_in) {
        // Jika sudah login, tampilkan layout lengkap
        include 'modul/layouts/header.php';
        
        // Pilih navbar sesuai role
        if ($user_role == 'admin') {
            include 'modul/layouts/navbar.php';
        } else {
            include 'modul/layouts/navbar_karyawan.php';
        }
        
        // Content area
        echo '<div id="content" class="container-fluid mt-4 flex-fill">';
        include 'modul/layouts/content.php';
        echo '</div>';
        
        include 'modul/layouts/footer.php';
    } else {
        // Jika belum login, tampilkan halaman login
        include 'modul/auth/login.php';
    }
    ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (untuk AJAX optional) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
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

    <script>
window.onload = function() {
    const target = document.getElementById('content'); 
    if (target) {
        target.scrollIntoView({ behavior: 'smooth' });
    } else {
        console.warn("Elemen #content tidak ditemukan");
    }
}
</script>

</body>
</html>