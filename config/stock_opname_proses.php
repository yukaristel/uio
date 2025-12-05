<?php
/**
 * PROSES STOCK OPNAME + APPROVAL (Updated for Weighted Average)
 * Step 23/64 (35.9%)
 * 
 * PERUBAHAN:
 * - createOpname() menggunakan harga weighted average terkini
 * - approveOpname() recalculate nilai_selisih dengan harga terkini saat approval
 * - Movement menggunakan harga weighted average saat approval
 */

session_start();
require_once 'database.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Anda harus login!';
    header('Location: ../index.php?page=login');
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'create':
        createOpname();
        break;
    case 'approve':
        approveOpname();
        break;
    case 'delete':
        deleteOpname();
        break;
    default:
        header('Location: ../index.php?page=list_opname');
        exit;
}

/**
 * CREATE - Buat Stock Opname (Draft)
 * UPDATED: Menggunakan weighted average terkini
 */
function createOpname() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../index.php?page=tambah_opname');
        exit;
    }
    
    $bahan_id = intval($_POST['bahan_id']);
    $stok_fisik = floatval($_POST['stok_fisik']);
    $jenis_selisih = $_POST['jenis_selisih'];
    $keterangan = trim($_POST['keterangan']);
    $tanggal_opname = $_POST['tanggal_opname'];
    
    // Validasi
    if ($bahan_id == 0 || $stok_fisik < 0 || empty($tanggal_opname)) {
        $_SESSION['error'] = 'Semua field harus diisi dengan benar!';
        header('Location: ../index.php?page=tambah_opname');
        exit;
    }
    
    // Ambil data bahan
    $bahan = fetchOne("SELECT * FROM bahan_baku WHERE id = ?", [$bahan_id]);
    if (!$bahan) {
        $_SESSION['error'] = 'Bahan tidak ditemukan!';
        header('Location: ../index.php?page=tambah_opname');
        exit;
    }
    
    // Hitung selisih dengan weighted average terkini
    $stok_sistem = $bahan['stok_tersedia'];
    $selisih = $stok_fisik - $stok_sistem;
    
    // UPDATED: Gunakan harga weighted average terkini
    $harga_per_satuan = $bahan['harga_beli_per_satuan'];
    $nilai_selisih = abs($selisih) * $harga_per_satuan;
    
    // Generate nomor opname
    $no_opname = generateNoOpname();
    
    // Insert stock opname (status: draft)
    $sql = "INSERT INTO stock_opname 
        (no_opname, tanggal_opname, bahan_id, stok_sistem, stok_fisik, selisih, 
        satuan, harga_per_satuan, nilai_selisih, jenis_selisih, keterangan, status, user_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?)";
    
    $result = execute($sql, [
        $no_opname, $tanggal_opname, $bahan_id, $stok_sistem, $stok_fisik, $selisih,
        $bahan['satuan'], $harga_per_satuan, $nilai_selisih,
        $jenis_selisih, $keterangan, $_SESSION['user_id']
    ]);
    
    if ($result['success']) {
        if ($selisih < 0) {
            $_SESSION['warning'] = 'Stock opname berhasil dibuat! Status: DRAFT. Selisih: ' . 
                                   number_format($selisih, 2) . ' ' . $bahan['satuan'] . 
                                   ' (Kurang ' . formatRupiah(abs($nilai_selisih)) . ')';
        } else {
            $_SESSION['success'] = 'Stock opname berhasil dibuat! Status: DRAFT. Selisih: +' . 
                                   number_format($selisih, 2) . ' ' . $bahan['satuan'];
        }
        header('Location: ../index.php?page=list_opname');
    } else {
        $_SESSION['error'] = 'Gagal membuat stock opname!';
        header('Location: ../index.php?page=tambah_opname');
    }
    exit;
}

/**
 * APPROVE - Approve Stock Opname (Admin Only)
 * UPDATED: Recalculate dengan weighted average terkini saat approval
 */
