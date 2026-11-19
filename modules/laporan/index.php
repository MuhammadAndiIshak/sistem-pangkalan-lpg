<?php
/**
 * Modul Laporan & Monitoring (V2.0 - Termasuk Laporan Subsidi NIK & Tabung Kosong)
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/../../config/app.php';
require_login();

$pageTitle = 'Laporan & Monitoring';
$pdo = get_db();

$jenisLaporan = $_GET['jenis'] ?? 'penjualan';
if (!in_array($jenisLaporan, ['stok', 'lpg_masuk', 'penjualan', 'mutasi', 'subsidi_pelanggan'], true)) {
    $jenisLaporan = 'penjualan';
}

$startDate = trim($_GET['start_date'] ?? date('Y-m-01'));
$endDate = trim($_GET['end_date'] ?? date('Y-m-d'));

$reportData = [];
$rekap = [];

if ($jenisLaporan === 'stok') {
    $stmt = $pdo->query('SELECT * FROM produk ORDER BY nama_produk ASC');
    $reportData = $stmt->fetchAll();

    $totProduk = count($reportData);
    $totStok = 0;
    $totKosong = 0;
    $totAsetModal = 0;
    $totPotensiOmzet = 0;
    $totMenipis = 0;

    foreach ($reportData as $row) {
        $stok = (int)$row['stok'];
        $totStok += $stok;
        $totKosong += (int)($row['stok_kosong'] ?? 0);
        $totAsetModal += ($stok * (float)$row['harga_beli']);
        $totPotensiOmzet += ($stok * (float)$row['harga_jual']);
        if ($stok <= (int)$row['stok_minimum']) {
            $totMenipis++;
        }
    }

    $rekap = [
        'tot_produk'        => $totProduk,
        'tot_stok'          => $totStok,
        'tot_kosong'        => $totKosong,
        'tot_aset_modal'    => $totAsetModal,
        'tot_potensi_omzet' => $totPotensiOmzet,
        'tot_menipis'       => $totMenipis
    ];
} elseif ($jenisLaporan === 'lpg_masuk') {
    $stmt = $pdo->prepare("
        SELECT lm.*, p.nama_produk, p.ukuran, p.harga_beli, pm.nama_pemasok,
               COALESCE(u.nama_lengkap, 'Sistem') AS nama_petugas
        FROM lpg_masuk lm
        JOIN produk p ON lm.produk_id = p.id
        JOIN pemasok pm ON lm.pemasok_id = pm.id
        LEFT JOIN users u ON lm.user_id = u.id
        WHERE lm.status = 'selesai' AND lm.tanggal_masuk BETWEEN :start AND :end
        ORDER BY lm.tanggal_masuk ASC, lm.id ASC
    ");
    $stmt->execute([':start' => $startDate, ':end' => $endDate]);
    $reportData = $stmt->fetchAll();

    $totTrx = count($reportData);
    $totTabung = 0;
    $totModal = 0;

    foreach ($reportData as $row) {
        $qty = (int)$row['jumlah'];
        $totTabung += $qty;
        $totModal += ($qty * (float)$row['harga_beli']);
    }

    $rekap = [
        'tot_trx'    => $totTrx,
        'tot_tabung' => $totTabung,
        'tot_modal'  => $totModal
    ];
} elseif ($jenisLaporan === 'penjualan') {
    $stmt = $pdo->prepare("
        SELECT pj.*, p.nama_produk, p.ukuran, pl.nama_pelanggan, pl.nik, pl.alamat AS alamat_pelanggan,
               COALESCE(u.nama_lengkap, 'Sistem') AS nama_petugas
        FROM penjualan pj
        JOIN produk p ON pj.produk_id = p.id
        JOIN pelanggan pl ON pj.pelanggan_id = pl.id
        LEFT JOIN users u ON pj.user_id = u.id
        WHERE pj.status = 'selesai' AND pj.tanggal_penjualan BETWEEN :start AND :end
        ORDER BY pj.tanggal_penjualan ASC, pj.id ASC
    ");
    $stmt->execute([':start' => $startDate, ':end' => $endDate]);
    $reportData = $stmt->fetchAll();

    $totTrx = count($reportData);
    $totTabung = 0;
    $totOmzet = 0;

    foreach ($reportData as $row) {
        $totTabung += (int)$row['jumlah'];
        $totOmzet += (float)$row['total'];
    }

    $rekap = [
        'tot_trx'    => $totTrx,
        'tot_tabung' => $totTabung,
        'tot_omzet'  => $totOmzet
    ];
} elseif ($jenisLaporan === 'mutasi') {
    try {
        $stmt = $pdo->prepare("
            SELECT ms.*, p.nama_produk, p.ukuran,
                   COALESCE(u.nama_lengkap, 'Sistem') AS nama_petugas
            FROM mutasi_stok ms
            JOIN produk p ON ms.produk_id = p.id
            LEFT JOIN users u ON ms.user_id = u.id
            WHERE DATE(ms.created_at) BETWEEN :start AND :end
            ORDER BY ms.id DESC
        ");
        $stmt->execute([':start' => $startDate, ':end' => $endDate]);
        $reportData = $stmt->fetchAll();
    } catch (Exception $e) {
        $reportData = [];
    }

    $totMasuk = 0;
    $totKeluar = 0;
    foreach ($reportData as $row) {
        if (($row['jenis'] ?? '') === 'masuk') {
            $totMasuk += (int)$row['jumlah'];
        } else {
            $totKeluar += (int)$row['jumlah'];
        }
    }

    $rekap = [
        'tot_log'    => count($reportData),
        'tot_masuk'  => $totMasuk,
        'tot_keluar' => $totKeluar
    ];
} elseif ($jenisLaporan === 'subsidi_pelanggan') {
    $stmt = $pdo->prepare("
        SELECT 
            pl.id,
            pl.nama_pelanggan,
            pl.nik,
            pl.alamat,
            pl.no_telepon,
            COUNT(pj.id) AS frekuensi_beli,
            COALESCE(SUM(pj.jumlah), 0) AS total_tabung,
            COALESCE(SUM(pj.total), 0) AS total_belanja
        FROM penjualan pj
        JOIN produk p ON pj.produk_id = p.id
        JOIN pelanggan pl ON pj.pelanggan_id = pl.id
        WHERE pj.status = 'selesai'
          AND (p.ukuran LIKE '%3%' OR p.nama_produk LIKE '%3 kg%')
          AND pj.tanggal_penjualan BETWEEN :start AND :end
        GROUP BY pl.id, pl.nama_pelanggan, pl.nik, pl.alamat, pl.no_telepon
        ORDER BY total_tabung DESC, pl.nama_pelanggan ASC
    ");
    $stmt->execute([':start' => $startDate, ':end' => $endDate]);
    $reportData = $stmt->fetchAll();

    $totPenerima = count($reportData);
    $totTabung = 0;
    $totNominal = 0;

    foreach ($reportData as $row) {
        $totTabung += (int)$row['total_tabung'];
        $totNominal += (float)$row['total_belanja'];
    }

    $rekap = [
        'tot_penerima' => $totPenerima,
        'tot_tabung'   => $totTabung,
        'tot_nominal'  => $totNominal
    ];
}

$judulMap = [
    'penjualan'         => 'Laporan Penjualan Gas LPG',
    'lpg_masuk'         => 'Laporan Pasokan LPG Masuk',
    'stok'              => 'Laporan Posisi Stok Tabung (Isi & Kosong)',
    'mutasi'            => 'Log Mutasi Stok Tabung',
    'subsidi_pelanggan' => 'Rekap Penyaluran LPG 3 kg Bersubsidi per Pelanggan'
];

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
@media print {
    .app-sidebar, .app-topbar, .no-print, .sidebar-overlay { display: none !important; }
    .app-main { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
    .card { box-shadow: none !important; border: 1px solid #cbd5e1 !important; }
}
</style>

<div class="content-header">
    <div class="page-heading">
        <h1 class="page-title">Laporan & Monitoring Pangkalan</h1>
        <p class="page-subtitle">Rekapitulasi penyaluran LPG, stok tabung isi/kosong, dan monitoring subsidi Kota Palu.</p>
    </div>
    <div class="page-actions no-print">
        <button type="button" onclick="window.print()" class="btn btn-primary">
            <?= icon('printer', '', 14) ?>
            <span>Cetak Laporan</span>
        </button>
    </div>
</div>

<!-- Filter Jenis Laporan & Periode -->
<div class="card no-print" style="margin-bottom: 20px;">
    <div class="card-body">
        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; padding-bottom: 14px; border-bottom: 1px solid var(--border-color, #e2e8f0);">
            <a href="?jenis=penjualan&start_date=<?= e($startDate) ?>&end_date=<?= e($endDate) ?>"
               class="btn btn-sm <?= $jenisLaporan === 'penjualan' ? 'btn-primary' : 'btn-secondary' ?>">
                Penjualan LPG
            </a>
            <a href="?jenis=lpg_masuk&start_date=<?= e($startDate) ?>&end_date=<?= e($endDate) ?>"
               class="btn btn-sm <?= $jenisLaporan === 'lpg_masuk' ? 'btn-primary' : 'btn-secondary' ?>">
                Pasokan Masuk
            </a>
            <a href="?jenis=stok"
               class="btn btn-sm <?= $jenisLaporan === 'stok' ? 'btn-primary' : 'btn-secondary' ?>">
                Stok & Aset Tabung
            </a>
            <a href="?jenis=subsidi_pelanggan&start_date=<?= e($startDate) ?>&end_date=<?= e($endDate) ?>"
               class="btn btn-sm <?= $jenisLaporan === 'subsidi_pelanggan' ? 'btn-primary' : 'btn-secondary' ?>">
                Rekap Subsidi 3 kg (NIK)
            </a>
            <a href="?jenis=mutasi&start_date=<?= e($startDate) ?>&end_date=<?= e($endDate) ?>"
               class="btn btn-sm <?= $jenisLaporan === 'mutasi' ? 'btn-primary' : 'btn-secondary' ?>">
                Riwayat Mutasi
            </a>
        </div>

        <?php if ($jenisLaporan !== 'stok'): ?>
        <form method="GET" action="" class="filter-toolbar-form">
            <input type="hidden" name="jenis" value="<?= e($jenisLaporan) ?>">
            <div class="date-range-group">
                <input type="date" name="start_date" class="form-control" value="<?= e($startDate) ?>" required>
                <span style="color: var(--text-muted);">&ndash;</span>
                <input type="date" name="end_date" class="form-control" value="<?= e($endDate) ?>" required>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary">Tampilkan Periode</button>
                <a href="?jenis=<?= e($jenisLaporan) ?>" class="btn btn-secondary">Bulan Ini</a>
            </div>
        </form>
        <?php else: ?>
        <div style="font-size: 13px; color: var(--text-muted);">
            Menampilkan posisi stok tabung isi dan tabung kosong secara <em>real-time</em> per hari ini (<?= tanggal_indo(date('Y-m-d')) ?>).
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Kartu Ringkasan Sesuai Jenis Laporan -->
<?php if ($jenisLaporan === 'penjualan'): ?>
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header"><span>Total Transaksi</span></div>
        <div class="stat-value num"><?= $rekap['tot_trx'] ?> Transaksi</div>
        <div class="stat-subtext"><?= tanggal_indo($startDate) ?> &ndash; <?= tanggal_indo($endDate) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><span>Volume Tabung Terjual</span></div>
        <div class="stat-value num" style="color: var(--color-primary);"><?= tabung($rekap['tot_tabung']) ?></div>
        <div class="stat-subtext">Total penyaluran periode ini</div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><span>Total Omzet Penjualan</span></div>
        <div class="stat-value num" style="color: var(--color-success);"><?= rupiah($rekap['tot_omzet']) ?></div>
        <div class="stat-subtext">Penerimaan kotor penjualan</div>
    </div>
</div>

<?php elseif ($jenisLaporan === 'lpg_masuk'): ?>
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header"><span>Total Pengiriman DO</span></div>
        <div class="stat-value num"><?= $rekap['tot_trx'] ?> Kali</div>
        <div class="stat-subtext"><?= tanggal_indo($startDate) ?> &ndash; <?= tanggal_indo($endDate) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><span>Total Tabung Masuk</span></div>
        <div class="stat-value num" style="color: var(--color-primary);">+<?= tabung($rekap['tot_tabung']) ?></div>
        <div class="stat-subtext">Pasokan diterima dari agen</div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><span>Total Belanja Modal</span></div>
        <div class="stat-value num" style="color: var(--color-success);"><?= rupiah($rekap['tot_modal']) ?></div>
        <div class="stat-subtext">Berdasarkan harga beli produk</div>
    </div>
</div>

<?php elseif ($jenisLaporan === 'stok'): ?>
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header"><span>Total Tabung Isi</span></div>
        <div class="stat-value num" style="color: var(--color-success);"><?= tabung($rekap['tot_stok']) ?></div>
        <div class="stat-subtext">Tabung kosong: <?= tabung($rekap['tot_kosong']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><span>Nilai Aset Stok (Modal)</span></div>
        <div class="stat-value num"><?= rupiah($rekap['tot_aset_modal']) ?></div>
        <div class="stat-subtext"><?= $rekap['tot_produk'] ?> varian produk LPG</div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><span>Potensi Omzet Jual</span></div>
        <div class="stat-value num" style="color: var(--color-primary);"><?= rupiah($rekap['tot_potensi_omzet']) ?></div>
        <div class="stat-subtext">Produk stok menipis: <?= $rekap['tot_menipis'] ?></div>
    </div>
</div>

<?php elseif ($jenisLaporan === 'subsidi_pelanggan'): ?>
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header"><span>Pelanggan Terlayani</span></div>
        <div class="stat-value num"><?= $rekap['tot_penerima'] ?> Orang/Usaha</div>
        <div class="stat-subtext">Pembeli LPG 3 kg Bersubsidi</div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><span>Total Tabung 3 kg Tersalur</span></div>
        <div class="stat-value num" style="color: var(--color-primary);"><?= tabung($rekap['tot_tabung']) ?></div>
        <div class="stat-subtext"><?= tanggal_indo($startDate) ?> &ndash; <?= tanggal_indo($endDate) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><span>Nilai Penyaluran Subsidi</span></div>
        <div class="stat-value num" style="color: var(--color-success);"><?= rupiah($rekap['tot_nominal']) ?></div>
        <div class="stat-subtext">Total transaksi LPG 3 kg</div>
    </div>
</div>

<?php elseif ($jenisLaporan === 'mutasi'): ?>
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header"><span>Total Aktivitas Mutasi</span></div>
        <div class="stat-value num"><?= $rekap['tot_log'] ?> Catatan</div>
        <div class="stat-subtext"><?= tanggal_indo($startDate) ?> &ndash; <?= tanggal_indo($endDate) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><span>Total Penambahan Stok</span></div>
        <div class="stat-value num" style="color: var(--color-success);">+<?= tabung($rekap['tot_masuk']) ?></div>
        <div class="stat-subtext">Pasokan & pembatalan jual</div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><span>Total Pengurangan Stok</span></div>
        <div class="stat-value num" style="color: var(--color-danger);">-<?= tabung($rekap['tot_keluar']) ?></div>
        <div class="stat-subtext">Penjualan & koreksi stok</div>
    </div>
</div>
<?php endif; ?>

<!-- Tabel Data Laporan -->
<div class="card">
    <div class="card-header">
        <div class="card-title"><?= e($judulMap[$jenisLaporan]) ?></div>
        <?php if ($jenisLaporan !== 'stok'): ?>
            <span style="font-size: 12px; color: var(--text-muted);">
                Periode: <?= tanggal_indo($startDate) ?> s/d <?= tanggal_indo($endDate) ?>
            </span>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table-custom">
                <?php if ($jenisLaporan === 'penjualan'): ?>
                    <thead>
                        <tr>
                            <th style="width: 45px;">No</th>
                            <th>No. Resi</th>
                            <th>Tanggal</th>
                            <th>Pelanggan / NIK</th>
                            <th>Produk LPG</th>
                            <th class="num">Qty</th>
                            <th class="num">Total</th>
                            <th>Petugas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reportData)): ?>
                            <tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 24px;">Tidak ada data penjualan pada periode ini.</td></tr>
                        <?php else: ?>
                            <?php foreach ($reportData as $i => $r): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><code>TRX-<?= str_pad((string)$r['id'], 5, '0', STR_PAD_LEFT) ?></code></td>
                                <td><?= tanggal_indo($r['tanggal_penjualan']) ?></td>
                                <td>
                                    <div style="font-weight: 600;"><?= e($r['nama_pelanggan']) ?></div>
                                    <div style="font-size: 11px; color: var(--text-muted);">
                                        NIK: <?= e($r['nik'] ?: '-') ?> &bull; <?= e($r['alamat_pelanggan']) ?>
                                    </div>
                                </td>
                                <td><?= e($r['nama_produk']) ?> <span class="badge badge-neutral" style="font-size: 10px;"><?= e($r['ukuran']) ?></span></td>
                                <td class="num" style="font-weight: 600;"><?= tabung($r['jumlah']) ?></td>
                                <td class="num" style="font-weight: 700; color: var(--color-primary);"><?= rupiah($r['total']) ?></td>
                                <td style="font-size: 12px; color: var(--text-muted);"><?= e($r['nama_petugas']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>

                <?php elseif ($jenisLaporan === 'lpg_masuk'): ?>
                    <thead>
                        <tr>
                            <th style="width: 45px;">No</th>
                            <th>Tanggal Masuk</th>
                            <th>Agen Pemasok</th>
                            <th>Produk LPG</th>
                            <th class="num">Jumlah</th>
                            <th class="num">Nilai Beli</th>
                            <th>Keterangan</th>
                            <th>Petugas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reportData)): ?>
                            <tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 24px;">Tidak ada data pasokan masuk pada periode ini.</td></tr>
                        <?php else: ?>
                            <?php foreach ($reportData as $i => $r): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= tanggal_indo($r['tanggal_masuk']) ?></td>
                                <td style="font-weight: 600;"><?= e($r['nama_pemasok']) ?></td>
                                <td><?= e($r['nama_produk']) ?> <span class="badge badge-neutral" style="font-size: 10px;"><?= e($r['ukuran']) ?></span></td>
                                <td class="num" style="font-weight: 700; color: var(--color-success);">+<?= tabung($r['jumlah']) ?></td>
                                <td class="num"><?= rupiah((int)$r['jumlah'] * (float)$r['harga_beli']) ?></td>
                                <td style="font-size: 12px;"><?= e($r['keterangan'] ?: '-') ?></td>
                                <td style="font-size: 12px; color: var(--text-muted);"><?= e($r['nama_petugas']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>

                <?php elseif ($jenisLaporan === 'stok'): ?>
                    <thead>
                        <tr>
                            <th style="width: 45px;">No</th>
                            <th>Nama Produk</th>
                            <th>Ukuran</th>
                            <th class="num">Harga Beli</th>
                            <th class="num">Harga Jual</th>
                            <th class="num">Stok Isi</th>
                            <th class="num">Tabung Kosong</th>
                            <th class="num">Nilai Aset</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData as $i => $r):
                            $badge = get_stock_badge((int)$r['stok'], (int)$r['stok_minimum']);
                        ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td style="font-weight: 600;"><?= e($r['nama_produk']) ?></td>
                            <td><span class="badge badge-neutral"><?= e($r['ukuran']) ?></span></td>
                            <td class="num"><?= rupiah($r['harga_beli']) ?></td>
                            <td class="num"><?= rupiah($r['harga_jual']) ?></td>
                            <td class="num" style="font-weight: 700; color: var(--color-success);"><?= tabung($r['stok']) ?></td>
                            <td class="num" style="color: var(--text-muted);"><?= tabung($r['stok_kosong'] ?? 0) ?></td>
                            <td class="num" style="font-weight: 600;"><?= rupiah((int)$r['stok'] * (float)$r['harga_beli']) ?></td>
                            <td>
                                <span class="badge <?= $badge['class'] ?>">
                                    <span class="badge-dot"></span>
                                    <?= $badge['label'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>

                <?php elseif ($jenisLaporan === 'subsidi_pelanggan'): ?>
                    <thead>
                        <tr>
                            <th style="width: 45px;">No</th>
                            <th>Nama Pelanggan</th>
                            <th>NIK</th>
                            <th>Alamat & Telepon</th>
                            <th class="num">Frekuensi</th>
                            <th class="num">Total Tabung 3 kg</th>
                            <th class="num">Total Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reportData)): ?>
                            <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">Belum ada penyaluran LPG 3 kg pada periode ini.</td></tr>
                        <?php else: ?>
                            <?php foreach ($reportData as $i => $r): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td style="font-weight: 600;"><?= e($r['nama_pelanggan']) ?></td>
                                <td><code><?= e($r['nik'] ?: 'Belum diisi') ?></code></td>
                                <td>
                                    <div><?= e($r['alamat']) ?></div>
                                    <div style="font-size: 11px; color: var(--text-muted);"><?= e($r['no_telepon']) ?></div>
                                </td>
                                <td class="num"><?= $r['frekuensi_beli'] ?>x</td>
                                <td class="num" style="font-weight: 700; color: var(--color-primary);"><?= tabung($r['total_tabung']) ?></td>
                                <td class="num" style="font-weight: 600;"><?= rupiah($r['total_belanja']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>

                <?php elseif ($jenisLaporan === 'mutasi'): ?>
                    <thead>
                        <tr>
                            <th style="width: 45px;">No</th>
                            <th>Tanggal</th>
                            <th>Produk</th>
                            <th>Jenis</th>
                            <th class="num">Jumlah</th>
                            <th class="num">Stok Awal</th>
                            <th class="num">Stok Akhir</th>
                            <th>Keterangan</th>
                            <th>Petugas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reportData)): ?>
                            <tr><td colspan="9" style="text-align: center; color: var(--text-muted); padding: 24px;">Belum ada catatan mutasi stok pada periode ini.</td></tr>
                        <?php else: ?>
                            <?php foreach ($reportData as $i => $r): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= tanggal_indo(date('Y-m-d', strtotime($r['created_at']))) ?></td>
                                <td style="font-weight: 600;"><?= e($r['nama_produk']) ?></td>
                                <td>
                                    <span class="badge <?= ($r['jenis'] ?? '') === 'masuk' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= strtoupper(e($r['jenis'] ?? '-')) ?>
                                    </span>
                                </td>
                                <td class="num" style="font-weight: 700;"><?= tabung($r['jumlah'] ?? 0) ?></td>
                                <td class="num"><?= tabung($r['stok_sebelum'] ?? 0) ?></td>
                                <td class="num"><?= tabung($r['stok_sesudah'] ?? 0) ?></td>
                                <td style="font-size: 12px;"><?= e($r['keterangan'] ?: '-') ?></td>
                                <td style="font-size: 12px; color: var(--text-muted);"><?= e($r['nama_petugas']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>