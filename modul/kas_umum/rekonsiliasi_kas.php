<?php
/**
 * REKONSILIASI KAS
 * Step 32/64 (50.0%) - SUDAH 50%! 🎉
 */

// Get saldo kas sistem
$saldo_kas = fetchOne("SELECT saldo_sesudah FROM kas_umum ORDER BY created_at DESC, id DESC LIMIT 1");
$saldo_sistem = $saldo_kas ? $saldo_kas['saldo_sesudah'] : 0;

// Get rekonsiliasi terakhir
$rekonsiliasi_terakhir = fetchOne("
    SELECT * FROM kas_umum 
    WHERE keterangan LIKE 'Rekonsiliasi Kas%' 
    ORDER BY created_at DESC 
    LIMIT 1
");
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-check2-square"></i> Rekonsiliasi Kas</h2>
        <p class="text-muted">Sesuaikan saldo kas sistem dengan kas fisik</p>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Form Rekonsiliasi</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h5><i class="bi bi-info-circle"></i> Panduan Rekonsiliasi:</h5>
                    <ol class="mb-0">
                        <li>Hitung kas fisik (uang di brankas/laci)</li>
                        <li>Bandingkan dengan saldo sistem</li>
                        <li>Input saldo fisik di form</li>
                        <li>Sistem akan otomatis hitung selisih</li>
                        <li>Beri keterangan jika ada selisih</li>
                    </ol>
                </div>

                <form action="config/kas_proses.php?action=rekonsiliasi" method="POST" id="formRekonsiliasi">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-primary text-center">
                                <h6 class="mb-2">Saldo Kas Sistem</h6>
                                <h3 class="mb-0"><?php echo formatRupiah($saldo_sistem); ?></h3>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Saldo Kas Fisik (Hasil Hitung) *</label>
                                <input type="number" class="form-control form-control-lg" 
                                       name="saldo_fisik" id="saldoFisik" required 
                                       min="0" step="1000" placeholder="Masukkan hasil hitung kas fisik">
                                <small class="text-muted">
                                    Hitung uang tunai yang ada di brankas/laci kasir
                                </small>
                            </div>
                        </div>

                        <div class="col-md-12" id="selisihInfo" style="display:none;">
                            <div class="alert" id="selisihAlert">
                                <h5 class="mb-2">Selisih Terdeteksi:</h5>
                                <h3 id="selisihNominal">Rp 0</h3>
                                <p class="mb-0" id="selisihKeterangan"></p>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Keterangan *</label>
                                <textarea class="form-control" name="keterangan" rows="3" required
                                          placeholder="Jelaskan penyebab selisih (jika ada), contoh: Uang hilang, Salah hitung, dll"></textarea>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <button type="submit" class="btn btn-success btn-lg" id="btnSubmit">
                                <i class="bi bi-check-circle"></i> Proses Rekonsiliasi
                            </button>
                            <a href="index.php?page=dashboard_kas" class="btn btn-secondary btn-lg">
                                <i class="bi bi-x-circle"></i> Batal
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <i class="bi bi-calculator"></i> Perhitungan Selisih
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted">Saldo Sistem:</label>
                    <h5><?php echo formatRupiah($saldo_sistem); ?></h5>
                </div>
                <div class="mb-3">
                    <label class="text-muted">Saldo Fisik:</label>
                    <h5 id="displaySaldoFisik">Rp 0</h5>
                </div>
                <hr>
                <div>
                    <label class="text-muted">Selisih:</label>
                    <h4 id="displaySelisih">Rp 0</h4>
                    <small id="displayStatus" class="text-muted"></small>
                </div>
            </div>
        </div>

        <?php if ($rekonsiliasi_terakhir): ?>
        <div class="card mt-3">
            <div class="card-header bg-secondary text-white">
                <i class="bi bi-clock-history"></i> Rekonsiliasi Terakhir
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <small class="text-muted">Tanggal:</small><br>
                    <strong><?php echo formatDateTime($rekonsiliasi_terakhir['tanggal_transaksi']); ?></strong>
                </div>
                <div class="mb-2">
                    <small class="text-muted">Nominal:</small><br>
                    <strong class="<?php echo $rekonsiliasi_terakhir['jenis_transaksi'] == 'masuk' ? 'text-success' : 'text-danger'; ?>">
                        <?php echo formatRupiah($rekonsiliasi_terakhir['nominal']); ?>
                    </strong>
                </div>
                <div>
                    <small class="text-muted">Keterangan:</small><br>
                    <small><?php echo nl2br(htmlspecialchars($rekonsiliasi_terakhir['keterangan'])); ?></small>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card mt-3">
            <div class="card-header bg-warning">
                <i class="bi bi-lightbulb"></i> Tips
            </div>
            <div class="card-body">
                <ul class="mb-0 small">
                    <li>Lakukan rekonsiliasi <strong>setiap hari</strong></li>
                    <li>Hitung kas fisik dengan teliti</li>
                    <li>Catat penyebab selisih dengan jelas</li>
                    <li>Jika selisih > 1%, perlu investigasi</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
const saldoSistem = <?php echo $saldo_sistem; ?>;

document.getElementById('saldoFisik').addEventListener('input', function() {
    const saldoFisik = parseFloat(this.value) || 0;
    const selisih = saldoFisik - saldoSistem;
    
    document.getElementById('displaySaldoFisik').textContent = formatRupiah(saldoFisik);
    document.getElementById('displaySelisih').textContent = formatRupiah(selisih);
    
    const selisihInfo = document.getElementById('selisihInfo');
    const selisihAlert = document.getElementById('selisihAlert');
    const selisihNominal = document.getElementById('selisihNominal');
    const selisihKeterangan = document.getElementById('selisihKeterangan');
    const displayStatus = document.getElementById('displayStatus');
    
    if (selisih === 0) {
        selisihInfo.style.display = 'none';
        displayStatus.textContent = 'Saldo sesuai ✓';
        displayStatus.className = 'text-success';
        document.getElementById('displaySelisih').className = 'text-muted';
    } else if (selisih > 0) {
        selisihInfo.style.display = 'block';
        selisihAlert.className = 'alert alert-success';
        selisihNominal.textContent = formatRupiah(selisih);
        selisihKeterangan.innerHTML = '<i class="bi bi-arrow-up-circle"></i> Kas fisik <strong>LEBIH</strong> dari sistem (Selisih lebih)';
        displayStatus.textContent = 'Selisih lebih (+)';
        displayStatus.className = 'text-success';
        document.getElementById('displaySelisih').className = 'text-success';
    } else {
        selisihInfo.style.display = 'block';
        selisihAlert.className = 'alert alert-danger';
        selisihNominal.textContent = formatRupiah(Math.abs(selisih));
        selisihKeterangan.innerHTML = '<i class="bi bi-arrow-down-circle"></i> Kas fisik <strong>KURANG</strong> dari sistem (Selisih kurang)';
        displayStatus.textContent = 'Selisih kurang (-)';
        displayStatus.className = 'text-danger';
        document.getElementById('displaySelisih').className = 'text-danger';
    }
});

document.getElementById('formRekonsiliasi').addEventListener('submit', function(e) {
    const saldoFisik = parseFloat(document.getElementById('saldoFisik').value) || 0;
    const selisih = saldoFisik - saldoSistem;
    
    if (selisih === 0) {
        return confirm('Saldo kas sudah sesuai. Lanjutkan rekonsiliasi?');
    } else if (selisih > 0) {
        return confirm('Terdapat selisih LEBIH sebesar ' + formatRupiah(selisih) + '. Proses rekonsiliasi?');
    } else {
        return confirm('PERHATIAN! Terdapat selisih KURANG sebesar ' + formatRupiah(Math.abs(selisih)) + '. Pastikan sudah dicek dengan teliti. Proses rekonsiliasi?');
    }
});

function formatRupiah(number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(number);
}
</script>