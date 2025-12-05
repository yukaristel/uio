<?php
/**
 * LAPORAN STOCK & INVENTORY
 * Laporan stock bahan baku dan pergerakannya
 */

// Default periode (bulan ini)
$tanggal_dari = isset($_GET['tanggal_dari']) ? $_GET['tanggal_dari'] : date('Y-m-01');
$tanggal_sampai = isset($_GET['tanggal_sampai']) ? $_GET['tanggal_sampai'] : date('Y-m-d');
$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : 'semua';

// ============================================
// DATA STOCK SAAT INI
// ============================================
$stock_bahan = fetchAll("
    SELECT 
        bb.id,
        bb.kode_bahan,
        bb.nama_bahan,
        bb.satuan,
        bb.stok_tersedia,
        bb.stok_minimum,
        bb.harga_beli_per_satuan,
        (bb.stok_tersedia * bb.harga_beli_per_satuan) as nilai_stock,
        CASE 
            WHEN bb.stok_tersedia <= 0 THEN 'habis'
            WHEN bb.stok_tersedia <= bb.stok_minimum THEN 'menipis'
            ELSE 'aman'
        END as status_stock
    FROM bahan_baku bb
    ORDER BY 
        CASE 
            WHEN bb.stok_tersedia <= 0 THEN 1
            WHEN bb.stok_tersedia <= bb.stok_minimum THEN 2
            ELSE 3
        END,
        bb.nama_bahan ASC
");

// ============================================
// RINGKASAN STOCK
// ============================================
$ringkasan_stock = [
    'total_bahan' => 0,
    'stock_habis' => 0,
    'stock_menipis' => 0,
    'stock_aman' => 0,
    'total_nilai_stock' => 0
];

foreach ($stock_bahan as $bahan) {
    $ringkasan_stock['total_bahan']++;
    $ringkasan_stock['total_nilai_stock'] += $bahan['nilai_stock'];
    
    if ($bahan['status_stock'] == 'habis') {
        $ringkasan_stock['stock_habis']++;
    } elseif ($bahan['status_stock'] == 'menipis') {
        $ringkasan_stock['stock_menipis']++;
    } else {
        $ringkasan_stock['stock_aman']++;
    }
}

// ============================================
// PERGERAKAN STOCK PERIODE INI
// ============================================
$where_jenis = '';
if ($jenis != 'semua') {
    $where_jenis = "AND sm.jenis_pergerakan = '$jenis'";
}

$pergerakan_stock = fetchAll("
    SELECT 
        sm.id,
        sm.created_at,
        bb.kode_bahan,
        bb.nama_bahan,
        bb.satuan,
        sm.jenis_pergerakan,
        sm.jumlah,
        sm.harga_per_satuan,
        sm.total_nilai,
        sm.stok_sebelum,
        sm.stok_sesudah,
        sm.keterangan,
        u.nama_lengkap as user_name
    FROM stock_movement sm
    JOIN bahan_baku bb ON sm.bahan_id = bb.id
    LEFT JOIN users u ON sm.user_id = u.id
    WHERE DATE(sm.created_at) BETWEEN ? AND ?
    $where_jenis
    ORDER BY sm.created_at DESC
    LIMIT 100
", [$tanggal_dari, $tanggal_sampai]);

// ============================================
// RINGKASAN PERGERAKAN
// ============================================
$ringkasan_pergerakan = fetchOne("
    SELECT 
        COUNT(*) as total_pergerakan,
        COALESCE(SUM(CASE WHEN jenis_pergerakan = 'masuk' THEN total_nilai ELSE 0 END), 0) as nilai_masuk,
        COALESCE(SUM(CASE WHEN jenis_pergerakan = 'keluar' THEN total_nilai ELSE 0 END), 0) as nilai_keluar,
        COALESCE(SUM(CASE WHEN jenis_pergerakan IN ('rusak', 'expired', 'hilang', 'tumpah') THEN total_nilai ELSE 0 END), 0) as nilai_kerugian
    FROM stock_movement
    WHERE DATE(created_at) BETWEEN ? AND ?
", [$tanggal_dari, $tanggal_sampai]);

// ============================================
// TOP BAHAN PALING SERING DIGUNAKAN
// ============================================
$top_bahan_keluar = fetchAll("
    SELECT 
        bb.nama_bahan,
        bb.satuan,
        COUNT(*) as frekuensi,
        SUM(sm.jumlah) as total_keluar,
        SUM(sm.total_nilai) as total_nilai
    FROM stock_movement sm
    JOIN bahan_baku bb ON sm.bahan_id = bb.id
    WHERE sm.jenis_pergerakan = 'keluar'
    AND DATE(sm.created_at) BETWEEN ? AND ?
    GROUP BY sm.bahan_id
    ORDER BY total_keluar DESC
    LIMIT 10
", [$tanggal_dari, $tanggal_sampai]);

// ============================================
// KERUGIAN BAHAN (Rusak, Expired, Hilang, Tumpah)
// ============================================
$kerugian_bahan = fetchAll("
    SELECT 
        bb.nama_bahan,
        bb.satuan,
        sm.jenis_pergerakan,
        SUM(sm.jumlah) as total_jumlah,
        SUM(sm.total_nilai) as total_kerugian
    FROM stock_movement sm
    JOIN bahan_baku bb ON sm.bahan_id = bb.id
    WHERE sm.jenis_pergerakan IN ('rusak', 'expired', 'hilang', 'tumpah')
    AND DATE(sm.created_at) BETWEEN ? AND ?
    GROUP BY sm.bahan_id, sm.jenis_pergerakan
    ORDER BY total_kerugian DESC
    LIMIT 10
", [$tanggal_dari, $tanggal_sampai]);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-box-seam"></i> Laporan Stock & Inventory</h2>
        <button onclick="window.print()" class="btn btn-primary no-print">
            <i class="bi bi-printer"></i> Cetak Laporan
        </button>
    </div>

    <!-- Filter -->
    <div class="card mb-4 no-print">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-funnel"></i> Filter Pergerakan Stock</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="laporan_stock">
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
                    <div class="col-md-3">
                        <label class="form-label">Jenis Pergerakan</label>
                        <select class="form-select" name="jenis">
                            <option value="semua" <?php echo $jenis == 'semua' ? 'selected' : ''; ?>>Semua</option>
                            <option value="masuk" <?php echo $jenis == 'masuk' ? 'selected' : ''; ?>>Masuk</option>
                            <option value="keluar" <?php echo $jenis == 'keluar' ? 'selected' : ''; ?>>Keluar</option>
                            <option value="rusak" <?php echo $jenis == 'rusak' ? 'selected' : ''; ?>>Rusak</option>
                            <option value="expired" <?php echo $jenis == 'expired' ? 'selected' : ''; ?>>Expired</option>
                            <option value="hilang" <?php echo $jenis == 'hilang' ? 'selected' : ''; ?>>Hilang</option>
                            <option value="tumpah" <?php echo $jenis == 'tumpah' ? 'selected' : ''; ?>>Tumpah</option>
                            <option value="opname" <?php echo $jenis == 'opname' ? 'selected' : ''; ?>>Stock Opname</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Tampilkan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Header Laporan -->
    <div class="text-center mb-4 print-header">
        <h3><strong>LAPORAN STOCK & INVENTORY</strong></h3>
        <h6>Per Tanggal: <?php echo date('d F Y', strtotime($tanggal_sampai)); ?></h6>
        <h6 class="text-muted">Pergerakan: <?php echo date('d F Y', strtotime($tanggal_dari)); ?> s/d <?php echo date('d F Y', strtotime($tanggal_sampai)); ?></h6>
        <hr>
    </div>

    <!-- Ringkasan Stock Saat Ini -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-clipboard-data"></i> Ringkasan Stock Saat Ini</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <td width="50%"><strong>Total Jenis Bahan</strong></td>
                        <td class="text-end" width="50%"><?php echo $ringkasan_stock['total_bahan']; ?> item</td>
                    </tr>
                    <tr>
                        <td>Stock Habis</td>
                        <td class="text-end">
                            <span class="badge bg-danger"><?php echo $ringkasan_stock['stock_habis']; ?></span> item
                        </td>
                    </tr>
                    <tr>
                        <td>Stock Menipis (≤ Minimum)</td>
                        <td class="text-end">
                            <span class="badge bg-warning text-dark"><?php echo $ringkasan_stock['stock_menipis']; ?></span> item
                        </td>
                    </tr>
                    <tr>
                        <td>Stock Aman</td>
                        <td class="text-end">
                            <span class="badge bg-success"><?php echo $ringkasan_stock['stock_aman']; ?></span> item
                        </td>
                    </tr>
                    <tr class="table-light">
                        <td><strong>Total Nilai Stock</strong></td>
                        <td class="text-end">
                            <strong class="text-primary"><?php echo formatRupiah($ringkasan_stock['total_nilai_stock']); ?></strong>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Ringkasan Pergerakan -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-arrow-left-right"></i> Ringkasan Pergerakan Stock (Periode)</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <td width="50%"><strong>Total Pergerakan</strong></td>
                        <td class="text-end" width="50%"><?php echo $ringkasan_pergerakan['total_pergerakan']; ?> transaksi</td>
                    </tr>
                    <tr>
                        <td>Nilai Stock Masuk</td>
                        <td class="text-end text-success"><?php echo formatRupiah($ringkasan_pergerakan['nilai_masuk']); ?></td>
                    </tr>
                    <tr>
                        <td>Nilai Stock Keluar (Terpakai)</td>
                        <td class="text-end text-primary"><?php echo formatRupiah($ringkasan_pergerakan['nilai_keluar']); ?></td>
                    </tr>
                    <tr>
                        <td>Nilai Kerugian (Rusak/Expired/Hilang/Tumpah)</td>
                        <td class="text-end text-danger"><?php echo formatRupiah($ringkasan_pergerakan['nilai_kerugian']); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Daftar Stock Bahan Baku -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-boxes"></i> Daftar Stock Bahan Baku Saat Ini</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">No</th>
                            <th>Kode</th>
                            <th>Nama Bahan</th>
                            <th class="text-center">Satuan</th>
                            <th class="text-center">Stock Tersedia</th>
                            <th class="text-center">Stock Minimum</th>
                            <th class="text-end">Harga/Satuan</th>
                            <th class="text-end">Nilai Stock</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($stock_bahan as $bahan): 
                            if ($bahan['status_stock'] == 'habis') {
                                $badge_class = 'bg-danger';
                                $status_text = 'HABIS';
                            } elseif ($bahan['status_stock'] == 'menipis') {
                                $badge_class = 'bg-warning text-dark';
                                $status_text = 'MENIPIS';
                            } else {
                                $badge_class = 'bg-success';
                                $status_text = 'AMAN';
                            }
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $no++; ?></td>
                            <td><small><?php echo $bahan['kode_bahan']; ?></small></td>
                            <td><strong><?php echo $bahan['nama_bahan']; ?></strong></td>
                            <td class="text-center"><?php echo $bahan['satuan']; ?></td>
                            <td class="text-center">
                                <strong><?php echo $bahan['stok_tersedia']; ?></strong>
                            </td>
                            <td class="text-center">
                                <small class="text-muted"><?php echo $bahan['stok_minimum']; ?></small>
                            </td>
                            <td class="text-end"><?php echo formatRupiah($bahan['harga_beli_per_satuan']); ?></td>
                            <td class="text-end">
                                <strong class="text-primary"><?php echo formatRupiah($bahan['nilai_stock']); ?></strong>
                            </td>
                            <td class="text-center">
                                <span class="badge <?php echo $badge_class; ?>"><?php echo $status_text; ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="7" class="text-end">TOTAL NILAI STOCK:</th>
                            <th class="text-end"><?php echo formatRupiah($ringkasan_stock['total_nilai_stock']); ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Top 10 Bahan Paling Sering Digunakan -->
    <?php if (!empty($top_bahan_keluar)): ?>
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="bi bi-graph-down"></i> Top 10 Bahan Paling Sering Digunakan</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th width="5%">No</th>
                        <th>Nama Bahan</th>
                        <th class="text-center">Frekuensi</th>
                        <th class="text-end">Total Keluar</th>
                        <th class="text-end">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    foreach ($top_bahan_keluar as $bahan): 
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $no++; ?></td>
                        <td><strong><?php echo $bahan['nama_bahan']; ?></strong></td>
                        <td class="text-center">
                            <span class="badge bg-info"><?php echo $bahan['frekuensi']; ?>x</span>
                        </td>
                        <td class="text-end"><?php echo $bahan['total_keluar']; ?> <?php echo $bahan['satuan']; ?></td>
                        <td class="text-end"><?php echo formatRupiah($bahan['total_nilai']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Kerugian Bahan -->
    <?php if (!empty($kerugian_bahan)): ?>
    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="bi bi-trash"></i> Kerugian Bahan (Rusak/Expired/Hilang/Tumpah)</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th width="5%">No</th>
                        <th>Nama Bahan</th>
                        <th class="text-center">Jenis</th>
                        <th class="text-end">Jumlah</th>
                        <th class="text-end">Nilai Kerugian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    $total_kerugian_display = 0;
                    foreach ($kerugian_bahan as $bahan): 
                        $total_kerugian_display += $bahan['total_kerugian'];
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $no++; ?></td>
                        <td><strong><?php echo $bahan['nama_bahan']; ?></strong></td>
                        <td class="text-center">
                            <span class="badge bg-secondary"><?php echo strtoupper($bahan['jenis_pergerakan']); ?></span>
                        </td>
                        <td class="text-end"><?php echo $bahan['total_jumlah']; ?> <?php echo $bahan['satuan']; ?></td>
                        <td class="text-end text-danger">
                            <strong><?php echo formatRupiah($bahan['total_kerugian']); ?></strong>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="4" class="text-end">TOTAL KERUGIAN:</th>
                        <th class="text-end text-danger"><?php echo formatRupiah($total_kerugian_display); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Riwayat Pergerakan Stock -->
    <?php if (!empty($pergerakan_stock)): ?>
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Riwayat Pergerakan Stock (100 Terakhir)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Bahan</th>
                            <th class="text-center">Jenis</th>
                            <th class="text-end">Jumlah</th>
                            <th class="text-end">Stock Sebelum</th>
                            <th class="text-end">Stock Sesudah</th>
                            <th class="text-end">Nilai</th>
                            <th>Keterangan</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pergerakan_stock as $move): 
                            $badge_class = match($move['jenis_pergerakan']) {
                                'masuk' => 'bg-success',
                                'keluar' => 'bg-primary',
                                'rusak' => 'bg-danger',
                                'expired' => 'bg-warning text-dark',
                                'hilang' => 'bg-dark',
                                'tumpah' => 'bg-info',
                                default => 'bg-secondary'
                            };
                        ?>
                        <tr>
                            <td><small><?php echo date('d/m/Y H:i', strtotime($move['created_at'])); ?></small></td>
                            <td><strong><?php echo $move['nama_bahan']; ?></strong></td>
                            <td class="text-center">
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo strtoupper($move['jenis_pergerakan']); ?>
                                </span>
                            </td>
                            <td class="text-end"><?php echo $move['jumlah']; ?> <?php echo $move['satuan']; ?></td>
                            <td class="text-end"><?php echo $move['stok_sebelum']; ?></td>
                            <td class="text-end"><strong><?php echo $move['stok_sesudah']; ?></strong></td>
                            <td class="text-end"><?php echo formatRupiah($move['total_nilai']); ?></td>
                            <td><small><?php echo $move['keterangan'] ?: '-'; ?></small></td>
                            <td><small><?php echo $move['user_name']; ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="row mb-4 no-print">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h6>Total Bahan</h6>
                    <h4><?php echo $ringkasan_stock['total_bahan']; ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h6>Stock Habis</h6>
                    <h4><?php echo $ringkasan_stock['stock_habis']; ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h6>Stock Menipis</h6>
                    <h4><?php echo $ringkasan_stock['stock_menipis']; ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h6>Nilai Stock</h6>
                    <h4><?php echo formatRupiah($ringkasan_stock['total_nilai_stock']); ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Actions -->
    <div class="card no-print">
        <div class="card-body">
            <div class="d-flex gap-2">
                <button onclick="exportToExcel()" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel"></i> Export Excel
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
    .no-print { display: none !important; }
    .card { border: 1px solid #ddd !important; box-shadow: none !important; page-break-inside: avoid; }
    .table { font-size: 10px; }
    @page { size: A4 landscape; margin: 10mm; }
    body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
}
</style>

<script>
function exportToExcel() { alert('Fitur Export Excel akan segera tersedia'); }
</script>