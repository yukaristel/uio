<?php
/**
 * LAPORAN HARIAN - FORMAT SEDERHANA
 * Laporan transaksi dan kas harian
 */

// Default hari ini
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// ============================================
// SALDO AWAL HARI
// ============================================
$tanggal_kemarin = date('Y-m-d', strtotime($tanggal . ' -1 day'));
$saldo_awal_data = fetchOne("SELECT saldo_akhir FROM saldo_kas WHERE tanggal <= '$tanggal_kemarin' ORDER BY tanggal DESC LIMIT 1");
$saldo_awal = $saldo_awal_data ? $saldo_awal_data['saldo_akhir'] : 0;

// ============================================
// PEMASUKAN HARI INI
// ============================================

// 1. Pendapatan Penjualan
$pendapatan_penjualan = fetchOne("
    SELECT 
        COALESCE(SUM(total_harga), 0) as total,
        COUNT(*) as jumlah_transaksi,
        COALESCE(AVG(total_harga), 0) as rata_rata
    FROM transaksi_penjualan
    WHERE DATE(tanggal_transaksi) = ?
", [$tanggal]);

// 2. Pemasukan Lainnya
$pemasukan_lainnya = fetchOne("
    SELECT COALESCE(SUM(nominal), 0) as total
    FROM kas_umum
    WHERE jenis_transaksi = 'masuk'
    AND kategori IN ('investasi', 'lainnya')
    AND DATE(tanggal_transaksi) = ?
", [$tanggal])['total'];

$total_pemasukan = $pendapatan_penjualan['total'] + $pemasukan_lainnya;

// ============================================
// PENGELUARAN HARI INI
// ============================================

// 1. Pembelian Bahan
$pembelian_bahan = fetchOne("
    SELECT COALESCE(SUM(total_harga), 0) as total
    FROM pembelian_bahan
    WHERE DATE(tanggal_beli) = ?
", [$tanggal])['total'];

// 2. Gaji
$gaji = fetchOne("
    SELECT COALESCE(SUM(nominal), 0) as total
    FROM kas_umum
    WHERE jenis_transaksi = 'keluar'
    AND kategori = 'gaji'
    AND DATE(tanggal_transaksi) = ?
", [$tanggal])['total'];

// 3. Operasional
$operasional = fetchOne("
    SELECT COALESCE(SUM(nominal), 0) as total
    FROM kas_umum
    WHERE jenis_transaksi = 'keluar'
    AND kategori = 'operasional'
    AND DATE(tanggal_transaksi) = ?
", [$tanggal])['total'];

// 4. Pengeluaran Lainnya
$pengeluaran_lainnya = fetchOne("
    SELECT COALESCE(SUM(nominal), 0) as total
    FROM kas_umum
    WHERE jenis_transaksi = 'keluar'
    AND kategori = 'lainnya'
    AND DATE(tanggal_transaksi) = ?
", [$tanggal])['total'];

$total_pengeluaran = $pembelian_bahan + $gaji + $operasional + $pengeluaran_lainnya;

// ============================================
// PERHITUNGAN
// ============================================
$surplus_defisit = $total_pemasukan - $total_pengeluaran;
$saldo_akhir = $saldo_awal + $surplus_defisit;

// ============================================
// DETAIL PENJUALAN
// ============================================
$detail_penjualan = fetchOne("
    SELECT 
        COALESCE(SUM(total_modal), 0) as total_modal,
        COALESCE(SUM(total_keuntungan), 0) as keuntungan_kotor
    FROM transaksi_penjualan
    WHERE DATE(tanggal_transaksi) = ?
", [$tanggal]);

$margin_kotor = $pendapatan_penjualan['total'] > 0 
    ? ($detail_penjualan['keuntungan_kotor'] / $pendapatan_penjualan['total']) * 100 
    : 0;

// ============================================
// MENU TERJUAL HARI INI
// ============================================
$menu_terjual = fetchAll("
    SELECT 
        m.nama_menu,
        k.nama_kategori,
        SUM(dt.jumlah) as total_terjual,
        SUM(dt.subtotal) as total_pendapatan,
        SUM(dt.subtotal - dt.subtotal_modal) as keuntungan
    FROM detail_transaksi dt
    JOIN transaksi_penjualan tp ON dt.transaksi_id = tp.id
    JOIN menu_makanan m ON dt.menu_id = m.id
    JOIN kategori_menu k ON m.kategori_id = k.id
    WHERE DATE(tp.tanggal_transaksi) = ?
    GROUP BY dt.menu_id
    ORDER BY total_terjual DESC
    LIMIT 10
", [$tanggal]);

// ============================================
// TRANSAKSI PER METODE PEMBAYARAN
// ============================================
$metode_pembayaran = fetchAll("
    SELECT 
        metode_pembayaran,
        COUNT(*) as jumlah_transaksi,
        SUM(total_harga) as total
    FROM transaksi_penjualan
    WHERE DATE(tanggal_transaksi) = ?
    GROUP BY metode_pembayaran
", [$tanggal]);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-calendar-day"></i> Laporan Harian</h2>
        <button onclick="window.print()" class="btn btn-primary no-print">
            <i class="bi bi-printer"></i> Cetak Laporan
        </button>
    </div>

    <!-- Filter Tanggal -->
    <div class="card mb-4 no-print">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-calendar"></i> Pilih Tanggal</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="laporan_harian">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tanggal</label>
                        <input type="date" class="form-control" name="tanggal" 
                               value="<?php echo $tanggal; ?>" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Tampilkan
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="setTanggal('today')">
                                Hari Ini
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="setTanggal('yesterday')">
                                Kemarin
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Header Laporan -->
    <div class="text-center mb-4 print-header">
        <h3><strong>LAPORAN HARIAN</strong></h3>
        <h5>Tanggal: <?php echo date('d F Y', strtotime($tanggal)); ?></h5>
        <hr>
    </div>

    <!-- Laporan Kas Harian -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-cash-stack"></i> Laporan Kas Harian</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <tbody>
                    <!-- Saldo Awal -->
                    <tr class="table-light">
                        <td colspan="2"><strong>Saldo Kas Awal Hari</strong></td>
                        <td class="text-end" width="200"><strong><?php echo formatRupiah($saldo_awal); ?></strong></td>
                    </tr>

                    <!-- PEMASUKAN -->
                    <tr class="table-success">
                        <td colspan="3"><strong>PEMASUKAN HARI INI</strong></td>
                    </tr>
                    <tr>
                        <td width="50"></td>
                        <td>Pendapatan dari Penjualan</td>
                        <td class="text-end"><?php echo formatRupiah($pendapatan_penjualan['total']); ?></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>Pemasukan Lainnya</td>
                        <td class="text-end"><?php echo formatRupiah($pemasukan_lainnya); ?></td>
                    </tr>
                    <tr class="table-success">
                        <td colspan="2" class="text-end"><strong>Total Pemasukan</strong></td>
                        <td class="text-end"><strong><?php echo formatRupiah($total_pemasukan); ?></strong></td>
                    </tr>

                    <!-- PENGELUARAN -->
                    <tr class="table-danger">
                        <td colspan="3"><strong>PENGELUARAN HARI INI</strong></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>Pembelian Bahan Baku</td>
                        <td class="text-end"><?php echo formatRupiah($pembelian_bahan); ?></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>Gaji Karyawan</td>
                        <td class="text-end"><?php echo formatRupiah($gaji); ?></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>Biaya Operasional</td>
                        <td class="text-end"><?php echo formatRupiah($operasional); ?></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>Pengeluaran Lainnya</td>
                        <td class="text-end"><?php echo formatRupiah($pengeluaran_lainnya); ?></td>
                    </tr>
                    <tr class="table-danger">
                        <td colspan="2" class="text-end"><strong>Total Pengeluaran</strong></td>
                        <td class="text-end"><strong><?php echo formatRupiah($total_pengeluaran); ?></strong></td>
                    </tr>

                    <!-- Surplus/Defisit -->
                    <tr class="table-warning">
                        <td colspan="2" class="text-end"><strong>Surplus / (Defisit) Hari Ini</strong></td>
                        <td class="text-end">
                            <strong class="<?php echo $surplus_defisit >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo formatRupiah($surplus_defisit); ?>
                            </strong>
                        </td>
                    </tr>

                    <!-- Saldo Akhir -->
                    <tr class="table-primary">
                        <td colspan="2"><strong>Saldo Kas Akhir Hari</strong></td>
                        <td class="text-end">
                            <strong style="font-size: 1.1em;"><?php echo formatRupiah($saldo_akhir); ?></strong>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Ringkasan Penjualan -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-receipt"></i> Ringkasan Penjualan Hari Ini</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <td width="50%"><strong>Total Penjualan</strong></td>
                        <td class="text-end" width="50%">
                            <strong class="text-primary"><?php echo formatRupiah($pendapatan_penjualan['total']); ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td>Jumlah Transaksi</td>
                        <td class="text-end"><?php echo $pendapatan_penjualan['jumlah_transaksi']; ?> transaksi</td>
                    </tr>
                    <tr>
                        <td>Rata-rata per Transaksi</td>
                        <td class="text-end"><?php echo formatRupiah($pendapatan_penjualan['rata_rata']); ?></td>
                    </tr>
                    <tr class="table-light">
                        <td>Total Modal</td>
                        <td class="text-end"><?php echo formatRupiah($detail_penjualan['total_modal']); ?></td>
                    </tr>
                    <tr class="table-success">
                        <td><strong>Keuntungan Kotor</strong></td>
                        <td class="text-end">
                            <strong class="text-success"><?php echo formatRupiah($detail_penjualan['keuntungan_kotor']); ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td>Margin Keuntungan</td>
                        <td class="text-end">
                            <span class="badge bg-success"><?php echo number_format($margin_kotor, 2); ?>%</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Metode Pembayaran -->
    <?php if (!empty($metode_pembayaran)): ?>
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="bi bi-wallet2"></i> Metode Pembayaran</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Metode</th>
                        <th class="text-center">Jumlah Transaksi</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($metode_pembayaran as $metode): ?>
                    <tr>
                        <td><strong><?php echo strtoupper($metode['metode_pembayaran']); ?></strong></td>
                        <td class="text-center"><?php echo $metode['jumlah_transaksi']; ?></td>
                        <td class="text-end"><?php echo formatRupiah($metode['total']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th>TOTAL</th>
                        <th class="text-center"><?php echo $pendapatan_penjualan['jumlah_transaksi']; ?></th>
                        <th class="text-end"><?php echo formatRupiah($pendapatan_penjualan['total']); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Top 10 Menu Terjual -->
    <?php if (!empty($menu_terjual)): ?>
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-star"></i> Top 10 Menu Terjual Hari Ini</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th width="5%">No</th>
                        <th>Menu</th>
                        <th>Kategori</th>
                        <th class="text-center">Qty Terjual</th>
                        <th class="text-end">Pendapatan</th>
                        <th class="text-end">Keuntungan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    foreach ($menu_terjual as $menu): 
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $no++; ?></td>
                        <td><strong><?php echo $menu['nama_menu']; ?></strong></td>
                        <td><?php echo $menu['nama_kategori']; ?></td>
                        <td class="text-center">
                            <span class="badge bg-success"><?php echo $menu['total_terjual']; ?></span>
                        </td>
                        <td class="text-end"><?php echo formatRupiah($menu['total_pendapatan']); ?></td>
                        <td class="text-end text-success"><?php echo formatRupiah($menu['keuntungan']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="row mb-4 no-print">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h6>Total Pemasukan</h6>
                    <h4><?php echo formatRupiah($total_pemasukan); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h6>Total Pengeluaran</h6>
                    <h4><?php echo formatRupiah($total_pengeluaran); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h6>Jumlah Transaksi</h6>
                    <h4><?php echo $pendapatan_penjualan['jumlah_transaksi']; ?></h4>
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
    .table { font-size: 11px; }
    @page { size: A4; margin: 15mm; }
    body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
}
</style>

<script>
function exportToExcel() { alert('Fitur Export Excel akan segera tersedia'); }
function setTanggal(type) {
    const today = new Date();
    let targetDate = type === 'today' ? today : new Date(today.setDate(today.getDate() - 1));
    document.querySelector('input[name="tanggal"]').value = targetDate.toISOString().split('T')[0];
    document.querySelector('form').submit();
}
</script>