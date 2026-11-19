<?php 
require_once '../../config/database.php';
require_once '../../config/security.php'; 
protect_page(); 

$active_nav = 'guru';
$page_title = 'Manajemen Guru & Mapel';

// 1. Parameter Pencarian & Filter
$search = trim($_GET['q'] ?? '');
$filter_mapel = trim($_GET['mapel'] ?? '');
$filter_has_mapel = trim($_GET['status_mapel'] ?? ''); // 'all', 'has', 'empty'

// 2. Query Seluruh Data Guru dan Relasi Mata Pelajaran
$queryStr = "SELECT u.id as user_id, u.username, u.nama_lengkap, u.token,
                    s.id as subject_id, s.nama_mapel, s.kelas as mapel_kelas,
                    (SELECT COUNT(*) FROM questions WHERE subject_id = s.id) as jml_soal
             FROM users u 
             LEFT JOIN subjects s ON u.id = s.guru_id 
             WHERE u.role_id = 2 
             ORDER BY u.nama_lengkap ASC, s.nama_mapel ASC";

$stmt = $pdo->query($queryStr);
$rawData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Kelompokkan Data per Guru
$teachers = [];
$allMapelList = [];

foreach ($rawData as $row) {
    $uid = $row['user_id'];
    if (!isset($teachers[$uid])) {
        $teachers[$uid] = [
            'id' => $row['user_id'],
            'username' => $row['username'],
            'nama_lengkap' => $row['nama_lengkap'],
            'token' => $row['token'],
            'subjects' => [],
            'total_soal' => 0
        ];
    }

    if (!empty($row['subject_id'])) {
        $teachers[$uid]['subjects'][] = [
            'id' => $row['subject_id'],
            'nama_mapel' => $row['nama_mapel'],
            'kelas' => $row['mapel_kelas'],
            'jml_soal' => (int)$row['jml_soal']
        ];
        $teachers[$uid]['total_soal'] += (int)$row['jml_soal'];
        if (!in_array($row['nama_mapel'], $allMapelList)) {
            $allMapelList[] = $row['nama_mapel'];
        }
    }
}
sort($allMapelList);

// 4. Terapkan Filter & Pencarian pada Array Guru
$filteredTeachers = [];
foreach ($teachers as $t) {
    if ($search !== '') {
        $matchSearch = (stripos($t['nama_lengkap'], $search) !== false) || (stripos($t['username'], $search) !== false);
        if (!$matchSearch) {
            foreach ($t['subjects'] as $sub) {
                if (stripos($sub['nama_mapel'], $search) !== false || stripos($sub['kelas'], $search) !== false) {
                    $matchSearch = true;
                    break;
                }
            }
        }
        if (!$matchSearch) continue;
    }

    if ($filter_mapel !== '') {
        $matchMapel = false;
        foreach ($t['subjects'] as $sub) {
            if (strcasecmp($sub['nama_mapel'], $filter_mapel) === 0) {
                $matchMapel = true;
                break;
            }
        }
        if (!$matchMapel) continue;
    }

    if ($filter_has_mapel === 'has' && count($t['subjects']) === 0) continue;
    if ($filter_has_mapel === 'empty' && count($t['subjects']) > 0) continue;

    $filteredTeachers[] = $t;
}

// 5. Ringkasan Statistik Guru Global
$totalGuruGlobal = count($teachers);
$totalMapelPenugasan = (int)$pdo->query("SELECT COUNT(*) FROM subjects WHERE guru_id IS NOT NULL")->fetchColumn();
$totalSoalGuru = (int)$pdo->query("SELECT COUNT(*) FROM questions q JOIN subjects s ON q.subject_id = s.id WHERE s.guru_id IS NOT NULL")->fetchColumn();
$totalGuruTanpaMapel = 0;
foreach ($teachers as $t) {
    if (count($t['subjects']) === 0) $totalGuruTanpaMapel++;
}

$classesFromDb = $pdo->query("SELECT DISTINCT kelas FROM users WHERE role_id = 3 AND kelas IS NOT NULL AND TRIM(kelas) != '' ORDER BY kelas ASC")->fetchAll(PDO::FETCH_COLUMN);
$availableClasses = !empty($classesFromDb) ? $classesFromDb : ['7A', '7B', '7C', '7D', '8A', '8B', '8C', '8D', '9A', '9B', '9C', '9D'];

