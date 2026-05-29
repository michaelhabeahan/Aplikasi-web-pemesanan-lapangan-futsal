<?php
session_start();
// Menghapus semua variabel sesi
session_unset();
// Menghancurkan sesi
session_destroy();
// Mengalihkan pengguna kembali ke halaman autentikasi
header("Location: login.php");
exit();
?>