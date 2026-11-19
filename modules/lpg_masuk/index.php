<?php
/**
 * Modul LPG Masuk - Riwayat Penerimaan Pasokan
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/../../config/app.php';
require_login();

$pageTitle = 'LPG Masuk (Pasokan)';
$pdo = get_db();

$search = trim($_GET['search'] ?? '');
$produkId = (int)($_GET['produk_id'] ?? 0);
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$whereClauses = [];
$params = [];

if ($search !== '') {
    $whereClauses[] = '(pm.nama_pemasok LIKE :s1 OR lm.keterangan LIKE :s2 OR p.nama_produk LIKE :s3)';
    $params[':s1'] = "%$search%";
    $params[':s2'] = "%$search%";
    $params[':s3'] = "%$search%";
}

if ($produkId > 0) {
    $whereClauses[] = 'lm.produk_id = :pid';
    $params[':pid'] = $produkId;
}

if ($startDate !== '') {
    $whereClauses[] = 'lm.tanggal_masuk >= :start_date';
    $params[':start_date'] = $startDate;
}

if ($endDate !== '') {
    $whereClauses[] = 'lm.tanggal_masuk <= :end_date';
    $params[':end_date'] = $endDate;
}

$where = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Hitung total dan rekap jumlah tabung
$countQuery = "
    SELECT COUNT(*) AS total_rows, COALESCE(SUM(lm.jumlah), 0) AS total_tabung_masuk
    FROM lpg_masuk lm
    JOIN produk p ON lm.produk_id = p.id
    JOIN pemasok pm ON lm.pemasok_id = pm.id
    $where
";
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$rekap = $countStmt->fetch();
$totalRows = (int)$rekap['total_rows'];
$totalTabung = (int)$rekap['total_tabung_masuk'];
$totalPages = ceil($totalRows / $perPage);

// Ambil daftar transaksi
$query = "
    SELECT lm.*, p.nama_produk, p.ukuran, pm.nama_pemasok
    FROM lpg_masuk lm
    JOIN produk p ON lm.produk_id = p.id
    JOIN pemasok pm ON lm.pemasok_id = pm.id
    $where
    ORDER BY lm.tanggal_masuk DESC, lm.id DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$masukList = $stmt->fetchAll();

// Ambil daftar produk untuk filter
$allProduk = $pdo->query('SELECT id, nama_produk, ukuran FROM produk ORDER BY nama_produk ASC')->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <div class="page-heading">
        <h1 class="page-title">Penerimaan Pasokan LPG Masuk</h1>
        <p class="page-subtitle">Pencatatan delivery order (DO) dan pengiriman tabung gas dari agen Pertamina ke pangkalan.</p>
    </div>
    <?php if (is_admin()): ?>
    <div class="page-actions">
        <a href="<?= url('modules/lpg_masuk/create.php') ?>" class="btn btn-primary">
            <?= icon('plus', '', 14) ?>
            <span>Tambah Pasokan Masuk</span>
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Kartu Ringkasan Filter Saat Ini -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <span>Total Pengiriman Masuk</span>
            <div class="stat-icon"><?= icon('lpg_masuk', '', 16) ?></div>
        </div>
        <div class="stat-value num"><?= $totalRows ?> Transaksi</div>
        <div class="stat-subtext">Sesuai kriteria filter</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span>Total Volume Tabung Diterima</span>
            <div class="stat-icon" style="color: var(--color-success);"><?= icon('produk', '', 16) ?></div>
        </div>
        <div class="stat-value num" style="color: var(--color-success);">+<?= tabung($totalTabung) ?></div>
        <div class="stat-subtext">Menambah stok inventaris pangkalan</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" action="" class="filter-toolbar-form">
            <div class="search-box">
                <?= icon('search', '', 15) ?>
                <input type="text" name="search" class="form-control" placeholder="Cari agen, catatan DO, produk..." value="<?= e($search) ?>">
            </div>

            <div style="flex: 1; min-width: 140px;">
                <select name="produk_id" class="form-control" onchange="this.form.submit()">
                    <option value="">Semua Produk LPG</option>
                    <?php foreach ($allProduk as $ap): ?>
                        <option value="<?= $ap['id'] ?>" <?= $produkId === (int)$ap['id'] ? 'selected' : '' ?>>
                            <?= e($ap['nama_produk']) ?> (<?= e($ap['ukuran']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="date-range-group">
                <input type="date" name="start_date" class="form-control" value="<?= e($startDate) ?>" title="Dari Tanggal">
                <span style="color: var(--text-muted);">&ndash;</span>
                <input type="date" name="end_date" class="form-control" value="<?= e($endDate) ?>" title="Sampai Tanggal">
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-secondary" style="flex: 1;">Filter</button>
                <?php if ($search !== '' || $produkId > 0 || $startDate !== '' || $endDate !== ''): ?>
                    <a href="<?= url('modules/lpg_masuk/index.php') ?>" class="btn btn-secondary" title="Reset Filter">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Tanggal Masuk</th>
                        <th>Agen Pemasok</th>
                        <th>Produk Gas LPG</th>
                        <th>Ukuran</th>
                        <th class="num">Jumlah Tabung</th>
                        <th>Keterangan / No. DO</th>
                        <th style="text-align: right;">Waktu Input</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($masukList)): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <?= icon('lpg_masuk') ?>
                                <h4>Data Pasokan Tidak Ditemukan</h4>
                                <p>Tidak ada catatan transaksi LPG masuk yang sesuai dengan filter.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($masukList as $idx => $m): ?>
                    <tr>
                        <td style="color: var(--text-muted);"><?= $offset + $idx + 1 ?></td>
                        <td style="font-weight: 600;"><?= tanggal_indo($m['tanggal_masuk']) ?></td>
                        <td style="font-weight: 500; color: var(--color-primary);"><?= e($m['nama_pemasok']) ?></td>
                        <td><?= e($m['nama_produk']) ?></td>
                        <td><span class="badge badge-neutral"><?= e($m['ukuran']) ?></span></td>
                        <td class="num" style="font-size: 13.5px; font-weight: 700; color: var(--color-success);">
                            +<?= tabung($m['jumlah']) ?>
                        </td>
                        <td style="color: var(--text-muted); max-width: 250px;"><?= e($m['keterangan'] ?: '-') ?></td>
                        <td style="text-align: right; color: var(--text-muted); font-size: 11.5px;">
                            <?= date('H:i', strtotime($m['created_at'])) ?> WITA
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
            <?php 
                $queryParams = $_GET;
                for ($i = 1; $i <= $totalPages; $i++): 
                    $queryParams['page'] = $i;
            ?>
                <a href="?<?= http_build_query($queryParams) ?>" class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
