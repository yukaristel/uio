<?php
/**
 * STRUK TRANSAKSI (PRINT)
 * Step 28/64 (43.8%)
 */

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    die('ID transaksi tidak valid!');
}

// Get transaksi
$transaksi = fetchOne("
    SELECT t.*, u.nama_lengkap 
    FROM transaksi_penjualan t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.id = ?
", [$id]);

if (!$transaksi) {
    die('Transaksi tidak ditemukan!');
}

// Get detail items
$items = fetchAll("
    SELECT dt.*, m.nama_menu 
    FROM detail_transaksi dt 
    JOIN menu_makanan m ON dt.menu_id = m.id 
    WHERE dt.transaksi_id = ?
", [$id]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk - <?php echo $transaksi['no_transaksi']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            padding: 20px;
            max-width: 300px;
            margin: 0 auto;
        }
        
        .struk {
            border: 1px dashed #000;
            padding: 10px;
        }
        
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        
        .header h2 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .info {
            margin-bottom: 10px;
            font-size: 11px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        
        .divider {
            border-bottom: 1px dashed #000;
            margin: 10px 0;
        }
        
        .items {
            margin-bottom: 10px;
        }
        
        .item {
            margin-bottom: 8px;
        }
        
        .item-name {
            font-weight: bold;
        }
        
        .item-detail {
            display: flex;
            justify-content: space-between;
            padding-left: 10px;
            font-size: 11px;
        }
        
        .summary {
            border-top: 1px dashed #000;
            padding-top: 10px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .summary-row.total {
            font-size: 14px;
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 5px 0;
            margin-top: 5px;
        }
        
        .footer {
            text-align: center;
            border-top: 1px dashed #000;
            padding-top: 10px;
            margin-top: 10px;
            font-size: 11px;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
            
            .struk {
                border: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 14px; cursor: pointer;">
            🖨️ CETAK STRUK
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 14px; cursor: pointer; margin-left: 10px;">
            ❌ TUTUP
        </button>
    </div>

    <div class="struk">
        <!-- Header -->
        <div class="header">
            <h2>RUMAH MAKAN</h2>
            <div>Jl. Contoh No. 123</div>
            <div>Telp: 0274-123456</div>
        </div>

        <!-- Info Transaksi -->
        <div class="info">
            <div class="info-row">
                <span>No. Transaksi</span>
                <span><?php echo $transaksi['no_transaksi']; ?></span>
            </div>
            <div class="info-row">
                <span>Tanggal</span>
                <span><?php echo date('d/m/Y H:i', strtotime($transaksi['tanggal_transaksi'])); ?></span>
            </div>
            <div class="info-row">
                <span>Kasir</span>
                <span><?php echo $transaksi['nama_lengkap']; ?></span>
            </div>
            <div class="info-row">
                <span>Metode</span>
                <span><?php echo strtoupper($transaksi['metode_pembayaran']); ?></span>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Items -->
        <div class="items">
            <?php foreach ($items as $item): ?>
            <div class="item">
                <div class="item-name"><?php echo $item['nama_menu']; ?></div>
                <div class="item-detail">
                    <span><?php echo $item['jumlah']; ?> x <?php echo formatRupiah($item['harga_satuan']); ?></span>
                    <span><?php echo formatRupiah($item['subtotal']); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Summary -->
        <div class="summary">
            <div class="summary-row">
                <span>Subtotal</span>
                <span><?php echo formatRupiah($transaksi['total_harga']); ?></span>
            </div>
            
            <div class="summary-row total">
                <span>TOTAL</span>
                <span><?php echo formatRupiah($transaksi['total_harga']); ?></span>
            </div>
            
            <?php if ($transaksi['metode_pembayaran'] == 'tunai'): ?>
            <div class="summary-row">
                <span>Uang Bayar</span>
                <span><?php echo formatRupiah($transaksi['uang_bayar']); ?></span>
            </div>
            <div class="summary-row">
                <span>Kembali</span>
                <span><?php echo formatRupiah($transaksi['uang_kembali']); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div style="margin-bottom: 5px;">*** TERIMA KASIH ***</div>
            <div>Barang yang sudah dibeli</div>
            <div>tidak dapat dikembalikan</div>
            <div style="margin-top: 10px;">www.rumah-makan.com</div>
        </div>
    </div>

    <script>
        // Auto print on load (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>