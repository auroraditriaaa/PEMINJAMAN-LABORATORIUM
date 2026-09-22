<?php
include 'koneksi.php';
if (!isset($_SESSION['id_siswa'])) { header('Location: login.php'); exit; }

 $id_siswa = (int)$_SESSION['id_siswa'];

/* ---------- UBAH PASSWORD ---------- */
if (isset($_POST['ubah_password'])) {
    $lama  = mysqli_real_escape_string($koneksi, $_POST['password_lama']);
    $baru  = mysqli_real_escape_string($koneksi, $_POST['password_baru']);
    $ulang = mysqli_real_escape_string($koneksi, $_POST['password_ulang']);

    $cocok = mysqli_num_rows(mysqli_query($koneksi,
        "SELECT id_siswa FROM siswa WHERE id_siswa = $id_siswa AND password = MD5('$lama')"));

    if ($cocok != 1) {
        header("Location: siswa_profil.php?gagal=password_lama");
    } elseif (strlen($baru) < 5) {
        header("Location: siswa_profil.php?gagal=password_pendek");
    } elseif ($baru != $ulang) {
        header("Location: siswa_profil.php?gagal=password_beda");
    } else {
        mysqli_query($koneksi, "UPDATE siswa SET password = MD5('$baru') WHERE id_siswa = $id_siswa");
        header("Location: siswa_profil.php?sukses=password");
    }
    exit;
}

 $menu  = 'profil_siswa';
 $judul = 'Profil Siswa';
include 'template_header.php';

 $siswa = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_siswa = $id_siswa"));
?>

<h4 class="mb-3">Profil Saya</h4>

<div class="row g-3">
    <!-- Data diri -->
    <div class="col-lg-7">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-person-vcard"></i> Data Diri</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th width="140">NIS</th><td><?= e($siswa['nis']) ?></td></tr>
                    <tr><th>Nama</th><td><?= e($siswa['nama_siswa']) ?></td></tr>
                    <tr><th>Kelas</th><td><?= e($siswa['kelas']) ?></td></tr>
                    <tr><th>Jurusan</th><td><?= e($siswa['jurusan']) ?: '-' ?></td></tr>
                    <tr><th>No. Telepon</th><td><?= e($siswa['no_telp']) ?: '-' ?></td></tr>
                    <tr><th>Alamat</th><td><?= e($siswa['alamat']) ?: '-' ?></td></tr>
                    <tr><th>Username</th><td><?= e($siswa['username']) ?></td></tr>
                </table>
            </div>
            <div class="card-footer bg-white small text-muted">
                <i class="bi bi-info-circle"></i> Untuk mengubah data diri, hubungi Admin/Petugas laboratorium.
            </div>
        </div>
    </div>

    <!-- Ubah password -->
    <div class="col-lg-5">
        <div class="card shadow-sm h-100 border-warning">
            <div class="card-header bg-white fw-semibold text-warning"><i class="bi bi-key"></i> Ubah Password</div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Password Lama</label>
                        <input type="password" name="password_lama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Baru <small class="text-muted">(min. 5 karakter)</small></label>
                        <input type="password" name="password_baru" class="form-control" minlength="5" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ulangi Password Baru</label>
                        <input type="password" name="password_ulang" class="form-control" minlength="5" required>
                    </div>
                    <button type="submit" name="ubah_password" class="btn btn-warning w-100">
                        <i class="bi bi-check-lg"></i> Simpan Password Baru
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'template_footer.php'; ?>