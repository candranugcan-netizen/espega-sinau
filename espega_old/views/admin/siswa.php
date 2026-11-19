<?php 
require_once '../../config/database.php';
require_once '../../config/security.php';
protect_page();

$active_nav = 'siswa';
$page_title = 'Manajemen Data Siswa';

// 1. Parameter Filter & Search
$search = trim($_GET['q'] ?? '');
$filter_kelas = trim($_GET['kelas'] ?? '');
$filter_exam = trim($_GET['exam_id'] ?? '');
$filter_status = trim($_GET['status_filter'] ?? ''); // 'all', 'aktif', 'blokir'

// 2. Ambil data dropdown filter
$classes = $pdo->query("SELECT DISTINCT kelas FROM users WHERE role_id = 3 AND kelas IS NOT NULL AND TRIM(kelas) != '' ORDER BY kelas ASC")->fetchAll(PDO::FETCH_COLUMN);
$list_kelas = !empty($classes) ? $classes : ['7A', '7B', '7C', '7D', '8A', '8B', '8C', '8D', '9A', '9B', '9C', '9D'];

$exams = $pdo->query("SELECT e.id, e.nama_ujian, e.target_kelas, s.nama_mapel FROM exams e JOIN subjects s ON e.subject_id = s.id ORDER BY e.id DESC")->fetchAll(PDO::FETCH_ASSOC);
$list_exams = $exams ?: [];

// 3. Hitung Statistik Global
$totalSiswaGlobal = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 3")->fetchColumn();
$totalKelasGlobal = count($list_kelas);
$totalBlokirGlobal = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 3 AND status_blokir = '1'")->fetchColumn();
$totalSedangUjianGlobal = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM exam_sessions WHERE status_siswa = 'pengerjaan'")->fetchColumn();

// 4. Query Builder untuk Siswa (Tanpa duplikasi dengan subquery aggregasi)
$whereClauses = ["u.role_id = 3"];
$params = [];

if ($search !== '') {
    $whereClauses[] = "(u.username LIKE :search OR u.nama_lengkap LIKE :search)";
    $params['search'] = "%$search%";
}

if ($filter_kelas !== '') {
    $whereClauses[] = "u.kelas = :kelas";
    $params['kelas'] = $filter_kelas;
}

if ($filter_status === 'blokir') {
    $whereClauses[] = "u.status_blokir = '1'";
} elseif ($filter_status === 'aktif') {
    $whereClauses[] = "(u.status_blokir = '0' OR u.status_blokir IS NULL)";
}

if ($filter_exam !== '') {
    $whereClauses[] = "u.id IN (SELECT user_id FROM exam_sessions WHERE exam_id = :exam_filter)";
    $params['exam_filter'] = $filter_exam;
}

$whereSQL = "WHERE " . implode(" AND ", $whereClauses);

// Subquery kolom untuk status ujian spesifik jika filter_exam dipilih
$extraCols = "";
if ($filter_exam !== '') {
    $extraCols = ",
        (SELECT status_siswa FROM exam_sessions WHERE user_id = u.id AND exam_id = :exam_col LIMIT 1) as status_ujian_spesifik,
        (SELECT skor FROM exam_sessions WHERE user_id = u.id AND exam_id = :exam_col2 LIMIT 1) as skor_spesifik
    ";
    $params['exam_col'] = $filter_exam;
    $params['exam_col2'] = $filter_exam;
}

$queryCount = "SELECT COUNT(*) FROM users u $whereSQL";
$stmtCount = $pdo->prepare($queryCount);
$stmtCount->execute($params);
$totalFiltered = (int)$stmtCount->fetchColumn();

// 5. Konfigurasi Paginasi
$per_page_options = [10, 15, 25, 50, 100];
$per_page = (int)($_GET['per_page'] ?? 15);
if (!in_array($per_page, $per_page_options)) $per_page = 15;

$totalPages = max(1, (int)ceil($totalFiltered / $per_page));
$page = max(1, (int)($_GET['page'] ?? 1));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $per_page;

// 6. Query Ambil Data Siswa Sesuai Paginasi
$queryData = "
    SELECT u.id, u.username, u.nama_lengkap, u.kelas, u.token, u.status_blokir,
           (SELECT COUNT(*) FROM exam_sessions WHERE user_id = u.id) as total_sesi,
           (SELECT COUNT(*) FROM exam_sessions WHERE user_id = u.id AND status_siswa = 'pengerjaan') as sesi_pengerjaan,
           (SELECT COUNT(*) FROM exam_sessions WHERE user_id = u.id AND status_siswa = 'selesai') as sesi_selesai,
           (SELECT COUNT(*) FROM exam_sessions WHERE user_id = u.id AND (status_siswa = 'belum' OR status_siswa IS NULL)) as sesi_belum
           $extraCols
    FROM users u
    $whereSQL
    ORDER BY u.kelas ASC, u.nama_lengkap ASC
    LIMIT :offset, :limit
