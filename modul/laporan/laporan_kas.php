<?php
/**
 * LAPORAN ARUS KAS (CASH FLOW STATEMENT)
 * Menampilkan arus kas dari saldo awal sampai saldo akhir periode
 */

// Default filter
$tanggal_dari = isset($_GET['tanggal_dari']) ? $_GET['tanggal_dari'] : date('Y-m-01');
$tanggal_sampai = isset($_GET['tanggal_sampai']) ? $_GET['tanggal_sampai'] : date('Y-m-d');

// Ambil saldo awal (saldo akhir hari sebelum tanggal_dari)
$tanggal_sebelum = date('Y-m-d', strtotime($tanggal_dari . ' -1 day'));
$saldo_awal_data = fetchOne("SELECT saldo_akhir FROM saldo_kas WHERE tanggal <= '$tanggal_sebelum' ORDER BY tanggal DESC LIMIT 1");
$saldo_awal = $saldo_awal_data ? $saldo_awal_data['saldo_akhir'] : 0;

// Query kas masuk berdasarkan kategori
$kas_masuk = [];
$query_masuk = "
    SELECT 
        kategori,
        SUM(nominal) as total
    FROM kas_umum 
    WHERE jenis_transaksi = 'masuk' 
    AND DATE(tanggal_transaksi) BETWEEN '$tanggal_dari' AND '$tanggal_sampai'
    GROUP BY kategori
";
$result_masuk = fetchAll($query_masuk);
foreach ($result_masuk as $row) {
    $kas_masuk[$row['kategori']] = $row['total'];
}

// Query kas keluar berdasarkan kategori
$kas_keluar = [];
$query_keluar = "
    SELECT 
        kategori,
        SUM(nominal) as total
    FROM kas_umum 
    WHERE jenis_transaksi = 'keluar' 
    AND DATE(tanggal_transaksi) BETWEEN '$tanggal_dari' AND '$tanggal_sampai'
    GROUP BY kategori
";
$result_keluar = fetchAll($query_keluar);
foreach ($result_keluar as $row) {
    $kas_keluar[$row['kategori']] = $row['total'];
}

// Hitung total
$total_kas_masuk = array_sum($kas_masuk);
$total_kas_keluar = array_sum($kas_keluar);
$arus_kas_bersih = $total_kas_masuk - $total_kas_keluar;
$saldo_akhir = $saldo_awal + $arus_kas_bersih;

// Definisi kategori dan label
$kategori_masuk = [
    'penjualan' => 'Penerimaan dari Penjualan',
    'investasi' => 'Investasi/Modal',
    'lainnya' => 'Penerimaan Lainnya'
];

$kategori_keluar = [
    'pembelian_bahan' => 'Pembelian Bahan Baku',
    'gaji' => 'Pembayaran Gaji Karyawan',
    'operasional' => 'Biaya Operasional',
    'lainnya' => 'Pengeluaran Lainnya'
];

// Ambil detail transaksi untuk tabel
$query_detail = "
    SELECT 
        k.*,
        u.nama_lengkap as nama_user
    FROM kas_umum k
    LEFT JOIN users u ON k.user_id = u.id
    WHERE DATE(k.tanggal_transaksi) BETWEEN '$tanggal_dari' AND '$tanggal_sampai'
    ORDER BY k.tanggal_transaksi ASC, k.id ASC
";
$detail_transaksi = fetchAll($query_detail);

