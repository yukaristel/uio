# 🍽️ APLIKASI RUMAH MAKAN

Aplikasi web manajemen rumah makan dengan fitur lengkap: transaksi penjualan, pembelian bahan, kas, stock management, dan laporan.

## ✨ FITUR LENGKAP

### 🔐 Autentikasi & Role Management
- ✅ Login/Logout dengan session
- ✅ 3 Role: Admin, Karyawan, Kasir
- ✅ Akses menu berbeda per role

### 📊 Dashboard
- ✅ Dashboard Admin (lengkap)
- ✅ Dashboard Karyawan (terbatas)
- ✅ Real-time statistics
- ✅ Alert stok menipis & kerugian

### 💰 KEUANGAN (100% TERINTEGRASI)
- ✅ **Transaksi Penjualan** (auto update stok + kas masuk)
- ✅ **Pembelian Bahan** (weighted average + kas keluar)
- ✅ **Kas Manual** (gaji, operasional, investasi)
- ✅ **Rekonsiliasi Kas** (sesuaikan saldo fisik vs sistem)
- ✅ **History Saldo** (tracking harian)

### 📦 STOCK MANAGEMENT
- ✅ **Bahan Baku** (CRUD + weighted average)
- ✅ **Stock Movement** (auto tracking masuk/keluar)
- ✅ **Stock Opname** (dengan approval)
- ✅ Alert stok menipis

### 🍲 MENU & RESEP
- ✅ **Kategori Menu** (CRUD)
- ✅ **Menu Makanan** (CRUD + upload foto)
- ✅ **Resep Menu** (komposisi bahan + auto HPP)
- ✅ **Konversi Satuan Otomatis** (kg↔gram, liter↔ml)
- ✅ **Auto Calculate HPP**

### 📈 LAPORAN (COMING SOON)
- Laporan Harian
- Laporan Bulanan
- Laporan Kas
- Laporan Stok
- Laporan Menu Terlaris

---

## 🚀 CARA INSTALASI

### 1️⃣ REQUIREMENTS
- PHP 7.4 atau lebih baru
- MySQL 5.7 atau lebih baru
- Apache/Nginx (XAMPP/WAMP/LAMP)

### 2️⃣ LANGKAH INSTALASI

#### A. Setup Database
1. Buka phpMyAdmin
2. Buat database baru: `rumah_makan`
3. Import file `database.sql` (Step 13)

```sql
-- Atau jalankan query SQL langsung
CREATE DATABASE rumah_makan;
USE rumah_makan;
-- Copy paste isi database.sql
```

#### B. Setup Project
1. Extract/copy semua file ke folder:
   ```
   C:\xampp\htdocs\rumah-makan\
   ```

2. Buat folder `uploads/menu/` untuk foto menu:
   ```
   mkdir uploads
   mkdir uploads/menu
   ```

3. Set permission folder (jika Linux):
   ```bash
   chmod 777 uploads/menu
   ```

#### C. Konfigurasi Database
Edit file `config/database.php` jika perlu:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Sesuaikan password MySQL
define('DB_NAME', 'rumah_makan');
```

#### D. Akses Aplikasi
Buka browser: `http://localhost/rumah-makan/`

---

## 🔑 LOGIN CREDENTIALS

### Admin:
- Username: `admin`
- Password: `admin123`

### Karyawan:
- Username: `karyawan`
- Password: `karyawan123`

### Kasir:
- Username: `kasir`
- Password: `kasir123`

---

## 📁 STRUKTUR FOLDER

