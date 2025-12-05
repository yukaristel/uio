<?php
/**
 * GENERATE STOCK - Recalculate Harga Rata-rata Tertimbang
 * Fitur ini akan:
 * 1. Recalculate harga rata-rata tertimbang untuk semua bahan
 * 2. Update harga_per_satuan di stock_movement
 * 3. Update nilai_selisih di stock_opname
 * Step Custom - Generate Stock
 */

// Ambil semua bahan baku
$bahan_list = fetchAll("SELECT * FROM bahan_baku ORDER BY nama_bahan");

// Statistik
$stats = [
    'total_bahan' => 0,
    'total_movement_updated' => 0,
    'total_opname_updated' => 0,
    'errors' => []
];
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-arrow-repeat"></i> Generate Stock - Recalculate Harga</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php?page=list_bahan">Bahan Baku</a></li>
                    <li class="breadcrumb-item active">Generate Stock</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Warning Box -->
    <div class="alert alert-warning" role="alert">
        <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Peringatan!</h5>
        <p class="mb-0">
            Proses ini akan menghitung ulang <strong>harga rata-rata tertimbang</strong> untuk semua bahan baku 
            berdasarkan riwayat pembelian dan stock movement. Proses ini akan:
        </p>
        <ul class="mb-0 mt-2">
            <li>Update <code>harga_beli_per_satuan</code> di tabel <code>bahan_baku</code></li>
            <li>Update <code>harga_per_satuan</code> dan <code>total_nilai</code> di tabel <code>stock_movement</code></li>
            <li>Update <code>harga_per_satuan</code> dan <code>nilai_selisih</code> di tabel <code>stock_opname</code></li>
        </ul>
        <hr>
        <p class="mb-0">
            <strong>Catatan:</strong> Proses ini sebaiknya dilakukan di luar jam operasional atau saat tidak ada transaksi.
        </p>
    </div>

    <!-- Action Buttons -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-gear"></i> Pilihan Generate</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-calculator display-4 text-primary"></i>
                            <h5 class="card-title mt-3">Generate Semua</h5>
                            <p class="card-text">Recalculate harga untuk semua bahan baku</p>
                            <form action="config/generate_stock_proses.php?action=generate_all" method="POST" onsubmit="return confirm('Yakin generate semua bahan? Proses ini mungkin memakan waktu.')">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-arrow-repeat"></i> Generate Semua
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-funnel display-4 text-success"></i>
                            <h5 class="card-title mt-3">Generate Per Bahan</h5>
                            <p class="card-text">Pilih bahan tertentu untuk di-generate</p>
                            <a href="#formPerBahan" class="btn btn-success w-100">
                                <i class="bi bi-arrow-down"></i> Pilih Bahan
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-eye display-4 text-info"></i>
                            <h5 class="card-title mt-3">Preview Perubahan</h5>
                            <p class="card-text">Lihat simulasi tanpa mengubah data</p>
                            <form action="config/generate_stock_proses.php?action=preview" method="POST" target="_blank">
                                <button type="submit" class="btn btn-info w-100">
                                    <i class="bi bi-eye"></i> Preview
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Generate Per Bahan -->
    <div class="card mb-4" id="formPerBahan">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-funnel"></i> Generate Per Bahan</h5>
        </div>
        <div class="card-body">
            <form action="config/generate_stock_proses.php?action=generate_selected" method="POST" onsubmit="return confirm('Yakin generate bahan yang dipilih?')">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)">
                                </th>
                                <th>Kode</th>
                                <th>Nama Bahan</th>
                                <th>Satuan</th>
                                <th>Stok</th>
                                <th>Harga Saat Ini</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bahan_list)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada data bahan baku</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bahan_list as $bahan): ?>
                                    <?php
                                    // Cek apakah bahan punya riwayat pembelian
                                    $has_purchase = fetchOne("SELECT COUNT(*) as total FROM pembelian_bahan WHERE bahan_id = ?", [$bahan['id']]);
                                    $can_generate = $has_purchase['total'] > 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="bahan_ids[]" value="<?php echo $bahan['id']; ?>" 
                                                   <?php echo !$can_generate ? 'disabled' : ''; ?>>
                                        </td>
                                        <td><?php echo $bahan['kode_bahan']; ?></td>
                                        <td><?php echo $bahan['nama_bahan']; ?></td>
                                        <td><span class="badge bg-secondary"><?php echo strtoupper($bahan['satuan']); ?></span></td>
                                        <td><?php echo number_format($bahan['stok_tersedia'], 2); ?></td>
                                        <td><?php echo formatRupiah($bahan['harga_beli_per_satuan']); ?></td>
                                        <td>
                                            <?php if ($can_generate): ?>
                                                <span class="badge bg-success">Siap Generate</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Belum ada pembelian</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="bi bi-arrow-repeat"></i> Generate Bahan Terpilih
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Info Box -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-info-circle"></i> Cara Kerja Generate Stock</h5>
        </div>
        <div class="card-body">
            <h6>1. Perhitungan Harga Rata-rata Tertimbang (Weighted Average)</h6>
            <p>Sistem akan menghitung harga baru menggunakan formula:</p>
            <div class="alert alert-light">
                <code>
                    Harga Baru = [(Stok Lama × Harga Lama) + (Stok Masuk × Harga Beli)] / (Stok Lama + Stok Masuk)
                </code>
            </div>

            <h6 class="mt-4">2. Contoh Perhitungan</h6>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Stok</th>
                        <th>Harga/Unit</th>
                        <th>Nilai Total</th>
                        <th>Harga Rata-rata Baru</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Stok Awal</td>
                        <td>5 kg</td>
                        <td>Rp 10,000</td>
                        <td>Rp 50,000</td>
                        <td>Rp 10,000</td>
                    </tr>
                    <tr>
                        <td>Pembelian</td>
                        <td>100 kg</td>
                        <td>Rp 12,000</td>
                        <td>Rp 1,200,000</td>
                        <td>-</td>
                    </tr>
                    <tr class="table-success">
                        <td><strong>Total</strong></td>
                        <td><strong>105 kg</strong></td>
                        <td>-</td>
                        <td><strong>Rp 1,250,000</strong></td>
                        <td><strong>Rp 11,905</strong></td>
                    </tr>
                </tbody>
            </table>
            <p class="text-muted">
                <small>Perhitungan: (50,000 + 1,200,000) / 105 = 11,905</small>
            </p>

            <h6 class="mt-4">3. Update Data</h6>
            <ul>
                <li><strong>bahan_baku:</strong> Update kolom <code>harga_beli_per_satuan</code></li>
                <li><strong>stock_movement:</strong> Update kolom <code>harga_per_satuan</code> dan <code>total_nilai</code></li>
                <li><strong>stock_opname:</strong> Update kolom <code>harga_per_satuan</code> dan <code>nilai_selisih</code></li>
            </ul>

            <h6 class="mt-4">4. Kapan Perlu Generate?</h6>
            <ul>
                <li>Setelah import data besar</li>
                <li>Jika menemukan ketidaksesuaian harga</li>
                <li>Setelah melakukan koreksi data pembelian</li>
                <li>Secara berkala untuk maintenance (misal: akhir bulan)</li>
            </ul>
        </div>
    </div>
</div>

<script>
function toggleSelectAll(source) {
    const checkboxes = document.querySelectorAll('input[name="bahan_ids[]"]:not(:disabled)');
    checkboxes.forEach(checkbox => {
        checkbox.checked = source.checked;
    });
}
</script>