function approveOpname() {
    // Cek akses admin
    if ($_SESSION['role'] != 'admin') {
        $_SESSION['error'] = 'Hanya admin yang bisa approve stock opname!';
        header('Location: ../index.php?page=list_opname');
        exit;
    }
    
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id == 0) {
        $_SESSION['error'] = 'ID opname tidak valid!';
        header('Location: ../index.php?page=list_opname');
        exit;
    }
    
    // Ambil data opname dengan JOIN ke bahan_baku untuk mendapat harga terkini
    $opname = fetchOne("SELECT so.*, 
                               b.nama_bahan, 
                               b.satuan, 
                               b.harga_beli_per_satuan
                        FROM stock_opname so 
                        JOIN bahan_baku b ON so.bahan_id = b.id 
                        WHERE so.id = ?", [$id]);
    
    if (!$opname) {
        $_SESSION['error'] = 'Stock opname tidak ditemukan!';
        header('Location: ../index.php?page=list_opname');
        exit;
    }
    
    if ($opname['status'] == 'approved') {
        $_SESSION['warning'] = 'Stock opname sudah di-approve sebelumnya!';
        header('Location: ../index.php?page=list_opname');
        exit;
    }
    
    // START TRANSACTION
    $conn = getConnection();
    try {
        $conn->begin_transaction();
        
        // CRITICAL: Gunakan harga weighted average TERKINI saat approval
        $harga_terkini = $opname['harga_beli_per_satuan'];
        $nilai_selisih_baru = abs($opname['selisih']) * $harga_terkini;
        
        // 1. Update status opname dengan harga dan nilai terkini
        $sql_update_opname = "UPDATE stock_opname 
                              SET status = 'approved', 
                                  harga_per_satuan = ?,
                                  nilai_selisih = ?,
                                  approved_by = ?, 
                                  approved_at = NOW() 
                              WHERE id = ?";
        $result = execute($sql_update_opname, [
            $harga_terkini, 
            $nilai_selisih_baru,
            $_SESSION['user_id'], 
            $id
        ]);
        
        if (!$result['success']) {
            throw new Exception('Gagal update status opname!');
        }
        
        // 2. Update stok bahan ke stok fisik
        // Note: Harga weighted average TIDAK berubah, hanya stok yang berubah
        $sql_update_bahan = "UPDATE bahan_baku SET stok_tersedia = ? WHERE id = ?";
        $result_bahan = execute($sql_update_bahan, [$opname['stok_fisik'], $opname['bahan_id']]);
        
        if (!$result_bahan['success']) {
            throw new Exception('Gagal update stok bahan!');
        }
        
        // 3. Catat stock movement dengan harga weighted average terkini
        $jenis_movement = ($opname['selisih'] < 0) ? $opname['jenis_selisih'] : 'opname';
        $jumlah_movement = abs($opname['selisih']);
        
        $sql_movement = "INSERT INTO stock_movement 
            (bahan_id, jenis_pergerakan, jumlah, satuan, harga_per_satuan, total_nilai, 
            stok_sebelum, stok_sesudah, referensi_type, referensi_id, keterangan, user_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'opname', ?, ?, ?)";
        
        $keterangan_movement = "Stock Opname - {$opname['no_opname']} " . 
                               ($opname['selisih'] < 0 ? '(Kurang)' : '(Lebih)');
        
        $result_movement = execute($sql_movement, [
            $opname['bahan_id'], 
            $jenis_movement, 
            $jumlah_movement, 
            $opname['satuan'],
            $harga_terkini, // UPDATED: Gunakan harga terkini
            $nilai_selisih_baru, // UPDATED: Gunakan nilai terkini
            $opname['stok_sistem'], 
            $opname['stok_fisik'],
            $id, 
            $keterangan_movement, 
            $_SESSION['user_id']
        ]);
        
        if (!$result_movement['success']) {
            throw new Exception('Gagal catat stock movement!');
        }
        
        // COMMIT
        $conn->commit();
        
        // Pesan sukses dengan info terbaru
        if ($opname['selisih'] < 0) {
            $_SESSION['warning'] = 'Stock opname berhasil di-approve! Kerugian: ' . 
                                   formatRupiah($nilai_selisih_baru) . 
                                   ' (' . number_format(abs($opname['selisih']), 2) . ' ' . $opname['satuan'] . ')';
        } else {
            $_SESSION['success'] = 'Stock opname berhasil di-approve! Stok ' . $opname['nama_bahan'] . 
                                   ' diupdate ke ' . $opname['stok_fisik'] . ' ' . $opname['satuan'];
        }
        
        header('Location: ../index.php?page=list_opname');
        
    } catch (Exception $e) {
        // ROLLBACK
        $conn->rollback();
        $_SESSION['error'] = 'Gagal approve opname: ' . $e->getMessage();
        header('Location: ../index.php?page=approval_opname&id=' . $id);
    }
    
    exit;
}

/**
 * DELETE - Hapus Stock Opname (Hanya Draft)
 */
function deleteOpname() {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id == 0) {
        $_SESSION['error'] = 'ID opname tidak valid!';
        header('Location: ../index.php?page=list_opname');
        exit;
    }
    
    // Cek status
    $opname = fetchOne("SELECT status FROM stock_opname WHERE id = ?", [$id]);
    if (!$opname) {
        $_SESSION['error'] = 'Stock opname tidak ditemukan!';
        header('Location: ../index.php?page=list_opname');
        exit;
    }
    
    if ($opname['status'] == 'approved') {
        $_SESSION['error'] = 'Stock opname yang sudah di-approve tidak bisa dihapus!';
        header('Location: ../index.php?page=list_opname');
        exit;
    }
    
    $sql = "DELETE FROM stock_opname WHERE id = ?";
    $result = execute($sql, [$id]);
    
    if ($result['success']) {
        $_SESSION['success'] = 'Stock opname berhasil dihapus!';
    } else {
        $_SESSION['error'] = 'Gagal menghapus stock opname!';
    }
    
    header('Location: ../index.php?page=list_opname');
    exit;
}

/**
 * Helper: Generate Nomor Opname
 */
function generateNoOpname() {
    $today = date('Ymd');
    $last = fetchOne("SELECT no_opname FROM stock_opname 
                      WHERE no_opname LIKE 'OPN-$today-%' 
                      ORDER BY no_opname DESC LIMIT 1");
    
    if ($last) {
        $last_no = intval(substr($last['no_opname'], -3));
        $new_no = $last_no + 1;
    } else {
        $new_no = 1;
    }
    
    return 'OPN-' . $today . '-' . str_pad($new_no, 3, '0', STR_PAD_LEFT);
}
?>