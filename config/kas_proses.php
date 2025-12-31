<?php
/**
 * PROSES TRANSAKSI
 * Menggunakan Double Entry System
 * Modified: Tanpa validasi saldo + Fitur jurnal pembalik
 */

session_start();
require_once 'database.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Anda harus login terlebih dahulu!';
    header('Location: ../index.php');
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'create':
        createTransaksi();
        break;
    case 'delete':
        deleteTransaksi();
        break;
    case 'reverse':
        reverseTransaksi();
        break;
    default:
        header('Location: ../index.php?page=list_transaksi');
        exit;
}

/**
 * CREATE - Tambah Transaksi
 */
function createTransaksi() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../index.php?page=tambah_transaksi');
        exit;
    }
    
    $jenis_transaksi = $_POST['jenis_transaksi'];
    $rekening_debet = $_POST['rekening_debet'];
    $rekening_kredit = $_POST['rekening_kredit'];
    $jumlah = floatval($_POST['jumlah']);
    $keterangan = trim($_POST['keterangan_transaksi']);
    $tgl_transaksi = $_POST['tgl_transaksi'];
    
    // Validasi dasar
    if (empty($rekening_debet) || empty($rekening_kredit) || $jumlah <= 0) {
        $_SESSION['error'] = 'Semua field harus diisi dengan benar!';
        header('Location: ../index.php?page=tambah_transaksi');
        exit;
    }
    
    // Validasi tidak boleh sama
    if ($rekening_debet === $rekening_kredit) {
        $_SESSION['error'] = 'Rekening debet dan kredit tidak boleh sama!';
        header('Location: ../index.php?page=tambah_transaksi');
        exit;
    }
    
    // VALIDASI SALDO DIHAPUS - Biarkan transaksi jalan walaupun minus
    // Sesuai konsep akuntansi, saldo bisa minus (overdraft, dll)
    
    // Auto generate keterangan jika kosong
    if (empty($keterangan)) {
        $keterangan = generateKeteranganOtomatis($rekening_debet, $rekening_kredit, $jenis_transaksi);
    }
    
    // START TRANSACTION
    $conn = getConnection();
    try {
        $conn->begin_transaction();
        
        // Insert transaksi
        $sql = "INSERT INTO transaksi 
                (tgl_transaksi, rekening_debet, rekening_kredit, keterangan_transaksi, jumlah, id_user) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $result = execute($sql, [
            $tgl_transaksi,
            $rekening_debet,
            $rekening_kredit,
            $keterangan,
            $jumlah,
            $_SESSION['user_id']
        ]);
        
        if (!$result['success']) {
            throw new Exception('Gagal insert transaksi!');
        }
        
        $transaksi_id = $result['insert_id'];
        
        // Jika pembelian inventaris, insert ke aset_tetap
        $tipe_pengeluaran = isset($_POST['tipe_pengeluaran']) ? $_POST['tipe_pengeluaran'] : '';
        
        if ($tipe_pengeluaran === 'inventaris') {
            $nama_barang = trim($_POST['nama_barang']);
            $unit = intval($_POST['unit']);
            $harsat = floatval($_POST['harsat']);
            $umur_ekonomis = isset($_POST['umur_ekonomis']) ? intval($_POST['umur_ekonomis']) : null;
            $jenis = $_POST['jenis'];
            
            $sql_aset = "INSERT INTO aset_tetap 
                        (nama_barang, tgl_beli, unit, harsat, umur_ekonomis, jenis, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'Baik')";
            
            $result_aset = execute($sql_aset, [
                $nama_barang,
                $tgl_transaksi,
                $unit,
                $harsat,
                $umur_ekonomis,
                $jenis
            ]);
            
            if (!$result_aset['success']) {
                throw new Exception('Gagal insert aset tetap!');
            }
        }
        
        // COMMIT
        $conn->commit();
        
        $pesan_sukses = 'Transaksi berhasil dicatat!';
        if ($tipe_pengeluaran === 'inventaris') {
            $pesan_sukses .= ' Inventaris juga telah ditambahkan.';
        }
        
        // Cek apakah ada saldo yang minus
        $saldo_minus = cekSaldoMinus($rekening_kredit);
        if ($saldo_minus < 0 && isKasBank($rekening_kredit)) {
            $nama_akun = getNamaRekening($rekening_kredit);
            $pesan_sukses .= '<br><span class="text-warning"><i class="bi bi-exclamation-triangle"></i> Perhatian: Saldo ' . 
                           $nama_akun . ' sekarang: ' . formatRupiah($saldo_minus) . ' (MINUS)</span>';
        }
        
        $_SESSION['success'] = $pesan_sukses;
        header('Location: ../index.php?page=list_transaksi');
        
    } catch (Exception $e) {
        // ROLLBACK
        $conn->rollback();
        $_SESSION['error'] = 'Gagal catat transaksi: ' . $e->getMessage();
        header('Location: ../index.php?page=tambah_transaksi');
    }
    
    exit;
}

