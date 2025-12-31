<?php
/**
 * FORM TAMBAH STOCK MOVEMENT MANUAL
 * (Untuk catat bahan rusak, tumpah, expired, hilang)
 * Step 49/64 (76.6%)
 */

$bahan_list = fetchAll("SELECT * FROM bahan_baku ORDER BY nama_bahan");
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-plus-circle"></i> Catat Stock Movement Manual</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=list_movement">Stock Movement</a></li>
                <li class="breadcrumb-item active">Tambah Movement</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-warning">
                <i class="bi bi-exclamation-triangle"></i> Form Catat Movement Manual
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Kapan menggunakan form ini?</strong><br>
                    Gunakan form ini untuk mencatat bahan yang rusak, tumpah, expired, atau hilang 
                    yang tidak tercatat otomatis oleh sistem.
                </div>

                <form action="config/stock_movement_proses.php?action=create" method="POST" id="formMovement">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Pilih Bahan *</label>
                                <select class="form-select" name="bahan_id" id="bahanSelect" required>
                                    <option value="">-- Pilih Bahan --</option>
                                    <?php foreach ($bahan_list as $bahan): ?>
                                    <option value="<?php echo $bahan['id']; ?>"
                                            data-stok="<?php echo $bahan['stok_tersedia']; ?>"
                                            data-satuan="<?php echo $bahan['satuan']; ?>"
                                            data-harga="<?php echo $bahan['harga_beli_per_satuan']; ?>">
                                        <?php echo $bahan['nama_bahan']; ?> 
                                        (Stok: <?php echo number_format($bahan['stok_tersedia'], 2); ?> <?php echo $bahan['satuan']; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="infoBahan" class="mt-2" style="display:none;">
                                    <small class="text-muted">
                                        <strong>Stok Saat Ini:</strong> <span id="stokSekarang">0</span> <span id="satuanBahan">-</span><br>
                                        <strong>Harga:</strong> <span id="hargaBahan">Rp 0</span>/<span id="satuanHarga">-</span>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Jenis Pergerakan *</label>
                                <select class="form-select" name="jenis_pergerakan" required>
                                    <option value="">-- Pilih Jenis --</option>
                                    <option value="rusak">Rusak (Tidak Layak Pakai)</option>
                                    <option value="tumpah">Tumpah/Terbuang</option>
                                    <option value="expired">Expired/Kadaluarsa</option>
                                    <option value="hilang">Hilang</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Jumlah *</label>
                                <input type="number" class="form-control" name="jumlah" 
                                       id="jumlah" required min="0.01" step="0.01" placeholder="0">
                                <small class="text-muted">Satuan: <span id="satuanJumlah">-</span></small>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Keterangan *</label>
                                <textarea class="form-control" name="keterangan" rows="3" required
                                          placeholder="Jelaskan penyebab (misal: Tumpah saat proses memasak, Telur pecah saat pengiriman, dll)"></textarea>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="alert alert-hide alert-warning" id="alertStok" style="display:none;">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>Peringatan:</strong> Jumlah melebihi stok yang tersedia!
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="alert alert-hide alert-danger" id="alertKerugian" style="display:none;">
                                <i class="bi bi-x-circle"></i>
                                <strong>Kerugian:</strong> <span id="nilaiKerugian">Rp 0</span>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <button type="submit" class="btn btn-warning" id="btnSubmit">
                                <i class="bi bi-save"></i> Catat Movement
                            </button>
                            <a href="index.php?page=list_movement" class="btn btn-secondary">
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
                    <label class="text-muted">Bahan:</label>
                    <h5 id="displayBahan">-</h5>
                </div>
                <div class="mb-3">
                    <label class="text-muted">Jumlah:</label>
                    <h5 id="displayJumlah">0</h5>
                </div>
                <div class="mb-3">
                    <label class="text-muted">Harga per Satuan:</label>
                    <h5 id="displayHarga">Rp 0</h5>
                </div>
                <hr>
                <div class="mb-3">
                    <label class="text-muted">Nilai Kerugian:</label>
                    <h4 class="text-danger" id="displayKerugian">Rp 0</h4>
                </div>
                <hr>
                <div>
                    <label class="text-muted">Stok Setelah:</label>
                    <h5 id="displayStokAkhir">0</h5>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-warning">
                <i class="bi bi-lightbulb"></i> Contoh Kasus
            </div>
            <div class="card-body">
                <ul class="small mb-0">
                    <li><strong>Rusak:</strong> Sayuran busuk, daging basi</li>
                    <li><strong>Tumpah:</strong> Minyak tumpah, susu tercecer</li>
                    <li><strong>Expired:</strong> Bahan melewati tanggal kadaluarsa</li>
                    <li><strong>Hilang:</strong> Bahan tidak diketahui keberadaannya</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
let stokSekarang = 0;
let hargaBahan = 0;

document.getElementById('bahanSelect').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    if (this.value) {
        stokSekarang = parseFloat(option.dataset.stok);
        hargaBahan = parseFloat(option.dataset.harga);
        const satuan = option.dataset.satuan;
        const namaBahan = option.text.split(' (')[0];
        
        document.getElementById('infoBahan').style.display = 'block';
        document.getElementById('stokSekarang').textContent = stokSekarang.toFixed(2);
        document.getElementById('satuanBahan').textContent = satuan;
        document.getElementById('hargaBahan').textContent = formatRupiah(hargaBahan);
        document.getElementById('satuanHarga').textContent = satuan;
        document.getElementById('satuanJumlah').textContent = satuan;
        document.getElementById('displayBahan').textContent = namaBahan;
        
        hitung();
    } else {
        document.getElementById('infoBahan').style.display = 'none';
        document.getElementById('displayBahan').textContent = '-';
    }
});

document.getElementById('jumlah').addEventListener('input', hitung);

function hitung() {
    const jumlah = parseFloat(document.getElementById('jumlah').value) || 0;
    const option = document.getElementById('bahanSelect').options[document.getElementById('bahanSelect').selectedIndex];
    const satuan = option.dataset ? option.dataset.satuan : '-';
    
    const kerugian = jumlah * hargaBahan;
    const stokAkhir = stokSekarang - jumlah;
    
    document.getElementById('displayJumlah').textContent = jumlah.toFixed(2) + ' ' + satuan;
    document.getElementById('displayHarga').textContent = formatRupiah(hargaBahan) + '/' + satuan;
    document.getElementById('displayKerugian').textContent = formatRupiah(kerugian);
    document.getElementById('displayStokAkhir').textContent = stokAkhir.toFixed(2) + ' ' + satuan;
    
    // Validasi stok
    const alertStok = document.getElementById('alertStok');
    const alertKerugian = document.getElementById('alertKerugian');
    const btnSubmit = document.getElementById('btnSubmit');
    
    if (jumlah > stokSekarang) {
        alertStok.style.display = 'block';
        btnSubmit.disabled = true;
    } else {
        alertStok.style.display = 'none';
        btnSubmit.disabled = false;
    }
    
    if (kerugian > 0) {
        alertKerugian.style.display = 'block';
        document.getElementById('nilaiKerugian').textContent = formatRupiah(kerugian);
    } else {
        alertKerugian.style.display = 'none';
    }
}

document.getElementById('formMovement').addEventListener('submit', function(e) {
    const jumlah = parseFloat(document.getElementById('jumlah').value) || 0;
    const kerugian = jumlah * hargaBahan;
    
    return confirm('Catat movement ini? Kerugian: ' + formatRupiah(kerugian));
});

function formatRupiah(number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(number);
}
</script>