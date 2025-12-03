<?php
/**
 * LAPORAN ARUS KAS
 * Step 61/64 (95.3%)
 */

$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
list($tahun, $bulan_num) = explode('-', $bulan);

// Summary Kas Bulanan
$kas_summary = fetchOne("
    SELECT 
        SUM(total_masuk) as total_masuk,
        SUM(total_keluar) as total_keluar
    FROM saldo_kas
    WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?
", [$bulan]);

// Per Kategori Masuk
$kategori_masuk = fetchAll("
    SELECT 
        kategori,
        COUNT(*) as jumlah,
        SUM(nominal) as total
    FROM kas_umum
    WHERE DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ? 
        AND jenis_transaksi = 'masuk'
    GROUP BY kategori
    ORDER BY total DESC
", [$bulan]);

// Per Kategori Keluar
$kategori_keluar = fetchAll("
    SELECT 
        kategori,
        COUNT(*) as jumlah,
        SUM(nominal) as total
    FROM kas_umum
    WHERE DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ? 
        AND jenis_transaksi = 'keluar'
    GROUP BY kategori
    ORDER BY total DESC
", [$bulan]);

// Transaksi Per Hari
$transaksi_per_hari = fetchAll("
    SELECT 
        tanggal,
        saldo_awal,
        total_masuk,
        total_keluar,
        saldo_akhir
    FROM saldo_kas
    WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?
    ORDER BY tanggal
", [$bulan]);

$saldo_awal_bulan = !empty($transaksi_per_hari) ? $transaksi_per_hari[0]['saldo_awal'] : 0;
$saldo_akhir_bulan = !empty($transaksi_per_hari) ? end($transaksi_per_hari)['saldo_akhir'] : 0;
$selisih = $kas_summary['total_masuk'] - $kas_summary['total_keluar'];

$nama_bulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-cash-stack"></i> Laporan Arus Kas</h2>
        <p class="text-muted">Periode: <strong><?php echo $nama_bulan[$bulan_num]; ?> <?php echo $tahun; ?></strong></p>
    </div>
</div>

<!-- Filter -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="page" value="laporan_kas">
                    <div class="col-md-3">
                        <label class="form-label">Periode</label>
                        <input type="month" class="form-control" name="bulan" value="<?php echo $bulan; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="btn-group w-100">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Tampilkan
                            </button>
                            <button type="button" class="btn btn-success" onclick="window.print()">
                                <i class="bi bi-printer"></i> Cetak
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <h6 class="text-muted">Saldo Awal</h6>
                <h4><?php echo formatRupiah($saldo_awal_bulan); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <h6 class="text-muted">Total Masuk</h6>
                <h4 class="text-success">+<?php echo formatRupiah($kas_summary['total_masuk']); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-danger">
            <div class="card-body text-center">
                <h6 class="text-muted">Total Keluar</h6>
                <h4 class="text-danger">-<?php echo formatRupiah($kas_summary['total_keluar']); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <h6 class="text-muted">Saldo Akhir</h6>
                <h4 class="text-primary"><strong><?php echo formatRupiah($saldo_akhir_bulan); ?></strong></h4>
            </div>
        </div>
    </div>
</div>

<!-- Selisih -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="alert <?php echo $selisih >= 0 ? 'alert-success' : 'alert-danger'; ?>">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5><i class="bi bi-calculator"></i> Selisih Kas (Surplus/Defisit)</h5>
                </div>
                <div>
                    <h3 class="mb-0"><strong><?php echo formatRupiah($selisih); ?></strong></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Pemasukan -->
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header bg-success text-white">
                <i class="bi bi-arrow-down-circle"></i> Pemasukan per Kategori
            </div>
            <div class="card-body">
                <?php if (empty($kategori_masuk)): ?>
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-inbox"></i><br>
                        Tidak ada pemasukan
                    </div>
                <?php else: ?>
                    <?php foreach ($kategori_masuk as $km): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <h6 class="mb-0"><?php echo ucfirst($km['kategori']); ?></h6>
                            <small class="text-muted"><?php echo $km['jumlah']; ?> transaksi</small>
                        </div>
                        <div class="text-end">
                            <h5 class="mb-0 text-success"><?php echo formatRupiah($km['total']); ?></h5>
                            <small class="text-muted">
                                <?php echo number_format(($km['total'] / $kas_summary['total_masuk']) * 100, 1); ?>%
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="alert alert-success mb-0">
                        <strong>TOTAL PEMASUKAN: <?php echo formatRupiah($kas_summary['total_masuk']); ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pengeluaran -->
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <i class="bi bi-arrow-up-circle"></i> Pengeluaran per Kategori
            </div>
            <div class="card-body">
                <?php if (empty($kategori_keluar)): ?>
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-inbox"></i><br>
                        Tidak ada pengeluaran
                    </div>
                <?php else: ?>
                    <?php foreach ($kategori_keluar as $kk): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <h6 class="mb-0"><?php echo ucfirst($kk['kategori']); ?></h6>
                            <small class="text-muted"><?php echo $kk['jumlah']; ?> transaksi</small>
                        </div>
                        <div class="text-end">
                            <h5 class="mb-0 text-danger"><?php echo formatRupiah($kk['total']); ?></h5>
                            <small class="text-muted">
                                <?php echo number_format(($kk['total'] / $kas_summary['total_keluar']) * 100, 1); ?>%
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="alert alert-danger mb-0">
                        <strong>TOTAL PENGELUARAN: <?php echo formatRupiah($kas_summary['total_keluar']); ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Arus Kas Harian -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-graph-up"></i> Arus Kas Harian
            </div>
            <div class="card-body">
                <?php if (empty($transaksi_per_hari)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-3">Tidak ada transaksi kas pada bulan ini</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th class="text-end">Saldo Awal</th>
                                    <th class="text-end">Masuk</th>
                                    <th class="text-end">Keluar</th>
                                    <th class="text-end">Saldo Akhir</th>
                                    <th class="text-end">Perubahan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transaksi_per_hari as $tph): 
                                    $perubahan = $tph['total_masuk'] - $tph['total_keluar'];
                                ?>
                                <tr>
                                    <td><?php echo formatTanggal($tph['tanggal'], 'd/m/Y'); ?></td>
                                    <td class="text-end"><?php echo formatRupiah($tph['saldo_awal']); ?></td>
                                    <td class="text-end text-success"><?php echo formatRupiah($tph['total_masuk']); ?></td>
                                    <td class="text-end text-danger"><?php echo formatRupiah($tph['total_keluar']); ?></td>
                                    <td class="text-end"><strong><?php echo formatRupiah($tph['saldo_akhir']); ?></strong></td>
                                    <td class="text-end <?php echo $perubahan >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $perubahan > 0 ? '+' : ''; ?><?php echo formatRupiah($perubahan); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <th>TOTAL</th>
                                    <th class="text-end"><?php echo formatRupiah($saldo_awal_bulan); ?></th>
                                    <th class="text-end"><?php echo formatRupiah($kas_summary['total_masuk']); ?></th>
                                    <th class="text-end"><?php echo formatRupiah($kas_summary['total_keluar']); ?></th>
                                    <th class="text-end"><?php echo formatRupiah($saldo_akhir_bulan); ?></th>
                                    <th class="text-end <?php echo $selisih >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $selisih > 0 ? '+' : ''; ?><?php echo formatRupiah($selisih); ?>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .navbar, .breadcrumb, .btn, .card-body form { display: none !important; }
    .card { border: 1px solid #ddd !important; page-break-inside: avoid; }
    body { font-size: 11px; }
}
</style>