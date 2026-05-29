<?php
$host = "nothing"; 
$user = "nothing"; 
$pass = "nothing"; 
$db   = "nothing";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>