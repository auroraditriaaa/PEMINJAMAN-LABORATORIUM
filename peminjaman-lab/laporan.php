<?php
include 'koneksi.php';
if (!isset($_SESSION['id_admin'])) { header('Location: login.php'); exit; }

 $menu  = 'laporan';
 $judul = 'Laporan Transaksi';

/* ============================================================
   FILTER PERIODE — dengan preset cepat (hari/bulan/tahun ini)
   ============================================================ */
 $preset = $_GET['preset'] ?? '';
 $today  = date('Y-m-d');

if ($preset == 'hari') {
    $tgl_awal = $tgl_akhir = $today;
} elseif ($preset == 'bulan') {
    $tgl_awal  = date('Y-m-01'); $tgl_akhir = $today;
} elseif ($preset == 'tahun') {
    $tgl_awal  = date('Y-01-01'); $tgl_akhir = $today;
} else {
    $tgl_awal  = !empty($_GET['tgl_awal'])  ? date('Y-m-d', strtotime($_GET['tgl_awal']))  : date('Y-m-01');
    $tgl_akhir = !empty($_GET['tgl_akhir']) ? date('Y-m-d', strtotime($_GET['tgl_akhir'])) : $today;
}

/* ---------- FILTER STATUS ---------- */
 $status = $_GET['status'] ?? '';
 $where  = "WHERE p.tgl_pinjam BETWEEN '$tgl_awal' AND '$tgl_akhir'";
if ($status == 'Dipinjam' || $status == 'Dikembalikan') {
    $where .= " AND p.status = '$status'";
} else {
    $status = ''; // "Semua" = hanya transaksi yang sudah diproses
    $where .= " AND p.status IN ('Dipinjam','Dikembalikan')";
}

