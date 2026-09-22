<?php
include 'koneksi.php';
if (!isset($_SESSION['id_siswa'])) { header('Location: login.php'); exit; }

 $id_siswa = (int)$_SESSION['id_siswa'];
 $error = '';

/* ---------- PROSES SIMPAN PENGAJUAN ---------- */
if (isset($_POST['simpan'])) {
    $tgl_pinjam      = mysqli_real_escape_string($koneksi, $_POST['tgl_pinjam']);
    $tgl_jatuh_tempo = mysqli_real_escape_string($koneksi, $_POST['tgl_jatuh_tempo']);
    $hari_ini = date('Y-m-d');

    if ($tgl_pinjam < $hari_ini) {
        $error = 'Tanggal pinjam tidak boleh di masa lalu!';
    } elseif ($tgl_jatuh_tempo < $tgl_pinjam) {
        $error = 'Tanggal jatuh tempo tidak boleh sebelum tanggal pinjam!';
    } else {
        // Siswa yang masih terlambat tidak boleh mengajukan lagi
        $telat = (int)mysqli_fetch_assoc(mysqli_query($koneksi,
            "SELECT COUNT(*) AS jml FROM peminjaman
             WHERE id_siswa = $id_siswa AND status = 'Dipinjam' AND tgl_jatuh_tempo < '$hari_ini'"))['jml'];
        if ($telat > 0) {
            $error = 'Kamu masih punya pinjaman yang <strong>terlambat</strong>. Kembalikan dulu sebelum mengajukan yang baru!';
        } else {
            // Maksimal 2 pengajuan menunggu persetujuan
            $menunggu = (int)mysqli_fetch_assoc(mysqli_query($koneksi,
                "SELECT COUNT(*) AS jml FROM peminjaman
                 WHERE id_siswa = $id_siswa AND status = 'Menunggu'"))['jml'];
            if ($menunggu >= 2) {
                $error = 'Kamu masih punya ' . $menunggu . ' pengajuan menunggu persetujuan. Tunggu diproses petugas dulu ya!';
            }
        }
    }

    // Validasi stok cukup (berdasarkan stok saat ini)
    if ($error == '') {
        foreach ($_POST['id_barang'] as $i => $id_barang) {
            $id_barang = (int)$id_barang;
            $jumlah    = (int)$_POST['jumlah'][$i];
            if ($id_barang > 0 && $jumlah > 0) {
                $b = mysqli_fetch_assoc(mysqli_query($koneksi,
                    "SELECT nama_barang, jumlah FROM barang WHERE id_barang = $id_barang"));
                if ($jumlah > $b['jumlah']) {
                    $error = 'Stok <strong>' . e($b['nama_barang']) . '</strong> tidak cukup! Tersedia hanya ' . $b['jumlah'] . ' unit.';
                    break;
                }
            }
        }
    }

    if ($error == '') {
        // Simpan: status 'Menunggu', id_admin NULL (diisi petugas saat disetujui)
        // Stok BELUM dikurangi — baru berkurang saat disetujui petugas
        mysqli_query($koneksi, "INSERT INTO peminjaman
            (id_siswa, id_admin, tgl_pinjam, tgl_jatuh_tempo, status)
            VALUES ($id_siswa, NULL, '$tgl_pinjam', '$tgl_jatuh_tempo', 'Menunggu')");
        $id_peminjaman = mysqli_insert_id($koneksi);

        foreach ($_POST['id_barang'] as $i => $id_barang) {
            $id_barang = (int)$id_barang;
            $jumlah    = (int)$_POST['jumlah'][$i];
            if ($id_barang > 0 && $jumlah > 0) {
                mysqli_query($koneksi, "INSERT INTO detail (id_peminjaman, id_barang, jumlah)
                    VALUES ($id_peminjaman, $id_barang, $jumlah)");
            }
        }
        header("Location: siswa_riwayat.php?sukses=ajukan");
        exit;
    }
}

 $menu  = 'ajukan_siswa';
 $judul = 'Ajukan Peminjaman';
include 'template_header.php';

 $barang = mysqli_query($koneksi, "SELECT * FROM barang WHERE jumlah > 0 ORDER BY nama_barang");
?>

<div class="d-flex justify-content-between align-items-center gap-2 mb-3">
    <h4 class="mb-0">Ajukan Peminjaman Barang</h4>
    <a href="siswa_dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<div class="alert alert-info no-print">
    <i class="bi bi-info-circle-fill"></i>
    Pengajuan kamu akan <strong>ditinjau oleh Petugas Laboratorium</strong> terlebih dahulu.
    Jika disetujui, barang resmi dipinjamkan. Kamu bisa memantau statusnya di menu <strong>Riwayat Peminjaman</strong>.
</div>

<?php if ($error): ?>
    <div class="alert alert-danger no-print"><i class="bi bi-x-circle-fill"></i> <?= $error ?></div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="post">
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Tanggal Pinjam <span class="text-danger">*</span></label>
                    <input type="date" name="tgl_pinjam" class="form-control"
                           value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tanggal Jatuh Tempo <span class="text-danger">*</span></label>
                    <input type="date" name="tgl_jatuh_tempo" class="form-control"
                           value="<?= date('Y-m-d', strtotime('+7 days')) ?>" min="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <label class="form-label fw-semibold">Barang yang Ingin Dipinjam</label>
            <table class="table align-middle" id="tabelBarang">
                <thead class="table-light">
                    <tr>
                        <th style="width:65%">Barang</th>
                        <th style="width:20%">Jumlah</th>
                        <th style="width:15%"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select name="id_barang[]" class="form-select" required>
                                <option value="">-- Pilih Barang --</option>
                                <?php while ($b = mysqli_fetch_assoc($barang)): ?>
                                    <option value="<?= $b['id_barang'] ?>">
                                        <?= e($b['nama_barang']) ?> (stok: <?= $b['jumlah'] ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </td>
                        <td><input type="number" name="jumlah[]" class="form-control" min="1" value="1" required></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-outline-danger btn-hapus-baris" title="Hapus baris">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <button type="button" id="btnTambahBaris" class="btn btn-outline-primary btn-sm mb-4">
                <i class="bi bi-plus-lg"></i> Tambah Baris
            </button>

            <hr>
            <button type="submit" name="simpan" class="btn btn-primary">
                <i class="bi bi-send"></i> Kirim Pengajuan
            </button>
            <a href="siswa_dashboard.php" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>

<script>
document.getElementById('btnTambahBaris').addEventListener('click', function () {
    var barisAsli = document.querySelector('#tabelBarang tbody tr');
    var barisBaru = barisAsli.cloneNode(true);
    barisBaru.querySelector('select').selectedIndex = 0;
    barisBaru.querySelector('input[type=number]').value = 1;
    document.querySelector('#tabelBarang tbody').appendChild(barisBaru);
});
document.addEventListener('click', function (e) {
    var tombol = e.target.closest('.btn-hapus-baris');
    if (tombol) {
        var tbody = document.querySelector('#tabelBarang tbody');
        if (tbody.querySelectorAll('tr').length > 1) tombol.closest('tr').remove();
    }
});
</script>

<?php include 'template_footer.php'; ?>