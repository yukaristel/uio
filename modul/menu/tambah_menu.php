<?php
/**
 * FORM TAMBAH MENU MAKANAN (dengan Auto Generate Kode)
 * Step 41/64 (64.1%)
 */

// Get kategori
$kategori_list = fetchAll("SELECT * FROM kategori_menu ORDER BY nama_kategori");

// Generate kode menu otomatis
$last_menu = fetchOne("SELECT kode_menu FROM menu_makanan ORDER BY id DESC LIMIT 1");
if ($last_menu) {
    // Ambil angka dari kode terakhir
    preg_match('/\d+/', $last_menu['kode_menu'], $matches);
    $last_number = isset($matches[0]) ? intval($matches[0]) : 0;
    $new_number = $last_number + 1;
    $kode_auto = 'MNU' . str_pad($new_number, 3, '0', STR_PAD_LEFT);
} else {
    $kode_auto = 'MNU001';
}
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-plus-circle"></i> Tambah Menu Makanan</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=list_menu">Menu</a></li>
                <li class="breadcrumb-item active">Tambah Menu</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-card-list"></i> Form Data Menu
            </div>
            <div class="card-body">
                <form action="config/menu_proses.php?action=create" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kode Menu *</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="kode_menu" id="kodeMenu"
                                           placeholder="Contoh: MNU001" required maxlength="20"
                                           value="<?php echo $kode_auto; ?>"
                                           style="text-transform: uppercase;">
                                    <button class="btn btn-outline-secondary" type="button" id="btnAutoKode">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                    <button class="btn btn-outline-info" type="button" id="btnManualKode">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Kode otomatis: <strong><?php echo $kode_auto; ?></strong></small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nama Menu *</label>
                                <input type="text" class="form-control" name="nama_menu" 
                                       placeholder="Contoh: Nasi Goreng Spesial" required maxlength="100">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kategori *</label>
                                <select class="form-select" name="kategori_id" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($kategori_list as $kat): ?>
                                    <option value="<?php echo $kat['id']; ?>"><?php echo $kat['nama_kategori']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($kategori_list)): ?>
                                <small class="text-danger">
                                    <a href="index.php?page=tambah_kategori">Tambah kategori dulu</a>
                                </small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Harga Jual *</label>
                                <input type="number" class="form-control" name="harga_jual" 
                                       required min="0" step="500" placeholder="0">
                                <small class="text-muted">HPP akan dihitung dari resep</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status *</label>
                                <select class="form-select" name="status" required>
                                    <option value="tersedia">Tersedia</option>
                                    <option value="habis">Habis</option>
                                    <option value="tidak_tersedia">Tidak Tersedia</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Foto Menu</label>
                                <input type="file" class="form-control" name="foto_menu" 
                                       accept="image/jpeg,image/png,image/jpg" id="fotoMenu">
                                <small class="text-muted">Max 2MB, Format: JPG/PNG</small>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Preview Foto</label>
                                <div id="previewContainer" style="display: none;">
                                    <img id="previewImage" src="" alt="Preview" class="img-thumbnail" style="max-height: 200px;">
                                    <button type="button" class="btn btn-sm btn-danger ms-2" id="btnHapusPreview">
                                        <i class="bi bi-trash"></i> Hapus
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <strong>Langkah Selanjutnya:</strong><br>
                                Setelah menu disimpan, Anda akan diarahkan ke halaman <strong>Resep Menu</strong> 
                                untuk menambahkan komposisi bahan. HPP (Harga Pokok Produksi) akan otomatis dihitung dari resep.
                            </div>
                        </div>

                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Simpan & Lanjut ke Resep
                            </button>
                            <a href="index.php?page=list_menu" class="btn btn-secondary">
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
                <i class="bi bi-lightbulb"></i> Tips
            </div>
            <div class="card-body">
                <ul class="small mb-0">
                    <li>Klik <strong>Auto</strong> untuk generate kode otomatis</li>
                    <li>Klik <strong>Manual</strong> untuk input kode sendiri</li>
                    <li>Nama menu harus jelas dan menarik</li>
                    <li>Harga jual minimal harus > HPP</li>
                    <li>Upload foto untuk tampilan menarik</li>
                    <li>Jangan lupa tambah resep setelah ini</li>
                </ul>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-warning">
                <i class="bi bi-exclamation-triangle"></i> Perhatian
            </div>
            <div class="card-body">
                <p class="small mb-0">
                    <strong>Menu tanpa resep tidak bisa dijual!</strong><br>
                    Pastikan menambahkan resep (komposisi bahan) setelah menyimpan menu.
                </p>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-success text-white">
                <i class="bi bi-hash"></i> Format Kode Menu
            </div>
            <div class="card-body">
                <p class="small mb-2"><strong>Contoh Kode:</strong></p>
                <ul class="small mb-0">
                    <li>MNU001, MNU002, MNU003</li>
                    <li>NASI001, NASI002</li>
                    <li>MIN001, MIN002</li>
                    <li>SNK001, SNK002</li>
                </ul>
                <hr>
                <p class="small mb-0">
                    <i class="bi bi-info-circle"></i> 
                    Gunakan format yang konsisten untuk memudahkan pencarian dan laporan.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-generate kode
const kodeAuto = '<?php echo $kode_auto; ?>';
const kodeMenuInput = document.getElementById('kodeMenu');
const btnAutoKode = document.getElementById('btnAutoKode');
const btnManualKode = document.getElementById('btnManualKode');

// Set auto mode by default
let autoMode = true;
kodeMenuInput.readOnly = true;

btnAutoKode.addEventListener('click', function() {
    autoMode = true;
    kodeMenuInput.value = kodeAuto;
    kodeMenuInput.readOnly = true;
    btnAutoKode.classList.add('active');
    btnManualKode.classList.remove('active');
});

btnManualKode.addEventListener('click', function() {
    autoMode = false;
    kodeMenuInput.readOnly = false;
    kodeMenuInput.focus();
    kodeMenuInput.select();
    btnManualKode.classList.add('active');
    btnAutoKode.classList.remove('active');
});

// Preview foto
const fotoMenu = document.getElementById('fotoMenu');
const previewContainer = document.getElementById('previewContainer');
const previewImage = document.getElementById('previewImage');
const btnHapusPreview = document.getElementById('btnHapusPreview');

fotoMenu.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Validasi ukuran file (max 2MB)
        if (file.size > 2 * 1024 * 1024) {
            alert('Ukuran file maksimal 2MB!');
            fotoMenu.value = '';
            previewContainer.style.display = 'none';
            return;
        }
        
        // Validasi tipe file
        if (!['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
            alert('Format file harus JPG atau PNG!');
            fotoMenu.value = '';
            previewContainer.style.display = 'none';
            return;
        }
        
        // Preview image
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            previewContainer.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        previewContainer.style.display = 'none';
    }
});

btnHapusPreview.addEventListener('click', function() {
    fotoMenu.value = '';
    previewContainer.style.display = 'none';
    previewImage.src = '';
});

// Transform kode to uppercase
kodeMenuInput.addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});
</script>

<style>

#previewContainer {
    padding: 10px;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    background-color: #f8f9fa;
}

#previewImage {
    display: block;
    margin: 0 auto;
}
</style>