/* ============================================================
   AMBIL DATA
   ============================================================ */
 $q = mysqli_query($koneksi, "
    SELECT p.*, s.nama_siswa, s.kelas, s.nis, a.nama_admin,
           g.tgl_kembali, g.denda, g.kondisi_barang,
           GREATEST(DATEDIFF(g.tgl_kembali, p.tgl_jatuh_tempo), 0) AS terlambat
    FROM peminjaman p
    JOIN siswa s             ON p.id_siswa = s.id_siswa
    LEFT JOIN admin a        ON p.id_admin = a.id_admin
    LEFT JOIN pengembalian g ON g.id_peminjaman = p.id_peminjaman
    $where
    ORDER BY p.tgl_pinjam ASC, p.id_peminjaman ASC");

/* ============================================================
   REKAP — dihitung dari baris yang SAMA dengan tabel,
   sehingga angka kartu statistik SELALU sinkron dengan tabel
   ============================================================ */
 $rows = [];
while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;

 $total_transaksi = count($rows);
 $total_dipinjam  = 0;
 $total_kembali   = 0;
 $total_terlambat = 0;
 $total_denda     = 0;
foreach ($rows as $r) {
    if ($r['status'] == 'Dipinjam')      $total_dipinjam++;
    if ($r['status'] == 'Dikembalikan')  $total_kembali++;
    if ((int)($r['terlambat'] ?? 0) > 0) $total_terlambat++;
    $total_denda += (int)($r['denda'] ?? 0);
}

/* Total unit barang keluar pada periode ini */
 $qUnit = mysqli_query($koneksi, "
    SELECT IFNULL(SUM(d.jumlah), 0) AS jml
    FROM detail d
    JOIN peminjaman p ON d.id_peminjaman = p.id_peminjaman
    $where");
 $total_unit = (int)mysqli_fetch_assoc($qUnit)['jml'];

include 'template_header.php';
?>

<style>
/* ================== KHUSUS CETAK ================== */
@media print {
    @page { size: A4 landscape; margin: 12mm 12mm 14mm; }   /* A4 tidur, cocok untuk 9 kolom */

    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

    body { font-size: 12px; }

    /* tabel tidak terpotong saat dicetak (fix umum bootstrap) */
    .table-responsive { overflow: visible !important; }

    .table-laporan { font-size: 11px; }
    .table-laporan th, .table-laporan td {
        border: 1px solid #444 !important;   /* bingkai penuh seperti laporan resmi */
        padding: 4px 6px !important;
    }
    .table-laporan thead { display: table-header-group; } /* header tabel berulang tiap halaman */
    .table-laporan tr { page-break-inside: avoid; }       /* baris tidak terpotong pindah halaman */
    .table-laporan tfoot td { border-top: 2px solid #000 !important; }
    .text-muted { color: #555 !important; }
}
</style>

<!-- ================== JUDUL (layar) ================== -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
    <div>
        <h4 class="mb-1">Laporan Transaksi Peminjaman</h4>
        <p class="text-muted mb-0 small">
            Menampilkan <strong><?= $total_transaksi ?></strong> transaksi
            periode <strong><?= tglIndo($tgl_awal) ?></strong> s.d. <strong><?= tglIndo($tgl_akhir) ?></strong>
            <?= $status != '' ? '— status: <strong>' . e($status) . '</strong>' : '' ?>
        </p>
    </div>
    <button type="button" onclick="window.print()" class="btn btn-dark">
        <i class="bi bi-printer"></i> Cetak Laporan
    </button>
</div>

<!-- ================== FILTER (layar) ================== -->
<div class="card shadow-sm mb-4 no-print">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Tanggal Mulai</label>
                <input type="date" name="tgl_awal" class="form-control" value="<?= $tgl_awal ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Tanggal Selesai</label>
                <input type="date" name="tgl_akhir" class="form-control" value="<?= $tgl_akhir ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    <option value="Dipinjam"     <?= $status == 'Dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
                    <option value="Dikembalikan" <?= $status == 'Dikembalikan' ? 'selected' : '' ?>>Dikembalikan</option>
                </select>
            </div>
            <div class="col-6 col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel"></i> Tampilkan
                </button>
            </div>
        </form>
        <div class="d-flex flex-wrap gap-2 mt-3 align-items-center">
            <span class="small text-muted"><i class="bi bi-lightning-charge"></i> Periode cepat:</span>
            <?php $qs = $status != '' ? '&status=' . $status : ''; ?>
            <a href="?preset=hari<?= $qs ?>"  class="btn btn-sm <?= $preset == 'hari'  ? 'btn-secondary' : 'btn-outline-secondary' ?>">Hari Ini</a>
            <a href="?preset=bulan<?= $qs ?>" class="btn btn-sm <?= $preset == 'bulan' ? 'btn-secondary' : 'btn-outline-secondary' ?>">Bulan Ini</a>
            <a href="?preset=tahun<?= $qs ?>" class="btn btn-sm <?= $preset == 'tahun' ? 'btn-secondary' : 'btn-outline-secondary' ?>">Tahun Ini</a>
        </div>
    </div>
</div>

<!-- ================== KARTU STATISTIK (layar) ================== -->
<div class="row g-3 mb-4 no-print">
    <div class="col-6 col-lg-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="ikon bg-primary bg-opacity-10 text-primary"><i class="bi bi-journal-text"></i></div>
                <div>
                    <div class="fs-4 fw-bold lh-1"><?= $total_transaksi ?></div>
                    <div class="text-muted small">Total Transaksi</div>
                    <div class="text-muted" style="font-size:.7rem;"><?= $total_unit ?> unit barang keluar</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="ikon bg-warning bg-opacity-10 text-warning"><i class="bi bi-arrow-right-circle"></i></div>
                <div>
                    <div class="fs-4 fw-bold lh-1"><?= $total_dipinjam ?></div>
                    <div class="text-muted small">Sedang Dipinjam</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="ikon bg-success bg-opacity-10 text-success"><i class="bi bi-check2-circle"></i></div>
                <div>
                    <div class="fs-4 fw-bold lh-1"><?= $total_kembali ?></div>
                    <div class="text-muted small">Dikembalikan</div>
                    <div class="text-danger" style="font-size:.7rem;"><?= $total_terlambat ?> terlambat</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="ikon bg-danger bg-opacity-10 text-danger"><i class="bi bi-cash-coin"></i></div>
                <div>
                    <div class="fs-5 fw-bold lh-1"><?= rupiah($total_denda) ?></div>
                    <div class="text-muted small">Total Denda</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================== KOP LAPORAN (hanya saat dicetak) ================== -->
<div class="d-none d-print-block mb-3">
    <div class="text-center" style="border-bottom:3px double #000; padding-bottom:10px;">
        <div style="font-size:15px; font-weight:700; text-transform:uppercase; letter-spacing:.5px;">
            Laporan Transaksi Peminjaman Barang Laboratorium
        </div>
        <div style="font-size:12px; font-weight:600;">SMK PGRI 2 PONOROGO</div>
        <div style="font-size:11px; margin-top:2px;">
            Periode: <?= tglIndo($tgl_awal) ?> s.d. <?= tglIndo($tgl_akhir) ?>
            <?= $status != '' ? ' &nbsp;|&nbsp; Status: ' . e($status) : '' ?>
        </div>
    </div>
</div>

<!-- ================== RINGKASAN (hanya saat dicetak) ================== -->
<div class="d-none d-print-block mb-3">
    <table class="table table-bordered mb-0" style="font-size:11px; width:100%;">
        <tr class="text-center fw-semibold">
            <th style="width:25%">Total Transaksi</th>
            <th style="width:25%">Sedang Dipinjam</th>
            <th style="width:25%">Dikembalikan (Terlambat)</th>
            <th style="width:25%">Total Denda</th>
        </tr>
        <tr class="text-center">
            <td><?= $total_transaksi ?> transaksi / <?= $total_unit ?> unit</td>
            <td><?= $total_dipinjam ?></td>
            <td><?= $total_kembali ?> (<?= $total_terlambat ?>)</td>
            <td><?= rupiah($total_denda) ?></td>
        </tr>
    </table>
</div>

<!-- ================== TABEL UTAMA ================== -->
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center no-print">
        <span class="fw-semibold"><i class="bi bi-table"></i> Detail Transaksi</span>
        <span class="badge text-bg-light border"><?= $total_transaksi ?> transaksi</span>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-hover table-laporan align-middle mb-0">
            <thead class="table-light">
                <tr class="text-center">
                    <th style="width:40px">No</th>
                    <th>Kode</th>
                    <th>Tgl Pinjam</th>
                    <th>Jatuh Tempo</th>
                    <th>Siswa</th>
                    <th>Barang Dipinjam</th>
                    <th>Tgl Kembali</th>
                    <th>Status</th>
                    <th class="text-end">Denda</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_transaksi == 0): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <i class="bi bi-inbox" style="font-size:2rem;"></i><br>
                            Tidak ada transaksi pada periode ini
                        </td>
                    </tr>
                <?php endif; ?>

                <?php $no = 1; foreach ($rows as $r): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td>
                        <span class="fw-semibold">PJ-<?= str_pad($r['id_peminjaman'], 4, '0', STR_PAD_LEFT) ?></span>
                        <br><small class="text-muted">oleh: <?= e($r['nama_admin'] ?: '—') ?></small>
                    </td>
                    <td><?= tglIndo($r['tgl_pinjam']) ?></td>
                    <td><?= tglIndo($r['tgl_jatuh_tempo']) ?></td>
                    <td>
                        <span class="fw-semibold"><?= e($r['nama_siswa']) ?></span>
                        <br><small class="text-muted"><?= e($r['kelas']) ?> &bull; NIS <?= e($r['nis']) ?></small>
                    </td>
                    <td class="small">
                        <?php
                        $qd = mysqli_query($koneksi, "
                            SELECT d.jumlah, b.nama_barang
                            FROM detail d JOIN barang b ON d.id_barang = b.id_barang
                            WHERE d.id_peminjaman = {$r['id_peminjaman']}");
                        $items = [];
                        while ($d = mysqli_fetch_assoc($qd)) {
                            $items[] = e($d['nama_barang']) . ' <span class="text-muted">&times;' . $d['jumlah'] . '</span>';
                        }
                        echo implode('<br>', $items);
                        ?>
                    </td>
                    <td>
                        <?php if ($r['tgl_kembali']): ?>
                            <?= tglIndo($r['tgl_kembali']) ?>
                            <?php if (($r['kondisi_barang'] ?? 'Baik') != 'Baik'): ?>
                                <br><small class="text-danger">kondisi: <?= e($r['kondisi_barang']) ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">&mdash;</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?= statusPeminjaman($r) ?>
                        <?php if ($r['status'] == 'Dikembalikan' && (int)$r['terlambat'] > 0): ?>
                            <br><small class="text-danger">terlambat <?= (int)$r['terlambat'] ?> hari</small>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <?php if ((int)($r['denda'] ?? 0) > 0): ?>
                            <span class="text-danger fw-semibold"><?= rupiah($r['denda']) ?></span>
                        <?php else: ?>
                            <span class="text-muted">&mdash;</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>

            <?php if ($total_transaksi > 0): ?>
            <tfoot>
                <tr class="table-light fw-bold">
                    <td colspan="8" class="text-end">TOTAL DENDA PERIODE INI</td>
                    <td class="text-end"><?= rupiah($total_denda) ?></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<!-- ================== TANDA TANGAN (hanya saat dicetak) ================== -->
<div class="d-none d-print-block" style="margin-top:28px;">
    <div class="d-flex justify-content-between align-items-end">
        <div style="font-size:10px; color:#555;">
            Dicetak pada: <?= tglIndo(date('Y-m-d')) ?> <?= date('H:i') ?> WIB<br>
            Oleh: <?= e($_SESSION['nama_admin']) ?> (<?= e($_SESSION['role'] ?? 'admin') ?>)
        </div>
        <div style="text-align:center; font-size:12px; width:280px;">
            Ponorogo, <?= tglIndo(date('Y-m-d')) ?><br>
            Mengetahui,<br>
            Admin / Petugas Laboratorium
            <div style="height:55px;"></div>
            <span style="font-weight:700; border-top:1px solid #000; padding-top:2px;">
                <?= e($_SESSION['nama_admin']) ?>
            </span>
        </div>
    </div>
</div>

<?php include 'template_footer.php'; ?>