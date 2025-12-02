<?php
/**
 * DETAIL TRANSAKSI PENJUALAN
 * Step 27/64 (42.2%)
 */

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    $_SESSION['error'] = 'ID transaksi tidak valid!';
    header('Location: index.php?page=list_transaksi');
    exit;
}

// Get transaksi
$transaksi = fetchOne("
    SELECT t.*, u.nama_lengkap 
    FROM transaksi_penjualan t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.id = ?
", [$id]);

if (!$transaksi) {
    $_SESSION['error'] = 'Transaksi tidak ditemukan!';
    header('Location: index.php?page=list_transaksi');
    exit;
}

// Get detail items
$items = fetchAll("
    SELECT dt.*, m.nama_menu, m.kode_menu 
    FROM detail_transaksi dt 
    JOIN menu_makanan m ON dt.menu_id = m.id 
    WHERE dt.transaksi_id = ?
", [$id]);

// Get stock movements terkait
$movements = fetchAll("
    SELECT sm.*, b.nama_bahan, b.satuan 
    FROM stock_movement sm 
    JOIN bahan_baku b ON sm.bahan_id = b.id 
    WHERE sm.referensi_type = 'penjualan' AND sm.referensi_id = ?
    ORDER BY sm.created_at
", [$id]);

// Get kas terkait
$kas = fetchOne("
    SELECT * FROM kas_umum 
    WHERE referensi_type = 'penjualan' AND referensi_id = ?
", [$id]);
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-receipt"></i> Detail Transaksi</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=list_transaksi">Transaksi</a></li>
                <li class="breadcrumb-item active"><?php echo $transaksi['no_transaksi']; ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <!-- Info Transaksi -->
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informasi Transaksi</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="150"><strong>No. Transaksi:</strong></td>
                                <td><?php echo $transaksi['no_transaksi']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Tanggal:</strong></td>
                                <td><?php echo formatDateTime($transaksi['tanggal_transaksi'], 'd F Y H:i'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Kasir:</strong></td>
                                <td><?php echo $transaksi['nama_lengkap']; ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="150"><strong>Metode Bayar:</strong></td>
                                <td><span class="badge bg-secondary"><?php echo strtoupper($transaksi['metode_pembayaran']); ?></span></td>
                            </tr>
                            <?php if ($transaksi['metode_pembayaran'] == 'tunai'): ?>
                            <tr>
                                <td><strong>Uang Bayar:</strong></td>
                                <td><?php echo formatRupiah($transaksi['uang_bayar']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Uang Kembali:</strong></td>
                                <td><?php echo formatRupiah($transaksi['uang_kembali']); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Item Transaksi -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-cart-check"></i> Item Transaksi
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Menu</th>
                                <th>Harga</th>
                                <th>HPP</th>
                                <th class="text-center">Qty</th>
                                <th>Subtotal</th>
                                <th>Keuntungan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($items as $item): 
                                $keuntungan = $item['subtotal'] - $item['subtotal_modal'];
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <strong><?php echo $item['nama_menu']; ?></strong><br>
                                    <small class="text-muted"><?php echo $item['kode_menu']; ?></small>
                                </td>
                                <td><?php echo formatRupiah($item['harga_satuan']); ?></td>
                                <td><small><?php echo formatRupiah($item['harga_modal_satuan']); ?></small></td>
                                <td class="text-center"><strong><?php echo $item['jumlah']; ?></strong></td>
                                <td class="text-rupiah"><?php echo formatRupiah($item['subtotal']); ?></td>
                                <td class="text-success"><?php echo formatRupiah($keuntungan); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-secondary">
                                <th colspan="5" class="text-end">TOTAL:</th>
                                <th class="text-rupiah"><?php echo formatRupiah($transaksi['total_harga']); ?></th>
                                <th class="text-success"><?php echo formatRupiah($transaksi['total_keuntungan']); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Stock Movement -->
        <?php if (!empty($movements)): ?>
        <div class="card">
            <div class="card-header">
                <i class="bi bi-arrow-left-right"></i> Stock Movement (Bahan Terpakai)
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Bahan</th>
                                <th>Jumlah</th>
                                <th>Harga/Unit</th>
                                <th>Total Nilai</th>
                                <th>Stok</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movements as $move): ?>
                            <tr>
                                <td><?php echo $move['nama_bahan']; ?></td>
                                <td><?php echo number_format($move['jumlah'], 2); ?> <?php echo $move['satuan']; ?></td>
                                <td><?php echo formatRupiah($move['harga_per_satuan']); ?></td>
                                <td><?php echo formatRupiah($move['total_nilai']); ?></td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo number_format($move['stok_sebelum'], 2); ?> → 
                                        <?php echo number_format($move['stok_sesudah'], 2); ?>
                                    </small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Summary & Actions -->
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-calculator"></i> Ringkasan</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Item:</span>
                    <strong><?php echo count($items); ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Qty:</span>
                    <strong><?php echo array_sum(array_column($items, 'jumlah')); ?></strong>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-2">
                    <span>Pendapatan Kotor:</span>
                    <strong class="text-rupiah"><?php echo formatRupiah($transaksi['total_harga']); ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Modal (HPP):</span>
                    <strong><?php echo formatRupiah($transaksi['total_modal']); ?></strong>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <h5>Keuntungan Bersih:</h5>
                    <h4 class="text-success mb-0"><?php echo formatRupiah($transaksi['total_keuntungan']); ?></h4>
                </div>
                <div class="text-end">
                    <small class="text-muted">
                        Margin: <?php echo number_format(($transaksi['total_keuntungan'] / $transaksi['total_harga']) * 100, 1); ?>%
                    </small>
                </div>
            </div>
        </div>

        <?php if ($kas): ?>
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <i class="bi bi-wallet2"></i> Info Kas
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>No. Kas:</span>
                    <strong><?php echo $kas['no_transaksi_kas']; ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Nominal:</span>
                    <strong class="text-success"><?php echo formatRupiah($kas['nominal']); ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Saldo Sebelum:</span>
                    <small><?php echo formatRupiah($kas['saldo_sebelum']); ?></small>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Saldo Sesudah:</span>
                    <strong><?php echo formatRupiah($kas['saldo_sesudah']); ?></strong>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-gear"></i> Aksi
            </div>
            <div class="card-body">
                <a href="index.php?page=struk_transaksi&id=<?php echo $transaksi['id']; ?>" 
                   class="btn btn-success w-100 mb-2" target="_blank">
                    <i class="bi bi-printer"></i> Cetak Struk
                </a>
                <a href="index.php?page=list_transaksi" class="btn btn-secondary w-100">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>