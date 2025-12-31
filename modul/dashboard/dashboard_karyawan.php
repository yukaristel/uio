<?php
/**
 * DASHBOARD KARYAWAN/KASIR
 * Step 12/64 (18.8%)
 */

// Query untuk statistik dashboard karyawan
$today = date('Y-m-d');

// Total menu tersedia
$menu_stats = fetchOne("
    SELECT 
        COUNT(*) as total_menu,
        SUM(CASE WHEN status = 'tersedia' THEN 1 ELSE 0 END) as menu_tersedia,
        SUM(CASE WHEN status = 'habis' THEN 1 ELSE 0 END) as menu_habis
    FROM menu_makanan
");

// Transaksi hari ini
$transaksi_today = fetchOne("
    SELECT 
        COUNT(*) as jumlah_transaksi,
        COALESCE(SUM(total_harga), 0) as total_pendapatan
    FROM transaksi_penjualan
    WHERE DATE(tanggal_transaksi) = ?
", [$today]);

// Transaksi saya hari ini
$transaksi_saya = fetchOne("
    SELECT 
        COUNT(*) as jumlah_transaksi,
        COALESCE(SUM(total_harga), 0) as total_pendapatan
    FROM transaksi_penjualan
    WHERE DATE(tanggal_transaksi) = ? AND user_id = ?
", [$today, $_SESSION['user_id']]);

// Bahan baku stok menipis
$bahan_menipis = fetchAll("
    SELECT * FROM bahan_baku 
    WHERE stok_tersedia <= stok_minimum 
    ORDER BY stok_tersedia ASC 
    LIMIT 5
");

// Menu terlaris hari ini
$menu_terlaris = fetchAll("
    SELECT 
        m.nama_menu,
        m.harga_jual,
        SUM(dt.jumlah) as total_terjual
    FROM detail_transaksi dt
    JOIN menu_makanan m ON dt.menu_id = m.id
    JOIN transaksi_penjualan t ON dt.transaksi_id = t.id
    WHERE DATE(t.tanggal_transaksi) = ?
    GROUP BY m.id
    ORDER BY total_terjual DESC
    LIMIT 5
", [$today]);
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="bi bi-speedometer2"></i> Dashboard <?php echo ucfirst($_SESSION['role']); ?></h2>
            <p class="text-muted">Selamat datang, <?php echo $_SESSION['nama_lengkap']; ?>! Berikut ringkasan hari ini (<?php echo formatTanggal($today, 'd F Y'); ?>)</p>
        </div>
    </div>

    <!-- Statistik Cards -->
    <div class="row mb-4">
        <!-- Menu Tersedia -->
        <div class="col-md-3 mb-3">
            <div class="card dashboard-card card-primary">
                <div class="card-body">
                    <div>
                        <h6 class="text-muted">Menu Tersedia</h6>
                        <h3 class="mb-0"><?php echo $menu_stats['menu_tersedia']; ?></h3>
                        <small class="text-muted">dari <?php echo $menu_stats['total_menu']; ?> menu</small>
                    </div>
                    <div class="icon">
                        <i class="bi bi-card-list text-primary"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaksi Hari Ini -->
        <div class="col-md-3 mb-3">
            <div class="card dashboard-card card-success">
                <div class="card-body">
                    <div>
                        <h6 class="text-muted">Total Transaksi</h6>
                        <h3 class="mb-0"><?php echo $transaksi_today['jumlah_transaksi']; ?></h3>
                        <small class="text-muted">transaksi hari ini</small>
                    </div>
                    <div class="icon">
                        <i class="bi bi-receipt text-success"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pendapatan Hari Ini -->
        <div class="col-md-3 mb-3">
            <div class="card dashboard-card card-info">
                <div class="card-body">
                    <div>
                        <h6 class="text-muted">Pendapatan Hari Ini</h6>
                        <h3 class="mb-0 text-info"><?php echo formatRupiah($transaksi_today['total_pendapatan']); ?></h3>
                        <small class="text-muted">total penjualan</small>
                    </div>
                    <div class="icon">
                        <i class="bi bi-cash-stack text-info"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaksi Saya -->
        <div class="col-md-3 mb-3">
            <div class="card dashboard-card card-warning">
                <div class="card-body">
                    <div>
                        <h6 class="text-muted">Transaksi Saya</h6>
                        <h3 class="mb-0"><?php echo $transaksi_saya['jumlah_transaksi']; ?></h3>
                        <small class="text-muted"><?php echo formatRupiah($transaksi_saya['total_pendapatan']); ?></small>
                    </div>
                    <div class="icon">
                        <i class="bi bi-person-check text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-lightning-charge"></i> Aksi Cepat
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="index.php?page=buat_transaksi" class="btn btn-primary w-100">
                                <i class="bi bi-plus-circle"></i> Transaksi Baru
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="index.php?page=list_transaksi" class="btn btn-success w-100">
                                <i class="bi bi-list-ul"></i> Daftar Transaksi
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="index.php?page=list_menu" class="btn btn-info w-100">
                                <i class="bi bi-card-list"></i> Lihat Menu
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="index.php?page=tambah_opname" class="btn btn-warning w-100">
                                <i class="bi bi-clipboard-check"></i> Buat Opname
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Menu Terlaris -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-star text-warning"></i> Menu Terlaris Hari Ini
                </div>
                <div class="card-body">
                    <?php if (empty($menu_terlaris)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-cart-x" style="font-size: 2rem;"></i>
                            <p class="mb-0">Belum ada penjualan hari ini</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Menu</th>
                                        <th>Harga</th>
                                        <th class="text-center">Terjual</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    foreach ($menu_terlaris as $menu): 
                                    ?>
                                    <tr>
                                        <td>
                                            <?php if ($no == 1): ?>
                                                <i class="bi bi-trophy-fill text-warning"></i>
                                            <?php else: ?>
                                                <?php echo $no; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $menu['nama_menu']; ?></td>
                                        <td><?php echo formatRupiah($menu['harga_jual']); ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-success"><?php echo $menu['total_terjual']; ?></span>
                                        </td>
                                    </tr>
                                    <?php 
                                    $no++;
                                    endforeach; 
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Bahan Stok Menipis -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-exclamation-triangle text-warning"></i> Bahan Stok Menipis
                </div>
                <div class="card-body">
                    <?php if (empty($bahan_menipis)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                            <p class="mb-0">Semua stok bahan aman</p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-hide alert-warning">
                            <i class="bi bi-info-circle"></i> 
                            <strong>Perhatian!</strong> Beberapa bahan stok menipis. Segera hubungi admin untuk pembelian.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Bahan</th>
                                        <th>Stok Tersedia</th>
                                        <th>Stok Minimum</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bahan_menipis as $bahan): ?>
                                    <tr>
                                        <td><?php echo $bahan['nama_bahan']; ?></td>
                                        <td class="text-danger">
                                            <strong><?php echo $bahan['stok_tersedia'] . ' ' . $bahan['satuan']; ?></strong>
                                        </td>
                                        <td><?php echo $bahan['stok_minimum'] . ' ' . $bahan['satuan']; ?></td>
                                        <td>
                                            <?php if ($bahan['stok_tersedia'] == 0): ?>
                                                <span class="badge bg-danger">HABIS</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">MENIPIS</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="index.php?page=list_bahan" class="btn btn-sm btn-outline-primary w-100">
                            Lihat Semua Bahan
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>