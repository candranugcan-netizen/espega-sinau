<?php 
require_once '../../config/database.php';
require_once '../../config/security.php'; 
protect_page(); 

$active_nav = 'dashboard';
$page_title = 'Dashboard Utama';

// Query counts for quick dashboard stats
try {
    $total_siswa = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 3")->fetchColumn() ?: 0;
    $total_guru = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 2")->fetchColumn() ?: 0;
    $total_ujian = $pdo->query("SELECT COUNT(*) FROM exams")->fetchColumn() ?: 0;
    $total_terblokir = $pdo->query("SELECT COUNT(*) FROM exam_sessions WHERE status_siswa = 'terblokir'")->fetchColumn() ?: 0;
} catch (Exception $e) {
    $total_siswa = 0; $total_guru = 0; $total_ujian = 0; $total_terblokir = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Espega Sinau</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="admin_theme.css">
    <style>
        .welcome-banner {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 28px 32px;
            margin-bottom: 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-sm);
        }
        .welcome-content h1 {
            font-size: 1.65rem;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.025em;
            margin-bottom: 6px;
        }
        .welcome-content p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        /* Modules Grid */
        .section-header {
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .section-header h2 {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .module-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-sm);
        }
        .module-card:hover {
            transform: translateY(-3px);
            border-color: var(--adm-slate-400);
            box-shadow: var(--shadow-md);
        }

        .module-top {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 20px;
        }
        .module-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-sm);
            background: var(--adm-blue-50);
            color: var(--primary);
            border: 1px solid var(--adm-blue-100);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
            transition: all 0.2s;
        }
        .module-card:hover .module-icon {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }
        .module-meta h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 4px;
        }
        .module-meta p {
            font-size: 0.86rem;
            color: var(--text-muted);
            line-height: 1.45;
        }

        .module-action {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 16px;
            border-top: 1px solid var(--adm-slate-100);
        }
        .module-action .link-text {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 6px;
            transition: gap 0.2s;
        }
        .module-card:hover .link-text {
            gap: 10px;
        }
        .module-action .btn-open {
            padding: 7px 14px;
            font-size: 0.82rem;
        }
    </style>
</head>
<body class="admin-body">

