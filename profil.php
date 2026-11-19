<?php
/**
 * Halaman Profil & Pengaturan Password Pengguna
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/config/app.php';
require_login();

$pageTitle = 'Profil & Password';
$pdo = get_db();

$userId = (int)($_SESSION['user']['id'] ?? 0);

// Ambil data terbaru pengguna dari database
$stmt = $pdo->prepare('SELECT id, username, password, nama_lengkap, role, created_at FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    redirect('logout.php');
}

// Proses Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // 1. Update Nama Lengkap & Username
    if ($action === 'update_profil') {
        $namaLengkap = trim($_POST['nama_lengkap'] ?? '');
        $username    = trim($_POST['username'] ?? '');

        if ($namaLengkap === '' || $username === '') {
            set_flash('danger', 'Nama lengkap dan username wajib diisi.');
            redirect('profil.php');
        }

        if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
            set_flash('danger', 'Username hanya boleh berisi huruf, angka, titik, atau garis bawah (3–50 karakter).');
            redirect('profil.php');
        }

        // Cek apakah username sudah dipakai akun lain
        $cekStmt = $pdo->prepare('SELECT id FROM users WHERE username = :username AND id != :id LIMIT 1');
        $cekStmt->execute([
            ':username' => $username,
            ':id'       => $userId
        ]);

        if ($cekStmt->fetch()) {
            set_flash('danger', 'Username tersebut sudah digunakan oleh pengguna lain.');
            redirect('profil.php');
        }

        $updStmt = $pdo->prepare('UPDATE users SET nama_lengkap = :nama, username = :username WHERE id = :id');
        $updStmt->execute([
            ':nama'     => $namaLengkap,
            ':username' => $username,
            ':id'       => $userId
        ]);

        // Sinkronkan data session agar tampilan sidebar langsung berubah
        $_SESSION['user']['nama_lengkap'] = $namaLengkap;
        $_SESSION['user']['username']     = $username;

        set_flash('success', 'Informasi profil berhasil diperbarui.');
        redirect('profil.php');
    }

    // 2. Ubah Password
    if ($action === 'update_password') {
        $passwordLama       = $_POST['password_lama'] ?? '';
        $passwordBaru       = $_POST['password_baru'] ?? '';
        $konfirmasiPassword = $_POST['konfirmasi_password'] ?? '';

        if ($passwordLama === '' || $passwordBaru === '' || $konfirmasiPassword === '') {
            set_flash('danger', 'Semua kolom password wajib diisi.');
            redirect('profil.php');
        }

        if (!password_verify($passwordLama, $user['password'])) {
            set_flash('danger', 'Password lama yang Anda masukkan salah.');
            redirect('profil.php');
        }

        if (strlen($passwordBaru) < 6) {
            set_flash('danger', 'Password baru minimal harus terdiri dari 6 karakter.');
            redirect('profil.php');
        }

        if ($passwordBaru !== $konfirmasiPassword) {
            set_flash('danger', 'Konfirmasi password baru tidak cocok.');
            redirect('profil.php');
        }

        $hashBaru = password_hash($passwordBaru, PASSWORD_DEFAULT);
        $pwdStmt = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
        $pwdStmt->execute([
            ':password' => $hashBaru,
            ':id'       => $userId
        ]);

        set_flash('success', 'Password akun berhasil diubah. Gunakan password baru pada login berikutnya.');
        redirect('profil.php');
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="content-header">
    <div class="page-heading">
        <h1 class="page-title">Profil & Pengaturan Akun</h1>
        <p class="page-subtitle">Kelola identitas akun pengguna dan keamanan kata sandi sistem pangkalan LPG.</p>
    </div>
</div>

<div class="split-grid">
    <!-- Kartu 1: Informasi Profil -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Informasi Pengguna</div>
            <span class="badge badge-neutral">
                <?= $user['role'] === 'admin' ? 'Admin / Petugas' : 'Pemilik Pangkalan' ?>
            </span>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_profil">

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">
                        Nama Lengkap <span style="color: var(--color-danger);">*</span>
                    </label>
                    <input type="text"
                           name="nama_lengkap"
                           class="form-control"
                           value="<?= e($user['nama_lengkap']) ?>"
                           required
                           maxlength="100">
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">
                        Username Login <span style="color: var(--color-danger);">*</span>
                    </label>
                    <input type="text"
                           name="username"
                           class="form-control"
                           value="<?= e($user['username']) ?>"
                           required
                           maxlength="50">
                    <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">
                        Digunakan untuk masuk ke dalam sistem.
                    </div>
                </div>

                <div style="margin-bottom: 20px; padding: 10px 12px; background: var(--bg-body, #f8fafc); border-radius: 6px; font-size: 12.5px; color: var(--text-muted);">
                    <div><strong>Hak Akses:</strong> <?= $user['role'] === 'admin' ? 'Administrator / Petugas Operasional' : 'Pemilik Pangkalan (Monitoring)' ?></div>
                    <div style="margin-top: 2px;"><strong>Terdaftar Sejak:</strong> <?= tanggal_indo(date('Y-m-d', strtotime($user['created_at']))) ?></div>
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary">
                        <span>Simpan Perubahan Profil</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Kartu 2: Ganti Password -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Ubah Password</div>
            <span style="font-size: 12px; color: var(--text-muted);">Keamanan Akun</span>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_password">

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">
                        Password Lama <span style="color: var(--color-danger);">*</span>
                    </label>
                    <input type="password"
                           name="password_lama"
                           class="form-control"
                           placeholder="Masukkan password saat ini"
                           required>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">
                        Password Baru <span style="color: var(--color-danger);">*</span>
                    </label>
                    <input type="password"
                           name="password_baru"
                           class="form-control"
                           placeholder="Minimal 6 karakter"
                           minlength="6"
                           required>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">
                        Konfirmasi Password Baru <span style="color: var(--color-danger);">*</span>
                    </label>
                    <input type="password"
                           name="konfirmasi_password"
                           class="form-control"
                           placeholder="Ulangi password baru"
                           minlength="6"
                           required>
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-secondary">
                        <span>Perbarui Password</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>