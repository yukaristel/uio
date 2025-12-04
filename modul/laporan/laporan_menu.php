<?php
/**
 * LAPORAN PERFORMA MENU
 * Analisis penjualan, keuntungan, dan performa menu
 */

// Filter periode
$periode = isset($_GET['periode']) ? $_GET['periode'] : 'bulan_ini';

switch ($periode) {
    case 'hari_ini':
        $where = "DATE(tp.tanggal_transaksi) = CURDATE()";
        $label = "Hari Ini";
        break;
    case 'minggu_ini':
        $where = "YEARWEEK(tp.tanggal_transaksi, 1) = YEARWEEK(CURDATE(), 1)";
        $label = "Minggu Ini";
        break;
    case 'bulan_ini':
        $where = "DATE_FORMAT(tp.tanggal_transaksi, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
        $label = "Bulan Ini";
        break;
    case 'bulan_lalu':
        $where = "DATE_FORMAT(tp.tanggal_transaksi, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m')";
        $label = "Bulan Lalu";
        break;
    case '3_bulan':
        $where = "tp.tanggal_transaksi >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
        $label = "3 Bulan Terakhir";
        break;
    case 'tahun_ini':
        $where = "YEAR(tp.tanggal_transaksi) = YEAR(CURDATE())";
        $label = "Tahun Ini";
        break;
    default:
        $where = "DATE_FORMAT(tp.tanggal_transaksi, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
        $label = "Bulan Ini";
}

