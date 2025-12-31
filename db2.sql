-- ============================================
-- DATABASE RUMAH MAKAN - AKUNTANSI
-- ============================================

-- ============================================
-- 1. TABEL CHART OF ACCOUNTS (COA)
-- ============================================
CREATE TABLE IF NOT EXISTS chart_of_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lev1 INT NOT NULL,
    lev2 INT NOT NULL,
    lev3 INT NOT NULL,
    lev4 INT NOT NULL,
    kode_akun VARCHAR(20) UNIQUE NOT NULL,
    nama_akun VARCHAR(200) NOT NULL,
    jenis_mutasi ENUM('Debet', 'Kredit') NOT NULL,
    posisi TINYINT NOT NULL COMMENT '1=Neraca, 2=Laba Rugi',
    status ENUM('Aktif', 'Nonaktif') DEFAULT 'Aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kode (kode_akun),
    INDEX idx_level (lev1, lev2, lev3, lev4)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- ISI DATA COA
-- ============================================

-- ASET (1.x.xx.xx)
-- ============================================
INSERT INTO chart_of_accounts (lev1, lev2, lev3, lev4, kode_akun, nama_akun, jenis_mutasi, posisi) VALUES
-- Kas & Bank
(1, 1, 0, 0, '1.1.00.00', 'Kas dan Bank', 'Debet', 1),
(1, 1, 1, 0, '1.1.01.00', 'Kas', 'Debet', 1),
(1, 1, 1, 1, '1.1.01.01', 'Kas Tunai', 'Debet', 1),
(1, 1, 1, 2, '1.1.01.02', 'Kas QRIS', 'Debet', 1),
(1, 1, 1, 3, '1.1.01.03', 'Kas GoPay', 'Debet', 1),
(1, 1, 1, 4, '1.1.01.04', 'Kas Grab', 'Debet', 1),
(1, 1, 1, 99, '1.1.01.99', 'Kas Lain-lain', 'Debet', 1),
(1, 1, 2, 0, '1.1.02.00', 'Bank', 'Debet', 1),
(1, 1, 2, 1, '1.1.02.01', 'Bank Mandiri', 'Debet', 1),
(1, 1, 2, 99, '1.1.02.99', 'Bank Lain-lain', 'Debet', 1),

-- Persediaan
(1, 2, 0, 0, '1.2.00.00', 'Persediaan', 'Debet', 1),
(1, 2, 1, 0, '1.2.01.00', 'Persediaan Bahan Baku', 'Debet', 1),
(1, 2, 1, 99, '1.2.01.99', 'Persediaan Lain-lain', 'Debet', 1),

-- Aset Tetap
(1, 3, 0, 0, '1.3.00.00', 'Aset Tetap', 'Debet', 1),
(1, 3, 1, 0, '1.3.01.00', 'Peralatan', 'Debet', 1),
(1, 3, 1, 1, '1.3.01.01', 'Peralatan Dapur', 'Debet', 1),
(1, 3, 1, 99, '1.3.01.99', 'Peralatan Lain-lain', 'Debet', 1),
(1, 3, 2, 0, '1.3.02.00', 'Akumulasi Penyusutan', 'Kredit', 1),
(1, 3, 2, 1, '1.3.02.01', 'Akumulasi Penyusutan Peralatan', 'Kredit', 1),
(1, 3, 2, 99, '1.3.02.99', 'Akumulasi Penyusutan Lain-lain', 'Kredit', 1);

-- LIABILITAS (2.x.xx.xx)
-- ============================================
INSERT INTO chart_of_accounts (lev1, lev2, lev3, lev4, kode_akun, nama_akun, jenis_mutasi, posisi) VALUES
-- Utang Lancar
(2, 1, 0, 0, '2.1.00.00', 'Utang Lancar', 'Kredit', 1),
(2, 1, 1, 0, '2.1.01.00', 'Utang Usaha', 'Kredit', 1),
(2, 1, 1, 1, '2.1.01.01', 'Utang Supplier', 'Kredit', 1),
(2, 1, 1, 99, '2.1.01.99', 'Utang Usaha Lain-lain', 'Kredit', 1),

