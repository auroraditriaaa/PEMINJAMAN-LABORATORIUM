<?php
// Halaman awal: arahkan ke login / dashboard
include 'koneksi.php';
if (isset($_SESSION['id_admin'])) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit;
?>