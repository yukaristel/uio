<?php
/**
 * DASHBOARD ADMIN ENHANCED
 * Dashboard dengan grafik, statistik lengkap, dan visual menarik
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
// STATISTIK HARI INI
// ============================================

// Transaksi hari ini
$transaksi_today = fetchOne("
    SELECT 
        COUNT(*) as jumlah_transaksi,
        COALESCE(SUM(total_harga), 0) as total_pendapatan,
        COALESCE(SUM(total_modal), 0) as total_modal,
        COALESCE(SUM(total_keuntungan), 0) as total_keuntungan,
        COALESCE(AVG(total_harga), 0) as rata_rata_transaksi
    FROM transaksi_penjualan
    WHERE DATE(tanggal_transaksi) = ?
", [$today]);

// Saldo kas terkini
$saldo_kas = fetchOne("
    SELECT saldo_sesudah 
    FROM kas_umum 
    ORDER BY created_at DESC, id DESC 
    LIMIT 1
");
$saldo_kas_terkini = $saldo_kas ? $saldo_kas['saldo_sesudah'] : 0;

// Pemasukan dan pengeluaran hari ini
$kas_today = fetchOne("
    SELECT 
        COALESCE(SUM(CASE WHEN jenis_transaksi = 'masuk' THEN nominal ELSE 0 END), 0) as pemasukan,
        COALESCE(SUM(CASE WHEN jenis_transaksi = 'keluar' THEN nominal ELSE 0 END), 0) as pengeluaran
    FROM kas_umum
    WHERE DATE(tanggal_transaksi) = ?
", [$today]);

// Kerugian hari ini
$kerugian_today = fetchOne("
    SELECT COALESCE(SUM(total_nilai), 0) as total_kerugian
    FROM stock_movement
    WHERE jenis_pergerakan IN ('rusak', 'tumpah', 'expired', 'hilang')
    AND DATE(created_at) = ?
", [$today]);

// ============================================
// STATISTIK BULAN INI
// ============================================

$tanggal_awal_bulan = $bulan_ini . '-01';
$tanggal_akhir_bulan = date('Y-m-t', strtotime($tanggal_awal_bulan));

$transaksi_bulan = fetchOne("
    SELECT 
        COUNT(*) as jumlah_transaksi,
        COALESCE(SUM(total_harga), 0) as total_pendapatan,
        COALESCE(SUM(total_keuntungan), 0) as total_keuntungan
    FROM transaksi_penjualan
    WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?
", [$tanggal_awal_bulan, $tanggal_akhir_bulan]);

// ============================================
// PERBANDINGAN DENGAN KEMARIN
// ============================================

$yesterday = date('Y-m-d', strtotime('-1 day'));
$transaksi_yesterday = fetchOne("
    SELECT 
        COUNT(*) as jumlah_transaksi,
        COALESCE(SUM(total_harga), 0) as total_pendapatan,
        COALESCE(SUM(total_keuntungan), 0) as total_keuntungan
    FROM transaksi_penjualan
    WHERE DATE(tanggal_transaksi) = ?
", [$yesterday]);

// Hitung persentase perubahan
function hitungPerubahan($nilai_sekarang, $nilai_kemarin) {
    if ($nilai_kemarin == 0) return $nilai_sekarang > 0 ? 100 : 0;
    return (($nilai_sekarang - $nilai_kemarin) / $nilai_kemarin) * 100;
}

$perubahan_transaksi = hitungPerubahan($transaksi_today['jumlah_transaksi'], $transaksi_yesterday['jumlah_transaksi']);
$perubahan_pendapatan = hitungPerubahan($transaksi_today['total_pendapatan'], $transaksi_yesterday['total_pendapatan']);
$perubahan_keuntungan = hitungPerubahan($transaksi_today['total_keuntungan'], $transaksi_yesterday['total_keuntungan']);

// ============================================
// DATA UNTUK GRAFIK - 7 HARI TERAKHIR
// ============================================

$data_7_hari = [];
for ($i = 6; $i >= 0; $i--) {
    $tanggal = date('Y-m-d', strtotime("-$i days"));
    $data = fetchOne("
        SELECT 
            COALESCE(SUM(total_harga), 0) as pendapatan,
            COALESCE(SUM(total_keuntungan), 0) as keuntungan,
            COUNT(*) as transaksi
        FROM transaksi_penjualan
        WHERE DATE(tanggal_transaksi) = ?
    ", [$tanggal]);
    
    $data_7_hari[] = [
        'tanggal' => date('D', strtotime($tanggal)),
        'tanggal_lengkap' => date('d/m', strtotime($tanggal)),
        'pendapatan' => $data['pendapatan'],
        'keuntungan' => $data['keuntungan'],
        'transaksi' => $data['transaksi']
    ];
}

// ============================================
// TOP 5 MENU TERLARIS HARI INI
// ============================================

$top_menu_today = fetchAll("
    SELECT 
        m.nama_menu,
        k.nama_kategori,
        SUM(dt.jumlah) as total_terjual,
        SUM(dt.subtotal) as total_pendapatan
    FROM detail_transaksi dt
    JOIN transaksi_penjualan tp ON dt.transaksi_id = tp.id
    JOIN menu_makanan m ON dt.menu_id = m.id
    JOIN kategori_menu k ON m.kategori_id = k.id
    WHERE DATE(tp.tanggal_transaksi) = ?
    GROUP BY m.id, m.nama_menu, k.nama_kategori
    ORDER BY total_terjual DESC
    LIMIT 5
", [$today]);

// ============================================
// BAHAN STOK MENIPIS
// ============================================

$bahan_menipis = fetchAll("
    SELECT * FROM bahan_baku 
    WHERE stok_tersedia <= stok_minimum 
    ORDER BY stok_tersedia ASC 
    LIMIT 5
");

// ============================================
// TRANSAKSI TERAKHIR
// ============================================

$transaksi_terakhir = fetchAll("
    SELECT 
        tp.*,
        u.nama_lengkap as kasir
    FROM transaksi_penjualan tp
    LEFT JOIN users u ON tp.user_id = u.id
    WHERE DATE(tp.tanggal_transaksi) = ?
    ORDER BY tp.created_at DESC
    LIMIT 5
", [$today]);

// ============================================
// METODE PEMBAYARAN HARI INI
// ============================================

$metode_pembayaran = fetchAll("
    SELECT 
        metode_pembayaran,
        COUNT(*) as jumlah,
        SUM(total_harga) as total
    FROM transaksi_penjualan
    WHERE DATE(tanggal_transaksi) = ?
    GROUP BY metode_pembayaran
", [$today]);

// ============================================
// MENU STATUS
// ============================================

$menu_stats = fetchOne("
    SELECT 
        COUNT(*) as total_menu,
        SUM(CASE WHEN status = 'tersedia' THEN 1 ELSE 0 END) as menu_tersedia,
        SUM(CASE WHEN status = 'habis' THEN 1 ELSE 0 END) as menu_habis
    FROM menu_makanan
");

// ============================================
// STOCK OPNAME PENDING
// ============================================

$opname_pending_count = fetchOne("
    SELECT COUNT(*) as total 
    FROM stock_opname 
    WHERE status = 'draft'
")['total'];
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="mb-1"><i class="bi bi-speedometer2"></i> Dashboard Admin</h2>
            <p class="text-muted mb-0">Selamat datang, <strong><?php echo $_SESSION['nama_lengkap']; ?></strong>! 🎉</p>
            <p class="text-muted"><i class="bi bi-calendar3"></i> <?php echo formatTanggal($today, 'l, d F Y'); ?></p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group" role="group">
                <a href="index.php?page=buat_transaksi" class="btn btn-primary">
                    <i class="bi bi-cart-plus"></i> POS
                </a>
                <a href="index.php?page=laporan_bulanan" class="btn btn-outline-primary">
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
                            <h2 class="mb-0 fw-bold"><?php echo $transaksi_today['jumlah_transaksi']; ?></h2>
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
                            <h2 class="mb-0 fw-bold text-success"><?php echo formatRupiah($transaksi_today['total_pendapatan'], true); ?></h2>
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

        <!-- Keuntungan Bersih -->
        <div class="col-md-3 mb-3">
            <div class="card dashboard-card-modern card-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small">Keuntungan Bersih</p>
                            <h2 class="mb-0 fw-bold text-info"><?php echo formatRupiah($transaksi_today['total_keuntungan'], true); ?></h2>
                            <div class="mt-2">
                                <?php 
                                $margin = $transaksi_today['total_pendapatan'] > 0 
                                    ? ($transaksi_today['total_keuntungan'] / $transaksi_today['total_pendapatan']) * 100 
                                    : 0;
                                ?>
                                <span class="badge bg-info badge-sm">Margin: <?php echo number_format($margin, 1); ?>%</span>
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
                            <p class="text-muted mb-1 small">Saldo Kas Terkini</p>
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
        <!-- Grafik 7 Hari Terakhir -->
        <div class="col-md-8 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-graph-up"></i> Trend Pendapatan 7 Hari Terakhir</h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary active" onclick="toggleChart('pendapatan')">
                            Pendapatan
                        </button>
                        <button type="button" class="btn btn-outline-success" onclick="toggleChart('keuntungan')">
                            Keuntungan
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="chartPendapatan" height="80"></canvas>
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
                            <h4 class="mb-0 text-primary"><?php echo $transaksi_bulan['jumlah_transaksi']; ?></h4>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-primary" style="width: 100%"></div>
                        </div>
                    </div>

                    <div class="stat-item mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Total Pendapatan</span>
                            <h4 class="mb-0 text-success"><?php echo formatRupiah($transaksi_bulan['total_pendapatan'], true); ?></h4>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: 100%"></div>
                        </div>
                    </div>

                    <div class="stat-item mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Total Keuntungan</span>
                            <h4 class="mb-0 text-info"><?php echo formatRupiah($transaksi_bulan['total_keuntungan'], true); ?></h4>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-info" style="width: 100%"></div>
                        </div>
                    </div>

                    <div class="stat-item">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Rata-rata/Hari</span>
                            <h4 class="mb-0 text-warning"><?php echo formatRupiah($transaksi_bulan['total_pendapatan'] / date('j'), true); ?></h4>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-warning" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row Kedua -->
    <div class="row mb-4">
        <!-- Top Menu Hari Ini -->
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header bg-gradient-primary">
                    <h5 class="mb-0 text-white"><i class="bi bi-trophy-fill"></i> Top 5 Menu Hari Ini</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($top_menu_today)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mb-0 mt-2">Belum ada penjualan hari ini</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php 
                            $rank = 1;
                            foreach ($top_menu_today as $menu): 
                            ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="rank-badge rank-<?php echo $rank; ?> me-3">
                                            <?php echo $rank; ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo $menu['nama_menu']; ?></h6>
                                            <small class="text-muted">
                                                <span class="badge bg-secondary badge-sm"><?php echo $menu['nama_kategori']; ?></span>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-primary"><?php echo $menu['total_terjual']; ?> porsi</div>
                                        <small class="text-muted"><?php echo formatRupiah($menu['total_pendapatan'], true); ?></small>
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
                <?php if (!empty($top_menu_today)): ?>
                <div class="card-footer bg-light">
                    <a href="index.php?page=laporan_menu" class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-bar-chart"></i> Lihat Laporan Lengkap
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Metode Pembayaran -->
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header bg-gradient-success">
                    <h5 class="mb-0 text-white"><i class="bi bi-credit-card"></i> Metode Pembayaran</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($metode_pembayaran)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mb-0 mt-2">Belum ada transaksi</p>
                        </div>
                    <?php else: ?>
                        <canvas id="chartMetodePembayaran" height="200"></canvas>
                        <div class="mt-3">
                            <?php 
                            $total_all = array_sum(array_column($metode_pembayaran, 'total'));
                            foreach ($metode_pembayaran as $metode): 
                                $persen = ($metode['total'] / $total_all) * 100;
                            ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <span class="badge bg-<?php 
                                        echo $metode['metode_pembayaran'] == 'tunai' ? 'success' : 
                                            ($metode['metode_pembayaran'] == 'qris' ? 'info' : 'primary'); 
                                    ?>">
                                        <?php echo strtoupper($metode['metode_pembayaran']); ?>
                                    </span>
                                    <small class="text-muted ms-2"><?php echo $metode['jumlah']; ?>x</small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold"><?php echo formatRupiah($metode['total'], true); ?></div>
                                    <small class="text-muted"><?php echo number_format($persen, 1); ?>%</small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header bg-gradient-info">
                    <h5 class="mb-0 text-white"><i class="bi bi-speedometer"></i> Quick Stats</h5>
                </div>
                <div class="card-body">
                    <!-- Menu Status -->
                    <div class="quick-stat-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-card-list text-primary"></i>
                                <span class="ms-2">Status Menu</span>
                            </div>
                            <div>
                                <span class="badge bg-success"><?php echo $menu_stats['menu_tersedia']; ?> Tersedia</span>
                                <span class="badge bg-danger"><?php echo $menu_stats['menu_habis']; ?> Habis</span>
                            </div>
                        </div>
                    </div>

                    <!-- Stok Menipis -->
                    <div class="quick-stat-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-exclamation-triangle text-warning"></i>
                                <span class="ms-2">Stok Menipis</span>
                            </div>
                            <div>
                                <?php if (count($bahan_menipis) > 0): ?>
                                <span class="badge bg-warning text-dark"><?php echo count($bahan_menipis); ?> Item</span>
                                <a href="index.php?page=list_bahan" class="btn btn-sm btn-outline-warning ms-1">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php else: ?>
                                <span class="badge bg-success">Aman</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Opname Pending -->
                    <div class="quick-stat-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-clipboard-check text-info"></i>
                                <span class="ms-2">Opname Pending</span>
                            </div>
                            <div>
                                <?php if ($opname_pending_count > 0): ?>
                                <span class="badge bg-info"><?php echo $opname_pending_count; ?> Item</span>
                                <a href="index.php?page=list_opname" class="btn btn-sm btn-outline-info ms-1">
                                    <i class="bi bi-check-circle"></i>
                                </a>
                                <?php else: ?>
                                <span class="badge bg-success">Tidak Ada</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Kerugian -->
                    <?php if ($kerugian_today['total_kerugian'] > 0): ?>
                    <div class="quick-stat-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-exclamation-circle text-danger"></i>
                                <span class="ms-2">Kerugian Hari Ini</span>
                            </div>
                            <div>
                                <span class="badge bg-danger"><?php echo formatRupiah($kerugian_today['total_kerugian'], true); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Rata-rata Transaksi -->
                    <div class="quick-stat-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-calculator text-success"></i>
                                <span class="ms-2">Rata-rata Transaksi</span>
                            </div>
                            <div>
                                <span class="badge bg-success"><?php echo formatRupiah($transaksi_today['rata_rata_transaksi'], true); ?></span>
                            </div>
                        </div>
                    </div>
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
                                        <th width="15%">No. Transaksi</th>
                                        <th width="15%">Waktu</th>
                                        <th width="20%">Kasir</th>
                                        <th width="15%" class="text-center">Pembayaran</th>
                                        <th width="15%" class="text-end">Total</th>
                                        <th width="15%" class="text-end">Keuntungan</th>
                                        <th width="5%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transaksi_terakhir as $trx): ?>
                                    <tr>
                                        <td><small class="text-primary fw-bold"><?php echo $trx['no_transaksi']; ?></small></td>
                                        <td><small><?php echo date('H:i', strtotime($trx['tanggal_transaksi'])); ?></small></td>
                                        <td><small><?php echo $trx['kasir']; ?></small></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?php 
                                                echo $trx['metode_pembayaran'] == 'tunai' ? 'success' : 
                                                    ($trx['metode_pembayaran'] == 'qris' ? 'info' : 'primary'); 
                                            ?>">
                                                <?php echo strtoupper($trx['metode_pembayaran']); ?>
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold"><?php echo formatRupiah($trx['total_harga']); ?></td>
                                        <td class="text-end text-success"><?php echo formatRupiah($trx['total_keuntungan']); ?></td>
                                        <td class="text-center">
                                            <a href="index.php?page=detail_transaksi&id=<?php echo $trx['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
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
// Data untuk grafik
const dataGrafik = <?php echo json_encode($data_7_hari); ?>;

// Grafik Pendapatan 7 Hari
const ctx = document.getElementById('chartPendapatan');
let currentChart = null;

function createChart(type) {
    const labels = dataGrafik.map(d => d.tanggal_lengkap);
    const data = type === 'pendapatan' 
        ? dataGrafik.map(d => d.pendapatan)
        : dataGrafik.map(d => d.keuntungan);
    
    const color = type === 'pendapatan' ? 'rgba(18, 154, 125, 1)' : 'rgba(160, 220, 194, 1)';
    const bgColor = type === 'pendapatan' ? 'rgba(18, 154, 125, 0.2)' : 'rgba(160, 220, 194, 0.2)';
    
    if (currentChart) {
        currentChart.destroy();
    }
    
    currentChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: type === 'pendapatan' ? 'Pendapatan' : 'Keuntungan',
                data: data,
                borderColor: color,
                backgroundColor: bgColor,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: color,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        label: function(context) {
                            return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + (value / 1000) + 'K';
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

function toggleChart(type) {
    // Update active button
    document.querySelectorAll('.btn-group button').forEach(btn => {
        btn.classList.remove('active');
        if (type === 'pendapatan' && btn.textContent.includes('Pendapatan')) {
            btn.classList.add('active');
        } else if (type === 'keuntungan' && btn.textContent.includes('Keuntungan')) {
            btn.classList.add('active');
        }
    });
    
    createChart(type);
}

// Initialize chart
createChart('pendapatan');

// Grafik Metode Pembayaran
<?php if (!empty($metode_pembayaran)): ?>
const metodeData = <?php echo json_encode($metode_pembayaran); ?>;
const ctxMetode = document.getElementById('chartMetodePembayaran');

const metodePembayaranChart = new Chart(ctxMetode, {
    type: 'doughnut',
    data: {
        labels: metodeData.map(m => m.metode_pembayaran.toUpperCase()),
        datasets: [{
            data: metodeData.map(m => m.total),
            backgroundColor: [
                'rgba(160, 220, 194, 1)',  // success - tunai
                'rgba(18, 154, 125, 1)',   // primary - debit
                'rgba(155, 211, 203, 1)',  // secondary - qris
                'rgba(252, 188, 188, 1)'   // warning - transfer
            ],
            borderWidth: 3,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: {
                        size: 12
                    },
                    usePointStyle: true,
                    pointStyle: 'circle'
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return label + ': Rp ' + value.toLocaleString('id-ID') + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});
<?php endif; ?>

// Auto refresh setiap 5 menit
setTimeout(function() {
    location.reload();
}, 300000);
</script>

<style>
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

/* Responsive */
@media (max-width: 768px) {
    .dashboard-card-modern .icon-wrapper {
        width: 48px;
        height: 48px;
        font-size: 20px;
    }
    
    .dashboard-card-modern h2 {
        font-size: 1.5rem;
    }
}

/* Animation untuk cards */
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