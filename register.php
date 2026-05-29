<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
require 'koneksi.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!$name) $errors[] = 'NAMA WAJIB DIISI.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'FORMAT EMAIL TIDAK VALID.';
    if (strlen($password) < 8) $errors[] = 'KATA SANDI MIN. 8 KARAKTER.';

    if (empty($errors)) {
        $cek = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
        if (mysqli_num_rows($cek) > 0) {
            $errors[] = 'EMAIL SUDAH TERDAFTAR.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (nama, email, password, role) VALUES ('$name', '$email', '$hashed', 'user')";
            if (mysqli_query($conn, $query)) {
                header('Location: login.php?status=success');
                exit();
            } else {
                $errors[] = 'GALAT SISTEM: ' . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REGISTER | FutsalConnect</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&family=Oswald:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --fc-bg: #0a0a0a; --fc-panel: #141414; --fc-neon: #d4ff00; --fc-text: #f5f5f5; --fc-muted: #888888; --fc-border: #222222; }
        [data-theme="light"] { --fc-bg: #f8fafc; --fc-panel: #ffffff; --fc-neon: #059669; --fc-text: #0f172a; --fc-muted: #64748b; --fc-border: #cbd5e1; }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Montserrat', sans-serif; background-color: var(--fc-bg); color: var(--fc-text); min-height: 100vh; display: flex; align-items: center; justify-content: center; transition: background-color 0.3s, color 0.3s; }
        
        .theme-toggle-fixed { position: fixed; top: 20px; right: 25px; background: transparent; border: none; color: var(--fc-text); font-size: 1.5rem; cursor: pointer; transition: 0.2s; z-index: 1000; }
        .theme-toggle-fixed:hover { color: var(--fc-neon); }

        .auth-container { width: 100%; max-width: 480px; padding: 2rem; }
        .auth-card { background: var(--fc-panel); border: 1px solid var(--fc-border); padding: 3rem 2.5rem; position: relative; transition: background-color 0.3s, border-color 0.3s; }
        .auth-card::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: var(--fc-neon); }
        .brand-title { font-family: 'Oswald', sans-serif; font-size: 2.5rem; font-weight: 700; text-transform: uppercase; line-height: 1.1; margin-bottom: 0.5rem; }
        .brand-subtitle { color: var(--fc-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; font-family: 'Oswald', sans-serif; font-size: 0.9rem; color: var(--fc-muted); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 1px; }
        .form-control { width: 100%; background: var(--fc-bg); border: 1px solid var(--fc-border); color: var(--fc-text); padding: 1rem; font-family: 'Montserrat', sans-serif; outline: none; transition: 0.2s; }
        .form-control:focus { border-color: var(--fc-neon); box-shadow: inset 4px 0 0 var(--fc-neon); }
        .btn-submit { width: 100%; background: var(--fc-neon); color: #000; border: none; padding: 1.2rem; font-family: 'Oswald', sans-serif; font-size: 1.2rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; transition: 0.2s; margin-top: 1rem; }
        .btn-submit:hover { filter: brightness(1.1); }
        .alert { padding: 1rem; margin-bottom: 1.5rem; font-family: 'Oswald', sans-serif; font-size: 0.9rem; text-transform: uppercase; background: rgba(239, 68, 68, 0.1); border-left: 3px solid #ef4444; color: #ef4444; }
        .auth-link { display: block; text-align: center; margin-top: 2rem; color: var(--fc-muted); text-decoration: none; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; transition: 0.2s; }
        .auth-link:hover { color: var(--fc-neon); }
    </style>
</head>
<body>
    <script>
        if (localStorage.getItem('theme') === 'light') document.documentElement.setAttribute('data-theme', 'light');
    </script>

    <button id="themeToggle" class="theme-toggle-fixed" title="Ubah Tema"><i id="themeIcon" class="bi bi-sun-fill"></i></button>

    <div class="auth-container">
        <div class="auth-card">
            <div class="brand-title">REKRUTMEN<br>PEMAIN BARU</div>
            <div class="brand-subtitle">BUAT AKUN FUTSALCONNECT</div>

            <?php if ($errors): ?>
                <div class="alert">
                    <?php foreach ($errors as $e) echo "<div>$e</div>"; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Nama Pemain</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Aktif</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Kode Akses (Kata Sandi)</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn-submit">DAFTAR SEKARANG</button>
            </form>
            <a href="login.php" class="auth-link">SUDAH PUNYA AKUN? MASUK</a>
        </div>
    </div>

    <script>
        const htmlEl = document.documentElement;
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');

        if (htmlEl.getAttribute('data-theme') === 'light') {
            themeIcon.className = 'bi bi-moon-fill';
        } else {
            themeIcon.className = 'bi bi-sun-fill';
        }

        themeToggle.addEventListener('click', (e) => {
            e.preventDefault();
            if (htmlEl.getAttribute('data-theme') === 'light') {
                htmlEl.removeAttribute('data-theme');
                localStorage.setItem('theme', 'dark');
                themeIcon.className = 'bi bi-sun-fill';
            } else {
                htmlEl.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light');
                themeIcon.className = 'bi bi-moon-fill';
            }
        });
    </script>
</body>
</html>