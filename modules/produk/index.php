<?php
/**
 * Modul Produk - Daftar Produk Gas LPG
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/../../config/app.php';
require_login();

$pageTitle = 'Data Produk LPG';
$pdo = get_db();

$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];

if ($search !== '') {
    $where = 'WHERE nama_produk LIKE :s1 OR ukuran LIKE :s2';
    $params[':s1'] = "%$search%";
    $params[':s2'] = "%$search%";
}

// Hitung total data
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM produk $where");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

// Ambil data
$query = "SELECT * FROM produk $where ORDER BY id ASC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$produkList = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <div class="page-heading">
        <h1 class="page-title">Katalog Produk Gas LPG</h1>
        <p class="page-subtitle">Kelola daftar varian tabung gas, acuan harga beli, harga jual, dan batas stok minimum.</p>
    </div>
    <?php if (is_admin()): ?>
    <div class="page-actions">
        <a href="<?= url('modules/produk/create.php') ?>" class="btn btn-primary">
            <?= icon('plus', '', 14) ?>
            <span>Tambah Produk Baru</span>
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <div class="search-box">
            <form method="GET" action="" style="margin: 0;">
                <?= icon('search', '', 15) ?>
                <input type="text" name="search" class="form-control" placeholder="Cari nama atau ukuran produk..." value="<?= e($search) ?>">
            </form>
        </div>
        <div style="font-size: 12.5px; color: var(--text-muted);">
            Menampilkan <strong><?= count($produkList) ?></strong> dari total <strong><?= $totalRows ?></strong> produk
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama Produk</th>
                        <th>Ukuran</th>
                        <th class="num">Harga Beli (Modal)</th>
                        <th class="num">Harga Jual (HET/Pangkalan)</th>
                        <th class="num">Stok Saat Ini</th>
                        <th class="num">Stok Min.</th>
                        <th>Status Stok</th>
                        <th style="text-align: right; width: 180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($produkList)): ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <?= icon('produk') ?>
                                <h4>Data Produk Tidak Ditemukan</h4>
                                <p>Tidak ada produk yang cocok dengan pencarian kata kunci tersebut.</p>
                                <?php if ($search !== ''): ?>
                                    <a href="<?= url('modules/produk/index.php') ?>" class="btn btn-sm btn-secondary">Reset Pencarian</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($produkList as $idx => $p): 
                        $badge = get_stock_badge((int)$p['stok'], (int)$p['stok_minimum']);
                    ?>
                    <tr>
                        <td style="color: var(--text-muted);"><?= $offset + $idx + 1 ?></td>
                        <td style="font-weight: 600;"><?= e($p['nama_produk']) ?></td>
                        <td><span class="badge badge-neutral"><?= e($p['ukuran']) ?></span></td>
                        <td class="num"><?= rupiah($p['harga_beli']) ?></td>
                        <td class="num" style="font-weight: 600; color: var(--color-primary);"><?= rupiah($p['harga_jual']) ?></td>
                        <td class="num" style="font-size: 13.5px; font-weight: 700;"><?= tabung($p['stok']) ?></td>
                        <td class="num" style="color: var(--text-muted);"><?= tabung($p['stok_minimum']) ?></td>
                        <td>
                            <span class="badge <?= $badge['class'] ?>">
                                <span class="badge-dot"></span>
                                <?= $badge['label'] ?>
                            </span>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="<?= url('modules/produk/mutasi.php?id=' . $p['id']) ?>" class="btn btn-sm btn-secondary" title="Riwayat Mutasi Stok">
                                <?= icon('history', '', 12) ?>
                                <span>Mutasi</span>
                            </a>
                            <?php if (is_admin()): ?>
                            <a href="<?= url('modules/produk/edit.php?id=' . $p['id']) ?>" class="btn btn-sm btn-secondary" title="Ubah Data">
                                <?= icon('edit', '', 12) ?>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger-ghost" 
                                    data-delete-url="<?= url('modules/produk/delete.php?id=' . $p['id']) ?>"
                                    data-item-name="<?= e($p['nama_produk']) ?>"
                                    title="Hapus Produk">
                                <?= icon('trash', '', 12) ?>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="card-footer">
        <div style="font-size: 12.5px; color: var(--text-muted);">
            Halaman <?= $page ?> dari <?= $totalPages ?>
        </div>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
