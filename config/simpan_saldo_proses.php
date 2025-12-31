<?php
/**
 * PROSES GENERATE SALDO PER BULAN
 * Dipanggil via AJAX dari generate_saldo.php
 */

// Set header JSON dulu sebelum session_start
header('Content-Type: application/json');

session_start();
require_once 'database.php';

// Disable output buffering untuk mencegah HTML error masuk ke JSON
ob_clean();

// Cek login
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized - Silakan login'
    ]);
    exit;
}

// Validasi input
if (!isset($_POST['tahun']) || !isset($_POST['bulan'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Parameter tahun dan bulan harus diisi'
    ]);
    exit;
}

$tahun = intval($_POST['tahun']);
$bulan = str_pad(intval($_POST['bulan']), 2, '0', STR_PAD_LEFT);

// Validasi tahun dan bulan
if ($tahun < 2000 || $tahun > 2100) {
    echo json_encode([
        'success' => false,
        'message' => 'Tahun tidak valid'
    ]);
    exit;
}

if ($bulan < '01' || $bulan > '12') {
    echo json_encode([
        'success' => false,
        'message' => 'Bulan tidak valid'
    ]);
    exit;
}

// Proses generate
try {
    $result = generateSaldoBulan($tahun, $bulan);
    
    // Clean output buffer sebelum echo JSON
    if (ob_get_length()) ob_clean();
    
    echo json_encode([
        'success' => true,
        'message' => 'Berhasil generate saldo',
        'tahun' => $tahun,
        'bulan' => $bulan,
        'total_akun' => $result['total_akun']
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Clean output buffer
    if (ob_get_length()) ob_clean();
    
    // Log error ke file untuk debugging
    error_log("Generate Saldo Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
}

exit;

/**
 * Generate Saldo untuk 1 Bulan
 */
function generateSaldoBulan($tahun, $bulan) {
    $conn = getConnection();
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // 1. Hapus saldo bulan ini (jika ada)
        $sql_delete = "DELETE FROM saldo WHERE tahun = ? AND bulan = ?";
        $delete_result = execute($sql_delete, [$tahun, $bulan]);
        
        if (!$delete_result['success']) {
            throw new Exception('Gagal hapus saldo lama: ' . $delete_result['error']);
        }
        
        // 2. Hitung tanggal awal dan akhir bulan
        $tgl_awal = "$tahun-$bulan-01";
        $tgl_akhir = date('Y-m-t', strtotime($tgl_awal)); // Last day of month
        
        // 3. Get semua akun yang ada transaksi di bulan ini
        $sql_akun = "
            SELECT DISTINCT kode_akun 
            FROM (
                SELECT rekening_debet as kode_akun FROM transaksi 
                WHERE tgl_transaksi >= ? AND tgl_transaksi <= ?
                UNION
                SELECT rekening_kredit as kode_akun FROM transaksi 
                WHERE tgl_transaksi >= ? AND tgl_transaksi <= ?
            ) t
            ORDER BY kode_akun
        ";
        
        $akun_list = fetchAll($sql_akun, [$tgl_awal, $tgl_akhir, $tgl_awal, $tgl_akhir]);
        
        if (!$akun_list || count($akun_list) == 0) {
            // Tidak ada transaksi di bulan ini
            $conn->commit();
            return [
                'total_akun' => 0
            ];
        }
        
        $total_akun = 0;
        
        // 4. Loop setiap akun dan hitung saldo
        foreach ($akun_list as $akun) {
            $kode_akun = $akun['kode_akun'];
            
            // Hitung total debet
            $sql_debet = "
                SELECT COALESCE(SUM(jumlah), 0) as total
                FROM transaksi
                WHERE rekening_debet = ?
                AND tgl_transaksi >= ? AND tgl_transaksi <= ?
            ";
            $debet = fetchOne($sql_debet, [$kode_akun, $tgl_awal, $tgl_akhir]);
            $total_debet = $debet ? $debet['total'] : 0;
            
            // Hitung total kredit
            $sql_kredit = "
                SELECT COALESCE(SUM(jumlah), 0) as total
                FROM transaksi
                WHERE rekening_kredit = ?
                AND tgl_transaksi >= ? AND tgl_transaksi <= ?
            ";
            $kredit = fetchOne($sql_kredit, [$kode_akun, $tgl_awal, $tgl_akhir]);
            $total_kredit = $kredit ? $kredit['total'] : 0;
            
            // Insert ke tabel saldo
            $id_saldo = str_replace('.', '', $kode_akun) . $tahun . $bulan;
            
            $sql_insert = "
                INSERT INTO saldo (id, kode_akun, tahun, bulan, debet, kredit)
                VALUES (?, ?, ?, ?, ?, ?)
            ";
            
            $insert_result = execute($sql_insert, [
                $id_saldo,
                $kode_akun,
                $tahun,
                $bulan,
                $total_debet,
                $total_kredit
            ]);
            
            if (!$insert_result['success']) {
                throw new Exception("Gagal insert saldo untuk akun {$kode_akun}: " . $insert_result['error']);
            }
            
            $total_akun++;
        }
        
        // Commit transaction
        $conn->commit();
        
        return [
            'total_akun' => $total_akun
        ];
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        throw $e;
    }
}

?>