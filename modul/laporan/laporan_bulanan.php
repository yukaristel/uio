<?php
/**
 * LAPORAN BULANAN - FORMAT ARUS KAS
 * Laporan bulanan dengan format sederhana seperti laporan arus kas
 */

// Default bulan ini
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
$tahun = substr($bulan, 0, 4);
$bulan_angka = substr($bulan, 5, 2);

// Rentang tanggal
$tanggal_awal = $bulan . '-01';
$tanggal_akhir = date('Y-m-t', strtotime($tanggal_awal));

// Nama bulan
$nama_bulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
    '04' => 'April', '05' => 'Mei', '06' => 'Juni',
    '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
    '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$bulan_formatted = $nama_bulan[$bulan_angka] . ' ' . $tahun;

// ============================================
// SALDO AWAL BULAN (dari saldo akhir bulan sebelumnya)
// ============================================
$bulan_sebelumnya = date('Y-m-t', strtotime($tanggal_awal . ' -1 day'));
$saldo_awal_data = fetchOne("SELECT saldo_akhir FROM saldo_kas WHERE tanggal <= '$bulan_sebelumnya' ORDER BY tanggal DESC LIMIT 1");
$saldo_awal = $saldo_awal_data ? $saldo_awal_data['saldo_akhir'] : 0;

// ============================================
// PEMASUKAN BULANAN
// ============================================

