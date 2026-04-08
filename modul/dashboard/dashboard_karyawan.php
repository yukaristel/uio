<?php
/**
 * DASHBOARD KARYAWAN
 * Dashboard untuk karyawan dengan akses terbatas
 * Menampilkan statistik transaksi yang dilakukan oleh karyawan
 */

// Pastikan user adalah karyawan
if ($_SESSION['role'] != 'karyawan') {
    header('Location: ?page=dashboard');
    exit;
}

$id_user = $_SESSION['user_id'];
$today = date('Y-m-d');
$bulan_ini = date('Y-m');
$tahun_ini = date('Y');

// ============================================
// STATISTIK TRANSAKSI KARYAWAN HARI INI
// ============================================

// TRANSAKSI YANG DIBUAT HARI INI
$transaksi_today = fetchOne("
    SELECT 
        COUNT(*) as jumlah_transaksi,
        COALESCE(SUM(jumlah), 0) as total_nilai
    FROM transaksi
    WHERE DATE(tgl_transaksi) = ?
    AND id_user = ?
", [$today, $id_user]);

$jumlah_trx_hari_ini = $transaksi_today['jumlah_transaksi'];
$nilai_trx_hari_ini = $transaksi_today['total_nilai'];

// PENDAPATAN DARI TRANSAKSI YANG DIBUAT HARI INI
$pendapatan_today = fetchOne("
    SELECT COALESCE(SUM(t.jumlah), 0) as total
    FROM transaksi t
    JOIN chart_of_accounts c ON t.rekening_kredit = c.kode_akun
    WHERE DATE(t.tgl_transaksi) = ?
    AND t.id_user = ?
    AND c.lev1 = 4
", [$today, $id_user]);

$pendapatan_hari_ini = $pendapatan_today['total'];

// RATA-RATA NILAI TRANSAKSI
$rata_rata_transaksi = $jumlah_trx_hari_ini > 0 ? $nilai_trx_hari_ini / $jumlah_trx_hari_ini : 0;

// ============================================
// STATISTIK BULAN INI
// ============================================

// Transaksi bulan ini
$transaksi_bulan = fetchOne("
    SELECT 
        COUNT(*) as jumlah_transaksi,
        COALESCE(SUM(jumlah), 0) as total_nilai
    FROM transaksi
    WHERE YEAR(tgl_transaksi) = ?
    AND MONTH(tgl_transaksi) = ?
    AND id_user = ?
", [date('Y'), date('m'), $id_user]);

$jumlah_trx_bulan_ini = $transaksi_bulan['jumlah_transaksi'];
$nilai_trx_bulan_ini = $transaksi_bulan['total_nilai'];

// Pendapatan bulan ini
$pendapatan_bulan = fetchOne("
    SELECT COALESCE(SUM(t.jumlah), 0) as total
    FROM transaksi t
    JOIN chart_of_accounts c ON t.rekening_kredit = c.kode_akun
    WHERE YEAR(t.tgl_transaksi) = ?
    AND MONTH(t.tgl_transaksi) = ?
    AND t.id_user = ?
    AND c.lev1 = 4
", [date('Y'), date('m'), $id_user]);

$pendapatan_bulan_ini = $pendapatan_bulan['total'];

// ============================================
// PERBANDINGAN DENGAN KEMARIN
// ============================================

$yesterday = date('Y-m-d', strtotime('-1 day'));

$transaksi_yesterday = fetchOne("
    SELECT 
        COUNT(*) as jumlah_transaksi,
        COALESCE(SUM(jumlah), 0) as total_nilai
    FROM transaksi
    WHERE DATE(tgl_transaksi) = ?
    AND id_user = ?
", [$yesterday, $id_user]);

// Hitung persentase perubahan (fungsi sudah ada di dashboard.php)
$perubahan_transaksi = hitungPerubahan($jumlah_trx_hari_ini, $transaksi_yesterday['jumlah_transaksi']);
$perubahan_nilai = hitungPerubahan($nilai_trx_hari_ini, $transaksi_yesterday['total_nilai']);

// ============================================
// DATA GRAFIK - 7 HARI TERAKHIR
// ============================================

$data_7_hari = [];
for ($i = 6; $i >= 0; $i--) {
    $tanggal = date('Y-m-d', strtotime("-$i days"));
    
    $data_hari = fetchOne("
        SELECT 
            COUNT(*) as jumlah_transaksi,
            COALESCE(SUM(jumlah), 0) as total_nilai,
            COALESCE(SUM(CASE WHEN c.lev1 = 4 THEN t.jumlah ELSE 0 END), 0) as pendapatan
        FROM transaksi t
        LEFT JOIN chart_of_accounts c ON t.rekening_kredit = c.kode_akun
        WHERE DATE(t.tgl_transaksi) = ?
        AND t.id_user = ?
    ", [$tanggal, $id_user]);
    
    $data_7_hari[] = [
        'tanggal' => date('d M', strtotime($tanggal)),
        'jumlah_transaksi' => $data_hari['jumlah_transaksi'],
        'total_nilai' => $data_hari['total_nilai'],
        'pendapatan' => $data_hari['pendapatan']
    ];
}

// ============================================
// TRANSAKSI TERAKHIR YANG DIBUAT
// ============================================

$transaksi_terakhir = fetchAll("
    SELECT 
        t.*,
        cd.nama_akun as akun_debet,
        ck.nama_akun as akun_kredit
    FROM transaksi t
    LEFT JOIN chart_of_accounts cd ON t.rekening_debet = cd.kode_akun
    LEFT JOIN chart_of_accounts ck ON t.rekening_kredit = ck.kode_akun
    WHERE t.id_user = ?
    ORDER BY t.created_at DESC
    LIMIT 10
", [$id_user]);

// ============================================
// TOP 5 JENIS TRANSAKSI
// ============================================

$top_transaksi = fetchAll("
    SELECT 
        c.nama_akun,
        COUNT(*) as jumlah,
        SUM(t.jumlah) as total
    FROM transaksi t
    JOIN chart_of_accounts c ON (t.rekening_kredit = c.kode_akun OR t.rekening_debet = c.kode_akun)
    WHERE t.id_user = ?
    AND DATE(t.tgl_transaksi) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    AND c.lev4 > 0
    GROUP BY c.nama_akun
    ORDER BY jumlah DESC
    LIMIT 5
", [$id_user]);

?>

<!-- ============================================
     HEADER DASHBOARD
     ============================================ -->

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h2 class="page-title">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard Karyawan
            </h2>
            <p class="text-muted mb-0">
                <i class="far fa-calendar-alt me-1"></i>
                <?php echo date('l, d F Y'); ?>
            </p>
            <p class="text-muted mb-0">
            &nbsp;
            </p>
        </div>
        <div class="col-auto">
            <!-- Tombol POS -->
            <a href="?page=buat_transaksi" class="btn btn-primary btn-lg">
                <i class="fas fa-cash-register me-2"></i>POS
            </a>
        </div>
    </div>
</div>

<!-- ============================================
     STATISTIK CARDS
     ============================================ -->

<div class="row g-3 mb-4">
    <!-- Transaksi Hari Ini -->
    <div class="col-md-3">
        <div class="card dashboard-card-modern border-0">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="icon-wrapper bg-primary bg-opacity-10 text-primary me-3">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-0 small">Transaksi Hari Ini</p>
                        <h2 class="mb-0 fw-bold"><?php echo number_format($jumlah_trx_hari_ini); ?></h2>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <?php if ($perubahan_transaksi >= 0): ?>
                        <span class="badge bg-success bg-opacity-10 text-success badge-sm">
                            <i class="fas fa-arrow-up me-1"></i><?php echo number_format(abs($perubahan_transaksi), 1); ?>%
                        </span>
                    <?php else: ?>
                        <span class="badge bg-danger bg-opacity-10 text-danger badge-sm">
                            <i class="fas fa-arrow-down me-1"></i><?php echo number_format(abs($perubahan_transaksi), 1); ?>%
                        </span>
                    <?php endif; ?>
                    <small class="text-muted ms-2">vs kemarin</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Nilai Transaksi Hari Ini -->
    <div class="col-md-3">
        <div class="card dashboard-card-modern border-0">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="icon-wrapper bg-success bg-opacity-10 text-success me-3">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-0 small">Nilai Transaksi</p>
                        <h2 class="mb-0 fw-bold">Rp <?php echo number_format($nilai_trx_hari_ini, 0, ',', '.'); ?></h2>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <?php if ($perubahan_nilai >= 0): ?>
                        <span class="badge bg-success bg-opacity-10 text-success badge-sm">
                            <i class="fas fa-arrow-up me-1"></i><?php echo number_format(abs($perubahan_nilai), 1); ?>%
                        </span>
                    <?php else: ?>
                        <span class="badge bg-danger bg-opacity-10 text-danger badge-sm">
                            <i class="fas fa-arrow-down me-1"></i><?php echo number_format(abs($perubahan_nilai), 1); ?>%
                        </span>
                    <?php endif; ?>
                    <small class="text-muted ms-2">vs kemarin</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Pendapatan Hari Ini -->
    <div class="col-md-3">
        <div class="card dashboard-card-modern border-0">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="icon-wrapper bg-info bg-opacity-10 text-info me-3">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-0 small">Pendapatan Hari Ini</p>
                        <h2 class="mb-0 fw-bold">Rp <?php echo number_format($pendapatan_hari_ini, 0, ',', '.'); ?></h2>
                    </div>
                </div>
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>Dari transaksi Anda
                </small>
            </div>
        </div>
    </div>

    <!-- Rata-rata Transaksi -->
    <div class="col-md-3">
        <div class="card dashboard-card-modern border-0">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="icon-wrapper bg-warning bg-opacity-10 text-warning me-3">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-0 small">Rata-rata / Transaksi</p>
                        <h2 class="mb-0 fw-bold">Rp <?php echo number_format($rata_rata_transaksi, 0, ',', '.'); ?></h2>
                    </div>
                </div>
                <small class="text-muted">
                    <i class="fas fa-chart-bar me-1"></i>Hari ini
                </small>
            </div>
        </div>
    </div>
</div>

<!-- ============================================
     STATISTIK BULAN INI
     ============================================ -->

<div class="row g-3 mb-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-gradient-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-check me-2"></i>Statistik Bulan Ini
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="quick-stat-item">
                            <h4 class="mb-1 fw-bold text-primary"><?php echo number_format($jumlah_trx_bulan_ini); ?></h4>
                            <p class="text-muted mb-0 small">Total Transaksi</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="quick-stat-item">
                            <h4 class="mb-1 fw-bold text-success">Rp <?php echo number_format($nilai_trx_bulan_ini, 0, ',', '.'); ?></h4>
                            <p class="text-muted mb-0 small">Total Nilai</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="quick-stat-item">
                            <h4 class="mb-1 fw-bold text-info">Rp <?php echo number_format($pendapatan_bulan_ini, 0, ',', '.'); ?></h4>
                            <p class="text-muted mb-0 small">Total Pendapatan</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================
     GRAFIK & TOP TRANSAKSI
     ============================================ -->

<div class="row g-3 mb-4">
    <!-- Grafik Aktivitas 7 Hari -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">
                    <i class="fas fa-chart-area me-2 text-primary"></i>Aktivitas 7 Hari Terakhir
                </h5>
            </div>
            <div class="card-body">
                <canvas id="chartAktivitas" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Top 5 Jenis Transaksi -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">
                    <i class="fas fa-trophy me-2 text-warning"></i>Top Transaksi (30 Hari)
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($top_transaksi)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p class="mb-0">Belum ada transaksi</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php 
                        $rank = 1;
                        foreach ($top_transaksi as $trx): 
                        ?>
                            <div class="list-group-item">
                                <div class="d-flex align-items-center">
                                    <div class="rank-badge rank-<?php echo $rank; ?> me-3">
                                        <?php echo $rank; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($trx['nama_akun']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo $trx['jumlah']; ?> transaksi
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-primary">
                                            Rp <?php echo number_format($trx['total'], 0, ',', '.'); ?>
                                        </div>
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
</div>

<!-- ============================================
     TRANSAKSI TERAKHIR
     ============================================ -->

<div class="row g-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2 text-primary"></i>Transaksi Terakhir
                </h5>
                <a href="?page=transaksi" class="btn btn-sm btn-outline-primary">
                    Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($transaksi_terakhir)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                        <p class="mb-0">Belum ada transaksi hari ini</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Keterangan</th>
                                    <th>Debet</th>
                                    <th>Kredit</th>
                                    <th class="text-end">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transaksi_terakhir as $trx): ?>
                                    <tr>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($trx['tgl_transaksi'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($trx['keterangan']); ?></div>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($trx['akun_debet']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($trx['akun_kredit']); ?>
                                            </small>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-success bg-opacity-10 text-success">
                                                Rp <?php echo number_format($trx['jumlah'], 0, ',', '.'); ?>
                                            </span>
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

<!-- ============================================
     JAVASCRIPT & GRAFIK
     ============================================ -->

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
// Data untuk grafik
const dataGrafik = <?php echo json_encode($data_7_hari); ?>;

// Grafik Aktivitas 7 Hari
const ctxAktivitas = document.getElementById('chartAktivitas');
if (ctxAktivitas) {
    new Chart(ctxAktivitas, {
        type: 'line',
        data: {
            labels: dataGrafik.map(d => d.tanggal),
            datasets: [
                {
                    label: 'Jumlah Transaksi',
                    data: dataGrafik.map(d => d.jumlah_transaksi),
                    borderColor: '#129A7D',
                    backgroundColor: 'rgba(18, 154, 125, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y'
                },
                {
                    label: 'Nilai Transaksi (Rp)',
                    data: dataGrafik.map(d => d.total_nilai),
                    borderColor: '#FCBCBC',
                    backgroundColor: 'rgba(252, 188, 188, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                        font: {
                            size: 12,
                            weight: '500'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 13,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 12
                    },
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.datasetIndex === 0) {
                                label += context.parsed.y + ' transaksi';
                            } else {
                                label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    title: {
                        display: true,
                        text: 'Jumlah Transaksi',
                        font: {
                            size: 11,
                            weight: 'bold'
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false
                    },
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
                    title: {
                        display: true,
                        text: 'Nilai (Rp)',
                        font: {
                            size: 11,
                            weight: 'bold'
                        }
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
   DASHBOARD KARYAWAN STYLES
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
    
    #chartAktivitas {
        max-height: 250px;
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