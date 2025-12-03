<?php
/**
 * DAFTAR STOCK MOVEMENT
 * Step 48/64 (75.0%)
 */

// Filter
$tanggal_dari = isset($_GET['tanggal_dari']) ? $_GET['tanggal_dari'] : date('Y-m-d', strtotime('-7 days'));
$tanggal_sampai = isset($_GET['tanggal_sampai']) ? $_GET['tanggal_sampai'] : date('Y-m-d');
$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';
$bahan_id = isset($_GET['bahan_id']) ? intval($_GET['bahan_id']) : 0;

// Build query
$where = "DATE(sm.created_at) BETWEEN ? AND ?";
$params = [$tanggal_dari, $tanggal_sampai];

if (!empty($jenis)) {
    $where .= " AND sm.jenis_pergerakan = ?";
    $params[] = $jenis;
}

if ($bahan_id > 0) {
    $where .= " AND sm.bahan_id = ?";
    $params[] = $bahan_id;
}

// Get movements
$movements = fetchAll("
    SELECT sm.*, b.nama_bahan, b.satuan, u.nama_lengkap 
    FROM stock_movement sm
    JOIN bahan_baku b ON sm.bahan_id = b.id
    JOIN users u ON sm.user_id = u.id
    WHERE $where
    ORDER BY sm.created_at DESC
", $params);

// Summary
$summary = fetchOne("
    SELECT 
        COUNT(*) as total_movement,
        COALESCE(SUM(CASE WHEN jenis_pergerakan = 'masuk' THEN total_nilai ELSE 0 END), 0) as nilai_masuk,
        COALESCE(SUM(CASE WHEN jenis_pergerakan = 'keluar' THEN total_nilai ELSE 0 END), 0) as nilai_keluar,
        COALESCE(SUM(CASE WHEN jenis_pergerakan IN ('rusak', 'tumpah', 'expired', 'hilang') THEN total_nilai ELSE 0 END), 0) as nilai_kerugian
    FROM stock_movement sm
    WHERE $where
", $params);

// Get bahan untuk filter
$bahan_list = fetchAll("SELECT id, nama_bahan FROM bahan_baku ORDER BY nama_bahan");
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-arrow-left-right"></i> Stock Movement</h2>
    </div>
</div>

<!-- Summary -->
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card dashboard-card card-primary">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Total Movement</h6>
                    <h3 class="mb-0"><?php echo $summary['total_movement']; ?></h3>
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
                    <h4 class="text-success"><?php echo formatRupiah($summary['nilai_masuk']); ?></h4>
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
                    <h4 class="text-info"><?php echo formatRupiah($summary['nilai_keluar']); ?></h4>
                </div>
                <div class="icon"><i class="bi bi-arrow-up-circle text-info"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card card-danger">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Kerugian</h6>
                    <h4 class="text-danger"><?php echo formatRupiah($summary['nilai_kerugian']); ?></h4>
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
                    <input type="hidden" name="page" value="list_movement">
                    
                    <div class="col-md-2">
                        <label class="form-label">Tanggal Dari</label>
                        <input type="date" class="form-control" name="tanggal_dari" value="<?php echo $tanggal_dari; ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Tanggal Sampai</label>
                        <input type="date" class="form-control" name="tanggal_sampai" value="<?php echo $tanggal_sampai; ?>">
                    </div>
                    
                    <div class="col-md-3">
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
                            <option value="opname" <?php echo $jenis == 'opname' ? 'selected' : ''; ?>>Opname</option>
                            <option value="rusak" <?php echo $jenis == 'rusak' ? 'selected' : ''; ?>>Rusak</option>
                            <option value="tumpah" <?php echo $jenis == 'tumpah' ? 'selected' : ''; ?>>Tumpah</option>
                            <option value="expired" <?php echo $jenis == 'expired' ? 'selected' : ''; ?>>Expired</option>
                            <option value="hilang" <?php echo $jenis == 'hilang' ? 'selected' : ''; ?>>Hilang</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Filter
                            </button>
                            <a href="index.php?page=list_movement" class="btn btn-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Movement -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-list-ul"></i> Daftar Stock Movement</span>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                <a href="index.php?page=tambah_movement" class="btn btn-warning btn-sm">
                    <i class="bi bi-plus-circle"></i> Catat Movement Manual
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($movements)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-3">Tidak ada stock movement pada periode ini</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Bahan</th>
                                    <th>Jenis</th>
                                    <th>Jumlah</th>
                                    <th>Nilai</th>
                                    <th>Stok</th>
                                    <th>Referensi</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movements as $m): 
                                    $is_kerugian = in_array($m['jenis_pergerakan'], ['rusak', 'tumpah', 'expired', 'hilang']);
                                ?>
                                <tr class="<?php echo $is_kerugian ? 'table-warning' : ''; ?>">
                                    <td><small><?php echo formatDateTime($m['created_at'], 'd/m/Y H:i'); ?></small></td>
                                    <td><strong><?php echo $m['nama_bahan']; ?></strong></td>
                                    <td>
                                        <?php
                                        $badge_class = [
                                            'masuk' => 'bg-success',
                                            'keluar' => 'bg-info',
                                            'opname' => 'bg-primary',
                                            'rusak' => 'bg-danger',
                                            'tumpah' => 'bg-warning',
                                            'expired' => 'bg-danger',
                                            'hilang' => 'bg-dark'
                                        ];
                                        $class = $badge_class[$m['jenis_pergerakan']] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?php echo $class; ?>">
                                            <?php echo ucfirst($m['jenis_pergerakan']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($m['jumlah'], 2); ?> <?php echo $m['satuan']; ?></td>
                                    <td class="<?php echo $is_kerugian ? 'text-danger' : ''; ?>">
                                        <?php echo formatRupiah($m['total_nilai']); ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo number_format($m['stok_sebelum'], 2); ?> → 
                                            <strong><?php echo number_format($m['stok_sesudah'], 2); ?></strong>
                                        </small>
                                    </td>
                                    <td>
                                        <small>
                                            <?php echo ucfirst($m['referensi_type']); ?>
                                            <?php if ($m['referensi_id']): ?>
                                                #<?php echo $m['referensi_id']; ?>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td><small><?php echo $m['nama_lengkap']; ?></small></td>
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