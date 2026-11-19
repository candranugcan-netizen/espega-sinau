<?php
/**
 * Shared Admin Sidebar & Layout Header
 * Espega Sinau - Professional Admin Panel
 */
if (!isset($active_nav)) $active_nav = 'dashboard';
if (!isset($page_title)) $page_title = 'Admin Panel';
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
            <span class="brand-badge">ADMIN PANEL</span>
        </div>
    </a>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <div class="nav-section-title">Utama</div>
        <a href="dashboard.php" class="nav-item <?= ($active_nav === 'dashboard') ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge-high"></i>
            <span>Dashboard</span>
        </a>

        <div class="nav-section-title">Ujian & Sesi</div>
        <a href="kelola_ujian.php" class="nav-item <?= ($active_nav === 'ujian') ? 'active' : '' ?>">
            <i class="fa-solid fa-file-signature"></i>
            <span>Kelola Ujian</span>
        </a>
        <a href="kelola_sesi.php" class="nav-item <?= ($active_nav === 'sesi') ? 'active' : '' ?>">
            <i class="fa-solid fa-clock"></i>
            <span>Sesi Ujian</span>
        </a>
        <a href="manajemen_soal.php" class="nav-item <?= ($active_nav === 'soal') ? 'active' : '' ?>">
            <i class="fa-solid fa-book-open"></i>
            <span>Bank Soal</span>
        </a>

        <div class="nav-section-title">Pengguna & Pantau</div>
        <a href="siswa.php" class="nav-item <?= ($active_nav === 'siswa') ? 'active' : '' ?>">
            <i class="fa-solid fa-user-graduate"></i>
            <span>Data Siswa</span>
        </a>
        <a href="monitoring.php" class="nav-item <?= ($active_nav === 'monitoring') ? 'active' : '' ?>">
            <i class="fa-solid fa-shield-halved"></i>
            <span>Live Monitoring</span>
        </a>
        <a href="manajemen_guru.php" class="nav-item <?= ($active_nav === 'guru') ? 'active' : '' ?>">
            <i class="fa-solid fa-chalkboard-user"></i>
            <span>Manajemen Guru</span>
        </a>

        <div class="nav-section-title">Hasil</div>
        <a href="laporan.php" class="nav-item <?= ($active_nav === 'laporan') ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-simple"></i>
            <span>Laporan Nilai</span>
        </a>
    </nav>

    <!-- Sidebar Footer (User & Logout) -->
    <div class="sidebar-footer">
        <div class="user-box">
            <div class="user-avatar">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div class="user-meta-info">
                <div class="user-name">Administrator</div>
                <div class="user-role">Super Admin</div>
            </div>
        </div>
        <a href="../../controllers/AuthController.php?logout=1" class="btn-sidebar-logout" title="Keluar dari Panel">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
        </a>
    </div>
</aside>

<!-- Backdrop Overlay for Mobile -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- Admin Main Content Wrapper -->
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
            <div class="system-status-pill">
                <span class="status-dot"></span>
                <span>Sistem Aktif</span>
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
