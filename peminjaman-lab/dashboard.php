<?php
include 'koneksi.php';
if (!isset($_SESSION['id_admin'])) { header('Location: login.php'); exit; }

 $menu  = 'dashboard';
 $judul = 'Dashboard';
include 'template_header.php';

// ===== Statistik =====
 $statBarang  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS jenis, IFNULL(SUM(jumlah),0) AS stok FROM barang"));
 $statSiswa   = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM siswa"))['total'];
 $statPinjam  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM peminjaman WHERE status='Dipinjam'"))['total'];
 $statKembali = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM peminjaman WHERE status='Dikembalikan'"))['total'];

 $terbaru = mysqli_query($koneksi, "
    SELECT p.*, s.nama_siswa, s.kelas
    FROM peminjaman p JOIN siswa s ON p.id_siswa = s.id_siswa
    ORDER BY p.id_peminjaman DESC LIMIT 5");

 $menipis = mysqli_query($koneksi, "SELECT * FROM barang WHERE jumlah <= 2 ORDER BY jumlah ASC LIMIT 5");
?>

<div class="d-flex justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h4 class="mb-1">Dashboard</h4>
        <p class="text-muted mb-0">Selamat datang, <strong><?= e($_SESSION['nama_admin']) ?></strong>!</p>
    </div>
    <a href="peminjaman_tambah.php" class="btn btn-primary no-print">
        <i class="bi bi-plus-lg"></i> <span class="d-none d-sm-inline">Buat Peminjaman</span>
    </a>
</div>

<!-- ===== Kartu statistik ===== -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="ikon bg-primary bg-opacity-10 text-primary"><i class="bi bi-box-seam"></i></div>
                <div>
                    <div class="fs-4 fw-bold lh-1"><?= $statBarang['jenis'] ?></div>
                    <div class="text-muted small">Jenis Barang</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="ikon bg-success bg-opacity-10 text-success"><i class="bi bi-stack"></i></div>
                <div>
                    <div class="fs-4 fw-bold lh-1"><?= $statBarang['stok'] ?></div>
                    <div class="text-muted small">Total Unit Tersedia</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="ikon bg-info bg-opacity-10 text-info"><i class="bi bi-people"></i></div>
                <div>
                    <div class="fs-4 fw-bold lh-1"><?= $statSiswa ?></div>
                    <div class="text-muted small">Siswa Terdaftar</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="ikon bg-warning bg-opacity-10 text-warning"><i class="bi bi-arrow-right-circle"></i></div>
                <div>
                    <div class="fs-4 fw-bold lh-1"><?= $statPinjam ?></div>
                    <div class="text-muted small">Sedang Dipinjam (<?= $statKembali ?> selesai)</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Peminjaman terbaru -->
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history"></i> Peminjaman Terbaru</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>Kode</th><th>Siswa</th><th>Tgl Pinjam</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($terbaru) == 0): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada transaksi peminjaman</td></tr>
                        <?php endif; ?>
                        <?php while ($t = mysqli_fetch_assoc($terbaru)): ?>
                        <tr>
                            <td class="fw-semibold">PJ-<?= str_pad($t['id_peminjaman'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td><?= e($t['nama_siswa']) ?><br><small class="text-muted"><?= e($t['kelas']) ?></small></td>
                            <td><?= tglIndo($t['tgl_pinjam']) ?></td>
                            <td><?= statusPeminjaman($t) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Stok menipis -->
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold text-warning"><i class="bi bi-exclamation-triangle"></i> Stok Menipis</div>
            <ul class="list-group list-group-flush">
                <?php if (mysqli_num_rows($menipis) == 0): ?>
                    <li class="list-group-item text-muted">Semua stok aman 👍</li>
                <?php endif; ?>
                <?php while ($m = mysqli_fetch_assoc($menipis)): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?= e($m['nama_barang']) ?>
                    <span class="badge bg-danger rounded-pill"><?= $m['jumlah'] ?></span>
                </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>
</div>

<?php include 'template_footer.php'; ?>