<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

include 'koneksi.php';

if (isset($_GET['id']) && isset($_GET['action'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'approve') {
        $status_baru = 'dikonfirmasi';
    } elseif ($action === 'reject') {
        $status_baru = 'ditolak';
    } else {
        header("Location: admin.php");
        exit();
    }

    $query = "UPDATE reservasi SET status='$status_baru' WHERE id='$id'";
    mysqli_query($conn, $query);
    
    header("Location: admin.php");
    exit();
} else {
    header("Location: admin.php");
    exit();
}
?>