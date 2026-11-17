-- ==============================================================
-- SQL File: database.sql
-- Proyek: Sistem Manajemen Pengguna dengan Autentikasi dan Hak Akses
-- Database: db_login_hakakses
-- ==============================================================

CREATE DATABASE IF NOT EXISTS `db_login_hakakses` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `db_login_hakakses`;

-- 1. Struktur Tabel users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'petugas', 'siswa') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Struktur Tabel pengumuman
CREATE TABLE IF NOT EXISTS `pengumuman` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `judul` VARCHAR(255) NOT NULL,
  `kategori` VARCHAR(50) NOT NULL,
  `isi` TEXT NOT NULL,
  `penulis` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Struktur Tabel permohonan_layanan
CREATE TABLE IF NOT EXISTS `permohonan_layanan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `jenis_layanan` VARCHAR(100) NOT NULL,
  `keterangan` TEXT NOT NULL,
  `status` ENUM('menunggu', 'proses', 'selesai', 'ditolak') DEFAULT 'menunggu',
  `catatan_petugas` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Struktur Tabel log_aktivitas
CREATE TABLE IF NOT EXISTS `log_aktivitas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `username` VARCHAR(50) NOT NULL,
  `aktivitas` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Awal Pengguna Default
INSERT INTO `users` (`id`, `nama`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'Administrator Utama', 'admin', '$2y$10$I8sOlI3s8dA6YEp6o1DUg.nRC.68VshElFa8ow9kCImBBjdy9e4ka', 'admin', NOW()),
(2, 'Petugas Tata Usaha', 'petugas', '$2y$10$7cPMwi/ClHwwfx301XDbt.YSaKh3M6FqQ4F.Bk2hlzIwTIBsjp9Se', 'petugas', NOW()),
(3, 'Ahmad Siswa Teladan', 'siswa', '$2y$10$Yjn8xh36F6JcNt7pGMfbwO6.ZY9D0.GlCYpqfyYnD8gbQxrh/3fwi', 'siswa', NOW())
ON DUPLICATE KEY UPDATE `nama` = VALUES(`nama`), `password` = VALUES(`password`), `role` = VALUES(`role`);

-- Data Awal Pengumuman
INSERT INTO `pengumuman` (`id`, `judul`, `kategori`, `isi`, `penulis`, `created_at`) VALUES
(1, 'Jadwal Penilaian Akhir Semester (PAS) Genap', 'Jadwal Ujian', 'Penilaian Akhir Semester dijadwalkan berlangsung serentak mulai tanggal 1 Desember mendatang. Seluruh siswa diharapkan memastikan kartu ujian dan administrasi telah lengkap.', 'Petugas Tata Usaha', NOW()),
(2, 'Layanan Permohonan Surat Keterangan Siswa Online', 'Layanan Tata Usaha', 'Siswa kini dapat mengajukan permohonan surat keterangan aktif siswa untuk keperluan beasiswa melalui sistem ini dengan waktu proses maksimal 1x24 jam kerja.', 'Petugas Tata Usaha', NOW()),
(3, 'Himbauan Mengganti Kata Sandi Default Akun', 'Pengingat Keamanan', 'Bagi siswa yang masih menggunakan kata sandi awal (siswa123), disarankan segera mengganti kata sandi di menu Profil Saya untuk menjaga kerahasiaan akun.', 'Administrator', NOW())
ON DUPLICATE KEY UPDATE `judul` = VALUES(`judul`);
