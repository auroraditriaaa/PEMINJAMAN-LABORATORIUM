<?php
/* =====================================================
   UPGRADE.PHP — Penambahan Fitur Multi-Role
   (Admin / Petugas Laboratorium / Siswa)
   Jalankan SEKALI di browser:
   http://localhost/peminjaman-lab/upgrade.php
   Jika database belum ada, jalankan install.php dulu.
   ===================================================== */
header('Content-Type: text/html; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    /* 1. Koneksi & pilih database */
    $koneksi = mysqli_connect('localhost', 'root', '');
    mysqli_select_db($koneksi, 'peminjaman_lab');

    /* Cek apakah sebuah kolom sudah ada (agar aman dijalankan ulang) */
    function kolomAda($koneksi, $tabel, $kolom) {
        $q = mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = 'peminjaman_lab' AND TABLE_NAME = '$tabel' AND COLUMN_NAME = '$kolom'");
        return mysqli_fetch_assoc($q)['jml'] > 0;
    }

    $langkah = [];

    /* 2. Tabel admin → tambah kolom role (admin / petugas) */
    if (!kolomAda($koneksi, 'admin', 'role')) {
        mysqli_query($koneksi, "ALTER TABLE admin
            ADD COLUMN role ENUM('admin','petugas') NOT NULL DEFAULT 'admin'");
        $langkah[] = 'Kolom <code>role</code> ditambahkan ke tabel <code>admin</code>';
    }

    /* 3. Tabel siswa → tambah kolom username & password */
    if (!kolomAda($koneksi, 'siswa', 'username')) {
        mysqli_query($koneksi, "ALTER TABLE siswa ADD COLUMN username VARCHAR(50) NULL");
        $langkah[] = 'Kolom <code>username</code> ditambahkan ke tabel <code>siswa</code>';
    }
    if (!kolomAda($koneksi, 'siswa', 'password')) {
        mysqli_query($koneksi, "ALTER TABLE siswa ADD COLUMN password VARCHAR(255) NULL");
        $langkah[] = 'Kolom <code>password</code> ditambahkan ke tabel <code>siswa</code>';
    }

    /* 4. Buatkan akun login semua siswa: username = NIS, password awal = siswa123 */
    mysqli_query($koneksi, "UPDATE siswa SET username = nis WHERE username IS NULL OR username = ''");
    mysqli_query($koneksi, "UPDATE siswa SET password = MD5('siswa123') WHERE password IS NULL OR password = ''");
    $langkah[] = 'Akun login semua siswa dibuat (username = NIS, password awal = siswa123)';

    /* 5. Tambah akun petugas demo */
    mysqli_query($koneksi, "INSERT IGNORE INTO admin (nama_admin, username, password, role)
        VALUES ('Petugas Laboratorium', 'petugas', MD5('petugas123'), 'petugas')");
    $langkah[] = 'Akun Petugas Laboratorium dibuat (petugas / petugas123)';

    /* ===== Halaman sukses ===== */
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Upgrade Multi-Role</title></head>';
    echo '<body style="font-family:Segoe UI,Arial,sans-serif;background:#f4f6fb;padding:40px 15px">';
    echo '<div style="background:#fff;max-width:560px;margin:auto;padding:35px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.1)">';
    echo '<div style="text-align:center"><div style="font-size:55px">✅</div>';
    echo '<h2 style="color:#198754;margin:10px 0">Upgrade Berhasil!</h2></div>';
    echo '<p>Sistem sekarang mendukung 3 role login. Perubahan yang dilakukan:</p><ul>';
    foreach ($langkah as $l) echo '<li>' . $l . '</li>';
    echo '</ul><p><strong>Akun demo:</strong></p>';
    echo '<table rules="all" style="border-collapse:collapse;width:100%;text-align:center;font-size:14px">';
    echo '<tr style="background:#eef2ff"><th>Role</th><th>Username</th><th>Password</th></tr>';
    echo '<tr><td>Admin</td><td>admin</td><td>admin123</td></tr>';
    echo '<tr><td>Petugas</td><td>petugas</td><td>petugas123</td></tr>';
    echo '<tr><td>Siswa</td><td>22001 (NIS)</td><td>siswa123</td></tr>';
    echo '</table><p style="text-align:center;margin-top:25px">';
    echo '<a href="login.php" style="background:#0d6efd;color:#fff;text-decoration:none;padding:10px 30px;border-radius:6px;font-weight:600">Coba Login →</a>';
    echo '</p></div></body></html>';

} catch (mysqli_sql_exception $e) {
    $pesan = $e->getMessage();
    if (strpos($pesan, 'Unknown database') !== false) {
        $pesan = 'Database <strong>peminjaman_lab</strong> belum ada. Jalankan <strong>install.php</strong> terlebih dahulu, lalu buka halaman ini lagi.';
    }
    echo '<h2 style="color:#dc3545;font-family:Segoe UI">❌ Upgrade Gagal</h2>';
    echo '<p><strong>Pesan error:</strong><br>' . htmlspecialchars($pesan) . '</p>';
}
?>