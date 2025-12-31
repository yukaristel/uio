<?php
/**
 * GENERATE ULANG SALDO
 * Reset dan perbaiki tabel saldo jika terjadi kesalahan
 */

// Get bulan tahun transaksi terawal dan terakhir
$transaksi_awal = fetchOne("SELECT MIN(tgl_transaksi) as tgl_awal FROM transaksi");
$transaksi_akhir = fetchOne("SELECT MAX(tgl_transaksi) as tgl_akhir FROM transaksi");

// Cek apakah ada transaksi dan tgl tidak null
if ($transaksi_awal && $transaksi_awal['tgl_awal']) {
    $tahun_awal = date('Y', strtotime($transaksi_awal['tgl_awal']));
} else {
    $tahun_awal = date('Y');
}

if ($transaksi_akhir && $transaksi_akhir['tgl_akhir']) {
    $tahun_akhir = date('Y', strtotime($transaksi_akhir['tgl_akhir']));
    $bulan_akhir = date('m', strtotime($transaksi_akhir['tgl_akhir']));
} else {
    $tahun_akhir = date('Y');
    $bulan_akhir = date('m');
}

// Generate options untuk dropdown
$bulan_list = [
    '01' => 'Januari',
    '02' => 'Februari',
    '03' => 'Maret',
    '04' => 'April',
    '05' => 'Mei',
    '06' => 'Juni',
    '07' => 'Juli',
    '08' => 'Agustus',
    '09' => 'September',
    '10' => 'Oktober',
    '11' => 'November',
    '12' => 'Desember'
];
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-arrow-clockwise"></i> Generate Ulang Saldo</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Generate Saldo</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-warning">
                <i class="bi bi-exclamation-triangle"></i> Reset dan Generate Ulang Saldo
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h5><i class="bi bi-info-circle"></i> Informasi</h5>
                    <ul class="mb-0">
                        <li>Fitur ini digunakan untuk <strong>memperbaiki saldo</strong> yang salah atau tidak sinkron</li>
                        <li>Pilih bulan dan tahun <strong>mulai dari bulan yang salah</strong></li>
                        <li>Sistem akan generate ulang saldo dari bulan tersebut hingga <strong>bulan saat ini</strong></li>
                        <li>Proses akan menghapus saldo lama dan menghitung ulang dari transaksi</li>
                    </ul>
                </div>

                <form id="formGenerate">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Bulan Mulai *</label>
                                <select class="form-select" name="bulan_mulai" id="bulanMulai" required>
                                    <option value="">-- Pilih Bulan --</option>
                                    <?php foreach($bulan_list as $key => $value): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tahun Mulai *</label>
                                <select class="form-select" name="tahun_mulai" id="tahunMulai" required>
                                    <option value="">-- Pilih Tahun --</option>
                                    <?php for($y = $tahun_awal; $y <= $tahun_akhir; $y++): ?>
                                    <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="alert alert-warning" id="alertInfo" style="display:none;">
                                <strong><i class="bi bi-calendar-check"></i> Akan Generate:</strong><br>
                                <span id="rangeInfo"></span>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <button type="submit" class="btn btn-warning" id="btnGenerate">
                                <i class="bi bi-arrow-clockwise"></i> Mulai Generate
                            </button>
                            <a href="index.php?page=dashboard" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Batal
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Progress Container -->
                <div id="progressContainer" style="display:none;" class="mt-4">
                    <hr>
                    <h5><i class="bi bi-gear"></i> Proses Generate</h5>
                    
                    <div class="mb-3">
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 id="progressBar" role="progressbar" 
                                 style="width: 0%">0%</div>
                        </div>
                    </div>

                    <div id="logContainer" class="border rounded p-3" 
                         style="background-color: #f8f9fa; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 13px;">
                        <div id="logContent"></div>
                    </div>

                    <div class="mt-3" id="btnSelesaiContainer" style="display:none;">
                        <a href="index.php?page=list_transaksi" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Selesai, Lihat Transaksi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <i class="bi bi-calendar-range"></i> Data Transaksi
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted">Transaksi Terawal:</label>
                    <h6>
                        <?php 
                        if($transaksi_awal && $transaksi_awal['tgl_awal']) {
                            echo date('d F Y', strtotime($transaksi_awal['tgl_awal']));
                        } else {
                            echo 'Belum ada transaksi';
                        }
                        ?>
                    </h6>
                </div>
                <div class="mb-3">
                    <label class="text-muted">Transaksi Terakhir:</label>
                    <h6>
                        <?php 
                        if($transaksi_akhir && $transaksi_akhir['tgl_akhir']) {
                            echo date('d F Y', strtotime($transaksi_akhir['tgl_akhir']));
                        } else {
                            echo 'Belum ada transaksi';
                        }
                        ?>
                    </h6>
                </div>
                <hr>
                <div class="mb-0">
                    <label class="text-muted">Total Transaksi:</label>
                    <h5>
                        <?php 
                        $total = fetchOne("SELECT COUNT(*) as total FROM transaksi");
                        echo number_format($total['total']);
                        ?> transaksi
                    </h5>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-danger text-white">
                <i class="bi bi-exclamation-octagon"></i> Peringatan
            </div>
            <div class="card-body">
                <ul class="mb-0 small text-danger">
                    <li>Proses ini akan <strong>menghapus</strong> saldo lama</li>
                    <li>Pastikan periode yang dipilih sudah <strong>benar</strong></li>
                    <li>Jangan tutup halaman saat proses berlangsung</li>
                    <li>Backup database direkomendasikan</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
const bulanList = <?php echo json_encode($bulan_list); ?>;
const tahunAkhir = <?php echo $tahun_akhir; ?>;
const bulanAkhir = '<?php echo $bulan_akhir; ?>';

