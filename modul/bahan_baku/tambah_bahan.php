<?php
/**
 * FORM TAMBAH BAHAN BAKU (Harga Total)
 * Step 35/64 (54.7%)
 */
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-plus-circle"></i> Tambah Bahan Baku</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=list_bahan">Bahan Baku</a></li>
                <li class="breadcrumb-item active">Tambah Bahan</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-box-seam"></i> Form Data Bahan Baku
            </div>
            <div class="card-body">
                <form action="config/bahan_proses.php?action=create" method="POST" id="formBahan">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kode Bahan *</label>
                                <input type="text" class="form-control" name="kode_bahan" 
                                       placeholder="Contoh: BHN001" required maxlength="20"
                                       style="text-transform: uppercase;">
                                <small class="text-muted">Kode unik untuk identifikasi bahan</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nama Bahan *</label>
                                <input type="text" class="form-control" name="nama_bahan" 
                                       placeholder="Contoh: Beras Premium" required maxlength="100">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Satuan *</label>
                                <select class="form-select" name="satuan" id="satuan" required>
                                    <option value="">-- Pilih Satuan --</option>
                                    <option value="kg">Kilogram (kg)</option>
                                    <option value="gram">Gram</option>
                                    <option value="liter">Liter</option>
                                    <option value="ml">Mililiter (ml)</option>
                                    <option value="pcs">Pieces (pcs)</option>
                                    <option value="sachet">Sachet</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Stok Awal</label>
                                <input type="number" class="form-control" name="stok_tersedia" 
                                       id="stokAwal" value="0" min="0" step="0.01">
                                <small class="text-muted">Stok saat pertama kali input</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Stok Minimum *</label>
                                <input type="number" class="form-control" name="stok_minimum" 
                                       required min="0" step="0.01" placeholder="0">
                                <small class="text-muted">Batas stok untuk alert</small>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Total Harga Stok Awal</label>
                                <input type="number" class="form-control" name="total_harga_awal" 
                                       id="totalHargaAwal" min="0" step="100" placeholder="0">
                                <small class="text-muted">Total harga untuk <span id="infoStok">0</span> <span id="infoSatuan">-</span> 
                                (Harga per satuan: <strong id="hargaPerSatuan">Rp 0</strong>)</small>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <strong>Catatan:</strong>
                                <ul class="mb-0">
                                    <li>Kode bahan harus unik (tidak boleh sama)</li>
                                    <li>Input <strong>TOTAL HARGA</strong> untuk stok awal</li>
                                    <li>Harga per satuan akan dihitung otomatis</li>
                                    <li>Stok awal bisa 0 jika belum ada stok</li>
                                    <li>Harga akan otomatis dihitung weighted average saat pembelian</li>
                                    <li>Jika stok awal > 0, stock movement akan tercatat otomatis</li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Simpan Bahan
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
                <i class="bi bi-lightbulb"></i> Panduan
            </div>
            <div class="card-body">
                <h6>Contoh Satuan:</h6>
                <ul class="small">
                    <li><strong>kg:</strong> Beras, Gula, Tepung</li>
                    <li><strong>gram:</strong> Bumbu halus</li>
                    <li><strong>liter:</strong> Minyak, Susu</li>
                    <li><strong>ml:</strong> Saus, Kecap</li>
                    <li><strong>pcs:</strong> Telur, Sayuran</li>
                    <li><strong>sachet:</strong> Bumbu instant</li>
                </ul>

                <hr>

                <h6>Tips:</h6>
                <ul class="small mb-0">
                    <li>Gunakan kode yang mudah diingat</li>
                    <li>Set stok minimum 20% dari stok normal</li>
                    <li>Input total harga beli, bukan per satuan</li>
                    <li>Harga akan auto-update saat pembelian</li>
                </ul>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-warning">
                <i class="bi bi-exclamation-triangle"></i> Perhatian
            </div>
            <div class="card-body">
                <ul class="small mb-0">
                    <li>Satuan tidak bisa diubah setelah bahan dipakai di resep</li>
                    <li>Pastikan satuan sudah benar sebelum menyimpan</li>
                    <li>Konversi otomatis hanya: kg↔gram, liter↔ml</li>
                    <li>Jika stok awal > 0, wajib isi total harga</li>
                </ul>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-success text-white">
                <i class="bi bi-calculator"></i> Perhitungan
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <label class="text-muted">Stok Awal:</label>
                    <h5 id="displayStok">0 -</h5>
                </div>
                <div class="mb-2">
                    <label class="text-muted">Total Harga:</label>
                    <h5 id="displayTotal">Rp 0</h5>
                </div>
                <hr>
                <div>
                    <label class="text-muted">Harga per Satuan:</label>
                    <h5 id="displayHargaSatuan">Rp 0</h5>
                    <small class="text-muted">(otomatis dihitung)</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('satuan').addEventListener('change', updateInfo);
document.getElementById('stokAwal').addEventListener('input', updateInfo);
document.getElementById('totalHargaAwal').addEventListener('input', updateInfo);

function updateInfo() {
    const satuan = document.getElementById('satuan').value || '-';
    const stokAwal = parseFloat(document.getElementById('stokAwal').value) || 0;
    const totalHarga = parseFloat(document.getElementById('totalHargaAwal').value) || 0;
    
    // Hitung harga per satuan
    const hargaSatuan = stokAwal > 0 ? totalHarga / stokAwal : 0;
    
    // Update info di bawah input
    document.getElementById('infoStok').textContent = stokAwal.toFixed(2);
    document.getElementById('infoSatuan').textContent = satuan;
    document.getElementById('hargaPerSatuan').textContent = formatRupiah(hargaSatuan);
    
    // Update card perhitungan
    document.getElementById('displayStok').textContent = stokAwal.toFixed(2) + ' ' + satuan;
    document.getElementById('displayTotal').textContent = formatRupiah(totalHarga);
    document.getElementById('displayHargaSatuan').textContent = formatRupiah(hargaSatuan) + '/' + satuan;
}

function formatRupiah(number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(number);
}

// Validasi form sebelum submit
document.getElementById('formBahan').addEventListener('submit', function(e) {
    const stokAwal = parseFloat(document.getElementById('stokAwal').value) || 0;
    const totalHarga = parseFloat(document.getElementById('totalHargaAwal').value) || 0;
    
    if (stokAwal > 0 && totalHarga <= 0) {
        e.preventDefault();
        alert('Jika stok awal > 0, total harga harus diisi!');
        return false;
    }
});
</script>