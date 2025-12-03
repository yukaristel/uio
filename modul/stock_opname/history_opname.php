<?php
/**
 * HISTORY STOCK OPNAME (APPROVED ONLY)
 * Step 56/64 (87.5%)
 */

// Filter
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$bahan_id = isset($_GET['bahan_id']) ? intval($_GET['bahan_id']) : 0;

// Build query
$where = "YEAR(so.tanggal_opname) = ? AND so.status = 'approved'";
$params = [$tahun];

if ($bahan_id > 0) {
    $where .= " AND so.bahan_id = ?";
    $params[] = $bahan_id;
}

// Get history
$history = fetchAll("
    SELECT so.*, b.nama_bahan, b.satuan,
           u.nama_lengkap as dibuat_oleh,
           ua.nama_lengkap as approved_by_name
    FROM stock_opname so
    JOIN bahan_baku b ON so.bahan_id = b.id
    JOIN users u ON so.user_id = u.id
    LEFT JOIN users ua ON so.approved_by = ua.id
    WHERE $where
    ORDER BY so.tanggal_opname DESC, so.created_at DESC
", $params);

// Summary per bahan
$summary_bahan = fetchAll("
    SELECT 
        b.nama_bahan,
        COUNT(*) as jumlah_opname,
        SUM(CASE WHEN so.selisih < 0 THEN 1 ELSE 0 END) as kurang,
        SUM(CASE WHEN so.selisih > 0 THEN 1 ELSE 0 END) as lebih,
        SUM(CASE WHEN so.selisih = 0 THEN 1 ELSE 0 END) as sesuai,
        SUM(CASE WHEN so.selisih < 0 THEN so.nilai_selisih ELSE 0 END) as total_kerugian
    FROM stock_opname so
    JOIN bahan_baku b ON so.bahan_id = b.id
    WHERE $where
    GROUP BY b.id, b.nama_bahan
    ORDER BY total_kerugian ASC
", $params);

// Total summary
$total_summary = fetchOne("
    SELECT 
        COUNT(*) as total_opname,
        SUM(CASE WHEN selisih < 0 THEN 1 ELSE 0 END) as total_kurang,
        SUM(CASE WHEN selisih > 0 THEN 1 ELSE 0 END) as total_lebih,
        SUM(CASE WHEN selisih = 0 THEN 1 ELSE 0 END) as total_sesuai,
        COALESCE(SUM(CASE WHEN selisih < 0 THEN nilai_selisih ELSE 0 END), 0) as total_kerugian
    FROM stock_opname so
    WHERE $where
", $params);

// Get bahan untuk filter
$bahan_list = fetchAll("SELECT id, nama_bahan FROM bahan_baku ORDER BY nama_bahan");

// Group by bulan
$history_by_month = [];
foreach ($history as $h) {
    $month = date('Y-m', strtotime($h['tanggal_opname']));
    if (!isset($history_by_month[$month])) {
        $history_by_month[$month] = [];
    }
    $history_by_month[$month][] = $h;
}

// Nama bulan
$nama_bulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-clock-history"></i> History Stock Opname</h2>
        <p class="text-muted">Riwayat stock opname yang sudah di-approve - Tahun: <strong><?php echo $tahun; ?></strong></p>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card dashboard-card card-primary">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Total Opname</h6>
                    <h3 class="mb-0"><?php echo $total_summary['total_opname']; ?></h3>
                </div>
                <div class="icon"><i class="bi bi-clipboard-check text-primary"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card card-danger">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Stok Kurang</h6>
                    <h3 class="mb-0 text-danger"><?php echo $total_summary['total_kurang']; ?></h3>
                </div>
                <div class="icon"><i class="bi bi-arrow-down-circle text-danger"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card card-success">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Stok Lebih</h6>
                    <h3 class="mb-0 text-success"><?php echo $total_summary['total_lebih']; ?></h3>
                </div>
                <div class="icon"><i class="bi bi-arrow-up-circle text-success"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card card-danger">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Total Kerugian</h6>
                    <h5 class="text-danger"><?php echo formatRupiah(abs($total_summary['total_kerugian'])); ?></h5>
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
                    <input type="hidden" name="page" value="history_opname">
                    
                    <div class="col-md-2">
                        <label class="form-label">Tahun</label>
                        <select class="form-select" name="tahun">
                            <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $tahun == $y ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                            <?php endfor; ?>
                        </select>
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
                    
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <a href="index.php?page=list_opname" class="btn btn-secondary w-100">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Summary per Bahan -->
    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-header bg-info text-white">
                <i class="bi bi-bar-chart"></i> Summary per Bahan
            </div>
            <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                <?php if (empty($summary_bahan)): ?>
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-inbox"></i><br>
                        Tidak ada data
                    </div>
                <?php else: ?>
                    <?php foreach ($summary_bahan as $sb): ?>
                    <div class="mb-3 pb-3 border-bottom">
                        <strong><?php echo $sb['nama_bahan']; ?></strong>
                        <div class="mt-2">
                            <small class="text-muted">
                                Total: <span class="badge bg-primary"><?php echo $sb['jumlah_opname']; ?>×</span>
                                Kurang: <span class="badge bg-danger"><?php echo $sb['kurang']; ?></span>
                                Lebih: <span class="badge bg-success"><?php echo $sb['lebih']; ?></span>
                                Sesuai: <span class="badge bg-secondary"><?php echo $sb['sesuai']; ?></span>
                            </small>
                        </div>
                        <?php if ($sb['total_kerugian'] < 0): ?>
                        <div class="mt-2">
                            <small class="text-danger">
                                <i class="bi bi-exclamation-triangle"></i>
                                Kerugian: <strong><?php echo formatRupiah(abs($sb['total_kerugian'])); ?></strong>
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- History List -->
    <div class="col-md-8">
        <?php if (empty($history)): ?>
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p class="mt-3">Tidak ada history stock opname pada tahun <?php echo $tahun; ?></p>
                    <a href="index.php?page=tambah_opname" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Buat Opname Baru
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($history_by_month as $month => $items): 
                list($y, $m) = explode('-', $month);
            ?>
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-calendar-month"></i> <?php echo $nama_bulan[$m]; ?> <?php echo $y; ?>
                    <span class="badge bg-light text-dark float-end"><?php echo count($items); ?> opname</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>No. Opname</th>
                                    <th>Bahan</th>
                                    <th>Sistem</th>
                                    <th>Fisik</th>
                                    <th>Selisih</th>
                                    <th>Nilai</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><small><?php echo formatTanggal($item['tanggal_opname'], 'd/m'); ?></small></td>
                                    <td><small><strong><?php echo $item['no_opname']; ?></strong></small></td>
                                    <td><?php echo $item['nama_bahan']; ?></td>
                                    <td><small><?php echo number_format($item['stok_sistem'], 2); ?></small></td>
                                    <td><small><?php echo number_format($item['stok_fisik'], 2); ?></small></td>
                                    <td>
                                        <small class="<?php echo $item['selisih'] < 0 ? 'text-danger' : ($item['selisih'] > 0 ? 'text-success' : 'text-muted'); ?>">
                                            <strong>
                                                <?php echo $item['selisih'] > 0 ? '+' : ''; ?><?php echo number_format($item['selisih'], 2); ?>
                                            </strong>
                                        </small>
                                    </td>
                                    <td>
                                        <small class="<?php echo $item['nilai_selisih'] < 0 ? 'text-danger' : ($item['nilai_selisih'] > 0 ? 'text-success' : 'text-muted'); ?>">
                                            <?php echo formatRupiah($item['nilai_selisih']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <a href="index.php?page=detail_opname&id=<?php echo $item['id']; ?>" 
                                           class="btn btn-sm btn-info" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Print Style -->
<style>
@media print {
    .navbar, .breadcrumb, .btn, .card-body form { display: none !important; }
    .card { border: 1px solid #ddd !important; page-break-inside: avoid; }
    body { font-size: 11px; }
    h2 { font-size: 18px; }
}
</style>