<?php
/**
 * FORM BUAT TRANSAKSI PENJUALAN - MODERN TOUCH UI + GRATIS SPLIT
 * POS System dengan input uang diterima untuk GoPay/Grab
 */

// Ambil daftar menu yang tersedia
$menu_list = fetchAll("
    SELECT m.*, k.nama_kategori 
    FROM menu_makanan m 
    JOIN kategori_menu k ON m.kategori_id = k.id 
    WHERE m.status = 'tersedia' 
    ORDER BY k.nama_kategori, m.nama_menu
");

// Group by kategori
$menu_by_kategori = [];
foreach ($menu_list as $menu) {
    $menu_by_kategori[$menu['nama_kategori']][] = $menu;
}
?>

<div class="pos-container">
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
            <div class="row g-3">
                <!-- LEFT SIDE: Menu Grid -->
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#collapseMenu" aria-expanded="true">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="bi bi-grid-3x3-gap"></i> Pilih Menu
                                    <i class="bi bi-chevron-down ms-2" id="iconCollapse"></i>
                                </h5>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-light btn-sm" onclick="changeView('grid')" id="btnGrid">
                                        <i class="bi bi-grid-3x3"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-light btn-sm" onclick="changeView('list')" id="btnList">
                                        <i class="bi bi-list-ul"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="collapse show" id="collapseMenu">
                            <div class="card-body p-3">
                            <!-- Filter Kategori -->
                            <div class="mb-3">
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-kategori active" data-kategori="all">
                                        Semua
                                    </button>
                                    <?php foreach (array_keys($menu_by_kategori) as $kategori): ?>
                                    <button type="button" class="btn btn-outline-primary btn-kategori" data-kategori="<?php echo htmlspecialchars($kategori); ?>">
                                        <?php echo $kategori; ?>
                                    </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Menu Grid/List -->
                            <div class="menu-container" id="menuContainer">
                                <?php foreach ($menu_by_kategori as $kategori => $menus): ?>
                                    <?php foreach ($menus as $menu): ?>
                                    <div class="menu-card" data-kategori="<?php echo htmlspecialchars($kategori); ?>" 
                                         data-id="<?php echo $menu['id']; ?>" 
                                         data-harga="<?php echo $menu['harga_jual']; ?>"
                                         data-nama="<?php echo htmlspecialchars($menu['nama_menu']); ?>">
                                        <?php if ($menu['foto_menu']): ?>
                                            <img src="uploads/menu/<?php echo $menu['foto_menu']; ?>" 
                                                 class="menu-image" alt="<?php echo $menu['nama_menu']; ?>">
                                        <?php else: ?>
                                            <div class="menu-image-placeholder">
                                                <i class="bi bi-image"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="menu-info">
                                            <h6 class="menu-name"><?php echo $menu['nama_menu']; ?></h6>
                                            <p class="menu-price"><?php echo formatRupiah($menu['harga_jual']); ?></p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        </div>
                    </div>
                    <!-- Cart -->
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-cart3"></i> Keranjang Pesanan</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="cart-items" id="cartItems">
                                <div class="empty-cart">
                                    <i class="bi bi-cart-x"></i>
                                    <p>Keranjang masih kosong</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="summary-row">
                                <span>Total Item:</span>
                                <strong id="totalItem">0</strong>
                            </div>
                            <div class="summary-row">
                                <span>Total Qty:</span>
                                <strong id="totalQty">0</strong>
                            </div>
                            <hr>
                            <div class="summary-row total">
                                <h5>TOTAL:</h5>
                                <h4 class="text-primary mb-0" id="totalHarga">Rp 0</h4>
                            </div>
                            <div id="potonganInfo" style="display: none;">
                                <div class="summary-row text-muted mt-2">
                                    <small>Harga Awal:</small>
                                    <small><s id="hargaAwal">Rp 0</s></small>
                                </div>
                                <div class="summary-row text-danger">
                                    <small>Potongan:</small>
                                    <small id="nominalPotongan">Rp 0</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT SIDE: Cart & Payment -->
                <div class="col-lg-5">
                    
                    <!-- Payment -->
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-wallet2"></i> Pembayaran</h5>
                                <button type="button" class="btn btn-sm btn-light" onclick="resetForm()" title="Reset">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 mb-3">
                                <!-- LEFT: Summary -->
                                <div class="col-6">
                                    <div class="summary-payment">
                                        <div class="summary-mini">
                                            <small class="text-muted">Total Item:</small>
                                            <strong id="totalItem2">0</strong>
                                        </div>
                                        <div class="summary-mini">
                                            <small class="text-muted">Total Qty:</small>
                                            <strong id="totalQty2">0</strong>
                                        </div>
                                        <hr class="my-2">
                                    </div>
                                </div>
                                
                                <!-- RIGHT: Payment Form -->
                                <div class="col-6">
                                    <div class="summary-payment">
                                        <div class="summary-mini">
                                            <strong>TOTAL:</strong>
                                            <h5 class="text-primary mb-0" id="totalHarga2">Rp 0</h5>
                                        </div>
                                        <div id="potonganInfo2" style="display: none;">
                                            <div class="summary-mini mt-1">
                                                <small class="text-muted">Harga Awal:</small>
                                                <small class="text-muted"><s id="hargaAwal2">Rp 0</s></small>
                                            </div>
                                            <div class="summary-mini">
                                                <small class="text-danger">Potongan:</small>
                                                <small class="text-danger" id="nominalPotongan2">Rp 0</small>
                                            </div>
                                        </div>
                                        <div class="summary-mini mt-2" id="displayKembaliMini">
                                            <strong class="text-success">Kembalian:</strong>
                                            <h6 class="text-success mb-0" id="uangKembaliMini">Rp 0</h6>
                                        </div>
                                        <hr class="my-2">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <!-- LEFT: Metode -->
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="form-label">Metode Pembayaran</label>
                                        <select class="form-select" name="metode_pembayaran" id="metodePembayaran" required>
                                            <option value="tunai">💵 Tunai</option>
                                            <option value="qris">📱 QRIS</option>
                                            <option value="gopay">🟢 GoPay</option>
                                            <option value="grab">🟢 Grab</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- RIGHT: Input -->
                                <div class="col-6">
                                    <!-- Input untuk TUNAI -->
                                    <div id="formUangBayarInput">
                                        <div class="mb-3">
                                            <label class="form-label">Uang Bayar</label>
                                            <input type="text" class="form-control text-end" 
                                                   name="uang_bayar" id="uangBayar" placeholder="0" readonly>
                                        </div>
                                    </div>
                                    
                                    <!-- Input untuk GOPAY/GRAB/QRIS -->
                                    <div id="formUangTerimaInput" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">Uang Diterima <small class="text-muted">(setelah fee)</small></label>
                                            <input type="text" class="form-control text-end" 
                                                   name="uang_terima" id="uangTerima" placeholder="0" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="formUangBayar">

                                <!-- Quick Amount Buttons -->
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <button type="button" class="btn btn-outline-primary w-100 btn-quick" data-value="exact">
                                            <i class="bi bi-check2-circle"></i> Uang Pas
                                        </button>
                                    </div>
                                    <div class="col-6">
                                        <button type="button" class="btn btn-outline-danger w-100 btn-quick" data-value="clear">
                                            <i class="bi bi-x-circle"></i> Hapus
                                        </button>
                                    </div>
                                    <div class="col-4">
                                        <button type="button" class="btn btn-outline-secondary w-100 btn-quick" data-value="20000">20K</button>
                                    </div>
                                    <div class="col-4">
                                        <button type="button" class="btn btn-outline-secondary w-100 btn-quick" data-value="50000">50K</button>
                                    </div>
                                    <div class="col-4">
                                        <button type="button" class="btn btn-outline-secondary w-100 btn-quick" data-value="100000">100K</button>
                                    </div>
                                </div>

                                <!-- Numpad -->
                                <div class="numpad">
                                    <div class="numpad-row">
                                        <button type="button" class="numpad-btn" data-value="1">1</button>
                                        <button type="button" class="numpad-btn" data-value="2">2</button>
                                        <button type="button" class="numpad-btn" data-value="3">3</button>
                                    </div>
                                    <div class="numpad-row">
                                        <button type="button" class="numpad-btn" data-value="4">4</button>
                                        <button type="button" class="numpad-btn" data-value="5">5</button>
                                        <button type="button" class="numpad-btn" data-value="6">6</button>
                                    </div>
                                    <div class="numpad-row">
                                        <button type="button" class="numpad-btn" data-value="7">7</button>
                                        <button type="button" class="numpad-btn" data-value="8">8</button>
                                        <button type="button" class="numpad-btn" data-value="9">9</button>
                                    </div>
                                    <div class="numpad-row">
                                        <button type="button" class="numpad-btn" data-value="000">000</button>
                                        <button type="button" class="numpad-btn" data-value="0">0</button>
                                        <button type="button" class="numpad-btn numpad-backspace" data-value="backspace">
                                            <i class="bi bi-backspace"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="mt-3 btn btn-success w-100" id="btnProses">
                                <i class="bi bi-check-circle"></i> PROSES TRANSAKSI
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<style>
    /* POS Container */
    .pos-container {
        padding: 1rem;
        min-height: 100vh;
    }

    /* Menu Cards */
    .menu-container {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        max-height: calc(100vh - 300px);
        overflow-y: auto;
        padding: 0.5rem;
    }

    .menu-container.list-view {
        grid-template-columns: 1fr;
    }

    .menu-card {
        border-radius: 10px;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
    }

    .menu-card:active {
        transform: scale(0.98);
    }

    .menu-card.selected {
        border: 3px solid var(--success-color);
        box-shadow: 0 0 0 4px rgba(160, 220, 194, 0.3);
    }

    .menu-image {
        width: 100%;
        height: 100px;
        object-fit: cover;
    }

    .menu-image-placeholder {
        width: 100%;
        height: 100px;
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: white;
    }

    .menu-info {
        padding: 1rem;
    }

    .menu-name {
        font-size: 0.95rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #333;
    }

    .menu-price {
        font-size: 1rem;
        font-weight: 700;
        color: var(--primary-color);
        margin: 0;
    }

    /* List View */
    .menu-container.list-view .menu-card {
        display: flex;
        flex-direction: row;
    }

    .menu-container.list-view .menu-image,
    .menu-container.list-view .menu-image-placeholder {
        width: 120px;
        height: 100px;
        flex-shrink: 0;
    }

    .menu-container.list-view .menu-info {
        flex: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Cart */
    .cart-items {
        max-height: 400px;
        overflow-y: auto;
        min-height: 200px;
    }

    .empty-cart {
        text-align: center;
        padding: 3rem 1rem;
        color: #999;
    }

    .empty-cart i {
        font-size: 4rem;
        opacity: 0.3;
        display: block;
        margin-bottom: 1rem;
    }

    .cart-item {
        padding: 1rem;
        border-bottom: 1px solid #eee;
        transition: background 0.2s;
    }

    .cart-item.gratis {
        background: #f0fff4;
        border-left: 4px solid #28a745;
    }

    .cart-item:hover {
        background: #f8f9fa;
    }

    .cart-item.gratis:hover {
        background: #e6f9ec;
    }

    .cart-item-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 0.5rem;
    }

    .cart-item-name {
        font-weight: 600;
        margin-bottom: 0.25rem;
        color: #333;
        flex: 1;
    }

    .cart-item.gratis .cart-item-name {
        color: #28a745;
    }

    .badge-gratis {
        background: #28a745;
        color: white;
        padding: 0.15rem 0.5rem;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 600;
        display: inline-block;
        margin-bottom: 0.25rem;
    }

    .cart-item-price-info {
        font-size: 0.85rem;
        color: #666;
    }

    .cart-item.gratis .cart-item-price-info {
        text-decoration: line-through;
        color: #999;
    }

    .cart-item-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 0.5rem;
    }

    .btn-action-gratis {
        width: 36px;
        height: 36px;
        border-radius: 6px;
        border: 2px solid #28a745;
        background: white;
        color: #28a745;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 1rem;
    }

    .btn-action-gratis:hover,
    .btn-action-gratis.active {
        background: #28a745;
        color: white;
    }

    .cart-item-controls {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .qty-btn {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        border: 2px solid var(--primary-color);
        background: white;
        color: var(--primary-color);
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }

    .qty-btn:hover {
        background: var(--primary-color);
        color: white;
    }

    .qty-btn:active {
        transform: scale(0.9);
    }

    .qty-display {
        min-width: 35px;
        text-align: center;
        font-weight: 700;
        font-size: 1rem;
    }

    .btn-remove {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        border: 2px solid var(--danger-color);
        background: white;
        color: var(--danger-color);
        font-size: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-remove:hover {
        background: var(--danger-color);
        color: white;
    }

    /* Summary */
    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
    }

    .summary-row.total {
        align-items: center;
    }

    .summary-mini {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.25rem 0;
    }

    .summary-payment {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 10px;
        height: 100%;
    }

    /* Numpad */
    .numpad {
        display: grid;
        gap: 0.5rem;
    }

    .numpad-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
    }

    .numpad-btn {
        height: 50px;
        font-size: 1.2rem;
        font-weight: 600;
        border-radius: 10px;
        border: 2px solid #dee2e6;
        background: white;
        color: #333;
        cursor: pointer;
        transition: all 0.15s;
        user-select: none;
    }

    .numpad-btn:hover {
        background: var(--light-color);
        border-color: var(--primary-color);
    }

    .numpad-btn:active {
        transform: scale(0.95);
        background: var(--primary-color);
        color: white;
    }

    .numpad-backspace {
        background: var(--warning-color);
        border-color: var(--warning-color);
        color: #7a4a4a;
    }

    .numpad-backspace:hover {
        background: #FCACAC;
    }

    /* Kategori Filter */
    .btn-kategori {
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .btn-outline-primary.btn-kategori {
        color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .btn-outline-primary.btn-kategori:hover {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        color: white;
    }

    .btn-outline-primary.btn-kategori.active {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        color: white;
    }

    /* Card Header Custom untuk POS */
    .card-header.bg-success {
        background-color: var(--success-color) !important;
        color: #1e5a3f !important;
    }

    .card-header.bg-info {
        background-color: var(--info-color) !important;
        color: #7a4a4a !important;
    }

    /* Collapse Icon Animation */
    .card-header[aria-expanded="true"] #iconCollapse {
        transform: rotate(180deg);
        transition: transform 0.3s ease;
    }

    .card-header[aria-expanded="false"] #iconCollapse {
        transform: rotate(0deg);
        transition: transform 0.3s ease;
    }

    /* Scrollbar */
    .menu-container::-webkit-scrollbar,
    .cart-items::-webkit-scrollbar {
        width: 8px;
    }

    .menu-container::-webkit-scrollbar-track,
    .cart-items::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .menu-container::-webkit-scrollbar-thumb,
    .cart-items::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }

    .menu-container::-webkit-scrollbar-thumb:hover,
    .cart-items::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .menu-container {
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        }
    
        .menu-image,
        .menu-image-placeholder {
            height: 120px !important;
        }
    }

    @media (max-width: 768px) {
        .pos-container {
            padding: 0.5rem;
        }
    
        .numpad-btn {
            height: 50px;
            font-size: 1.3rem;
        }
    
        .btn-kategori {
            font-size: 0.85rem;
            padding: 0.5rem 0.75rem;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let cart = {};
    let cartIdCounter = 0;
    let uangBayarValue = "";
    let uangTerimaValue = "";
    let currentView = 'grid';

    const metodePembayaran = document.getElementById('metodePembayaran');
    const formUangBayar = document.getElementById('formUangBayar');
    const uangBayarInput = document.getElementById('uangBayar');
    const uangTerimaInput = document.getElementById('uangTerima');
    const btnProses = document.getElementById('btnProses');
    const cartItemsContainer = document.getElementById('cartItems');
    const menuContainer = document.getElementById('menuContainer');

    // =================================
    // COLLAPSE ICON ANIMATION
    // =================================
    const collapseMenu = document.getElementById('collapseMenu');
    const cardHeader = document.querySelector('[data-bs-toggle="collapse"]');
    
    collapseMenu.addEventListener('show.bs.collapse', function () {
        cardHeader.setAttribute('aria-expanded', 'true');
    });
    
    collapseMenu.addEventListener('hide.bs.collapse', function () {
        cardHeader.setAttribute('aria-expanded', 'false');
    });

    // =================================
    // MENU SELECTION
    // =================================
    document.querySelectorAll('.menu-card').forEach(card => {
        card.addEventListener('click', function() {
            const menuId = this.dataset.id;
            const menuNama = this.dataset.nama;
            const menuHarga = parseFloat(this.dataset.harga);

            // Cek apakah sudah ada item normal (not gratis) dengan menu_id ini
            let foundNormal = false;
            for (let cartId in cart) {
                if (cart[cartId].menu_id === menuId && !cart[cartId].is_gratis) {
                    cart[cartId].qty++;
                    foundNormal = true;
                    break;
                }
            }

            // Jika tidak ada, buat baru
            if (!foundNormal) {
                const newCartId = 'cart_' + (++cartIdCounter);
                cart[newCartId] = {
                    cart_id: newCartId,
                    menu_id: menuId,
                    nama: menuNama,
                    harga: menuHarga,
                    qty: 1,
                    is_gratis: false
                };
            }

            this.classList.add('selected');
            setTimeout(() => this.classList.remove('selected'), 300);

            updateCart();
        });
    });

    // =================================
    // KATEGORI FILTER
    // =================================
    document.querySelectorAll('.btn-kategori').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.btn-kategori').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const kategori = this.dataset.kategori;
            document.querySelectorAll('.menu-card').forEach(card => {
                if (kategori === 'all' || card.dataset.kategori === kategori) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // =================================
    // VIEW TOGGLE
    // =================================
    window.changeView = function(view) {
        currentView = view;
        if (view === 'grid') {
            menuContainer.classList.remove('list-view');
            document.getElementById('btnGrid').classList.add('btn-light');
            document.getElementById('btnGrid').classList.remove('btn-outline-light');
            document.getElementById('btnList').classList.add('btn-outline-light');
            document.getElementById('btnList').classList.remove('btn-light');
        } else {
            menuContainer.classList.add('list-view');
            document.getElementById('btnList').classList.add('btn-light');
            document.getElementById('btnList').classList.remove('btn-outline-light');
            document.getElementById('btnGrid').classList.add('btn-outline-light');
            document.getElementById('btnGrid').classList.remove('btn-light');
        }
    };

    // =================================
    // UPDATE CART
    // =================================
    function updateCart() {
        if (Object.keys(cart).length === 0) {
            cartItemsContainer.innerHTML = `
                <div class="empty-cart">
                    <i class="bi bi-cart-x"></i>
                    <p>Keranjang masih kosong</p>
                </div>
            `;
        } else {
            let html = '';
            let totalItem = 0;
            let totalQty = 0;
            let totalHarga = 0;
            let totalHargaAwal = 0;

            for (let cartId in cart) {
                const item = cart[cartId];
                const subtotal = item.is_gratis ? 0 : (item.harga * item.qty);
                const subtotalAwal = item.harga * item.qty;

                // Badge gratis
                let badgeHtml = '';
                if (item.is_gratis) {
                    badgeHtml = '<span class="badge-gratis">🎁 GRATIS</span>';
                }

                // Price display
                let priceDisplay = `${formatRupiah(item.harga)} × ${item.qty} = ${formatRupiah(subtotalAwal)}`;

                html += `
                    <div class="cart-item ${item.is_gratis ? 'gratis' : ''}">
                        <div class="cart-item-header">
                            <div style="flex: 1;">
                                <div class="cart-item-name">${item.nama}</div>
                                ${badgeHtml}
                            </div>
                        </div>
                        <div class="cart-item-price-info">${priceDisplay}</div>
                        <div class="cart-item-footer">
                            <button type="button" class="btn-action-gratis ${item.is_gratis ? 'active' : ''}" 
                                    onclick="toggleGratis('${cartId}')" title="${item.is_gratis ? 'Klik untuk kembali normal' : 'Klik untuk gratis'}">
                                <i class="bi bi-gift"></i>
                            </button>
                            <div class="cart-item-controls">
                                <button type="button" class="qty-btn" onclick="decreaseQty('${cartId}')">−</button>
                                <span class="qty-display">${item.qty}</span>
                                <button type="button" class="qty-btn" onclick="increaseQty('${cartId}')">+</button>
                                <button type="button" class="btn-remove" onclick="removeItem('${cartId}')">×</button>
                            </div>
                        </div>
                    </div>
                `;

                totalItem++;
                totalQty += item.qty;
                totalHarga += subtotal;
                totalHargaAwal += subtotalAwal;
            }

            cartItemsContainer.innerHTML = html;

            // Update summary
            document.getElementById('totalItem').textContent = totalItem;
            document.getElementById('totalQty').textContent = totalQty;
            document.getElementById('totalHarga').textContent = formatRupiah(totalHarga);
            
            document.getElementById('totalItem2').textContent = totalItem;
            document.getElementById('totalQty2').textContent = totalQty;
            document.getElementById('totalHarga2').textContent = formatRupiah(totalHarga);

            // Show/hide potongan info
            const potongan = totalHargaAwal - totalHarga;
            if (potongan > 0) {
                document.getElementById('potonganInfo').style.display = 'block';
                document.getElementById('hargaAwal').textContent = formatRupiah(totalHargaAwal);
                document.getElementById('nominalPotongan').textContent = formatRupiah(potongan);
                
                document.getElementById('potonganInfo2').style.display = 'block';
                document.getElementById('hargaAwal2').textContent = formatRupiah(totalHargaAwal);
                document.getElementById('nominalPotongan2').textContent = formatRupiah(potongan);
            } else {
                document.getElementById('potonganInfo').style.display = 'none';
                document.getElementById('potonganInfo2').style.display = 'none';
            }

            if (uangBayarValue || uangTerimaValue) updateUangBayar();
        }
    }

    window.increaseQty = function(cartId) {
        if (cart[cartId] && cart[cartId].qty < 99) {
            cart[cartId].qty++;
            updateCart();
        }
    };

    window.decreaseQty = function(cartId) {
        if (cart[cartId]) {
            if (cart[cartId].qty > 1) {
                cart[cartId].qty--;
                updateCart();
            } else {
                removeItem(cartId);
            }
        }
    };

    window.removeItem = function(cartId) {
        delete cart[cartId];
        updateCart();
    };

    // =================================
    // TOGGLE GRATIS (SPLIT LOGIC)
    // =================================
    window.toggleGratis = function(cartId) {
        const item = cart[cartId];
        
        if (item.is_gratis) {
            // Jika item gratis diklik → gabung ke item normal
            let foundNormal = false;
            for (let cId in cart) {
                if (cId !== cartId && cart[cId].menu_id === item.menu_id && !cart[cId].is_gratis) {
                    cart[cId].qty += item.qty;
                    delete cart[cartId];
                    foundNormal = true;
                    break;
                }
            }
            
            if (!foundNormal) {
                item.is_gratis = false;
            }
        } else {
            // Jika item normal diklik → split 1 qty jadi gratis
            if (item.qty > 1) {
                item.qty--;
                
                const newCartId = 'cart_' + (++cartIdCounter);
                cart[newCartId] = {
                    cart_id: newCartId,
                    menu_id: item.menu_id,
                    nama: item.nama,
                    harga: item.harga,
                    qty: 1,
                    is_gratis: true
                };
            } else {
                item.is_gratis = true;
            }
        }
        
        updateCart();
    };

    // =================================
    // PAYMENT METHOD
    // =================================
    metodePembayaran.addEventListener('change', function() {
        const metode = this.value;
        
        if (metode === 'tunai') {
            // Tunai: tampilkan numpad untuk uang bayar
            formUangBayar.style.display = 'block';
            document.getElementById('formUangBayarInput').style.display = 'block';
            document.getElementById('formUangTerimaInput').style.display = 'none';
            document.getElementById('displayKembaliMini').style.display = 'block';
            uangBayarInput.required = true;
        } else {
            // GoPay/Grab/QRIS: tampilkan numpad untuk uang diterima
            formUangBayar.style.display = 'block';
            document.getElementById('formUangBayarInput').style.display = 'none';
            document.getElementById('formUangTerimaInput').style.display = 'block';
            document.getElementById('displayKembaliMini').style.display = 'none';
            uangBayarInput.required = false;
        }
        
        // Reset input
        uangBayarValue = "";
        uangTerimaValue = "";
        updateUangBayar();
    });

    // =================================
    // NUMPAD & QUICK BUTTONS
    // =================================
    document.querySelectorAll('.numpad-btn, .btn-quick').forEach(btn => {
        btn.addEventListener('click', function() {
            const value = this.dataset.value;
            const metode = metodePembayaran.value;

            if (value === 'clear') {
                if (metode === 'tunai') {
                    uangBayarValue = "";
                } else {
                    uangTerimaValue = "";
                }
                updateUangBayar();
                return;
            }

            if (value === 'backspace') {
                if (metode === 'tunai') {
                    uangBayarValue = uangBayarValue.slice(0, -1);
                } else {
                    uangTerimaValue = uangTerimaValue.slice(0, -1);
                }
                updateUangBayar();
                return;
            }

            if (value === 'exact') {
                const totalHarga = getTotalHarga();
                if (metode === 'tunai') {
                    uangBayarValue = String(totalHarga);
                } else {
                    uangTerimaValue = String(totalHarga);
                }
                updateUangBayar();
                return;
            }

            // Tambah digit
            if (metode === 'tunai') {
                uangBayarValue += value;
                uangBayarValue = uangBayarValue.replace(/^0+(\d)/, "$1");
            } else {
                uangTerimaValue += value;
                uangTerimaValue = uangTerimaValue.replace(/^0+(\d)/, "$1");
            }
            updateUangBayar();
        });
    });

    function updateUangBayar() {
        const metode = metodePembayaran.value;
        const totalHarga = getTotalHarga();

        if (metode === 'tunai') {
            // TUNAI: Validasi uang bayar >= total
            const numberValue = parseInt(uangBayarValue || "0");
            uangBayarInput.value = formatRupiahInput(numberValue);

            const kembali = numberValue - totalHarga;
            document.getElementById('uangKembaliMini').textContent = formatRupiah(kembali);

            if (kembali >= 0 && numberValue > 0) {
                btnProses.disabled = false;
            } else {
                btnProses.disabled = true;
            }
        } else {
            // GOPAY/GRAB/QRIS: Input uang yang diterima (bebas)
            const numberValue = parseInt(uangTerimaValue || "0");
            uangTerimaInput.value = formatRupiahInput(numberValue);

            if (numberValue > 0) {
                btnProses.disabled = false;
            } else {
                btnProses.disabled = true;
            }
        }
    }

    // =================================
    // FORM SUBMIT
    // =================================
    document.getElementById('formTransaksi').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (Object.keys(cart).length === 0) {
            alert('Pilih minimal 1 menu!');
            return false;
        }

        const totalHarga = getTotalHarga();
        const metode = metodePembayaran.value;

        // Validasi sesuai metode
        if (metode === 'tunai') {
            const uangBayar = parseInt(uangBayarValue || "0");
            if (uangBayar < totalHarga) {
                alert('Uang bayar kurang! Total: ' + formatRupiah(totalHarga));
                return false;
            }
        } else {
            const uangTerima = parseInt(uangTerimaValue || "0");
            if (uangTerima <= 0) {
                alert('Masukkan jumlah uang yang diterima!');
                return false;
            }
        }

        // GROUP cart by menu_id untuk POST
        const groupedCart = {};
        for (let cartId in cart) {
            const item = cart[cartId];
            if (!groupedCart[item.menu_id]) {
                groupedCart[item.menu_id] = {
                    menu_id: item.menu_id,
                    qty_total: 0,
                    qty_bayar: 0
                };
            }
            groupedCart[item.menu_id].qty_total += item.qty;
            if (!item.is_gratis) {
                groupedCart[item.menu_id].qty_bayar += item.qty;
            }
        }

        // POST data
        for (let menuId in groupedCart) {
            const data = groupedCart[menuId];
            
            const inputMenuId = document.createElement('input');
            inputMenuId.type = 'hidden';
            inputMenuId.name = 'menu_id[]';
            inputMenuId.value = data.menu_id;
            this.appendChild(inputMenuId);

            const inputJumlah = document.createElement('input');
            inputJumlah.type = 'hidden';
            inputJumlah.name = 'jumlah[]';
            inputJumlah.value = data.qty_bayar;
            this.appendChild(inputJumlah);
        }

        // Hidden input untuk uang bayar/terima
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'uang_bayar_value';
        
        if (metode === 'tunai') {
            hiddenInput.value = uangBayarValue;
        } else {
            // Untuk GoPay/Grab/QRIS, kirim uang yang diterima
            hiddenInput.value = uangTerimaValue;
        }
        this.appendChild(hiddenInput);

        const pesanKonfirmasi = metode === 'tunai' 
            ? 'Proses transaksi sebesar ' + formatRupiah(totalHarga) + '?'
            : 'Proses transaksi sebesar ' + formatRupiah(totalHarga) + '?\nUang diterima: ' + formatRupiah(parseInt(uangTerimaValue || "0"));

        if (confirm(pesanKonfirmasi)) {
            this.submit();
        }
    });

    // =================================
    // UTILITIES
    // =================================
    function getTotalHarga() {
        let total = 0;
        for (let cartId in cart) {
            const item = cart[cartId];
            if (!item.is_gratis) {
                total += item.harga * item.qty;
            }
        }
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
        return new Intl.NumberFormat('id-ID').format(number);
    }

});

// RESET FORM
function resetForm() {
    if (!confirm('Reset semua data transaksi?')) return;
    window.location.reload(); 
}
</script>