/**
 * DELETE - Hapus Transaksi
 */
function deleteTransaksi() {
    if (!isset($_GET['id'])) {
        $_SESSION['error'] = 'ID transaksi tidak valid!';
        header('Location: ../index.php?page=list_transaksi');
        exit;
    }
    
    $id = intval($_GET['id']);
    
    // Cek apakah transaksi ada
    $transaksi = fetchOne("SELECT * FROM transaksi WHERE id = ?", [$id]);
    
    if (!$transaksi) {
        $_SESSION['error'] = 'Transaksi tidak ditemukan!';
        header('Location: ../index.php?page=list_transaksi');
        exit;
    }
    
    // START TRANSACTION
    $conn = getConnection();
    try {
        $conn->begin_transaction();
        
        // Delete transaksi
        $sql = "DELETE FROM transaksi WHERE id = ?";
        $result = execute($sql, [$id]);
        
        if (!$result['success']) {
            throw new Exception('Gagal hapus transaksi!');
        }
        
        // COMMIT
        $conn->commit();
        
        $_SESSION['success'] = 'Transaksi berhasil dihapus!';
        header('Location: ../index.php?page=list_transaksi');
        
    } catch (Exception $e) {
        // ROLLBACK
        $conn->rollback();
        $_SESSION['error'] = 'Gagal hapus transaksi: ' . $e->getMessage();
        header('Location: ../index.php?page=list_transaksi');
    }
    
    exit;
}

/**
 * REVERSE - Jurnal Pembalik (untuk koreksi)
 * Membuat jurnal kebalikan dari transaksi yang salah
 */
