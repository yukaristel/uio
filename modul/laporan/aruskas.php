<?php
/**
 * LAPORAN ARUS KAS (Cash Flow Statement)
 * Menampilkan aliran kas masuk dan keluar
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
 * Get Saldo Kas Awal (akhir bulan sebelumnya)
 */
$bulan_sebelum = date('Y-m-t', strtotime("$tgl_awal -1 day"));

$sql_kas_awal = "
    SELECT COALESCE(SUM(
        CASE 
            WHEN t.rekening_debet IN (SELECT kode_akun FROM chart_of_accounts WHERE lev1 = 1 AND lev2 = 1 AND lev4 > 0) 
            THEN t.jumlah
            WHEN t.rekening_kredit IN (SELECT kode_akun FROM chart_of_accounts WHERE lev1 = 1 AND lev2 = 1 AND lev4 > 0) 
            THEN -t.jumlah
            ELSE 0
        END
    ), 0) as saldo
    FROM transaksi t
    WHERE t.tgl_transaksi <= ?
";
$result_awal = fetchOne($sql_kas_awal, [$bulan_sebelum]);
$saldo_kas_awal = $result_awal['saldo'];

/**
 * ARUS KAS DARI AKTIVITAS OPERASI
 */

// Kas Masuk dari Penjualan (Pendapatan)
$sql_penjualan = "
    SELECT COALESCE(SUM(t.jumlah), 0) as total
    FROM transaksi t
    WHERE t.rekening_kredit IN (SELECT kode_akun FROM chart_of_accounts WHERE lev1 = 4)
    AND t.rekening_debet IN (SELECT kode_akun FROM chart_of_accounts WHERE lev1 = 1 AND lev2 = 1)
    AND t.tgl_transaksi >= ? AND t.tgl_transaksi <= ?
";
$penjualan = fetchOne($sql_penjualan, [$tgl_awal, $tgl_akhir]);
$kas_dari_penjualan = $penjualan['total'];

// Kas Keluar untuk Pembelian Bahan Baku
$sql_pembelian = "
    SELECT COALESCE(SUM(t.jumlah), 0) as total
    FROM transaksi t
    WHERE t.rekening_debet IN (SELECT kode_akun FROM chart_of_accounts WHERE lev1 = 1 AND lev2 = 2)
    AND t.rekening_kredit IN (SELECT kode_akun FROM chart_of_accounts WHERE lev1 = 1 AND lev2 = 1)
    AND t.tgl_transaksi >= ? AND t.tgl_transaksi <= ?
";
$pembelian = fetchOne($sql_pembelian, [$tgl_awal, $tgl_akhir]);
$kas_untuk_pembelian = $pembelian['total'];

// Kas Keluar untuk Beban Operasional
$sql_beban = "
    SELECT COALESCE(SUM(t.jumlah), 0) as total
    FROM transaksi t
    WHERE t.rekening_debet IN (SELECT kode_akun FROM chart_of_accounts WHERE lev1 = 5 AND lev2 = 2)
    AND t.rekening_kredit IN (SELECT kode_akun FROM chart_of_accounts WHERE lev1 = 1 AND lev2 = 1)
    AND t.tgl_transaksi >= ? AND t.tgl_transaksi <= ?
";
$beban = fetchOne($sql_beban, [$tgl_awal, $tgl_akhir]);
$kas_untuk_beban = $beban['total'];

$total_kas_operasi_masuk = $kas_dari_penjualan;
$total_kas_operasi_keluar = $kas_untuk_pembelian + $kas_untuk_beban;
$kas_bersih_operasi = $total_kas_operasi_masuk - $total_kas_operasi_keluar;

/**
 * ARUS KAS DARI AKTIVITAS INVESTASI
 */

// Kas Keluar untuk Pembelian Aset Tetap
$sql_investasi = "
    SELECT COALESCE(SUM(t.jumlah), 0) as total
    FROM transaksi t
    WHERE t.rekening_debet IN (SELECT kode_akun FROM chart_of_accounts WHERE lev1 = 1 AND lev2 = 3)
    AND t.rekening_kredit IN (SELECT kode_akun FROM chart_of_accounts WHERE lev1 = 1 AND lev2 = 1)
    AND t.tgl_transaksi >= ? AND t.tgl_transaksi <= ?
