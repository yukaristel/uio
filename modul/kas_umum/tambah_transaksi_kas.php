<?php
/**
 * FORM TAMBAH TRANSAKSI
 * Menggunakan COA dan Double Entry
 */

// Get saldo kas dari berbagai sumber
$kas_tunai = fetchOne("
    SELECT COALESCE(SUM(
        CASE 
            WHEN rekening_debet = '1.1.01.01' THEN jumlah
            WHEN rekening_kredit = '1.1.01.01' THEN -jumlah
            ELSE 0
        END
    ), 0) as saldo
    FROM transaksi
");

$kas_qris = fetchOne("
    SELECT COALESCE(SUM(
        CASE 
            WHEN rekening_debet = '1.1.01.02' THEN jumlah
            WHEN rekening_kredit = '1.1.01.02' THEN -jumlah
            ELSE 0
        END
    ), 0) as saldo
    FROM transaksi
");

$kas_gopay = fetchOne("
    SELECT COALESCE(SUM(
        CASE 
            WHEN rekening_debet = '1.1.01.03' THEN jumlah
            WHEN rekening_kredit = '1.1.01.03' THEN -jumlah
            ELSE 0
        END
    ), 0) as saldo
    FROM transaksi
");

$kas_grab = fetchOne("
    SELECT COALESCE(SUM(
        CASE 
            WHEN rekening_debet = '1.1.01.04' THEN jumlah
            WHEN rekening_kredit = '1.1.01.04' THEN -jumlah
            ELSE 0
        END
    ), 0) as saldo
    FROM transaksi
");

$bank_mandiri = fetchOne("
    SELECT COALESCE(SUM(
        CASE 
            WHEN rekening_debet = '1.1.02.01' THEN jumlah
            WHEN rekening_kredit = '1.1.02.01' THEN -jumlah
            ELSE 0
        END
    ), 0) as saldo
    FROM transaksi
");

// Get COA untuk dropdown
$coa_kas_bank = fetchAll("
    SELECT kode_akun, nama_akun 
    FROM chart_of_accounts 
    WHERE lev1 = 1 AND lev2 = 1 AND lev4 > 0
    ORDER BY kode_akun
");

$coa_beban = fetchAll("
    SELECT kode_akun, nama_akun 
    FROM chart_of_accounts 
    WHERE lev1 = 5 AND lev4 > 0
    ORDER BY kode_akun
");

$coa_pendapatan = fetchAll("
    SELECT kode_akun, nama_akun 
    FROM chart_of_accounts 
    WHERE lev1 = 4 AND lev4 > 0
    ORDER BY kode_akun
");

$coa_aset = fetchAll("
    SELECT kode_akun, nama_akun 
    FROM chart_of_accounts 
    WHERE lev1 = 1 AND lev4 > 0
    ORDER BY kode_akun
");

$coa_liabilitas = fetchAll("
    SELECT kode_akun, nama_akun 
    FROM chart_of_accounts 
    WHERE lev1 = 2 AND lev4 > 0
    ORDER BY kode_akun
");

$coa_ekuitas = fetchAll("
    SELECT kode_akun, nama_akun 
    FROM chart_of_accounts 
    WHERE lev1 = 3 AND lev4 > 0
    ORDER BY kode_akun
");

$coa_all = fetchAll("
    SELECT kode_akun, nama_akun, jenis_mutasi 
    FROM chart_of_accounts 
    WHERE lev4 > 0
    ORDER BY kode_akun
");
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-plus-circle"></i> Tambah Transaksi</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Tambah Transaksi</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-receipt"></i> Form Transaksi
            </div>
            <div class="card-body">
                <!-- Info Saldo -->
                <div class="alert alert-info mb-4">
                    <strong><i class="bi bi-wallet2"></i> Saldo Saat Ini:</strong><br>
                    <div class="row mt-2">
                        <div class="col-md-4">Kas Tunai: <strong><?php echo formatRupiah($kas_tunai['saldo']); ?></strong></div>
                        <div class="col-md-4">QRIS: <strong><?php echo formatRupiah($kas_qris['saldo']); ?></strong></div>
                        <div class="col-md-4">GoPay: <strong><?php echo formatRupiah($kas_gopay['saldo']); ?></strong></div>
                    </div>
                    <div class="row mt-1">
                        <div class="col-md-4">Grab: <strong><?php echo formatRupiah($kas_grab['saldo']); ?></strong></div>
                        <div class="col-md-4">Bank Mandiri: <strong><?php echo formatRupiah($bank_mandiri['saldo']); ?></strong></div>
                    </div>
                </div>

                <form action="config/kas_proses.php?action=create" method="POST" id="formTransaksi">
                    
                    <!-- Jenis Transaksi -->
                    <div class="mb-3">
                        <label class="form-label">Jenis Transaksi *</label>
                        <select class="form-select" name="jenis_transaksi" id="jenisTransaksi" required>
                            <option value="">-- Pilih Jenis Transaksi --</option>
                            <option value="pemasukan">Pemasukan (Terima Uang)</option>
                            <option value="pengeluaran">Pengeluaran (Keluar Uang)</option>
                            <option value="pemindahan">Pemindahan Saldo</option>
                        </select>
                    </div>

                    <!-- Container untuk form dinamis -->
                    <div id="formDinamis"></div>

                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <i class="bi bi-calculator"></i> Ringkasan
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted">Jenis:</label>
                    <h5 id="displayJenis">-</h5>
                </div>
                <div class="mb-3">
                    <label class="text-muted">Jumlah:</label>
                    <h4 id="displayJumlah" class="text-primary">Rp 0</h4>
                </div>
                <hr>
                <div id="displayDetail"></div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-warning">
                <i class="bi bi-lightbulb"></i> Panduan
            </div>
            <div class="card-body">
                <ul class="mb-0 small">
                    <li><strong>Pemasukan:</strong> Terima uang dari penjualan, modal, pinjaman, dll</li>
                    <li><strong>Pengeluaran:</strong> Bayar gaji, listrik, sewa, beli inventaris, dll</li>
                    <li><strong>Pemindahan:</strong> Transfer antar kas/bank</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Data COA untuk JavaScript
const coaData = <?php echo json_encode($coa_all); ?>;

document.getElementById('jenisTransaksi').addEventListener('change', function() {
    const jenis = this.value;
    const container = document.getElementById('formDinamis');
    
    if (!jenis) {
        container.innerHTML = '';
        return;
    }
    
    if (jenis === 'pemasukan') {
        container.innerHTML = formPemasukan();
    } else if (jenis === 'pengeluaran') {
        container.innerHTML = formPengeluaran();
    } else if (jenis === 'pemindahan') {
        container.innerHTML = formPemindahan();
    }
    
    // Attach event listeners
    attachEventListeners();
});

function formPemasukan() {
    return `
        <div class="mb-3">
            <label class="form-label">Sumber Dana (Dari) *</label>
            <select class="form-select" name="rekening_kredit" id="rekeningKredit" required>
                <option value="">-- Pilih Sumber Dana --</option>
                <optgroup label="Pendapatan">
                    <?php foreach($coa_pendapatan as $coa): ?>
                    <option value="<?php echo $coa['kode_akun']; ?>">
                        <?php echo $coa['kode_akun']; ?> - <?php echo $coa['nama_akun']; ?>
                    </option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Pinjaman">
                    <?php foreach($coa_liabilitas as $coa): ?>
                    <option value="<?php echo $coa['kode_akun']; ?>">
                        <?php echo $coa['kode_akun']; ?> - <?php echo $coa['nama_akun']; ?>
                    </option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Modal">
                    <?php foreach($coa_ekuitas as $coa): ?>
                    <option value="<?php echo $coa['kode_akun']; ?>">
                        <?php echo $coa['kode_akun']; ?> - <?php echo $coa['nama_akun']; ?>
                    </option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Disimpan Ke *</label>
            <select class="form-select" name="rekening_debet" id="rekeningDebet" required>
                <option value="">-- Pilih Tempat Penyimpanan --</option>
                <?php foreach($coa_kas_bank as $coa): ?>
                <option value="<?php echo $coa['kode_akun']; ?>">
                    <?php echo $coa['kode_akun']; ?> - <?php echo $coa['nama_akun']; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Jumlah *</label>
            <input type="number" class="form-control" name="jumlah" id="jumlah" 
                   required min="0" step="1000" placeholder="0">
        </div>
        
        <div class="mb-3">
            <label class="form-label">Tanggal Transaksi *</label>
            <input type="date" class="form-control" name="tgl_transaksi" 
                   value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Keterangan</label>
            <textarea class="form-control" name="keterangan_transaksi" id="keterangan" 
                      rows="3" placeholder="Keterangan otomatis akan diisi"></textarea>
        </div>
        
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Simpan Transaksi
            </button>
            <a href="index.php?page=list_transaksi" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> Batal
            </a>
        </div>
    `;
}

function formPengeluaran() {
    return `
        <div class="mb-3">
            <label class="form-label">Tipe Pengeluaran *</label>
            <select class="form-select" name="tipe_pengeluaran" id="tipePengeluaran" required>
                <option value="">-- Pilih Tipe --</option>
                <option value="beban">Beban Operasional (Gaji, Listrik, Sewa, dll)</option>
                <option value="inventaris">Pembelian Inventaris/Aset Tetap</option>
                <option value="hutang">Bayar Hutang</option>
            </select>
        </div>
        
        <div id="formPengeluaranDetail"></div>
    `;
}

function formPemindahan() {
    return `
        <div class="mb-3">
            <label class="form-label">Dari Akun *</label>
            <select class="form-select" name="rekening_kredit" id="rekeningKredit" required>
                <option value="">-- Pilih Akun Sumber --</option>
                <?php foreach($coa_kas_bank as $coa): ?>
                <option value="<?php echo $coa['kode_akun']; ?>">
                    <?php echo $coa['kode_akun']; ?> - <?php echo $coa['nama_akun']; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Ke Akun *</label>
            <select class="form-select" name="rekening_debet" id="rekeningDebet" required>
                <option value="">-- Pilih Akun Tujuan --</option>
                <?php foreach($coa_kas_bank as $coa): ?>
                <option value="<?php echo $coa['kode_akun']; ?>">
                    <?php echo $coa['kode_akun']; ?> - <?php echo $coa['nama_akun']; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Jumlah *</label>
            <input type="number" class="form-control" name="jumlah" id="jumlah" 
                   required min="0" step="1000" placeholder="0">
        </div>
        
        <div class="mb-3">
            <label class="form-label">Tanggal Transaksi *</label>
            <input type="date" class="form-control" name="tgl_transaksi" 
                   value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Keterangan</label>
            <textarea class="form-control" name="keterangan_transaksi" id="keterangan" 
                      rows="2" placeholder="Keterangan otomatis akan diisi"></textarea>
        </div>
        
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Simpan Transaksi
            </button>
            <a href="index.php?page=list_transaksi" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> Batal
            </a>
        </div>
    `;
}

// Event listener untuk tipe pengeluaran
document.addEventListener('change', function(e) {
    if (e.target && e.target.id === 'tipePengeluaran') {
        const tipe = e.target.value;
        const container = document.getElementById('formPengeluaranDetail');
        
        if (tipe === 'beban') {
            container.innerHTML = formBeban();
        } else if (tipe === 'inventaris') {
            container.innerHTML = formInventaris();
        } else if (tipe === 'hutang') {
            container.innerHTML = formHutang();
        }
        
        attachEventListeners();
    }
});

function formBeban() {
    return `
        <div class="mb-3">
            <label class="form-label">Jenis Beban *</label>
            <select class="form-select" name="rekening_debet" id="rekeningDebet" required>
                <option value="">-- Pilih Jenis Beban --</option>
                <?php foreach($coa_beban as $coa): ?>
                <option value="<?php echo $coa['kode_akun']; ?>">
                    <?php echo $coa['kode_akun']; ?> - <?php echo $coa['nama_akun']; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Dibayar Dari *</label>
            <select class="form-select" name="rekening_kredit" id="rekeningKredit" required>
                <option value="">-- Pilih Sumber Pembayaran --</option>
                <?php foreach($coa_kas_bank as $coa): ?>
                <option value="<?php echo $coa['kode_akun']; ?>">
                    <?php echo $coa['kode_akun']; ?> - <?php echo $coa['nama_akun']; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Jumlah *</label>
            <input type="number" class="form-control" name="jumlah" id="jumlah" 
                   required min="0" step="1000" placeholder="0">
        </div>
        
        <div class="mb-3">
            <label class="form-label">Tanggal Transaksi *</label>
            <input type="date" class="form-control" name="tgl_transaksi" 
                   value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Keterangan</label>
            <textarea class="form-control" name="keterangan_transaksi" id="keterangan" 
                      rows="3" placeholder="Keterangan otomatis akan diisi"></textarea>
        </div>
        
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Simpan Transaksi
            </button>
            <a href="index.php?page=list_transaksi" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> Batal
            </a>
        </div>
    `;
}

function formInventaris() {
    return `
        <input type="hidden" name="rekening_debet" value="1.3.01.01">
        
        <div class="mb-3">
            <label class="form-label">Nama Barang *</label>
            <input type="text" class="form-control" name="nama_barang" required>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Jumlah Unit *</label>
                    <input type="number" class="form-control" name="unit" id="unitInventaris" 
                           required min="1" value="1">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Harga Satuan *</label>
                    <input type="number" class="form-control" name="harsat" id="harsatInventaris" 
                           required min="0" step="1000" placeholder="0">
                </div>
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Total Harga</label>
            <input type="number" class="form-control" name="jumlah" id="jumlah" 
                   readonly style="background-color: #e9ecef;">
        </div>
        
        <div class="mb-3">
            <label class="form-label">Umur Ekonomis (bulan)</label>
            <input type="number" class="form-control" name="umur_ekonomis" 
                   min="1" placeholder="60">
        </div>
        
        <div class="mb-3">
            <label class="form-label">Jenis Inventaris *</label>
            <select class="form-select" name="jenis" required>
                <option value="">-- Pilih Jenis --</option>
                <option value="Peralatan Dapur">Peralatan Dapur</option>
                <option value="Peralatan Pendingin">Peralatan Pendingin</option>
                <option value="Furniture">Furniture</option>
                <option value="Elektronik">Elektronik</option>
                <option value="Peralatan Saji">Peralatan Saji</option>
                <option value="Peralatan Kasir">Peralatan Kasir</option>
                <option value="Kendaraan">Kendaraan</option>
            </select>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Dibayar Dari *</label>
            <select class="form-select" name="rekening_kredit" id="rekeningKredit" required>
                <option value="">-- Pilih Sumber Pembayaran --</option>
                <?php foreach($coa_kas_bank as $coa): ?>
                <option value="<?php echo $coa['kode_akun']; ?>">
                    <?php echo $coa['kode_akun']; ?> - <?php echo $coa['nama_akun']; ?>
                </option>
                <?php endforeach; ?>
                <optgroup label="Atau Hutang">
                    <option value="2.1.01.01">2.1.01.01 - Utang Supplier</option>
                </optgroup>
            </select>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Tanggal Pembelian *</label>
            <input type="date" class="form-control" name="tgl_transaksi" 
                   value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Keterangan</label>
            <textarea class="form-control" name="keterangan_transaksi" id="keterangan" 
                      rows="2" placeholder="Keterangan otomatis akan diisi"></textarea>
        </div>
        
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Simpan Transaksi & Inventaris
            </button>
            <a href="index.php?page=list_transaksi" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> Batal
            </a>
        </div>
    `;
}

function formHutang() {
    return `
        <div class="mb-3">
            <label class="form-label">Jenis Hutang *</label>
            <select class="form-select" name="rekening_debet" id="rekeningDebet" required>
                <option value="">-- Pilih Jenis Hutang --</option>
                <option value="2.1.01.01">2.1.01.01 - Utang Supplier</option>
                <option value="2.2.01.01">2.2.01.01 - Pinjaman Pihak Ketiga</option>
            </select>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Dibayar Dari *</label>
            <select class="form-select" name="rekening_kredit" id="rekeningKredit" required>
                <option value="">-- Pilih Sumber Pembayaran --</option>
                <?php foreach($coa_kas_bank as $coa): ?>
                <option value="<?php echo $coa['kode_akun']; ?>">
                    <?php echo $coa['kode_akun']; ?> - <?php echo $coa['nama_akun']; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Jumlah *</label>
            <input type="number" class="form-control" name="jumlah" id="jumlah" 
                   required min="0" step="1000" placeholder="0">
        </div>
        
        <div class="mb-3">
            <label class="form-label">Tanggal Transaksi *</label>
            <input type="date" class="form-control" name="tgl_transaksi" 
                   value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Keterangan</label>
            <textarea class="form-control" name="keterangan_transaksi" id="keterangan" 
                      rows="3" placeholder="Keterangan otomatis akan diisi"></textarea>
        </div>
        
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Simpan Transaksi
            </button>
            <a href="index.php?page=list_transaksi" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> Batal
            </a>
        </div>
    `;
}

function attachEventListeners() {
    // Auto calculate untuk inventaris
    const unitInput = document.getElementById('unitInventaris');
    const harsatInput = document.getElementById('harsatInventaris');
    
    if (unitInput && harsatInput) {
        const calculateTotal = () => {
            const unit = parseInt(unitInput.value) || 0;
            const harsat = parseInt(harsatInput.value) || 0;
            const total = unit * harsat;
            document.getElementById('jumlah').value = total;
            updateDisplay();
        };
        
        unitInput.addEventListener('input', calculateTotal);
        harsatInput.addEventListener('input', calculateTotal);
    }
    
    // Auto generate keterangan
    const rekeningDebet = document.getElementById('rekeningDebet');
    const rekeningKredit = document.getElementById('rekeningKredit');
    const jumlah = document.getElementById('jumlah');
    
    if (rekeningDebet) rekeningDebet.addEventListener('change', generateKeterangan);
    if (rekeningKredit) rekeningKredit.addEventListener('change', generateKeterangan);
    if (jumlah) jumlah.addEventListener('input', updateDisplay);
}

function generateKeterangan() {
    const jenis = document.getElementById('jenisTransaksi').value;
    const rekeningDebet = document.getElementById('rekeningDebet');
    const rekeningKredit = document.getElementById('rekeningKredit');
    const keterangan = document.getElementById('keterangan');
    
    if (!rekeningDebet || !rekeningKredit || !keterangan) return;
    
    const namaDebet = rekeningDebet.options[rekeningDebet.selectedIndex]?.text || '';
    const namaKredit = rekeningKredit.options[rekeningKredit.selectedIndex]?.text || '';
    
    if (jenis === 'pemasukan') {
        keterangan.value = `Terima uang dari ${namaKredit.split(' - ')[1] || namaKredit}`;
    } else if (jenis === 'pengeluaran') {
        keterangan.value = `Bayar ${namaDebet.split(' - ')[1] || namaDebet}`;
    } else if (jenis === 'pemindahan') {
        keterangan.value = `Transfer dari ${namaKredit.split(' - ')[1] || ''} ke ${namaDebet.split(' - ')[1] || ''}`;
    }
    
    updateDisplay();
}

function updateDisplay() {
    const jenis = document.getElementById('jenisTransaksi').value;
    const jumlah = document.getElementById('jumlah')?.value || 0;
    const keterangan = document.getElementById('keterangan')?.value || '';
    
    let jenisText = '-';
    if (jenis === 'pemasukan') jenisText = '<span class="badge bg-success">Pemasukan</span>';
    else if (jenis === 'pengeluaran') jenisText = '<span class="badge bg-danger">Pengeluaran</span>';
    else if (jenis === 'pemindahan') jenisText = '<span class="badge bg-info">Pemindahan</span>';
    
    document.getElementById('displayJenis').innerHTML = jenisText;
    document.getElementById('displayJumlah').textContent = formatRupiah(parseInt(jumlah));
    
    const detail = keterangan ? `<div class="mb-2"><small class="text-muted">Keterangan:</small><br>${keterangan}</div>` : '';
    document.getElementById('displayDetail').innerHTML = detail;
}

function formatRupiah(number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(number);
}

// Form validation
document.addEventListener('submit', function(e) {
    if (e.target && e.target.id === 'formTransaksi') {
        if (!confirm('Simpan transaksi ini?')) {
            e.preventDefault();
            return false;
        }
    }
});
</script>