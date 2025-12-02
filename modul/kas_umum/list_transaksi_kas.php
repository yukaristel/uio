<?php
/**
 * DAFTAR TRANSAKSI KAS
 * Step 33/64 (51.6%)
 */

// Filter
$tanggal_dari = isset($_GET['tanggal_dari']) ? $_GET['tanggal_dari'] : date('Y-m-01');
$tanggal_sampai = isset($_GET['tanggal_sampai']) ? $_GET['tanggal_sampai'] : date('Y-m-d');
$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';

// Build query
$where = "DATE(tanggal_transaksi) BETWEEN ? AND ?";
$params = [$tanggal_dari, $tanggal_sampai];

if (!empty($jenis)) {
    $where .= " AND jenis_transaksi = ?";
    $params[] = $jenis;
}

if (!empty($kategori)) {
    $where .= " AND kategori = ?";
    $params[] = $kategori;
}

// Get transaksi kas
$transaksi_list = fetchAll("
    SELECT k.*, u.nama_lengkap 
    FROM kas_umum k
    JOIN users u ON k.user_id = u.id
    WHERE $where
    ORDER BY k.tanggal_transaksi DESC, k.id DESC
", $params);

// Summary
$summary = fetchOne("
    SELECT 
        COUNT(*) as total_transaksi,
        COALESCE(SUM(CASE WHEN jenis_transaksi = 'masuk' THEN nominal ELSE 0 END), 0) as total_masuk,
        COALESCE(SUM(CASE WHEN jenis_transaksi = 'keluar' THEN nominal ELSE 0 END), 0) as total_keluar
    FROM kas_umum
    WHERE $where
", $params);

$selisih = $summary['total_masuk'] - $summary['total_keluar'];
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-list-check"></i> Daftar Transaksi Kas</h2>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card dashboard-card card-primary">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Total Transaksi</h6>
                    <h3 class="mb-0"><?php echo $summary['total_transaksi']; ?></h3>
                </div>
                <div class="icon">
                    <i class="bi bi-list-check text-primary"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card dashboard-card card-success">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Total Pemasukan</h6>
                    <h4 class="mb-0 text-success"><?php echo formatRupiah($summary['total_masuk']); ?></h4>
                </div>
                <div class="icon">
                    <i class="bi bi-arrow-down-circle text-success"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card dashboard-card card-danger">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Total Pengeluaran</h6>
                    <h4 class="mb-0 text-danger"><?php echo formatRupiah($summary['total_keluar']); ?></h4>
                </div>
                <div class="icon">
                    <i class="bi bi-arrow-up-circle text-danger"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card dashboard-card card-info">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Selisih</h6>
                    <h4 class="mb-0 <?php echo $selisih >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo formatRupiah($selisih); ?>
                    </h4>
                </div>
                <div class="icon">
                    <i class="bi bi-graph-up-arrow text-info"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter & Action -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <input type="hidden" name="page" value="list_transaksi_kas">
                    
                    <div class="col-md-2">
                        <label class="form-label">Tanggal Dari</label>
                        <input type="date" class="form-control" name="tanggal_dari" 
                               value="<?php echo $tanggal_dari; ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Tanggal Sampai</label>
                        <input type="date" class="form-control" name="tanggal_sampai" 
                               value="<?php echo $tanggal_sampai; ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Jenis</label>
                        <select class="form-select" name="jenis">
                            <option value="">Semua</option>
                            <option value="masuk" <?php echo $jenis == 'masuk' ? 'selected' : ''; ?>>Pemasukan</option>
                            <option value="keluar" <?php echo $jenis == 'keluar' ? 'selected' : ''; ?>>Pengeluaran</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="kategori">
                            <option value="">Semua</option>
                            <option value="penjualan" <?php echo $kategori == 'penjualan' ? 'selected' : ''; ?>>Penjualan</option>
                            <option value="pembelian_bahan" <?php echo $kategori == 'pembelian_bahan' ? 'selected' : ''; ?>>Pembelian Bahan</option>
                            <option value="gaji" <?php echo $kategori == 'gaji' ? 'selected' : ''; ?>>Gaji</option>
                            <option value="operasional" <?php echo $kategori == 'operasional' ? 'selected' : ''; ?>>Operasional</option>
                            <option value="investasi" <?php echo $kategori == 'investasi' ? 'selected' : ''; ?>>Investasi</option>
                            <option value="lainnya" <?php echo $kategori == 'lainnya' ? 'selected' : ''; ?>>Lainnya</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Filter
                            </button>
                            <a href="index.php?page=list_transaksi_kas" class="btn btn-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Transaksi -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-ul"></i> Daftar Transaksi Kas</span>
                <a href="index.php?page=tambah_transaksi_kas" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle"></i> Transaksi Baru
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($transaksi_list)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-3">Tidak ada transaksi pada periode ini</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>No. Transaksi</th>
                                    <th>Tanggal</th>
                                    <th>Jenis</th>
                                    <th>Kategori</th>
                                    <th>Nominal</th>
                                    <th>Saldo</th>
                                    <th>Keterangan</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transaksi_list as $trx): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $trx['no_transaksi_kas']; ?></strong>
                                        <?php if ($trx['referensi_type']): ?>
                                            <br><small class="text-muted">
                                                Ref: <?php echo ucfirst($trx['referensi_type']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatDateTime($trx['tanggal_transaksi'], 'd/m/Y H:i'); ?></td>
                                    <td>
                                        <?php if ($trx['jenis_transaksi'] == 'masuk'): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-arrow-down-circle"></i> Masuk
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">
                                                <i class="bi bi-arrow-up-circle"></i> Keluar
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo ucwords(str_replace('_', ' ', $trx['kategori'])); ?></small>
                                    </td>
                                    <td class="<?php echo $trx['jenis_transaksi'] == 'masuk' ? 'text-success' : 'text-danger'; ?>">
                                        <strong><?php echo formatRupiah($trx['nominal']); ?></strong>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo formatRupiah($trx['saldo_sebelum']); ?> 
                                            → 
                                            <strong><?php echo formatRupiah($trx['saldo_sesudah']); ?></strong>
                                        </small>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars(substr($trx['keterangan'], 0, 50)); ?></small>
                                        <?php if (strlen($trx['keterangan']) > 50): ?>
                                            ...
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?php echo $trx['nama_lengkap']; ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-secondary">
                                    <th colspan="4" class="text-end">TOTAL:</th>
                                    <th>
                                        <span class="text-success">▼ <?php echo formatRupiah($summary['total_masuk']); ?></span><br>
                                        <span class="text-danger">▲ <?php echo formatRupiah($summary['total_keluar']); ?></span>
                                    </th>
                                    <th colspan="3"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>