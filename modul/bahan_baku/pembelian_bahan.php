<?php
/**
 * FORM PEMBELIAN BAHAN BAKU (Harga Total)
 * Step 29/64 (45.3%)
 */

// Get daftar bahan
$bahan_list = fetchAll("SELECT * FROM bahan_baku ORDER BY nama_bahan");

// Get saldo kas
$saldo_kas = fetchOne("SELECT saldo_sesudah FROM kas_umum ORDER BY created_at DESC, id DESC LIMIT 1");
$saldo = $saldo_kas ? $saldo_kas['saldo_sesudah'] : 0;

// Jika ada parameter bahan_id (dari alert stok menipis)
$bahan_id_selected = isset($_GET['bahan_id']) ? intval($_GET['bahan_id']) : 0;
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-cart-plus"></i> Pembelian Bahan Baku</h2>
    </div>
</div>

<!-- Saldo Kas -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="alert alert-info">
            <i class="bi bi-wallet2"></i> <strong>Saldo Kas Terkini:</strong> 
            <span class="fs-5"><?php echo formatRupiah($saldo); ?></span>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-plus-circle"></i> Form Pembelian Bahan
            </div>
            <div class="card-body">
                <form action="config/pembelian_proses.php?action=create" method="POST" id="formPembelian">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Pilih Bahan *</label>
                                <select class="form-select" name="bahan_id" id="bahanSelect" required>
                                    <option value="">-- Pilih Bahan --</option>
                                    <?php foreach ($bahan_list as $bahan): ?>
                                    <option value="<?php echo $bahan['id']; ?>"
                                            data-satuan="<?php echo $bahan['satuan']; ?>"
                                            data-stok="<?php echo $bahan['stok_tersedia']; ?>"
                                            data-harga="<?php echo $bahan['harga_beli_per_satuan']; ?>"
                                            <?php echo $bahan_id_selected == $bahan['id'] ? 'selected' : ''; ?>>
                                        <?php echo $bahan['nama_bahan']; ?> 
                                        (Stok: <?php echo number_format($bahan['stok_tersedia'], 2); ?> <?php echo $bahan['satuan']; ?>)
                                        <?php if ($bahan['stok_tersedia'] <= $bahan['stok_minimum']): ?>
                                            ⚠️ MENIPIS
                                        <?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="infoBahan" class="mt-2" style="display:none;">
                                    <small class="text-muted">
                                        <strong>Stok Saat Ini:</strong> <span id="stokSekarang">0</span> <span id="satuanBahan">-</span><br>
                                        <strong>Harga Rata-rata Sekarang:</strong> <span id="hargaSekarang">Rp 0</span>/<span id="satuanHarga">-</span>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Jumlah Beli *</label>
                                <input type="number" class="form-control" name="jumlah_beli" 
                                       id="jumlahBeli" required min="0.01" step="0.01" placeholder="0">
                                <small class="text-muted">Satuan: <span id="satuanJumlah">-</span></small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Total Harga Beli *</label>
                                <input type="number" class="form-control" name="total_harga_beli" 
                                       id="totalHargaBeli" required min="0" step="100" placeholder="0">
                                <small class="text-muted">Total harga untuk <span id="jumlahInfo">0</span> <span id="satuanInfo">-</span></small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Supplier</label>
                                <input type="text" class="form-control" name="supplier" 
                                       placeholder="Nama supplier (opsional)">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tanggal Beli *</label>
                                <input type="date" class="form-control" name="tanggal_beli" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="alert alert-warning" id="alertSaldo" style="display:none;">
                                <i class="bi bi-exclamation-triangle"></i> 
                                <strong>Peringatan:</strong> Total pembelian melebihi saldo kas!
                            </div>
                        </div>

                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary" id="btnSubmit">
                                <i class="bi bi-save"></i> Proses Pembelian
                            </button>
                            <a href="index.php?page=list_bahan" class="btn btn-secondary">
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
                <i class="bi bi-calculator"></i> Perhitungan
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted">Jumlah Beli:</label>
                    <h5 id="displayJumlah">0</h5>
                </div>
                <div class="mb-3">
                    <label class="text-muted">Total Harga:</label>
                    <h4 class="text-primary" id="displayTotal">Rp 0</h4>
                </div>
                <div class="mb-3">
                    <label class="text-muted">Harga per Satuan:</label>
                    <h5 id="displayHargaSatuan">Rp 0</h5>
                    <small class="text-muted">(otomatis dihitung)</small>
                </div>
                <hr>
                <div class="mb-3">
                    <label class="text-muted">Stok Setelah Pembelian:</label>
                    <h5 id="displayStokBaru">0</h5>
                </div>
                <div class="mb-3">
                    <label class="text-muted">Harga Rata-rata Baru:</label>
                    <h5 id="displayHargaBaru">Rp 0</h5>
                    <small class="text-muted">(Weighted Average)</small>
                </div>
                <hr>
                <div>
                    <label class="text-muted">Saldo Kas Sesudah:</label>
                    <h5 id="displaySaldoSesudah"><?php echo formatRupiah($saldo); ?></h5>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-warning">
                <i class="bi bi-info-circle"></i> Informasi
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>Input <strong>TOTAL HARGA</strong> pembelian</li>
                    <li>Harga per satuan dihitung otomatis</li>
                    <li>Sistem menggunakan <strong>Weighted Average</strong></li>
                    <li>Kas keluar tercatat otomatis</li>
                    <li>Stock movement tercatat otomatis</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
