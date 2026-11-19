<?php
/**
 * Pembatalan (Void) Transaksi Penjualan & Rollback Stok Otomatis
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/../../config/app.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/penjualan/index.php');
}

verify_csrf();

$id = (int)($_GET['id'] ?? 0);
$pdo = get_db();

try {
    $pdo->beginTransaction();

    $trxStmt = $pdo->prepare('
        SELECT pj.*, p.nama_produk
        FROM penjualan pj
        JOIN produk p ON pj.produk_id = p.id
        WHERE pj.id = :id
        FOR UPDATE
    ');
    $trxStmt->execute([':id' => $id]);
    $trx = $trxStmt->fetch();

    if (!$trx) {
        throw new Exception('Transaksi penjualan tidak ditemukan.');
    }

    // Kunci baris produk terkait
    $prodStmt = $pdo->prepare('SELECT id, stok, stok_kosong FROM produk WHERE id = :pid FOR UPDATE');
    $prodStmt->execute([':pid' => $trx['produk_id']]);
    $prod = $prodStmt->fetch();

    if (!$prod) {
        throw new Exception('Data produk terkait transaksi tidak ditemukan.');
    }

    // Kembalikan stok tabung isi; kurangi stok tabung kosong jika sebelumnya tukar tabung
    $kurangiKosong = ((int)$trx['tukar_tabung'] === 1) ? (int)$trx['jumlah'] : 0;
    $updProd = $pdo->prepare('
        UPDATE produk
        SET stok = stok + :jml,
            stok_kosong = GREATEST(0, stok_kosong - :kosong)
        WHERE id = :pid
    ');
    $updProd->execute([
        ':jml'    => (int)$trx['jumlah'],
        ':kosong' => $kurangiKosong,
        ':pid'    => (int)$trx['produk_id']
    ]);

    // Hapus record transaksi penjualan
    $delStmt = $pdo->prepare('DELETE FROM penjualan WHERE id = :id');
    $delStmt->execute([':id' => $id]);

    $pdo->commit();

    $kodeResi = 'TRX-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT);
    set_flash('success', "Transaksi {$kodeResi} dibatalkan. Stok '{$trx['nama_produk']}' dikembalikan sebanyak " . tabung($trx['jumlah']) . ".");
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_app_error('Gagal membatalkan penjualan: ' . $e->getMessage());
    set_flash('danger', $e->getMessage());
}

redirect('modules/penjualan/index.php');