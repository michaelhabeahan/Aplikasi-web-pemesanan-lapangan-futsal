<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');
$today = date('Y-m-d');

$harga_lapangan = [
    1 => 120000, 2 => 150000, 3 => 130000, 4 => 80000, 5 => 120000,
    6 => 130000, 7 => 180000, 8 => 160000, 9 => 120000, 10 => 150000
];

// Metrik 1: Pendapatan & Jumlah Dikonfirmasi
$q_reservasi_sukses = mysqli_query($conn, "SELECT lapangan_id FROM reservasi WHERE status = 'dikonfirmasi'");
$income = 0;
$dikonfirmasi_count = 0;
if ($q_reservasi_sukses) {
    $dikonfirmasi_count = mysqli_num_rows($q_reservasi_sukses);
    while($row = mysqli_fetch_assoc($q_reservasi_sukses)) $income += $harga_lapangan[$row['lapangan_id']] ?? 0;
}

// Metrik 2: Pending
$q_pending = mysqli_query($conn, "SELECT COUNT(id) as pending FROM reservasi WHERE status = 'menunggu'");
$pending = $q_pending ? (mysqli_fetch_assoc($q_pending)['pending'] ?? 0) : 0;

// Metrik 3: Pengguna Aktif Hari Ini
$q_active_users = mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) as active FROM reservasi WHERE tanggal = '$today'");
$active_users = $q_active_users ? (mysqli_fetch_assoc($q_active_users)['active'] ?? 0) : 0;

// Metrik 4: Total Reservasi Sistem
$q_total = mysqli_query($conn, "SELECT COUNT(id) as total_res FROM reservasi");
$total_reservasi = $q_total ? (mysqli_fetch_assoc($q_total)['total_res'] ?? 0) : 0;

// Data Grafik 1: Distribusi Sewa 7 Hari Kedepan
$chart_labels = [];
$chart_data = [];
$hari_indo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

// Membuat array default untuk 7 hari ke depan
$data_7hari = [];
for ($i = 0; $i < 7; $i++) {
    $tgl = date('Y-m-d', strtotime("+$i days"));
    $nama_hari = $hari_indo[date('w', strtotime($tgl))];
    $tgl_format = date('d/m', strtotime($tgl));
    
    $chart_labels[] = $nama_hari . ' (' . $tgl_format . ')';
    $data_7hari[$tgl] = 0; // Default 0
}

// Menarik data reservasi untuk 7 hari ke depan
$tgl_awal = date('Y-m-d');
$tgl_akhir = date('Y-m-d', strtotime("+6 days"));
$q_chart = mysqli_query($conn, "SELECT tanggal, COUNT(id) as total FROM reservasi WHERE tanggal BETWEEN '$tgl_awal' AND '$tgl_akhir' AND status != 'ditolak' GROUP BY tanggal");

if ($q_chart) {
    while($row = mysqli_fetch_assoc($q_chart)){
        if (isset($data_7hari[$row['tanggal']])) {
            $data_7hari[$row['tanggal']] = (int)$row['total'];
        }
    }
}

// Memindahkan nilai ke array chart_data
foreach ($data_7hari as $tgl => $total) {
    $chart_data[] = $total;
}

// Data Tabel Antrean
$query_table = "SELECT r.*, u.nama as nama_user, l.nama_lapangan 
          FROM reservasi r JOIN users u ON r.user_id = u.id JOIN lapangan l ON r.lapangan_id = l.id 
          ORDER BY r.id DESC";
