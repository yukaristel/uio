<?php
/**
 * SIDEBAR UNTUK KARYAWAN - Bisa dikecilkan seperti Claude
 * Step 6/64 (9.4%)
 */
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>

<!-- Toggle Button (Floating) -->
<button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
    <i class="bi bi-list"></i>
</button>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <i class="bi bi-shop"></i>
            <span class="sidebar-text">Rumah Makan</span>
        </div>
    </div>

    <div class="sidebar-menu">
        <!-- Dashboard -->
        <a href="index.php?page=dashboard" class="sidebar-item <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i>
            <span class="sidebar-text">Dashboard</span>
        </a>

        <!-- Menu -->
        <div class="sidebar-dropdown">
            <a href="#" class="sidebar-item" onclick="toggleDropdown(event, 'menu')">
                <i class="bi bi-card-list"></i>
                <span class="sidebar-text">Menu</span>
                <i class="bi bi-chevron-down dropdown-arrow"></i>
            </a>
            <div class="sidebar-submenu" id="menu">
                <a href="index.php?page=list_menu" class="sidebar-subitem">
                    <i class="bi bi-list-ul"></i>
                    <span class="sidebar-text">Daftar Menu</span>
                </a>
                <a href="index.php?page=detail_menu" class="sidebar-subitem">
                    <i class="bi bi-info-circle"></i>
                    <span class="sidebar-text">Detail Menu</span>
                </a>
            </div>
        </div>

        <!-- Bahan Baku -->
        <a href="index.php?page=list_bahan" class="sidebar-item <?php echo $current_page == 'list_bahan' ? 'active' : ''; ?>">
            <i class="bi bi-box-seam"></i>
            <span class="sidebar-text">Bahan Baku</span>
        </a>

        <!-- Transaksi -->
        <div class="sidebar-dropdown">
            <a href="#" class="sidebar-item" onclick="toggleDropdown(event, 'transaksi')">
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
                    <span class="sidebar-text">Daftar Transaksi</span>
                </a>
                <a href="index.php?page=detail_transaksi" class="sidebar-subitem">
                    <i class="bi bi-receipt"></i>
                    <span class="sidebar-text">Detail Transaksi</span>
                </a>
                <a href="index.php?page=struk_transaksi" class="sidebar-subitem">
                    <i class="bi bi-printer"></i>
                    <span class="sidebar-text">Cetak Struk</span>
                </a>
            </div>
        </div>

        <!-- Stock Opname -->
        <div class="sidebar-dropdown">
            <a href="#" class="sidebar-item" onclick="toggleDropdown(event, 'opname')">
                <i class="bi bi-clipboard-check"></i>
                <span class="sidebar-text">Stock Opname</span>
                <i class="bi bi-chevron-down dropdown-arrow"></i>
            </a>
            <div class="sidebar-submenu" id="opname">
                <a href="index.php?page=list_opname" class="sidebar-subitem">
                    <i class="bi bi-list-ul"></i>
                    <span class="sidebar-text">Daftar Opname</span>
                </a>
                <a href="index.php?page=tambah_opname" class="sidebar-subitem">
                    <i class="bi bi-plus-square"></i>
                    <span class="sidebar-text">Buat Opname</span>
                </a>
                <a href="index.php?page=detail_opname" class="sidebar-subitem">
                    <i class="bi bi-info-circle"></i>
                    <span class="sidebar-text">Detail Opname</span>
                </a>
            </div>
        </div>

        <!-- Laporan -->
        <div class="sidebar-dropdown">
            <a href="#" class="sidebar-item" onclick="toggleDropdown(event, 'laporan')">
                <i class="bi bi-file-earmark-text"></i>
                <span class="sidebar-text">Laporan</span>
                <i class="bi bi-chevron-down dropdown-arrow"></i>
            </a>
            <div class="sidebar-submenu" id="laporan">
                <a href="index.php?page=laporan_harian" class="sidebar-subitem">
                    <i class="bi bi-calendar-day"></i>
                    <span class="sidebar-text">Laporan Harian</span>
                </a>
                <a href="index.php?page=laporan_stok" class="sidebar-subitem">
                    <i class="bi bi-box"></i>
                    <span class="sidebar-text">Laporan Stok</span>
                </a>
            </div>
        </div>
    </div>

    <!-- User Info di bawah -->
    <div class="sidebar-footer">
        <div class="sidebar-dropdown">
            <a href="#" class="sidebar-item" onclick="toggleDropdown(event, 'userMenu')">
                <i class="bi bi-person-circle"></i>
                <span class="sidebar-text"><?php echo $_SESSION['nama_lengkap']; ?></span>
                <i class="bi bi-chevron-down dropdown-arrow"></i>
            </a>
            <div class="sidebar-submenu" id="userMenu">
                <a href="index.php?page=profile" class="sidebar-subitem">
                    <i class="bi bi-person"></i>
                    <span class="sidebar-text">Profil</span>
                </a>
                <div class="sidebar-divider"></div>
                <a href="config/auth_proses.php?action=logout" class="sidebar-subitem text-danger">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="sidebar-text">Logout</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
// Toggle Sidebar
document.getElementById('sidebarToggle').addEventListener('click', function() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('expanded');
    
    // Simpan state di localStorage
    const isCollapsed = sidebar.classList.contains('collapsed');
    localStorage.setItem('sidebarCollapsed', isCollapsed);
});

// Load saved state
window.addEventListener('DOMContentLoaded', function() {
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isCollapsed) {
        document.getElementById('sidebar').classList.add('collapsed');
        document.querySelector('.main-content').classList.add('expanded');
    }
});

// Toggle Dropdown
function toggleDropdown(event, id) {
    event.preventDefault();
    const submenu = document.getElementById(id);
    const sidebar = document.getElementById('sidebar');
    const parentItem = event.currentTarget;
    
    // Jika sidebar collapsed, buka dulu sidebar
    if (sidebar.classList.contains('collapsed')) {
        sidebar.classList.remove('collapsed');
        document.querySelector('.main-content').classList.remove('expanded');
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