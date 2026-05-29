<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
include 'koneksi.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'user';
$user_nama = $_SESSION['nama'];

$query_user = mysqli_query($conn, "SELECT email FROM users WHERE id = '$user_id'");
$user_data = mysqli_fetch_assoc($query_user);
$user_email = $user_data['email'] ?? 'user@futsalconnect.com';

date_default_timezone_set('Asia/Jakarta');
$tanggal_aktif = $_GET['tanggal'] ?? date('Y-m-d');
$time_now = date('H:i:s');
$is_today = ($tanggal_aktif == date('Y-m-d'));

$query_booked = mysqli_query($conn, "SELECT lapangan_id, jam_mulai FROM reservasi WHERE tanggal = '$tanggal_aktif' AND status != 'ditolak'");
$booked_slots = [];
while($row = mysqli_fetch_assoc($query_booked)){
    $time = date('H:i', strtotime($row['jam_mulai']));
    $booked_slots[$row['lapangan_id']][] = $time;
}

$daftar_lapangan = [
    1 => ['nama' => 'Lapangan A - Rumput Sintetis', 'harga' => 120000, 'img' => 'assets/lap1.png'],
    2 => ['nama' => 'Lapangan B - Vinyl Premium', 'harga' => 150000, 'img' => 'assets/lap1.png'],
    3 => ['nama' => 'Lapangan C - Interlocking', 'harga' => 130000, 'img' => 'assets/lap1.png'],
    4 => ['nama' => 'Lapangan D - Semen Plester', 'harga' => 80000, 'img' => 'assets/lap1.png'],
    5 => ['nama' => 'Lapangan E - Rumput Sintetis', 'harga' => 120000, 'img' => 'assets/lap1.png'],
    6 => ['nama' => 'Lapangan F - Vinyl Standar', 'harga' => 130000, 'img' => 'assets/lap1.png'],
    7 => ['nama' => 'Lapangan G - Parquet Kayu', 'harga' => 180000, 'img' => 'assets/lap1.png'],
    8 => ['nama' => 'Lapangan H - Taraflex', 'harga' => 160000, 'img' => 'assets/lap1.png'],
    9 => ['nama' => 'Lapangan I - Rumput Sintetis', 'harga' => 120000, 'img' => 'assets/lap1.png'],
    10=> ['nama' => 'Lapangan J - Vinyl Premium', 'harga' => 150000, 'img' => 'assets/lap1.png']
];

