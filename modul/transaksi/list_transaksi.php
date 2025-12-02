<?php
/**
 * DAFTAR TRANSAKSI PENJUALAN
 * Step 26/64 (40.6%)
 */

// Filter
$tanggal_dari = isset($_GET['tanggal_dari']) ? $_GET['tanggal_dari'] : date('Y-m-d');
$tanggal_sampai = isset($_GET['tanggal_sampai']) ? $_GET['tanggal_sampai'] : date('Y-m-d');
$metode = isset($_GET['metode']) ? $_GET['metode'] : '';

// Build query
$where = "DATE(tanggal_transaksi) BETWEEN ? AND ?";
$params = [$tanggal_dari, $tanggal_sampai];

if (!empty($metode)) {
    $where .= " AND metode_pembayaran = ?";
    $params[] = $metode;
}

// Get transaksi
$transaksi_list = fetchAll("
    SELECT t.*, u.nama_lengkap 
    FROM transaksi_penjualan t 
    JOIN users u ON t.user_id = u.id 
    WHERE $where 
    ORDER BY t.tanggal_transaksi DESC
", $params);

// Summary
$summary = fetchOne("
    SELECT 
        COUNT(*) as total_transaksi,
        COALESCE(SUM(total_harga), 0) as total_pendapatan,
        COALESCE(SUM(total_modal), 0) as total_modal,
        COALESCE(SUM(total_keuntungan), 0) as total_keuntungan
    FROM transaksi_penjualan 
    WHERE $where
", $params);
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-receipt"></i> Daftar Transaksi Penjualan</h2>
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
                    <i class="bi bi-receipt text-primary"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card card-success">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Total Pendapatan</h6>
                    <h4 class="mb-0 text-success"><?php echo formatRupiah($summary['total_pendapatan']); ?></h4>
                </div>
                <div class="icon">
                    <i class="bi bi-cash-stack text-success"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card card-warning">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Total Modal</h6>
                    <h4 class="mb-0 text-warning"><?php echo formatRupiah($summary['total_modal']); ?></h4>
                </div>
                <div class="icon">
                    <i class="bi bi-wallet text-warning"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card card-info">
            <div class="card-body">
                <div>
                    <h6 class="text-muted">Total Keuntungan</h6>
                    <h4 class="mb-0 text-info"><?php echo formatRupiah($summary['total_keuntungan']); ?></h4>
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
                    <input type="hidden" name="page" value="list_transaksi">
                    
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Dari</label>
                        <input type="date" class="form-control" name="tanggal_dari" 
                               value="<?php echo $tanggal_dari; ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Sampai</label>
                        <input type="date" class="form-control" name="tanggal_sampai" 
                               value="<?php echo $tanggal_sampai; ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Metode Pembayaran</label>
                        <select class="form-select" name="metode">
                            <option value="">Semua</option>
                            <option value="tunai" <?php echo $metode == 'tunai' ? 'selected' : ''; ?>>Tunai</option>
                            <option value="debit" <?php echo $metode == 'debit' ? 'selected' : ''; ?>>Debit</option>
                            <option value="qris" <?php echo $metode == 'qris' ? 'selected' : ''; ?>>QRIS</option>
                            <option value="transfer" <?php echo $metode == 'transfer' ? 'selected' : ''; ?>>Transfer</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Filter
                            </button>
                            <a href="index.php?page=list_transaksi" class="btn btn-secondary">
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
                <span><i class="bi bi-list-ul"></i> Daftar Transaksi</span>
                <a href="index.php?page=buat_transaksi" class="btn btn-success btn-sm">
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
                                    <th>Kasir</th>
                                    <th>Total Harga</th>
                                    <th>Modal</th>
                                    <th>Keuntungan</th>
                                    <th>Metode</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transaksi_list as $trx): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $trx['no_transaksi']; ?></strong>
                                    </td>
                                    <td><?php echo formatDateTime($trx['tanggal_transaksi'], 'd/m/Y H:i'); ?></td>
                                    <td><?php echo $trx['nama_lengkap']; ?></td>
                                    <td class="text-rupiah"><?php echo formatRupiah($trx['total_harga']); ?></td>
                                    <td><?php echo formatRupiah($trx['total_modal']); ?></td>
                                    <td class="text-success"><strong><?php echo formatRupiah($trx['total_keuntungan']); ?></strong></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo strtoupper($trx['metode_pembayaran']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="index.php?page=detail_transaksi&id=<?php echo $trx['id']; ?>" 
                                           class="btn btn-sm btn-info" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="index.php?page=struk_transaksi&id=<?php echo $trx['id']; ?>" 
                                           class="btn btn-sm btn-success" title="Cetak Struk" target="_blank">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-secondary">
                                    <th colspan="3" class="text-end">TOTAL:</th>
                                    <th class="text-rupiah"><?php echo formatRupiah($summary['total_pendapatan']); ?></th>
                                    <th><?php echo formatRupiah($summary['total_modal']); ?></th>
                                    <th class="text-success"><?php echo formatRupiah($summary['total_keuntungan']); ?></th>
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