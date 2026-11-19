<?php
/**
 * Catat Pasokan LPG Masuk
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/../../config/app.php';
require_role('admin');

$pageTitle = 'Catat Pasokan LPG Masuk';
$pdo = get_db();

// Ambil data produk dan pemasok untuk opsi dropdown
$produkList = $pdo->query('SELECT id, nama_produk, ukuran, stok FROM produk ORDER BY nama_produk ASC')->fetchAll();
$pemasokList = $pdo->query('SELECT id, nama_pemasok FROM pemasok ORDER BY nama_pemasok ASC')->fetchAll();

$errors = [];
$old = [
    'produk_id'     => '',
    'pemasok_id'    => '',
    'tanggal_masuk' => date('Y-m-d'),
    'jumlah'        => '',
    'keterangan'    => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $produk_id     = (int)($_POST['produk_id'] ?? 0);
    $pemasok_id    = (int)($_POST['pemasok_id'] ?? 0);
    $tanggal_masuk = trim($_POST['tanggal_masuk'] ?? '');
    $jumlah        = (int)($_POST['jumlah'] ?? 0);
    $keterangan    = trim($_POST['keterangan'] ?? '');

    $old = [
        'produk_id'     => $produk_id,
        'pemasok_id'    => $pemasok_id,
        'tanggal_masuk' => $tanggal_masuk,
        'jumlah'        => $_POST['jumlah'] ?? '',
        'keterangan'    => $keterangan
    ];

    if ($produk_id <= 0) {
        $errors['produk_id'] = 'Pilih salah satu produk gas LPG.';
    }
    if ($pemasok_id <= 0) {
        $errors['pemasok_id'] = 'Pilih agen pemasok yang mendistribusikan.';
    }
    if ($tanggal_masuk === '' || !strtotime($tanggal_masuk)) {
        $errors['tanggal_masuk'] = 'Format tanggal masuk tidak valid.';
    }
    if ($jumlah <= 0) {
        $errors['jumlah'] = 'Jumlah tabung masuk harus lebih dari 0.';
    }

    if (empty($errors)) {
        try {
            // Memulai DB Transaction untuk memastikan integritas data dan atomisitas
            $pdo->beginTransaction();

            // 1. Simpan ke tabel lpg_masuk
            $insertStmt = $pdo->prepare('
                INSERT INTO lpg_masuk (produk_id, pemasok_id, tanggal_masuk, jumlah, keterangan)
                VALUES (:produk_id, :pemasok_id, :tanggal, :jumlah, :ket)
            ');
            $insertStmt->execute([
                ':produk_id' => $produk_id,
                ':pemasok_id'=> $pemasok_id,
                ':tanggal'   => $tanggal_masuk,
                ':jumlah'    => $jumlah,
                ':ket'       => $keterangan !== '' ? $keterangan : null
            ]);

            // 2. Tambah stok produk secara otomatis
            $updateStmt = $pdo->prepare('
                UPDATE produk 
                SET stok = stok + :jumlah 
                WHERE id = :produk_id
            ');
            $updateStmt->execute([
                ':jumlah'    => $jumlah,
                ':produk_id' => $produk_id
            ]);

            // 3. Ambil data produk dengan stok terbaru
            $stockStmt = $pdo->prepare('SELECT nama_produk, stok FROM produk WHERE id = :produk_id');
            $stockStmt->execute([':produk_id' => $produk_id]);
            $updatedProduk = $stockStmt->fetch();

            $pdo->commit();

            set_flash('success', "Pasokan {$jumlah} tabung '{$updatedProduk['nama_produk']}' berhasil ditambahkan! Stok terbaru di pangkalan kini: " . tabung($updatedProduk['stok']) . ".");
            redirect('modules/lpg_masuk/index.php');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            log_app_error('Gagal mencatat LPG Masuk: ' . $e->getMessage());
            $errors['general'] = 'Terjadi kegagalan transaksi database. Data tidak tersimpan.';
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <div class="page-heading">
        <h1 class="page-title">Pencatatan Penerimaan LPG Masuk</h1>
        <p class="page-subtitle">Input kiriman tabung gas baru dari agen resmi untuk menambah persediaan stok fisik pangkalan.</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('modules/lpg_masuk/index.php') ?>" class="btn btn-secondary">
            <?= icon('arrow-left', '', 14) ?>
            <span>Kembali</span>
        </a>
    </div>
</div>

<div class="card" style="max-width: 680px;">
    <div class="card-header">
        <div class="card-title">Formulir Penerimaan Pasokan</div>
    </div>
    <div class="card-body">
        <?php if (!empty($errors['general'])): ?>
            <div class="alert-banner" style="background: var(--color-danger-light); color: var(--color-danger-text); border-color: #fecaca; margin-bottom: 16px;">
                <div class="alert-banner-content">
                    <?= icon('alert-triangle') ?>
                    <span><?= e($errors['general']) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <?= csrf_field() ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="produk_id" class="form-label">Produk Gas LPG <span class="required">*</span></label>
                    <select id="produk_id" name="produk_id" class="form-control" required>
                        <option value="">-- Pilih Produk LPG --</option>
                        <?php foreach ($produkList as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= (int)$old['produk_id'] === (int)$p['id'] ? 'selected' : '' ?>>
                                <?= e($p['nama_produk']) ?> (<?= e($p['ukuran']) ?>) - Stok saat ini: <?= tabung($p['stok']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['produk_id'])): ?>
                        <div class="form-error"><?= e($errors['produk_id']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="pemasok_id" class="form-label">Agen Pemasok <span class="required">*</span></label>
                    <select id="pemasok_id" name="pemasok_id" class="form-control" required>
                        <option value="">-- Pilih Agen Pertamina --</option>
                        <?php foreach ($pemasokList as $pm): ?>
                            <option value="<?= $pm['id'] ?>" <?= (int)$old['pemasok_id'] === (int)$pm['id'] ? 'selected' : '' ?>>
                                <?= e($pm['nama_pemasok']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['pemasok_id'])): ?>
                        <div class="form-error"><?= e($errors['pemasok_id']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="tanggal_masuk" class="form-label">Tanggal Masuk / Penerimaan <span class="required">*</span></label>
                    <input type="date" id="tanggal_masuk" name="tanggal_masuk" class="form-control" 
                           value="<?= e($old['tanggal_masuk']) ?>" required>
                    <?php if (!empty($errors['tanggal_masuk'])): ?>
                        <div class="form-error"><?= e($errors['tanggal_masuk']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="jumlah" class="form-label">Jumlah Tabung Masuk <span class="required">*</span></label>
                    <input type="number" id="jumlah" name="jumlah" class="form-control tabular-nums" 
                           placeholder="Contoh: 150" value="<?= e($old['jumlah']) ?>" min="1" required>
                    <div class="form-text">Stok produk terkait akan langsung bertambah sesuai angka ini.</div>
                    <?php if (!empty($errors['jumlah'])): ?>
                        <div class="form-error"><?= e($errors['jumlah']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="keterangan" class="form-label">Keterangan / Nomor Delivery Order (DO)</label>
                <textarea id="keterangan" name="keterangan" class="form-control" rows="3" 
                          placeholder="Contoh: No DO: DO-PLU-2026/11/008 - Alokasi reguler SPPBE Mamboro"><?= e($old['keterangan']) ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= icon('check', '', 14) ?>
                    <span>Simpan & Tambah Stok</span>
                </button>
                <a href="<?= url('modules/lpg_masuk/index.php') ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
