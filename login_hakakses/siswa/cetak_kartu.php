<?php
/**
 * siswa/cetak_kartu.php
 * Halaman Cetak Kartu Pelajar Digital Resmi Siswa
 */

require_once __DIR__ . '/../includes/auth.php';

// Proteksi Sisi Server: Hanya Siswa
cekRole('siswa');

$userId = (int) $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare('SELECT id, nama, username, role, created_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $siswa = $stmt->fetch();

    if (!$siswa) {
        die("Data siswa tidak ditemukan.");
    }
} catch (PDOException $e) {
    die("Gagal memuat data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu Pelajar - <?= e($siswa['nama']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
        }
        .kartu-pelajar {
            width: 100%;
            max-width: 520px;
            background: #ffffff;
            border-radius: 20px;
            border: 2px solid #e2e8f0;
            box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
        }
        .kartu-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            padding: 1.25rem 1.5rem;
            border-bottom: 4px solid #f97316;
        }
        .barcode-box {
            font-family: monospace;
            letter-spacing: 4px;
            font-size: 1.2rem;
            background: #f8fafc;
            padding: 0.5rem 1rem;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            display: inline-block;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: none;
                padding: 0;
            }
            .kartu-pelajar {
                box-shadow: none;
                border: 1px solid #000;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body class="py-5">

<div class="container text-center">
    <!-- Tombol Aksi di Layar -->
    <div class="no-print mb-4 d-flex justify-content-center gap-2">
        <a href="<?= BASE_URL ?>siswa/dashboard.php" class="btn btn-outline-secondary px-3 py-2 fw-semibold rounded-3">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Dashboard
        </a>
        <button onclick="window.print();" class="btn btn-primary px-4 py-2 fw-semibold rounded-3 shadow-sm" style="background: linear-gradient(90deg, #ff6b00 0%, #f97316 100%); border: none;">
            <i class="bi bi-printer-fill me-1"></i>Cetak Kartu Sekarang
        </button>
    </div>

    <!-- Tampilan Fisik Kartu Pelajar -->
    <div class="kartu-pelajar mx-auto text-start">
        <!-- Header Kartu -->
        <div class="kartu-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-white text-dark d-flex align-items-center justify-content-center p-2 shadow-sm" style="width: 44px; height: 44px;">
                    <i class="bi bi-mortarboard-fill fs-4" style="color: #f97316;"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0 text-white tracking-wide">KARTU TANDA PELAJAR</h6>
                    <small class="text-white-50" style="font-size: 0.75rem;">Sistem Informasi Akademik &bull; Tahun 2026/2027</small>
                </div>
            </div>
            <span class="badge" style="background: rgba(249, 115, 22, 0.2); color: #fb923c; border: 1px solid #f97316;">AKTIF</span>
        </div>

        <!-- Body Kartu -->
        <div class="p-4">
            <div class="row align-items-center">
                <div class="col-4 text-center">
                    <div class="rounded-4 bg-light border p-1 mb-2 shadow-sm d-inline-block">
                        <div class="rounded-3 text-white d-flex align-items-center justify-content-center" style="width: 100px; height: 120px; font-size: 2.5rem; font-weight: 800; background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);">
                            <?= strtoupper(substr($siswa['nama'], 0, 1)) ?>
                        </div>
                    </div>
                    <div class="small fw-bold text-muted" style="font-size: 0.7rem;">FOTO SISWA</div>
                </div>

                <div class="col-8">
                    <table class="table table-sm table-borderless small mb-0">
                        <tr>
                            <td class="text-muted" style="width: 90px;">Nama Lengkap</td>
                            <td>: <strong class="text-dark"><?= e($siswa['nama']) ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">NIS / User</td>
                            <td>: <span class="font-monospace fw-bold text-primary">@<?= e($siswa['username']) ?></span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">ID Siswa</td>
                            <td>: <span class="fw-semibold">#SISWA-00<?= e($siswa['id']) ?></span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status Akun</td>
                            <td>: <span class="text-success fw-bold">Terverifikasi RBAC</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Terdaftar</td>
                            <td>: <?= date('d F Y', strtotime($siswa['created_at'])) ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <hr class="my-3 text-muted opacity-25">

            <!-- Footer Barcode Simulasi & TTD -->
            <div class="d-flex justify-content-between align-items-end">
                <div>
                    <div class="barcode-box">
                        ||| | |||| | |||||| || |
                    </div>
                    <div class="text-muted" style="font-size: 0.65rem;">VALIDITY ID: 2026-EDU-<?= e($siswa['id']) ?>-SECURE</div>
                </div>

                <div class="text-center" style="width: 140px;">
                    <small class="text-muted d-block" style="font-size: 0.68rem;">Kepala Tata Usaha,</small>
                    <div class="text-success small my-1">
                        <i class="bi bi-patch-check-fill fs-5"></i>
                        <span class="d-block" style="font-size: 0.62rem; color: #16a34a;">Digital Verified</span>
                    </div>
                    <strong class="d-block text-dark border-top pt-1" style="font-size: 0.72rem;">Petugas Tata Usaha</strong>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
