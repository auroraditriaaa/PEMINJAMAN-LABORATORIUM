<?php
include 'koneksi.php';
if (!isset($_SESSION['id_admin'])) { header('Location: login.php'); exit; }

 $menu  = 'pengembalian';
 $judul = 'Pengembalian';
include 'template_header.php';

 $dipinjam = mysqli_query($koneksi, "
    SELECT p.*, s.nama_siswa, s.kelas
    FROM peminjaman p JOIN siswa s ON p.id_siswa = s.id_siswa
    WHERE p.status = 'Dipinjam'
    ORDER BY p.tgl_jatuh_tempo ASC");

 $riwayat = mysqli_query($koneksi, "
    SELECT g.*, s.nama_siswa, s.kelas,
           GREATEST(DATEDIFF(g.tgl_kembali, p.tgl_jatuh_tempo), 0) AS terlambat
    FROM pengembalian g
    JOIN peminjaman p ON g.id_peminjaman = p.id_peminjaman
    JOIN siswa s ON p.id_siswa = s.id_siswa
    ORDER BY g.id_pengembalian DESC");
?>

<h4 class="mb-3">Pengembalian</h4>

<!-- ===== Menunggu pengembalian ===== -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-hourglass-split"></i> Menunggu Pengembalian
        <span class="badge bg-warning text-dark"><?= mysqli_num_rows($dipinjam) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th width="45">No</th><th>Kode</th><th>Siswa</th><th>Tgl Pinjam</th>
                    <th>Jatuh Tempo</th><th>Status</th><th width="130" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($dipinjam) == 0): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada barang yang sedang dipinjam 🎉</td></tr>
                <?php endif; ?>
                <?php $no = 1; while ($p = mysqli_fetch_assoc($dipinjam)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td class="fw-semibold">PJ-<?= str_pad($p['id_peminjaman'], 4, '0', STR_PAD_LEFT) ?></td>
                    <td><?= e($p['nama_siswa']) ?> <small class="text-muted">(<?= e($p['kelas']) ?>)</small></td>
                    <td><?= tglIndo($p['tgl_pinjam']) ?></td>
                    <td><?= tglIndo($p['tgl_jatuh_tempo']) ?></td>
                    <td><?= statusPeminjaman($p) ?></td>
                    <td class="text-center">
                        <a href="pengembalian_proses.php?id=<?= $p['id_peminjaman'] ?>" class="btn btn-sm btn-success">
                            <i class="bi bi-arrow-return-left"></i> Proses
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ===== Riwayat pengembalian ===== -->
<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history"></i> Riwayat Pengembalian</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th width="45">No</th><th>Tgl Dikembalikan</th><th>Kode</th><th>Siswa</th>
                    <th>Terlambat</th><th>Kondisi</th><th>Denda</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($riwayat) == 0): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada riwayat pengembalian</td></tr>
                <?php endif; ?>
                <?php $no = 1; while ($r = mysqli_fetch_assoc($riwayat)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= tglIndo($r['tgl_kembali']) ?></td>
                    <td class="fw-semibold">PJ-<?= str_pad($r['id_peminjaman'], 4, '0', STR_PAD_LEFT) ?></td>
                    <td><?= e($r['nama_siswa']) ?> <small class="text-muted">(<?= e($r['kelas']) ?>)</small></td>
                    <td>
                        <?= $r['terlambat'] > 0
                            ? '<span class="badge bg-danger">' . $r['terlambat'] . ' hari</span>'
                            : '<span class="badge bg-success">Tepat waktu</span>' ?>
                    </td>
                    <td><?= e($r['kondisi_barang']) ?></td>
                    <td><?= $r['denda'] > 0 ? '<span class="text-danger fw-semibold">' . rupiah($r['denda']) . '</span>' : '-' ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'template_footer.php'; ?>