$result_table = mysqli_query($conn, $query_table);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADMIN | FutsalConnect</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&family=Oswald:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { 
            --fc-bg: #0a0a0a; --fc-panel: #141414; --fc-neon: #d4ff00; --fc-red: #ff3333; 
            --fc-text: #f5f5f5; --fc-muted: #888888; --fc-border: #222222; --fc-card-bg: #1a1a1a;
            --fc-green: #00ff00;
        }

        [data-theme="light"] {
            --fc-bg: #f8fafc; --fc-panel: #ffffff; --fc-neon: #059669; --fc-red: #dc2626;
            --fc-text: #0f172a; --fc-muted: #64748b; --fc-border: #cbd5e1; --fc-card-bg: #ffffff;
            --fc-green: #059669;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Montserrat', sans-serif; background-color: var(--fc-bg); color: var(--fc-text); display: flex; min-height: 100vh; transition: 0.3s; }
        
        /* Sidebar */
        .sidebar { background: var(--fc-panel); border-right: 1px solid var(--fc-border); width: 260px; flex-shrink: 0; padding: 2rem 0; position: relative; z-index: 1000; transition: 0.3s; }
        .brand-logo { font-family: 'Oswald', sans-serif; font-size: 1.8rem; font-weight: 700; text-transform: uppercase; text-align: center; color: var(--fc-text); margin-bottom: 3rem; letter-spacing: -1px; }
        .brand-logo span { color: var(--fc-neon); }
        .nav-link { color: var(--fc-muted); text-decoration: none; padding: 1rem 2rem; display: block; font-family: 'Oswald', sans-serif; text-transform: uppercase; letter-spacing: 1px; transition: 0.2s; border-left: 4px solid transparent; }
        .nav-link:hover, .nav-link.active { background: rgba(212, 255, 0, 0.05); color: var(--fc-neon); border-left-color: var(--fc-neon); }

        /* Main Content */
        .main-content { flex-grow: 1; padding: 2rem 3rem; overflow-y: auto; width: calc(100% - 260px); }
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; }
        .page-title { font-family: 'Oswald', sans-serif; font-size: 1.8rem; text-transform: uppercase; letter-spacing: 1px; margin: 0; }
        .btn-theme { background: transparent; border: none; color: var(--fc-text); font-size: 1.5rem; cursor: pointer; transition: 0.2s; }
        .btn-theme:hover { color: var(--fc-neon); }

        /* Material Layout: Top Cards */
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem; }
        .stat-card { background: var(--fc-card-bg); border: 1px solid var(--fc-border); border-radius: 8px; padding: 1.5rem; display: flex; justify-content: space-between; align-items: flex-start; transition: 0.3s; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .stat-card:hover { border-color: var(--fc-neon); transform: translateY(-3px); }
        .stat-info { display: flex; flex-direction: column; }
        .stat-label { font-family: 'Oswald', sans-serif; color: var(--fc-muted); text-transform: uppercase; letter-spacing: 1px; font-size: 0.85rem; margin-bottom: 0.5rem; }
        .stat-value { font-size: 1.8rem; font-weight: 700; font-family: 'Oswald', sans-serif; color: var(--fc-text); line-height: 1; }
        .stat-icon { width: 48px; height: 48px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #000; }
        .icon-income { background: var(--fc-neon); }
        .icon-pending { background: #ffaa00; }
        .icon-users { background: #00eeff; }
        .icon-total { background: #b05bff; color: #fff;}

        /* Material Layout: Charts Row */
        .chart-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem; }
        .chart-card { background: var(--fc-card-bg); border: 1px solid var(--fc-border); border-radius: 8px; padding: 1.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .chart-header { font-family: 'Oswald', sans-serif; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 1.5rem; color: var(--fc-text); border-bottom: 1px solid var(--fc-border); padding-bottom: 0.8rem; }
        .chart-wrapper { position: relative; height: 280px; width: 100%; }

        /* Status Metrics Text Blocks */
        .status-metrics { display: flex; flex-direction: column; gap: 1.5rem; justify-content: center; height: 100%; min-height: 280px; }
        .metric-item { display: flex; justify-content: space-between; align-items: center; padding: 1.5rem 2rem; border-radius: 8px; border: 1px solid var(--fc-border); background: rgba(255,255,255,0.02); }
        .metric-item.status-confirmed { border-left: 6px solid var(--fc-green); }
        .metric-item.status-pending { border-left: 6px solid var(--fc-red); }
        .metric-count { font-family: 'Oswald', sans-serif; font-size: 3.5rem; font-weight: 700; line-height: 1; }
        .status-confirmed .metric-count { color: var(--fc-green); }
        .status-pending .metric-count { color: var(--fc-red); }
        .metric-label { font-family: 'Oswald', sans-serif; font-size: 1.1rem; text-transform: uppercase; color: var(--fc-text); letter-spacing: 1px; }

        /* Material Layout: Table Row */
        .table-card { background: var(--fc-card-bg); border: 1px solid var(--fc-border); border-radius: 8px; padding: 1.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; }
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .fc-table { width: 100%; border-collapse: collapse; min-width: 700px; }
        .fc-table th { text-align: left; padding: 1rem; border-bottom: 1px solid var(--fc-border); font-family: 'Oswald', sans-serif; text-transform: uppercase; color: var(--fc-muted); letter-spacing: 1px; }
        .fc-table td { padding: 1rem; border-bottom: 1px solid var(--fc-border); font-size: 0.9rem; color: var(--fc-text); vertical-align: middle; }
        
        .badge { padding: 0.4rem 0.8rem; font-family: 'Oswald', sans-serif; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; border-radius: 4px; }
        .bg-menunggu { background: rgba(255, 170, 0, 0.1); color: #ffaa00; border: 1px solid #ffaa00; }
        .bg-dikonfirmasi { background: rgba(212, 255, 0, 0.1); color: var(--fc-neon); border: 1px solid var(--fc-neon); }
        .bg-ditolak { background: rgba(255, 51, 51, 0.1); color: var(--fc-red); border: 1px solid var(--fc-red); }

        .action-group { display: flex; gap: 0.5rem; }
        .btn-action { padding: 0.4rem 0.8rem; border: none; border-radius: 4px; font-family: 'Oswald', sans-serif; text-transform: uppercase; font-size: 0.75rem; cursor: pointer; text-decoration: none; transition: 0.2s; }
        .btn-approve { background: var(--fc-neon); color: #000; }
        .btn-approve:hover { filter: brightness(1.2); }
        .btn-reject { background: transparent; border: 1px solid var(--fc-red); color: var(--fc-red); }
        .btn-reject:hover { background: rgba(255, 51, 51, 0.1); }
        .btn-disabled { background: var(--fc-border); color: var(--fc-muted); cursor: not-allowed; }

        /* Mobile Adjustments */
        .mobile-header { display: none; }
        .mobile-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 999; backdrop-filter: blur(2px); }

        @media (max-width: 992px) {
            .chart-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            body { flex-direction: column; }
            .mobile-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; background: var(--fc-panel); border-bottom: 1px solid var(--fc-border); position: sticky; top: 0; z-index: 998; }
            .mobile-header .brand-logo { margin-bottom: 0; font-size: 1.5rem; }
            .btn-hamburger { background: none; border: none; color: var(--fc-text); font-size: 1.8rem; cursor: pointer; }
            .sidebar { position: fixed; left: 0; top: 0; height: 100vh; transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { width: 100%; padding: 1.5rem; }
            .header-top .btn-theme { display: none; }
            .sidebar .btn-theme-mobile { display: block; border-bottom: 1px solid var(--fc-border); }
        }
        @media (min-width: 769px) { .sidebar .btn-theme-mobile { display: none; } }
    </style>
</head>
<body>

    <div class="mobile-header">
        <div class="brand-logo">FUTSAL<span>CONNECT</span></div>
        <div class="d-flex align-items-center gap-3">
            <button id="themeToggleMobileHeader" class="btn-theme"><i class="bi bi-sun-fill"></i></button>
            <button id="mobileMenuBtn" class="btn-hamburger"><i class="bi bi-list"></i></button>
        </div>
    </div>

    <div class="mobile-overlay" id="mobileOverlay"></div>

    <div class="sidebar" id="sidebar">
        <div class="d-flex justify-content-between align-items-center px-4 mb-4 d-md-none">
            <div class="brand-logo" style="margin: 0; font-size: 1.5rem;">FC<span>ADMIN</span></div>
            <button id="closeMenuBtn" style="background: none; border: none; color: var(--fc-text); font-size: 1.5rem; cursor: pointer;"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="brand-logo d-none d-md-block">FC<span>ADMIN</span></div>
        
        <a href="#" class="nav-link active"><i class="bi bi-grid-1x2-fill me-2"></i> Dasbor Utama</a>
        <a href="index.php" class="nav-link"><i class="bi bi-house-door-fill me-2"></i> Mode Arena</a>
        <a href="logout.php" class="nav-link" style="color: var(--fc-red); margin-top: 2rem;"><i class="bi bi-box-arrow-right me-2"></i> Akhiri Sesi</a>
    </div>

    <div class="main-content">
        <div class="header-top">
            <h1 class="page-title">Tinjauan Operasional</h1>
            <button id="themeToggle" class="btn-theme" title="Ubah Tema"><i id="themeIcon" class="bi bi-sun-fill"></i></button>
        </div>
        
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <span class="stat-label">Pendapatan Dikonfirmasi</span>
                    <span class="stat-value">Rp <?= number_format($income, 0, ',', '.') ?></span>
                </div>
                <div class="stat-icon icon-income"><i class="bi bi-wallet2"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <span class="stat-label">Total Pesanan Sistem</span>
                    <span class="stat-value"><?= $total_reservasi ?></span>
                </div>
                <div class="stat-icon icon-total"><i class="bi bi-journal-check"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <span class="stat-label">Menunggu Persetujuan</span>
                    <span class="stat-value"><?= $pending ?></span>
                </div>
                <div class="stat-icon icon-pending"><i class="bi bi-hourglass-split"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <span class="stat-label">Pemain Aktif (Harian)</span>
                    <span class="stat-value"><?= $active_users ?></span>
                </div>
                <div class="stat-icon icon-users"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>

        <div class="chart-grid">
            <div class="chart-card">
                <div class="chart-header">Distribusi Sewa (7 Hari Ke Depan)</div>
                <div class="chart-wrapper">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-header">Rasio Status Keputusan</div>
                <div class="status-metrics">
                    <div class="metric-item status-confirmed">
                        <span class="metric-count"><?= $dikonfirmasi_count ?></span>
                        <span class="metric-label">Telah Dikonfirmasi</span>
                    </div>
                    <div class="metric-item status-pending">
                        <span class="metric-count"><?= $pending ?></span>
                        <span class="metric-label">Masih Menunggu</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="chart-header" style="margin-bottom: 1rem; border: none;">Antrean Keputusan Wasit</div>
            <div class="table-responsive">
                <table class="fc-table">
                    <thead>
                        <tr>
                            <th>Pemain</th>
                            <th>Jadwal Kick-Off</th>
                            <th>Fasilitas Arena</th>
                            <th>Status</th>
                            <th>Keputusan Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result_table && mysqli_num_rows($result_table) > 0): 
                            $bulan_indo = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
                            while($row = mysqli_fetch_assoc($result_table)): 
                            $time_format = date('H:i', strtotime($row['jam_mulai']));
                            $tanggal_pisah = explode('-', $row['tanggal']);
                            $date_format = $tanggal_pisah[2] . ' ' . $bulan_indo[(int)$tanggal_pisah[1]] . ' ' . $tanggal_pisah[0];
                        ?>
                        <tr>
                            <td style="font-weight: 600; font-family: 'Oswald', sans-serif; letter-spacing: 0.5px;"><?= htmlspecialchars($row['nama_user']) ?></td>
                            <td>
                                <div style="color: var(--fc-muted); font-size: 0.75rem; text-transform: uppercase;"><?= $date_format ?></div>
                                <div style="font-family: 'Oswald', sans-serif; font-size: 1.1rem; color: var(--fc-neon);"><?= $time_format ?> WIB</div>
                            </td>
                            <td><?= htmlspecialchars($row['nama_lapangan']) ?></td>
                            <td>
                                <?php if($row['status'] == 'menunggu'): ?>
                                    <span class="badge bg-menunggu">PENDING</span>
                                <?php elseif($row['status'] == 'dikonfirmasi'): ?>
                                    <span class="badge bg-dikonfirmasi">APPROVED</span>
                                <?php else: ?>
                                    <span class="badge bg-ditolak">REJECTED</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($row['status'] == 'menunggu'): ?>
                                    <div class="action-group">
                                        <a href="update_status.php?id=<?= $row['id'] ?>&action=approve" class="btn-action btn-approve"><i class="bi bi-check-lg"></i> Setuju</a>
                                        <a href="update_status.php?id=<?= $row['id'] ?>&action=reject" class="btn-action btn-reject"><i class="bi bi-x-lg"></i> Tolak</a>
                                    </div>
                                <?php else: ?>
                                    <span class="btn-action btn-disabled"><i class="bi bi-lock-fill"></i> Terkunci</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 2rem; color: var(--fc-muted);">Tidak ada riwayat pemesanan saat ini.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Interaktivitas Sidebar Seluler
        const sidebar = document.getElementById('sidebar');
        const mobileOverlay = document.getElementById('mobileOverlay');
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const closeMenuBtn = document.getElementById('closeMenuBtn');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            mobileOverlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
        }
        if(mobileMenuBtn) mobileMenuBtn.addEventListener('click', toggleSidebar);
        if(closeMenuBtn) closeMenuBtn.addEventListener('click', toggleSidebar);
        if(mobileOverlay) mobileOverlay.addEventListener('click', toggleSidebar);

        // Manajemen Tema
        const htmlEl = document.documentElement;
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const themeToggleMobileHeader = document.getElementById('themeToggleMobileHeader');

        function applyTheme(theme) {
            if (theme === 'light') {
                htmlEl.setAttribute('data-theme', 'light');
                if(themeIcon) themeIcon.className = 'bi bi-moon-fill';
                if(themeToggleMobileHeader) themeToggleMobileHeader.innerHTML = '<i class="bi bi-moon-fill"></i>';
            } else {
                htmlEl.removeAttribute('data-theme');
                if(themeIcon) themeIcon.className = 'bi bi-sun-fill';
                if(themeToggleMobileHeader) themeToggleMobileHeader.innerHTML = '<i class="bi bi-sun-fill"></i>';
            }
            updateChartColors();
        }

        if (localStorage.getItem('theme') === 'light') applyTheme('light');

        function toggleTheme(e) {
            e.preventDefault();
            const currentTheme = htmlEl.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            localStorage.setItem('theme', newTheme);
            applyTheme(newTheme);
        }

        if(themeToggle) themeToggle.addEventListener('click', toggleTheme);
        if(themeToggleMobileHeader) themeToggleMobileHeader.addEventListener('click', toggleTheme);

        // Render Grafik Bar 7 Hari Kedepan
        let barChartInstance;

        function renderCharts() {
            const textColor = getComputedStyle(document.body).getPropertyValue('--fc-text').trim() || '#f5f5f5';
            const gridColor = getComputedStyle(document.body).getPropertyValue('--fc-border').trim() || '#222';

            const ctxBar = document.getElementById('barChart').getContext('2d');
            barChartInstance = new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($chart_labels) ?>,
                    datasets: [{
                        label: 'Pemesanan',
                        data: <?= json_encode($chart_data) ?>,
                        backgroundColor: '#d4ff00',
                        borderRadius: 4,
                        maxBarThickness: 40 // Agar balok tidak terlalu gemuk
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            ticks: { color: textColor, precision: 0 }, 
                            grid: { color: gridColor } 
                        },
                        x: { 
                            ticks: { color: textColor, font: { family: 'Montserrat', size: 10 } }, 
                            grid: { display: false } 
                        }
                    }
                }
            });
        }

        function updateChartColors() {
            if(!barChartInstance) return;
            const textColor = getComputedStyle(document.body).getPropertyValue('--fc-text').trim() || '#f5f5f5';
            const gridColor = getComputedStyle(document.body).getPropertyValue('--fc-border').trim() || '#222';
            
            barChartInstance.options.scales.y.ticks.color = textColor;
            barChartInstance.options.scales.x.ticks.color = textColor;
            barChartInstance.options.scales.y.grid.color = gridColor;
            barChartInstance.update();
        }

        document.addEventListener('DOMContentLoaded', renderCharts);
    </script>
</body>
</html>