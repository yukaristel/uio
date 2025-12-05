<?php
/**
 * LAPORAN MENU PENJUALAN
 * Menampilkan statistik penjualan per menu dalam periode tertentu
 */

// Default filter
$tanggal_dari = isset($_GET['tanggal_dari']) ? $_GET['tanggal_dari'] : date('Y-m-01');
$tanggal_sampai = isset($_GET['tanggal_sampai']) ? $_GET['tanggal_sampai'] : date('Y-m-d');
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'total_terjual';

// Query data penjualan per menu
$where_kategori = $kategori_filter ? "AND m.kategori_id = '$kategori_filter'" : "";

$query = "
    SELECT 
        m.id,
        m.kode_menu,
        m.nama_menu,
        k.nama_kategori,
        m.harga_jual,
        m.harga_modal,
        COALESCE(SUM(dt.jumlah), 0) as total_terjual,
        COALESCE(SUM(dt.subtotal), 0) as total_pendapatan,
        COALESCE(SUM(dt.subtotal_modal), 0) as total_modal,
        COALESCE(SUM(dt.subtotal - dt.subtotal_modal), 0) as total_keuntungan,
        COUNT(DISTINCT dt.transaksi_id) as frekuensi_transaksi,
        CASE 
            WHEN SUM(dt.jumlah) > 0 THEN ROUND(SUM(dt.subtotal) / SUM(dt.jumlah), 0)
            ELSE m.harga_jual 
        END as harga_rata_rata
    FROM menu_makanan m
    LEFT JOIN kategori_menu k ON m.kategori_id = k.id
    LEFT JOIN detail_transaksi dt ON m.id = dt.menu_id
    LEFT JOIN transaksi_penjualan tp ON dt.transaksi_id = tp.id 
        AND DATE(tp.tanggal_transaksi) BETWEEN '$tanggal_dari' AND '$tanggal_sampai'
    WHERE 1=1 $where_kategori
    GROUP BY m.id, m.kode_menu, m.nama_menu, k.nama_kategori, m.harga_jual, m.harga_modal
";

// Sorting
switch($sort_by) {
    case 'total_terjual':
        $query .= " ORDER BY total_terjual DESC, m.nama_menu ASC";
        break;
    case 'total_pendapatan':
        $query .= " ORDER BY total_pendapatan DESC, m.nama_menu ASC";
        break;
    case 'total_keuntungan':
        $query .= " ORDER BY total_keuntungan DESC, m.nama_menu ASC";
        break;
    case 'nama_menu':
        $query .= " ORDER BY m.nama_menu ASC";
        break;
    default:
        $query .= " ORDER BY total_terjual DESC, m.nama_menu ASC";
}

$data_menu = fetchAll($query);

// Hitung total keseluruhan
$total_semua_terjual = 0;
$total_semua_pendapatan = 0;
$total_semua_modal = 0;
$total_semua_keuntungan = 0;
$total_item_terjual = 0;

foreach ($data_menu as $menu) {
    if ($menu['total_terjual'] > 0) {
        $total_semua_terjual += $menu['total_terjual'];
        $total_semua_pendapatan += $menu['total_pendapatan'];
        $total_semua_modal += $menu['total_modal'];
        $total_semua_keuntungan += $menu['total_keuntungan'];
        $total_item_terjual++;
    }
}

$margin_rata_rata = $total_semua_pendapatan > 0 
    ? ($total_semua_keuntungan / $total_semua_pendapatan) * 100 
    : 0;

// Get list kategori untuk filter
$list_kategori = fetchAll("SELECT id, nama_kategori FROM kategori_menu ORDER BY nama_kategori");

// Top 5 Menu Terlaris
$top_menu = array_slice(array_filter($data_menu, function($m) { return $m['total_terjual'] > 0; }), 0, 5);

// Menu dengan keuntungan tertinggi
$top_profit = $data_menu;
usort($top_profit, function($a, $b) {
    return $b['total_keuntungan'] - $a['total_keuntungan'];
});
$top_profit = array_slice(array_filter($top_profit, function($m) { return $m['total_terjual'] > 0; }), 0, 5);

