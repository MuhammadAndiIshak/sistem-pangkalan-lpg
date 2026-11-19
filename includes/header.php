<?php
/**
 * Header Layout Template
 * Pangkalan Gas LPG Kota Palu
 */
require_once __DIR__ . '/../config/app.php';
require_login();

$user = current_user();
$title = !empty($pageTitle) ? $pageTitle . ' &bull; ' . APP_NAME : APP_NAME;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?></title>
    
    <!-- Favicon SVG -->
    <link rel="icon" type="image/svg+xml" href="<?= url('assets/img/logo.svg') ?>">
    
    <!-- CSS Utama -->
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
</head>
<body>
<div class="sidebar-backdrop"></div>
<div class="app-layout">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="app-main">
        <header class="app-topbar">
            <div class="topbar-left">
                <button type="button" class="mobile-menu-btn" aria-label="Buka Menu">
                    <?= icon('menu', '', 18) ?>
                </button>
                <div class="topbar-title"><?= e($pageTitle ?? 'Dashboard') ?></div>
            </div>

            <div class="topbar-right">
                <div class="datetime-display">
                    <?= icon('calendar', '', 14) ?>
                    <span><?= tanggal_indo(date('Y-m-d'), false, true) ?></span>
                </div>

                <div class="user-profile-badge">
                    <div class="user-avatar" title="<?= e($user['nama_lengkap']) ?>">
                        <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
                    </div>
                    <div class="user-details no-print">
                        <div class="user-name"><?= e($user['nama_lengkap']) ?></div>
                        <span class="user-role-tag user-role-<?= e($user['role']) ?>">
                            <?= $user['role'] === 'admin' ? 'Petugas' : 'Pemilik' ?>
                        </span>
                    </div>
                </div>
            </div>
        </header>

        <main class="app-content">
