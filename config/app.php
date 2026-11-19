<?php
/**
 * Konfigurasi Utama Aplikasi Pangkalan LPG Kota Palu
 */

// Zona Waktu Kota Palu (WITA / UTC+8)
date_default_timezone_set('Asia/Makassar');

// Keamanan Session Cookie
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

// Konfigurasi Nama & Lokasi Aplikasi
define('APP_NAME', 'Pangkalan LPG Kota Palu');
define('APP_SUBTITLE', 'Sistem Informasi Pengelolaan & Distribusi Gas LPG');
define('PANGKALAN_NAMA', 'Pangkalan LPG Berkah Madani');
define('PANGKALAN_ALAMAT', 'Jl. Trans Sulawesi KM 12, Kel. Tondo, Kec. Mantikulore, Kota Palu');
define('PANGKALAN_TELP', '0852-4112-9988');

// Deteksi Otomatis Base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

$appRootReal = str_replace('\\', '/', realpath(__DIR__ . '/..') ?: '');
$docRootReal = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');

if ($appRootReal !== '' && $docRootReal !== '' && str_starts_with(strtolower($appRootReal), strtolower($docRootReal))) {
    $relativeRoot = substr($appRootReal, strlen($docRootReal));
    $basePath = '/' . ltrim($relativeRoot, '/');
} else {
    $basePath = '/pangkalan-lpg';
    if (preg_match('#^(/[^/]+)#', $_SERVER['REQUEST_URI'] ?? '', $matches)) {
        if (in_array(trim($matches[1], '/'), ['pangkalan-lpg', 'pangkalan_lpg'], true)) {
            $basePath = $matches[1];
        }
    }
}

$basePath = rtrim($basePath, '/');
define('BASE_URL', $protocol . $host . $basePath);

// Include helper dan database
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
