<?php
/**
 * DAFTAR KATEGORI MENU
 * Step 37/64 (57.8%)
 */

// Get kategori dengan jumlah menu
$kategori_list = fetchAll("
    SELECT k.*, COUNT(m.id) as jumlah_menu
    FROM kategori_menu k
    LEFT JOIN menu_makanan m ON k.id = m.kategori_id
    GROUP BY k.id
    ORDER BY k.nama_kategori
");
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-tag"></i> Daftar Kategori Menu</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-ul"></i> Kategori Menu</span>
                <a href="index.php?page=tambah_kategori" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle"></i> Tambah Kategori
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($kategori_list)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-3">Belum ada kategori menu</p>
                        <a href="index.php?page=tambah_kategori" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Tambah Kategori Pertama
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th width="50">#</th>
                                    <th>Nama Kategori</th>
                                    <th>Deskripsi</th>
                                    <th width="100" class="text-center">Jumlah Menu</th>
                                    <th width="150">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                foreach ($kategori_list as $kat): 
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><strong><?php echo $kat['nama_kategori']; ?></strong></td>
                                    <td><?php echo $kat['deskripsi'] ?: '-'; ?></td>
                                    <td class="text-center">
                                        <?php if ($kat['jumlah_menu'] > 0): ?>
                                            <span class="badge bg-info"><?php echo $kat['jumlah_menu']; ?> menu</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">0 menu</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="index.php?page=edit_kategori&id=<?php echo $kat['id']; ?>" 
                                               class="btn btn-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="config/kategori_proses.php?action=delete&id=<?php echo $kat['id']; ?>" 
                                               class="btn btn-danger" 
                                               onclick="return confirm('Hapus kategori <?php echo $kat['nama_kategori']; ?>? <?php echo $kat['jumlah_menu'] > 0 ? 'Kategori ini memiliki ' . $kat['jumlah_menu'] . ' menu!' : ''; ?>')"
                                               title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </a>
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

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <i class="bi bi-info-circle"></i> Informasi
            </div>
            <div class="card-body">
                <h5>Total Kategori: <?php echo count($kategori_list); ?></h5>
                <hr>
                <p class="mb-2"><strong>Contoh Kategori:</strong></p>
                <ul class="small">
                    <li>Makanan Berat</li>
                    <li>Makanan Ringan</li>
                    <li>Minuman Dingin</li>
                    <li>Minuman Panas</li>
                    <li>Snack</li>
                    <li>Dessert</li>
                    <li>Paket Hemat</li>
                </ul>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-warning">
                <i class="bi bi-lightbulb"></i> Tips
            </div>
            <div class="card-body">
                <ul class="small mb-0">
                    <li>Kategorikan menu agar mudah dicari</li>
                    <li>Gunakan nama kategori yang jelas</li>
                    <li>Kategori dengan menu tidak bisa dihapus</li>
                    <li>Hapus/pindahkan menu dulu sebelum hapus kategori</li>
                </ul>
            </div>
        </div>
    </div>
</div>