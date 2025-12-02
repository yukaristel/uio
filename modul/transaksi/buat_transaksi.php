<?php
/**
 * FORM BUAT TRANSAKSI PENJUALAN
 * Step 25/64 (39.1%)
 * Touch-friendly version
 */

// Ambil daftar menu yang tersedia
$menu_list = fetchAll("
    SELECT m.*, k.nama_kategori 
    FROM menu_makanan m 
    JOIN kategori_menu k ON m.kategori_id = k.id 
    WHERE m.status = 'tersedia' 
    ORDER BY k.nama_kategori, m.nama_menu
");
?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-cart-plus"></i> Buat Transaksi Penjualan Baru
            </div>
            <div class="card-body">
                <?php if (empty($menu_list)): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        Tidak ada menu yang tersedia. Silakan tambah menu terlebih dahulu.
                    </div>
                    <a href="index.php?page=list_menu" class="btn btn-primary">
                        <i class="bi bi-plus"></i> Kelola Menu
                    </a>
                <?php else: ?>
                    <form action="config/transaksi_proses.php?action=create" method="POST" id="formTransaksi">
                        <div class="row">
                            <!-- Daftar Menu -->
                            <div class="col-md-8">
                                <h5 class="mb-3"><i class="bi bi-card-list"></i> Pilih Menu</h5>
                                <div class="table-responsive" style="overflow-y: auto;">
                                    <table class="table table-hover">
                                        <thead class="sticky-top bg-white">
                                            <tr>
                                                <th width="50">
                                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                                </th>
                                                <th>Menu</th>
                                                <th>Harga</th>
                                                <th width="150">Jumlah</th>
                                                <th>Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $current_kategori = '';
                                            foreach ($menu_list as $menu): 
                                                if ($current_kategori != $menu['nama_kategori']):
                                                    $current_kategori = $menu['nama_kategori'];
                                            ?>
                                            <tr class="table-secondary">
                                                <td colspan="5"><strong><?php echo $current_kategori; ?></strong></td>
                                            </tr>
                                            <?php endif; ?>
                                            <tr class="menu-row clickable-row" data-id="<?php echo $menu['id']; ?>" data-harga="<?php echo $menu['harga_jual']; ?>">
                                                <td class="checkbox-cell">
                                                    <input type="checkbox" class="form-check-input menu-checkbox" 
                                                           name="menu_id[]" value="<?php echo $menu['id']; ?>">
                                                </td>
                                                <td>
                                                    <?php if ($menu['foto_menu']): ?>
                                                        <img src="uploads/menu/<?php echo $menu['foto_menu']; ?>" 
                                                             class="img-menu me-2" alt="<?php echo $menu['nama_menu']; ?>">
                                                    <?php endif; ?>
                                                    <strong><?php echo $menu['nama_menu']; ?></strong>
                                                    <br><small class="text-muted"><?php echo $menu['kode_menu']; ?></small>
                                                </td>
                                                <td class="harga-menu"><?php echo formatRupiah($menu['harga_jual']); ?></td>
                                                <td class="qty-cell">
                                                    <div class="input-group input-group-sm qty-control">
                                                        <button type="button" class="btn btn-outline-secondary btn-minus" tabindex="-1">
                                                            <i class="bi bi-dash-lg"></i>
                                                        </button>
                                                        <input type="number" class="form-control text-center jumlah-menu" 
                                                               name="jumlah[]" value="1" min="1" max="99" readonly>
                                                        <button type="button" class="btn btn-outline-secondary btn-plus" tabindex="-1">
                                                            <i class="bi bi-plus-lg"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="subtotal-menu text-rupiah">Rp 0</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        
                                            <tr class="table-secondary">
                                                <td colspan="5">
                                                
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Total Item:</span>
                                                <strong id="totalItem">0</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Total Qty:</span>
                                                <strong id="totalQty">0</strong>
                                            </div>
                                            <hr>
                                            <div class="d-flex justify-content-between mb-3">
                                                <h5>Total Harga:</h5>
                                                <h4 class="text-primary" id="totalHarga">Rp 0</h4>
                                            </div>

                                                </td>
                                            </tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Summary & Pembayaran -->
                            <div class="col-md-4">
                                <div class="card border-primary">
                                    <div class="card-body">

                                        <div class="mb-3">
                                            <label class="form-label">Metode Pembayaran *</label>
                                            <select class="form-select" name="metode_pembayaran" id="metodePembayaran" required>
                                                <option value="tunai">Tunai</option>
                                                <option value="qris">QRIS</option>
                                                <option value="grab">Grab</option>
                                                <option value="gojek">GoJek</option>
                                            </select>
                                        </div>

                                        <div class="mb-3" id="formUangBayar">
                                            <label class="form-label">Uang Bayar *</label>
                                            <input type="text" class="form-control form-control-lg text-end" 
                                                   name="uang_bayar" id="uangBayar" placeholder="0" readonly>
                                            
                                            <!-- Numpad Kalkulator -->
                                            <div class="numpad mt-2">
                                                <div class="row g-1">
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-light btn-numpad w-100" data-value="1">1</button>
                                                    </div>
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-light btn-numpad w-100" data-value="2">2</button>
                                                    </div>
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-light btn-numpad w-100" data-value="3">3</button>
                                                    </div>
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-warning btn-numpad w-100" data-value="clear">
                                                            <i class="bi bi-backspace"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="row g-1 mt-1">
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-light btn-numpad w-100" data-value="4">4</button>
                                                    </div>
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-light btn-numpad w-100" data-value="5">5</button>
                                                    </div>
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-light btn-numpad w-100" data-value="6">6</button>
                                                    </div>
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-info btn-numpad w-100" data-value="50000">50K</button>
                                                    </div>
                                                </div>
                                                <div class="row g-1 mt-1">
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-light btn-numpad w-100" data-value="7">7</button>
                                                    </div>
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-light btn-numpad w-100" data-value="8">8</button>
                                                    </div>
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-light btn-numpad w-100" data-value="9">9</button>
                                                    </div>
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-info btn-numpad w-100" data-value="100000">100K</button>
                                                    </div>
                                                </div>
                                                <div class="row g-1 mt-1">
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-light btn-numpad w-100" data-value="000">000</button>
                                                    </div>
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-light btn-numpad w-100" data-value="0">0</button>
                                                    </div>
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-light btn-numpad w-100" data-value="00">00</button>
                                                    </div>
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-success btn-numpad w-100" data-value="exact">PAS</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3" id="displayKembali" style="display:none;">
                                            <label class="form-label">Uang Kembali</label>
                                            <h4 class="text-success" id="uangKembali">Rp 0</h4>
                                        </div>

                                        <button type="submit" class="btn btn-success btn-lg w-100" id="btnProses">
                                            <i class="bi bi-check-circle"></i> Proses Transaksi
                                        </button>
                                        <button type="button" class="btn btn-secondary w-100 mt-2" onclick="resetForm()">
                                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const metodePembayaran = document.getElementById('metodePembayaran');
    const formUangBayar = document.getElementById('formUangBayar');
    const uangBayarInput = document.getElementById('uangBayar');
    const displayKembali = document.getElementById('displayKembali');
    const btnProses = document.getElementById('btnProses');
    const btnBackspace = document.getElementById('btnBackspace');

    let uangBayarValue = ""; // STRING supaya bisa menangani 00 dan 000 dengan benar

    // ===============================
    // METODE PEMBAYARAN
    // ===============================
    metodePembayaran.addEventListener('change', function() {
        if (this.value === 'tunai') {
            formUangBayar.style.display = 'block';
            uangBayarInput.required = true;
        } else {
            formUangBayar.style.display = 'none';
            uangBayarInput.required = false;
            displayKembali.style.display = 'none';
        }
    });

    // ===============================
    // CLICKABLE ROW
    // ===============================
    document.querySelectorAll('.clickable-row').forEach(row => {
        row.addEventListener('click', function(e) {
            if (e.target.closest('.checkbox-cell') || 
                e.target.closest('.qty-cell') || 
                e.target.tagName === 'BUTTON' || 
                e.target.tagName === 'INPUT') return;

            const checkbox = this.querySelector('.menu-checkbox');
            checkbox.checked = !checkbox.checked;
            hitungTotal();
        });
        row.style.cursor = 'pointer';
    });

    // ===============================
    // TOGGLE SEMUA CHECKBOX
    // ===============================
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.menu-checkbox');

    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        hitungTotal();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', hitungTotal);
    });

    // ===============================
    // BUTTON PLUS / MINUS
    // ===============================
    document.querySelectorAll('.btn-minus').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const input = this.parentElement.querySelector('.jumlah-menu');
            let value = parseInt(input.value) || 1;
            if (value > 1) {
                input.value = value - 1;
                hitungTotal();
            }
        });
    });

    document.querySelectorAll('.btn-plus').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const input = this.parentElement.querySelector('.jumlah-menu');
            let value = parseInt(input.value) || 1;
            if (value < 99) {
                input.value = value + 1;
                hitungTotal();
            }
        });
    });

    // =====================================================
    //                 NUMPAD PERBAIKAN BARU
    // =====================================================
    document.querySelectorAll('.btn-numpad').forEach(btn => {
        btn.addEventListener('click', function() {
            const value = this.dataset.value;

            if (value === 'clear') {
                uangBayarValue = "";
                updateUangBayar();
                return;
            }

            if (value === 'exact') {
                uangBayarValue = String(getTotalHarga());
                updateUangBayar();
                return;
            }

            // Tambah angka APA ADANYA (termasuk 00 dan 000)
            uangBayarValue += value;

            // Hapus leading zero kalau hanya ada satu
            uangBayarValue = uangBayarValue.replace(/^0+(\d)/, "$1");

            updateUangBayar();
        });
    });

    // ===============================
    // BACKSPACE — hapus 1 digit
    // ===============================
    if (btnBackspace) {
        btnBackspace.addEventListener('click', function() {
            uangBayarValue = uangBayarValue.slice(0, -1);
            updateUangBayar();
        });
    }

    // ===============================
    // UPDATE INPUT UANG BAYAR
    // ===============================
    function updateUangBayar() {
        const numberValue = parseInt(uangBayarValue || "0");
        uangBayarInput.value = formatRupiahInput(numberValue);

        const totalHarga = getTotalHarga();
        const kembali = numberValue - totalHarga;

        if (kembali >= 0 && numberValue > 0) {
            displayKembali.style.display = 'block';
            document.getElementById('uangKembali').textContent = formatRupiah(kembali);
            btnProses.disabled = false;
        } else {
            displayKembali.style.display = 'none';
            btnProses.disabled = numberValue === 0 ? false : true;
        }
    }

    // ===============================
    // SUBMIT VALIDASI
    // ===============================
    document.getElementById('formTransaksi').addEventListener('submit', function(e) {
        const checked = document.querySelectorAll('.menu-checkbox:checked');

        if (checked.length === 0) {
            e.preventDefault();
            alert('Pilih minimal 1 menu!');
            return false;
        }

        const totalHarga = getTotalHarga();
        const metode = metodePembayaran.value;

        if (metode === 'tunai' && parseInt(uangBayarValue || "0") < totalHarga) {
            e.preventDefault();
            alert('Uang bayar kurang! Total: ' + formatRupiah(totalHarga));
            return false;
        }

        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'uang_bayar_value';
        hiddenInput.value = uangBayarValue;
        this.appendChild(hiddenInput);

        return confirm('Proses transaksi ini?');
    });

    // ===============================
    // HITUNG TOTAL (TERMASUK HIGHLIGHT)
    // ===============================
    function hitungTotal() {
        let totalItem = 0;
        let totalQty = 0;
        let totalHarga = 0;

        document.querySelectorAll('.menu-row').forEach(row => {
            const checkbox = row.querySelector('.menu-checkbox');
            const jumlahInput = row.querySelector('.jumlah-menu');
            const subtotalEl = row.querySelector('.subtotal-menu');
            const harga = parseFloat(row.dataset.harga);

            if (checkbox.checked) {
                const jumlah = parseInt(jumlahInput.value) || 1;
                const subtotal = harga * jumlah;

                totalItem++;
                totalQty += jumlah;
                totalHarga += subtotal;

                subtotalEl.textContent = formatRupiah(subtotal);

                // HIGHLIGHT ROW
                row.style.backgroundColor = '#e6f3ff';
            } else {
                subtotalEl.textContent = 'Rp 0';
                row.style.backgroundColor = '';
            }
        });

        document.getElementById('totalItem').textContent = totalItem;
        document.getElementById('totalQty').textContent = totalQty;
        document.getElementById('totalHarga').textContent = formatRupiah(totalHarga);

        if (uangBayarValue.length > 0) updateUangBayar();
    }

    function getTotalHarga() {
        let total = 0;
        document.querySelectorAll('.menu-row').forEach(row => {
            const checkbox = row.querySelector('.menu-checkbox');
            if (checkbox.checked) {
                const jumlah = parseInt(row.querySelector('.jumlah-menu').value) || 1;
                const harga = parseFloat(row.dataset.harga);
                total += harga * jumlah;
            }
        });
        return total;
    }

    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(number);
    }

    function formatRupiahInput(number) {
        return new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: 0
        }).format(number);
    }

    window.formatRupiah = formatRupiah;
});

// ===============================
// RESET FORM
// ===============================
function resetForm() {
    if (confirm('Reset form transaksi?')) {
        location.reload();
    }
}
</script>


<style>
.menu-row {
    transition: background-color 0.2s;
}

.clickable-row:hover {
    background-color: #f8f9fa !important;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}

.qty-control {
    width: 120px;
}

.qty-control .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.qty-control input {
    pointer-events: none;
    background-color: white;
}

.btn-numpad {
    padding: 12px 8px;
    font-size: 1.1rem;
    font-weight: bold;
    border: 1px solid #dee2e6;
    user-select: none;
    -webkit-tap-highlight-color: transparent;
}

.btn-numpad:active {
    transform: scale(0.95);
}

.numpad {
    touch-action: manipulation;
}

/* Make buttons more touch-friendly on mobile */
@media (max-width: 768px) {
    .btn-numpad {
        padding: 15px 10px;
        font-size: 1.2rem;
    }
    
    .qty-control .btn {
        padding: 0.5rem 0.75rem;
    }
}
</style>