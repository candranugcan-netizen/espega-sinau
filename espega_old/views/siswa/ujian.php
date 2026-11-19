<?php
require_once '../../config/database.php';
require_once '../../config/security.php';
protect_page();

$user_id = $_SESSION['user_id'];
$exam_id_input = $_GET['id'] ?? null; 

if (!$exam_id_input) {
    header("Location: dashboard.php");
    exit;
}

// 1. Cek Status Blokir & Data Profil Siswa
$checkUser = $pdo->prepare("SELECT id, username, nama_lengkap, status_blokir, kelas FROM users WHERE id = ?");
$checkUser->execute([$user_id]);
$user = $checkUser->fetch();

if ($user['status_blokir'] == '1') {
    header("Location: ../../controllers/AuthController.php?logout=1&error=blocked");
    exit;
}

$nama_siswa = !empty($user['nama_lengkap']) ? $user['nama_lengkap'] : ($_SESSION['nama'] ?? 'Siswa');
$kelas_siswa = $user['kelas'] ?? '';

// 2. Ambil Info Sesi, Data Ujian & Mata Pelajaran
$stmtSession = $pdo->prepare("
    SELECT es.id as session_id, es.status_siswa, es.sisa_waktu, e.*, s.nama_mapel 
    FROM exam_sessions es
    JOIN exams e ON es.exam_id = e.id
    LEFT JOIN subjects s ON e.subject_id = s.id
    WHERE es.user_id = ? AND es.exam_id = ? AND e.target_kelas = ?
");
$stmtSession->execute([$user_id, $exam_id_input, $user['kelas']]);
$sessionData = $stmtSession->fetch();

if (!$sessionData) {
    // Periksa apakah ujiannya ada untuk kelas siswa
    $stmtExamOnly = $pdo->prepare("SELECT * FROM exams WHERE id = ? AND target_kelas = ?");
    $stmtExamOnly->execute([$exam_id_input, $user['kelas']]);
    $examOnly = $stmtExamOnly->fetch();
    if ($examOnly) {
        header("Location: dashboard.php?error=need_token");
    } else {
        header("Location: dashboard.php?error=unauthorized_class");
    }
    exit;
}

// 3. Validasi Waktu Ujian
date_default_timezone_set('Asia/Jakarta');
$now = time();
$startTime = strtotime($sessionData['waktu_mulai']);
$endTime = get_exam_end_time($sessionData);

// Jika sisa durasi pengerjaan siswa telah habis:
if (isset($sessionData['sisa_waktu']) && (int)$sessionData['sisa_waktu'] <= 0) {
    finalize_exam_session($pdo, $sessionData['session_id']);
    header("Location: hasil.php?status=auto_submitted");
    exit;
}

if ($now < $startTime) {
    header("Location: dashboard.php?error=exam_not_started");
    exit;
}

if ($sessionData['status_siswa'] === 'selesai') {
    header("Location: hasil.php");
    exit;
}

if ($sessionData['status_siswa'] === 'belum') {
    header("Location: dashboard.php?error=need_token");
    exit;
}

/**
 * Helper SVG Icon Offline-proof
 */
function cbt_icon($name, $extraClass = '', $size = 15) {
    $cls = 'cbt-icon' . ($extraClass ? ' ' . $extraClass : '');
    $icons = [
        'clock' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'expand' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>',
        'compress' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="14" y1="10" x2="21" y2="3"/><line x1="3" y1="21" x2="10" y2="14"/></svg>',
        'grid' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
        'book' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
        'user' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'check' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
        'chevron-left' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>',
        'chevron-right' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>',
        'lock' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
        'zoom' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>',
        'alert-triangle' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        'type' => '<svg class="'.$cls.'" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>'
    ];
    return $icons[$name] ?? '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>CBT Sinau - <?= htmlspecialchars($sessionData['nama_ujian']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <!-- Font Inter & Fallback Native System Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #eff6ff;
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

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body { 
            background: #f1f5f9; 
            color: var(--slate-900);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        .cbt-icon {
            display: inline-block;
            vertical-align: middle;
            flex-shrink: 0;
        }

        /* ===== TOP APP BAR (STICKY, BERSIH, MODERN) ===== */
        .cbt-navbar {
            background-color: var(--white);
            border-bottom: 1px solid var(--slate-200);
            position: sticky;
            top: 0;
            z-index: 100;
            width: 100%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }

        .cbt-navbar-inner {
            max-width: 1300px;
            margin: 0 auto;
            padding: 8px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .exam-info-brand {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .exam-info-title {
            font-size: 0.92rem;
            font-weight: 800;
            color: var(--slate-900);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.2;
        }

        .exam-info-meta {
            font-size: 0.72rem;
            color: var(--slate-500);
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .mapel-pill {
            background-color: var(--primary-light);
            color: var(--primary-dark);
            padding: 1px 6px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 0.68rem;
        }

        /* ===== TIMER BADGE (ALWAYS VISIBLE & HIGH IMPACT) ===== */
        .cbt-timer-badge {
            background-color: var(--slate-900);
            color: var(--white);
            padding: 6px 14px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-variant-numeric: tabular-nums;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            transition: background-color 0.2s ease;
        }

        .cbt-timer-badge.timer-warning {
            background-color: var(--warning);
            color: var(--white);
        }

        .cbt-timer-badge.timer-danger {
            background-color: var(--danger);
            color: var(--white);
            animation: pulseAlert 1.5s infinite;
        }

        @keyframes pulseAlert {
            0% { transform: scale(1); }
            50% { transform: scale(1.04); }
            100% { transform: scale(1); }
        }

        .timer-time-text {
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        /* Tombol Ukuran Huruf (A- / A / A+) */
        .font-adjust-group {
            display: inline-flex;
            background-color: var(--slate-100);
            border: 1px solid var(--slate-300);
            border-radius: var(--radius-sm);
            padding: 2px;
            gap: 2px;
        }

        .btn-font-size {
            background: transparent;
            border: none;
            padding: 4px 7px;
            border-radius: 4px;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--slate-700);
            cursor: pointer;
            line-height: 1;
        }

        .btn-font-size.active {
            background-color: var(--white);
            color: var(--primary);
            box-shadow: 0 1px 2px rgba(0,0,0,0.06);
        }

        /* Tombol Fullscreen & Daftar Soal */
        .btn-action-pill {
            background-color: var(--slate-100);
            border: 1px solid var(--slate-300);
            color: var(--slate-700);
            padding: 6px 10px;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.15s ease;
        }

        .btn-action-pill:hover {
            background-color: var(--slate-200);
        }

        .btn-open-grid-mobile {
            display: none;
            background-color: var(--primary-light);
            border: 1px solid #bfdbfe;
            color: var(--primary-dark);
        }

        /* ===== BANNER PERINGATAN FULLSCREEN BELUM AKTIF ===== */
        .fs-alert-banner {
            background-color: var(--danger-light);
            border-bottom: 1px solid #fecaca;
            color: #991b1b;
            padding: 8px 14px;
            display: none;
            justify-content: center;
            align-items: center;
            gap: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
        }

        .fs-alert-banner button {
            background-color: var(--danger);
            color: var(--white);
            border: none;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.72rem;
            font-weight: 700;
            cursor: pointer;
        }

        /* ===== LAYOUT UTAMA (DESKTOP & HP) ===== */
        .cbt-container {
            max-width: 1300px;
            margin: 12px auto;
            padding: 0 14px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 310px;
            gap: 16px;
            align-items: start;
            flex: 1;
        }

        /* ===== KARTU SOAL UTAMA ===== */
        .question-card {
            background-color: var(--white);
            border: 1px solid var(--slate-200);
            border-radius: var(--radius-md);
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-width: 0;
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--slate-100);
            padding-bottom: 12px;
            gap: 10px;
        }

        .question-num-tag {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--slate-900);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .question-status-badge {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-unanswered {
            background-color: var(--slate-100);
            color: var(--slate-600);
            border: 1px solid var(--slate-300);
        }

        .status-answered {
            background-color: var(--success-light);
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        /* AREA TEKS PERTANYAAN */
        .question-content {
            color: var(--slate-800);
            line-height: 1.7;
            word-break: break-word;
        }

        .font-normal .question-content,
        .font-normal .option-text {
            font-size: 1rem;
        }

        .font-medium .question-content,
        .font-medium .option-text {
            font-size: 1.15rem;
            line-height: 1.8;
        }

        .font-large .question-content,
        .font-large .option-text {
            font-size: 1.3rem;
            line-height: 1.9;
        }

        .question-image-wrapper {
            margin: 12px 0;
            cursor: pointer;
            position: relative;
            display: inline-block;
            max-width: 100%;
        }

        .question-image {
            max-width: 100%;
            height: auto;
            border-radius: var(--radius-sm);
            border: 1px solid var(--slate-200);
            display: block;
            background-color: var(--slate-50);
        }

        .image-zoom-hint {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background-color: rgba(15, 23, 42, 0.75);
            color: var(--white);
            font-size: 0.68rem;
            font-weight: 600;
            padding: 3px 7px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
            pointer-events: none;
        }

        /* AREA PILIHAN JAWABAN (A, B, C, D, E) */
        .options-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 6px;
        }

        .option-card {
            border: 2px solid var(--slate-200);
            background-color: var(--white);
            border-radius: var(--radius-sm);
            padding: 12px 14px;
            cursor: pointer;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            transition: border-color 0.15s ease, background-color 0.15s ease;
            position: relative;
        }

        .option-card:hover {
            border-color: var(--slate-300);
            background-color: var(--slate-50);
        }

        .option-card.selected {
            border-color: var(--primary);
            background-color: var(--primary-light);
        }

        .option-badge {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--slate-100);
            color: var(--slate-700);
            border: 1px solid var(--slate-300);
            font-size: 0.88rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.15s ease;
        }

        .option-card.selected .option-badge {
            background-color: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }

        .option-body {
            flex: 1;
            min-width: 0;
            padding-top: 4px;
        }

        .option-text {
            color: var(--slate-800);
            word-break: break-word;
        }

        .option-img {
            max-width: 220px;
            width: 100%;
            height: auto;
            border-radius: 4px;
            border: 1px solid var(--slate-200);
            margin-top: 8px;
            display: block;
            background-color: var(--white);
        }

        /* Sembunyikan radio bawaan, interaksi berbasis card */
        .option-card input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        /* ===== BOTTOM TOOLBAR AKSI SOAL ===== */
        .question-actions-bar {
            border-top: 1px solid var(--slate-200);
            padding-top: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            width: 100%;
        }

        .btn-cbt {
            padding: 10px 18px;
            border-radius: var(--radius-sm);
            border: none;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background-color 0.15s ease;
        }

        .btn-cbt-prev {
            background-color: var(--slate-200);
            color: var(--slate-700);
        }
        .btn-cbt-prev:hover {
            background-color: var(--slate-300);
        }

        .btn-cbt-next {
            background-color: var(--primary);
            color: var(--white);
        }
        .btn-cbt-next:hover {
            background-color: var(--primary-dark);
        }

        .btn-cbt-finish {
            background-color: var(--success);
            color: var(--white);
        }
        .btn-cbt-finish:hover {
            background-color: #059669;
        }

        .finish-locked-box {
            display: none;
            text-align: center;
        }

        .btn-cbt-locked {
            background-color: var(--slate-200);
            color: var(--slate-500);
            cursor: not-allowed;
            border: 1px solid var(--slate-300);
        }

        /* ===== SIDEBAR NAVIGASI SOAL (DESKTOP) ===== */
        .sidebar-panel {
            background-color: var(--white);
            border: 1px solid var(--slate-200);
            border-radius: var(--radius-md);
            padding: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            gap: 14px;
            position: sticky;
            top: 68px;
        }

        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--slate-200);
            padding-bottom: 10px;
        }

        .sidebar-header h3 {
            font-size: 0.92rem;
            font-weight: 800;
            color: var(--slate-900);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .progress-count {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--primary);
            background-color: var(--primary-light);
            padding: 2px 8px;
            border-radius: 12px;
        }

        /* GRID NOMOR SOAL */
        .nav-numbers-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 7px;
            max-height: 380px;
            overflow-y: auto;
            padding-right: 2px;
        }

        .nav-num-btn {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            border: 1px solid var(--slate-200);
            background-color: var(--slate-50);
            color: var(--slate-700);
            transition: all 0.12s ease;
        }

        .nav-num-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .nav-num-btn.answered {
            background-color: var(--success);
            color: var(--white);
            border-color: var(--success);
        }

        .nav-num-btn.active {
            background-color: var(--primary);
            color: var(--white);
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.3);
        }

        .nav-num-btn.answered.active {
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.4);
        }

        /* KETERANGAN WARNA (LEGEND) */
        .nav-legend {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--slate-600);
            border-top: 1px solid var(--slate-100);
            padding-top: 10px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .legend-box {
            width: 12px;
            height: 12px;
            border-radius: 3px;
        }

        .legend-box.box-active { background-color: var(--primary); }
        .legend-box.box-done { background-color: var(--success); }
        .legend-box.box-empty { background-color: var(--slate-200); border: 1px solid var(--slate-300); }

        /* ===== MODAL DRAWER NAVIGASI SOAL UNTUK HP ===== */
        .drawer-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(15, 23, 42, 0.65);
            z-index: 999;
            display: none;
        }

        .drawer-content {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            max-height: 80vh;
            background-color: var(--white);
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
            z-index: 1000;
            padding: 18px 16px;
            display: none;
            flex-direction: column;
            gap: 14px;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
            animation: slideUp 0.25s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }

        .drawer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--slate-200);
            padding-bottom: 10px;
        }

        .drawer-close {
            background: transparent;
            border: none;
            font-size: 1.4rem;
            color: var(--slate-400);
            cursor: pointer;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ===== LIGHTBOX MODAL ZOOM GAMBAR ===== */
        .lightbox-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(15, 23, 42, 0.88);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .lightbox-img {
            max-width: 95%;
            max-height: 90vh;
            border-radius: var(--radius-sm);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            object-fit: contain;
            background-color: var(--white);
        }

        .lightbox-close {
            position: absolute;
            top: 14px;
            right: 14px;
            background-color: rgba(255,255,255,0.2);
            color: var(--white);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        /* ===== KEYBOARD HINT FOOTER ===== */
        .keyboard-hint-bar {
            text-align: center;
            font-size: 0.72rem;
            color: var(--slate-400);
            padding: 8px 14px 14px;
        }

        .kbd-key {
            background-color: var(--white);
            border: 1px solid var(--slate-300);
            padding: 1px 5px;
            border-radius: 3px;
            font-family: monospace;
            font-weight: 700;
            color: var(--slate-600);
        }

        /* ===== RESPONSIVE UNTUK HP / TABLET ===== */
        @media (max-width: 820px) {
            body {
                background-color: var(--slate-50);
            }

            .cbt-container {
                grid-template-columns: 1fr;
                margin-top: 8px;
                padding: 0 8px;
            }

            .sidebar-panel {
                display: none; /* Sembunyikan sidebar kanan di HP, gunakan Drawer */
            }

            .btn-open-grid-mobile {
                display: inline-flex;
            }

            .exam-info-meta {
                display: none;
            }

            .exam-info-title {
                max-width: 140px;
            }

            .question-card {
                padding: 14px;
                border-radius: 10px;
            }

            .cbt-timer-badge {
                padding: 5px 10px;
            }

            .timer-time-text {
                font-size: 0.9rem;
            }

            .nav-numbers-grid {
                grid-template-columns: repeat(6, 1fr);
                max-height: 50vh;
            }

            .keyboard-hint-bar {
                display: none;
            }
        }
    </style>
</head>
<body oncontextmenu="return false" oncopy="return false" oncut="return false" onpaste="return false">

<!-- APP BAR / NAVBAR -->
<header class="cbt-navbar">
    <div class="cbt-navbar-inner">
        <!-- Sisi Kiri: Judul Ujian & Mapel -->
        <div class="nav-left">
            <div class="exam-info-brand">
                <span class="exam-info-title" title="<?= htmlspecialchars($sessionData['nama_ujian']) ?>">
                    <?= htmlspecialchars($sessionData['nama_ujian']) ?>
                </span>
                <div class="exam-info-meta">
                    <?php if (!empty($sessionData['nama_mapel'])): ?>
                        <span class="mapel-pill"><?= htmlspecialchars($sessionData['nama_mapel']) ?></span>
                    <?php endif; ?>
                    <span>• <?= htmlspecialchars($nama_siswa) ?> (<?= htmlspecialchars($kelas_siswa) ?>)</span>
                </div>
            </div>
        </div>

        <!-- Tengah: Timer Countdown (Pusat Perhatian Siswa) -->
        <div class="cbt-timer-badge" id="cbtTimerBadge" title="Sisa Waktu Ujian">
            <?= cbt_icon('clock', '', 15) ?>
            <span class="timer-time-text" id="timer">--:--:--</span>
        </div>

        <!-- Sisi Kanan: Pengatur Font, Tombol Drawer Soal (HP), Tombol Fullscreen -->
        <div class="nav-right">
            <!-- Pengatur Ukuran Teks Soal -->
            <div class="font-adjust-group" title="Ukuran Tulisan">
                <button type="button" class="btn-font-size active" onclick="setFontSize('normal', this)">A</button>
                <button type="button" class="btn-font-size" onclick="setFontSize('medium', this)">A+</button>
                <button type="button" class="btn-font-size" onclick="setFontSize('large', this)">A++</button>
            </div>

            <!-- Tombol Buka Daftar Soal di Layar HP -->
            <button type="button" class="btn-action-pill btn-open-grid-mobile" onclick="toggleDrawer(true)" title="Daftar Soal">
                <?= cbt_icon('grid', '', 14) ?>
                <span id="mobileNavLabel">Soal</span>
            </button>

            <!-- Tombol Fullscreen -->
            <button type="button" class="btn-action-pill" id="btnNavbarFs" onclick="toggleFullscreen()" title="Layar Penuh">
                <span id="fsIcon"><?= cbt_icon('expand', '', 14) ?></span>
                <span id="fsText" style="display:none;">Layar Penuh</span>
            </button>
        </div>
    </div>
</header>

<!-- BANNER PERINGATAN FULLSCREEN BELUM AKTIF ATAU KELUAR -->
<div id="fsAlertBanner" class="fs-alert-banner" onclick="enterFullscreen()">
    <span><?= cbt_icon('alert-triangle', '', 15) ?> Mode Layar Penuh belum aktif atau keluar. <strong>Klik di sini</strong> untuk mengaktifkan kembali.</span>
    <button type="button">Layar Penuh</button>
</div>

<!-- CONTAINER UTAMA -->
<main class="cbt-container font-normal" id="cbtMainContainer">

    <!-- KOLOM KIRI: KARTU SOAL & OPSI JAWABAN -->
    <section class="question-card">
        <!-- Header Soal (Nomor & Status Terjawab) -->
        <div class="question-header">
            <div class="question-num-tag">
                <span id="questionNumText">Soal No. 1</span>
            </div>
            <div id="questionStatusBadge" class="question-status-badge status-unanswered">
                Belum Dijawab
            </div>
        </div>

        <!-- Area Teks Soal & Gambar -->
        <div id="soal-container">
            <p style="text-align: center; color: var(--slate-400); padding: 40px 0;">Memuat soal ujian...</p>
        </div>

        <!-- Toolbar Tombol Aksi di Bawah Soal -->
        <div class="question-actions-bar">
            <button type="button" onclick="prevSoal()" id="btnPrevSoal" class="btn-cbt btn-cbt-prev">
                <?= cbt_icon('chevron-left', '', 15) ?>
                <span>Sebelumnya</span>
            </button>

            <!-- Kotak Tombol Selesai Terkunci (Jika sisa waktu > 15 menit) -->
            <div id="finish-locked-container" class="finish-locked-box">
                <button type="button" class="btn-cbt btn-cbt-locked" disabled title="Terkunci hingga 15 menit terakhir">
                    <?= cbt_icon('lock', '', 14) ?>
                    <span>Selesaikan (Terkunci)</span>
                </button>
            </div>

            <!-- Tombol Selesaikan Ujian Aktif -->
            <div id="finish-btn-container" style="display:none;">
                <button type="button" onclick="finishExam()" class="btn-cbt btn-cbt-finish">
                    <?= cbt_icon('check', '', 15) ?>
                    <span>Selesaikan Ujian</span>
                </button>
            </div>

            <button type="button" onclick="nextSoal()" id="next-btn" class="btn-cbt btn-cbt-next">
                <span>Selanjutnya</span>
                <?= cbt_icon('chevron-right', '', 15) ?>
            </button>
        </div>
    </section>

    <!-- KOLOM KANAN: SIDEBAR NAVIGASI NOMOR SOAL (DESKTOP) -->
    <aside class="sidebar-panel">
        <div class="sidebar-header">
            <h3>
                <?= cbt_icon('grid', '', 15) ?>
                <span>Nomor Soal</span>
            </h3>
            <span class="progress-count" id="progressCountLabel">0 / 0</span>
        </div>

        <!-- Grid Tombol Nomor -->
        <div class="nav-numbers-grid" id="nav-grid"></div>

        <!-- Keterangan Legend Warna -->
        <div class="nav-legend">
            <div class="legend-item">
                <span class="legend-box box-active"></span>
                <span>Sedang Dibuka</span>
            </div>
            <div class="legend-item">
                <span class="legend-box box-done"></span>
                <span>Sudah Dijawab</span>
            </div>
            <div class="legend-item">
                <span class="legend-box box-empty"></span>
                <span>Belum Dijawab</span>
            </div>
        </div>
    </aside>

</main>

<!-- PETUNJUK TOMBOL CEPAT KEYBOARD DI DESKTOP -->
<footer class="keyboard-hint-bar">
    Petunjuk Keyboard: Tekan <span class="kbd-key">A</span> - <span class="kbd-key">E</span> untuk memilih jawaban • <span class="kbd-key">&larr;</span> Sebelumnya • <span class="kbd-key">&rarr;</span> Selanjutnya
</footer>

<!-- BOTTOM DRAWER DAFTAR SOAL KHUSUS MOBILE (HP) -->
<div class="drawer-overlay" id="drawerOverlay" onclick="toggleDrawer(false)"></div>
<div class="drawer-content" id="drawerContent">
    <div class="drawer-header">
        <div style="display:flex; align-items:center; gap:8px;">
            <?= cbt_icon('grid', '', 16) ?>
            <strong style="color:var(--slate-900); font-size:0.95rem;">Daftar Nomor Soal</strong>
            <span class="progress-count" id="mobileDrawerCount">0 / 0</span>
        </div>
        <button type="button" class="drawer-close" onclick="toggleDrawer(false)">&times;</button>
    </div>
    <div class="nav-numbers-grid" id="mobile-nav-grid"></div>
    <div class="nav-legend">
        <div class="legend-item"><span class="legend-box box-active"></span><span>Sedang Dibuka</span></div>
        <div class="legend-item"><span class="legend-box box-done"></span><span>Sudah Dijawab</span></div>
        <div class="legend-item"><span class="legend-box box-empty"></span><span>Belum Dijawab</span></div>
    </div>
</div>

<!-- LIGHTBOX MODAL UNTUK PERBESAR GAMBAR SOAL / PILIHAN -->
<div class="lightbox-modal" id="imageLightbox" onclick="closeLightbox()">
    <button type="button" class="lightbox-close" onclick="closeLightbox()">&times;</button>
    <img src="" id="lightboxImg" class="lightbox-img" alt="Perbesar Gambar" onclick="event.stopPropagation()">
</div>

<script>
// ===== LOGIKA FULLSCREEN OTOMATIS (DESKTOP & MOBILE) =====
const SVG_ICON_EXPAND = `<svg class="cbt-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>`;
const SVG_ICON_COMPRESS = `<svg class="cbt-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="14" y1="10" x2="21" y2="3"/><line x1="3" y1="21" x2="10" y2="14"/></svg>`;

function isFullscreenActive() {
    return !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
}

let isEnteringFs = false;

function enterFullscreen() {
    const el = document.documentElement;
    const requestFs = el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen || el.msRequestFullscreen;
    
    if (requestFs && !isFullscreenActive()) {
        isEnteringFs = true;
        try {
            const promise = requestFs.call(el);
            if (promise && promise.catch) {
                promise.catch(() => {});
            }
        } catch(e) {}
        setTimeout(() => { isEnteringFs = false; }, 800);
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
    const fsIcon = document.getElementById('fsIcon');
    const fsBanner = document.getElementById('fsAlertBanner');

    if (fsIcon) {
        fsIcon.innerHTML = isFs ? SVG_ICON_COMPRESS : SVG_ICON_EXPAND;
    }

    if (fsBanner) {
        fsBanner.style.display = isFs ? 'none' : 'flex';
    }
}

// 1. Coba aktifkan fullscreen saat halaman dimuat
window.addEventListener('load', () => {
    enterFullscreen();
    updateFullscreenUI();
});

// 2. Otomatis masuk fullscreen pada klik / sentuhan pertama di layar
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

// ===== PENGATUR UKURAN FONT (A / A+ / A++) =====
function setFontSize(size, btn) {
    const container = document.getElementById('cbtMainContainer');
    container.classList.remove('font-normal', 'font-medium', 'font-large');
    container.classList.add('font-' + size);

    document.querySelectorAll('.btn-font-size').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
}

// ===== PENGELOLA DRAWER DAFTAR SOAL (HP) =====
function toggleDrawer(open) {
    const overlay = document.getElementById('drawerOverlay');
    const drawer = document.getElementById('drawerContent');
    if (open) {
        overlay.style.display = 'block';
        drawer.style.display = 'flex';
    } else {
        overlay.style.display = 'none';
        drawer.style.display = 'none';
    }
}

// ===== LIGHTBOX PERBESAR GAMBAR =====
function openLightbox(imgSrc) {
    const modal = document.getElementById('imageLightbox');
    const img = document.getElementById('lightboxImg');
    img.src = imgSrc;
    modal.style.display = 'flex';
}

function closeLightbox() {
    document.getElementById('imageLightbox').style.display = 'none';
}

// ===== VARIABEL DATA UJIAN =====
let questions = [];
let currentIndex = 0;
let answers = {};
let sessionId = <?= (int)$sessionData['session_id'] ?>;
<?php
$sisaWaktuSiswa = ($sessionData['sisa_waktu'] !== null) ? (int)$sessionData['sisa_waktu'] : ((int)$sessionData['durasi'] * 60);
$effectiveExamDuration = max(0, $sisaWaktuSiswa);
?>
let examDuration = <?= (int)$effectiveExamDuration ?>;
let serverTimeMs = <?= $now * 1000 ?>;
let clientServerOffset = Date.now() - serverTimeMs;

// FLAG PENTING: Mencegah trigger anti-cheat saat sistem memunculkan pop-up konfirmasi/loading
let isFinishing = false; 

$(document).ready(function() {
    // Cek jika durasi sudah habis sejak awal halaman dimuat
    if (examDuration <= 0) {
        finishExam(true);
        return;
    }

    // Ambil daftar soal ujian via AJAX
    $.getJSON(`../../controllers/SiswaController.php?action=get_soal&exam_id=<?= $exam_id_input ?>&subject_id=<?= $sessionData['subject_id'] ?>&session_id=${sessionId}`, function(data) {
        if (!data || data.error) {
            Swal.fire('Perhatian', data ? data.error : 'Gagal memuat soal ujian.', 'warning');
            return;
        }

        questions = data;
        questions.forEach(q => {
            if (q.jawaban_siswa) {
                answers[q.id] = q.jawaban_siswa.toUpperCase();
            }
        });

        renderSoal();
        renderNav();
        startTimer();
    }).fail(function() {
        $('#soal-container').html('<p style="text-align:center; color:#ef4444; padding:30px;">Gagal terhubung ke server. Periksa jaringan Anda dan muat ulang halaman.</p>');
    });

    // Heartbeat timer ke database setiap 10 detik
    setInterval(function() {
        if (examDuration > 0 && !isFinishing) {
            $.post('../../controllers/SiswaController.php?action=update_timer', {
                session_id: sessionId,
                sisa_waktu: examDuration
            });
        }
    }, 10000); 

    // DETEKSI KECURANGAN (Tab Switch, Split Screen, Floating Window)
    function handleCheat() {
        if (isFinishing || isEnteringFs) return; // Abaikan saat transisi submit atau fullscreen
        isFinishing = true; // Kunci layar
        
        $.ajax({
            url: '../../controllers/SiswaController.php?action=log_pelanggaran',
            type: 'POST',
            data: { session_id: sessionId },
            async: false,
            success: function() {
                Swal.fire({
                    title: 'AKUN DIBLOKIR!',
                    text: 'Terdeteksi keluar dari halaman ujian atau membuka aplikasi lain!',
                    icon: 'error',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    confirmButtonText: 'Logout'
                }).then(() => {
                    window.location.href = "../../controllers/AuthController.php?logout=1";
                });
            }
        });
    }

    // 1. Deteksi ganti tab / minimize
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) handleCheat();
    });

    // 2. Deteksi kehilangan fokus (Split screen, Floating App, Notifikasi ditarik)
    window.addEventListener('blur', function() {
        handleCheat();
    });
});

