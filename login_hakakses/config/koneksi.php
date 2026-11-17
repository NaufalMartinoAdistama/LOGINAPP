<?php
/**
 * config/koneksi.php
 * File konfigurasi koneksi database PDO, inisialisasi sesi, dan auto-setup tabel lengkap.
 */

// Mulai sesi jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfigurasi Database
$host     = 'localhost';
$port     = '3306';
$dbname   = 'db_login_hakakses';
$username = 'root';
$password = '';
$charset  = 'utf8mb4';

$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Jika database belum ada, buat database secara otomatis
    if ($e->getCode() == 1049 || str_contains($e->getMessage(), 'Unknown database')) {
        try {
            $pdoRoot = new PDO("mysql:host={$host};port={$port};charset={$charset}", $username, $password, $options);
            $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e2) {
            tampilkanErrorKoneksi($e2->getMessage());
        }
    } else {
        tampilkanErrorKoneksi($e->getMessage());
    }
}

// Auto-setup tabel-tabel sistem & inisialisasi data awal
try {
    if (isset($pdo)) {
        // 1. Tabel users
        $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nama` VARCHAR(100) NOT NULL,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `role` ENUM('admin', 'petugas', 'siswa') NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 2. Tabel pengumuman
        $pdo->exec("CREATE TABLE IF NOT EXISTS `pengumuman` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `judul` VARCHAR(255) NOT NULL,
            `kategori` VARCHAR(50) NOT NULL,
            `isi` TEXT NOT NULL,
            `penulis` VARCHAR(100) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 3. Tabel permohonan_layanan siswa
        $pdo->exec("CREATE TABLE IF NOT EXISTS `permohonan_layanan` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `jenis_layanan` VARCHAR(100) NOT NULL,
            `keterangan` TEXT NOT NULL,
            `status` ENUM('menunggu', 'proses', 'selesai', 'ditolak') DEFAULT 'menunggu',
            `catatan_petugas` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // 4. Tabel log aktivitas audit
        $pdo->exec("CREATE TABLE IF NOT EXISTS `log_aktivitas` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NULL,
            `username` VARCHAR(50) NOT NULL,
            `aktivitas` VARCHAR(255) NOT NULL,
            `ip_address` VARCHAR(45) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // Hash akun default
        $hashAdmin   = password_hash('admin123', PASSWORD_DEFAULT);
        $hashPetugas = password_hash('petugas123', PASSWORD_DEFAULT);
        $hashSiswa   = password_hash('siswa123', PASSWORD_DEFAULT);

        // Seed akun default jika tabel users kosong
        $cek = $pdo->query("SELECT COUNT(*) AS total FROM `users`")->fetch();
        if (($cek['total'] ?? 0) == 0) {
            $stmtInsert = $pdo->prepare("INSERT INTO `users` (`id`, `nama`, `username`, `password`, `role`, `created_at`) VALUES
                (1, 'Administrator Utama', 'admin', :passAdmin, 'admin', NOW()),
                (2, 'Petugas Tata Usaha', 'petugas', :passPetugas, 'petugas', NOW()),
                (3, 'Ahmad Siswa Teladan', 'siswa', :passSiswa, 'siswa', NOW())");
            $stmtInsert->execute([
                ':passAdmin'   => $hashAdmin,
                ':passPetugas' => $hashPetugas,
                ':passSiswa'   => $hashSiswa,
            ]);
        } else {
            // Perbaiki hash jika belum cocok
            $updatePassStmt = $pdo->prepare("UPDATE `users` SET `password` = :pass WHERE `username` = :uname");
            $rowAdmin = $pdo->query("SELECT `password` FROM `users` WHERE `username` = 'admin'")->fetch();
            if ($rowAdmin && !password_verify('admin123', $rowAdmin['password'])) {
                $updatePassStmt->execute([':pass' => $hashAdmin, ':uname' => 'admin']);
            }
            $rowPetugas = $pdo->query("SELECT `password` FROM `users` WHERE `username` = 'petugas'")->fetch();
            if ($rowPetugas && !password_verify('petugas123', $rowPetugas['password'])) {
                $updatePassStmt->execute([':pass' => $hashPetugas, ':uname' => 'petugas']);
            }
            $rowSiswa = $pdo->query("SELECT `password` FROM `users` WHERE `username` = 'siswa'")->fetch();
            if ($rowSiswa && !password_verify('siswa123', $rowSiswa['password'])) {
                $updatePassStmt->execute([':pass' => $hashSiswa, ':uname' => 'siswa']);
            }
        }

        // Seed data pengumuman awal jika kosong
        $cekPengumuman = $pdo->query("SELECT COUNT(*) AS total FROM `pengumuman`")->fetch();
        if (($cekPengumuman['total'] ?? 0) == 0) {
            $pdo->exec("INSERT INTO `pengumuman` (`judul`, `kategori`, `isi`, `penulis`, `created_at`) VALUES
            ('Jadwal Penilaian Akhir Semester (PAS) Genap', 'Jadwal Ujian', 'Penilaian Akhir Semester dijadwalkan serentak mulai tanggal 1 Desember mendatang. Seluruh siswa diharapkan memastikan kartu ujian dan administrasi telah lengkap.', 'Petugas Tata Usaha', NOW()),
            ('Layanan Permohonan Surat Keterangan Siswa Online', 'Layanan Tata Usaha', 'Siswa kini dapat mengajukan permohonan surat keterangan aktif siswa untuk keperluan beasiswa melalui sistem ini dengan waktu proses maksimal 1x24 jam kerja.', 'Petugas Tata Usaha', NOW()),
            ('Himbauan Mengganti Kata Sandi Default Akun', 'Pengingat Keamanan', 'Bagi siswa yang masih menggunakan kata sandi awal (siswa123), disarankan segera mengganti kata sandi di menu Profil Saya untuk menjaga kerahasiaan akun.', 'Administrator', NOW());");
        }

        // Seed data permohonan layanan awal jika kosong
        $cekLayanan = $pdo->query("SELECT COUNT(*) AS total FROM `permohonan_layanan`")->fetch();
        if (($cekLayanan['total'] ?? 0) == 0) {
            // Ambil ID siswa
            $idSiswa = $pdo->query("SELECT id FROM users WHERE role = 'siswa' LIMIT 1")->fetch()['id'] ?? 3;
            $pdo->exec("INSERT INTO `permohonan_layanan` (`user_id`, `jenis_layanan`, `keterangan`, `status`, `catatan_petugas`, `created_at`) VALUES
            ({$idSiswa}, 'Surat Keterangan Aktif Siswa', 'Keperluan pendaftaran beasiswa prestasi tingkat provinsi tahun 2026/2027.', 'menunggu', NULL, NOW()),
            ({$idSiswa}, 'Legalisir Rapor Semester Ganjil', 'Keperluan verifikasi data berkas lomba karya ilmiah remaja.', 'selesai', 'Dokumen telah selesai dicetak dan dilegalisir. Silakan ambil di loket TU.', DATE_SUB(NOW(), INTERVAL 1 DAY));");
        }
    }
} catch (PDOException $ex) {
    // Abaikan jika sudah ada
}

// Konfigurasi BASE_URL dinamis
if (!defined('BASE_URL')) {
    $projectDir = str_replace(DIRECTORY_SEPARATOR, '/', realpath(__DIR__ . '/..'));
    $docRoot    = str_replace(DIRECTORY_SEPARATOR, '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));

    if (!empty($docRoot) && str_starts_with($projectDir, $docRoot)) {
        $relative = substr($projectDir, strlen($docRoot));
        $baseUrl  = rtrim($relative, '/') . '/';
    } else {
        $baseUrl = '/login/';
    }

    if (!str_starts_with($baseUrl, '/')) {
        $baseUrl = '/' . $baseUrl;
    }
    define('BASE_URL', $baseUrl);
}

if (!defined('APP_NAME')) {
    define('APP_NAME', 'EduAccess');
}
if (!defined('APP_TITLE')) {
    define('APP_TITLE', 'Sistem Manajemen Pengguna & Hak Akses');
}

/**
 * Tampilan Pesan Error Koneksi Database yang Informatif
 */
function tampilkanErrorKoneksi($pesan) {
    $pesanClean = htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8');
    die("
    <!DOCTYPE html>
    <html lang='id'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Koneksi MySQL Gagal</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
        <link href='https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap' rel='stylesheet'>
        <style>body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }</style>
    </head>
    <body class='d-flex align-items-center min-vh-100 py-5'>
        <div class='container'>
            <div class='row justify-content-center'>
                <div class='col-md-7 col-lg-6'>
                    <div class='card shadow border-danger border-opacity-25 rounded-4 p-4 p-md-5 bg-white'>
                        <div class='text-center mb-4'>
                            <h3 class='fw-bold text-dark'>MySQL Belum Berjalan / Terhubung</h3>
                            <p class='text-muted small'>Aplikasi tidak dapat membuat koneksi ke database MySQL.</p>
                        </div>
                        <div class='alert alert-danger py-2 px-3 small font-monospace mb-4'>
                            {$pesanClean}
                        </div>
                        <div class='bg-light p-3 rounded-3 small text-muted mb-4'>
                            <p class='fw-semibold text-dark mb-1'>Langkah Mudah:</p>
                            <ol class='ps-3 mb-0'>
                                <li>Buka <strong>XAMPP Control Panel</strong> Anda.</li>
                                <li>Klik tombol <strong>Start</strong> pada baris <strong>Apache</strong> dan <strong>MySQL</strong>.</li>
                                <li>Muat ulang halaman browser ini.</li>
                            </ol>
                        </div>
                        <a href='' onclick='window.location.reload(); return false;' class='btn btn-primary w-100 py-2.5 fw-semibold'>
                            Muat Ulang Halaman
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    ");
}
