<?php
/**
 * profil.php
 * Pengaturan Profil & Ganti Sandi - Tema Clean Light & Vibrant Orange
 */

$pageTitle = 'Profil Saya & Pengaturan Akun';
require_once __DIR__ . '/includes/header.php';

$userId = $_SESSION['user_id'];
$pesanSukses = '';
$pesanError  = '';

try {
    $stmt = $pdo->prepare('SELECT id, nama, username, password, role, created_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    if (!$user) {
        header('Location: ' . BASE_URL . 'logout.php');
        exit;
    }
} catch (PDOException $e) {
    die("Terjadi kesalahan: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $namaBaru = trim($_POST['nama'] ?? '');

        if (empty($namaBaru)) {
            $pesanError = 'Nama lengkap tidak boleh kosong!';
        } elseif (strlen($namaBaru) < 3) {
            $pesanError = 'Nama lengkap minimal 3 karakter!';
        } else {
            try {
                $updateStmt = $pdo->prepare('UPDATE users SET nama = :nama WHERE id = :id');
                $updateStmt->execute([
                    ':nama' => $namaBaru,
                    ':id'   => $userId
                ]);

                $_SESSION['nama'] = $namaBaru;
                $user['nama']     = $namaBaru;
                catatLog("Pengguna @{$_SESSION['username']} memperbarui nama profil menjadi '{$namaBaru}'.");
                $pesanSukses      = 'Nama profil Anda berhasil diperbarui!';
            } catch (PDOException $e) {
                $pesanError = 'Gagal memperbarui profil: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'change_password') {
        $passwordLama       = $_POST['password_lama'] ?? '';
        $passwordBaru       = $_POST['password_baru'] ?? '';
        $konfirmasiPassword = $_POST['konfirmasi_password'] ?? '';

        if (empty($passwordLama) || empty($passwordBaru) || empty($konfirmasiPassword)) {
            $pesanError = 'Semua bidang kata sandi wajib diisi!';
        } elseif (!password_verify($passwordLama, $user['password'])) {
            $pesanError = 'Kata sandi saat ini (lama) tidak cocok!';
        } elseif (strlen($passwordBaru) < 6) {
            $pesanError = 'Kata sandi baru minimal 6 karakter!';
        } elseif ($passwordBaru !== $konfirmasiPassword) {
            $pesanError = 'Konfirmasi kata sandi baru tidak cocok!';
        } else {
            try {
                $hashedNewPassword = password_hash($passwordBaru, PASSWORD_DEFAULT);
                $updatePassStmt = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
                $updatePassStmt->execute([
                    ':password' => $hashedNewPassword,
                    ':id'       => $userId
                ]);

                $user['password'] = $hashedNewPassword;
                catatLog("Pengguna @{$_SESSION['username']} berhasil mengubah kata sandi akun.");
                $pesanSukses      = 'Kata sandi Anda berhasil diperbarui!';
            } catch (PDOException $e) {
                $pesanError = 'Gagal mengubah kata sandi: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="container-fluid p-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold text-dark mb-1">Pengaturan Profil &amp; Keamanan</h4>
            <p class="text-muted small mb-0">Kelola identitas akun dan perbarui kata sandi secara berkala.</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>dashboard.php" class="btn btn-outline-secondary rounded-3 px-3 py-2 small fw-semibold">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Dashboard
            </a>
        </div>
    </div>

    <?php if (!empty($pesanSukses)): ?>
        <div class="alert alert-success border-0 rounded-3 py-2.5 px-3 small d-flex align-items-center gap-2 mb-4" role="alert" style="background: #f0fdf4; color: #16a34a;">
            <i class="bi bi-check-circle-fill text-success fs-5"></i>
            <div><strong>Berhasil:</strong> <?= e($pesanSukses) ?></div>
            <button type="button" class="btn-close small p-3 ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($pesanError)): ?>
        <div class="alert alert-danger border-0 rounded-3 py-2.5 px-3 small d-flex align-items-center gap-2 mb-4" role="alert" style="background: #fef2f2; color: #dc2626;">
            <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
            <div><strong>Peringatan:</strong> <?= e($pesanError) ?></div>
            <button type="button" class="btn-close small p-3 ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Kolom Kiri: Profil Card -->
        <div class="col-lg-4">
            <div class="card card-modern p-4 text-center">
                <div class="position-relative d-inline-block mx-auto mb-3">
                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 80px; height: 80px; font-size: 2rem; font-weight: 800; background: var(--orange-gradient);">
                        <?= strtoupper(substr($user['nama'], 0, 1)) ?>
                    </div>
                </div>
                <h5 class="fw-bold text-dark mb-1"><?= e($user['nama']) ?></h5>
                <p class="text-muted small mb-2">@<?= e($user['username']) ?></p>
                <div class="mb-4">
                    <?= getRoleBadge($user['role']) ?>
                </div>

                <hr class="border-light-subtle">

                <div class="text-start small">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">ID Pengguna:</span>
                        <span class="fw-semibold text-dark">#<?= e($user['id']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Hak Akses:</span>
                        <span class="fw-semibold text-capitalize" style="color: #ea580c;"><?= e($user['role']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Terdaftar:</span>
                        <span class="fw-semibold text-dark"><?= date('d M Y, H:i', strtotime($user['created_at'])) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted">Status:</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Form Ubah Nama & Sandi -->
        <div class="col-lg-8">
            <div class="card card-modern p-4 mb-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="badge p-2 rounded-3" style="background: rgba(249, 115, 22, 0.12); color: #ea580c;">
                        <i class="bi bi-pencil-square fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Informasi Pribadi</h6>
                        <small class="text-muted">Perbarui nama tampilan akun Anda.</small>
                    </div>
                </div>

                <form action="<?= BASE_URL ?>profil.php" method="POST">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="mb-3">
                        <label for="username_dis" class="form-label-dark">Username (Tidak dapat diubah)</label>
                        <input type="text" id="username_dis" class="form-control-light bg-light" value="<?= e($user['username']) ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="nama" class="form-label-dark">Nama Lengkap</label>
                        <input type="text" name="nama" id="nama" class="form-control-light" value="<?= e($user['nama']) ?>" required>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-orange-gradient px-4 py-2 small fw-semibold my-0" style="width: auto;">
                            <i class="bi bi-save me-1"></i>Simpan Perubahan Nama
                        </button>
                    </div>
                </form>
            </div>

            <div class="card card-modern p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="badge p-2 rounded-3" style="background: rgba(245, 158, 11, 0.12); color: #d97706;">
                        <i class="bi bi-shield-lock-fill fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Keamanan &amp; Kata Sandi</h6>
                        <small class="text-muted">Ganti kata sandi secara berkala untuk menjaga keamanan akun.</small>
                    </div>
                </div>

                <form action="<?= BASE_URL ?>profil.php" method="POST">
                    <input type="hidden" name="action" value="change_password">

                    <div class="mb-3">
                        <label for="password_lama" class="form-label-dark">Kata Sandi Saat Ini</label>
                        <div class="input-light-group mb-0">
                            <i class="bi bi-key input-icon-light"></i>
                            <input type="password" name="password_lama" id="password_lama" class="form-control-light" placeholder="Masukkan sandi saat ini" required>
                            <button type="button" class="btn-toggle-password-light" data-target="password_lama" aria-label="Toggle Sandi">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="password_baru" class="form-label-dark">Kata Sandi Baru</label>
                            <div class="input-light-group mb-0">
                                <i class="bi bi-lock input-icon-light"></i>
                                <input type="password" name="password_baru" id="password_baru" class="form-control-light" placeholder="Minimal 6 karakter" required>
                                <button type="button" class="btn-toggle-password-light" data-target="password_baru" aria-label="Toggle Sandi">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="konfirmasi_password" class="form-label-dark">Ulangi Kata Sandi Baru</label>
                            <div class="input-light-group mb-0">
                                <i class="bi bi-lock-fill input-icon-light"></i>
                                <input type="password" name="konfirmasi_password" id="konfirmasi_password" class="form-control-light" placeholder="Ulangi sandi baru" required>
                                <button type="button" class="btn-toggle-password-light" data-target="konfirmasi_password" aria-label="Toggle Sandi">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-orange-gradient px-4 py-2 small fw-semibold my-0" style="width: auto;">
                            <i class="bi bi-key-fill me-1"></i>Perbarui Kata Sandi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
