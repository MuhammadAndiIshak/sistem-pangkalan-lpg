# Sistem Informasi Pengelolaan Pangkalan Gas LPG Kota Palu

Aplikasi web sistem informasi pangkalan gas LPG modern, bersih, dan profesional yang dirancang khusus untuk memonitor inventaris stok, pencatatan pasokan masuk dari agen Pertamina, transaksi penjualan ke warga/UMKM, riwayat mutasi stok, serta rekapitulasi laporan di Kota Palu, Sulawesi Tengah.

---

## 1. Fitur Utama

- **Role-Based Access Control (RBAC)**:
  - **Admin / Petugas Operasional**: Akses penuh mengelola master data (Produk, Pemasok, Pelanggan), transaksi pasokan masuk (LPG Masuk), kasir penjualan, dan laporan.
  - **Pemilik Pangkalan**: Akses hanya-lihat (*read-only*) untuk memantau ringkasan Dashboard, pergerakan stok, riwayat transaksi, dan mencetak laporan. Menu tambah/edit/hapus disembunyikan dan diproteksi di sisi server (mencegah akses langsung via URL).
- **Katalog Produk Gas LPG & Peringatan Stok Menipis**:
  - Pengelolaan data tabung: LPG 3 kg (Bersubsidi), Bright Gas 5,5 kg, Bright Gas 12 kg, dan Elpiji 50 kg (Industri).
  - Peringatan dini (*low-stock badge*) otomatis muncul di dashboard dan tabel produk ketika stok berada di bawah atau sama dengan batas stok minimum.
- **Penerimaan Pasokan LPG Masuk (DB Transaction)**:
  - Pencatatan DO pengiriman dari agen resmi Pertamina di Palu.
  - Penambahan stok fisik otomatis dalam transaksi database ACID yang aman.
  - Notifikasi kilat (*toast*) menampilkan posisi stok terbaru setelah pasokan diterima.
- **Kasir Penjualan & Proteksi Race Condition**:
  - Pengisian harga satuan otomatis berdasarkan HET produk terpilih (dapat disesuaikan jika ada diskon).
  - Kalkulasi total pembayaran secara *live* di formulir (JavaScript) dan divalidasi ulang secara ketat di server.
  - Validasi stok ketat: transaksi otomatis ditolak jika kuantitas melebihi stok yang tersedia.
  - Menggunakan query `SELECT ... FOR UPDATE` dan database transaction untuk mencegah *race condition* dan selisih stok.
  - Pembuatan struk/nota transaksi resmi siap cetak (*print-ready*).
- **Log Mutasi Perubahan Stok**:
  - Rekam jejak kronologis setiap tabung gas masuk dan keluar per produk secara transparan.
- **Laporan & Ekspor Data**:
  - 4 jenis laporan: Laporan Posisi Stok, Laporan Pasokan LPG Masuk, Laporan Penjualan, dan Laporan Riwayat Mutasi.
  - Filter rentang periode tanggal dinamis.
  - Rekap total kartu statistik di atas tabel.
  - Tampilan siap cetak (`@media print` tanpa tombol & elemen navigasi) dan fitur Ekspor file CSV berkode UTF-8 (kompatibel penuh dengan Microsoft Excel).
- **Desain UI/UX Berkualitas Tinggi**:
  - Palet warna *deep petrol teal* (`#0e4a5a`) dan aksen *flame amber* (`#d97706`).
  - Tipografi rapi menggunakan *Plus Jakarta Sans* dengan angka tabular (`font-variant-numeric: tabular-nums`).
  - Modal konfirmasi hapus data kustom (tanpa pop-up `confirm()` bawaan browser yang kaku).
  - Sistem notifikasi *toast* mengambang yang halus dan responsif di layar desktop maupun smartphone.

---

## 2. Struktur Direktori Proyek

```
C:\laragon\www\pangkalan-lpg\
├── config/
│   ├── app.php              # Pengaturan sesi, zona waktu WITA, base URL, konstanta
│   └── database.php         # Koneksi PDO MySQL & penanganan error
├── includes/
│   ├── auth.php             # Middleware otorisasi, sesi login, dan RBAC
│   ├── helpers.php          # Format Rupiah, tanggal Indo, escaping XSS, CSRF, icon SVG
│   ├── header.php           # Template head HTML, CSS, dan topbar
│   ├── sidebar.php          # Menu navigasi dinamis berbasis role
│   └── footer.php           # Toast global, modal hapus kustom, scripts
├── modules/
│   ├── produk/              # CRUD produk, status stok, dan log mutasi
│   ├── pemasok/             # CRUD agen pemasok resmi Pertamina
│   ├── pelanggan/           # CRUD data pembeli pangkalan (warga/UMKM Palu)
│   ├── lpg_masuk/           # Transaksi pasokan masuk & penambahan stok otomatis
│   ├── penjualan/           # Transaksi kasir penjualan & struk cetak
│   └── laporan/             # Filter laporan berkala, print, & ekspor CSV
├── assets/
│   ├── css/
│   │   └── app.css          # Desain sistem kustom SaaS, responsif, dan print styles
│   ├── js/
│   │   └── app.js           # Toast engine, modal kustom, kalkulator kasir live
│   └── img/
│       └── logo.svg         # Logo vektor minimalis tabung & api pangkalan LPG
├── database.sql             # Skema DDL tabel, foreign key, & seed data realistis
├── index.php                # Router utama ke Dashboard / Login
├── login.php                # Halaman autentikasi layout dua kolom
├── logout.php               # Pembersihan sesi dan pengalihan ke login
├── dashboard.php            # Dashboard metrik, Chart.js, & peringatan stok
└── README.md                # Dokumentasi instalasi dan panduan penggunaan
```

