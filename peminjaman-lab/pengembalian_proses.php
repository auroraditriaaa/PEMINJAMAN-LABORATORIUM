<?php
include 'koneksi.php';
if (!isset($_SESSION['id_admin'])) { header('Location: login.php'); exit; }

 $id = (int)($_GET['id'] ?? $_POST['id_peminjaman'] ?? 0);

// ambil data peminjaman yang masih berstatus Dipinjam
 $p = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT p.*, s.nama_siswa, s.kelas, s.nis
    FROM peminjaman p JOIN siswa s ON p.id_siswa = s.id_siswa
    WHERE p.id_peminjaman = $id AND p.status = 'Dipinjam'"));

if (!$p) {
    header("Location: pengembalian.php");
    exit;
}

/* ---------- PROSES SIMPAN PENGEMBALIAN ---------- */
if (isset($_POST['kembalikan'])) {
    $tgl_kembali = mysqli_real_escape_string($koneksi, $_POST['tgl_kembali']);
    $kondisi     = mysqli_real_escape_string($koneksi, $_POST['kondisi']);

    // denda otomatis: Rp 1.000 per hari keterlambatan
    $terlambat = floor((strtotime($tgl_kembali) - strtotime($p['tgl_jatuh_tempo'])) / 86400);
    $denda     = ($terlambat > 0) ? $terlambat * 1000 : 0;

    // 1. catat ke tabel pengembalian
    mysqli_query($koneksi, "INSERT INTO pengembalian (id_peminjaman, tgl_kembali, kondisi_barang, denda)
        VALUES ($id, '$tgl_kembali', '$kondisi', $denda)");
    // 2. ubah status peminjaman
    mysqli_query($koneksi, "UPDATE peminjaman SET status='Dikembalikan' WHERE id_peminjaman=$id");
    // 3. kembalikan stok barang
    $qDetail = mysqli_query($koneksi, "SELECT id_barang, jumlah FROM detail WHERE id_peminjaman=$id");
    while ($d = mysqli_fetch_assoc($qDetail)) {
        mysqli_query($koneksi, "UPDATE barang SET jumlah = jumlah + {$d['jumlah']} WHERE id_barang = {$d['id_barang']}");
    }

    header("Location: pengembalian.php?sukses=kembali");
    exit;
}

 $menu  = 'pengembalian';
 $judul = 'Proses Pengembalian';
include 'template_header.php';

 $rincian = mysqli_query($koneksi, "
    SELECT d.jumlah, b.kode_barang, b.nama_barang
    FROM detail d JOIN barang b ON d.id_barang = b.id_barang
    WHERE d.id_peminjaman = $id");
?>

<div class="d-flex justify-content-between align-items-center gap-2 mb-3">
    <h4 class="mb-0">Proses Pengembalian</h4>
    <a href="pengembalian.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<div class="row g-3 mb-4">
    <!-- Info transaksi -->
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">
                Detail Peminjaman — <span class="text-primary">PJ-<?= str_pad($p['id_peminjaman'], 4, '0', STR_PAD_LEFT) ?></span>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-2">
                    <tr><th width="150">Nama Siswa</th><td><?= e($p['nama_siswa']) ?></td></tr>
                    <tr><th>NIS / Kelas</th><td><?= e($p['nis']) ?> / <?= e($p['kelas']) ?></td></tr>
                    <tr><th>Tanggal Pinjam</th><td><?= tglIndo($p['tgl_pinjam']) ?></td></tr>
                    <tr><th>Jatuh Tempo</th><td><?= tglIndo($p['tgl_jatuh_tempo']) ?></td></tr>
                </table>
                <strong>Rincian Barang:</strong>
                <ul class="mb-0 mt-1">
                    <?php while ($d = mysqli_fetch_assoc($rincian)): ?>
                        <li><?= e($d['nama_barang']) ?> <span class="text-muted">(<?= e($d['kode_barang']) ?>)</span> — <strong><?= $d['jumlah'] ?> unit</strong></li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Form pengembalian -->
    <div class="col-lg-5">
        <div class="card shadow-sm h-100 border-primary">
            <div class="card-header bg-primary text-white fw-semibold"><i class="bi bi-arrow-return-left"></i> Form Pengembalian</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="id_peminjaman" value="<?= $p['id_peminjaman'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Tanggal Dikembalikan <span class="text-danger">*</span></label>
                        <input type="date" name="tgl_kembali" id="tglKembali" class="form-control"
                               value="<?= date('Y-m-d') ?>" min="<?= $p['tgl_pinjam'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kondisi Barang</label>
                        <select name="kondisi" class="form-select">
                            <option value="Baik">Baik</option>
                            <option value="Rusak Ringan">Rusak Ringan</option>
                            <option value="Rusak Berat">Rusak Berat</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estimasi Denda (Rp 1.000/hari)</label>
                        <div class="form-control bg-light" id="infoDenda">—</div>
                    </div>
                    <button type="submit" name="kembalikan" class="btn btn-primary w-100"
                            onclick="return confirm('Konfirmasi pengembalian barang ini?')">
                        <i class="bi bi-check-lg"></i> Simpan Pengembalian
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
var jatuhTempo = '<?= $p['tgl_jatuh_tempo'] ?>';
var inputTgl   = document.getElementById('tglKembali');

function hitungDenda() {
    var selisih   = Math.floor((new Date(inputTgl.value) - new Date(jatuhTempo)) / 86400000);
    var infoDenda = document.getElementById('infoDenda');
    if (selisih > 0) {
        infoDenda.innerHTML = '<span class="text-danger fw-semibold">Terlambat ' + selisih +
            ' hari — Denda Rp ' + (selisih * 1000).toLocaleString('id-ID') + '</span>';
    } else {
        infoDenda.innerHTML = '<span class="text-success">Tepat waktu — tanpa denda</span>';
    }
}
inputTgl.addEventListener('change', hitungDenda);
hitungDenda();
</script>

<?php include 'template_footer.php'; ?>