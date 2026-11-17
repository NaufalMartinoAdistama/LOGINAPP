<?php
/**
 * includes/navbar.php
 * Top Navbar Clean Light dengan Aksen Oranye & Role Badge
 */

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/auth.php';

$currentUser = currentUser();
?>

<header class="app-navbar">
    <div class="d-flex align-items-center gap-3">
        <!-- Tombol Hamburger Mobile -->
        <button class="btn btn-light d-lg-none border-0 shadow-none p-2 rounded-3 text-secondary" id="sidebarToggleBtn" type="button" aria-label="Toggle Sidebar">
            <i class="bi bi-list fs-4"></i>
        </button>

        <div class="d-none d-sm-block">
            <h6 class="mb-0 fw-bold text-dark"><?= isset($pageTitle) ? e($pageTitle) : 'Dashboard' ?></h6>
            <small class="text-muted" style="font-size: 0.75rem;">
                <i class="bi bi-calendar3 me-1"></i><?= date('l, d F Y') ?>
            </small>
        </div>
    </div>

    <!-- Sisi Kanan Navbar: Info User & Dropdown -->
    <div class="d-flex align-items-center gap-3">
        <!-- Role Badge -->
        <div class="d-none d-md-block">
            <?= getRoleBadge($currentUser['role']) ?>
        </div>

        <!-- User Dropdown Menu -->
        <div class="dropdown">
            <button class="btn btn-light bg-transparent border-0 d-flex align-items-center gap-2 p-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="user-avatar-initials" style="background: var(--orange-gradient);">
                    <?= strtoupper(substr($currentUser['nama'], 0, 1)) ?>
                </div>
                <div class="text-start d-none d-lg-block">
                    <div class="fw-semibold text-dark small leading-tight"><?= e($currentUser['nama']) ?></div>
                    <div class="text-muted" style="font-size: 0.72rem;">@<?= e($currentUser['username']) ?></div>
                </div>
                <i class="bi bi-chevron-down text-muted small ms-1"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border border-light-subtle rounded-3 mt-2 p-2" style="background-color: #ffffff; min-width: 220px;">
                <li class="px-2 py-1 text-muted small border-bottom mb-2">
                    <span class="d-block fw-semibold text-dark"><?= e($currentUser['nama']) ?></span>
                    <span class="d-block text-secondary" style="font-size: 0.75rem;">Role: <strong class="text-capitalize text-warning"><?= e($currentUser['role']) ?></strong></span>
                </li>
                <li>
                    <a class="dropdown-item rounded-2 py-2 small" href="<?= BASE_URL ?>profil.php">
                        <i class="bi bi-person-gear me-2 text-warning"></i>Pengaturan Profil & Sandi
                    </a>
                </li>
                <li>
                    <a class="dropdown-item rounded-2 py-2 small" href="<?= BASE_URL ?>dashboard.php">
                        <i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard Utama
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <a class="dropdown-item rounded-2 py-2 small text-danger" href="<?= BASE_URL ?>logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i>Keluar (Logout)
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>
