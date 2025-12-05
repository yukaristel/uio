<?php
/**
 * PROSES GENERATE STOCK - Recalculate Weighted Average
 * Step Custom - Generate Stock Processing
 */

session_start();
require_once 'database.php';

// Cek login & role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = 'Akses ditolak! Hanya admin yang bisa generate stock.';
    header('Location: ../index.php?page=list_bahan');
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'generate_all':
        generateAll();
        break;
    case 'generate_selected':
        generateSelected();
        break;
    case 'preview':
        previewGenerate();
        break;
    default:
        header('Location: ../index.php?page=generate_stock');
        exit;
}

/**
 * GENERATE ALL BAHAN
 */
function generateAll() {
    $conn = getConnection();
    
    try {
        $conn->begin_transaction();
        
        // Ambil semua bahan yang punya riwayat pembelian
        $bahan_list = fetchAll("
            SELECT DISTINCT b.* 
            FROM bahan_baku b
            INNER JOIN pembelian_bahan pb ON b.id = pb.bahan_id
            ORDER BY b.nama_bahan
        ");
        
        if (empty($bahan_list)) {
            throw new Exception('Tidak ada bahan yang memiliki riwayat pembelian!');
        }
        
        $success_count = 0;
        $error_count = 0;
        $errors = [];
        
        foreach ($bahan_list as $bahan) {
            try {
                generateStockForBahan($bahan['id']);
                $success_count++;
            } catch (Exception $e) {
                $error_count++;
                $errors[] = $bahan['nama_bahan'] . ': ' . $e->getMessage();
            }
        }
        
        $conn->commit();
        
        $message = "Generate stock berhasil! ";
        $message .= "Berhasil: $success_count bahan, Gagal: $error_count bahan.";
        
        if (!empty($errors)) {
            $message .= " Error: " . implode('; ', $errors);
        }
        
        $_SESSION['success'] = $message;
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = 'Generate gagal: ' . $e->getMessage();
    }
    
    header('Location: ../index.php?page=generate_stock');
    exit;
}

/**
 * GENERATE SELECTED BAHAN
 */
function generateSelected() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../index.php?page=generate_stock');
        exit;
    }
    
    $bahan_ids = isset($_POST['bahan_ids']) ? $_POST['bahan_ids'] : [];
    
    if (empty($bahan_ids)) {
        $_SESSION['error'] = 'Pilih minimal 1 bahan untuk di-generate!';
        header('Location: ../index.php?page=generate_stock');
        exit;
    }
    
    $conn = getConnection();
    
    try {
        $conn->begin_transaction();
        
        $success_count = 0;
        $error_count = 0;
        $errors = [];
        
        foreach ($bahan_ids as $bahan_id) {
            try {
                generateStockForBahan($bahan_id);
                $success_count++;
            } catch (Exception $e) {
                $error_count++;
                $bahan = fetchOne("SELECT nama_bahan FROM bahan_baku WHERE id = ?", [$bahan_id]);
                $errors[] = $bahan['nama_bahan'] . ': ' . $e->getMessage();
            }
        }
        
        $conn->commit();
        
        $message = "Generate stock berhasil! ";
        $message .= "Berhasil: $success_count bahan, Gagal: $error_count bahan.";
        
        if (!empty($errors)) {
            $message .= " Error: " . implode('; ', $errors);
        }
        
        $_SESSION['success'] = $message;
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = 'Generate gagal: ' . $e->getMessage();
    }
    
    header('Location: ../index.php?page=generate_stock');
    exit;
}

/**
 * PREVIEW GENERATE (tanpa commit)
 */
