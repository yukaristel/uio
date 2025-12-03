<?php
/**
 * FORM EDIT KATEGORI MENU
 * Step 39/64 (60.9%)
 */

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    $_SESSION['error'] = 'ID kategori tidak valid!';
    header('Location: index.php?page=list_kategori');
    exit;
}

$kategori = fetchOne("SELECT * FROM kategori_menu WHERE id = ?", [$id]);

if (!$kategori) {
    $_SESSION['error'] = 'Kategori tidak ditemukan!';
    header('Location: index.php?page=list_kategori');
    exit;
}

// Get jumlah menu di kategori ini
$jumlah_menu = fetchOne("SELECT COUNT(*) as total FROM menu_makanan WHERE kategori_id = ?", [$id]);
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-pencil"></i> Edit Kategori Menu</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=list_kategori">Kategori Menu</a></li>
                <li class="breadcrumb-item active">Edit: <?php echo $kategori['nama_kategori']; ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-tag"></i> Form Edit Kategori
            </div>
            <div class="card-body">
                <form action="config/kategori_proses.php?action=update" method="POST">
                    <input type="hidden" name="id" value="<?php echo $kategori['id']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori *</label>
                        <input type="text" class="form-control" name="nama_kategori" 
                               value="<?php echo $kategori['nama_kategori']; ?>"
                               required maxlength="50">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="deskripsi" rows="3"><?php echo $kategori['deskripsi']; ?></textarea>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Kategori ini memiliki <strong><?php echo $jumlah_menu['total']; ?> menu</strong>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Update Kategori
                    </button>
                    <a href="index.php?page=list_kategori" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Batal
                    </a>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-info text-white">
                <i class="bi bi-list-ul"></i> Menu di Kategori Ini
            </div>
            <div class="card-body">
                <?php
                $menu_list = fetchAll("SELECT * FROM menu_makanan WHERE kategori_id = ? ORDER BY nama_menu", [$id]);
                
                if (empty($menu_list)): ?>
                    <p class="text-muted">Belum ada menu di kategori ini</p>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($menu_list as $menu): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?php echo $menu['nama_menu']; ?>
                            <span class="badge bg-primary"><?php echo formatRupiah($menu['harga_jual']); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($jumlah_menu['total'] > 0): ?>
        <div class="alert alert-warning mt-3">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Perhatian:</strong> Untuk menghapus kategori ini, pindahkan atau hapus semua menu terlebih dahulu.
        </div>
        <?php endif; ?>
    </div>
</div>