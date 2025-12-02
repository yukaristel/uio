<?php
/**
 * FORM TAMBAH TRANSAKSI KAS MANUAL
 * Step 31/64 (48.4%)
 */

// Get saldo kas
$saldo_kas = fetchOne("SELECT saldo_sesudah FROM kas_umum ORDER BY created_at DESC, id DESC LIMIT 1");
$saldo = $saldo_kas ? $saldo_kas['saldo_sesudah'] : 0;
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-plus-circle"></i> Tambah Transaksi Kas Manual</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard_kas">Dashboard Kas</a></li>
                <li class="breadcrumb-item active">Tambah Transaksi</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-cash-coin"></i> Form Transaksi Kas
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-4">
                    <i class="bi bi-info-circle"></i>
                    <strong>Saldo Kas Saat Ini:</strong> <span class="fs-5"><?php echo formatRupiah($saldo); ?></span>
                </div>

                <form action="config/kas_proses.php?action=create" method="POST" id="formKas">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Jenis Transaksi *</label>
                                <select class="form-select" name="jenis_transaksi" id="jenisTransaksi" required>
                                    <option value="">-- Pilih Jenis --</option>
                                    <option value="masuk">Pemasukan</option>
                                    <option value="keluar">Pengeluaran</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kategori *</label>
                                <select class="form-select" name="kategori" id="kategori" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <option value="gaji">Gaji Karyawan</option>
                                    <option value="operasional">Operasional (Listrik, Air, dll)</option>
                                    <option value="investasi">Investasi/Modal</option>
                                    <option value="lainnya">Lainnya</option>
                                </select>
                                <small class="text-muted">
                                    Penjualan & Pembelian Bahan tercatat otomatis
                                </small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nominal *</label>
                                <input type="number" class="form-control" name="nominal" 
                                       id="nominal" required min="0" step="1000" placeholder="0">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tanggal Transaksi *</label>
                                <input type="date" class="form-control" name="tanggal_transaksi" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Keterangan</label>
                                <textarea class="form-control" name="keterangan" rows="3" 
                                          placeholder="Keterangan transaksi (opsional)"></textarea>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="alert alert-warning" id="alertSaldo" style="display:none;">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>Peringatan:</strong> Saldo kas tidak cukup untuk pengeluaran ini!
                            </div>
                        </div>

                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary" id="btnSubmit">
                                <i class="bi bi-save"></i> Simpan Transaksi
                            </button>
                            <a href="index.php?page=dashboard_kas" class="btn btn-secondary">
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
                <i class="bi bi-calculator"></i> Simulasi
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted">Jenis:</label>
                    <h5 id="displayJenis">-</h5>
                </div>
                <div class="mb-3">
                    <label class="text-muted">Nominal:</label>
                    <h4 id="displayNominal" class="text-primary">Rp 0</h4>
                </div>
                <hr>
                <div class="mb-3">
                    <label class="text-muted">Saldo Sebelum:</label>
                    <h5><?php echo formatRupiah($saldo); ?></h5>
                </div>
                <div class="mb-3">
                    <label class="text-muted">Saldo Sesudah:</label>
                    <h4 id="displaySaldoSesudah"><?php echo formatRupiah($saldo); ?></h4>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-warning">
                <i class="bi bi-lightbulb"></i> Tips
            </div>
            <div class="card-body">
                <ul class="mb-0 small">
                    <li><strong>Gaji:</strong> Pembayaran gaji karyawan</li>
                    <li><strong>Operasional:</strong> Listrik, air, internet, sewa, dll</li>
                    <li><strong>Investasi:</strong> Modal tambahan atau penarikan</li>
                    <li><strong>Lainnya:</strong> Transaksi lain yang tidak masuk kategori</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
const saldoKas = <?php echo $saldo; ?>;

document.getElementById('jenisTransaksi').addEventListener('change', hitung);
document.getElementById('nominal').addEventListener('input', hitung);

function hitung() {
    const jenis = document.getElementById('jenisTransaksi').value;
    const nominal = parseFloat(document.getElementById('nominal').value) || 0;
    
    let saldoSesudah = saldoKas;
    let displayJenis = '-';
    
    if (jenis === 'masuk') {
        saldoSesudah = saldoKas + nominal;
        displayJenis = '<span class="badge bg-success">Pemasukan</span>';
    } else if (jenis === 'keluar') {
        saldoSesudah = saldoKas - nominal;
        displayJenis = '<span class="badge bg-danger">Pengeluaran</span>';
    }
    
    document.getElementById('displayJenis').innerHTML = displayJenis;
    document.getElementById('displayNominal').textContent = formatRupiah(nominal);
    document.getElementById('displaySaldoSesudah').textContent = formatRupiah(saldoSesudah);
    
    // Validasi saldo untuk pengeluaran
    const alertSaldo = document.getElementById('alertSaldo');
    const btnSubmit = document.getElementById('btnSubmit');
    
    if (jenis === 'keluar' && nominal > saldoKas) {
        alertSaldo.style.display = 'block';
        btnSubmit.disabled = true;
    } else {
        alertSaldo.style.display = 'none';
        btnSubmit.disabled = false;
    }
}

function formatRupiah(number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(number);
}

// Validasi form
document.getElementById('formKas').addEventListener('submit', function(e) {
    const jenis = document.getElementById('jenisTransaksi').value;
    const nominal = parseFloat(document.getElementById('nominal').value) || 0;
    
    if (jenis === 'keluar' && nominal > saldoKas) {
        e.preventDefault();
        alert('Saldo kas tidak cukup!');
        return false;
    }
    
    return confirm('Simpan transaksi kas ini?');
});
</script>