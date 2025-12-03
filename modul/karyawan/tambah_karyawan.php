<?php
/**
 * FORM TAMBAH KARYAWAN
 * Halaman untuk menambah karyawan/user baru
 */
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-person-plus"></i> Tambah Karyawan Baru</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php?page=list_karyawan">Karyawan</a></li>
                <li class="breadcrumb-item active">Tambah Karyawan</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-badge"></i> Form Data Karyawan
            </div>
            <div class="card-body">
                <form action="config/karyawan_proses.php?action=create" method="POST" id="formTambahKaryawan">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Username *</label>
                                <input type="text" class="form-control" name="username" 
                                       placeholder="Username untuk login" required maxlength="50"
                                       pattern="[a-zA-Z0-9_]+" 
                                       title="Username hanya boleh huruf, angka, dan underscore">
                                <small class="text-muted">Hanya huruf, angka, dan underscore (_)</small>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap *</label>
                                <input type="text" class="form-control" name="nama_lengkap" 
                                       placeholder="Nama lengkap karyawan" required maxlength="100">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Password *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="password" 
                                           id="password" placeholder="Password minimal 6 karakter" 
                                           required minlength="6" maxlength="50">
                                    <button class="btn btn-outline-secondary" type="button" 
                                            id="togglePassword">
                                        <i class="bi bi-eye" id="iconPassword"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    <i class="bi bi-info-circle"></i> Minimal 6 karakter
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Role/Jabatan *</label>
                                <select class="form-select" name="role" required>
                                    <option value="">-- Pilih Role --</option>
                                    <option value="admin">Admin</option>
                                    <option value="karyawan">Karyawan</option>
                                </select>
                                <div class="form-text">
                                    <i class="bi bi-info-circle"></i> Admin: akses penuh, Karyawan: akses terbatas
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="bi bi-shield-check"></i>
                                <strong>Perbedaan Role:</strong>
                                <ul class="mb-0 mt-2">
                                    <li><strong>Admin:</strong> Akses penuh ke semua fitur termasuk manajemen karyawan, pembelian, kas, dan laporan lengkap</li>
                                    <li><strong>Karyawan:</strong> Akses terbatas untuk transaksi penjualan, lihat menu, lihat stok, dan laporan harian</li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>Catatan Penting:</strong>
                                <ul class="mb-0">
                                    <li>Username tidak bisa diubah setelah dibuat (untuk keamanan log)</li>
                                    <li>Password dapat diubah sewaktu-waktu di menu edit</li>
                                    <li>Pastikan username mudah diingat oleh karyawan</li>
                                    <li>Password akan di-enkripsi secara otomatis</li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Simpan Karyawan
                            </button>
                            <a href="index.php?page=list_karyawan" class="btn btn-secondary">
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
            <div class="card-header bg-primary text-white">
                <i class="bi bi-lightbulb"></i> Tips Keamanan
            </div>
            <div class="card-body">
                <h6>Password yang Kuat:</h6>
                <ul class="small">
                    <li>Minimal 6-8 karakter</li>
                    <li>Kombinasi huruf dan angka</li>
                    <li>Hindari password umum (123456, password, dll)</li>
                    <li>Jangan gunakan nama atau tanggal lahir</li>
                </ul>

                <hr>

                <h6>Tips Username:</h6>
                <ul class="small mb-0">
                    <li>Gunakan nama depan + angka</li>
                    <li>Contoh: budi123, ani_kasir</li>
                    <li>Mudah diingat tapi unik</li>
                </ul>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-success text-white">
                <i class="bi bi-list-check"></i> Checklist
            </div>
            <div class="card-body">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check1">
                    <label class="form-check-label" for="check1">
                        Username sudah diverifikasi
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check2">
                    <label class="form-check-label" for="check2">
                        Password sudah dicatat
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="check3">
                    <label class="form-check-label" for="check3">
                        Role sudah sesuai
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordField = document.getElementById('password');
    const icon = document.getElementById('iconPassword');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        passwordField.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
});

// Form validation
document.getElementById('formTambahKaryawan').addEventListener('submit', function(e) {
    const username = document.querySelector('[name="username"]').value;
    const password = document.querySelector('[name="password"]').value;
    
    // Validasi username
    if (!/^[a-zA-Z0-9_]+$/.test(username)) {
        e.preventDefault();
        alert('Username hanya boleh mengandung huruf, angka, dan underscore (_)');
        return false;
    }
    
    // Validasi password
    if (password.length < 6) {
        e.preventDefault();
        alert('Password minimal 6 karakter!');
        return false;
    }
    
    return true;
});
</script>