";
$stmtData = $pdo->prepare($queryData);
foreach ($params as $key => $val) {
    $stmtData->bindValue(":$key", $val);
}
$stmtData->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmtData->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmtData->execute();
$paginatedSiswa = $stmtData->fetchAll(PDO::FETCH_ASSOC);

// Ambil riwayat sesi untuk siswa pada halaman aktif
$studentIds = array_column($paginatedSiswa, 'id');
$studentSessionsMap = [];
if (!empty($studentIds)) {
    $inPlaceholders = implode(',', array_fill(0, count($studentIds), '?'));
    $queryStudentSessions = "
        SELECT es.user_id, es.exam_id, es.status_siswa, es.skor, es.mulai_ujian, es.selesai_ujian,
               e.nama_ujian, e.durasi, s.nama_mapel
        FROM exam_sessions es
        JOIN exams e ON es.exam_id = e.id
        LEFT JOIN subjects s ON e.subject_id = s.id
        WHERE es.user_id IN ($inPlaceholders)
        ORDER BY es.id DESC
    ";
    $stmtSS = $pdo->prepare($queryStudentSessions);
    $stmtSS->execute($studentIds);
    $allStudentSessions = $stmtSS->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allStudentSessions as $row) {
        $studentSessionsMap[$row['user_id']][] = $row;
    }
}

function build_page_url($targetPage, $targetPerPage = null) {
    $params = $_GET;
    $params['page'] = $targetPage;
    if ($targetPerPage !== null) $params['per_page'] = $targetPerPage;
    return 'siswa.php?' . http_build_query($params);
}

