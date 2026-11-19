<?php
/**
 * Tambah Produk Baru
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/../../config/app.php';
require_role('admin');

$pageTitle = 'Tambah Produk LPG';
$errors = [];
$old = [
    'nama_produk'  => '',
    'ukuran'       => '',
    'harga_beli'   => '',
    'harga_jual'   => '',
    'stok'         => 0,
    'stok_minimum' => 10
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
        $errors['ukuran'] = 'Ukuran tabung (misal: 3 kg, 12 kg) wajib diisi.';
    }
    if ($harga_beli < 0) {
        $errors['harga_beli'] = 'Harga beli tidak boleh bernilai negatif.';
    }
    if ($harga_jual < 0) {
        $errors['harga_jual'] = 'Harga jual tidak boleh bernilai negatif.';
    }
    if ($stok < 0) {
        $errors['stok'] = 'Stok awal tidak boleh bernilai negatif.';
    }
    if ($stok_minimum < 0) {
        $errors['stok_minimum'] = 'Batas stok minimum tidak boleh bernilai negatif.';
    }

    if (empty($errors)) {
        try {
            $pdo = get_db();
            $stmt = $pdo->prepare('
                INSERT INTO produk (nama_produk, ukuran, harga_beli, harga_jual, stok, stok_minimum)
                VALUES (:nama, :ukuran, :harga_beli, :harga_jual, :stok, :stok_min)
            ');
            $stmt->execute([
                ':nama'       => $nama_produk,
                ':ukuran'     => $ukuran,
                ':harga_beli' => $harga_beli,
                ':harga_jual' => $harga_jual,
                ':stok'       => $stok,
                ':stok_min'   => $stok_minimum
            ]);

            set_flash('success', "Produk '{$nama_produk}' berhasil ditambahkan ke katalog!");
            redirect('modules/produk/index.php');
        } catch (PDOException $e) {
            log_app_error('Gagal tambah produk: ' . $e->getMessage());
            $errors['general'] = 'Terjadi kesalahan sistem saat menyimpan data.';
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <div class="page-heading">
        <h1 class="page-title">Tambah Produk Gas LPG</h1>
        <p class="page-subtitle">Daftarkan varian tabung baru ke dalam sistem pengelolaan pangkalan.</p>
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
        <div class="card-title">Formulir Data Produk</div>
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
                           placeholder="Contoh: LPG 3 kg Bersubsidi" value="<?= e($old['nama_produk']) ?>" required>
                    <?php if (!empty($errors['nama_produk'])): ?>
                        <div class="form-error"><?= e($errors['nama_produk']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="ukuran" class="form-label">Ukuran / Berat <span class="required">*</span></label>
                    <input type="text" id="ukuran" name="ukuran" class="form-control" 
                           placeholder="Contoh: 3 kg, 5.5 kg, 12 kg" value="<?= e($old['ukuran']) ?>" required>
                    <?php if (!empty($errors['ukuran'])): ?>
                        <div class="form-error"><?= e($errors['ukuran']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="harga_beli" class="form-label">Harga Beli Modal (Rp) <span class="required">*</span></label>
                    <input type="number" id="harga_beli" name="harga_beli" class="form-control tabular-nums" 
                           placeholder="Contoh: 15500" value="<?= e($old['harga_beli']) ?>" min="0" step="500" required>
                    <div class="form-text">Harga perolehan resmi dari agen Pertamina.</div>
                    <?php if (!empty($errors['harga_beli'])): ?>
                        <div class="form-error"><?= e($errors['harga_beli']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="harga_jual" class="form-label">Harga Jual / HET (Rp) <span class="required">*</span></label>
                    <input type="number" id="harga_jual" name="harga_jual" class="form-control tabular-nums" 
                           placeholder="Contoh: 18000" value="<?= e($old['harga_jual']) ?>" min="0" step="500" required>
                    <div class="form-text">Harga eceran resmi di pangkalan untuk pembeli.</div>
                    <?php if (!empty($errors['harga_jual'])): ?>
                        <div class="form-error"><?= e($errors['harga_jual']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="stok" class="form-label">Stok Awal (Tabung) <span class="required">*</span></label>
                    <input type="number" id="stok" name="stok" class="form-control tabular-nums" 
                           value="<?= e($old['stok']) ?>" min="0" required>
                    <div class="form-text">Jumlah tabung fisik yang tersedia saat ini.</div>
                    <?php if (!empty($errors['stok'])): ?>
                        <div class="form-error"><?= e($errors['stok']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="stok_minimum" class="form-label">Batas Stok Minimum (Tabung) <span class="required">*</span></label>
                    <input type="number" id="stok_minimum" name="stok_minimum" class="form-control tabular-nums" 
                           value="<?= e($old['stok_minimum']) ?>" min="0" required>
                    <div class="form-text">Peringatan otomatis muncul bila stok &le; angka ini.</div>
                    <?php if (!empty($errors['stok_minimum'])): ?>
                        <div class="form-error"><?= e($errors['stok_minimum']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= icon('check', '', 14) ?>
                    <span>Simpan Produk</span>
                </button>
                <a href="<?= url('modules/produk/index.php') ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
