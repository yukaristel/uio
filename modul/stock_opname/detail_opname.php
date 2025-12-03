<?php
/**
 * DETAIL STOCK OPNAME
 * Step 54/64 (84.4%)
 */

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    $_SESSION['error'] = 'ID opname tidak valid!';
    header('Location: index.php?page=list_opname');
    exit;
}

// Get opname detail
$opname = fetchOne("
    SELECT so.*, b.nama_bahan, b.kode_bahan, b.satuan,
           u.nama_lengkap as dibuat_oleh, u.role,
           ua.nama_lengkap as approved_by_name
    FROM stock_opname so
    JOIN bahan_baku b ON so.bahan_id = b.id
    JOIN users u ON so.user_id = u.id
    LEFT JOIN users ua ON so.approved_by = ua.id
    WHERE so.id = ?
", [$id]);

if (!$opname) {
    $_SESSION['error'] = 'Stock opname tidak ditemukan!';
    header('Location: index.php?page=list_opname');
    exit;
}

// Get stock movement terkait (jika sudah approved)
$movement = null;
if ($opname['status'] == 'approved') {
    $movement = fetchOne("
        SELECT sm.*, u.nama_lengkap
        FROM stock_movement sm
        JOIN users u ON sm.user_id = u.id
        WHERE sm.referensi_type = 'opname' AND sm.referensi_id = ?
    ", [$id]);
}

$is_kurang = $opname['selisih'] < 0;
$status_badge = $opname['status'] == 'draft' ? 'bg-warning' : 'bg-success';
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-clipboard-check"></i> Detail Stock Opname</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=list_opname">Stock Opname</a></li>
                <li class="breadcrumb-item active"><?php echo $opname['no_opname']; ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Informasi Opname -->
        <div class="card mb-3">
            <div class="card-header <?php echo $opname['status'] == 'draft' ? 'bg-warning' : 'bg-success text-white'; ?>">
                <i class="bi bi-info-circle"></i> Informasi Stock Opname
                <span class="badge <?php echo $status_badge; ?> float-end">
                    <?php echo $opname['status'] == 'draft' ? 'DRAFT' : 'APPROVED'; ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>No. Opname:</strong><br>
                        <h5 class="mb-0"><?php echo $opname['no_opname']; ?></h5>
                    </div>
                    <div class="col-md-6">
                        <strong>Tanggal Opname:</strong><br>
                        <span class="text-muted"><?php echo formatTanggal($opname['tanggal_opname'], 'd F Y'); ?></span>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Dibuat Oleh:</strong><br>
                        <span class="text-muted">
                            <?php echo $opname['dibuat_oleh']; ?>
                            <span class="badge bg-secondary"><?php echo ucfirst($opname['role']); ?></span>
                        </span><br>
                        <small class="text-muted"><?php echo formatDateTime($opname['created_at'], 'd/m/Y H:i'); ?></small>
                    </div>
                    <div class="col-md-6">
                        <?php if ($opname['status'] == 'approved'): ?>
                            <strong>Approved Oleh:</strong><br>
                            <span class="text-muted"><?php echo $opname['approved_by_name']; ?></span><br>
                            <small class="text-muted"><?php echo formatDateTime($opname['approved_at'], 'd/m/Y H:i'); ?></small>
                        <?php else: ?>
                            <span class="badge bg-warning">Menunggu Approval</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($opname['keterangan'])): ?>
                <div class="row">
                    <div class="col-md-12">
                        <strong>Keterangan:</strong><br>
                        <p class="text-muted mb-0"><?php echo nl2br(htmlspecialchars($opname['keterangan'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Detail Bahan -->
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <i class="bi bi-box-seam"></i> Detail Bahan
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Kode Bahan:</strong><br>
                        <span class="text-muted"><?php echo $opname['kode_bahan']; ?></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Nama Bahan:</strong><br>
                        <h5 class="mb-0"><?php echo $opname['nama_bahan']; ?></h5>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <strong>Harga per Satuan:</strong><br>
                        <h5><?php echo formatRupiah($opname['harga_per_satuan']); ?>/<small><?php echo $opname['satuan']; ?></small></h5>
                    </div>
                    <div class="col-md-6">
                        <strong>Satuan:</strong><br>
                        <span class="badge bg-secondary fs-6"><?php echo strtoupper($opname['satuan']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Perbandingan Stok -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-graph-up"></i> Perbandingan Stok
            </div>
            <div class="card-body">
                <div class="row text-center mb-4">
                    <div class="col-md-5">
                        <div class="p-3 bg-light rounded">
                            <h6 class="text-muted">Stok Sistem</h6>
                            <h2 class="text-primary"><?php echo number_format($opname['stok_sistem'], 2); ?></h2>
                            <small class="text-muted"><?php echo $opname['satuan']; ?></small>
                            <p class="text-muted small mt-2 mb-0">Stok yang tercatat di sistem</p>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-center justify-content-center">
                        <i class="bi bi-arrow-left-right text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <div class="col-md-5">
                        <div class="p-3 bg-light rounded">
                            <h6 class="text-muted">Stok Fisik</h6>
                            <h2 class="text-success"><?php echo number_format($opname['stok_fisik'], 2); ?></h2>
                            <small class="text-muted"><?php echo $opname['satuan']; ?></small>
                            <p class="text-muted small mt-2 mb-0">Hasil penghitungan fisik</p>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="text-center">
                    <h6 class="text-muted">SELISIH</h6>
                    <h1 class="<?php echo $is_kurang ? 'text-danger' : 'text-success'; ?>">
                        <?php echo $opname['selisih'] > 0 ? '+' : ''; ?><?php echo number_format($opname['selisih'], 2); ?>
                    </h1>
                    <p class="text-muted"><?php echo $opname['satuan']; ?></p>
                    
                    <?php if ($opname['selisih'] == 0): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> <strong>Stok Sesuai</strong> - Tidak ada selisih
                        </div>
                    <?php elseif ($is_kurang): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-arrow-down-circle"></i> <strong>Stok Kurang</strong> - 
                            Stok fisik lebih sedikit dari sistem
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <i class="bi bi-arrow-up-circle"></i> <strong>Stok Lebih</strong> - 
                            Stok fisik lebih banyak dari sistem
                        </div>
                    <?php endif; ?>
                </div>

                <hr>

                <div class="row text-center">
                    <div class="col-md-6">
                        <h6 class="text-muted">Nilai Selisih</h6>
                        <h3 class="<?php echo $is_kurang ? 'text-danger' : 'text-success'; ?>">
                            <?php echo formatRupiah($opname['nilai_selisih']); ?>
                        </h3>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Jenis Selisih</h6>
                        <?php if (!empty($opname['jenis_selisih'])): ?>
                            <span class="badge bg-warning fs-6">
                                <?php echo ucfirst($opname['jenis_selisih']); ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Movement Terkait -->
        <?php if ($movement): ?>
        <div class="card mb-3">
            <div class="card-header bg-dark text-white">
                <i class="bi bi-arrow-left-right"></i> Stock Movement Terkait
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Movement ID:</strong><br>
                        <span class="text-muted">#<?php echo $movement['id']; ?></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Jenis:</strong><br>
                        <span class="badge bg-primary"><?php echo ucfirst($movement['jenis_pergerakan']); ?></span>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <strong>Tanggal Catat:</strong><br>
                        <span class="text-muted"><?php echo formatDateTime($movement['created_at'], 'd/m/Y H:i'); ?></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Dicatat Oleh:</strong><br>
                        <span class="text-muted"><?php echo $movement['nama_lengkap']; ?></span>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="index.php?page=detail_movement&id=<?php echo $movement['id']; ?>" 
                       class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i> Lihat Detail Movement
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Sidebar -->
    <div class="col-md-4">
        <!-- Status Card -->
        <div class="card mb-3 <?php echo $opname['status'] == 'draft' ? 'border-warning' : 'border-success'; ?>">
            <div class="card-header <?php echo $opname['status'] == 'draft' ? 'bg-warning' : 'bg-success text-white'; ?>">
                <i class="bi bi-flag"></i> Status Opname
            </div>
            <div class="card-body">
                <?php if ($opname['status'] == 'draft'): ?>
                    <div class="alert alert-warning mb-3">
                        <i class="bi bi-clock-history"></i>
                        <strong>Menunggu Approval</strong>
                        <p class="small mb-0 mt-2">Stock opname ini masih dalam status draft dan menunggu persetujuan admin.</p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success mb-3">
                        <i class="bi bi-check-circle"></i>
                        <strong>Sudah Approved</strong>
                        <p class="small mb-0 mt-2">Stok telah disesuaikan dengan hasil opname.</p>
                    </div>
                <?php endif; ?>

                <?php if ($is_kurang && $opname['nilai_selisih'] < 0): ?>
                    <div class="alert alert-danger">
                        <h6 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Kerugian!</h6>
                        <hr>
                        <h4 class="mb-0"><?php echo formatRupiah(abs($opname['nilai_selisih'])); ?></h4>
                        <small>Selisih: <?php echo number_format(abs($opname['selisih']), 2); ?> <?php echo $opname['satuan']; ?></small>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Aksi -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-gear"></i> Aksi
            </div>
            <div class="card-body">
                <a href="index.php?page=list_opname" class="btn btn-secondary w-100 mb-2">
                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                </a>
                
                <?php if ($opname['status'] == 'draft' && $_SESSION['role'] == 'admin'): ?>
                    <a href="index.php?page=approval_opname&id=<?php echo $id; ?>" 
                       class="btn btn-success w-100 mb-2">
                        <i class="bi bi-check-circle"></i> Approve Opname
                    </a>
                    <a href="config/stock_opname_proses.php?action=delete&id=<?php echo $id; ?>" 
                       class="btn btn-danger w-100"
                       onclick="return confirm('Hapus stock opname ini?')">
                        <i class="bi bi-trash"></i> Hapus Opname
                    </a>
                <?php elseif ($movement): ?>
                    <a href="index.php?page=detail_movement&id=<?php echo $movement['id']; ?>" 
                       class="btn btn-info w-100">
                        <i class="bi bi-arrow-left-right"></i> Lihat Movement
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Timeline -->
        <div class="card">
            <div class="card-header bg-light">
                <i class="bi bi-clock-history"></i> Timeline
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker bg-primary"></div>
                        <div class="timeline-content">
                            <strong>Dibuat</strong><br>
                            <small class="text-muted">
                                <?php echo formatDateTime($opname['created_at'], 'd/m/Y H:i'); ?><br>
                                Oleh: <?php echo $opname['dibuat_oleh']; ?>
                            </small>
                        </div>
                    </div>
                    
                    <?php if ($opname['status'] == 'approved'): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <strong>Approved</strong><br>
                            <small class="text-muted">
                                <?php echo formatDateTime($opname['approved_at'], 'd/m/Y H:i'); ?><br>
                                Oleh: <?php echo $opname['approved_by_name']; ?>
                            </small>
                        </div>
                    </div>
                    
                    <?php if ($movement): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-info"></div>
                        <div class="timeline-content">
                            <strong>Stock Movement Dicatat</strong><br>
                            <small class="text-muted">
                                <?php echo formatDateTime($movement['created_at'], 'd/m/Y H:i'); ?><br>
                                Movement ID: #<?php echo $movement['id']; ?>
                            </small>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-warning"></div>
                        <div class="timeline-content">
                            <strong>Menunggu Approval</strong><br>
                            <small class="text-muted">Belum disetujui admin</small>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}
.timeline-item {
    position: relative;
    padding-bottom: 20px;
}
.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -22px;
    top: 20px;
    width: 2px;
    height: calc(100% - 10px);
    background: #ddd;
}
.timeline-marker {
    position: absolute;
    left: -27px;
    top: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid white;
}
.timeline-content {
    padding-left: 10px;
}
</style>