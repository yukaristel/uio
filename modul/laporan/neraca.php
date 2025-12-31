<?php
/**
 * LAPORAN NERACA (Balance Sheet)
 * Menampilkan Aset, Liabilitas, dan Ekuitas
 */

// Jika tidak ada koneksi database, include dari root
if (!function_exists('fetchOne')) {
    require_once '../../config/database.php';
}

// Ambil parameter dari GET
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

// Hitung tanggal akhir bulan
$tgl_akhir = "$tahun-$bulan-" . date('t', strtotime("$tahun-$bulan-01"));

$bulan_nama = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
    '04' => 'April', '05' => 'Mei', '06' => 'Juni',
    '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
    '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

/**
 * Fungsi untuk menghitung saldo akun sampai periode tertentu
 */
function getSaldoAkun($kode_akun, $tgl_akhir) {
    // Get jenis mutasi akun
    $akun = fetchOne("SELECT jenis_mutasi FROM chart_of_accounts WHERE kode_akun = ?", [$kode_akun]);
    if (!$akun) return 0;
    
    $jenis_mutasi = $akun['jenis_mutasi'];
    
    // Hitung saldo dari semua transaksi sampai tanggal tertentu
    $sql = "
        SELECT 
            COALESCE(SUM(CASE WHEN rekening_debet = ? THEN jumlah ELSE 0 END), 0) as total_debet,
            COALESCE(SUM(CASE WHEN rekening_kredit = ? THEN jumlah ELSE 0 END), 0) as total_kredit
        FROM transaksi
        WHERE tgl_transaksi <= ?
    ";
    
    $result = fetchOne($sql, [$kode_akun, $kode_akun, $tgl_akhir]);
    
    if (!$result) return 0;
    
    $total_debet = $result['total_debet'];
    $total_kredit = $result['total_kredit'];
    
    // Hitung saldo sesuai jenis mutasi normal akun
    if ($jenis_mutasi == 'Debet') {
        return $total_debet - $total_kredit;
    } else {
        return $total_kredit - $total_debet;
    }
}

/**
 * Get Aset
 */
$sql_aset = "
    SELECT kode_akun, nama_akun, lev2, lev3, lev4
    FROM chart_of_accounts
    WHERE lev1 = 1 AND status = 'Aktif'
    ORDER BY kode_akun
";
$aset_list = fetchAll($sql_aset);

$aset_data = [];
$total_aset = 0;

foreach ($aset_list as $item) {
    $saldo = getSaldoAkun($item['kode_akun'], $tgl_akhir);
    
    // Tampilkan semua akun (termasuk yang saldo 0)
    $aset_data[] = [
        'kode' => $item['kode_akun'],
        'nama' => $item['nama_akun'],
        'saldo' => $saldo,
        'level' => $item['lev4']
    ];
    
    if ($item['lev4'] > 0) {
        $total_aset += $saldo;
    }
}

/**
 * Get Liabilitas
 */
$sql_liabilitas = "
    SELECT kode_akun, nama_akun, lev2, lev3, lev4
    FROM chart_of_accounts
    WHERE lev1 = 2 AND status = 'Aktif'
    ORDER BY kode_akun
";
$liabilitas_list = fetchAll($sql_liabilitas);

$liabilitas_data = [];
$total_liabilitas = 0;

foreach ($liabilitas_list as $item) {
    $saldo = getSaldoAkun($item['kode_akun'], $tgl_akhir);
    
    // Tampilkan semua akun (termasuk yang saldo 0)
    $liabilitas_data[] = [
        'kode' => $item['kode_akun'],
        'nama' => $item['nama_akun'],
        'saldo' => $saldo,
        'level' => $item['lev4']
    ];
    
    if ($item['lev4'] > 0) {
        $total_liabilitas += $saldo;
    }
}

/**
 * Get Ekuitas
 */
$sql_ekuitas = "
    SELECT kode_akun, nama_akun, lev2, lev3, lev4
    FROM chart_of_accounts
    WHERE lev1 = 3 AND status = 'Aktif'
    ORDER BY kode_akun
";
$ekuitas_list = fetchAll($sql_ekuitas);

$ekuitas_data = [];
$total_ekuitas_akun = 0;

foreach ($ekuitas_list as $item) {
    $saldo = getSaldoAkun($item['kode_akun'], $tgl_akhir);
    
    // Tampilkan semua akun (termasuk yang saldo 0)
    $ekuitas_data[] = [
        'kode' => $item['kode_akun'],
        'nama' => $item['nama_akun'],
        'saldo' => $saldo,
        'level' => $item['lev4']
    ];
    
    if ($item['lev4'] > 0) {
        $total_ekuitas_akun += $saldo;
    }
}

// Hitung Laba/Rugi Periode Berjalan
// Pendapatan (Kredit) - Beban (Debet)
$sql_pendapatan = "
    SELECT COALESCE(SUM(t.jumlah), 0) as total
    FROM transaksi t
    JOIN chart_of_accounts c ON t.rekening_kredit = c.kode_akun
    WHERE c.lev1 = 4 AND t.tgl_transaksi <= ?
";
$pendapatan = fetchOne($sql_pendapatan, [$tgl_akhir]);
$total_pendapatan = $pendapatan['total'];

$sql_beban = "
    SELECT COALESCE(SUM(t.jumlah), 0) as total
    FROM transaksi t
    JOIN chart_of_accounts c ON t.rekening_debet = c.kode_akun
    WHERE c.lev1 = 5 AND t.tgl_transaksi <= ?
