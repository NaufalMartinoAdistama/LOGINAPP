<?php
/**
 * siswa/dashboard.php
 * Dashboard Utama Khusus Siswa - Tema Clean Light & Vibrant Orange
 * Fitur: Pengajuan Surat Layanan Mandiri, Riwayat Permohonan, Cetak Kartu, & Pengumuman Dinamis
 */

$pageTitle = 'Dashboard Siswa';
require_once __DIR__ . '/../includes/auth.php';

// Proteksi Sisi Server: Hanya Siswa yang diizinkan
cekRole('siswa');

$userId = (int) $_SESSION['user_id'];
$pesanSukses = '';
$pesanError  = '';

// -------------------------------------------------------------
// PROSES PENGAJUAN PERMOHONAN LAYANAN SURAT
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'ajukan_surat') {
        $jenisLayanan = trim($_POST['jenis_layanan'] ?? '');
        $keterangan   = trim($_POST['keterangan'] ?? '');

        if (empty($jenisLayanan) || empty($keterangan)) {
            $pesanError = 'Jenis layanan dan keperluan wajib diisi!';
        } else {
            try {
                $insertStmt = $pdo->prepare('INSERT INTO permohonan_layanan (user_id, jenis_layanan, keterangan, status) VALUES (:uid, :jenis, :ket, "menunggu")');
                $insertStmt->execute([
                    ':uid'   => $userId,
                    ':jenis' => $jenisLayanan,
                    ':ket'   => $keterangan
                ]);

                catatLog("Siswa @{$_SESSION['username']} mengajukan permohonan: '{$jenisLayanan}'.");
                $pesanSukses = 'Permohonan surat Anda berhasil dikirim ke Petugas Tata Usaha!';
            } catch (PDOException $e) {
                $pesanError = 'Gagal mengajukan permohonan: ' . $e->getMessage();
            }
        }
    }
}

