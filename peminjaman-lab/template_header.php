<?php
/* =====================================================
   TEMPLATE HEADER — set $menu & $judul sebelum include
   Sidebar otomatis menyesuaikan role: admin/petugas/siswa
   ===================================================== */

if (isset($_SESSION['id_siswa'])) {
    $role     = 'siswa';
    $namaUser = $_SESSION['nama_siswa'];
} elseif (isset($_SESSION['id_admin'])) {
    $role     = $_SESSION['role'] ?? 'admin';
    $namaUser = $_SESSION['nama_admin'];
} else {
    header("Location: login.php");
    exit;
}

// Jumlah pengajuan menunggu (untuk badge di menu Petugas/Admin)
 $jmlMenunggu = 0;
if ($role != 'siswa') {
    $jmlMenunggu = (int)mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT COUNT(*) AS jml FROM peminjaman WHERE status = 'Menunggu'"))['jml'];
}

// Notifikasi dari URL
 $pesan_sukses = [
    'tambah'   => 'Data berhasil ditambahkan!',
    'edit'     => 'Data berhasil diubah!',
    'hapus'    => 'Data berhasil dihapus!',
    'kembali'  => 'Barang berhasil dikembalikan!',
    'password' => 'Password berhasil diubah!',
    'ajukan'   => 'Pengajuan berhasil dikirim! Menunggu persetujuan petugas.',
    'setuju'   => 'Pengajuan disetujui — barang resmi dipinjamkan & stok berkurang.',
    'tolak'    => 'Pengajuan telah ditolak.',
    'batal'    => 'Pengajuan berhasil dibatalkan.',
];
 $pesan_gagal = [
    'hapus'           => 'Data tidak dapat dihapus karena masih dipakai dalam transaksi!',
    'nis'             => 'NIS sudah terdaftar, gunakan NIS lain!',
    'kode'            => 'Kode barang sudah digunakan!',
    'akses'           => 'Akses ditolak! Hanya Admin yang boleh mengubah data master.',
    'stok'            => 'Stok barang tidak mencukupi! Pengajuan tidak dapat disetujui.',
    'password_lama'   => 'Password lama salah!',
    'password_pendek' => 'Password baru minimal 5 karakter!',
    'password_beda'   => 'Konfirmasi password tidak sama!',
];
 $alert = '';