-- Utang Jangka Panjang
(2, 2, 0, 0, '2.2.00.00', 'Utang Jangka Panjang', 'Kredit', 1),
(2, 2, 1, 0, '2.2.01.00', 'Pinjaman', 'Kredit', 1),
(2, 2, 1, 1, '2.2.01.01', 'Pinjaman Pihak Ketiga', 'Kredit', 1),
(2, 2, 1, 99, '2.2.01.99', 'Pinjaman Lain-lain', 'Kredit', 1);

-- EKUITAS (3.x.xx.xx)
-- ============================================
INSERT INTO chart_of_accounts (lev1, lev2, lev3, lev4, kode_akun, nama_akun, jenis_mutasi, posisi) VALUES
-- Modal
(3, 1, 0, 0, '3.1.00.00', 'Modal', 'Kredit', 1),
(3, 1, 1, 0, '3.1.01.00', 'Modal Pemilik', 'Kredit', 1),
(3, 1, 1, 1, '3.1.01.01', 'Modal Pemilik', 'Kredit', 1),

-- Laba Ditahan
(3, 2, 0, 0, '3.2.00.00', 'Laba Ditahan', 'Kredit', 1),
(3, 2, 1, 0, '3.2.01.00', 'Laba Ditahan', 'Kredit', 1);

-- PENDAPATAN (4.x.xx.xx)
-- ============================================
INSERT INTO chart_of_accounts (lev1, lev2, lev3, lev4, kode_akun, nama_akun, jenis_mutasi, posisi) VALUES
-- Pendapatan Usaha
(4, 1, 0, 0, '4.1.00.00', 'Pendapatan Usaha', 'Kredit', 2),
(4, 1, 1, 0, '4.1.01.00', 'Pendapatan Penjualan', 'Kredit', 2),
(4, 1, 1, 1, '4.1.01.01', 'Pendapatan Penjualan Makanan', 'Kredit', 2),
(4, 1, 1, 99, '4.1.01.99', 'Pendapatan Penjualan Lain-lain', 'Kredit', 2);

-- BEBAN (5.x.xx.xx)
-- ============================================
INSERT INTO chart_of_accounts (lev1, lev2, lev3, lev4, kode_akun, nama_akun, jenis_mutasi, posisi) VALUES
-- Harga Pokok Penjualan
(5, 1, 0, 0, '5.1.00.00', 'Harga Pokok Penjualan', 'Debet', 2),
(5, 1, 1, 0, '5.1.01.00', 'HPP', 'Debet', 2),
(5, 1, 1, 1, '5.1.01.01', 'HPP Bahan Baku', 'Debet', 2),
(5, 1, 1, 99, '5.1.01.99', 'HPP Lain-lain', 'Debet', 2),

-- Beban Operasional
(5, 2, 0, 0, '5.2.00.00', 'Beban Operasional', 'Debet', 2),
(5, 2, 1, 0, '5.2.01.00', 'Beban Gaji', 'Debet', 2),
(5, 2, 1, 1, '5.2.01.01', 'Beban Gaji', 'Debet', 2),
(5, 2, 2, 0, '5.2.02.00', 'Beban Utilitas', 'Debet', 2),
(5, 2, 2, 1, '5.2.02.01', 'Beban Listrik', 'Debet', 2),
(5, 2, 2, 99, '5.2.02.99', 'Beban Utilitas Lain-lain', 'Debet', 2),
(5, 2, 3, 0, '5.2.03.00', 'Beban Sewa', 'Debet', 2),
(5, 2, 3, 1, '5.2.03.01', 'Beban Sewa Gedung', 'Debet', 2),
(5, 2, 3, 99, '5.2.03.99', 'Beban Sewa Lain-lain', 'Debet', 2),
(5, 2, 99, 0, '5.2.99.00', 'Beban Operasional Lain-lain', 'Debet', 2),
(5, 2, 99, 99, '5.2.99.99', 'Beban Operasional Lain-lain', 'Debet', 2),