// ===== RENDER TAMPILAN SOAL & OPSI JAWABAN =====
function renderSoal() {
    if (questions.length === 0) return;
    const q = questions[currentIndex];
    const totalQ = questions.length;

    // Perbarui judul nomor
    document.getElementById('questionNumText').textContent = `Soal No. ${currentIndex + 1} dari ${totalQ}`;

    // Perbarui status terjawab
    const statusBadge = document.getElementById('questionStatusBadge');
    if (answers[q.id]) {
        statusBadge.className = 'question-status-badge status-answered';
        statusBadge.innerHTML = '✓ Sudah Dijawab (' + answers[q.id] + ')';
    } else {
        statusBadge.className = 'question-status-badge status-unanswered';
        statusBadge.textContent = 'Belum Dijawab';
    }

    // Tombol Sebelumnya di-disable jika berada di nomor 1
    const btnPrev = document.getElementById('btnPrevSoal');
    if (btnPrev) {
        btnPrev.disabled = (currentIndex === 0);
        btnPrev.style.opacity = (currentIndex === 0) ? '0.45' : '1';
        btnPrev.style.cursor = (currentIndex === 0) ? 'not-allowed' : 'pointer';
    }

    let html = `
        <div class="question-content">
            <div>${q.pertanyaan}</div>
            ${q.img_pertanyaan ? `
                <div class="question-image-wrapper" onclick="openLightbox('../../assets/img/soal/${q.img_pertanyaan}')" title="Klik untuk memperbesar">
                    <img src="../../assets/img/soal/${q.img_pertanyaan}" class="question-image" alt="Gambar Soal ${currentIndex + 1}">
                    <div class="image-zoom-hint">
                        <?= cbt_icon('zoom', '', 12) ?> Perbesar
                    </div>
                </div>
            ` : ''}
        </div>

        <div class="options-list">
    `;

    ['A', 'B', 'C', 'D', 'E'].forEach(opt => {
        const key = opt.toLowerCase();
        const teksPilihan = q['pil_' + key];
        const gbrPilihan = q['img_' + key];
        const isChecked = (answers[q.id] === opt);
        
        if (teksPilihan || gbrPilihan) {
            html += `
                <div class="option-card ${isChecked ? 'selected' : ''}" onclick="selectOption(${q.id}, '${opt}')">
                    <input type="radio" name="jawaban" value="${opt}" id="opt_${opt}" ${isChecked ? 'checked' : ''}>
                    <div class="option-badge">${opt}</div>
                    <div class="option-body">
                        ${teksPilihan ? `<div class="option-text">${teksPilihan}</div>` : ''}
                        ${gbrPilihan ? `
                            <img src="../../assets/img/soal/${gbrPilihan}" 
                                 class="option-img" 
                                 alt="Pilihan ${opt}" 
                                 onclick="event.stopPropagation(); openLightbox('../../assets/img/soal/${gbrPilihan}')"
                                 title="Klik untuk memperbesar">
                        ` : ''}
                    </div>
                </div>
            `;
        }
    });

    html += `</div>`;
    $('#soal-container').html(html);

    updateNavButtons();
}

