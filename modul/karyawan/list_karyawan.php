<?php
/**
 * LIST KARYAWAN
 * Halaman daftar semua karyawan/user
 */

// Get semua karyawan
$karyawan_list = fetchAll("SELECT * FROM users ORDER BY created_at DESC");

// Statistik
$total_admin = fetchOne("SELECT COUNT(*) as total FROM users WHERE role = 'admin'")['total'];
$total_karyawan = fetchOne("SELECT COUNT(*) as total FROM users WHERE role = 'karyawan'")['total'];
$total_aktif = fetchOne("SELECT COUNT(*) as total FROM users WHERE is_active = 1")['total'];
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-people"></i> Manajemen Karyawan</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Karyawan</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Statistik Cards -->
<div class="row mb-3">
    <div class="col-md-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Karyawan</h6>
                        <h2 class="mb-0"><?php echo count($karyawan_list); ?></h2>
                    </div>
                    <div>
                        <i class="bi bi-people fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Admin</h6>
                        <h2 class="mb-0"><?php echo $total_admin; ?></h2>
                    </div>
                    <div>
                        <i class="bi bi-shield-check fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Karyawan</h6>
                        <h2 class="mb-0"><?php echo $total_karyawan; ?></h2>
                    </div>
                    <div>
                        <i class="bi bi-person-badge fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="row mb-3">
    <div class="col-md-12">
        <a href="index.php?page=tambah_karyawan" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Karyawan Baru
        </a>
    </div>
</div>

<!-- Tabel Karyawan -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-table"></i> Daftar Karyawan
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tableKaryawan">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%">No</th>
                                <th width="15%">Username</th>
                                <th width="20%">Nama Lengkap</th>
                                <th width="12%">Role</th>
                                <th width="12%">Status</th>
                                <th width="15%">Terdaftar</th>
                                <th width="15%">Login Terakhir</th>
                                <th width="6%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($karyawan_list)): ?>
                            <tr>
                                <td colspan="8" class="text-center">Belum ada data karyawan</td>
                            </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($karyawan_list as $k): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($k['username']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($k['nama_lengkap']); ?></td>
                                    <td>
                                        <?php if ($k['role'] == 'admin'): ?>
                                            <span class="badge bg-danger">
                                                <i class="bi bi-shield-check"></i> Admin
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-info">
                                                <i class="bi bi-person-badge"></i> Karyawan
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($k['is_active']): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i> Aktif
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-x-circle"></i> Non-aktif
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo date('d/m/Y H:i', strtotime($k['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($k['last_login']): ?>
                                            <small><?php echo date('d/m/Y H:i', strtotime($k['last_login'])); ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">Belum login</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="index.php?page=edit_karyawan&id=<?php echo $k['id']; ?>" 
                                               class="btn btn-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if ($k['id'] != $_SESSION['user_id']): ?>
                                            <a href="config/karyawan_proses.php?action=delete&id=<?php echo $k['id']; ?>" 
                                               class="btn btn-danger" 
                                               onclick="return confirm('Yakin ingin menghapus karyawan <?php echo htmlspecialchars($k['nama_lengkap']); ?>?')"
                                               title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// DataTable initialization
$(document).ready(function() {
    $('#tableKaryawan').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json"
        },
        "order": [[5, "desc"]], // Sort by terdaftar
        "pageLength": 25
    });
});
</script>