// 1. Pendapatan dari Penjualan
$pendapatan_penjualan = fetchOne("
    SELECT COALESCE(SUM(total_harga), 0) as total
    FROM transaksi_penjualan
    WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?
", [$tanggal_awal, $tanggal_akhir])['total'];

// 2. Pemasukan Lainnya (dari kas_umum - investasi dll)
$pemasukan_lainnya = fetchOne("
    SELECT COALESCE(SUM(nominal), 0) as total
    FROM kas_umum
    WHERE jenis_transaksi = 'masuk'
    AND kategori IN ('investasi', 'lainnya')
    AND DATE(tanggal_transaksi) BETWEEN ? AND ?
", [$tanggal_awal, $tanggal_akhir])['total'];

$total_pemasukan = $pendapatan_penjualan + $pemasukan_lainnya;

// ============================================
// PENGELUARAN BULANAN
// ============================================

// 1. Pembelian Bahan Baku
$pembelian_bahan = fetchOne("
    SELECT COALESCE(SUM(total_harga), 0) as total
    FROM pembelian_bahan
    WHERE DATE(tanggal_beli) BETWEEN ? AND ?
", [$tanggal_awal, $tanggal_akhir])['total'];

// 2. Biaya Operasional (dari kas_umum)
$biaya_operasional = fetchOne("
    SELECT COALESCE(SUM(nominal), 0) as total
    FROM kas_umum
    WHERE jenis_transaksi = 'keluar'
    AND kategori = 'operasional'
    AND DATE(tanggal_transaksi) BETWEEN ? AND ?
", [$tanggal_awal, $tanggal_akhir])['total'];

// 3. Gaji Karyawan (dari kas_umum)
$gaji_karyawan = fetchOne("
    SELECT COALESCE(SUM(nominal), 0) as total
    FROM kas_umum
    WHERE jenis_transaksi = 'keluar'
    AND kategori = 'gaji'
    AND DATE(tanggal_transaksi) BETWEEN ? AND ?
", [$tanggal_awal, $tanggal_akhir])['total'];

// 4. Kerugian Barang (rusak, expired, hilang, tumpah)
$kerugian_barang = fetchOne("
    SELECT COALESCE(SUM(total_nilai), 0) as total
    FROM stock_movement
    WHERE jenis_pergerakan IN ('rusak', 'expired', 'hilang', 'tumpah')
    AND DATE(created_at) BETWEEN ? AND ?
", [$tanggal_awal, $tanggal_akhir])['total'];

// 5. Selisih Stock Opname (yang minus/kurang)
$selisih_opname = fetchOne("
    SELECT COALESCE(SUM(ABS(nilai_selisih)), 0) as total
    FROM stock_opname
    WHERE selisih < 0
    AND status = 'approved'
    AND DATE(tanggal_opname) BETWEEN ? AND ?
", [$tanggal_awal, $tanggal_akhir])['total'];

// 6. Pengeluaran Lainnya
$pengeluaran_lainnya = fetchOne("
    SELECT COALESCE(SUM(nominal), 0) as total
    FROM kas_umum
    WHERE jenis_transaksi = 'keluar'
    AND kategori = 'lainnya'
    AND DATE(tanggal_transaksi) BETWEEN ? AND ?
", [$tanggal_awal, $tanggal_akhir])['total'];

$total_pengeluaran = $pembelian_bahan + $biaya_operasional + $gaji_karyawan + 
                     $kerugian_barang + $selisih_opname + $pengeluaran_lainnya;

// ============================================
// PERHITUNGAN AKHIR
// ============================================
$surplus_defisit = $total_pemasukan - $total_pengeluaran;
$saldo_akhir = $saldo_awal + $surplus_defisit;

// Saldo akhir sebenarnya (dari tabel saldo_kas)
$saldo_akhir_real = fetchOne("
    SELECT saldo_akhir FROM saldo_kas 
    WHERE tanggal <= '$tanggal_akhir' 
    ORDER BY tanggal DESC LIMIT 1
")['saldo_akhir'] ?? $saldo_akhir;

// ============================================
// DATA STATISTIK TAMBAHAN
// ============================================
$statistik = fetchOne("
    SELECT 
        COUNT(*) as total_transaksi,
        COALESCE(AVG(total_harga), 0) as rata_rata_transaksi,
        COALESCE(SUM(total_modal), 0) as total_modal,
        COALESCE(SUM(total_keuntungan), 0) as keuntungan_kotor
    FROM transaksi_penjualan
    WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?
", [$tanggal_awal, $tanggal_akhir]);

$margin_kotor = $pendapatan_penjualan > 0 
    ? ($statistik['keuntungan_kotor'] / $pendapatan_penjualan) * 100 
    : 0;

// Total kerugian
$total_kerugian = $kerugian_barang + $selisih_opname;
$keuntungan_bersih = $statistik['keuntungan_kotor'] - $total_kerugian;

$margin_bersih = $pendapatan_penjualan > 0 
    ? ($keuntungan_bersih / $pendapatan_penjualan) * 100 
    : 0;
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-calendar-month"></i> Laporan Bulanan</h2>
        <button onclick="window.print()" class="btn btn-primary no-print">
            <i class="bi bi-printer"></i> Cetak Laporan
        </button>
    </div>

    <!-- Filter Bulan -->
    <div class="card mb-4 no-print">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-calendar-range"></i> Pilih Periode</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="laporan_bulanan">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Bulan</label>
                        <input type="month" class="form-control" name="bulan" 
                               value="<?php echo $bulan; ?>" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Tampilkan Laporan
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="setBulan('current')">
                                Bulan Ini
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="setBulan('last')">
                                Bulan Lalu
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Header Laporan -->
    <div class="text-center mb-4 print-header">
        <h3><strong>LAPORAN BULANAN</strong></h3>
        <h5>Bulan: <?php echo $bulan_formatted; ?></h5>
        <h6 class="text-muted">Periode: <?php echo date('d F Y', strtotime($tanggal_awal)); ?> s/d <?php echo date('d F Y', strtotime($tanggal_akhir)); ?></h6>
        <hr>
    </div>

    <!-- Laporan Format Arus Kas -->
    <div class="card mb-4">
        <div class="card-body">
            <table class="table table-bordered">
                <tbody>
                    <!-- Saldo Awal -->
                    <tr class="table-light">
                        <td colspan="2"><strong>Saldo Kas Awal Bulan (<?php echo date('d/m/Y', strtotime($tanggal_awal)); ?>)</strong></td>
                        <td class="text-end" width="200"><strong><?php echo formatRupiah($saldo_awal); ?></strong></td>
                    </tr>

                    <!-- PEMASUKAN -->
                    <tr class="table-success">
                        <td colspan="3"><strong>PEMASUKAN BULAN INI</strong></td>
                    </tr>
                    <tr>
                        <td width="50"></td>
                        <td>Pendapatan dari Penjualan</td>
                        <td class="text-end"><?php echo formatRupiah($pendapatan_penjualan); ?></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>Pemasukan Lainnya (Investasi, dll)</td>
                        <td class="text-end"><?php echo formatRupiah($pemasukan_lainnya); ?></td>
                    </tr>
                    <tr class="table-success">
                        <td colspan="2" class="text-end"><strong>Total Pemasukan</strong></td>
                        <td class="text-end"><strong><?php echo formatRupiah($total_pemasukan); ?></strong></td>
                    </tr>

                    <!-- PENGELUARAN -->
                    <tr class="table-danger">
                        <td colspan="3"><strong>PENGELUARAN BULAN INI</strong></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>Pembelian Bahan Baku</td>
                        <td class="text-end"><?php echo formatRupiah($pembelian_bahan); ?></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>Gaji Karyawan</td>
                        <td class="text-end"><?php echo formatRupiah($gaji_karyawan); ?></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>Biaya Operasional</td>
                        <td class="text-end"><?php echo formatRupiah($biaya_operasional); ?></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>Kerugian Barang (Rusak, Expired, Hilang)</td>
                        <td class="text-end"><?php echo formatRupiah($kerugian_barang); ?></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>Selisih Stock Opname</td>
                        <td class="text-end"><?php echo formatRupiah($selisih_opname); ?></td>
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
                        <td colspan="2" class="text-end"><strong>Surplus / (Defisit) Bulan Ini</strong></td>
                        <td class="text-end">
                            <strong class="<?php echo $surplus_defisit >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo formatRupiah($surplus_defisit); ?>
                            </strong>
                        </td>
                    </tr>

                    <!-- Saldo Akhir -->
                    <tr class="table-primary">
                        <td colspan="2"><strong>Saldo Kas Akhir Bulan (<?php echo date('d/m/Y', strtotime($tanggal_akhir)); ?>)</strong></td>
                        <td class="text-end">
                            <strong style="font-size: 1.1em;"><?php echo formatRupiah($saldo_akhir_real); ?></strong>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Ringkasan Penjualan -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-receipt"></i> Ringkasan Penjualan Bulan Ini</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <td width="50%"><strong>Total Penjualan (Omzet)</strong></td>
                        <td class="text-end" width="50%">
                            <strong class="text-primary"><?php echo formatRupiah($pendapatan_penjualan); ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td>Jumlah Transaksi</td>
                        <td class="text-end"><?php echo $statistik['total_transaksi']; ?> transaksi</td>
                    </tr>
                    <tr>
                        <td>Rata-rata per Transaksi</td>
                        <td class="text-end"><?php echo formatRupiah($statistik['rata_rata_transaksi']); ?></td>
                    </tr>
                    <tr class="table-light">
                        <td>Total Modal Penjualan</td>
                        <td class="text-end"><?php echo formatRupiah($statistik['total_modal']); ?></td>
                    </tr>
                    <tr class="table-success">
                        <td><strong>Keuntungan Kotor (Gross Profit)</strong></td>
                        <td class="text-end">
                            <strong class="text-success"><?php echo formatRupiah($statistik['keuntungan_kotor']); ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td>Margin Keuntungan Kotor</td>
                        <td class="text-end">
                            <span class="badge bg-success"><?php echo number_format($margin_kotor, 2); ?>%</span>
                        </td>
                    </tr>
                    <tr class="table-light">
                        <td>Dikurangi: Total Kerugian</td>
                        <td class="text-end text-danger">(<?php echo formatRupiah($total_kerugian); ?>)</td>
                    </tr>
                    <tr class="table-info">
                        <td><strong>Keuntungan Bersih (Net Profit)</strong></td>
                        <td class="text-end">
                            <strong class="<?php echo $keuntungan_bersih >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo formatRupiah($keuntungan_bersih); ?>
                            </strong>
                        </td>
                    </tr>
                    <tr>
                        <td>Margin Keuntungan Bersih</td>
                        <td class="text-end">
                            <span class="badge bg-<?php echo $keuntungan_bersih >= 0 ? 'info' : 'danger'; ?>">
                                <?php echo number_format($margin_bersih, 2); ?>%
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

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
            <div class="card bg-<?php echo $surplus_defisit >= 0 ? 'info' : 'warning'; ?> text-white">
                <div class="card-body text-center">
                    <h6><?php echo $surplus_defisit >= 0 ? 'Surplus' : 'Defisit'; ?></h6>
                    <h4><?php echo formatRupiah(abs($surplus_defisit)); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h6>Keuntungan Bersih</h6>
                    <h4><?php echo formatRupiah($keuntungan_bersih); ?></h4>
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

function setBulan(type) {
    const today = new Date();
    let targetDate;
    
    if (type === 'current') {
        targetDate = today;
    } else if (type === 'last') {
        targetDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
    }
    
    const year = targetDate.getFullYear();
    const month = String(targetDate.getMonth() + 1).padStart(2, '0');
    const bulanValue = `${year}-${month}`;
    
    document.querySelector('input[name="bulan"]').value = bulanValue;
    document.querySelector('form').submit();
}
</script>