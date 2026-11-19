<?php
/**
 * Dashboard Monitoring & Operasional (V2.0)
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/config/app.php';
require_login();

$pageTitle = 'Dashboard';
$pdo = get_db();

// 1. Ringkasan Statistik Utama (Stok Isi & Tabung Kosong)
$statProduk = $pdo->query("SELECT COUNT(*) AS total_produk, COALESCE(SUM(stok), 0) AS total_stok, COALESCE(SUM(stok_kosong), 0) AS total_kosong FROM produk")->fetch();
$statPemasok = $pdo->query("SELECT COUNT(*) AS total_pemasok FROM pemasok")->fetch();
$statPelanggan = $pdo->query("SELECT COUNT(*) AS total_pelanggan FROM pelanggan")->fetch();

// Transaksi Hari Ini (Hanya status 'selesai')
$todayStmt = $pdo->prepare("SELECT COUNT(*) AS total_trx, COALESCE(SUM(total), 0) AS total_omzet, COALESCE(SUM(jumlah), 0) AS total_tabung FROM penjualan WHERE tanggal_penjualan = CURDATE() AND status = 'selesai'");
$todayStmt->execute();
$statToday = $todayStmt->fetch();

// Penjualan Bulan Berjalan (Hanya status 'selesai')
$monthStmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) AS omzet_bulan, COALESCE(SUM(jumlah), 0) AS tabung_bulan FROM penjualan WHERE YEAR(tanggal_penjualan) = YEAR(CURDATE()) AND MONTH(tanggal_penjualan) = MONTH(CURDATE()) AND status = 'selesai'");
$monthStmt->execute();
$statMonth = $monthStmt->fetch();

// 2. Produk dengan Stok Menipis atau Habis
$lowStockStmt = $pdo->query("SELECT * FROM produk WHERE stok <= stok_minimum ORDER BY stok ASC");
$lowStockProducts = $lowStockStmt->fetchAll();

// 3. Semua Produk untuk Tabel Status Stok
$allProducts = $pdo->query("SELECT * FROM produk ORDER BY nama_produk ASC")->fetchAll();

// 4. Data Grafik Penjualan 30 Hari Terakhir (Hanya status 'selesai')
$chartStmt = $pdo->query("
    SELECT 
        tanggal_penjualan,
        SUM(total) AS total_omzet,
        SUM(jumlah) AS total_qty
    FROM penjualan
    WHERE tanggal_penjualan >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
      AND status = 'selesai'
    GROUP BY tanggal_penjualan
    ORDER BY tanggal_penjualan ASC
");
$chartRaw = $chartStmt->fetchAll();

$chartLabels = [];
$chartRevenue = [];
$chartQty = [];

foreach ($chartRaw as $cr) {
    $chartLabels[] = date('d M', strtotime($cr['tanggal_penjualan']));
    $chartRevenue[] = (float)$cr['total_omzet'];
    $chartQty[] = (int)$cr['total_qty'];
}

// 5. Transaksi Terbaru (5 Penjualan & 5 Pasokan Masuk Terakhir)
$recentSales = $pdo->query("
    SELECT pj.*, p.nama_produk, p.ukuran, pl.nama_pelanggan
    FROM penjualan pj
    JOIN produk p ON pj.produk_id = p.id
    JOIN pelanggan pl ON pj.pelanggan_id = pl.id
    WHERE pj.status = 'selesai'
    ORDER BY pj.id DESC
    LIMIT 5
")->fetchAll();

$recentSupply = $pdo->query("
    SELECT lm.*, p.nama_produk, p.ukuran, pm.nama_pemasok
    FROM lpg_masuk lm
    JOIN produk p ON lm.produk_id = p.id
    JOIN pemasok pm ON lm.pemasok_id = pm.id
    WHERE lm.status = 'selesai'
    ORDER BY lm.id DESC
    LIMIT 5
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="content-header">
    <div class="page-heading">
        <h1 class="page-title">Dashboard Monitoring</h1>
        <p class="page-subtitle">Ringkasan inventaris tabung isi, tabung kosong, dan penyaluran LPG di Kota Palu.</p>
    </div>
    <?php if (is_admin()): ?>
    <div class="page-actions">
        <a href="<?= url('modules/lpg_masuk/create.php') ?>" class="btn btn-secondary">
            <?= icon('plus', '', 14) ?>
            <span>Tambah Pasokan</span>
        </a>
        <a href="<?= url('modules/penjualan/create.php') ?>" class="btn btn-primary">
            <?= icon('plus', '', 14) ?>
            <span>Input Penjualan</span>
        </a>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($lowStockProducts)): ?>
<div class="alert-banner">
    <div class="alert-banner-content">
        <?= icon('alert-triangle', '', 20) ?>
        <div>
            <strong>Peringatan Batas Stok Minimum:</strong> Terdapat <?= count($lowStockProducts) ?> produk gas LPG dengan stok isi menipis atau mendekati batas minimum:
            <strong>
            <?php 
                $names = array_map(function($p) {
                    return e($p['nama_produk']) . ' (' . tabung($p['stok']) . ')';
                }, $lowStockProducts);
                echo implode(', ', $names);
            ?>
            </strong>.
        </div>
    </div>
    <?php if (is_admin()): ?>
    <a href="<?= url('modules/lpg_masuk/create.php') ?>" class="btn btn-sm btn-primary" style="white-space: nowrap;">
        Pesan Pasokan Baru
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <span>Stok Tabung Isi</span>
            <div class="stat-icon"><?= icon('produk', '', 16) ?></div>
        </div>
        <div class="stat-value num"><?= number_format($statProduk['total_stok'], 0, ',', '.') ?></div>
        <div class="stat-subtext">Tabung kosong: <?= tabung($statProduk['total_kosong']) ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span>Transaksi Hari Ini</span>
            <div class="stat-icon"><?= icon('penjualan', '', 16) ?></div>
        </div>
        <div class="stat-value num"><?= $statToday['total_trx'] ?></div>
        <div class="stat-subtext"><?= tabung($statToday['total_tabung']) ?> &bull; <?= rupiah($statToday['total_omzet']) ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span>Penjualan Bulan Ini</span>
            <div class="stat-icon"><?= icon('laporan', '', 16) ?></div>
        </div>
        <div class="stat-value num" style="font-size: 20px;"><?= rupiah($statMonth['omzet_bulan']) ?></div>
        <div class="stat-subtext">Volume: <?= tabung($statMonth['tabung_bulan']) ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span>Mitra & Pelanggan</span>
            <div class="stat-icon"><?= icon('pelanggan', '', 16) ?></div>
        </div>
        <div class="stat-value num"><?= $statPelanggan['total_pelanggan'] ?></div>
        <div class="stat-subtext"><?= $statPemasok['total_pemasok'] ?> agen resmi Pertamina</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Tren Penjualan 30 Hari Terakhir</div>
        <span style="font-size: 12px; color: var(--text-muted);">Transaksi sah harian Kota Palu</span>
    </div>
    <div class="card-body">
        <div class="chart-wrapper">
            <canvas id="salesChart"></canvas>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Status Stok Produk LPG (Isi vs Kosong)</div>
        <a href="<?= url('modules/produk/index.php') ?>" class="btn btn-sm btn-secondary">Kelola Produk</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Nama Produk</th>
                        <th>Ukuran</th>
                        <th class="num">Harga Jual</th>
                        <th class="num">Stok Isi</th>
                        <th class="num">Tabung Kosong</th>
                        <th class="num">Stok Min.</th>
                        <th>Status</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allProducts as $p): 
                        $badge = get_stock_badge((int)$p['stok'], (int)$p['stok_minimum']);
                    ?>
                    <tr>
                        <td style="font-weight: 600;"><?= e($p['nama_produk']) ?></td>
                        <td><span class="badge badge-neutral"><?= e($p['ukuran']) ?></span></td>
                        <td class="num" style="font-weight: 600;"><?= rupiah($p['harga_jual']) ?></td>
                        <td class="num" style="font-size: 14px; font-weight: 700; color: var(--color-success);">
                            <?= tabung($p['stok']) ?>
                        </td>
                        <td class="num" style="font-weight: 600; color: var(--text-muted);">
                            <?= tabung($p['stok_kosong'] ?? 0) ?>
                        </td>
                        <td class="num" style="color: var(--text-muted);"><?= tabung($p['stok_minimum']) ?></td>
                        <td>
                            <span class="badge <?= $badge['class'] ?>">
                                <span class="badge-dot"></span>
                                <?= $badge['label'] ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <a href="<?= url('modules/produk/mutasi.php?id=' . $p['id']) ?>" class="btn btn-sm btn-secondary" title="Riwayat Mutasi">
                                <?= icon('history', '', 13) ?>
                                <span>Mutasi</span>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="split-grid">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Penjualan Terbaru</div>
            <a href="<?= url('modules/penjualan/index.php') ?>" class="btn btn-sm btn-secondary">Semua</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Produk</th>
                            <th class="num">Qty</th>
                            <th class="num">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentSales)): ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--text-muted);">Belum ada data penjualan.</td></tr>
                        <?php else: ?>
                        <?php foreach ($recentSales as $rs): ?>
                        <tr>
                            <td><?= tanggal_indo($rs['tanggal_penjualan']) ?></td>
                            <td style="font-weight: 500;"><?= e($rs['nama_pelanggan']) ?></td>
                            <td><?= e($rs['nama_produk']) ?></td>
                            <td class="num font-weight-bold"><?= $rs['jumlah'] ?></td>
                            <td class="num" style="font-weight: 600;"><?= rupiah($rs['total']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">Pasokan Masuk Terbaru</div>
            <a href="<?= url('modules/lpg_masuk/index.php') ?>" class="btn btn-sm btn-secondary">Semua</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Agen Pemasok</th>
                            <th>Produk</th>
                            <th class="num">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentSupply)): ?>
                        <tr><td colspan="4" style="text-align: center; color: var(--text-muted);">Belum ada data pasokan masuk.</td></tr>
                        <?php else: ?>
                        <?php foreach ($recentSupply as $rsu): ?>
                        <tr>
                            <td><?= tanggal_indo($rsu['tanggal_masuk']) ?></td>
                            <td style="font-weight: 500;"><?= e($rsu['nama_pemasok']) ?></td>
                            <td><?= e($rsu['nama_produk']) ?></td>
                            <td class="num" style="font-weight: 600; color: var(--color-success); font-size: 13.5px;">
                                +<?= tabung($rsu['jumlah']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesChart');
    if (!ctx) return;

    const labels = <?= json_encode($chartLabels) ?>;
    const revenueData = <?= json_encode($chartRevenue) ?>;
    const qtyData = <?= json_encode($chartQty) ?>;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    type: 'bar',
                    label: 'Volume Tabung (Pcs)',
                    data: qtyData,
                    backgroundColor: 'rgba(14, 74, 90, 0.15)',
                    borderColor: 'rgba(14, 74, 90, 0.5)',
                    borderWidth: 1,
                    borderRadius: 4,
                    yAxisID: 'yQty',
                    order: 2
                },
                {
                    type: 'line',
                    label: 'Pendapatan Penjualan (Rp)',
                    data: revenueData,
                    borderColor: '#0e4a5a',
                    backgroundColor: 'rgba(14, 74, 90, 0.05)',
                    borderWidth: 2,
                    tension: 0.25,
                    fill: false,
                    pointBackgroundColor: '#0e4a5a',
                    pointRadius: 3,
                    yAxisID: 'yRev',
                    order: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'top',
                    labels: { boxWidth: 12, font: { family: 'Plus Jakarta Sans', size: 12 } }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if (context.dataset.yAxisID === 'yRev') {
                                return 'Pendapatan: Rp ' + Number(context.parsed.y).toLocaleString('id-ID');
                            }
                            return 'Volume: ' + context.parsed.y + ' tabung';
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } } },
                yRev: {
                    type: 'linear', display: true, position: 'left', grid: { color: '#f1f5f9' },
                    ticks: {
                        font: { family: 'Plus Jakarta Sans', size: 11 },
                        callback: function(value) {
                            if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + ' jt';
                            if (value >= 1000) return 'Rp ' + (value / 1000).toFixed(0) + ' rb';
                            return 'Rp ' + value;
                        }
                    }
                },
                yQty: {
                    type: 'linear', display: true, position: 'right', grid: { drawOnChartArea: false },
                    ticks: {
                        font: { family: 'Plus Jakarta Sans', size: 11 },
                        callback: function(value) { return value + ' tabung'; }
                    }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>