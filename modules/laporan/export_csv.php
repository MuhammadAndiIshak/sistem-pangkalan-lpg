<?php
/**
 * Ekspor Laporan ke File CSV
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/../../config/app.php';
require_login();

$pdo = get_db();

$jenisLaporan = $_GET['jenis'] ?? 'penjualan';
if (!in_array($jenisLaporan, ['stok', 'lpg_masuk', 'penjualan', 'mutasi'], true)) {
    $jenisLaporan = 'penjualan';
}

$startDate = trim($_GET['start_date'] ?? date('Y-m-01'));
$endDate = trim($_GET['end_date'] ?? date('Y-m-d'));

$filename = sprintf('Laporan_%s_%s.csv', $jenisLaporan, date('Ymd_His'));

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// UTF-8 BOM untuk kompatibilitas penuh dengan Microsoft Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

if ($jenisLaporan === 'stok') {
    fputcsv($output, ['No', 'Nama Produk', 'Ukuran', 'Harga Beli (Modal)', 'Harga Jual (HET)', 'Stok Fisik', 'Stok Minimum', 'Status Stok']);
    
    $stmt = $pdo->query('SELECT * FROM produk ORDER BY nama_produk ASC');
    $rows = $stmt->fetchAll();
    
    foreach ($rows as $i => $r) {
        $badge = get_stock_badge((int)$r['stok'], (int)$r['stok_minimum']);
        fputcsv($output, [
            $i + 1,
            $r['nama_produk'],
            $r['ukuran'],
            $r['harga_beli'],
            $r['harga_jual'],
            $r['stok'],
            $r['stok_minimum'],
            $badge['label']
        ]);
    }

} elseif ($jenisLaporan === 'lpg_masuk') {
    fputcsv($output, ['No', 'Tanggal Masuk', 'Agen Pemasok', 'Produk Gas LPG', 'Ukuran', 'Jumlah Tabung', 'Harga Beli Satuan', 'Total Nilai Modal', 'Keterangan DO']);

    $stmt = $pdo->prepare('
        SELECT lm.*, p.nama_produk, p.ukuran, p.harga_beli, pm.nama_pemasok
        FROM lpg_masuk lm
        JOIN produk p ON lm.produk_id = p.id
        JOIN pemasok pm ON lm.pemasok_id = pm.id
        WHERE lm.tanggal_masuk BETWEEN :start AND :end
        ORDER BY lm.tanggal_masuk ASC, lm.id ASC
    ');
    $stmt->execute([':start' => $startDate, ':end' => $endDate]);
    $rows = $stmt->fetchAll();

    foreach ($rows as $i => $r) {
        $totalModal = (int)$r['jumlah'] * (float)$r['harga_beli'];
        fputcsv($output, [
            $i + 1,
            $r['tanggal_masuk'],
            $r['nama_pemasok'],
            $r['nama_produk'],
            $r['ukuran'],
            $r['jumlah'],
            $r['harga_beli'],
            $totalModal,
            $r['keterangan'] ?? ''
        ]);
    }

} elseif ($jenisLaporan === 'penjualan') {
    fputcsv($output, ['No', 'No. Resi', 'Tanggal Transaksi', 'Nama Pelanggan', 'Alamat Pelanggan', 'Produk Gas LPG', 'Ukuran', 'Harga Satuan', 'Jumlah Tabung', 'Total Pembayaran']);

    $stmt = $pdo->prepare('
        SELECT pj.*, p.nama_produk, p.ukuran, pl.nama_pelanggan, pl.alamat AS alamat_pelanggan
        FROM penjualan pj
        JOIN produk p ON pj.produk_id = p.id
        JOIN pelanggan pl ON pj.pelanggan_id = pl.id
        WHERE pj.tanggal_penjualan BETWEEN :start AND :end
        ORDER BY pj.tanggal_penjualan ASC, pj.id ASC
    ');
    $stmt->execute([':start' => $startDate, ':end' => $endDate]);
    $rows = $stmt->fetchAll();

    foreach ($rows as $i => $r) {
        fputcsv($output, [
            $i + 1,
            'TRX-' . str_pad($r['id'], 5, '0', STR_PAD_LEFT),
            $r['tanggal_penjualan'],
            $r['nama_pelanggan'],
            $r['alamat_pelanggan'],
            $r['nama_produk'],
            $r['ukuran'],
            $r['harga'],
            $r['jumlah'],
            $r['total']
        ]);
    }

} elseif ($jenisLaporan === 'mutasi') {
    fputcsv($output, ['No', 'Tanggal', 'Jenis Mutasi', 'Produk Gas LPG', 'Ukuran', 'Mitra / Pihak Terkait', 'Perubahan Stok (Qty)', 'Catatan']);

    $stmt = $pdo->prepare("
        SELECT 
            'MASUK' AS tipe,
            lm.tanggal_masuk AS tanggal,
            p.nama_produk,
            p.ukuran,
            lm.jumlah,
            pm.nama_pemasok AS pihak,
            lm.keterangan AS catatan
        FROM lpg_masuk lm
        JOIN produk p ON lm.produk_id = p.id
        JOIN pemasok pm ON lm.pemasok_id = pm.id
        WHERE lm.tanggal_masuk BETWEEN :start1 AND :end1

        UNION ALL

        SELECT 
            'KELUAR' AS tipe,
            pj.tanggal_penjualan AS tanggal,
            p.nama_produk,
            p.ukuran,
            pj.jumlah,
            pl.nama_pelanggan AS pihak,
            CONCAT('Total Belanja Rp ', FORMAT(pj.total, 0, 'id_ID')) AS catatan
        FROM penjualan pj
        JOIN produk p ON pj.produk_id = p.id
        JOIN pelanggan pl ON pj.pelanggan_id = pl.id
        WHERE pj.tanggal_penjualan BETWEEN :start2 AND :end2

        ORDER BY tanggal ASC
    ");
    $stmt->execute([
        ':start1' => $startDate, ':end1' => $endDate,
        ':start2' => $startDate, ':end2' => $endDate
    ]);
    $rows = $stmt->fetchAll();

    foreach ($rows as $i => $r) {
        $qtyFormat = ($r['tipe'] === 'MASUK' ? '+' : '-') . $r['jumlah'];
        fputcsv($output, [
            $i + 1,
            $r['tanggal'],
            $r['tipe'],
            $r['nama_produk'],
            $r['ukuran'],
            $r['pihak'],
            $qtyFormat,
            $r['catatan'] ?? ''
        ]);
    }
}

fclose($output);
exit;
