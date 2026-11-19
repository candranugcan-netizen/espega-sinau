<?php
/**
 * Shared Teacher Sidebar & Layout Header
 * Espega Sinau - Professional Teacher Panel
 */
if (!isset($active_nav)) $active_nav = 'dashboard';
if (!isset($page_title)) $page_title = 'Panel Guru';

// Pastikan data sesi guru & mapel tersedia secara aman
$guru_id = $_SESSION['user_id'] ?? 0;
$nama_guru = $_SESSION['nama'] ?? 'Guru Pengampu';

if (!isset($daftar_mapel) && isset($pdo) && $guru_id) {
    try {
        $stmt_mapel_side = $pdo->prepare("SELECT * FROM subjects WHERE guru_id = ? ORDER BY nama_mapel ASC");
        $stmt_mapel_side->execute([$guru_id]);
        $daftar_mapel = $stmt_mapel_side->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $daftar_mapel = [];
    }
}
if (!isset($daftar_mapel)) {
    $daftar_mapel = [];
}

if (!isset($daftar_side_banks) && isset($pdo) && $guru_id) {
    try {
        $stmt_bank_side = $pdo->prepare("SELECT id, nama_bank FROM bank_soal WHERE guru_id = ? ORDER BY id DESC");
        $stmt_bank_side->execute([$guru_id]);
        $daftar_side_banks = $stmt_bank_side->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $daftar_side_banks = [];
    }
}
$daftar_side_banks = $daftar_side_banks ?? [];
$active_bank_id = $_SESSION['active_bank_soal_id'] ?? ($daftar_side_banks[0]['id'] ?? 0);

if (!isset($active_id) || empty($active_id)) {
    $active_id = $_SESSION['active_subject_id'] ?? ($daftar_mapel[0]['id'] ?? 0);
}
if (!isset($nama_mapel_aktif) || empty($nama_mapel_aktif)) {
    $nama_mapel_aktif = $_SESSION['active_subject_name'] ?? ($daftar_mapel[0]['nama_mapel'] ?? 'Belum Ada Mapel');
}
if (!isset($kelas_aktif) || empty($kelas_aktif)) {
    $kelas_aktif = $_SESSION['active_subject_kelas'] ?? ($daftar_mapel[0]['kelas'] ?? '-');
}
?>
<!-- Sidebar Navigation -->
<aside class="admin-sidebar" id="adminSidebar">
    <!-- Brand Header -->
    <a href="dashboard.php" class="sidebar-brand">
        <div class="brand-icon">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <div class="brand-info">
            <span class="brand-title">ESPEGA SINAU</span>
            <span class="brand-badge">PANEL GURU</span>
        </div>
    </a>

    <!-- Active Bank Soal Switcher Box -->
    <div class="sidebar-subject-box">
        <div class="subject-box-label">
            <i class="fa-solid fa-layer-group"></i>
            <span>Bank Soal Aktif</span>
        </div>
        <form action="../../controllers/GuruController.php" method="GET">
            <select name="pilih_bank_soal" onchange="this.form.submit()" class="sidebar-subject-select" title="Ganti Bank Soal Aktif">
                <?php if(empty($daftar_side_banks)): ?>
                    <option value="">- Belum ada Bank Soal -</option>
                <?php else: ?>
                    <?php foreach($daftar_side_banks as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ($b['id'] == $active_bank_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['nama_bank']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </form>
    </div>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <div class="nav-section-title">Navigasi Utama</div>
        <a href="dashboard.php" class="nav-item <?= ($active_nav === 'dashboard') ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge-high"></i>
            <span>Dashboard</span>
        </a>

        <div class="nav-section-title">Manajemen Bank Soal</div>
        <a href="bank_soal.php" class="nav-item <?= ($active_nav === 'bank_soal') ? 'active' : '' ?>">
            <i class="fa-solid fa-layer-group"></i>
            <span>Daftar Bank Soal</span>
        </a>
        <a href="kelola_soal.php" class="nav-item <?= ($active_nav === 'kelola_soal') ? 'active' : '' ?>">
            <i class="fa-solid fa-list-check"></i>
            <span>Butir Soal & Media</span>
        </a>
        <a href="input_soal.php" class="nav-item <?= ($active_nav === 'input_soal') ? 'active' : '' ?>">
            <i class="fa-solid fa-pen-to-square"></i>
            <span>Input Soal Manual</span>
        </a>
        <a href="import_soal.php" class="nav-item <?= ($active_nav === 'import_soal') ? 'active' : '' ?>">
            <i class="fa-solid fa-file-excel"></i>
            <span>Import Excel</span>
        </a>

        <div class="nav-section-title">Hasil Evaluasi</div>
        <a href="nilai.php" class="nav-item <?= ($active_nav === 'nilai') ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-simple"></i>
            <span>Rekap Nilai Siswa</span>
        </a>
    </nav>

    <!-- Sidebar Footer (Teacher Profile & Logout) -->
    <div class="sidebar-footer">
        <div class="user-box">
            <div class="user-avatar">
                <i class="fa-solid fa-chalkboard-user"></i>
            </div>
            <div class="user-meta-info">
                <div class="user-name" title="<?= htmlspecialchars($nama_guru) ?>"><?= htmlspecialchars($nama_guru) ?></div>
                <div class="user-role">Guru Pengampu</div>
            </div>
        </div>
        <a href="../../controllers/AuthController.php?logout=1" class="btn-sidebar-logout" title="Keluar dari Panel">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
        </a>
    </div>
</aside>

<!-- Backdrop Overlay for Mobile -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- Teacher Main Content Wrapper -->
<div class="admin-main-wrap">
    <!-- Topbar Header -->
    <header class="admin-topbar">
        <div class="topbar-left">
            <button type="button" class="mobile-toggle-btn" id="sidebarToggle" aria-label="Toggle Sidebar">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="topbar-breadcrumbs">
                <a href="dashboard.php"><i class="fa-solid fa-house" style="font-size: 0.8rem;"></i></a>
                <span class="divider">/</span>
                <span class="current"><?= htmlspecialchars($page_title) ?></span>
            </div>
        </div>
        <div class="topbar-right">
            <div class="active-mapel-pill">
                <i class="fa-solid fa-book-bookmark"></i>
                <span><?= htmlspecialchars($nama_mapel_aktif) ?> (Kelas <?= htmlspecialchars($kelas_aktif) ?>)</span>
            </div>
            <div class="system-status-pill">
                <span class="status-dot"></span>
                <span>Sistem Siap</span>
            </div>
        </div>
    </header>

    <!-- Content Area Start -->
    <main class="admin-content-area">

<script>
// Sidebar Mobile Toggle Handler
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('adminSidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const backdrop = document.getElementById('sidebarBackdrop');

    if (toggleBtn && sidebar && backdrop) {
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('open');
            backdrop.classList.toggle('active');
        });

        backdrop.addEventListener('click', function() {
            sidebar.classList.remove('open');
            backdrop.classList.remove('active');
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
                backdrop.classList.remove('active');
            }
        });
    }
});
</script>