// Performa menu
$menu_stats = fetchAll("
    SELECT 
        m.id,
        m.kode_menu,
        m.nama_menu,
        m.harga_jual,
        m.harga_modal,
        m.margin_keuntungan,
        m.status,
        k.nama_kategori,
        COALESCE(SUM(dt.jumlah), 0) as total_terjual,
        COALESCE(SUM(dt.subtotal), 0) as total_pendapatan,
        COALESCE(SUM(dt.subtotal_modal), 0) as total_modal,
        COALESCE(SUM(dt.subtotal - dt.subtotal_modal), 0) as total_keuntungan,
        COUNT(DISTINCT dt.transaksi_id) as jumlah_transaksi
    FROM menu_makanan m
    LEFT JOIN kategori_menu k ON m.kategori_id = k.id
    LEFT JOIN detail_transaksi dt ON m.id = dt.menu_id
    LEFT JOIN transaksi_penjualan tp ON dt.transaksi_id = tp.id AND $where
    GROUP BY m.id, m.kode_menu, m.nama_menu, m.harga_jual, m.harga_modal, m.margin_keuntungan, m.status, k.nama_kategori
    ORDER BY total_terjual DESC, m.nama_menu ASC
");

// Hitung total
$total_menu = count($menu_stats);
$total_terjual = 0;
$total_pendapatan = 0;
$total_keuntungan = 0;
$menu_aktif = 0;
$menu_tidak_laku = 0;

foreach ($menu_stats as $menu) {
    $total_terjual += $menu['total_terjual'];
    $total_pendapatan += $menu['total_pendapatan'];
    $total_keuntungan += $menu['total_keuntungan'];
    
    if ($menu['total_terjual'] > 0) $menu_aktif++;
    else $menu_tidak_laku++;
}

// Kategori terlaris
$kategori_stats = fetchAll("
    SELECT 
        k.nama_kategori,
        COUNT(DISTINCT m.id) as jumlah_menu,
        COALESCE(SUM(dt.jumlah), 0) as total_terjual,
        COALESCE(SUM(dt.subtotal), 0) as total_pendapatan,
        COALESCE(SUM(dt.subtotal - dt.subtotal_modal), 0) as total_keuntungan
    FROM kategori_menu k
    LEFT JOIN menu_makanan m ON k.id = m.kategori_id
    LEFT JOIN detail_transaksi dt ON m.id = dt.menu_id
    LEFT JOIN transaksi_penjualan tp ON dt.transaksi_id = tp.id AND $where
    GROUP BY k.id, k.nama_kategori
    ORDER BY total_pendapatan DESC
");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-graph-up-arrow"></i> Laporan Performa Menu</h2>
            <p class="text-muted">Analisis penjualan dan keuntungan menu - <?php echo $label; ?></p>
        </div>
        <button onclick="window.print()" class="btn btn-primary no-print">
            <i class="bi bi-printer"></i> Cetak
        </button>
    </div>

    <!-- Filter Periode -->
    <div class="card mb-4 no-print">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-funnel"></i> Pilih Periode</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="index.php" class="row g-2">
                <input type="hidden" name="page" value="laporan_menu">
                <div class="col-md-10">
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="periode" value="hari_ini" id="hari_ini" <?php echo $periode == 'hari_ini' ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-primary" for="hari_ini">Hari Ini</label>

                        <input type="radio" class="btn-check" name="periode" value="minggu_ini" id="minggu_ini" <?php echo $periode == 'minggu_ini' ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-primary" for="minggu_ini">Minggu Ini</label>

                        <input type="radio" class="btn-check" name="periode" value="bulan_ini" id="bulan_ini" <?php echo $periode == 'bulan_ini' ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-primary" for="bulan_ini">Bulan Ini</label>

                        <input type="radio" class="btn-check" name="periode" value="bulan_lalu" id="bulan_lalu" <?php echo $periode == 'bulan_lalu' ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-primary" for="bulan_lalu">Bulan Lalu</label>

                        <input type="radio" class="btn-check" name="periode" value="3_bulan" id="3_bulan" <?php echo $periode == '3_bulan' ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-primary" for="3_bulan">3 Bulan</label>

                        <input type="radio" class="btn-check" name="periode" value="tahun_ini" id="tahun_ini" <?php echo $periode == 'tahun_ini' ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-primary" for="tahun_ini">Tahun Ini</label>
                    </div>
                </div>
                <div class="col-md-2">
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
                <div class="card-body text-center">
                    <h6 class="text-muted">Total Menu</h6>
                    <h2 class="text-primary"><?php echo $total_menu; ?></h2>
                    <small class="text-muted">
                        Aktif: <?php echo $menu_aktif; ?> | Tidak Laku: <?php echo $menu_tidak_laku; ?>
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card card-success">
                <div class="card-body text-center">
                    <h6 class="text-muted">Total Terjual</h6>
                    <h2 class="text-success"><?php echo number_format($total_terjual); ?></h2>
                    <small class="text-muted">porsi</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card card-info">
                <div class="card-body text-center">
                    <h6 class="text-muted">Total Pendapatan</h6>
                    <h3 class="text-info"><?php echo formatRupiah($total_pendapatan); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card card-warning">
                <div class="card-body text-center">
                    <h6 class="text-muted">Total Keuntungan</h6>
                    <h3 class="text-warning"><?php echo formatRupiah($total_keuntungan); ?></h3>
                    <?php if ($total_pendapatan > 0): ?>
                        <small class="text-muted">
                            Margin: <?php echo number_format(($total_keuntungan / $total_pendapatan) * 100, 1); ?>%
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Performa per Menu -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-table"></i> Detail Performa Menu</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Menu</th>
                                    <th>Kategori</th>
                                    <th class="text-center">Terjual</th>
                                    <th class="text-end">Pendapatan</th>
                                    <th class="text-end">Keuntungan</th>
                                    <th class="text-end">Margin</th>
                                    <th>Performa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rank = 1;
                                foreach ($menu_stats as $menu): 
                                    $margin = $menu['total_pendapatan'] > 0 ? ($menu['total_keuntungan'] / $menu['total_pendapatan']) * 100 : 0;
                                    
                                    // Kategori performa
                                    if ($menu['total_terjual'] == 0) {
                                        $performa = 'Tidak Laku';
                                        $badge = 'secondary';
                                    } elseif ($rank <= 5) {
                                        $performa = 'Best Seller';
                                        $badge = 'success';
                                    } elseif ($menu['total_terjual'] < 5 && $periode == 'bulan_ini') {
                                        $performa = 'Slow Moving';
                                        $badge = 'warning';
                                    } else {
                                        $performa = 'Normal';
                                        $badge = 'info';
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <?php if ($rank <= 3 && $menu['total_terjual'] > 0): ?>
                                            <i class="bi bi-trophy-fill <?php 
                                                echo $rank == 1 ? 'text-warning' : ($rank == 2 ? 'text-secondary' : '');
                                            ?>" style="<?php echo $rank == 3 ? 'color: #CD7F32;' : ''; ?>"></i>
                                        <?php else: ?>
                                            <?php echo $menu['total_terjual'] > 0 ? $rank : '-'; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo $menu['nama_menu']; ?></strong><br>
                                        <small class="text-muted"><?php echo formatRupiah($menu['harga_jual']); ?></small>
                                    </td>
                                    <td><small><?php echo $menu['nama_kategori']; ?></small></td>
                                    <td class="text-center">
                                        <strong><?php echo number_format($menu['total_terjual']); ?></strong>
                                    </td>
                                    <td class="text-end"><strong><?php echo formatRupiah($menu['total_pendapatan']); ?></strong></td>
                                    <td class="text-end text-success"><strong><?php echo formatRupiah($menu['total_keuntungan']); ?></strong></td>
                                    <td class="text-end"><small><?php echo number_format($margin, 1); ?>%</small></td>
                                    <td>
                                        <span class="badge bg-<?php echo $badge; ?>"><?php echo $performa; ?></span>
                                    </td>
                                </tr>
                                <?php 
                                if ($menu['total_terjual'] > 0) $rank++;
                                endforeach; 
                                ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="3" class="text-end">TOTAL:</th>
                                    <th class="text-center"><?php echo number_format($total_terjual); ?></th>
                                    <th class="text-end"><?php echo formatRupiah($total_pendapatan); ?></th>
                                    <th class="text-end"><?php echo formatRupiah($total_keuntungan); ?></th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Tambahan -->
        <div class="col-md-4 mb-4">
            <!-- Performa per Kategori -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Per Kategori</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th class="text-center">Terjual</th>
                                    <th class="text-end">Untung</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kategori_stats as $kat): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $kat['nama_kategori']; ?></strong><br>
                                        <small class="text-muted"><?php echo $kat['jumlah_menu']; ?> menu</small>
                                    </td>
                                    <td class="text-center"><?php echo number_format($kat['total_terjual']); ?></td>
                                    <td class="text-end">
                                        <strong class="text-success"><?php echo formatRupiah($kat['total_keuntungan']); ?></strong>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Insight -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-lightbulb"></i> Insight & Rekomendasi</h5>
                </div>
                <div class="card-body">
                    <?php if ($menu_tidak_laku > 0): ?>
                        <div class="alert alert-warning">
                            <strong><i class="bi bi-exclamation-triangle"></i> Perhatian!</strong><br>
                            Ada <strong><?php echo $menu_tidak_laku; ?> menu</strong> yang tidak laku sama sekali. Pertimbangkan untuk:
                            <ul class="mb-0 mt-2">
                                <li>Revisi harga</li>
                                <li>Promosi khusus</li>
                                <li>Evaluasi rasa/kualitas</li>
                                <li>Hapus dari menu</li>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($total_menu > 0): ?>
                        <h6>Statistik:</h6>
                        <ul>
                            <li><strong>Menu Aktif:</strong> <?php echo $menu_aktif; ?> (<?php echo number_format(($menu_aktif / $total_menu) * 100, 1); ?>%)</li>
                            <li><strong>Rata-rata per Menu:</strong> <?php echo number_format($total_terjual / max($menu_aktif, 1), 1); ?> porsi</li>
                            <li><strong>Pendapatan per Menu:</strong> <?php echo formatRupiah($total_pendapatan / max($menu_aktif, 1)); ?></li>
                        </ul>
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
    .table {
        font-size: 10px;
    }
}
</style>