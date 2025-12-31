<?php
/**
 * LAPORAN KAS - Menu Utama
 * Pilih jenis laporan dan periode
 */

// Get range tahun dari transaksi
$transaksi_awal = fetchOne("SELECT MIN(tgl_transaksi) as tgl_awal FROM transaksi");
$transaksi_akhir = fetchOne("SELECT MAX(tgl_transaksi) as tgl_akhir FROM transaksi");

if ($transaksi_awal && $transaksi_awal['tgl_awal']) {
    $tahun_awal = date('Y', strtotime($transaksi_awal['tgl_awal']));
} else {
    $tahun_awal = date('Y');
}

if ($transaksi_akhir && $transaksi_akhir['tgl_akhir']) {
    $tahun_akhir = date('Y', strtotime($transaksi_akhir['tgl_akhir']));
} else {
    $tahun_akhir = date('Y');
}

$bulan_list = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
    '04' => 'April', '05' => 'Mei', '06' => 'Juni',
    '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
    '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

$bulan_sekarang = date('m');
$tahun_sekarang = date('Y');
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-file-earmark-text"></i> Laporan Keuangan</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Laporan Keuangan</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-filter"></i> Filter Laporan
            </div>
            <div class="card-body">
                <form action="" method="GET" id="formLaporan">
                    <input type="hidden" name="page" value="laporan_kas">
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Jenis Laporan *</label>
                                <select class="form-select form-select-lg" name="jenis_laporan" id="jenisLaporan" required>
                                    <option value="">-- Pilih Jenis Laporan --</option>
                                    <option value="neraca">📊 Neraca (Balance Sheet)</option>
                                    <option value="labarugi">💰 Laba Rugi (Income Statement)</option>
                                    <option value="aruskas">💸 Arus Kas (Cash Flow)</option>
                                    <option value="buku_besar">📖 Buku Besar</option>
                                    <option value="jurnal_umum">📝 Jurnal Umum</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Bulan *</label>
                                <select class="form-select" name="bulan" id="bulan" required>
                                    <?php foreach($bulan_list as $key => $value): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($key == $bulan_sekarang) ? 'selected' : ''; ?>>
                                        <?php echo $value; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tahun *</label>
                                <select class="form-select" name="tahun" id="tahun" required>
                                    <?php for($y = $tahun_awal; $y <= $tahun_akhir; $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo ($y == $tahun_sekarang) ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary btn-lg" id="btnTampilkan">
                                <i class="bi bi-eye"></i> Tampilkan Laporan
                            </button>
                            <button type="button" class="btn btn-success" id="btnExport" style="display:none;">
                                <i class="bi bi-file-earmark-excel"></i> Export Excel
                            </button>
                            <button type="button" class="btn btn-danger" id="btnPrint" style="display:none;">
                                <i class="bi bi-printer"></i> Print PDF
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Container Laporan -->
        <div id="containerLaporan" style="display:none;" class="mt-4">
            <!-- Laporan akan dimuat di sini -->
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <i class="bi bi-info-circle"></i> Jenis Laporan
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6><i class="bi bi-diagram-3"></i> Neraca</h6>
                    <small class="text-muted">
                        Menampilkan posisi Aset, Liabilitas, dan Ekuitas pada periode tertentu
                    </small>
                </div>
                <hr>
                <div class="mb-3">
                    <h6><i class="bi bi-cash-stack"></i> Laba Rugi</h6>
                    <small class="text-muted">
                        Menampilkan Pendapatan, Beban, dan Laba/Rugi dalam periode tertentu
                    </small>
                </div>
                <hr>
                <div class="mb-3">
                    <h6><i class="bi bi-arrow-left-right"></i> Arus Kas</h6>
                    <small class="text-muted">
                        Menampilkan aliran kas masuk dan keluar dalam periode tertentu
                    </small>
                </div>
                <hr>
                <div class="mb-3">
                    <h6><i class="bi bi-book"></i> Buku Besar</h6>
                    <small class="text-muted">
                        Menampilkan mutasi per akun dalam periode tertentu
                    </small>
                </div>
                <hr>
                <div class="mb-0">
                    <h6><i class="bi bi-journal-text"></i> Jurnal Umum</h6>
                    <small class="text-muted">
                        Menampilkan semua transaksi jurnal dalam periode tertentu
                    </small>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-warning">
                <i class="bi bi-calendar-check"></i> Periode Tersedia
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <small class="text-muted">Data Dari:</small><br>
                    <strong>
                        <?php 
                        if($transaksi_awal && $transaksi_awal['tgl_awal']) {
                            echo date('F Y', strtotime($transaksi_awal['tgl_awal']));
                        } else {
                            echo 'Belum ada data';
                        }
                        ?>
                    </strong>
                </div>
                <div class="mb-0">
                    <small class="text-muted">Sampai:</small><br>
                    <strong>
                        <?php 
                        if($transaksi_akhir && $transaksi_akhir['tgl_akhir']) {
                            echo date('F Y', strtotime($transaksi_akhir['tgl_akhir']));
                        } else {
                            echo 'Belum ada data';
                        }
                        ?>
                    </strong>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('formLaporan').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const jenis = document.getElementById('jenisLaporan').value;
    const bulan = document.getElementById('bulan').value;
    const tahun = document.getElementById('tahun').value;
    
    if (!jenis) {
        alert('Pilih jenis laporan terlebih dahulu!');
        return;
    }
    
    // Show loading
    const container = document.getElementById('containerLaporan');
    container.style.display = 'block';
    container.innerHTML = '<div class="card"><div class="card-body text-center"><i class="bi bi-hourglass-split"></i> Memuat laporan...</div></div>';
    
    // Load laporan via AJAX
    loadLaporan(jenis, bulan, tahun);
});

async function loadLaporan(jenis, bulan, tahun) {
    const container = document.getElementById('containerLaporan');
    
    try {
        // Load langsung dari folder modul/laporan/
        const response = await fetch(`modul/laporan/${jenis}.php?bulan=${bulan}&tahun=${tahun}&ajax=1`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const html = await response.text();
        
        // Check if response contains error
        if (html.includes('404') || html.includes('Not Found')) {
            throw new Error('Laporan tidak ditemukan. File laporan belum dibuat.');
        }
        
        container.innerHTML = html;
        
        // Show export buttons
        document.getElementById('btnExport').style.display = 'inline-block';
        document.getElementById('btnPrint').style.display = 'inline-block';
        
        // Scroll to laporan
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        
    } catch (error) {
        container.innerHTML = `
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> 
                        <strong>Error:</strong> ${error.message}
                        <hr>
                        <small>
                            <strong>Pastikan file berikut ada:</strong><br>
                            - modul/laporan/${jenis}.php<br>
                            <strong>URL yang diakses:</strong><br>
                            modul/laporan/${jenis}.php?bulan=${bulan}&tahun=${tahun}
                        </small>
                    </div>
                </div>
            </div>
        `;
    }
}

// Print
document.getElementById('btnPrint').addEventListener('click', function() {
    window.print();
});

// Export (placeholder)
document.getElementById('btnExport').addEventListener('click', function() {
    alert('Fitur export Excel akan segera hadir!');
});

// Keyboard shortcut
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + P untuk print
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        const container = document.getElementById('containerLaporan');
        if (container.style.display === 'block') {
            e.preventDefault();
            window.print();
        }
    }
});
</script>

<style>
@media print {
    .card-header, .breadcrumb, nav, form, .btn, .col-md-4 {
        display: none !important;
    }
    .col-md-8 {
        width: 100% !important;
    }
    #containerLaporan {
        margin: 0 !important;
        padding: 0 !important;
    }
}
</style>