<?php
/**
 * LAPORAN BUKU BESAR (General Ledger)
 * Menampilkan mutasi detail per akun dalam periode tertentu
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
 * Fungsi untuk menghitung saldo awal akun (sebelum periode)
 */
function getSaldoAwal($kode_akun, $tgl_awal) {
    $akun = fetchOne("SELECT jenis_mutasi FROM chart_of_accounts WHERE kode_akun = ?", [$kode_akun]);
    if (!$akun) return 0;
    
    $jenis_mutasi = $akun['jenis_mutasi'];
    
    // Hitung saldo dari semua transaksi sebelum tanggal awal
    $sql = "
        SELECT 
            COALESCE(SUM(CASE WHEN rekening_debet = ? THEN jumlah ELSE 0 END), 0) as total_debet,
            COALESCE(SUM(CASE WHEN rekening_kredit = ? THEN jumlah ELSE 0 END), 0) as total_kredit
        FROM transaksi
        WHERE tgl_transaksi < ?
    ";
    
    $result = fetchOne($sql, [$kode_akun, $kode_akun, $tgl_awal]);
    
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
 * Get transaksi dalam periode
 */
function getTransaksiAkun($kode_akun, $tgl_awal, $tgl_akhir) {
    $sql = "
        SELECT 
            t.id,
            t.tgl_transaksi,
            t.rekening_debet,
            t.rekening_kredit,
            t.keterangan_transaksi,
            t.jumlah,
            CASE 
                WHEN t.rekening_debet = ? THEN 'Debet'
                WHEN t.rekening_kredit = ? THEN 'Kredit'
            END as posisi
        FROM transaksi t
        WHERE (t.rekening_debet = ? OR t.rekening_kredit = ?)
        AND t.tgl_transaksi >= ? 
        AND t.tgl_transaksi <= ?
        ORDER BY t.tgl_transaksi, t.id
    ";
    
    return fetchAll($sql, [$kode_akun, $kode_akun, $kode_akun, $kode_akun, $tgl_awal, $tgl_akhir]);
}

// Ambil semua akun yang memiliki transaksi dalam periode
$sql_akun_ada_transaksi = "
    SELECT DISTINCT c.kode_akun, c.nama_akun, c.jenis_mutasi
    FROM chart_of_accounts c
    WHERE c.status = 'Aktif' AND c.lev4 > 0
    AND EXISTS (
        SELECT 1 FROM transaksi t 
        WHERE (t.rekening_debet = c.kode_akun OR t.rekening_kredit = c.kode_akun)
        AND t.tgl_transaksi >= ? AND t.tgl_transaksi <= ?
    )
    ORDER BY c.kode_akun
";

$akun_list = fetchAll($sql_akun_ada_transaksi, [$tgl_awal, $tgl_akhir]);

$buku_besar_data = [];

foreach ($akun_list as $akun) {
    $saldo_awal = getSaldoAwal($akun['kode_akun'], $tgl_awal);
    $transaksi_list = getTransaksiAkun($akun['kode_akun'], $tgl_awal, $tgl_akhir);
    
    $buku_besar_data[] = [
        'akun' => $akun,
        'saldo_awal' => $saldo_awal,
        'transaksi' => $transaksi_list
    ];
}
?>

<div class="card">
    <div class="card-header bg-warning">
        <h4 class="mb-0"><i class="bi bi-book"></i> BUKU BESAR (General Ledger)</h4>
    </div>
    <div class="card-body">
        <!-- Header Laporan -->
        <div class="text-center mb-4">
            <h5 class="mb-0"><strong>RUMAH MAKAN ANDA</strong></h5>
            <h6>BUKU BESAR</h6>
            <p class="mb-0">Periode: <?php echo $bulan_nama[$bulan]; ?> <?php echo $tahun; ?></p>
            <p class="mb-0 text-muted small">(<?php echo date('d/m/Y', strtotime($tgl_awal)); ?> s/d <?php echo date('d/m/Y', strtotime($tgl_akhir)); ?>)</p>
        </div>

        <?php if (empty($buku_besar_data)): ?>
        <!-- Jika tidak ada data -->
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> 
            Tidak ada transaksi dalam periode ini. Silakan pilih periode lain.
        </div>
        
        <?php else: ?>
        <!-- Tampilan Semua Akun -->
        <?php foreach ($buku_besar_data as $data): ?>
        <?php 
        $akun = $data['akun'];
        $saldo_awal = $data['saldo_awal'];
        $transaksi = $data['transaksi'];
        $saldo_berjalan = $saldo_awal;
        
        // Hitung total debet dan kredit
        $total_debet = 0;
        $total_kredit = 0;
        foreach ($transaksi as $trx) {
            if ($trx['rekening_debet'] == $akun['kode_akun']) {
                $total_debet += $trx['jumlah'];
            }
            if ($trx['rekening_kredit'] == $akun['kode_akun']) {
                $total_kredit += $trx['jumlah'];
            }
        }
        
        // Hitung saldo akhir
        if ($akun['jenis_mutasi'] == 'Debet') {
            $saldo_akhir = $saldo_awal + $total_debet - $total_kredit;
        } else {
            $saldo_akhir = $saldo_awal - $total_debet + $total_kredit;
        }
        ?>
        
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-secondary text-white print-show">
                <div class="row">
                    <div class="col-md-8">
                        <strong><?php echo $akun['kode_akun']; ?> - <?php echo $akun['nama_akun']; ?></strong>
                    </div>
                    <div class="col-md-4 text-end">
                        <small>Jenis Mutasi: <span class="badge bg-light text-dark"><?php echo $akun['jenis_mutasi']; ?></span></small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="12%">Tanggal</th>
                                <th width="8%">ID</th>
                                <th width="40%">Keterangan</th>
                                <th width="13%" class="text-end">Debet</th>
                                <th width="13%" class="text-end">Kredit</th>
                                <th width="14%" class="text-end">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Saldo Awal -->
                            <tr class="table-info">
                                <td colspan="3"><strong>Saldo Awal</strong></td>
                                <td class="text-end">-</td>
                                <td class="text-end">-</td>
                                <td class="text-end"><strong><?php echo number_format($saldo_awal, 0, ',', '.'); ?></strong></td>
                            </tr>
                            
                            <?php foreach ($transaksi as $trx): ?>
                            <?php
                            $debet = ($trx['posisi'] == 'Debet') ? $trx['jumlah'] : 0;
                            $kredit = ($trx['posisi'] == 'Kredit') ? $trx['jumlah'] : 0;
                            
                            if ($akun['jenis_mutasi'] == 'Debet') {
                                $saldo_berjalan = $saldo_berjalan + $debet - $kredit;
                            } else {
                                $saldo_berjalan = $saldo_berjalan - $debet + $kredit;
                            }
                            ?>
                            <tr>
                                <td><small><?php echo date('d/m/Y', strtotime($trx['tgl_transaksi'])); ?></small></td>
                                <td class="text-center"><small><?php echo $trx['id']; ?></small></td>
                                <td><small><?php echo $trx['keterangan_transaksi']; ?></small></td>
                                <td class="text-end"><small><?php echo $debet > 0 ? number_format($debet, 0, ',', '.') : '-'; ?></small></td>
                                <td class="text-end"><small><?php echo $kredit > 0 ? number_format($kredit, 0, ',', '.') : '-'; ?></small></td>
                                <td class="text-end <?php echo $saldo_berjalan < 0 ? 'text-danger' : ''; ?>">
                                    <small><?php echo number_format($saldo_berjalan, 0, ',', '.'); ?></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <!-- Saldo Akhir -->
                            <tr class="table-success">
                                <td colspan="3" class="text-end"><strong>Saldo Akhir</strong></td>
                                <td class="text-end"><strong><?php echo number_format($total_debet, 0, ',', '.'); ?></strong></td>
                                <td class="text-end"><strong><?php echo number_format($total_kredit, 0, ',', '.'); ?></strong></td>
                                <td class="text-end <?php echo $saldo_akhir < 0 ? 'text-danger' : 'text-success'; ?>">
                                    <strong><?php echo number_format($saldo_akhir, 0, ',', '.'); ?></strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Footer untuk setiap akun -->
            <div class="card-footer bg-white">
                <div class="row mt-3">
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
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
@media print {
    /* Pastikan header akun tetap tampil */
    .card-header.print-show {
        display: block !important;
        background-color: #6c757d !important;
        color: white !important;
        padding: 10px 15px !important;
        border-bottom: 2px solid #000 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
        page-break-inside: avoid;
    }
    
    .card-body {
        padding: 10px !important;
    }
    
    /* Setiap akun di halaman baru */
    .card.mb-4 {
        page-break-after: always;
    }
    
    /* Footer akun tetap tampil di print */
    .card-footer {
        display: block !important;
        padding: 20px 15px !important;
        border-top: 1px solid #ddd !important;
    }
    
    /* Pastikan badge tetap terlihat */
    .badge {
        border: 1px solid #000 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>