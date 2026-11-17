<?php
/**
 * admin/kelola_user.php
 * Manajemen Pengguna Lengkap (CRUD, Export CSV, Reset Sandi, Audit Log) - Tema Clean Light & Vibrant Orange
 */

$pageTitle = 'Kelola Pengguna Sistem';
require_once __DIR__ . '/../includes/auth.php';

// Proteksi Sisi Server
cekRole('admin');

$currentAdminId = (int) $_SESSION['user_id'];
$pesanSukses = '';
$pesanError  = '';

// -------------------------------------------------------------
// FITUR: EXPORT DATA PENGGUNA KE CSV / EXCEL
// -------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    catatLog("Admin mengekspor seluruh data pengguna ke CSV.");
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=data_pengguna_' . date('Y-m-d_His') . '.csv');
    
    $output = fopen('php://output', 'w');
    // Header Kolom CSV
    fputcsv($output, ['ID Pengguna', 'Nama Lengkap', 'Username', 'Hak Akses (Role)', 'Waktu Pendaftaran']);
    
    $stmtAll = $pdo->query('SELECT id, nama, username, role, created_at FROM users ORDER BY id ASC');
    while ($row = $stmtAll->fetch()) {
        fputcsv($output, [
            $row['id'],
            $row['nama'],
            $row['username'],
            ucfirst($row['role']),
            $row['created_at']
        ]);
    }
    fclose($output);
    exit;
}