<?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Welcome Hero -->
    <div class="welcome-banner">
        <div class="welcome-content">
            <h1>Pusat Kontrol Administrator</h1>
            <p>Kelola konfigurasi ujian, bank soal, akun pengguna, serta pantau integritas sesi secara terpusat.</p>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="adm-stats-grid">
        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-user-graduate"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= number_format($total_siswa) ?></div>
                <div class="adm-stat-label">Total Siswa</div>
            </div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-chalkboard-user"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= number_format($total_guru) ?></div>
                <div class="adm-stat-label">Guru Pengampu</div>
            </div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-file-signature"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= number_format($total_ujian) ?></div>
                <div class="adm-stat-label">Jadwal Ujian</div>
            </div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-icon" style="color: var(--adm-danger);">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <div class="adm-stat-num" style="color: <?= ($total_terblokir > 0) ? 'var(--adm-danger)' : 'inherit' ?>;"><?= number_format($total_terblokir) ?></div>
                <div class="adm-stat-label">Siswa Terblokir</div>
            </div>
        </div>
    </div>

    <!-- Modules Grid -->
    <div class="section-header">
        <h2>Menu Pengelolaan Sistem</h2>
    </div>

    <div class="modules-grid">
        <!-- Kelola Ujian -->
        <div class="module-card">
            <div class="module-top">
                <div class="module-icon">
                    <i class="fa-solid fa-file-signature"></i>
                </div>
                <div class="module-meta">
                    <h3>Kelola Ujian</h3>
                    <p>Atur jadwal waktu ujian, batas durasi, mata pelajaran sasaran, dan generate token ujian baru.</p>
                </div>
            </div>
            <div class="module-action">
                <span class="link-text">Kelola Jadwal <i class="fa-solid fa-arrow-right"></i></span>
                <a href="kelola_ujian.php" class="btn btn-primary btn-open">Buka</a>
            </div>
        </div>

        <!-- Kelola Sesi -->
        <div class="module-card">
            <div class="module-top">
                <div class="module-icon">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div class="module-meta">
                    <h3>Sesi Ujian Siswa</h3>
                    <p>Pantau status sesi siswa (sedang/selesai/terblokir), perpanjang waktu, atau reset sesi ujian.</p>
                </div>
            </div>
            <div class="module-action">
                <span class="link-text">Pantau Sesi <i class="fa-solid fa-arrow-right"></i></span>
                <a href="kelola_sesi.php" class="btn btn-primary btn-open">Buka</a>
            </div>
        </div>

        <!-- Bank Soal -->
        <div class="module-card">
            <div class="module-top">
                <div class="module-icon">
                    <i class="fa-solid fa-book-open"></i>
                </div>
                <div class="module-meta">
                    <h3>Bank Soal Pusat</h3>
                    <p>Kelola paket bank soal, distribusi kelas rilis ujian, dan inventaris butir pertanyaan sekolah.</p>
                </div>
            </div>
            <div class="module-action">
                <span class="link-text">Lihat Soal <i class="fa-solid fa-arrow-right"></i></span>
                <a href="manajemen_soal.php" class="btn btn-primary btn-open">Buka</a>
            </div>
        </div>

        <!-- Data Siswa -->
        <div class="module-card">
            <div class="module-top">
                <div class="module-icon">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>
                <div class="module-meta">
                    <h3>Manajemen Siswa</h3>
                    <p>Kelola data induk siswa, impor data dari file Excel, cetak kartu ujian, dan reset token login.</p>
                </div>
            </div>
            <div class="module-action">
                <span class="link-text">Kelola Siswa <i class="fa-solid fa-arrow-right"></i></span>
                <a href="siswa.php" class="btn btn-primary btn-open">Buka</a>
            </div>
        </div>

        <!-- Live Monitoring -->
        <div class="module-card">
            <div class="module-top">
                <div class="module-icon">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div class="module-meta">
                    <h3>Live Monitoring Integritas</h3>
                    <p>Pantau kecurangan siswa yang terblokir secara real-time (setiap 3 detik) dan buka blokir siswa.</p>
                </div>
            </div>
            <div class="module-action">
                <span class="link-text">Live Monitor <i class="fa-solid fa-arrow-right"></i></span>
                <a href="monitoring.php" class="btn btn-primary btn-open">Buka</a>
            </div>
        </div>

        <!-- Manajemen Guru -->
        <div class="module-card">
            <div class="module-top">
                <div class="module-icon">
                    <i class="fa-solid fa-chalkboard-user"></i>
                </div>
                <div class="module-meta">
                    <h3>Manajemen Guru</h3>
                    <p>Kelola akun guru, token login, serta pemetaan penugasan mata pelajaran dan kelas sasaran.</p>
                </div>
            </div>
            <div class="module-action">
                <span class="link-text">Atur Guru <i class="fa-solid fa-arrow-right"></i></span>
                <a href="manajemen_guru.php" class="btn btn-primary btn-open">Buka</a>
            </div>
        </div>

        <!-- Laporan Nilai -->
        <div class="module-card">
            <div class="module-top">
                <div class="module-icon">
                    <i class="fa-solid fa-chart-simple"></i>
                </div>
                <div class="module-meta">
                    <h3>Laporan Hasil Ujian</h3>
                    <p>Rekapitulasi perolehan nilai siswa seluruh kelas dan unduh laporan komprehensif ke format Excel.</p>
                </div>
            </div>
            <div class="module-action">
                <span class="link-text">Lihat Rekap <i class="fa-solid fa-arrow-right"></i></span>
                <a href="laporan.php" class="btn btn-primary btn-open">Buka</a>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/footer.php'; ?>