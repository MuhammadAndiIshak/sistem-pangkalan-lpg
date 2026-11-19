<?php
/**
 * Pembatalan (Void) Pasokan LPG Masuk & Rollback Stok
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/../../config/app.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/lpg_masuk/index.php');
}

verify_csrf();

$id = (int)($_GET['id'] ?? 0);
$pdo = get_db();

try {
    $pdo->beginTransaction();

    $masukStmt = $pdo->prepare('
        SELECT lm.*, p.nama_produk
        FROM lpg_masuk lm
        JOIN produk p ON lm.produk_id = p.id
        WHERE lm.id = :id
        FOR UPDATE
    ');
    $masukStmt->execute([':id' => $id]);
    $masuk = $masukStmt->fetch();

    if (!$masuk) {
        throw new Exception('Data pasokan LPG masuk tidak ditemukan.');
    }

    $prodStmt = $pdo->prepare('SELECT stok FROM produk WHERE id = :pid FOR UPDATE');
    $prodStmt->execute([':pid' => $masuk['produk_id']]);
    $stokSaatIni = (int)$prodStmt->fetchColumn();

    if ($stokSaatIni < (int)$masuk['jumlah']) {
        throw new Exception("Pasokan tidak dapat dibatalkan karena sebagian tabung sudah terjual (sisa stok saat ini: {$stokSaatIni} tabung, sedangkan pasokan tercatat: {$masuk['jumlah']} tabung).");
    }

    $tambahKosong = ((int)$masuk['tukar_tabung'] === 1) ? (int)$masuk['jumlah'] : 0;
    $updProd = $pdo->prepare('
        UPDATE produk
        SET stok = stok - :jml,
            stok_kosong = stok_kosong + :kosong
        WHERE id = :pid
    ');
    $updProd->execute([
        ':jml'    => (int)$masuk['jumlah'],
        ':kosong' => $tambahKosong,
        ':pid'    => (int)$masuk['produk_id']
    ]);

    $delStmt = $pdo->prepare('DELETE FROM lpg_masuk WHERE id = :id');
    $delStmt->execute([':id' => $id]);

    $pdo->commit();
    set_flash('success', "Catatan pasokan '{$masuk['nama_produk']}' (+" . tabung($masuk['jumlah']) . ") berhasil dibatalkan dan stok telah disesuaikan kembali.");
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_app_error('Gagal membatalkan LPG masuk: ' . $e->getMessage());
    set_flash('danger', $e->getMessage());
}

redirect('modules/lpg_masuk/index.php');