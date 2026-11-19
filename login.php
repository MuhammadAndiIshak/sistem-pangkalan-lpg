<?php
/**
 * Halaman Masuk (Login)
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/config/app.php';

// Jika sudah login, langsung alihkan ke dashboard
if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';
$usernameVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $usernameVal = $username;

    if (empty($username) || empty($password)) {
        $error = 'Silakan masukkan username dan password.';
    } else {
        try {
            $pdo = get_db();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                login_user($user);
                set_flash('success', 'Selamat datang kembali, ' . $user['nama_lengkap'] . '!');
                redirect('dashboard.php');
            } else {
                $error = 'Username atau password yang Anda masukkan salah.';
            }
        } catch (PDOException $e) {
            log_app_error('Login error: ' . $e->getMessage());
            $error = 'Terjadi gangguan koneksi pada database. Silakan coba sesaat lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk &bull; <?= APP_NAME ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= url('assets/img/logo.svg') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
</head>
<body class="login-body">

<div class="login-container">
    <!-- Kolom Kiri: Branding & Informasi Pangkalan -->
    <div class="login-brand-panel">
        <div class="login-brand-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <img src="<?= url('assets/img/logo.svg') ?>" alt="Logo LPG" style="width: 44px; height: 44px;">
                <div>
                    <div style="font-size: 16px; font-weight: 700; letter-spacing: -0.2px;"><?= APP_NAME ?></div>
                    <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Sulawesi Tengah</div>
                </div>
            </div>
        </div>

        <div class="login-brand-body">
            <h2 class="login-brand-title">Penyaluran LPG Tepat Sasaran & Terdata Transparan</h2>
            <p class="login-brand-desc">
                Sistem pengelolaan terintegrasi untuk pencatatan pasokan masuk dari agen Pertamina, penjualan ke pangkalan/warga, kontrol batas stok minimum, dan pelaporan berkala.
            </p>

            <div class="login-badges-list">
                <div class="login-badge-item">
                    <?= icon('check', '', 16) ?>
                    <span>Pencatatan pasokan masuk & mutasi real-time</span>
                </div>
                <div class="login-badge-item">
                    <?= icon('check', '', 16) ?>
                    <span>Validasi stok anti-selisih dengan DB lock transaction</span>
                </div>
                <div class="login-badge-item">
                    <?= icon('check', '', 16) ?>
                    <span>Hak akses bertingkat (Petugas Operasional & Pemilik)</span>
                </div>
            </div>
        </div>

        <div style="font-size: 11.5px; color: #94a3b8; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 16px;">
            <?= PANGKALAN_NAMA ?><br>
            <?= PANGKALAN_ALAMAT ?>
        </div>
    </div>

    <!-- Kolom Kanan: Formulir Masuk -->
    <div class="login-form-panel">
        <h1 class="login-form-title">Masuk ke Sistem</h1>
        <p class="login-form-subtitle">Silakan masukkan kredensial akun terdaftar Anda.</p>

        <?php if (!empty($error)): ?>
            <div class="alert-banner" style="background-color: var(--color-danger-light); border-color: #fecaca; color: var(--color-danger-text); margin-bottom: 20px;">
                <div class="alert-banner-content">
                    <?= icon('alert-triangle', '', 18) ?>
                    <span><?= e($error) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('login.php') ?>">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-control" 
                       value="<?= e($usernameVal) ?>" placeholder="Contoh: admin atau pemilik" required autofocus>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" 
                       placeholder="Masukkan password Anda" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 10px; font-weight: 600;">
                Masuk ke Aplikasi
            </button>
        </form>

        <div class="demo-account-box">
            <div style="font-weight: 600; margin-bottom: 6px; color: var(--text-main);">Akun Login Bawaan (Default):</div>
            <div style="display: flex; flex-direction: column; gap: 4px;">
                <div>&bull; <strong>Admin / Petugas:</strong> <code>admin</code> / <code>admin123</code> (Akses Penuh)</div>
                <div>&bull; <strong>Pemilik Pangkalan:</strong> <code>pemilik</code> / <code>pemilik123</code> (Hanya Lihat)</div>
            </div>
        </div>
    </div>
</div>

<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
