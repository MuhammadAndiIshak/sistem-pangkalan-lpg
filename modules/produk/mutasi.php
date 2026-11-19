<?php
/**
 * Riwayat Mutasi Stok Produk
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/../../config/app.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$pdo = get_db();

// Ambil info produk
$stmt = $pdo->prepare('SELECT * FROM produk WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$produk = $stmt->fetch();

if (!$produk) {
    set_flash('danger', 'Data produk tidak ditemukan.');
    redirect('modules/produk/index.php');
}

$pageTitle = 'Mutasi Stok: ' . $produk['nama_produk'];

// Ambil pergerakan stok gabungan (UNION) dari lpg_masuk dan penjualan
$mutasiStmt = $pdo->prepare("
    SELECT 
        'masuk' AS tipe,
        lm.id,
        lm.tanggal_masuk AS tanggal,
        lm.created_at,
        lm.jumlah,
        pm.nama_pemasok AS mitra,
        lm.keterangan AS catatan
    FROM lpg_masuk lm
    JOIN pemasok pm ON lm.pemasok_id = pm.id
    WHERE lm.produk_id = :id1

    UNION ALL

    SELECT 
        'keluar' AS tipe,
        pj.id,
        pj.tanggal_penjualan AS tanggal,
        pj.created_at,
        pj.jumlah,
        pl.nama_pelanggan AS mitra,
        CONCAT('Penjualan senilai ', 'Rp ', FORMAT(pj.total, 0, 'id_ID')) AS catatan
    FROM penjualan pj
    JOIN pelanggan pl ON pj.pelanggan_id = pl.id
    WHERE pj.produk_id = :id2

    ORDER BY tanggal DESC, created_at DESC
");
$mutasiStmt->execute([':id1' => $id, ':id2' => $id]);
$mutasiList = $mutasiStmt->fetchAll();

// Hitung total masuk dan total keluar
$totMasuk = 0;
$totKeluar = 0;
foreach ($mutasiList as $m) {
    if ($m['tipe'] === 'masuk') {
        $totMasuk += (int)$m['jumlah'];
    } else {
        $totKeluar += (int)$m['jumlah'];
    }
}

$badge = get_stock_badge((int)$produk['stok'], (int)$produk['stok_minimum']);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <div class="page-heading">
        <h1 class="page-title">Riwayat Mutasi Perubahan Stok</h1>
        <p class="page-subtitle">Daftar pergerakan pasokan masuk dan transaksi penjualan untuk <strong><?= e($produk['nama_produk']) ?></strong>.</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('modules/produk/index.php') ?>" class="btn btn-secondary">
            <?= icon('arrow-left', '', 14) ?>
            <span>Kembali ke Produk</span>
        </a>
    </div>
</div>

<!-- Kartu Ringkasan Stok Produk Ini -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <span>Sisa Stok Fisik</span>
            <div class="stat-icon"><?= icon('produk', '', 16) ?></div>
        </div>
        <div class="stat-value num" style="color: var(--color-primary);"><?= tabung($produk['stok']) ?></div>
        <div class="stat-subtext">
            <span class="badge <?= $badge['class'] ?>"><?= $badge['label'] ?></span>
            &bull; Min: <?= tabung($produk['stok_minimum']) ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span>Total Pasokan Masuk</span>
            <div class="stat-icon" style="color: var(--color-success);"><?= icon('lpg_masuk', '', 16) ?></div>
        </div>
        <div class="stat-value num" style="color: var(--color-success);">+<?= tabung($totMasuk) ?></div>
        <div class="stat-subtext">Akumulasi pengiriman dari agen</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span>Total Gas Keluar (Terjual)</span>
            <div class="stat-icon" style="color: var(--color-danger);"><?= icon('penjualan', '', 16) ?></div>
        </div>
        <div class="stat-value num" style="color: var(--color-danger);">-<?= tabung($totKeluar) ?></div>
        <div class="stat-subtext">Akumulasi penyaluran ke pelanggan</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Log Kronologis Perubahan Stok</div>
        <div style="font-size: 12.5px; color: var(--text-muted);">
            Total <strong><?= count($mutasiList) ?></strong> mutasi tercatat
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Tanggal</th>
                        <th>Waktu Catat</th>
                        <th>Jenis Mutasi</th>
                        <th>Mitra / Pihak Terkait</th>
                        <th class="num">Perubahan Stok</th>
                        <th>Keterangan / Detail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mutasiList)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <?= icon('history') ?>
                                <h4>Belum Ada Riwayat Mutasi</h4>
                                <p>Produk ini belum memiliki catatan pasokan masuk maupun transaksi penjualan.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($mutasiList as $idx => $m): ?>
                    <tr>
                        <td style="color: var(--text-muted);"><?= $idx + 1 ?></td>
                        <td style="font-weight: 500;"><?= tanggal_indo($m['tanggal']) ?></td>
                        <td style="color: var(--text-muted); font-size: 12px;"><?= date('H:i', strtotime($m['created_at'])) ?> WITA</td>
                        <td>
                            <?php if ($m['tipe'] === 'masuk'): ?>
                                <span class="badge badge-success">
                                    <?= icon('lpg_masuk', '', 12) ?>
                                    <span>LPG Masuk (Pasokan)</span>
                                </span>
                            <?php else: ?>
                                <span class="badge badge-danger">
                                    <?= icon('penjualan', '', 12) ?>
                                    <span>Penjualan (Keluar)</span>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: 500;"><?= e($m['mitra']) ?></td>
                        <td class="num" style="font-size: 13.5px; font-weight: 700;">
                            <?php if ($m['tipe'] === 'masuk'): ?>
                                <span style="color: var(--color-success);">+<?= tabung($m['jumlah']) ?></span>
                            <?php else: ?>
                                <span style="color: var(--color-danger);">-<?= tabung($m['jumlah']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="color: var(--text-muted);"><?= e($m['catatan'] ?: '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
