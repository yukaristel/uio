<?php
/**
 * NAVBAR UNTUK KARYAWAN/KASIR
 * Step 6/64 (9.4%)
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

                <!-- Menu (hanya lihat) -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'list_menu' ? 'active' : ''; ?>" href="index.php?page=list_menu">
                        <i class="bi bi-card-list"></i> Daftar Menu
                    </a>
                </li>

                <!-- Stok Bahan (hanya lihat) -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'list_bahan' ? 'active' : ''; ?>" href="index.php?page=list_bahan">
                        <i class="bi bi-box-seam"></i> Stok Bahan
                    </a>
                </li>

                <!-- Stock Opname (buat draft) -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-clipboard-check"></i> Stock Opname
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="index.php?page=tambah_opname">
                            <i class="bi bi-plus-square"></i> Buat Opname
                        </a></li>
                        <li><a class="dropdown-item" href="index.php?page=list_opname">
                            <i class="bi bi-list-ul"></i> Daftar Opname
                        </a></li>
                    </ul>
                </li>

                <!-- Laporan (terbatas) -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-file-earmark-text"></i> Laporan
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="index.php?page=laporan_harian">
                            <i class="bi bi-calendar-day"></i> Laporan Harian
                        </a></li>
                        <li><a class="dropdown-item" href="index.php?page=laporan_stok">
                            <i class="bi bi-box"></i> Laporan Stok
                        </a></li>
                    </ul>
                </li>
            </ul>

            <!-- User Info -->
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo $_SESSION['nama_lengkap']; ?> 
                        (<?php echo ucfirst($_SESSION['role']); ?>)
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