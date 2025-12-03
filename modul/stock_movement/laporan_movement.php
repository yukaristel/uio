<?php
/**
 * LAPORAN STOCK MOVEMENT
 * Step 53/64 (82.8%)
 */

// Filter
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
$bahan_id = isset($_GET['bahan_id']) ? intval($_GET['bahan_id']) : 0;
$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';

// Parse bulan
list($tahun, $bulan_num) = explode('-', $bulan);
$tanggal_dari = "$bulan-01";
$tanggal_sampai = date('Y-m-t', strtotime($tanggal_dari));

// Build query
$where = "DATE(sm.created_at) BETWEEN ? AND ?";
$params = [$tanggal_dari, $tanggal_sampai];

if ($bahan_id > 0) {
    $where .= " AND sm.bahan_id = ?";
    $params[] = $bahan_id;
}

if (!empty($jenis)) {
    $where .= " AND sm.jenis_pergerakan = ?";
    $params[] = $jenis;
}

// Summary per jenis
$summary_jenis = fetchAll("
    SELECT 
        jenis_pergerakan,
        COUNT(*) as jumlah_movement,
        SUM(total_nilai) as total_nilai
    FROM stock_movement sm
    WHERE $where
    GROUP BY jenis_pergerakan
    ORDER BY total_nilai DESC
", $params);

// Summary per bahan
$summary_bahan = fetchAll("
    SELECT 
        b.nama_bahan, b.satuan,
        COUNT(*) as jumlah_movement,
        SUM(CASE WHEN sm.jenis_pergerakan = 'masuk' THEN sm.jumlah ELSE 0 END) as total_masuk,
        SUM(CASE WHEN sm.jenis_pergerakan = 'keluar' THEN sm.jumlah ELSE 0 END) as total_keluar,
        SUM(CASE WHEN sm.jenis_pergerakan IN ('rusak', 'tumpah', 'expired', 'hilang') THEN sm.total_nilai ELSE 0 END) as total_kerugian
    FROM stock_movement sm
    JOIN bahan_baku b ON sm.bahan_id = b.id
    WHERE $where
    GROUP BY b.id, b.nama_bahan, b.satuan
    ORDER BY total_kerugian DESC
", $params);

// Total summary
$total_summary = fetchOne("
    SELECT 
        COUNT(*) as total_movement,
        SUM(CASE WHEN jenis_pergerakan = 'masuk' THEN total_nilai ELSE 0 END) as nilai_masuk,
        SUM(CASE WHEN jenis_pergerakan = 'keluar' THEN total_nilai ELSE 0 END) as nilai_keluar,
        SUM(CASE WHEN jenis_pergerakan IN ('rusak', 'tumpah', 'expired', 'hilang') THEN total_nilai ELSE 0 END) as total_kerugian
    FROM stock_movement sm
    WHERE $where
", $params);

// Get bahan untuk filter
$bahan_list = fetchAll("SELECT id, nama_bahan FROM bahan_baku ORDER BY nama_bahan");

// Nama bulan
$nama_bulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$judul_periode = $nama_bulan[$bulan_num] . ' ' . $tahun;
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-file-earmark-bar-graph"></i> Laporan Stock Movement</h2>
        <p class="text-muted">Periode: <strong><?php echo $judul_periode; ?></strong></p>
    </div>
</div>

<!-- Total Summary -->
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card dashboard-card card-primary">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Total Movement</h6>
                    <h3 class="mb-0"><?php echo $total_summary['total_movement']; ?></h3>
                </div>
                <div class="icon"><i class="bi bi-arrow-left-right text-primary"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card card-success">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Nilai Masuk</h6>
                    <h5 class="text-success"><?php echo formatRupiah($total_summary['nilai_masuk']); ?></h5>
                </div>
                <div class="icon"><i class="bi bi-arrow-down-circle text-success"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card card-info">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Nilai Keluar</h6>
                    <h5 class="text-info"><?php echo formatRupiah($total_summary['nilai_keluar']); ?></h5>
                </div>
                <div class="icon"><i class="bi bi-arrow-up-circle text-info"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card card-danger">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Total Kerugian</h6>
                    <h5 class="text-danger"><?php echo formatRupiah($total_summary['total_kerugian']); ?></h5>
                </div>
                <div class="icon"><i class="bi bi-exclamation-triangle text-danger"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="page" value="laporan_movement">
                    
                    <div class="col-md-3">
                        <label class="form-label">Periode (Bulan)</label>
                        <input type="month" class="form-control" name="bulan" value="<?php echo $bulan; ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Bahan</label>
                        <select class="form-select" name="bahan_id">
                            <option value="">Semua Bahan</option>
                            <?php foreach ($bahan_list as $b): ?>
                            <option value="<?php echo $b['id']; ?>" <?php echo $bahan_id == $b['id'] ? 'selected' : ''; ?>>
                                <?php echo $b['nama_bahan']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Jenis</label>
                        <select class="form-select" name="jenis">
                            <option value="">Semua</option>
                            <option value="masuk" <?php echo $jenis == 'masuk' ? 'selected' : ''; ?>>Masuk</option>
                            <option value="keluar" <?php echo $jenis == 'keluar' ? 'selected' : ''; ?>>Keluar</option>
                            <option value="rusak" <?php echo $jenis == 'rusak' ? 'selected' : ''; ?>>Rusak</option>
                            <option value="tumpah" <?php echo $jenis == 'tumpah' ? 'selected' : ''; ?>>Tumpah</option>
                            <option value="expired" <?php echo $jenis == 'expired' ? 'selected' : ''; ?>>Expired</option>
                            <option value="hilang" <?php echo $jenis == 'hilang' ? 'selected' : ''; ?>>Hilang</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="btn-group w-100">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Filter
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

<div class="row">
    <!-- Summary per Jenis -->
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-pie-chart"></i> Summary per Jenis Movement
            </div>
            <div class="card-body">
                <?php if (empty($summary_jenis)): ?>
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-inbox"></i><br>
                        Tidak ada data
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Jenis</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-end">Total Nilai</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($summary_jenis as $sj): 
                                    $is_kerugian = in_array($sj['jenis_pergerakan'], ['rusak', 'tumpah', 'expired', 'hilang']);
                                    $badge_class = [
                                        'masuk' => 'bg-success',
                                        'keluar' => 'bg-info',
                                        'opname' => 'bg-primary',
                                        'rusak' => 'bg-danger',
                                        'tumpah' => 'bg-warning',
                                        'expired' => 'bg-danger',
                                        'hilang' => 'bg-dark'
                                    ];
                                    $class = $badge_class[$sj['jenis_pergerakan']] ?? 'bg-secondary';
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge <?php echo $class; ?>">
                                            <?php echo ucfirst($sj['jenis_pergerakan']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center"><?php echo $sj['jumlah_movement']; ?>×</td>
                                    <td class="text-end <?php echo $is_kerugian ? 'text-danger' : ''; ?>">
                                        <strong><?php echo formatRupiah($sj['total_nilai']); ?></strong>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-dark">
                                    <th>TOTAL</th>
                                    <th class="text-center"><?php echo $total_summary['total_movement']; ?>×</th>
                                    <th class="text-end">
                                        <?php echo formatRupiah($total_summary['nilai_masuk'] + $total_summary['nilai_keluar'] + $total_summary['total_kerugian']); ?>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Summary per Bahan -->
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header bg-info text-white">
                <i class="bi bi-bar-chart"></i> Summary per Bahan
            </div>
            <div class="card-body">
                <?php if (empty($summary_bahan)): ?>
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-inbox"></i><br>
                        Tidak ada data
                    </div>
                <?php else: ?>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Bahan</th>
                                    <th class="text-center">Movement</th>
                                    <th class="text-end">Kerugian</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($summary_bahan as $sb): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $sb['nama_bahan']; ?></strong><br>
                                        <small class="text-muted">
                                            Masuk: <?php echo number_format($sb['total_masuk'], 2); ?> 
                                            | Keluar: <?php echo number_format($sb['total_keluar'], 2); ?>
                                            <?php echo $sb['satuan']; ?>
                                        </small>
                                    </td>
                                    <td class="text-center"><?php echo $sb['jumlah_movement']; ?>×</td>
                                    <td class="text-end text-danger">
                                        <?php if ($sb['total_kerugian'] > 0): ?>
                                            <strong><?php echo formatRupiah($sb['total_kerugian']); ?></strong>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
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

<!-- Kerugian Detail -->
<?php if ($total_summary['total_kerugian'] > 0): ?>
<div class="row">
    <div class="col-md-12">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <i class="bi bi-exclamation-triangle"></i> Detail Kerugian
            </div>
            <div class="card-body">
                <?php
                $kerugian_detail = fetchAll("
                    SELECT sm.*, b.nama_bahan, u.nama_lengkap
                    FROM stock_movement sm
                    JOIN bahan_baku b ON sm.bahan_id = b.id
                    JOIN users u ON sm.user_id = u.id
                    WHERE $where AND sm.jenis_pergerakan IN ('rusak', 'tumpah', 'expired', 'hilang')
                    ORDER BY sm.total_nilai DESC
                ", $params);
                ?>
                
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Bahan</th>
                                <th>Jenis</th>
                                <th>Jumlah</th>
                                <th class="text-end">Nilai Kerugian</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_kerugian_detail = 0;
                            foreach ($kerugian_detail as $kd): 
                                $total_kerugian_detail += $kd['total_nilai'];
                            ?>
                            <tr>
                                <td><small><?php echo formatDateTime($kd['created_at'], 'd/m/Y'); ?></small></td>
                                <td><?php echo $kd['nama_bahan']; ?></td>
                                <td>
                                    <span class="badge bg-danger">
                                        <?php echo ucfirst($kd['jenis_pergerakan']); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($kd['jumlah'], 2); ?> <?php echo $kd['satuan']; ?></td>
                                <td class="text-end text-danger">
                                    <strong><?php echo formatRupiah($kd['total_nilai']); ?></strong>
                                </td>
                                <td><small><?php echo substr($kd['keterangan'], 0, 50); ?><?php echo strlen($kd['keterangan']) > 50 ? '...' : ''; ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-danger">
                                <th colspan="4" class="text-end">TOTAL KERUGIAN:</th>
                                <th class="text-end"><?php echo formatRupiah($total_kerugian_detail); ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Print Style -->
<style>
@media print {
    .navbar, .breadcrumb, .btn, .card-body form { display: none !important; }
    .card { border: 1px solid #ddd !important; page-break-inside: avoid; }
    body { font-size: 12px; }
}
</style>