<?php
/**
 * LAPORAN PENJUALAN BULANAN
 * Menampilkan ringkasan penjualan per bulan
 */

// Default bulan ini
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
$tahun = substr($bulan, 0, 4);
$bln = substr($bulan, 5, 2);

// Ringkasan bulan ini
$ringkasan = fetchOne("
    SELECT 
        COUNT(*) as total_transaksi,
        COALESCE(SUM(total_harga), 0) as total_pendapatan,
        COALESCE(SUM(total_modal), 0) as total_modal,
        COALESCE(SUM(total_keuntungan), 0) as total_keuntungan
    FROM transaksi_penjualan
    WHERE DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ?
", [$bulan]);

// Penjualan per hari
$per_hari = fetchAll("
    SELECT 
        DATE(tanggal_transaksi) as tanggal,
        COUNT(*) as jumlah_transaksi,
        SUM(total_harga) as total_pendapatan,
        SUM(total_keuntungan) as total_keuntungan
    FROM transaksi_penjualan
    WHERE DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ?
    GROUP BY DATE(tanggal_transaksi)
    ORDER BY tanggal ASC
", [$bulan]);

// Menu terlaris bulan ini
$menu_terlaris = fetchAll("
    SELECT 
        m.nama_menu,
        k.nama_kategori,
        SUM(dt.jumlah) as total_terjual,
        SUM(dt.subtotal) as total_pendapatan,
        SUM(dt.subtotal_modal) as total_modal,
        SUM(dt.subtotal - dt.subtotal_modal) as total_keuntungan
    FROM detail_transaksi dt
    JOIN menu_makanan m ON dt.menu_id = m.id
    JOIN kategori_menu k ON m.kategori_id = k.id
    JOIN transaksi_penjualan tp ON dt.transaksi_id = tp.id
    WHERE DATE_FORMAT(tp.tanggal_transaksi, '%Y-%m') = ?
    GROUP BY dt.menu_id, m.nama_menu, k.nama_kategori
    ORDER BY total_terjual DESC
    LIMIT 15
", [$bulan]);

// Perbandingan dengan bulan sebelumnya
$bulan_lalu = date('Y-m', strtotime($bulan . '-01 -1 month'));
$ringkasan_lalu = fetchOne("
    SELECT 
        COALESCE(SUM(total_harga), 0) as total_pendapatan,
        COALESCE(SUM(total_keuntungan), 0) as total_keuntungan
    FROM transaksi_penjualan
    WHERE DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ?
", [$bulan_lalu]);

// Hitung pertumbuhan
$growth_pendapatan = 0;
$growth_keuntungan = 0;
if ($ringkasan_lalu['total_pendapatan'] > 0) {
    $growth_pendapatan = (($ringkasan['total_pendapatan'] - $ringkasan_lalu['total_pendapatan']) / $ringkasan_lalu['total_pendapatan']) * 100;
}
if ($ringkasan_lalu['total_keuntungan'] > 0) {
    $growth_keuntungan = (($ringkasan['total_keuntungan'] - $ringkasan_lalu['total_keuntungan']) / $ringkasan_lalu['total_keuntungan']) * 100;
}

// Nama bulan
$nama_bulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-calendar-month"></i> Laporan Penjualan Bulanan</h2>
            <p class="text-muted">Ringkasan penjualan bulan <?php echo $nama_bulan[$bln] . ' ' . $tahun; ?></p>
        </div>
        <button onclick="window.print()" class="btn btn-primary no-print">
            <i class="bi bi-printer"></i> Cetak
        </button>
    </div>

    <!-- Filter Bulan -->
    <div class="card mb-4 no-print">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-funnel"></i> Pilih Bulan</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="index.php" class="row g-3">
                <input type="hidden" name="page" value="laporan_bulanan">
                <div class="col-md-3">
                    <label class="form-label">Bulan</label>
                    <input type="month" class="form-control" name="bulan" value="<?php echo $bulan; ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card dashboard-card card-primary">
                <div class="card-body">
                    <h6 class="text-muted">Total Transaksi</h6>
                    <h2 class="text-primary"><?php echo number_format($ringkasan['total_transaksi']); ?></h2>
                    <small class="text-muted">transaksi</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card card-success">
                <div class="card-body">
                    <h6 class="text-muted">Total Pendapatan</h6>
                    <h3 class="text-success"><?php echo formatRupiah($ringkasan['total_pendapatan']); ?></h3>
                    <?php if ($growth_pendapatan != 0): ?>
                        <small class="<?php echo $growth_pendapatan > 0 ? 'text-success' : 'text-danger'; ?>">
                            <i class="bi bi-<?php echo $growth_pendapatan > 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                            <?php echo abs(number_format($growth_pendapatan, 1)); ?>% vs bulan lalu
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card card-warning">
                <div class="card-body">
                    <h6 class="text-muted">Total Modal</h6>
                    <h4 class="text-warning"><?php echo formatRupiah($ringkasan['total_modal']); ?></h4>
                    <?php if ($ringkasan['total_pendapatan'] > 0): ?>
                        <small class="text-muted">
                            <?php echo number_format(($ringkasan['total_modal'] / $ringkasan['total_pendapatan']) * 100, 1); ?>% dari pendapatan
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card card-info">
                <div class="card-body">
                    <h6 class="text-muted">Keuntungan Bersih</h6>
                    <h3 class="text-info"><?php echo formatRupiah($ringkasan['total_keuntungan']); ?></h3>
                    <?php if ($growth_keuntungan != 0): ?>
                        <small class="<?php echo $growth_keuntungan > 0 ? 'text-success' : 'text-danger'; ?>">
                            <i class="bi bi-<?php echo $growth_keuntungan > 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                            <?php echo abs(number_format($growth_keuntungan, 1)); ?>% vs bulan lalu
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Grafik Penjualan Harian -->
        <div class="col-md-7 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-graph-up"></i> Grafik Penjualan per Hari</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($per_hari)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-inbox"></i>
                            <p>Belum ada transaksi bulan ini</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th class="text-center">Transaksi</th>
                                        <th class="text-end">Pendapatan</th>
                                        <th class="text-end">Keuntungan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($per_hari as $hari): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y (D)', strtotime($hari['tanggal'])); ?></td>
                                        <td class="text-center"><?php echo $hari['jumlah_transaksi']; ?>x</td>
                                        <td class="text-end"><strong><?php echo formatRupiah($hari['total_pendapatan']); ?></strong></td>
                                        <td class="text-end text-success"><?php echo formatRupiah($hari['total_keuntungan']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>TOTAL</th>
                                        <th class="text-center"><?php echo $ringkasan['total_transaksi']; ?>x</th>
                                        <th class="text-end"><?php echo formatRupiah($ringkasan['total_pendapatan']); ?></th>
                                        <th class="text-end"><?php echo formatRupiah($ringkasan['total_keuntungan']); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Menu Terlaris -->
        <div class="col-md-5 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-trophy"></i> Menu Terlaris Bulan Ini</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($menu_terlaris)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-inbox"></i>
                            <p>Belum ada penjualan</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Menu</th>
                                        <th class="text-center">Terjual</th>
                                        <th class="text-end">Untung</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $rank = 1;
                                    foreach ($menu_terlaris as $item): 
                                    ?>
                                    <tr>
                                        <td>
                                            <?php if ($rank <= 3): ?>
                                                <i class="bi bi-trophy-fill <?php 
                                                    echo $rank == 1 ? 'text-warning' : ($rank == 2 ? 'text-secondary' : '');
                                                ?>" style="<?php echo $rank == 3 ? 'color: #CD7F32;' : ''; ?>"></i>
                                            <?php else: ?>
                                                <?php echo $rank; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo $item['nama_menu']; ?></strong><br>
                                            <small class="text-muted"><?php echo $item['nama_kategori']; ?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?php echo $item['total_terjual']; ?></span>
                                        </td>
                                        <td class="text-end">
                                            <strong class="text-success"><?php echo formatRupiah($item['total_keuntungan']); ?></strong>
                                        </td>
                                    </tr>
                                    <?php 
                                    $rank++;
                                    endforeach; 
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Statistik Tambahan -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Statistik</h5>
                </div>
                <div class="card-body">
                    <?php if ($ringkasan['total_transaksi'] > 0): ?>
                        <div class="mb-3">
                            <small class="text-muted">Rata-rata per Transaksi</small>
                            <h5><?php echo formatRupiah($ringkasan['total_pendapatan'] / $ringkasan['total_transaksi']); ?></h5>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Rata-rata per Hari</small>
                            <h5><?php echo formatRupiah($ringkasan['total_pendapatan'] / count($per_hari)); ?></h5>
                        </div>
                        <div class="mb-0">
                            <small class="text-muted">Margin Keuntungan</small>
                            <h5><?php echo number_format(($ringkasan['total_keuntungan'] / $ringkasan['total_pendapatan']) * 100, 2); ?>%</h5>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Belum ada data statistik</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
        page-break-inside: avoid;
    }
}
</style>