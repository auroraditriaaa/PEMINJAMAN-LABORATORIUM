<?php
/* =====================================================
   FIX.PHP — Perbaiki struktur database
   Mengatasi error: "Column 'id_admin' cannot be null"
   Jalankan di: http://localhost/peminjaman-lab/fix.php
   Aman dijalankan berulang. Data TIDAK terhapus.
   ===================================================== */
header('Content-Type: text/html; charset=utf-8');
mysqli_report(MYSQLI_REPORT_OFF); // error ditangani manual, tidak di-throw

 $log = [];

 $koneksi = mysqli_connect('localhost', 'root', '', 'peminjaman_lab');
if (!$koneksi) {
    die('<h3 style="color:#dc3545;font-family:Segoe UI,sans-serif">❌ Koneksi gagal: '
        . mysqli_connect_error() . '<br>Pastikan Apache &amp; MySQL sudah RUNNING.</h3>');
}

/* Fungsi bantu: jalankan query & catat hasilnya */
function jalankan($koneksi, &$log, $suksesTxt, $gagalTxt, $sql) {
    if (mysqli_query($koneksi, $sql)) {
        $log[] = ['ok', $suksesTxt];
    } else {
        $log[] = ['warn', $gagalTxt . ' &rarr; ' . mysqli_error($koneksi)];
    }
}

function kolomAda($koneksi, $tabel, $kolom) {
    $q = mysqli_query($koneksi, "SELECT COUNT(*) AS jml FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = 'peminjaman_lab' AND TABLE_NAME = '$tabel' AND COLUMN_NAME = '$kolom'");
    return (int)mysqli_fetch_assoc($q)['jml'] > 0;
}

/* ========== 1. PERBAIKAN UTAMA: id_admin boleh NULL ========== */
 $q = mysqli_query($koneksi, "SELECT IS_NULLABLE FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA='peminjaman_lab' AND TABLE_NAME='peminjaman' AND COLUMN_NAME='id_admin'");
 $nullable = mysqli_fetch_assoc($q)['IS_NULLABLE'] ?? '';

if ($nullable !== 'YES') {
    // Kolom foreign key boleh diubah jadi NULL tanpa melepas relasi —
    // cukup satu perintah ALTER ini:
    jalankan($koneksi, $log,
        'Kolom <code>id_admin</code> sekarang <strong>boleh NULL</strong> (kunci perbaikan!)',
        'Gagal mengubah kolom id_admin',
        'ALTER TABLE peminjaman MODIFY id_admin INT NULL');
} else {
    $log[] = ['ok', 'Kolom <code>id_admin</code> sudah bisa NULL — tidak perlu diubah'];
}

/* ========== 2. status harus punya 4 nilai ========== */
 $q = mysqli_query($koneksi, "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA='peminjaman_lab' AND TABLE_NAME='peminjaman' AND COLUMN_NAME='status'");
 $tipe = mysqli_fetch_assoc($q)['COLUMN_TYPE'] ?? '';
if (strpos($tipe, 'Menunggu') === false || strpos($tipe, 'Ditolak') === false) {
    jalankan($koneksi, $log,
        'Kolom <code>status</code> kini mendukung: Menunggu / Dipinjam / Dikembalikan / Ditolak',
        'Gagal memperbarui kolom status',
        "ALTER TABLE peminjaman MODIFY status
         ENUM('Menunggu','Dipinjam','Dikembalikan','Ditolak') NOT NULL DEFAULT 'Dipinjam'");
} else {
    $log[] = ['ok', 'Kolom <code>status</code> sudah mendukung 4 nilai'];
}

/* ========== 3. kolom alasan_penolakan ========== */
if (!kolomAda($koneksi, 'peminjaman', 'alasan_penolakan')) {
    jalankan($koneksi, $log,
        'Kolom <code>alasan_penolakan</code> ditambahkan',
        'Gagal menambah kolom alasan_penolakan',
        'ALTER TABLE peminjaman ADD COLUMN alasan_penolakan VARCHAR(255) NULL');
} else {
    $log[] = ['ok', 'Kolom <code>alasan_penolakan</code> sudah ada'];
}

/* ========== 4. Cek penyokong fitur multi-role ========== */
if (!kolomAda($koneksi, 'admin', 'role')) {
    jalankan($koneksi, $log,
        'Kolom <code>role</code> di tabel admin ditambahkan',
        'Gagal menambah kolom role',
        "ALTER TABLE admin ADD COLUMN role ENUM('admin','petugas') NOT NULL DEFAULT 'admin'");
} else {
    $log[] = ['ok', 'Kolom <code>role</code> di tabel admin sudah ada'];
}

foreach ([['siswa', 'username'], ['siswa', 'password']] as $pasangan) {
    $t = $pasangan[0]; $k = $pasangan[1];
    if (!kolomAda($koneksi, $t, $k)) {
        jalankan($koneksi, $log,
            "Kolom <code>$k</code> di tabel $t ditambahkan",
            "Gagal menambah kolom $k di tabel $t",
            "ALTER TABLE $t ADD COLUMN $k VARCHAR(255) NULL");
    } else {
        $log[] = ['ok', "Kolom <code>$k</code> di tabel $t sudah ada"];
    }
}

// pastikan semua siswa punya akun login
mysqli_query($koneksi, "UPDATE siswa SET username = nis WHERE username IS NULL OR username = ''");
mysqli_query($koneksi, "UPDATE siswa SET password = MD5('siswa123') WHERE password IS NULL OR password = ''");

/* ========== VERIFIKASI AKHIR ========== */
 $q = mysqli_query($koneksi, "SELECT IS_NULLABLE FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA='peminjaman_lab' AND TABLE_NAME='peminjaman' AND COLUMN_NAME='id_admin'");
 $sukses = (mysqli_fetch_assoc($q)['IS_NULLABLE'] ?? '') === 'YES';

/* ========== TAMPILKAN HASIL ========== */
echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Perbaikan Database</title></head>';
echo '<body style="font-family:Segoe UI,Arial,sans-serif;background:#f4f6fb;padding:40px 15px">';
echo '<div style="background:#fff;max-width:620px;margin:auto;padding:35px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.1)">';
if ($sukses) {
    echo '<div style="text-align:center"><div style="font-size:55px">✅</div>';
    echo '<h2 style="color:#198754;margin:10px 0">Perbaikan Berhasil!</h2>';
    echo '<p style="color:#555">Struktur database kini mendukung fitur pengajuan peminjaman.</p></div><hr><ul style="line-height:1.9">';
} else {
    echo '<div style="text-align:center"><div style="font-size:55px">❌</div>';
    echo '<h2 style="color:#dc3545;margin:10px 0">Perbaikan Gagal</h2>';
    echo '<p style="color:#555">Periksa rincian di bawah — salin pesan errornya bila masih bermasalah.</p></div><hr><ul style="line-height:1.9">';
}
foreach ($log as $l) {
    echo '<li>' . ($l[0] == 'ok' ? '✅' : '⚠️') . ' ' . $l[1] . '</li>';
}
echo '</ul>';
if ($sukses) {
    echo '<p style="text-align:center;margin-top:25px">';
    echo '<a href="login.php" style="display:inline-block;background:#0d6efd;color:#fff;text-decoration:none;padding:10px 30px;border-radius:6px;font-weight:600">Menuju Login →</a></p>';
}
echo '</div></body></html>';
?>