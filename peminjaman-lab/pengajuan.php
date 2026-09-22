<?php
include 'koneksi.php';
if (!isset($_SESSION['id_admin'])) { header('Location: login.php'); exit; }

/* ---------- SETUJUI ---------- */
if (isset($_GET['setuju'])) {
    $id = (int)$_GET['setuju'];
    $p  = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT * FROM peminjaman WHERE id_peminjaman = $id AND status = 'Menunggu'"));

    if ($p) {
        // stok diperiksa ulang saat persetujuan (bisa saja berubah sejak siswa mengajukan)
        $cukup = true;
        $qCek = mysqli_query($koneksi, "SELECT d.jumlah, b.nama_barang, b.jumlah AS stok
            FROM detail d JOIN barang b ON d.id_barang = b.id_barang WHERE d.id_peminjaman = $id");
        while ($d = mysqli_fetch_assoc($qCek)) {
            if ($d['jumlah'] > $d['stok']) { $cukup = false; break; }
        }
        if (!$cukup) { header("Location: pengajuan.php?gagal=stok"); exit; }

        // setujui: catat petugas, ubah status, kurangi stok
        mysqli_query($koneksi, "UPDATE peminjaman
            SET status = 'Dipinjam', id_admin = {$_SESSION['id_admin']}, alasan_penolakan = NULL
            WHERE id_peminjaman = $id AND status = 'Menunggu'");
        $qDetail = mysqli_query($koneksi, "SELECT id_barang, jumlah FROM detail WHERE id_peminjaman = $id");
        while ($d = mysqli_fetch_assoc($qDetail)) {
            mysqli_query($koneksi, "UPDATE barang SET jumlah = jumlah - {$d['jumlah']} WHERE id_barang = {$d['id_barang']}");
        }
        header("Location: pengajuan.php?sukses=setuju");
        exit;
    }
    header("Location: pengajuan.php");
    exit;
}

/* ---------- TOLAK ---------- */
if (isset($_POST['tolak'])) {
    $id     = (int)$_POST['id_peminjaman'];
    $alasan = mysqli_real_escape_string($koneksi, trim($_POST['alasan']) ?: 'Tidak memenuhi ketentuan peminjaman.');
    mysqli_query($koneksi, "UPDATE peminjaman
        SET status = 'Ditolak', id_admin = {$_SESSION['id_admin']}, alasan_penolakan = '$alasan'
        WHERE id_peminjaman = $id AND status = 'Menunggu'");
    header("Location: pengajuan.php?sukses=tolak");
    exit;
}

 $menu  = 'pengajuan';
 $judul = 'Pengajuan Peminjaman';
include 'template_header.php';

 $data = mysqli_query($koneksi, "
    SELECT p.*, s.nama_siswa, s.kelas, s.nis
    FROM peminjaman p JOIN siswa s ON p.id_siswa = s.id_siswa
    WHERE p.status = 'Menunggu'
    ORDER BY p.id_peminjaman ASC");
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h4 class="mb-0">Pengajuan Peminjaman</h4>
    <span class="badge bg-primary fs-6"><?= mysqli_num_rows($data) ?> menunggu persetujuan</span>
</div>

<?php if (mysqli_num_rows($data) == 0): ?>
<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> Tidak ada pengajuan yang menunggu persetujuan 🎉</div>
<?php endif; ?>

<?php while ($p = mysqli_fetch_assoc($data)):
    $qDetail = mysqli_query($koneksi, "SELECT d.jumlah, b.nama_barang
        FROM detail d JOIN barang b ON d.id_barang = b.id_barang
        WHERE d.id_peminjaman = {$p['id_peminjaman']}");
?>
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
            <div>
                <h6 class="mb-1">
                    <span class="badge text-bg-light border">PJ-<?= str_pad($p['id_peminjaman'], 4, '0', STR_PAD_LEFT) ?></span>
                    <strong><?= e($p['nama_siswa']) ?></strong>
                    <small class="text-muted">(<?= e($p['kelas']) ?> • NIS <?= e($p['nis']) ?>)</small>
                </h6>
                <small class="text-muted">
                    <i class="bi bi-calendar"></i> <?= tglIndo($p['tgl_pinjam']) ?>
                    &nbsp;s.d.&nbsp; <i class="bi bi-calendar-event"></i> <?= tglIndo($p['tgl_jatuh_tempo']) ?>
                </small>
            </div>
            <div class="text-md-end">
                <div class="fw-semibold small mb-1">Barang diminta:</div>
                <?php while ($d = mysqli_fetch_assoc($qDetail)): ?>
                    <span class="badge text-bg-light border"><?= e($d['nama_barang']) ?> × <?= $d['jumlah'] ?></span>
                <?php endwhile; ?>
            </div>
        </div>
        <hr class="my-2">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <a href="pengajuan.php?setuju=<?= $p['id_peminjaman'] ?>" class="btn btn-success"
               onclick="return confirm('Setujui pengajuan ini? Stok barang akan berkurang.')">
                <i class="bi bi-check-lg"></i> Setujui
            </a>
            <form method="post" class="d-flex gap-2 flex-grow-1" style="min-width:250px;">
                <input type="hidden" name="id_peminjaman" value="<?= $p['id_peminjaman'] ?>">
                <input type="text" name="alasan" class="form-control" placeholder="Alasan penolakan (opsional)">
                <button type="submit" name="tolak" class="btn btn-danger"
                        onclick="return confirm('Tolak pengajuan ini?')">
                    <i class="bi bi-x-lg"></i> Tolak
                </button>
            </form>
        </div>
    </div>
</div>
<?php endwhile; ?>

<?php include 'template_footer.php'; ?>