function selectOption(soalId, opt) {
    saveAnswer(soalId, opt);
}

function saveAnswer(soalId, jawaban) {
    answers[soalId] = jawaban;
    
    // Perbarui status pill dan kartu opsi aktif
    const statusBadge = document.getElementById('questionStatusBadge');
    if (statusBadge) {
        statusBadge.className = 'question-status-badge status-answered';
        statusBadge.innerHTML = '✓ Sudah Dijawab (' + jawaban + ')';
    }

    $('.option-card').removeClass('selected');
    $(`input[value="${jawaban}"]`).prop('checked', true).closest('.option-card').addClass('selected');

    renderNav();

    // Simpan ke database via AJAX
    $.post('../../controllers/SiswaController.php?action=simpan_jawaban', {
        session_id: sessionId,
        soal_id: soalId,
        jawaban: jawaban,
        sisa_waktu: examDuration 
    });
}

// ===== RENDER TOMBOL GRID NAVIGASI (DESKTOP & MOBILE) =====
function renderNav() {
    let html = '';
    let answeredCount = 0;

    questions.forEach((q, i) => {
        const isAnswered = !!answers[q.id];
        if (isAnswered) answeredCount++;

        let cls = '';
        if (i === currentIndex) {
            cls = 'active';
            if (isAnswered) cls += ' answered';
        } else if (isAnswered) {
            cls = 'answered';
        }

        html += `<button type="button" class="nav-num-btn ${cls}" onclick="goTo(${i})">${i + 1}</button>`;
    });

    $('#nav-grid').html(html);
    $('#mobile-nav-grid').html(html);

    // Perbarui label counter progress
    const totalQ = questions.length;
    const countText = `${answeredCount} / ${totalQ}`;
    const progressEl = document.getElementById('progressCountLabel');
    if (progressEl) progressEl.textContent = countText;

    const mobileCountEl = document.getElementById('mobileDrawerCount');
    if (mobileCountEl) mobileCountEl.textContent = countText;

    const mobileLabel = document.getElementById('mobileNavLabel');
    if (mobileLabel) mobileLabel.textContent = `Soal (${countText})`;
}