-- Beban Lain-lain
(5, 3, 0, 0, '5.3.00.00', 'Beban Lain-lain', 'Debet', 2),
(5, 3, 1, 0, '5.3.01.00', 'Beban Bunga', 'Debet', 2),
(5, 3, 1, 1, '5.3.01.01', 'Beban Bunga Pinjaman', 'Debet', 2),
(5, 3, 99, 0, '5.3.99.00', 'Beban Lain-lain', 'Debet', 2),
(5, 3, 99, 99, '5.3.99.99', 'Beban Lain-lain', 'Debet', 2);


-- ============================================
-- 2. TABEL TRANSAKSI
-- ============================================
CREATE TABLE IF NOT EXISTS transaksi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tgl_transaksi DATE NOT NULL,
    rekening_debet VARCHAR(20) NOT NULL,
    rekening_kredit VARCHAR(20) NOT NULL,
    keterangan_transaksi TEXT,
    jumlah BIGINT NOT NULL,
    id_user INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tgl (tgl_transaksi),
    INDEX idx_debet (rekening_debet),
    INDEX idx_kredit (rekening_kredit),
    FOREIGN KEY (rekening_debet) REFERENCES chart_of_accounts(kode_akun),
    FOREIGN KEY (rekening_kredit) REFERENCES chart_of_accounts(kode_akun)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================