// -------------------------------------------------------------
// QUERY DATA SISWA, PENGAJUAN LAYANAN, & PENGUMUMAN
// -------------------------------------------------------------
try {
    // 1. Data detail siswa
    $stmtSiswa = $pdo->prepare('SELECT id, nama, username, role, created_at FROM users WHERE id = :id LIMIT 1');
    $stmtSiswa->execute([':id' => $userId]);
    $siswa = $stmtSiswa->fetch();

    // 2. Riwayat pengajuan permohonan siswa
    $stmtRiwayat = $pdo->prepare('SELECT id, jenis_layanan, keterangan, status, catatan_petugas, created_at FROM permohonan_layanan WHERE user_id = :uid ORDER BY id DESC');
    $stmtRiwayat->execute([':uid' => $userId]);
    $riwayatLayanan = $stmtRiwayat->fetchAll();

    // 3. Pengumuman aktif dari database
    $stmtPengumuman = $pdo->query('SELECT id, judul, kategori, isi, penulis, created_at FROM pengumuman ORDER BY id DESC LIMIT 5');
    $daftarPengumuman = $stmtPengumuman->fetchAll();
} catch (PDOException $e) {
    die("Terjadi kegagalan data: " . $e->getMessage());
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid p-0">
    <!-- Header Sambutan Siswa -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h4 class="fw-bold text-dark mb-0">Portal Akademik Siswa</h4>
                <span class="badge" style="background: rgba(16, 185, 129, 0.12); color: #059669; border: 1px solid rgba(16, 185, 129, 0.25);">
                    Siswa Terdaftar
                </span>
            </div>
            <p class="text-muted small mb-0">Halo, <strong class="text-dark"><?= e($siswa['nama']) ?></strong>! Selamat datang di portal layanan mandiri &amp; akademik Anda.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <!-- Tombol Ajukan Surat -->
            <button type="button" class="btn btn-orange-gradient rounded-3 px-3 py-2 small fw-semibold shadow-sm my-0" style="width: auto; height: auto;" data-bs-toggle="modal" data-bs-target="#modalAjukanSurat">
                <i class="bi bi-file-earmark-plus-fill me-1"></i>Ajukan Permohonan Surat
            </button>
            <!-- Tombol Cetak Kartu Pelajar -->
            <a href="<?= BASE_URL ?>siswa/cetak_kartu.php" target="_blank" class="btn btn-outline-primary rounded-3 px-3 py-2 small fw-semibold shadow-sm">
                <i class="bi bi-printer-fill me-1"></i>Cetak Kartu Pelajar
            </a>
            <a href="<?= BASE_URL ?>profil.php" class="btn btn-outline-secondary rounded-3 px-3 py-2 small fw-semibold">
                <i class="bi bi-person-gear me-1"></i>Profil
            </a>
        </div>
    </div>

    <!-- Alert Notifikasi -->
    <?php if (!empty($pesanSukses)): ?>
        <div class="alert alert-success border-0 rounded-3 py-2.5 px-3 small d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert" style="background: #f0fdf4; color: #16a34a;">
            <i class="bi bi-check-circle-fill text-success fs-5"></i>
            <div><?= e($pesanSukses) ?></div>
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

    <!-- 4 Academic Overview Stat Cards -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Status Akademik -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase tracking-wider">Status Siswa</div>
                        <h4 class="fw-bold text-success my-2">Aktif / Terdaftar</h4>
                        <div class="text-muted small">
                            <i class="bi bi-check2-all text-success me-1"></i>Tahun Ajaran 2026/2027
                        </div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-green">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Permohonan Surat Siswa -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase tracking-wider">Permohonan Surat</div>
                        <h2 class="fw-bold text-dark my-1"><?= count($riwayatLayanan) ?></h2>
                        <div class="small fw-semibold" style="color: #ea580c;">
                            <i class="bi bi-file-earmark-text me-1"></i>Total Diajukan
                        </div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-orange">
                        <i class="bi bi-file-earmark-check-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Pengumuman Baru -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase tracking-wider">Pengumuman</div>
                        <h2 class="fw-bold text-dark my-1"><?= count($daftarPengumuman) ?></h2>
                        <div class="small fw-semibold text-primary">
                            <i class="bi bi-bell-fill me-1"></i>Informasi Terbaru
                        </div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-blue">
                        <i class="bi bi-megaphone-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Hak Akses Role -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase tracking-wider">Hak Akses Sistem</div>
                        <h4 class="fw-bold text-primary my-2">Role: Siswa</h4>
                        <div class="text-muted small">
                            <i class="bi bi-shield-lock me-1"></i>Portal Mandiri Terverifikasi
                        </div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-blue">
                        <i class="bi bi-shield-shaded"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row: Kartu Pelajar Digital & Feed Pengumuman -->
    <div class="row g-4 mb-4">
        <!-- Kartu Identitas Digital Siswa -->
        <div class="col-lg-5">
            <div class="card card-modern p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="brand-icon" style="width: 34px; height: 34px; font-size: 1rem;">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-0">Kartu Pelajar Digital</h6>
                            <small class="text-muted" style="font-size: 0.72rem;"><?= APP_TITLE ?></small>
                        </div>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>
                </div>

                <div class="text-center py-2">
                    <div class="rounded-circle text-white d-inline-flex align-items-center justify-content-center shadow-sm mb-3" style="width: 80px; height: 80px; font-size: 2.2rem; font-weight: 800; background: var(--orange-gradient);">
                        <?= strtoupper(substr($siswa['nama'], 0, 1)) ?>
                    </div>
                    <h5 class="fw-bold text-dark mb-1"><?= e($siswa['nama']) ?></h5>
                    <div class="text-muted small mb-3">
                        <span class="badge bg-light text-dark border font-monospace px-2.5 py-1.5">NIS / Username: @<?= e($siswa['username']) ?></span>
                    </div>
                </div>

                <div class="bg-light p-3 rounded-3 small mb-3">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Nomor Identitas:</span>
                        <span class="fw-bold text-dark">#SISWA-00<?= e($siswa['id']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Peran Akun:</span>
                        <span class="fw-bold text-success text-capitalize"><?= e($siswa['role']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Terdaftar Sejak:</span>
                        <span class="fw-semibold text-dark"><?= date('d F Y', strtotime($siswa['created_at'])) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Keamanan Akun:</span>
                        <span class="text-success fw-semibold"><i class="bi bi-shield-check me-1"></i>Terproteksi RBAC</span>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-auto">
                    <a href="<?= BASE_URL ?>siswa/cetak_kartu.php" target="_blank" class="btn btn-outline-primary btn-sm py-2 rounded-3 fw-semibold">
                        <i class="bi bi-printer-fill me-1"></i>Cetak Kartu Pelajar (PDF / Print)
                    </a>
                </div>
            </div>
        </div>

        <!-- Feed Informasi Akademik & Pengumuman -->
        <div class="col-lg-7" id="pengumuman">
            <div class="card card-modern h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-1">Informasi &amp; Pengumuman Akademik</h6>
                        <small class="text-muted">Pemberitahuan resmi terbaru dari sekolah yang tersimpan di database.</small>
                    </div>
                    <i class="bi bi-bell-fill fs-5" style="color: #ea580c;"></i>
                </div>
                <div class="card-body p-4 pt-2">
                    <?php if (empty($daftarPengumuman)): ?>
                        <div class="text-center py-4 text-muted small">Belum ada pengumuman terbaru.</div>
                    <?php else: ?>
                        <?php foreach ($daftarPengumuman as $p): ?>
                            <div class="border rounded-3 p-3 mb-3 bg-white shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge" style="background: rgba(249, 115, 22, 0.12); color: #ea580c; border: 1px solid rgba(249, 115, 22, 0.3);">
                                        <?= e($p['kategori']) ?>
                                    </span>
                                    <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($p['created_at'])) ?></small>
                                </div>
                                <h6 class="fw-bold text-dark mb-1"><?= e($p['judul']) ?></h6>
                                <p class="text-muted small mb-1"><?= nl2br(e($p['isi'])) ?></p>
                                <small class="text-secondary" style="font-size: 0.72rem;">Diterbitkan oleh: <strong><?= e($p['penulis']) ?></strong></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Riwayat Permohonan Layanan Siswa -->
    <div class="card card-modern mb-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold text-dark mb-0">Riwayat Pengajuan Permohonan Dokumen Anda</h6>
                <small class="text-muted">Pantau status verifikasi surat keterangan dan berkas akademik Anda.</small>
            </div>
            <button type="button" class="btn btn-sm btn-orange-gradient px-3 py-1.5 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAjukanSurat">
                <i class="bi bi-plus-lg me-1"></i>Ajukan Baru
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern align-middle">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Jenis Layanan Dokumen</th>
                            <th>Keperluan / Catatan Anda</th>
                            <th>Waktu Pengajuan</th>
                            <th>Status Proses</th>
                            <th>Catatan Petugas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($riwayatLayanan)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted small">
                                    Belum ada permohonan surat yang diajukan. Klik tombol <strong>Ajukan Baru</strong> untuk membuat permohonan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($riwayatLayanan as $layanan): ?>
                                <tr>
                                    <td class="text-muted small fw-semibold"><?= $no++ ?></td>
                                    <td>
                                        <strong class="text-dark d-block"><?= e($layanan['jenis_layanan']) ?></strong>
                                    </td>
                                    <td class="small text-muted"><?= e($layanan['keterangan']) ?></td>
                                    <td class="small text-muted">
                                        <i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($layanan['created_at'])) ?>
                                    </td>
                                    <td>
                                        <?php if ($layanan['status'] === 'menunggu'): ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Menunggu Verifikasi</span>
                                        <?php elseif ($layanan['status'] === 'proses'): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">Sedang Diproses</span>
                                        <?php elseif ($layanan['status'] === 'selesai'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Selesai &amp; Siap Diambil</span>
                                        <?php elseif ($layanan['status'] === 'ditolak'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Ditolak</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?= !empty($layanan['catatan_petugas']) ? e($layanan['catatan_petugas']) : '-' ?>
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
<!-- MODAL: AJUKAN PERMOHONAN SURAT OLEH SISWA                      -->
<!-- ============================================================== -->
<div class="modal fade" id="modalAjukanSurat" tabindex="-1" aria-labelledby="modalAjukanSuratLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4" style="background-color: #ffffff;">
            <div class="modal-header border-bottom px-4 pt-4 pb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="badge p-2 rounded-3" style="background: rgba(249, 115, 22, 0.12); color: #ea580c;">
                        <i class="bi bi-file-earmark-text-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark" id="modalAjukanSuratLabel">Ajukan Permohonan Dokumen</h5>
                        <small class="text-muted">Kirim permohonan surat keterangan ke loket Tata Usaha.</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>siswa/dashboard.php" method="POST">
                <input type="hidden" name="action" value="ajukan_surat">
                <div class="modal-body px-4 py-3">
                    <div class="mb-3">
                        <label for="jenis_layanan" class="form-label-dark">Jenis Dokumen Layanan</label>
                        <select name="jenis_layanan" id="jenis_layanan" class="form-select bg-white border-light-subtle" required>
                            <option value="" disabled selected>-- Pilih Jenis Surat --</option>
                            <option value="Surat Keterangan Aktif Siswa">Surat Keterangan Aktif Siswa</option>
                            <option value="Legalisir Rapor Siswa">Legalisir Rapor Siswa</option>
                            <option value="Surat Pengantar Beasiswa PIP">Surat Pengantar Beasiswa PIP</option>
                            <option value="Surat Keterangan Berkelakuan Baik">Surat Keterangan Berkelakuan Baik</option>
                            <option value="Surat Rekomendasi Magang / PKL">Surat Rekomendasi Magang / PKL</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="keterangan" class="form-label-dark">Keperluan / Keterangan Lengkap</label>
                        <textarea name="keterangan" id="keterangan" rows="3" class="form-control-light" placeholder="Contoh: Untuk persyaratan pendaftaran beasiswa prestasi tingkat kabupaten tahun 2026." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light rounded-3 px-3 py-2 small fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-orange-gradient px-4 py-2 small fw-semibold my-0" style="width: auto;">
                        <i class="bi bi-send-fill me-1"></i>Kirim Permohonan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