";
$beban = fetchOne($sql_beban, [$tgl_akhir]);
$total_beban = $beban['total'];

$laba_rugi_berjalan = $total_pendapatan - $total_beban;
$total_ekuitas = $total_ekuitas_akun + $laba_rugi_berjalan;

$total_pasiva = $total_liabilitas + $total_ekuitas;
?>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h4 class="mb-0"><i class="bi bi-diagram-3"></i> NERACA (Balance Sheet)</h4>
    </div>
    <div class="card-body">
        <!-- Header Laporan -->
        <div class="text-center mb-4">
            <h5 class="mb-0"><strong>RUMAH MAKAN ANDA</strong></h5>
            <h6>NERACA</h6>
            <p class="mb-0">Per <?php echo date('d', strtotime($tgl_akhir)); ?> <?php echo $bulan_nama[$bulan]; ?> <?php echo $tahun; ?></p>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th width="20%">Kode Akun</th>
                        <th width="50%">Nama Akun</th>
                        <th width="30%" class="text-end">Jumlah (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- ASET -->
                    <tr class="table-info">
                        <td colspan="3"><strong>ASET</strong></td>
                    </tr>
                    <?php foreach ($aset_data as $item): ?>
                    <tr>
                        <td><?php echo $item['kode']; ?></td>
                        <td class="<?php echo $item['level'] == 0 ? 'fw-bold' : ''; ?>" 
                            style="padding-left: 20px;">
                            <?php echo $item['nama']; ?>
                        </td>
                        <td class="text-end <?php echo $item['level'] == 0 ? 'fw-bold' : ''; ?>">
                            <?php 
                            if ($item['level'] > 0) {
                                echo number_format($item['saldo'], 0, ',', '.');
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <tr class="table-warning">
                        <td colspan="2" class="text-end"><strong>TOTAL ASET</strong></td>
                        <td class="text-end"><strong><?php echo number_format($total_aset, 0, ',', '.'); ?></strong></td>
                    </tr>

                    <!-- LIABILITAS -->
                    <tr class="table-info">
                        <td colspan="3"><strong>LIABILITAS</strong></td>
                    </tr>
                    <?php foreach ($liabilitas_data as $item): ?>
                    <tr>
                        <td><?php echo $item['kode']; ?></td>
                        <td class="<?php echo $item['level'] == 0 ? 'fw-bold' : ''; ?>" 
                            style="padding-left: 20px;">
                            <?php echo $item['nama']; ?>
                        </td>
                        <td class="text-end <?php echo $item['level'] == 0 ? 'fw-bold' : ''; ?>">
                            <?php 
                            if ($item['level'] > 0) {
                                echo number_format($item['saldo'], 0, ',', '.');
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <tr class="table-warning">
                        <td colspan="2" class="text-end"><strong>TOTAL LIABILITAS</strong></td>
                        <td class="text-end"><strong><?php echo number_format($total_liabilitas, 0, ',', '.'); ?></strong></td>
                    </tr>

                    <!-- EKUITAS -->
                    <tr class="table-info">
                        <td colspan="3"><strong>EKUITAS</strong></td>
                    </tr>
                    <?php foreach ($ekuitas_data as $item): ?>
                    <tr>
                        <td><?php echo $item['kode']; ?></td>
                        <td class="<?php echo $item['level'] == 0 ? 'fw-bold' : ''; ?>" 
                            style="padding-left: 20px;">
                            <?php echo $item['nama']; ?>
                        </td>
                        <td class="text-end <?php echo $item['level'] == 0 ? 'fw-bold' : ''; ?>">
                            <?php 
                            if ($item['level'] > 0) {
                                echo number_format($item['saldo'], 0, ',', '.');
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <tr>
                        <td>-</td>
                        <td style="padding-left: 20px;">Laba/Rugi Periode Berjalan</td>
                        <td class="text-end <?php echo $laba_rugi_berjalan < 0 ? 'text-danger' : 'text-success'; ?>">
                            <?php echo number_format($laba_rugi_berjalan, 0, ',', '.'); ?>
                        </td>
                    </tr>
                    
                    <tr class="table-warning">
                        <td colspan="2" class="text-end"><strong>TOTAL EKUITAS</strong></td>
                        <td class="text-end"><strong><?php echo number_format($total_ekuitas, 0, ',', '.'); ?></strong></td>
                    </tr>

                    <!-- TOTAL PASIVA -->
                    <tr class="table-success">
                        <td colspan="2" class="text-end"><strong>TOTAL LIABILITAS + EKUITAS</strong></td>
                        <td class="text-end"><strong><?php echo number_format($total_pasiva, 0, ',', '.'); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Validasi Balance -->
        <?php if ($total_aset == $total_pasiva): ?>
        <div class="alert alert-hide alert-success">
            <i class="bi bi-check-circle"></i> 
            <strong>Balance OK!</strong> Total Aset = Total Liabilitas + Ekuitas
        </div>
        <?php else: ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i> 
            <strong>Not Balance!</strong> Selisih: Rp <?php echo number_format(abs($total_aset - $total_pasiva), 0, ',', '.'); ?>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="mt-4">
            <div class="row">
                <div class="col-6 text-center">
                    <p class="mb-5">Disiapkan Oleh,</p>
                    <p class="mb-0">_________________</p>
                    <p>Admin</p>
                </div>
                <div class="col-6 text-center">
                    <p class="mb-5">Disetujui Oleh,</p>
                    <p class="mb-0">_________________</p>
                    <p>Pemilik</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .alert {
        display: none !important;
    }
}
</style>