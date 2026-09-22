<?php
include 'koneksi.php';
if (!isset($_SESSION['id_admin'])) { header('Location: login.php'); exit; }
/* Petugas hanya boleh MELIHAT data barang — blokir aksi di server */
if (($_SESSION['role'] ?? 'admin') == 'petugas'
    && (isset($_POST['tambah']) || isset($_POST['edit']) || isset($_GET['hapus']))) {
    header("Location: barang.php?gagal=akses");
    exit;
}
/* ---------- PROSES TAMBAH ---------- */
if (isset($_POST['tambah'])) {
    $kode    = mysqli_real_escape_string($koneksi, $_POST['kode_barang']);
    $nama    = mysqli_real_escape_string($koneksi, $_POST['nama_barang']);
    $jenis   = mysqli_real_escape_string($koneksi, $_POST['jenis_barang']);
    $jumlah  = (int)$_POST['jumlah'];
    $kondisi = mysqli_real_escape_string($koneksi, $_POST['kondisi']);
    $ket     = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

    if (mysqli_num_rows(mysqli_query($koneksi, "SELECT id_barang FROM barang WHERE kode_barang='$kode'")) > 0) {
        header("Location: barang.php?aksi=tambah&gagal=kode");
        exit;
    }
    mysqli_query($koneksi, "INSERT INTO barang (kode_barang, nama_barang, jenis_barang, jumlah, kondisi, keterangan)
        VALUES ('$kode', '$nama', '$jenis', '$jumlah', '$kondisi', '$ket')");
    header("Location: barang.php?sukses=tambah");
    exit;
}

/* ---------- PROSES EDIT ---------- */
if (isset($_POST['edit'])) {
    $id      = (int)$_POST['id_barang'];
    $kode    = mysqli_real_escape_string($koneksi, $_POST['kode_barang']);
    $nama    = mysqli_real_escape_string($koneksi, $_POST['nama_barang']);
    $jenis   = mysqli_real_escape_string($koneksi, $_POST['jenis_barang']);
    $jumlah  = (int)$_POST['jumlah'];
    $kondisi = mysqli_real_escape_string($koneksi, $_POST['kondisi']);
    $ket     = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

    if (mysqli_num_rows(mysqli_query($koneksi, "SELECT id_barang FROM barang WHERE kode_barang='$kode' AND id_barang != $id")) > 0) {
        header("Location: barang.php?aksi=edit&id=$id&gagal=kode");
        exit;
    }
    mysqli_query($koneksi, "UPDATE barang SET kode_barang='$kode', nama_barang='$nama', jenis_barang='$jenis',
        jumlah=$jumlah, kondisi='$kondisi', keterangan='$ket' WHERE id_barang=$id");
    header("Location: barang.php?sukses=edit");
    exit;
}

/* ---------- HAPUS ---------- */
if (isset($_GET['hapus'])) {
    $id     = (int)$_GET['hapus'];
    $dipakai = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM detail WHERE id_barang=$id"))['jml'];
    if ($dipakai > 0) {
        header("Location: barang.php?gagal=hapus");
        exit;
    }
    mysqli_query($koneksi, "DELETE FROM barang WHERE id_barang=$id");
    header("Location: barang.php?sukses=hapus");
    exit;
}

 $menu  = 'barang';
 $judul = 'Data Barang';
include 'template_header.php';

 $aksi = $_GET['aksi'] ?? 'list';
 $edit = null;
if ($aksi == 'edit') {
    $id   = (int)($_GET['id'] ?? 0);
    $edit = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM barang WHERE id_barang=$id"));
}

 $keyword = mysqli_real_escape_string($koneksi, $_GET['cari'] ?? '');
if ($keyword != '') {
    $data = mysqli_query($koneksi, "SELECT * FROM barang
        WHERE kode_barang LIKE '%$keyword%' OR nama_barang LIKE '%$keyword%' OR jenis_barang LIKE '%$keyword%'
        ORDER BY id_barang DESC");
} else {
    $data = mysqli_query($koneksi, "SELECT * FROM barang ORDER BY id_barang DESC");
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h4 class="mb-0">Data Barang</h4>
    <?php if ($aksi == 'list'): ?>
        <?php if (($_SESSION['role'] ?? 'admin') != 'petugas'): ?>
    <a href="barang.php?aksi=tambah" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Barang</a>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- ===== FORM TAMBAH / EDIT ===== -->
<?php if ($aksi == 'tambah' || ($aksi == 'edit' && $edit)): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-<?= $aksi == 'tambah' ? 'plus-circle' : 'pencil' ?>"></i>
        <?= $aksi == 'tambah' ? 'Tambah Barang Baru' : 'Edit Barang' ?>
    </div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <?php if ($aksi == 'edit'): ?>
                <input type="hidden" name="id_barang" value="<?= $edit['id_barang'] ?>">
            <?php endif; ?>
            <div class="col-md-3">
                <label class="form-label">Kode Barang <span class="text-danger">*</span></label>
                <input type="text" name="kode_barang" class="form-control" placeholder="cth: LP-001"
                       value="<?= e($edit['kode_barang'] ?? '') ?>" required>
            </div>
            <div class="col-md-5">
                <label class="form-label">Nama Barang <span class="text-danger">*</span></label>
                <input type="text" name="nama_barang" class="form-control" placeholder="cth: Laptop ASUS A416"
                       value="<?= e($edit['nama_barang'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Jenis Barang <span class="text-danger">*</span></label>
                <input type="text" name="jenis_barang" class="form-control" list="daftarJenis"
                       placeholder="cth: Laptop" value="<?= e($edit['jenis_barang'] ?? '') ?>" required>
                <datalist id="daftarJenis">
                    <option value="Laptop"></option><option value="Proyektor"></option>
                    <option value="Jaringan"></option><option value="Aksesoris"></option>
                    <option value="Kabel"></option><option value="Lainnya"></option>
                </datalist>
            </div>
            <div class="col-md-3">
                <label class="form-label">Jumlah / Stok <span class="text-danger">*</span></label>
                <input type="number" name="jumlah" class="form-control" min="0" value="<?= $edit['jumlah'] ?? 1 ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Kondisi</label>
                <select name="kondisi" class="form-select">
                    <?php foreach (['Baik', 'Rusak Ringan', 'Rusak Berat'] as $k): ?>
                        <option value="<?= $k ?>" <?= ($edit['kondisi'] ?? 'Baik') == $k ? 'selected' : '' ?>><?= $k ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Keterangan</label>
                <input type="text" name="keterangan" class="form-control" placeholder="Spesifikasi singkat (opsional)"
                       value="<?= e($edit['keterangan'] ?? '') ?>">
            </div>
            <div class="col-12">
                <hr class="mt-2 mb-2">
                <button type="submit" name="<?= $aksi ?>" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                <a href="barang.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ===== TABEL BARANG ===== -->
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <form method="get" class="d-flex gap-2">
            <input type="text" name="cari" class="form-control" placeholder="Cari kode / nama / jenis barang..."
                   value="<?= e($keyword) ?>">
            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
            <?php if ($keyword != ''): ?>
                <a href="barang.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th width="45">No</th><th>Kode</th><th>Nama Barang</th><th>Jenis</th>
                    <th>Stok</th><th>Kondisi</th><th width="110" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($data) == 0): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Data tidak ditemukan</td></tr>
                <?php endif; ?>
                <?php $no = 1; while ($b = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><span class="badge text-bg-light border"><?= e($b['kode_barang']) ?></span></td>
                    <td>
                        <?= e($b['nama_barang']) ?>
                        <?php if ($b['keterangan']): ?><br><small class="text-muted"><?= e($b['keterangan']) ?></small><?php endif; ?>
                    </td>
                    <td><?= e($b['jenis_barang']) ?></td>
                    <td>
                        <?php if ($b['jumlah'] > 0): ?>
                            <span class="badge bg-success"><?= $b['jumlah'] ?> unit</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Habis</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($b['kondisi']) ?></td>
                                        <td class="text-center">
                        <?php if (($_SESSION['role'] ?? 'admin') != 'petugas'): ?>
                        <a href="barang.php?aksi=edit&id=<?= $b['id_barang'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                        <a href="barang.php?hapus=<?= $b['id_barang'] ?>" class="btn btn-sm btn-outline-danger" title="Hapus"
                           onclick="return confirm('Yakin ingin menghapus barang ini?')"><i class="bi bi-trash"></i></a>
                        <?php else: ?>
                        <span class="text-muted small"><i class="bi bi-eye"></i> lihat</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'template_footer.php'; ?>