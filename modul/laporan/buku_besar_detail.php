<?php
/**
 * DETAIL BUKU BESAR PER AKUN
 * File terpisah untuk menampilkan detail 1 akun
 */
 error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<!-- File loaded successfully -->";
// Jika tidak ada koneksi database, include dari root
if (!function_exists('fetchOne')) {
    require_once '../../config/database.php';
}

// Ambil parameter dari GET
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$akun_kode = isset($_GET['akun']) ? $_GET['akun'] : '';

if (empty($akun_kode)) {
    echo '<div class="alert alert-warning">Kode akun tidak valid!</div>';
    exit;
}

// Hitung tanggal awal dan akhir bulan
$tgl_awal = "$tahun-$bulan-01";
$tgl_akhir = "$tahun-$bulan-" . date('t', strtotime("$tahun-$bulan-01"));

$bulan_nama = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
    '04' => 'April', '05' => 'Mei', '06' => 'Juni',
    '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
    '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// Get info akun
$sql_akun = "
    SELECT kode_akun, nama_akun, jenis_mutasi
    FROM chart_of_accounts
    WHERE kode_akun = ?
";
$akun = fetchOne($sql_akun, [$akun_kode]);

if (!$akun) {
    echo '<div class="alert alert-danger">Akun tidak ditemukan!</div>';
    exit;
}

/**
 * Get saldo awal (sebelum periode)
 */
$tgl_sebelum = date('Y-m-d', strtotime("$tgl_awal -1 day"));

$sql_saldo_awal = "
    SELECT 
        COALESCE(SUM(CASE WHEN rekening_debet = ? THEN jumlah ELSE 0 END), 0) as total_debet,
        COALESCE(SUM(CASE WHEN rekening_kredit = ? THEN jumlah ELSE 0 END), 0) as total_kredit
    FROM transaksi
    WHERE tgl_transaksi <= ?
";

$result_awal = fetchOne($sql_saldo_awal, [$akun_kode, $akun_kode, $tgl_sebelum]);

$total_debet_awal = $result_awal['total_debet'];
$total_kredit_awal = $result_awal['total_kredit'];

if ($akun['jenis_mutasi'] == 'Debet') {
    $saldo_awal = $total_debet_awal - $total_kredit_awal;
} else {
    $saldo_awal = $total_kredit_awal - $total_debet_awal;
}

/**
 * Get transaksi dalam periode
 */
$sql_transaksi = "
    SELECT 
        t.tgl_transaksi,
        t.keterangan_transaksi,
        CASE 
            WHEN t.rekening_debet = ? THEN t.jumlah 
            ELSE 0 
        END as debet,
        CASE 
            WHEN t.rekening_kredit = ? THEN t.jumlah 
            ELSE 0 
        END as kredit,
        CASE 
            WHEN t.rekening_debet = ? THEN CONCAT(t.rekening_kredit, ' - ', coa_k.nama_akun)
            ELSE CONCAT(t.rekening_debet, ' - ', coa_d.nama_akun)
        END as lawan
    FROM transaksi t
    LEFT JOIN chart_of_accounts coa_d ON t.rekening_debet = coa_d.kode_akun
    LEFT JOIN chart_of_accounts coa_k ON t.rekening_kredit = coa_k.kode_akun
    WHERE (t.rekening_debet = ? OR t.rekening_kredit = ?)
    AND t.tgl_transaksi >= ? AND t.tgl_transaksi <= ?
    ORDER BY t.tgl_transaksi ASC, t.id ASC
";

$transaksi = fetchAll($sql_transaksi, [
    $akun_kode, $akun_kode, $akun_kode,
    $akun_kode, $akun_kode,
    $tgl_awal, $tgl_akhir
]);

// Hitung mutasi
$saldo_berjalan = $saldo_awal;
$total_debet = 0;
$total_kredit = 0;
?>

