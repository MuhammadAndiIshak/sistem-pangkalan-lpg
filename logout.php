<?php
/**
 * Logout Handler
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/config/app.php';

logout_user();

// Mulai session baru hanya untuk menyampaikan flash message
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
set_flash('info', 'Anda telah berhasil keluar dari sistem.');
redirect('login.php');
