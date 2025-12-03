<?php
/**
 * DETAIL MENU MAKANAN
 * Step 46/64 (71.9%)
 */

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    $_SESSION['error'] = 'ID menu tidak valid!';
    header('Location: index.php?page=list_menu');
    exit;
}

$menu = fetchOne("
    SELECT m.*, k.nama_kategori 
    FROM menu_makanan m 
    JOIN kategori_menu k ON m.kategori_id = k.id 
    WHERE m.id = ?
", [$id]);

if (!$menu) {
    $_SESSION['error'] = 'Menu tidak ditemukan!';
    header('Location: index.php?page=list_menu');
    exit;
}

// Get resep
$resep = fetchAll("
    SELECT r.*, b.nama_bahan, b.satuan as satuan_bahan 
    FROM resep_menu r 
    JOIN bahan_baku b ON r.bahan_id = b.id 
    WHERE r.menu_id = ?
", [$id]);

// Get statistik penjualan
$statistik = fetchOne("
    SELECT 
        COUNT(*) as total_transaksi,
        SUM(jumlah) as total_terjual,
        SUM(subtotal) as total_pendapatan,
        SUM(subtotal_modal) as total_modal,
        SUM(subtotal - subtotal_modal) as total_keuntungan
    FROM detail_transaksi
    WHERE menu_id = ?
", [$id]);
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-info-circle"></i> Detail Menu</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=list_menu">Menu</a></li>
                <li class="breadcrumb-item active"><?php echo $menu['nama_menu']; ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Info Menu -->
        <div class="card mb-3">
            <div class="row g-0">
                <div class="col-md-4">
                    <?php if ($menu['foto_menu']): ?>
                        <img src="uploads/menu/<?php echo $menu['foto_menu']; ?>" 
                             class="img-fluid rounded-start" alt="<?php echo $menu['nama_menu']; ?>"
                             style="height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <div class="bg-secondary d-flex align-items-center justify-content-center rounded-start" style="height: 100%;">
                            <i class="bi bi-image text-white" style="font-size: 5rem;"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <div class="card-body">
                        <h3 class="card-title"><?php echo $menu['nama_menu']; ?></h3>
                        <p class="card-text">
                            <span class="badge bg-secondary"><?php echo $menu['kode_menu']; ?></span>
                            <span class="badge bg-info"><?php echo $menu['nama_kategori']; ?></span>
                            <?php if ($menu['status'] == 'tersedia'): ?>
                                <span class="badge bg-success">Tersedia</span>
                            <?php elseif ($menu['status'] == 'habis'): ?>
                                <span class="badge bg-danger">Habis</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Tidak Tersedia</span>
                            <?php endif; ?>
                        </p>
                        <hr>
                        <table class="table table-borderless">
                            <tr>
                                <td width="150"><strong>Harga Jual:</strong></td>
                                <td><h4 class="text-success mb-0"><?php echo formatRupiah($menu['harga_jual']); ?></h4></td>
                            </tr>
                            <tr>
                                <td><strong>HPP (Modal):</strong></td>
                                <td><?php echo formatRupiah($menu['harga_modal']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Margin:</strong></td>
                                <td>
                                    <?php echo formatRupiah($menu['margin_keuntungan']); ?>
                                    <small class="text-muted">
                                        (<?php echo $menu['harga_jual'] > 0 ? number_format(($menu['margin_keuntungan'] / $menu['harga_jual']) * 100, 1) : 0; ?>%)
                                    </small>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resep -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-list-check"></i> Resep (Komposisi Bahan)
            </div>
            <div class="card-body">
                <?php if (empty($resep)): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        Menu ini belum memiliki resep!
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                        <a href="index.php?page=resep_menu&id=<?php echo $menu['id']; ?>" class="alert-link">Tambah resep sekarang</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Bahan</th>
                                    <th>Jumlah</th>
                                    <th>Satuan</th>
                                    <th>Biaya</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                foreach ($resep as $r): 
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo $r['nama_bahan']; ?></td>
                                    <td><?php echo number_format($r['jumlah_bahan'], 2); ?></td>
                                    <td><?php echo $r['satuan']; ?></td>
                                    <td><?php echo formatRupiah($r['biaya_bahan']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-secondary">
                                    <th colspan="4" class="text-end">TOTAL HPP:</th>
                                    <th><?php echo formatRupiah($menu['harga_modal']); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Statistik Penjualan -->
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <i class="bi bi-graph-up"></i> Statistik Penjualan
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">Total Terjual:</small>
                    <h4><?php echo $statistik['total_terjual'] ?: 0; ?> porsi</h4>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Total Transaksi:</small>
                    <h5><?php echo $statistik['total_transaksi'] ?: 0; ?> transaksi</h5>
                </div>
                <hr>
                <div class="mb-3">
                    <small class="text-muted">Total Pendapatan:</small>
                    <h5 class="text-success"><?php echo formatRupiah($statistik['total_pendapatan'] ?: 0); ?></h5>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Total Keuntungan:</small>
                    <h4 class="text-success"><?php echo formatRupiah($statistik['total_keuntungan'] ?: 0); ?></h4>
                </div>
            </div>
        </div>

        <!-- Aksi -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-gear"></i> Aksi
            </div>
            <div class="card-body">
                <?php if ($_SESSION['role'] == 'admin'): ?>
                <a href="index.php?page=edit_menu&id=<?php echo $menu['id']; ?>" class="btn btn-warning w-100 mb-2">
                    <i class="bi bi-pencil"></i> Edit Menu
                </a>
                <a href="index.php?page=resep_menu&id=<?php echo $menu['id']; ?>" class="btn btn-success w-100 mb-2">
                    <i class="bi bi-list-check"></i> Kelola Resep
                </a>
                <?php endif; ?>
                <a href="index.php?page=list_menu" class="btn btn-secondary w-100">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>