// Hitung saldo berjalan
$saldo_berjalan = $saldo_awal;
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-graph-up-arrow"></i> Laporan Arus Kas</h2>
        <button onclick="window.print()" class="btn btn-primary no-print">
            <i class="bi bi-printer"></i> Cetak Laporan
        </button>
    </div>

    <!-- Filter -->
    <div class="card mb-4 no-print">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-calendar-range"></i> Periode Laporan</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="laporan_kas">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Dari</label>
                        <input type="date" class="form-control" name="tanggal_dari" 
                               value="<?php echo $tanggal_dari; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Sampai</label>
                        <input type="date" class="form-control" name="tanggal_sampai" 
                               value="<?php echo $tanggal_sampai; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Tampilkan Laporan
                        </button>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-12">
                        <small class="text-muted">Quick Filter:</small>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDateFilter('today')">Hari Ini</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDateFilter('week')">7 Hari</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDateFilter('month')">Bulan Ini</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Header Laporan -->
    <div class="text-center mb-4 print-header">
        <h3><strong>LAPORAN ARUS KAS</strong></h3>
        <h5>Periode: <?php echo date('d F Y', strtotime($tanggal_dari)); ?> s/d <?php echo date('d F Y', strtotime($tanggal_sampai)); ?></h5>
        <hr>
    </div>

    <!-- Laporan Arus Kas -->
    <div class="card mb-4">
        <div class="card-body">
            <table class="table table-bordered">
                <tbody>
                    <!-- Saldo Awal -->
                    <tr class="table-light">
                        <td colspan="2"><strong>Saldo Kas Awal (<?php echo date('d/m/Y', strtotime($tanggal_dari)); ?>)</strong></td>
                        <td class="text-end" width="200"><strong><?php echo formatRupiah($saldo_awal); ?></strong></td>
                    </tr>

                    <!-- KAS MASUK -->
                    <tr class="table-success">
                        <td colspan="3"><strong>ARUS KAS MASUK</strong></td>
                    </tr>
                    <?php foreach ($kategori_masuk as $key => $label): ?>
                    <tr>
                        <td width="50"></td>
                        <td><?php echo $label; ?></td>
                        <td class="text-end"><?php echo formatRupiah($kas_masuk[$key] ?? 0); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="table-success">
                        <td colspan="2" class="text-end"><strong>Total Kas Masuk</strong></td>
                        <td class="text-end"><strong><?php echo formatRupiah($total_kas_masuk); ?></strong></td>
                    </tr>

                    <!-- KAS KELUAR -->
                    <tr class="table-danger">
                        <td colspan="3"><strong>ARUS KAS KELUAR</strong></td>
                    </tr>
                    <?php foreach ($kategori_keluar as $key => $label): ?>
                    <tr>
                        <td></td>
                        <td><?php echo $label; ?></td>
                        <td class="text-end"><?php echo formatRupiah($kas_keluar[$key] ?? 0); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="table-danger">
                        <td colspan="2" class="text-end"><strong>Total Kas Keluar</strong></td>
                        <td class="text-end"><strong><?php echo formatRupiah($total_kas_keluar); ?></strong></td>
                    </tr>

                    <!-- Arus Kas Bersih -->
                    <tr class="table-warning">
                        <td colspan="2" class="text-end"><strong>Arus Kas Bersih (Surplus/Defisit)</strong></td>
                        <td class="text-end">
                            <strong class="<?php echo $arus_kas_bersih >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo formatRupiah($arus_kas_bersih); ?>
                            </strong>
                        </td>
                    </tr>

                    <!-- Saldo Akhir -->
                    <tr class="table-primary">
                        <td colspan="2"><strong>Saldo Kas Akhir (<?php echo date('d/m/Y', strtotime($tanggal_sampai)); ?>)</strong></td>
                        <td class="text-end">
                            <strong style="font-size: 1.1em;"><?php echo formatRupiah($saldo_akhir); ?></strong>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4 no-print">
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h6>Saldo Awal</h6>
                    <h4><?php echo formatRupiah($saldo_awal); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h6>Total Kas Masuk</h6>
                    <h4><?php echo formatRupiah($total_kas_masuk); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h6>Total Kas Keluar</h6>
                    <h4><?php echo formatRupiah($total_kas_keluar); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h6>Saldo Akhir</h6>
                    <h4><?php echo formatRupiah($saldo_akhir); ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Transaksi -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Detail Transaksi Kas</h5>
        </div>
        <div class="card-body">
            <?php if (empty($detail_transaksi)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Tidak ada transaksi kas pada periode yang dipilih.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="10%">Tanggal</th>
                                <th width="15%">No. Transaksi</th>
                                <th>Keterangan</th>
                                <th width="10%" class="text-center">Jenis</th>
                                <th width="12%" class="text-end">Kas Masuk</th>
                                <th width="12%" class="text-end">Kas Keluar</th>
                                <th width="12%" class="text-end">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="table-info">
                                <td colspan="7" class="text-end"><strong>Saldo Awal</strong></td>
                                <td class="text-end"><strong><?php echo formatRupiah($saldo_berjalan); ?></strong></td>
                            </tr>
                            <?php 
                            $no = 1;
                            foreach ($detail_transaksi as $kas): 
                                if ($kas['jenis_transaksi'] == 'masuk') {
                                    $saldo_berjalan += $kas['nominal'];
                                } else {
                                    $saldo_berjalan -= $kas['nominal'];
                                }
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($kas['tanggal_transaksi'])); ?></td>
                                <td><small><?php echo $kas['no_transaksi_kas']; ?></small></td>
                                <td>
                                    <small>
                                        <?php 
                                        $kategori_label = [
                                            'penjualan' => 'Penjualan',
                                            'pembelian_bahan' => 'Pembelian Bahan',
                                            'gaji' => 'Gaji',
                                            'operasional' => 'Operasional',
                                            'investasi' => 'Investasi',
                                            'lainnya' => 'Lainnya'
                                        ];
                                        echo '<strong>' . ($kategori_label[$kas['kategori']] ?? $kas['kategori']) . '</strong>';
                                        echo $kas['keterangan'] ? '<br>' . $kas['keterangan'] : '';
                                        ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <?php if ($kas['jenis_transaksi'] == 'masuk'): ?>
                                        <span class="badge bg-success">Masuk</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Keluar</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php echo $kas['jenis_transaksi'] == 'masuk' ? formatRupiah($kas['nominal']) : '-'; ?>
                                </td>
                                <td class="text-end">
                                    <?php echo $kas['jenis_transaksi'] == 'keluar' ? formatRupiah($kas['nominal']) : '-'; ?>
                                </td>
                                <td class="text-end">
                                    <strong><?php echo formatRupiah($saldo_berjalan); ?></strong>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="table-primary">
                                <td colspan="5" class="text-end"><strong>TOTAL</strong></td>
                                <td class="text-end"><strong><?php echo formatRupiah($total_kas_masuk); ?></strong></td>
                                <td class="text-end"><strong><?php echo formatRupiah($total_kas_keluar); ?></strong></td>
                                <td class="text-end"><strong><?php echo formatRupiah($saldo_akhir); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer Actions -->
    <div class="card mt-3 no-print">
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
    .no-print {
        display: none !important;
    }
    
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
        page-break-inside: avoid;
    }
    
    .table {
        font-size: 11px;
    }
    
    .print-header {
        margin-bottom: 20px;
    }
    
    @page {
        size: A4;
        margin: 15mm;
    }
    
    body {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }
}

.table-bordered td, .table-bordered th {
    border: 1px solid #dee2e6;
}

.card-body table tr td {
    padding: 0.5rem;
}
</style>

<script>
function exportToExcel() {
    alert('Fitur Export Excel akan segera tersedia');
}

function exportToPDF() {
    alert('Fitur Export PDF akan segera tersedia');
}

function setDateFilter(type) {
    const today = new Date();
    let dateFrom, dateTo;
    
    switch(type) {
        case 'today':
            dateFrom = dateTo = today.toISOString().split('T')[0];
            break;
        case 'week':
            const weekAgo = new Date(today);
            weekAgo.setDate(weekAgo.getDate() - 7);
            dateFrom = weekAgo.toISOString().split('T')[0];
            dateTo = today.toISOString().split('T')[0];
            break;
        case 'month':
            dateFrom = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
            dateTo = today.toISOString().split('T')[0];
            break;
    }
    
    document.querySelector('input[name="tanggal_dari"]').value = dateFrom;
    document.querySelector('input[name="tanggal_sampai"]').value = dateTo;
}
</script>