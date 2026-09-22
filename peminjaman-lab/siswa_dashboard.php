<?php
include 'koneksi.php';
if (!isset($_SESSION['id_siswa'])) { header('Location: login.php'); exit; }

 $id_siswa = (int)$_SESSION['id_siswa'];
 /* ---------- BATALKAN PENGAJUAN (hanya milik sendiri & masih Menunggu) ---------- */
if (isset($_GET['batalkan'])) {
    $id = (int)$_GET['batalkan'];
    mysqli_query($koneksi, "DELETE FROM peminjaman
        WHERE id_peminjaman = $id AND id_siswa = $id_siswa AND status = 'Menunggu'");
    header("Location: siswa_riwayat.php?sukses=batal");
    exit;
}
 $menu  = 'dashboard_siswa';
 $judul = 'Dashboard Siswa';
include 'template_header.php';

 $siswa = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_siswa = $id_siswa"));

// ===== Statistik pribadi siswa =====
 $statTotal = (int)mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COUNT(*) AS jml FROM peminjaman WHERE id_siswa = $id_siswa"))['jml'];
 $statAktif = (int)mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COUNT(*) AS jml FROM peminjaman WHERE id_siswa = $id_siswa AND status = 'Dipinjam'"))['jml'];
 $statTelat = (int)mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COUNT(*) AS jml FROM peminjaman WHERE id_siswa = $id_siswa AND status = 'Dipinjam' AND tgl_jatuh_tempo < CURDATE()"))['jml'];
 $statDenda = (int)mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT IFNULL(SUM(g.denda),0) AS jml FROM pengembalian g
     JOIN peminjaman p ON g.id_peminjaman = p.id_peminjaman
     WHERE p.id_siswa = $id_siswa"))['jml'];

// Pinjaman yang sedang berjalan
 $aktif = mysqli_query($koneksi, "SELECT * FROM peminjaman
    WHERE id_siswa = $id_siswa AND status = 'Dipinjam'
    ORDER BY tgl_jatuh_tempo ASC");
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h4 class="mb-1">Halo, <?= e($siswa['nama_siswa']) ?>! 👋</h4>
        <p class="text-muted mb-0">
            <span class="badge text-bg-light border"><?= e($siswa['nis']) ?></span>
            <?= e($siswa['kelas']) ?> — <?= e($siswa['jurusan']) ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="siswa_ajukan.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Ajukan Peminjaman</a>
        <a href="siswa_riwayat.php" class="btn btn-outline-primary"><i class="bi bi-clock-history"></i> Riwayat</a>
    </div>
</div>

<?php
// Info pengajuan yang masih menunggu
 $menungguSaya = (int)mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COUNT(*) AS jml FROM peminjaman WHERE id_siswa = $id_siswa AND status = 'Menunggu'"))['jml'];
?>
<?php if ($menungguSaya > 0): ?>
<div class="alert alert-info d-flex justify-content-between align-items-center no-print">
    <span><i class="bi bi-hourglass-split"></i>
        Kamu punya <strong><?= $menungguSaya ?> pengajuan</strong> yang sedang menunggu persetujuan petugas.</span>
    <a href="siswa_riwayat.php" class="btn btn-sm btn-outline-primary">Lihat</a>
</div>
<?php endif; ?>

<!-- ===== Kartu statistik ===== -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="ikon bg-primary bg-opacity-10 text-primary"><i class="bi bi-journal-text"></i></div>
                <div><div class="fs-4 fw-bold lh-1"><?= $statTotal ?></div>
                <div class="text-muted small">Total Peminjaman</div></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="ikon bg-warning bg-opacity-10 text-warning"><i class="bi bi-arrow-right-circle"></i></div>
                <div><div class="fs-4 fw-bold lh-1"><?= $statAktif ?></div>
                <div class="text-muted small">Sedang Dipinjam</div></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="ikon bg-danger bg-opacity-10 text-danger"><i class="bi bi-exclamation-triangle"></i></div>
                <div><div class="fs-4 fw-bold lh-1"><?= $statTelat ?></div>
                <div class="text-muted small">Terlambat</div></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="ikon bg-success bg-opacity-10 text-success"><i class="bi bi-cash-coin"></i></div>
                <div><div class="fs-5 fw-bold lh-1"><?= rupiah($statDenda) ?></div>
                <div class="text-muted small">Total Denda</div></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Barang yang sedang dipinjam -->
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-box-seam"></i> Barang yang Sedang Kamu Pinjam</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>Kode & Barang</th><th>Tgl Pinjam</th><th>Jatuh Tempo</th><th>Sisa Waktu</th></tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($aktif) == 0): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">Kamu tidak sedang meminjam barang apa pun 🎉</td></tr>
                        <?php endif; ?>
                        <?php while ($p = mysqli_fetch_assoc($aktif)):
                            $selisih = floor((strtotime($p['tgl_jatuh_tempo']) - strtotime(date('Y-m-d'))) / 86400);
                            $qDetail = mysqli_query($koneksi, "SELECT d.jumlah, b.nama_barang
                                FROM detail d JOIN barang b ON d.id_barang = b.id_barang
                                WHERE d.id_peminjaman = {$p['id_peminjaman']}");
                            $items = [];
                            while ($d = mysqli_fetch_assoc($qDetail)) $items[] = e($d['nama_barang']) . ' ×' . $d['jumlah'];
                        ?>
                        <tr>
                            <td class="fw-semibold">PJ-<?= str_pad($p['id_peminjaman'], 4, '0', STR_PAD_LEFT) ?>
                                <br><small class="text-muted fw-normal"><?= implode(', ', $items) ?></small></td>
                            <td><?= tglIndo($p['tgl_pinjam']) ?></td>
                            <td><?= tglIndo($p['tgl_jatuh_tempo']) ?></td>
                            <td>
                                <?php if ($selisih < 0): ?>
                                    <span class="badge bg-danger">Terlambat <?= abs($selisih) ?> hari</span>
                                <?php elseif ($selisih == 0): ?>
                                    <span class="badge bg-warning text-dark">Jatuh tempo hari ini!</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Sisa <?= $selisih ?> hari</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Info aturan -->
    <div class="col-lg-4">
        <div class="card shadow-sm h-100 border-info">
            <div class="card-header bg-white fw-semibold text-info"><i class="bi bi-info-circle"></i> Aturan Peminjaman</div>
            <div class="card-body small">
                <ul class="mb-0">
                    <li>Barang wajib dikembalikan <strong>sebelum tanggal jatuh tempo</strong>.</li>
                    <li>Keterlambatan dikenakan <strong>denda Rp 1.000/hari</strong>.</li>
                    <li>Peminjaman dilayani oleh <strong>Petugas Laboratorium</strong>.</li>
                    <li>Barang harus dikembalikan dalam kondisi baik.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include 'template_footer.php'; ?>