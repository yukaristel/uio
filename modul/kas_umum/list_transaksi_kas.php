<?php
/**
 * LIST TRANSAKSI
 * Dengan fitur Jurnal Pembalik
 */

// Pagination
$limit = 50;
$page = isset($_GET['hal']) ? intval($_GET['hal']) : 1;
$offset = ($page - 1) * $limit;

// Filter
$filter_tanggal_dari = isset($_GET['dari']) ? $_GET['dari'] : date('Y-m-01');
$filter_tanggal_sampai = isset($_GET['sampai']) ? $_GET['sampai'] : date('Y-m-d');
$filter_akun = isset($_GET['akun']) ? $_GET['akun'] : '';

// Query
$where = "WHERE tgl_transaksi BETWEEN ? AND ?";
$params = [$filter_tanggal_dari, $filter_tanggal_sampai];

if (!empty($filter_akun)) {
    $where .= " AND (rekening_debet = ? OR rekening_kredit = ?)";
    $params[] = $filter_akun;
    $params[] = $filter_akun;
}

// Get data
$transaksi = fetchAll("
    SELECT t.*, 
           d.nama_akun as nama_debet, 
           k.nama_akun as nama_kredit
    FROM transaksi t
    LEFT JOIN chart_of_accounts d ON t.rekening_debet = d.kode_akun
    LEFT JOIN chart_of_accounts k ON t.rekening_kredit = k.kode_akun
    {$where}
    ORDER BY t.tgl_transaksi DESC, t.id DESC
    LIMIT {$limit} OFFSET {$offset}
", $params);

// Total
$total = fetchOne("SELECT COUNT(*) as total FROM transaksi {$where}", $params);
$total_rows = $total['total'];
$total_pages = ceil($total_rows / $limit);

// Get COA untuk filter
$coa_all = fetchAll("
    SELECT kode_akun, nama_akun 
    FROM chart_of_accounts 
    WHERE lev4 > 0
    ORDER BY kode_akun
");
?>

<div class="row mb-3">
    <div class="col-md-12">
        <h2><i class="bi bi-journal-text"></i> Daftar Transaksi</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Daftar Transaksi</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="index.php" class="row g-3">
            <input type="hidden" name="page" value="list_transaksi">
            
            <div class="col-md-3">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" class="form-control" name="dari" 
                       value="<?php echo $filter_tanggal_dari; ?>">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" class="form-control" name="sampai" 
                       value="<?php echo $filter_tanggal_sampai; ?>">
            </div>
            
            <div class="col-md-4">
                <label class="form-label">Filter Akun</label>
                <select class="form-select" name="akun">
                    <option value="">-- Semua Akun --</option>
                    <?php foreach($coa_all as $coa): ?>
                    <option value="<?php echo $coa['kode_akun']; ?>" 
                            <?php echo ($filter_akun == $coa['kode_akun']) ? 'selected' : ''; ?>>
                        <?php echo $coa['kode_akun']; ?> - <?php echo $coa['nama_akun']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Actions -->
<div class="mb-3">
    <a href="index.php?page=tambah_transaksi" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Tambah Transaksi
    </a>
    <a href="index.php?page=list_transaksi" class="btn btn-secondary">
        <i class="bi bi-arrow-clockwise"></i> Reset Filter
    </a>
</div>

<!-- Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th width="5%">No</th>
                        <th width="10%">Tanggal</th>
                        <th width="15%">Debet</th>
                        <th width="15%">Kredit</th>
                        <th width="30%">Keterangan</th>
                        <th width="12%" class="text-end">Jumlah</th>
                        <th width="13%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transaksi)): ?>
                    <tr>
                        <td colspan="7" class="text-center">Tidak ada data transaksi</td>
                    </tr>
                    <?php else: ?>
                        <?php 
                        $no = $offset + 1;
                        foreach($transaksi as $t): 
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($t['tgl_transaksi'])); ?></td>
                            <td>
                                <small class="text-muted"><?php echo $t['rekening_debet']; ?></small><br>
                                <?php echo $t['nama_debet']; ?>
                            </td>
                            <td>
                                <small class="text-muted"><?php echo $t['rekening_kredit']; ?></small><br>
                                <?php echo $t['nama_kredit']; ?>
                            </td>
                            <td>
                                <?php 
                                // Highlight jika jurnal pembalik
                                if (strpos($t['keterangan_transaksi'], '[PEMBALIK]') !== false) {
                                    echo '<span class="badge bg-warning text-dark">PEMBALIK</span> ';
                                }
                                if (strpos($t['keterangan_transaksi'], '[SUDAH DIBALIK') !== false) {
                                    echo '<span class="badge bg-secondary">DIBALIK</span> ';
                                }
                                echo htmlspecialchars($t['keterangan_transaksi']); 
                                ?>
                            </td>
                            <td class="text-end">
                                <strong><?php echo formatRupiah($t['jumlah']); ?></strong>
                            </td>
                            <td class="text-center">
                                <!-- Tombol Jurnal Pembalik -->
                                <?php if (strpos($t['keterangan_transaksi'], '[SUDAH DIBALIK') === false): ?>
                                <a href="config/kas_proses.php?action=reverse&id=<?php echo $t['id']; ?>" 
                                   class="btn btn-warning btn-sm" 
                                   onclick="return confirm('Buat jurnal pembalik untuk transaksi ini?\n\nIni akan membuat transaksi kebalikan untuk membatalkan transaksi yang salah.');"
                                   title="Jurnal Pembalik">
                                    <i class="bi bi-arrow-left-right"></i>
                                </a>
                                <?php else: ?>
                                <button class="btn btn-secondary btn-sm" disabled title="Sudah dibalik">
                                    <i class="bi bi-check-circle"></i>
                                </button>
                                <?php endif; ?>
                                
                                <!-- Tombol Hapus -->
                                <a href="config/kas_proses.php?action=delete&id=<?php echo $t['id']; ?>" 
                                   class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Yakin hapus transaksi ini?\n\nPeringatan: Penghapusan akan mempengaruhi saldo!');"
                                   title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=list_transaksi&hal=<?php echo $i; ?>&dari=<?php echo $filter_tanggal_dari; ?>&sampai=<?php echo $filter_tanggal_sampai; ?>&akun=<?php echo $filter_akun; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
        
        <!-- Info -->
        <div class="text-center text-muted mt-3">
            <small>
                Menampilkan <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $total_rows); ?> 
                dari <?php echo $total_rows; ?> transaksi
            </small>
        </div>
    </div>
</div>

<!-- Legend -->
<div class="card mt-3">
    <div class="card-body">
        <h6><i class="bi bi-info-circle"></i> Keterangan:</h6>
        <ul class="mb-0">
            <li><span class="badge bg-warning text-dark">PEMBALIK</span> = Jurnal pembalik (untuk koreksi transaksi yang salah)</li>
            <li><span class="badge bg-secondary">DIBALIK</span> = Transaksi sudah dibuat pembaliknya</li>
            <li><i class="bi bi-arrow-left-right text-warning"></i> = Tombol untuk membuat jurnal pembalik</li>
            <li><i class="bi bi-trash text-danger"></i> = Tombol untuk hapus transaksi</li>
        </ul>
    </div>
</div>