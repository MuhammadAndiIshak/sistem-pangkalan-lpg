<?php
/**
 * Modul Pelanggan - Daftar Pelanggan Pangkalan
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/../../config/app.php';
require_login();

$pageTitle = 'Data Pelanggan';
$pdo = get_db();

$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];

if ($search !== '') {
    $where = 'WHERE nama_pelanggan LIKE :s1 OR alamat LIKE :s2 OR no_telepon LIKE :s3';
    $params[':s1'] = "%$search%";
    $params[':s2'] = "%$search%";
    $params[':s3'] = "%$search%";
}

// Hitung total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM pelanggan $where");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

// Ambil data pelanggan beserta riwayat pembelian
$query = "
    SELECT pl.*, 
           COUNT(pj.id) AS total_transaksi, 
           COALESCE(SUM(pj.jumlah), 0) AS total_tabung,
           COALESCE(SUM(pj.total), 0) AS total_belanja
    FROM pelanggan pl
    LEFT JOIN penjualan pj ON pl.id = pj.pelanggan_id
    $where
    GROUP BY pl.id
    ORDER BY pl.id ASC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$pelangganList = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <div class="page-heading">
        <h1 class="page-title">Data Pelanggan Pangkalan</h1>
        <p class="page-subtitle">Daftar warga dan UMKM terdaftar di Kota Palu yang menerima penyaluran gas LPG.</p>
    </div>
    <?php if (is_admin()): ?>
    <div class="page-actions">
        <a href="<?= url('modules/pelanggan/create.php') ?>" class="btn btn-primary">
            <?= icon('plus', '', 14) ?>
            <span>Tambah Pelanggan Baru</span>
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <div class="search-box">
            <form method="GET" action="" style="margin: 0;">
                <?= icon('search', '', 15) ?>
                <input type="text" name="search" class="form-control" placeholder="Cari nama warga, kelurahan/alamat, atau no. telp..." value="<?= e($search) ?>">
            </form>
        </div>
        <div style="font-size: 12.5px; color: var(--text-muted);">
            Menampilkan <strong><?= count($pelangganList) ?></strong> dari total <strong><?= $totalRows ?></strong> pelanggan
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama Pelanggan / Usaha</th>
                        <th>Alamat Domisili di Kota Palu</th>
                        <th>Nomor Telepon</th>
                        <th class="num">Riwayat Pembelian</th>
                        <th class="num">Total Belanja</th>
                        <?php if (is_admin()): ?>
                        <th style="text-align: right; width: 140px;">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pelangganList)): ?>
                    <tr>
                        <td colspan="<?= is_admin() ? 7 : 6 ?>">
                            <div class="empty-state">
                                <?= icon('pelanggan') ?>
                                <h4>Data Pelanggan Tidak Ditemukan</h4>
                                <p>Tidak ada pelanggan yang sesuai dengan pencarian tersebut.</p>
                                <?php if ($search !== ''): ?>
                                    <a href="<?= url('modules/pelanggan/index.php') ?>" class="btn btn-sm btn-secondary">Reset Pencarian</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($pelangganList as $idx => $pl): ?>
                    <tr>
                        <td style="color: var(--text-muted);"><?= $offset + $idx + 1 ?></td>
                        <td style="font-weight: 600; color: var(--color-primary);"><?= e($pl['nama_pelanggan']) ?></td>
                        <td style="max-width: 300px; line-height: 1.4;"><?= e($pl['alamat']) ?></td>
                        <td>
                            <span class="badge badge-neutral">
                                <?= e($pl['no_telepon']) ?>
                            </span>
                        </td>
                        <td class="num">
                            <strong><?= $pl['total_transaksi'] ?>x beli</strong>
                            <div style="font-size: 11.5px; color: var(--text-muted);"><?= tabung($pl['total_tabung']) ?></div>
                        </td>
                        <td class="num" style="font-weight: 600;">
                            <?= rupiah($pl['total_belanja']) ?>
                        </td>
                        <?php if (is_admin()): ?>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="<?= url('modules/pelanggan/edit.php?id=' . $pl['id']) ?>" class="btn btn-sm btn-secondary" title="Ubah Data">
                                <?= icon('edit', '', 12) ?>
                                <span>Edit</span>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger-ghost" 
                                    data-delete-url="<?= url('modules/pelanggan/delete.php?id=' . $pl['id']) ?>"
                                    data-item-name="<?= e($pl['nama_pelanggan']) ?>"
                                    title="Hapus Pelanggan">
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
