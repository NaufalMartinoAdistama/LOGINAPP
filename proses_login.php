<?php
/**
 * proses_login.php
 * Pemrosesan Autentikasi Pengguna: PDO Prepared Statement, Password Verify, dan Session Regeneration.
 */

require_once __DIR__ . '/config/koneksi.php';

// Hanya izinkan metode request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

// Ambil input dari form login
$rawUsername = trim($_POST['username'] ?? '');
$password    = $_POST['password'] ?? '';

// Validasi input tidak boleh kosong
if (empty($rawUsername) || empty($password)) {
    header('Location: ' . BASE_URL . 'index.php?pesan=gagal');
    exit;
}

// Normalisasi jika pengguna menginput email admin@smkrpl.sch.id
$username = strtolower($rawUsername);
if ($username === 'admin@smkrpl.sch.id') {
    $username = 'admin';
}

try {
    // Siapkan Prepared Statement PDO untuk mencegah SQL Injection
    $stmt = $pdo->prepare('SELECT id, nama, username, password, role FROM users WHERE username = :username OR (username = "admin" AND :raw = "admin@smkrpl.sch.id") LIMIT 1');
    $stmt->execute([
        ':username' => $username,
        ':raw'      => strtolower($rawUsername)
    ]);
    $user = $stmt->fetch();

    // Verifikasi keberadaan pengguna dan kecocokan password menggunakan password_verify()
    if ($user && password_verify($password, $user['password'])) {
        // Mencegah serangan Session Fixation dengan membuat ID sesi baru
        session_regenerate_id(true);

        // Simpan data pengguna ke dalam sesi
        $_SESSION['user_id']    = (int) $user['id'];
        $_SESSION['nama']       = $user['nama'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['role']       = $user['role'];
        $_SESSION['login_time'] = time();

        // Catat ke log aktivitas
        require_once __DIR__ . '/includes/auth.php';
        catatLog("Pengguna '{$user['nama']}' (@{$user['username']}) berhasil login dengan role {$user['role']}.");

        // Alihkan ke router dashboard terpusat
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit;
    } else {
        // Kredensial tidak valid
        header('Location: ' . BASE_URL . 'index.php?pesan=gagal');
        exit;
    }
} catch (PDOException $e) {
    // Tangani error database jika tabel belum dibuat atau ada kendala koneksi
    error_log('Login Error: ' . $e->getMessage());
    header('Location: ' . BASE_URL . 'index.php?pesan=db_error&detail=' . urlencode($e->getMessage()));
    exit;
}
