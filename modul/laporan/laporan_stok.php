<?php
/**
 * LAPORAN STOK BAHAN BAKU
 * Monitoring stok, nilai persediaan, dan stok kritis
 */

// Ambil semua data bahan dengan nilai
$bahan_list = fetchAll("
    SELECT 
        b.*,
        (b.stok_tersedia * b.harga_beli_per_satuan) as nilai_stok,
        CASE 
            WHEN b.stok_tersedia <= 0 THEN 'habis'
            WHEN b.stok_tersedia <= b.stok_minimum THEN 'kritis'
            ELSE 'aman'
        END as status_stok
    FROM bahan_baku b
    ORDER BY 
        CASE 
            WHEN b.stok_tersedia <= 0 THEN 1
            WHEN b.stok_tersedia <= b.stok_minimum THEN 2
            ELSE 3
        END,
        b.nama_bahan ASC
");

// Hitung ringkasan
$total_item = count($bahan_list);
$total_nilai = 0;
$stok_habis = 0;
$stok_kritis = 0;
$stok_aman = 0;

foreach ($bahan_list as $bahan) {
    $total_nilai += $bahan['nilai_stok'];
    if ($bahan['status_stok'] == 'habis') $stok_habis++;
    elseif ($bahan['status_stok'] == 'kritis') $stok_kritis++;
    else $stok_aman++;
}

// Movement bulan ini
$bulan_ini = date('Y-m');
$movement_bulan = fetchOne("
    SELECT 
        COALESCE(SUM(CASE WHEN jenis_pergerakan = 'masuk' THEN total_nilai ELSE 0 END), 0) as nilai_masuk,
        COALESCE(SUM(CASE WHEN jenis_pergerakan = 'keluar' THEN total_nilai ELSE 0 END), 0) as nilai_keluar
    FROM stock_movement
    WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
", [$bulan_ini]);

// Bahan dengan pergerakan tertinggi bulan ini
$top_movement = fetchAll("
    SELECT 
        b.nama_bahan,
        b.satuan,
        SUM(CASE WHEN sm.jenis_pergerakan = 'masuk' THEN sm.jumlah ELSE 0 END) as total_masuk,
        SUM(CASE WHEN sm.jenis_pergerakan = 'keluar' THEN sm.jumlah ELSE 0 END) as total_keluar,
        SUM(sm.total_nilai) as total_nilai_movement
    FROM stock_movement sm
    JOIN bahan_baku b ON sm.bahan_id = b.id
    WHERE DATE_FORMAT(sm.created_at, '%Y-%m') = ?
    GROUP BY sm.bahan_id, b.nama_bahan, b.satuan
    ORDER BY total_nilai_movement DESC
    LIMIT 10
", [$bulan_ini]);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-box-seam"></i> Laporan Stok Bahan Baku</h2>
            <p class="text-muted">Monitoring persediaan dan nilai stok</p>
        </div>
        <button onclick="window.print()" class="btn btn-primary no-print">
            <i class="bi bi-printer"></i> Cetak
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card dashboard-card card-primary">
                <div class="card-body text-center">
                    <h6 class="text-muted">Total Item Bahan</h6>
                    <h2 class="text-primary"><?php echo $total_item; ?></h2>
                    <small class="text-muted">jenis bahan</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card card-success">
                <div class="card-body text-center">
                    <h6 class="text-muted">Total Nilai Stok</h6>
                    <h3 class="text-success"><?php echo formatRupiah($total_nilai); ?></h3>
                    <small class="text-muted">nilai persediaan</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card card-danger">
                <div class="card-body text-center">
                    <h6 class="text-muted">Stok Habis/Kritis</h6>
                    <h2 class="text-danger"><?php echo ($stok_habis + $stok_kritis); ?></h2>
                    <small class="text-muted">
                        Habis: <?php echo $stok_habis; ?> | Kritis: <?php echo $stok_kritis; ?>
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card card-info">
                <div class="card-body text-center">
                    <h6 class="text-muted">Stok Aman</h6>
                    <h2 class="text-info"><?php echo $stok_aman; ?></h2>
                    <small class="text-muted">
                        <?php echo $total_item > 0 ? number_format(($stok_aman / $total_item) * 100, 1) : 0; ?>% dari total
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Stok Kritis -->
    <?php if (($stok_habis + $stok_kritis) > 0): ?>
    <div class="alert alert-warning no-print">
        <h5><i class="bi bi-exclamation-triangle"></i> Peringatan Stok!</h5>
        <p class="mb-0">
            Ada <strong><?php echo ($stok_habis + $stok_kritis); ?> bahan</strong> yang stoknya habis atau di bawah minimum. 
            Segera lakukan pembelian untuk bahan-bahan tersebut.
        </p>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Tabel Stok Bahan -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-table"></i> Detail Stok Bahan Baku</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Bahan</th>
                                    <th>Stok</th>
                                    <th>Min</th>
                                    <th>Satuan</th>
                                    <th class="text-end">Harga/Unit</th>
                                    <th class="text-end">Nilai Stok</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bahan_list as $bahan): ?>
                                <tr class="<?php echo $bahan['status_stok'] == 'habis' ? 'table-danger' : ($bahan['status_stok'] == 'kritis' ? 'table-warning' : ''); ?>">
                                    <td><small><?php echo $bahan['kode_bahan']; ?></small></td>
                                    <td><strong><?php echo $bahan['nama_bahan']; ?></strong></td>
                                    <td>
                                        <strong class="<?php echo $bahan['status_stok'] == 'habis' ? 'text-danger' : ($bahan['status_stok'] == 'kritis' ? 'text-warning' : ''); ?>">
                                            <?php echo number_format($bahan['stok_tersedia'], 2); ?>
                                        </strong>
                                    </td>
                                    <td><small class="text-muted"><?php echo number_format($bahan['stok_minimum'], 2); ?></small></td>
                                    <td><small><?php echo $bahan['satuan']; ?></small></td>
                                    <td class="text-end"><small><?php echo formatRupiah($bahan['harga_beli_per_satuan']); ?></small></td>
                                    <td class="text-end"><strong><?php echo formatRupiah($bahan['nilai_stok']); ?></strong></td>
                                    <td>
                                        <?php if ($bahan['status_stok'] == 'habis'): ?>
                                            <span class="badge bg-danger">Habis</span>
                                        <?php elseif ($bahan['status_stok'] == 'kritis'): ?>
                                            <span class="badge bg-warning text-dark">Kritis</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Aman</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="6" class="text-end">TOTAL NILAI PERSEDIAAN:</th>
                                    <th class="text-end"><?php echo formatRupiah($total_nilai); ?></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Tambahan -->
        <div class="col-md-4 mb-4">
            <!-- Movement Bulan Ini -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-arrow-left-right"></i> Movement Bulan Ini</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Nilai Masuk</small>
                        <h4 class="text-success"><?php echo formatRupiah($movement_bulan['nilai_masuk']); ?></h4>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted">Nilai Keluar</small>
                        <h4 class="text-danger"><?php echo formatRupiah($movement_bulan['nilai_keluar']); ?></h4>
                    </div>
                    <hr>
                    <div>
                        <small class="text-muted">Selisih</small>
                        <h4 class="<?php echo ($movement_bulan['nilai_masuk'] - $movement_bulan['nilai_keluar']) >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo formatRupiah($movement_bulan['nilai_masuk'] - $movement_bulan['nilai_keluar']); ?>
                        </h4>
                    </div>
                </div>
            </div>

            <!-- Top Movement -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-graph-up"></i> Bahan Paling Aktif</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($top_movement)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-inbox"></i>
                            <p>Belum ada movement bulan ini</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Bahan</th>
                                        <th class="text-center">Masuk</th>
                                        <th class="text-center">Keluar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_movement as $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $item['nama_bahan']; ?></strong><br>
                                            <small class="text-muted"><?php echo $item['satuan']; ?></small>
                                        </td>
                                        <td class="text-center">
                                            <small class="text-success"><?php echo number_format($item['total_masuk'], 1); ?></small>
                                        </td>
                                        <td class="text-center">
                                            <small class="text-danger"><?php echo number_format($item['total_keluar'], 1); ?></small>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-3 no-print">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-lightning"></i> Aksi Cepat</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="index.php?page=pembelian_bahan" class="btn btn-primary">
                            <i class="bi bi-cart-plus"></i> Beli Bahan
                        </a>
                        <a href="index.php?page=list_movement" class="btn btn-info">
                            <i class="bi bi-arrow-left-right"></i> Stock Movement
                        </a>
                        <a href="index.php?page=tambah_opname" class="btn btn-warning">
                            <i class="bi bi-clipboard-check"></i> Stock Opname
                        </a>
                    </div>
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
        font-size: 11px;
    }
}
</style>