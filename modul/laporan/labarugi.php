<?php
/**
 * LAPORAN LABA RUGI (Income Statement)
 * Menampilkan Pendapatan, Beban, dan Laba/Rugi
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
 * Fungsi untuk menghitung saldo akun dalam periode tertentu
 */
function getSaldoPeriode($kode_akun, $tgl_awal, $tgl_akhir) {
    // Get jenis mutasi akun
    $akun = fetchOne("SELECT jenis_mutasi FROM chart_of_accounts WHERE kode_akun = ?", [$kode_akun]);
    if (!$akun) return 0;
    
    $jenis_mutasi = $akun['jenis_mutasi'];
    
    // Hitung saldo dari transaksi dalam periode
    $sql = "
        SELECT 
            COALESCE(SUM(CASE WHEN rekening_debet = ? THEN jumlah ELSE 0 END), 0) as total_debet,
            COALESCE(SUM(CASE WHEN rekening_kredit = ? THEN jumlah ELSE 0 END), 0) as total_kredit
        FROM transaksi
        WHERE tgl_transaksi >= ? AND tgl_transaksi <= ?
    ";
    
    $result = fetchOne($sql, [$kode_akun, $kode_akun, $tgl_awal, $tgl_akhir]);
    
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
 * Get Pendapatan (Akun 4.x.xx.xx)
 */
$sql_pendapatan = "
    SELECT kode_akun, nama_akun, lev2, lev3, lev4
    FROM chart_of_accounts
    WHERE lev1 = 4 AND status = 'Aktif'
    ORDER BY kode_akun
";
$pendapatan_list = fetchAll($sql_pendapatan);

$pendapatan_data = [];
$total_pendapatan = 0;

foreach ($pendapatan_list as $item) {
    $saldo = getSaldoPeriode($item['kode_akun'], $tgl_awal, $tgl_akhir);
    
    $pendapatan_data[] = [
        'kode' => $item['kode_akun'],
        'nama' => $item['nama_akun'],
        'saldo' => $saldo,
        'level' => $item['lev4']
    ];
    
    if ($item['lev4'] > 0) {
        $total_pendapatan += $saldo;
    }
}

/**
 * Get Beban (Akun 5.x.xx.xx)
 */
$sql_beban = "
    SELECT kode_akun, nama_akun, lev2, lev3, lev4
    FROM chart_of_accounts
    WHERE lev1 = 5 AND status = 'Aktif'
    ORDER BY kode_akun
";
$beban_list = fetchAll($sql_beban);

$beban_data = [];
$total_hpp = 0;
$total_beban_operasional = 0;
$total_beban_lainlain = 0;

foreach ($beban_list as $item) {
    $saldo = getSaldoPeriode($item['kode_akun'], $tgl_awal, $tgl_akhir);
    
    $beban_data[] = [
        'kode' => $item['kode_akun'],
        'nama' => $item['nama_akun'],
        'saldo' => $saldo,
        'level' => $item['lev4'],
        'kategori' => $item['lev2'] // 1=HPP, 2=Operasional, 3=Lain-lain
    ];
    
    if ($item['lev4'] > 0) {
        if ($item['lev2'] == 1) {
            $total_hpp += $saldo;
        } elseif ($item['lev2'] == 2) {
            $total_beban_operasional += $saldo;
        } elseif ($item['lev2'] == 3) {
            $total_beban_lainlain += $saldo;
        }
    }
}

// Hitung laba kotor
$laba_kotor = $total_pendapatan - $total_hpp;

// Hitung laba operasional
$laba_operasional = $laba_kotor - $total_beban_operasional;

// Hitung laba bersih
$laba_bersih = $laba_operasional - $total_beban_lainlain;

// Total beban
$total_beban = $total_hpp + $total_beban_operasional + $total_beban_lainlain;
?>

<div class="card">
    <div class="card-header bg-success text-white">
        <h4 class="mb-0"><i class="bi bi-cash-stack"></i> LAPORAN LABA RUGI (Income Statement)</h4>
    </div>
    <div class="card-body">
        <!-- Header Laporan -->
        <div class="text-center mb-4">
            <h5 class="mb-0"><strong>RUMAH MAKAN ANDA</strong></h5>
            <h6>LAPORAN LABA RUGI</h6>
            <p class="mb-0">Periode: <?php echo $bulan_nama[$bulan]; ?> <?php echo $tahun; ?></p>
            <p class="mb-0 text-muted small">(<?php echo date('d/m/Y', strtotime($tgl_awal)); ?> s/d <?php echo date('d/m/Y', strtotime($tgl_akhir)); ?>)</p>
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
                    <!-- PENDAPATAN -->
                    <tr class="table-info">
                        <td colspan="3"><strong>PENDAPATAN</strong></td>
                    </tr>
                    <?php foreach ($pendapatan_data as $item): ?>
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
                        <td colspan="2" class="text-end"><strong>TOTAL PENDAPATAN</strong></td>
                        <td class="text-end"><strong><?php echo number_format($total_pendapatan, 0, ',', '.'); ?></strong></td>
                    </tr>

                    <!-- HARGA POKOK PENJUALAN -->
                    <tr class="table-info">
                        <td colspan="3"><strong>HARGA POKOK PENJUALAN</strong></td>
                    </tr>
                    <?php foreach ($beban_data as $item): ?>
                        <?php if ($item['kategori'] == 1): // HPP ?>
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
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <tr class="table-warning">
                        <td colspan="2" class="text-end"><strong>TOTAL HPP</strong></td>
                        <td class="text-end"><strong><?php echo number_format($total_hpp, 0, ',', '.'); ?></strong></td>
                    </tr>

                    <!-- LABA KOTOR -->
                    <tr class="table-primary">
                        <td colspan="2" class="text-end"><strong>LABA KOTOR</strong></td>
                        <td class="text-end <?php echo $laba_kotor < 0 ? 'text-danger' : 'text-success'; ?>">
                            <strong><?php echo number_format($laba_kotor, 0, ',', '.'); ?></strong>
                        </td>
                    </tr>

                    <!-- BEBAN OPERASIONAL -->
                    <tr class="table-info">
                        <td colspan="3"><strong>BEBAN OPERASIONAL</strong></td>
                    </tr>
                    <?php foreach ($beban_data as $item): ?>
                        <?php if ($item['kategori'] == 2): // Beban Operasional ?>
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
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <tr class="table-warning">
                        <td colspan="2" class="text-end"><strong>TOTAL BEBAN OPERASIONAL</strong></td>
                        <td class="text-end"><strong><?php echo number_format($total_beban_operasional, 0, ',', '.'); ?></strong></td>
                    </tr>

                    <!-- LABA OPERASIONAL -->
                    <tr class="table-primary">
                        <td colspan="2" class="text-end"><strong>LABA OPERASIONAL</strong></td>
                        <td class="text-end <?php echo $laba_operasional < 0 ? 'text-danger' : 'text-success'; ?>">
                            <strong><?php echo number_format($laba_operasional, 0, ',', '.'); ?></strong>
                        </td>
                    </tr>

                    <!-- BEBAN LAIN-LAIN -->
                    <tr class="table-info">
                        <td colspan="3"><strong>BEBAN LAIN-LAIN</strong></td>
                    </tr>
                    <?php foreach ($beban_data as $item): ?>
                        <?php if ($item['kategori'] == 3): // Beban Lain-lain ?>
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
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <tr class="table-warning">
                        <td colspan="2" class="text-end"><strong>TOTAL BEBAN LAIN-LAIN</strong></td>
                        <td class="text-end"><strong><?php echo number_format($total_beban_lainlain, 0, ',', '.'); ?></strong></td>
                    </tr>

                    <!-- LABA BERSIH -->
                    <tr class="table-success">
                        <td colspan="2" class="text-end"><strong>LABA (RUGI) BERSIH</strong></td>
                        <td class="text-end <?php echo $laba_bersih < 0 ? 'text-danger' : 'text-success'; ?>">
                            <strong><?php echo number_format($laba_bersih, 0, ',', '.'); ?></strong>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Summary Box -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <small class="text-muted">Total Pendapatan</small>
                        <h5 class="text-success mb-0">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <small class="text-muted">Total Beban</small>
                        <h5 class="text-danger mb-0">Rp <?php echo number_format($total_beban, 0, ',', '.'); ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <small class="text-muted">Laba Kotor</small>
                        <h5 class="<?php echo $laba_kotor < 0 ? 'text-danger' : 'text-success'; ?> mb-0">
                            Rp <?php echo number_format($laba_kotor, 0, ',', '.'); ?>
                        </h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-<?php echo $laba_bersih < 0 ? 'danger' : 'success'; ?> text-white">
                    <div class="card-body text-center">
                        <small>Laba Bersih</small>
                        <h5 class="mb-0">Rp <?php echo number_format($laba_bersih, 0, ',', '.'); ?></h5>
                    </div>
                </div>
            </div>
        </div>

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
    .card {
        box-shadow: none !important;
        border: none !important;
    }
}
</style>