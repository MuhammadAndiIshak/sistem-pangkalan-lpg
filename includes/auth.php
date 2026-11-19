<?php
/**
 * Modul Autentikasi & Otorisasi Berbasis Peran (RBAC)
 * Pangkalan Gas LPG Kota Palu
 */

function is_logged_in(): bool {
    return !empty($_SESSION['user']['id']);
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function current_role(): ?string {
    return $_SESSION['user']['role'] ?? null;
}

function is_admin(): bool {
    return current_role() === 'admin';
}

function is_pemilik(): bool {
    return current_role() === 'pemilik';
}

/**
 * Wajib login untuk mengakses halaman
 */
function require_login(): void {
    if (!is_logged_in()) {
        set_flash('warning', 'Sesi Anda belum aktif. Silakan masuk terlebih dahulu.');
        redirect('login.php');
    }
}

/**
 * Validasi Role Pengguna di sisi server
 * Mencegah Pemilik mengakses URL aksi atau halaman kelola data
 */
function require_role(string|array $roles): void {
    require_login();

    $allowedRoles = is_array($roles) ? $roles : [$roles];
    $userRole = current_role();

    if (!in_array($userRole, $allowedRoles, true)) {
        log_app_error("Percobaan akses tidak sah oleh role '{$userRole}' ke URL " . ($_SERVER['REQUEST_URI'] ?? ''));

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki izin untuk aksi ini.']);
            exit;
        }

        set_flash('danger', 'Akses ditolak. Akun Anda (Pemilik Pangkalan) hanya memiliki izin lihat (read-only).');
        redirect('dashboard.php');
    }
}

/**
 * Catat session login baru dengan aman
 */
function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'           => (int)$user['id'],
        'username'     => $user['username'],
        'nama_lengkap' => $user['nama_lengkap'],
        'role'         => $user['role'],
        'login_time'   => time()
    ];
}

/**
 * Hapus sesi login dan cookie secara menyeluruh
 */
function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}
