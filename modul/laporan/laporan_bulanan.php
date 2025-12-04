<?php
/**
 * LAPORAN BULANAN (FIXED NULL HANDLING)
 * Step 58/64 (90.6%)
 */

// Filter bulan
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
list($tahun, $bulan_num) = explode('-', $bulan);

// Summary Bulanan
$summary_bulanan = fetchOne("
    SELECT 
        COUNT(*) as total_transaksi,
        COALESCE(SUM(total_harga), 0) as total_pendapatan,
        COALESCE(SUM(total_modal), 0) as total_modal,
        COALESCE(SUM(total_keuntungan), 0) as total_keuntungan
    FROM transaksi_penjualan
    WHERE DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ?
", [$bulan]);

// Summary Per Hari
$summary_per_hari = fetchAll("
    SELECT 
        DATE(tanggal_transaksi) as tanggal,
        COUNT(*) as total_transaksi,
        COALESCE(SUM(total_harga), 0) as total_pendapatan,
        COALESCE(SUM(total_modal), 0) as total_modal,
        COALESCE(SUM(total_keuntungan), 0) as total_keuntungan
    FROM transaksi_penjualan
    WHERE DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ?
    GROUP BY DATE(tanggal_transaksi)
    ORDER BY tanggal
", [$bulan]);

// Menu Terlaris Bulan Ini
$menu_terlaris = fetchAll("
    SELECT 
        m.nama_menu, m.harga_jual,
        SUM(dt.jumlah) as total_terjual,
        COALESCE(SUM(dt.subtotal), 0) as total_pendapatan,
        COALESCE(SUM(dt.subtotal - dt.subtotal_modal), 0) as total_keuntungan
    FROM detail_transaksi dt
    JOIN menu_makanan m ON dt.menu_id = m.id
    JOIN transaksi_penjualan tp ON dt.transaksi_id = tp.id
    WHERE DATE_FORMAT(tp.tanggal_transaksi, '%Y-%m') = ?
    GROUP BY m.id, m.nama_menu, m.harga_jual
    ORDER BY total_terjual DESC
    LIMIT 10
", [$bulan]);

// Pembelian Bahan Bulanan
$pembelian_bulanan = fetchOne("
    SELECT 
        COUNT(*) as total_pembelian,
        COALESCE(SUM(total_harga), 0) as total_nilai
    FROM pembelian_bahan
    WHERE DATE_FORMAT(tanggal_beli, '%Y-%m') = ?
", [$bulan]);

// Stock Movement Bulanan
$stock_movement_bulanan = fetchOne("
    SELECT 
        COUNT(*) as total_movement,
        COALESCE(SUM(CASE WHEN jenis_pergerakan IN ('rusak', 'tumpah', 'expired', 'hilang') THEN total_nilai ELSE 0 END), 0) as total_kerugian
    FROM stock_movement
    WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
", [$bulan]);

// Kas Bulanan - Ensure default values if query returns null
$kas_bulanan_result = fetchOne("
    SELECT 
        COALESCE(SUM(total_masuk), 0) as total_masuk,
        COALESCE(SUM(total_keluar), 0) as total_keluar
    FROM saldo_kas
    WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?
", [$bulan]);

$kas_bulanan = [
    'total_masuk' => floatval($kas_bulanan_result['total_masuk'] ?? 0),
    'total_keluar' => floatval($kas_bulanan_result['total_keluar'] ?? 0)
];

// Saldo Awal Bulan
$saldo_awal_bulan = fetchOne("
    SELECT COALESCE(saldo_awal, 0) as saldo_awal 
    FROM saldo_kas 
    WHERE tanggal >= ? 
    ORDER BY tanggal ASC 
    LIMIT 1
", ["$bulan-01"]);

// Saldo Akhir Bulan
$saldo_akhir_bulan = fetchOne("
    SELECT COALESCE(saldo_akhir, 0) as saldo_akhir 
    FROM saldo_kas 
    WHERE tanggal <= LAST_DAY(?)
    ORDER BY tanggal DESC 
    LIMIT 1
", ["$bulan-01"]);

// Ensure all values are numeric (not null)
$summary_bulanan['total_transaksi'] = intval($summary_bulanan['total_transaksi'] ?? 0);
$summary_bulanan['total_pendapatan'] = floatval($summary_bulanan['total_pendapatan'] ?? 0);
$summary_bulanan['total_modal'] = floatval($summary_bulanan['total_modal'] ?? 0);
$summary_bulanan['total_keuntungan'] = floatval($summary_bulanan['total_keuntungan'] ?? 0);

$pembelian_bulanan['total_pembelian'] = intval($pembelian_bulanan['total_pembelian'] ?? 0);
$pembelian_bulanan['total_nilai'] = floatval($pembelian_bulanan['total_nilai'] ?? 0);

$stock_movement_bulanan['total_movement'] = intval($stock_movement_bulanan['total_movement'] ?? 0);
$stock_movement_bulanan['total_kerugian'] = floatval($stock_movement_bulanan['total_kerugian'] ?? 0);

$kas_bulanan['total_masuk'] = floatval($kas_bulanan['total_masuk'] ?? 0);
$kas_bulanan['total_keluar'] = floatval($kas_bulanan['total_keluar'] ?? 0);

$saldo_awal = floatval($saldo_awal_bulan['saldo_awal'] ?? 0);
$saldo_akhir = floatval($saldo_akhir_bulan['saldo_akhir'] ?? 0);

$nama_bulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-calendar-month"></i> Laporan Bulanan</h2>
        <p class="text-muted">Periode: <strong><?php echo $nama_bulan[$bulan_num]; ?> <?php echo $tahun; ?></strong></p>
    </div>
</div>

<!-- Filter -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="page" value="laporan_bulanan">
                    <div class="col-md-3">
                        <label class="form-label">Pilih Bulan</label>
                        <input type="month" class="form-control" name="bulan" value="<?php echo $bulan; ?>" max="<?php echo date('Y-m'); ?>">
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
        <div class="card dashboard-card card-primary">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Total Transaksi</h6>
                    <h3 class="mb-0"><?php echo $summary_bulanan['total_transaksi']; ?></h3>
                </div>
                <div class="icon"><i class="bi bi-receipt text-primary"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card card-success">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Pendapatan</h6>
                    <h5 class="text-success"><?php echo formatRupiah($summary_bulanan['total_pendapatan']); ?></h5>
                </div>
                <div class="icon"><i class="bi bi-cash-stack text-success"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card card-warning">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Total HPP</h6>
                    <h5 class="text-warning"><?php echo formatRupiah($summary_bulanan['total_modal']); ?></h5>
                </div>
                <div class="icon"><i class="bi bi-coin text-warning"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card card-success">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Keuntungan</h6>
                    <h5 class="text-success"><strong><?php echo formatRupiah($summary_bulanan['total_keuntungan']); ?></strong></h5>
                </div>
                <div class="icon"><i class="bi bi-graph-up-arrow text-success"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Grafik Penjualan Harian -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-graph-up"></i> Grafik Penjualan Harian
            </div>
            <div class="card-body">
                <?php if (empty($summary_per_hari)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-3">Tidak ada transaksi pada bulan ini</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th class="text-center">Transaksi</th>
                                    <th class="text-end">Pendapatan</th>
                                    <th class="text-end">HPP</th>
                                    <th class="text-end">Keuntungan</th>
                                    <th class="text-end">Margin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($summary_per_hari as $spd): 
                                    $margin = $spd['total_pendapatan'] > 0 ? ($spd['total_keuntungan'] / $spd['total_pendapatan']) * 100 : 0;
                                ?>
                                <tr>
                                    <td><?php echo formatTanggal($spd['tanggal'], 'd/m/Y'); ?></td>
                                    <td class="text-center"><?php echo $spd['total_transaksi']; ?></td>
                                    <td class="text-end"><?php echo formatRupiah($spd['total_pendapatan']); ?></td>
                                    <td class="text-end"><?php echo formatRupiah($spd['total_modal']); ?></td>
                                    <td class="text-end text-success"><strong><?php echo formatRupiah($spd['total_keuntungan']); ?></strong></td>
                                    <td class="text-end"><small><?php echo number_format($margin, 1); ?>%</small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <th>TOTAL</th>
                                    <th class="text-center"><?php echo $summary_bulanan['total_transaksi']; ?></th>
                                    <th class="text-end"><?php echo formatRupiah($summary_bulanan['total_pendapatan']); ?></th>
                                    <th class="text-end"><?php echo formatRupiah($summary_bulanan['total_modal']); ?></th>
                                    <th class="text-end"><?php echo formatRupiah($summary_bulanan['total_keuntungan']); ?></th>
                                    <th class="text-end">
                                        <?php 
                                        $margin_total = $summary_bulanan['total_pendapatan'] > 0 ? ($summary_bulanan['total_keuntungan'] / $summary_bulanan['total_pendapatan']) * 100 : 0;
                                        echo number_format($margin_total, 1); 
                                        ?>%
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Statistik -->
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <h6 class="text-muted">Rata-rata Harian</h6>
                                <h4><?php echo formatRupiah($summary_bulanan['total_pendapatan'] / count($summary_per_hari)); ?></h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <h6 class="text-muted">Hari Terbaik</h6>
                                <?php 
                                $hari_terbaik = $summary_per_hari[0];
                                foreach ($summary_per_hari as $spd) {
                                    if ($spd['total_pendapatan'] > $hari_terbaik['total_pendapatan']) {
                                        $hari_terbaik = $spd;
                                    }
                                }
                                ?>
                                <h4><?php echo formatRupiah($hari_terbaik['total_pendapatan']); ?></h4>
                                <small class="text-muted"><?php echo formatTanggal($hari_terbaik['tanggal'], 'd/m/Y'); ?></small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <h6 class="text-muted">Margin Keuntungan</h6>
                                <h4 class="text-success"><?php echo number_format($margin_total, 1); ?>%</h4>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Menu Terlaris -->
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header bg-success text-white">
                <i class="bi bi-trophy"></i> Top 10 Menu Terlaris
            </div>
            <div class="card-body">
                <?php if (empty($menu_terlaris)): ?>
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-inbox"></i><br>
                        Tidak ada data
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Menu</th>
                                    <th class="text-center">Terjual</th>
                                    <th class="text-end">Pendapatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($menu_terlaris as $idx => $menu): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?php echo $idx < 3 ? 'warning' : 'secondary'; ?>">
                                            #<?php echo $idx + 1; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo $menu['nama_menu']; ?></strong><br>
                                        <small class="text-muted"><?php echo formatRupiah($menu['harga_jual']); ?></small>
                                    </td>
                                    <td class="text-center"><strong><?php echo $menu['total_terjual']; ?>×</strong></td>
                                    <td class="text-end">
                                        <?php echo formatRupiah($menu['total_pendapatan']); ?><br>
                                        <small class="text-success">+<?php echo formatRupiah($menu['total_keuntungan']); ?></small>
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

    <!-- Summary Bulanan -->
    <div class="col-md-6 mb-3">
        <!-- Kas -->
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <i class="bi bi-wallet2"></i> Ringkasan Kas
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Saldo Awal Bulan:</span>
                    <strong><?php echo formatRupiah($saldo_awal); ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2 text-success">
                    <span>Total Kas Masuk:</span>
                    <strong>+<?php echo formatRupiah($kas_bulanan['total_masuk']); ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2 text-danger">
                    <span>Total Kas Keluar:</span>
                    <strong>-<?php echo formatRupiah($kas_bulanan['total_keluar']); ?></strong>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <strong>Saldo Akhir Bulan:</strong>
                    <h5 class="mb-0"><strong><?php echo formatRupiah($saldo_akhir); ?></strong></h5>
                </div>
            </div>
        </div>

        <!-- Pembelian & Kerugian -->
        <div class="card">
            <div class="card-header bg-warning">
                <i class="bi bi-exclamation-triangle"></i> Pengeluaran & Kerugian
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Pembelian Bahan:</span>
                        <strong class="text-danger"><?php echo formatRupiah($pembelian_bulanan['total_nilai']); ?></strong>
                    </div>
                    <small class="text-muted"><?php echo $pembelian_bulanan['total_pembelian']; ?> transaksi pembelian</small>
                </div>
                <hr>
                <div>
                    <div class="d-flex justify-content-between">
                        <span>Kerugian Stock:</span>
                        <strong class="text-danger"><?php echo formatRupiah($stock_movement_bulanan['total_kerugian']); ?></strong>
                    </div>
                    <small class="text-muted">Dari <?php echo $stock_movement_bulanan['total_movement']; ?> stock movement</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ringkasan Finansial Lengkap -->
<div class="row">
    <div class="col-md-12">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-calculator"></i> Ringkasan Finansial Bulanan
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted">Pendapatan & Keuntungan</h6>
                        <table class="table table-sm">
                            <tr>
                                <td>Pendapatan Penjualan</td>
                                <td class="text-end"><strong><?php echo formatRupiah($summary_bulanan['total_pendapatan']); ?></strong></td>
                            </tr>
                            <tr>
                                <td>Harga Pokok Penjualan (HPP)</td>
                                <td class="text-end text-danger">(<?php echo formatRupiah($summary_bulanan['total_modal']); ?>)</td>
                            </tr>
                            <tr class="table-light">
                                <td><strong>Laba Kotor</strong></td>
                                <td class="text-end"><strong class="text-success"><?php echo formatRupiah($summary_bulanan['total_keuntungan']); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Pengeluaran & Laba Bersih</h6>
                        <table class="table table-sm">
                            <tr>
                                <td>Kerugian Stock Movement</td>
                                <td class="text-end text-danger">(<?php echo formatRupiah($stock_movement_bulanan['total_kerugian']); ?>)</td>
                            </tr>
                            <tr>
                                <td>Pembelian Bahan</td>
                                <td class="text-end text-danger">(<?php echo formatRupiah($pembelian_bulanan['total_nilai']); ?>)</td>
                            </tr>
                            <tr class="table-success">
                                <td><strong>LABA BERSIH</strong></td>
                                <td class="text-end">
                                    <?php 
                                    $laba_bersih = $summary_bulanan['total_keuntungan'] - $stock_movement_bulanan['total_kerugian'];
                                    ?>
                                    <h5 class="mb-0 text-success"><strong><?php echo formatRupiah($laba_bersih); ?></strong></h5>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .navbar, .breadcrumb, .btn, .card-body form { display: none !important; }
    .card { border: 1px solid #ddd !important; page-break-inside: avoid; margin-bottom: 10px; }
    body { font-size: 10px; }
}
</style>