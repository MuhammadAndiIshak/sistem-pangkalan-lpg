<?php
/**
 * Ubah Data Pelanggan
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/../../config/app.php';
require_role('admin');

$pageTitle = 'Ubah Data Pelanggan';
$pdo = get_db();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM pelanggan WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$pelanggan = $stmt->fetch();

if (!$pelanggan) {
    set_flash('danger', 'Data pelanggan tidak ditemukan.');
    redirect('modules/pelanggan/index.php');
}

$errors = [];
$old = [
    'nama_pelanggan' => $pelanggan['nama_pelanggan'],
    'alamat'         => $pelanggan['alamat'],
    'no_telepon'     => $pelanggan['no_telepon']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $nama_pelanggan = trim($_POST['nama_pelanggan'] ?? '');
    $alamat         = trim($_POST['alamat'] ?? '');
    $no_telepon     = trim($_POST['no_telepon'] ?? '');

    $old = [
        'nama_pelanggan' => $nama_pelanggan,
        'alamat'         => $alamat,
        'no_telepon'     => $no_telepon
    ];

    if ($nama_pelanggan === '') {
        $errors['nama_pelanggan'] = 'Nama pelanggan wajib diisi.';
    }
    if ($alamat === '') {
        $errors['alamat'] = 'Alamat pelanggan di Palu wajib diisi.';
    }
    if ($no_telepon === '') {
        $errors['no_telepon'] = 'Nomor telepon wajib diisi.';
    } elseif (!preg_match('/^[0-9\-\+\s\(\)]{7,25}$/', $no_telepon)) {
        $errors['no_telepon'] = 'Format nomor telepon tidak valid.';
    }

    if (empty($errors)) {
        try {
            $update = $pdo->prepare('
                UPDATE pelanggan 
                SET nama_pelanggan = :nama, alamat = :alamat, no_telepon = :telp
                WHERE id = :id
            ');
            $update->execute([
                ':nama'   => $nama_pelanggan,
                ':alamat' => $alamat,
                ':telp'   => $no_telepon,
                ':id'     => $id
            ]);

            set_flash('success', "Data pelanggan '{$nama_pelanggan}' berhasil diperbarui!");
            redirect('modules/pelanggan/index.php');
        } catch (PDOException $e) {
            log_app_error('Gagal update pelanggan: ' . $e->getMessage());
            $errors['general'] = 'Terjadi kesalahan sistem saat memperbarui data pelanggan.';
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <div class="page-heading">
        <h1 class="page-title">Ubah Data Pelanggan</h1>
        <p class="page-subtitle">Perbarui data domisili dan nomor kontak warga atau pelaku usaha.</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('modules/pelanggan/index.php') ?>" class="btn btn-secondary">
            <?= icon('arrow-left', '', 14) ?>
            <span>Kembali</span>
        </a>
    </div>
</div>

<div class="card" style="max-width: 620px;">
    <div class="card-header">
        <div class="card-title">Edit Data: <?= e($pelanggan['nama_pelanggan']) ?></div>
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

            <div class="form-group">
                <label for="nama_pelanggan" class="form-label">Nama Pelanggan <span class="required">*</span></label>
                <input type="text" id="nama_pelanggan" name="nama_pelanggan" class="form-control" 
                       value="<?= e($old['nama_pelanggan']) ?>" required>
                <?php if (!empty($errors['nama_pelanggan'])): ?>
                    <div class="form-error"><?= e($errors['nama_pelanggan']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="alamat" class="form-label">Alamat Domisili di Kota Palu <span class="required">*</span></label>
                <textarea id="alamat" name="alamat" class="form-control" rows="3" required><?= e($old['alamat']) ?></textarea>
                <?php if (!empty($errors['alamat'])): ?>
                    <div class="form-error"><?= e($errors['alamat']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="no_telepon" class="form-label">Nomor Telepon <span class="required">*</span></label>
                <input type="text" id="no_telepon" name="no_telepon" class="form-control" 
                       value="<?= e($old['no_telepon']) ?>" required>
                <?php if (!empty($errors['no_telepon'])): ?>
                    <div class="form-error"><?= e($errors['no_telepon']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= icon('check', '', 14) ?>
                    <span>Perbarui Data</span>
                </button>
                <a href="<?= url('modules/pelanggan/index.php') ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
