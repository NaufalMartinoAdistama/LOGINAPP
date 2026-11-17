<?php
/**
 * dashboard.php
 * Router Pengarah Akses ke Dashboard Masing-Masing Role
 */

require_once __DIR__ . '/includes/auth.php';

// Pastikan pengguna sudah terautentikasi
cekLogin();

$role = $_SESSION['role'] ?? '';

// Alihkan pengguna ke dashboard yang sesuai dengan peran mereka
switch ($role) {
    case 'admin':
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
        break;
    case 'petugas':
        header('Location: ' . BASE_URL . 'petugas/dashboard.php');
        break;
    case 'siswa':
        header('Location: ' . BASE_URL . 'siswa/dashboard.php');
        break;
    default:
        // Jika role tidak dikenali, logout dan kembalikan ke login
        header('Location: ' . BASE_URL . 'logout.php');
        break;
}
exit;
