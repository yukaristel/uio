<?php
/**
 * PROSES GENERATE / REGENERATE KAS
 * Handle full regenerate dan sync transaksi baru
 */

session_start();
require_once 'database.php';

// Cek login dan akses admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    $_SESSION['error'] = 'Akses ditolak! Hanya admin yang dapat mengakses fitur ini.';
    header('Location: ../index.php');
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'full_regenerate':
        fullRegenerate();
        break;
    case 'sync_only':
        syncOnly();
        break;
    default:
        header('Location: ../index.php?page=generate_kas');
        exit;
}

/**
 * FULL REGENERATE - Reset dan buat ulang semua data kas
 */
function fullRegenerate() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../index.php?page=generate_kas');
        exit;
    }
    
    $saldo_awal = floatval($_POST['saldo_awal']);
    
    if ($saldo_awal < 0) {
        $_SESSION['error'] = 'Saldo awal tidak valid!';
        header('Location: ../index.php?page=generate_kas');
        exit;
    }
    
    $conn = getConnection();
    
    try {
        $conn->begin_transaction();
        
        // STEP 1: Hapus semua data kas
        execute("DELETE FROM kas_umum");
        execute("DELETE FROM saldo_kas");
        execute("ALTER TABLE kas_umum AUTO_INCREMENT = 1");
        execute("ALTER TABLE saldo_kas AUTO_INCREMENT = 1");
        
        $saldo_running = $saldo_awal;
        $counter = 1;
        
        // STEP 2: Insert modal awal
        $no_kas = 'KAS-' . date('Ymd') . '-' . str_pad($counter++, 3, '0', STR_PAD_LEFT);
        execute("INSERT INTO kas_umum 
            (no_transaksi_kas, tanggal_transaksi, jenis_transaksi, kategori, nominal, 
            saldo_sebelum, saldo_sesudah, referensi_type, keterangan, user_id) 
            VALUES (?, ?, 'masuk', 'investasi', ?, 0, ?, 'manual', 'Modal awal usaha (regenerate)', ?)",
            [$no_kas, date('Y-m-d H:i:s'), $saldo_awal, $saldo_running, $_SESSION['user_id']]
        );
        
        // STEP 3: Generate kas dari PENJUALAN
        $transaksi_penjualan = fetchAll("
            SELECT * FROM transaksi_penjualan 
            ORDER BY tanggal_transaksi ASC, id ASC
        ");
        
        $count_penjualan = 0;
        foreach ($transaksi_penjualan as $trx) {
            $saldo_sebelum = $saldo_running;
            $saldo_running += $trx['total_harga'];
            
            $no_kas = 'KAS-' . date('Ymd', strtotime($trx['tanggal_transaksi'])) . '-' . str_pad($counter++, 3, '0', STR_PAD_LEFT);
            
            execute("INSERT INTO kas_umum 
                (no_transaksi_kas, tanggal_transaksi, jenis_transaksi, kategori, nominal, 
                saldo_sebelum, saldo_sesudah, referensi_type, referensi_id, keterangan, user_id) 
                VALUES (?, ?, 'masuk', 'penjualan', ?, ?, ?, 'penjualan', ?, ?, ?)",
                [
                    $no_kas, 
                    $trx['tanggal_transaksi'], 
                    $trx['total_harga'], 
                    $saldo_sebelum, 
                    $saldo_running,
                    $trx['id'],
                    'Penjualan ' . $trx['no_transaksi'],
                    $trx['user_id']
                ]
            );
            
            $count_penjualan++;
        }
        
        // STEP 4: Generate kas dari PEMBELIAN BAHAN
        $pembelian_bahan = fetchAll("
            SELECT * FROM pembelian_bahan 
            ORDER BY tanggal_beli ASC, id ASC
        ");
        
        $count_pembelian = 0;
        foreach ($pembelian_bahan as $beli) {
            $saldo_sebelum = $saldo_running;
            $saldo_running -= $beli['total_harga'];
            
            $no_kas = 'KAS-' . date('Ymd', strtotime($beli['tanggal_beli'])) . '-' . str_pad($counter++, 3, '0', STR_PAD_LEFT);
            
            $tanggal_transaksi = $beli['tanggal_beli'] . ' ' . date('H:i:s', strtotime($beli['created_at']));
            
            execute("INSERT INTO kas_umum 
                (no_transaksi_kas, tanggal_transaksi, jenis_transaksi, kategori, nominal, 
                saldo_sebelum, saldo_sesudah, referensi_type, referensi_id, keterangan, user_id) 
                VALUES (?, ?, 'keluar', 'pembelian_bahan', ?, ?, ?, 'pembelian', ?, ?, ?)",
                [
                    $no_kas, 
                    $tanggal_transaksi, 
                    $beli['total_harga'], 
                    $saldo_sebelum, 
                    $saldo_running,
                    $beli['id'],
                    'Pembelian bahan dari ' . ($beli['supplier'] ?: 'Supplier'),
                    $beli['user_id']
                ]
            );
            
            $count_pembelian++;
        }
        
        // STEP 5: Generate saldo_kas harian
        $kas_harian = fetchAll("
            SELECT 
                DATE(tanggal_transaksi) as tanggal,
                MIN(saldo_sebelum) as saldo_awal,
                COALESCE(SUM(CASE WHEN jenis_transaksi = 'masuk' THEN nominal ELSE 0 END), 0) as total_masuk,
                COALESCE(SUM(CASE WHEN jenis_transaksi = 'keluar' THEN nominal ELSE 0 END), 0) as total_keluar,
                MAX(saldo_sesudah) as saldo_akhir
            FROM kas_umum
            GROUP BY DATE(tanggal_transaksi)
            ORDER BY DATE(tanggal_transaksi) ASC
        ");
        
        foreach ($kas_harian as $hari) {
            execute("INSERT INTO saldo_kas (tanggal, saldo_awal, total_masuk, total_keluar, saldo_akhir) 
                VALUES (?, ?, ?, ?, ?)",
                [
                    $hari['tanggal'],
                    $hari['saldo_awal'],
                    $hari['total_masuk'],
                    $hari['total_keluar'],
                    $hari['saldo_akhir']
                ]
            );
        }
        
        // COMMIT
        $conn->commit();
        
        $_SESSION['success'] = "✅ Regenerate kas berhasil!\n\n" .
                               "📊 Statistik:\n" .
                               "- Modal awal: " . formatRupiah($saldo_awal) . "\n" .
                               "- Penjualan: {$count_penjualan} transaksi\n" .
                               "- Pembelian: {$count_pembelian} transaksi\n" .
                               "- Total entry kas: " . ($count_penjualan + $count_pembelian + 1) . "\n" .
                               "- Saldo akhir: " . formatRupiah($saldo_running) . "\n" .
                               "- Hari dicatat: " . count($kas_harian);
        
        header('Location: ../index.php?page=dashboard_kas');
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = 'Gagal regenerate kas: ' . $e->getMessage();
        header('Location: ../index.php?page=generate_kas');
    }
    
    exit;
}

/**
 * SYNC ONLY - Tambahkan kas untuk transaksi yang belum tercatat
 */
function syncOnly() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../index.php?page=generate_kas');
        exit;
    }
    
    $conn = getConnection();
    
    try {
        $conn->begin_transaction();
        
        // Get saldo terakhir
        $saldo_kas = fetchOne("SELECT saldo_sesudah FROM kas_umum ORDER BY created_at DESC, id DESC LIMIT 1");
        $saldo_running = $saldo_kas ? $saldo_kas['saldo_sesudah'] : 0;
        
        // Get counter terakhir
        $last_kas = fetchOne("SELECT no_transaksi_kas FROM kas_umum ORDER BY id DESC LIMIT 1");
        $counter = 1;
        if ($last_kas) {
            $parts = explode('-', $last_kas['no_transaksi_kas']);
            $counter = intval(end($parts)) + 1;
        }
        
        // STEP 1: Cari penjualan yang belum ada di kas
        $penjualan_belum = fetchAll("
            SELECT tp.* 
            FROM transaksi_penjualan tp
            LEFT JOIN kas_umum k ON k.referensi_type = 'penjualan' AND k.referensi_id = tp.id
            WHERE k.id IS NULL
            ORDER BY tp.tanggal_transaksi ASC
        ");
        
        $count_penjualan = 0;
        foreach ($penjualan_belum as $trx) {
            $saldo_sebelum = $saldo_running;
            $saldo_running += $trx['total_harga'];
            
            $no_kas = 'KAS-' . date('Ymd', strtotime($trx['tanggal_transaksi'])) . '-' . str_pad($counter++, 3, '0', STR_PAD_LEFT);
            
            execute("INSERT INTO kas_umum 
                (no_transaksi_kas, tanggal_transaksi, jenis_transaksi, kategori, nominal, 
                saldo_sebelum, saldo_sesudah, referensi_type, referensi_id, keterangan, user_id) 
                VALUES (?, ?, 'masuk', 'penjualan', ?, ?, ?, 'penjualan', ?, ?, ?)",
                [
                    $no_kas, 
                    $trx['tanggal_transaksi'], 
                    $trx['total_harga'], 
                    $saldo_sebelum, 
                    $saldo_running,
                    $trx['id'],
                    'Penjualan ' . $trx['no_transaksi'] . ' (sync)',
                    $trx['user_id']
                ]
            );
            
            $count_penjualan++;
        }
        
        // STEP 2: Cari pembelian yang belum ada di kas
        $pembelian_belum = fetchAll("
            SELECT pb.* 
            FROM pembelian_bahan pb
            LEFT JOIN kas_umum k ON k.referensi_type = 'pembelian' AND k.referensi_id = pb.id
            WHERE k.id IS NULL
            ORDER BY pb.tanggal_beli ASC
        ");
        
        $count_pembelian = 0;
        foreach ($pembelian_belum as $beli) {
            $saldo_sebelum = $saldo_running;
            $saldo_running -= $beli['total_harga'];
            
            $no_kas = 'KAS-' . date('Ymd', strtotime($beli['tanggal_beli'])) . '-' . str_pad($counter++, 3, '0', STR_PAD_LEFT);
            
            $tanggal_transaksi = $beli['tanggal_beli'] . ' ' . date('H:i:s', strtotime($beli['created_at']));
            
            execute("INSERT INTO kas_umum 
                (no_transaksi_kas, tanggal_transaksi, jenis_transaksi, kategori, nominal, 
                saldo_sebelum, saldo_sesudah, referensi_type, referensi_id, keterangan, user_id) 
                VALUES (?, ?, 'keluar', 'pembelian_bahan', ?, ?, ?, 'pembelian', ?, ?, ?)",
                [
                    $no_kas, 
                    $tanggal_transaksi, 
                    $beli['total_harga'], 
                    $saldo_sebelum, 
                    $saldo_running,
                    $beli['id'],
                    'Pembelian bahan dari ' . ($beli['supplier'] ?: 'Supplier') . ' (sync)',
                    $beli['user_id']
                ]
            );
            
            $count_pembelian++;
        }
        
        // STEP 3: Update saldo_kas harian untuk tanggal yang terpengaruh
        if ($count_penjualan > 0 || $count_pembelian > 0) {
            // Get unique dates yang ada transaksi baru
            $affected_dates = fetchAll("
                SELECT DISTINCT DATE(tanggal_transaksi) as tanggal
                FROM kas_umum
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                ORDER BY tanggal
            ");
            
            foreach ($affected_dates as $date) {
                $hari_data = fetchOne("
                    SELECT 
                        DATE(tanggal_transaksi) as tanggal,
                        MIN(saldo_sebelum) as saldo_awal,
                        COALESCE(SUM(CASE WHEN jenis_transaksi = 'masuk' THEN nominal ELSE 0 END), 0) as total_masuk,
                        COALESCE(SUM(CASE WHEN jenis_transaksi = 'keluar' THEN nominal ELSE 0 END), 0) as total_keluar,
                        MAX(saldo_sesudah) as saldo_akhir
                    FROM kas_umum
                    WHERE DATE(tanggal_transaksi) = ?
                    GROUP BY DATE(tanggal_transaksi)
                ", [$date['tanggal']]);
                
                // Check if already exists
                $exists = fetchOne("SELECT id FROM saldo_kas WHERE tanggal = ?", [$date['tanggal']]);
                
                if ($exists) {
                    // Update
                    execute("UPDATE saldo_kas 
                        SET saldo_awal = ?, total_masuk = ?, total_keluar = ?, saldo_akhir = ?
                        WHERE tanggal = ?",
                        [
                            $hari_data['saldo_awal'],
                            $hari_data['total_masuk'],
                            $hari_data['total_keluar'],
                            $hari_data['saldo_akhir'],
                            $date['tanggal']
                        ]
                    );
                } else {
                    // Insert
                    execute("INSERT INTO saldo_kas (tanggal, saldo_awal, total_masuk, total_keluar, saldo_akhir) 
                        VALUES (?, ?, ?, ?, ?)",
                        [
                            $hari_data['tanggal'],
                            $hari_data['saldo_awal'],
                            $hari_data['total_masuk'],
                            $hari_data['total_keluar'],
                            $hari_data['saldo_akhir']
                        ]
                    );
                }
            }
        }
        
        // COMMIT
        $conn->commit();
        
        if ($count_penjualan == 0 && $count_pembelian == 0) {
            $_SESSION['info'] = '✅ Sinkronisasi selesai. Tidak ada transaksi baru yang perlu ditambahkan.';
        } else {
            $_SESSION['success'] = "✅ Sinkronisasi kas berhasil!\n\n" .
                                   "📊 Statistik:\n" .
                                   "- Penjualan ditambahkan: {$count_penjualan} transaksi\n" .
                                   "- Pembelian ditambahkan: {$count_pembelian} transaksi\n" .
                                   "- Total entry baru: " . ($count_penjualan + $count_pembelian) . "\n" .
                                   "- Saldo akhir: " . formatRupiah($saldo_running);
        }
        
        header('Location: ../index.php?page=dashboard_kas');
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = 'Gagal sinkronisasi kas: ' . $e->getMessage();
        header('Location: ../index.php?page=generate_kas');
    }
    
    exit;
}
?>