<?php
/**
 * NAVBAR UNTUK ADMIN
 * Step 5/64 (7.8%)
 */
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-shop"></i> Rumah Makan
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>" href="index.php?page=dashboard">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>

                <!-- Master Data -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-database"></i> Master Data
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="index.php?page=list_karyawan">
                            <i class="bi bi-people"></i> Karyawan
                        </a></li>
                        <li><a class="dropdown-item" href="index.php?page=list_kategori">
                            <i class="bi bi-tag"></i> Kategori Menu
                        </a></li>
                        <li><a class="dropdown-item" href="index.php?page=list_bahan">
                            <i class="bi bi-box-seam"></i> Bahan Baku
                        </a></li>
                        <li><a class="dropdown-item" href="index.php?page=list_menu">
                            <i class="bi bi-card-list"></i> Menu Makanan
                        </a></li>
                    </ul>
                </li>

                <!-- Pembelian -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'pembelian_bahan' ? 'active' : ''; ?>" href="index.php?page=pembelian_bahan">
                        <i class="bi bi-cart-plus"></i> Pembelian Bahan
                    </a>
                </li>

                <!-- Transaksi -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-cash-coin"></i> Transaksi
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="index.php?page=buat_transaksi">
                            <i class="bi bi-plus-circle"></i> Transaksi Baru
                        </a></li>
                        <li><a class="dropdown-item" href="index.php?page=list_transaksi">
                            <i class="bi bi-list-ul"></i> Daftar Transaksi
                        </a></li>
                    </ul>
                </li>

                <!-- Stock Management -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-boxes"></i> Stok
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="index.php?page=list_movement">
                            <i class="bi bi-arrow-left-right"></i> Stock Movement
                        </a></li>
                        <li><a class="dropdown-item" href="index.php?page=list_opname">
                            <i class="bi bi-clipboard-check"></i> Stock Opname
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="index.php?page=tambah_opname">
                            <i class="bi bi-plus-square"></i> Buat Opname Baru
                        </a></li>
                    </ul>
                </li>

                <!-- Kas -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-wallet2"></i> Kas
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="index.php?page=dashboard_kas">
                            <i class="bi bi-graph-up"></i> Dashboard Kas
                        </a></li>
                        <li><a class="dropdown-item" href="index.php?page=list_transaksi_kas">
                            <i class="bi bi-list-check"></i> Transaksi Kas
                        </a></li>
                        <li><a class="dropdown-item" href="index.php?page=tambah_transaksi_kas">
                            <i class="bi bi-plus-circle"></i> Input Manual
                        </a></li>
                        <li><a class="dropdown-item" href="index.php?page=rekonsiliasi_kas">
                            <i class="bi bi-check2-square"></i> Rekonsiliasi
                        </a></li>
                    </ul>
                </li>

                <!-- Laporan -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-file-earmark-text"></i> Laporan
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="index.php?page=laporan_harian">
                            <i class="bi bi-calendar-day"></i> Laporan Harian
                        </a></li>
                        <li><a class="dropdown-item" href="index.php?page=laporan_bulanan">
                            <i class="bi bi-calendar-month"></i> Laporan Bulanan
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="index.php?page=laporan_stok">
                            <i class="bi bi-box"></i> Laporan Stok
                        </a></li>
                        <li><a class="dropdown-item" href="index.php?page=laporan_menu">
                            <i class="bi bi-graph-up-arrow"></i> Laporan Menu
                        </a></li>
                        <li><a class="dropdown-item" href="index.php?page=laporan_kas">
                            <i class="bi bi-cash-stack"></i> Laporan Kas
                        </a></li>
                    </ul>
                </li>
            </ul>

            <!-- User Info -->
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo $_SESSION['nama_lengkap']; ?> (Admin)
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="index.php?page=profile">
                            <i class="bi bi-person"></i> Profil
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="config/auth_proses.php?action=logout">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>