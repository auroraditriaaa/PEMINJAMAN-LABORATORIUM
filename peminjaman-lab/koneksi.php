<?php
/* =====================================================
   KONEKSI DATABASE & FUNGSI BANTU
   ===================================================== */
session_start();

 $host     = "localhost";
 $user     = "root";
 $password = "";
 $database = "peminjaman_lab";

 $koneksi = mysqli_connect($host, $user, $password, $database);
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Mencegah XSS saat menampilkan data
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Format tanggal Indonesia
function tglIndo($tanggal) {
    $bulan = [1 => 'Januari','Februari','Maret','April','Mei','Juni','Juli',
              'Agustus','September','Oktober','November','Desember'];
    $t = strtotime($tanggal);
    return date('d', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y', $t);
}

// Format rupiah
function rupiah($angka) {
    return 'Rp ' . number_format((int)$angka, 0, ',', '.');
}

// Badge status peminjaman (Menunggu / Dipinjam / Terlambat / Dikembalikan / Ditolak)
function statusPeminjaman($row) {
    switch ($row['status']) {
        case 'Dikembalikan':
            return '<span class="badge bg-success">Dikembalikan</span>';
        case 'Menunggu':
            return '<span class="badge bg-primary">Menunggu Persetujuan</span>';
        case 'Ditolak':
            return '<span class="badge bg-secondary">Ditolak</span>';
    }
    if (strtotime(date('Y-m-d')) > strtotime($row['tgl_jatuh_tempo'])) {
        return '<span class="badge bg-danger">Terlambat</span>';
    }
    return '<span class="badge bg-warning text-dark">Dipinjam</span>';
}

/* =====================================================
   KONTROL AKSES BERDASARKAN ROLE
   - Admin & Petugas : login lewat tabel admin
                       (punya session id_admin)
   - Siswa           : login lewat tabel siswa
                       (punya session id_siswa)
   ===================================================== */
function isAdmin()   { return ($_SESSION['role'] ?? '') === 'admin'; }
function isPetugas() { return ($_SESSION['role'] ?? '') === 'petugas'; }
function isSiswa()   { return isset($_SESSION['id_siswa']); }

// Hanya ADMIN yang boleh tambah/ubah/hapus data master (barang & siswa).
// Petugas hanya boleh melihat + mengelola transaksi.
function bolehCRUDMaster() {
    return ($_SESSION['role'] ?? 'admin') === 'admin';
}
?>