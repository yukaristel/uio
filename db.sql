-- ============================================
-- DATABASE RUMAH MAKAN
-- Step 13/64 (20.3%)
-- ============================================

-- Create Database
CREATE DATABASE IF NOT EXISTS rumah_makan;
USE rumah_makan;

-- ============================================
-- 1. TABLE: users (Karyawan)
-- ============================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'karyawan', 'kasir') DEFAULT 'karyawan',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 2. TABLE: bahan_baku
-- ============================================
CREATE TABLE bahan_baku (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_bahan VARCHAR(20) UNIQUE NOT NULL,
    nama_bahan VARCHAR(100) NOT NULL,
    satuan ENUM('kg', 'gram', 'liter', 'ml', 'pcs', 'sachet') NOT NULL,
    stok_tersedia DECIMAL(10,2) DEFAULT 0,
    stok_minimum DECIMAL(10,2) DEFAULT 0,
    harga_beli_per_satuan DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 3. TABLE: kategori_menu
-- ============================================
CREATE TABLE kategori_menu (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_kategori VARCHAR(50) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 4. TABLE: menu_makanan
-- ============================================
CREATE TABLE menu_makanan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_menu VARCHAR(20) UNIQUE NOT NULL,
    nama_menu VARCHAR(100) NOT NULL,
    kategori_id INT NOT NULL,
    harga_modal DECIMAL(15,2) DEFAULT 0,
    harga_jual DECIMAL(15,2) NOT NULL,
    margin_keuntungan DECIMAL(10,2) DEFAULT 0,
    status ENUM('tersedia', 'habis', 'tidak_tersedia') DEFAULT 'tersedia',
    foto_menu VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori_menu(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 5. TABLE: resep_menu
-- ============================================
CREATE TABLE resep_menu (
    id INT PRIMARY KEY AUTO_INCREMENT,
    menu_id INT NOT NULL,
    bahan_id INT NOT NULL,
    jumlah_bahan DECIMAL(10,2) NOT NULL,
    satuan ENUM('kg', 'gram', 'liter', 'ml', 'pcs', 'sachet') NOT NULL,
    biaya_bahan DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (menu_id) REFERENCES menu_makanan(id) ON DELETE CASCADE,
    FOREIGN KEY (bahan_id) REFERENCES bahan_baku(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 6. TABLE: pembelian_bahan
-- ============================================
CREATE TABLE pembelian_bahan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bahan_id INT NOT NULL,
    jumlah_beli DECIMAL(10,2) NOT NULL,
    harga_beli_satuan DECIMAL(15,2) NOT NULL,
    total_harga DECIMAL(15,2) NOT NULL,
    supplier VARCHAR(100),
    tanggal_beli DATE NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bahan_id) REFERENCES bahan_baku(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 7. TABLE: transaksi_penjualan
-- ============================================
CREATE TABLE transaksi_penjualan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    no_transaksi VARCHAR(30) UNIQUE NOT NULL,
    tanggal_transaksi DATETIME NOT NULL,
    total_harga DECIMAL(15,2) NOT NULL,
    total_modal DECIMAL(15,2) DEFAULT 0,
    total_keuntungan DECIMAL(15,2) DEFAULT 0,
    metode_pembayaran ENUM('tunai', 'debit', 'qris', 'transfer') DEFAULT 'tunai',
    uang_bayar DECIMAL(15,2),
    uang_kembali DECIMAL(15,2),
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 8. TABLE: detail_transaksi
-- ============================================
CREATE TABLE detail_transaksi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaksi_id INT NOT NULL,
    menu_id INT NOT NULL,
    jumlah INT NOT NULL,
    harga_satuan DECIMAL(15,2) NOT NULL,
    harga_modal_satuan DECIMAL(15,2) DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL,
    subtotal_modal DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaksi_id) REFERENCES transaksi_penjualan(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_id) REFERENCES menu_makanan(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 9. TABLE: stock_movement
-- ============================================
CREATE TABLE stock_movement (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bahan_id INT NOT NULL,
    jenis_pergerakan ENUM('masuk', 'keluar', 'opname', 'rusak', 'expired', 'hilang', 'tumpah') NOT NULL,
    jumlah DECIMAL(10,2) NOT NULL,
    satuan VARCHAR(20) NOT NULL,
    harga_per_satuan DECIMAL(15,2) NOT NULL,
    total_nilai DECIMAL(15,2) NOT NULL,
    stok_sebelum DECIMAL(10,2) NOT NULL,
    stok_sesudah DECIMAL(10,2) NOT NULL,
    referensi_type ENUM('pembelian', 'penjualan', 'opname', 'manual') NOT NULL,
    referensi_id INT NULL,
    keterangan TEXT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bahan_id) REFERENCES bahan_baku(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_bahan_tanggal (bahan_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 10. TABLE: stock_opname
-- ============================================
CREATE TABLE stock_opname (
    id INT PRIMARY KEY AUTO_INCREMENT,
    no_opname VARCHAR(50) UNIQUE NOT NULL,
    tanggal_opname DATE NOT NULL,
    bahan_id INT NOT NULL,
    stok_sistem DECIMAL(10,2) NOT NULL,
    stok_fisik DECIMAL(10,2) NOT NULL,
    selisih DECIMAL(10,2) NOT NULL,
    satuan VARCHAR(20) NOT NULL,
    harga_per_satuan DECIMAL(15,2) NOT NULL,
    nilai_selisih DECIMAL(15,2) NOT NULL,
    jenis_selisih ENUM('hilang', 'rusak', 'expired', 'tumpah', 'salah_hitung', 'lainnya') NULL,
    keterangan TEXT NULL,
    status ENUM('draft', 'approved') DEFAULT 'draft',
    user_id INT NOT NULL,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bahan_id) REFERENCES bahan_baku(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    INDEX idx_tanggal (tanggal_opname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 11. TABLE: kas_umum
-- ============================================
CREATE TABLE kas_umum (
    id INT PRIMARY KEY AUTO_INCREMENT,
    no_transaksi_kas VARCHAR(50) UNIQUE NOT NULL,
    tanggal_transaksi DATETIME NOT NULL,
    jenis_transaksi ENUM('masuk', 'keluar') NOT NULL,
    kategori ENUM('penjualan', 'pembelian_bahan', 'gaji', 'operasional', 'investasi', 'lainnya') NOT NULL,
    nominal DECIMAL(15,2) NOT NULL,
    saldo_sebelum DECIMAL(15,2) NOT NULL,
    saldo_sesudah DECIMAL(15,2) NOT NULL,
    referensi_type ENUM('penjualan', 'pembelian', 'manual') NULL,
    referensi_id INT NULL,
    keterangan TEXT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_tanggal (tanggal_transaksi),
    INDEX idx_jenis (jenis_transaksi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 12. TABLE: saldo_kas
-- ============================================
CREATE TABLE saldo_kas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tanggal DATE UNIQUE NOT NULL,
    saldo_awal DECIMAL(15,2) NOT NULL,
    total_masuk DECIMAL(15,2) DEFAULT 0,
    total_keluar DECIMAL(15,2) DEFAULT 0,
    saldo_akhir DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tanggal (tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- INSERT DATA AWAL
-- ============================================

-- Insert Default Users (password: admin123, karyawan123)
INSERT INTO users (username, password, nama_lengkap, role) VALUES
('admin', 'admin123', 'Administrator', 'admin'),
('karyawan', 'karyawan123', 'Karyawan Satu', 'karyawan'),
('kasir', 'kasir123', 'Kasir Satu', 'kasir');

-- Insert Kategori Menu
INSERT INTO kategori_menu (nama_kategori, deskripsi) VALUES
('Makanan Berat', 'Menu makanan utama'),
('Minuman', 'Menu minuman dingin dan panas'),
('Snack', 'Makanan ringan dan cemilan'),
('Dessert', 'Menu penutup');

-- Insert Sample Bahan Baku
INSERT INTO bahan_baku (kode_bahan, nama_bahan, satuan, stok_tersedia, stok_minimum, harga_beli_per_satuan) VALUES
('BHN001', 'Beras', 'kg', 50, 10, 12000),
('BHN002', 'Minyak Goreng', 'liter', 10, 5, 20000),
('BHN003', 'Telur', 'pcs', 100, 20, 2000),
('BHN004', 'Garam', 'kg', 5, 1, 5000),
('BHN005', 'Gula Pasir', 'kg', 10, 2, 14000),
('BHN006', 'Kopi Bubuk', 'kg', 2, 1, 50000),
('BHN007', 'Teh', 'sachet', 200, 50, 500),
('BHN008', 'Tepung Terigu', 'kg', 15, 5, 10000);

-- Insert Sample Menu
INSERT INTO menu_makanan (kode_menu, nama_menu, kategori_id, harga_modal, harga_jual, margin_keuntungan, status) VALUES
('MNU001', 'Nasi Goreng', 1, 8000, 15000, 7000, 'tersedia'),
('MNU002', 'Mie Goreng', 1, 7000, 13000, 6000, 'tersedia'),
('MNU003', 'Es Teh Manis', 2, 1500, 5000, 3500, 'tersedia'),
('MNU004', 'Kopi Hitam', 2, 2000, 7000, 5000, 'tersedia');

-- Insert Saldo Kas Awal
INSERT INTO kas_umum (no_transaksi_kas, tanggal_transaksi, jenis_transaksi, kategori, nominal, saldo_sebelum, saldo_sesudah, referensi_type, keterangan, user_id) VALUES
('KAS-20250101-001', '2025-01-01 08:00:00', 'masuk', 'investasi', 5000000, 0, 5000000, 'manual', 'Modal awal usaha', 1);

INSERT INTO saldo_kas (tanggal, saldo_awal, total_masuk, total_keluar, saldo_akhir) VALUES
('2025-01-01', 0, 5000000, 0, 5000000);

-- ============================================
-- END OF DATABASE SCHEMA
-- ============================================