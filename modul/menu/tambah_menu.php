<?php
/**
 * FORM TAMBAH MENU MAKANAN
 * Step 41/64 (64.1%)
 */

// Get kategori
$kategori_list = fetchAll("SELECT * FROM kategori_menu ORDER BY nama_kategori");
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-plus-circle"></i> Tambah Menu Makanan</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=list_menu">Menu</a></li>
                <li class="breadcrumb-item active">Tambah Menu</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-card-list"></i> Form Data Menu
            </div>
            <div class="card-body">
                <form action="config/menu_proses.php?action=create" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kode Menu *</label>
                                <input type="text" class="form-control" name="kode_menu" 
                                       placeholder="Contoh: MNU001" required maxlength="20"
                                       style="text-transform: uppercase;">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nama Menu *</label>
                                <input type="text" class="form-control" name="nama_menu" 
                                       placeholder="Contoh: Nasi Goreng Spesial" required maxlength="100">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kategori *</label>
                                <select class="form-select" name="kategori_id" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($kategori_list as $kat): ?>
                                    <option value="<?php echo $kat['id']; ?>"><?php echo $kat['nama_kategori']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($kategori_list)): ?>
                                <small class="text-danger">
                                    <a href="index.php?page=tambah_kategori">Tambah kategori dulu</a>
                                </small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Harga Jual *</label>
                                <input type="number" class="form-control" name="harga_jual" 
                                       required min="0" step="500" placeholder="0">
                                <small class="text-muted">HPP akan dihitung dari resep</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status *</label>
                                <select class="form-select" name="status" required>
                                    <option value="tersedia">Tersedia</option>
                                    <option value="habis">Habis</option>
                                    <option value="tidak_tersedia">Tidak Tersedia</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Foto Menu</label>
                                <input type="file" class="form-control" name="foto_menu" 
                                       accept="image/jpeg,image/png,image/jpg">
                                <small class="text-muted">Max 2MB, Format: JPG/PNG</small>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <strong>Langkah Selanjutnya:</strong><br>
                                Setelah menu disimpan, Anda akan diarahkan ke halaman <strong>Resep Menu</strong> 
                                untuk menambahkan komposisi bahan. HPP (Harga Pokok Produksi) akan otomatis dihitung dari resep.
                            </div>
                        </div>

                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Simpan & Lanjut ke Resep
                            </button>
                            <a href="index.php?page=list_menu" class="btn btn-secondary">
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
                <i class="bi bi-lightbulb"></i> Tips
            </div>
            <div class="card-body">
                <ul class="small mb-0">
                    <li>Gunakan kode menu yang unik</li>
                    <li>Nama menu harus jelas dan menarik</li>
                    <li>Harga jual minimal harus > HPP</li>
                    <li>Upload foto untuk tampilan menarik</li>
                    <li>Jangan lupa tambah resep setelah ini</li>
                </ul>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-warning">
                <i class="bi bi-exclamation-triangle"></i> Perhatian
            </div>
            <div class="card-body">
                <p class="small mb-0">
                    <strong>Menu tanpa resep tidak bisa dijual!</strong><br>
                    Pastikan menambahkan resep (komposisi bahan) setelah menyimpan menu.
                </p>
            </div>
        </div>
    </div>
</div>