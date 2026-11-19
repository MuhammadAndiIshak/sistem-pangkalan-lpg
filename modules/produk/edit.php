<?php
/**
 * Ubah Data Produk LPG
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/../../config/app.php';
require_role('admin');

$pageTitle = 'Ubah Data Produk';
$pdo = get_db();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM produk WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$produk = $stmt->fetch();

if (!$produk) {
    set_flash('danger', 'Data produk tidak ditemukan.');
    redirect('modules/produk/index.php');
}

$errors = [];
$old = [
    'nama_produk'  => $produk['nama_produk'],
    'ukuran'       => $produk['ukuran'],
    'harga_beli'   => $produk['harga_beli'],
    'harga_jual'   => $produk['harga_jual'],
    'stok'         => $produk['stok'],
    'stok_minimum' => $produk['stok_minimum']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $nama_produk  = trim($_POST['nama_produk'] ?? '');
    $ukuran       = trim($_POST['ukuran'] ?? '');
    $harga_beli   = (float)($_POST['harga_beli'] ?? 0);
    $harga_jual   = (float)($_POST['harga_jual'] ?? 0);
    $stok         = (int)($_POST['stok'] ?? 0);
    $stok_minimum = (int)($_POST['stok_minimum'] ?? 0);

    $old = [
        'nama_produk'  => $nama_produk,
        'ukuran'       => $ukuran,
        'harga_beli'   => $_POST['harga_beli'] ?? '',
        'harga_jual'   => $_POST['harga_jual'] ?? '',
        'stok'         => $stok,
        'stok_minimum' => $stok_minimum
    ];

    if ($nama_produk === '') {
        $errors['nama_produk'] = 'Nama produk wajib diisi.';
    }
    if ($ukuran === '') {
        $errors['ukuran'] = 'Ukuran tabung gas wajib diisi.';
    }
    if ($harga_beli < 0) {
        $errors['harga_beli'] = 'Harga beli modal tidak boleh bernilai negatif.';
    }
    if ($harga_jual < 0) {
        $errors['harga_jual'] = 'Harga jual tidak boleh bernilai negatif.';
    }
    if ($stok < 0) {
        $errors['stok'] = 'Jumlah stok tidak boleh bernilai negatif.';
    }
    if ($stok_minimum < 0) {
        $errors['stok_minimum'] = 'Batas stok minimum tidak boleh bernilai negatif.';
    }

    if (empty($errors)) {
        try {
            $updateStmt = $pdo->prepare('
                UPDATE produk 
                SET nama_produk = :nama, ukuran = :ukuran, harga_beli = :harga_beli, 
                    harga_jual = :harga_jual, stok = :stok, stok_minimum = :stok_min
                WHERE id = :id
            ');
            $updateStmt->execute([
                ':nama'       => $nama_produk,
                ':ukuran'     => $ukuran,
                ':harga_beli' => $harga_beli,
                ':harga_jual' => $harga_jual,
                ':stok'       => $stok,
                ':stok_min'   => $stok_minimum,
                ':id'         => $id
            ]);

            set_flash('success', "Data produk '{$nama_produk}' berhasil diperbarui!");
            redirect('modules/produk/index.php');
        } catch (PDOException $e) {
            log_app_error('Gagal update produk: ' . $e->getMessage());
            $errors['general'] = 'Terjadi kesalahan sistem saat memperbarui data produk.';
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <div class="page-heading">
        <h1 class="page-title">Ubah Data Produk LPG</h1>
        <p class="page-subtitle">Perbarui rincian informasi tabung gas dan ketentuan harga pangkalan.</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('modules/produk/index.php') ?>" class="btn btn-secondary">
            <?= icon('arrow-left', '', 14) ?>
            <span>Kembali</span>
        </a>
    </div>
</div>

<div class="card" style="max-width: 680px;">
    <div class="card-header">
        <div class="card-title">Edit Data: <?= e($produk['nama_produk']) ?></div>
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
                    <label for="nama_produk" class="form-label">Nama Produk <span class="required">*</span></label>
                    <input type="text" id="nama_produk" name="nama_produk" class="form-control" 
                           value="<?= e($old['nama_produk']) ?>" required>
                    <?php if (!empty($errors['nama_produk'])): ?>
                        <div class="form-error"><?= e($errors['nama_produk']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="ukuran" class="form-label">Ukuran Tabung <span class="required">*</span></label>
                    <input type="text" id="ukuran" name="ukuran" class="form-control" 
                           value="<?= e($old['ukuran']) ?>" required>
                    <?php if (!empty($errors['ukuran'])): ?>
                        <div class="form-error"><?= e($errors['ukuran']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="harga_beli" class="form-label">Harga Beli Modal (Rp) <span class="required">*</span></label>
                    <input type="number" id="harga_beli" name="harga_beli" class="form-control tabular-nums" 
                           value="<?= e($old['harga_beli']) ?>" min="0" step="500" required>
                    <?php if (!empty($errors['harga_beli'])): ?>
                        <div class="form-error"><?= e($errors['harga_beli']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="harga_jual" class="form-label">Harga Jual / HET (Rp) <span class="required">*</span></label>
                    <input type="number" id="harga_jual" name="harga_jual" class="form-control tabular-nums" 
                           value="<?= e($old['harga_jual']) ?>" min="0" step="500" required>
                    <?php if (!empty($errors['harga_jual'])): ?>
                        <div class="form-error"><?= e($errors['harga_jual']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="stok" class="form-label">Jumlah Stok (Tabung) <span class="required">*</span></label>
                    <input type="number" id="stok" name="stok" class="form-control tabular-nums" 
                           value="<?= e($old['stok']) ?>" min="0" required>
                    <?php if (!empty($errors['stok'])): ?>
                        <div class="form-error"><?= e($errors['stok']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="stok_minimum" class="form-label">Batas Stok Minimum (Tabung) <span class="required">*</span></label>
                    <input type="number" id="stok_minimum" name="stok_minimum" class="form-control tabular-nums" 
                           value="<?= e($old['stok_minimum']) ?>" min="0" required>
                    <?php if (!empty($errors['stok_minimum'])): ?>
                        <div class="form-error"><?= e($errors['stok_minimum']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= icon('check', '', 14) ?>
                    <span>Perbarui Produk</span>
                </button>
                <a href="<?= url('modules/produk/index.php') ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
