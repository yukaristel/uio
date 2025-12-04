<?php
/**
 * GENERATE / REGENERATE DATA KAS
 * Untuk sinkronisasi data kas dari transaksi penjualan dan pembelian
 */

// Cek akses admin only
if ($_SESSION['role'] != 'admin') {
    echo "<div class='alert alert-danger'>Akses ditolak! Hanya admin yang dapat mengakses halaman ini.</div>";
    exit;
}

// Ambil informasi saldo kas terkini
$saldo_kas = fetchOne("SELECT saldo_sesudah FROM kas_umum ORDER BY created_at DESC, id DESC LIMIT 1");
$saldo_terkini = $saldo_kas ? $saldo_kas['saldo_sesudah'] : 0;

// Hitung total transaksi yang perlu disinkronkan
$total_penjualan_belum_kas = fetchOne("
    SELECT COUNT(*) as total 
    FROM transaksi_penjualan tp
    LEFT JOIN kas_umum k ON k.referensi_type = 'penjualan' AND k.referensi_id = tp.id
    WHERE k.id IS NULL
");

$total_pembelian_belum_kas = fetchOne("
    SELECT COUNT(*) as total 
    FROM pembelian_bahan pb
    LEFT JOIN kas_umum k ON k.referensi_type = 'pembelian' AND k.referensi_id = pb.id
    WHERE k.id IS NULL
");

// Statistik kas
$stat_kas = fetchOne("
    SELECT 
        COUNT(*) as total_transaksi,
        COALESCE(SUM(CASE WHEN jenis_transaksi = 'masuk' THEN nominal ELSE 0 END), 0) as total_masuk,
        COALESCE(SUM(CASE WHEN jenis_transaksi = 'keluar' THEN nominal ELSE 0 END), 0) as total_keluar
    FROM kas_umum
");

$total_saldo_kas_record = fetchOne("SELECT COUNT(*) as total FROM saldo_kas");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-arrow-repeat"></i> Generate & Sinkronisasi Data Kas</h2>
            <p class="text-muted">Regenerate data kas dari transaksi penjualan dan pembelian</p>
        </div>
    </div>

    <!-- Warning Box -->
    <div class="alert alert-warning">
        <h5><i class="bi bi-exclamation-triangle"></i> Peringatan Penting!</h5>
        <ul class="mb-0">
            <li>Fitur ini akan <strong>menghapus semua data kas yang ada</strong> dan membuat ulang dari transaksi penjualan & pembelian</li>
            <li>Proses ini <strong>tidak dapat dibatalkan (irreversible)</strong></li>
            <li>Pastikan <strong>backup database</strong> terlebih dahulu sebelum menjalankan</li>
            <li>Transaksi kas manual (gaji, operasional, dll) akan <strong>ikut terhapus</strong></li>
            <li>Gunakan fitur ini hanya jika data kas tidak sinkron atau bermasalah</li>
        </ul>
    </div>

    <div class="row mb-4">
        <!-- Current Status -->
        <div class="col-md-3">
            <div class="card dashboard-card card-primary">
                <div class="card-body text-center">
                    <h6 class="text-muted">Saldo Kas Terkini</h6>
                    <h3 class="text-primary"><?php echo formatRupiah($saldo_terkini); ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card dashboard-card card-success">
                <div class="card-body text-center">
                    <h6 class="text-muted">Total Kas Masuk</h6>
                    <h4 class="text-success"><?php echo formatRupiah($stat_kas['total_masuk']); ?></h4>
                    <small class="text-muted"><?php echo number_format($stat_kas['total_transaksi']); ?> transaksi</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card dashboard-card card-danger">
                <div class="card-body text-center">
                    <h6 class="text-muted">Total Kas Keluar</h6>
                    <h4 class="text-danger"><?php echo formatRupiah($stat_kas['total_keluar']); ?></h4>
                    <small class="text-muted"><?php echo $total_saldo_kas_record['total']; ?> hari dicatat</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card dashboard-card card-warning">
                <div class="card-body text-center">
                    <h6 class="text-muted">Transaksi Belum Sinkron</h6>
                    <h4 class="text-warning">
                        <?php echo ($total_penjualan_belum_kas['total'] + $total_pembelian_belum_kas['total']); ?>
                    </h4>
                    <small class="text-muted">
                        Penjualan: <?php echo $total_penjualan_belum_kas['total']; ?> | 
                        Pembelian: <?php echo $total_pembelian_belum_kas['total']; ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Cards -->
    <div class="row">
        <!-- Full Regenerate -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-trash"></i> Full Regenerate (Reset Total)</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <strong>Bahaya!</strong> Ini akan menghapus SEMUA data kas dan membuat ulang dari awal.
                    </div>
                    
                    <h6>Yang akan dilakukan:</h6>
                    <ul>
                        <li>Hapus semua data di tabel <code>kas_umum</code></li>
                        <li>Hapus semua data di tabel <code>saldo_kas</code></li>
                        <li>Generate ulang kas dari semua transaksi penjualan</li>
                        <li>Generate ulang kas dari semua pembelian bahan</li>
                        <li>Buat record saldo kas harian</li>
                    </ul>

                    <form action="config/generate_kas_proses.php?action=full_regenerate" method="POST" id="formFullRegenerate">
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi dengan mengetik: <strong>REGENERATE</strong></label>
                            <input type="text" class="form-control" id="confirmFull" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Saldo Awal Modal (Rp)</label>
                            <input type="number" class="form-control" name="saldo_awal" value="1000000" required>
                            <small class="text-muted">Saldo kas awal sebelum transaksi pertama</small>
                        </div>
                        <button type="submit" class="btn btn-danger w-100" id="btnFullRegenerate" disabled>
                            <i class="bi bi-trash"></i> REGENERATE SEMUA DATA KAS
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sync Only -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-arrow-repeat"></i> Sinkronisasi Transaksi Baru</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <strong>Hati-hati!</strong> Ini akan menambahkan kas untuk transaksi yang belum tercatat.
                    </div>
                    
                    <h6>Yang akan dilakukan:</h6>
                    <ul>
                        <li>Cari transaksi penjualan yang belum ada di kas_umum</li>
                        <li>Cari pembelian bahan yang belum ada di kas_umum</li>
                        <li>Tambahkan entry kas untuk transaksi tersebut</li>
                        <li>Update saldo kas harian</li>
                        <li><strong>Data kas yang sudah ada tidak akan dihapus</strong></li>
                    </ul>

                    <div class="mb-3">
                        <p class="mb-1"><strong>Transaksi yang akan disinkronkan:</strong></p>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-cart-check text-success"></i> Penjualan: <strong><?php echo $total_penjualan_belum_kas['total']; ?></strong> transaksi</li>
                            <li><i class="bi bi-bag-x text-danger"></i> Pembelian: <strong><?php echo $total_pembelian_belum_kas['total']; ?></strong> transaksi</li>
                        </ul>
                    </div>

                    <?php if (($total_penjualan_belum_kas['total'] + $total_pembelian_belum_kas['total']) == 0): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> Semua transaksi sudah tersinkronisasi dengan kas!
                        </div>
                        <button type="button" class="btn btn-warning w-100" disabled>
                            <i class="bi bi-check-circle"></i> TIDAK ADA YANG PERLU DISINKRONKAN
                        </button>
                    <?php else: ?>
                        <form action="config/generate_kas_proses.php?action=sync_only" method="POST" id="formSyncOnly">
                            <div class="mb-3">
                                <label class="form-label">Konfirmasi dengan mengetik: <strong>SYNC</strong></label>
                                <input type="text" class="form-control" id="confirmSync" required>
                            </div>
                            <button type="submit" class="btn btn-warning w-100 text-dark" id="btnSyncOnly" disabled>
                                <i class="bi bi-arrow-repeat"></i> SINKRONISASI TRANSAKSI BARU
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Card -->
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-info-circle"></i> Kapan Menggunakan Fitur Ini?</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="bi bi-check-circle text-success"></i> Gunakan <strong>Full Regenerate</strong> jika:</h6>
                    <ul>
                        <li>Data kas berantakan atau tidak akurat</li>
                        <li>Saldo kas tidak sesuai dengan transaksi</li>
                        <li>Ada kesalahan perhitungan yang sistemik</li>
                        <li>Ingin memulai pencatatan kas dari awal</li>
                        <li>Setelah import data transaksi massal</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6><i class="bi bi-check-circle text-warning"></i> Gunakan <strong>Sinkronisasi</strong> jika:</h6>
                    <ul>
                        <li>Ada transaksi baru yang belum tercatat di kas</li>
                        <li>Sistem kas sempat offline/error</li>
                        <li>Data kas lama masih benar, hanya perlu update</li>
                        <li>Ingin menambahkan kas tanpa hapus data lama</li>
                    </ul>
                </div>
            </div>
            <hr>
            <p class="mb-0 text-danger"><strong>Catatan:</strong> Selalu backup database sebelum menjalankan operasi ini!</p>
        </div>
    </div>
</div>

<script>
// Konfirmasi Full Regenerate
document.getElementById('confirmFull').addEventListener('input', function() {
    const btn = document.getElementById('btnFullRegenerate');
    if (this.value === 'REGENERATE') {
        btn.disabled = false;
    } else {
        btn.disabled = true;
    }
});

document.getElementById('formFullRegenerate').addEventListener('submit', function(e) {
    if (!confirm('PERINGATAN TERAKHIR!\n\nAnda akan menghapus SEMUA data kas dan membuat ulang dari awal.\n\nApakah Anda yakin ingin melanjutkan?')) {
        e.preventDefault();
        return false;
    }
    
    // Show loading
    const btn = document.getElementById('btnFullRegenerate');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
    btn.disabled = true;
});

// Konfirmasi Sync Only
document.getElementById('confirmSync')?.addEventListener('input', function() {
    const btn = document.getElementById('btnSyncOnly');
    if (this.value === 'SYNC') {
        btn.disabled = false;
    } else {
        btn.disabled = true;
    }
});

document.getElementById('formSyncOnly')?.addEventListener('submit', function(e) {
    if (!confirm('Anda akan menambahkan entry kas untuk transaksi yang belum tercatat.\n\nProses ini akan mengubah saldo kas terkini.\n\nLanjutkan?')) {
        e.preventDefault();
        return false;
    }
    
    // Show loading
    const btn = document.getElementById('btnSyncOnly');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
    btn.disabled = true;
});
</script>

<style>
.card-body ul {
    margin-bottom: 1rem;
}

.card-body ul li {
    margin-bottom: 0.5rem;
}

code {
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 4px;
    color: #d63384;
}
</style>