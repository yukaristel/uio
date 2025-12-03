<?php
/**
 * FORM EDIT MENU MAKANAN
 * Step 42/64 (65.6%)
 */

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    $_SESSION['error'] = 'ID menu tidak valid!';
    header('Location: index.php?page=list_menu');
    exit;
}

$menu = fetchOne("SELECT * FROM menu_makanan WHERE id = ?", [$id]);

if (!$menu) {
    $_SESSION['error'] = 'Menu tidak ditemukan!';
    header('Location: index.php?page=list_menu');
    exit;
}

$kategori_list = fetchAll("SELECT * FROM kategori_menu ORDER BY nama_kategori");
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-pencil"></i> Edit Menu: <?php echo $menu['nama_menu']; ?></h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=list_menu">Menu</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-card-list"></i> Form Edit Menu
            </div>
            <div class="card-body">
                <form action="config/menu_proses.php?action=update" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $menu['id']; ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kode Menu *</label>
                                <input type="text" class="form-control" name="kode_menu" 
                                       value="<?php echo $menu['kode_menu']; ?>"
                                       required maxlength="20" style="text-transform: uppercase;">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nama Menu *</label>
                                <input type="text" class="form-control" name="nama_menu" 
                                       value="<?php echo $menu['nama_menu']; ?>"
                                       required maxlength="100">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kategori *</label>
                                <select class="form-select" name="kategori_id" required>
                                    <?php foreach ($kategori_list as $kat): ?>
                                    <option value="<?php echo $kat['id']; ?>" <?php echo $menu['kategori_id'] == $kat['id'] ? 'selected' : ''; ?>>
                                        <?php echo $kat['nama_kategori']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Harga Jual *</label>
                                <input type="number" class="form-control" name="harga_jual" 
                                       value="<?php echo $menu['harga_jual']; ?>"
                                       required min="0" step="500">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status *</label>
                                <select class="form-select" name="status" required>
                                    <option value="tersedia" <?php echo $menu['status'] == 'tersedia' ? 'selected' : ''; ?>>Tersedia</option>
                                    <option value="habis" <?php echo $menu['status'] == 'habis' ? 'selected' : ''; ?>>Habis</option>
                                    <option value="tidak_tersedia" <?php echo $menu['status'] == 'tidak_tersedia' ? 'selected' : ''; ?>>Tidak Tersedia</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Foto Menu</label>
                                <?php if ($menu['foto_menu']): ?>
                                    <div class="mb-2">
                                        <img src="uploads/menu/<?php echo $menu['foto_menu']; ?>" class="img-thumbnail" style="max-width: 200px;">
                                        <p class="small text-muted mb-0">Foto saat ini</p>
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" name="foto_menu" 
                                       accept="image/jpeg,image/png,image/jpg">
                                <small class="text-muted">Kosongkan jika tidak ingin ubah foto</small>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <strong>Info HPP:</strong><br>
                                HPP: <?php echo formatRupiah($menu['harga_modal']); ?> | 
                                Harga Jual: <?php echo formatRupiah($menu['harga_jual']); ?> | 
                                Margin: <?php echo formatRupiah($menu['margin_keuntungan']); ?> 
                                (<?php echo $menu['harga_jual'] > 0 ? number_format(($menu['margin_keuntungan'] / $menu['harga_jual']) * 100, 1) : 0; ?>%)
                            </div>
                        </div>

                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update Menu
                            </button>
                            <a href="index.php?page=resep_menu&id=<?php echo $menu['id']; ?>" class="btn btn-success">
                                <i class="bi bi-list-check"></i> Kelola Resep
                            </a>
                            <a href="index.php?page=list_menu" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali
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
                <i class="bi bi-graph-up"></i> Statistik Menu
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <small class="text-muted">HPP (Harga Modal):</small>
                    <h5><?php echo formatRupiah($menu['harga_modal']); ?></h5>
                </div>
                <div class="mb-2">
                    <small class="text-muted">Harga Jual:</small>
                    <h5><?php echo formatRupiah($menu['harga_jual']); ?></h5>
                </div>
                <div class="mb-2">
                    <small class="text-muted">Margin Keuntungan:</small>
                    <h4 class="text-success"><?php echo formatRupiah($menu['margin_keuntungan']); ?></h4>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <i class="bi bi-gear"></i> Aksi
            </div>
            <div class="card-body">
                <a href="index.php?page=resep_menu&id=<?php echo $menu['id']; ?>" class="btn btn-success w-100 mb-2">
                    <i class="bi bi-list-check"></i> Kelola Resep
                </a>
                <a href="config/resep_proses.php?action=recalculate&menu_id=<?php echo $menu['id']; ?>" 
                   class="btn btn-info w-100 mb-2">
                    <i class="bi bi-arrow-repeat"></i> Recalculate HPP
                </a>
                <a href="config/menu_proses.php?action=delete&id=<?php echo $menu['id']; ?>" 
                   class="btn btn-danger w-100"
                   onclick="return confirm('Hapus menu ini?')">
                    <i class="bi bi-trash"></i> Hapus Menu
                </a>
            </div>
        </div>
    </div>
</div>