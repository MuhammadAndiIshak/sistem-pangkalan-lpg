<?php
/**
 * Konfigurasi Koneksi Database MySQL via PDO
 * Pangkalan LPG Kota Palu
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'pangkalan_lpg');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function get_db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Catat error ke log server tanpa mengekspos credential ke user
            error_log('[DB CONNECTION ERROR] ' . $e->getMessage());
            http_response_code(500);
            echo '<!DOCTYPE html>
            <html lang="id">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>Koneksi Database Bermasalah</title>
                <style>
                    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
                    .card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 32px; max-width: 480px; width: 100%; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
                    h2 { margin: 0 0 12px; color: #dc2626; font-size: 20px; }
                    p { margin: 0 0 16px; color: #64748b; line-height: 1.5; font-size: 14px; }
                    code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
                </style>
            </head>
            <body>
                <div class="card">
                    <h2>Koneksi Database Gagal</h2>
                    <p>Sistem tidak dapat terhubung ke database MySQL <code>pangkalan_lpg</code>. Pastikan layanan MySQL di Laragon sudah aktif dan file <code>database.sql</code> telah diimpor.</p>
                </div>
            </body>
            </html>';
            exit;
        }
    }

    return $pdo;
}