```
rumah-makan/
├── index.php                 # Router utama
├── config/
│   ├── database.php          # Koneksi DB
│   ├── auth_proses.php       # Login/Logout
│   ├── bahan_proses.php      # CRUD Bahan
│   ├── pembelian_proses.php  # Pembelian + Weighted Average
│   ├── menu_proses.php       # CRUD Menu
│   ├── resep_proses.php      # Resep + HPP
│   ├── transaksi_proses.php  # Transaksi Penjualan
│   ├── stock_movement_proses.php
│   ├── stock_opname_proses.php
│   └── kas_proses.php        # Kas + Rekonsiliasi
├── modul/
│   ├── layouts/              # Header, Footer, Navbar
│   ├── auth/                 # Login
│   ├── dashboard/            # Dashboard
│   ├── bahan_baku/           # CRUD Bahan
│   ├── kategori/             # CRUD Kategori
│   ├── menu/                 # CRUD Menu & Resep
│   ├── transaksi/            # Transaksi Penjualan
│   ├── kas_umum/             # Kas & Rekonsiliasi
│   └── laporan/              # Laporan (coming soon)
├── style/
│   └── css.css               # Custom CSS
└── uploads/
    └── menu/                 # Foto menu
```

---

## 🎯 ALUR PENGGUNAAN

### 1. Login
Akses `http://localhost/rumah-makan/` → Login dengan credentials

### 2. Setup Data Master (Admin)
1. **Kategori Menu**: Tambah kategori (Makanan Berat, Minuman, dll)
2. **Bahan Baku**: Tambah bahan (Beras, Minyak, Telur, dll)
3. **Menu**: Tambah menu + resep (komposisi bahan)

### 3. Transaksi Harian
1. **Pembelian Bahan**: Beli bahan → Stok & Kas otomatis update
2. **Penjualan**: Jual menu → Stok berkurang, Kas masuk otomatis
3. **Kas Manual**: Input gaji, operasional, dll

### 4. Monitoring
1. **Dashboard**: Lihat statistik real-time
2. **Laporan**: Export laporan harian/bulanan
3. **Stock Opname**: Cek stok fisik vs sistem

---

## 🔥 FITUR UNGGULAN

### ⚡ Weighted Average
Harga bahan otomatis dihitung rata-rata tertimbang setiap pembelian:
```
Harga Baru = (Stok Lama × Harga Lama + Stok Baru × Harga Beli) 
              ÷ (Stok Lama + Stok Baru)
```

### 🔄 Auto Integration
- Jual menu → Stok bahan berkurang + Kas masuk ✅
- Beli bahan → Stok bertambah + Kas keluar ✅
- Stock movement tercatat otomatis ✅

### 🎨 Konversi Satuan Otomatis
- kg ↔ gram
- liter ↔ ml
- Validasi otomatis jika tidak bisa dikonversi

### 📊 Real-time Tracking
- Saldo kas real-time
- HPP menu auto-calculate
- Alert stok menipis

---

## 🆘 TROUBLESHOOTING

### Error: Cannot modify header information
**Penyebab**: Ada output sebelum `header()` atau BOM di file PHP
**Solusi**: Pastikan tidak ada spasi/enter sebelum `<?php`

### Foto menu tidak muncul
**Penyebab**: Folder uploads belum ada atau permission salah
**Solusi**: 
```bash
mkdir -p uploads/menu
chmod 777 uploads/menu  # Linux
```

### Database connection failed
**Penyebab**: Password MySQL salah atau database belum dibuat
**Solusi**: Cek `config/database.php` dan pastikan database sudah diimport

### Saldo kas negatif
**Penyebab**: Belum input saldo awal
**Solusi**: Input modal awal di "Kas Manual" → Kategori: Investasi

---

## 📞 KONTAK

**Developer**: Roku and Yukari

---

## 📝 CATATAN

- ⚠️ Aplikasi ini untuk 
- ⚠️ Jangan gunakan di production tanpa security hardening
- ⚠️ Backup database secara rutin

---

## 🎓 CREDIT

Menggunakan: PHP, MySQL, Bootstrap 5, Chart.js

**Progress: 47/64 file (73.4%) - ALMOST DONE!** 🚀  or maybe 100% now,,, i dont know,, im not open the files again lol