// -------------------------------------------------------------
// PROSES AKSI POST (TAMBAH, EDIT, HAPUS, RESET PASSWORD)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. TAMBAH PENGGUNA BARU
    if ($action === 'tambah') {
        $nama     = trim($_POST['nama'] ?? '');
        $username = strtolower(trim($_POST['username'] ?? ''));
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? '';

        $allowedRoles = ['admin', 'petugas', 'siswa'];

        if (empty($nama) || empty($username) || empty($password) || empty($role)) {
            $pesanError = 'Semua field (Nama, Username, Password, dan Role) wajib diisi!';
        } elseif (!in_array($role, $allowedRoles, true)) {
            $pesanError = 'Role yang dipilih tidak valid!';
        } elseif (strlen($password) < 6) {
            $pesanError = 'Password pengguna baru minimal 6 karakter!';
        } else {
            try {
                $cekStmt = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
                $cekStmt->execute([':username' => $username]);
                if ($cekStmt->fetch()) {
                    $pesanError = "Username '@{$username}' sudah terdaftar! Gunakan username lain.";
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $insertStmt = $pdo->prepare('INSERT INTO users (nama, username, password, role) VALUES (:nama, :username, :password, :role)');
                    $insertStmt->execute([
                        ':nama'     => $nama,
                        ':username' => $username,
                        ':password' => $hashedPassword,
                        ':role'     => $role
                    ]);
                    catatLog("Menambahkan pengguna baru: '{$nama}' (@{$username}) role {$role}.");
                    $pesanSukses = "Pengguna baru '{$nama}' (@{$username}) dengan role {$role} berhasil ditambahkan!";
                }
            } catch (PDOException $e) {
                $pesanError = 'Gagal menambahkan pengguna: ' . $e->getMessage();
            }
        }
    }

    // 2. EDIT PENGGUNA & UBAH ROLE
    elseif ($action === 'edit') {
        $id           = (int) ($_POST['id'] ?? 0);
        $nama         = trim($_POST['nama'] ?? '');
        $username     = strtolower(trim($_POST['username'] ?? ''));
        $role         = $_POST['role'] ?? '';
        $passwordBaru = $_POST['password_baru'] ?? '';

        $allowedRoles = ['admin', 'petugas', 'siswa'];

        if ($id <= 0 || empty($nama) || empty($username) || empty($role)) {
            $pesanError = 'Data edit tidak valid atau field penting kosong!';
        } elseif (!in_array($role, $allowedRoles, true)) {
            $pesanError = 'Role yang dipilih tidak valid!';
        } else {
            try {
                if ($id === $currentAdminId && $role !== 'admin') {
                    $pesanError = 'Anda tidak dapat mengubah role akun Administrator Anda sendiri menjadi non-admin!';
                } else {
                    $cekUserStmt = $pdo->prepare('SELECT id FROM users WHERE username = :username AND id != :id LIMIT 1');
                    $cekUserStmt->execute([':username' => $username, ':id' => $id]);
                    if ($cekUserStmt->fetch()) {
                        $pesanError = "Username '@{$username}' telah digunakan pengguna lain!";
                    } else {
                        if (!empty($passwordBaru)) {
                            if (strlen($passwordBaru) < 6) {
                                $pesanError = 'Password baru minimal 6 karakter!';
                            } else {
                                $hashedNewPassword = password_hash($passwordBaru, PASSWORD_DEFAULT);
                                $updateStmt = $pdo->prepare('UPDATE users SET nama = :nama, username = :username, role = :role, password = :password WHERE id = :id');
                                $updateStmt->execute([
                                    ':nama'     => $nama,
                                    ':username' => $username,
                                    ':role'     => $role,
                                    ':password' => $hashedNewPassword,
                                    ':id'       => $id
                                ]);
                                catatLog("Memperbarui data & sandi pengguna ID #{$id} ('{$nama}').");
                                $pesanSukses = "Data pengguna dan password '{$nama}' berhasil diperbarui!";
                            }
                        } else {
                            $updateStmt = $pdo->prepare('UPDATE users SET nama = :nama, username = :username, role = :role WHERE id = :id');
                            $updateStmt->execute([
                                ':nama'     => $nama,
                                ':username' => $username,
                                ':role'     => $role,
                                ':id'       => $id
                            ]);
                            catatLog("Memperbarui profil pengguna ID #{$id} ('{$nama}').");
                            $pesanSukses = "Data pengguna '{$nama}' berhasil diperbarui!";
                        }

                        if ($id === $currentAdminId) {
                            $_SESSION['nama']     = $nama;
                            $_SESSION['username'] = $username;
                        }
                    }
                }
            } catch (PDOException $e) {
                $pesanError = 'Gagal memperbarui pengguna: ' . $e->getMessage();
            }
        }
    }

    // 3. RESET KATA SANDI CEPAT KE DEFAULT
    elseif ($action === 'reset_password') {
        $id = (int) ($_POST['id'] ?? 0);

        try {
            $stmtUser = $pdo->prepare('SELECT id, nama, username, role FROM users WHERE id = :id LIMIT 1');
            $stmtUser->execute([':id' => $id]);
            $targetUser = $stmtUser->fetch();

            if ($targetUser) {
                $defaultPass = $targetUser['role'] . '123';
                $newHash = password_hash($defaultPass, PASSWORD_DEFAULT);

                $updatePass = $pdo->prepare('UPDATE users SET password = :p WHERE id = :id');
                $updatePass->execute([':p' => $newHash, ':id' => $id]);

                catatLog("Mereset kata sandi pengguna ID #{$id} ('{$targetUser['nama']}') ke default.");
                $pesanSukses = "Kata sandi pengguna '{$targetUser['nama']}' berhasil direset menjadi: '<strong>{$defaultPass}</strong>'!";
            } else {
                $pesanError = 'Pengguna tidak ditemukan.';
            }
        } catch (PDOException $e) {
            $pesanError = 'Gagal mereset kata sandi: ' . $e->getMessage();
        }
    }

    // 4. HAPUS PENGGUNA
    elseif ($action === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $pesanError = 'ID pengguna tidak valid!';
        } elseif ($id === $currentAdminId) {
            $pesanError = 'Keamanan: Anda tidak dapat menghapus akun Administrator yang sedang Anda gunakan!';
        } else {
            try {
                $getStmt = $pdo->prepare('SELECT nama, username FROM users WHERE id = :id LIMIT 1');
                $getStmt->execute([':id' => $id]);
                $userToDelete = $getStmt->fetch();

                if ($userToDelete) {
                    $delStmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
                    $delStmt->execute([':id' => $id]);
                    catatLog("Menghapus akun pengguna ID #{$id} ('{$userToDelete['nama']}').");
                    $pesanSukses = "Akun pengguna '{$userToDelete['nama']}' (@{$userToDelete['username']}) berhasil dihapus.";
                } else {
                    $pesanError = 'Pengguna tidak ditemukan atau sudah dihapus.';
                }
            } catch (PDOException $e) {
                $pesanError = 'Gagal menghapus pengguna: ' . $e->getMessage();
            }
        }
    }
}

