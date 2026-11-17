<?php
/**
 * admin/dashboard.php
 * Dashboard Utama Administrator - Tema Clean Light & Vibrant Orange dengan Log Aktivitas Sistem
 */

$pageTitle = 'Dashboard Administrator';
require_once __DIR__ . '/../includes/auth.php';

// Proteksi Sisi Server
cekRole('admin');

require_once __DIR__ . '/../includes/header.php';

// Ambil data statistik dari database secara dinamis
try {
    $totalPengguna = $pdo->query('SELECT COUNT(*) AS total FROM users')->fetch()['total'] ?? 0;
    $totalAdmin    = $pdo->query("SELECT COUNT(*) AS total FROM users WHERE role = 'admin'")->fetch()['total'] ?? 0;
    $totalPetugas  = $pdo->query("SELECT COUNT(*) AS total FROM users WHERE role = 'petugas'")->fetch()['total'] ?? 0;
    $totalSiswa    = $pdo->query("SELECT COUNT(*) AS total FROM users WHERE role = 'siswa'")->fetch()['total'] ?? 0;

    $stmtRecent  = $pdo->query('SELECT id, nama, username, role, created_at FROM users ORDER BY id DESC LIMIT 5');
    $recentUsers = $stmtRecent->fetchAll();

    // Log Aktivitas Terbaru
    $stmtLogs = $pdo->query('SELECT id, username, aktivitas, ip_address, created_at FROM log_aktivitas ORDER BY id DESC LIMIT 6');
    $recentLogs = $stmtLogs->fetchAll();
} catch (PDOException $e) {
    die("Terjadi kegagalan query: " . $e->getMessage());
}
?>

<div class="container-fluid p-0">
    <!-- Header Sambutan -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h4 class="fw-bold text-dark mb-0">Dashboard Administrator</h4>
                <span class="badge" style="background: rgba(249, 115, 22, 0.12); color: #ea580c; border: 1px solid rgba(249, 115, 22, 0.3);">
                    <i class="bi bi-shield-lock-fill me-1"></i>Super Admin
                </span>
            </div>
            <p class="text-muted small mb-0">Selamat datang kembali, <strong class="text-dark"><?= e($_SESSION['nama']) ?></strong>! Ringkasan sistem dan kontrol hak akses terpadu.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= BASE_URL ?>admin/kelola_user.php?action=export_csv" class="btn btn-outline-success rounded-3 px-3 py-2 small fw-semibold shadow-sm">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Ekspor CSV
            </a>
            <a href="<?= BASE_URL ?>admin/kelola_user.php" class="btn btn-orange-gradient px-3 py-2 small fw-semibold my-0 shadow-sm" style="width: auto; height: auto;">
                <i class="bi bi-people-fill me-1"></i>Kelola Pengguna
            </a>
            <a href="<?= BASE_URL ?>profil.php" class="btn btn-outline-secondary rounded-3 px-3 py-2 small fw-semibold">
                <i class="bi bi-person-gear me-1"></i>Profil
            </a>
        </div>
    </div>

    <!-- 4 Stat Cards Modern Light -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Total Pengguna -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase tracking-wider">Total Pengguna</div>
                        <h2 class="fw-bold text-dark my-1"><?= number_format($totalPengguna) ?></h2>
                        <div class="small fw-semibold" style="color: #ea580c;">
                            <i class="bi bi-arrow-up-right me-1"></i>Semua Akun Aktif
                        </div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-orange">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Jumlah Admin -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase tracking-wider">Administrator</div>
                        <h2 class="fw-bold text-dark my-1"><?= number_format($totalAdmin) ?></h2>
                        <div class="small fw-semibold text-danger">
                            <i class="bi bi-shield-lock me-1"></i>Hak Akses Penuh
                        </div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-rose">
                        <i class="bi bi-shield-check"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Jumlah Petugas -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase tracking-wider">Petugas</div>
                        <h2 class="fw-bold text-dark my-1"><?= number_format($totalPetugas) ?></h2>
                        <div class="small fw-semibold text-primary">
                            <i class="bi bi-person-badge me-1"></i>Akses Operasional
                        </div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-blue">
                        <i class="bi bi-person-workspace"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Jumlah Siswa -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase tracking-wider">Siswa</div>
                        <h2 class="fw-bold text-dark my-1"><?= number_format($totalSiswa) ?></h2>
                        <div class="small fw-semibold text-success">
                            <i class="bi bi-mortarboard me-1"></i>Pengguna Siswa
                        </div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-green">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row: Tabel Pengguna Terbaru & Ringkasan Server -->
    <div class="row g-4 mb-4">
        <!-- Tabel Pengguna Terbaru -->
        <div class="col-lg-7">
            <div class="card card-modern h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-1">Pengguna Terdaftar Terbaru</h6>
                        <small class="text-muted">Daftar pengguna yang baru saja dibuat atau diperbarui di sistem.</small>
                    </div>
                    <a href="<?= BASE_URL ?>admin/kelola_user.php" class="btn btn-sm btn-outline-warning rounded-pill px-3" style="color: #ea580c; border-color: #f97316;">
                        Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0 mt-3">
                    <div class="table-responsive">
                        <table class="table table-modern align-middle">
                            <thead>
                                <tr>
                                    <th>Pengguna</th>
                                    <th>Username</th>
                                    <th>Role Akses</th>
                                    <th>Waktu Dibuat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentUsers)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">Belum ada data pengguna.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentUsers as $usr): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="user-avatar-initials bg-dark">
                                                        <?= strtoupper(substr($usr['nama'], 0, 1)) ?>
                                                    </div>
                                                    <span class="fw-semibold text-dark"><?= e($usr['nama']) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-secondary border font-monospace">
                                                    @<?= e($usr['username']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?= getRoleBadge($usr['role']) ?>
                                            </td>
                                            <td class="text-muted small">
                                                <i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($usr['created_at'])) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Log Aktivitas Audit Terbaru -->
        <div class="col-lg-5">
            <div class="card card-modern h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-1">Riwayat Aktivitas Sistem</h6>
                        <small class="text-muted">Catatan audit log keamanan terkini.</small>
                    </div>
                    <i class="bi bi-shield-check text-primary fs-5"></i>
                </div>
                <div class="card-body p-4 pt-2">
                    <?php if (empty($recentLogs)): ?>
                        <div class="text-center py-4 text-muted small">Belum ada catatan aktivitas.</div>
                    <?php else: ?>
                        <div class="timeline small">
                            <?php foreach ($recentLogs as $log): ?>
                                <div class="border-bottom py-2.5">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="badge bg-light text-dark border font-monospace">@<?= e($log['username']) ?></span>
                                        <small class="text-muted" style="font-size: 0.72rem;"><?= date('H:i, d M', strtotime($log['created_at'])) ?></small>
                                    </div>
                                    <div class="text-dark fw-medium"><?= e($log['aktivitas']) ?></div>
                                    <div class="text-muted" style="font-size: 0.72rem;">IP: <?= e($log['ip_address']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
