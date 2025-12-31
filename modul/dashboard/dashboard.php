<?php
/**
 * DASHBOARD ADMIN ENHANCED
 * Dashboard dengan grafik, statistik lengkap menggunakan sistem akuntansi
 * HANYA MENGGUNAKAN: chart_of_accounts, transaksi, saldo
 */

// Cek apakah user admin atau karyawan, load dashboard sesuai role
if ($_SESSION['role'] != 'admin') {
    include 'dashboard_karyawan.php';
    exit;
}

// Query untuk statistik dashboard
$today = date('Y-m-d');
$bulan_ini = date('Y-m');
$tahun_ini = date('Y');

// ============================================
// STATISTIK HARI INI (Dari Transaksi)
// ============================================

// PENDAPATAN HARI INI (Akun 4.x - Kredit)
$pendapatan_today = fetchOne("
    SELECT COALESCE(SUM(jumlah), 0) as total
    FROM transaksi t
    JOIN chart_of_accounts c ON t.rekening_kredit = c.kode_akun
    WHERE DATE(t.tgl_transaksi) = ?
    AND c.lev1 = 4
", [$today]);

// BEBAN HARI INI (Akun 5.x - Debet)
$beban_today = fetchOne("
    SELECT COALESCE(SUM(jumlah), 0) as total
    FROM transaksi t
    JOIN chart_of_accounts c ON t.rekening_debet = c.kode_akun
    WHERE DATE(t.tgl_transaksi) = ?
    AND c.lev1 = 5
", [$today]);

// JUMLAH TRANSAKSI HARI INI
$jumlah_transaksi_today = fetchOne("
    SELECT COUNT(DISTINCT id) as total
    FROM transaksi
    WHERE DATE(tgl_transaksi) = ?
", [$today]);

$pendapatan_hari_ini = $pendapatan_today['total'];
$beban_hari_ini = $beban_today['total'];
$surplus_hari_ini = $pendapatan_hari_ini - $beban_hari_ini;
$jumlah_trx_hari_ini = $jumlah_transaksi_today['total'];

// Rata-rata per transaksi
$rata_rata_transaksi = $jumlah_trx_hari_ini > 0 ? $pendapatan_hari_ini / $jumlah_trx_hari_ini : 0;

// SALDO KAS TERKINI (Akun 1.1.01.01 - Kas Tunai)
$saldo_kas = fetchOne("
    SELECT 
        COALESCE(SUM(
            CASE 
                WHEN t.rekening_debet = '1.1.01.01' THEN t.jumlah
                WHEN t.rekening_kredit = '1.1.01.01' THEN -t.jumlah
                ELSE 0
            END
        ), 0) as saldo
    FROM transaksi t
");
$saldo_kas_terkini = $saldo_kas['saldo'];

// Pemasukan Kas dan Pengeluaran Kas Hari Ini
$kas_today = fetchOne("
    SELECT 
        COALESCE(SUM(CASE WHEN t.rekening_debet LIKE '1.1.%' THEN t.jumlah ELSE 0 END), 0) as pemasukan,
        COALESCE(SUM(CASE WHEN t.rekening_kredit LIKE '1.1.%' THEN t.jumlah ELSE 0 END), 0) as pengeluaran
    FROM transaksi t
    WHERE DATE(t.tgl_transaksi) = ?
", [$today]);

// ============================================
// STATISTIK BULAN INI (Dari Saldo)
// ============================================

$bulan_num = date('m');
$tahun_num = date('Y');

// Pendapatan bulan ini dari Saldo
$pendapatan_bulan = fetchOne("
    SELECT COALESCE(SUM(kredit), 0) as total
    FROM saldo
    WHERE tahun = ? 
    AND bulan = ?
    AND kode_akun LIKE '4.%'
", [$tahun_num, $bulan_num]);

// Beban bulan ini dari Saldo
$beban_bulan = fetchOne("
    SELECT COALESCE(SUM(debet), 0) as total
    FROM saldo
    WHERE tahun = ? 
    AND bulan = ?
    AND kode_akun LIKE '5.%'
", [$tahun_num, $bulan_num]);

// Jumlah transaksi bulan ini
$jumlah_trx_bulan = fetchOne("
    SELECT COUNT(*) as total
    FROM transaksi
    WHERE YEAR(tgl_transaksi) = ?
    AND MONTH(tgl_transaksi) = ?
", [$tahun_num, $bulan_num]);

$pendapatan_bulan_ini = $pendapatan_bulan['total'];
$beban_bulan_ini = $beban_bulan['total'];
$surplus_bulan_ini = $pendapatan_bulan_ini - $beban_bulan_ini;

// ============================================
// PERBANDINGAN DENGAN KEMARIN
// ============================================

$yesterday = date('Y-m-d', strtotime('-1 day'));

$pendapatan_yesterday = fetchOne("
    SELECT COALESCE(SUM(jumlah), 0) as total
    FROM transaksi t
    JOIN chart_of_accounts c ON t.rekening_kredit = c.kode_akun
    WHERE DATE(t.tgl_transaksi) = ?
    AND c.lev1 = 4
", [$yesterday]);

$beban_yesterday = fetchOne("
    SELECT COALESCE(SUM(jumlah), 0) as total
    FROM transaksi t
    JOIN chart_of_accounts c ON t.rekening_debet = c.kode_akun
    WHERE DATE(t.tgl_transaksi) = ?
    AND c.lev1 = 5
", [$yesterday]);

$jumlah_trx_yesterday = fetchOne("
    SELECT COUNT(*) as total
    FROM transaksi
    WHERE DATE(tgl_transaksi) = ?
", [$yesterday]);

// Hitung persentase perubahan
function hitungPerubahan($nilai_sekarang, $nilai_kemarin) {
    if ($nilai_kemarin == 0) return $nilai_sekarang > 0 ? 100 : 0;
    return (($nilai_sekarang - $nilai_kemarin) / $nilai_kemarin) * 100;
}

$perubahan_transaksi = hitungPerubahan($jumlah_trx_hari_ini, $jumlah_trx_yesterday['total']);
$perubahan_pendapatan = hitungPerubahan($pendapatan_hari_ini, $pendapatan_yesterday['total']);
$surplus_yesterday = $pendapatan_yesterday['total'] - $beban_yesterday['total'];
$perubahan_surplus = hitungPerubahan($surplus_hari_ini, $surplus_yesterday);

// ============================================
// DATA UNTUK GRAFIK - 12 BULAN TERAKHIR (DARI TABEL SALDO)
// ============================================

$data_12_bulan = [];
$bulan_sekarang = date('n'); // 1-12
$tahun_sekarang = date('Y');

for ($i = 11; $i >= 0; $i--) {
    // Hitung bulan dan tahun mundur
    $bulan_target = $bulan_sekarang - $i;
    $tahun_target = $tahun_sekarang;
    
    // Jika bulan negatif, mundur ke tahun sebelumnya
    if ($bulan_target <= 0) {
        $bulan_target += 12;
        $tahun_target--;
    }
    
    $bulan_str = str_pad($bulan_target, 2, '0', STR_PAD_LEFT);
    
    // Query pendapatan (akun 4.x.xx.xx) - SUM kredit
    $pendapatan = fetchOne("
        SELECT COALESCE(SUM(kredit), 0) as total
        FROM saldo
        WHERE tahun = ? 
        AND bulan = ?
        AND kode_akun LIKE '4.%'
    ", [$tahun_target, $bulan_str]);
    
    // Query pengeluaran (akun 5.x.xx.xx) - SUM debet
    $pengeluaran = fetchOne("
        SELECT COALESCE(SUM(debet), 0) as total
        FROM saldo
        WHERE tahun = ? 
        AND bulan = ?
        AND kode_akun LIKE '5.%'
    ", [$tahun_target, $bulan_str]);
    
    $total_pendapatan = $pendapatan ? $pendapatan['total'] : 0;
    $total_pengeluaran = $pengeluaran ? $pengeluaran['total'] : 0;
    $surplus = $total_pendapatan - $total_pengeluaran;
    
    // Nama bulan dalam bahasa Indonesia
    $nama_bulan = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];
    
    $data_12_bulan[] = [
        'bulan' => $nama_bulan[$bulan_target],
        'bulan_tahun' => $nama_bulan[$bulan_target] . ' ' . substr($tahun_target, 2),
        'pendapatan' => $total_pendapatan,
        'pengeluaran' => $total_pengeluaran,
        'surplus' => $surplus,
        'tahun' => $tahun_target,
        'bulan_num' => $bulan_target
    ];
}

// ============================================
// TOP 5 AKUN PENDAPATAN HARI INI
// ============================================

$top_pendapatan_today = fetchAll("
    SELECT 
        c.kode_akun,
        c.nama_akun,
        COUNT(*) as jumlah_transaksi,
        SUM(t.jumlah) as total_pendapatan
    FROM transaksi t
    JOIN chart_of_accounts c ON t.rekening_kredit = c.kode_akun
    WHERE DATE(t.tgl_transaksi) = ?
    AND c.lev1 = 4
    AND c.lev4 > 0
    GROUP BY c.kode_akun, c.nama_akun
    ORDER BY total_pendapatan DESC
    LIMIT 5
", [$today]);

// ============================================
// TRANSAKSI TERAKHIR HARI INI
// ============================================

$transaksi_terakhir = fetchAll("
    SELECT 
        t.*,
        cd.nama_akun as akun_debet,
        ck.nama_akun as akun_kredit,
        u.nama_lengkap as user_name
    FROM transaksi t
    LEFT JOIN chart_of_accounts cd ON t.rekening_debet = cd.kode_akun
    LEFT JOIN chart_of_accounts ck ON t.rekening_kredit = ck.kode_akun
    LEFT JOIN users u ON t.id_user = u.id
    WHERE DATE(t.tgl_transaksi) = ?
    ORDER BY t.created_at DESC
    LIMIT 10
", [$today]);

// ============================================
// DISTRIBUSI TRANSAKSI BERDASARKAN JENIS
// ============================================

$distribusi_transaksi = fetchAll("
    SELECT 
        CASE 
            WHEN c.lev1 = 4 THEN 'Pendapatan'
            WHEN c.lev1 = 5 THEN 'Beban'
            WHEN c.lev1 = 1 THEN 'Kas/Aset'
            WHEN c.lev1 = 2 THEN 'Utang'
            ELSE 'Lainnya'
        END as jenis,
        COUNT(*) as jumlah,
        SUM(t.jumlah) as total
    FROM transaksi t
    JOIN chart_of_accounts c ON t.rekening_kredit = c.kode_akun OR t.rekening_debet = c.kode_akun
    WHERE DATE(t.tgl_transaksi) = ?
    GROUP BY jenis
    ORDER BY total DESC
", [$today]);

// ============================================
// SALDO KAS DAN BANK
// ============================================

$saldo_kas_bank = fetchAll("
    SELECT 
        c.kode_akun,
        c.nama_akun,
        COALESCE(SUM(
            CASE 
                WHEN t.rekening_debet = c.kode_akun THEN t.jumlah
                WHEN t.rekening_kredit = c.kode_akun THEN -t.jumlah
                ELSE 0
            END
        ), 0) as saldo
    FROM chart_of_accounts c
    LEFT JOIN transaksi t ON (t.rekening_debet = c.kode_akun OR t.rekening_kredit = c.kode_akun)
    WHERE c.lev1 = 1 AND c.lev2 = 1 AND c.lev4 > 0
    GROUP BY c.kode_akun, c.nama_akun
    ORDER BY c.kode_akun
");
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="mb-1"><i class="bi bi-speedometer2"></i> Dashboard </h2>
            <p class="text-muted mb-0">Selamat datang, <strong><?php echo $_SESSION['nama_lengkap']; ?></strong>! 🎉</p>
            <p class="text-muted"><i class="bi bi-calendar3"></i> <?php echo formatTanggal($today, 'l, d F Y'); ?></p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group" role="group">
                <a href="index.php?page=buat_transaksi" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> POS
                </a>
                <a href="index.php?page=tambah_transaksi_kas" class="btn btn-outline-primary">
                    <i class="bi bi-plus-circle"></i> Transaksi Umum
                </a>
                <a href="index.php?page=laporan_kas" class="btn btn-outline-danger">
                    <i class="bi bi-file-text"></i> Laporan
                </a>
            </div>
        </div>
    </div>

    <!-- Statistik Cards Utama -->
    <div class="row mb-4">
        <!-- Transaksi Hari Ini -->
        <div class="col-md-3 mb-3">
            <div class="card dashboard-card-modern card-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small">Transaksi Hari Ini</p>
                            <h2 class="mb-0 fw-bold"><?php echo $jumlah_trx_hari_ini; ?></h2>
                            <div class="mt-2">
                                <?php if ($perubahan_transaksi != 0): ?>
                                <span class="badge bg-<?php echo $perubahan_transaksi >= 0 ? 'success' : 'danger'; ?> badge-sm">
                                    <i class="bi bi-<?php echo $perubahan_transaksi >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                    <?php echo abs(number_format($perubahan_transaksi, 1)); ?>%
                                </span>
                                <small class="text-muted ms-1">vs kemarin</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="icon-wrapper bg-primary">
                            <i class="bi bi-receipt text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pendapatan Hari Ini -->
        <div class="col-md-3 mb-3">
            <div class="card dashboard-card-modern card-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small">Pendapatan Hari Ini</p>
                            <h2 class="mb-0 fw-bold text-success"><?php echo formatRupiah($pendapatan_hari_ini, true); ?></h2>
                            <div class="mt-2">
                                <?php if ($perubahan_pendapatan != 0): ?>
                                <span class="badge bg-<?php echo $perubahan_pendapatan >= 0 ? 'success' : 'danger'; ?> badge-sm">
                                    <i class="bi bi-<?php echo $perubahan_pendapatan >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                    <?php echo abs(number_format($perubahan_pendapatan, 1)); ?>%
                                </span>
                                <small class="text-muted ms-1">vs kemarin</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="icon-wrapper bg-success">
                            <i class="bi bi-cash-stack text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Surplus/Defisit Hari Ini -->
        <div class="col-md-3 mb-3">
            <div class="card dashboard-card-modern card-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small"><?php echo $surplus_hari_ini >= 0 ? 'Surplus' : 'Defisit'; ?> Hari Ini</p>
                            <h2 class="mb-0 fw-bold <?php echo $surplus_hari_ini >= 0 ? 'text-info' : 'text-danger'; ?>">
                                <?php echo formatRupiah(abs($surplus_hari_ini), true); ?>
                            </h2>
                            <div class="mt-2">
                                <?php 
                                $margin = $pendapatan_hari_ini > 0 
                                    ? ($surplus_hari_ini / $pendapatan_hari_ini) * 100 
                                    : 0;
                                ?>
                                <span class="badge bg-<?php echo $surplus_hari_ini >= 0 ? 'info' : 'danger'; ?> badge-sm">
                                    Margin: <?php echo number_format($margin, 1); ?>%
                                </span>
                            </div>
                        </div>
                        <div class="icon-wrapper bg-info">
                            <i class="bi bi-graph-up-arrow text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Saldo Kas -->
        <div class="col-md-3 mb-3">
            <div class="card dashboard-card-modern card-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small">Saldo Kas Tunai</p>
                            <h2 class="mb-0 fw-bold text-warning"><?php echo formatRupiah($saldo_kas_terkini, true); ?></h2>
                            <div class="mt-2">
                                <small class="text-success">
                                    <i class="bi bi-arrow-down-circle"></i> <?php echo formatRupiah($kas_today['pemasukan'], true); ?>
                                </small>
                                <small class="text-danger ms-2">
                                    <i class="bi bi-arrow-up-circle"></i> <?php echo formatRupiah($kas_today['pengeluaran'], true); ?>
                                </small>
                            </div>
                        </div>
                        <div class="icon-wrapper bg-warning">
                            <i class="bi bi-wallet2 text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik dan Statistik -->
    <div class="row mb-4">
        <!-- Grafik 12 Bulan Terakhir -->
        <div class="col-md-8 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-graph-up"></i> Trend Keuangan 12 Bulan Terakhir</h5>
                    <div class="text-muted small">
                        <i class="bi bi-info-circle"></i> Data dari tabel Saldo
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="chartPendapatan" height="80"></canvas>
                    
                    <!-- Legend Custom -->
                    <div class="chart-legend mt-3">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="legend-item">
                                    <span class="legend-color" style="background: rgba(18, 154, 125, 1);"></span>
                                    <span class="legend-label">Pendapatan</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="legend-item">
                                    <span class="legend-color" style="background: rgba(252, 188, 188, 1);"></span>
                                    <span class="legend-label">Beban</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="legend-item">
                                    <span class="legend-color" style="background: rgba(160, 220, 194, 0.3); border: 2px solid rgba(160, 220, 194, 1);"></span>
                                    <span class="legend-label">Surplus/Defisit</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistik Bulan Ini -->
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-calendar-month"></i> Bulan Ini</h5>
                </div>
                <div class="card-body">
                    <div class="stat-item mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Total Transaksi</span>
                            <h4 class="mb-0 text-primary"><?php echo $jumlah_trx_bulan['total']; ?></h4>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-primary" style="width: 100%"></div>
                        </div>
                    </div>

                    <div class="stat-item mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Total Pendapatan</span>
                            <h4 class="mb-0 text-success"><?php echo formatRupiah($pendapatan_bulan_ini, true); ?></h4>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: 100%"></div>
                        </div>
                    </div>

                    <div class="stat-item mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Total Beban</span>
                            <h4 class="mb-0 text-danger"><?php echo formatRupiah($beban_bulan_ini, true); ?></h4>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-danger" style="width: 100%"></div>
                        </div>
                    </div>

                    <div class="stat-item">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted"><?php echo $surplus_bulan_ini >= 0 ? 'Surplus' : 'Defisit'; ?></span>
                            <h4 class="mb-0 <?php echo $surplus_bulan_ini >= 0 ? 'text-info' : 'text-warning'; ?>">
                                <?php echo formatRupiah(abs($surplus_bulan_ini), true); ?>
                            </h4>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-<?php echo $surplus_bulan_ini >= 0 ? 'info' : 'warning'; ?>" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row Kedua -->
    <div class="row mb-4">
        <!-- Top 5 Akun Pendapatan Hari Ini -->
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header bg-gradient-primary">
                    <h5 class="mb-0 text-white"><i class="bi bi-trophy-fill"></i> Top 5 Akun Pendapatan Hari Ini</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($top_pendapatan_today)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mb-0 mt-2">Belum ada pendapatan hari ini</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php 
                            $rank = 1;
                            foreach ($top_pendapatan_today as $akun): 
                            ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="rank-badge rank-<?php echo $rank; ?> me-3">
                                            <?php echo $rank; ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo $akun['nama_akun']; ?></h6>
                                            <small class="text-muted">
                                                <span class="badge bg-secondary badge-sm"><?php echo $akun['kode_akun']; ?></span>
                                                <span class="ms-1"><?php echo $akun['jumlah_transaksi']; ?>x transaksi</span>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-success"><?php echo formatRupiah($akun['total_pendapatan'], true); ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php 
                            $rank++;
                            endforeach; 
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Saldo Kas dan Bank -->
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header bg-gradient-success">
                    <h5 class="mb-0 text-white"><i class="bi bi-wallet2"></i> Saldo Kas & Bank</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($saldo_kas_bank)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mb-0 mt-2">Data tidak tersedia</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($saldo_kas_bank as $kas): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0"><?php echo $kas['nama_akun']; ?></h6>
                                        <small class="text-muted">
                                            <span class="badge bg-secondary badge-sm"><?php echo $kas['kode_akun']; ?></span>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold <?php echo $kas['saldo'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo formatRupiah(abs($kas['saldo']), true); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Total Kas & Bank -->
                        <div class="card-footer bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>Total Kas & Bank:</strong>
                                <strong class="text-primary">
                                    <?php 
                                    $total_kas_bank = array_sum(array_column($saldo_kas_bank, 'saldo'));
                                    echo formatRupiah($total_kas_bank, true); 
                                    ?>
                                </strong>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaksi Terakhir -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Transaksi Terakhir Hari Ini</h5>
                    <a href="index.php?page=list_transaksi" class="btn btn-sm btn-outline-primary">
                        Lihat Semua <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($transaksi_terakhir)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mb-0 mt-2">Belum ada transaksi hari ini</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="10%">ID</th>
                                        <th width="12%">Tanggal</th>
                                        <th width="20%">Debet</th>
                                        <th width="20%">Kredit</th>
                                        <th width="25%">Keterangan</th>
                                        <th width="13%" class="text-end">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transaksi_terakhir as $trx): ?>
                                    <tr>
                                        <td><small class="text-primary fw-bold">#<?php echo $trx['id']; ?></small></td>
                                        <td><small><?php echo date('d/m H:i', strtotime($trx['tgl_transaksi'])); ?></small></td>
                                        <td>
                                            <small class="text-muted"><?php echo $trx['rekening_debet']; ?></small><br>
                                            <small><?php echo $trx['akun_debet']; ?></small>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo $trx['rekening_kredit']; ?></small><br>
                                            <small><?php echo $trx['akun_kredit']; ?></small>
                                        </td>
                                        <td><small><?php echo substr($trx['keterangan_transaksi'], 0, 50); ?><?php echo strlen($trx['keterangan_transaksi']) > 50 ? '...' : ''; ?></small></td>
                                        <td class="text-end fw-bold"><?php echo formatRupiah($trx['jumlah']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// ============================================
// GRAFIK 12 BULAN KEUANGAN
// ============================================

// Data untuk grafik 12 bulan
const dataGrafik = <?php echo json_encode($data_12_bulan); ?>;

console.log('Data 12 Bulan:', dataGrafik); // Debug

// Validasi data
if (!dataGrafik || dataGrafik.length === 0) {
    console.error('Data grafik kosong atau tidak valid');
    document.getElementById('chartPendapatan').style.display = 'none';
    const container = document.querySelector('#chartPendapatan').parentElement;
    container.innerHTML = `
        <div class="alert alert-warning text-center">
            <i class="bi bi-exclamation-triangle"></i>
            <p class="mb-0 mt-2">Data saldo belum tersedia. Silakan generate saldo terlebih dahulu.</p>
            <a href="index.php?page=generate_saldo" class="btn btn-sm btn-warning mt-2">
                <i class="bi bi-arrow-repeat"></i> Generate Saldo
            </a>
        </div>
    `;
} else {
    // Create Chart
    const ctx = document.getElementById('chartPendapatan');
    
    const chartKeuangan = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dataGrafik.map(d => d.bulan_tahun),
            datasets: [
                {
                    label: 'Pendapatan',
                    data: dataGrafik.map(d => d.pendapatan),
                    borderColor: 'rgba(18, 154, 125, 1)',
                    backgroundColor: 'rgba(18, 154, 125, 0.1)',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: 'rgba(18, 154, 125, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 7
                },
                {
                    label: 'Beban',
                    data: dataGrafik.map(d => d.pengeluaran),
                    borderColor: 'rgba(252, 188, 188, 1)',
                    backgroundColor: 'rgba(252, 188, 188, 0.1)',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: 'rgba(252, 188, 188, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 7
                },
                {
                    label: 'Surplus/Defisit',
                    data: dataGrafik.map(d => d.surplus),
                    borderColor: 'rgba(160, 220, 194, 1)',
                    backgroundColor: 'rgba(160, 220, 194, 0)', // Transparan
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: 'rgba(160, 220, 194, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 7
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: false // Kita pakai custom legend
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 15,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    bodySpacing: 6,
                    callbacks: {
                        title: function(context) {
                            return context[0].label;
                        },
                        label: function(context) {
                            let label = context.dataset.label || '';
                            let value = context.parsed.y;
                            
                            if (label === 'Surplus/Defisit') {
                                label = value >= 0 ? 'Surplus' : 'Defisit';
                            }
                            
                            return label + ': Rp ' + Math.abs(value).toLocaleString('id-ID');
                        },
                        afterBody: function(context) {
                            // Tambahan info margin
                            const index = context[0].dataIndex;
                            const data = dataGrafik[index];
                            
                            if (data.pendapatan > 0) {
                                const margin = ((data.surplus / data.pendapatan) * 100).toFixed(1);
                                return '\nMargin: ' + margin + '%';
                            }
                            return '';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) {
                                return 'Rp ' + (value / 1000000).toFixed(1) + 'Jt';
                            } else if (value >= 1000) {
                                return 'Rp ' + (value / 1000).toFixed(0) + 'K';
                            }
                            return 'Rp ' + value;
                        },
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });
}

// Auto refresh setiap 5 menit
setTimeout(function() {
    location.reload();
}, 300000);
</script>

<style>
/* ============================================
   DASHBOARD STYLES
   ============================================ */

/* Dashboard Card Modern */
.dashboard-card-modern {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    overflow: hidden;
}

.dashboard-card-modern:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
}

.dashboard-card-modern .icon-wrapper {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.dashboard-card-modern .card-body {
    padding: 1.5rem;
}

/* Badge Small */
.badge-sm {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}

/* Rank Badge */
.rank-badge {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.9rem;
    color: white;
}

.rank-badge.rank-1 {
    background: linear-gradient(135deg, #FFD700, #FFA500);
}

.rank-badge.rank-2 {
    background: linear-gradient(135deg, #C0C0C0, #808080);
}

.rank-badge.rank-3 {
    background: linear-gradient(135deg, #CD7F32, #8B4513);
}

.rank-badge.rank-4,
.rank-badge.rank-5 {
    background: linear-gradient(135deg, #129A7D, #0E6F57);
}

/* Gradient Headers */
.bg-gradient-primary {
    background: linear-gradient(135deg, #129A7D, #0E6F57);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #A0DCC2, #129A7D);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #FADAE2, #FCBCBC);
}

/* Quick Stat Item */
.quick-stat-item {
    padding: 12px;
    border-radius: 8px;
    background: rgba(18, 154, 125, 0.05);
    transition: all 0.3s ease;
}

.quick-stat-item:hover {
    background: rgba(18, 154, 125, 0.1);
    transform: translateX(4px);
}

/* List Group */
.list-group-item {
    border-left: none;
    border-right: none;
    transition: all 0.2s ease;
}

.list-group-item:first-child {
    border-top: none;
}

.list-group-item:hover {
    background: rgba(18, 154, 125, 0.05);
    border-left: 3px solid var(--primary-color);
}

/* Card Footer */
.card-footer {
    border-top: 1px solid rgba(0,0,0,0.05);
}

/* ============================================
   CHART LEGEND CUSTOM
   ============================================ */

.chart-legend {
    border-top: 1px solid rgba(0,0,0,0.05);
    padding-top: 15px;
}

.legend-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: rgba(0,0,0,0.02);
    border-radius: 6px;
    transition: all 0.2s ease;
}

.legend-item:hover {
    background: rgba(18, 154, 125, 0.1);
    transform: translateY(-2px);
}

.legend-color {
    width: 20px;
    height: 20px;
    border-radius: 4px;
    display: inline-block;
}

.legend-label {
    font-size: 0.85rem;
    font-weight: 500;
    color: #495057;
}

/* Chart Container Enhancement */
#chartPendapatan {
    max-height: 300px;
}

/* Loading state untuk chart */
.chart-loading {
    position: relative;
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chart-loading::after {
    content: "Memuat data...";
    color: #6c757d;
    font-size: 0.9rem;
}

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 768px) {
    .dashboard-card-modern .icon-wrapper {
        width: 48px;
        height: 48px;
        font-size: 20px;
    }
    
    .dashboard-card-modern h2 {
        font-size: 1.5rem;
    }
    
    #chartPendapatan {
        max-height: 250px;
    }
    
    .legend-item {
        font-size: 0.75rem;
        padding: 6px 8px;
    }
    
    .legend-color {
        width: 16px;
        height: 16px;
    }
}

/* ============================================
   ANIMATIONS
   ============================================ */

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dashboard-card-modern {
    animation: fadeInUp 0.5s ease-out;
}

.dashboard-card-modern:nth-child(1) { animation-delay: 0.1s; }
.dashboard-card-modern:nth-child(2) { animation-delay: 0.2s; }
.dashboard-card-modern:nth-child(3) { animation-delay: 0.3s; }
.dashboard-card-modern:nth-child(4) { animation-delay: 0.4s; }
</style>