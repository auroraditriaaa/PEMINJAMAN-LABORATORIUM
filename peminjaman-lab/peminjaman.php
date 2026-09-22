<?php
include 'koneksi.php';
if (!isset($_SESSION['id_admin'])) { header('Location: login.php'); exit; }

/* ---------- HAPUS PEMINJAMAN (stok dikembalikan otomatis) ---------- */
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $p  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT status FROM peminjaman WHERE id_peminjaman=$id"));

    if ($p && $p['status'] == 'Dipinjam') {
        // kembalikan stok barang
        $qDetail = mysqli_query($koneksi, "SELECT id_barang, jumlah FROM detail WHERE id_peminjaman=$id");
        while ($d = mysqli_fetch_assoc($qDetail)) {
            mysqli_query($koneksi, "UPDATE barang SET jumlah = jumlah + {$d['jumlah']} WHERE id_barang = {$d['id_barang']}");
        }
    }
    // detail & pengembalian ikut terhapus (ON DELETE CASCADE)
    mysqli_query($koneksi, "DELETE FROM peminjaman WHERE id_peminjaman=$id");
    header("Location: peminjaman.php?sukses=hapus");
    exit;
}

 $menu  = 'peminjaman';
 $judul = 'Data Peminjaman';
include 'template_header.php';

/* ---------- FILTER STATUS ---------- */
 $filter = $_GET['status'] ?? '';
 $where  = '';
if ($filter == 'Dipinjam' || $filter == 'Dikembalikan') {
    $where = " WHERE p.status = '$filter'";
}

 $data = mysqli_query($koneksi, "
    SELECT p.*, s.nama_siswa, s.kelas, s.nis, a.nama_admin
    FROM peminjaman p
    JOIN siswa s ON p.id_siswa = s.id_siswa
        LEFT JOIN admin a ON p.id_admin = a.id_admin
    $where
    ORDER BY p.id_peminjaman DESC
");
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h4 class="mb-0">Data Peminjaman</h4>
    <a href="peminjaman_tambah.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Buat Peminjaman</a>
</div>

<ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link <?= $filter == '' ? 'active' : '' ?>" href="peminjaman.php">Semua</a></li>
    <li class="nav-item"><a class="nav-link <?= $filter == 'Dipinjam' ? 'active' : '' ?>" href="peminjaman.php?status=Dipinjam">Sedang Dipinjam</a></li>
    <li class="nav-item"><a class="nav-link <?= $filter == 'Dikembalikan' ? 'active' : '' ?>" href="peminjaman.php?status=Dikembalikan">Dikembalikan</a></li>
</ul>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th width="45">No</th><th>Kode</th><th>Siswa</th><th>Tgl Pinjam</th>
                    <th>Jatuh Tempo</th><th>Dicatat Oleh</th><th>Status</th><th width="170" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($data) == 0): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada data peminjaman</td></tr>
                <?php endif; ?>
                <?php $no = 1; while ($p = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td class="fw-semibold">PJ-<?= str_pad($p['id_peminjaman'], 4, '0', STR_PAD_LEFT) ?></td>
                    <td>
                        <?= e($p['nama_siswa']) ?><br>
                        <small class="text-muted"><?= e($p['kelas']) ?> • NIS <?= e($p['nis']) ?></small>
                    </td>
                    <td><?= tglIndo($p['tgl_pinjam']) ?></td>
                    <td><?= tglIndo($p['tgl_jatuh_tempo']) ?></td>
                    <td><?= e($p['nama_admin']) ?: '—' ?></td>
                    <td><?= statusPeminjaman($p) ?></td>
                                        <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <?php if ($p['status'] == 'Dipinjam'): ?>
                                <a href="pengembalian_proses.php?id=<?= $p['id_peminjaman'] ?>" class="btn btn-success"
                                   title="Proses pengembalian"><i class="bi bi-arrow-return-left"></i> Kembalikan</a>
                            <?php elseif ($p['status'] == 'Menunggu'): ?>
                                <a href="pengajuan.php" class="btn btn-primary"><i class="bi bi-hourglass-split"></i> Proses</a>
                            <?php else: ?>
                                <span class="btn btn-outline-success disabled"><i class="bi bi-check-lg"></i> Selesai</span>
                            <?php endif; ?>
                            <a href="peminjaman.php?hapus=<?= $p['id_peminjaman'] ?>" class="btn btn-outline-danger" title="Hapus"
                               onclick="return confirm('Yakin hapus transaksi ini? Jika masih dipinjam, stok akan dikembalikan.')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <!-- rincian barang yang dipinjam -->
                <tr class="table-light">
                    <td colspan="8" class="small py-2">
                        <i class="bi bi-box-seam text-muted"></i>
                        <?php
                        $qDetail = mysqli_query($koneksi, "
                            SELECT d.jumlah, b.nama_barang
                            FROM detail d JOIN barang b ON d.id_barang = b.id_barang
                            WHERE d.id_peminjaman = {$p['id_peminjaman']}");
                        $items = [];
                        while ($d = mysqli_fetch_assoc($qDetail)) {
                            $items[] = e($d['nama_barang']) . ' <span class="text-muted">× ' . $d['jumlah'] . '</span>';
                        }
                        echo implode(' &nbsp;•&nbsp; ', $items);
                        ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'template_footer.php'; ?>