const saldoKas = <?php echo $saldo; ?>;
let stokSekarang = 0;
let hargaSekarang = 0;

document.getElementById('bahanSelect').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    if (this.value) {
        const satuan = option.dataset.satuan;
        stokSekarang = parseFloat(option.dataset.stok);
        hargaSekarang = parseFloat(option.dataset.harga);
        
        document.getElementById('infoBahan').style.display = 'block';
        document.getElementById('stokSekarang').textContent = stokSekarang.toFixed(2);
        document.getElementById('satuanBahan').textContent = satuan;
        document.getElementById('hargaSekarang').textContent = formatRupiah(hargaSekarang);
        document.getElementById('satuanHarga').textContent = satuan;
        
        document.getElementById('satuanJumlah').textContent = satuan;
        document.getElementById('satuanInfo').textContent = satuan;
        
        hitung();
    } else {
        document.getElementById('infoBahan').style.display = 'none';
    }
});

document.getElementById('jumlahBeli').addEventListener('input', function() {
    const jumlah = parseFloat(this.value) || 0;
    const satuan = document.getElementById('bahanSelect').options[document.getElementById('bahanSelect').selectedIndex].dataset.satuan || '-';
    document.getElementById('jumlahInfo').textContent = jumlah.toFixed(2);
    hitung();
});

document.getElementById('totalHargaBeli').addEventListener('input', hitung);

function hitung() {
    const jumlah = parseFloat(document.getElementById('jumlahBeli').value) || 0;
    const totalHarga = parseFloat(document.getElementById('totalHargaBeli').value) || 0;
    const satuan = document.getElementById('bahanSelect').options[document.getElementById('bahanSelect').selectedIndex].dataset.satuan || '-';
    
    // Hitung harga per satuan
    const hargaPerSatuan = jumlah > 0 ? totalHarga / jumlah : 0;
    
    const stokBaru = stokSekarang + jumlah;
    
    // Hitung weighted average
    const nilaiLama = stokSekarang * hargaSekarang;
    const nilaiBaru = totalHarga;
    const totalNilai = nilaiLama + nilaiBaru;
    const hargaBaru = stokBaru > 0 ? totalNilai / stokBaru : hargaPerSatuan;
    
    const saldoSesudah = saldoKas - totalHarga;
    
    document.getElementById('displayJumlah').textContent = jumlah.toFixed(2) + ' ' + satuan;
    document.getElementById('displayTotal').textContent = formatRupiah(totalHarga);
    document.getElementById('displayHargaSatuan').textContent = formatRupiah(hargaPerSatuan) + '/' + satuan;
    document.getElementById('displayStokBaru').textContent = stokBaru.toFixed(2) + ' ' + satuan;
    document.getElementById('displayHargaBaru').textContent = formatRupiah(hargaBaru) + '/' + satuan;
    document.getElementById('displaySaldoSesudah').textContent = formatRupiah(saldoSesudah);
    
    // Validasi saldo
    const alertSaldo = document.getElementById('alertSaldo');
    const btnSubmit = document.getElementById('btnSubmit');
    
    if (totalHarga > saldoKas) {
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

// Auto-select jika ada parameter
if (document.getElementById('bahanSelect').value) {
    document.getElementById('bahanSelect').dispatchEvent(new Event('change'));
}
</script>