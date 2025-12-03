<?php
/**
 * FORM TAMBAH KATEGORI MENU
 * Step 38/64 (59.4%)
 */
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-plus-circle"></i> Tambah Kategori Menu</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=list_kategori">Kategori Menu</a></li>
                <li class="breadcrumb-item active">Tambah Kategori</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-tag"></i> Form Kategori Menu
            </div>
            <div class="card-body">
                <form action="config/kategori_proses.php?action=create" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori *</label>
                        <input type="text" class="form-control" name="nama_kategori" 
                               required maxlength="50" placeholder="Contoh: Makanan Berat">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="deskripsi" rows="3"
                                  placeholder="Deskripsi kategori (opsional)"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Simpan Kategori
                    </button>
                    <a href="index.php?page=list_kategori" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Batal
                    </a>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-info text-white">
                <i class="bi bi-lightbulb"></i> Contoh Kategori
            </div>
            <div class="card-body">
                <div class="list-group">
                    <div class="list-group-item">
                        <h6 class="mb-1">Makanan Berat</h6>
                        <small class="text-muted">Nasi, Mie, Lauk Pauk</small>
                    </div>
                    <div class="list-group-item">
                        <h6 class="mb-1">Makanan Ringan</h6>
                        <small class="text-muted">Snack, Camilan, Gorengan</small>
                    </div>
                    <div class="list-group-item">
                        <h6 class="mb-1">Minuman Dingin</h6>
                        <small class="text-muted">Es Teh, Jus, Soft Drink</small>
                    </div>
                    <div class="list-group-item">
                        <h6 class="mb-1">Minuman Panas</h6>
                        <small class="text-muted">Kopi, Teh, Susu</small>
                    </div>
                    <div class="list-group-item">
                        <h6 class="mb-1">Dessert</h6>
                        <small class="text-muted">Pudding, Ice Cream, Cake</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>