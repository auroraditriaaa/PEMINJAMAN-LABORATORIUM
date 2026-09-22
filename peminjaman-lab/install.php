<?php
/* =====================================================
   INSTALL.PHP v3 — + Fitur Pengajuan Peminjaman Siswa
   Jalankan di: http://localhost/peminjaman-lab/install.php
   Aman dijalankan berulang, data lama TIDAK terhapus.
   ===================================================== */
header('Content-Type: text/html; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function kolomAda($koneksi, $namaTabel, $namaKolom) {
    $q = mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = 'peminjaman_lab' AND TABLE_NAME = '$namaTabel' AND COLUMN_NAME = '$namaKolom'");
    return mysqli_fetch_assoc($q)['jml'] > 0;
}

try {
    /* ===== 1. Koneksi & buat database ===== */
    $koneksi = mysqli_connect('localhost', 'root', '');
    mysqli_query($koneksi, "CREATE DATABASE IF NOT EXISTS peminjaman_lab
        CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    mysqli_select_db($koneksi, 'peminjaman_lab');

    /* ===== 2. Buat tabel (definisi TERBARU) ===== */
    $tabel = [
        "CREATE TABLE IF NOT EXISTS admin (
            id_admin   INT AUTO_INCREMENT PRIMARY KEY,
            nama_admin VARCHAR(100) NOT NULL,
            username   VARCHAR(50) NOT NULL UNIQUE,
            password   VARCHAR(255) NOT NULL,
            role       ENUM('admin','petugas') NOT NULL DEFAULT 'admin'
        )",
        "CREATE TABLE IF NOT EXISTS siswa (
            id_siswa   INT AUTO_INCREMENT PRIMARY KEY,
            nis        VARCHAR(20) NOT NULL UNIQUE,
            nama_siswa VARCHAR(100) NOT NULL,
            kelas      VARCHAR(20) NOT NULL,
            jurusan    VARCHAR(50),
            no_telp    VARCHAR(15),
            alamat     TEXT,
            username   VARCHAR(50) NULL,
            password   VARCHAR(255) NULL
        )",
        "CREATE TABLE IF NOT EXISTS barang (
            id_barang    INT AUTO_INCREMENT PRIMARY KEY,
            kode_barang  VARCHAR(20) NOT NULL UNIQUE,
            nama_barang  VARCHAR(100) NOT NULL,
            jenis_barang VARCHAR(50) NOT NULL,
            jumlah       INT NOT NULL DEFAULT 0,
            kondisi      VARCHAR(50) DEFAULT 'Baik',
            keterangan   TEXT
        )",
        "CREATE TABLE IF NOT EXISTS peminjaman (
            id_peminjaman   INT AUTO_INCREMENT PRIMARY KEY,
            id_siswa        INT NOT NULL,
            id_admin        INT NULL,
            tgl_pinjam      DATE NOT NULL,
            tgl_jatuh_tempo DATE NOT NULL,
            status          ENUM('Menunggu','Dipinjam','Dikembalikan','Ditolak') NOT NULL DEFAULT 'Dipinjam',
            alasan_penolakan VARCHAR(255) NULL,
            CONSTRAINT fk_peminjaman_siswa FOREIGN KEY (id_siswa) REFERENCES siswa(id_siswa) ON DELETE CASCADE,
            CONSTRAINT fk_peminjaman_admin FOREIGN KEY (id_admin) REFERENCES admin(id_admin)
        )",
        "CREATE TABLE IF NOT EXISTS detail (
            id_detail     INT AUTO_INCREMENT PRIMARY KEY,
            id_peminjaman INT NOT NULL,
            id_barang     INT NOT NULL,
            jumlah        INT NOT NULL,
            CONSTRAINT fk_detail_pinjam  FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman) ON DELETE CASCADE,
            CONSTRAINT fk_detail_barang  FOREIGN KEY (id_barang) REFERENCES barang(id_barang)
        )",
        "CREATE TABLE IF NOT EXISTS pengembalian (
            id_pengembalian INT AUTO_INCREMENT PRIMARY KEY,
            id_peminjaman   INT NOT NULL UNIQUE,
            tgl_kembali     DATE NOT NULL,
            kondisi_barang  VARCHAR(50) DEFAULT 'Baik',
            denda           INT NOT NULL DEFAULT 0,
            CONSTRAINT fk_kembali_pinjam FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman) ON DELETE CASCADE
        )",
    ];
    foreach ($tabel as $sql) { mysqli_query($koneksi, $sql); }

    /* ===== 3. Perbaiki database LAMA agar mendukung pengajuan ===== */
    // a) status: tambah pilihan 'Menunggu' & 'Ditolak'
    mysqli_query($koneksi, "ALTER TABLE peminjaman
        MODIFY status ENUM('Menunggu','Dipinjam','Dikembalikan','Ditolak') NOT NULL DEFAULT 'Dipinjam'");

    // b) id_admin jadi boleh NULL (diisi petugas saat pengajuan disetujui).
    //    FK harus dilepas dulu agar kolom bisa diubah, lalu dipasang lagi.
    $fkLama = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT CONSTRAINT_NAME AS nama FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = 'peminjaman_lab' AND TABLE_NAME = 'peminjaman'
           AND COLUMN_NAME = 'id_admin' AND REFERENCED_TABLE_NAME = 'admin'"));
    if ($fkLama) {
        mysqli_query($koneksi, "ALTER TABLE peminjaman DROP FOREIGN KEY `{$fkLama['nama']}`");
    }
    mysqli_query($koneksi, "ALTER TABLE peminjaman MODIFY id_admin INT NULL");
    $fkAda = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT COUNT(*) AS jml FROM information_schema.TABLE_CONSTRAINTS
         WHERE TABLE_SCHEMA = 'peminjaman_lab' AND TABLE_NAME = 'peminjaman'
           AND CONSTRAINT_NAME = 'fk_peminjaman_admin'"))['jml'];
    if ($fkAda == 0) {
        mysqli_query($koneksi, "ALTER TABLE peminjaman ADD CONSTRAINT fk_peminjaman_admin
            FOREIGN KEY (id_admin) REFERENCES admin(id_admin)");
    }

    // c) kolom alasan penolakan
    if (!kolomAda($koneksi, 'peminjaman', 'alasan_penolakan')) {
        mysqli_query($koneksi, "ALTER TABLE peminjaman ADD COLUMN alasan_penolakan VARCHAR(255) NULL");
    }

    /* ===== 4. Data awal ===== */
    mysqli_query($koneksi, "INSERT IGNORE INTO admin (nama_admin, username, password, role) VALUES
        ('Admin Lab RPL',        'admin',   MD5('admin123'),   'admin'),
        ('Petugas Laboratorium', 'petugas', MD5('petugas123'), 'petugas')");

    $siswa = [
        ['22001', 'Ahmad Fauzi',    'XII RPL 1', 'Rekayasa Perangkat Lunak',   '081234567801', 'Jl. Merdeka No. 10, Ponorogo'],
        ['22002', 'Siti Nurhaliza', 'XII RPL 1', 'Rekayasa Perangkat Lunak',   '081234567802', 'Jl. Sudirman No. 25, Ponorogo'],
        ['22003', 'Budi Santoso',   'XII TKJ 1', 'Teknik Komputer & Jaringan', '081234567803', 'Jl. Diponegoro No. 7, Ponorogo'],
        ['22004', 'Dewi Lestari',   'XII TKJ 2', 'Teknik Komputer & Jaringan', '081234567804', 'Jl. Kartini No. 3, Ponorogo'],
        ['22005', 'Rizky Pratama',  'XI RPL 1',  'Rekayasa Perangkat Lunak',   '081234567805', 'Jl. Cendrawasih No. 12, Ponorogo'],
    ];
    foreach ($siswa as $s) {
        mysqli_query($koneksi, "INSERT IGNORE INTO siswa
            (nis, nama_siswa, kelas, jurusan, no_telp, alamat, username, password)
            VALUES ('$s[0]', '$s[1]', '$s[2]', '$s[3]', '$s[4]', '$s[5]', '$s[0]', MD5('siswa123'))");
    }

    $barang = [
        ['LP-001', 'Laptop ASUS A416',         'Laptop',    10, 'Baik', 'Core i3, RAM 8GB, SSD 256GB'],
        ['LP-002', 'Laptop Lenovo V14',        'Laptop',     8, 'Baik', 'Core i5, RAM 8GB, SSD 512GB'],
        ['PR-001', 'Proyektor Epson EB-X06',   'Proyektor',  3, 'Baik', 'XGA 3600 lumens'],
        ['RD-001', 'Router TP-Link Archer C6', 'Jaringan',   5, 'Baik', 'Dual band'],
        ['SW-001', 'Switch Cisco 2960',        'Jaringan',   2, 'Baik', '24 port'],
        ['KB-001', 'Keyboard Logitech K120',   'Aksesoris', 15, 'Baik', 'USB'],
        ['MS-001', 'Mouse Logitech B100',      'Aksesoris', 15, 'Baik', 'USB'],
        ['KD-001', 'Kabel UTP Cat6',           'Kabel',     20, 'Baik', 'Panjang 3 meter'],
    ];
    foreach ($barang as $b) {
        mysqli_query($koneksi, "INSERT IGNORE INTO barang
            (kode_barang, nama_barang, jenis_barang, jumlah, kondisi, keterangan)
            VALUES ('$b[0]', '$b[1]', '$b[2]', $b[3], '$b[4]', '$b[5]')");
    }

    // beri akun login ke siswa lama yang belum punya
    mysqli_query($koneksi, "UPDATE siswa SET username = nis WHERE username IS NULL OR username = ''");
    mysqli_query($koneksi, "UPDATE siswa SET password = MD5('siswa123') WHERE password IS NULL OR password = ''");

    /* ===== 5. Halaman sukses ===== */
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Instalasi Database</title></head>';
    echo '<body style="font-family:Segoe UI,Arial,sans-serif;text-align:center;padding:40px 15px;background:#f4f6fb">';
    echo '<div style="background:#fff;max-width:520px;margin:auto;padding:35px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.1)">';
    echo '<div style="font-size:55px">✅</div>';
    echo '<h2 style="color:#198754;margin:10px 0">Database Siap!</h2>';
    echo '<p>Fitur <strong>Pengajuan Peminjaman Online</strong> sudah aktif.<br>Siswa dapat mengajukan peminjaman, menunggu persetujuan petugas/admin.</p>';
    echo '<table rules="all" style="border-collapse:collapse;width:100%;text-align:center;font-size:14px;margin:15px 0">';
    echo '<tr style="background:#eef2ff"><th>Role</th><th>Username</th><th>Password</th></tr>';
    echo '<tr><td>Admin</td><td>admin</td><td>admin123</td></tr>';
    echo '<tr><td>Petugas</td><td>petugas</td><td>petugas123</td></tr>';
    echo '<tr><td>Siswa</td><td>22001 (NIS)</td><td>siswa123</td></tr>';
    echo '</table>';
    echo '<a href="login.php" style="display:inline-block;background:#0d6efd;color:#fff;text-decoration:none;padding:10px 30px;border-radius:6px;font-weight:600">Menuju Halaman Login →</a>';
    echo '</div></body></html>';

} catch (mysqli_sql_exception $e) {
    echo '<h2 style="color:#dc3545;font-family:Segoe UI">❌ Instalasi Gagal</h2>';
    echo '<p><strong>Pesan error:</strong><br>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>Pastikan <strong>Apache</strong> dan <strong>MySQL</strong> sudah RUNNING di XAMPP, lalu refresh halaman ini.</p>';
}
?>