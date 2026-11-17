<?php
/**
 * index.php
 * Halaman Login Modern - Desain Clean Light & Vibrant Orange
 * Sesuai Mockup Sistem Informasi Akademik & Manajemen User
 */

require_once __DIR__ . '/config/koneksi.php';

// Jika pengguna sudah login, alihkan langsung ke router dashboard
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$pesan = $_GET['pesan'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi Akademik &amp; Manajemen User | Masuk</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons 1.11.3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom Theme Stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body class="login-light-wrapper">

<!-- Card Container Utama -->
<div class="browser-window-light">
    <!-- Inner Split Layout -->
    <div class="login-split-inner-light">
        <!-- SISI KIRI: Branding & Karakter Akademik -->
        <div class="login-left-light">
            <div>
                <!-- Badge Oranye -->
                <div class="hero-badge-orange">SISTEM INFORMASI</div>

                <!-- Headline Utama -->
                <h1 class="hero-title-dark">
                    AKADEMIK &amp;<br>
                    MANAJEMEN USER
                </h1>

                <!-- Subtitle -->
                <p class="hero-subtitle-light">
                    Platform Terintegrasi untuk Sekolah &amp; Organisasi
                </p>

                <!-- Ilustrasi Karakter & Database -->
                <div class="hero-illustration-box-light">
                    <img src="<?= BASE_URL ?>assets/img/hero-characters-light.png" alt="Akademik & Manajemen User" class="img-fluid">
                </div>
            </div>

            <!-- 3 Kartu Peran di Bawah -->
            <div class="role-cards-grid-light">
                <!-- Card Admin -->
                <div class="role-card-light btn-demo-fill" data-username="admin" data-password="admin123" title="Klik untuk coba login Admin">
                    <div class="role-avatar-circle-light">
                        <img src="<?= BASE_URL ?>assets/img/avatar-admin-light.png" alt="Admin">
                    </div>
                    <span class="role-name-dark">Admin</span>
                </div>

                <!-- Card Petugas -->
                <div class="role-card-light btn-demo-fill" data-username="petugas" data-password="petugas123" title="Klik untuk coba login Petugas">
                    <div class="role-avatar-circle-light">
                        <img src="<?= BASE_URL ?>assets/img/avatar-petugas-light.png" alt="Petugas">
                    </div>
                    <span class="role-name-dark">Petugas</span>
                </div>

                <!-- Card Siswa -->
                <div class="role-card-light btn-demo-fill" data-username="siswa" data-password="siswa123" title="Klik untuk coba login Siswa">
                    <div class="role-avatar-circle-light">
                        <img src="<?= BASE_URL ?>assets/img/avatar-siswa-light.png" alt="Siswa">
                    </div>
                    <span class="role-name-dark">Siswa</span>
                </div>
            </div>
        </div>

        <!-- SISI KANAN: Formulir Login Kartu Putih -->
        <div class="login-right-light">
            <div class="login-card-white">
                <!-- Glowing Orange Key Icon -->
                <div class="key-icon-wrapper-light">
                    <img src="<?= BASE_URL ?>assets/img/key-icon-light.png" alt="Key Security" class="img-fluid">
                </div>

                <!-- Title & Subtitle -->
                <h2 class="login-card-title-dark">Selamat Datang</h2>
                <p class="login-card-subtitle-dark">Silakan login untuk mengakses dashboard Anda</p>

                <!-- Alert Feedback -->
                <?php if ($pesan === 'gagal'): ?>
                    <div class="alert alert-danger border-0 rounded-3 py-2 px-3 small d-flex align-items-center gap-2 mb-3 text-start" role="alert" style="background: #fef2f2; color: #dc2626;">
                        <i class="bi bi-exclamation-triangle-fill fs-5 text-danger flex-shrink-0"></i>
                        <div><strong>Gagal Masuk:</strong> Username atau password tidak cocok!</div>
                    </div>
                <?php elseif ($pesan === 'logout'): ?>
                    <div class="alert alert-success border-0 rounded-3 py-2 px-3 small d-flex align-items-center gap-2 mb-3 text-start" role="alert" style="background: #f0fdf4; color: #16a34a;">
                        <i class="bi bi-check-circle-fill fs-5 text-success flex-shrink-0"></i>
                        <div>Anda telah berhasil keluar dari akun dengan aman.</div>
                    </div>
                <?php elseif ($pesan === 'belum_login'): ?>
                    <div class="alert alert-warning border-0 rounded-3 py-2 px-3 small d-flex align-items-center gap-2 mb-3 text-start" role="alert" style="background: #fffbeb; color: #d97706;">
                        <i class="bi bi-shield-exclamation fs-5 text-warning flex-shrink-0"></i>
                        <div>Sesi belum aktif. Silakan masuk terlebih dahulu.</div>
                    </div>
                <?php elseif ($pesan === 'db_error'): ?>
                    <div class="alert alert-danger border-0 rounded-3 py-2 px-3 small d-flex align-items-center gap-2 mb-3 text-start" role="alert" style="background: #fef2f2; color: #dc2626;">
                        <i class="bi bi-database-fill-slash fs-5 text-danger flex-shrink-0"></i>
                        <div><strong>Error Database:</strong> <?= e($_GET['detail'] ?? 'Query gagal diproses.') ?></div>
                    </div>
                <?php endif; ?>

                <!-- Form Login -->
                <form action="<?= BASE_URL ?>proses_login.php" method="POST" autocomplete="off">
                    <!-- Username Input -->
                    <div class="text-start mb-2">
                        <label for="username" class="form-label-dark">Username</label>
                        <div class="input-light-group">
                            <i class="bi bi-person input-icon-light"></i>
                            <input type="text" 
                                   name="username" 
                                   id="username" 
                                   class="form-control-light" 
                                   placeholder="Username" 
                                   required 
                                   autofocus>
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div class="text-start mb-3">
                        <label for="password" class="form-label-dark">Password</label>
                        <div class="input-light-group">
                            <i class="bi bi-lock input-icon-light"></i>
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   class="form-control-light" 
                                   placeholder="Password" 
                                   required>
                            <button type="button" class="btn-toggle-password-light" data-target="password" aria-label="Toggle Password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button Oranye -->
                    <button type="submit" class="btn-orange-gradient">
                        <span>Masuk ke Dashboard</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </form>

                <!-- Demo Box Akun di Bawah (Soft Blue Pill) -->
                <div class="demo-box-light">
                    <div class="demo-box-title-light">Demo Akun:</div>
                    <div class="demo-box-links-light">
                        <span class="btn-demo-fill" data-username="admin" data-password="admin123" role="button">admin (Admin)</span>
                        <span class="demo-separator-light">&bull;</span>
                        <span class="btn-demo-fill" data-username="petugas" data-password="petugas123" role="button">petugas (Petugas)</span>
                        <span class="demo-separator-light">&bull;</span>
                        <span class="btn-demo-fill" data-username="siswa" data-password="siswa123" role="button">siswa (Siswa)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="<?= BASE_URL ?>assets/js/main.js"></script>

</body>
</html>
