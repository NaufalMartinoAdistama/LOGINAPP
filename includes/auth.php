<?php
/**
 * includes/auth.php
 * Middleware autentikasi dan otorisasi Role-Based Access Control (RBAC).
 */

require_once __DIR__ . '/../config/koneksi.php';

/**
 * Memeriksa apakah pengguna sudah login.
 * Jika belum, alihkan ke halaman login dengan notifikasi.
 */
function cekLogin() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'index.php?pesan=belum_login');
        exit;
    }
}

/**
 * Memeriksa apakah role pengguna saat ini diizinkan untuk mengakses halaman.
 * Jika tidak diizinkan, kembalikan HTTP 403 Forbidden dengan halaman error elegan.
 * 
 * @param string|array $allowedRoles Role tunggal atau array role yang diperbolehkan.
 */
function cekRole($allowedRoles) {
    cekLogin();

    $allowedRoles = (array) $allowedRoles;
    $currentRole  = $_SESSION['role'] ?? '';

    if (!in_array($currentRole, $allowedRoles, true)) {
        http_response_code(403);
        tampilkanHalaman403($currentRole, $allowedRoles);
        exit;
    }
}

/**
 * Mengambil data ringkas pengguna yang sedang login saat ini dari sesi.
 */
function currentUser() {
    return [
        'id'       => $_SESSION['user_id'] ?? null,
        'nama'     => $_SESSION['nama'] ?? 'Pengguna',
        'username' => $_SESSION['username'] ?? '',
        'role'     => $_SESSION['role'] ?? '',
    ];
}

/**
 * Mencatat log aktivitas sistem audit trail.
 */
function catatLog($aktivitas) {
    global $pdo;
    try {
        if (isset($pdo)) {
            $userId   = $_SESSION['user_id'] ?? null;
            $username = $_SESSION['username'] ?? 'Tamu';
            $ip       = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            $stmt = $pdo->prepare('INSERT INTO log_aktivitas (user_id, username, aktivitas, ip_address) VALUES (:uid, :uname, :akt, :ip)');
            $stmt->execute([
                ':uid'   => $userId,
                ':uname' => $username,
                ':akt'   => $aktivitas,
                ':ip'    => $ip
            ]);
        }
    } catch (Exception $e) {
        // Abaikan jika tabel log belum siap
    }
}

/**
 * Sanitasi output teks untuk mencegah Cross-Site Scripting (XSS).
 */
function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Mendapatkan elemen badge HTML yang rapi sesuai role.
 */
function getRoleBadge($role) {
    switch (strtolower($role)) {
        case 'admin':
            return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 fw-semibold"><i class="bi bi-shield-lock-fill me-1"></i>Admin</span>';
        case 'petugas':
            return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1.5 fw-semibold"><i class="bi bi-person-badge-fill me-1"></i>Petugas</span>';
        case 'siswa':
            return '<span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 fw-semibold"><i class="bi bi-mortarboard-fill me-1"></i>Siswa</span>';
        default:
            return '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1.5 fw-semibold">' . e(ucfirst($role)) . '</span>';
    }
}

/**
 * Menampilkan halaman error 403 Forbidden modern & responsif.
 */
function tampilkanHalaman403($currentRole, $allowedRoles) {
    $roleName = ucfirst($currentRole);
    $required = implode(', ', array_map('ucfirst', $allowedRoles));
    $baseUrl  = BASE_URL;

    echo "<!DOCTYPE html>
    <html lang='id'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>403 - Akses Ditolak | " . APP_NAME . "</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
        <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css'>
        <link href='https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap' rel='stylesheet'>
        <style>
            body {
                font-family: 'Plus Jakarta Sans', sans-serif;
                background-color: #f8fafc;
                color: #1e293b;
            }
            .card-error {
                border-radius: 20px;
                border: 1px solid #e2e8f0;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            }
        </style>
    </head>
    <body class='d-flex align-items-center min-vh-100 py-5'>
        <div class='container'>
            <div class='row justify-content-center'>
                <div class='col-md-7 col-lg-5 text-center'>
                    <div class='card card-error p-4 p-md-5 bg-white'>
                        <div class='mb-4'>
                            <div class='d-inline-flex p-3 rounded-circle bg-danger-subtle text-danger mb-3'>
                                <i class='bi bi-shield-x fs-1'></i>
                            </div>
                            <h1 class='display-6 fw-bold text-danger mb-1'>403</h1>
                            <h4 class='fw-bold text-dark'>Akses Ditolak (Forbidden)</h4>
                            <p class='text-muted small'>Anda tidak memiliki izin (hak akses) untuk membuka halaman atau fitur ini.</p>
                        </div>
                        <div class='bg-light p-3 rounded-3 text-start small mb-4'>
                            <div class='d-flex justify-content-between mb-2'>
                                <span class='text-muted'>Role Anda:</span>
                                <span class='fw-bold text-capitalize text-danger'>{$roleName}</span>
                            </div>
                            <div class='d-flex justify-content-between'>
                                <span class='text-muted'>Hak Akses Dibutuhkan:</span>
                                <span class='fw-bold text-primary'>{$required}</span>
                            </div>
                        </div>
                        <div class='d-grid gap-2'>
                            <a href='{$baseUrl}dashboard.php' class='btn btn-primary py-2 fw-semibold'>
                                <i class='bi bi-speedometer2 me-2'></i>Kembali ke Dashboard Saya
                            </a>
                            <a href='{$baseUrl}logout.php' class='btn btn-outline-secondary py-2 fw-semibold'>
                                <i class='bi bi-box-arrow-right me-2'></i>Keluar / Ganti Akun
                            </a>
                        </div>
                    </div>
                    <p class='text-muted small mt-4'>&copy; " . date('Y') . " " . APP_TITLE . "</p>
                </div>
            </div>
        </div>
    </body>
    </html>";
}