// Update info range
document.getElementById('bulanMulai').addEventListener('change', updateRangeInfo);
document.getElementById('tahunMulai').addEventListener('change', updateRangeInfo);

function updateRangeInfo() {
    const bulan = document.getElementById('bulanMulai').value;
    const tahun = document.getElementById('tahunMulai').value;
    
    if (bulan && tahun) {
        const namaBulanMulai = bulanList[bulan];
        const namaBulanAkhir = bulanList[bulanAkhir];
        
        document.getElementById('rangeInfo').innerHTML = 
            `Dari <strong>${namaBulanMulai} ${tahun}</strong> hingga <strong>${namaBulanAkhir} ${tahunAkhir}</strong>`;
        document.getElementById('alertInfo').style.display = 'block';
    } else {
        document.getElementById('alertInfo').style.display = 'none';
    }
}

// Handle form submit
document.getElementById('formGenerate').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const bulan = document.getElementById('bulanMulai').value;
    const tahun = document.getElementById('tahunMulai').value;
    
    if (!confirm('Apakah Anda yakin ingin generate ulang saldo?\n\nProses ini akan menghapus saldo lama dan menghitung ulang dari transaksi.')) {
        return;
    }
    
    // Disable form
    document.getElementById('btnGenerate').disabled = true;
    document.getElementById('bulanMulai').disabled = true;
    document.getElementById('tahunMulai').disabled = true;
    
    // Show progress
    document.getElementById('progressContainer').style.display = 'block';
    
    // Start generate
    await generateSaldo(tahun, bulan);
});

async function generateSaldo(tahunMulai, bulanMulai) {
    const logContent = document.getElementById('logContent');
    const progressBar = document.getElementById('progressBar');
    
    addLog('🚀 Memulai proses generate saldo...', 'primary');
    addLog(`📅 Periode: ${bulanList[bulanMulai]} ${tahunMulai} - ${bulanList[bulanAkhir]} ${tahunAkhir}`, 'info');
    addLog('');
    
    try {
        // Generate list bulan yang akan diproses
        const bulanList = generateMonthList(tahunMulai, bulanMulai, tahunAkhir, bulanAkhir);
        const total = bulanList.length;
        
        addLog(`📊 Total bulan yang akan diproses: ${total} bulan`, 'info');
        addLog('');
        
        let processed = 0;
        
        for (const item of bulanList) {
            processed++;
            const progress = Math.round((processed / total) * 100);
            
            addLog(`⏳ [${processed}/${total}] Memproses ${item.nama}...`, 'warning');
            
            // Call backend
            const response = await fetch('config/simpan_saldo_proses.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `tahun=${item.tahun}&bulan=${item.bulan}`
            });
            
            // Cek apakah response OK
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const text = await response.text();
            
            // Debug: tampilkan raw response jika bukan JSON
            let result;
            try {
                result = JSON.parse(text);
            } catch (e) {
                console.error('Response bukan JSON:', text);
                addLog(`❌ ${item.nama}: Response error (bukan JSON)`, 'danger');
                addLog(`Raw response: ${text.substring(0, 200)}...`, 'danger');
                continue;
            }
            
            if (result.success) {
                addLog(`✅ ${item.nama}: ${result.total_akun} akun berhasil di-generate`, 'success');
            } else {
                addLog(`❌ ${item.nama}: ${result.message}`, 'danger');
            }
            
            // Update progress bar
            progressBar.style.width = progress + '%';
            progressBar.textContent = progress + '%';
            
            // Scroll to bottom
            logContent.parentElement.scrollTop = logContent.parentElement.scrollHeight;
            
            // Small delay untuk visual
            await sleep(300);
        }
        
        addLog('');
        addLog('🎉 SELESAI! Semua saldo berhasil di-generate ulang.', 'success');
        addLog('📝 Silakan cek laporan untuk memastikan saldo sudah benar.', 'info');
        
        // Show button selesai
        document.getElementById('btnSelesaiContainer').style.display = 'block';
        
    } catch (error) {
        addLog('');
        addLog('❌ ERROR: ' + error.message, 'danger');
    }
}

function generateMonthList(tahunMulai, bulanMulai, tahunAkhir, bulanAkhir) {
    const list = [];
    let currentYear = parseInt(tahunMulai);
    let currentMonth = parseInt(bulanMulai);
    const endYear = parseInt(tahunAkhir);
    const endMonth = parseInt(bulanAkhir);
    
    while (currentYear < endYear || (currentYear === endYear && currentMonth <= endMonth)) {
        const bulanPad = String(currentMonth).padStart(2, '0');
        list.push({
            tahun: currentYear,
            bulan: bulanPad,
            nama: bulanList[bulanPad] + ' ' + currentYear
        });
        
        currentMonth++;
        if (currentMonth > 12) {
            currentMonth = 1;
            currentYear++;
        }
    }
    
    return list;
}

function addLog(message, type = '') {
    const logContent = document.getElementById('logContent');
    const time = new Date().toLocaleTimeString('id-ID');
    
    let colorClass = '';
    if (type === 'success') colorClass = 'text-success';
    else if (type === 'danger') colorClass = 'text-danger';
    else if (type === 'warning') colorClass = 'text-warning';
    else if (type === 'info') colorClass = 'text-info';
    else if (type === 'primary') colorClass = 'text-primary';
    
    const logLine = document.createElement('div');
    logLine.className = colorClass;
    logLine.textContent = message ? `[${time}] ${message}` : '';
    
    logContent.appendChild(logLine);
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}
</script>

<style>
#logContainer {
    line-height: 1.6;
}
#logContent div {
    margin-bottom: 2px;
}
</style>