function reverseTransaksi() {
    if (!isset($_GET['id'])) {
        $_SESSION['error'] = 'ID transaksi tidak valid!';
        header('Location: ../index.php?page=list_transaksi');
        exit;
    }
    
    $id = intval($_GET['id']);
    
    // Ambil data transaksi asli
    $transaksi = fetchOne("SELECT * FROM transaksi WHERE id = ?", [$id]);
    
    if (!$transaksi) {
        $_SESSION['error'] = 'Transaksi tidak ditemukan!';
        header('Location: ../index.php?page=list_transaksi');
        exit;
    }
    
    // START TRANSACTION
    $conn = getConnection();
    try {
        $conn->begin_transaction();
        
        // Insert jurnal pembalik (DEBET <-> KREDIT dibalik)
        $keterangan_pembalik = "[PEMBALIK] " . $transaksi['keterangan_transaksi'] . 
                              " (Ref ID: " . $transaksi['id'] . ")";
        
        $sql = "INSERT INTO transaksi 
                (tgl_transaksi, rekening_debet, rekening_kredit, keterangan_transaksi, jumlah, id_user) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $result = execute($sql, [
            date('Y-m-d'), // Tanggal hari ini
            $transaksi['rekening_kredit'], // BALIK: kredit jadi debet
            $transaksi['rekening_debet'],   // BALIK: debet jadi kredit
            $keterangan_pembalik,
            $transaksi['jumlah'],
            $_SESSION['user_id']
        ]);
        
        if (!$result['success']) {
            throw new Exception('Gagal buat jurnal pembalik!');
        }
        
        $transaksi_pembalik_id = $result['insert_id'];
        
        // Update transaksi asli: tambah flag sudah dibalik
        $sql_update = "UPDATE transaksi 
                      SET keterangan_transaksi = CONCAT(keterangan_transaksi, ' [SUDAH DIBALIK - Ref: ', ?, ']')
                      WHERE id = ?";
        
        execute($sql_update, [$transaksi_pembalik_id, $id]);
        
        // COMMIT
        $conn->commit();
        
        $_SESSION['success'] = 'Jurnal pembalik berhasil dibuat! Transaksi ID: ' . $transaksi_pembalik_id;
        header('Location: ../index.php?page=list_transaksi');
        
    } catch (Exception $e) {
        // ROLLBACK
        $conn->rollback();
        $_SESSION['error'] = 'Gagal buat jurnal pembalik: ' . $e->getMessage();
        header('Location: ../index.php?page=list_transaksi');
    }
    
    exit;
}

/**
 * HELPER: Cek apakah rekening adalah Kas/Bank
 */
function isKasBank($kode_akun) {
    $rekening = fetchOne("SELECT * FROM chart_of_accounts WHERE kode_akun = ?", [$kode_akun]);
    
    if (!$rekening) return false;
    
    // Kas dan Bank = lev1:1, lev2:1
    return ($rekening['lev1'] == 1 && $rekening['lev2'] == 1);
}

/**
 * HELPER: Get Saldo Rekening
 */
function getSaldoRekening($kode_akun) {
    $rekening = fetchOne("SELECT jenis_mutasi FROM chart_of_accounts WHERE kode_akun = ?", [$kode_akun]);
    
    if (!$rekening) return 0;
    
    $jenis_mutasi = $rekening['jenis_mutasi'];
    
    // Hitung saldo
    $result = fetchOne("
        SELECT 
            COALESCE(SUM(
                CASE 
                    WHEN rekening_debet = ? THEN 
                        CASE WHEN ? = 'Debet' THEN jumlah ELSE -jumlah END
                    WHEN rekening_kredit = ? THEN 
                        CASE WHEN ? = 'Kredit' THEN jumlah ELSE -jumlah END
                    ELSE 0
                END
            ), 0) as saldo
        FROM transaksi
    ", [$kode_akun, $jenis_mutasi, $kode_akun, $jenis_mutasi]);
    
    return $result['saldo'];
}

/**
 * HELPER: Cek Saldo Minus (untuk warning)
 */
function cekSaldoMinus($kode_akun) {
    return getSaldoRekening($kode_akun);
}

/**
 * HELPER: Get Nama Rekening
 */
function getNamaRekening($kode_akun) {
    $rekening = fetchOne("SELECT nama_akun FROM chart_of_accounts WHERE kode_akun = ?", [$kode_akun]);
    return $rekening ? $rekening['nama_akun'] : 'Unknown';
}

/**
 * HELPER: Generate Keterangan Otomatis
 */
function generateKeteranganOtomatis($rekening_debet, $rekening_kredit, $jenis) {
    $nama_debet = getNamaRekening($rekening_debet);
    $nama_kredit = getNamaRekening($rekening_kredit);
    
    if ($jenis === 'pemasukan') {
        return "Terima uang dari {$nama_kredit}";
    } elseif ($jenis === 'pengeluaran') {
        return "Bayar {$nama_debet}";
    } elseif ($jenis === 'pemindahan') {
        return "Transfer dari {$nama_kredit} ke {$nama_debet}";
    }
    
    return "Transaksi {$nama_debet} - {$nama_kredit}";
}
?>