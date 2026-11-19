<?php
/**
- Modul Penjualan - Daftar Transaksi Penjualan Gas LPG
- Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/../../config/app.php';
require_login();

$pageTitle = 'Penjualan Gas LPG';
$pdo = get_db();

$search = trim($_GET['search'] ?? '');
$produkId = (int)($_GET['produk_id'] ?? 0);
$pelangganId = (int)($_GET['pelanggan_id'] ?? 0);
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$whereClauses = [];
$params = [];

if ($search !== '') {
    $whereClauses[] = '(pl.nama_pelanggan LIKE :s1 OR pl.alamat LIKE :s2 OR p.nama_produk LIKE :s3)';
    $params[':s1'] = "%$search%";
    $params[':s2'] = "%$search%";
    $params[':s3'] = "%$search%";
}

if ($produkId > 0) {
    $whereClauses[] = 'pj.produk_id = :pid';
    $params[':pid'] = $produkId;
}

if ($pelangganId > 0) {
    $whereClauses[] = 'pj.pelanggan_id = :plid';
    $params[':plid'] = $pelangganId;
}

if ($startDate !== '') {
    $whereClauses[] = 'pj.tanggal_penjualan >= :start_date';
    $params[':start_date'] = $startDate;
}

if ($endDate !== '') {
    $whereClauses[] = 'pj.tanggal_penjualan <= :end_date';
    $params[':end_date'] = $endDate;
}

$where = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Hitung total data, volume, dan omzet
$countQuery = "
    SELECT 
        COUNT(*) AS total_rows, 
        COALESCE(SUM(pj.jumlah), 0) AS total_tabung_terjual,
        COALESCE(SUM(pj.total), 0) AS total_omzet
    FROM penjualan pj
    JOIN produk p ON pj.produk_id = p.id
    JOIN pelanggan pl ON pj.pelanggan_id = pl.id
    $where
";
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$rekap = $countStmt->fetch();
$totalRows = (int)$rekap['total_rows'];
$totalTabung = (int)$rekap['total_tabung_terjual'];
$totalOmzet = (float)$rekap['total_omzet'];
$totalPages = ceil($totalRows / $perPage);

// Ambil transaksi penjualan
$query = "
    SELECT pj.*, p.nama_produk, p.ukuran, pl.nama_pelanggan, pl.no_telepon, pl.alamat
    FROM penjualan pj
    JOIN produk p ON pj.produk_id = p.id
    JOIN pelanggan pl ON pj.pelanggan_id = pl.id
    $where
    ORDER BY pj.tanggal_penjualan DESC, pj.id DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$penjualanList = $stmt->fetchAll();

// Opsi filter
$allProduk = $pdo->query('SELECT id, nama_produk, ukuran FROM produk ORDER BY nama_produk ASC')->fetchAll();
$allPelanggan = $pdo->query('SELECT id, nama_pelanggan FROM pelanggan ORDER BY nama_pelanggan ASC')->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <div class="page-heading">
        <h1 class="page-title">Penyaluran & Penjualan Gas LPG</h1>
        <p class="page-subtitle">Pencatatan transaksi eceran pangkalan ke pangkalan sub/warga/UMKM di Kota Palu.</p>
    </div>
    <?php if (is_admin()): ?>
    <div class="page-actions">
        <a href="<?= url('modules/penjualan/create.php') ?>" class="btn btn-primary">
            <?= icon('plus', '', 14) ?>
            <span>Input Penjualan Baru</span>
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Kartu Ringkasan Penjualan -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <span>Total Transaksi</span>
            <div class="stat-icon"><?= icon('penjualan', '', 16) ?></div>
        </div>
        <div class="stat-value num"><?= $totalRows ?> Penjualan</div>
        <div class="stat-subtext">Sesuai filter pencarian</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span>Volume Penyaluran</span>
            <div class="stat-icon" style="color: var(--color-primary);"><?= icon('produk', '', 16) ?></div>
        </div>
        <div class="stat-value num" style="color: var(--color-primary);"><?= tabung($totalTabung) ?></div>
        <div class="stat-subtext">Tabung terdistribusi ke masyarakat</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span>Total Penerimaan Omzet</span>
            <div class="stat-icon" style="color: var(--color-success);"><?= icon('laporan', '', 16) ?></div>
        </div>
        <div class="stat-value num" style="color: var(--color-success); font-size: 21px;"><?= rupiah($totalOmzet) ?></div>
        <div class="stat-subtext">Hasil penjualan tunai pangkalan</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" action="" class="filter-toolbar-form">
            <div class="search-box">
                <?= icon('search', '', 15) ?>
                <input type="text" name="search" class="form-control" placeholder="Cari nama pembeli, alamat, produk..." value="<?= e($search) ?>">
            </div>

            <div style="flex: 1; min-width: 140px;">
                <select name="produk_id" class="form-control" onchange="this.form.submit()">
                    <option value="">Semua Produk</option>
                    <?php foreach ($allProduk as $ap): ?>
                        <option value="<?= $ap['id'] ?>" <?= $produkId === (int)$ap['id'] ? 'selected' : '' ?>>
                            <?= e($ap['nama_produk']) ?> (<?= e($ap['ukuran']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex: 1; min-width: 140px;">
                <select name="pelanggan_id" class="form-control" onchange="this.form.submit()">
                    <option value="">Semua Pelanggan</option>
                    <?php foreach ($allPelanggan as $apl): ?>
                        <option value="<?= $apl['id'] ?>" <?= $pelangganId === (int)$apl['id'] ? 'selected' : '' ?>>
                            <?= e($apl['nama_pelanggan']) ?>
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
                <?php if ($search !== '' || $produkId > 0 || $pelangganId > 0 || $startDate !== '' || $endDate !== ''): ?>
                    <a href="<?= url('modules/penjualan/index.php') ?>" class="btn btn-secondary" title="Reset Filter">Reset</a>
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
                        <th>No. Resi</th>
                        <th>Tanggal</th>
                        <th>Nama Pelanggan</th>
                        <th>Produk Gas LPG</th>
                        <th class="num">Harga Satuan</th>
                        <th class="num">Qty</th>
                        <th class="num">Total Pembayaran</th>
                        <th style="text-align: right; width: 185px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($penjualanList)): ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <?= icon('penjualan') ?>
                                <h4>Data Penjualan Tidak Ditemukan</h4>
                                <p>Tidak ada transaksi penjualan yang cocok dengan filter yang ditentukan.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($penjualanList as $idx => $pj): ?>
                    <tr>
                        <td style="color: var(--text-muted);"><?= $offset + $idx + 1 ?></td>
                        <td>
                            <code style="font-size: 11.5px; font-weight: 600; color: var(--color-primary);">
                                TRX-<?= str_pad($pj['id'], 5, '0', STR_PAD_LEFT) ?>
                            </code>
                        </td>
                        <td style="font-weight: 500;"><?= tanggal_indo($pj['tanggal_penjualan']) ?></td>
                        <td>
                            <div style="font-weight: 600;"><?= e($pj['nama_pelanggan']) ?></div>
                            <div style="font-size: 11px; color: var(--text-muted);"><?= e($pj['alamat']) ?></div>
                        </td>
                        <td>
                            <div><?= e($pj['nama_produk']) ?></div>
                            <span class="badge badge-neutral" style="font-size: 10px;"><?= e($pj['ukuran']) ?></span>
                        </td>
                        <td class="num"><?= rupiah($pj['harga']) ?></td>
                        <td class="num" style="font-size: 13.5px; font-weight: 700;"><?= tabung($pj['jumlah']) ?></td>
                        <td class="num" style="font-weight: 700; color: var(--color-primary); font-size: 13.5px;">
                            <?= rupiah($pj['total']) ?>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <div style="display: inline-flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                <a href="<?= url('modules/penjualan/nota.php?id=' . $pj['id']) ?>" class="btn btn-sm btn-secondary" title="Cetak Struk Transaksi">
                                    <?= icon('printer', '', 12) ?>
                                    <span>Struk</span>
                                </a>

                                <?php if (is_admin()): ?>
                                <form action="<?= url('modules/penjualan/delete.php?id=' . $pj['id']) ?>" 
                                      method="POST" 
                                      style="display: inline; margin: 0;"
                                      onsubmit="return confirm('Yakin ingin membatalkan transaksi TRX-<?= str_pad((string)$pj['id'], 5, '0', STR_PAD_LEFT) ?>? Stok tabung gas akan otomatis dikembalikan.');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-danger" title="Batalkan Transaksi">
                                        <span>Batalkan</span>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
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