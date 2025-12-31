<?php
/**
 * SIDEBAR UNTUK ADMIN - Bisa dikecilkan seperti Claude
 * Step 5/64 (7.8%)
 */
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <i class="bi bi-shop"></i>
            <span class="sidebar-text">Rumah Makan</span>
        </div>
        <!-- Toggle Button di pojok kanan header -->
        <button class="sidebar-toggle-btn" id="sidebarToggle" title="Toggle Sidebar">
            <i class="bi bi-layout-sidebar-inset-reverse"></i>
        </button>
    </div>

    <div class="sidebar-menu">
        <!-- Dashboard -->
        <a href="index.php?page=dashboard" class="sidebar-item <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i>
            <span class="sidebar-text">Dashboard</span>
        </a>

        <!-- Master Data -->
        <div class="sidebar-dropdown">
            <a href="#" class="sidebar-item" onclick="toggleDropdown(event, 'masterData')">
                <i class="bi bi-database"></i>
                <span class="sidebar-text">Master Data</span>
                <i class="bi bi-chevron-down dropdown-arrow"></i>
            </a>
            <div class="sidebar-submenu" id="masterData">
                <a href="index.php?page=list_karyawan" class="sidebar-subitem">
                    <i class="bi bi-people"></i>
                    <span class="sidebar-text">Karyawan</span>
                </a>
                <a href="index.php?page=list_kategori" class="sidebar-subitem">
                    <i class="bi bi-tag"></i>
                    <span class="sidebar-text">Kategori Menu</span>
                </a>
                <a href="index.php?page=list_bahan" class="sidebar-subitem">
                    <i class="bi bi-box-seam"></i>
                    <span class="sidebar-text">Bahan Baku</span>
                </a>
                <a href="index.php?page=list_menu" class="sidebar-subitem">
                    <i class="bi bi-card-list"></i>
                    <span class="sidebar-text">Menu Makanan</span>
                </a>
            </div>
        </div>

        <!-- Pembelian -->
        <a href="index.php?page=pembelian_bahan" class="sidebar-item <?php echo $current_page == 'pembelian_bahan' ? 'active' : ''; ?>">
            <i class="bi bi-cart-plus"></i>
            <span class="sidebar-text">Pembelian</span>
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
            </div>
        </div>

        <!-- Stock Management -->
        <div class="sidebar-dropdown">
            <a href="#" class="sidebar-item" onclick="toggleDropdown(event, 'stock')">
                <i class="bi bi-boxes"></i>
                <span class="sidebar-text">Stock</span>
                <i class="bi bi-chevron-down dropdown-arrow"></i>
            </a>
            <div class="sidebar-submenu" id="stock">
                <a href="index.php?page=list_movement" class="sidebar-subitem">
                    <i class="bi bi-arrow-left-right"></i>
                    <span class="sidebar-text">Stock Movement</span>
                </a>
                <a href="index.php?page=list_opname" class="sidebar-subitem">
                    <i class="bi bi-clipboard-check"></i>
                    <span class="sidebar-text">Stock Opname</span>
                </a>
                <a href="index.php?page=tambah_opname" class="sidebar-subitem">
                    <i class="bi bi-plus-square"></i>
                    <span class="sidebar-text">Buat Opname</span>
                </a>
                <div class="sidebar-divider"></div>
                <a href="index.php?page=generate_stock" class="sidebar-subitem text-warning">
                    <i class="bi bi-arrow-repeat"></i>
                    <span class="sidebar-text">Generate Stock</span>
                </a>
            </div>
        </div>

        <!-- Kas -->
        <div class="sidebar-dropdown">
            <a href="#" class="sidebar-item" onclick="toggleDropdown(event, 'kas')">
                <i class="bi bi-wallet2"></i>
                <span class="sidebar-text">Kas</span>
                <i class="bi bi-chevron-down dropdown-arrow"></i>
            </a>
            <div class="sidebar-submenu" id="kas">
                <a href="index.php?page=dashboard_kas" class="sidebar-subitem">
                    <i class="bi bi-graph-up"></i>
                    <span class="sidebar-text">Dashboard Kas</span>
                </a>
                <a href="index.php?page=list_transaksi_kas" class="sidebar-subitem">
                    <i class="bi bi-list-check"></i>
                    <span class="sidebar-text">Transaksi Kas</span>
                </a>
                <a href="index.php?page=tambah_transaksi_kas" class="sidebar-subitem">
                    <i class="bi bi-plus-circle"></i>
                    <span class="sidebar-text">Input Manual</span>
                </a>
                <a href="index.php?page=rekonsiliasi_kas" class="sidebar-subitem">
                    <i class="bi bi-check2-square"></i>
                    <span class="sidebar-text">Rekonsiliasi</span>
                </a>
                <div class="sidebar-divider"></div>
                <a href="index.php?page=generate_kas" class="sidebar-subitem text-warning">
                    <i class="bi bi-arrow-repeat"></i>
                    <span class="sidebar-text">Generate Kas</span>
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
                <a href="index.php?page=laporan_bulanan" class="sidebar-subitem">
                    <i class="bi bi-calendar-month"></i>
                    <span class="sidebar-text">Laporan Bulanan</span>
                </a>
                <div class="sidebar-divider"></div>
                <a href="index.php?page=laporan_stok" class="sidebar-subitem">
                    <i class="bi bi-box"></i>
                    <span class="sidebar-text">Laporan Stok</span>
                </a>
                <a href="index.php?page=laporan_menu" class="sidebar-subitem">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span class="sidebar-text">Laporan Menu</span>
                </a>
                <a href="index.php?page=laporan_kas" class="sidebar-subitem">
                    <i class="bi bi-cash-stack"></i>
                    <span class="sidebar-text">Laporan Kas</span>
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
// Tunggu sampai DOM fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Toggle Sidebar
    const toggleBtn = document.querySelector('.sidebar-toggle-btn');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent sidebar click event
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
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
                    mainContent.classList.remove('expanded');
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

// Toggle Dropdown
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