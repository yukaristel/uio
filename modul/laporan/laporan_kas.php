<?php
/**
 * LAPORAN KAS UMUM
 * Menampilkan laporan mutasi kas harian, mingguan, bulanan
 */

// Default filter
$tanggal_dari = isset($_GET['tanggal_dari']) ? $_GET['tanggal_dari'] : date('Y-m-01');
$tanggal_sampai = isset($_GET['tanggal_sampai']) ? $_GET['tanggal_sampai'] : date('Y-m-d');
$jenis_transaksi = isset($_GET['jenis_transaksi']) ? $_GET['jenis_transaksi'] : 'semua';
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : 'semua';

// Query kas dengan filter
$where = [];
$where[] = "DATE(tanggal_transaksi) BETWEEN '$tanggal_dari' AND '$tanggal_sampai'";

if ($jenis_transaksi != 'semua') {
    $where[] = "jenis_transaksi = '$jenis_transaksi'";
}

if ($kategori != 'semua') {
    $where[] = "kategori = '$kategori'";
}

$where_clause = implode(' AND ', $where);

$query_kas = "
    SELECT 
        k.*,
        u.nama_lengkap as nama_user
    FROM kas_umum k
    LEFT JOIN users u ON k.user_id = u.id
    WHERE $where_clause
    ORDER BY k.tanggal_transaksi DESC, k.id DESC
";

$data_kas = fetchAll($query_kas);

// Hitung total
$total_masuk = 0;
$total_keluar = 0;
foreach ($data_kas as $kas) {
    if ($kas['jenis_transaksi'] == 'masuk') {
        $total_masuk += $kas['nominal'];
    } else {
        $total_keluar += $kas['nominal'];
    }
}
$selisih = $total_masuk - $total_keluar;

