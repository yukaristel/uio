<?php
/**
 * KELOLA RESEP MENU (Komposisi Bahan)
 * Step 43/64 (67.2%)
 */

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    $_SESSION['error'] = 'ID menu tidak valid!';
    header('Location: index.php?page=list_menu');
    exit;
}

$menu = fetchOne("SELECT m.*, k.nama_kategori FROM menu_makanan m JOIN kategori_menu k ON m.kategori_id = k.id WHERE m.id = ?", [$id]);

if (!$menu) {
    $_SESSION['error'] = 'Menu tidak ditemukan!';
    header('Location: index.php?page=list_menu');
    exit;
}

// Get resep
$resep = fetchAll("
    SELECT r.*, b.nama_bahan, b.satuan as satuan_bahan, b.harga_beli_per_satuan 
    FROM resep_menu r 
    JOIN bahan_baku b ON r.bahan_id = b.id 
    WHERE r.menu_id = ?
    ORDER BY r.created_at
", [$id]);

// Get bahan untuk dropdown
$bahan_list = fetchAll("SELECT * FROM bahan_baku ORDER BY nama_bahan");
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-list-check"></i> Resep: <?php echo $menu['nama_menu']; ?></h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=list_menu">Menu</a></li>
                <li class="breadcrumb-item active">Resep</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <!-- Form Tambah Bahan -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <i class="bi bi-plus-circle"></i> Tambah Bahan ke Resep
            </div>
            <div class="card-body">
                <form action="config/resep_proses.php?action=create" method="POST" id="formResep">
                    <input type="hidden" name="menu_id" value="<?php echo $menu['id']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Bahan *</label>
                        <select class="form-select" name="bahan_id" id="bahanSelect" required>
                            <option value="">-- Pilih Bahan --</option>
                            <?php foreach ($bahan_list as $bahan): ?>
                            <option value="<?php echo $bahan['id']; ?>"
                                    data-satuan="<?php echo $bahan['satuan']; ?>"
                                    data-harga="<?php echo $bahan['harga_beli_per_satuan']; ?>">
                                <?php echo $bahan['nama_bahan']; ?> (<?php echo $bahan['satuan']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jumlah *</label>
                        <input type="number" class="form-control" name="jumlah_bahan" 
                               id="jumlahBahan" required min="0.01" step="0.01" placeholder="0">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Satuan *</label>
                        <select class="form-select" name="satuan" id="satuanSelect" required>
                            <option value="">Pilih bahan dulu</option>
                        </select>
                        <small class="text-muted" id="infoKonversi"></small>
                    </div>

                    <div class="mb-3">
                        <div class="alert alert-info" id="previewBiaya" style="display:none;">
                            <small><strong>Preview Biaya:</strong></small><br>
                            <span id="displayBiaya">Rp 0</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-plus"></i> Tambah ke Resep
                    </button>
                </form>
            </div>
        </div>

        <!-- Summary HPP -->
        <div class="card mt-3">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-calculator"></i> HPP Menu
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <small class="text-muted">Total HPP:</small>
                    <h4 class="text-primary"><?php echo formatRupiah($menu['harga_modal']); ?></h4>
                </div>
                <div class="mb-2">
                    <small class="text-muted">Harga Jual:</small>
                    <h5><?php echo formatRupiah($menu['harga_jual']); ?></h5>
                </div>
                <hr>
                <div>
                    <small class="text-muted">Margin:</small>
                    <h4 class="text-success"><?php echo formatRupiah($menu['margin_keuntungan']); ?></h4>
                    <small class="text-muted">
                        (<?php echo $menu['harga_jual'] > 0 ? number_format(($menu['margin_keuntungan'] / $menu['harga_jual']) * 100, 1) : 0; ?>%)
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Daftar Resep -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-list-ul"></i> Komposisi Bahan</span>
                <div>
                    <a href="config/resep_proses.php?action=recalculate&menu_id=<?php echo $menu['id']; ?>" 
                       class="btn btn-info btn-sm">
                        <i class="bi bi-arrow-repeat"></i> Recalculate HPP
                    </a>
                    <a href="index.php?page=edit_menu&id=<?php echo $menu['id']; ?>" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($resep)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-3">Belum ada bahan di resep</p>
                        <p class="text-danger"><strong>Menu tanpa resep tidak bisa dijual!</strong></p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Bahan</th>
                                    <th>Jumlah</th>
                                    <th>Satuan</th>
                                    <th>Harga/Satuan</th>
                                    <th>Biaya</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                $total_hpp = 0;
                                foreach ($resep as $r): 
                                    $total_hpp += $r['biaya_bahan'];
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><strong><?php echo $r['nama_bahan']; ?></strong></td>
                                    <td><?php echo number_format($r['jumlah_bahan'], 2); ?></td>
                                    <td><?php echo $r['satuan']; ?></td>
                                    <td><?php echo formatRupiah($r['harga_beli_per_satuan']); ?></td>
                                    <td class="text-success"><strong><?php echo formatRupiah($r['biaya_bahan']); ?></strong></td>
                                    <td>
                                        <a href="config/resep_proses.php?action=delete&id=<?php echo $r['id']; ?>&menu_id=<?php echo $menu['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Hapus bahan ini dari resep?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-primary">
                                    <th colspan="5" class="text-end">TOTAL HPP:</th>
                                    <th class="text-primary"><?php echo formatRupiah($total_hpp); ?></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <?php if ($menu['harga_jual'] <= $total_hpp): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Perhatian!</strong> Harga jual (<?php echo formatRupiah($menu['harga_jual']); ?>) 
                        lebih kecil atau sama dengan HPP (<?php echo formatRupiah($total_hpp); ?>). 
                        <strong>Menu ini akan RUGI!</strong>
                        <a href="index.php?page=edit_menu&id=<?php echo $menu['id']; ?>">Ubah harga jual</a>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('bahanSelect').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    const satuanBahan = option.dataset.satuan;
    const satuanSelect = document.getElementById('satuanSelect');
    
    // Reset
    satuanSelect.innerHTML = '<option value="">-- Pilih Satuan --</option>';
    document.getElementById('infoKonversi').textContent = '';
    
    if (this.value) {
        // Set satuan yang valid berdasarkan satuan bahan
        const validSatuans = getSatuanValid(satuanBahan);
        validSatuans.forEach(satuan => {
            const opt = document.createElement('option');
            opt.value = satuan;
            opt.textContent = satuan;
            satuanSelect.appendChild(opt);
        });
        
        // Set info konversi
        if (validSatuans.length > 1) {
            document.getElementById('infoKonversi').textContent = 
                'Bahan: ' + satuanBahan + ' (Bisa dikonversi ke: ' + validSatuans.join(', ') + ')';
        }
    }
    
    hitungBiaya();
});