function goTo(i) {
    currentIndex = i;
    renderSoal();
    renderNav();
    toggleDrawer(false); // Tutup drawer jika diakses dari mobile
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function nextSoal() {
    if (currentIndex < questions.length - 1) {
        goTo(currentIndex + 1);
    }
}

function prevSoal() {
    if (currentIndex > 0) {
        goTo(currentIndex - 1);
    }
}

// ===== TOMBOL AKSI SELESAI / SELANJUTNYA =====
function updateNavButtons() {
    if (questions.length === 0) return;

    if (currentIndex === questions.length - 1) {
        $('#next-btn').hide();
        // Tombol submit hanya muncul jika sisa waktu <= 15 menit (900 detik)
        if (examDuration <= 900) {
            $('#finish-locked-container').hide();
            $('#finish-btn-container').show();
        } else {
            $('#finish-btn-container').hide();
            $('#finish-locked-container').show();
        }
    } else {
        $('#next-btn').show();
        $('#finish-btn-container').hide();
        $('#finish-locked-container').hide();
    }
}

// ===== HITUNG MUNDUR WAKTU UJIAN (TIMER) =====
function startTimer() {
    const timerBadge = document.getElementById('cbtTimerBadge');

    let timerInterval = setInterval(() => {
        // Auto-submit jika waktu durasi siswa habis
        if (examDuration <= 0) {
            clearInterval(timerInterval);
            examDuration = 0;
            $('#timer').text('00:00:00');
            finishExam(true); 
            return;
        }

        let h = Math.floor(examDuration / 3600);
        let m = Math.floor((examDuration % 3600) / 60);
        let s = examDuration % 60;
        $('#timer').text(`${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`);
        
        // Peringatan visual warna timer saat mendekati batas
        if (timerBadge) {
            if (examDuration <= 300) {
                timerBadge.className = 'cbt-timer-badge timer-danger';
            } else if (examDuration <= 900) {
                timerBadge.className = 'cbt-timer-badge timer-warning';
            } else {
                timerBadge.className = 'cbt-timer-badge';
            }
        }

        examDuration--;

        // Perbarui tampilan tombol submit jika sedang di nomor terakhir
        if (currentIndex === questions.length - 1) {
            updateNavButtons();
        }
    }, 1000);
}

// ===== PENYELESAIAN UJIAN (SUBMIT) =====
function finishExam(auto = false) {
    // Cegah submit manual sebelum sisa waktu tinggal 15 menit (900 detik), kecuali jika auto submit
    if (!auto && examDuration > 900) {
        let sisaMenit = Math.ceil((examDuration - 900) / 60);
        Swal.fire({
            title: 'Ujian Belum Dapat Disubmit',
            text: `Tombol selesai baru dapat digunakan ketika sisa waktu ujian &le; 15 menit (sekitar ${sisaMenit} menit lagi).`,
            icon: 'info',
            confirmButtonColor: '#2563eb'
        });
        return;
    }

    if (isFinishing && !auto) return; // Cegah double klik
    
    const action = () => {
        isFinishing = true; // Suspend anti-cheat saat proses loading
        
        Swal.fire({
            title: auto ? 'Waktu Ujian Telah Habis!' : 'Menyimpan Jawaban...',
            text: auto ? 'Sistem sedang otomatis merekap dan mengumpulkan hasil ujian Anda.' : 'Mohon tunggu, sistem sedang merekap hasil ujian Anda.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Eksekusi selesai ujian ke backend
        $.ajax({
            url: '../../controllers/SiswaController.php?action=selesai_ujian',
            type: 'POST',
            data: { 
                session_id: sessionId,
                sisa_waktu: examDuration,
                auto: auto ? 1 : 0
            },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    window.location.href = "hasil.php?status=" + (auto ? 'auto_submitted' : 'success');
                } else {
                    if (auto) {
                        window.location.href = "hasil.php?status=auto_submitted";
                    } else {
                        Swal.fire({
                            title: 'Pemberitahuan',
                            text: res.message || 'Gagal memproses ujian.',
                            icon: 'error'
                        });
                        isFinishing = false;
                    }
                }
            },
            error: function() {
                if (auto) {
                    window.location.href = "hasil.php?status=auto_submitted";
                } else {
                    Swal.fire('Koneksi Terputus', 'Gagal memproses data ke server. Pastikan Anda terhubung ke jaringan dan coba kembali.', 'error');
                    isFinishing = false;
                }
            }
        });
    };

    if (auto) { 
        action(); 
        return; 
    }

    // Hitung soal yang belum dijawab untuk konfirmasi yang jelas
    let totalUnanswered = 0;
    questions.forEach(q => {
        if (!answers[q.id]) totalUnanswered++;
    });

    let confirmMsg = "Apakah Anda yakin ingin menyelesaikan ujian ini?";
    if (totalUnanswered > 0) {
        confirmMsg = `Masih ada ${totalUnanswered} soal yang belum dijawab. Yakin ingin menyelesaikan ujian sekarang?`;
    }

    isFinishing = true; // Suspend anti-cheat saat modal SweetAlert muncul
    Swal.fire({
        title: 'Konfirmasi Selesai',
        text: confirmMsg,
        icon: totalUnanswered > 0 ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Selesaikan Ujian!',
        cancelButtonText: 'Cek Kembali Soal',
        allowOutsideClick: false
    }).then((result) => {
        if (result.isConfirmed) {
            action();
        } else {
            isFinishing = false; // Aktifkan lagi anti-cheat jika siswa menekan "Batal"
        }
    });
}

// ===== PINTASAN KEYBOARD (DESKTOP POWER USABILITY) =====
document.addEventListener('keydown', function(e) {
    // Abaikan jika modal sedang terbuka
    if (document.getElementById('imageLightbox').style.display === 'flex') {
        if (e.key === 'Escape') closeLightbox();
        return;
    }

    // Navigasi kiri / kanan
    if (e.key === 'ArrowLeft') {
        prevSoal();
    } else if (e.key === 'ArrowRight') {
        nextSoal();
    }

    // Pemilihan opsi A - E
    const key = e.key.toUpperCase();
    if (['A', 'B', 'C', 'D', 'E'].includes(key) && questions[currentIndex]) {
        const q = questions[currentIndex];
        const optKey = key.toLowerCase();
        if (q['pil_' + optKey] || q['img_' + optKey]) {
            selectOption(q.id, key);
        }
    }
});
</script>
</body>
</html>