// Get saldo akhir terkini
$saldo_terkini = fetchOne("SELECT saldo_akhir FROM saldo_kas ORDER BY tanggal DESC LIMIT 1");
$saldo_akhir = $saldo_terkini ? $saldo_terkini['saldo_akhir'] : 0;
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-cash-stack"></i> Laporan Kas Umum</h2>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="bi bi-printer"></i> Cetak Laporan
        </button>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-funnel"></i> Filter Laporan</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="laporan_kas">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Dari</label>
                        <input type="date" class="form-control" name="tanggal_dari" 
                               value="<?php echo $tanggal_dari; ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Sampai</label>
                        <input type="date" class="form-control" name="tanggal_sampai" 
                               value="<?php echo $tanggal_sampai; ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Jenis Transaksi</label>
                        <select class="form-select" name="jenis_transaksi">
                            <option value="semua" <?php echo $jenis_transaksi == 'semua' ? 'selected' : ''; ?>>Semua</option>
                            <option value="masuk" <?php echo $jenis_transaksi == 'masuk' ? 'selected' : ''; ?>>Masuk</option>
                            <option value="keluar" <?php echo $jenis_transaksi == 'keluar' ? 'selected' : ''; ?>>Keluar</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="kategori">
                            <option value="semua" <?php echo $kategori == 'semua' ? 'selected' : ''; ?>>Semua</option>
                            <option value="penjualan" <?php echo $kategori == 'penjualan' ? 'selected' : ''; ?>>Penjualan</option>
                            <option value="pembelian_bahan" <?php echo $kategori == 'pembelian_bahan' ? 'selected' : ''; ?>>Pembelian Bahan</option>
                            <option value="gaji" <?php echo $kategori == 'gaji' ? 'selected' : ''; ?>>Gaji</option>
                            <option value="operasional" <?php echo $kategori == 'operasional' ? 'selected' : ''; ?>>Operasional</option>
                            <option value="investasi" <?php echo $kategori == 'investasi' ? 'selected' : ''; ?>>Investasi</option>
                            <option value="lainnya" <?php echo $kategori == 'lainnya' ? 'selected' : ''; ?>>Lainnya</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card dashboard-card card-success">
                <div class="card-body">
                    <h6 class="text-muted">Total Kas Masuk</h6>
                    <h3 class="text-success"><?php echo formatRupiah($total_masuk); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card card-danger">
                <div class="card-body">
                    <h6 class="text-muted">Total Kas Keluar</h6>
                    <h3 class="text-danger"><?php echo formatRupiah($total_keluar); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card card-warning">
                <div class="card-body">
                    <h6 class="text-muted">Selisih</h6>
                    <h3 class="<?php echo $selisih >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo formatRupiah($selisih); ?>
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card dashboard-card card-primary">
                <div class="card-body">
                    <h6 class="text-muted">Saldo Akhir</h6>
                    <h3 class="text-primary"><?php echo formatRupiah($saldo_akhir); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Laporan -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-table"></i> Detail Mutasi Kas</h5>
        </div>
        <div class="card-body">
            <?php if (empty($data_kas)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Tidak ada data kas untuk periode yang dipilih.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="12%">Tanggal</th>
                                <th width="12%">No. Transaksi</th>
                                <th width="10%">Jenis</th>
                                <th width="10%">Kategori</th>
                                <th width="13%">Nominal</th>
                                <th width="13%">Saldo Sebelum</th>
                                <th width="13%">Saldo Sesudah</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($data_kas as $kas): 
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($kas['tanggal_transaksi'])); ?></td>
                                <td><small><?php echo $kas['no_transaksi_kas']; ?></small></td>
                                <td>
                                    <?php if ($kas['jenis_transaksi'] == 'masuk'): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-arrow-down"></i> Masuk
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">
                                            <i class="bi bi-arrow-up"></i> Keluar
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small>
                                        <?php 
                                        $kategori_label = [
                                            'penjualan' => 'Penjualan',
                                            'pembelian_bahan' => 'Pembelian',
                                            'gaji' => 'Gaji',
                                            'operasional' => 'Operasional',
                                            'investasi' => 'Investasi',
                                            'lainnya' => 'Lainnya'
                                        ];
                                        echo $kategori_label[$kas['kategori']] ?? $kas['kategori'];
                                        ?>
                                    </small>
                                </td>
                                <td class="text-end">
                                    <strong class="<?php echo $kas['jenis_transaksi'] == 'masuk' ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo formatRupiah($kas['nominal']); ?>
                                    </strong>
                                </td>
                                <td class="text-end">
                                    <small><?php echo formatRupiah($kas['saldo_sebelum']); ?></small>
                                </td>
                                <td class="text-end">
                                    <small><strong><?php echo formatRupiah($kas['saldo_sesudah']); ?></strong></small>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php 
                                        echo $kas['keterangan'] ? $kas['keterangan'] : '-';
                                        if ($kas['referensi_type'] && $kas['referensi_id']) {
                                            echo "<br><em>Ref: " . strtoupper($kas['referensi_type']) . " #" . $kas['referensi_id'] . "</em>";
                                        }
                                        ?>
                                    </small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-primary">
                                <th colspan="5" class="text-end">TOTAL:</th>
                                <th class="text-end">
                                    <span class="text-success">+<?php echo formatRupiah($total_masuk); ?></span><br>
                                    <span class="text-danger">-<?php echo formatRupiah($total_keluar); ?></span>
                                </th>
                                <th colspan="3" class="text-end">
                                    <strong>Selisih: 
                                        <span class="<?php echo $selisih >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo formatRupiah($selisih); ?>
                                        </span>
                                    </strong>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Export Options -->
    <div class="card mt-3">
        <div class="card-body">
            <div class="d-flex gap-2">
                <button onclick="exportToExcel()" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel"></i> Export Excel
                </button>
                <button onclick="exportToPDF()" class="btn btn-danger">
                    <i class="bi bi-file-earmark-pdf"></i> Export PDF
                </button>
                <button onclick="window.print()" class="btn btn-secondary">
                    <i class="bi bi-printer"></i> Cetak
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .btn, .card-header, .no-print {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .table {
        font-size: 12px;
    }
    
    @page {
        size: landscape;
        margin: 1cm;
    }
}

.dashboard-card .card-body {
    padding: 1.5rem;
}

.dashboard-card h3 {
    margin-bottom: 0;
    font-weight: 700;
}

.table thead th {
    font-size: 0.9rem;
    vertical-align: middle;
}

.table tbody td {
    vertical-align: middle;
}
</style>

<script>
function exportToExcel() {
    alert('Fitur Export Excel akan segera tersedia');
    // TODO: Implementasi export Excel
}

function exportToPDF() {
    alert('Fitur Export PDF akan segera tersedia');
    // TODO: Implementasi export PDF
}

// Quick date filter
function setDateFilter(type) {
    const today = new Date();
    let dateFrom, dateTo;
    
    switch(type) {
        case 'today':
            dateFrom = dateTo = today.toISOString().split('T')[0];
            break;
        case 'week':
            dateFrom = new Date(today.setDate(today.getDate() - 7)).toISOString().split('T')[0];
            dateTo = new Date().toISOString().split('T')[0];
            break;
        case 'month':
            dateFrom = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
            dateTo = new Date().toISOString().split('T')[0];
            break;
    }
    
    document.querySelector('input[name="tanggal_dari"]').value = dateFrom;
    document.querySelector('input[name="tanggal_sampai"]').value = dateTo;
    document.querySelector('form').submit();
}
</script>