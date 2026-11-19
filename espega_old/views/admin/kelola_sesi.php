<?php 
require_once '../../config/database.php';
require_once '../../config/security.php';
protect_page();

// Sinkronisasi zona waktu dengan WIB
date_default_timezone_set('Asia/Jakarta');

$active_nav = 'sesi';
$page_title = 'Monitoring Sesi Ujian';

// 1. Tangkap Parameter Pencarian & Filter
$search = trim($_GET['q'] ?? '');
$filter_exam = trim($_GET['exam_id'] ?? '');
$filter_kelas = trim($_GET['kelas'] ?? '');
$filter_status = trim($_GET['status_siswa'] ?? ''); // 'all', 'pengerjaan', 'selesai', 'belum'

// 2. Ambil daftar kelas unik siswa
$classes = $pdo->query("SELECT DISTINCT kelas FROM users WHERE role_id = 3 AND kelas IS NOT NULL AND TRIM(kelas) != '' ORDER BY kelas ASC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($classes)) {
    $classes = ['7A', '7B', '7C', '7D', '8A', '8B', '8C', '8D', '9A', '9B', '9C', '9D'];
}

// Jalankan auto-finalize untuk sesi ujian yang telah melewati jam akhir atau waktu habis
auto_finalize_expired_sessions($pdo);

