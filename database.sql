-- ==============================================================================
-- SISTEM INFORMASI PENGELOLAAN PANGKALAN GAS LPG KOTA PALU
-- Database Schema & Realistic Seed Data
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `pangkalan_lpg` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `pangkalan_lpg`;

-- 1. TABEL PENGGUNA (users)
DROP TABLE IF EXISTS `penjualan`;
DROP TABLE IF EXISTS `lpg_masuk`;
DROP TABLE IF EXISTS `pelanggan`;
DROP TABLE IF EXISTS `pemasok`;
DROP TABLE IF EXISTS `produk`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'pemilik') NOT NULL DEFAULT 'admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABEL PRODUK (produk)
CREATE TABLE `produk` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_produk` VARCHAR(100) NOT NULL,
  `ukuran` VARCHAR(50) NOT NULL,
  `harga_beli` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `harga_jual` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `stok` INT NOT NULL DEFAULT 0,
  `stok_minimum` INT NOT NULL DEFAULT 10,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABEL PEMASOK (pemasok)
CREATE TABLE `pemasok` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_pemasok` VARCHAR(100) NOT NULL,
  `alamat` TEXT NOT NULL,
  `no_telepon` VARCHAR(30) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABEL PELANGGAN (pelanggan)
CREATE TABLE `pelanggan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_pelanggan` VARCHAR(100) NOT NULL,
  `alamat` TEXT NOT NULL,
  `no_telepon` VARCHAR(30) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABEL LPG MASUK (lpg_masuk)
CREATE TABLE `lpg_masuk` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `produk_id` INT NOT NULL,
  `pemasok_id` INT NOT NULL,
  `tanggal_masuk` DATE NOT NULL,
  `jumlah` INT NOT NULL,
  `keterangan` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_lpg_masuk_produk` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_lpg_masuk_pemasok` FOREIGN KEY (`pemasok_id`) REFERENCES `pemasok` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. TABEL PENJUALAN (penjualan)