if (isset($_GET['sukses']) && isset($pesan_sukses[$_GET['sukses']])) {
    $alert = '<div class="alert alert-success alert-dismissible fade show no-print">'
           . '<i class="bi bi-check-circle-fill"></i> ' . $pesan_sukses[$_GET['sukses']]
           . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
} elseif (isset($_GET['gagal']) && isset($pesan_gagal[$_GET['gagal']])) {
    $alert = '<div class="alert alert-danger alert-dismissible fade show no-print">'
           . '<i class="bi bi-x-circle-fill"></i> ' . $pesan_gagal[$_GET['gagal']]
           . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($judul) ? e($judul) . ' — ' : '' ?>PinjamLab</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    body { background-color: #f4f6fb; }
    .sidebar { background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%); }
    .sidebar .brand { color:#fff; font-weight:700; padding:1.1rem 1rem; border-bottom:1px solid rgba(255,255,255,.15); }
    .sidebar .nav-link { color: rgba(255,255,255,.75); margin:2px 10px; border-radius:.5rem; }
    .sidebar .nav-link:hover  { color:#fff; background:rgba(255,255,255,.1); }
    .sidebar .nav-link.active { color:#fff; background:rgba(255,255,255,.22); font-weight:600; }
    .sidebar .nav-link i { margin-right:.55rem; }
    @media (min-width:768px){ .sidebar{ width:260px; flex-shrink:0; position:sticky; top:0; height:100vh; overflow-y:auto; } }
    @media (max-width:767.98px){ .sidebar .nav{ flex-direction:row; flex-wrap:nowrap; overflow-x:auto; } .sidebar .nav-link{ white-space:nowrap; } }
    .stat-card { border:none; border-radius:.75rem; }
    .stat-card .ikon { width:48px; height:48px; border-radius:.75rem; font-size:1.35rem; display:inline-flex; align-items:center; justify-content:center; }
    @media print {
        .sidebar, .topbar, .no-print { display:none !important; }
        body { background:#fff; }
        .content { padding:0 !important; }
        .card { box-shadow:none !important; border:none; }
    }
</style>
</head>
<body>
<div class="d-md-flex">

    <!-- ================= SIDEBAR ================= -->
    <aside class="sidebar">
        <div class="brand"><i class="bi bi-pc-display"></i> PinjamLab</div>
        <nav class="nav flex-column py-2">
            <?php if ($role == 'siswa'): ?>
                <a class="nav-link <?php if (($menu ?? '') == 'dashboard_siswa') echo 'active'; ?>" href="siswa_dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a class="nav-link <?php if (($menu ?? '') == 'ajukan_siswa') echo 'active'; ?>" href="siswa_ajukan.php"><i class="bi bi-plus-circle"></i> Ajukan Peminjaman</a>
                <a class="nav-link <?php if (($menu ?? '') == 'riwayat_siswa') echo 'active'; ?>" href="siswa_riwayat.php"><i class="bi bi-clock-history"></i> Riwayat Peminjaman</a>
                <a class="nav-link <?php if (($menu ?? '') == 'profil_siswa') echo 'active'; ?>" href="siswa_profil.php"><i class="bi bi-person-circle"></i> Profil Saya</a>
            <?php else: ?>
                <a class="nav-link <?php if (($menu ?? '') == 'dashboard') echo 'active'; ?>" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a class="nav-link <?php if (($menu ?? '') == 'pengajuan') echo 'active'; ?>" href="pengajuan.php">
                    <i class="bi bi-hourglass-split"></i> Pengajuan
                    <?php if ($jmlMenunggu > 0): ?><span class="badge bg-warning text-dark ms-1"><?= $jmlMenunggu ?></span><?php endif; ?>
                </a>
                <a class="nav-link <?php if (($menu ?? '') == 'barang') echo 'active'; ?>" href="barang.php"><i class="bi bi-box-seam"></i> Data Barang<?= $role == 'petugas' ? ' <small class="opacity-75">(lihat)</small>' : '' ?></a>
                <a class="nav-link <?php if (($menu ?? '') == 'siswa') echo 'active'; ?>" href="siswa.php"><i class="bi bi-people"></i> Data Siswa<?= $role == 'petugas' ? ' <small class="opacity-75">(lihat)</small>' : '' ?></a>
                <a class="nav-link <?php if (($menu ?? '') == 'peminjaman') echo 'active'; ?>" href="peminjaman.php"><i class="bi bi-arrow-right-circle"></i> Peminjaman</a>
                <a class="nav-link <?php if (($menu ?? '') == 'pengembalian') echo 'active'; ?>" href="pengembalian.php"><i class="bi bi-arrow-left-circle"></i> Pengembalian</a>
                <a class="nav-link <?php if (($menu ?? '') == 'laporan') echo 'active'; ?>" href="laporan.php"><i class="bi bi-file-earmark-text"></i> Laporan</a>
            <?php endif; ?>
        </nav>
    </aside>

    <!-- ================= KONTEN ================= -->
    <div class="flex-grow-1 min-vw-0">
        <nav class="topbar navbar bg-white shadow-sm px-3 py-2 sticky-top no-print">
            <span class="navbar-brand fw-semibold small mb-0">Aplikasi Peminjaman Barang Laboratorium</span>
            <div class="ms-auto d-flex align-items-center gap-2">
                <span class="d-none d-sm-inline text-muted small">
                    <i class="bi bi-person-circle"></i> <?= e($namaUser) ?>
                </span>
                <?php $warnaRole = ['admin' => 'bg-primary', 'petugas' => 'bg-info text-dark', 'siswa' => 'bg-success']; ?>
                <span class="badge <?= $warnaRole[$role] ?>"><?= ucfirst($role) ?></span>
                <a href="logout.php" class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Yakin ingin keluar?')">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </nav>

        <div class="content p-3 p-md-4">
            <?= $alert ?>