function get_student_initials($name) {
    $words = preg_split('/\s+/', trim($name));
    if (count($words) >= 2) return strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
    if (count($words) === 1 && strlen($words[0]) > 0) return strtoupper(mb_substr($words[0], 0, 2));
    return 'SW';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Siswa - Admin Panel - Espega Sinau</title>
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

        .main-layout {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 24px;
            align-items: flex-start;
        }

        .card {
            background: #ffffff;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }
        .card-title {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Filter Toolbar */
        .filter-section {
            background: var(--adm-slate-50);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 16px;
            margin-bottom: 22px;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1.3fr 1fr auto;
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
            font-size: 0.83rem;
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

        .user-cell { display: flex; align-items: center; gap: 12px; }
        .user-avatar {
            width: 36px; height: 36px; border-radius: var(--radius-sm);
            background: var(--adm-slate-100);
            color: var(--primary);
            border: 1px solid var(--border-color);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.82rem; font-weight: 700; flex-shrink: 0;
        }
        .user-name { font-weight: 700; color: var(--text-main); line-height: 1.3; }
        .user-nis {
            font-size: 0.77rem; color: var(--text-muted);
            font-family: 'JetBrains Mono', monospace;
        }

        .badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 9999px; font-size: 0.75rem;
            font-weight: 600; line-height: 1; white-space: nowrap;
        }
        .badge-kelas { background: var(--adm-slate-100); color: var(--adm-slate-800); border: 1px solid var(--border-color); font-weight: 700; border-radius: 4px; padding: 2px 7px; }
        .badge-success { background: var(--adm-success-bg); color: #065f46; border: 1px solid var(--adm-success-border); }
        .badge-danger { background: var(--adm-danger-bg); color: #991b1b; border: 1px solid var(--adm-danger-border); }
        .badge-warning { background: var(--adm-blue-50); color: var(--primary); border: 1px solid var(--adm-blue-100); }
        .badge-info { background: var(--adm-blue-50); color: var(--primary); border: 1px solid var(--adm-blue-100); }
        .badge-neutral { background: var(--adm-slate-100); color: var(--adm-slate-600); border: 1px solid var(--border-color); }

        .token-code {
            font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; font-weight: 700;
            letter-spacing: 1.2px; color: var(--primary); background: var(--adm-blue-50);
            padding: 3px 8px; border-radius: 6px; border: 1px solid var(--adm-blue-100);
            display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-token-action {
            background: #ffffff; border: 1px solid var(--border-color); color: var(--primary);
            cursor: pointer; padding: 4px 6px; border-radius: 4px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 0.8rem; transition: 0.15s;
        }
        .btn-token-action:hover { background: var(--adm-blue-50); border-color: var(--primary); }

        .btn-detail-sesi {
            background: var(--adm-blue-50); color: var(--primary); border: 1px solid var(--adm-blue-100);
            cursor: pointer; padding: 3px 7px; border-radius: 4px;
            font-size: 0.74rem; font-weight: 600; transition: 0.2s;
            display: inline-flex; align-items: center; gap: 4px;
        }
        .btn-detail-sesi:hover { background: var(--primary); color: #ffffff; }

        /* Side Panel */
        .side-panel { display: flex; flex-direction: column; gap: 20px; }
        .import-zone {
            border: 2px dashed var(--adm-slate-300); padding: 22px 16px;
            border-radius: var(--radius-md); text-align: center;
            transition: 0.2s; background: var(--adm-slate-50); cursor: pointer;
        }
        .import-zone:hover { border-color: var(--primary); background: var(--adm-blue-50); }
        .import-zone .icon { font-size: 2rem; color: var(--primary); display: block; margin-bottom: 8px; }
        .import-zone p { font-size: 0.82rem; color: var(--text-muted); margin-bottom: 4px; }

        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 5px; color: var(--adm-slate-700); }
        .form-group input, .form-group select {
            width: 100%; padding: 9px 12px; border: 1px solid var(--border-color);
            border-radius: var(--radius-sm); outline: none; font-size: 0.88rem;
            color: var(--text-main); background: #ffffff; box-sizing: border-box;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        /* Modal */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);
            z-index: 1000; display: flex; align-items: center; justify-content: center;
            padding: 20px; box-sizing: border-box;
            opacity: 0; pointer-events: none; transition: opacity 0.2s ease;
        }
        .modal-overlay.active { opacity: 1; pointer-events: auto; }
        .modal-card {
            background: #ffffff; border-radius: var(--radius-lg);
            width: 100%; max-width: 580px; box-shadow: var(--shadow-lg);
            overflow: hidden; transform: scale(0.96) translateY(-10px);
            transition: transform 0.2s ease;
        }
        .modal-overlay.active .modal-card { transform: scale(1) translateY(0); }
        .modal-header {
            padding: 18px 24px; background: var(--adm-slate-50); border-bottom: 1px solid var(--border-color);
            display: flex; justify-content: space-between; align-items: center;
        }
        .modal-header h3 { font-size: 1.15rem; font-weight: 700; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 8px; }
        .modal-close {
            background: transparent; border: none; font-size: 1.4rem;
            color: var(--text-muted); cursor: pointer; padding: 2px 6px;
            border-radius: 6px; line-height: 1;
        }
        .modal-close:hover { background: var(--adm-slate-200); color: var(--text-main); }
        .modal-body { padding: 22px 24px; max-height: 75vh; overflow-y: auto; }
        .modal-footer {
            padding: 14px 24px; background: var(--adm-slate-50); border-top: 1px solid var(--border-color);
            display: flex; justify-content: flex-end; gap: 10px;
        }

        /* Pagination */
        .pagination-wrap {
            display: flex; justify-content: space-between; align-items: center;
            padding-top: 18px; margin-top: 16px; border-top: 1px solid var(--border-color);
            flex-wrap: wrap; gap: 15px;
        }
        .pagination-info {
            font-size: 0.84rem; color: var(--text-muted);
            display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
        }
        .pagination-nav { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
        .page-btn {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 34px; height: 34px; padding: 0 10px;
            border-radius: var(--radius-sm); border: 1px solid var(--border-color);
            background: #ffffff; color: var(--text-main);
            font-size: 0.82rem; font-weight: 600; text-decoration: none;
            transition: all 0.15s ease;
        }
        .page-btn:hover:not(.disabled):not(.active) {
            background: var(--adm-slate-100); border-color: var(--adm-slate-400); color: var(--primary);
        }
        .page-btn.active {
            background: var(--primary); border-color: var(--primary);
            color: #ffffff; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.25);
        }
        .page-btn.disabled { opacity: 0.35; cursor: not-allowed; pointer-events: none; background: var(--adm-slate-50); }
        .per-page-select {
            padding: 4px 8px; border: 1px solid var(--border-color); border-radius: 4px;
            font-size: 0.82rem; color: var(--text-main); background: #ffffff; outline: none; cursor: pointer;
        }

        @media (max-width: 1024px) {
            .main-layout { grid-template-columns: 1fr; }
            .filter-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 640px) {
            .filter-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="admin-body">

<?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1>Monitoring & Manajemen Siswa</h1>
            <p>Kelola data identitas siswa, token login ujian, serta pantau status pengerjaan setiap siswa.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button type="button" class="btn btn-primary" onclick="openModal('modalTambahSiswa')">
                <i class="fa-solid fa-plus"></i>
                <span>Tambah Siswa Manual</span>
            </button>
            <a href="kelola_sesi.php" class="btn btn-outline">
                <i class="fa-solid fa-clock"></i>
                <span>Kelola Sesi Ujian</span>
            </a>
        </div>
    </div>

    <!-- Statistik Cepat -->
    <div class="adm-stats-grid">
        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= number_format($totalSiswaGlobal) ?></div>
                <div class="adm-stat-label">Total Seluruh Siswa</div>
            </div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-school"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= $totalKelasGlobal ?></div>
                <div class="adm-stat-label">Kelas Terdaftar</div>
            </div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-icon" style="color: var(--adm-danger);">
                <i class="fa-solid fa-user-xmark"></i>
            </div>
            <div>
                <div class="adm-stat-num" style="<?= ($totalBlokirGlobal > 0) ? 'color: var(--adm-danger);' : '' ?>"><?= $totalBlokirGlobal ?></div>
                <div class="adm-stat-label">Siswa Terblokir</div>
            </div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-icon" style="color: var(--adm-success);">
                <i class="fa-solid fa-bolt"></i>
            </div>
            <div>
                <div class="adm-stat-num" style="<?= ($totalSedangUjianGlobal > 0) ? 'color: var(--adm-success);' : '' ?>"><?= $totalSedangUjianGlobal ?></div>
                <div class="adm-stat-label">Sedang Ujian Aktif</div>
            </div>
        </div>
    </div>

    <!-- Main Layout -->
    <div class="main-layout">

        <!-- Kolom Kiri: Data Siswa -->
        <div class="card">

            <!-- Filter & Search -->
            <div class="filter-section">
                <form method="GET" action="siswa.php" id="filterForm">
                    <input type="hidden" name="per_page" value="<?= $per_page ?>">
                    <div class="filter-grid">
                        <div>
                            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" 
                                   placeholder="Cari NIS atau nama siswa..." class="filter-input">
                        </div>
                        <div>
                            <select name="kelas" class="filter-input">
                                <option value="">Semua Kelas</option>
                                <?php foreach($list_kelas as $k): ?>
                                    <option value="<?= htmlspecialchars($k) ?>" <?= ($filter_kelas === $k) ? 'selected' : '' ?>>
                                        Kelas <?= htmlspecialchars($k) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <select name="exam_id" class="filter-input">
                                <option value="">Semua Ujian (Global)</option>
                                <?php foreach($list_exams as $ex): ?>
                                    <option value="<?= $ex['id'] ?>" <?= ($filter_exam == $ex['id']) ? 'selected' : '' ?>>
                                        [<?= htmlspecialchars($ex['target_kelas']) ?>] <?= htmlspecialchars($ex['nama_mapel']) ?> - <?= htmlspecialchars($ex['nama_ujian']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <select name="status_filter" class="filter-input">
                                <option value="">Semua Akun</option>
                                <option value="aktif" <?= ($filter_status === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                                <option value="blokir" <?= ($filter_status === 'blokir') ? 'selected' : '' ?>>Terblokir</option>
                            </select>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button type="submit" class="btn btn-primary btn-sm" style="padding: 9px 14px;">
                                <i class="fa-solid fa-filter"></i> Filter
                            </button>
                            <?php if ($search !== '' || $filter_kelas !== '' || $filter_exam !== '' || ($filter_status !== '' && $filter_status !== 'all')): ?>
                                <a href="siswa.php" class="btn btn-outline btn-sm" style="padding: 9px 12px;" title="Reset Filter">
                                    <i class="fa-solid fa-xmark"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="filter-badge-bar">
                        <div>
                            Menampilkan <strong><?= $totalFiltered > 0 ? ($offset + 1) : 0 ?> - <?= min($offset + $per_page, $totalFiltered) ?></strong>
                            dari <strong><?= number_format($totalFiltered) ?></strong> siswa
                            <?php if (!empty($filter_kelas)): ?>
                                &bull; Kelas: <strong><?= htmlspecialchars($filter_kelas) ?></strong>
                            <?php endif; ?>
                            <?php if (!empty($filter_exam)): ?>
                                &bull; <span style="color: var(--primary);">Filter Ujian Aktif</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tabel Data Siswa -->
            <?php if (empty($paginatedSiswa)): ?>
                <div class="empty-state" style="padding: 50px 20px; text-align: center; color: var(--text-muted);">
                    <i class="fa-solid fa-folder-open" style="font-size: 2.8rem; margin-bottom: 12px; color: var(--adm-slate-400);"></i>
                    <h3 style="font-size: 1.15rem; color: var(--text-main); margin-bottom: 6px;">Tidak Ada Siswa Ditemukan</h3>
                    <p style="font-size: 0.88rem; max-width: 480px; margin: 0 auto 16px;">
                        Tidak ditemukan data siswa yang sesuai dengan pencarian atau filter Anda.
                    </p>
                    <a href="siswa.php" class="btn btn-outline btn-sm">Reset Semua Filter</a>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 45px; text-align: center;">No</th>
                                <th>Identitas Siswa</th>
                                <th style="text-align: center;">Kelas</th>
                                <th>Token Login</th>
                                <th>Status Sesi Ujian</th>
                                <th style="text-align: center;">Akun</th>
                                <th style="text-align: center; width: 130px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = $offset + 1;
                            foreach($paginatedSiswa as $s):
                                $sId = $s['id'];
                                $sesiList = $studentSessionsMap[$sId] ?? [];
                                $sesiJson = htmlspecialchars(json_encode($sesiList), ENT_QUOTES, 'UTF-8');
                                $initials = get_student_initials($s['nama_lengkap']);
                            ?>
                                <tr>
                                    <td style="text-align: center; color: var(--text-muted); font-weight: 600; font-size: 0.8rem;"><?= $no++ ?></td>

                                    <!-- Identitas Siswa -->
                                    <td>
                                        <div class="user-cell">
                                            <div class="user-avatar">
                                                <?= $initials ?>
                                            </div>
                                            <div class="user-info">
                                                <span class="user-name"><?= htmlspecialchars($s['nama_lengkap']) ?></span>
                                                <span class="user-nis">NIS: <?= htmlspecialchars($s['username']) ?></span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Kelas -->
                                    <td style="text-align: center;">
                                        <span class="badge badge-kelas"><?= htmlspecialchars($s['kelas'] ?: '-') ?></span>
                                    </td>

                                    <!-- Token Login -->
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <span class="token-code"><?= htmlspecialchars($s['token']) ?></span>
                                            <button type="button" class="btn-token-action" title="Salin Token"
                                                    onclick="copyToken('<?= htmlspecialchars($s['token']) ?>', this)">
                                                <i class="fa-solid fa-copy"></i>
                                            </button>
                                            <button type="button" class="btn-token-action" title="Reset Token Baru"
                                                    onclick="confirmResetToken(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['nama_lengkap'])) ?>')">
                                                <i class="fa-solid fa-arrows-rotate"></i>
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Status Sesi Ujian -->
                                    <td>
                                        <?php if (!empty($filter_exam)): ?>
                                            <?php 
                                                $st = $s['status_ujian_spesifik'];
                                                if (!$st):
                                            ?>
                                                <span class="badge badge-neutral">Belum Ada Sesi</span>
                                            <?php elseif ($st === 'belum'): ?>
                                                <span class="badge badge-warning">Belum Mulai</span>
                                            <?php elseif ($st === 'pengerjaan'): ?>
                                                <span class="badge badge-info"><i class="fa-solid fa-clock"></i> Mengerjakan</span>
                                            <?php elseif ($st === 'selesai'): ?>
                                                <span class="badge badge-success"><i class="fa-solid fa-check"></i> Selesai (<?= $s['skor_spesifik'] ?>)</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                                <?php 
                                                    $totalSesi = (int)($s['total_sesi'] ?? 0);
                                                    $sesiPengerjaan = (int)($s['sesi_pengerjaan'] ?? 0);
                                                    $sesiSelesai = (int)($s['sesi_selesai'] ?? 0);

                                                    if ($totalSesi === 0) {
                                                        echo '<span class="badge badge-neutral">Belum Ada Sesi</span>';
                                                    } elseif ($sesiPengerjaan > 0) {
                                                        echo '<span class="badge badge-info"><i class="fa-solid fa-clock"></i> Mengerjakan (' . $sesiPengerjaan . ')</span>';
                                                    } elseif ($sesiSelesai === $totalSesi) {
                                                        echo '<span class="badge badge-success"><i class="fa-solid fa-check"></i> ' . $sesiSelesai . ' Selesai</span>';
                                                    } else {
                                                        echo '<span class="badge badge-warning">' . $totalSesi . ' Sesi Terdaftar</span>';
                                                    }
                                                ?>
                                                <?php if ($totalSesi > 0): ?>
                                                    <button type="button" class="btn-detail-sesi" 
                                                            onclick="showStudentSessions(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['nama_lengkap'])) ?>', '<?= htmlspecialchars(addslashes($s['kelas'])) ?>', <?= $sesiJson ?>)">
                                                        <i class="fa-solid fa-eye"></i> Detail
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Status Akun -->
                                    <td style="text-align: center;">
                                        <?php if ($s['status_blokir'] == '1'): ?>
                                            <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                                                <span class="badge badge-danger"><i class="fa-solid fa-user-xmark"></i> Terblokir</span>
                                                <button type="button" class="btn btn-sm" style="font-size: 0.72rem; padding: 2px 8px; background: var(--adm-blue-50); color: var(--primary); border: 1px solid var(--adm-blue-100);"
                                                        onclick="confirmUnblock(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['nama_lengkap'])) ?>')">
                                                    Buka
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge badge-success"><i class="fa-solid fa-check"></i> Aktif</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Aksi -->
                                    <td style="text-align: center;">
                                        <div style="display: flex; justify-content: center; gap: 6px;">
                                            <button type="button" class="btn btn-sm btn-outline" 
                                                    onclick="openEditModal(<?= $s['id'] ?>, '<?= htmlspecialchars($s['username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($s['nama_lengkap'], ENT_QUOTES) ?>', '<?= htmlspecialchars($s['kelas'], ENT_QUOTES) ?>')">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline" style="color: var(--adm-danger); border-color: var(--adm-danger-border);"
                                                    onclick="confirmDeleteSiswa(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['nama_lengkap'])) ?>')">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalFiltered > 0): ?>
                <div class="pagination-wrap">
                    <div class="pagination-info">
                        <span>Halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong> (<?= number_format($totalFiltered) ?> siswa)</span>
                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                            <label for="perPageSelect" style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">Per hal:</label>
                            <select id="perPageSelect" class="per-page-select" onchange="window.location.href = this.value;">
                                <?php foreach ($per_page_options as $opt): ?>
                                    <option value="<?= htmlspecialchars(build_page_url(1, $opt)) ?>" <?= ($per_page == $opt) ? 'selected' : '' ?>>
                                        <?= $opt ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <?php if ($totalPages > 1): ?>
                    <div class="pagination-nav">
                        <a href="<?= htmlspecialchars(build_page_url(1)) ?>" class="page-btn <?= ($page <= 1) ? 'disabled' : '' ?>" title="Pertama"><i class="fa-solid fa-angles-left"></i></a>
                        <a href="<?= htmlspecialchars(build_page_url($page - 1)) ?>" class="page-btn <?= ($page <= 1) ? 'disabled' : '' ?>" title="Sebelumnya"><i class="fa-solid fa-angle-left"></i></a>

                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        if ($startPage > 1) {
                            echo '<a href="' . htmlspecialchars(build_page_url(1)) . '" class="page-btn">1</a>';
                            if ($startPage > 2) echo '<span style="color:var(--text-muted); padding:0 4px;">...</span>';
                        }
                        for ($p = $startPage; $p <= $endPage; $p++) {
                            echo '<a href="' . htmlspecialchars(build_page_url($p)) . '" class="page-btn ' . ($p == $page ? 'active' : '') . '">' . $p . '</a>';
                        }
                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) echo '<span style="color:var(--text-muted); padding:0 4px;">...</span>';
                            echo '<a href="' . htmlspecialchars(build_page_url($totalPages)) . '" class="page-btn">' . $totalPages . '</a>';
                        }
                        ?>

                        <a href="<?= htmlspecialchars(build_page_url($page + 1)) ?>" class="page-btn <?= ($page >= $totalPages) ? 'disabled' : '' ?>" title="Berikutnya"><i class="fa-solid fa-angle-right"></i></a>
                        <a href="<?= htmlspecialchars(build_page_url($totalPages)) ?>" class="page-btn <?= ($page >= $totalPages) ? 'disabled' : '' ?>" title="Terakhir"><i class="fa-solid fa-angles-right"></i></a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Kolom Kanan: Side Panel -->
        <aside class="side-panel">

            <!-- Cetak Kartu Ujian (PDF) -->
            <div class="card" style="background: var(--adm-slate-900); color: #ffffff; border: 1px solid var(--adm-slate-800);">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.15rem; color: white;">
                        <i class="fa-solid fa-print"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1rem; color: white;">Cetak Kartu Ujian</h3>
                        <p style="margin: 0; font-size: 0.78rem; color: var(--adm-slate-400);">Generate kartu peserta ujian</p>
                    </div>
                </div>
                <p style="font-size: 0.82rem; margin-bottom: 14px; color: var(--adm-slate-300); line-height: 1.45;">
                    Kartu memuat NIS, Nama, Kelas, dan Token Login resmi siswa.
                </p>
                <a href="../../controllers/AdminController.php?action=cetak_kartu&kelas=<?= urlencode($filter_kelas) ?>&q=<?= urlencode($search) ?>" 
                   target="_blank" class="btn btn-primary" style="width: 100%;">
                    <i class="fa-solid fa-file-pdf"></i> Download PDF <?= !empty($filter_kelas) ? "(Kelas $filter_kelas)" : "(Semua)" ?>
                </a>
            </div>

            <!-- Import Siswa dari Excel -->
            <div class="card">
                <div class="card-title">
                    <i class="fa-solid fa-file-import" style="color: var(--primary);"></i>
                    <span>Import dari Excel</span>
                </div>
                <form action="../../controllers/AdminController.php?action=import_excel" method="POST" enctype="multipart/form-data">
                    <div class="import-zone" onclick="document.getElementById('file_excel').click()">
                        <i class="fa-solid fa-file-excel icon"></i>
                        <p><strong>Klik untuk memilih file</strong></p>
                        <p style="font-size: 0.76rem;">Format didukung: Excel (.xlsx)</p>
                        <input type="file" name="file_excel" id="file_excel" accept=".xlsx" required style="display:none;" onchange="updateFileName(this)">
                        <div id="file-name" style="font-size: 12px; color: var(--primary); font-weight: 700; margin-top: 8px;"></div>
                    </div>

                    <div style="margin: 14px 0; background: var(--adm-slate-50); border: 1px solid var(--border-color); padding: 10px 12px; border-radius: var(--radius-sm); font-size: 0.78rem; color: var(--adm-slate-700);">
                        <strong style="color: var(--text-main); display: block; margin-bottom: 4px;">Susunan Kolom Excel:</strong>
                        1. Kolom A: <b>NIS / Username</b><br>
                        2. Kolom B: <b>Nama Lengkap</b><br>
                        3. Kolom C: <b>Kelas</b> (Contoh: 7A, 8B)
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Upload & Import
                    </button>
                </form>
            </div>

            <!-- Export Excel -->
            <div class="card">
                <div class="card-title">
                    <i class="fa-solid fa-file-export" style="color: var(--primary);"></i>
                    <span>Backup Data Siswa</span>
                </div>
                <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 14px;">
                    Unduh master data siswa beserta token login ke file Excel (.xlsx).
                </p>
                <a href="../../controllers/AdminController.php?action=export_siswa" class="btn btn-outline" style="width: 100%; border-style: dashed; font-weight: 600;">
                    <i class="fa-solid fa-file-excel"></i> Download Excel (.xlsx)
                </a>
            </div>

        </aside>

    </div>

    <!-- MODAL: Detail Sesi Ujian Siswa -->
    <div id="modalSesiDetail" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <h3 id="modalSesiStudentName"><i class="fa-solid fa-clock"></i> Detail Sesi Ujian</h3>
                    <p id="modalSesiStudentInfo" style="font-size: 0.82rem; color: var(--text-muted); margin: 2px 0 0 0;"></p>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('modalSesiDetail')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="modalSesiContent"></div>
            </div>
            <div class="modal-footer">
                <a href="kelola_sesi.php" class="btn btn-sm btn-outline">Buka Kelola Sesi</a>
                <button type="button" class="btn btn-sm btn-primary" onclick="closeModal('modalSesiDetail')">Tutup</button>
            </div>
        </div>
    </div>

    <!-- MODAL: Edit Data Siswa -->
    <div id="modalEditSiswa" class="modal-overlay">
        <div class="modal-card" style="max-width: 460px;">
            <div class="modal-header">
                <h3><i class="fa-solid fa-pen-to-square"></i> Edit Data Siswa</h3>
                <button type="button" class="modal-close" onclick="closeModal('modalEditSiswa')">&times;</button>
            </div>
            <form action="../../controllers/AdminController.php?action=edit_siswa" method="POST">
                <input type="hidden" name="id" id="edit_siswa_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nomor Induk Siswa (NIS)</label>
                        <input type="text" name="username" id="edit_siswa_nis" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" id="edit_siswa_nama" required>
                    </div>
                    <div class="form-group">
                        <label>Kelas</label>
                        <input type="text" name="kelas" id="edit_siswa_kelas" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('modalEditSiswa')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Tambah Siswa Manual -->
    <div id="modalTambahSiswa" class="modal-overlay">
        <div class="modal-card" style="max-width: 460px;">
            <div class="modal-header">
                <h3><i class="fa-solid fa-user-plus"></i> Tambah Siswa Manual</h3>
                <button type="button" class="modal-close" onclick="closeModal('modalTambahSiswa')">&times;</button>
            </div>
            <form action="../../controllers/AdminController.php?action=add_siswa" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nomor Induk Siswa (NIS)</label>
                        <input type="text" name="username" placeholder="Contoh: 20240101" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" placeholder="Nama sesuai absen siswa" required>
                    </div>
                    <div class="form-group">
                        <label>Kelas</label>
                        <input type="text" name="kelas" placeholder="Contoh: 7A / 8B / 9C" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('modalTambahSiswa')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Data Siswa</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function updateFileName(input) {
        if (input.files && input.files[0]) {
            document.getElementById('file-name').innerHTML = "<i class='fa-solid fa-file-excel'></i> File: " + input.files[0].name;
        }
    }

    function copyToken(tokenText, btnElement) {
        navigator.clipboard.writeText(tokenText).then(() => {
            const orig = btnElement.innerHTML;
            btnElement.innerHTML = '<i class="fa-solid fa-check" style="color:var(--adm-success);"></i>';
            setTimeout(() => { btnElement.innerHTML = orig; }, 1500);
        }).catch(err => {
            prompt("Salin token:", tokenText);
        });
    }

    function openModal(id) { document.getElementById(id)?.classList.add('active'); }
    function closeModal(id) { document.getElementById(id)?.classList.remove('active'); }

    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) e.target.classList.remove('active');
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
    });

    function openEditModal(id, nis, nama, kelas) {
        document.getElementById('edit_siswa_id').value = id;
        document.getElementById('edit_siswa_nis').value = nis;
        document.getElementById('edit_siswa_nama').value = nama;
        document.getElementById('edit_siswa_kelas').value = kelas;
        openModal('modalEditSiswa');
    }

    function showStudentSessions(userId, nama, kelas, sessions) {
        document.getElementById('modalSesiStudentName').innerHTML = "<i class='fa-solid fa-clock'></i> " + escapeHtml(nama);
        document.getElementById('modalSesiStudentInfo').textContent = "Kelas " + kelas + " • " + sessions.length + " Sesi Ujian";

        const container = document.getElementById('modalSesiContent');
        if (!sessions || sessions.length === 0) {
            container.innerHTML = `<div style="text-align: center; padding: 25px 10px; color: var(--text-muted);"><i class="fa-solid fa-folder-open" style="font-size: 2rem; display: block; margin-bottom: 8px;"></i>Belum ada sesi ujian untuk siswa ini.</div>`;
        } else {
            let html = `<table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                <thead><tr style="background: var(--adm-slate-50); border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 8px 10px; text-align: left;">Mata Pelajaran & Ujian</th>
                    <th style="padding: 8px 10px; text-align: center;">Status</th>
                    <th style="padding: 8px 10px; text-align: center;">Skor</th>
                </tr></thead><tbody>`;

            sessions.forEach(s => {
                let badge = '';
                if (s.status_siswa === 'pengerjaan') badge = '<span class="badge badge-info"><i class="fa-solid fa-clock"></i> Mengerjakan</span>';
                else if (s.status_siswa === 'selesai') badge = '<span class="badge badge-success"><i class="fa-solid fa-check"></i> Selesai</span>';
                else badge = '<span class="badge badge-warning">Belum Mulai</span>';

                const skor = (s.status_siswa === 'selesai') ? `<strong>${s.skor ?? 0}</strong>` : '-';

                html += `<tr style="border-bottom: 1px solid var(--adm-slate-100);">
                    <td style="padding: 10px;"><div style="font-weight: 700; color: var(--text-main);">${escapeHtml(s.nama_mapel || '-')}</div>
                    <div style="font-size: 0.78rem; color: var(--text-muted);">${escapeHtml(s.nama_ujian || '-')} (${s.durasi || 0} menit)</div></td>
                    <td style="padding: 10px; text-align: center;">${badge}</td>
                    <td style="padding: 10px; text-align: center;">${skor}</td></tr>`;
            });

            html += `</tbody></table>`;
            container.innerHTML = html;
        }
        openModal('modalSesiDetail');
    }

    function escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    function confirmDeleteSiswa(id, nama) {
        Swal.fire({
            title: 'Hapus Data Siswa?',
            html: `Apakah Anda yakin ingin menghapus data siswa <b>${nama}</b>?<br><br><span style="font-size:0.85rem; color:#dc2626;">Riwayat ujian, nilai, dan semua data siswa ini akan ikut terhapus secara permanen.</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fa-solid fa-trash-can"></i> Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `../../controllers/AdminController.php?action=delete_user&id=${id}`;
            }
        });
    }

    function confirmResetToken(id, nama) {
        Swal.fire({
            title: 'Reset Token Login?',
            html: `Buat token login baru untuk siswa <b>${nama}</b>?<br><br><span style="font-size:0.85rem; color:#b45309;">Token lama tidak akan berlaku lagi.</span>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fa-solid fa-arrows-rotate"></i> Ya, Reset Token',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `../../controllers/AdminController.php?action=reset_token_siswa&id=${id}`;
            }
        });
    }

    function confirmUnblock(id, nama) {
        Swal.fire({
            title: 'Buka Blokir Siswa?',
            html: `Apakah Anda yakin ingin membuka status blokir untuk siswa <b>${nama}</b>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fa-solid fa-lock-open"></i> Ya, Buka Blokir',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `../../controllers/AdminController.php?action=unblock&id=${id}`;
            }
        });
    }

    window.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const error = urlParams.get('error');

        const statusMessages = {
            'success': { icon: 'success', title: 'Berhasil!', text: 'Data siswa berhasil ditambahkan.' },
            'imported': { icon: 'success', title: 'Import Berhasil!', text: 'Data siswa berhasil diimpor dari file Excel.' },
            'deleted': { icon: 'success', title: 'Terhapus!', text: 'Data siswa berhasil dihapus dari sistem.' },
            'updated': { icon: 'success', title: 'Diperbarui!', text: 'Data identitas siswa berhasil diperbarui.' },
            'token_reset': { icon: 'success', title: 'Token Direset!', text: 'Token login siswa berhasil di-reset dengan kode baru.' },
            'unblocked': { icon: 'success', title: 'Blokir Dibuka!', text: 'Status blokir siswa berhasil dibuka kembali.' },
        };

        if (status && statusMessages[status]) {
            Swal.fire({ ...statusMessages[status], confirmButtonColor: '#2563eb' });
        } else if (error) {
            Swal.fire({ icon: 'error', title: 'Gagal!', text: error, confirmButtonColor: '#dc2626' });
        }

        if (status || error) {
            urlParams.delete('status');
            urlParams.delete('error');
            const newQ = urlParams.toString();
            window.history.replaceState({}, document.title, window.location.pathname + (newQ ? '?' + newQ : ''));
        }
    });
    </script>

<?php include __DIR__ . '/footer.php'; ?>