";
$investasi = fetchOne($sql_investasi, [$tgl_awal, $tgl_akhir]);
$kas_untuk_investasi = $investasi['total'];

// Kas Masuk dari Penjualan Aset (jika ada)
$sql_jual_aset = "
    SELECT COALESCE(SUM(t.jumlah), 0) as total
    FROM transaksi t
    WHERE t.rekening_debet IN (SELECT kode_akun FROM chart_of_accounts WHERE lev1 = 1 AND lev2 = 1)
    AND t.rekening_kredit IN (SELECT kode_akun FROM chart_of_accounts WHERE lev1 = 1 AND lev2 = 3)
    AND t.tgl_transaksi >= ? AND t.tgl_transaksi <= ?
";
$jual_aset = fetchOne($sql_jual_aset, [$tgl_awal, $tgl_akhir]);
$kas_dari_jual_aset = $jual_aset['total'];

$total_kas_investasi_masuk = $kas_dari_jual_aset;
$total_kas_investasi_keluar = $kas_untuk_investasi;
$kas_bersih_investasi = $total_kas_investasi_masuk - $total_kas_investasi_keluar;

/**
 * ARUS KAS DARI AKTIVITAS PENDANAAN
 */

// Kas Masuk dari Modal/Pinjaman
$sql_modal = "
    SELECT COALESCE(SUM(t.jumlah), 0) as total
    FROM transaksi t
    WHERE t.rekening_debet IN (SELECT kode_akun FROM chart_of_accounts WHERE lev1 = 1 AND lev2 = 1)
    AND t.rekening_kredit IN (SELECT kode_akun FROM chart_of_accounts WHERE lev1 IN (2, 3))
    AND t.tgl_transaksi >= ? AND t.tgl_transaksi <= ?
";
$modal = fetchOne($sql_modal, [$tgl_awal, $tgl_akhir]);
$kas_dari_modal = $modal['total'];

// Kas Keluar untuk Pembayaran Hutang/Pinjaman
$sql_bayar_hutang = "
    SELECT COALESCE(SUM(t.jumlah), 0) as total
    FROM transaksi t
    WHERE t.rekening_debet IN (SELECT kode_akun FROM chart_of_accounts WHERE lev1 = 2)
    AND t.rekening_kredit IN (SELECT kode_akun FROM chart_of_accounts WHERE lev1 = 1 AND lev2 = 1)
    AND t.tgl_transaksi >= ? AND t.tgl_transaksi <= ?
";
$bayar_hutang = fetchOne($sql_bayar_hutang, [$tgl_awal, $tgl_akhir]);
$kas_untuk_bayar_hutang = $bayar_hutang['total'];

// Kas Keluar untuk Beban Bunga
$sql_bunga = "
    SELECT COALESCE(SUM(t.jumlah), 0) as total
    FROM transaksi t
    WHERE t.rekening_debet IN (SELECT kode_akun FROM chart_of_accounts WHERE lev1 = 5 AND lev2 = 3)
    AND t.rekening_kredit IN (SELECT kode_akun FROM chart_of_accounts WHERE lev1 = 1 AND lev2 = 1)
    AND t.tgl_transaksi >= ? AND t.tgl_transaksi <= ?
";
$bunga = fetchOne($sql_bunga, [$tgl_awal, $tgl_akhir]);
$kas_untuk_bunga = $bunga['total'];

$total_kas_pendanaan_masuk = $kas_dari_modal;
$total_kas_pendanaan_keluar = $kas_untuk_bayar_hutang + $kas_untuk_bunga;
$kas_bersih_pendanaan = $total_kas_pendanaan_masuk - $total_kas_pendanaan_keluar;

/**
 * KENAIKAN/PENURUNAN KAS
 */
$kenaikan_kas = $kas_bersih_operasi + $kas_bersih_investasi + $kas_bersih_pendanaan;
$saldo_kas_akhir = $saldo_kas_awal + $kenaikan_kas;

/**
 * Verifikasi dengan saldo aktual
 */
$sql_kas_akhir_aktual = "
    SELECT COALESCE(SUM(
        CASE 
            WHEN t.rekening_debet IN (SELECT kode_akun FROM chart_of_accounts WHERE lev1 = 1 AND lev2 = 1 AND lev4 > 0) 
            THEN t.jumlah
            WHEN t.rekening_kredit IN (SELECT kode_akun FROM chart_of_accounts WHERE lev1 = 1 AND lev2 = 1 AND lev4 > 0) 
            THEN -t.jumlah
            ELSE 0
        END
    ), 0) as saldo
    FROM transaksi t
    WHERE t.tgl_transaksi <= ?
