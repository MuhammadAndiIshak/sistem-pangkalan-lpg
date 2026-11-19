<?php
/**
 * Hapus Pemasok (Safe Foreign Key Check)
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/../../config/app.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/pemasok/index.php');
}

verify_csrf();

$id = (int)($_GET['id'] ?? 0);
$pdo = get_db();

// Cek pemasok ada
$stmt = $pdo->prepare('SELECT nama_pemasok FROM pemasok WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$pemasok = $stmt->fetch();

if (!$pemasok) {
    set_flash('danger', 'Data pemasok tidak ditemukan.');
    redirect('modules/pemasok/index.php');
}

$nama = $pemasok['nama_pemasok'];

// Cek keterkaitan dengan transaksi lpg_masuk
$checkSupply = $pdo->prepare('SELECT COUNT(*) FROM lpg_masuk WHERE pemasok_id = :id');
$checkSupply->execute([':id' => $id]);
$count = (int)$checkSupply->fetchColumn();

if ($count > 0) {
    set_flash('danger', "Pemasok '{$nama}' tidak dapat dihapus karena tercatat dalam {$count} riwayat transaksi penerimaan pasokan LPG masuk.");
    redirect('modules/pemasok/index.php');
}

try {
    $del = $pdo->prepare('DELETE FROM pemasok WHERE id = :id');
    $del->execute([':id' => $id]);
    set_flash('success', "Pemasok '{$nama}' berhasil dihapus.");
} catch (PDOException $e) {
    log_app_error('Gagal hapus pemasok: ' . $e->getMessage());
    set_flash('danger', 'Gagal menghapus pemasok karena kendala relasi database.');
}

redirect('modules/pemasok/index.php');
