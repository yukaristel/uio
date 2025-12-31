<?php
/**
 * LAPORAN JURNAL UMUM (General Journal)
 * Menampilkan semua transaksi dalam periode
 */

// Jika tidak ada koneksi database, include dari root
if (!function_exists('fetchOne')) {
    require_once '../../config/database.php';
}

// Ambil parameter dari GET
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

// Hitung tanggal awal dan akhir bulan
$tgl_awal = "$tahun-$bulan-01";
$tgl_akhir = "$tahun-$bulan-" . date('t', strtotime("$tahun-$bulan-01"));

$bulan_nama = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
    '04' => 'April', '05' => 'Mei', '06' => 'Juni',
    '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
    '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

/**
 * Get semua transaksi dalam periode
 */
$sql = "
    SELECT 
        t.id,
        t.tgl_transaksi,
        t.rekening_debet,
        coa_d.nama_akun as nama_debet,
        t.rekening_kredit,
        coa_k.nama_akun as nama_kredit,
        t.jumlah,
        t.keterangan_transaksi,
        t.created_at
    FROM transaksi t
    LEFT JOIN chart_of_accounts coa_d ON t.rekening_debet = coa_d.kode_akun
    LEFT JOIN chart_of_accounts coa_k ON t.rekening_kredit = coa_k.kode_akun
    WHERE t.tgl_transaksi >= ? AND t.tgl_transaksi <= ?
    ORDER BY t.tgl_transaksi ASC, t.id ASC
";

$transaksi_list = fetchAll($sql, [$tgl_awal, $tgl_akhir]);

// Hitung total debet dan kredit
$total_debet = 0;
$total_kredit = 0;

foreach ($transaksi_list as $item) {
    $total_debet += $item['jumlah'];
    $total_kredit += $item['jumlah'];
}

$jumlah_transaksi = count($transaksi_list);
?>

<div class="card">
    <div class="card-header bg-warning">
        <h4 class="mb-0"><i class="bi bi-journal-text"></i> JURNAL UMUM (General Journal)</h4>
    </div>
    <div class="card-body">
        <!-- Header Laporan -->
        <div class="text-center mb-4">
            <h5 class="mb-0"><strong>RUMAH MAKAN ANDA</strong></h5>
            <h6>JURNAL UMUM</h6>
            <p class="mb-0">Periode: <?php echo $bulan_nama[$bulan]; ?> <?php echo $tahun; ?></p>
            <p class="mb-0 text-muted small">(<?php echo date('d/m/Y', strtotime($tgl_awal)); ?> s/d <?php echo date('d/m/Y', strtotime($tgl_akhir)); ?>)</p>
        </div>

        <!-- Info -->
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> 
            <strong>Total Transaksi:</strong> <?php echo number_format($jumlah_transaksi); ?> transaksi
        </div>

        <?php if ($jumlah_transaksi > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="table-light">
                    <tr>
                        <th width="3%" class="text-center">No</th>
                        <th width="8%">Tanggal</th>
                        <th width="10%">Kode Akun</th>
                        <th width="27%">Nama Akun</th>
                        <th width="12%" class="text-end">Debet (Rp)</th>
                        <th width="12%" class="text-end">Kredit (Rp)</th>
                        <th width="28%">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    $current_date = '';
                    foreach ($transaksi_list as $item): 
                        $tgl = date('d/m/Y', strtotime($item['tgl_transaksi']));
                        $show_date = ($tgl != $current_date);
                        $current_date = $tgl;
                    ?>
                    
                    <!-- Baris Debet -->
                    <tr>
                        <td class="text-center" rowspan="2">
                            <?php if ($show_date): ?>
                            <?php echo $no++; ?>
                            <?php endif; ?>
                        </td>
                        <td rowspan="2">
                            <?php if ($show_date): ?>
                            <?php echo $tgl; ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $item['rekening_debet']; ?></td>
                        <td style="padding-left: 20px;"><?php echo $item['nama_debet']; ?></td>
                        <td class="text-end"><?php echo number_format($item['jumlah'], 0, ',', '.'); ?></td>
                        <td class="text-end">-</td>
                        <td rowspan="2" style="font-size: 12px;">
                            <?php echo $item['keterangan_transaksi']; ?>
                        </td>
                    </tr>
                    
                    <!-- Baris Kredit -->
                    <tr>
                        <td><?php echo $item['rekening_kredit']; ?></td>
                        <td style="padding-left: 40px;"><?php echo $item['nama_kredit']; ?></td>
                        <td class="text-end">-</td>
                        <td class="text-end"><?php echo number_format($item['jumlah'], 0, ',', '.'); ?></td>
                    </tr>
                    
                    <!-- Separator -->
                    <tr>
                        <td colspan="7" style="height: 5px; background-color: #f8f9fa;"></td>
                    </tr>
                    
                    <?php endforeach; ?>
                    
                    <!-- Total -->
                    <tr class="table-warning">
                        <td colspan="4" class="text-end"><strong>TOTAL</strong></td>
                        <td class="text-end"><strong><?php echo number_format($total_debet, 0, ',', '.'); ?></strong></td>
                        <td class="text-end"><strong><?php echo number_format($total_kredit, 0, ',', '.'); ?></strong></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Validasi Balance -->
        <?php if ($total_debet == $total_kredit): ?>
        <div class="alert alert-success mt-3">
            <i class="bi bi-check-circle"></i> 
            <strong>Balance OK!</strong> Total Debet = Total Kredit
        </div>
        <?php else: ?>
        <div class="alert alert-danger mt-3">
            <i class="bi bi-exclamation-triangle"></i> 
            <strong>Not Balance!</strong> Selisih: Rp <?php echo number_format(abs($total_debet - $total_kredit), 0, ',', '.'); ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <!-- Tidak ada transaksi -->
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> 
            <strong>Tidak ada transaksi</strong> dalam periode ini.
        </div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <small class="text-muted">Jumlah Transaksi</small>
                        <h5 class="mb-0"><?php echo number_format($jumlah_transaksi); ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <small class="text-muted">Total Debet</small>
                        <h5 class="mb-0">Rp <?php echo number_format($total_debet, 0, ',', '.'); ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <small class="text-muted">Total Kredit</small>
                        <h5 class="mb-0">Rp <?php echo number_format($total_kredit, 0, ',', '.'); ?></h5>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-4">
            <div class="row">
                <div class="col-6 text-center">
                    <p class="mb-5">Dibuat Oleh,</p>
                    <p class="mb-0">_________________</p>
                    <p>Admin</p>
                </div>
                <div class="col-6 text-center">
                    <p class="mb-5">Diperiksa Oleh,</p>
                    <p class="mb-0">_________________</p>
                    <p>Pemilik</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.table-sm td, .table-sm th {
    padding: 0.3rem;
    vertical-align: middle;
}

@media print {
    .alert {
        page-break-inside: avoid;
    }
    .card {
        box-shadow: none !important;
        border: none !important;
    }
}
</style>