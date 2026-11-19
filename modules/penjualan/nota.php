<?php
/**
 * Struk / Nota Transaksi Penjualan Gas LPG
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/../../config/app.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$pdo = get_db();

// Ambil rincian transaksi
$stmt = $pdo->prepare('
    SELECT pj.*, p.nama_produk, p.ukuran, pl.nama_pelanggan, pl.alamat AS alamat_pelanggan, pl.no_telepon
    FROM penjualan pj
    JOIN produk p ON pj.produk_id = p.id
    JOIN pelanggan pl ON pj.pelanggan_id = pl.id
    WHERE pj.id = :id
    LIMIT 1
');
$stmt->execute([':id' => $id]);
$trx = $stmt->fetch();

if (!$trx) {
    set_flash('danger', 'Transaksi penjualan tidak ditemukan.');
    redirect('modules/penjualan/index.php');
}

$pageTitle = 'Struk Penjualan #' . str_pad($trx['id'], 5, '0', STR_PAD_LEFT);
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="content-header no-print">
    <div class="page-heading">
        <h1 class="page-title">Resi / Bukti Transaksi Penjualan</h1>
        <p class="page-subtitle">Rincian tanda terima pembayaran resmi penyaluran gas LPG pangkalan.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <?= icon('printer', '', 14) ?>
            <span>Cetak Struk (Print)</span>
        </button>
        <a href="<?= url('modules/penjualan/index.php') ?>" class="btn btn-secondary">
            <?= icon('arrow-left', '', 14) ?>
            <span>Daftar Penjualan</span>
        </a>
    </div>
</div>

<div class="receipt-wrapper">
    <div class="receipt-header">
        <div style="display: flex; justify-content: center; margin-bottom: 8px;">
            <img src="<?= url('assets/img/logo.svg') ?>" alt="Logo" style="width: 36px; height: 36px;">
        </div>
        <div class="receipt-title"><?= PANGKALAN_NAMA ?></div>
        <div class="receipt-address">
            <?= PANGKALAN_ALAMAT ?><br>
            Telp / WA: <?= PANGKALAN_TELP ?> &bull; Kota Palu
        </div>
    </div>

    <div style="font-size: 12px; margin-bottom: 14px; line-height: 1.6;">
        <div style="display: flex; justify-content: space-between;">
            <span style="color: var(--text-muted);">No. Resi:</span>
            <strong>TRX-<?= str_pad($trx['id'], 5, '0', STR_PAD_LEFT) ?></strong>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span style="color: var(--text-muted);">Tanggal:</span>
            <span><?= tanggal_indo($trx['tanggal_penjualan']) ?></span>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span style="color: var(--text-muted);">Waktu Cetak:</span>
            <span><?= date('H:i', strtotime($trx['created_at'])) ?> WITA</span>
        </div>
        <div style="display: flex; justify-content: space-between; border-top: 1px dotted var(--border-medium); padding-top: 6px; margin-top: 6px;">
            <span style="color: var(--text-muted);">Pembeli:</span>
            <strong style="text-align: right;"><?= e($trx['nama_pelanggan']) ?></strong>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span style="color: var(--text-muted);">Alamat:</span>
            <span style="text-align: right; max-width: 200px;"><?= e($trx['alamat_pelanggan']) ?></span>
        </div>
    </div>

    <table class="receipt-table">
        <thead>
            <tr>
                <th>Deskripsi Produk</th>
                <th style="text-align: center;">Qty</th>
                <th style="text-align: right;">Harga</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div style="font-weight: 600;"><?= e($trx['nama_produk']) ?></div>
                    <div style="font-size: 11px; color: var(--text-muted);"><?= e($trx['ukuran']) ?></div>
                </td>
                <td style="text-align: center; font-weight: 600;"><?= $trx['jumlah'] ?></td>
                <td style="text-align: right;"><?= rupiah($trx['harga'], false) ?></td>
                <td style="text-align: right; font-weight: 600;"><?= rupiah($trx['total'], false) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="receipt-total-row">
        <span>TOTAL PEMBAYARAN</span>
        <span style="color: var(--color-primary); font-size: 16px;"><?= rupiah($trx['total']) ?></span>
    </div>

    <div style="text-align: center; margin-top: 24px; padding-top: 16px; border-top: 1px dashed var(--border-medium); font-size: 11px; color: var(--text-muted); line-height: 1.5;">
        Terima kasih atas kepercayaan Anda.<br>
        Barang yang sudah dibeli telah dicek dalam kondisi baik dan berstandar SNI.<br>
        <strong>Simpan struk ini sebagai bukti transaksi sah.</strong>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
