<?php
/**
 * FORM EDIT BAHAN BAKU
 * Step 36/64 (56.3%)
 */

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    $_SESSION['error'] = 'ID bahan tidak valid!';
    header('Location: index.php?page=list_bahan');
    exit;
}

$bahan = fetchOne("SELECT * FROM bahan_baku WHERE id = ?", [$id]);

if (!$bahan) {
    $_SESSION['error'] = 'Bahan tidak ditemukan!';
    header('Location: index.php?page=list_bahan');
    exit;
}
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-pencil"></i> Edit Bahan Baku</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=list_bahan">Bahan Baku</a></li>
                <li class="breadcrumb-item active">Edit: <?php echo $bahan['nama_bahan']; ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-box-seam"></i> Form Edit Bahan Baku
            </div>
            <div class="card-body">
                <form action="config/bahan_proses.php?action=update" method="POST">
                    <input type="hidden" name="id" value="<?php echo $bahan['id']; ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kode Bahan *</label>
                                <input type="text" class="form-control" name="kode_bahan" 
                                       value="<?php echo $bahan['kode_bahan']; ?>"
                                       required maxlength="20" style="text-transform: uppercase;">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nama Bahan *</label>
                                <input type="text" class="form-control" name="nama_bahan" 
                                       value="<?php echo $bahan['nama_bahan']; ?>"
                                       required maxlength="100">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Satuan *</label>
                                <select class="form-select" name="satuan" required>
                                    <option value="kg" <?php echo $bahan['satuan'] == 'kg' ? 'selected' : ''; ?>>Kilogram (kg)</option>
                                    <option value="gram" <?php echo $bahan['satuan'] == 'gram' ? 'selected' : ''; ?>>Gram</option>
                                    <option value="liter" <?php echo $bahan['satuan'] == 'liter' ? 'selected' : ''; ?>>Liter</option>
                                    <option value="ml" <?php echo $bahan['satuan'] == 'ml' ? 'selected' : ''; ?>>Mililiter (ml)</option>
                                    <option value="pcs" <?php echo $bahan['satuan'] == 'pcs' ? 'selected' : ''; ?>>Pieces (pcs)</option>
                                    <option value="sachet" <?php echo $bahan['satuan'] == 'sachet' ? 'selected' : ''; ?>>Sachet</option>
                                </select>
                                <small class="text-warning">
                                    <i class="bi bi-exclamation-triangle"></i> Hati-hati mengubah satuan jika sudah dipakai di resep
                                </small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Stok Minimum *</label>
                                <input type="number" class="form-control" name="stok_minimum" 
                                       value="<?php echo $bahan['stok_minimum']; ?>"
                                       required min="0" step="0.01">
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Harga Beli per Satuan (Weighted Average) *</label>
                                <input type="number" class="form-control" name="harga_beli_per_satuan" 
                                       value="<?php echo $bahan['harga_beli_per_satuan']; ?>"
                                       required min="0" step="100">
                                <small class="text-muted">
                                    Harga ini akan otomatis di-update saat pembelian baru
                                </small>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <strong>Info Stok Saat Ini:</strong><br>
                                Stok Tersedia: <strong><?php echo number_format($bahan['stok_tersedia'], 2); ?> <?php echo $bahan['satuan']; ?></strong><br>
                                Nilai Stok: <strong><?php echo formatRupiah($bahan['stok_tersedia'] * $bahan['harga_beli_per_satuan']); ?></strong>
                            </div>
                            <div class="alert alert-hide alert-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>Catatan:</strong> Stok tersedia tidak bisa diubah di sini. 
                                Gunakan menu <strong>Pembelian Bahan</strong> untuk menambah stok atau 
                                <strong>Stock Opname</strong> untuk penyesuaian stok.
                            </div>
                        </div>

                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update Bahan
                            </button>
                            <a href="index.php?page=list_bahan" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Batal
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <i class="bi bi-graph-up"></i> Statistik Bahan
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted">Stok Tersedia:</label>
                    <h4><?php echo number_format($bahan['stok_tersedia'], 2); ?> <?php echo $bahan['satuan']; ?></h4>
                </div>
                <div class="mb-3">
                    <label class="text-muted">Stok Minimum:</label>
                    <h5><?php echo number_format($bahan['stok_minimum'], 2); ?> <?php echo $bahan['satuan']; ?></h5>
                </div>
                <div class="mb-3">
                    <label class="text-muted">Harga per Satuan:</label>
                    <h5><?php echo formatRupiah($bahan['harga_beli_per_satuan']); ?></h5>
                </div>
                <hr>
                <div>
                    <label class="text-muted">Nilai Total Stok:</label>
                    <h4 class="text-success">
                        <?php echo formatRupiah($bahan['stok_tersedia'] * $bahan['harga_beli_per_satuan']); ?>
                    </h4>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <i class="bi bi-gear"></i> Aksi Lainnya
            </div>
            <div class="card-body">
                <a href="index.php?page=pembelian_bahan&bahan_id=<?php echo $bahan['id']; ?>" 
                   class="btn btn-success w-100 mb-2">
                    <i class="bi bi-cart-plus"></i> Beli Bahan Ini
                </a>
                <a href="index.php?page=tambah_opname" class="btn btn-warning w-100 mb-2">
                    <i class="bi bi-clipboard-check"></i> Stock Opname
                </a>
                <a href="index.php?page=list_bahan" class="btn btn-secondary w-100">
                    <i class="bi bi-arrow-left"></i> Kembali ke List
                </a>
            </div>
        </div>
    </div>
</div>