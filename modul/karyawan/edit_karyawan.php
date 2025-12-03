<?php
/**
 * FORM EDIT KARYAWAN
 * Halaman untuk edit data karyawan/user
 */

// Get ID dari URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    $_SESSION['error'] = 'ID karyawan tidak valid!';
    header('Location: index.php?page=list_karyawan');
    exit;
}

// Get data karyawan
$karyawan = fetchOne("SELECT * FROM users WHERE id = ?", [$id]);

if (!$karyawan) {
    $_SESSION['error'] = 'Karyawan tidak ditemukan!';
    header('Location: index.php?page=list_karyawan');
    exit;
}

// Get statistik karyawan
$total_transaksi = fetchOne("SELECT COUNT(*) as total FROM transaksi_penjualan WHERE user_id = ?", [$id])['total'];
$total_opname = fetchOne("SELECT COUNT(*) as total FROM stock_opname WHERE user_id = ?", [$id])['total'];
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-pencil-square"></i> Edit Data Karyawan</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php?page=list_karyawan">Karyawan</a></li>
                <li class="breadcrumb-item active">Edit Karyawan</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-badge"></i> Form Edit Data Karyawan
            </div>
            <div class="card-body">
                <form action="config/karyawan_proses.php?action=update" method="POST" id="formEditKaryawan">
                    <input type="hidden" name="id" value="<?php echo $karyawan['id']; ?>">
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Username *</label>
                                <input type="text" class="form-control" name="username" 
                                       value="<?php echo htmlspecialchars($karyawan['username']); ?>"
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
                                       value="<?php echo htmlspecialchars($karyawan['nama_lengkap']); ?>"
                                       placeholder="Nama lengkap karyawan" required maxlength="100">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Password Baru</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="password" 
                                           id="password" placeholder="Kosongkan jika tidak diubah" 
                                           minlength="6" maxlength="50">
                                    <button class="btn btn-outline-secondary" type="button" 
                                            id="togglePassword">
                                        <i class="bi bi-eye" id="iconPassword"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    <i class="bi bi-info-circle"></i> Kosongkan jika tidak ingin mengubah password
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Role/Jabatan *</label>
                                <select class="form-select" name="role" required>
                                    <option value="">-- Pilih Role --</option>
                                    <option value="admin" <?php echo $karyawan['role'] == 'admin' ? 'selected' : ''; ?>>
                                        Admin
                                    </option>
                                    <option value="karyawan" <?php echo $karyawan['role'] == 'karyawan' ? 'selected' : ''; ?>>
                                        Karyawan
                                    </option>
                                </select>
                            </div>
                        </div>

                        <?php if ($id == $_SESSION['user_id']): ?>
                        <div class="col-md-12">
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>Perhatian:</strong> Anda sedang mengedit akun Anda sendiri. 
                                Hati-hati saat mengubah role atau password!
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <strong>Informasi:</strong>
                                <ul class="mb-0">
                                    <li>Password hanya diubah jika field password diisi</li>
                                    <li>Username sebaiknya tidak diubah jika sudah banyak digunakan</li>
                                    <li>Perubahan role akan langsung berpengaruh pada akses sistem</li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update Data
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
            <div class="card-header bg-info text-white">
                <i class="bi bi-person-circle"></i> Info Karyawan
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>Username:</strong></td>
                        <td><?php echo htmlspecialchars($karyawan['username']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Role:</strong></td>
                        <td>
                            <?php if ($karyawan['role'] == 'admin'): ?>
                                <span class="badge bg-danger">Admin</span>
                            <?php else: ?>
                                <span class="badge bg-info">Karyawan</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            <?php if ($karyawan['is_active']): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Non-aktif</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Terdaftar:</strong></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($karyawan['created_at'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Login Terakhir:</strong></td>
                        <td>
                            <?php if ($karyawan['last_login']): ?>
                                <?php echo date('d/m/Y H:i', strtotime($karyawan['last_login'])); ?>
                            <?php else: ?>
                                <em class="text-muted">Belum pernah login</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-success text-white">
                <i class="bi bi-graph-up"></i> Statistik Aktivitas
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted">Total Transaksi:</label>
                    <h4 class="text-primary"><?php echo $total_transaksi; ?></h4>
                </div>
                <div class="mb-3">
                    <label class="text-muted">Total Stock Opname:</label>
                    <h4 class="text-success"><?php echo $total_opname; ?></h4>
                </div>
                <?php if ($total_transaksi > 0 || $total_opname > 0): ?>
                <div class="alert alert-warning mb-0">
                    <small>
                        <i class="bi bi-info-circle"></i> 
                        Karyawan ini memiliki history aktivitas. 
                        Pertimbangkan untuk non-aktifkan daripada menghapus.
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-warning">
                <i class="bi bi-shield-check"></i> Keamanan
            </div>
            <div class="card-body">
                <h6>Tips Keamanan:</h6>
                <ul class="small mb-0">
                    <li>Ubah password secara berkala</li>
                    <li>Gunakan password yang kuat</li>
                    <li>Jangan bagikan password ke orang lain</li>
                    <li>Logout setelah selesai bekerja</li>
                </ul>
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
document.getElementById('formEditKaryawan').addEventListener('submit', function(e) {
    const username = document.querySelector('[name="username"]').value;
    const password = document.querySelector('[name="password"]').value;
    
    // Validasi username
    if (!/^[a-zA-Z0-9_]+$/.test(username)) {
        e.preventDefault();
        alert('Username hanya boleh mengandung huruf, angka, dan underscore (_)');
        return false;
    }
    
    // Validasi password jika diisi
    if (password && password.length < 6) {
        e.preventDefault();
        alert('Password minimal 6 karakter!');
        return false;
    }
    
    return true;
});
</script>