// -------------------------------------------------------------
// FILTER & PENCARIAN PENGGUNA
// -------------------------------------------------------------
$searchKeyword = trim($_GET['search'] ?? '');
$filterRole    = trim($_GET['filter_role'] ?? 'all');

try {
    $sql = 'SELECT id, nama, username, role, created_at FROM users WHERE 1=1';
    $params = [];

    if (!empty($searchKeyword)) {
        $sql .= ' AND (nama LIKE :kw OR username LIKE :kw)';
        $params[':kw'] = '%' . $searchKeyword . '%';
    }

    if (!empty($filterRole) && $filterRole !== 'all') {
        $sql .= ' AND role = :role';
        $params[':role'] = $filterRole;
    }

    $sql .= ' ORDER BY id DESC';

    $stmtUsers = $pdo->prepare($sql);
    $stmtUsers->execute($params);
    $users = $stmtUsers->fetchAll();
} catch (PDOException $e) {
    die("Terjadi kegagalan mengambil data: " . $e->getMessage());
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid p-0">
    <!-- Header Halaman -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold text-dark mb-1">Kelola Pengguna &amp; Hak Akses</h4>
            <p class="text-muted small mb-0">Manajemen akun terpadu, ekspor laporan, reset sandi, dan pembagian hak akses (RBAC).</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <!-- Tombol Export CSV -->
            <a href="<?= BASE_URL ?>admin/kelola_user.php?action=export_csv" class="btn btn-outline-success rounded-3 px-3 py-2 small fw-semibold shadow-sm" title="Unduh data pengguna ke format Excel / CSV">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Ekspor CSV
            </a>
            <!-- Tombol Tambah User -->
            <button type="button" class="btn btn-orange-gradient px-3 py-2 small fw-semibold my-0 shadow-sm" style="width: auto; height: auto;" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
                <i class="bi bi-person-plus-fill me-1"></i>Tambah Pengguna Baru
            </button>
            <a href="<?= BASE_URL ?>admin/dashboard.php" class="btn btn-outline-secondary rounded-3 px-3 py-2 small fw-semibold">
                <i class="bi bi-arrow-left me-1"></i>Dashboard
            </a>
        </div>
    </div>

    <!-- Alert Notifikasi -->
    <?php if (!empty($pesanSukses)): ?>
        <div class="alert alert-success border-0 rounded-3 py-2.5 px-3 small d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert" style="background: #f0fdf4; color: #16a34a;">
            <i class="bi bi-check-circle-fill text-success fs-5"></i>
            <div><?= $pesanSukses ?></div>
            <button type="button" class="btn-close small p-3 ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($pesanError)): ?>
        <div class="alert alert-danger border-0 rounded-3 py-2.5 px-3 small d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert" style="background: #fef2f2; color: #dc2626;">
            <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
            <div><strong>Peringatan:</strong> <?= e($pesanError) ?></div>
            <button type="button" class="btn-close small p-3 ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Filter & Pencarian -->
    <div class="card card-modern p-3 mb-4">
        <form method="GET" action="<?= BASE_URL ?>admin/kelola_user.php" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-light-group mb-0">
                    <i class="bi bi-search input-icon-light"></i>
                    <input type="text" name="search" class="form-control-light" placeholder="Cari nama atau username..." value="<?= e($searchKeyword) ?>" style="height: 42px;">
                </div>
            </div>
            <div class="col-md-4">
                <select name="filter_role" class="form-select bg-white border-light-subtle" style="height: 42px;">
                    <option value="all" <?= $filterRole === 'all' ? 'selected' : '' ?>>Semua Role (Hak Akses)</option>
                    <option value="admin" <?= $filterRole === 'admin' ? 'selected' : '' ?>>Administrator</option>
                    <option value="petugas" <?= $filterRole === 'petugas' ? 'selected' : '' ?>>Petugas</option>
                    <option value="siswa" <?= $filterRole === 'siswa' ? 'selected' : '' ?>>Siswa</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-orange-gradient flex-grow-1 my-0 fw-semibold" style="height: 42px;">
                    <i class="bi bi-filter me-1"></i>Filter
                </button>
                <?php if (!empty($searchKeyword) || $filterRole !== 'all'): ?>
                    <a href="<?= BASE_URL ?>admin/kelola_user.php" class="btn btn-outline-secondary d-flex align-items-center px-3" title="Reset Filter">
                        <i class="bi bi-x-circle"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabel Data Pengguna -->
    <div class="card card-modern overflow-hidden">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold text-dark mb-0">Daftar Seluruh Pengguna</h6>
                <small class="text-muted">Total: <strong style="color: #ea580c;"><?= count($users) ?></strong> akun pengguna terdaftar.</small>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern align-middle">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Nama Lengkap</th>
                            <th>Username</th>
                            <th>Role / Hak Akses</th>
                            <th>Tanggal Terdaftar</th>
                            <th class="text-end" style="width: 170px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-people fs-1 d-block mb-2 opacity-50"></i>
                                    Tidak ada data pengguna yang sesuai kriteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($users as $u): ?>
                                <tr>
                                    <td class="text-muted small fw-semibold"><?= $no++ ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="user-avatar-initials bg-dark">
                                                <?= strtoupper(substr($u['nama'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <span class="fw-semibold text-dark d-block"><?= e($u['nama']) ?></span>
                                                <?php if ((int)$u['id'] === $currentAdminId): ?>
                                                    <span class="badge" style="background: rgba(249, 115, 22, 0.12); color: #ea580c; font-size: 0.65rem;">Akun Anda</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border font-monospace">
                                            @<?= e($u['username']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= getRoleBadge($u['role']) ?>
                                    </td>
                                    <td class="text-muted small">
                                        <i class="bi bi-calendar-event me-1"></i><?= date('d M Y, H:i', strtotime($u['created_at'])) ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <!-- Tombol Reset Sandi Cepat -->
                                            <form method="POST" action="<?= BASE_URL ?>admin/kelola_user.php" class="d-inline">
                                                <input type="hidden" name="action" value="reset_password">
                                                <input type="hidden" name="id" value="<?= e($u['id']) ?>">
                                                <button type="submit" 
                                                        class="btn btn-sm btn-outline-info px-2 py-1 rounded-2 btn-confirm-delete"
                                                        data-confirm="Reset kata sandi pengguna '<?= e($u['nama']) ?>' ke sandi awal default (<?= e($u['role']) ?>123)?"
                                                        title="Reset Password ke Default">
                                                    <i class="bi bi-key"></i>
                                                </button>
                                            </form>

                                            <!-- Tombol Edit Modal -->
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-warning px-2 py-1 rounded-2 btn-edit-user"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalEditUser"
                                                    data-id="<?= e($u['id']) ?>"
                                                    data-nama="<?= e($u['nama']) ?>"
                                                    data-username="<?= e($u['username']) ?>"
                                                    data-role="<?= e($u['role']) ?>"
                                                    title="Edit Pengguna"
                                                    style="color: #ea580c; border-color: #f97316;">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <!-- Tombol Hapus -->
                                            <?php if ((int)$u['id'] !== $currentAdminId): ?>
                                                <form method="POST" action="<?= BASE_URL ?>admin/kelola_user.php" class="d-inline">
                                                    <input type="hidden" name="action" value="hapus">
                                                    <input type="hidden" name="id" value="<?= e($u['id']) ?>">
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-outline-danger px-2 py-1 rounded-2 btn-confirm-delete"
                                                            data-confirm="Apakah Anda yakin ingin menghapus pengguna '<?= e($u['nama']) ?>' (@<?= e($u['username']) ?>)?"
                                                            title="Hapus Pengguna">
                                                        <i class="bi bi-trash3-fill"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary px-2 py-1 rounded-2 opacity-50" disabled title="Tidak dapat menghapus akun sendiri">
                                                    <i class="bi bi-slash-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
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

<!-- ============================================================== -->
<!-- MODAL: TAMBAH PENGGUNA BARU                                    -->
<!-- ============================================================== -->
<div class="modal fade" id="modalTambahUser" tabindex="-1" aria-labelledby="modalTambahUserLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4" style="background-color: #ffffff;">
            <div class="modal-header border-bottom px-4 pt-4 pb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="badge p-2 rounded-3" style="background: rgba(249, 115, 22, 0.12); color: #ea580c;">
                        <i class="bi bi-person-plus-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark" id="modalTambahUserLabel">Tambah Pengguna Baru</h5>
                        <small class="text-muted">Buat akun baru dengan hak akses yang ditentukan.</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>admin/kelola_user.php" method="POST">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body px-4 py-3">
                    <div class="mb-3">
                        <label for="tambah_nama" class="form-label-dark">Nama Lengkap</label>
                        <input type="text" name="nama" id="tambah_nama" class="form-control-light" placeholder="Contoh: Muhammad Farhan" required>
                    </div>

                    <div class="mb-3">
                        <label for="tambah_username" class="form-label-dark">Username (Unik)</label>
                        <input type="text" name="username" id="tambah_username" class="form-control-light" placeholder="farhan123" required>
                    </div>

                    <div class="mb-3">
                        <label for="tambah_role" class="form-label-dark">Hak Akses (Role)</label>
                        <select name="role" id="tambah_role" class="form-select bg-white border-light-subtle" required>
                            <option value="" disabled selected>-- Pilih Tingkat Hak Akses --</option>
                            <option value="admin">Administrator (Akses Penuh Master)</option>
                            <option value="petugas">Petugas (Operasional &amp; Layanan)</option>
                            <option value="siswa">Siswa (Portal Akademik &amp; Profil)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="tambah_password" class="form-label-dark">Kata Sandi Awal</label>
                        <div class="input-light-group mb-0">
                            <i class="bi bi-lock input-icon-light"></i>
                            <input type="password" name="password" id="tambah_password" class="form-control-light" placeholder="Minimal 6 karakter" required>
                            <button type="button" class="btn-toggle-password-light" data-target="tambah_password" aria-label="Lihat Sandi">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light rounded-3 px-3 py-2 small fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-orange-gradient px-4 py-2 small fw-semibold my-0" style="width: auto;">
                        <i class="bi bi-check-lg me-1"></i>Simpan Pengguna
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: EDIT PENGGUNA & UBAH ROLE                               -->
<!-- ============================================================== -->
<div class="modal fade" id="modalEditUser" tabindex="-1" aria-labelledby="modalEditUserLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4" style="background-color: #ffffff;">
            <div class="modal-header border-bottom px-4 pt-4 pb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="badge p-2 rounded-3" style="background: rgba(249, 115, 22, 0.12); color: #ea580c;">
                        <i class="bi bi-pencil-square fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark" id="modalEditUserLabel">Edit Data Pengguna</h5>
                        <small class="text-muted">Ubah profil atau sesuaikan level hak akses.</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>admin/kelola_user.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">

                <div class="modal-body px-4 py-3">
                    <div class="mb-3">
                        <label for="edit_nama" class="form-label-dark">Nama Lengkap</label>
                        <input type="text" name="nama" id="edit_nama" class="form-control-light" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_username" class="form-label-dark">Username</label>
                        <input type="text" name="username" id="edit_username" class="form-control-light" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_role" class="form-label-dark">Hak Akses (Role)</label>
                        <select name="role" id="edit_role" class="form-select bg-white border-light-subtle" required>
                            <option value="admin">Administrator (Akses Penuh)</option>
                            <option value="petugas">Petugas (Operasional &amp; Layanan)</option>
                            <option value="siswa">Siswa (Portal Akademik &amp; Profil)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_password" class="form-label-dark">
                            Kata Sandi Baru <span class="text-muted fw-normal">(Kosongkan jika tidak diubah)</span>
                        </label>
                        <div class="input-light-group mb-0">
                            <i class="bi bi-lock input-icon-light"></i>
                            <input type="password" name="password_baru" id="edit_password" class="form-control-light" placeholder="Masukkan jika ingin mengganti sandi">
                            <button type="button" class="btn-toggle-password-light" data-target="edit_password" aria-label="Lihat Sandi">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light rounded-3 px-3 py-2 small fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-orange-gradient px-4 py-2 small fw-semibold my-0" style="width: auto;">
                        <i class="bi bi-save me-1"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editButtons = document.querySelectorAll('.btn-edit-user');
    editButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_nama').value = this.getAttribute('data-nama');
            document.getElementById('edit_username').value = this.getAttribute('data-username');
            document.getElementById('edit_role').value = this.getAttribute('data-role');
            document.getElementById('edit_password').value = '';
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