// 3. Ambil data Ujian lengkap dengan nama mapel untuk dropdown dan modal
$exams = $pdo->query("
    SELECT e.id, e.nama_ujian, e.target_kelas, e.durasi, e.waktu_mulai, e.waktu_selesai, e.token_ujian, s.nama_mapel 
    FROM exams e 
    JOIN subjects s ON e.subject_id = s.id 
    ORDER BY e.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// 4. Query Seluruh Data Sesi Ujian beserta detail Siswa, Ujian, dan Mata Pelajaran
$querySessions = "
    SELECT es.id as session_id, es.exam_id, es.user_id, es.status_siswa, es.skor, 
           es.mulai_ujian, es.selesai_ujian, es.sisa_waktu,
           u.username, u.nama_lengkap, u.kelas,
           e.nama_ujian, e.durasi, e.target_kelas, e.waktu_mulai as jadwal_mulai, e.waktu_selesai, e.token_ujian,
           s.nama_mapel,
           (SELECT COUNT(*) FROM exam_answers WHERE session_id = es.id) as total_jawaban
    FROM exam_sessions es
    JOIN users u ON es.user_id = u.id
    JOIN exams e ON es.exam_id = e.id
    LEFT JOIN subjects s ON e.subject_id = s.id
    ORDER BY es.id DESC
";
$allSessions = $pdo->query($querySessions)->fetchAll(PDO::FETCH_ASSOC);

// 5. Hitung Statistik Global dan Lakukan Filtering
$totalSessionsGlobal = count($allSessions);
$totalPengerjaan = 0;
$totalSelesai = 0;
$totalBelum = 0;

$filteredSessions = [];

foreach ($allSessions as $s) {
    if ($s['status_siswa'] === 'pengerjaan') {
        $totalPengerjaan++;
    } elseif ($s['status_siswa'] === 'selesai') {
        $totalSelesai++;
    } else {
        $totalBelum++;
    }

    if ($search !== '') {
        $match = (stripos($s['nama_lengkap'], $search) !== false) ||
                 (stripos($s['username'], $search) !== false) ||
                 (stripos($s['nama_ujian'], $search) !== false) ||
                 (stripos($s['nama_mapel'] ?? '', $search) !== false);
        if (!$match) continue;
    }

    if ($filter_exam !== '' && (string)$s['exam_id'] !== (string)$filter_exam) {
        continue;
    }

    if ($filter_kelas !== '' && strcasecmp($s['kelas'], $filter_kelas) !== 0) {
        continue;
    }

    if ($filter_status !== '' && $filter_status !== 'all' && $s['status_siswa'] !== $filter_status) {
        continue;
    }

    $filteredSessions[] = $s;
}

// 6. Konfigurasi dan Perhitungan Paginasi
$per_page_options = [10, 15, 25, 50, 100];
$per_page = (int)($_GET['per_page'] ?? 15);
if (!in_array($per_page, $per_page_options)) {
    $per_page = 15;
}

$totalFiltered = count($filteredSessions);
$totalPages = max(1, (int)ceil($totalFiltered / $per_page));
$page = max(1, (int)($_GET['page'] ?? 1));
if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $per_page;
$paginatedSessions = array_slice($filteredSessions, $offset, $per_page);

function get_pagination_url($targetPage, $targetPerPage = null) {
    $params = $_GET;
    $params['page'] = $targetPage;
    if ($targetPerPage !== null) {
        $params['per_page'] = $targetPerPage;
    }
    return 'kelola_sesi.php?' . http_build_query($params);
}

function format_indo_dt($datetime_str, $with_date = true) {
    if (!$datetime_str || $datetime_str === '0000-00-00 00:00:00') return '-';
    if (is_numeric($datetime_str)) {
        $time = (int)$datetime_str;
    } else {
        $time = strtotime($datetime_str);
    }
    if (!$time) return '-';
    if (!$with_date) return date('H:i', $time) . ' WIB';
    
    $bulanIndo = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $tgl = date('d', $time);
    $bln = (int)date('m', $time);
    $jam = date('H:i', $time);
    return "$tgl {$bulanIndo[$bln]}, $jam WIB";
}

function get_initials($name) {
    $words = preg_split('/\s+/', trim($name));
    $initials = '';
    if (count($words) >= 2) {
        $initials = mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1);
    } elseif (count($words) === 1 && strlen($words[0]) > 0) {
        $initials = mb_substr($words[0], 0, 2);
    }
    return strtoupper($initials ?: 'SW');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Sesi Ujian - Admin Panel - Espega Sinau</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="admin_theme.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .page-header h1 {
            font-size: 1.55rem;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.02em;
        }
        .page-header p {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 4px;
        }

        .info-callout {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--primary);
            border-radius: var(--radius-sm);
            padding: 14px 18px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            box-shadow: var(--shadow-sm);
        }
        .info-callout i {
            font-size: 1.15rem;
            color: var(--primary);
            margin-top: 2px;
            flex-shrink: 0;
        }
        .info-text {
            font-size: 0.85rem;
            color: var(--adm-slate-700);
            line-height: 1.5;
        }

        .card {
            background: #ffffff;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }

        /* Filter Section */
        .filter-section {
            background: var(--adm-slate-50);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 16px;
            margin-bottom: 22px;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: 2fr 1.3fr 1fr 1fr auto;
            gap: 12px;
            align-items: center;
        }
        .filter-input {
            width: 100%;
            padding: 9px 13px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            outline: none;
            font-size: 0.88rem;
            background: #ffffff;
            color: var(--text-main);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .filter-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }
        .filter-badge-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px dashed var(--border-color);
            font-size: 0.82rem;
            color: var(--text-muted);
            flex-wrap: wrap;
            gap: 8px;
        }

        /* Table */
        .table-wrap {
            overflow-x: auto;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.88rem;
        }
        thead {
            background: var(--adm-slate-50);
            border-bottom: 1px solid var(--border-color);
        }
        th {
            padding: 13px 15px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--adm-slate-600);
            font-weight: 700;
            white-space: nowrap;
        }
        td {
            padding: 14px 15px;
            border-bottom: 1px solid var(--adm-slate-100);
            vertical-align: middle;
            color: var(--text-main);
        }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover { background-color: #fbfcfe; }

        /* User Cell */
        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            background: var(--adm-slate-100);
            border: 1px solid var(--border-color);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.82rem;
            flex-shrink: 0;
        }
        .user-name {
            font-weight: 700;
            color: var(--text-main);
            font-size: 0.88rem;
        }
        .user-meta {
            font-size: 0.77rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 2px;
        }
        .badge-kelas {
            background: var(--adm-slate-100);
            color: var(--adm-slate-800);
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 0.72rem;
            border: 1px solid var(--border-color);
        }

        .exam-title {
            font-weight: 600;
            color: var(--text-main);
            font-size: 0.88rem;
        }
        .exam-mapel-badge {
            display: inline-block;
            font-size: 0.74rem;
            font-weight: 600;
            color: var(--primary);
            background: var(--adm-blue-50);
            border: 1px solid var(--adm-blue-100);
            padding: 2px 8px;
            border-radius: 4px;
        }

        /* Status Pills */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .status-pill.pengerjaan {
            background: var(--adm-success-bg);
            color: #065f46;
            border: 1px solid var(--adm-success-border);
        }
        .status-pill.selesai {
            background: var(--adm-blue-50);
            color: #1e40af;
            border: 1px solid var(--adm-blue-100);
        }
        .status-pill.belum {
            background: var(--adm-slate-100);
            color: var(--adm-slate-600);
            border: 1px solid var(--border-color);
        }

        .pulse-dot {
            width: 7px;
            height: 7px;
            background: var(--adm-success);
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulseGreen 1.6s infinite;
        }

        .time-detail {
            font-size: 0.82rem;
            line-height: 1.4;
        }
        .time-remaining-active {
            font-size: 0.76rem;
            font-weight: 700;
            color: #b45309;
            background: #fffbeb;
            padding: 2px 7px;
            border-radius: 4px;
            border: 1px solid #fde68a;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .score-box {
            font-size: 1.05rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 3px 10px;
            border-radius: 6px;
            min-width: 44px;
        }
        .score-good { background: var(--adm-success-bg); color: #047857; border: 1px solid var(--adm-success-border); }
        .score-mid { background: var(--adm-blue-50); color: var(--primary); border: 1px solid var(--adm-blue-100); }
        .score-none { color: var(--text-muted); font-weight: bold; }

        .btn-action {
            padding: 5px 10px;
            font-size: 0.78rem;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
            background: #ffffff;
            color: var(--adm-slate-700);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-action:hover {
            background: var(--adm-slate-50);
            border-color: var(--adm-slate-400);
            color: var(--text-main);
        }
        .btn-action.danger:hover {
            background: var(--adm-danger-bg);
            border-color: var(--adm-danger-border);
            color: var(--adm-danger);
        }
        .btn-action.success {
            color: #059669;
            border-color: rgba(16, 185, 129, 0.4);
            background: rgba(16, 185, 129, 0.05);
        }
        .btn-action.success:hover {
            background: #10b981;
            border-color: #10b981;
            color: #ffffff;
        }

        .bulk-action-bar {
            display: none;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
            border: 1px solid rgba(16, 185, 129, 0.35);
            padding: 10px 18px;
            border-radius: var(--radius-md);
            margin-bottom: 14px;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.1);
            animation: fadeIn 0.2s ease;
            gap: 12px;
            flex-wrap: wrap;
        }
        .bulk-action-bar.active {
            display: flex;
        }
        .badge-selected-count {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #10b981;
            color: #ffffff;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
        }
        .adm-checkbox {
            width: 17px;
            height: 17px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        /* Pagination */
        .pagination-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 18px;
            margin-top: 16px;
            border-top: 1px solid var(--border-color);
            flex-wrap: wrap;
            gap: 15px;
        }
        .pagination-info {
            font-size: 0.84rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .pagination-nav {
            display: flex;
            align-items: center;
            gap: 4px;
            flex-wrap: wrap;
        }
        .page-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
            background: #ffffff;
            color: var(--text-main);
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .page-btn:hover:not(.disabled):not(.active) {
            background: var(--adm-slate-100);
            border-color: var(--adm-slate-400);
            color: var(--primary);
        }
        .page-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.25);
        }
        .page-btn.disabled {
            opacity: 0.35;
            cursor: not-allowed;
            pointer-events: none;
            background: var(--adm-slate-50);
        }
        .per-page-select {
            padding: 4px 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 0.82rem;
            color: var(--text-main);
            background: #ffffff;
            outline: none;
            cursor: pointer;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }
        .modal-overlay.active { opacity: 1; pointer-events: auto; }
        .modal-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 540px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            transform: scale(0.96) translateY(-10px);
            transition: transform 0.2s ease;
        }
        .modal-overlay.active .modal-card { transform: scale(1) translateY(0); }
        .modal-header {
            padding: 18px 24px;
            background: var(--adm-slate-50);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 { font-size: 1.15rem; font-weight: 700; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 8px; }
        .btn-close {
            background: transparent;
            border: none;
            font-size: 1.25rem;
            color: var(--text-muted);
            cursor: pointer;
            padding: 2px 6px;
            border-radius: 6px;
        }
        .btn-close:hover { background: var(--adm-slate-200); color: var(--text-main); }
        .modal-body { padding: 22px 24px; max-height: 75vh; overflow-y: auto; }
        .modal-footer {
            padding: 14px 24px;
            background: var(--adm-slate-50);
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 700;
            margin-bottom: 6px;
            color: var(--adm-slate-700);
        }
        .form-group select {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            outline: none;
            font-size: 0.88rem;
            color: var(--text-main);
            background: #ffffff;
            box-sizing: border-box;
        }
        .form-group select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .helper-box {
            padding: 12px 14px;
            background: var(--adm-slate-50);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 0.82rem;
            color: var(--adm-slate-700);
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.45;
            margin-top: 10px;
        }

        @media (max-width: 950px) {
            .filter-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="admin-body">

<?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1>Kelola & Monitoring Sesi Ujian</h1>
            <p>Pantau progres pengerjaan ujian secara realtime, tangani reset kendala siswa, atau pra-alokasikan sesi kelas.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button type="button" class="btn btn-primary" onclick="openAddSessionModal()">
                <i class="fa-solid fa-plus"></i>
                <span>Buka Sesi per Kelas</span>
            </button>
            <a href="kelola_sesi.php" class="btn btn-outline" title="Segarkan Data">
                <i class="fa-solid fa-arrows-rotate"></i>
                <span>Refresh</span>
            </a>
        </div>
    </div>

    <!-- Info Callout -->
    <div class="info-callout">
        <i class="fa-solid fa-circle-info"></i>
        <div class="info-text">
            <strong>Sistem Sesi Cerdas & Fleksibel:</strong> Sesi ujian terbentuk <strong>secara otomatis</strong> ketika siswa mengklik <em>"Mulai Ujian"</em> pada jadwal yang sedang berlangsung. Anda tidak diwajibkan membuka sesi manual. Tombol <strong>"Buka Sesi per Kelas"</strong> bersifat opsional untuk pra-alokasi massal. Jika siswa mengalami kendala teknis (mati lampu/perangkat tertutup), gunakan tombol <strong>Reset</strong> pada tabel agar siswa dapat melanjutkan ujian.
        </div>
    </div>

    <!-- Statistik Global Sesi -->
    <div class="adm-stats-grid">
        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-list-check"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= number_format($totalSessionsGlobal) ?></div>
                <div class="adm-stat-label">Total Sesi Terdaftar</div>
            </div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-icon" style="color: var(--adm-success);">
                <span class="pulse-dot"></span>
            </div>
            <div>
                <div class="adm-stat-num" style="color: var(--adm-success);"><?= number_format($totalPengerjaan) ?></div>
                <div class="adm-stat-label">Sedang Mengerjakan</div>
            </div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= number_format($totalSelesai) ?></div>
                <div class="adm-stat-label">Selesai Mengerjakan</div>
            </div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-hourglass-start"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= number_format($totalBelum) ?></div>
                <div class="adm-stat-label">Belum Mulai</div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Section -->
    <div class="filter-section">
        <form method="GET" action="kelola_sesi.php">
            <input type="hidden" name="per_page" value="<?= $per_page ?>">
            <div class="filter-grid">
                <div>
                    <input type="text" name="q" class="filter-input" placeholder="Cari nama siswa, NIS, atau ujian..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div>
                    <select name="exam_id" class="filter-input">
                        <option value="">Semua Ujian</option>
                        <?php foreach($exams as $e): ?>
                            <option value="<?= $e['id'] ?>" <?= ($filter_exam == $e['id']) ? 'selected' : '' ?>>
                                [<?= htmlspecialchars($e['nama_mapel']) ?>] <?= htmlspecialchars($e['nama_ujian']) ?> (<?= $e['target_kelas'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <select name="kelas" class="filter-input">
                        <option value="">Semua Kelas</option>
                        <?php foreach($classes as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= ($filter_kelas == $c) ? 'selected' : '' ?>>
                                Kelas <?= htmlspecialchars($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <select name="status_siswa" class="filter-input">
                        <option value="all" <?= ($filter_status === 'all' || $filter_status === '') ? 'selected' : '' ?>>Semua Status</option>
                        <option value="pengerjaan" <?= ($filter_status === 'pengerjaan') ? 'selected' : '' ?>>Sedang Mengerjakan</option>
                        <option value="selesai" <?= ($filter_status === 'selesai') ? 'selected' : '' ?>>Selesai</option>
                        <option value="belum" <?= ($filter_status === 'belum') ? 'selected' : '' ?>>Belum Mulai</option>
                    </select>
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn btn-primary" style="padding: 9px 14px;">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                    <?php if ($search !== '' || $filter_exam !== '' || $filter_kelas !== '' || ($filter_status !== '' && $filter_status !== 'all')): ?>
                        <a href="kelola_sesi.php" class="btn btn-outline" style="padding: 9px 12px;" title="Reset Filter">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="filter-badge-bar">
                <div>
                    Menampilkan <strong><?= $totalFiltered > 0 ? ($offset + 1) : 0 ?> - <?= min($offset + $per_page, $totalFiltered) ?></strong> dari <strong><?= number_format($totalFiltered) ?></strong> data sesi (Total: <?= number_format($totalSessionsGlobal) ?>).
                </div>
            </div>
        </form>
    </div>

    <!-- Form Tersembunyi untuk Submit Paksa Terpilih -->
    <form id="bulkForceSubmitForm" action="../../controllers/AdminController.php?action=bulk_force_submit_sesi" method="POST" style="display:none;"></form>

    <!-- Bulk Action Bar -->
    <div id="bulkActionBar" class="bulk-action-bar">
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <span class="badge-selected-count" id="selectedCountBadge">
                <i class="fa-solid fa-check-double"></i> <span id="selectedCountNumber">0</span> Siswa Terpilih
            </span>
            <span style="font-size: 0.84rem; color: var(--text-main); font-weight: 500;">
                Tindakan untuk siswa yang dicentang:
            </span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn btn-sm btn-primary" style="background: #10b981; border-color: #10b981; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(16, 185, 129, 0.25);" onclick="confirmBulkForceSubmit()">
                <i class="fa-solid fa-cloud-arrow-up"></i> Submit Paksa Terpilih
            </button>
            <button type="button" class="btn btn-sm btn-outline" onclick="clearAllSelections()">
                <i class="fa-solid fa-xmark"></i> Batal
            </button>
        </div>
    </div>

    <!-- Tabel Sesi Siswa -->
    <div class="card">
        <?php if (empty($filteredSessions)): ?>
            <div class="empty-state" style="padding: 50px 20px; text-align: center; color: var(--text-muted);">
                <i class="fa-solid fa-folder-open" style="font-size: 2.8rem; margin-bottom: 12px; color: var(--adm-slate-400);"></i>
                <h3 style="font-size: 1.15rem; color: var(--text-main); margin-bottom: 6px;">Tidak Ada Sesi yang Cocok</h3>
                <p style="font-size: 0.88rem; max-width: 480px; margin: 0 auto 16px;">
                    Tidak ditemukan sesi pengerjaan siswa yang sesuai dengan kriteria filter atau pencarian Anda.
                </p>
                <a href="kelola_sesi.php" class="btn btn-outline btn-sm">Reset Semua Filter</a>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 38px; text-align: center;">
                                <input type="checkbox" id="checkAll" class="adm-checkbox" onchange="toggleSelectAll(this)" title="Pilih Semua Siswa di Halaman Ini">
                            </th>
                            <th style="width: 45px;">No</th>
                            <th>Identitas Siswa</th>
                            <th>Ujian & Mata Pelajaran</th>
                            <th>Status Pengerjaan</th>
                            <th>Waktu & Sisa Waktu</th>
                            <th style="text-align: center;">Skor / Jawaban</th>
                            <th style="text-align: center; width: 220px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = $offset + 1;
                        foreach ($paginatedSessions as $s): 
                            $initials = get_initials($s['nama_lengkap']);
                        ?>
                        <tr>
                            <td style="text-align: center;">
                                <input type="checkbox" name="session_ids[]" form="bulkForceSubmitForm" class="session-checkbox adm-checkbox" value="<?= $s['session_id'] ?>" data-name="<?= htmlspecialchars($s['nama_lengkap']) ?>" data-status="<?= $s['status_siswa'] ?>" onchange="updateSelectedCount()">
                            </td>
                            <td style="color: var(--text-muted); font-weight: 600;"><?= $no++ ?></td>
                            
                            <!-- Siswa -->
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar">
                                        <?= $initials ?>
                                    </div>
                                    <div>
                                        <div class="user-name"><?= htmlspecialchars($s['nama_lengkap']) ?></div>
                                        <div class="user-meta">
                                            <span>NIS: <?= htmlspecialchars($s['username']) ?></span>
                                            <span class="badge-kelas">Kelas <?= htmlspecialchars($s['kelas']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Ujian & Mapel -->
                            <td>
                                <div>
                                    <div class="exam-title"><?= htmlspecialchars($s['nama_ujian']) ?></div>
                                    <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap; margin-top: 3px;">
                                        <span class="exam-mapel-badge"><?= htmlspecialchars($s['nama_mapel'] ?? 'Umum') ?></span>
                                        <span style="font-size: 0.74rem; color: var(--text-muted);">
                                            Kelas <?= htmlspecialchars($s['target_kelas']) ?>
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Status Pengerjaan -->
                            <td>
                                <?php if ($s['status_siswa'] === 'pengerjaan'): ?>
                                    <span class="status-pill pengerjaan">
                                        <span class="pulse-dot"></span> Sedang Mengerjakan
                                    </span>
                                <?php elseif ($s['status_siswa'] === 'selesai'): ?>
                                    <span class="status-pill selesai">
                                        <i class="fa-solid fa-check"></i> Selesai
                                    </span>
                                <?php else: ?>
                                    <span class="status-pill belum">
                                        <i class="fa-solid fa-hourglass-start"></i> Belum Mulai
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Waktu & Sisa Waktu -->
                            <td>
                                <div class="time-detail">
                                    <div>
                                        <span style="color: var(--text-muted);">Mulai:</span> 
                                        <strong><?= format_indo_dt($s['mulai_ujian']) ?></strong>
                                    </div>
                                    <?php if ($s['status_siswa'] === 'pengerjaan'): ?>
                                        <?php 
                                        $sisaDetik = ($s['sisa_waktu'] !== null) ? (int)$s['sisa_waktu'] : ((int)$s['durasi'] * 60);
                                        $sisaMenit = floor($sisaDetik / 60);
                                        $sisaDetikOnly = $sisaDetik % 60;
                                        ?>
                                        <div style="margin-top: 2px;">
                                            <span class="time-remaining-active">
                                                <i class="fa-solid fa-stopwatch"></i> <?= sprintf("%02d:%02d", $sisaMenit, $sisaDetikOnly) ?> tersisa
                                            </span>
                                        </div>
                                    <?php elseif ($s['status_siswa'] === 'selesai'): ?>
                                        <div>
                                            <span style="color: var(--text-muted);">Selesai:</span> 
                                            <span><?= format_indo_dt($s['selesai_ujian']) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div>
                                            <span style="color: var(--text-muted);">Durasi:</span> 
                                            <span><?= (int)$s['durasi'] ?> Menit</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Skor / Jawaban -->
                            <td style="text-align: center;">
                                <?php if ($s['status_siswa'] === 'selesai'): ?>
                                    <?php 
                                    $skorNum = (float)($s['skor'] ?? 0);
                                    $scoreClass = ($skorNum >= 75) ? 'score-good' : 'score-mid';
                                    ?>
                                    <span class="score-box <?= $scoreClass ?>">
                                        <?= round($skorNum, 1) ?>
                                    </span>
                                <?php elseif ($s['status_siswa'] === 'pengerjaan'): ?>
                                    <span style="font-size: 0.8rem; font-weight: 600; color: var(--primary); background: var(--adm-blue-50); padding: 4px 8px; border-radius: 4px; border: 1px solid var(--adm-blue-100);">
                                        <?= (int)$s['total_jawaban'] ?> dijawab
                                    </span>
                                <?php else: ?>
                                    <span class="score-none">-</span>
                                <?php endif; ?>
                            </td>

                            <!-- Aksi -->
                            <td style="text-align: center;">
                                <div style="display: inline-flex; gap: 6px; align-items: center; justify-content: center; flex-wrap: wrap;">
                                    <?php if ($s['status_siswa'] !== 'selesai'): ?>
                                        <button type="button" 
                                                class="btn-action success" 
                                                title="Submit Paksa Jawaban Siswa Sekarang"
                                                onclick="confirmForceSubmitSession(<?= $s['session_id'] ?>, '<?= htmlspecialchars(addslashes($s['nama_lengkap'])) ?>', '<?= htmlspecialchars(addslashes($s['nama_ujian'])) ?>', '<?= $s['status_siswa'] ?>')">
                                            <i class="fa-solid fa-cloud-arrow-up"></i> Submit Paksa
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" 
                                            class="btn-action" 
                                            title="Reset Pengerjaan Siswa"
                                            onclick="confirmResetSession(<?= $s['session_id'] ?>, '<?= htmlspecialchars(addslashes($s['nama_lengkap'])) ?>', '<?= htmlspecialchars(addslashes($s['nama_ujian'])) ?>')">
                                        <i class="fa-solid fa-arrows-rotate"></i> Reset
                                    </button>
                                    <button type="button" 
                                            class="btn-action danger" 
                                            title="Hapus Sesi Siswa"
                                            onclick="confirmDeleteSession(<?= $s['session_id'] ?>, '<?= htmlspecialchars(addslashes($s['nama_lengkap'])) ?>', '<?= htmlspecialchars(addslashes($s['nama_ujian'])) ?>')">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <?php if ($totalFiltered > 0): ?>
            <div class="pagination-wrap">
                <div class="pagination-info">
                    <span>Menampilkan baris ke-<strong><?= ($offset + 1) ?></strong> sampai <strong><?= min($offset + $per_page, $totalFiltered) ?></strong> (Hal <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong>)</span>
                    <div style="display: inline-flex; align-items: center; gap: 6px;">
                        <label for="perPageSelect" style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">Per halaman:</label>
                        <select id="perPageSelect" class="per-page-select" onchange="window.location.href = this.value;">
                            <?php foreach ($per_page_options as $opt): ?>
                                <option value="<?= htmlspecialchars(get_pagination_url(1, $opt)) ?>" <?= ($per_page == $opt) ? 'selected' : '' ?>>
                                    <?= $opt ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="pagination-nav">
                    <a href="<?= htmlspecialchars(get_pagination_url(1)) ?>" 
                       class="page-btn <?= ($page <= 1) ? 'disabled' : '' ?>" 
                       title="Pertama"><i class="fa-solid fa-angles-left"></i></a>
                    <a href="<?= htmlspecialchars(get_pagination_url($page - 1)) ?>" 
                       class="page-btn <?= ($page <= 1) ? 'disabled' : '' ?>" 
                       title="Sebelumnya"><i class="fa-solid fa-angle-left"></i></a>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);

                    if ($startPage > 1) {
                        echo '<a href="' . htmlspecialchars(get_pagination_url(1)) . '" class="page-btn">1</a>';
                        if ($startPage > 2) echo '<span style="color:var(--text-muted); padding:0 4px;">...</span>';
                    }

                    for ($i = $startPage; $i <= $endPage; $i++) {
                        $activeCls = ($i == $page) ? 'active' : '';
                        echo '<a href="' . htmlspecialchars(get_pagination_url($i)) . '" class="page-btn ' . $activeCls . '">' . $i . '</a>';
                    }

                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) echo '<span style="color:var(--text-muted); padding:0 4px;">...</span>';
                        echo '<a href="' . htmlspecialchars(get_pagination_url($totalPages)) . '" class="page-btn">' . $totalPages . '</a>';
                    }
                    ?>

                    <a href="<?= htmlspecialchars(get_pagination_url($page + 1)) ?>" 
                       class="page-btn <?= ($page >= $totalPages) ? 'disabled' : '' ?>" 
                       title="Berikutnya"><i class="fa-solid fa-angle-right"></i></a>
                    <a href="<?= htmlspecialchars(get_pagination_url($totalPages)) ?>" 
                       class="page-btn <?= ($page >= $totalPages) ? 'disabled' : '' ?>" 
                       title="Terakhir"><i class="fa-solid fa-angles-right"></i></a>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Modal Buka Sesi per Kelas -->
    <div class="modal-overlay" id="addSessionModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3><i class="fa-solid fa-plus"></i> Buka Sesi Ujian per Kelas</h3>
                <button type="button" class="btn-close" onclick="closeAddSessionModal()">&times;</button>
            </div>
            <form action="../../controllers/AdminController.php?action=add_sesi" method="POST">
                <div class="modal-body">
                    <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 18px;">
                        Fitur ini akan mendaftarkan seluruh siswa dalam satu kelas ke jadwal ujian yang dipilih secara massal sebelum ujian dimulai.
                    </p>

                    <div class="form-group">
                        <label for="modalKelasSelect">Pilih Kelas Sasaran <span style="color: var(--adm-danger);">*</span></label>
                        <select name="kelas" id="modalKelasSelect" required>
                            <option value="">-- Pilih Kelas Siswa --</option>
                            <?php foreach($classes as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>">Kelas <?= htmlspecialchars($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="modalExamSelect">Pilih Ujian <span style="color: var(--adm-danger);">*</span></label>
                        <select name="exam_id" id="modalExamSelect" required>
                            <option value="">-- Pilih Ujian yang Sesuai --</option>
                            <?php foreach($exams as $e): ?>
                                <option value="<?= $e['id'] ?>" data-kelas="<?= htmlspecialchars($e['target_kelas']) ?>">
                                    [<?= htmlspecialchars($e['nama_mapel']) ?>] <?= htmlspecialchars($e['nama_ujian']) ?> (Target: Kelas <?= htmlspecialchars($e['target_kelas']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span id="examFilterHint" style="font-size: 0.76rem; color: var(--text-muted); margin-top: 4px; display: none;">
                            <i class="fa-solid fa-circle-info"></i> Menampilkan ujian yang cocok untuk kelas sasaran yang dipilih.
                        </span>
                    </div>

                    <div class="helper-box">
                        <i class="fa-solid fa-shield-halved" style="color: var(--primary); font-size: 1.1rem;"></i>
                        <div>
                            <strong>Aman dari Duplikasi:</strong> Siswa di kelas tersebut yang sudah memiliki sesi ujian ini tidak akan diduplikasi atau tertimpa.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeAddSessionModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Aktifkan untuk 1 Kelas</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Script Handling & Alerts -->
    <script>
    function openAddSessionModal() {
        document.getElementById('addSessionModal').classList.add('active');
        filterModalExams();
    }

    function closeAddSessionModal() {
        document.getElementById('addSessionModal').classList.remove('active');
    }

    document.getElementById('addSessionModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeAddSessionModal();
        }
    });

    const modalKelasSelect = document.getElementById('modalKelasSelect');
    const modalExamSelect = document.getElementById('modalExamSelect');
    const examFilterHint = document.getElementById('examFilterHint');

    function filterModalExams() {
        const selectedKelas = modalKelasSelect.value.trim().toLowerCase();
        const options = modalExamSelect.querySelectorAll('option');

        options.forEach(opt => {
            if (opt.value === "") {
                opt.style.display = 'block';
                return;
            }

            const targetKelas = (opt.getAttribute('data-kelas') || '').trim().toLowerCase();

            if (selectedKelas === "") {
                opt.style.display = 'block';
            } else if (targetKelas === selectedKelas || targetKelas === 'all' || targetKelas === 'semua' || targetKelas === '') {
                opt.style.display = 'block';
            } else {
                opt.style.display = 'none';
            }
        });

        if (selectedKelas !== "") {
            examFilterHint.style.display = 'block';
        } else {
            examFilterHint.style.display = 'none';
        }
    }

    modalKelasSelect.addEventListener('change', function() {
        modalExamSelect.value = "";
        filterModalExams();
    });

    // Checkbox & Bulk Actions Selection
    function toggleSelectAll(master) {
        const checkboxes = document.querySelectorAll('.session-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = master.checked;
        });
        updateSelectedCount();
    }

    function updateSelectedCount() {
        const checkboxes = document.querySelectorAll('.session-checkbox:checked');
        const count = checkboxes.length;
        const bar = document.getElementById('bulkActionBar');
        const countEl = document.getElementById('selectedCountNumber');
        if (countEl) countEl.innerText = count;

        if (count > 0) {
            bar.classList.add('active');
        } else {
            bar.classList.remove('active');
        }

        const allBoxes = document.querySelectorAll('.session-checkbox');
        const master = document.getElementById('checkAll');
        if (master) {
            master.checked = allBoxes.length > 0 && checkboxes.length === allBoxes.length;
        }
    }

    function clearAllSelections() {
        const master = document.getElementById('checkAll');
        if (master) master.checked = false;
        const checkboxes = document.querySelectorAll('.session-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = false;
        });
        updateSelectedCount();
    }

    // Force Submit Single Sesi
    function confirmForceSubmitSession(sessionId, studentName, examName, statusSiswa) {
        Swal.fire({
            title: 'Submit Paksa Ujian?',
            html: `Apakah Anda yakin ingin memfinalisasi / melakukan <b>Submit Paksa</b> untuk siswa <b>${studentName}</b> pada ujian <b>${examName}</b>?<br><br><span style="font-size:0.85rem; color:#047857;">Jawaban siswa yang sudah tersimpan saat ini akan langsung dinilai dan status sesi diubah menjadi <b>SELESAI</b>.</span>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fa-solid fa-cloud-arrow-up"></i> Ya, Submit Paksa',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `../../controllers/AdminController.php?action=force_submit_sesi&id=${sessionId}`;
            }
        });
    }

    // Force Submit Bulk Sesi
    function confirmBulkForceSubmit() {
        const selected = document.querySelectorAll('.session-checkbox:checked');
        if (selected.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Pilih Siswa Terlebih Dahulu',
                text: 'Silakan centang minimal satu siswa yang ingin Anda submit paksa.',
                confirmButtonColor: '#2563eb'
            });
            return;
        }

        Swal.fire({
            title: `Submit Paksa ${selected.length} Siswa?`,
            html: `Apakah Anda yakin ingin melakukan <b>Submit Paksa</b> massal pada <b>${selected.length} siswa terpilih</b>?<br><br><span style="font-size:0.85rem; color:#047857;">Semua jawaban yang telah dikerjakan akan dikoreksi dan status sesi mereka akan diselesaikan seketika.</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#64748b',
            confirmButtonText: `<i class="fa-solid fa-cloud-arrow-up"></i> Ya, Submit Paksa (${selected.length})`,
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('bulkForceSubmitForm');
                form.innerHTML = '';
                selected.forEach(cb => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'session_ids[]';
                    input.value = cb.value;
                    form.appendChild(input);
                });
                form.submit();
            }
        });
    }

    function confirmResetSession(sessionId, studentName, examName) {
        Swal.fire({
            title: 'Reset Sesi Pengerjaan?',
            html: `Apakah Anda yakin ingin mereset sesi untuk siswa <b>${studentName}</b> pada ujian <b>${examName}</b>?<br><br><span style="font-size:0.85rem; color:#b45309;">Jawaban tersimpan akan dibersihkan agar siswa dapat mengulang ujian dari awal.</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fa-solid fa-arrows-rotate"></i> Ya, Reset Sesi',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `../../controllers/AdminController.php?action=reset_sesi&id=${sessionId}`;
            }
        });
    }

    function confirmDeleteSession(sessionId, studentName, examName) {
        Swal.fire({
            title: 'Hapus Sesi Siswa?',
            html: `Apakah Anda yakin ingin menghapus sesi untuk siswa <b>${studentName}</b> pada ujian <b>${examName}</b>?<br><br><span style="font-size:0.85rem; color:#dc2626;">Sesi ini akan dihapus permanen dari sistem beserta riwayat jawabannya.</span>`,
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fa-solid fa-trash-can"></i> Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `../../controllers/AdminController.php?action=delete_sesi&id=${sessionId}`;
            }
        });
    }

    window.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const error = urlParams.get('error');

        if (status === 'add_success') {
            Swal.fire({
                icon: 'success',
                title: 'Sesi Berhasil Diaktifkan!',
                text: 'Sesi ujian untuk seluruh siswa pada kelas terpilih telah berhasil didaftarkan.',
                confirmButtonColor: '#2563eb'
            });
        } else if (status === 'reset_success') {
            Swal.fire({
                icon: 'success',
                title: 'Sesi Berhasil Direset!',
                text: 'Status pengerjaan siswa dikembalikan ke awal. Siswa dapat login dan memulai kembali ujian.',
                confirmButtonColor: '#2563eb'
            });
        } else if (status === 'delete_success') {
            Swal.fire({
                icon: 'success',
                title: 'Sesi Berhasil Dihapus!',
                text: 'Data sesi ujian siswa telah dihapus dari sistem.',
                confirmButtonColor: '#2563eb'
            });
        } else if (status === 'force_submit_success') {
            Swal.fire({
                icon: 'success',
                title: 'Submit Paksa Berhasil!',
                text: 'Sesi ujian siswa telah berhasil diselesaikan dan skor nilai telah diperbarui.',
                confirmButtonColor: '#10b981'
            });
        } else if (status === 'bulk_force_submit_success') {
            const count = urlParams.get('count') || '';
            Swal.fire({
                icon: 'success',
                title: 'Submit Paksa Massal Berhasil!',
                text: `Sebanyak ${count} sesi ujian siswa terpilih telah berhasil diselesaikan dan dinilai.`,
                confirmButtonColor: '#10b981'
            });
        } else if (error === 'no_students') {
            Swal.fire({
                icon: 'warning',
                title: 'Tidak Ada Siswa',
                text: 'Tidak ditemukan akun siswa aktif pada kelas yang Anda pilih.',
                confirmButtonColor: '#2563eb'
            });
        }

        if (status || error) {
            urlParams.delete('status');
            urlParams.delete('error');
            urlParams.delete('count');
            const newQuery = urlParams.toString();
            const cleanUrl = window.location.pathname + (newQuery ? '?' + newQuery : '');
            window.history.replaceState({}, document.title, cleanUrl);
        }
    });
    </script>

<?php include __DIR__ . '/footer.php'; ?>