<div class="card buku-besar-detail">
    <div class="card-header bg-info text-white print-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="mb-0">
                    <strong><?php echo $akun['kode_akun']; ?> - <?php echo $akun['nama_akun']; ?></strong>
                </h5>
                <small>Periode: <?php echo $bulan_nama[$bulan]; ?> <?php echo $tahun; ?></small>
            </div>
            <div class="col-md-4 text-end">
                <span class="badge bg-light text-dark fs-6">
                    Jenis Mutasi: <?php echo $akun['jenis_mutasi']; ?>
                </span>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="10%">Tanggal</th>
                        <th width="30%">Keterangan</th>
                        <th width="25%">Akun Lawan</th>
                        <th width="12%" class="text-end">Debet (Rp)</th>
                        <th width="12%" class="text-end">Kredit (Rp)</th>
                        <th width="13%" class="text-end">Saldo (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Saldo Awal -->
                    <tr class="table-secondary">
                        <td colspan="3"><strong>Saldo Awal</strong></td>
                        <td class="text-end">-</td>
                        <td class="text-end">-</td>
                        <td class="text-end"><strong><?php echo number_format($saldo_awal, 0, ',', '.'); ?></strong></td>
                    </tr>
                    
                    <!-- Transaksi -->
                    <?php if (count($transaksi) > 0): ?>
                        <?php foreach ($transaksi as $trx): ?>
                            <?php
                            // Update saldo
                            if ($akun['jenis_mutasi'] == 'Debet') {
                                $saldo_berjalan += $trx['debet'] - $trx['kredit'];
                            } else {
                                $saldo_berjalan += $trx['kredit'] - $trx['debet'];
                            }
                            
                            $total_debet += $trx['debet'];
                            $total_kredit += $trx['kredit'];
                            ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($trx['tgl_transaksi'])); ?></td>
                                <td><?php echo $trx['keterangan_transaksi']; ?></td>
                                <td class="small"><?php echo $trx['lawan']; ?></td>
                                <td class="text-end">
                                    <?php echo $trx['debet'] > 0 ? number_format($trx['debet'], 0, ',', '.') : '-'; ?>
                                </td>
                                <td class="text-end">
                                    <?php echo $trx['kredit'] > 0 ? number_format($trx['kredit'], 0, ',', '.') : '-'; ?>
                                </td>
                                <td class="text-end <?php echo $saldo_berjalan < 0 ? 'text-danger' : ''; ?>">
                                    <?php echo number_format($saldo_berjalan, 0, ',', '.'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="bi bi-inbox"></i><br>
                                Tidak ada transaksi dalam periode ini
                            </td>
                        </tr>
                    <?php endif; ?>
                    
                    <!-- Total -->
                    <tr class="table-warning">
                        <td colspan="3" class="text-end"><strong>TOTAL MUTASI</strong></td>
                        <td class="text-end"><strong><?php echo number_format($total_debet, 0, ',', '.'); ?></strong></td>
                        <td class="text-end"><strong><?php echo number_format($total_kredit, 0, ',', '.'); ?></strong></td>
                        <td></td>
                    </tr>
                    
                    <!-- Saldo Akhir -->
                    <tr class="table-success">
                        <td colspan="3"><strong>Saldo Akhir</strong></td>
                        <td class="text-end">-</td>
                        <td class="text-end">-</td>
                        <td class="text-end <?php echo $saldo_berjalan < 0 ? 'text-danger' : ''; ?>">
                            <strong><?php echo number_format($saldo_berjalan, 0, ',', '.'); ?></strong>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Summary -->
<div class="row mt-3">
    <div class="col-md-3">
        <div class="card bg-light">
            <div class="card-body text-center">
                <small class="text-muted">Saldo Awal</small>
                <h5 class="mb-0">Rp <?php echo number_format($saldo_awal, 0, ',', '.'); ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-light">
            <div class="card-body text-center">
                <small class="text-muted">Total Debet</small>
                <h5 class="text-primary mb-0">Rp <?php echo number_format($total_debet, 0, ',', '.'); ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-light">
            <div class="card-body text-center">
                <small class="text-muted">Total Kredit</small>
                <h5 class="text-danger mb-0">Rp <?php echo number_format($total_kredit, 0, ',', '.'); ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-<?php echo $saldo_berjalan < 0 ? 'danger' : 'success'; ?> text-white">
            <div class="card-body text-center">
                <small>Saldo Akhir</small>
                <h5 class="mb-0">Rp <?php echo number_format($saldo_berjalan, 0, ',', '.'); ?></h5>
            </div>
        </div>
    </div>
</div>

<!-- Tombol -->
<div class="text-center mt-4 no-print">
    <button class="btn btn-success" onclick="window.print()">
        <i class="bi bi-printer"></i> Print Buku Besar
    </button>
    <button class="btn btn-secondary" onclick="pilihAkunLain()">
        <i class="bi bi-arrow-left"></i> Pilih Akun Lain
    </button>
</div>

<script>
function pilihAkunLain() {
    document.getElementById('hasilBukuBesar').innerHTML = '';
    document.getElementById('selectAkun').value = '';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

<style>
.table-sm td, .table-sm th {
    padding: 0.4rem;
    vertical-align: middle;
}

@media print {
    /* Pastikan header tetap muncul */
    .print-header {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color: white !important;
        background-color: #17a2b8 !important;
    }
    
    .no-print {
        display: none !important;
    }
    
    .card {
        box-shadow: none !important;
        border: 1px solid #000 !important;
    }
    
    .buku-besar-detail {
        page-break-inside: avoid;
    }
    
    /* Force background colors */
    .table-secondary,
    .table-warning,
    .table-success,
    .table-light {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>