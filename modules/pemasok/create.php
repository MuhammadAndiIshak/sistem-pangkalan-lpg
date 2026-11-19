<?php
/**
 * Tambah Pemasok Baru
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/../../config/app.php';
require_role('admin');

$pageTitle = 'Tambah Pemasok Baru';
$errors = [];
$old = [
    'nama_pemasok' => '',
    'alamat'       => '',
    'no_telepon'   => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $nama_pemasok = trim($_POST['nama_pemasok'] ?? '');
    $alamat       = trim($_POST['alamat'] ?? '');
    $no_telepon   = trim($_POST['no_telepon'] ?? '');

    $old = [
        'nama_pemasok' => $nama_pemasok,
        'alamat'       => $alamat,
        'no_telepon'   => $no_telepon
    ];

    if ($nama_pemasok === '') {
        $errors['nama_pemasok'] = 'Nama pemasok / agen wajib diisi.';
    }
    if ($alamat === '') {
        $errors['alamat'] = 'Alamat kantor / gudang agen di Palu wajib diisi.';
    }
    if ($no_telepon === '') {
        $errors['no_telepon'] = 'Nomor telepon wajib diisi.';
    } elseif (!preg_match('/^[0-9\-\+\s\(\)]{7,25}$/', $no_telepon)) {
        $errors['no_telepon'] = 'Format nomor telepon tidak valid (contoh: 0451-482910 atau 0812-3456-7890).';
    }

    if (empty($errors)) {
        try {
            $pdo = get_db();
            $stmt = $pdo->prepare('
                INSERT INTO pemasok (nama_pemasok, alamat, no_telepon)
                VALUES (:nama, :alamat, :telp)
            ');
            $stmt->execute([
                ':nama'   => $nama_pemasok,
                ':alamat' => $alamat,
                ':telp'   => $no_telepon
            ]);

            set_flash('success', "Pemasok '{$nama_pemasok}' berhasil ditambahkan!");
            redirect('modules/pemasok/index.php');
        } catch (PDOException $e) {
            log_app_error('Gagal tambah pemasok: ' . $e->getMessage());
            $errors['general'] = 'Terjadi kesalahan sistem saat menyimpan data pemasok.';
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <div class="page-heading">
        <h1 class="page-title">Tambah Pemasok / Agen LPG</h1>
        <p class="page-subtitle">Daftarkan agen penyalur resmi Pertamina untuk pencatatan pasokan gas masuk.</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('modules/pemasok/index.php') ?>" class="btn btn-secondary">
            <?= icon('arrow-left', '', 14) ?>
            <span>Kembali</span>
        </a>
    </div>
</div>

<div class="card" style="max-width: 620px;">
    <div class="card-header">
        <div class="card-title">Formulir Data Agen Pemasok</div>
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
                <label for="nama_pemasok" class="form-label">Nama Agen Pemasok <span class="required">*</span></label>
                <input type="text" id="nama_pemasok" name="nama_pemasok" class="form-control" 
                       placeholder="Contoh: PT Palu Gas Mitra Mandiri" value="<?= e($old['nama_pemasok']) ?>" required>
                <?php if (!empty($errors['nama_pemasok'])): ?>
                    <div class="form-error"><?= e($errors['nama_pemasok']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="alamat" class="form-label">Alamat Kantor / Gudang Agen di Kota Palu <span class="required">*</span></label>
                <textarea id="alamat" name="alamat" class="form-control" rows="3" 
                          placeholder="Contoh: Jl. I Gusti Ngurah Rai No. 42, Kelurahan Tavanjuka, Kec. Tatanga, Kota Palu" required><?= e($old['alamat']) ?></textarea>
                <?php if (!empty($errors['alamat'])): ?>
                    <div class="form-error"><?= e($errors['alamat']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="no_telepon" class="form-label">Nomor Telepon / WhatsApp <span class="required">*</span></label>
                <input type="text" id="no_telepon" name="no_telepon" class="form-control" 
                       placeholder="Contoh: 0451-482910 atau 0812-4211-8890" value="<?= e($old['no_telepon']) ?>" required>
                <?php if (!empty($errors['no_telepon'])): ?>
                    <div class="form-error"><?= e($errors['no_telepon']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= icon('check', '', 14) ?>
                    <span>Simpan Pemasok</span>
                </button>
                <a href="<?= url('modules/pemasok/index.php') ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