-- 3. TABEL ASET TETAP
-- ============================================
CREATE TABLE IF NOT EXISTS aset_tetap (
    id_aset INT PRIMARY KEY AUTO_INCREMENT,
    nama_barang VARCHAR(200) NOT NULL,
    tgl_beli DATE NOT NULL,
    unit INT DEFAULT 1 COMMENT 'Jumlah barang',
    harsat DECIMAL(15,2) NOT NULL COMMENT 'Harga satuan',
    umur_ekonomis INT COMMENT 'Umur ekonomis dalam bulan',
    jenis VARCHAR(100) COMMENT 'Peralatan Dapur, Furniture, Elektronik, dll',
    status ENUM('Baik', 'Rusak', 'Dijual', 'Hapus', 'Maintenance') DEFAULT 'Baik',
    tgl_validasi DATE,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_jenis (jenis),
    INDEX idx_status (status),
    INDEX idx_tgl_beli (tgl_beli)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================
-- CONTOH DATA TRANSAKSI
-- ============================================

-- Modal Awal
INSERT INTO transaksi (tgl_transaksi, rekening_debet, rekening_kredit, keterangan_transaksi, jumlah, id_user) VALUES
('2024-01-01', '1.1.01.01', '3.1.01.01', 'Modal awal usaha', 50000000, 1);

-- Pembelian Inventaris
INSERT INTO transaksi (tgl_transaksi, rekening_debet, rekening_kredit, keterangan_transaksi, jumlah, id_user) VALUES
('2024-01-05', '1.3.01.01', '1.1.01.01', 'Pembelian Kompor Gas 2 Tungku', 500000, 1),
('2024-01-05', '1.3.01.01', '1.1.01.01', 'Pembelian Kulkas 2 Pintu', 3500000, 1),
('2024-01-05', '1.3.01.01', '1.1.01.01', 'Pembelian Meja Makan Kayu', 750000, 1);

-- Data Aset Tetap
INSERT INTO aset_tetap (nama_barang, tgl_beli, unit, harsat, umur_ekonomis, jenis, status) VALUES
('Kompor Gas 2 Tungku', '2024-01-05', 1, 500000, 60, 'Peralatan Dapur', 'Baik'),
('Kulkas 2 Pintu', '2024-01-05', 1, 3500000, 120, 'Peralatan Pendingin', 'Baik'),
('Meja Makan Kayu', '2024-01-05', 3, 750000, 60, 'Furniture', 'Baik');

-- Penjualan Tunai
INSERT INTO transaksi (tgl_transaksi, rekening_debet, rekening_kredit, keterangan_transaksi, jumlah, id_user) VALUES
('2024-01-10', '1.1.01.01', '4.1.01.01', 'Penjualan makanan tunai', 2500000, 1);

-- Penjualan QRIS
INSERT INTO transaksi (tgl_transaksi, rekening_debet, rekening_kredit, keterangan_transaksi, jumlah, id_user) VALUES
('2024-01-10', '1.1.01.02', '4.1.01.01', 'Penjualan makanan via QRIS', 1500000, 1);

-- Bayar Gaji
INSERT INTO transaksi (tgl_transaksi, rekening_debet, rekening_kredit, keterangan_transaksi, jumlah, id_user) VALUES
('2024-01-25', '5.2.01.01', '1.1.01.01', 'Bayar gaji karyawan bulan Januari', 3000000, 1);

-- Bayar Listrik
INSERT INTO transaksi (tgl_transaksi, rekening_debet, rekening_kredit, keterangan_transaksi, jumlah, id_user) VALUES
('2024-01-28', '5.2.02.01', '1.1.01.01', 'Bayar listrik bulan Januari', 500000, 1);

-- Bayar Sewa Gedung
INSERT INTO transaksi (tgl_transaksi, rekening_debet, rekening_kredit, keterangan_transaksi, jumlah, id_user) VALUES
('2024-01-05', '5.2.03.01', '1.1.01.01', 'Bayar sewa gedung bulan Januari', 5000000, 1);

-- Transfer Kas ke Bank
INSERT INTO transaksi (tgl_transaksi, rekening_debet, rekening_kredit, keterangan_transaksi, jumlah, id_user) VALUES
('2024-01-15', '1.1.02.01', '1.1.01.01', 'Transfer kas tunai ke Bank Mandiri', 10000000, 1);


-- ============================================
-- VIEW UNTUK LAPORAN
-- ============================================

-- View Buku Besar
CREATE OR REPLACE VIEW v_buku_besar AS
SELECT 
    t.id,
    t.tgl_transaksi,
    t.keterangan_transaksi,
    t.rekening_debet,
    coa_d.nama_akun as nama_debet,
    t.rekening_kredit,
    coa_k.nama_akun as nama_kredit,
    t.jumlah,
    t.created_at
FROM transaksi t
LEFT JOIN chart_of_accounts coa_d ON t.rekening_debet = coa_d.kode_akun
LEFT JOIN chart_of_accounts coa_k ON t.rekening_kredit = coa_k.kode_akun
ORDER BY t.tgl_transaksi DESC, t.id DESC;

-- View Saldo Kas/Bank
CREATE OR REPLACE VIEW v_saldo_kas_bank AS
SELECT 
    coa.kode_akun,
    coa.nama_akun,
    COALESCE(SUM(
        CASE 
            WHEN t.rekening_debet = coa.kode_akun THEN t.jumlah
            WHEN t.rekening_kredit = coa.kode_akun THEN -t.jumlah
            ELSE 0
        END
    ), 0) as saldo
FROM chart_of_accounts coa
LEFT JOIN transaksi t ON (t.rekening_debet = coa.kode_akun OR t.rekening_kredit = coa.kode_akun)
WHERE coa.lev1 = 1 AND coa.lev2 = 1 AND coa.lev4 > 0
GROUP BY coa.kode_akun, coa.nama_akun
ORDER BY coa.kode_akun;


-- ============================================
-- QUERY CEK SALDO
-- ============================================

-- Cek saldo semua kas/bank
SELECT * FROM v_saldo_kas_bank;

-- Cek total aset tetap
SELECT 
    jenis,
    COUNT(*) as jumlah_item,
    SUM(unit) as total_unit,
    SUM(unit * harsat) as total_nilai
FROM aset_tetap
WHERE status = 'Baik'
GROUP BY jenis;

-- Cek transaksi hari ini
SELECT * FROM v_buku_besar 
WHERE tgl_transaksi = CURDATE();