";
$result_akhir = fetchOne($sql_kas_akhir_aktual, [$tgl_akhir]);
$saldo_kas_akhir_aktual = $result_akhir['saldo'];
?>

<div class="card">
    <div class="card-header bg-info text-white">
        <h4 class="mb-0"><i class="bi bi-arrow-left-right"></i> LAPORAN ARUS KAS (Cash Flow Statement)</h4>
    </div>
    <div class="card-body">
        <!-- Header Laporan -->
        <div class="text-center mb-4">
            <h5 class="mb-0"><strong>RUMAH MAKAN ANDA</strong></h5>
            <h6>LAPORAN ARUS KAS</h6>
            <p class="mb-0">Periode: <?php echo $bulan_nama[$bulan]; ?> <?php echo $tahun; ?></p>
            <p class="mb-0 text-muted small">(<?php echo date('d/m/Y', strtotime($tgl_awal)); ?> s/d <?php echo date('d/m/Y', strtotime($tgl_akhir)); ?>)</p>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered">
                <tbody>
                    <!-- SALDO KAS AWAL -->
                    <tr class="table-secondary">
                        <td width="70%"><strong>Saldo Kas Awal Periode</strong></td>
                        <td width="30%" class="text-end"><strong><?php echo number_format($saldo_kas_awal, 0, ',', '.'); ?></strong></td>
                    </tr>

                    <!-- AKTIVITAS OPERASI -->
                    <tr class="table-primary">
                        <td colspan="2"><strong>ARUS KAS DARI AKTIVITAS OPERASI</strong></td>
                    </tr>
                    <tr>
                        <td style="padding-left: 20px;">Penerimaan dari Penjualan</td>
                        <td class="text-end text-success"><?php echo number_format($kas_dari_penjualan, 0, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td style="padding-left: 20px;">Pembayaran untuk Pembelian Bahan Baku</td>
                        <td class="text-end text-danger">(<?php echo number_format($kas_untuk_pembelian, 0, ',', '.'); ?>)</td>
                    </tr>
                    <tr>
                        <td style="padding-left: 20px;">Pembayaran untuk Beban Operasional</td>
                        <td class="text-end text-danger">(<?php echo number_format($kas_untuk_beban, 0, ',', '.'); ?>)</td>
                    </tr>
                    <tr class="table-warning">
                        <td class="text-end"><strong>Kas Bersih dari Aktivitas Operasi</strong></td>
                        <td class="text-end <?php echo $kas_bersih_operasi < 0 ? 'text-danger' : 'text-success'; ?>">
                            <strong><?php echo number_format($kas_bersih_operasi, 0, ',', '.'); ?></strong>
                        </td>
                    </tr>

                    <!-- AKTIVITAS INVESTASI -->
                    <tr class="table-primary">
                        <td colspan="2"><strong>ARUS KAS DARI AKTIVITAS INVESTASI</strong></td>
                    </tr>
                    <?php if ($kas_dari_jual_aset > 0): ?>
                    <tr>
                        <td style="padding-left: 20px;">Penerimaan dari Penjualan Aset Tetap</td>
                        <td class="text-end text-success"><?php echo number_format($kas_dari_jual_aset, 0, ',', '.'); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($kas_untuk_investasi > 0): ?>
                    <tr>
                        <td style="padding-left: 20px;">Pembayaran untuk Pembelian Aset Tetap</td>
                        <td class="text-end text-danger">(<?php echo number_format($kas_untuk_investasi, 0, ',', '.'); ?>)</td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($kas_dari_jual_aset == 0 && $kas_untuk_investasi == 0): ?>
                    <tr>
                        <td style="padding-left: 20px;" class="text-muted">Tidak ada transaksi investasi</td>
                        <td class="text-end">0</td>
                    </tr>
                    <?php endif; ?>
                    <tr class="table-warning">
                        <td class="text-end"><strong>Kas Bersih dari Aktivitas Investasi</strong></td>
                        <td class="text-end <?php echo $kas_bersih_investasi < 0 ? 'text-danger' : 'text-success'; ?>">
                            <strong><?php echo number_format($kas_bersih_investasi, 0, ',', '.'); ?></strong>
                        </td>
                    </tr>

                    <!-- AKTIVITAS PENDANAAN -->
                    <tr class="table-primary">
                        <td colspan="2"><strong>ARUS KAS DARI AKTIVITAS PENDANAAN</strong></td>
                    </tr>
                    <?php if ($kas_dari_modal > 0): ?>
                    <tr>
                        <td style="padding-left: 20px;">Penerimaan dari Modal/Pinjaman</td>
                        <td class="text-end text-success"><?php echo number_format($kas_dari_modal, 0, ',', '.'); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($kas_untuk_bayar_hutang > 0): ?>
                    <tr>
                        <td style="padding-left: 20px;">Pembayaran Hutang/Pinjaman</td>
                        <td class="text-end text-danger">(<?php echo number_format($kas_untuk_bayar_hutang, 0, ',', '.'); ?>)</td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($kas_untuk_bunga > 0): ?>
                    <tr>
                        <td style="padding-left: 20px;">Pembayaran Beban Bunga</td>
                        <td class="text-end text-danger">(<?php echo number_format($kas_untuk_bunga, 0, ',', '.'); ?>)</td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($kas_dari_modal == 0 && $kas_untuk_bayar_hutang == 0 && $kas_untuk_bunga == 0): ?>
                    <tr>
                        <td style="padding-left: 20px;" class="text-muted">Tidak ada transaksi pendanaan</td>
                        <td class="text-end">0</td>
                    </tr>
                    <?php endif; ?>
                    <tr class="table-warning">
                        <td class="text-end"><strong>Kas Bersih dari Aktivitas Pendanaan</strong></td>
                        <td class="text-end <?php echo $kas_bersih_pendanaan < 0 ? 'text-danger' : 'text-success'; ?>">
                            <strong><?php echo number_format($kas_bersih_pendanaan, 0, ',', '.'); ?></strong>
                        </td>
                    </tr>

                    <!-- KENAIKAN/PENURUNAN KAS -->
                    <tr class="table-info">
                        <td class="text-end"><strong>Kenaikan (Penurunan) Kas Bersih</strong></td>
                        <td class="text-end <?php echo $kenaikan_kas < 0 ? 'text-danger' : 'text-success'; ?>">
                            <strong><?php echo number_format($kenaikan_kas, 0, ',', '.'); ?></strong>
                        </td>
                    </tr>

                    <!-- SALDO KAS AKHIR -->
                    <tr class="table-success">
                        <td><strong>Saldo Kas Akhir Periode</strong></td>
                        <td class="text-end"><strong><?php echo number_format($saldo_kas_akhir, 0, ',', '.'); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Validasi -->
        <?php if (abs($saldo_kas_akhir - $saldo_kas_akhir_aktual) < 10): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i> 
            <strong>Validasi OK!</strong> Saldo kas akhir sesuai dengan perhitungan.
        </div>
        <?php else: ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> 
            <strong>Perbedaan Saldo:</strong><br>
            Saldo Kas Akhir (Laporan): Rp <?php echo number_format($saldo_kas_akhir, 0, ',', '.'); ?><br>
            Saldo Kas Akhir (Aktual): Rp <?php echo number_format($saldo_kas_akhir_aktual, 0, ',', '.'); ?><br>
            Selisih: Rp <?php echo number_format(abs($saldo_kas_akhir - $saldo_kas_akhir_aktual), 0, ',', '.'); ?>
        </div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <small class="text-muted">Aktivitas Operasi</small>
                        <h5 class="<?php echo $kas_bersih_operasi < 0 ? 'text-danger' : 'text-success'; ?> mb-0">
                            Rp <?php echo number_format($kas_bersih_operasi, 0, ',', '.'); ?>
                        </h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <small class="text-muted">Aktivitas Investasi</small>
                        <h5 class="<?php echo $kas_bersih_investasi < 0 ? 'text-danger' : 'text-success'; ?> mb-0">
                            Rp <?php echo number_format($kas_bersih_investasi, 0, ',', '.'); ?>
                        </h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <small class="text-muted">Aktivitas Pendanaan</small>
                        <h5 class="<?php echo $kas_bersih_pendanaan < 0 ? 'text-danger' : 'text-success'; ?> mb-0">
                            Rp <?php echo number_format($kas_bersih_pendanaan, 0, ',', '.'); ?>
                        </h5>
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