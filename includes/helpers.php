<?php
/**
 * Helper Functions
 * Pangkalan Gas LPG Kota Palu
 */

/**
 * Escape output HTML untuk mencegah XSS
 */
function e(?string $string): string {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Format angka ke mata uang Rupiah
 */
function rupiah(float|int|string|null $angka, bool $withPrefix = true): string {
    $num = (float)($angka ?? 0);
    $formatted = number_format($num, 0, ',', '.');
    return $withPrefix ? 'Rp ' . $formatted : $formatted;
}

/**
 * Format jumlah tabung gas
 */
function tabung(int|float|string|null $angka): string {
    $num = (int)($angka ?? 0);
    return number_format($num, 0, ',', '.') . ' tabung';
}

/**
 * Format tanggal Indonesia
 * Contoh: 2026-11-18 -> 18 Nov 2026
 */
function tanggal_indo(?string $tanggal, bool $withTime = false, bool $shortMonth = true): string {
    if (!$tanggal || $tanggal === '0000-00-00' || $tanggal === '0000-00-00 00:00:00') {
        return '-';
    }

    $timestamp = strtotime($tanggal);
    if ($timestamp === false) {
        return $tanggal;
    }

    $bulanPanjang = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $bulanPendek = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
    ];

    $d = date('d', $timestamp);
    $m = (int)date('m', $timestamp);
    $y = date('Y', $timestamp);

    $namaBulan = $shortMonth ? $bulanPendek[$m] : $bulanPanjang[$m];
    $hasil = "$d $namaBulan $y";

    if ($withTime) {
        $hasil .= ' ' . date('H:i', $timestamp) . ' WITA';
    }

    return $hasil;
}

/**
 * Redirect ke URL tujuan
 */
function redirect(string $path): void {
    if (!str_starts_with($path, 'http://') && !str_starts_with($path, 'https://')) {
        $path = url($path);
    }
    header("Location: $path");
    exit;
}

/**
 * Menghasilkan URL lengkap berdasarkan path aplikasi
 */
function url(string $path = ''): string {
    $cleanPath = ltrim($path, '/');
    return BASE_URL . ($cleanPath !== '' ? '/' . $cleanPath : '');
}

/**
 * Cek apakah halaman saat ini aktif untuk menu navigasi
 */
function is_active(string $keyword): string {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    return str_contains($uri, $keyword) ? 'active' : '';
}

/**
 * Flash notification message (disimpan di session)
 */
function set_flash(string $type, string $message): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type'    => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Ambil dan bersihkan semua flash message
 */
function get_flashes(): array {
    $flashes = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $flashes;
}

/**
 * Cek status stok dan kembalikan label/badge class
 */
function get_stock_badge(int $stok, int $stok_minimum): array {
    if ($stok <= 0) {
        return [
            'status' => 'habis',
            'label'  => 'Stok Habis',
            'class'  => 'badge-danger',
            'is_low' => true
        ];
    } elseif ($stok <= $stok_minimum) {
        return [
            'status' => 'menipis',
            'label'  => 'Stok Menipis',
            'class'  => 'badge-warning',
            'is_low' => true
        ];
    } else {
        return [
            'status' => 'aman',
            'label'  => 'Stok Aman',
            'class'  => 'badge-success',
            'is_low' => false
        ];
    }
}

/**
 * CSRF Protection
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            set_flash('danger', 'Validasi keamanan (CSRF) gagal. Silakan ulangi formulir.');
            redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard.php');
            return false;
        }
    }
    return true;
}

/**
 * Catat error sistem ke file log
 */
function log_app_error(string $message): void {
    $logFile = dirname(__DIR__) . '/app_error.log';
    $time = date('Y-m-d H:i:s');
    $user = $_SESSION['user']['username'] ?? 'GUEST';
    $entry = "[$time][$user] $message" . PHP_EOL;
    @file_put_contents($logFile, $entry, FILE_APPEND);
}

/**
 * Render SVG Icon yang konsisten dan ringan tanpa dependensi eksternal
 */
function icon(string $name, string $class = '', int $size = 18): string {
    $style = "width: {$size}px; height: {$size}px;";
    $cls = 'icon ' . $class;
    
    $icons = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect>',
        'produk' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line>',
        'pemasok' => '<rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle>',
        'pelanggan' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
        'lpg_masuk' => '<path d="M12 2v20M17 17l-5 5-5-5"></path><path d="M2 12h3M19 12h3"></path>',
        'penjualan' => '<circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>',
        'laporan' => '<line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line>',
        'plus' => '<line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line>',
        'edit' => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>',
        'trash' => '<polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>',
        'printer' => '<polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect>',
        'search' => '<circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>',
        'alert-triangle' => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>',
        'check' => '<polyline points="20 6 9 17 4 12"></polyline>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line>',
        'history' => '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>',
        'menu' => '<line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line>',
        'arrow-left' => '<line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline>',
        'clock' => '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>',
        'close' => '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>'
    ];

    $svgContent = $icons[$name] ?? '<circle cx="12" cy="12" r="10"></circle>';
    return sprintf(
        '<svg class="%s" style="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">%s</svg>',
        e($cls),
        $style,
        $svgContent
    );
}