CREATE TABLE `penjualan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `produk_id` INT NOT NULL,
  `pelanggan_id` INT NOT NULL,
  `tanggal_penjualan` DATE NOT NULL,
  `jumlah` INT NOT NULL,
  `harga` DECIMAL(12, 2) NOT NULL,
  `total` DECIMAL(12, 2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_penjualan_produk` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_penjualan_pelanggan` FOREIGN KEY (`pelanggan_id`) REFERENCES `pelanggan` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- SEED DATA REALISTIS KOTA PALU, SULAWESI TENGAH
-- ==============================================================================

-- Akun Pengguna:
-- 1. admin / admin123
-- 2. pemilik / pemilik123
INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$yRoDk28g7Kvt67VSmpvo9ezuGE3oIG1bdCHMepUkSB3bzrsS/WaX6', 'Ahmad Fauzi (Petugas Operasional)', 'admin', '2026-10-01 08:00:00'),
(2, 'pemilik', '$2y$10$JZsNweg5lANusdSAW2uA8eZRJw.IHCx3LuV.KrNeaSJzwUUVqjIr2', 'Hj. Siti Rahmawati (Pemilik Pangkalan)', 'pemilik', '2026-10-01 08:00:00');

-- Produk LPG
-- LPG 3kg diset pada stok 38 tabung (di bawah stok_minimum 50) untuk menampilkan alert 'Stok Menipis'
INSERT INTO `produk` (`id`, `nama_produk`, `ukuran`, `harga_beli`, `harga_jual`, `stok`, `stok_minimum`, `created_at`) VALUES
(1, 'LPG 3 kg Bersubsidi', '3 kg', 15500.00, 18000.00, 38, 50, '2026-10-01 09:00:00'),
(2, 'Bright Gas 5,5 kg', '5.5 kg', 95000.00, 110000.00, 28, 15, '2026-10-01 09:00:00'),
(3, 'Bright Gas 12 kg', '12 kg', 205000.00, 225000.00, 18, 10, '2026-10-01 09:00:00'),
(4, 'Elpiji 50 kg Industri', '50 kg', 880000.00, 950000.00, 8, 5, '2026-10-01 09:00:00');

-- Pemasok / Agen Resmi Pertamina di Kota Palu
INSERT INTO `pemasok` (`id`, `nama_pemasok`, `alamat`, `no_telepon`, `created_at`) VALUES
(1, 'PT Palu Gas Mitra Mandiri', 'Jl. I Gusti Ngurah Rai No. 42, Kelurahan Tavanjuka, Kec. Tatanga, Kota Palu', '0451-482910', '2026-10-01 09:30:00'),
(2, 'PT Mitra Palu Sejahtera Bersama', 'Jl. Sam Ratulangi No. 88, Kelurahan Besusu Barat, Kec. Palu Timur, Kota Palu', '0451-423188', '2026-10-01 09:35:00'),
(3, 'PT Gas Bumi Tadulako Palu', 'Jl. Soekarno-Hatta KM 9, Kelurahan Tondo, Kec. Mantikulore, Kota Palu', '0811-456-7890', '2026-10-01 09:40:00');

-- Pelanggan (Warga dan Pelaku Usaha Mikro/Kecil di Kota Palu)
INSERT INTO `pelanggan` (`id`, `nama_pelanggan`, `alamat`, `no_telepon`, `created_at`) VALUES
(1, 'Warung Makan Berkah Talise', 'Jl. Yos Sudarso No. 12, Kel. Talise, Kec. Mantikulore, Kota Palu', '0812-4211-8890', '2026-10-05 10:00:00'),
(2, 'Rumah Makan Kaledo Stereo Lere', 'Jl. Diponegoro No. 34, Kel. Lere, Kec. Palu Barat, Kota Palu', '0813-5520-1922', '2026-10-05 10:15:00'),
(3, 'Ibu Nurhayati (RT 03)', 'Jl. Zebra IV No. 8, Kel. Birobuli Selatan, Kec. Palu Selatan, Kota Palu', '0852-4100-3341', '2026-10-06 11:00:00'),
(4, 'Bapak Hendra Wijaya', 'Jl. Veteran No. 19, Kel. Lasoani, Kec. Mantikulore, Kota Palu', '0821-9988-7712', '2026-10-06 11:30:00'),
(5, 'Dapur Mama Besusu (Catering)', 'Jl. Jenderal Sudirman No. 45, Kel. Besusu Tengah, Kec. Palu Timur, Kota Palu', '0853-9876-1234', '2026-10-07 09:00:00'),
(6, 'Laundry Cemerlang Tondo', 'Jl. Kampus Untad I No. 10, Kel. Tondo, Kec. Mantikulore, Kota Palu', '0812-4455-6677', '2026-10-08 14:00:00'),
(7, 'Bapak Syahril Mamboro', 'Jl. Trans Sulawesi KM 14, Kel. Mamboro, Kec. Palu Utara, Kota Palu', '0852-5678-9012', '2026-10-09 15:20:00'),
(8, 'Ibu Hasnah Lolu', 'Jl. Kartini No. 22, Kel. Lolu Selatan, Kec. Palu Timur, Kota Palu', '0822-9182-7364', '2026-10-10 08:45:00'),
(9, 'Kedai Kopi Silae Pantai', 'Jl. Malonda No. 7, Kel. Silae, Kec. Ulujadi, Kota Palu', '0813-4122-3344', '2026-10-11 16:30:00'),
(10, 'Panti Asuhan Al-Kautsar Palu', 'Jl. Bali No. 15, Kel. Lolu Utara, Kec. Palu Timur, Kota Palu', '0852-3344-5566', '2026-10-12 10:10:00');

-- Riwayat LPG Masuk (18 transaksi selama 30 hari terakhir)
INSERT INTO `lpg_masuk` (`id`, `produk_id`, `pemasok_id`, `tanggal_masuk`, `jumlah`, `keterangan`, `created_at`) VALUES
(1, 1, 1, '2026-10-21', 150, 'DO Reguler Alokasi Mingguan PT Pertamina', '2026-10-21 08:30:00'),
(2, 2, 2, '2026-10-22', 15, 'Pengiriman perdana Bright Gas 5.5 kg', '2026-10-22 09:15:00'),
(3, 3, 3, '2026-10-23', 15, 'Suplai Bright Gas 12 kg', '2026-10-23 10:00:00'),
(4, 4, 1, '2026-10-24', 5, 'Tabung 50 kg pesanan catering hotel', '2026-10-24 11:20:00'),
(5, 1, 1, '2026-10-27', 180, 'Penerimaan alokasi tambahan pangkalan', '2026-10-27 08:45:00'),
(6, 2, 2, '2026-10-28', 20, 'Penambahan stok Bright Gas 5.5 kg', '2026-10-28 09:30:00'),
(7, 3, 3, '2026-10-30', 20, 'Alokasi bulanan Bright Gas 12 kg', '2026-10-30 10:10:00'),
(8, 1, 1, '2026-11-02', 200, 'Alokasi awal November jalur reguler', '2026-11-02 08:15:00'),
(9, 4, 1, '2026-11-04', 6, 'Suplai 50 kg kebutuhan industri', '2026-11-04 13:00:00'),
(10, 2, 2, '2026-11-05', 25, 'Pengiriman tabung baru 5.5 kg', '2026-11-05 09:00:00'),
(11, 1, 1, '2026-11-07', 170, 'Alokasi penyaluran Kelurahan Besusu & Lolu', '2026-11-07 08:50:00'),
(12, 3, 3, '2026-11-09', 15, 'Drop tabung 12 kg dari agen Tadulako', '2026-11-09 11:15:00'),
(13, 1, 1, '2026-11-11', 190, 'Alokasi reguler Pertamina SPPBE Mamboro', '2026-11-11 08:30:00'),
(14, 2, 2, '2026-11-12', 15, 'Tambahan stok 5.5 kg akhir pekan', '2026-11-12 10:25:00'),
(15, 4, 1, '2026-11-13', 4, 'Suplai 50 kg restoran lokal', '2026-11-13 14:10:00'),
(16, 1, 1, '2026-11-15', 180, 'DO pengiriman rutin hari Sabtu', '2026-11-15 08:20:00'),
(17, 3, 3, '2026-11-16', 12, 'Drop tabung 12 kg', '2026-11-16 09:40:00'),
(18, 1, 1, '2026-11-17', 160, 'Penerimaan DO Pangkalan Talise', '2026-11-17 08:30:00');

-- Riwayat Transaksi Penjualan (25 transaksi selama 30 hari terakhir)
INSERT INTO `penjualan` (`id`, `produk_id`, `pelanggan_id`, `tanggal_penjualan`, `jumlah`, `harga`, `total`, `created_at`) VALUES
(1, 1, 1, '2026-10-22', 15, 18000.00, 270000.00, '2026-10-22 10:15:00'),
(2, 2, 5, '2026-10-23', 3, 110000.00, 330000.00, '2026-10-23 11:00:00'),
(3, 3, 2, '2026-10-24', 4, 225000.00, 900000.00, '2026-10-24 14:30:00'),
(4, 1, 3, '2026-10-25', 2, 18000.00, 36000.00, '2026-10-25 16:00:00'),
(5, 4, 2, '2026-10-26', 1, 950000.00, 950000.00, '2026-10-26 15:45:00'),
(6, 1, 6, '2026-10-28', 10, 18000.00, 180000.00, '2026-10-28 09:20:00'),
(7, 2, 9, '2026-10-29', 2, 110000.00, 220000.00, '2026-10-29 11:30:00'),
(8, 1, 4, '2026-10-31', 3, 18000.00, 54000.00, '2026-10-31 16:10:00'),
(9, 3, 5, '2026-11-01', 5, 225000.00, 1125000.00, '2026-11-01 10:00:00'),
(10, 1, 8, '2026-11-02', 2, 18000.00, 36000.00, '2026-11-02 11:45:00'),
(11, 1, 10, '2026-11-03', 5, 18000.00, 90000.00, '2026-11-03 14:00:00'),
(12, 4, 5, '2026-11-04', 2, 950000.00, 1900000.00, '2026-11-04 15:30:00'),
(13, 2, 9, '2026-11-05', 4, 110000.00, 440000.00, '2026-11-05 16:15:00'),
(14, 1, 1, '2026-11-06', 20, 18000.00, 360000.00, '2026-11-06 09:30:00'),
(15, 3, 2, '2026-11-07', 3, 225000.00, 675000.00, '2026-11-07 13:40:00'),
(16, 1, 7, '2026-11-08', 4, 18000.00, 72000.00, '2026-11-08 15:10:00'),
(17, 2, 3, '2026-11-09', 1, 110000.00, 110000.00, '2026-11-09 10:20:00'),
(18, 1, 6, '2026-11-10', 12, 18000.00, 216000.00, '2026-11-10 11:00:00'),
(19, 3, 5, '2026-11-11', 4, 225000.00, 900000.00, '2026-11-11 14:20:00'),
(20, 1, 4, '2026-11-12', 3, 18000.00, 54000.00, '2026-11-12 16:30:00'),
(21, 4, 2, '2026-11-13', 1, 950000.00, 950000.00, '2026-11-13 15:00:00'),
(22, 1, 1, '2026-11-14', 18, 18000.00, 324000.00, '2026-11-14 09:45:00'),
(23, 2, 9, '2026-11-15', 2, 110000.00, 220000.00, '2026-11-15 11:15:00'),
(24, 1, 8, '2026-11-16', 2, 18000.00, 36000.00, '2026-11-16 14:00:00'),
(25, 3, 2, '2026-11-17', 3, 225000.00, 675000.00, '2026-11-17 15:20:00');
