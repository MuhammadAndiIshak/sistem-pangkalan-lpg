<?php
/**
 * Hapus Pelanggan (Safe Foreign Key Check)
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/../../config/app.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/pelanggan/index.php');
}

verify_csrf();

$id = (int)($_GET['id'] ?? 0);
$pdo = get_db();

// Cek pelanggan ada
$stmt = $pdo->prepare('SELECT nama_pelanggan FROM pelanggan WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$pelanggan = $stmt->fetch();

if (!$pelanggan) {
    set_flash('danger', 'Data pelanggan tidak ditemukan.');
    redirect('modules/pelanggan/index.php');
}

$nama = $pelanggan['nama_pelanggan'];

// Cek keterkaitan dengan transaksi penjualan
$checkSales = $pdo->prepare('SELECT COUNT(*) FROM penjualan WHERE pelanggan_id = :id');
$checkSales->execute([':id' => $id]);
$count = (int)$checkSales->fetchColumn();

if ($count > 0) {
    set_flash('danger', "Pelanggan '{$nama}' tidak dapat dihapus karena telah memiliki {$count} riwayat transaksi pembelian gas LPG.");
    redirect('modules/pelanggan/index.php');
}

try {
    $del = $pdo->prepare('DELETE FROM pelanggan WHERE id = :id');
    $del->execute([':id' => $id]);
    set_flash('success', "Pelanggan '{$nama}' berhasil dihapus dari sistem.");
} catch (PDOException $e) {
    log_app_error('Gagal hapus pelanggan: ' . $e->getMessage());
    set_flash('danger', 'Gagal menghapus pelanggan karena kendala relasi database.');
}

redirect('modules/pelanggan/index.php');