function previewGenerate() {
    // Ambil semua bahan yang punya riwayat pembelian
    $bahan_list = fetchAll("
        SELECT DISTINCT b.* 
        FROM bahan_baku b
        INNER JOIN pembelian_bahan pb ON b.id = pb.bahan_id
        ORDER BY b.nama_bahan
    ");
    
    $preview_data = [];
    
    foreach ($bahan_list as $bahan) {
        $history = calculateWeightedAverageHistory($bahan['id']);
        
        $preview_data[] = [
            'bahan' => $bahan,
            'harga_lama' => $bahan['harga_beli_per_satuan'],
            'harga_baru' => $history['final_price'],
            'selisih' => $history['final_price'] - $bahan['harga_beli_per_satuan'],
            'history' => $history['movements']
        ];
    }
    
    // Tampilkan preview
    displayPreview($preview_data);
    exit;
}

/**
 * CORE FUNCTION: Generate Stock untuk 1 Bahan
 */
function generateStockForBahan($bahan_id) {
    // 1. Hitung weighted average dari history pembelian
    $result = calculateWeightedAverageHistory($bahan_id);
    
    if (!$result['success']) {
        throw new Exception($result['message']);
    }
    
    $final_price = $result['final_price'];
    $movements = $result['movements'];
    
    // 2. Update harga di bahan_baku
    $sql_update_bahan = "UPDATE bahan_baku SET harga_beli_per_satuan = ? WHERE id = ?";
    execute($sql_update_bahan, [$final_price, $bahan_id]);
    
    // 3. Update harga di stock_movement (semua movement untuk bahan ini)
    foreach ($movements as $movement) {
        if ($movement['type'] == 'keluar') {
            // Update harga_per_satuan dan total_nilai
            $sql_update_movement = "
                UPDATE stock_movement 
                SET harga_per_satuan = ?,
                    total_nilai = jumlah * ?
                WHERE bahan_id = ? AND id = ?
            ";
            execute($sql_update_movement, [
                $movement['weighted_price'], 
                $movement['weighted_price'],
                $bahan_id,
                $movement['id']
            ]);
        }
    }
    
    // 4. Update harga di stock_opname
    $opnames = fetchAll("SELECT * FROM stock_opname WHERE bahan_id = ?", [$bahan_id]);
    
    foreach ($opnames as $opname) {
        $nilai_selisih_baru = abs($opname['selisih']) * $final_price;
        
        $sql_update_opname = "
            UPDATE stock_opname 
            SET harga_per_satuan = ?,
                nilai_selisih = ?
            WHERE id = ?
        ";
        execute($sql_update_opname, [$final_price, $nilai_selisih_baru, $opname['id']]);
    }
    
    return true;
}

/**
 * CALCULATE WEIGHTED AVERAGE DARI HISTORY
 */
function calculateWeightedAverageHistory($bahan_id) {
    // Ambil bahan info
    $bahan = fetchOne("SELECT * FROM bahan_baku WHERE id = ?", [$bahan_id]);
    
    if (!$bahan) {
        return ['success' => false, 'message' => 'Bahan tidak ditemukan'];
    }
    
    // Ambil semua pembelian berurutan
    $pembelian = fetchAll("
        SELECT * FROM pembelian_bahan 
        WHERE bahan_id = ? 
        ORDER BY tanggal_beli ASC, created_at ASC
    ", [$bahan_id]);
    
    if (empty($pembelian)) {
        return ['success' => false, 'message' => 'Belum ada riwayat pembelian'];
    }
    
    // Ambil semua stock movement untuk referensi
    $movements_db = fetchAll("
        SELECT * FROM stock_movement 
        WHERE bahan_id = ? 
        ORDER BY created_at ASC
    ", [$bahan_id]);
    
    // Simulasi stock flow dengan weighted average
    $current_stock = 0;
    $current_price = 0;
    $movements = [];
    
    // Index untuk tracking movement
    $movement_index = 0;
    
    foreach ($pembelian as $beli) {
        $qty_masuk = $beli['jumlah_beli'];
        $harga_beli = $beli['harga_beli_satuan'];
        
        // Hitung weighted average
        if ($current_stock > 0) {
            // Ada stok lama, hitung rata-rata tertimbang
            $total_nilai_lama = $current_stock * $current_price;
            $total_nilai_baru = $qty_masuk * $harga_beli;
            $total_stock = $current_stock + $qty_masuk;
            
            $current_price = ($total_nilai_lama + $total_nilai_baru) / $total_stock;
        } else {
            // Stok kosong, gunakan harga pembelian langsung
            $current_price = $harga_beli;
        }
        
        $current_stock += $qty_masuk;
        
        // Catat movement masuk
        $movements[] = [
            'type' => 'masuk',
            'qty' => $qty_masuk,
            'price' => $harga_beli,
            'weighted_price' => $current_price,
            'stock_after' => $current_stock,
            'tanggal' => $beli['tanggal_beli']
        ];
        
        // Cari movement keluar setelah pembelian ini
        while ($movement_index < count($movements_db)) {
            $move = $movements_db[$movement_index];
            
            if ($move['jenis_pergerakan'] == 'keluar') {
                $current_stock -= $move['jumlah'];
                
                $movements[] = [
                    'id' => $move['id'],
                    'type' => 'keluar',
                    'qty' => $move['jumlah'],
                    'price' => $move['harga_per_satuan'], // harga lama
                    'weighted_price' => $current_price, // harga baru
                    'stock_after' => $current_stock,
                    'tanggal' => $move['created_at']
                ];
            }
            
            $movement_index++;
            
            // Jika sudah mencapai pembelian berikutnya, break
            if ($movement_index < count($movements_db) && 
                isset($pembelian[array_search($beli, $pembelian) + 1])) {
                $next_purchase = $pembelian[array_search($beli, $pembelian) + 1];
                if (strtotime($movements_db[$movement_index]['created_at']) > 
                    strtotime($next_purchase['tanggal_beli'])) {
                    break;
                }
            }
        }
    }
    
    return [
        'success' => true,
        'final_price' => $current_price,
        'movements' => $movements
    ];
}

/**
 * DISPLAY PREVIEW
 */
function displayPreview($preview_data) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Preview Generate Stock</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    </head>
    <body>
        <div class="container my-5">
            <h2 class="mb-4"><i class="bi bi-eye"></i> Preview Generate Stock</h2>
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> 
                Ini adalah preview perhitungan. Data belum diubah di database.
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-primary">
                        <tr>
                            <th>Kode</th>
                            <th>Nama Bahan</th>
                            <th>Satuan</th>
                            <th>Harga Lama</th>
                            <th>Harga Baru</th>
                            <th>Selisih</th>
                            <th>% Perubahan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preview_data as $data): ?>
                            <?php
                            $bahan = $data['bahan'];
                            $harga_lama = $data['harga_lama'];
                            $harga_baru = $data['harga_baru'];
                            $selisih = $data['selisih'];
                            $persen = $harga_lama > 0 ? ($selisih / $harga_lama * 100) : 0;
                            ?>
                            <tr>
                                <td><?php echo $bahan['kode_bahan']; ?></td>
                                <td><?php echo $bahan['nama_bahan']; ?></td>
                                <td><?php echo strtoupper($bahan['satuan']); ?></td>
                                <td><?php echo formatRupiah($harga_lama); ?></td>
                                <td class="fw-bold text-primary"><?php echo formatRupiah($harga_baru); ?></td>
                                <td class="<?php echo $selisih >= 0 ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo formatRupiah(abs($selisih)); ?>
                                    <?php echo $selisih >= 0 ? '↑' : '↓'; ?>
                                </td>
                                <td class="<?php echo $persen >= 0 ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo number_format(abs($persen), 2); ?>%
                                    <?php echo $persen >= 0 ? '↑' : '↓'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="text-center mt-4">
                <button onclick="window.close()" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Tutup
                </button>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>