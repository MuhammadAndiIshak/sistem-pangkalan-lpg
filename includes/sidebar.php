<?php
/**
 * Sidebar Navigation Component
 * Pangkalan Gas LPG Kota Palu
 */
$currentRole = current_role();
?>
<aside class="app-sidebar">
    <div class="sidebar-header">
        <a href="<?= url('dashboard.php') ?>" class="brand-logo">
            <img src="<?= url('assets/img/logo.svg') ?>" alt="Logo Pangkalan LPG">
            <div class="brand-text">
                <span class="brand-name">Pangkalan LPG</span>
                <span class="brand-city">Kota Palu</span>
            </div>
        </a>
        <button type="button" class="sidebar-close-btn" aria-label="Tutup Menu">
            <?= icon('close', '', 18) ?>
        </button>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Menu Utama</div>
        <a href="<?= url('dashboard.php') ?>" class="nav-link <?= is_active('dashboard.php') ?>">
            <?= icon('dashboard') ?>
            <span>Dashboard</span>
        </a>

        <div class="nav-section-title">Data Master</div>
        <a href="<?= url('modules/produk/index.php') ?>" class="nav-link <?= is_active('modules/produk') ?>">
            <?= icon('produk') ?>
            <span>Data Produk LPG</span>
        </a>
        <a href="<?= url('modules/pemasok/index.php') ?>" class="nav-link <?= is_active('modules/pemasok') ?>">
            <?= icon('pemasok') ?>
            <span>Data Pemasok Agen</span>
        </a>
        <a href="<?= url('modules/pelanggan/index.php') ?>" class="nav-link <?= is_active('modules/pelanggan') ?>">
            <?= icon('pelanggan') ?>
            <span>Data Pelanggan</span>
        </a>

        <div class="nav-section-title">Aktivitas Transaksi</div>
        <a href="<?= url('modules/lpg_masuk/index.php') ?>" class="nav-link <?= is_active('modules/lpg_masuk') ?>">
            <?= icon('lpg_masuk') ?>
            <span>LPG Masuk (Pasokan)</span>
        </a>
        <a href="<?= url('modules/penjualan/index.php') ?>" class="nav-link <?= is_active('modules/penjualan') ?>">
            <?= icon('penjualan') ?>
            <span>Penjualan Gas LPG</span>
        </a>

        <div class="nav-section-title">Analisis & Laporan</div>
        <a href="<?= url('modules/laporan/index.php') ?>" class="nav-link <?= is_active('modules/laporan') ?>">
            <?= icon('laporan') ?>
            <span>Laporan & Monitoring</span>
        </a>

        <div class="nav-section-title">Sesi Pengguna</div>
        <a href="<?= url('profil.php') ?>" class="nav-link <?= is_active('profil.php') ?>">
            <?= icon('user') ?>
            <span>Profil & Password</span>
        </a>
        <a href="<?= url('logout.php') ?>" class="nav-link" style="color: var(--color-danger-hover);">
            <?= icon('logout') ?>
            <span>Keluar (Logout)</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-profile-badge">
            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['user']['nama_lengkap'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="user-details">
                <div class="user-name"><?= e($_SESSION['user']['nama_lengkap'] ?? 'Pengguna') ?></div>
                <span class="user-role-tag user-role-<?= e($currentRole) ?>">
                    <?= $currentRole === 'admin' ? 'Admin / Petugas' : 'Pemilik Pangkalan' ?>
                </span>
            </div>
        </div>
    </div>
</aside>
