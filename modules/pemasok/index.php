<?php
/**
 * Modul Pemasok - Daftar Pemasok / Agen LPG
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/../../config/app.php';
require_login();

$pageTitle = 'Data Pemasok Agen';
$pdo = get_db();

$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];

if ($search !== '') {
    $where = 'WHERE nama_pemasok LIKE :s1 OR alamat LIKE :s2 OR no_telepon LIKE :s3';
    $params[':s1'] = "%$search%";
    $params[':s2'] = "%$search%";
    $params[':s3'] = "%$search%";
}

// Hitung total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM pemasok $where");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

// Ambil data beserta jumlah riwayat suplai masuk
$query = "
    SELECT pm.*, COUNT(lm.id) AS total_transaksi, COALESCE(SUM(lm.jumlah), 0) AS total_tabung_disuplai
    FROM pemasok pm
    LEFT JOIN lpg_masuk lm ON pm.id = lm.pemasok_id
    $where
    GROUP BY pm.id
    ORDER BY pm.id ASC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$pemasokList = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <div class="page-heading">
        <h1 class="page-title">Pemasok / Agen Resmi Pertamina</h1>
        <p class="page-subtitle">Daftar agen dan penyalur resmi LPG yang mendistribusikan pasokan tabung ke pangkalan.</p>
    </div>
    <?php if (is_admin()): ?>
    <div class="page-actions">
        <a href="<?= url('modules/pemasok/create.php') ?>" class="btn btn-primary">
            <?= icon('plus', '', 14) ?>
            <span>Tambah Pemasok Baru</span>
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <div class="search-box">
            <form method="GET" action="" style="margin: 0;">
                <?= icon('search', '', 15) ?>
                <input type="text" name="search" class="form-control" placeholder="Cari nama agen, alamat, atau no. telepon..." value="<?= e($search) ?>">
            </form>
        </div>
        <div style="font-size: 12.5px; color: var(--text-muted);">
            Menampilkan <strong><?= count($pemasokList) ?></strong> dari total <strong><?= $totalRows ?></strong> pemasok
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama Agen Pemasok</th>
                        <th>Alamat Lengkap di Kota Palu</th>
                        <th>Nomor Telepon</th>
                        <th class="num">Total Pasokan Masuk</th>
                        <?php if (is_admin()): ?>
                        <th style="text-align: right; width: 140px;">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pemasokList)): ?>
                    <tr>
                        <td colspan="<?= is_admin() ? 6 : 5 ?>">
                            <div class="empty-state">
                                <?= icon('pemasok') ?>
                                <h4>Data Pemasok Tidak Ditemukan</h4>
                                <p>Tidak ada pemasok yang cocok dengan kata kunci pencarian tersebut.</p>
                                <?php if ($search !== ''): ?>
                                    <a href="<?= url('modules/pemasok/index.php') ?>" class="btn btn-sm btn-secondary">Reset Pencarian</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($pemasokList as $idx => $pm): ?>
                    <tr>
                        <td style="color: var(--text-muted);"><?= $offset + $idx + 1 ?></td>
                        <td style="font-weight: 600; color: var(--color-primary);"><?= e($pm['nama_pemasok']) ?></td>
                        <td style="max-width: 320px; line-height: 1.4;"><?= e($pm['alamat']) ?></td>
                        <td>
                            <span class="badge badge-neutral">
                                <?= e($pm['no_telepon']) ?>
                            </span>
                        </td>
                        <td class="num">
                            <strong><?= $pm['total_transaksi'] ?>x kirim</strong>
                            <div style="font-size: 11.5px; color: var(--text-muted);"><?= tabung($pm['total_tabung_disuplai']) ?></div>
                        </td>
                        <?php if (is_admin()): ?>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="<?= url('modules/pemasok/edit.php?id=' . $pm['id']) ?>" class="btn btn-sm btn-secondary" title="Ubah Data">
                                <?= icon('edit', '', 12) ?>
                                <span>Edit</span>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger-ghost" 
                                    data-delete-url="<?= url('modules/pemasok/delete.php?id=' . $pm['id']) ?>"
                                    data-item-name="<?= e($pm['nama_pemasok']) ?>"
                                    title="Hapus Pemasok">
                                <?= icon('trash', '', 12) ?>
                            </button>
                        </td>
                        <?php endif; ?>
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
