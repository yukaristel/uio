<?php
/**
 * DASHBOARD KAS
 * Step 30/64 (46.9%)
 */

$today = date('Y-m-d');

// Saldo kas terkini
$saldo_kas = fetchOne("SELECT saldo_sesudah FROM kas_umum ORDER BY created_at DESC, id DESC LIMIT 1");
$saldo = $saldo_kas ? $saldo_kas['saldo_sesudah'] : 0;

// Kas hari ini
$kas_today = fetchOne("
    SELECT 
        COALESCE(SUM(CASE WHEN jenis_transaksi = 'masuk' THEN nominal ELSE 0 END), 0) as pemasukan,
        COALESCE(SUM(CASE WHEN jenis_transaksi = 'keluar' THEN nominal ELSE 0 END), 0) as pengeluaran
    FROM kas_umum
    WHERE DATE(tanggal_transaksi) = ?
", [$today]);

$selisih = $kas_today['pemasukan'] - $kas_today['pengeluaran'];

// Kas bulan ini
$bulan_ini = date('Y-m');
$kas_bulan = fetchOne("
    SELECT 
        COALESCE(SUM(CASE WHEN jenis_transaksi = 'masuk' THEN nominal ELSE 0 END), 0) as pemasukan,
        COALESCE(SUM(CASE WHEN jenis_transaksi = 'keluar' THEN nominal ELSE 0 END), 0) as pengeluaran
    FROM kas_umum
    WHERE DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ?
", [$bulan_ini]);

// Kas per kategori (bulan ini)
$kas_kategori = fetchAll("
    SELECT 
        kategori,
        jenis_transaksi,
        COALESCE(SUM(nominal), 0) as total
    FROM kas_umum
    WHERE DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ?
    GROUP BY kategori, jenis_transaksi
    ORDER BY total DESC
", [$bulan_ini]);

// Transaksi kas terbaru
$transaksi_terbaru = fetchAll("
    SELECT k.*, u.nama_lengkap 
    FROM kas_umum k
    JOIN users u ON k.user_id = u.id
    ORDER BY k.created_at DESC
    LIMIT 10
");
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-wallet2"></i> Dashboard Kas</h2>
        <p class="text-muted">Monitoring arus kas masuk dan keluar</p>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card dashboard-card card-primary">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Saldo Kas Terkini</h6>
                    <h3 class="mb-0 text-primary"><?php echo formatRupiah($saldo); ?></h3>
                </div>
                <div class="icon">
                    <i class="bi bi-wallet2 text-primary"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card dashboard-card card-success">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Pemasukan Hari Ini</h6>
                    <h4 class="mb-0 text-success"><?php echo formatRupiah($kas_today['pemasukan']); ?></h4>
                </div>
                <div class="icon">
                    <i class="bi bi-arrow-down-circle text-success"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card dashboard-card card-danger">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Pengeluaran Hari Ini</h6>
                    <h4 class="mb-0 text-danger"><?php echo formatRupiah($kas_today['pengeluaran']); ?></h4>
                </div>
                <div class="icon">
                    <i class="bi bi-arrow-up-circle text-danger"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card dashboard-card card-info">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Selisih Hari Ini</h6>
                    <h4 class="mb-0 <?php echo $selisih >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo formatRupiah($selisih); ?>
                    </h4>
                </div>
                <div class="icon">
                    <i class="bi bi-graph-up-arrow text-info"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Chart Kas Bulanan -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-bar-chart"></i> Arus Kas Bulan Ini
            </div>
            <div class="card-body">
                <div class="row text-center mb-3">
                    <div class="col-md-6">
                        <small class="text-muted">Pemasukan</small>
                        <h4 class="text-success"><?php echo formatRupiah($kas_bulan['pemasukan']); ?></h4>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Pengeluaran</small>
                        <h4 class="text-danger"><?php echo formatRupiah($kas_bulan['pengeluaran']); ?></h4>
                    </div>
                </div>
                <hr>
                <div class="text-center">
                    <small class="text-muted">Selisih Bulan Ini</small>
                    <h3 class="<?php echo ($kas_bulan['pemasukan'] - $kas_bulan['pengeluaran']) >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo formatRupiah($kas_bulan['pemasukan'] - $kas_bulan['pengeluaran']); ?>
                    </h3>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mt-3">
            <div class="card-header">
                <i class="bi bi-lightning-charge"></i> Aksi Cepat
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <a href="index.php?page=tambah_transaksi_kas" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle"></i> Transaksi Kas
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="index.php?page=list_transaksi_kas" class="btn btn-success w-100">
                            <i class="bi bi-list-ul"></i> Daftar Transaksi
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="index.php?page=rekonsiliasi_kas" class="btn btn-warning w-100">
                            <i class="bi bi-check2-square"></i> Rekonsiliasi
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="index.php?page=history_saldo" class="btn btn-info w-100">
                            <i class="bi bi-clock-history"></i> History Saldo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kas per Kategori -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pie-chart"></i> Kas per Kategori (Bulan Ini)
            </div>
            <div class="card-body">
                <?php if (empty($kas_kategori)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-3">Belum ada transaksi kas bulan ini</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Jenis</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kas_kategori as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo ucwords(str_replace('_', ' ', $item['kategori'])); ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($item['jenis_transaksi'] == 'masuk'): ?>
                                            <span class="badge bg-success">Masuk</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Keluar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end <?php echo $item['jenis_transaksi'] == 'masuk' ? 'text-success' : 'text-danger'; ?>">
                                        <strong><?php echo formatRupiah($item['total']); ?></strong>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Transaksi Terbaru -->
<div class="row mt-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clock-history"></i> Transaksi Kas Terbaru
            </div>
            <div class="card-body">
                <?php if (empty($transaksi_terbaru)): ?>
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                        <p class="mt-2">Belum ada transaksi kas</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>No. Transaksi</th>
                                    <th>Tanggal</th>
                                    <th>Jenis</th>
                                    <th>Kategori</th>
                                    <th>Nominal</th>
                                    <th>Saldo</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transaksi_terbaru as $trx): ?>
                                <tr>
                                    <td><small><?php echo $trx['no_transaksi_kas']; ?></small></td>
                                    <td><small><?php echo formatDateTime($trx['tanggal_transaksi'], 'd/m H:i'); ?></small></td>
                                    <td>
                                        <?php if ($trx['jenis_transaksi'] == 'masuk'): ?>
                                            <span class="badge bg-success">Masuk</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Keluar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?php echo ucwords(str_replace('_', ' ', $trx['kategori'])); ?></small></td>
                                    <td class="<?php echo $trx['jenis_transaksi'] == 'masuk' ? 'text-success' : 'text-danger'; ?>">
                                        <strong><?php echo formatRupiah($trx['nominal']); ?></strong>
                                    </td>
                                    <td><small><?php echo formatRupiah($trx['saldo_sesudah']); ?></small></td>
                                    <td><small><?php echo $trx['nama_lengkap']; ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-2">
                        <a href="index.php?page=list_transaksi_kas" class="btn btn-sm btn-outline-primary">
                            Lihat Semua <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>