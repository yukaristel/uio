<?php
/**
 * DASHBOARD ADMIN
 * Step 11/64 (17.2%)
 */

// Cek apakah user admin atau karyawan, load dashboard sesuai role
if ($_SESSION['role'] != 'admin') {
    include 'dashboard_karyawan.php';
    exit;
}

// Query untuk statistik dashboard
$today = date('Y-m-d');

// Total menu tersedia/habis
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
        COALESCE(SUM(total_harga), 0) as total_pendapatan,
        COALESCE(SUM(total_modal), 0) as total_modal,
        COALESCE(SUM(total_keuntungan), 0) as total_keuntungan
    FROM transaksi_penjualan
    WHERE DATE(tanggal_transaksi) = ?
", [$today]);

// Saldo kas terkini
$saldo_kas = fetchOne("
    SELECT saldo_sesudah 
    FROM kas_umum 
    ORDER BY created_at DESC, id DESC 
    LIMIT 1
");
$saldo_kas_terkini = $saldo_kas ? $saldo_kas['saldo_sesudah'] : 0;

// Pemasukan dan pengeluaran hari ini
$kas_today = fetchOne("
    SELECT 
        COALESCE(SUM(CASE WHEN jenis_transaksi = 'masuk' THEN nominal ELSE 0 END), 0) as pemasukan,
        COALESCE(SUM(CASE WHEN jenis_transaksi = 'keluar' THEN nominal ELSE 0 END), 0) as pengeluaran
    FROM kas_umum
    WHERE DATE(tanggal_transaksi) = ?
", [$today]);

// Bahan baku stok menipis
$bahan_menipis = fetchAll("
    SELECT * FROM bahan_baku 
    WHERE stok_tersedia <= stok_minimum 
    ORDER BY stok_tersedia ASC 
    LIMIT 5
");

// Stock opname pending approval
$opname_pending = fetchAll("
    SELECT so.*, b.nama_bahan 
    FROM stock_opname so
    JOIN bahan_baku b ON so.bahan_id = b.id
    WHERE so.status = 'draft'
    ORDER BY so.created_at DESC
    LIMIT 5
");

// Kerugian dari stock movement hari ini
$kerugian_today = fetchOne("
    SELECT COALESCE(SUM(total_nilai), 0) as total_kerugian
    FROM stock_movement
    WHERE jenis_pergerakan IN ('rusak', 'tumpah', 'expired', 'hilang')
    AND DATE(created_at) = ?
", [$today]);
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="bi bi-speedometer2"></i> Dashboard Admin</h2>
            <p class="text-muted">Selamat datang, <?php echo $_SESSION['nama_lengkap']; ?>! Berikut ringkasan hari ini (<?php echo formatTanggal($today, 'd F Y'); ?>)</p>
        </div>
    </div>

    <!-- Statistik Cards -->
    <div class="row mb-4">
        <!-- Transaksi Hari Ini -->
        <div class="col-md-3 mb-3">
            <div class="card dashboard-card card-primary">
                <div class="card-body">
                    <div>
                        <h6 class="text-muted">Transaksi Hari Ini</h6>
                        <h3 class="mb-0"><?php echo $transaksi_today['jumlah_transaksi']; ?></h3>
                        <small class="text-muted">transaksi</small>
                    </div>
                    <div class="icon">
                        <i class="bi bi-receipt text-primary"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pendapatan Hari Ini -->
        <div class="col-md-3 mb-3">
            <div class="card dashboard-card card-success">
                <div class="card-body">
                    <div>
                        <h6 class="text-muted">Pendapatan Kotor</h6>
                        <h3 class="mb-0 text-success"><?php echo formatRupiah($transaksi_today['total_pendapatan']); ?></h3>
                        <small class="text-muted">hari ini</small>
                    </div>
                    <div class="icon">
                        <i class="bi bi-cash-stack text-success"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Keuntungan Bersih -->
        <div class="col-md-3 mb-3">
            <div class="card dashboard-card card-info">
                <div class="card-body">
                    <div>
                        <h6 class="text-muted">Keuntungan Bersih</h6>
                        <h3 class="mb-0 text-info"><?php echo formatRupiah($transaksi_today['total_keuntungan']); ?></h3>
                        <small class="text-muted">hari ini</small>
                    </div>
                    <div class="icon">
                        <i class="bi bi-graph-up-arrow text-info"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Saldo Kas -->
        <div class="col-md-3 mb-3">
            <div class="card dashboard-card card-warning">
                <div class="card-body">
                    <div>
                        <h6 class="text-muted">Saldo Kas Terkini</h6>
                        <h3 class="mb-0 text-warning"><?php echo formatRupiah($saldo_kas_terkini); ?></h3>
                        <small class="text-muted">
                            <i class="bi bi-arrow-up text-success"></i> <?php echo formatRupiah($kas_today['pemasukan']); ?>
                            <i class="bi bi-arrow-down text-danger"></i> <?php echo formatRupiah($kas_today['pengeluaran']); ?>
                        </small>
                    </div>
                    <div class="icon">
                        <i class="bi bi-wallet2 text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Menu Status -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-card-list"></i> Status Menu
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Menu:</span>
                        <strong><?php echo $menu_stats['total_menu']; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="bi bi-check-circle text-success"></i> Tersedia:</span>
                        <strong class="text-success"><?php echo $menu_stats['menu_tersedia']; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><i class="bi bi-x-circle text-danger"></i> Habis:</span>
                        <strong class="text-danger"><?php echo $menu_stats['menu_habis']; ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kas Hari Ini -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-wallet2"></i> Arus Kas Hari Ini
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="bi bi-arrow-down-circle text-success"></i> Pemasukan:</span>
                        <strong class="text-success"><?php echo formatRupiah($kas_today['pemasukan']); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="bi bi-arrow-up-circle text-danger"></i> Pengeluaran:</span>
                        <strong class="text-danger"><?php echo formatRupiah($kas_today['pengeluaran']); ?></strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span><strong>Selisih:</strong></span>
                        <strong class="<?php echo ($kas_today['pemasukan'] - $kas_today['pengeluaran']) >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo formatRupiah($kas_today['pemasukan'] - $kas_today['pengeluaran']); ?>
                        </strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kerugian -->
        <?php if ($kerugian_today['total_kerugian'] > 0): ?>
        <div class="col-md-4">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <i class="bi bi-exclamation-triangle"></i> Kerugian Hari Ini
                </div>
                <div class="card-body">
                    <h3 class="text-danger mb-2"><?php echo formatRupiah($kerugian_today['total_kerugian']); ?></h3>
                    <p class="mb-0 text-muted small">Dari bahan rusak/tumpah/expired/hilang</p>
                    <a href="index.php?page=list_movement" class="btn btn-sm btn-outline-danger mt-2">
                        <i class="bi bi-eye"></i> Lihat Detail
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="row">
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
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Bahan</th>
                                        <th>Stok</th>
                                        <th>Min</th>
                                        <th>Aksi</th>
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
                                            <a href="index.php?page=pembelian_bahan&bahan_id=<?php echo $bahan['id']; ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="bi bi-cart-plus"></i> Beli
                                            </a>
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

        <!-- Stock Opname Pending -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clipboard-check text-info"></i> Stock Opname Pending
                </div>
                <div class="card-body">
                    <?php if (empty($opname_pending)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                            <p class="mb-0">Tidak ada opname yang menunggu approval</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>No Opname</th>
                                        <th>Bahan</th>
                                        <th>Selisih</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($opname_pending as $opname): ?>
                                    <tr>
                                        <td><?php echo $opname['no_opname']; ?></td>
                                        <td><?php echo $opname['nama_bahan']; ?></td>
                                        <td class="<?php echo $opname['selisih'] < 0 ? 'text-danger' : 'text-success'; ?>">
                                            <?php echo $opname['selisih'] . ' ' . $opname['satuan']; ?>
                                        </td>
                                        <td>
                                            <a href="index.php?page=approval_opname&id=<?php echo $opname['id']; ?>" 
                                               class="btn btn-sm btn-success">
                                                <i class="bi bi-check-circle"></i> Approve
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="index.php?page=list_opname" class="btn btn-sm btn-outline-primary w-100">
                            Lihat Semua Opname
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>