// Menu yang tidak terjual
$menu_tidak_terjual = array_filter($data_menu, function($m) { return $m['total_terjual'] == 0; });
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-bar-chart-line"></i> Laporan Menu Penjualan</h2>
        <button onclick="window.print()" class="btn btn-primary no-print">
            <i class="bi bi-printer"></i> Cetak Laporan
        </button>
    </div>

    <!-- Filter -->
    <div class="card mb-4 no-print">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-funnel"></i> Filter Laporan</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="laporan_menu">
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
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="kategori">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($list_kategori as $kat): ?>
                            <option value="<?php echo $kat['id']; ?>" <?php echo $kategori_filter == $kat['id'] ? 'selected' : ''; ?>>
                                <?php echo $kat['nama_kategori']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Urutkan Berdasarkan</label>
                        <select class="form-select" name="sort_by">
                            <option value="total_terjual" <?php echo $sort_by == 'total_terjual' ? 'selected' : ''; ?>>Jumlah Terjual</option>
                            <option value="total_pendapatan" <?php echo $sort_by == 'total_pendapatan' ? 'selected' : ''; ?>>Total Pendapatan</option>
                            <option value="total_keuntungan" <?php echo $sort_by == 'total_keuntungan' ? 'selected' : ''; ?>>Total Keuntungan</option>
                            <option value="nama_menu" <?php echo $sort_by == 'nama_menu' ? 'selected' : ''; ?>>Nama Menu</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Tampilkan Laporan
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setDateFilter('today')">
                            Hari Ini
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setDateFilter('week')">
                            7 Hari
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setDateFilter('month')">
                            Bulan Ini
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Header Laporan -->
    <div class="text-center mb-4 print-header">
        <h3><strong>LAPORAN PENJUALAN MENU</strong></h3>
        <h5>Periode: <?php echo date('d F Y', strtotime($tanggal_dari)); ?> s/d <?php echo date('d F Y', strtotime($tanggal_sampai)); ?></h5>
        <?php if ($kategori_filter): ?>
        <h6 class="text-muted">Kategori: <?php 
            $kat_selected = array_filter($list_kategori, function($k) use ($kategori_filter) { 
                return $k['id'] == $kategori_filter; 
            });
            echo reset($kat_selected)['nama_kategori'];
        ?></h6>
        <?php endif; ?>
        <hr>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h6>Total Item Terjual</h6>
                    <h4><?php echo $total_item_terjual; ?> Menu</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h6>Total Porsi Terjual</h6>
                    <h4><?php echo number_format($total_semua_terjual, 0); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h6>Total Pendapatan</h6>
                    <h4><?php echo formatRupiah($total_semua_pendapatan); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h6>Total Keuntungan</h6>
                    <h4><?php echo formatRupiah($total_semua_keuntungan); ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Top 5 Menu -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-trophy"></i> Top 5 Menu Terlaris</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($top_menu)): ?>
                        <div class="alert alert-info mb-0">Belum ada penjualan</div>
                    <?php else: ?>
                        <ol class="list-group list-group-numbered">
                            <?php foreach ($top_menu as $menu): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold"><?php echo $menu['nama_menu']; ?></div>
                                    <small class="text-muted"><?php echo $menu['nama_kategori']; ?></small>
                                </div>
                                <span class="badge bg-success rounded-pill">
                                    <?php echo number_format($menu['total_terjual'], 0); ?> porsi
                                </span>
                            </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0"><i class="bi bi-cash-coin"></i> Top 5 Menu Teruntung</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($top_profit)): ?>
                        <div class="alert alert-info mb-0">Belum ada penjualan</div>
                    <?php else: ?>
                        <ol class="list-group list-group-numbered">
                            <?php foreach ($top_profit as $menu): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold"><?php echo $menu['nama_menu']; ?></div>
                                    <small class="text-muted"><?php echo $menu['nama_kategori']; ?></small>
                                </div>
                                <span class="badge bg-warning rounded-pill">
                                    <?php echo formatRupiah($menu['total_keuntungan']); ?>
                                </span>
                            </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Penjualan Menu -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Detail Penjualan per Menu</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th width="3%">No</th>
                            <th width="10%">Kode</th>
                            <th width="20%">Nama Menu</th>
                            <th width="12%">Kategori</th>
                            <th width="10%" class="text-end">Harga</th>
                            <th width="8%" class="text-center">Terjual</th>
                            <th width="8%" class="text-center">Frekuensi</th>
                            <th width="12%" class="text-end">Pendapatan</th>
                            <th width="12%" class="text-end">Keuntungan</th>
                            <th width="5%" class="text-center">Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        $has_data = false;
                        foreach ($data_menu as $menu): 
                            if ($menu['total_terjual'] == 0) continue;
                            $has_data = true;
                            $margin = $menu['total_pendapatan'] > 0 
                                ? ($menu['total_keuntungan'] / $menu['total_pendapatan']) * 100 
                                : 0;
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><small><?php echo $menu['kode_menu']; ?></small></td>
                            <td><strong><?php echo $menu['nama_menu']; ?></strong></td>
                            <td><span class="badge bg-secondary"><?php echo $menu['nama_kategori']; ?></span></td>
                            <td class="text-end"><?php echo formatRupiah($menu['harga_jual']); ?></td>
                            <td class="text-center">
                                <span class="badge bg-info"><?php echo number_format($menu['total_terjual'], 0); ?></span>
                            </td>
                            <td class="text-center">
                                <small><?php echo $menu['frekuensi_transaksi']; ?>x</small>
                            </td>
                            <td class="text-end">
                                <strong class="text-success"><?php echo formatRupiah($menu['total_pendapatan']); ?></strong>
                            </td>
                            <td class="text-end">
                                <strong class="text-warning"><?php echo formatRupiah($menu['total_keuntungan']); ?></strong>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-<?php echo $margin >= 40 ? 'success' : ($margin >= 25 ? 'warning' : 'danger'); ?>">
                                    <?php echo number_format($margin, 1); ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if ($has_data): ?>
                        <tr class="table-primary">
                            <td colspan="5" class="text-end"><strong>TOTAL</strong></td>
                            <td class="text-center">
                                <strong><?php echo number_format($total_semua_terjual, 0); ?></strong>
                            </td>
                            <td></td>
                            <td class="text-end">
                                <strong><?php echo formatRupiah($total_semua_pendapatan); ?></strong>
                            </td>
                            <td class="text-end">
                                <strong><?php echo formatRupiah($total_semua_keuntungan); ?></strong>
                            </td>
                            <td class="text-center">
                                <strong><?php echo number_format($margin_rata_rata, 1); ?>%</strong>
                            </td>
                        </tr>
                        <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center">
                                <div class="alert alert-info mb-0">
                                    <i class="bi bi-info-circle"></i> Tidak ada penjualan menu pada periode yang dipilih
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Menu Tidak Terjual -->
    <?php if (!empty($menu_tidak_terjual)): ?>
    <div class="card mb-4 no-print">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Menu Tidak Terjual (<?php echo count($menu_tidak_terjual); ?>)</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($menu_tidak_terjual as $menu): ?>
                <div class="col-md-3 mb-2">
                    <div class="card">
                        <div class="card-body p-2">
                            <small>
                                <strong><?php echo $menu['nama_menu']; ?></strong><br>
                                <span class="text-muted"><?php echo $menu['nama_kategori']; ?></span><br>
                                <span class="text-primary"><?php echo formatRupiah($menu['harga_jual']); ?></span>
                            </small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <small class="text-muted">
                <i class="bi bi-info-circle"></i> Menu-menu ini tidak terjual sama sekali pada periode yang dipilih. 
                Pertimbangkan untuk evaluasi harga, promosi, atau ketersediaan menu.
            </small>
        </div>
    </div>
    <?php endif; ?>

    <!-- Analisis Performa -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-graph-up"></i> Analisis Performa</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Total Menu Terdaftar</strong></td>
                            <td class="text-end"><?php echo count($data_menu); ?> menu</td>
                        </tr>
                        <tr>
                            <td><strong>Menu yang Terjual</strong></td>
                            <td class="text-end"><?php echo $total_item_terjual; ?> menu</td>
                        </tr>
                        <tr>
                            <td><strong>Menu Tidak Terjual</strong></td>
                            <td class="text-end"><?php echo count($menu_tidak_terjual); ?> menu</td>
                        </tr>
                        <tr>
                            <td><strong>Persentase Menu Aktif</strong></td>
                            <td class="text-end">
                                <span class="badge bg-<?php echo ($total_item_terjual / count($data_menu) * 100) >= 70 ? 'success' : 'warning'; ?>">
                                    <?php echo number_format($total_item_terjual / count($data_menu) * 100, 1); ?>%
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Rata-rata Pendapatan per Menu</strong></td>
                            <td class="text-end"><?php echo formatRupiah($total_item_terjual > 0 ? $total_semua_pendapatan / $total_item_terjual : 0); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Rata-rata Keuntungan per Menu</strong></td>
                            <td class="text-end"><?php echo formatRupiah($total_item_terjual > 0 ? $total_semua_keuntungan / $total_item_terjual : 0); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Rata-rata Porsi per Menu</strong></td>
                            <td class="text-end"><?php echo number_format($total_item_terjual > 0 ? $total_semua_terjual / $total_item_terjual : 0, 1); ?> porsi</td>
                        </tr>
                        <tr>
                            <td><strong>Margin Keuntungan Rata-rata</strong></td>
                            <td class="text-end">
                                <span class="badge bg-<?php echo $margin_rata_rata >= 40 ? 'success' : ($margin_rata_rata >= 25 ? 'warning' : 'danger'); ?>">
                                    <?php echo number_format($margin_rata_rata, 2); ?>%
                                </span>
                            </td>
                        </tr>
                    </table>
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
        font-size: 10px;
    }
    
    .print-header {
        margin-bottom: 20px;
    }
    
    @page {
        size: A4 landscape;
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

.badge {
    font-size: 0.85em;
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