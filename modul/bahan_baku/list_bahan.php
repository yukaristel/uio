<?php
/**
 * DAFTAR BAHAN BAKU
 * Step 34/64 (53.1%)
 */

// Get daftar bahan
$bahan_list = fetchAll("SELECT * FROM bahan_baku ORDER BY nama_bahan");

// Hitung total nilai stok
$total_nilai_stok = 0;
foreach ($bahan_list as $bahan) {
    $total_nilai_stok += $bahan['stok_tersedia'] * $bahan['harga_beli_per_satuan'];
}
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-box-seam"></i> Daftar Bahan Baku</h2>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card dashboard-card card-primary">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Total Bahan</h6>
                    <h3 class="mb-0"><?php echo count($bahan_list); ?></h3>
                </div>
                <div class="icon">
                    <i class="bi bi-box-seam text-primary"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card dashboard-card card-warning">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Stok Menipis</h6>
                    <h3 class="mb-0">
                        <?php 
                        $menipis = array_filter($bahan_list, function($b) {
                            return $b['stok_tersedia'] <= $b['stok_minimum'];
                        });
                        echo count($menipis); 
                        ?>
                    </h3>
                </div>
                <div class="icon">
                    <i class="bi bi-exclamation-triangle text-warning"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card dashboard-card card-danger">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Stok Habis</h6>
                    <h3 class="mb-0">
                        <?php 
                        $habis = array_filter($bahan_list, function($b) {
                            return $b['stok_tersedia'] == 0;
                        });
                        echo count($habis); 
                        ?>
                    </h3>
                </div>
                <div class="icon">
                    <i class="bi bi-x-circle text-danger"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card dashboard-card card-success">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Total Nilai Stok</h6>
                    <h4 class="mb-0 text-success"><?php echo formatRupiah($total_nilai_stok); ?></h4>
                </div>
                <div class="icon">
                    <i class="bi bi-cash-stack text-success"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Bahan -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-ul"></i> Daftar Bahan Baku</span>
                <div>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                    <a href="index.php?page=tambah_bahan" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle"></i> Tambah Bahan
                    </a>
                    <a href="index.php?page=pembelian_bahan" class="btn btn-success btn-sm">
                        <i class="bi bi-cart-plus"></i> Pembelian
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($bahan_list)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-3">Belum ada bahan baku</p>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                        <a href="index.php?page=tambah_bahan" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Tambah Bahan Pertama
                        </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Bahan</th>
                                    <th>Stok Tersedia</th>
                                    <th>Stok Min</th>
                                    <th>Satuan</th>
                                    <th>Harga/Satuan</th>
                                    <th>Nilai Stok</th>
                                    <th>Status</th>
                                    <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <th>Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bahan_list as $bahan): 
                                    $nilai_stok = $bahan['stok_tersedia'] * $bahan['harga_beli_per_satuan'];
                                    $is_menipis = $bahan['stok_tersedia'] <= $bahan['stok_minimum'];
                                    $is_habis = $bahan['stok_tersedia'] == 0;
                                ?>
                                <tr class="<?php echo $is_habis ? 'table-danger' : ($is_menipis ? 'table-warning' : ''); ?>">
                                    <td><strong><?php echo $bahan['kode_bahan']; ?></strong></td>
                                    <td><strong><?php echo $bahan['nama_bahan']; ?></strong></td>
                                    <td>
                                        <strong class="<?php echo $is_habis ? 'text-danger' : ($is_menipis ? 'text-warning' : ''); ?>">
                                            <?php echo number_format($bahan['stok_tersedia'], 2); ?>
                                        </strong>
                                    </td>
                                    <td><?php echo number_format($bahan['stok_minimum'], 2); ?></td>
                                    <td><?php echo $bahan['satuan']; ?></td>
                                    <td><?php echo formatRupiah($bahan['harga_beli_per_satuan']); ?></td>
                                    <td class="text-success"><strong><?php echo formatRupiah($nilai_stok); ?></strong></td>
                                    <td>
                                        <?php if ($is_habis): ?>
                                            <span class="badge bg-danger">HABIS</span>
                                        <?php elseif ($is_menipis): ?>
                                            <span class="badge bg-warning">MENIPIS</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">AMAN</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="index.php?page=edit_bahan&id=<?php echo $bahan['id']; ?>" 
                                               class="btn btn-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="index.php?page=pembelian_bahan&bahan_id=<?php echo $bahan['id']; ?>" 
                                               class="btn btn-success" title="Beli">
                                                <i class="bi bi-cart-plus"></i>
                                            </a>
                                            <a href="config/bahan_proses.php?action=delete&id=<?php echo $bahan['id']; ?>" 
                                               class="btn btn-danger" 
                                               onclick="return confirm('Hapus bahan <?php echo $bahan['nama_bahan']; ?>?')"
                                               title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-secondary">
                                    <th colspan="6" class="text-end">TOTAL NILAI STOK:</th>
                                    <th class="text-success"><?php echo formatRupiah($total_nilai_stok); ?></th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Alert Stok Menipis -->
<?php if (!empty($menipis)): ?>
<div class="row mt-3">
    <div class="col-md-12">
        <div class="alert alert-warning">
            <h5><i class="bi bi-exclamation-triangle"></i> Peringatan: <?php echo count($menipis); ?> Bahan Stok Menipis!</h5>
            <ul class="mb-0">
                <?php foreach ($menipis as $bahan): ?>
                <li>
                    <strong><?php echo $bahan['nama_bahan']; ?></strong>: 
                    <?php echo number_format($bahan['stok_tersedia'], 2); ?> <?php echo $bahan['satuan']; ?>
                    (Min: <?php echo number_format($bahan['stok_minimum'], 2); ?> <?php echo $bahan['satuan']; ?>)
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                    - <a href="index.php?page=pembelian_bahan&bahan_id=<?php echo $bahan['id']; ?>" class="alert-link">Beli Sekarang</a>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<?php endif; ?>