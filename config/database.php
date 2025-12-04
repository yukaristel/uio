<?php
/**
 * DATABASE CONFIGURATION & HELPER FUNCTIONS
 * Step 2/64 (3.1%)
 */

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'uio');

// Koneksi Database
$conn = null;

function getConnection() {
    global $conn;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            // Set charset
            $conn->set_charset('utf8mb4');
            
            // Check connection
            if ($conn->connect_error) {
                throw new Exception("Koneksi gagal: " . $conn->connect_error);
            }
        } catch (Exception $e) {
            die("Error Database: " . $e->getMessage());
        }
    }
    
    return $conn;
}

// Helper function untuk query
function query($sql, $params = []) {
    $conn = getConnection();
    
    if (empty($params)) {
        $result = $conn->query($sql);
        if (!$result) {
            error_log("Query Error: " . $conn->error . " | SQL: " . $sql);
        }
        return $result;
    }
    
    // Prepared statement
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare Error: " . $conn->error . " | SQL: " . $sql);
        return false;
    }
    
    if (!empty($params)) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_double($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    
    return $result;
}

// Helper function untuk insert/update/delete
function execute($sql, $params = []) {
    $conn = getConnection();
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare Error: " . $conn->error . " | SQL: " . $sql);
        return false;
    }
    
    if (!empty($params)) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_double($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        $stmt->bind_param($types, ...$params);
    }
    
    $success = $stmt->execute();
    $affected_rows = $stmt->affected_rows;
    $insert_id = $stmt->insert_id;
    $stmt->close();
    
    return [
        'success' => $success,
        'affected_rows' => $affected_rows,
        'insert_id' => $insert_id
    ];
}

// Helper function untuk fetch single row
function fetchOne($sql, $params = []) {
    $result = query($sql, $params);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

// Helper function untuk fetch all rows
function fetchAll($sql, $params = []) {
    $result = query($sql, $params);
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

// Helper function untuk escape string
function escape($string) {
    $conn = getConnection();
    return $conn->real_escape_string($string);
}

// Helper function untuk format rupiah (FIXED: Handle NULL)
function formatRupiah($number) {
    // Handle null, empty string, or non-numeric values
    if ($number === null || $number === '' || !is_numeric($number)) {
        $number = 0;
    }
    
    // Convert to float to ensure numeric type
    $number = floatval($number);
    
    return 'Rp ' . number_format($number, 0, ',', '.');
}

// Helper function untuk format tanggal Indonesia
function formatTanggal($date, $format = 'd/m/Y') {
    if (empty($date) || $date === null || $date === '0000-00-00') return '-';
    return date($format, strtotime($date));
}

// Helper function untuk format tanggal dan waktu
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime) || $datetime === null || $datetime === '0000-00-00 00:00:00') return '-';
    return date($format, strtotime($datetime));
}

// Generate nomor transaksi
function generateNoTransaksi($prefix = 'TRX') {
    $tanggal = date('Ymd');
    
    // Ambil nomor urut terakhir hari ini
    $sql = "SELECT MAX(CAST(SUBSTRING(no_transaksi, -3) AS UNSIGNED)) as last_num
            FROM transaksi_penjualan
            WHERE DATE(tanggal_transaksi) = CURDATE()";
    
    $result = fetchOne($sql);
    $last_num = $result ? ($result['last_num'] ?? 0) : 0;
    $new_num = $last_num + 1;
    
    return $prefix . $tanggal . str_pad($new_num, 3, '0', STR_PAD_LEFT);
}

// Generate nomor kas
function generateNoKas() {
    $tanggal = date('Ymd');
    
    $sql = "SELECT MAX(CAST(SUBSTRING(no_transaksi_kas, -3) AS UNSIGNED)) as last_num
            FROM kas_umum
            WHERE DATE(tanggal_transaksi) = CURDATE()";
    
    $result = fetchOne($sql);
    $last_num = $result ? ($result['last_num'] ?? 0) : 0;
    $new_num = $last_num + 1;
    
    return 'KAS-' . $tanggal . '-' . str_pad($new_num, 3, '0', STR_PAD_LEFT);
}

// Generate nomor opname
function generateNoOpname() {
    $tanggal = date('Ymd');
    
    $sql = "SELECT MAX(CAST(SUBSTRING(no_opname, -3) AS UNSIGNED)) as last_num
            FROM stock_opname
            WHERE DATE(tanggal_opname) = CURDATE()";
    
    $result = fetchOne($sql);
    $last_num = $result ? ($result['last_num'] ?? 0) : 0;
    $new_num = $last_num + 1;
    
    return 'OPNAME-' . $tanggal . '-' . str_pad($new_num, 3, '0', STR_PAD_LEFT);
}

// Fungsi konversi satuan
function konversiSatuan($jumlah, $dari_satuan, $ke_satuan) {
    // Jika satuan sama, tidak perlu konversi
    if ($dari_satuan == $ke_satuan) {
        return $jumlah;
    }
    
    // Konversi kg <-> gram
    if ($dari_satuan == 'kg' && $ke_satuan == 'gram') {
        return $jumlah * 1000;
    }
    if ($dari_satuan == 'gram' && $ke_satuan == 'kg') {
        return $jumlah / 1000;
    }
    
    // Konversi liter <-> ml
    if ($dari_satuan == 'liter' && $ke_satuan == 'ml') {
        return $jumlah * 1000;
    }
    if ($dari_satuan == 'ml' && $ke_satuan == 'liter') {
        return $jumlah / 1000;
    }
    
    // Jika tidak bisa dikonversi
    return false;
}

// Validasi satuan bisa dikonversi
function validasiSatuan($satuan_bahan, $satuan_resep) {
    $valid_conversions = [
        'kg' => ['kg', 'gram'],
        'gram' => ['kg', 'gram'],
        'liter' => ['liter', 'ml'],
        'ml' => ['liter', 'ml'],
        'pcs' => ['pcs'],
        'sachet' => ['sachet']
    ];
    
    if (!isset($valid_conversions[$satuan_bahan])) {
        return false;
    }
    
    return in_array($satuan_resep, $valid_conversions[$satuan_bahan]);
}

// Initialize connection
getConnection();
?>