$jam_operasional = ['10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ARENA | FutsalConnect</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&family=Oswald:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <style>
        :root { 
            --fc-bg: #0a0a0a; 
            --fc-panel: #141414; 
            --fc-neon: #d4ff00; 
            --fc-red: #ff3333; 
            --fc-text: #f5f5f5; 
            --fc-muted: #888888; 
            --fc-border: #222222; 
            --fc-hover: rgba(212, 255, 0, 0.1);
            --fc-btn-text: #000;
        }

        [data-theme="light"] {
            --fc-bg: #f8fafc;
            --fc-panel: #ffffff;
            --fc-neon: #059669; 
            --fc-red: #dc2626;
            --fc-text: #0f172a;
            --fc-muted: #64748b;
            --fc-border: #cbd5e1;
            --fc-hover: rgba(5, 150, 105, 0.1);
            --fc-btn-text: #fff;
        }

        body { font-family: 'Montserrat', sans-serif; background-color: var(--fc-bg); color: var(--fc-text); transition: background-color 0.3s, color 0.3s; }
        
        .navbar { background-color: var(--fc-panel); border-bottom: 1px solid var(--fc-border); padding: 1rem 5%; transition: background-color 0.3s, border-color 0.3s; }
        .nav-brand { font-family: 'Oswald', sans-serif; font-weight: 700; font-size: 1.5rem; color: var(--fc-text); text-decoration: none; text-transform: uppercase; letter-spacing: -1px; }
        .nav-brand span { color: var(--fc-neon); }
        
        .btn-theme { background: transparent; border: none; color: var(--fc-text); font-size: 1.3rem; cursor: pointer; transition: 0.2s; padding: 5px; }
        .btn-theme:hover { color: var(--fc-neon); }
        .btn-hamburger { color: var(--fc-text); background: none; border: none; font-size: 1.8rem; line-height: 1; margin-left: 10px; cursor: pointer; transition: 0.2s; }
        .btn-hamburger:hover { color: var(--fc-neon); }

        .profile-dropdown .dropdown-menu { background-color: var(--fc-panel); border: 1px solid var(--fc-border); min-width: 260px; border-radius: 0; padding: 0; margin-top: 15px; }
        .profile-dropdown .dropdown-item { color: var(--fc-text); padding: 12px 20px; font-family: 'Oswald', sans-serif; text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem; transition: 0.2s; }
        .profile-dropdown .dropdown-item:hover { background-color: var(--fc-hover); color: var(--fc-neon); }
        .dropdown-divider { border-top-color: var(--fc-border); margin: 0; }

        .hero-banner { padding: 4rem 2rem 2rem; text-align: center; }
        .hero-title { font-family: 'Oswald', sans-serif; font-size: 3.5rem; text-transform: uppercase; font-weight: 700; line-height: 1; margin-bottom: 1rem; letter-spacing: -1px; }
        .hero-subtitle { color: var(--fc-muted); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 2px; }

        .filter-section { margin: 0 5% 3rem; display: flex; justify-content: center; }
        .date-picker-form { background: var(--fc-panel); padding: 10px 20px; border: 1px solid var(--fc-border); display: flex; align-items: center; gap: 15px; }
        .date-picker-form label { font-family: 'Oswald', sans-serif; color: var(--fc-neon); text-transform: uppercase; letter-spacing: 1px; margin: 0;}
        .date-picker-form input { background: var(--fc-bg); color: var(--fc-text); border: 1px solid var(--fc-border); padding: 8px 12px; font-family: 'Montserrat', sans-serif; font-weight: 600; outline: none; cursor: pointer; transition: 0.2s; }
        .date-picker-form input:focus { border-color: var(--fc-neon); }

        .card-lapangan { background: var(--fc-panel); border: 1px solid var(--fc-border); height: 100%; display: flex; flex-direction: column; transition: transform 0.2s, border-color 0.2s; position: relative; }
        .card-lapangan:hover { border-color: var(--fc-neon); transform: translateY(-5px); }
        .card-img-wrapper { height: 200px; background-color: var(--fc-border); border-bottom: 1px solid var(--fc-border); }
        .card-img-wrapper img { width: 100%; height: 100%; object-fit: cover; filter: grayscale(20%); transition: 0.3s; }
        .card-lapangan:hover .card-img-wrapper img { filter: grayscale(0%); }
        
        .card-body { padding: 1.5rem; flex-grow: 1; display: flex; flex-direction: column; }
        .lapangan-nama { font-family: 'Oswald', sans-serif; font-size: 1.4rem; text-transform: uppercase; margin-bottom: 0.2rem; }
        .lapangan-harga { color: var(--fc-neon); font-family: 'Oswald', sans-serif; font-size: 1.1rem; margin-bottom: 1.5rem; }
        .slot-title { font-family: 'Oswald', sans-serif; color: var(--fc-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 1rem; border-bottom: 1px solid var(--fc-border); padding-bottom: 0.5rem; }

        .slot-btn { font-family: 'Oswald', sans-serif; font-size: 0.9rem; padding: 8px 0; width: calc(33.33% - 6px); border-radius: 0; text-transform: uppercase; letter-spacing: 1px; transition: all 0.2s; }
        .btn-available { background: transparent; border: 1px solid var(--fc-border); color: var(--fc-text); }
        .btn-available:hover { background: var(--fc-neon); color: var(--fc-btn-text); border-color: var(--fc-neon); }
        .btn-booked { background: rgba(255, 51, 51, 0.1); border: 1px solid var(--fc-red); color: var(--fc-red); cursor: not-allowed; }
        .btn-past { background: var(--fc-bg); border: 1px solid var(--fc-border); color: var(--fc-muted); cursor: not-allowed; }
        
        .modal-content { background-color: var(--fc-panel); color: var(--fc-text); border: 1px solid var(--fc-neon); border-radius: 0; }
        .modal-header { border-bottom: 1px solid var(--fc-border); }
        .modal-title { font-family: 'Oswald', sans-serif; text-transform: uppercase; letter-spacing: 1px; color: var(--fc-neon); }
        .btn-confirm { background-color: var(--fc-neon); color: var(--fc-btn-text); font-family: 'Oswald', sans-serif; font-size: 1.1rem; font-weight: 700; border: none; padding: 1rem; width: 100%; text-transform: uppercase; letter-spacing: 1px; transition: 0.2s; }
        .btn-confirm:hover { filter: brightness(1.1); }

        .flatpickr-calendar { background: var(--fc-panel) !important; border: 1px solid var(--fc-border) !important; box-shadow: 0 4px 15px rgba(0,0,0,0.5) !important; font-family: 'Montserrat', sans-serif !important; border-radius: 0 !important; }
        .flatpickr-calendar, .flatpickr-days, .flatpickr-day { color: var(--fc-text) !important; }
        .flatpickr-day.selected { background: var(--fc-neon) !important; border-color: var(--fc-neon) !important; color: var(--fc-btn-text) !important; font-weight: bold; }
        .flatpickr-current-month, .flatpickr-month { color: var(--fc-text) !important; fill: var(--fc-text) !important; }
        .flatpickr-day:hover { background: var(--fc-hover) !important; border-color: var(--fc-neon) !important; color: var(--fc-neon) !important; }
        
        .flatpickr-monthDropdown-months { background: var(--fc-panel) !important; color: var(--fc-text) !important; border: none; outline: none; appearance: none; -webkit-appearance: none; font-weight: 600; cursor: pointer; }
        .flatpickr-monthDropdown-months .flatpickr-monthDropdown-month { background: var(--fc-panel) !important; color: var(--fc-text) !important; }
        span.flatpickr-weekday { color: var(--fc-muted) !important; }
        .flatpickr-current-month input.cur-year { color: var(--fc-text) !important; font-weight: 600; }
    </style>
</head>
<body>
    <script>
        if (localStorage.getItem('theme') === 'light') document.documentElement.setAttribute('data-theme', 'light');
    </script>
    <nav class="navbar d-flex justify-content-between align-items-center sticky-top">
        <a href="#" class="nav-brand">FUTSAL<span>CONNECT</span></a>
        
        <div class="d-flex align-items-center">
            <button id="themeToggle" class="btn-theme" title="Ubah Tema"><i id="themeIcon" class="bi bi-sun-fill"></i></button>
            <div class="dropdown profile-dropdown">
                <button class="btn-hamburger" type="button" data-bs-toggle="dropdown"><i class="bi bi-list"></i></button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li class="px-4 py-3" style="border-bottom: 1px solid var(--fc-border);">
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background: var(--fc-neon); color: var(--fc-btn-text); font-family: 'Oswald', sans-serif; font-size: 1.5rem;">
                                <?= strtoupper(substr($user_nama, 0, 1)); ?>
                            </div>
                            <div>
                                <div class="fw-bold d-flex align-items-center gap-2" style="font-family: 'Oswald', sans-serif; text-transform: uppercase; letter-spacing: 1px; color: var(--fc-text);">
                                    <?= htmlspecialchars($user_nama); ?>
                                    <?php if($user_role === 'admin'): ?>
                                        <span class="badge" style="background: var(--fc-red); font-size: 0.65rem; color: #fff;">ADMIN</span>
                                    <?php endif; ?>
                                </div>
                                <div style="color: var(--fc-muted); font-size: 0.8rem;"><?= htmlspecialchars($user_email); ?></div>
                            </div>
                        </div>
                    </li>
                    <?php if($user_role === 'admin'): ?>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2" href="admin.php" style="color: var(--fc-neon);">
                                <i class="bi bi-speedometer2"></i> DASBOR ADMIN
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                    <?php endif; ?>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="logout.php" style="color: var(--fc-red);">
                            <i class="bi bi-box-arrow-right"></i> AKHIRI SESI
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="hero-banner">
        <h1 class="hero-title">TENTUKAN ARENA<br>BERMAIN ANDA</h1>
        <p class="hero-subtitle">SINKRONISASI JADWAL WAKTU NYATA. PILIH SLOT KOSONG UNTUK KICK-OFF.</p>
    </div>

    <div class="filter-section">
        <form method="GET" class="date-picker-form">
            <label for="tanggal">JADWAL MATCH:</label>
            <input type="text" name="tanggal" id="tanggal" value="<?= $tanggal_aktif ?>" placeholder="Pilih Tanggal">
        </form>
    </div>

    <div class="container pb-5">
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach($daftar_lapangan as $id => $lapangan): ?>
                <div class="col">
                    <div class="card-lapangan">
                        <div class="card-img-wrapper">
                            <img src="<?= $lapangan['img'] ?>" alt="<?= $lapangan['nama'] ?>">
                        </div>
                        <div class="card-body">
                            <h5 class="lapangan-nama"><?= $lapangan['nama'] ?></h5>
                            <div class="lapangan-harga">RP <?= number_format($lapangan['harga'], 0, ',', '.') ?> <span style="color: var(--fc-muted); font-size: 0.8rem;">/ JAM</span></div>
                            
                            <div class="slot-title">SLOT KICK-OFF TERSISA</div>
                            
                            <div class="d-flex flex-wrap gap-2">
                                <?php 
                                foreach($jam_operasional as $jam): 
                                    $is_booked = isset($booked_slots[$id]) && in_array($jam, $booked_slots[$id]);
                                    $is_past = ($is_today && $jam.':00' < $time_now);
                                    
                                    if ($is_booked) {
                                        $btn_class = 'btn-booked';
                                        $disabled = 'disabled';
                                        $label = 'FULL';
                                    } elseif ($is_past) {
                                        $btn_class = 'btn-past';
                                        $disabled = 'disabled';
                                        $label = $jam;
                                    } else {
                                        $btn_class = 'btn-available';
                                        $disabled = '';
                                        $label = $jam;
                                    }
                                ?>
                                    <button type="button" class="btn <?= $btn_class ?> slot-btn" <?= $disabled ?> 
                                            data-bs-toggle="modal" data-bs-target="#modalBooking" 
                                            onclick="siapkanBooking(<?= $id ?>, '<?= $lapangan['nama'] ?>', '<?= $jam ?>', '<?= $tanggal_aktif ?>')">
                                        <?= $label ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="modal fade" id="modalBooking" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header border-0 pb-0 mt-2 mx-2">
                    <h5 class="modal-title">KONFIRMASI KICK-OFF</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 mb-4" style="background: var(--fc-hover); border-left: 4px solid var(--fc-neon);">
                        <div class="fw-bold mb-2" id="textNamaLapangan" style="font-family: 'Oswald', sans-serif; font-size: 1.2rem; color: var(--fc-text);"></div>
                        <div class="d-flex align-items-center gap-2 mb-1" style="color: var(--fc-muted); font-size: 0.9rem; font-family: 'Oswald', sans-serif; letter-spacing: 1px;">
                            <i class="bi bi-calendar-event"></i> <span id="textTanggal"></span>
                        </div>
                        <div class="d-flex align-items-center gap-2" style="color: var(--fc-neon); font-size: 1rem; font-family: 'Oswald', sans-serif; letter-spacing: 1px;">
                            <i class="bi bi-clock"></i> <span id="textJam"></span> WIB (1 JAM)
                        </div>
                    </div>
                    
                    <form action="proses_booking.php" method="POST">
                        <input type="hidden" name="lapangan_id" id="inputLapanganId">
                        <input type="hidden" name="tanggal" id="inputTanggal">
                        <input type="hidden" name="jam_mulai" id="inputJamMulai">
                        <button type="submit" class="btn-confirm">PROSES RESERVASI</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>
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

        flatpickr("#tanggal", {
            locale: "id",
            minDate: "today",
            altInput: true,
            altFormat: "d F Y",
            dateFormat: "Y-m-d",
            disableMobile: true,
            onChange: function(selectedDates, dateStr, instance) {
                instance.element.closest('form').submit();
            }
        });

        function siapkanBooking(lapanganId, namaLapangan, jam, tanggal) {
            document.getElementById('inputLapanganId').value = lapanganId;
            document.getElementById('inputTanggal').value = tanggal;
            document.getElementById('inputJamMulai').value = jam;
            document.getElementById('textNamaLapangan').textContent = namaLapangan;
            
            const dateObj = new Date(tanggal);
            const options = { day: 'numeric', month: 'long', year: 'numeric' };
            const formattedDate = dateObj.toLocaleDateString('id-ID', options);
            
            document.getElementById('textTanggal').textContent = formattedDate;
            document.getElementById('textJam').textContent = jam;
        }
    </script>
</body>
</html>