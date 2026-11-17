# EduAccess - Sistem Informasi Akademik & Manajemen User (RBAC)

Aplikasi web **Sistem Manajemen Pengguna dengan Autentikasi dan Hak Akses (Role-Based Access Control)** yang dibangun menggunakan **PHP Native (PDO)**, **MySQL**, dan **Bootstrap 5**. Desain disesuaikan dengan tema **Clean Light & Vibrant Orange** yang modern dan elegan.

---

## 🚀 Fitur Unggulan Sistem

### 1. Peran Administrator (Super Admin)
* **Analitik Statistik Pengguna**: 4 Stat Cards dinamis (Total Pengguna, Admin, Petugas, Siswa).
* **CRUD Pengguna Lengkap**: Tambah pengguna baru, ubah biodata & ganti password, atur level hak akses, dan hapus akun.
* **Ekspor Data ke CSV / Excel**: Unduh seluruh daftar akun pengguna langsung ke file format spreadsheet `.csv`.
* **Reset Password Cepat (1-Click)**: Mereset kata sandi pengguna langsung ke default (`admin123`, `petugas123`, `siswa123`).
* **Audit Trail / Log Aktivitas**: Memantau rekam jejak aktivitas sistem secara *real-time* (login, logout, modifikasi akun, verifikasi layanan).

### 2. Peran Petugas (Tata Usaha & Layanan)
* **Verifikasi Permohonan Dokumen Siswa**: Antrean layanan dokumen siswa terintegrasi langsung dengan database (Ubah status: *Menunggu*, *Diproses*, *Selesai*, atau *Ditolak* disertai catatan petugas).
* **Manajemen Pengumuman Sekolah**: Menerbitkan dan menghapus pengumuman resmi yang langsung tampil pada portal siswa.
* **Statistik Operasional**: Memantau jumlah antrean surat yang belum diverifikasi dan dokumen yang sudah selesai.

### 3. Peran Siswa (Portal Mandiri Akademik)
* **Pengajuan Surat Layanan Mandiri**: Siswa dapat mengajukan Surat Keterangan Aktif, Legalisir Rapor, Surat Pengantar Beasiswa PIP, dll secara online.
* **Riwayat & Status Verifikasi Permohonan**: Memantau progres permohonan surat beserta catatan dari loket Tata Usaha.
* **Pengumuman Resmi Sekolah**: Membaca feed informasi akademik terbaru dari database.
* **Cetak Kartu Tanda Pelajar Digital**: Tampilan kartu pelajar resmi lengkap dengan barcode digital dan stempel verifikasi yang siap dicetak (*print-ready*).

### 4. Standar Keamanan
* **PDO Prepared Statements**: Perlindungan penuh dari serangan SQL Injection.
* **Bcrypt Password Hash**: Menggunakan `password_hash()` dan `password_verify()`.
* **Pencegahan Session Fixation**: Menerapkan `session_regenerate_id(true)` saat login.
* **Anti-XSS**: Sanitasi seluruh data dengan `htmlspecialchars()`.
* **Otorisasi Server-Side**: Proteksi `cekLogin()` dan `cekRole()` yang mengembalikan **HTTP 403 Forbidden** jika hak akses tidak sesuai.
* **Proteksi Self-Deletion**: Mencegah akun administrator menghapus atau menurunkan level dirinya sendiri.

---

## 🔑 Akun Default untuk Pengujian

| Peran | Username | Password | Deskripsi Wewenang |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin123` | Akses penuh sistem, kelola pengguna & audit log |
| **Petugas** | `petugas` | `petugas123` | Verifikasi surat/dokumen & terbitkan pengumuman |
| **Siswa** | `siswa` | `siswa123` | Ajukan surat, kartu pelajar digital & baca info |

---

## 🛠️ Cara Menjalankan

1. Jalankan **Apache** dan **MySQL** di XAMPP Control Panel.
2. Buka browser: **[http://localhost/login/](http://localhost/login/)**
3. Sistem secara otomatis membuat tabel-tabel database (`users`, `pengumuman`, `permohonan_layanan`, `log_aktivitas`) dan mengisi data awal akun demo.
