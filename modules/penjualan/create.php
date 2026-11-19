<?php
/**
 * Input Transaksi Penjualan Gas LPG
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/../../config/app.php';
require_role('admin');

$pageTitle = 'Input Transaksi Penjualan';
$pdo = get_db();

// Ambil daftar produk dengan stok dan harga
$produkList = $pdo->query('SELECT id, nama_produk, ukuran, harga_jual, stok FROM produk ORDER BY nama_produk ASC')->fetchAll();
// Ambil daftar pelanggan
$pelangganList = $pdo->query('SELECT id, nama_pelanggan, alamat FROM pelanggan ORDER BY nama_pelanggan ASC')->fetchAll();

$errors = [];
$old = [
    'produk_id'        => '',
    'pelanggan_id'     => '',
    'tanggal_penjualan'=> date('Y-m-d'),
    'jumlah'           => '1',
    'harga'            => '',
    'total'            => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $produk_id         = (int)($_POST['produk_id'] ?? 0);
    $pelanggan_id      = (int)($_POST['pelanggan_id'] ?? 0);
    $tanggal_penjualan = trim($_POST['tanggal_penjualan'] ?? '');
    $jumlah            = (int)($_POST['jumlah'] ?? 0);
    $harga             = (float)($_POST['harga'] ?? 0);

    $old = [
        'produk_id'        => $produk_id,
        'pelanggan_id'     => $pelanggan_id,
        'tanggal_penjualan'=> $tanggal_penjualan,
        'jumlah'           => $_POST['jumlah'] ?? '',
        'harga'            => $_POST['harga'] ?? '',
        'total'            => $_POST['total'] ?? ''
    ];

    if ($produk_id <= 0) {
        $errors['produk_id'] = 'Pilih salah satu produk gas LPG.';
    }
    if ($pelanggan_id <= 0) {
        $errors['pelanggan_id'] = 'Pilih pelanggan yang membeli.';
    }
    if ($tanggal_penjualan === '' || !strtotime($tanggal_penjualan)) {
        $errors['tanggal_penjualan'] = 'Format tanggal penjualan tidak valid.';
    }
    if ($jumlah <= 0) {
        $errors['jumlah'] = 'Jumlah pembelian tabung harus lebih dari 0.';
    }
    if ($harga < 0) {
        $errors['harga'] = 'Harga satuan tidak boleh bernilai negatif.';
    }

if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $lockStmt = $pdo->prepare('SELECT nama_produk, is_subsidi, stok FROM produk WHERE id = :id FOR UPDATE');
            $lockStmt->execute([':id' => $produk_id]);
            $currentProd = $lockStmt->fetch();

            if (!$currentProd) {
                throw new Exception('Produk tidak ditemukan di database.');
            }

            if ((int)$currentProd['stok'] < $jumlah) {
                throw new Exception("Stok untuk {$currentProd['nama_produk']} tidak mencukupi! Tersedia hanya {$currentProd['stok']} tabung, permintaan transaksi {$jumlah} tabung.");
            }

            // Validasi NIK & Kuota Bulanan khusus LPG 3 kg Bersubsidi
            if ((int)$currentProd['is_subsidi'] === 1) {
                $plStmt = $pdo->prepare('SELECT nama_pelanggan, nik, kategori, kuota_bulanan FROM pelanggan WHERE id = :plid LIMIT 1');
                $plStmt->execute([':plid' => $pelanggan_id]);
                $pelangganData = $plStmt->fetch();

                if (!$pelangganData) {
                    throw new Exception('Data pelanggan tidak ditemukan.');
                }
                if (empty($pelangganData['nik']) || !preg_match('/^[0-9]{16}$/', $pelangganData['nik'])) {
                    throw new Exception("Pelanggan '{$pelangganData['nama_pelanggan']}' belum memiliki NIK 16 digit yang valid. Lengkapi NIK di menu Data Pelanggan sebelum membeli LPG 3 kg bersubsidi.");
                }
                if ($pelangganData['kategori'] === 'Industri/Komersial' || (int)$pelangganData['kuota_bulanan'] <= 0) {
                    throw new Exception("Pelanggan kategori '{$pelangganData['kategori']}' tidak berhak membeli LPG 3 kg bersubsidi. Gunakan tabung non-subsidi (Bright Gas / 50 kg).");
                }

                $kuotaStmt = $pdo->prepare('
                    SELECT COALESCE(SUM(pj.jumlah), 0)
                    FROM penjualan pj
                    JOIN produk p ON pj.produk_id = p.id
                    WHERE pj.pelanggan_id = :plid
                      AND p.is_subsidi = 1
                      AND YEAR(pj.tanggal_penjualan) = :tahun
                      AND MONTH(pj.tanggal_penjualan) = :bulan
                ');
                $tsTrx = strtotime($tanggal_penjualan);
                $kuotaStmt->execute([
                    ':plid'  => $pelanggan_id,
                    ':tahun' => (int)date('Y', $tsTrx),
                    ':bulan' => (int)date('m', $tsTrx)
                ]);
                $sudahTerpakai = (int)$kuotaStmt->fetchColumn();
                $kuotaMaks = (int)$pelangganData['kuota_bulanan'];
                $sisaKuota = max(0, $kuotaMaks - $sudahTerpakai);

                if ($jumlah > $sisaKuota) {
                    throw new Exception("Melebihi kuota bulanan LPG 3 kg! Pelanggan '{$pelangganData['nama_pelanggan']}' ({$pelangganData['kategori']}) memiliki kuota {$kuotaMaks} tabung/bulan dan sudah menggunakan {$sudahTerpakai} tabung (sisa kuota: {$sisaKuota} tabung).");
                }
            }

            $calculatedTotal = $jumlah * $harga;
            $tukarTabung = isset($_POST['tukar_tabung']) && $_POST['tukar_tabung'] === '0' ? 0 : 1;
            $kasirId = (int)(current_user()['id'] ?? 0) ?: null;

            $insertStmt = $pdo->prepare('
                INSERT INTO penjualan (produk_id, pelanggan_id, user_id, tanggal_penjualan, jumlah, harga, total, tukar_tabung)
                VALUES (:pid, :plid, :uid, :tgl, :jml, :hrg, :tot, :tukar)
            ');
            $insertStmt->execute([
                ':pid'   => $produk_id,
                ':plid'  => $pelanggan_id,
                ':uid'   => $kasirId,
                ':tgl'   => $tanggal_penjualan,
                ':jml'   => $jumlah,
                ':hrg'   => $harga,
                ':tot'   => $calculatedTotal,
                ':tukar' => $tukarTabung
            ]);
            $penjualanId = (int)$pdo->lastInsertId();

            $deductStmt = $pdo->prepare('
                UPDATE produk
                SET stok = stok - :jml,
                    stok_kosong = stok_kosong + :kosong
                WHERE id = :id
            ');
            $deductStmt->execute([
                ':jml'    => $jumlah,
                ':kosong' => ($tukarTabung === 1 ? $jumlah : 0),
                ':id'     => $produk_id
            ]);

            $sisaStok = (int)$currentProd['stok'] - $jumlah;
            $pdo->commit();

            set_flash('success', "Transaksi penjualan berhasil disimpan! Sisa stok '{$currentProd['nama_produk']}': " . tabung($sisaStok) . ".");
            redirect('modules/penjualan/nota.php?id=' . $penjualanId);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            log_app_error('Gagal memproses penjualan: ' . $e->getMessage());
            $errors['general'] = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <div class="page-heading">
        <h1 class="page-title">Input Transaksi Penjualan LPG</h1>
        <p class="page-subtitle">Pencatatan distribusi tabung gas ke warga/UMKM dengan pembaruan stok otomatis.</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('modules/penjualan/index.php') ?>" class="btn btn-secondary">
            <?= icon('arrow-left', '', 14) ?>
            <span>Kembali</span>
        </a>
    </div>
</div>

<div class="card" style="max-width: 720px;">
    <div class="card-header">
        <div class="card-title">Formulir Kasir Penjualan</div>
    </div>
    <div class="card-body">
        <?php if (!empty($errors['general'])): ?>
            <div class="alert-banner" style="background: var(--color-danger-light); color: var(--color-danger-text); border-color: #fecaca; margin-bottom: 20px;">
                <div class="alert-banner-content">
                    <?= icon('alert-triangle') ?>
                    <span><?= e($errors['general']) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Warning Live JS jika stok tidak mencukupi -->
        <div id="stock_warning_alert" class="alert-banner" style="display: none; background: var(--color-danger-light); color: var(--color-danger-text); border-color: #fecaca; margin-bottom: 20px;">
            <div class="alert-banner-content">
                <?= icon('alert-triangle') ?>
                <span id="stock_warning_text">Jumlah melebihi stok yang tersedia!</span>
            </div>
        </div>

        <form method="POST" action="">
            <?= csrf_field() ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="penjualan_produk_id" class="form-label">Produk Gas LPG <span class="required">*</span></label>
                    <select id="penjualan_produk_id" name="produk_id" class="form-control" required>
                        <option value="">-- Pilih Produk LPG --</option>
                        <?php foreach ($produkList as $p): ?>
                            <option value="<?= $p['id'] ?>" 
                                    data-harga="<?= $p['harga_jual'] ?>" 
                                    data-stok="<?= $p['stok'] ?>"
                                    <?= (int)$old['produk_id'] === (int)$p['id'] ? 'selected' : '' ?>>
                                <?= e($p['nama_produk']) ?> (<?= e($p['ukuran']) ?>) - Rp <?= number_format($p['harga_jual'], 0, ',', '.') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="produk_stock_info" class="form-text" style="color: var(--color-primary); font-weight: 500;"></div>
                    <?php if (!empty($errors['produk_id'])): ?>
                        <div class="form-error"><?= e($errors['produk_id']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="pelanggan_id" class="form-label">Pelanggan Pembeli <span class="required">*</span></label>
                    <select id="pelanggan_id" name="pelanggan_id" class="form-control" required>
                        <option value="">-- Pilih Pelanggan --</option>
                        <?php foreach ($pelangganList as $pl): ?>
                            <option value="<?= $pl['id'] ?>" <?= (int)$old['pelanggan_id'] === (int)$pl['id'] ? 'selected' : '' ?>>
                                <?= e($pl['nama_pelanggan']) ?> (<?= e($pl['alamat']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['pelanggan_id'])): ?>
                        <div class="form-error"><?= e($errors['pelanggan_id']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="tanggal_penjualan" class="form-label">Tanggal Transaksi <span class="required">*</span></label>
                    <input type="date" id="tanggal_penjualan" name="tanggal_penjualan" class="form-control" 
                           value="<?= e($old['tanggal_penjualan']) ?>" required>
                    <?php if (!empty($errors['tanggal_penjualan'])): ?>
                        <div class="form-error"><?= e($errors['tanggal_penjualan']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="penjualan_jumlah" class="form-label">Jumlah Pembelian (Tabung) <span class="required">*</span></label>
                    <input type="number" id="penjualan_jumlah" name="jumlah" class="form-control tabular-nums" 
                           value="<?= e($old['jumlah']) ?>" min="1" required>
                    <?php if (!empty($errors['jumlah'])): ?>
                        <div class="form-error"><?= e($errors['jumlah']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="penjualan_harga" class="form-label">Harga Satuan (Rp) <span class="required">*</span></label>
                    <input type="number" id="penjualan_harga" name="harga" class="form-control tabular-nums" 
                           value="<?= e($old['harga']) ?>" min="0" step="500" required>
                    <div class="form-text">Terisi otomatis sesuai HET produk, dapat disesuaikan jika ada diskon/penyesuaian.</div>
                    <?php if (!empty($errors['harga'])): ?>
                        <div class="form-error"><?= e($errors['harga']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label">Total Pembayaran</label>
                    <input type="hidden" id="penjualan_total" name="total" value="<?= e($old['total']) ?>">
                    <div id="display_penjualan_total" style="font-size: 20px; font-weight: 700; color: var(--color-primary); padding: 8px 12px; background: var(--color-primary-light); border-radius: var(--radius-md); border: 1px solid var(--color-primary-border); height: 42px; display: flex; align-items: center;">
                        Rp 0
                    </div>
                    <div class="form-text">Dihitung otomatis: Jumlah &times; Harga Satuan</div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" id="btn_submit_penjualan" class="btn btn-primary">
                    <?= icon('check', '', 14) ?>
                    <span>Simpan & Buat Struk</span>
                </button>
                <a href="<?= url('modules/penjualan/index.php') ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
