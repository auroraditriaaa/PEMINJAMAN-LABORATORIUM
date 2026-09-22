<?php
include 'koneksi.php';

// Jika sudah login, arahkan sesuai role
if (isset($_SESSION['id_siswa'])) { header("Location: siswa_dashboard.php"); exit; }
if (isset($_SESSION['id_admin'])) { header("Location: dashboard.php"); exit; }

 $error = '';
 $role_terpilih = 'admin';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);
    $role     = in_array($_POST['role'] ?? '', ['admin', 'petugas', 'siswa']) ? $_POST['role'] : 'admin';
    $role_terpilih = $role;

    if ($role == 'siswa') {
        // ===== LOGIN SISWA (tabel siswa) =====
        $result = mysqli_query($koneksi, "SELECT * FROM siswa
            WHERE username = '$username' AND password = MD5('$password')");
        if (mysqli_num_rows($result) == 1) {
            $siswa = mysqli_fetch_assoc($result);
            unset($_SESSION['id_admin'], $_SESSION['nama_admin']); // cegah role campur
            $_SESSION['id_siswa']   = $siswa['id_siswa'];
            $_SESSION['nama_siswa'] = $siswa['nama_siswa'];
            $_SESSION['role']       = 'siswa';
            header("Location: siswa_dashboard.php");
            exit;
        }
        $error = 'Username (NIS) atau password siswa salah!';
    } else {
        // ===== LOGIN ADMIN / PETUGAS (tabel admin) =====
        $result = mysqli_query($koneksi, "SELECT * FROM admin
            WHERE username = '$username' AND password = MD5('$password') AND role = '$role'");
        if (mysqli_num_rows($result) == 1) {
            $admin = mysqli_fetch_assoc($result);
            unset($_SESSION['id_siswa'], $_SESSION['nama_siswa']); // cegah role campur
            $_SESSION['id_admin']   = $admin['id_admin'];
            $_SESSION['nama_admin'] = $admin['nama_admin'];
            $_SESSION['role']       = $admin['role'];
            header("Location: dashboard.php");
            exit;
        }
        $error = $role == 'admin'
            ? 'Username atau password salah, atau akun ini bukan akun Admin!'
            : 'Username atau password salah, atau akun ini bukan akun Petugas!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — PinjamLab</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    body { background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); min-height:100vh; display:flex; align-items:center; }
</style>
</head>
<body>
<div class="container py-5">
    <div class="card shadow-lg border-0 rounded-4 mx-auto" style="max-width:420px;">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="bg-primary bg-gradient text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                     style="width:64px;height:64px;">
                    <i class="bi bi-pc-display" style="font-size:2rem;"></i>
                </div>
                <h4 class="mt-3 mb-0">PinjamLab</h4>
                <p class="text-muted mb-0">Aplikasi Peminjaman Barang Laboratorium<br>SMK PGRI 2 Ponorogo</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger py-2"><i class="bi bi-x-circle-fill"></i> <?= $error ?></div>
            <?php endif; ?>

            <form method="post">
                <label class="form-label small text-muted mb-2">Masuk sebagai:</label>
                <div class="btn-group w-100 mb-3" role="group">
                    <input type="radio" class="btn-check" name="role" value="admin" id="roleAdmin" <?= $role_terpilih == 'admin' ? 'checked' : '' ?>>
                    <label class="btn btn-outline-primary" for="roleAdmin"><i class="bi bi-shield-lock"></i> Admin</label>

                    <input type="radio" class="btn-check" name="role" value="petugas" id="rolePetugas" <?= $role_terpilih == 'petugas' ? 'checked' : '' ?>>
                    <label class="btn btn-outline-primary" for="rolePetugas"><i class="bi bi-person-badge"></i> Petugas</label>

                    <input type="radio" class="btn-check" name="role" value="siswa" id="roleSiswa" <?= $role_terpilih == 'siswa' ? 'checked' : '' ?>>
                    <label class="btn btn-outline-primary" for="roleSiswa"><i class="bi bi-mortarboard"></i> Siswa</label>
                </div>

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" id="inputUsername" class="form-control" required autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <button type="submit" name="login" class="btn btn-primary w-100 py-2">
                    <i class="bi bi-box-arrow-in-right"></i> Masuk
                </button>
            </form>

            <div class="border rounded-3 p-3 mt-4 bg-light small">
                <div class="fw-semibold mb-1"><i class="bi bi-key"></i> Akun Demo</div>
                <div>Admin &nbsp;&nbsp;: <strong>admin / admin123</strong></div>
                <div>Petugas : <strong>petugas / petugas123</strong></div>
                <div>Siswa &nbsp;&nbsp;&nbsp;: <strong>22001 / siswa123</strong> (username = NIS)</div>
            </div>
        </div>
    </div>
</div>

<script>
// Jika memilih "Siswa", beri petunjuk NIS pada kolom username
document.querySelectorAll('input[name="role"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
        document.getElementById('inputUsername').placeholder =
            this.value === 'siswa' ? 'Masukkan NIS (cth: 22001)' : '';
    });
});
</script>
</body>
</html>