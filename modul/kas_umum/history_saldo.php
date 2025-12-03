<?php
/**
 * HISTORY SALDO KAS HARIAN
 * Step 45/64 (70.3%)
 */

// Filter bulan
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');

// Get saldo kas harian
$saldo_list = fetchAll("
    SELECT * FROM saldo_kas 
    WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?
    ORDER BY tanggal DESC
", [$bulan]);

// Summary bulan
$summary = fetchOne("
    SELECT 
        COALESCE(SUM(total_masuk), 0) as total_masuk,
        COALESCE(SUM(total_keluar), 0) as total_keluar
    FROM saldo_kas 
    WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?
", [$bulan]);
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-clock-history"></i> History Saldo Kas</h2>
    </div>
</div>

<!-- Filter -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="page" value="history_saldo">
                    <div class="col-md-3">
                        <label class="form-label">Bulan</label>
                        <input type="month" class="form-control" name="bulan" value="<?php echo $bulan; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Summary -->
<div class="row mb-3">
    <div class="col-md-4">
        <div class="card dashboard-card card-success">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Total Pemasukan</h6>
                    <h4 class="text-success"><?php echo formatRupiah($summary['total_masuk']); ?></h4>
                </div>
                <div class="icon"><i class="bi bi-arrow-down-circle text-success"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card dashboard-card card-danger">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Total Pengeluaran</h6>
                    <h4 class="text-danger"><?php echo formatRupiah($summary['total_keluar']); ?></h4>
                </div>
                <div class="icon"><i class="bi bi-arrow-up-circle text-danger"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card dashboard-card card-info">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Selisih</h6>
                    <h4 class="<?php echo ($summary['total_masuk'] - $summary['total_keluar']) >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo formatRupiah($summary['total_masuk'] - $summary['total_keluar']); ?>
                    </h4>
                </div>
                <div class="icon"><i class="bi bi-graph-up-arrow text-info"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel History -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-calendar"></i> History Saldo Harian - <?php echo date('F Y', strtotime($bulan . '-01')); ?>
            </div>
            <div class="card-body">
                <?php if (empty($saldo_list)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-3">Tidak ada data pada bulan ini</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Saldo Awal</th>
                                    <th>Pemasukan</th>
                                    <th>Pengeluaran</th>
                                    <th>Selisih</th>
                                    <th>Saldo Akhir</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($saldo_list as $saldo): 
                                    $selisih = $saldo['total_masuk'] - $saldo['total_keluar'];
                                ?>
                                <tr>
                                    <td><strong><?php echo formatTanggal($saldo['tanggal'], 'd F Y'); ?></strong></td>
                                    <td><?php echo formatRupiah($saldo['saldo_awal']); ?></td>
                                    <td class="text-success"><strong><?php echo formatRupiah($saldo['total_masuk']); ?></strong></td>
                                    <td class="text-danger"><strong><?php echo formatRupiah($saldo['total_keluar']); ?></strong></td>
                                    <td class="<?php echo $selisih >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <strong><?php echo formatRupiah($selisih); ?></strong>
                                    </td>
                                    <td>
                                        <strong class="text-primary"><?php echo formatRupiah($saldo['saldo_akhir']); ?></strong>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-secondary">
                                    <th>TOTAL</th>
                                    <th>-</th>
                                    <th class="text-success"><?php echo formatRupiah($summary['total_masuk']); ?></th>
                                    <th class="text-danger"><?php echo formatRupiah($summary['total_keluar']); ?></th>
                                    <th class="<?php echo ($summary['total_masuk'] - $summary['total_keluar']) >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo formatRupiah($summary['total_masuk'] - $summary['total_keluar']); ?>
                                    </th>
                                    <th>-</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>