<?php
/**
 * FORM TAMBAH STOCK OPNAME
 * Step 51/64 (79.7%)
 */

$bahan_list = fetchAll("SELECT * FROM bahan_baku ORDER BY nama_bahan");
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-plus-circle"></i> Buat Stock Opname</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=list_opname">Stock Opname</a></li>
                <li class="breadcrumb-item active">Buat Opname</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clipboard-check"></i> Form Stock Opname
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Panduan Stock Opname:</strong>
                    <ol class="mb-0">
                        <li>Pilih bahan yang akan di-opname</li>
                        <li>Sistem akan menampilkan stok tercatat</li>
                        <li>Hitung stok fisik di gudang/rak</li>
                        <li>Input hasil penghitungan fisik</li>
                        <li>Sistem otomatis hitung selisih</li>
                        <li>Simpan sebagai draft → Admin approve</li>
                    </ol>
                </div>

                <form action="config/stock_opname_proses.php?action=create" method="POST" id="formOpname">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tanggal Opname *</label>
                                <input type="date" class="form-control" name="tanggal_opname" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>

                        <div class="col-md-6">
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
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="card bg-light mb-3" id="infoStok" style="display:none;">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="text-muted">Stok Sistem (Tercatat):</label>
                                            <h4 id="stokSistem">0</h4>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="text-muted">Harga per Satuan:</label>
                                            <h5 id="hargaSatuan">Rp 0</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Stok Fisik (Hasil Hitung) *</label>
                                <input type="number" class="form-control form-control-lg" 
                                       name="stok_fisik" id="stokFisik" required 
                                       min="0" step="0.01" placeholder="0">
                                <small class="text-muted">Hasil penghitungan stok di gudang/rak</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Jenis Selisih</label>
                                <select class="form-select" name="jenis_selisih" id="jenisSelisih">
                                    <option value="">-- Auto (jika ada selisih) --</option>
                                    <option value="hilang">Hilang</option>
                                    <option value="rusak">Rusak</option>
                                    <option value="expired">Expired</option>
                                    <option value="tumpah">Tumpah</option>
                                    <option value="salah_hitung">Salah Hitung</option>
                                    <option value="lainnya">Lainnya</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Keterangan</label>
                                <textarea class="form-control" name="keterangan" rows="2"
                                          placeholder="Keterangan tambahan (opsional)"></textarea>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="alert" id="alertSelisih" style="display:none;"></div>
                        </div>

                        <div class="col-md-12">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-save"></i> Simpan sebagai Draft
                            </button>
                            <a href="index.php?page=list_opname" class="btn btn-secondary">
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
                    <label class="text-muted">Stok Sistem:</label>
                    <h5 id="displayStokSistem">0</h5>
                </div>
                <div class="mb-3">
                    <label class="text-muted">Stok Fisik:</label>
                    <h5 id="displayStokFisik">0</h5>
                </div>
                <hr>
                <div class="mb-3">
                    <label class="text-muted">Selisih:</label>
                    <h4 id="displaySelisih">0</h4>
                    <small id="displayStatus"></small>
                </div>
                <hr>
                <div>
                    <label class="text-muted">Nilai Selisih:</label>
                    <h4 id="displayNilai">Rp 0</h4>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-warning">
                <i class="bi bi-exclamation-triangle"></i> Catatan Penting
            </div>
            <div class="card-body">
                <ul class="small mb-0">
                    <li>Hitung stok dengan teliti dan benar</li>
                    <li>Opname disimpan sebagai <strong>DRAFT</strong></li>
                    <li>Admin akan review & approve</li>
                    <li>Setelah approved, stok sistem akan disesuaikan</li>
                    <li>Jika ada selisih, wajib isi jenis & keterangan</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
let stokSistem = 0;
let hargaSatuan = 0;
let satuanBahan = '';

document.getElementById('bahanSelect').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    if (this.value) {
        stokSistem = parseFloat(option.dataset.stok);
        hargaSatuan = parseFloat(option.dataset.harga);
        satuanBahan = option.dataset.satuan;
        
        document.getElementById('infoStok').style.display = 'block';
        document.getElementById('stokSistem').textContent = stokSistem.toFixed(2) + ' ' + satuanBahan;
        document.getElementById('hargaSatuan').textContent = formatRupiah(hargaSatuan) + '/' + satuanBahan;
        document.getElementById('displayStokSistem').textContent = stokSistem.toFixed(2) + ' ' + satuanBahan;
        
        hitung();
    } else {
        document.getElementById('infoStok').style.display = 'none';
    }
});

document.getElementById('stokFisik').addEventListener('input', hitung);

function hitung() {
    const stokFisik = parseFloat(document.getElementById('stokFisik').value) || 0;
    const selisih = stokFisik - stokSistem;
    const nilaiSelisih = selisih * hargaSatuan;
    
    document.getElementById('displayStokFisik').textContent = stokFisik.toFixed(2) + ' ' + satuanBahan;
    document.getElementById('displaySelisih').textContent = (selisih > 0 ? '+' : '') + selisih.toFixed(2) + ' ' + satuanBahan;
    document.getElementById('displayNilai').textContent = formatRupiah(nilaiSelisih);
    
    const displayStatus = document.getElementById('displayStatus');
    const alertSelisih = document.getElementById('alertSelisih');
    const jenisSelisih = document.getElementById('jenisSelisih');
    
    if (selisih === 0) {
        displayStatus.textContent = '✓ Stok sesuai';
        displayStatus.className = 'text-success';
        document.getElementById('displaySelisih').className = 'text-muted';
        document.getElementById('displayNilai').className = 'text-muted';
        alertSelisih.style.display = 'none';
        jenisSelisih.required = false;
    } else if (selisih > 0) {
        displayStatus.textContent = '(Stok fisik LEBIH dari sistem)';
        displayStatus.className = 'text-success';
        document.getElementById('displaySelisih').className = 'text-success';
        document.getElementById('displayNilai').className = 'text-success';
        alertSelisih.className = 'alert alert-success';
        alertSelisih.innerHTML = '<i class="bi bi-arrow-up-circle"></i> <strong>Selisih Lebih:</strong> ' + formatRupiah(Math.abs(nilaiSelisih));
        alertSelisih.style.display = 'block';
        jenisSelisih.required = false;
    } else {
        displayStatus.textContent = '(Stok fisik KURANG dari sistem)';
        displayStatus.className = 'text-danger';
        document.getElementById('displaySelisih').className = 'text-danger';
        document.getElementById('displayNilai').className = 'text-danger';
        alertSelisih.className = 'alert alert-danger';
        alertSelisih.innerHTML = '<i class="bi bi-arrow-down-circle"></i> <strong>Selisih Kurang (KERUGIAN):</strong> ' + formatRupiah(Math.abs(nilaiSelisih));
        alertSelisih.style.display = 'block';
        jenisSelisih.required = true;
    }
}

document.getElementById('formOpname').addEventListener('submit', function(e) {
    const selisih = parseFloat(document.getElementById('displaySelisih').textContent);
    
    if (selisih < 0) {
        const jenisSelisih = document.getElementById('jenisSelisih').value;
        if (!jenisSelisih) {
            e.preventDefault();
            alert('Jenis selisih wajib diisi untuk selisih kurang!');
            return false;
        }
    }
    
    return confirm('Simpan stock opname ini sebagai draft?');
});

function formatRupiah(number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(number);
}
</script>