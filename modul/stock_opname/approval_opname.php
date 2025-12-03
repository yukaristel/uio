<?php
/**
 * APPROVAL STOCK OPNAME (ADMIN ONLY)
 * Step 55/64 (85.9%)
 */

// Cek akses admin
if ($_SESSION['role'] != 'admin') {
    $_SESSION['error'] = 'Hanya admin yang bisa approve stock opname!';
    header('Location: index.php?page=list_opname');
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    $_SESSION['error'] = 'ID opname tidak valid!';
    header('Location: index.php?page=list_opname');
    exit;
}

// Get opname detail
$opname = fetchOne("
    SELECT so.*, b.nama_bahan, b.kode_bahan, b.satuan, b.stok_tersedia as stok_bahan_sekarang,
           u.nama_lengkap as dibuat_oleh, u.role
    FROM stock_opname so
    JOIN bahan_baku b ON so.bahan_id = b.id
    JOIN users u ON so.user_id = u.id
    WHERE so.id = ?
", [$id]);

if (!$opname) {
    $_SESSION['error'] = 'Stock opname tidak ditemukan!';
    header('Location: index.php?page=list_opname');
    exit;
}

if ($opname['status'] == 'approved') {
    $_SESSION['warning'] = 'Stock opname ini sudah di-approve sebelumnya!';
    header('Location: index.php?page=detail_opname&id=' . $id);
    exit;
}

$is_kurang = $opname['selisih'] < 0;
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-check-circle"></i> Approval Stock Opname</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=list_opname">Stock Opname</a></li>
                <li class="breadcrumb-item"><a href="index.php?page=detail_opname&id=<?php echo $id; ?>"><?php echo $opname['no_opname']; ?></a></li>
                <li class="breadcrumb-item active">Approval</li>
            </ol>
        </nav>
    </div>
</div>

<form action="config/stock_opname_proses.php?action=approve&id=<?php echo $id; ?>" method="POST" id="formApproval">
    <div class="row">
        <div class="col-md-8">
            <!-- Warning/Konfirmasi -->
            <div class="alert alert-warning">
                <h5 class="alert-heading">
                    <i class="bi bi-exclamation-triangle"></i> Perhatian - Approval Stock Opname
                </h5>
                <hr>
                <p><strong>Dengan menyetujui stock opname ini, sistem akan:</strong></p>
                <ol class="mb-0">
                    <li>Mengupdate stok bahan dari <strong><?php echo number_format($opname['stok_sistem'], 2); ?></strong> menjadi <strong><?php echo number_format($opname['stok_fisik'], 2); ?> <?php echo $opname['satuan']; ?></strong></li>
                    <li>Mencatat stock movement dengan jenis "<?php echo $opname['jenis_selisih'] ?? 'opname'; ?>"</li>
                    <?php if ($is_kurang): ?>
                    <li class="text-danger"><strong>Mencatat kerugian sebesar <?php echo formatRupiah(abs($opname['nilai_selisih'])); ?></strong></li>
                    <?php endif; ?>
                    <li>Mengubah status opname menjadi "Approved"</li>
                </ol>
                <hr>
                <p class="mb-0"><strong>Pastikan data sudah benar sebelum approve!</strong></p>
            </div>

            <!-- Detail Opname -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-clipboard-check"></i> Detail Stock Opname
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>No. Opname:</strong><br>
                            <h5><?php echo $opname['no_opname']; ?></h5>
                        </div>
                        <div class="col-md-6">
                            <strong>Tanggal:</strong><br>
                            <span class="text-muted"><?php echo formatTanggal($opname['tanggal_opname'], 'd F Y'); ?></span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Bahan:</strong><br>
                            <h5><?php echo $opname['nama_bahan']; ?></h5>
                            <small class="text-muted">Kode: <?php echo $opname['kode_bahan']; ?></small>
                        </div>
                        <div class="col-md-6">
                            <strong>Dibuat Oleh:</strong><br>
                            <span class="text-muted">
                                <?php echo $opname['dibuat_oleh']; ?>
                                <span class="badge bg-secondary"><?php echo ucfirst($opname['role']); ?></span>
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($opname['keterangan'])): ?>
                    <div class="row">
                        <div class="col-md-12">
                            <strong>Keterangan:</strong><br>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($opname['keterangan'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Perbandingan Stok -->
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-graph-up"></i> Perbandingan Stok
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th width="25%"></th>
                                    <th class="text-center">Stok Sistem</th>
                                    <th class="text-center">Stok Fisik</th>
                                    <th class="text-center">Selisih</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th>Jumlah</th>
                                    <td class="text-center">
                                        <h4><?php echo number_format($opname['stok_sistem'], 2); ?></h4>
                                        <small class="text-muted"><?php echo $opname['satuan']; ?></small>
                                    </td>
                                    <td class="text-center">
                                        <h4 class="text-success"><?php echo number_format($opname['stok_fisik'], 2); ?></h4>
                                        <small class="text-muted"><?php echo $opname['satuan']; ?></small>
                                    </td>
                                    <td class="text-center">
                                        <h4 class="<?php echo $is_kurang ? 'text-danger' : 'text-success'; ?>">
                                            <?php echo $opname['selisih'] > 0 ? '+' : ''; ?><?php echo number_format($opname['selisih'], 2); ?>
                                        </h4>
                                        <small class="text-muted"><?php echo $opname['satuan']; ?></small>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Nilai (Rp)</th>
                                    <td class="text-center">
                                        <?php echo formatRupiah($opname['stok_sistem'] * $opname['harga_per_satuan']); ?>
                                    </td>
                                    <td class="text-center text-success">
                                        <?php echo formatRupiah($opname['stok_fisik'] * $opname['harga_per_satuan']); ?>
                                    </td>
                                    <td class="text-center">
                                        <strong class="<?php echo $is_kurang ? 'text-danger' : 'text-success'; ?>">
                                            <?php echo formatRupiah($opname['nilai_selisih']); ?>
                                        </strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($opname['selisih'] != 0): ?>
                    <div class="alert <?php echo $is_kurang ? 'alert-danger' : 'alert-success'; ?> mt-3">
                        <strong>Jenis Selisih:</strong> 
                        <span class="badge <?php echo $is_kurang ? 'bg-danger' : 'bg-success'; ?> ms-2">
                            <?php echo !empty($opname['jenis_selisih']) ? ucfirst($opname['jenis_selisih']) : 'Opname'; ?>
                        </span>
                        <?php if ($is_kurang): ?>
                            <p class="mb-0 mt-2">
                                <i class="bi bi-arrow-down-circle"></i> 
                                Stok fisik <strong>KURANG</strong> dari sistem sebesar 
                                <strong><?php echo number_format(abs($opname['selisih']), 2); ?> <?php echo $opname['satuan']; ?></strong>
                            </p>
                        <?php else: ?>
                            <p class="mb-0 mt-2">
                                <i class="bi bi-arrow-up-circle"></i> 
                                Stok fisik <strong>LEBIH</strong> dari sistem sebesar 
                                <strong><?php echo number_format($opname['selisih'], 2); ?> <?php echo $opname['satuan']; ?></strong>
                            </p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Yang Akan Terjadi -->
            <div class="card mb-3 border-warning">
                <div class="card-header bg-warning">
                    <i class="bi bi-lightning"></i> Yang Akan Terjadi Setelah Approval
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>1. Update Stok Bahan</h6>
                            <div class="alert alert-light">
                                <strong>Stok Sekarang:</strong> <?php echo number_format($opname['stok_bahan_sekarang'], 2); ?> <?php echo $opname['satuan']; ?><br>
                                <i class="bi bi-arrow-down"></i><br>
                                <strong>Stok Setelah:</strong> <span class="text-success"><?php echo number_format($opname['stok_fisik'], 2); ?> <?php echo $opname['satuan']; ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>2. Catat Stock Movement</h6>
                            <div class="alert alert-light">
                                <strong>Jenis:</strong> <?php echo $opname['jenis_selisih'] ?? 'opname'; ?><br>
                                <strong>Jumlah:</strong> <?php echo number_format(abs($opname['selisih']), 2); ?> <?php echo $opname['satuan']; ?><br>
                                <strong>Nilai:</strong> <?php echo formatRupiah(abs($opname['nilai_selisih'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Konfirmasi Checkbox -->
            <div class="card border-danger">
                <div class="card-body">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="konfirmasi" required>
                        <label class="form-check-label" for="konfirmasi">
                            <strong>Saya telah memeriksa data dan yakin untuk menyetujui stock opname ini</strong>
                        </label>
                    </div>
                </div>
            </div>

        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Info Admin -->
            <div class="card mb-3 border-primary">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-person-check"></i> Info Approval
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Akan di-approve oleh:</strong><br>
                        <h5><?php echo $_SESSION['nama_lengkap']; ?></h5>
                        <span class="badge bg-primary">Admin</span>
                    </div>
                    <div>
                        <strong>Waktu Approval:</strong><br>
                        <span class="text-muted"><?php echo date('d F Y, H:i'); ?> WIB</span>
                    </div>
                </div>
            </div>

            <!-- Ringkasan -->
            <?php if ($is_kurang): ?>
            <div class="card mb-3 border-danger">
                <div class="card-header bg-danger text-white">
                    <i class="bi bi-exclamation-triangle"></i> Kerugian!
                </div>
                <div class="card-body">
                    <h6>Total Kerugian:</h6>
                    <h3 class="text-danger mb-0"><?php echo formatRupiah(abs($opname['nilai_selisih'])); ?></h3>
                    <hr>
                    <small class="text-muted">
                        Kerugian ini akan tercatat dalam laporan stock movement dan laporan keuangan.
                    </small>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tombol Aksi -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-check-circle"></i> Aksi Approval
                </div>
                <div class="card-body">
                    <button type="submit" class="btn btn-success btn-lg w-100 mb-2" id="btnApprove" disabled>
                        <i class="bi bi-check-circle"></i> Approve Opname
                    </button>
                    <a href="index.php?page=detail_opname&id=<?php echo $id; ?>" class="btn btn-secondary w-100 mb-2">
                        <i class="bi bi-x-circle"></i> Batal
                    </a>
                    <a href="index.php?page=list_opname" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>

            <!-- Catatan -->
            <div class="card mt-3">
                <div class="card-header bg-light">
                    <i class="bi bi-info-circle"></i> Catatan Penting
                </div>
                <div class="card-body">
                    <small class="text-muted">
                        • Approval tidak bisa dibatalkan<br>
                        • Stok akan langsung ter-update<br>
                        • Movement tercatat otomatis<br>
                        • Pastikan data sudah benar<br>
                        <?php if ($is_kurang): ?>
                        • <strong>Kerugian akan masuk laporan</strong>
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Enable/disable button berdasarkan checkbox
document.getElementById('konfirmasi').addEventListener('change', function() {
    document.getElementById('btnApprove').disabled = !this.checked;
});

// Konfirmasi sebelum submit
document.getElementById('formApproval').addEventListener('submit', function(e) {
    const isKurang = <?php echo $is_kurang ? 'true' : 'false'; ?>;
    const kerugian = '<?php echo formatRupiah(abs($opname['nilai_selisih'])); ?>';
    
    let message = 'Approve stock opname ini?\n\n';
    message += 'Stok akan diupdate menjadi: <?php echo number_format($opname['stok_fisik'], 2); ?> <?php echo $opname['satuan']; ?>\n';
    
    if (isKurang) {
        message += '\n⚠️ KERUGIAN: ' + kerugian + '\n';
    }
    
    if (!confirm(message)) {
        e.preventDefault();
        return false;
    }
});
</script>