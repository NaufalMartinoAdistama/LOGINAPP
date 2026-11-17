<?php
/**
 * includes/sidebar.php
 * Komponen Sidebar Dinamis berdasarkan $_SESSION['role']
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/auth.php';

$currentRole = $_SESSION['role'] ?? 'guest';
$currentUser = currentUser();
$currentScript = basename($_SERVER['SCRIPT_NAME']);
?>

<aside class="app-sidebar" id="appSidebar">
    <!-- Brand / Logo Header -->
    <a href="<?= BASE_URL ?>dashboard.php" class="sidebar-brand">
        <div class="brand-icon">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <div class="brand-text">
            <h5><?= APP_NAME ?></h5>
            <span>Role-Based Access</span>
        </div>
    </a>

    <!-- Menu Navigasi Sesuai Role -->
    <div class="sidebar-menu">
        <div class="menu-heading">Menu Utama</div>

        <?php if ($currentRole === 'admin'): ?>
            <!-- Menu Khusus Admin -->
            <a href="<?= BASE_URL ?>admin/dashboard.php" class="sidebar-link <?= ($currentScript === 'dashboard.php' && strpos($_SERVER['REQUEST_URI'], 'admin') !== false) ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard Admin</span>
            </a>
            <a href="<?= BASE_URL ?>admin/kelola_user.php" class="sidebar-link <?= ($currentScript === 'kelola_user.php') ? 'active' : '' ?>">
                <i class="bi bi-people-fill"></i>
                <span>Kelola Pengguna</span>
            </a>

        <?php elseif ($currentRole === 'petugas'): ?>
            <!-- Menu Khusus Petugas -->
            <a href="<?= BASE_URL ?>petugas/dashboard.php" class="sidebar-link <?= ($currentScript === 'dashboard.php' && strpos($_SERVER['REQUEST_URI'], 'petugas') !== false) ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard Petugas</span>
            </a>
            <a href="<?= BASE_URL ?>petugas/dashboard.php#layanan" class="sidebar-link">
                <i class="bi bi-clipboard2-check-fill"></i>
                <span>Layanan & Tugas</span>
            </a>

        <?php elseif ($currentRole === 'siswa'): ?>
            <!-- Menu Khusus Siswa -->
            <a href="<?= BASE_URL ?>siswa/dashboard.php" class="sidebar-link <?= ($currentScript === 'dashboard.php' && strpos($_SERVER['REQUEST_URI'], 'siswa') !== false) ? 'active' : '' ?>">
                <i class="bi bi-mortarboard-fill"></i>
                <span>Dashboard Siswa</span>
            </a>
            <a href="<?= BASE_URL ?>siswa/dashboard.php#pengumuman" class="sidebar-link">
                <i class="bi bi-megaphone-fill"></i>
                <span>Pengumuman</span>
            </a>
        <?php endif; ?>

        <!-- Menu Umum / Pengaturan Akun -->
        <div class="menu-heading mt-3">Akun & Sistem</div>
        <a href="<?= BASE_URL ?>profil.php" class="sidebar-link <?= ($currentScript === 'profil.php') ? 'active' : '' ?>">
            <i class="bi bi-person-circle"></i>
            <span>Profil & Password</span>
        </a>
        <a href="<?= BASE_URL ?>logout.php" class="sidebar-link text-danger-emphasis">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout / Keluar</span>
        </a>
    </div>

    <!-- User Mini Profile di Bawah Sidebar -->
    <div class="sidebar-footer">
        <div class="user-mini-card">
            <div class="user-avatar-sm">
                <?= strtoupper(substr($currentUser['nama'], 0, 1)) ?>
            </div>
            <div class="user-info flex-grow-1 overflow-hidden">
                <div class="fw-semibold text-white text-truncate small" style="max-width: 140px;">
                    <?= e($currentUser['nama']) ?>
                </div>
                <div class="small">
                    <?= getRoleBadge($currentUser['role']) ?>
                </div>
            </div>
        </div>
    </div>
</aside>

<!-- Overlay untuk Sidebar di Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
