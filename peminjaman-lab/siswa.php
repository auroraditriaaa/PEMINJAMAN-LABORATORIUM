<?php
include 'koneksi.php';
if (!isset($_SESSION['id_admin'])) { header('Location: login.php'); exit; }

/* ---------- PROSES TAMBAH ---------- */
if (isset($_POST['tambah'])) {
    $nis     = mysqli_real_escape_string($koneksi, $_POST['nis']);
    $nama    = mysqli_real_escape_string($koneksi, $_POST['nama_siswa']);
    $kelas   = mysqli_real_escape_string($koneksi, $_POST['kelas']);
    $jurusan = mysqli_real_escape_string($koneksi, $_POST['jurusan']);
    $telp    = mysqli_real_escape_string($koneksi, $_POST['no_telp']);
    $alamat  = mysqli_real_escape_string($koneksi, $_POST['alamat']);

    if (mysqli_num_rows(mysqli_query($koneksi, "SELECT id_siswa FROM siswa WHERE nis='$nis'")) > 0) {
        header("Location: siswa.php?aksi=tambah&gagal=nis");
        exit;
    }
        // username = NIS, password awal = siswa123 (bisa diganti siswa di menu Profil)
    mysqli_query($koneksi, "INSERT INTO siswa (nis, nama_siswa, kelas, jurusan, no_telp, alamat, username, password)
        VALUES ('$nis', '$nama', '$kelas', '$jurusan', '$telp', '$alamat', '$nis', MD5('siswa123'))");
    header("Location: siswa.php?sukses=tambah");
    exit;
}

/* ---------- PROSES EDIT ---------- */
if (isset($_POST['edit'])) {
    $id      = (int)$_POST['id_siswa'];
    $nis     = mysqli_real_escape_string($koneksi, $_POST['nis']);
    $nama    = mysqli_real_escape_string($koneksi, $_POST['nama_siswa']);
    $kelas   = mysqli_real_escape_string($koneksi, $_POST['kelas']);
    $jurusan = mysqli_real_escape_string($koneksi, $_POST['jurusan']);
    $telp    = mysqli_real_escape_string($koneksi, $_POST['no_telp']);
    $alamat  = mysqli_real_escape_string($koneksi, $_POST['alamat']);

    if (mysqli_num_rows(mysqli_query($koneksi, "SELECT id_siswa FROM siswa WHERE nis='$nis' AND id_siswa != $id")) > 0) {
        header("Location: siswa.php?aksi=edit&id=$id&gagal=nis");
        exit;
    }
    mysqli_query($koneksi, "UPDATE siswa SET nis='$nis', nama_siswa='$nama', kelas='$kelas',
        jurusan='$jurusan', no_telp='$telp', alamat='$alamat' WHERE id_siswa=$id");
    header("Location: siswa.php?sukses=edit");
    exit;
}

/* ---------- HAPUS ---------- */
if (isset($_GET['hapus'])) {
    $id     = (int)$_GET['hapus'];
    $dipakai = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM peminjaman WHERE id_siswa=$id"))['jml'];
    if ($dipakai > 0) {
        header("Location: siswa.php?gagal=hapus");
        exit;
    }
    mysqli_query($koneksi, "DELETE FROM siswa WHERE id_siswa=$id");
    header("Location: siswa.php?sukses=hapus");
    exit;
}

 $menu  = 'siswa';
 $judul = 'Data Siswa';
include 'template_header.php';

 $aksi = $_GET['aksi'] ?? 'list';
 $edit = null;
if ($aksi == 'edit') {
    $id   = (int)($_GET['id'] ?? 0);
    $edit = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_siswa=$id"));
}

 $keyword = mysqli_real_escape_string($koneksi, $_GET['cari'] ?? '');
if ($keyword != '') {
    $data = mysqli_query($koneksi, "SELECT * FROM siswa
        WHERE nis LIKE '%$keyword%' OR nama_siswa LIKE '%$keyword%' OR kelas LIKE '%$keyword%'
        ORDER BY id_siswa DESC");
} else {
    $data = mysqli_query($koneksi, "SELECT * FROM siswa ORDER BY id_siswa DESC");
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h4 class="mb-0">Data Siswa</h4>
    <?php if ($aksi == 'list'): ?>
    <a href="siswa.php?aksi=tambah" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Siswa</a>
    <?php endif; ?>
</div>

<!-- ===== FORM TAMBAH / EDIT ===== -->
<?php if ($aksi == 'tambah' || ($aksi == 'edit' && $edit)): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-<?= $aksi == 'tambah' ? 'plus-circle' : 'pencil' ?>"></i>
        <?= $aksi == 'tambah' ? 'Tambah Siswa Baru' : 'Edit Data Siswa' ?>
    </div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <?php if ($aksi == 'edit'): ?>
                <input type="hidden" name="id_siswa" value="<?= $edit['id_siswa'] ?>">
            <?php endif; ?>
            <div class="col-md-3">
                <label class="form-label">NIS <span class="text-danger">*</span></label>
                <input type="text" name="nis" class="form-control" value="<?= e($edit['nis'] ?? '') ?>" required>
            </div>
            <div class="col-md-5">
                <label class="form-label">Nama Siswa <span class="text-danger">*</span></label>
                <input type="text" name="nama_siswa" class="form-control" value="<?= e($edit['nama_siswa'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Kelas <span class="text-danger">*</span></label>
                <input type="text" name="kelas" class="form-control" list="daftarKelas"
                       value="<?= e($edit['kelas'] ?? '') ?>" required>
                <datalist id="daftarKelas">
                    <option value="X RPL 1"></option><option value="XI RPL 1"></option><option value="XII RPL 1"></option>
                    <option value="X TKJ 1"></option><option value="XI TKJ 1"></option><option value="XII TKJ 1"></option>
                </datalist>
            </div>
            <div class="col-md-5">
                <label class="form-label">Jurusan</label>
                <input type="text" name="jurusan" class="form-control" list="daftarJurusan"
                       value="<?= e($edit['jurusan'] ?? '') ?>">
                <datalist id="daftarJurusan">
                    <option value="Rekayasa Perangkat Lunak"></option>
                    <option value="Teknik Komputer & Jaringan"></option>
                    <option value="Akuntansi dan Keuangan Lembaga"></option>
                    <option value="Bisnis Digital"></option>
                </datalist>
            </div>
            <div class="col-md-3">
                <label class="form-label">No. Telepon</label>
                <input type="text" name="no_telp" class="form-control" value="<?= e($edit['no_telp'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Alamat</label>
                <input type="text" name="alamat" class="form-control" value="<?= e($edit['alamat'] ?? '') ?>">
            </div>
            <div class="col-12">
                <hr class="mt-2 mb-2">
                <button type="submit" name="<?= $aksi ?>" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                <a href="siswa.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ===== TABEL SISWA ===== -->
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <form method="get" class="d-flex gap-2">
            <input type="text" name="cari" class="form-control" placeholder="Cari NIS / nama / kelas..."
                   value="<?= e($keyword) ?>">
            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
            <?php if ($keyword != ''): ?><a href="siswa.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a><?php endif; ?>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th width="45">No</th><th>NIS</th><th>Nama Siswa</th><th>Kelas</th>
                    <th>Jurusan</th><th>No. Telepon</th><th width="110" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($data) == 0): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Data tidak ditemukan</td></tr>
                <?php endif; ?>
                <?php $no = 1; while ($s = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><span class="badge text-bg-light border"><?= e($s['nis']) ?></span></td>
                    <td class="fw-semibold"><?= e($s['nama_siswa']) ?></td>
                    <td><?= e($s['kelas']) ?></td>
                    <td><?= e($s['jurusan']) ?></td>
                    <td><?= e($s['no_telp']) ?: '-' ?></td>
                    <td class="text-center">
                        <a href="siswa.php?aksi=edit&id=<?= $s['id_siswa'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <a href="siswa.php?hapus=<?= $s['id_siswa'] ?>" class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Yakin ingin menghapus siswa ini?')"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'template_footer.php'; ?>