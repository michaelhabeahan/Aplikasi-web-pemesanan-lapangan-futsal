<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
include 'koneksi.php';

$tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
$jam_mulai = mysqli_real_escape_string($conn, $_POST['jam_mulai']);
$lapangan_id = mysqli_real_escape_string($conn, $_POST['lapangan_id']);
$user_id = $_SESSION['user_id'];

$cek_jadwal = mysqli_query($conn, "SELECT id FROM reservasi WHERE lapangan_id = '$lapangan_id' AND tanggal = '$tanggal' AND jam_mulai = '$jam_mulai' AND status != 'ditolak'");

if (mysqli_num_rows($cek_jadwal) > 0) {
    echo "<script>alert('SLOT WAKTU TELAH TERISI. SILAKAN PILIH JADWAL LAIN.'); window.location.href='index.php?tanggal=$tanggal';</script>";
} else {
    $insert = mysqli_query($conn, "INSERT INTO reservasi (user_id, lapangan_id, tanggal, jam_mulai, jam_selesai, status) VALUES ('$user_id', '$lapangan_id', '$tanggal', '$jam_mulai', ADDTIME('$jam_mulai', '01:00:00'), 'menunggu')");
    echo "<script>alert('RESERVASI BERHASIL DISERAHKAN KE WASIT (ADMIN) UNTUK KONFIRMASI.'); window.location.href='index.php?tanggal=$tanggal';</script>";
}
?>