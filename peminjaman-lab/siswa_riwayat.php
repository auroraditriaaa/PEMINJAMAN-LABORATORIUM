<?php
include 'koneksi.php';
if (!isset($_SESSION['id_siswa'])) { header('Location: login.php'); exit; }

 $id_siswa = (int)$_SESSION['id_siswa'];
 $menu  = 'riwayat_siswa';
 $judul = 'Riwayat Peminjaman';
include 'template_header.php';

 $siswa = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_siswa = $id_siswa"));

 $data = mysqli_query($koneksi, "
    SELECT p.*, g.tgl_kembali, g.denda
    FROM peminjaman p
    LEFT JOIN pengembalian g ON g.id_peminjaman = p.id_peminjaman
    WHERE p.id_siswa = $id_siswa
    ORDER BY p.id_peminjaman DESC");
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h4 class="mb-0">Riwayat Peminjaman Saya</h4>
    <button onclick="window.print()" class="btn btn-outline-dark no-print"><i class="bi bi-printer"></i> Cetak</button>
</div>

<!-- Kop (hanya muncul saat dicetak) -->
<div class="d-none d-print-block text-center border-bottom border-2 border-dark pb-2 mb-3">
    <h5 class="mb-0 fw-bold">RIWAYAT PEMINJAMAN BARANG LABORATORIUM</h5>
    <div>SMK PGRI 2 Ponorogo</div>
    <small><?= e($siswa['nama_siswa']) ?> — <?= e($siswa['kelas']) ?> (NIS <?= e($siswa['nis']) ?>)</small>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th width="45">No</th><th>Kode</th><th>Tgl Pinjam</th><th>Jatuh Tempo</th>
                    <th>Tgl Kembali</th><th>Status</th><th>Denda</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($data) == 0): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Kamu belum pernah meminjam barang</td></tr>
                <?php endif; ?>
                <?php $no = 1; while ($p = mysqli_fetch_assoc($data)):
                    // hitung keterlambatan (jika sudah dikembalikan)
                    $telat = 0;
                    if ($p['tgl_kembali']) {
                        $telat = max(floor((strtotime($p['tgl_kembali']) - strtotime($p['tgl_jatuh_tempo'])) / 86400), 0);
                    }
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td class="fw-semibold">PJ-<?= str_pad($p['id_peminjaman'], 4, '0', STR_PAD_LEFT) ?></td>
                    <td><?= tglIndo($p['tgl_pinjam']) ?></td>
                    <td><?= tglIndo($p['tgl_jatuh_tempo']) ?></td>
                    <td><?= $p['tgl_kembali'] ? tglIndo($p['tgl_kembali']) : '—' ?></td>
                    <td>
                        <?= statusPeminjaman($p) ?>
                        <?php if ($telat > 0): ?><br><small class="text-danger">terlambat <?= $telat ?> hari</small><?php endif; ?>
                    </td>
                    <td><?= ($p['denda'] ?? 0) > 0 ? '<span class="text-danger fw-semibold">' . rupiah($p['denda']) . '</span>' : '—' ?></td>
                </tr>
                <!-- rincian barang -->
                <tr class="table-light">
                    <td colspan="7" class="small py-2">
                        <i class="bi bi-box-seam text-muted"></i>
                        <?php
                        $qDetail = mysqli_query($koneksi, "SELECT d.jumlah, b.nama_barang
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