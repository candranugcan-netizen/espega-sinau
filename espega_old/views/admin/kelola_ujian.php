<?php 
require_once '../../config/database.php';
require_once '../../config/security.php';
protect_page();

// Pastikan zona waktu sinkron dengan WIB
date_default_timezone_set('Asia/Jakarta');

$active_nav = 'ujian';
$page_title = 'Kelola Jadwal & Token Ujian';

// 1. Parameter Filter & Search
$search = trim($_GET['q'] ?? '');
$filter_subject = trim($_GET['subject_id'] ?? '');
$filter_kelas = trim($_GET['kelas'] ?? '');
$filter_status = trim($_GET['status_ujian'] ?? ''); // 'all', 'running', 'upcoming', 'ended'

// 2. Ambil data Mata Pelajaran untuk dropdown (sertakan guru & jumlah bank soal)
$subjects = $pdo->query("
    SELECT s.id, s.nama_mapel, s.kelas, u.nama_lengkap as nama_guru,
           (SELECT COUNT(*) FROM bank_soal WHERE subject_id = s.id) as jml_bank,
           (SELECT COUNT(*) FROM questions WHERE subject_id = s.id) as jml_soal_total
    FROM subjects s 
    LEFT JOIN users u ON s.guru_id = u.id 
    ORDER BY s.nama_mapel ASC, s.kelas ASC
")->fetchAll(PDO::FETCH_ASSOC);

// 2b. Ambil data bank soal per subject untuk dynamic lookup di modal
$bankSoalData = $pdo->query("
    SELECT bs.id, bs.subject_id, bs.nama_bank, bs.kelas_rilis,
           (SELECT COUNT(*) FROM questions WHERE bank_soal_id = bs.id) as jml_soal
    FROM bank_soal bs
    ORDER BY bs.subject_id, bs.nama_bank
")->fetchAll(PDO::FETCH_ASSOC);

// Kelompokkan bank soal per subject_id untuk JS
$banksBySubject = [];
foreach ($bankSoalData as $b) {
    $banksBySubject[$b['subject_id']][] = [
        'id' => $b['id'],
        'nama_bank' => $b['nama_bank'],
        'kelas_rilis' => $b['kelas_rilis'],
        'jml_soal' => (int)$b['jml_soal']
    ];
}
$banksBySubjectJson = json_encode($banksBySubject, JSON_UNESCAPED_UNICODE);

// 3. Ambil daftar kelas unik siswa
$classes = $pdo->query("SELECT DISTINCT kelas FROM users WHERE role_id = 3 AND kelas IS NOT NULL AND TRIM(kelas) != '' ORDER BY kelas ASC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($classes)) {
    $classes = ['7A', '7B', '7C', '7D', '8A', '8B', '8C', '8D', '9A', '9B', '9C', '9D'];
}

// 4. Query Seluruh Data Ujian lengkap dengan relasi soal, guru, dan peserta sesi
$queryExams = "
    SELECT e.*, 
           s.nama_mapel, 
           s.kelas as mapel_kelas,
           u.nama_lengkap as nama_guru,
           COALESCE(
               NULLIF((
                   SELECT COUNT(DISTINCT q.id)
                   FROM questions q
                   JOIN bank_soal bs ON q.bank_soal_id = bs.id
                   WHERE bs.subject_id = e.subject_id
                     AND FIND_IN_SET(e.target_kelas, REPLACE(COALESCE(bs.kelas_rilis, ''), ' ', '')) > 0
               ), 0),
               NULLIF((
                   SELECT COUNT(DISTINCT q.id)
                   FROM questions q
                   JOIN bank_soal bs ON q.bank_soal_id = bs.id
                   WHERE bs.subject_id = e.subject_id
                     AND bs.nama_bank LIKE CONCAT('%Kelas ', SUBSTRING(e.target_kelas, 1, 1), '%')
               ), 0),
               (SELECT COUNT(*) FROM questions WHERE subject_id = e.subject_id)
           ) as total_soal,
           (
               SELECT bs.id
               FROM bank_soal bs
               WHERE bs.subject_id = e.subject_id
                 AND (
                     FIND_IN_SET(e.target_kelas, REPLACE(COALESCE(bs.kelas_rilis, ''), ' ', '')) > 0
                     OR bs.nama_bank LIKE CONCAT('%Kelas ', SUBSTRING(e.target_kelas, 1, 1), '%')
                 )
               LIMIT 1
           ) as matched_bank_id,
           (
               SELECT bs.nama_bank
               FROM bank_soal bs
               WHERE bs.subject_id = e.subject_id
                 AND (
                     FIND_IN_SET(e.target_kelas, REPLACE(COALESCE(bs.kelas_rilis, ''), ' ', '')) > 0
                     OR bs.nama_bank LIKE CONCAT('%Kelas ', SUBSTRING(e.target_kelas, 1, 1), '%')
                 )
               LIMIT 1
           ) as matched_bank_name,
           (SELECT COUNT(*) FROM exam_sessions WHERE exam_id = e.id) as total_peserta,
           (SELECT COUNT(*) FROM exam_sessions WHERE exam_id = e.id AND status_siswa = 'selesai') as peserta_selesai,
           (SELECT COUNT(*) FROM exam_sessions WHERE exam_id = e.id AND status_siswa = 'pengerjaan') as peserta_aktif
    FROM exams e 
    LEFT JOIN subjects s ON e.subject_id = s.id 
    LEFT JOIN users u ON s.guru_id = u.id
    ORDER BY e.id DESC
";
$allExams = $pdo->query($queryExams)->fetchAll(PDO::FETCH_ASSOC);

// 5. Hitung Statistik Global & Tentukan Status Tiap Ujian
$now = time();
$totalExamsGlobal = count($allExams);
$totalRunning = 0;
$totalUpcoming = 0;
$totalEnded = 0;
$totalPesertaAll = 0;

$processedExams = [];
foreach ($allExams as $e) {
    $startTime = strtotime($e['waktu_mulai']);
    $endTime = get_exam_end_time($e);

    if ($now >= $startTime && $now <= $endTime) {
        $statusKey = 'running';
        $statusLabel = 'Sedang Berlangsung';
        $statusBadge = 'running';
        $totalRunning++;
    } elseif ($now < $startTime) {
        $statusKey = 'upcoming';
        $statusLabel = 'Akan Datang';
        $statusBadge = 'upcoming';
        $totalUpcoming++;
    } else {
        $statusKey = 'ended';
        $statusLabel = 'Selesai';
        $statusBadge = 'ended';
        $totalEnded++;
    }

    $totalPesertaAll += (int)$e['total_peserta'];

    $e['status_key'] = $statusKey;
    $e['status_label'] = $statusLabel;
    $e['status_badge'] = $statusBadge;
    $e['end_time'] = $endTime;
    $e['waktu_selesai_val'] = !empty($e['waktu_selesai']) ? $e['waktu_selesai'] : date('Y-m-d H:i:s', $endTime);

    // Filter Logic
    $matchesSearch = true;
    if ($search !== '') {
        $searchLower = strtolower($search);
        $matchesSearch = (
            strpos(strtolower($e['nama_ujian']), $searchLower) !== false ||
            strpos(strtolower($e['nama_mapel'] ?? ''), $searchLower) !== false ||
            strpos(strtolower($e['target_kelas']), $searchLower) !== false ||
            strpos(strtolower($e['token_ujian']), $searchLower) !== false
        );
    }

    $matchesSubject = ($filter_subject === '' || (string)$e['subject_id'] === $filter_subject);
    $matchesKelas = ($filter_kelas === '' || $e['target_kelas'] === $filter_kelas);
    $matchesStatus = ($filter_status === '' || $statusKey === $filter_status);

    if ($matchesSearch && $matchesSubject && $matchesKelas && $matchesStatus) {
        $processedExams[] = $e;
    }
}

function format_indo_datetime($datetimeStr) {
    if (!$datetimeStr || $datetimeStr === '0000-00-00 00:00:00') return '-';
    if (is_numeric($datetimeStr)) {
        $timestamp = (int)$datetimeStr;
    } else {
        $timestamp = strtotime($datetimeStr);
    }
    if (!$timestamp) return '-';
    $months = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $day = date('d', $timestamp);
    $month = $months[(int)date('m', $timestamp)];
    $year = date('Y', $timestamp);
    $time = date('H:i', $timestamp);
    return "$day $month $year &bull; $time WIB";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jadwal Ujian - Admin Panel - Espega Sinau</title>
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
            grid-template-columns: 2fr 1.2fr 1.1fr 1.1fr auto;
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
        .status-pill.running {
            background: var(--adm-success-bg);
            color: #065f46;
            border: 1px solid var(--adm-success-border);
        }
        .status-pill.upcoming {
            background: var(--adm-blue-50);
            color: #1e40af;
            border: 1px solid var(--adm-blue-100);
        }
        .status-pill.ended {
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
        @keyframes pulseGreen {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* Token Display Box */
        .token-code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: var(--primary);
            background: var(--adm-blue-50);
            padding: 4px 9px;
            border-radius: 6px;
            border: 1px solid var(--adm-blue-100);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-copy-token {
            background: #ffffff;
            border: 1px solid var(--border-color);
            color: var(--primary);
            cursor: pointer;
            padding: 5px 8px;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            transition: 0.15s;
        }
        .btn-copy-token:hover {
            background: var(--adm-blue-50);
            border-color: var(--primary);
        }

        /* Table */
        .table-responsive {
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

        .tag-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.76rem;
            font-weight: 600;
        }
        .tag-mapel { background: var(--adm-blue-50); color: var(--primary); border: 1px solid var(--adm-blue-100); }
        .tag-kelas { background: var(--adm-slate-100); color: var(--adm-slate-800); font-weight: 700; border: 1px solid var(--border-color); }
        .tag-soal-ready { background: var(--adm-success-bg); color: #065f46; border: 1px solid var(--adm-success-border); }
        .tag-soal-empty { background: var(--adm-danger-bg); color: #991b1b; border: 1px solid var(--adm-danger-border); }

        /* Bank Info Badge (di modal tambah/edit ujian) */
        .bank-info-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.82rem;
            line-height: 1.4;
            animation: fadeInBadge 0.25s ease;
        }
        .bank-info-found {
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            border: 1px solid #6ee7b7;
            color: #065f46;
        }
        .bank-info-warn {
            background: linear-gradient(135deg, #fffbeb, #fef3c7);
            border: 1px solid #fcd34d;
            color: #92400e;
        }
        .bank-info-empty {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            border: 1px solid #fca5a5;
            color: #991b1b;
        }
        @keyframes fadeInBadge {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Modals */
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
            max-width: 600px;
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
        .modal-close {
            background: transparent;
            border: none;
            font-size: 1.5rem;
            color: var(--text-muted);
            cursor: pointer;
            padding: 2px 6px;
            border-radius: 6px;
            line-height: 1;
        }
        .modal-close:hover { background: var(--adm-slate-200); color: var(--text-main); }
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
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.82rem;
            font-weight: 700;
            margin-bottom: 6px;
            color: var(--adm-slate-700);
        }
        .form-group label .req { color: var(--adm-danger); }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px 13px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            outline: none;
            font-size: 0.9rem;
            color: var(--text-main);
            box-sizing: border-box;
            background: #ffffff;
            transition: all 0.2s ease;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .preset-chips-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 6px;
            flex-wrap: wrap;
        }
        .preset-chip-btn {
            background: var(--adm-slate-100);
            border: 1px solid var(--border-color);
            color: var(--adm-slate-700);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.15s;
        }
        .preset-chip-btn:hover { background: var(--adm-blue-50); border-color: var(--primary); color: var(--primary); }

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

        .empty-state {
            padding: 50px 20px;
            text-align: center;
            color: var(--text-muted);
        }
        .empty-state-icon { font-size: 2.8rem; margin-bottom: 12px; color: var(--adm-slate-400); }

        @media (max-width: 992px) {
            .filter-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 640px) {
            .filter-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="admin-body">

<?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Notifikasi Status Operasi -->
    <?php if (isset($_GET['status'])): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check" style="font-size: 1.1rem;"></i>
            <div>
                <?php 
                    if ($_GET['status'] == 'success') echo "Jadwal ujian baru berhasil ditambahkan beserta token login ujian!";
                    elseif ($_GET['status'] == 'updated') echo "Data jadwal ujian berhasil diperbarui.";
                    elseif ($_GET['status'] == 'deleted') echo "Jadwal ujian berhasil dihapus dari sistem.";
                    elseif ($_GET['status'] == 'token_updated') echo "Token login ujian berhasil di-reset dengan kode baru!";
                    else echo "Operasi berhasil dijalankan.";
                ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.1rem;"></i>
            <div><?= htmlspecialchars($_GET['error']) ?></div>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1>Kelola Jadwal & Token Ujian</h1>
            <p>Atur jadwal pelaksanaan ujian, target kelas sasaran, pantau sesi peserta, serta kelola token akses ujian siswa.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button type="button" class="btn btn-primary" onclick="openAddExamModal()">
                <i class="fa-solid fa-plus"></i>
                <span>Tambah Jadwal Ujian</span>
            </button>
            <a href="kelola_sesi.php" class="btn btn-outline">
                <i class="fa-solid fa-clock"></i>
                <span>Atur Sesi Ujian Siswa</span>
            </a>
            <a href="manajemen_soal.php" class="btn btn-outline">
                <i class="fa-solid fa-book-open"></i>
                <span>Bank Soal</span>
            </a>
        </div>
    </div>

    <!-- Ringkasan Statistik Ujian -->
    <div class="adm-stats-grid">
        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-file-signature"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= $totalExamsGlobal ?></div>
                <div class="adm-stat-label">Total Jadwal Ujian</div>
            </div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-icon" style="color: var(--adm-success);">
                <span class="pulse-dot" style="width: 10px; height: 10px;"></span>
            </div>
            <div>
                <div class="adm-stat-num" style="color: var(--adm-success);"><?= $totalRunning ?></div>
                <div class="adm-stat-label">Sedang Berlangsung</div>
            </div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= $totalUpcoming ?></div>
                <div class="adm-stat-label">Ujian Mendatang</div>
            </div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= number_format($totalPesertaAll) ?></div>
                <div class="adm-stat-label">Total Peserta Terdaftar</div>
            </div>
        </div>
    </div>

    <!-- Card Tabel Ujian -->
    <div class="card">
        
        <!-- Toolbar Filter & Search -->
        <div class="filter-section">
            <form method="GET" action="kelola_ujian.php" id="filterForm">
                <div class="filter-grid">
                    <div>
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Cari nama ujian, mapel, kelas, token..." class="filter-input">
                    </div>
                    <div>
                        <select name="subject_id" onchange="this.form.submit()" class="filter-input">
                            <option value="">Semua Mata Pelajaran</option>
                            <?php foreach($subjects as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($filter_subject == (string)$s['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['nama_mapel']) ?> (<?= htmlspecialchars($s['kelas']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <select name="kelas" onchange="this.form.submit()" class="filter-input">
                            <option value="">Semua Target Kelas</option>
                            <?php foreach($classes as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>" <?= ($filter_kelas === $c) ? 'selected' : '' ?>>
                                    Kelas <?= htmlspecialchars($c) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <select name="status_ujian" onchange="this.form.submit()" class="filter-input">
                            <option value="">Semua Status Waktu</option>
                            <option value="running" <?= ($filter_status === 'running') ? 'selected' : '' ?>>Sedang Berlangsung</option>
                            <option value="upcoming" <?= ($filter_status === 'upcoming') ? 'selected' : '' ?>>Mendatang</option>
                            <option value="ended" <?= ($filter_status === 'ended') ? 'selected' : '' ?>>Sudah Selesai</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary" style="padding: 9px 16px;">
                            <i class="fa-solid fa-filter"></i> Filter
                        </button>
                    </div>
                </div>

                <div class="filter-badge-bar">
                    <div>
                        <span>Menampilkan <strong><?= count($processedExams) ?></strong> dari total <?= $totalExamsGlobal ?> jadwal ujian</span>
                        <?php if (!empty($filter_status)): ?>
                            &bull; <span>Status: <strong><?= htmlspecialchars(ucfirst($filter_status)) ?></strong></span>
                        <?php endif; ?>
                        <?php if (!empty($filter_kelas)): ?>
                            &bull; <span>Kelas: <strong><?= htmlspecialchars($filter_kelas) ?></strong></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($search) || !empty($filter_subject) || !empty($filter_kelas) || !empty($filter_status)): ?>
                        <a href="kelola_ujian.php" style="color: var(--adm-danger); text-decoration: none; font-weight: 600;">
                            <i class="fa-solid fa-xmark"></i> Reset Filter
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tabel Data Ujian -->
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">No</th>
                        <th style="min-width: 260px;">Ujian & Mata Pelajaran</th>
                        <th style="width: 170px;">Target Kelas & Peserta</th>
                        <th style="min-width: 200px;">Jadwal Pelaksanaan</th>
                        <th style="width: 180px;">Token Login Ujian</th>
                        <th style="text-align: right; width: 170px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($processedExams)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-state-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                                    <h4 style="color: var(--text-main); margin-bottom: 4px;">Tidak Ada Jadwal Ujian Ditemukan</h4>
                                    <p style="font-size: 0.85rem;">Coba sesuaikan kata kunci pencarian atau klik tombol "Tambah Jadwal Ujian" di atas.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $no = 1;
                        foreach($processedExams as $e): 
                            $eJson = htmlspecialchars(json_encode([
                                'id' => $e['id'],
                                'nama_ujian' => $e['nama_ujian'],
                                'subject_id' => $e['subject_id'],
                                'target_kelas' => $e['target_kelas'],
                                'waktu_mulai' => date('Y-m-d\TH:i', strtotime($e['waktu_mulai'])),
                                'waktu_selesai' => date('Y-m-d\TH:i', $e['end_time']),
                                'durasi' => $e['durasi'],
                                'token_ujian' => $e['token_ujian']
                            ]), ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr>
                                <td style="text-align: center; color: var(--text-muted); font-size: 0.8rem;"><?= $no++ ?></td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 5px;">
                                        <div style="font-weight: 700; color: var(--text-main); font-size: 0.95rem;">
                                            <?= htmlspecialchars($e['nama_ujian']) ?>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span class="status-pill <?= $e['status_badge'] ?>">
                                                <?php if ($e['status_badge'] === 'running'): ?>
                                                    <span class="pulse-dot"></span>
                                                <?php endif; ?>
                                                <?= $e['status_label'] ?>
                                            </span>
                                            <span class="tag-badge tag-mapel">
                                                <i class="fa-solid fa-book"></i> <?= htmlspecialchars($e['nama_mapel'] ?? 'Mapel Tidak Ditemukan') ?>
                                            </span>
                                            <?php 
                                                $bankLink = 'manajemen_soal.php?subject_id=' . $e['subject_id'];
                                                if (!empty($e['matched_bank_id'])) {
                                                    $bankLink = 'manajemen_soal.php?tab=soal&bank_id=' . $e['matched_bank_id'];
                                                }
                                                $bankTooltip = !empty($e['matched_bank_name']) ? htmlspecialchars($e['matched_bank_name']) : 'Buka Bank Soal';
                                            ?>
                                            <?php if ((int)$e['total_soal'] > 0): ?>
                                                <a href="<?= $bankLink ?>" 
                                                   class="tag-badge tag-soal-ready" style="text-decoration: none;" title="<?= $bankTooltip ?>">
                                                    <i class="fa-solid fa-file-lines"></i> <?= $e['total_soal'] ?> Soal
                                                </a>
                                            <?php else: ?>
                                                <a href="manajemen_soal.php?subject_id=<?= $e['subject_id'] ?>" 
                                                   class="tag-badge tag-soal-empty" style="text-decoration: none;" title="Klik untuk buat soal">
                                                    <i class="fa-solid fa-triangle-exclamation"></i> 0 Soal (Kosong)
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                        <div>
                                            <span class="tag-badge tag-kelas">
                                                <i class="fa-solid fa-school"></i> Kelas <?= htmlspecialchars($e['target_kelas']) ?>
                                            </span>
                                        </div>
                                        <div style="font-size: 0.78rem; color: var(--text-muted);">
                                            <?php if ((int)$e['total_peserta'] > 0): ?>
                                                <span><i class="fa-solid fa-users"></i> <strong><?= $e['total_peserta'] ?></strong> siswa mengikuti</span>
                                                <?php if ((int)$e['peserta_selesai'] > 0 || (int)$e['peserta_aktif'] > 0): ?>
                                                    <div style="font-size: 0.74rem; color: var(--adm-success);">
                                                        <i class="fa-solid fa-check"></i> <?= $e['peserta_selesai'] ?> selesai &bull; <?= $e['peserta_aktif'] ?> aktif
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="color: var(--adm-success); font-weight: 600;"><i class="fa-solid fa-check"></i> Sesi Otomatis Aktif</span>
                                                <div style="font-size: 0.72rem; color: var(--text-muted);">
                                                    Siswa kelas <?= htmlspecialchars($e['target_kelas']) ?> langsung dapat login saat waktu ujian tiba.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 0.85rem;">
                                        <div style="font-weight: 600; color: var(--text-main);">
                                            <i class="fa-solid fa-calendar-days" style="color: var(--text-muted); margin-right: 4px;"></i> <?= format_indo_datetime($e['waktu_mulai']) ?>
                                        </div>
                                        <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 3px;">
                                            <i class="fa-solid fa-hourglass-end" style="color: var(--primary); margin-right: 3px;"></i> Akhir: <strong><?= format_indo_datetime($e['end_time']) ?></strong>
                                        </div>
                                        <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                                            <i class="fa-solid fa-stopwatch" style="margin-right: 3px;"></i> Durasi: <strong><?= $e['durasi'] ?> Menit</strong>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 5px;">
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <span class="token-code"><?= htmlspecialchars($e['token_ujian']) ?></span>
                                            <button type="button" class="btn-copy-token" title="Salin Token Ujian" 
                                                    onclick="copyToken('<?= htmlspecialchars($e['token_ujian']) ?>', this)">
                                                <i class="fa-solid fa-copy"></i>
                                            </button>
                                            <button type="button" class="btn-copy-token" title="Acak & Rilis Token Baru"
                                                    onclick="confirmRefreshToken(<?= $e['id'] ?>, '<?= htmlspecialchars($e['nama_ujian'], ENT_QUOTES) ?>')">
                                                <i class="fa-solid fa-arrows-rotate"></i>
                                            </button>
                                        </div>
                                        <span style="font-size: 0.72rem; color: var(--text-muted);">
                                            Bagikan ke siswa saat ujian
                                        </span>
                                    </div>
                                </td>
                                <td style="text-align: right; vertical-align: middle;">
                                    <div style="display: flex; justify-content: flex-end; gap: 6px; flex-wrap: wrap;">
                                        <a href="kelola_sesi.php?exam_id=<?= $e['id'] ?>" class="btn btn-sm btn-outline" title="Atur Sesi Peserta Ujian">
                                            <i class="fa-solid fa-clock"></i> Sesi
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline" title="Edit Jadwal Ujian"
                                                onclick="openEditExamModal(<?= $eJson ?>)">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline" style="color: var(--adm-danger); border-color: var(--adm-danger-border);" title="Hapus Jadwal Ujian"
                                                onclick="confirmDeleteExam(<?= $e['id'] ?>, '<?= htmlspecialchars($e['nama_ujian'], ENT_QUOTES) ?>')">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- MODAL 1: Tambah Ujian Baru -->
    <div id="modalAddExam" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3><i class="fa-solid fa-plus"></i> Tambah Jadwal Ujian Baru</h3>
                <button type="button" class="modal-close" onclick="closeModal('modalAddExam')">&times;</button>
            </div>
            <form action="../../controllers/AdminController.php?action=add_exam" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Ujian <span class="req">*</span></label>
                        <input type="text" name="nama_ujian" placeholder="Contoh: Penilaian Tengah Semester (PTS) Ganjil" required>
                    </div>

                    <div class="form-group">
                        <label>Mata Pelajaran <span class="req">*</span></label>
                        <select name="subject_id" id="add_subject_id" required onchange="updateBankInfo('add')">
                            <option value="">-- Pilih Mata Pelajaran --</option>
                            <?php foreach($subjects as $s): ?>
                                <option value="<?= $s['id'] ?>">
                                    <?= htmlspecialchars($s['nama_mapel']) ?> (Kelas: <?= htmlspecialchars($s['kelas']) ?> &bull; <?= (int)$s['jml_bank'] ?> Bank Soal)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="add_bank_info_badge" class="bank-info-badge" style="display:none;"></div>
                    </div>

                    <div class="form-group">
                        <label>Target Kelas <span class="req">*</span></label>
                        <select name="target_kelas" id="add_target_kelas" required onchange="updateBankInfo('add')">
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach($classes as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>">Kelas <?= htmlspecialchars($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="form-group">
                            <label>Waktu Mulai Ujian <span class="req">*</span></label>
                            <input type="datetime-local" name="waktu_mulai" id="add_waktu_mulai" required>
                        </div>
                        <div class="form-group">
                            <label>Jam Akhir Ujian (Batas Selesai) <span class="req">*</span></label>
                            <input type="datetime-local" name="waktu_selesai" id="add_waktu_selesai" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Durasi Pengerjaan Siswa (Menit) <span class="req">*</span></label>
                        <input type="number" name="durasi" id="add_durasi" value="90" min="5" max="720" placeholder="90" required>
                        <div class="preset-chips-wrap">
                            <span style="font-size: 0.72rem; color: var(--text-muted);">Pilihan cepat durasi:</span>
                            <button type="button" class="preset-chip-btn" onclick="setDuration('add', 45)">45 Menit</button>
                            <button type="button" class="preset-chip-btn" onclick="setDuration('add', 60)">60 Menit</button>
                            <button type="button" class="preset-chip-btn" onclick="setDuration('add', 90)">90 Menit</button>
                            <button type="button" class="preset-chip-btn" onclick="setDuration('add', 120)">120 Menit</button>
                        </div>
                    </div>

                    <div class="helper-box">
                        <i class="fa-solid fa-circle-info" style="font-size: 1.1rem; color: var(--primary);"></i>
                        <div>
                            <strong>Pengaturan Waktu Fleksibel:</strong> Admin bebas menentukan jam akhir ujian tanpa mempengaruhi durasi waktu ujian. Siswa yang masuk sebelum jam akhir ujian tetap mendapatkan waktu pengerjaan penuh sesuai durasi yang ditentukan. Jawaban akan otomatis tersimpan &amp; tersubmit saat durasi pengerjaan siswa habis.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('modalAddExam')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Jadwal Ujian</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Edit Ujian -->
    <div id="modalEditExam" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3><i class="fa-solid fa-pen-to-square"></i> Edit Jadwal Ujian</h3>
                <button type="button" class="modal-close" onclick="closeModal('modalEditExam')">&times;</button>
            </div>
            <form action="../../controllers/AdminController.php?action=edit_exam" method="POST">
                <input type="hidden" name="id" id="edit_exam_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Ujian <span class="req">*</span></label>
                        <input type="text" name="nama_ujian" id="edit_nama_ujian" required>
                    </div>

                    <div class="form-group">
                        <label>Mata Pelajaran <span class="req">*</span></label>
                        <select name="subject_id" id="edit_subject_id" required onchange="updateBankInfo('edit')">
                            <option value="">-- Pilih Mata Pelajaran --</option>
                            <?php foreach($subjects as $s): ?>
                                <option value="<?= $s['id'] ?>">
                                    <?= htmlspecialchars($s['nama_mapel']) ?> (Kelas: <?= htmlspecialchars($s['kelas']) ?> &bull; <?= (int)$s['jml_bank'] ?> Bank Soal)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="edit_bank_info_badge" class="bank-info-badge" style="display:none;"></div>
                    </div>

                    <div class="form-group">
                        <label>Target Kelas <span class="req">*</span></label>
                        <select name="target_kelas" id="edit_target_kelas" required onchange="updateBankInfo('edit')">
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach($classes as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>">Kelas <?= htmlspecialchars($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="form-group">
                            <label>Waktu Mulai Ujian <span class="req">*</span></label>
                            <input type="datetime-local" name="waktu_mulai" id="edit_waktu_mulai" required>
                        </div>
                        <div class="form-group">
                            <label>Jam Akhir Ujian (Batas Selesai) <span class="req">*</span></label>
                            <input type="datetime-local" name="waktu_selesai" id="edit_waktu_selesai" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="form-group">
                            <label>Durasi Pengerjaan (Menit) <span class="req">*</span></label>
                            <input type="number" name="durasi" id="edit_durasi" min="5" max="720" required>
                            <div class="preset-chips-wrap">
                                <button type="button" class="preset-chip-btn" onclick="setDuration('edit', 45)">45m</button>
                                <button type="button" class="preset-chip-btn" onclick="setDuration('edit', 60)">60m</button>
                                <button type="button" class="preset-chip-btn" onclick="setDuration('edit', 90)">90m</button>
                                <button type="button" class="preset-chip-btn" onclick="setDuration('edit', 120)">120m</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Token Login Ujian</label>
                            <input type="text" name="token_ujian" id="edit_token_ujian" style="font-family:'JetBrains Mono', monospace; font-weight:700; text-transform:uppercase;" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('modalEditExam')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.add('active');
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.remove('active');
        }

        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.classList.remove('active');
            }
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
            }
        });

        function formatDateTimeLocal(d) {
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            const hours = String(d.getHours()).padStart(2, '0');
            const minutes = String(d.getMinutes()).padStart(2, '0');
            return `${year}-${month}-${day}T${hours}:${minutes}`;
        }

        function setDuration(prefix, val) {
            const input = document.getElementById(prefix + '_durasi');
            if (input) {
                input.value = val;
                input.focus();
            }
        }

        function openAddExamModal() {
            const now = new Date();
            const datetimeStr = formatDateTimeLocal(now);
            const timeInput = document.getElementById('add_waktu_mulai');
            if (timeInput) {
                timeInput.value = datetimeStr;
            }

            const selesaiInput = document.getElementById('add_waktu_selesai');
            if (selesaiInput && !selesaiInput.value) {
                // Default awal 2 jam dari waktu mulai (tetap bebas diubah admin tanpa memengaruhi durasi)
                const defaultSelesai = new Date(now.getTime() + (120 * 60000));
                selesaiInput.value = formatDateTimeLocal(defaultSelesai);
            }
            
            const durasiInput = document.getElementById('add_durasi');
            if (durasiInput && !durasiInput.value) {
                durasiInput.value = 90;
            }

            openModal('modalAddExam');
        }

        // Data bank soal per subject (untuk dynamic lookup)
        const banksBySubject = <?= $banksBySubjectJson ?>;

        function updateBankInfo(prefix) {
            const subjectId = document.getElementById(prefix + '_subject_id')?.value;
            const targetKelas = document.getElementById(prefix + '_target_kelas')?.value;
            const badge = document.getElementById(prefix + '_bank_info_badge');
            if (!badge) return;

            if (!subjectId || !targetKelas) {
                badge.style.display = 'none';
                return;
            }

            const banks = banksBySubject[subjectId] || [];
            if (banks.length === 0) {
                badge.innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="color:#ef4444;"></i> Belum ada bank soal untuk mapel ini';
                badge.style.display = 'flex';
                badge.className = 'bank-info-badge bank-info-empty';
                return;
            }

            // Cari bank yang cocok dengan target_kelas
            let matched = banks.find(b => {
                if (!b.kelas_rilis) return false;
                const kelasArr = b.kelas_rilis.replace(/\s/g, '').split(',');
                return kelasArr.includes(targetKelas);
            });

            // Fallback: cari berdasarkan angka kelas di nama bank
            if (!matched) {
                const kelasNum = targetKelas.replace(/[^0-9]/g, '');
                matched = banks.find(b => b.nama_bank && b.nama_bank.includes('Kelas ' + kelasNum));
            }

            if (matched) {
                badge.innerHTML = `<i class="fa-solid fa-bookmark" style="color:var(--primary);"></i> <strong>${escapeHtml(matched.nama_bank)}</strong> &mdash; ${matched.jml_soal} Soal`;
                badge.className = 'bank-info-badge bank-info-found';
            } else {
                badge.innerHTML = `<i class="fa-solid fa-circle-info" style="color:#d97706;"></i> Tidak ditemukan bank soal yang cocok untuk Kelas ${escapeHtml(targetKelas)} (Total ${banks.length} bank: ${banks.reduce((s,b)=>s+b.jml_soal,0)} soal)`;
                badge.className = 'bank-info-badge bank-info-warn';
            }
            badge.style.display = 'flex';
        }

        function openEditExamModal(data) {
            document.getElementById('edit_exam_id').value = data.id || '';
            document.getElementById('edit_nama_ujian').value = data.nama_ujian || '';
            document.getElementById('edit_subject_id').value = data.subject_id || '';
            document.getElementById('edit_target_kelas').value = data.target_kelas || '';
            document.getElementById('edit_waktu_mulai').value = data.waktu_mulai || '';
            document.getElementById('edit_waktu_selesai').value = data.waktu_selesai || '';
            document.getElementById('edit_durasi').value = data.durasi || 90;
            document.getElementById('edit_token_ujian').value = data.token_ujian || '';
            openModal('modalEditExam');
            setTimeout(() => updateBankInfo('edit'), 100);
        }

        function copyToken(tokenText, btnElement) {
            navigator.clipboard.writeText(tokenText).then(() => {
                const originalHtml = btnElement.innerHTML;
                btnElement.innerHTML = '<i class="fa-solid fa-check" style="color:var(--adm-success);"></i>';
                setTimeout(() => {
                    btnElement.innerHTML = originalHtml;
                }, 1500);
            }).catch(err => {
                prompt("Salin token ujian:", tokenText);
            });
        }

        function confirmRefreshToken(id, examName) {
            Swal.fire({
                title: 'Rilis Token Baru?',
                html: `Apakah Anda yakin ingin membuat token ujian baru untuk <strong>${escapeHtml(examName)}</strong>?<br><br><small style="color:#d97706;">Perhatian: Token lama tidak akan berlaku lagi untuk siswa yang belum login.</small>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fa-solid fa-arrows-rotate"></i> Ya, Buat Token Baru',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `../../controllers/AdminController.php?action=refresh_token_exam&id=${id}`;
                }
            });
        }

        function confirmDeleteExam(id, examName) {
            Swal.fire({
                title: 'Hapus Jadwal Ujian?',
                html: `Apakah Anda yakin ingin menghapus jadwal <strong>${escapeHtml(examName)}</strong>?<br><br><small style="color:#ef4444;">Perhatian: Seluruh sesi ujian dan data hasil pengerjaan siswa yang terhubung dengan ujian ini akan ikut terhapus.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fa-solid fa-trash-can"></i> Ya, Hapus Ujian!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `../../controllers/AdminController.php?action=delete_exam&id=${id}`;
                }
            });
        }

        function escapeHtml(text) {
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = "0.5s";
                alert.style.opacity = "0";
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>

<?php include __DIR__ . '/footer.php'; ?>