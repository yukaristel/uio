<?php
/**
 * DAFTAR STOCK OPNAME
 * Step 50/64 (78.1%)
 */

// Filter
$status = isset($_GET['status']) ? $_GET['status'] : '';
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');

// Build query
$where = "DATE_FORMAT(so.tanggal_opname, '%Y-%m') = ?";
$params = [$bulan];

if (!empty($status)) {
    $where .= " AND so.status = ?";
    $params[] = $status;
}

// Get opname
$opname_list = fetchAll("
    SELECT so.*, b.nama_bahan, u.nama_lengkap,
           ua.nama_lengkap as approved_by_name
    FROM stock_opname so
    JOIN bahan_baku b ON so.bahan_id = b.id
    JOIN users u ON so.user_id = u.id
    LEFT JOIN users ua ON so.approved_by = ua.id
    WHERE $where
    ORDER BY so.created_at DESC
", $params);

// Summary
$summary = fetchOne("
    SELECT 
        COUNT(*) as total_opname,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        COALESCE(SUM(CASE WHEN status = 'approved' AND selisih < 0 THEN nilai_selisih ELSE 0 END), 0) as total_kerugian
    FROM stock_opname so
    WHERE $where
", $params);
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-clipboard-check"></i> Stock Opname</h2>
    </div>
</div>

<!-- Summary -->
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card dashboard-card card-primary">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Total Opname</h6>
                    <h3 class="mb-0"><?php echo $summary['total_opname']; ?></h3>
                </div>
                <div class="icon"><i class="bi bi-clipboard-check text-primary"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card card-warning">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Pending (Draft)</h6>
                    <h3 class="mb-0"><?php echo $summary['draft']; ?></h3>
                </div>
                <div class="icon"><i class="bi bi-clock-history text-warning"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card card-success">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Approved</h6>
                    <h3 class="mb-0"><?php echo $summary['approved']; ?></h3>
                </div>
                <div class="icon"><i class="bi bi-check-circle text-success"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card card-danger">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Kerugian</h6>
                    <h4 class="text-danger"><?php echo formatRupiah(abs($summary['total_kerugian'])); ?></h4>
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
                    <input type="hidden" name="page" value="list_opname">
                    
                    <div class="col-md-3">
                        <label class="form-label">Bulan</label>
                        <input type="month" class="form-control" name="bulan" value="<?php echo $bulan; ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">Semua</option>
                            <option value="draft" <?php echo $status == 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="approved" <?php echo $status == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        </select>
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

<!-- Tabel Opname -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-list-ul"></i> Daftar Stock Opname</span>
                <a href="index.php?page=tambah_opname" class="btn btn-success btn-sm">
                    <i class="bi bi-plus-circle"></i> Buat Opname Baru
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($opname_list)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-3">Tidak ada stock opname pada periode ini</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>No. Opname</th>
                                    <th>Tanggal</th>
                                    <th>Bahan</th>
                                    <th>Stok Sistem</th>
                                    <th>Stok Fisik</th>
                                    <th>Selisih</th>
                                    <th>Nilai</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($opname_list as $op): ?>
                                <tr>
                                    <td><strong><?php echo $op['no_opname']; ?></strong></td>
                                    <td><?php echo formatTanggal($op['tanggal_opname'], 'd/m/Y'); ?></td>
                                    <td><?php echo $op['nama_bahan']; ?></td>
                                    <td><?php echo number_format($op['stok_sistem'], 2); ?> <?php echo $op['satuan']; ?></td>
                                    <td><?php echo number_format($op['stok_fisik'], 2); ?> <?php echo $op['satuan']; ?></td>
                                    <td class="<?php echo $op['selisih'] < 0 ? 'text-danger' : 'text-success'; ?>">
                                        <strong><?php echo $op['selisih'] > 0 ? '+' : ''; ?><?php echo number_format($op['selisih'], 2); ?></strong> <?php echo $op['satuan']; ?>
                                    </td>
                                    <td class="<?php echo $op['nilai_selisih'] < 0 ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo formatRupiah($op['nilai_selisih']); ?>
                                    </td>
                                    <td>
                                        <?php if ($op['status'] == 'draft'): ?>
                                            <span class="badge bg-warning">Draft</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Approved</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="index.php?page=detail_opname&id=<?php echo $op['id']; ?>" 
                                               class="btn btn-info" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($op['status'] == 'draft' && $_SESSION['role'] == 'admin'): ?>
                                            <a href="index.php?page=approval_opname&id=<?php echo $op['id']; ?>" 
                                               class="btn btn-success" title="Approve">
                                                <i class="bi bi-check-circle"></i>
                                            </a>
                                            <a href="config/stock_opname_proses.php?action=delete&id=<?php echo $op['id']; ?>" 
                                               class="btn btn-danger" 
                                               onclick="return confirm('Hapus opname ini?')"
                                               title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
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

<?php if ($summary['draft'] > 0 && $_SESSION['role'] == 'admin'): ?>
<div class="row mt-3">
    <div class="col-md-12">
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Perhatian!</strong> Ada <?php echo $summary['draft']; ?> stock opname yang menunggu approval.
            <a href="index.php?page=list_opname&status=draft" class="alert-link">Lihat sekarang</a>
        </div>
    </div>
</div>
<?php endif; ?>