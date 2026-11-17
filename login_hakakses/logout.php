<?php
/**
 * logout.php
 * Pembersihan Sesi dan Cookie Secara Menyeluruh dan Aman
 */

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/includes/auth.php';

// Catat log keluar jika ada sesi aktif
if (isset($_SESSION['username'])) {
    catatLog("Pengguna @{$_SESSION['username']} telah logout dari sistem.");
}

// Kosongkan semua variabel sesi
$_SESSION = [];

// Hapus cookie sesi dari browser jika ada
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Hancurkan sesi di server
session_destroy();

// Alihkan kembali ke halaman login dengan pesan sukses logout
header('Location: ' . BASE_URL . 'index.php?pesan=logout');
exit;
