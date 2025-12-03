<?php
/**
 * DETAIL TRANSAKSI KAS
 * Step 44/64 (68.8%)
 */

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    $_SESSION['error'] = 'ID transaksi kas tidak valid!';
    header('Location: index.php?page=list_transaksi_kas');
    exit;
}

$kas = fetchOne("
    SELECT k.*, u.nama_lengkap 
    FROM kas_umum k
    JOIN users u ON k.user_id = u.id
    WHERE k.id = ?
", [$id]);

if (!$kas) {
    $_SESSION['error'] = 'Transaksi kas tidak ditemukan!';
    header('Location: index.php?page=list_transaksi_kas');
    exit;
}

// Get referensi transaksi jika ada
$referensi = null;
if ($kas['referensi_type'] == 'penjualan' && $kas['referensi_id']) {
    $referensi = fetchOne("SELECT * FROM transaksi_penjualan WHERE id = ?", [$kas['referensi_id']]);
} elseif ($kas['referensi_type'] == 'pembelian' && $kas['referensi_id']) {
    $referensi = fetchOne("
        SELECT pb.*, b.nama_bahan 
        FROM pembelian_bahan pb 
        JOIN bahan_baku b ON pb.bahan_id = b.id 
        WHERE pb.id = ?
    ", [$kas['referensi_id']]);
}
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-receipt"></i> Detail Transaksi Kas</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=list_transaksi_kas">Transaksi Kas</a></li>
                <li class="breadcrumb-item active"><?php echo $kas['no_transaksi_kas']; ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header <?php echo $kas['jenis_transaksi'] == 'masuk' ? 'bg-success' : 'bg-danger'; ?> text-white">
                <h5 class="mb-0">
                    <i class="bi bi-<?php echo $kas['jenis_transaksi'] == 'masuk' ? 'arrow-down-circle' : 'arrow-up-circle'; ?>"></i>
                    Transaksi Kas <?php echo ucfirst($kas['jenis_transaksi']); ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td width="150"><strong>No. Transaksi:</strong></td>
                                <td><?php echo $kas['no_transaksi_kas']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Tanggal:</strong></td>
                                <td><?php echo formatDateTime($kas['tanggal_transaksi'], 'd F Y H:i'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Jenis:</strong></td>
                                <td>
                                    <?php if ($kas['jenis_transaksi'] == 'masuk'): ?>
                                        <span class="badge bg-success">Pemasukan</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Pengeluaran</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Kategori:</strong></td>
                                <td><span class="badge bg-secondary"><?php echo ucwords(str_replace('_', ' ', $kas['kategori'])); ?></span></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td width="150"><strong>Nominal:</strong></td>
                                <td>
                                    <h4 class="<?php echo $kas['jenis_transaksi'] == 'masuk' ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo formatRupiah($kas['nominal']); ?>
                                    </h4>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Saldo Sebelum:</strong></td>
                                <td><?php echo formatRupiah($kas['saldo_sebelum']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Saldo Sesudah:</strong></td>
                                <td><strong><?php echo formatRupiah($kas['saldo_sesudah']); ?></strong></td>
                            </tr>
                            <tr>
                                <td><strong>User:</strong></td>
                                <td><?php echo $kas['nama_lengkap']; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php if ($kas['keterangan']): ?>
                <hr>
                <div>
                    <strong>Keterangan:</strong><br>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($kas['keterangan'])); ?></p>
                </div>
                <?php endif; ?>

                <?php if ($referensi): ?>
                <hr>
                <div class="alert alert-info">
                    <strong><i class="bi bi-link-45deg"></i> Referensi Transaksi:</strong><br>
                    <?php if ($kas['referensi_type'] == 'penjualan'): ?>
                        No. Transaksi: <strong><?php echo $referensi['no_transaksi']; ?></strong><br>
                        Total: <?php echo formatRupiah($referensi['total_harga']); ?><br>
                        <a href="index.php?page=detail_transaksi&id=<?php echo $referensi['id']; ?>" class="btn btn-sm btn-info mt-2">
                            <i class="bi bi-eye"></i> Lihat Detail Transaksi
                        </a>
                    <?php elseif ($kas['referensi_type'] == 'pembelian'): ?>
                        Bahan: <strong><?php echo $referensi['nama_bahan']; ?></strong><br>
                        Jumlah: <?php echo $referensi['jumlah_beli']; ?> @ <?php echo formatRupiah($referensi['harga_beli_satuan']); ?><br>
                        Total: <?php echo formatRupiah($referensi['total_harga']); ?><br>
                        Supplier: <?php echo $referensi['supplier'] ?: '-'; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-graph-up"></i> Dampak ke Saldo
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <small class="text-muted">Saldo Sebelum</small>
                    <h4><?php echo formatRupiah($kas['saldo_sebelum']); ?></h4>
                </div>
                <div class="text-center mb-3">
                    <i class="bi bi-arrow-down" style="font-size: 2rem;"></i><br>
                    <span class="badge <?php echo $kas['jenis_transaksi'] == 'masuk' ? 'bg-success' : 'bg-danger'; ?>" style="font-size: 1.2rem;">
                        <?php echo $kas['jenis_transaksi'] == 'masuk' ? '+' : '-'; ?>
                        <?php echo formatRupiah($kas['nominal']); ?>
                    </span>
                </div>
                <div class="text-center">
                    <small class="text-muted">Saldo Sesudah</small>
                    <h3 class="text-primary"><?php echo formatRupiah($kas['saldo_sesudah']); ?></h3>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <i class="bi bi-gear"></i> Aksi
            </div>
            <div class="card-body">
                <a href="index.php?page=list_transaksi_kas" class="btn btn-secondary w-100 mb-2">
                    <i class="bi bi-arrow-left"></i> Kembali ke List
                </a>
                <a href="index.php?page=dashboard_kas" class="btn btn-primary w-100">
                    <i class="bi bi-speedometer2"></i> Dashboard Kas
                </a>
            </div>
        </div>
    </div>
</div>