---

## 3. Akun Login Bawaan (Default)

Password telah dienkripsi dengan standar aman `password_hash()` (bcrypt):

| Peran (Role) | Username | Password | Hak Akses |
|---|---|---|---|
| **Admin / Petugas** | `admin` | `admin123` | Akses penuh: kelola produk, pemasok, pelanggan, input pasokan masuk, transaksi penjualan, mutasi, dan laporan |
| **Pemilik Pangkalan** | `pemilik` | `pemilik123` | Akses monitor (*read-only*): dashboard, lihat stok, riwayat transaksi, mutasi, dan cetak laporan |

---

## 4. Cara Instalasi & Menjalankan di Laragon

### Langkah 1: Pastikan Laragon Aktif
1. Buka aplikasi **Laragon**.
2. Klik tombol **Start All** untuk menyalakan Apache dan MySQL.

### Langkah 2: Lokasi Folder Proyek
Pastikan folder proyek berada di direktori web server Laragon:
`C:\laragon\www\pangkalan-lpg`

*(Jika instalasi Laragon Anda berada di drive `D:\semester_3\PRAKTIKUM_BASIS_DATA\laragon`, folder ini telah dihubungkan secara otomatis melalui symbolic junction, sehingga dapat langsung diakses).*

### Langkah 3: Import Database MySQL
1. Buka browser dan buka **phpMyAdmin** (`http://localhost/phpmyadmin`) atau buka terminal / **HeidiSQL** bawaan Laragon.
2. Buat database baru bernama `pangkalan_lpg` (jika belum ada).
3. Import file `database.sql` yang terletak di dalam folder proyek:
   ```bash
   mysql -u root -p pangkalan_lpg < C:\laragon\www\pangkalan-lpg\database.sql
   ```
   *Atau gunakan menu **Import** pada phpMyAdmin lalu pilih file `database.sql`.*

### Langkah 4: Akses Aplikasi di Browser
Buka Google Chrome / Firefox / Edge dan kunjungi URL berikut:
```
http://localhost/pangkalan-lpg/
```
Gunakan akun `admin` / `admin123` untuk menguji alur operasional penuh atau `pemilik` / `pemilik123` untuk menguji hak akses pemilik.

---

## 5. Ringkasan Pengujian Fitur End-to-End

1. **Login & RBAC**:
   - Berhasil login dengan validasi hash password bcrypt.
   - Akun Pemilik tidak dapat melihat tombol Tambah/Edit/Hapus, dan jika mencoba membuka URL `/modules/produk/create.php` langsung dialihkan ke dashboard dengan peringatan akses ditolak (HTTP 403).
2. **Tambah LPG Masuk**:
   - Memilih produk dan memasukkan kuantitas (misal 50 tabung). Stok fisik produk langsung bertambah secara atomik dan muncul notifikasi sukses berisi jumlah stok terbaru.
3. **Penjualan Gas**:
   - Harga satuan terisi otomatis sesuai HET produk.
   - Total harga terhitung otomatis secara live.
   - Jika kuantitas melebihi stok yang ada, form menampilkan peringatan merah dan tombol dinonaktifkan.
   - Server memvalidasi ulang dengan `SELECT ... FOR UPDATE` dan memotong stok dengan aman.
   - Sistem langsung menampilkan resi/struk penjualan yang siap dicetak via printer kasir/thermal.
4. **Perlindungan Foreign Key (Safe Deletion)**:
   - Menghapus produk/pemasok/pelanggan yang memiliki riwayat transaksi ditolak secara elegan dengan pesan informatif tanpa menyebabkan SQL error crash.
5. **Laporan & Ekspor**:
   - Menampilkan posisi stok, pasokan masuk, penjualan, dan riwayat mutasi dengan rekap total.
   - Fitur ekspor CSV menghasilkan file dengan encoding UTF-8 BOM yang rapi saat dibuka di Microsoft Excel.
