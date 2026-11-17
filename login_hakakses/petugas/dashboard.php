<?php
/**
 * petugas/dashboard.php
 * Dashboard Utama Khusus Petugas Operasional - Tema Clean Light & Vibrant Orange
 * Fitur Lengkap: Verifikasi Permohonan Surat Siswa, Manajemen Pengumuman, & Audit Log
 */

$pageTitle = 'Dashboard Petugas Operasional';
require_once __DIR__ . '/../includes/auth.php';

// Proteksi Sisi Server: Hanya Petugas
cekRole('petugas');

$pesanSukses = '';
$pesanError  = '';

// -------------------------------------------------------------
// PROSES AKSI POST (VERIFIKASI LAYANAN & BUAT PENGUMUMAN)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. UPDATE STATUS PERMOHONAN LAYANAN SISWA
    if ($action === 'update_status_layanan') {
        $idLayanan = (int) ($_POST['id_layanan'] ?? 0);
        $statusBaru = $_POST['status'] ?? '';
        $catatan    = trim($_POST['catatan_petugas'] ?? '');

        $allowedStatus = ['menunggu', 'proses', 'selesai', 'ditolak'];

        if ($idLayanan <= 0 || !in_array($statusBaru, $allowedStatus, true)) {
            $pesanError = 'Status permohonan tidak valid!';
        } else {
            try {
                $updateStmt = $pdo->prepare('UPDATE permohonan_layanan SET status = :st, catatan_petugas = :ct WHERE id = :id');
                $updateStmt->execute([
                    ':st' => $statusBaru,
                    ':ct' => $catatan,
                    ':id' => $idLayanan
                ]);

                catatLog("Petugas @{$_SESSION['username']} memperbarui status permohonan #{$idLayanan} menjadi '{$statusBaru}'.");
                $pesanSukses = "Status permohonan #{$idLayanan} berhasil diperbarui menjadi '{$statusBaru}'!";
            } catch (PDOException $e) {
                $pesanError = 'Gagal memperbarui status: ' . $e->getMessage();
            }
        }
    }

    // 2. TAMBAH PENGUMUMAN BARU
    elseif ($action === 'tambah_pengumuman') {
        $judul    = trim($_POST['judul'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $isi      = trim($_POST['isi'] ?? '');

        if (empty($judul) || empty($kategori) || empty($isi)) {
            $pesanError = 'Semua field pengumuman wajib diisi!';
        } else {
            try {
                $insertP = $pdo->prepare('INSERT INTO pengumuman (judul, kategori, isi, penulis) VALUES (:j, :k, :i, :p)');
                $insertP->execute([
                    ':j' => $judul,
                    ':k' => $kategori,
                    ':i' => $isi,
                    ':p' => $_SESSION['nama']
                ]);

                catatLog("Petugas @{$_SESSION['username']} menerbitkan pengumuman baru: '{$judul}'.");
                $pesanSukses = "Pengumuman baru '{$judul}' berhasil diterbitkan!";
            } catch (PDOException $e) {
                $pesanError = 'Gagal menerbitkan pengumuman: ' . $e->getMessage();
            }
        }
    }

    // 3. HAPUS PENGUMUMAN
    elseif ($action === 'hapus_pengumuman') {
        $idPengumuman = (int) ($_POST['id_pengumuman'] ?? 0);

        try {
            $delP = $pdo->prepare('DELETE FROM pengumuman WHERE id = :id');
            $delP->execute([':id' => $idPengumuman]);

            catatLog("Petugas @{$_SESSION['username']} menghapus pengumuman ID #{$idPengumuman}.");
            $pesanSukses = 'Pengumuman berhasil dihapus dari sistem.';
        } catch (PDOException $e) {
            $pesanError = 'Gagal menghapus pengumuman: ' . $e->getMessage();
        }
    }
}

// -------------------------------------------------------------
// QUERY STATISTIK & DATA PERMOHONAN DARI DATABASE
// -------------------------------------------------------------
try {
    $totalSiswa = $pdo->query("SELECT COUNT(*) AS total FROM users WHERE role = 'siswa'")->fetch()['total'] ?? 0;
    $totalMenunggu = $pdo->query("SELECT COUNT(*) AS total FROM permohonan_layanan WHERE status = 'menunggu'")->fetch()['total'] ?? 0;
    $totalSelesai = $pdo->query("SELECT COUNT(*) AS total FROM permohonan_layanan WHERE status = 'selesai'")->fetch()['total'] ?? 0;
    $totalPengumuman = $pdo->query("SELECT COUNT(*) AS total FROM pengumuman")->fetch()['total'] ?? 0;

    // Ambil seluruh permohonan layanan dengan join nama siswa
    $stmtAntrean = $pdo->query('SELECT pl.*, u.nama AS nama_siswa, u.username AS nis_siswa 
                                FROM permohonan_layanan pl 
                                JOIN users u ON pl.user_id = u.id 
                                ORDER BY pl.id DESC');
    $daftarPermohonan = $stmtAntrean->fetchAll();

    // Ambil daftar pengumuman
    $stmtListPengumuman = $pdo->query('SELECT * FROM pengumuman ORDER BY id DESC');
    $daftarPengumuman = $stmtListPengumuman->fetchAll();
} catch (PDOException $e) {
    die("Terjadi kegagalan query: " . $e->getMessage());
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid p-0">
    <!-- Header Sambutan -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h4 class="fw-bold text-dark mb-0">Dashboard Petugas Operasional</h4>
                <span class="badge" style="background: rgba(59, 130, 246, 0.12); color: #2563eb; border: 1px solid rgba(59, 130, 246, 0.25);">
                    Tata Usaha &amp; Layanan Siswa
                </span>
            </div>
            <p class="text-muted small mb-0">Selamat bertugas, <strong class="text-dark"><?= e($_SESSION['nama']) ?></strong>! Kelola antrean dokumen siswa dan terbitkan informasi sekolah.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <!-- Tombol Tambah Pengumuman -->
            <button type="button" class="btn btn-orange-gradient px-3 py-2 small fw-semibold my-0 shadow-sm" style="width: auto; height: auto;" data-bs-toggle="modal" data-bs-target="#modalTambahPengumuman">
                <i class="bi bi-megaphone-fill me-1"></i>Buat Pengumuman Baru
            </button>
            <a href="<?= BASE_URL ?>profil.php" class="btn btn-outline-secondary rounded-3 px-3 py-2 small fw-semibold">
                <i class="bi bi-person-gear me-1"></i>Pengaturan Akun
            </a>
            <a href="<?= BASE_URL ?>admin/kelola_user.php" class="btn btn-outline-danger rounded-3 px-3 py-2 small fw-semibold" title="Uji Coba Proteksi RBAC 403">
                <i class="bi bi-shield-slash me-1"></i>Tes Buka Area Admin (403)
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

    <!-- 4 Operational Stat Cards -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Total Siswa Terdaftar -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase tracking-wider">Siswa Terdaftar</div>
                        <h2 class="fw-bold text-dark my-1"><?= number_format($totalSiswa) ?></h2>
                        <div class="small fw-semibold text-primary">
                            <i class="bi bi-person-lines-fill me-1"></i>Data Terkoneksi MySQL
                        </div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-blue">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Permohonan Menunggu Verifikasi -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase tracking-wider">Menunggu Verifikasi</div>
                        <h2 class="fw-bold text-dark my-1"><?= number_format($totalMenunggu) ?></h2>
                        <div class="small fw-semibold" style="color: #ea580c;">
                            <i class="bi bi-hourglass-split me-1"></i>Perlu Tindakan Petugas
                        </div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-orange">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Dokumen Selesai -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase tracking-wider">Dokumen Selesai</div>
                        <h2 class="fw-bold text-dark my-1"><?= number_format($totalSelesai) ?></h2>
                        <div class="small fw-semibold text-success">
                            <i class="bi bi-check-circle-fill me-1"></i>Siap Diambil Siswa
                        </div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-green">
                        <i class="bi bi-patch-check-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Total Pengumuman -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase tracking-wider">Pengumuman Aktif</div>
                        <h2 class="fw-bold text-dark my-1"><?= number_format($totalPengumuman) ?></h2>
                        <div class="small fw-semibold text-primary">
                            <i class="bi bi-megaphone me-1"></i>Informasi Sekolah
                        </div>
                    </div>
                    <div class="stat-icon-wrapper stat-icon-blue">
                        <i class="bi bi-megaphone-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row: Antrean Layanan Dokumen Siswa Real-Time -->
    <div class="card card-modern mb-4" id="layanan">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold text-dark mb-0">Antrean Permohonan Dokumen &amp; Surat Siswa</h6>
                <small class="text-muted">Kelola persetujuan, proses pembuatan dokumen, dan berikan catatan langsung kepada siswa.</small>
            </div>
            <span class="badge" style="background: rgba(249, 115, 22, 0.12); color: #ea580c; border: 1px solid rgba(249, 115, 22, 0.3);">
                Database Real-Time
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern align-middle">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Pemohon (Siswa)</th>
                            <th>Jenis Dokumen</th>
                            <th>Keperluan / Alasan</th>
                            <th>Tanggal Masuk</th>
                            <th>Status Saat Ini</th>
                            <th class="text-end" style="width: 140px;">Verifikasi Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($daftarPermohonan)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted small">
                                    Belum ada permohonan surat dari siswa.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($daftarPermohonan as $p): ?>
                                <tr>
                                    <td class="text-muted small fw-semibold"><?= $no++ ?></td>
                                    <td>
                                        <strong class="text-dark d-block"><?= e($p['nama_siswa']) ?></strong>
                                        <span class="badge bg-light text-secondary border font-monospace">@<?= e($p['nis_siswa']) ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-medium text-dark"><?= e($p['jenis_layanan']) ?></span>
                                    </td>
                                    <td class="small text-muted" style="max-width: 200px;">
                                        <?= e($p['keterangan']) ?>
                                    </td>
                                    <td class="small text-muted">
                                        <i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($p['created_at'])) ?>
                                    </td>
                                    <td>
                                        <?php if ($p['status'] === 'menunggu'): ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Menunggu Verifikasi</span>
                                        <?php elseif ($p['status'] === 'proses'): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Sedang Diproses</span>
                                        <?php elseif ($p['status'] === 'selesai'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Selesai &amp; Siap Diambil</span>
                                        <?php elseif ($p['status'] === 'ditolak'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Ditolak</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary px-2.5 py-1 rounded-2 btn-verifikasi-layanan" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalVerifikasiLayanan"
                                                data-id="<?= e($p['id']) ?>"
                                                data-siswa="<?= e($p['nama_siswa']) ?>"
                                                data-jenis="<?= e($p['jenis_layanan']) ?>"
                                                data-status="<?= e($p['status']) ?>"
                                                data-catatan="<?= e($p['catatan_petugas'] ?? '') ?>">
                                            <i class="bi bi-pencil-square me-1"></i>Update
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Row: Daftar Pengumuman Sekolah yang Dikelola -->
    <div class="card card-modern mb-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold text-dark mb-0">Pengumuman Sekolah</h6>
                <small class="text-muted">Daftar pengumuman yang tampil pada portal siswa.</small>
            </div>
            <button type="button" class="btn btn-sm btn-orange-gradient px-3 py-1.5 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahPengumuman">
                <i class="bi bi-plus-lg me-1"></i>Buat Pengumuman
            </button>
        </div>
        <div class="card-body p-4 pt-2">
            <?php if (empty($daftarPengumuman)): ?>
                <div class="text-center py-4 text-muted small">Belum ada pengumuman yang diterbitkan.</div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($daftarPengumuman as $peng): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="border rounded-3 p-3 bg-white h-100 d-flex flex-column justify-content-between shadow-sm">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge" style="background: rgba(249, 115, 22, 0.12); color: #ea580c; border: 1px solid rgba(249, 115, 22, 0.3);">
                                            <?= e($peng['kategori']) ?>
                                        </span>
                                        <form method="POST" action="<?= BASE_URL ?>petugas/dashboard.php" class="d-inline">
                                            <input type="hidden" name="action" value="hapus_pengumuman">
                                            <input type="hidden" name="id_pengumuman" value="<?= e($peng['id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0 btn-confirm-delete" data-confirm="Hapus pengumuman '<?= e($peng['judul']) ?>'?" title="Hapus Pengumuman">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1"><?= e($peng['judul']) ?></h6>
                                    <p class="text-muted small mb-2"><?= nl2br(e($peng['isi'])) ?></p>
                                </div>
                                <div class="border-top pt-2 mt-2 d-flex justify-content-between small text-secondary" style="font-size: 0.72rem;">
                                    <span>Oleh: <?= e($peng['penulis']) ?></span>
                                    <span><?= date('d M Y', strtotime($peng['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: VERIFIKASI STATUS LAYANAN SISWA                         -->
<!-- ============================================================== -->
<div class="modal fade" id="modalVerifikasiLayanan" tabindex="-1" aria-labelledby="modalVerifikasiLayananLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4" style="background-color: #ffffff;">
            <div class="modal-header border-bottom px-4 pt-4 pb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="badge p-2 rounded-3" style="background: rgba(59, 130, 246, 0.12); color: #2563eb;">
                        <i class="bi bi-patch-check-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark" id="modalVerifikasiLayananLabel">Verifikasi Permohonan Dokumen</h5>
                        <small class="text-muted" id="label_nama_siswa">Nama Siswa</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>petugas/dashboard.php" method="POST">
                <input type="hidden" name="action" value="update_status_layanan">
                <input type="hidden" name="id_layanan" id="verif_id_layanan">

                <div class="modal-body px-4 py-3">
                    <div class="mb-3">
                        <label class="form-label-dark">Jenis Dokumen</label>
                        <input type="text" id="verif_jenis_layanan" class="form-control-light bg-light" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="verif_status" class="form-label-dark">Perbarui Status</label>
                        <select name="status" id="verif_status" class="form-select bg-white border-light-subtle" required>
                            <option value="menunggu">Menunggu Verifikasi</option>
                            <option value="proses">Sedang Diproses (Pencetakan / Tanda Tangan)</option>
                            <option value="selesai">Selesai (Siap Diambil di Loket TU)</option>
                            <option value="ditolak">Ditolak (Berkas Tidak Lengkap / Tidak Sesuai)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="verif_catatan" class="form-label-dark">Catatan untuk Siswa</label>
                        <textarea name="catatan_petugas" id="verif_catatan" rows="3" class="form-control-light" placeholder="Contoh: Dokumen telah selesai ditandatangani Kepala Sekolah. Silakan diambil di ruang TU."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light rounded-3 px-3 py-2 small fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-orange-gradient px-4 py-2 small fw-semibold my-0" style="width: auto;">
                        <i class="bi bi-save me-1"></i>Simpan Pembaruan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: BUAT PENGUMUMAN BARU                                    -->
<!-- ============================================================== -->
<div class="modal fade" id="modalTambahPengumuman" tabindex="-1" aria-labelledby="modalTambahPengumumanLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4" style="background-color: #ffffff;">
            <div class="modal-header border-bottom px-4 pt-4 pb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="badge p-2 rounded-3" style="background: rgba(249, 115, 22, 0.12); color: #ea580c;">
                        <i class="bi bi-megaphone-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark" id="modalTambahPengumumanLabel">Buat Pengumuman Baru</h5>
                        <small class="text-muted">Pengumuman akan langsung tampil pada portal akademik siswa.</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>petugas/dashboard.php" method="POST">
                <input type="hidden" name="action" value="tambah_pengumuman">
                <div class="modal-body px-4 py-3">
                    <div class="mb-3">
                        <label for="peng_judul" class="form-label-dark">Judul Pengumuman</label>
                        <input type="text" name="judul" id="peng_judul" class="form-control-light" placeholder="Contoh: Pengambilan Ijazah dan Raport Semester Ganjil" required>
                    </div>

                    <div class="mb-3">
                        <label for="peng_kategori" class="form-label-dark">Kategori Pengumuman</label>
                        <select name="kategori" id="peng_kategori" class="form-select bg-white border-light-subtle" required>
                            <option value="Layanan Tata Usaha">Layanan Tata Usaha</option>
                            <option value="Jadwal Ujian">Jadwal Ujian</option>
                            <option value="Pengingat Keamanan">Pengingat Keamanan</option>
                            <option value="Kegiatan Kesiswaan">Kegiatan Kesiswaan</option>
                            <option value="Informasi Libur">Informasi Libur</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="peng_isi" class="form-label-dark">Isi Informasi Pengumuman</label>
                        <textarea name="isi" id="peng_isi" rows="4" class="form-control-light" placeholder="Tuliskan isi pengumuman secara rinci..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light rounded-3 px-3 py-2 small fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-orange-gradient px-4 py-2 small fw-semibold my-0" style="width: auto;">
                        <i class="bi bi-send-fill me-1"></i>Terbitkan Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const verifButtons = document.querySelectorAll('.btn-verifikasi-layanan');
    verifButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('verif_id_layanan').value = this.getAttribute('data-id');
            document.getElementById('label_nama_siswa').textContent = 'Pemohon: ' + this.getAttribute('data-siswa');
            document.getElementById('verif_jenis_layanan').value = this.getAttribute('data-jenis');
            document.getElementById('verif_status').value = this.getAttribute('data-status');
            document.getElementById('verif_catatan').value = this.getAttribute('data-catatan');
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
