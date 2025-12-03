<?php
/**
 * DAFTAR MENU MAKANAN
 * Step 40/64 (62.5%)
 */

$menu_list = fetchAll("
    SELECT m.*, k.nama_kategori 
    FROM menu_makanan m 
    JOIN kategori_menu k ON m.kategori_id = k.id 
    ORDER BY k.nama_kategori, m.nama_menu
");
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-card-list"></i> Daftar Menu Makanan</h2>
    </div>
</div>

<!-- Summary -->
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card dashboard-card card-primary">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Total Menu</h6>
                    <h3 class="mb-0"><?php echo count($menu_list); ?></h3>
                </div>
                <div class="icon"><i class="bi bi-card-list text-primary"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card card-success">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Tersedia</h6>
                    <h3 class="mb-0"><?php echo count(array_filter($menu_list, fn($m) => $m['status'] == 'tersedia')); ?></h3>
                </div>
                <div class="icon"><i class="bi bi-check-circle text-success"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card card-danger">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Habis</h6>
                    <h3 class="mb-0"><?php echo count(array_filter($menu_list, fn($m) => $m['status'] == 'habis')); ?></h3>
                </div>
                <div class="icon"><i class="bi bi-x-circle text-danger"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card card-warning">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Tanpa Resep</h6>
                    <h3 class="mb-0"><?php echo count(array_filter($menu_list, fn($m) => $m['harga_modal'] == 0)); ?></h3>
                </div>
                <div class="icon"><i class="bi bi-exclamation-triangle text-warning"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Menu -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-list-ul"></i> Daftar Menu</span>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                <a href="index.php?page=tambah_menu" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle"></i> Tambah Menu
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($menu_list)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <p>Belum ada menu</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php 
                        $current_kategori = '';
                        foreach ($menu_list as $menu): 
                            if ($current_kategori != $menu['nama_kategori']):
                                if ($current_kategori != ''): echo '</div>'; endif;
                                $current_kategori = $menu['nama_kategori'];
                        ?>
                        <div class="col-12 mt-3">
                            <h5 class="border-bottom pb-2"><i class="bi bi-tag"></i> <?php echo $current_kategori; ?></h5>
                        </div>
                        <div class="row">
                        <?php endif; ?>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <?php if ($menu['foto_menu']): ?>
                                <img src="uploads/menu/<?php echo $menu['foto_menu']; ?>" class="card-img-top" alt="<?php echo $menu['nama_menu']; ?>" style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <i class="bi bi-image text-white" style="font-size: 3rem;"></i>
                                </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $menu['nama_menu']; ?></h5>
                                    <p class="card-text">
                                        <small class="text-muted"><?php echo $menu['kode_menu']; ?></small><br>
                                        <strong class="text-success"><?php echo formatRupiah($menu['harga_jual']); ?></strong><br>
                                        <small>HPP: <?php echo formatRupiah($menu['harga_modal']); ?></small><br>
                                        <small>Margin: <?php echo formatRupiah($menu['margin_keuntungan']); ?></small>
                                    </p>
                                    <div class="mb-2">
                                        <?php if ($menu['status'] == 'tersedia'): ?>
                                            <span class="badge bg-success">Tersedia</span>
                                        <?php elseif ($menu['status'] == 'habis'): ?>
                                            <span class="badge bg-danger">Habis</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Tidak Tersedia</span>
                                        <?php endif; ?>
                                        
                                        <?php if ($menu['harga_modal'] == 0): ?>
                                            <span class="badge bg-warning">Tanpa Resep</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="btn-group btn-group-sm w-100">
                                        <a href="index.php?page=detail_menu&id=<?php echo $menu['id']; ?>" class="btn btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($_SESSION['role'] == 'admin'): ?>
                                        <a href="index.php?page=resep_menu&id=<?php echo $menu['id']; ?>" class="btn btn-success">
                                            <i class="bi bi-list-check"></i> Resep
                                        </a>
                                        <a href="index.php?page=edit_menu&id=<?php echo $menu['id']; ?>" class="btn btn-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>