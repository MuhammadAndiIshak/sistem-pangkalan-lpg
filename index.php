<?php
/**
 * Root Router
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/config/app.php';

if (is_logged_in()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