$presetMapel = [
    'Matematika', 'IPA', 'IPS', 'Bahasa Indonesia', 'Bahasa Inggris',
    'Pendidikan Agama Islam', 'Pendidikan Pancasila / PKn', 'Informatika',
    'Seni Budaya', 'PJOK', 'Prakarya', 'Bahasa Jawa'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Guru - Admin Panel - Espega Sinau</title>
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
            grid-template-columns: 2fr 1.3fr 1.2fr auto;
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

        .teacher-avatar {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            background: var(--adm-blue-50);
            color: var(--primary);
            border: 1px solid var(--adm-blue-100);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .subjects-container {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .subject-badge-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 4px 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.78rem;
        }
        .sub-tag-mapel {
            font-weight: 700;
            color: var(--text-main);
        }
        .sub-tag-kelas {
            background: var(--adm-slate-100);
            color: var(--adm-slate-700);
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 0.72rem;
            font-weight: 600;
        }
        .sub-tag-soal {
            background: var(--adm-blue-50);
            color: var(--primary);
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 0.72rem;
            font-weight: 600;
        }

        .token-code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
            font-weight: 700;
            letter-spacing: 1.2px;
            color: var(--primary);
            background: var(--adm-blue-50);
            padding: 3px 8px;
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
            padding: 4px 6px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            transition: 0.15s;
            text-decoration: none;
        }
        .btn-copy-token:hover {
            background: var(--adm-blue-50);
            border-color: var(--primary);
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
            max-width: 650px;
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
            font-size: 1.4rem;
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

        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 5px; color: var(--adm-slate-700); }
        .form-group input, .form-group select {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            outline: none;
            font-size: 0.88rem;
            color: var(--text-main);
            box-sizing: border-box;
            background: #ffffff;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        /* Mapel Repeater Box */
        .mapel-repeater-box {
            background: var(--adm-slate-50);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 18px;
            margin-top: 8px;
            margin-bottom: 14px;
        }
        .mapel-repeater-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 8px;
        }
        .mapel-repeater-title-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .mapel-repeater-title {
            font-weight: 700;
            font-size: 0.92rem;
            color: var(--text-main);
            margin: 0;
        }
        .mapel-count-badge {
            background: var(--adm-blue-50);
            color: var(--primary);
            border: 1px solid var(--adm-blue-100);
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
        }
        .mapel-item-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 14px 16px;
            margin-bottom: 12px;
            box-shadow: var(--shadow-sm);
            position: relative;
        }
        .mapel-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--adm-slate-100);
        }
        .mapel-card-title {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--text-main);
        }
        .mapel-index-badge {
            background: var(--adm-slate-100);
            color: var(--adm-slate-700);
            font-weight: 700;
            font-size: 0.72rem;
            padding: 1px 6px;
            border-radius: 4px;
        }
        .btn-delete-mapel-card {
            background: #ffffff;
            border: 1px solid var(--adm-danger-border);
            color: var(--adm-danger);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-delete-mapel-card:hover {
            background: var(--adm-danger-bg);
        }
        .mapel-card-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .mapel-field-label {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--adm-slate-700);
            margin-bottom: 5px;
        }
        .mapel-quick-chips {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 4px;
            margin-top: 6px;
        }
        .chips-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            font-weight: 600;
            margin-right: 2px;
        }
        .quick-chip-btn {
            background: var(--adm-slate-100);
            color: var(--adm-slate-700);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 2px 6px;
            cursor: pointer;
            transition: 0.15s;
        }
        .quick-chip-btn:hover {
            background: var(--adm-blue-50);
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-add-mapel-card {
            background: #ffffff;
            border: 1.5px dashed var(--border-color);
            border-radius: var(--radius-sm);
            padding: 10px;
            color: var(--primary);
            font-size: 0.84rem;
            font-weight: 700;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-add-mapel-card:hover {
            background: var(--adm-blue-50);
            border-color: var(--primary);
        }

        .mapel-tips-box {
            margin-top: 12px;
            padding: 10px 14px;
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 0.78rem;
            color: var(--adm-slate-700);
            display: flex;
            align-items: flex-start;
            gap: 8px;
            line-height: 1.45;
        }

        @media (max-width: 900px) {
            .filter-grid { grid-template-columns: 1fr; }
            .mapel-card-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="admin-body">

<?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Notifikasi Status -->
    <?php if (isset($_GET['status'])): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check" style="font-size: 1.1rem;"></i>
            <div>
                <?php 
                    if ($_GET['status'] == 'success') echo "Data guru dan mata pelajaran berhasil ditambahkan.";
                    elseif ($_GET['status'] == 'updated') echo "Data guru dan penugasan mapel berhasil diperbarui.";
                    elseif ($_GET['status'] == 'deleted') echo "Data guru beserta mapel terkait berhasil dihapus.";
                    elseif ($_GET['status'] == 'token_reset') echo "Token login guru berhasil di-reset dengan kode baru.";
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
            <h1>Manajemen Guru & Mata Pelajaran</h1>
            <p>Kelola data akun guru pengampu, kredensial token login, serta pengaturan penugasan mata pelajaran dan kelas.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button type="button" class="btn btn-primary" onclick="openAddGuruModal()">
                <i class="fa-solid fa-plus"></i>
                <span>Tambah Guru Baru</span>
            </button>
            <a href="manajemen_soal.php" class="btn btn-outline">
                <i class="fa-solid fa-book-open"></i>
                <span>Bank Soal Pusat</span>
            </a>
        </div>
    </div>

    <!-- Statistik Guru -->
    <div class="adm-stats-grid">
        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-chalkboard-user"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= $totalGuruGlobal ?></div>
                <div class="adm-stat-label">Total Guru Pengampu</div>
            </div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-book"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= $totalMapelPenugasan ?></div>
                <div class="adm-stat-label">Penugasan Mapel Aktif</div>
            </div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-file-lines"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= number_format($totalSoalGuru) ?></div>
                <div class="adm-stat-label">Total Soal Dibuat Guru</div>
            </div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-icon" style="color: <?= ($totalGuruTanpaMapel > 0) ? 'var(--adm-warning)' : 'var(--adm-success)' ?>;">
                <i class="fa-solid fa-circle-question"></i>
            </div>
            <div>
                <div class="adm-stat-num" style="<?= ($totalGuruTanpaMapel > 0) ? 'color: var(--adm-warning);' : '' ?>">
                    <?= $totalGuruTanpaMapel ?>
                </div>
                <div class="adm-stat-label">Guru Belum Ada Mapel</div>
            </div>
        </div>
    </div>

    <!-- Card Tabel Guru -->
    <div class="card">
        
        <!-- Toolbar Filter & Search -->
        <div class="filter-section">
            <form method="GET" action="manajemen_guru.php" id="filterForm">
                <div class="filter-grid">
                    <div>
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Cari nama guru, NIP/username, atau mapel..." class="filter-input">
                    </div>
                    <div>
                        <select name="mapel" onchange="this.form.submit()" class="filter-input">
                            <option value="">Semua Mata Pelajaran</option>
                            <?php foreach($allMapelList as $m): ?>
                                <option value="<?= htmlspecialchars($m) ?>" <?= (strcasecmp($filter_mapel, $m) === 0) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <select name="status_mapel" onchange="this.form.submit()" class="filter-input">
                            <option value="">Semua Status Guru</option>
                            <option value="has" <?= ($filter_has_mapel === 'has') ? 'selected' : '' ?>>Sudah Ada Mapel</option>
                            <option value="empty" <?= ($filter_has_mapel === 'empty') ? 'selected' : '' ?>>Belum Ada Mapel</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary" style="padding: 9px 14px;">
                            <i class="fa-solid fa-filter"></i> Filter
                        </button>
                    </div>
                </div>

                <div class="filter-badge-bar">
                    <div>
                        <span>Menampilkan <strong><?= count($filteredTeachers) ?></strong> guru</span>
                        <?php if (!empty($filter_mapel)): ?>
                            &bull; <span>Mapel: <strong><?= htmlspecialchars($filter_mapel) ?></strong></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($search) || !empty($filter_mapel) || !empty($filter_has_mapel)): ?>
                        <a href="manajemen_guru.php" style="color: var(--adm-danger); text-decoration: none; font-weight: 600;">
                            <i class="fa-solid fa-xmark"></i> Reset Filter
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tabel Data Guru -->
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">No</th>
                        <th style="width: 280px;">Identitas Guru</th>
                        <th>Mata Pelajaran & Kelas yang Diampu</th>
                        <th style="width: 150px;">Token Login</th>
                        <th style="text-align: right; width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filteredTeachers)): ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state" style="padding: 50px 20px; text-align: center; color: var(--text-muted);">
                                    <i class="fa-solid fa-chalkboard-user" style="font-size: 2.8rem; margin-bottom: 12px; color: var(--adm-slate-400);"></i>
                                    <h4 style="color: var(--text-main); margin-bottom: 4px;">Tidak Ada Data Guru Ditemukan</h4>
                                    <p style="font-size: 0.85rem;">Coba sesuaikan kata kunci pencarian atau ubah pilihan filter mata pelajaran.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $no = 1;
                        foreach($filteredTeachers as $t): 
                            $tJson = htmlspecialchars(json_encode($t), ENT_QUOTES, 'UTF-8');
                            $initial = strtoupper(mb_substr($t['nama_lengkap'], 0, 1));
                        ?>
                            <tr>
                                <td style="text-align: center; color: var(--text-muted); font-size: 0.8rem;"><?= $no++ ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div class="teacher-avatar"><?= $initial ?></div>
                                        <div>
                                            <div style="font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($t['nama_lengkap']) ?></div>
                                            <div style="font-size: 0.78rem; color: var(--text-muted); font-family: 'JetBrains Mono', monospace;">
                                                NIP: <?= htmlspecialchars($t['username']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if (empty($t['subjects'])): ?>
                                        <span style="font-size: 0.8rem; color: var(--text-muted); background: var(--adm-slate-100); padding: 3px 8px; border-radius: 4px;">
                                            Belum Ditugaskan Mapel
                                        </span>
                                    <?php else: ?>
                                        <div class="subjects-container">
                                            <?php foreach($t['subjects'] as $sub): ?>
                                                <div class="subject-badge-card">
                                                    <span class="sub-tag-mapel"><i class="fa-solid fa-book" style="color: var(--primary); font-size: 0.75rem;"></i> <?= htmlspecialchars($sub['nama_mapel']) ?></span>
                                                    <span class="sub-tag-kelas">Kelas: <?= htmlspecialchars($sub['kelas'] ?: '-') ?></span>
                                                    <span class="sub-tag-soal"><?= $sub['jml_soal'] ?> Soal</span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <span class="token-code"><?= htmlspecialchars($t['token']) ?></span>
                                        <button type="button" class="btn-copy-token" title="Salin Token" 
                                                onclick="copyToken('<?= htmlspecialchars($t['token']) ?>', this)">
                                            <i class="fa-solid fa-copy"></i>
                                        </button>
                                        <a href="../../controllers/AdminController.php?action=reset_token_guru&id=<?= $t['id'] ?>" 
                                           class="btn-copy-token" title="Acak Token Login Baru"
                                           onclick="return confirm('Buat token login baru untuk <?= htmlspecialchars($t['nama_lengkap'], ENT_QUOTES) ?>?')">
                                            <i class="fa-solid fa-arrows-rotate"></i>
                                        </a>
                                    </div>
                                </td>
                                <td style="text-align: right; vertical-align: middle;">
                                    <div style="display: flex; justify-content: flex-end; gap: 6px;">
                                        <button type="button" class="btn btn-sm btn-outline" title="Edit Guru & Mapel"
                                                onclick="openEditGuruModal(<?= $tJson ?>)">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline" style="color: var(--adm-danger); border-color: var(--adm-danger-border);" title="Hapus Guru"
                                                onclick="confirmDeleteGuru(<?= $t['id'] ?>, '<?= htmlspecialchars($t['nama_lengkap'], ENT_QUOTES) ?>')">
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

    <!-- MODAL 1: Tambah Guru Baru -->
    <div id="modalAddGuru" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3><i class="fa-solid fa-plus"></i> Tambah Guru & Mata Pelajaran</h3>
                <button type="button" class="modal-close" onclick="closeModal('modalAddGuru')">&times;</button>
            </div>
            <form action="../../controllers/AdminController.php?action=add_guru" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Lengkap Guru (beserta gelar) <span style="color:var(--adm-danger);">*</span></label>
                        <input type="text" name="nama_lengkap" placeholder="Contoh: Dra. Hj. Siti Aminah, M.Pd" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="form-group">
                            <label>Username / NIP <span style="color:var(--adm-danger);">*</span></label>
                            <input type="text" name="username" placeholder="Contoh: 19800101..." required>
                        </div>
                        <div class="form-group">
                            <label>Token Login <small style="color:var(--text-muted);">(Otomatis/Manual)</small></label>
                            <input type="text" name="token" id="add_guru_token" placeholder="Biarkan kosong untuk auto-generate">
                        </div>
                    </div>

                    <div class="mapel-repeater-box">
                        <div class="mapel-repeater-header">
                            <div class="mapel-repeater-title-wrap">
                                <label class="mapel-repeater-title"><i class="fa-solid fa-book"></i> Penugasan Mata Pelajaran & Kelas</label>
                                <span class="mapel-count-badge" id="add_mapel_count_badge">1 Mapel Ditugaskan</span>
                            </div>
                        </div>

                        <div id="add_mapel_container"></div>

                        <button type="button" class="btn-add-mapel-card" onclick="addMapelRow('add_mapel_container')">
                            <i class="fa-solid fa-plus"></i> Tambah Mata Pelajaran Lain
                        </button>

                        <div class="mapel-tips-box">
                            <i class="fa-solid fa-circle-info" style="color: var(--primary); font-size: 1rem; margin-top: 1px;"></i>
                            <div>
                                <strong>Tips Pengisian:</strong> Ketik nama mapel atau gunakan pilihan cepat di bawah kotak. Untuk kelas sasaran, pisahkan dengan tanda koma (contoh: <code>7A, 7B, 7C</code>).
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('modalAddGuru')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Data Guru</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Edit Guru & Mapel -->
    <div id="modalEditGuru" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3><i class="fa-solid fa-pen-to-square"></i> Edit Data Guru & Mapel</h3>
                <button type="button" class="modal-close" onclick="closeModal('modalEditGuru')">&times;</button>
            </div>
            <form action="../../controllers/AdminController.php?action=edit_guru" method="POST">
                <input type="hidden" name="id" id="edit_guru_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Lengkap Guru <span style="color:var(--adm-danger);">*</span></label>
                        <input type="text" name="nama_lengkap" id="edit_guru_nama" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="form-group">
                            <label>Username / NIP <span style="color:var(--adm-danger);">*</span></label>
                            <input type="text" name="username" id="edit_guru_user" required>
                        </div>
                        <div class="form-group">
                            <label>Token Login</label>
                            <input type="text" name="token" id="edit_guru_token" required>
                        </div>
                    </div>

                    <div class="mapel-repeater-box">
                        <div class="mapel-repeater-header">
                            <div class="mapel-repeater-title-wrap">
                                <label class="mapel-repeater-title"><i class="fa-solid fa-book"></i> Penugasan Mata Pelajaran & Kelas</label>
                                <span class="mapel-count-badge" id="edit_mapel_count_badge">1 Mapel Ditugaskan</span>
                            </div>
                        </div>

                        <div id="edit_mapel_container"></div>

                        <button type="button" class="btn-add-mapel-card" onclick="addMapelRow('edit_mapel_container')">
                            <i class="fa-solid fa-plus"></i> Tambah Mata Pelajaran Lain
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('modalEditGuru')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Datalist Autocomplete Presets -->
    <datalist id="datalistMapelList">
        <?php foreach ($presetMapel as $m): ?>
            <option value="<?= htmlspecialchars($m) ?>"></option>
        <?php endforeach; ?>
    </datalist>

    <datalist id="datalistKelasList">
        <?php foreach ($availableClasses as $k): ?>
            <option value="<?= htmlspecialchars($k) ?>"></option>
        <?php endforeach; ?>
    </datalist>

    <script>
        const AVAILABLE_CLASSES = <?= json_encode($availableClasses) ?>;
        const PRESET_MAPEL = <?= json_encode($presetMapel) ?>;

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

        function openAddGuruModal() {
            const randomToken = Math.random().toString(36).substring(2, 8).toUpperCase();
            document.getElementById('add_guru_token').value = randomToken;
            
            const container = document.getElementById('add_mapel_container');
            if (container && container.children.length === 0) {
                addMapelRow('add_mapel_container');
            }
            updateMapelCounter('add_mapel_container');
            openModal('modalAddGuru');
        }

        function openEditGuruModal(data) {
            document.getElementById('edit_guru_id').value = data.id || '';
            document.getElementById('edit_guru_nama').value = data.nama_lengkap || '';
            document.getElementById('edit_guru_user').value = data.username || '';
            document.getElementById('edit_guru_token').value = data.token || '';

            const container = document.getElementById('edit_mapel_container');
            container.innerHTML = '';

            const subjects = data.subjects || [];
            if (subjects.length === 0) {
                addMapelRow('edit_mapel_container');
            } else {
                subjects.forEach(sub => {
                    addMapelRow('edit_mapel_container', sub);
                });
            }

            openModal('modalEditGuru');
        }

        function renderKelasChips() {
            let chipsHtml = '';
            const previewClasses = AVAILABLE_CLASSES.slice(0, 6);
            previewClasses.forEach(cls => {
                chipsHtml += `<button type="button" class="quick-chip-btn chip-kelas" onclick="appendKelas(this, '${escapeHtml(cls)}')">+${escapeHtml(cls)}</button>`;
            });
            chipsHtml += `
                <button type="button" class="quick-chip-btn" onclick="appendKelas(this, 'Semua 7')">Semua 7</button>
                <button type="button" class="quick-chip-btn" onclick="appendKelas(this, 'Semua 8')">Semua 8</button>
                <button type="button" class="quick-chip-btn" onclick="appendKelas(this, 'Semua 9')">Semua 9</button>
            `;
            return chipsHtml;
        }

        function addMapelRow(containerId, data = {}) {
            const container = document.getElementById(containerId);
            if (!container) return;

            const subId = data.id || '';
            const mapelVal = data.nama_mapel || '';
            const kelasVal = data.kelas || '';

            const card = document.createElement('div');
            card.className = 'mapel-item-card';
            card.innerHTML = `
                <input type="hidden" name="subject_id[]" value="${escapeHtml(String(subId))}">
                <div class="mapel-card-header">
                    <div class="mapel-card-title">
                        <span class="mapel-index-badge">#1</span>
                        <span>Mata Pelajaran & Kelas</span>
                    </div>
                    <button type="button" class="btn-delete-mapel-card" onclick="removeMapelCard(this, '${containerId}')" title="Hapus Mapel Ini">
                        <i class="fa-solid fa-trash-can"></i>
                        <span>Hapus</span>
                    </button>
                </div>
                <div class="mapel-card-grid">
                    <div>
                        <label class="mapel-field-label">
                            <i class="fa-solid fa-book" style="color: var(--primary);"></i>
                            <span>Nama Mata Pelajaran</span>
                            <span style="color: var(--adm-danger);">*</span>
                        </label>
                        <input type="text" name="mapel[]" list="datalistMapelList" value="${escapeHtml(mapelVal)}" placeholder="Ketik / pilih mapel (misal: IPA)" required autocomplete="off">
                        <div class="mapel-quick-chips">
                            <span class="chips-label">Cepat:</span>
                            <button type="button" class="quick-chip-btn" onclick="fillMapel(this, 'Matematika')">Matematika</button>
                            <button type="button" class="quick-chip-btn" onclick="fillMapel(this, 'IPA')">IPA</button>
                            <button type="button" class="quick-chip-btn" onclick="fillMapel(this, 'Bahasa Indonesia')">B. Indo</button>
                            <button type="button" class="quick-chip-btn" onclick="fillMapel(this, 'Bahasa Inggris')">B. Inggris</button>
                            <button type="button" class="quick-chip-btn" onclick="fillMapel(this, 'Informatika')">Informatika</button>
                        </div>
                    </div>
                    <div>
                        <label class="mapel-field-label">
                            <i class="fa-solid fa-school" style="color: var(--primary);"></i>
                            <span>Kelas Sasaran</span>
                            <span style="color: var(--adm-danger);">*</span>
                        </label>
                        <input type="text" name="mapel_kelas[]" list="datalistKelasList" value="${escapeHtml(kelasVal)}" placeholder="Misal: 7A, 7B, 7C" required autocomplete="off">
                        <div class="mapel-quick-chips">
                            <span class="chips-label">Kelas:</span>
                            ${renderKelasChips()}
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(card);
            updateMapelCounter(containerId);
        }

        function removeMapelCard(btn, containerId) {
            const container = document.getElementById(containerId);
            if (!container) return;

            if (container.children.length <= 1) {
                const card = btn.closest('.mapel-item-card');
                const inputs = card.querySelectorAll('input[type="text"]');
                inputs.forEach(inp => inp.value = '');
                inputs[0].focus();
                return;
            }

            const card = btn.closest('.mapel-item-card');
            card.remove();
            updateMapelCounter(containerId);
        }

        function updateMapelCounter(containerId) {
            const container = document.getElementById(containerId);
            if (!container) return;

            const cards = container.querySelectorAll('.mapel-item-card');
            cards.forEach((card, idx) => {
                const badge = card.querySelector('.mapel-index-badge');
                if (badge) badge.textContent = `#${idx + 1}`;
            });

            const badgeId = containerId === 'add_mapel_container' ? 'add_mapel_count_badge' : 'edit_mapel_count_badge';
            const badgeEl = document.getElementById(badgeId);
            if (badgeEl) {
                badgeEl.textContent = `${cards.length} Mapel Ditugaskan`;
            }
        }

        function fillMapel(btn, val) {
            const card = btn.closest('.mapel-item-card');
            if (!card) return;
            const input = card.querySelector('input[name="mapel[]"]');
            if (input) {
                input.value = val;
                input.focus();
            }
        }

        function appendKelas(btn, val) {
            const card = btn.closest('.mapel-item-card');
            if (!card) return;
            const input = card.querySelector('input[name="mapel_kelas[]"]');
            if (!input) return;

            let current = input.value.trim();
            if (val.startsWith('Semua ')) {
                const grade = val.split(' ')[1];
                let matched = AVAILABLE_CLASSES.filter(c => c.startsWith(grade));
                if (matched.length === 0) {
                    matched = [grade + 'A', grade + 'B', grade + 'C', grade + 'D'];
                }
                let existingList = current ? current.split(',').map(s => s.trim()).filter(Boolean) : [];
                matched.forEach(cls => {
                    if (!existingList.includes(cls)) {
                        existingList.push(cls);
                    }
                });
                input.value = existingList.join(', ');
            } else {
                if (!current) {
                    input.value = val;
                } else {
                    let existingList = current.split(',').map(s => s.trim()).filter(Boolean);
                    if (!existingList.includes(val)) {
                        existingList.push(val);
                        input.value = existingList.join(', ');
                    }
                }
            }
            input.focus();
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('add_mapel_container')) {
                addMapelRow('add_mapel_container');
            }
        });

        function copyToken(tokenText, btnElement) {
            navigator.clipboard.writeText(tokenText).then(() => {
                const originalHtml = btnElement.innerHTML;
                btnElement.innerHTML = '<i class="fa-solid fa-check" style="color:var(--adm-success);"></i>';
                setTimeout(() => {
                    btnElement.innerHTML = originalHtml;
                }, 1500);
            }).catch(err => {
                prompt("Salin token login:", tokenText);
            });
        }

        function confirmDeleteGuru(id, namaGuru) {
            Swal.fire({
                title: 'Hapus Guru Ini?',
                html: `Apakah Anda yakin ingin menghapus akun guru <strong>${escapeHtml(namaGuru)}</strong>?<br><br><small style="color:#ef4444;">Perhatian: Seluruh mata pelajaran dan bank soal yang dibuat oleh guru ini akan ikut terhapus.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fa-solid fa-trash-can"></i> Ya, Hapus Guru',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `../../controllers/AdminController.php?action=delete_guru&id=${id}`;
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