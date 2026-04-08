<?php
// Cegah akses jika belum login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek role (sesuaikan dengan sistemmu)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'karyawan') {
    header("Location: ../login.php");
    exit;
}

// Ambil halaman aktif
$current_page = $_GET['page'] ?? 'dashboard';
?>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <i class="bi bi-shop"></i>
            <span class="sidebar-text">Rumah Makan</span>
        </div>

        <button class="sidebar-toggle-btn" id="sidebarToggle">
            <i class="bi bi-layout-sidebar-inset-reverse"></i>
        </button>
    </div>

    <div class="sidebar-menu">

        <!-- Dashboard -->
        <a href="index.php?page=dashboard"
           class="sidebar-item <?= $current_page == 'dashboard' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i>
            <span class="sidebar-text">Dashboard</span>
        </a>

        <!-- Transaksi -->
        <div class="sidebar-dropdown">
            <a href="#" class="sidebar-item"
               onclick="toggleDropdown(event,'transaksi')">
                <i class="bi bi-cash-coin"></i>
                <span class="sidebar-text">Transaksi</span>
                <i class="bi bi-chevron-down dropdown-arrow"></i>
            </a>

            <div class="sidebar-submenu" id="transaksi">
                <a href="index.php?page=buat_transaksi" class="sidebar-subitem">
                    <i class="bi bi-plus-circle"></i>
                    <span class="sidebar-text">Transaksi Baru</span>
                </a>

                <a href="index.php?page=list_transaksi" class="sidebar-subitem">
                    <i class="bi bi-list-ul"></i>
                    <span class="sidebar-text">Riwayat Transaksi</span>
                </a>
            </div>
        </div>

        <!-- Laporan -->
        <a href="index.php?page=laporan_harian"
           class="sidebar-item <?= $current_page == 'laporan_harian' ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-text"></i>
            <span class="sidebar-text">Laporan Harian</span>
        </a>

    </div>

    <!-- User Info -->
    <div class="sidebar-footer">

        <div class="sidebar-dropdown">
            <a href="#" class="sidebar-item"
               onclick="toggleDropdown(event,'userMenu')">
                <i class="bi bi-person-circle"></i>
                <span class="sidebar-text">
                    <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Karyawan'); ?>
                </span>
                <i class="bi bi-chevron-down dropdown-arrow"></i>
            </a>

            <div class="sidebar-submenu" id="userMenu">

                <a href="index.php?page=profile" class="sidebar-subitem">
                    <i class="bi bi-person"></i>
                    <span class="sidebar-text">Profil</span>
                </a>

                <div class="sidebar-divider"></div>

                <a href="config/auth_proses.php?action=logout"
                   class="sidebar-subitem text-danger">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="sidebar-text">Logout</span>
                </a>

            </div>
        </div>

    </div>
</nav>