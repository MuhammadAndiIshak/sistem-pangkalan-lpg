<?php
/**
 * Hapus Produk LPG (Safe Foreign Key Check)
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/../../config/app.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/produk/index.php');
}

verify_csrf();

$id = (int)($_GET['id'] ?? 0);
$pdo = get_db();

// Cek produk ada
$stmt = $pdo->prepare('SELECT nama_produk FROM produk WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$produk = $stmt->fetch();

if (!$produk) {
    set_flash('danger', 'Data produk tidak ditemukan.');
    redirect('modules/produk/index.php');
}

$nama = $produk['nama_produk'];

// Periksa apakah produk terkait dengan transaksi LPG masuk atau penjualan
$checkSupply = $pdo->prepare('SELECT COUNT(*) FROM lpg_masuk WHERE produk_id = :id');
$checkSupply->execute([':id' => $id]);
$countSupply = (int)$checkSupply->fetchColumn();

$checkSales = $pdo->prepare('SELECT COUNT(*) FROM penjualan WHERE produk_id = :id');
$checkSales->execute([':id' => $id]);
$countSales = (int)$checkSales->fetchColumn();

if ($countSupply > 0 || $countSales > 0) {
    set_flash('danger', "Produk '{$nama}' tidak dapat dihapus karena telah memiliki {$countSupply} transaksi pasokan masuk dan {$countSales} transaksi penjualan.");
    redirect('modules/produk/index.php');
}

try {
    $del = $pdo->prepare('DELETE FROM produk WHERE id = :id');
    $del->execute([':id' => $id]);
    set_flash('success', "Produk '{$nama}' berhasil dihapus dari sistem.");
} catch (PDOException $e) {
    log_app_error('Gagal hapus produk: ' . $e->getMessage());
    set_flash('danger', "Gagal menghapus produk karena kendala relasi database.");
}

redirect('modules/produk/index.php');