document.getElementById('jumlahBahan').addEventListener('input', hitungBiaya);
document.getElementById('satuanSelect').addEventListener('change', hitungBiaya);

function getSatuanValid(satuanBahan) {
    const konversi = {
        'kg': ['kg', 'gram'],
        'gram': ['kg', 'gram'],
        'liter': ['liter', 'ml'],
        'ml': ['liter', 'ml'],
        'pcs': ['pcs'],
        'sachet': ['sachet']
    };
    return konversi[satuanBahan] || [satuanBahan];
}

function hitungBiaya() {
    const bahanSelect = document.getElementById('bahanSelect');
    const option = bahanSelect.options[bahanSelect.selectedIndex];
    
    if (!option.value) return;
    
    const satuanBahan = option.dataset.satuan;
    const hargaBahan = parseFloat(option.dataset.harga);
    const jumlah = parseFloat(document.getElementById('jumlahBahan').value) || 0;
    const satuanResep = document.getElementById('satuanSelect').value;
    
    if (!satuanResep || jumlah === 0) {
        document.getElementById('previewBiaya').style.display = 'none';
        return;
    }
    
    // Konversi ke satuan bahan
    let jumlahKonversi = jumlah;
    if (satuanBahan === 'kg' && satuanResep === 'gram') {
        jumlahKonversi = jumlah / 1000;
    } else if (satuanBahan === 'gram' && satuanResep === 'kg') {
        jumlahKonversi = jumlah * 1000;
    } else if (satuanBahan === 'liter' && satuanResep === 'ml') {
        jumlahKonversi = jumlah / 1000;
    } else if (satuanBahan === 'ml' && satuanResep === 'liter') {
        jumlahKonversi = jumlah * 1000;
    }
    
    const biaya = jumlahKonversi * hargaBahan;
    document.getElementById('displayBiaya').textContent = formatRupiah(biaya);
    document.getElementById('previewBiaya').style.display = 'block';
}

function formatRupiah(number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(number);
}
</script>