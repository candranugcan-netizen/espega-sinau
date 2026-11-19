<?php
require_once '../../config/database.php';
require_once '../../config/security.php';
protect_page();

date_default_timezone_set('Asia/Jakarta');
$now = time();

$user_id = $_SESSION['user_id'];

// 1. Ambil data profil siswa lengkap dari database
$stmtUser = $pdo->prepare("SELECT id, username, nama_lengkap, kelas, status_blokir FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$userData = $stmtUser->fetch();
$kelas_siswa = $userData['kelas'] ?? '';
$nama_siswa = !empty($userData['nama_lengkap']) ? $userData['nama_lengkap'] : ($_SESSION['nama'] ?? 'Siswa');
$username_siswa = $userData['username'] ?? ($_SESSION['username'] ?? '');
$is_blocked = ($userData['status_blokir'] ?? '0') === '1';

// Inisial avatar siswa
$nama_parts = explode(' ', trim($nama_siswa));
$initials = '';
if (count($nama_parts) >= 2) {
    $initials = strtoupper(mb_substr($nama_parts[0], 0, 1) . mb_substr($nama_parts[1], 0, 1));
} else {
    $initials = strtoupper(mb_substr($nama_siswa, 0, 2));
}

// 2. Ambil seluruh ujian untuk kelas siswa
$query = "SELECT e.*, s.nama_mapel, 
                 es.id as session_id,
                 es.status_siswa,
                 es.sisa_waktu,
                 es.mulai_ujian as sesi_mulai_ujian
          FROM exams e 
          JOIN subjects s ON e.subject_id = s.id 
          LEFT JOIN exam_sessions es ON es.exam_id = e.id AND es.user_id = ?
          WHERE e.target_kelas = ?
          ORDER BY e.waktu_mulai ASC";

// Jalankan auto-finalize untuk sesi ujian siswa yang waktunya telah habis
auto_finalize_expired_sessions($pdo, null, $user_id);

$stmt = $pdo->prepare($query);
$stmt->execute([$user_id, $kelas_siswa]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung metrik ringkas untuk badge tab
$total_exams = count($exams);
$count_active = 0;
$count_done = 0;
$count_upcoming = 0;

foreach ($exams as $exam_item) {
    $st = strtotime($exam_item['waktu_mulai']);
    $et = get_exam_end_time($exam_item);
    $status_siswa_item = $exam_item['status_siswa'] ?? 'belum';

    if ($status_siswa_item === 'selesai') {
        $count_done++;
    } elseif ($status_siswa_item === 'pengerjaan') {
        $count_active++;
    } elseif ($now >= $st && $now <= $et) {
        $count_active++;
    } elseif ($now < $st) {
        $count_upcoming++;
    }
}

/**
 * Helper SVG Icon Offline-proof
 * Menghindari kotak kosong '▯' akibat font eksternal gagal dimuat pada lingkungan offline CBT
 */
function cbt_icon($name, $extraClass = '', $size = 15) {
    $cls = 'cbt-icon' . ($extraClass ? ' ' . $extraClass : '');
    $icons = [
        'graduation' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>',
        'expand' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>',
        'compress' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="14" y1="10" x2="21" y2="3"/><line x1="3" y1="21" x2="10" y2="14"/></svg>',
        'logout' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
        'shield' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        'user' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><line x1="15" y1="8" x2="17" y2="8"/><line x1="15" y1="12" x2="17" y2="12"/><line x1="7" y1="16" x2="11" y2="16"/></svg>',
        'class' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'check-circle' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        'check' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
        'alert-triangle' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        'alert-circle' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
        'book' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
        'search' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
        'stopwatch' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="13" r="8"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="10" y1="2" x2="14" y2="2"/><line x1="12" y1="2" x2="12" y2="5"/></svg>',
        'calendar' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
        'clock' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'hourglass' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 22h14"/><path d="M5 2h14"/><path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22"/><path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2"/></svg>',
        'ban' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>',
        'play' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="currentColor"><polygon points="6 4 20 12 6 20 6 4"/></svg>',
        'key' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-1.5 1.5L16 7l-1.5-1.5M19 4l-4 4-2-2-7 7a5 5 0 1 0 7 7l7-7-2-2z"/></svg>',
        'arrow-right' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>',
        'calendar-times' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="10" y1="14" x2="14" y2="18"/><line x1="14" y1="14" x2="10" y2="18"/></svg>'
    ];
    return $icons[$name] ?? '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard Siswa - Espega Sinau CBT</title>
    <!-- Font Inter & Fallback Native System Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- jQuery & SweetAlert2 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* ===== TEMA WARNA SOLID FLAT & VARIABEL DESAIN ===== */
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #eff6ff;
            --navy: #1e3a8a;
            --navy-dark: #172554;
            --success: #10b981;
            --success-light: #ecfdf5;
            --warning: #f59e0b;
            --warning-light: #fffbeb;
            --danger: #dc2626;
            --danger-light: #fef2f2;
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-300: #cbd5e1;
            --slate-400: #94a3b8;
            --slate-500: #64748b;
            --slate-600: #475569;
            --slate-700: #334155;
            --slate-800: #1e293b;
            --slate-900: #0f172a;
            --white: #ffffff;
            --radius-sm: 8px;
            --radius-md: 12px;
        }

        /* ===== RESET & VIEWPORT SAFETY ===== */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        html, body {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
            position: relative;
            background-color: #f3f6fa;
            color: var(--slate-900);
            min-height: 100vh;
            padding-bottom: 30px;
        }

        /* SVG Icon Universal Styling (100% Offline Resilience) */
        .cbt-icon {
            display: inline-block;
            vertical-align: middle;
            flex-shrink: 0;
        }

        .spin-icon {
            animation: cbtSpin 1s linear infinite;
        }

        @keyframes cbtSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Pencegah tabel keluar frame */
        table {
            width: 100% !important;
            max-width: 100% !important;
            display: block !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
            border-collapse: collapse;
        }

        /* ===== NAVBAR ===== */
        .navbar {
            background-color: var(--white);
            border-bottom: 1px solid var(--slate-200);
            position: sticky;
            top: 0;
            z-index: 100;
            width: 100%;
        }

        .nav-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 10px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            width: 100%;
        }

        .brand-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            min-width: 0;
        }

        .brand-icon {
            width: 36px;
            height: 36px;
            background-color: var(--primary);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            flex-shrink: 0;
        }

        .brand-text h1 {
            font-size: 1rem;
            font-weight: 800;
            color: var(--slate-900);
            line-height: 1.15;
            letter-spacing: -0.2px;
            white-space: nowrap;
        }

        .brand-text span {
            font-size: 0.7rem;
            color: var(--slate-500);
            font-weight: 600;
            display: block;
            white-space: nowrap;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .btn-nav-fs {
            background-color: var(--slate-100);
            color: var(--slate-700);
            border: 1px solid var(--slate-300);
            padding: 6px 10px;
            border-radius: var(--radius-sm);
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.15s, border-color 0.15s;
        }

        .btn-nav-fs:hover {
            background-color: var(--slate-200);
            border-color: var(--slate-400);
        }

        .btn-logout {
            color: var(--danger);
            background-color: var(--danger-light);
            border: 1px solid #fecaca;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            font-size: 0.78rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-logout:hover {
            background-color: #fee2e2;
        }

        /* ===== CONTAINER UTAMA ===== */
        .container {
            max-width: 900px;
            margin: 14px auto 0 auto;
            padding: 0 14px;
            width: 100%;
        }

        /* ===== BANNER SISWA TERBLOKIR (ANTI-CHEAT) ===== */
        .blocked-banner {
            background-color: var(--danger-light);
            border: 1px solid #fecaca;
            border-left: 5px solid var(--danger);
            border-radius: var(--radius-sm);
            padding: 12px 14px;
            margin-bottom: 14px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            width: 100%;
        }

        .blocked-banner-icon {
            color: var(--danger);
            flex-shrink: 0;
            margin-top: 2px;
        }

        .blocked-banner-content h4 {
            color: #991b1b;
            font-size: 0.9rem;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .blocked-banner-content p {
            color: #b91c1c;
            font-size: 0.78rem;
            line-height: 1.45;
        }

        /* ===== KARTU PROFIL SISWA (SOLID NAVY, RAPI & BERSIH) ===== */
        .welcome-hero {
            background-color: var(--navy);
            color: var(--white);
            border-radius: var(--radius-md);
            padding: 14px 16px;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            width: 100%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }

        .student-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            flex: 1;
        }

        .student-avatar {
            width: 44px;
            height: 44px;
            background-color: var(--primary);
            color: var(--white);
            font-weight: 800;
            font-size: 1.15rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255, 255, 255, 0.35);
            flex-shrink: 0;
        }

        .student-info {
            min-width: 0;
            flex: 1;
        }

        .student-info h2 {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .student-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
        }

        .pill-tag {
            background-color: rgba(255, 255, 255, 0.16);
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--white);
        }

        .pill-status-ready {
            background-color: var(--success);
            color: var(--white);
        }

        .pill-status-blocked {
            background-color: var(--danger);
            color: var(--white);
        }

        /* ===== TOOLBAR (FILTER TABS & SEARCH) ===== */
        .section-toolbar {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 14px;
            width: 100%;
        }

        .toolbar-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .section-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--slate-900);
            display: flex;
            align-items: center;
            gap: 7px;
        }

        /* Filter bar scrollable tanpa bar abu-abu */
        .filter-group {
            display: flex;
            align-items: center;
            gap: 6px;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE / Edge */
            padding: 2px 1px 4px 1px;
        }

        .filter-group::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }

        .filter-btn {
            background-color: var(--white);
            border: 1px solid var(--slate-300);
            color: var(--slate-700);
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 0.74rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            flex-shrink: 0;
            transition: all 0.15s ease;
        }

        .filter-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .filter-btn.active {
            background-color: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }

        .filter-count {
            background-color: var(--slate-100);
            color: var(--slate-700);
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 0.68rem;
            font-weight: 700;
        }

        .filter-btn.active .filter-count {
            background-color: rgba(255, 255, 255, 0.25);
            color: var(--white);
        }

        .search-box {
            position: relative;
            width: 100%;
        }

        .search-icon-wrapper {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--slate-400);
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .search-box input {
            width: 100%;
            padding: 8px 12px 8px 34px;
            border: 1px solid var(--slate-300);
            border-radius: var(--radius-sm);
            font-size: 0.8rem;
            color: var(--slate-900);
            background-color: var(--white);
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .search-box input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
        }

        /* ===== GRID KARTU UJIAN ===== */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
            gap: 12px;
            width: 100%;
        }

        .exam-card {
            background-color: var(--white);
            border-radius: var(--radius-sm);
            padding: 14px;
            border: 1px solid var(--slate-200);
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
            transition: transform 0.1s ease, box-shadow 0.1s ease;
        }

        .exam-card.card-ongoing {
            border-left: 4px solid var(--warning);
        }

        .exam-card.card-active {
            border-left: 4px solid var(--success);
        }

        .exam-card.card-ended {
            background-color: var(--slate-50);
            opacity: 0.9;
        }

        .exam-card.card-completed {
            background-color: var(--slate-50);
        }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 6px;
            margin-bottom: 10px;
            width: 100%;
        }

        .mapel-badge {
            background-color: var(--primary-light);
            color: var(--primary-dark);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.72rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: 1px solid #bfdbfe;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 65%;
        }

        .status-pill {
            padding: 3px 7px;
            border-radius: 4px;
            font-size: 0.68rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .status-pill.status-live {
            background-color: var(--success-light);
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .pulse-dot {
            width: 6px;
            height: 6px;
            background-color: var(--success);
            border-radius: 50%;
            display: inline-block;
        }

        .status-pill.status-ongoing {
            background-color: var(--warning-light);
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .status-pill.status-ended {
            background-color: var(--danger-light);
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .status-pill.status-upcoming {
            background-color: var(--slate-100);
            color: var(--slate-600);
            border: 1px solid var(--slate-300);
        }

        .status-pill.status-done {
            background-color: var(--slate-100);
            color: var(--slate-700);
            border: 1px solid var(--slate-300);
        }

        .exam-title {
            font-size: 0.98rem;
            font-weight: 700;
            color: var(--slate-900);
            margin-bottom: 10px;
            line-height: 1.35;
            word-break: break-word;
        }

        .ongoing-alert-box {
            background-color: var(--warning-light);
            border: 1px solid #fde68a;
            border-radius: 4px;
            padding: 6px 9px;
            font-size: 0.72rem;
            color: #92400e;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .meta-list {
            background-color: var(--slate-50);
            border-radius: 6px;
            padding: 8px 10px;
            border: 1px solid var(--slate-200);
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 12px;
            width: 100%;
        }

        .meta-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.74rem;
            color: var(--slate-500);
            gap: 6px;
        }

        .meta-row .meta-key {
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .meta-row .meta-val {
            font-weight: 600;
            color: var(--slate-800);
            text-align: right;
            word-break: break-word;
        }

        /* ===== ACTION BUTTONS ===== */
        .btn {
            width: 100%;
            padding: 9px 12px;
            border-radius: var(--radius-sm);
            border: none;
            font-weight: 700;
            font-size: 0.82rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            box-sizing: border-box;
            text-align: center;
            transition: background-color 0.15s;
        }

        .btn-start {
            background-color: var(--primary);
            color: var(--white);
        }

        .btn-start:hover {
            background-color: var(--primary-dark);
        }

        .btn-continue {
            background-color: var(--warning);
            color: var(--white);
        }

        .btn-continue:hover {
            background-color: #d97706;
        }

        .btn-disabled {
            background-color: var(--slate-200);
            color: var(--slate-500);
            border: 1px solid var(--slate-300);
            cursor: not-allowed;
        }

        .btn-disabled.btn-ended {
            background-color: var(--danger-light);
            color: #991b1b;
            border-color: #fecaca;
        }

        .btn-subtext {
            font-size: 0.68rem;
            text-align: center;
            margin-top: 5px;
            font-weight: 500;
        }

        .btn-subtext.text-danger { color: var(--danger); font-weight: 600; }
        .btn-subtext.text-muted { color: var(--slate-500); }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 30px 14px;
            background-color: var(--white);
            border-radius: var(--radius-sm);
            border: 1px dashed var(--slate-300);
            width: 100%;
        }

        .empty-icon {
            color: var(--slate-400);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .empty-state h3 {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--slate-800);
            margin-bottom: 3px;
        }

        .empty-state p {
            font-size: 0.76rem;
            color: var(--slate-500);
            max-width: 320px;
            margin: 0 auto;
        }

        /* ===== TOKEN MODAL ===== */
        .token-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 14px;
            box-sizing: border-box;
        }

        .token-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(15, 23, 42, 0.75);
            z-index: 1;
        }

        .token-modal-card {
            position: relative;
            z-index: 2;
            background-color: var(--white);
            width: 100%;
            max-width: 360px;
            border-radius: var(--radius-md);
            padding: 16px;
            border: 1px solid var(--slate-200);
            box-sizing: border-box;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .token-modal-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            position: relative;
        }

        .token-modal-icon {
            width: 36px;
            height: 36px;
            background-color: var(--primary-light);
            color: var(--primary);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border: 1px solid #bfdbfe;
        }

        .token-modal-close {
            position: absolute;
            right: -2px;
            top: -2px;
            background: transparent;
            border: none;
            font-size: 1.3rem;
            line-height: 1;
            color: var(--slate-400);
            cursor: pointer;
            width: 28px;
            height: 28px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .token-modal-close:hover {
            color: var(--slate-700);
            background-color: var(--slate-100);
        }

        .token-modal-info {
            background-color: var(--slate-50);
            border: 1px solid var(--slate-200);
            border-radius: var(--radius-sm);
            padding: 8px 10px;
            margin-bottom: 12px;
            font-size: 0.76rem;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .token-info-item {
            display: flex;
            gap: 6px;
        }

        .token-info-item span {
            color: var(--slate-500);
            min-width: 85px;
        }

        .token-info-item strong {
            color: var(--slate-900);
            word-break: break-word;
        }

        .token-input-field {
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
            font-size: 1.25rem;
            font-weight: 800;
            text-transform: uppercase;
            text-align: center;
            letter-spacing: 5px;
            border: 2px solid var(--slate-300);
            border-radius: var(--radius-sm);
            outline: none;
            background-color: var(--white);
            color: var(--slate-900);
            transition: border-color 0.15s;
        }

        .token-input-field:focus {
            border-color: var(--primary);
        }

        .token-error {
            color: #b91c1c;
            font-size: 0.74rem;
            font-weight: 600;
            margin-top: 6px;
            text-align: center;
            background-color: var(--danger-light);
            border: 1px solid #fee2e2;
            padding: 6px 8px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .btn-modal-cancel {
            padding: 8px 14px;
            background-color: var(--slate-100);
            color: var(--slate-700);
            border: 1px solid var(--slate-300);
            border-radius: var(--radius-sm);
            font-weight: 700;
            font-size: 0.78rem;
            cursor: pointer;
        }

        .btn-modal-cancel:hover {
            background-color: var(--slate-200);
        }

        .btn-modal-submit {
            padding: 8px 16px;
            background-color: var(--primary);
            color: var(--white);
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 700;
            font-size: 0.78rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-modal-submit:hover {
            background-color: var(--primary-dark);
        }

        /* ===== PENYESUAIAN MOBILE ===== */
        @media (max-width: 640px) {
            .container {
                padding: 0 10px;
                margin-top: 10px;
            }

            .grid {
                display: flex;
                flex-direction: column;
                gap: 10px;
                width: 100%;
            }

            .exam-card {
                width: 100%;
            }

            .btn-nav-fs span {
                display: none;
            }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<header class="navbar">
    <div class="nav-container">
        <div class="brand-wrapper">
            <div class="brand-icon">
                <?= cbt_icon('graduation', '', 20) ?>
            </div>
            <div class="brand-text">
                <h1>ESPEGA SINAU</h1>
                <span>Sistem Ujian CBT</span>
            </div>
        </div>

        <div class="nav-actions">
            <!-- TOMBOL FULLSCREEN NAVBAR -->
            <button type="button" class="btn-nav-fs" id="btnNavbarFs" onclick="toggleFullscreen()" title="Beralih Layar Penuh">
                <span id="wrapIconFs"><?= cbt_icon('expand', '', 15) ?></span>
                <span id="textFs">Layar Penuh</span>
            </button>

            <!-- TOMBOL LOGOUT -->
            <a href="../../controllers/AuthController.php?logout=1" class="btn-logout" id="logoutLink" title="Keluar">
                <?= cbt_icon('logout', '', 14) ?>
                <span>Keluar</span>
            </a>
        </div>
    </div>
</header>

<main class="container">

    <!-- ALERT JIKA STATUS SISWA TERBLOKIR AKIBAT PELANGGARAN -->
    <?php if ($is_blocked): ?>
        <div class="blocked-banner">
            <div class="blocked-banner-icon">
                <?= cbt_icon('shield', '', 22) ?>
            </div>
            <div class="blocked-banner-content">
                <h4>Akun Anda Sedang Dibekukan</h4>
                <p>
                    Terdeteksi aktivitas yang melanggar aturan ujian (berpindah aplikasi/tab). 
                    Segera temui <strong>Proktor / Guru Pengawas</strong> di ruangan Anda untuk mengaktifkan kembali akun Anda.
                </p>
            </div>
        </div>
    <?php endif; ?>

    <!-- WELCOME HERO PROFIL SISWA (SEDERHANA, SOLID) -->
    <section class="welcome-hero">
        <div class="student-profile">
            <div class="student-avatar" title="<?= htmlspecialchars($nama_siswa) ?>">
                <?= htmlspecialchars($initials) ?>
            </div>
            <div class="student-info">
                <h2>Halo, <?= htmlspecialchars(explode(' ', trim($nama_siswa))[0]) ?>!</h2>
                <div class="student-meta">
                    <span class="pill-tag">
                        <?= cbt_icon('user', '', 13) ?>
                        <?= htmlspecialchars($username_siswa) ?>
                    </span>
                    <span class="pill-tag">
                        <?= cbt_icon('class', '', 13) ?>
                        Kelas <?= htmlspecialchars($kelas_siswa) ?>
                    </span>
                    <?php if ($is_blocked): ?>
                        <span class="pill-tag pill-status-blocked">
                            <?= cbt_icon('alert-triangle', '', 13) ?>
                            Terblokir
                        </span>
                    <?php else: ?>
                        <span class="pill-tag pill-status-ready">
                            <?= cbt_icon('check-circle', '', 13) ?>
                            Siap Ujian
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- TOOLBAR (FILTER TABS & SEARCH) -->
    <div class="section-toolbar">
        <div class="toolbar-top">
            <div class="section-title">
                <span style="color: var(--primary); display: flex; align-items: center;">
                    <?= cbt_icon('book', '', 16) ?>
                </span>
                <span>Daftar Ujian</span>
            </div>
        </div>

        <div class="filter-group">
            <button type="button" class="filter-btn active" data-filter="all" onclick="applyFilter('all', this)">
                Semua <span class="filter-count"><?= $total_exams ?></span>
            </button>
            <button type="button" class="filter-btn" data-filter="berlangsung" onclick="applyFilter('berlangsung', this)">
                Berlangsung <span class="filter-count"><?= $count_active ?></span>
            </button>
            <button type="button" class="filter-btn" data-filter="upcoming" onclick="applyFilter('upcoming', this)">
                Akan Datang <span class="filter-count"><?= $count_upcoming ?></span>
            </button>
            <button type="button" class="filter-btn" data-filter="selesai" onclick="applyFilter('selesai', this)">
                Selesai <span class="filter-count"><?= $count_done ?></span>
            </button>
        </div>

        <div class="search-box">
            <div class="search-icon-wrapper">
                <?= cbt_icon('search', '', 14) ?>
            </div>
            <input type="text" id="searchExamInput" placeholder="Cari mapel / ujian..." onkeyup="filterBySearch()">
        </div>
    </div>

    <!-- EXAMS GRID -->
    <?php if (empty($exams)): ?>
        <div class="empty-state">
            <div class="empty-icon"><?= cbt_icon('calendar-times', '', 32) ?></div>
            <h3>Belum Ada Jadwal Ujian</h3>
            <p>Tidak ada ujian aktif untuk kelas <strong><?= htmlspecialchars($kelas_siswa) ?></strong> saat ini.</p>
        </div>
    <?php else: ?>
        <div class="grid" id="examCardsGrid">
            <?php 
            foreach ($exams as $exam): 
                $startTime = strtotime($exam['waktu_mulai']);
                $endTime = get_exam_end_time($exam);

                $isStarted = ($now >= $startTime);
                $isEnded = ($now > $endTime);
                $isActive = ($now >= $startTime && $now <= $endTime);
                $statusSiswa = $exam['status_siswa'] ?? 'belum';

                $filterCategory = 'other';
                $cardClass = '';

                if ($statusSiswa === 'selesai') {
                    $filterCategory = 'selesai';
                    $cardClass = 'card-completed';
                } elseif ($statusSiswa === 'pengerjaan') {
                    $filterCategory = 'berlangsung';
                    $cardClass = 'card-ongoing';
                } elseif ($isActive) {
                    $filterCategory = 'berlangsung';
                    $cardClass = 'card-active';
                } elseif (!$isStarted) {
                    $filterCategory = 'upcoming';
                } elseif ($isEnded) {
                    $filterCategory = 'ended';
                    $cardClass = 'card-ended';
                }
            ?>
            <div class="exam-card <?= $cardClass ?>" 
                 data-category="<?= $filterCategory ?>" 
                 data-search="<?= htmlspecialchars(strtolower($exam['nama_mapel'] . ' ' . $exam['nama_ujian'])) ?>">
                
                <div>
                    <!-- TOP BADGES -->
                    <div class="card-top">
                        <span class="mapel-badge" title="<?= htmlspecialchars($exam['nama_mapel']) ?>">
                            <?= cbt_icon('book', '', 12) ?>
                            <?= htmlspecialchars($exam['nama_mapel']) ?>
                        </span>
                        
                        <?php if ($statusSiswa === 'selesai'): ?>
                            <span class="status-pill status-done">
                                <?= cbt_icon('check', '', 12) ?>
                                Selesai
                            </span>
                        <?php elseif ($statusSiswa === 'pengerjaan'): ?>
                            <span class="status-pill status-ongoing">
                                <span class="pulse-dot" style="background-color: var(--warning);"></span>
                                Pengerjaan
                            </span>
                        <?php elseif ($isEnded): ?>
                            <span class="status-pill status-ended">
                                <?= cbt_icon('ban', '', 12) ?>
                                Waktu Lewat
                            </span>
                        <?php elseif ($isActive): ?>
                            <span class="status-pill status-live">
                                <span class="pulse-dot"></span>
                                Berlangsung
                            </span>
                        <?php else: ?>
                            <span class="status-pill status-upcoming">
                                <?= cbt_icon('clock', '', 12) ?>
                                Belum Mulai
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- EXAM TITLE -->
                    <h3 class="exam-title">
                        <?= htmlspecialchars($exam['nama_ujian']) ?>
                    </h3>

                    <!-- NOTICE STATUS PENGERJAAN -->
                    <?php if ($statusSiswa === 'pengerjaan'): ?>
                        <div class="ongoing-alert-box">
                            <?= cbt_icon('alert-circle', '', 14) ?>
                            <span>Sesi Anda aktif. Silakan lanjutkan ujian!</span>
                        </div>
                    <?php endif; ?>

                    <!-- SPECIFICATIONS -->
                    <div class="meta-list">
                        <div class="meta-row">
                            <span class="meta-key">
                                <?= cbt_icon('stopwatch', '', 13) ?>
                                Durasi
                            </span>
                            <span class="meta-val"><?= (int)$exam['durasi'] ?> Menit</span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-key">
                                <?= cbt_icon('calendar', '', 13) ?>
                                Mulai
                            </span>
                            <span class="meta-val"><?= date('d M Y, H:i', $startTime) ?> WIB</span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-key">
                                <?= cbt_icon('clock', '', 13) ?>
                                Selesai
                            </span>
                            <span class="meta-val"><?= date('d M Y, H:i', $endTime) ?> WIB</span>
                        </div>
                    </div>
                </div>

                <!-- CARD ACTIONS (FUNGSI ASLI 100% PRESERVED) -->
                <div>
                    <?php if ($statusSiswa === 'selesai'): ?>
                        <button class="btn btn-disabled" disabled>
                            <?= cbt_icon('check', '', 14) ?>
                            Ujian Sudah Selesai
                        </button>

                    <?php elseif ($isEnded): ?>
                        <button class="btn btn-disabled btn-ended" disabled title="Waktu pelaksanaan ujian berakhir">
                            <?= cbt_icon('ban', '', 14) ?>
                            Waktu Telah Berakhir
                        </button>
                        <p class="btn-subtext text-danger">Batas waktu berakhir pada <?= date('H:i', $endTime) ?> WIB</p>

                    <?php elseif (!$isStarted): ?>
                        <button class="btn btn-disabled" disabled title="Ujian belum dapat dibuka">
                            <?= cbt_icon('hourglass', '', 14) ?>
                            Menunggu Jadwal
                        </button>
                        <p class="btn-subtext text-muted">Dapat dibuka mulai pukul <?= date('H:i', $startTime) ?> WIB</p>

                    <?php elseif ($statusSiswa === 'pengerjaan'): ?>
                        <a href="ujian.php?id=<?= $exam['id'] ?>" class="btn btn-continue" onclick="enterFullscreen()">
                            <?= cbt_icon('play', '', 13) ?>
                            Lanjutkan Ujian
                        </a>

                    <?php else: ?>
                        <!-- Waktu ujian sedang aktif & siswa belum mulai -->
                        <button type="button" 
                                class="btn btn-start" 
                                data-id="<?= $exam['id'] ?>" 
                                data-ujian="<?= htmlspecialchars($exam['nama_ujian'], ENT_QUOTES, 'UTF-8') ?>" 
                                data-mapel="<?= htmlspecialchars($exam['nama_mapel'], ENT_QUOTES, 'UTF-8') ?>" 
                                onclick="openTokenModal(<?= $exam['id'] ?>, this)">
                            <?= cbt_icon('key', '', 14) ?>
                            Masukkan Token & Mulai
                        </button>
                    <?php endif; ?>
                </div>

            </div>
            <?php endforeach; ?>
        </div>

        <div id="noSearchResults" class="empty-state" style="display: none; margin-top: 10px;">
            <div class="empty-icon"><?= cbt_icon('search', '', 30) ?></div>
            <h3>Ujian Tidak Ditemukan</h3>
            <p>Tidak ada mata pelajaran atau ujian yang cocok dengan kata kunci pencarian Anda.</p>
        </div>
    <?php endif; ?>

</main>

<!-- MODAL INPUT TOKEN UJIAN (FUNGSI, ID & FORM 100% PRESERVED) -->
<div id="tokenModal" class="token-modal" style="display: none;">
    <div class="token-modal-backdrop" onclick="closeTokenModal()"></div>
    <div class="token-modal-card">
        <div class="token-modal-header">
            <div class="token-modal-icon">
                <?= cbt_icon('key', '', 18) ?>
            </div>
            <div style="flex: 1; min-width: 0;">
                <h3 style="margin: 0; color: var(--slate-900); font-size: 1rem; font-weight: 800;">Konfirmasi Token Ujian</h3>
                <p style="margin: 2px 0 0 0; color: var(--slate-500); font-size: 0.72rem;">Masukkan kode akses dari pengawas</p>
            </div>
            <button type="button" class="token-modal-close" onclick="closeTokenModal()">&times;</button>
        </div>
        
        <div class="token-modal-info">
            <div class="token-info-item">
                <span>Mata Pelajaran:</span>
                <strong id="modalMapel">-</strong>
            </div>
            <div class="token-info-item">
                <span>Nama Ujian:</span>
                <strong id="modalUjian">-</strong>
            </div>
        </div>

        <form id="formToken" method="POST" action="proses_mulai.php" onsubmit="handleTokenSubmit(event)">
            <input type="hidden" name="id" id="modalExamId" value="">
            
            <div style="margin-bottom: 12px;">
                <label for="inputToken" style="display: block; font-size: 0.76rem; font-weight: 700; color: var(--slate-700); margin-bottom: 5px;">
                    Kode Token Ujian (6 Karakter)
                </label>
                <input type="text" 
                       name="token" 
                       id="inputToken" 
                       class="token-input-field" 
                       placeholder="XXXXXX" 
                       maxlength="10" 
                       required 
                       autocomplete="off" 
                       spellcheck="false"
                       oninput="this.value = this.value.toUpperCase()">
                <div id="tokenErrorMsg" class="token-error" style="display: none;"></div>
            </div>

            <div style="display: flex; gap: 8px; justify-content: flex-end; margin-top: 14px;">
                <button type="button" class="btn-modal-cancel" onclick="closeTokenModal()">Batal</button>
                <button type="submit" id="btnSubmitToken" class="btn-modal-submit">
                    <span id="btnSubmitText" style="display: inline-flex; align-items: center; gap: 6px;">
                        <?= cbt_icon('arrow-right', '', 14) ?> Mulai Kerjakan
                    </span>
                    <span id="btnSubmitSpinner" style="display: none; align-items: center; gap: 6px;">
                        <svg class="cbt-icon spin-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10"/></svg>
                        Memeriksa...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ===== LOGIKA FULLSCREEN OTOMATIS (DESKTOP & MOBILE) =====
const SVG_ICON_EXPAND = `<svg class="cbt-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>`;
const SVG_ICON_COMPRESS = `<svg class="cbt-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="14" y1="10" x2="21" y2="3"/><line x1="3" y1="21" x2="10" y2="14"/></svg>`;

function isFullscreenActive() {
    return !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
}

function enterFullscreen() {
    const el = document.documentElement;
    const requestFs = el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen || el.msRequestFullscreen;
    
    if (requestFs && !isFullscreenActive()) {
        try {
            const promise = requestFs.call(el);
            if (promise && promise.catch) {
                promise.catch(() => {});
            }
        } catch(e) {}
    }
    updateFullscreenUI();
}

function exitFullscreen() {
    const exitFs = document.exitFullscreen || document.webkitExitFullscreen || document.mozCancelFullScreen || document.msExitFullscreen;
    if (exitFs && isFullscreenActive()) {
        try {
            const promise = exitFs.call(document);
            if (promise && promise.catch) {
                promise.catch(() => {});
            }
        } catch(e) {}
    }
    updateFullscreenUI();
}

function toggleFullscreen() {
    if (isFullscreenActive()) {
        exitFullscreen();
    } else {
        enterFullscreen();
    }
}

function updateFullscreenUI() {
    const isFs = isFullscreenActive();
    const wrapIconFs = document.getElementById('wrapIconFs');
    const textFs = document.getElementById('textFs');

    if (wrapIconFs && textFs) {
        if (isFs) {
            wrapIconFs.innerHTML = SVG_ICON_COMPRESS;
            textFs.textContent = 'Normal';
        } else {
            wrapIconFs.innerHTML = SVG_ICON_EXPAND;
            textFs.textContent = 'Layar Penuh';
        }
    }
}

// 1. Coba aktifkan fullscreen saat halaman selesai dimuat
window.addEventListener('load', () => {
    enterFullscreen();
    updateFullscreenUI();
});

// 2. Otomatis masuk fullscreen pada sentuhan / klik pertama di layar
let fsAutoDone = false;
function triggerAutoFullscreen() {
    if (!fsAutoDone && !isFullscreenActive()) {
        enterFullscreen();
        fsAutoDone = true;
    }
}
document.addEventListener('click', triggerAutoFullscreen, { passive: true });
document.addEventListener('touchstart', triggerAutoFullscreen, { passive: true });
document.addEventListener('touchend', triggerAutoFullscreen, { passive: true });

// 3. Pantau perubahan status fullscreen
document.addEventListener('fullscreenchange', updateFullscreenUI);
document.addEventListener('webkitfullscreenchange', updateFullscreenUI);
document.addEventListener('mozfullscreenchange', updateFullscreenUI);
document.addEventListener('MSFullscreenChange', updateFullscreenUI);

// ===== KONFIRMASI LOGOUT =====
document.getElementById('logoutLink')?.addEventListener('click', function(e) {
    e.preventDefault();
    const href = this.getAttribute('href');
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Konfirmasi Keluar',
            text: 'Apakah Anda yakin ingin keluar dari akun ujian?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Keluar',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = href;
            }
        });
    } else {
        if (confirm('Apakah Anda yakin ingin keluar?')) {
            window.location.href = href;
        }
    }
});

// ===== FILTER & PENCARIAN =====
let currentFilter = 'all';
function applyFilter(filter, btn) {
    currentFilter = filter;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    filterCards();
}

function filterBySearch() {
    filterCards();
}

function filterCards() {
    const searchVal = (document.getElementById('searchExamInput')?.value || '').toLowerCase().trim();
    const cards = document.querySelectorAll('.exam-card');
    let visibleCount = 0;

    cards.forEach(card => {
        const cat = card.getAttribute('data-category');
        const text = card.getAttribute('data-search') || '';

        let matchCategory = (currentFilter === 'all') || (cat === currentFilter);
        let matchSearch = !searchVal || text.includes(searchVal);

        if (matchCategory && matchSearch) {
            card.style.display = 'flex';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    const noResEl = document.getElementById('noSearchResults');
    if (noResEl) {
        noResEl.style.display = (visibleCount === 0 && cards.length > 0) ? 'block' : 'none';
    }
}

// ===== FUNGSI TOKEN MODAL (100% PRESERVED & FUNCTIONAL) =====
function openTokenModal(id, btnOrUjian, mapel) {
    enterFullscreen();

    let namaUjian = '';
    let namaMapel = '';

    if (typeof btnOrUjian === 'object' && btnOrUjian !== null) {
        namaUjian = btnOrUjian.getAttribute('data-ujian') || '';
        namaMapel = btnOrUjian.getAttribute('data-mapel') || '';
    } else {
        namaUjian = btnOrUjian || '';
        namaMapel = mapel || '';
    }

    document.getElementById('modalExamId').value = id;
    document.getElementById('modalMapel').textContent = namaMapel || '-';
    document.getElementById('modalUjian').textContent = namaUjian || '-';

    const input = document.getElementById('inputToken');
    input.value = '';

    const errBox = document.getElementById('tokenErrorMsg');
    errBox.style.display = 'none';
    errBox.textContent = '';

    const submitBtn = document.getElementById('btnSubmitToken');
    submitBtn.disabled = false;
    document.getElementById('btnSubmitText').style.display = 'inline-flex';
    document.getElementById('btnSubmitSpinner').style.display = 'none';

    const modal = document.getElementById('tokenModal');
    modal.style.display = 'flex';

    setTimeout(() => {
        input.focus();
    }, 150);
}

// Alias untuk kompatibilitas
function confirmStart(id, namaUjian, namaMapel) {
    openTokenModal(id, namaUjian, namaMapel);
}

function closeTokenModal() {
    const modal = document.getElementById('tokenModal');
    if (modal) modal.style.display = 'none';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTokenModal();
    }
});

function handleTokenSubmit(e) {
    e.preventDefault();
    enterFullscreen();

    const examId = document.getElementById('modalExamId').value;
    const tokenInput = document.getElementById('inputToken').value.trim();
    const errBox = document.getElementById('tokenErrorMsg');
    const submitBtn = document.getElementById('btnSubmitToken');
    const submitText = document.getElementById('btnSubmitText');
    const submitSpinner = document.getElementById('btnSubmitSpinner');

    if (!tokenInput) {
        errBox.innerHTML = '<svg class="cbt-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Token ujian tidak boleh kosong!';
        errBox.style.display = 'flex';
        document.getElementById('inputToken').focus();
        return;
    }

    submitBtn.disabled = true;
    submitText.style.display = 'none';
    submitSpinner.style.display = 'inline-flex';
    errBox.style.display = 'none';

    fetch('../../controllers/SiswaController.php?action=verify_token', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `exam_id=${encodeURIComponent(examId)}&token=${encodeURIComponent(tokenInput)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            document.getElementById('formToken').submit();
        } else {
            errBox.innerHTML = '<svg class="cbt-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> ' + (data.message || 'Token ujian yang Anda masukkan salah!');
            errBox.style.display = 'flex';
            submitBtn.disabled = false;
            submitText.style.display = 'inline-flex';
            submitSpinner.style.display = 'none';
            document.getElementById('inputToken').select();
        }
    })
    .catch(err => {
        document.getElementById('formToken').submit();
    });
}

// Notifikasi error dari redirect parameter
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('error')) {
    const errorType = urlParams.get('error');
    let message = "Terjadi kesalahan";
    let title = "Perhatian";
    let icon = "warning";

    if (errorType === 'unauthorized_class') message = "Anda tidak memiliki akses ke ujian kelas ini!";
    if (errorType === 'no_session_found' || errorType === 'no_session') message = "Sesi ujian belum dibuka oleh admin.";
    if (errorType === 'invalid_token') message = "Token ujian salah atau belum dimasukkan!";
    if (errorType === 'need_token') message = "Silakan klik tombol 'Mulai Ujian' dan masukkan token ujian terlebih dahulu.";
    if (errorType === 'invalid_exam') message = "Data ujian tidak ditemukan!";
    if (errorType === 'exam_expired') {
        title = "Waktu Ujian Telah Lewat";
        message = "Waktu pelaksanaan ujian ini telah berakhir. Anda tidak dapat lagi mengikuti atau membuka ujian ini.";
        icon = "error";
    }
    if (errorType === 'exam_not_started') {
        title = "Ujian Belum Dimulai";
        message = "Ujian belum dimulai! Silakan tunggu hingga jadwal waktu pelaksanaan ujian tiba.";
        icon = "info";
    }

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: title,
            text: message,
            icon: icon,
            confirmButtonColor: '#2563eb'
        });
    } else {
        alert(message);
    }
}
</script>
</body>
</html>