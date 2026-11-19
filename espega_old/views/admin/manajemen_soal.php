<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/database.php';
require_once '../../config/security.php'; 
protect_page(); 

$active_nav = 'soal';
$page_title = 'Bank Soal Pusat';

// 1. Tentukan Tab Aktif ('bank' = Daftar Paket Bank Soal, 'soal' = Butir Soal)
$active_tab = $_GET['tab'] ?? (isset($_GET['bank_id']) ? 'soal' : 'bank');

// 2. Data Master (Guru, Mapel, dan Kelas)
$stmtGuru = $pdo->query("SELECT id, nama_lengkap FROM users WHERE role_id = 2 ORDER BY nama_lengkap ASC");
$list_guru = $stmtGuru->fetchAll(PDO::FETCH_ASSOC);

$stmtSubjects = $pdo->query("
    SELECT s.id, s.nama_mapel, s.kelas, s.guru_id, u.nama_lengkap as nama_guru 
    FROM subjects s 
    LEFT JOIN users u ON s.guru_id = u.id 
    ORDER BY s.nama_mapel ASC
");
$list_subjects = $stmtSubjects->fetchAll(PDO::FETCH_ASSOC);

// Daftar Kelas
$classesList = $pdo->query("SELECT DISTINCT kelas FROM users WHERE role_id = 3 AND kelas IS NOT NULL AND TRIM(kelas) != '' ORDER BY kelas ASC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($classesList)) {
    $classesList = ['7A', '7B', '7C', '7D', '8A', '8B', '8C', '8D', '9A', '9B', '9C', '9D'];
}

// 3. Ambil Semua Bank Soal untuk Switcher & Filter
$stmtAllBanks = $pdo->query("
    SELECT bs.*, s.nama_mapel, s.kelas as kelas_mapel, u.nama_lengkap as nama_guru,
           (SELECT COUNT(*) FROM questions WHERE bank_soal_id = bs.id) as total_soal
    FROM bank_soal bs
    JOIN subjects s ON bs.subject_id = s.id
    LEFT JOIN users u ON bs.guru_id = u.id
    ORDER BY bs.id DESC
");
$all_banks_unfiltered = $stmtAllBanks->fetchAll(PDO::FETCH_ASSOC);

// 4. Statistik Global
$total_bank_global = count($all_banks_unfiltered);
$total_bank_rilis  = count(array_filter($all_banks_unfiltered, fn($b) => $b['status_rilis'] === 'rilis'));
$total_bank_draft  = $total_bank_global - $total_bank_rilis;
$total_soal_global = (int)$pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$total_guru_global = (int)$pdo->query("SELECT COUNT(DISTINCT guru_id) FROM bank_soal")->fetchColumn();
$total_mapel_global = (int)$pdo->query("SELECT COUNT(DISTINCT subject_id) FROM bank_soal")->fetchColumn();

// =========================================================================
// LOGIKA TAB 1: PAKET BANK SOAL
// =========================================================================
$q_bank            = trim($_GET['q_bank'] ?? '');
$filter_guru_bank  = trim($_GET['guru_bank'] ?? '');
$filter_subj_bank  = trim($_GET['subject_bank'] ?? '');
$filter_stat_bank  = trim($_GET['status_bank'] ?? 'all');

$bankWhere = [];
$bankParams = [];

if ($q_bank !== '') {
    $bankWhere[] = "(bs.nama_bank LIKE :q_bank OR s.nama_mapel LIKE :q_bank OR u.nama_lengkap LIKE :q_bank)";
    $bankParams['q_bank'] = "%$q_bank%";
}
if ($filter_guru_bank !== '') {
    $bankWhere[] = "bs.guru_id = :guru_bank";
    $bankParams['guru_bank'] = $filter_guru_bank;
}
if ($filter_subj_bank !== '') {
    $bankWhere[] = "bs.subject_id = :subj_bank";
    $bankParams['subj_bank'] = $filter_subj_bank;
}
if ($filter_stat_bank === 'rilis') {
    $bankWhere[] = "bs.status_rilis = 'rilis'";
} elseif ($filter_stat_bank === 'draft') {
    $bankWhere[] = "bs.status_rilis = 'draft'";
}

$bankWhereSQL = !empty($bankWhere) ? "WHERE " . implode(" AND ", $bankWhere) : "";
$stmtTabBanks = $pdo->prepare("
    SELECT bs.*, s.nama_mapel, s.kelas as kelas_mapel, u.nama_lengkap as nama_guru,
           (SELECT COUNT(*) FROM questions WHERE bank_soal_id = bs.id) as total_soal
    FROM bank_soal bs
    JOIN subjects s ON bs.subject_id = s.id
    LEFT JOIN users u ON bs.guru_id = u.id
    $bankWhereSQL
    ORDER BY bs.id DESC
");
$stmtTabBanks->execute($bankParams);
$tab_bank_list = $stmtTabBanks->fetchAll(PDO::FETCH_ASSOC);

// =========================================================================
// LOGIKA TAB 2: BUTIR SOAL
// =========================================================================
$filter_bank_id = isset($_GET['bank_id']) ? (int)$_GET['bank_id'] : 0;
$search_soal    = trim($_GET['q'] ?? '');
$filter_guru_q  = trim($_GET['guru_id'] ?? '');
$filter_subj_q  = trim($_GET['subject_id'] ?? '');
$filter_kelas_q = trim($_GET['kelas'] ?? '');

$current_bank = null;
if ($filter_bank_id > 0) {
    foreach ($all_banks_unfiltered as $b) {
        if ($b['id'] == $filter_bank_id) {
            $current_bank = $b;
            break;
        }
    }
}

// Pagination Butir Soal
$page_soal = max(1, (int)($_GET['page'] ?? 1));
$limit_soal = 20;
$offset_soal = ($page_soal - 1) * $limit_soal;

$qWhere = [];
$qParams = [];

if ($filter_bank_id > 0) {
    $qWhere[] = "q.bank_soal_id = :bank_id";
    $qParams['bank_id'] = $filter_bank_id;
}
if ($filter_guru_q !== '') {
    $qWhere[] = "(bs.guru_id = :guru_q OR s.guru_id = :guru_q)";
    $qParams['guru_q'] = $filter_guru_q;
}
if ($filter_subj_q !== '') {
    $qWhere[] = "q.subject_id = :subj_q";
    $qParams['subj_q'] = $filter_subj_q;
}
if ($filter_kelas_q !== '') {
    $qWhere[] = "FIND_IN_SET(:kelas_q, q.kelas)";
    $qParams['kelas_q'] = $filter_kelas_q;
}
if ($search_soal !== '') {
    $qWhere[] = "(q.pertanyaan LIKE :search OR q.pil_a LIKE :search OR q.pil_b LIKE :search OR q.pil_c LIKE :search OR q.pil_d LIKE :search)";
    $qParams['search'] = "%$search_soal%";
}

$qWhereSQL = !empty($qWhere) ? "WHERE " . implode(" AND ", $qWhere) : "";

$stmtCountSoal = $pdo->prepare("
    SELECT COUNT(*) 
    FROM questions q
    LEFT JOIN bank_soal bs ON q.bank_soal_id = bs.id
    JOIN subjects s ON q.subject_id = s.id
    $qWhereSQL
");
$stmtCountSoal->execute($qParams);
$total_soal_filtered = (int)$stmtCountSoal->fetchColumn();
$total_pages_soal = ceil($total_soal_filtered / $limit_soal);

$stmtQuestions = $pdo->prepare("
    SELECT q.*, bs.nama_bank, bs.status_rilis, bs.kelas_rilis, s.nama_mapel, u.nama_lengkap as nama_guru
    FROM questions q
    LEFT JOIN bank_soal bs ON q.bank_soal_id = bs.id
    JOIN subjects s ON q.subject_id = s.id
    LEFT JOIN users u ON (bs.guru_id = u.id OR s.guru_id = u.id)
    $qWhereSQL
    ORDER BY " . ($filter_bank_id > 0 ? "(CASE WHEN q.urutan > 0 THEN q.urutan ELSE 9999999 END) ASC, q.id ASC" : "q.id DESC") . "
    LIMIT $limit_soal OFFSET $offset_soal
");
$stmtQuestions->execute($qParams);
$questions = $stmtQuestions->fetchAll(PDO::FETCH_ASSOC);

function buildUrlParams($overrides = []) {
    $params = array_merge($_GET, $overrides);
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Soal Pusat - Admin Panel - Espega Sinau</title>
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
            gap: 16px;
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

        /* Container Card */
        .card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
        }

        /* Tabs Navigation */
        .nav-tabs-bar {
            display: flex;
            gap: 8px;
            border-bottom: 2px solid var(--border-color);
            margin-bottom: 24px;
        }
        .tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 12px 22px;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }
        .tab-btn:hover {
            color: var(--primary);
        }
        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
        .tab-badge {
            background: var(--adm-slate-100);
            color: var(--adm-slate-700);
            font-size: 0.74rem;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }
        .tab-btn.active .tab-badge {
            background: var(--adm-blue-50);
            color: var(--primary);
            border: 1px solid var(--adm-blue-100);
        }

        /* Active Bank Banner (Tab 2) */
        .bank-banner {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--primary);
            border-radius: var(--radius-md);
            padding: 18px 22px;
            margin-bottom: 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            box-shadow: var(--shadow-sm);
        }

        /* Badges & Status */
        .badge-mapel {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 8px;
            border-radius: 4px;
            background: var(--adm-blue-50);
            color: var(--primary);
            border: 1px solid var(--adm-blue-100);
            font-weight: 700;
            font-size: 0.76rem;
        }
        .badge-kunci {
            background: var(--adm-blue-50);
            color: var(--primary);
            border: 1px solid var(--adm-blue-100);
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 800;
            display: inline-block;
        }
        .badge-rilis {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .badge-rilis.active-released {
            background: var(--adm-success-bg);
            color: #065f46;
            border: 1px solid var(--adm-success-border);
        }
        .badge-rilis.is-draft {
            background: var(--adm-slate-100);
            color: var(--adm-slate-600);
            border: 1px solid var(--border-color);
        }
        .pill-soal-count {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 6px;
            background: var(--adm-blue-50);
            color: var(--primary);
            border: 1px solid var(--adm-blue-100);
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 0.85rem;
        }

        /* Filter Section */
        .filter-section {
            background: var(--adm-slate-50);
            border-bottom: 1px solid var(--border-color);
            padding: 16px 20px;
        }
        .filter-grid-bank {
            display: grid;
            grid-template-columns: 2fr 1.3fr 1.3fr 1.1fr auto;
            gap: 12px;
            align-items: center;
        }
        .filter-grid-soal {
            display: grid;
            grid-template-columns: 2fr 1.4fr 1.2fr 1.2fr auto;
            gap: 12px;
            align-items: center;
        }
        .filter-input {
            width: 100%;
            height: 38px;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            outline: none;
            font-size: 0.88rem;
            background: #ffffff;
            color: var(--text-main);
            transition: border-color 0.15s, box-shadow 0.15s;
            box-sizing: border-box;
        }
        .filter-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
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
            padding: 13px 18px;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--adm-slate-600);
            font-weight: 700;
            white-space: nowrap;
        }
        td {
            padding: 14px 18px;
            border-bottom: 1px solid var(--adm-slate-100);
            vertical-align: middle;
            color: var(--text-main);
        }
        tbody tr:last-child td {
            border-bottom: none;
        }
        tbody tr:hover {
            background-color: #fbfcfe;
        }
        tr.row-selected {
            background-color: var(--adm-blue-50) !important;
        }
        .custom-checkbox {
            width: 17px;
            height: 17px;
            cursor: pointer;
            accent-color: var(--primary);
            vertical-align: middle;
        }

        /* Bulk Action Bar */
        .bulk-action-bar {
            display: none;
            background: #ffffff;
            border: 1px solid var(--adm-blue-100);
            border-left: 4px solid var(--primary);
            border-radius: var(--radius-md);
            padding: 12px 20px;
            margin-bottom: 20px;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            box-shadow: var(--shadow-sm);
            animation: slideDown 0.2s ease-out;
            flex-wrap: wrap;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .bulk-count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 26px;
            height: 26px;
            padding: 0 8px;
            border-radius: 20px;
            background: var(--adm-blue-50);
            color: var(--primary);
            font-weight: 800;
            font-size: 0.85rem;
            font-family: 'JetBrains Mono', monospace;
            border: 1px solid var(--adm-blue-100);
        }

        /* Pagination */
        .pagination-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-top: 1px solid var(--border-color);
            flex-wrap: wrap;
            gap: 12px;
            background: #ffffff;
        }
        .pagination-info {
            font-size: 0.84rem;
            color: var(--text-muted);
        }
        .pagination-nav {
            display: flex;
            align-items: center;
            gap: 5px;
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

        /* Class Selection Chips (Modal) */
        .chip-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(75px, 1fr));
            gap: 8px;
            margin-top: 10px;
            max-height: 220px;
            overflow-y: auto;
            padding: 4px;
        }
        .chip-checkbox-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px 10px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            background: #ffffff;
            cursor: pointer;
            user-select: none;
            transition: all 0.15s ease;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-muted);
            text-align: center;
        }
        .chip-checkbox-label:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--adm-blue-50);
        }
        .chip-checkbox-label input {
            display: none;
        }
        .chip-checkbox-label.checked {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 1px 3px rgba(37, 99, 235, 0.3);
        }

        /* Modals */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.65);
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
        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }
        .modal-card {
            background: #ffffff;
            border-radius: var(--radius-md);
            width: 100%;
            max-width: 620px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            transform: scale(0.96) translateY(-8px);
            transition: transform 0.2s ease;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }
        .modal-card.modal-lg {
            max-width: 720px;
        }
        .modal-overlay.active .modal-card {
            transform: scale(1) translateY(0);
        }
        .modal-header {
            padding: 18px 24px;
            background: var(--adm-slate-50);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-main);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .modal-close {
            background: transparent;
            border: none;
            font-size: 1.25rem;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 6px;
            line-height: 1;
            transition: color 0.15s;
        }
        .modal-close:hover {
            color: var(--adm-danger);
        }
        .modal-body {
            padding: 22px 24px;
            overflow-y: auto;
            flex: 1;
        }
        .modal-footer {
            padding: 14px 24px;
            background: var(--adm-slate-50);
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 700;
            margin-bottom: 6px;
            color: var(--adm-slate-700);
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            outline: none;
            font-size: 0.88rem;
            color: var(--text-main);
            box-sizing: border-box;
            background: #ffffff;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* CBT Preview */
        .cbt-preview-box {
            background: var(--adm-slate-50);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 20px;
            margin-bottom: 15px;
        }
        .cbt-option-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            background: #ffffff;
            margin-bottom: 8px;
            transition: 0.15s;
        }
        .cbt-option-row.active-key {
            border-color: var(--primary);
            background: var(--adm-blue-50);
        }
        .cbt-option-circle {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.82rem;
            background: var(--adm-slate-100);
            color: var(--adm-slate-700);
            flex-shrink: 0;
        }
        .cbt-option-row.active-key .cbt-option-circle {
            background: var(--primary);
            color: white;
        }

        /* Responsive Breakpoints */
        @media (max-width: 992px) {
            .filter-grid-bank, .filter-grid-soal {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 640px) {
            .filter-grid-bank, .filter-grid-soal {
                grid-template-columns: 1fr;
            }
            .page-header {
                flex-direction: column;
                align-items: stretch;
            }
            .nav-tabs-bar {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body class="admin-body">

<?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Notifikasi Alert -->
    <?php if (isset($_GET['status'])): ?>
        <div class="alert alert-success" id="alertBox">
            <i class="fa-solid fa-circle-check"></i>
            <div>
                <strong>Informasi</strong> &bull;
                <?php 
                    if ($_GET['status'] === 'bank_created') echo "Paket Bank Soal baru berhasil dibuat.";
                    elseif ($_GET['status'] === 'bank_updated') echo "Data Paket Bank Soal berhasil diperbarui.";
                    elseif ($_GET['status'] === 'bank_deleted') echo "Paket Bank Soal beserta butir soalnya berhasil dihapus.";
                    elseif ($_GET['status'] === 'success') echo "Butir soal berhasil ditambahkan ke Bank Soal.";
                    elseif ($_GET['status'] === 'updated') echo "Butir soal berhasil diperbarui.";
                    elseif ($_GET['status'] === 'deleted') echo "Butir soal berhasil dihapus.";
                    elseif ($_GET['status'] === 'custom') echo htmlspecialchars($_GET['msg'] ?? 'Operasi berhasil diproses.');
                    else echo "Operasi berhasil dijalankan.";
                ?>
            </div>
        </div>
        <script>
            setTimeout(() => {
                const el = document.getElementById('alertBox');
                if (el) { el.style.transition = '0.5s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 500); }
            }, 4000);
        </script>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error" id="errorBox">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div><strong>Gagal</strong> &bull; <?= htmlspecialchars($_GET['error']) ?></div>
        </div>
        <script>
            setTimeout(() => {
                const el = document.getElementById('errorBox');
                if (el) { el.style.transition = '0.5s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 500); }
            }, 5000);
        </script>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1>Bank Soal Pusat</h1>
            <p>Pusat kendali paket bank soal, distribusi kelas rilis, dan inventaris butir soal sekolah.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button type="button" class="btn btn-primary" onclick="openModalAddBank()">
                <i class="fa-solid fa-folder-plus"></i>
                <span>+ Buat Bank Soal</span>
            </button>
            <button type="button" class="btn btn-outline" onclick="openAddModalSoal()">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>+ Input Butir Soal</span>
            </button>
            <a href="kelola_ujian.php" class="btn btn-outline">
                <i class="fa-solid fa-file-signature"></i>
                <span>Kelola Ujian</span>
            </a>
        </div>
    </div>

    <!-- Ringkasan Statistik Global -->
    <div class="adm-stats-grid">
        <div class="adm-stat-card">
            <div class="adm-stat-icon"><i class="fa-solid fa-layer-group"></i></div>
            <div>
                <div class="adm-stat-num"><?= $total_bank_global ?></div>
                <div class="adm-stat-label">Total Paket Bank Soal</div>
            </div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-icon" style="color: var(--adm-success); background: #ecfdf5; border-color: #a7f3d0;">
                <i class="fa-solid fa-bullhorn"></i>
            </div>
            <div>
                <div class="adm-stat-num">
                    <?= $total_bank_rilis ?> 
                    <span style="font-size: 0.85rem; font-weight: 500; color: var(--text-muted);">Dirilis</span>
                </div>
                <div class="adm-stat-label"><?= $total_bank_draft ?> Paket masih Draft</div>
            </div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-icon"><i class="fa-solid fa-file-lines"></i></div>
            <div>
                <div class="adm-stat-num"><?= number_format($total_soal_global) ?></div>
                <div class="adm-stat-label">Total Butir Soal</div>
            </div>
        </div>
        <div class="adm-stat-card">
            <div class="adm-stat-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
            <div>
                <div class="adm-stat-num"><?= $total_guru_global ?></div>
                <div class="adm-stat-label">Guru Kontributor</div>
            </div>
        </div>
    </div>

    <!-- Tab Navigasi -->
    <div class="nav-tabs-bar">
        <a href="<?= buildUrlParams(['tab' => 'bank']) ?>" class="tab-btn <?= $active_tab === 'bank' ? 'active' : '' ?>">
            <i class="fa-solid fa-layer-group"></i>
            <span>Paket Bank Soal</span>
            <span class="tab-badge"><?= count($tab_bank_list) ?></span>
        </a>
        <a href="<?= buildUrlParams(['tab' => 'soal']) ?>" class="tab-btn <?= $active_tab === 'soal' ? 'active' : '' ?>">
            <i class="fa-solid fa-list-check"></i>
            <span>Daftar Butir Soal</span>
            <span class="tab-badge"><?= number_format($total_soal_filtered) ?></span>
        </a>
    </div>

    <!-- =========================================================================
         KONTEN TAB 1: PAKET BANK SOAL
         ========================================================================= -->
    <?php if ($active_tab === 'bank'): ?>
        
        <div class="card" style="overflow: hidden; margin-bottom: 24px;">
            <!-- Filter Bar Tab Bank -->
            <div class="filter-section">
                <form method="GET" action="manajemen_soal.php">
                    <input type="hidden" name="tab" value="bank">
                    <div class="filter-grid-bank">
                        <div>
                            <input type="text" name="q_bank" value="<?= htmlspecialchars($q_bank) ?>" placeholder="Cari nama bank soal / guru / mapel..." class="filter-input">
                        </div>
                        <div>
                            <select name="subject_bank" onchange="this.form.submit()" class="filter-input">
                                <option value="">Semua Mata Pelajaran</option>
                                <?php foreach($list_subjects as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= ($filter_subj_bank == $s['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['nama_mapel']) ?> (Kelas <?= htmlspecialchars($s['kelas']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <select name="guru_bank" onchange="this.form.submit()" class="filter-input">
                                <option value="">Semua Guru Pengampu</option>
                                <?php foreach($list_guru as $g): ?>
                                    <option value="<?= $g['id'] ?>" <?= ($filter_guru_bank == $g['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($g['nama_lengkap']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <select name="status_bank" onchange="this.form.submit()" class="filter-input">
                                <option value="all" <?= $filter_stat_bank === 'all' ? 'selected' : '' ?>>Semua Status Rilis</option>
                                <option value="rilis" <?= $filter_stat_bank === 'rilis' ? 'selected' : '' ?>>Sudah Dirilis</option>
                                <option value="draft" <?= $filter_stat_bank === 'draft' ? 'selected' : '' ?>>Draft (Belum Rilis)</option>
                            </select>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button type="submit" class="btn btn-primary" style="height: 38px; padding: 0 16px;">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </button>
                            <?php if (!empty($q_bank) || !empty($filter_subj_bank) || !empty($filter_guru_bank) || $filter_stat_bank !== 'all'): ?>
                                <a href="manajemen_soal.php?tab=bank" class="btn btn-outline" style="height: 38px; padding: 0 14px; color: var(--adm-danger);" title="Reset Filter">
                                    <i class="fa-solid fa-rotate-left"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tabel Paket Bank Soal -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">No</th>
                            <th>Paket Bank Soal</th>
                            <th style="width: 180px;">Mata Pelajaran</th>
                            <th style="width: 170px;">Guru Pengampu</th>
                            <th style="width: 110px; text-align: center;">Jumlah Soal</th>
                            <th style="width: 180px;">Status & Sasaran</th>
                            <th style="width: 190px; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tab_bank_list)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 50px 20px;">
                                    <div style="font-size: 2.8rem; color: var(--adm-slate-400); margin-bottom: 12px;">
                                        <i class="fa-solid fa-folder-open"></i>
                                    </div>
                                    <h4 style="font-weight: 700; color: var(--text-main); margin-bottom: 6px;">Tidak Ada Paket Bank Soal</h4>
                                    <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 16px;">Belum ada paket bank soal yang sesuai kriteria pencarian.</p>
                                    <button type="button" class="btn btn-primary" onclick="openModalAddBank()">
                                        <i class="fa-solid fa-folder-plus"></i> + Buat Bank Soal Sekarang
                                    </button>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $no_b = 1;
                            foreach ($tab_bank_list as $bank): 
                                $isReleased = ($bank['status_rilis'] === 'rilis' && !empty($bank['kelas_rilis']));
                            ?>
                            <tr>
                                <td style="text-align: center; font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: var(--text-muted);">
                                    <?= $no_b++ ?>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-main); font-size: 0.95rem; margin-bottom: 3px;">
                                        <?= htmlspecialchars($bank['nama_bank']) ?>
                                    </div>
                                    <div style="font-size: 0.78rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                                        <i class="fa-regular fa-calendar"></i> Dibuat: <?= date('d M Y', strtotime($bank['created_at'] ?? 'now')) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-mapel"><?= htmlspecialchars($bank['nama_mapel']) ?></span>
                                    <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                                        Basis: Kelas <?= htmlspecialchars($bank['kelas_mapel'] ?: '-') ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; font-size: 0.88rem; color: var(--text-main);">
                                        <?= htmlspecialchars($bank['nama_guru'] ?: 'Admin/Umum') ?>
                                    </div>
                                    <div style="font-size: 0.76rem; color: var(--text-muted);">
                                        Pengampu
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="pill-soal-count">
                                        <i class="fa-solid fa-list-check" style="font-size: 0.75rem;"></i> <?= (int)$bank['total_soal'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($isReleased): ?>
                                        <div style="margin-bottom: 4px;">
                                            <span class="badge-rilis active-released">
                                                <i class="fa-solid fa-circle-check"></i> Dirilis ke Kelas
                                            </span>
                                        </div>
                                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--adm-slate-700);">
                                            <?= htmlspecialchars($bank['kelas_rilis']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge-rilis is-draft">
                                            <i class="fa-solid fa-clock"></i> Draft (Belum Rilis)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                        <a href="manajemen_soal.php?tab=soal&bank_id=<?= $bank['id'] ?>" class="btn btn-sm btn-primary" title="Kelola butir soal di paket ini">
                                            <i class="fa-solid fa-list-check"></i>
                                            <span>Kelola</span>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline" onclick="openModalRilisBank(<?= htmlspecialchars(json_encode($bank)) ?>)" title="Rilis bank soal ke kelas sasaran">
                                            <i class="fa-solid fa-bullhorn"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline" onclick="confirmAcakBankAdmin(<?= $bank['id'] ?>, '<?= htmlspecialchars(addslashes($bank['nama_bank'])) ?>', <?= (int)$bank['total_soal'] ?>, 'bank')" title="Acak urutan butir soal dalam paket ini">
                                            <i class="fa-solid fa-shuffle"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline" onclick="openModalDownloadAdmin(<?= $bank['id'] ?>, '<?= htmlspecialchars(addslashes($bank['nama_bank'])) ?>', <?= (int)$bank['total_soal'] ?>)" title="Download Bank Soal (PDF / Excel)">
                                            <i class="fa-solid fa-cloud-arrow-down"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline" onclick="openModalEditBank(<?= htmlspecialchars(json_encode($bank)) ?>)" title="Edit info paket bank soal">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline" style="color: var(--adm-danger);" onclick="confirmDeleteBank(<?= $bank['id'] ?>, '<?= htmlspecialchars(addslashes($bank['nama_bank'])) ?>')" title="Hapus paket bank soal">
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

    <!-- =========================================================================
         KONTEN TAB 2: DAFTAR BUTIR SOAL
         ========================================================================= -->
    <?php else: ?>

        <!-- Banner jika sedang memfilter Bank Soal Tertentu -->
        <?php if ($current_bank): ?>
            <div class="bank-banner">
                <div>
                    <div style="font-size: 1.25rem; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-layer-group" style="color: var(--primary);"></i>
                        <span><?= htmlspecialchars($current_bank['nama_bank']) ?></span>
                    </div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-top: 6px;">
                        <span><i class="fa-solid fa-book"></i> Mapel: <strong><?= htmlspecialchars($current_bank['nama_mapel']) ?></strong></span>
                        <span>&bull;</span>
                        <span><i class="fa-solid fa-user-tie"></i> Pengampu: <strong><?= htmlspecialchars($current_bank['nama_guru'] ?: 'Admin') ?></strong></span>
                        <span>&bull;</span>
                        <span>
                            <?php if ($current_bank['status_rilis'] === 'rilis' && !empty($current_bank['kelas_rilis'])): ?>
                                <span class="badge-rilis active-released"><i class="fa-solid fa-circle-check"></i> Dirilis: <?= htmlspecialchars($current_bank['kelas_rilis']) ?></span>
                            <?php else: ?>
                                <span class="badge-rilis is-draft"><i class="fa-solid fa-clock"></i> Draft (Belum Dirilis)</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <button type="button" class="btn btn-sm btn-outline" onclick="confirmAcakBankAdmin(<?= $current_bank['id'] ?>, '<?= htmlspecialchars(addslashes($current_bank['nama_bank'])) ?>', <?= (int)$total_soal_filtered ?>, 'soal')" title="Acak urutan butir soal dalam paket ini">
                        <i class="fa-solid fa-shuffle" style="color: var(--primary);"></i>
                        <span>Acak Urutan Soal</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="confirmResetUrutanAdmin(<?= $current_bank['id'] ?>, '<?= htmlspecialchars(addslashes($current_bank['nama_bank'])) ?>', 'soal')" title="Kembalikan urutan default (berdasarkan nomor ID)">
                        <i class="fa-solid fa-arrow-rotate-left"></i>
                        <span>Reset Urutan</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="openModalDownloadAdmin(<?= $current_bank['id'] ?>, '<?= htmlspecialchars(addslashes($current_bank['nama_bank'])) ?>', <?= (int)$total_soal_filtered ?>)">
                        <i class="fa-solid fa-cloud-arrow-down"></i>
                        <span>Download Soal</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="openModalRilisBank(<?= htmlspecialchars(json_encode($current_bank)) ?>)">
                        <i class="fa-solid fa-bullhorn"></i>
                        <span>Ubah Kelas Rilis</span>
                    </button>
                    <a href="manajemen_soal.php?tab=soal" class="btn btn-sm btn-outline" style="color: var(--adm-slate-600);">
                        <i class="fa-solid fa-xmark"></i>
                        <span>Semua Bank Soal</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Hidden Form untuk Bulk Delete Soal -->
        <form id="formBulkDelete" action="../../controllers/AdminController.php?action=bulk_delete_soal" method="POST" style="display: none;">
            <input type="hidden" name="bank_id" value="<?= $filter_bank_id ?>">
            <div id="bulkDeleteInputs"></div>
        </form>
        <form id="formBulkAcak" action="../../controllers/AdminController.php?action=bulk_acak_soal" method="POST" style="display: none;">
            <input type="hidden" name="bank_id" value="<?= $filter_bank_id ?>">
            <div id="bulkAcakInputs"></div>
        </form>

        <!-- Bulk Action Toolbar (Muncul saat ada butir soal dicentang) -->
        <div id="bulkActionBar" class="bulk-action-bar">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <span class="bulk-count-badge" id="selectedCountBadge">0</span>
                <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-main);">
                    <span id="selectedCountText">0</span> Butir Soal Dipilih
                </span>
                <span style="color: var(--border-color); margin: 0 4px;">|</span>
                <button type="button" class="btn btn-sm btn-outline" onclick="selectAllVisible(true)" style="padding: 4px 10px; font-size: 0.78rem;">
                    <i class="fa-solid fa-check-double"></i> Pilih Semua Halaman Ini (<?= count($questions) ?>)
                </button>
                <button type="button" class="btn btn-sm btn-outline" onclick="selectAllVisible(false)" style="padding: 4px 10px; font-size: 0.78rem; color: var(--adm-slate-600);">
                    <i class="fa-solid fa-xmark"></i> Batalkan Pilihan
                </button>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="button" class="btn btn-sm btn-outline" onclick="confirmAcakSelectedAdmin()" style="border-color: var(--primary); color: var(--primary); font-weight: 700;">
                    <i class="fa-solid fa-shuffle"></i>
                    <span>Acak Urutan Terpilih</span>
                </button>
                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDeleteSelected()" style="box-shadow: 0 2px 4px rgba(239, 68, 68, 0.25);">
                    <i class="fa-solid fa-trash-can"></i>
                    <span>Hapus Soal Terpilih</span>
                </button>
            </div>
        </div>

        <div class="card" style="overflow: hidden; margin-bottom: 24px;">
            <!-- Filter Bar Tab Butir Soal -->
            <div class="filter-section">
                <form method="GET" action="manajemen_soal.php">
                    <input type="hidden" name="tab" value="soal">
                    <div class="filter-grid-soal">
                        <div>
                            <input type="text" name="q" value="<?= htmlspecialchars($search_soal) ?>" placeholder="Cari teks pertanyaan / opsi..." class="filter-input">
                        </div>
                        <div>
                            <select name="bank_id" onchange="this.form.submit()" class="filter-input">
                                <option value="">Semua Paket Bank Soal</option>
                                <?php foreach($all_banks_unfiltered as $b): ?>
                                    <option value="<?= $b['id'] ?>" <?= ($filter_bank_id == $b['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($b['nama_bank']) ?> (<?= (int)$b['total_soal'] ?> Soal)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <select name="subject_id" onchange="this.form.submit()" class="filter-input">
                                <option value="">Semua Mata Pelajaran</option>
                                <?php foreach($list_subjects as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= ($filter_subj_q == $s['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['nama_mapel']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <select name="guru_id" onchange="this.form.submit()" class="filter-input">
                                <option value="">Semua Guru</option>
                                <?php foreach($list_guru as $g): ?>
                                    <option value="<?= $g['id'] ?>" <?= ($filter_guru_q == $g['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($g['nama_lengkap']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button type="submit" class="btn btn-primary" style="height: 38px; padding: 0 16px;">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </button>
                            <?php if (!empty($search_soal) || !empty($filter_bank_id) || !empty($filter_subj_q) || !empty($filter_guru_q)): ?>
                                <a href="manajemen_soal.php?tab=soal" class="btn btn-outline" style="height: 38px; padding: 0 14px; color: var(--adm-danger);" title="Reset Filter">
                                    <i class="fa-solid fa-rotate-left"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tabel Data Soal -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 44px; text-align: center;">
                                <input type="checkbox" id="selectAllCheckbox" class="custom-checkbox" onchange="toggleSelectAll(this.checked)" title="Pilih Semua di Halaman Ini">
                            </th>
                            <th style="width: 50px; text-align: center;">No</th>
                            <th style="width: 210px;">Bank Soal & Pengampu</th>
                            <th>Pertanyaan & Pilihan Jawaban</th>
                            <th style="width: 95px; text-align: center;">Media</th>
                            <th style="width: 70px; text-align: center;">Kunci</th>
                            <th style="width: 140px; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($questions)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 50px 20px;">
                                    <div style="font-size: 2.8rem; color: var(--adm-slate-400); margin-bottom: 12px;">
                                        <i class="fa-solid fa-file-circle-question"></i>
                                    </div>
                                    <h4 style="font-weight: 700; color: var(--text-main); margin-bottom: 6px;">Belum Ada Butir Soal</h4>
                                    <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 16px;">Tidak ada butir soal yang sesuai filter pencarian saat ini.</p>
                                    <button type="button" class="btn btn-primary" onclick="openAddModalSoal()">
                                        <i class="fa-solid fa-pen-to-square"></i> + Input Butir Soal Baru
                                    </button>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $no_q = $offset_soal + 1;
                            foreach ($questions as $q): 
                                $hasImg = !empty($q['img_pertanyaan']) || !empty($q['img_a']) || !empty($q['img_b']) || !empty($q['img_c']) || !empty($q['img_d']) || !empty($q['img_e']);
                                $kunci = strtoupper(trim($q['kunci_jawaban'] ?? 'A'));
                            ?>
                            <tr id="row-soal-<?= $q['id'] ?>">
                                <td style="text-align: center;">
                                    <input type="checkbox" class="chk-soal custom-checkbox" value="<?= $q['id'] ?>" onchange="onItemCheckChange()" data-id="<?= $q['id'] ?>" title="Pilih butir soal ini">
                                </td>
                                <td style="text-align: center; font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: var(--text-muted);">
                                    <?= $no_q++ ?>
                                    <?php if (!empty($q['urutan'])): ?>
                                        <div style="font-size: 0.7rem; font-weight: 700; color: var(--primary); background: var(--adm-blue-50); border: 1px solid var(--adm-blue-100); border-radius: 4px; padding: 1px 4px; margin-top: 3px;" title="Urutan Acak: Nomor <?= $q['urutan'] ?>">#<?= $q['urutan'] ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-main); font-size: 0.88rem; margin-bottom: 4px;">
                                        <?= htmlspecialchars($q['nama_bank'] ?: 'Tanpa Paket') ?>
                                    </div>
                                    <div style="margin-bottom: 4px;">
                                        <span class="badge-mapel"><?= htmlspecialchars($q['nama_mapel']) ?></span>
                                    </div>
                                    <div style="font-size: 0.78rem; color: var(--text-muted);">
                                        <i class="fa-solid fa-chalkboard-user"></i> <?= htmlspecialchars($q['nama_guru'] ?: 'Admin') ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 0.92rem; font-weight: 600; color: var(--adm-slate-900); line-height: 1.5; margin-bottom: 8px; max-width: 520px; word-break: break-word;">
                                        <?= nl2br(htmlspecialchars(mb_strimwidth($q['pertanyaan'], 0, 160, '...'))) ?>
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px; font-size: 0.78rem; color: var(--adm-slate-700); max-width: 520px;">
                                        <div style="padding: 3px 6px; border-radius: 4px; <?= ($kunci === 'A') ? 'font-weight:700; color:#065f46; background:#ecfdf5; border:1px solid #a7f3d0;' : 'background:#f8fafc; border:1px solid #e2e8f0;' ?>">
                                            <strong>A.</strong> <?= htmlspecialchars(mb_strimwidth($q['pil_a'], 0, 42, '...')) ?>
                                        </div>
                                        <div style="padding: 3px 6px; border-radius: 4px; <?= ($kunci === 'B') ? 'font-weight:700; color:#065f46; background:#ecfdf5; border:1px solid #a7f3d0;' : 'background:#f8fafc; border:1px solid #e2e8f0;' ?>">
                                            <strong>B.</strong> <?= htmlspecialchars(mb_strimwidth($q['pil_b'], 0, 42, '...')) ?>
                                        </div>
                                        <div style="padding: 3px 6px; border-radius: 4px; <?= ($kunci === 'C') ? 'font-weight:700; color:#065f46; background:#ecfdf5; border:1px solid #a7f3d0;' : 'background:#f8fafc; border:1px solid #e2e8f0;' ?>">
                                            <strong>C.</strong> <?= htmlspecialchars(mb_strimwidth($q['pil_c'], 0, 42, '...')) ?>
                                        </div>
                                        <div style="padding: 3px 6px; border-radius: 4px; <?= ($kunci === 'D') ? 'font-weight:700; color:#065f46; background:#ecfdf5; border:1px solid #a7f3d0;' : 'background:#f8fafc; border:1px solid #e2e8f0;' ?>">
                                            <strong>D.</strong> <?= htmlspecialchars(mb_strimwidth($q['pil_d'], 0, 42, '...')) ?>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($hasImg): ?>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 4px; background: var(--adm-blue-50); color: var(--primary); font-size: 0.75rem; font-weight: 700; border: 1px solid var(--adm-blue-100);">
                                            <i class="fa-solid fa-image"></i> Gambar
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 0.8rem;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-kunci"><?= $kunci ?></span>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                        <button type="button" class="btn btn-sm btn-outline" onclick="previewCBTSoal(<?= htmlspecialchars(json_encode($q)) ?>)" title="Pratinjau Simulasi CBT">
                                            <i class="fa-solid fa-desktop"></i>
                                            <span>Lihat</span>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline" onclick="openEditModalSoal(<?= htmlspecialchars(json_encode($q)) ?>)" title="Edit Butir Soal">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline" style="color: var(--adm-danger);" onclick="confirmDeleteSoalSingle(<?= $q['id'] ?>)" title="Hapus Soal">
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

            <!-- Pagination Butir Soal -->
            <?php if ($total_pages_soal > 1): ?>
                <div class="pagination-wrap">
                    <div class="pagination-info">
                        Menampilkan halaman <strong><?= $page_soal ?></strong> dari <strong><?= $total_pages_soal ?></strong> (Total <strong><?= number_format($total_soal_filtered) ?></strong> butir soal)
                    </div>
                    <div class="pagination-nav">
                        <a href="<?= buildUrlParams(['page' => 1]) ?>" class="page-btn <?= ($page_soal <= 1) ? 'disabled' : '' ?>" title="Halaman Pertama">
                            <i class="fa-solid fa-angles-left"></i>
                        </a>
                        <a href="<?= buildUrlParams(['page' => max(1, $page_soal - 1)]) ?>" class="page-btn <?= ($page_soal <= 1) ? 'disabled' : '' ?>" title="Halaman Sebelumnya">
                            <i class="fa-solid fa-chevron-left"></i>
                        </a>

                        <?php 
                        $startPage = max(1, $page_soal - 2);
                        $endPage = min($total_pages_soal, $page_soal + 2);
                        for ($i = $startPage; $i <= $endPage; $i++): 
                        ?>
                            <a href="<?= buildUrlParams(['page' => $i]) ?>" class="page-btn <?= ($i == $page_soal) ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <a href="<?= buildUrlParams(['page' => min($total_pages_soal, $page_soal + 1)]) ?>" class="page-btn <?= ($page_soal >= $total_pages_soal) ? 'disabled' : '' ?>" title="Halaman Selanjutnya">
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                        <a href="<?= buildUrlParams(['page' => $total_pages_soal]) ?>" class="page-btn <?= ($page_soal >= $total_pages_soal) ? 'disabled' : '' ?>" title="Halaman Terakhir">
                            <i class="fa-solid fa-angles-right"></i>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>

    <!-- =========================================================================
         MODAL 1: TAMBAH BANK SOAL BARU (ADMIN)
         ========================================================================= -->
    <div id="modalAddBank" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3><i class="fa-solid fa-folder-plus" style="color: var(--primary);"></i> <span>Buat Paket Bank Soal Baru</span></h3>
                <button type="button" class="modal-close" onclick="closeModal('modalAddBank')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="../../controllers/AdminController.php?action=add_bank_soal" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Paket Bank Soal <span style="color:red;">*</span></label>
                        <input type="text" name="nama_bank" placeholder="Contoh: Bank Soal IPA Kelas 7 - Penilaian Tengah Semester" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="form-group">
                            <label>Mata Pelajaran <span style="color:red;">*</span></label>
                            <select name="subject_id" id="addBankSubjectSelect" required onchange="syncGuruFromSubject(this)">
                                <option value="">-- Pilih Mata Pelajaran --</option>
                                <?php foreach($list_subjects as $s): ?>
                                    <option value="<?= $s['id'] ?>" data-guru="<?= $s['guru_id'] ?>">
                                        <?= htmlspecialchars($s['nama_mapel']) ?> (Kelas <?= htmlspecialchars($s['kelas']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Guru Pengampu <span style="color:red;">*</span></label>
                            <select name="guru_id" id="addBankGuruSelect" required>
                                <option value="">-- Pilih Guru --</option>
                                <?php foreach($list_guru as $g): ?>
                                    <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nama_lengkap']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <label style="margin: 0;">Kelas Sasaran Rilis Awal (Opsional)</label>
                            <div style="display: flex; gap: 6px;">
                                <button type="button" class="btn btn-sm btn-outline" onclick="toggleChipsAddBank(true)" style="padding: 2px 8px; font-size: 0.72rem;">Pilih Semua</button>
                                <button type="button" class="btn btn-sm btn-outline" onclick="toggleChipsAddBank(false)" style="padding: 2px 8px; font-size: 0.72rem;">Kosongkan</button>
                            </div>
                        </div>
                        <p style="font-size: 0.78rem; color: var(--text-muted); margin: 0 0 8px 0;">Bisa ditentukan sekarang atau dibiarkan kosong sebagai status <strong>Draft</strong>.</p>
                        <div class="chip-grid">
                            <?php foreach ($classesList as $cls): ?>
                                <label class="chip-checkbox-label" id="chip-label-add-<?= $cls ?>">
                                    <input type="checkbox" name="target_kelas[]" value="<?= $cls ?>" class="chk-chip-add" onchange="handleChipAdd(this)">
                                    <?= htmlspecialchars($cls) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('modalAddBank')">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Simpan Bank Soal</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- =========================================================================
         MODAL 2: EDIT BANK SOAL (ADMIN)
         ========================================================================= -->
    <div id="modalEditBank" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3><i class="fa-solid fa-pen-to-square" style="color: var(--primary);"></i> <span>Edit Paket Bank Soal</span></h3>
                <button type="button" class="modal-close" onclick="closeModal('modalEditBank')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="../../controllers/AdminController.php?action=edit_bank_soal" method="POST">
                <input type="hidden" name="bank_id" id="edit_bank_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Paket Bank Soal <span style="color:red;">*</span></label>
                        <input type="text" name="nama_bank" id="edit_bank_nama" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="form-group">
                            <label>Mata Pelajaran <span style="color:red;">*</span></label>
                            <select name="subject_id" id="edit_bank_subject" required>
                                <?php foreach($list_subjects as $s): ?>
                                    <option value="<?= $s['id'] ?>">
                                        <?= htmlspecialchars($s['nama_mapel']) ?> (Kelas <?= htmlspecialchars($s['kelas']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Guru Pengampu <span style="color:red;">*</span></label>
                            <select name="guru_id" id="edit_bank_guru" required>
                                <?php foreach($list_guru as $g): ?>
                                    <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nama_lengkap']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('modalEditBank')">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- =========================================================================
         MODAL 3: RILIS BANK SOAL KE KELAS (ADMIN)
         ========================================================================= -->
    <div id="modalRilisBank" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <h3 id="modalRilisTitle"><i class="fa-solid fa-bullhorn" style="color: var(--primary);"></i> <span>Rilis Bank Soal ke Kelas</span></h3>
                    <p id="modalRilisSubtitle" style="font-size: 0.8rem; color: var(--text-muted); margin: 3px 0 0 0;"></p>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('modalRilisBank')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="../../controllers/AdminController.php?action=rilis_bank_soal" method="POST">
                <input type="hidden" name="bank_id" id="rilis_bank_id">
                <input type="hidden" name="redirect_tab" value="<?= $active_tab ?>">
                <div class="modal-body">
                    <div style="background: var(--adm-blue-50); border: 1px solid var(--adm-blue-100); border-radius: var(--radius-sm); padding: 12px 14px; margin-bottom: 16px; font-size: 0.82rem; color: var(--adm-slate-700); line-height: 1.5;">
                        <i class="fa-solid fa-circle-info" style="color: var(--primary); margin-right: 4px;"></i>
                        Centang kelas yang berhak mengakses butir soal ujian ini. Jika semua centang dikosongkan, status bank soal otomatis kembali menjadi <strong>Draft</strong>.
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: var(--text-main); margin: 0;">Pilih Kelas Sasaran:</label>
                        <div style="display: flex; gap: 6px;">
                            <button type="button" class="btn btn-sm btn-outline" onclick="toggleChipsRilis(true)" style="padding: 2px 8px; font-size: 0.72rem;">Pilih Semua</button>
                            <button type="button" class="btn btn-sm btn-outline" onclick="toggleChipsRilis(false)" style="padding: 2px 8px; font-size: 0.72rem;">Kosongkan</button>
                        </div>
                    </div>

                    <div class="chip-grid">
                        <?php foreach ($classesList as $cls): ?>
                            <label class="chip-checkbox-label" id="chip-label-rilis-<?= $cls ?>">
                                <input type="checkbox" name="target_kelas[]" value="<?= $cls ?>" class="chk-chip-rilis" onchange="handleChipRilis(this)">
                                <?= htmlspecialchars($cls) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('modalRilisBank')">Batal</button>
                    <button type="submit" class="btn btn-primary" style="padding: 9px 20px;">
                        <i class="fa-solid fa-bullhorn"></i>
                        <span>Simpan Status Rilis</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- =========================================================================
         MODAL 4: INPUT & EDIT BUTIR SOAL
         ========================================================================= -->
    <div id="soalModal" class="modal-overlay">
        <div class="modal-card modal-lg">
            <div class="modal-header">
                <h3 id="modalSoalTitle">
                    <i class="fa-solid fa-pen-to-square" style="color: var(--primary);"></i>
                    <span>Input Butir Soal</span>
                </h3>
                <button type="button" class="modal-close" onclick="closeModal('soalModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="soalForm" method="POST" action="../../controllers/AdminController.php?action=add_soal">
                <input type="hidden" name="id" id="formSoalId">
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>Paket Bank Soal Sasaran <span style="color:red;">*</span></label>
                            <select name="bank_soal_id" id="formSoalBank" required onchange="onBankSelectInSoalModal(this)">
                                <option value="">-- Pilih Paket Bank Soal --</option>
                                <?php foreach($all_banks_unfiltered as $b): ?>
                                    <option value="<?= $b['id'] ?>" data-subject="<?= $b['subject_id'] ?>" data-kelas="<?= htmlspecialchars($b['kelas_rilis'] ?? '') ?>" <?= ($filter_bank_id == $b['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($b['nama_bank']) ?> (<?= htmlspecialchars($b['nama_mapel']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Target Kelas</label>
                            <input type="text" name="kelas" id="formSoalKelas" placeholder="Otomatis dari bank soal">
                        </div>
                    </div>
                    <input type="hidden" name="subject_id" id="formSoalSubjectId" value="">

                    <div class="form-group">
                        <label>Teks Pertanyaan Soal <span style="color:red;">*</span></label>
                        <textarea name="pertanyaan" id="formSoalPertanyaan" rows="3" placeholder="Tuliskan butir soal di sini..." required></textarea>
                    </div>

                    <div style="background: var(--adm-slate-50); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 14px;">
                        <span style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--adm-slate-800); margin-bottom: 10px;">
                            Pilihan Jawaban (A, B, C, D)
                        </span>

                        <div class="form-group">
                            <label>Pilihan A</label>
                            <input type="text" name="pil_a" id="formSoalA" placeholder="Teks opsi A..." required>
                        </div>
                        <div class="form-group">
                            <label>Pilihan B</label>
                            <input type="text" name="pil_b" id="formSoalB" placeholder="Teks opsi B..." required>
                        </div>
                        <div class="form-group">
                            <label>Pilihan C</label>
                            <input type="text" name="pil_c" id="formSoalC" placeholder="Teks opsi C..." required>
                        </div>
                        <div class="form-group">
                            <label>Pilihan D</label>
                            <input type="text" name="pil_d" id="formSoalD" placeholder="Teks opsi D..." required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Kunci Jawaban Benar <span style="color:red;">*</span></label>
                        <select name="kunci_jawaban" id="formSoalKunci" required style="font-weight: 700;">
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('soalModal')">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitSoal">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Simpan Soal</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- =========================================================================
         MODAL 5: PRATINJAU CBT (SIMULATOR SOAL)
         ========================================================================= -->
    <div id="previewModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <h3 id="previewTitle"><i class="fa-solid fa-desktop" style="color: var(--primary);"></i> <span>Pratinjau Soal (Simulasi CBT)</span></h3>
                    <p id="previewSubtitle" style="font-size: 0.8rem; color: var(--text-muted); margin: 3px 0 0 0;"></p>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('previewModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <div class="cbt-preview-box">
                    <div id="previewImgPertanyaanWrap" style="display: none; margin-bottom: 14px; text-align: center;">
                        <img id="previewImgPertanyaan" src="" alt="Gambar Soal" style="max-width: 100%; max-height: 240px; border-radius: 6px; border: 1px solid var(--border-color);">
                    </div>
                    <div id="previewPertanyaan" style="font-size: 1.05rem; font-weight: 600; line-height: 1.6; color: var(--text-main); margin-bottom: 18px;"></div>
                    
                    <div id="previewOptions"></div>
                </div>
                <div style="background: var(--adm-blue-50); border: 1px solid var(--adm-blue-100); border-radius: var(--radius-sm); padding: 12px 16px; font-size: 0.88rem; color: var(--primary); display: flex; align-items: center; justify-content: space-between;">
                    <span>Kunci Jawaban Resmi:</span>
                    <strong id="previewKunciText" style="font-size: 1.1rem; font-family: 'JetBrains Mono', monospace;">-</strong>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="closeModal('previewModal')">Tutup Pratinjau</button>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         MODAL 6: DOWNLOAD BANK SOAL (ADMIN)
         ========================================================================= -->
    <div id="modalDownloadAdmin" class="modal-overlay">
        <div class="modal-card" style="max-width: 580px;">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: var(--adm-blue-50); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">
                        <i class="fa-solid fa-cloud-arrow-down"></i>
                    </div>
                    <div>
                        <h3 id="adminDownloadTitle" style="margin: 0; font-size: 1.15rem;">Download Bank Soal</h3>
                        <p id="adminDownloadSubtitle" style="font-size: 0.8rem; color: var(--text-muted); margin: 2px 0 0 0;"></p>
                    </div>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('modalDownloadAdmin')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="modal-body" style="padding: 22px 24px;">
                <!-- Option 1: PDF Naskah Ujian 2 Kolom -->
                <div style="border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 18px; margin-bottom: 16px; background: #fafbfc;">
                    <div style="display: flex; gap: 14px; align-items: flex-start;">
                        <div style="width: 42px; height: 42px; border-radius: 8px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
                            <i class="fa-solid fa-file-pdf"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <h4 style="font-size: 0.98rem; font-weight: 700; color: var(--text-main); margin: 0;">Naskah Ujian Cetak (PDF)</h4>
                                <span style="font-size: 0.72rem; font-weight: 700; background: #fee2e2; color: #dc2626; padding: 2px 8px; border-radius: 12px;">Format Cetak Resmi</span>
                            </div>
                            <p style="font-size: 0.82rem; color: var(--text-muted); margin: 6px 0 12px; line-height: 1.45;">
                                Naskah soal pilihan ganda <strong>2 kolom (kiri & kanan)</strong> standar ujian dengan <strong>Kop SMP Negeri 3 Ngawen</strong> serta lembar kunci jawaban terpisah di halaman akhir.
                            </p>
                            <div style="margin-bottom: 14px; background: #ffffff; border: 1px solid var(--border-color); border-radius: 6px; padding: 8px 12px;">
                                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.82rem; font-weight: 600; color: var(--adm-slate-700); cursor: pointer; margin: 0;">
                                    <input type="checkbox" id="pdf_admin_include_kunci" checked style="accent-color: var(--primary); width: 16px; height: 16px; cursor: pointer;">
                                    <span>Sertakan Lembar Kunci Jawaban di Halaman Terpisah</span>
                                </label>
                            </div>
                            <button type="button" class="btn btn-outline" onclick="triggerAdminDownloadPDF()" style="width: 100%; justify-content: center; border-color: #dc2626; color: #dc2626;">
                                <i class="fa-solid fa-file-arrow-down"></i>
                                <span>Download Format PDF (2 Kolom)</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Option 2: Excel Template Import -->
                <div style="border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 18px; background: #fafbfc;">
                    <div style="display: flex; gap: 14px; align-items: flex-start;">
                        <div style="width: 42px; height: 42px; border-radius: 8px; background: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
                            <i class="fa-solid fa-file-excel"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <h4 style="font-size: 0.98rem; font-weight: 700; color: var(--text-main); margin: 0;">Spreadsheet Excel (.xlsx)</h4>
                                <span style="font-size: 0.72rem; font-weight: 700; background: #d1fae5; color: #059669; padding: 2px 8px; border-radius: 12px;">Template CBT</span>
                            </div>
                            <p style="font-size: 0.82rem; color: var(--text-muted); margin: 6px 0 14px; line-height: 1.45;">
                                Format kolom template CBT: <em>Pertanyaan, Pilihan A, B, C, D, E, dan Kunci Jawaban</em>. Sesuai untuk arsip bank soal dan editing Excel.
                            </p>
                            <button type="button" class="btn btn-outline" onclick="triggerAdminDownloadExcel()" style="width: 100%; justify-content: center; border-color: #059669; color: #059669;">
                                <i class="fa-solid fa-file-arrow-down"></i>
                                <span>Download Format Excel (.xlsx)</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalDownloadAdmin')">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        let adminDownloadBankId = 0;
        function openModalDownloadAdmin(id, nama, jmlSoal) {
            adminDownloadBankId = id;
            document.getElementById('adminDownloadTitle').innerText = 'Download: ' + nama;
            document.getElementById('adminDownloadSubtitle').innerText = (jmlSoal || 0) + ' Butir Soal Terdaftar';
            openModal('modalDownloadAdmin');
        }
        function triggerAdminDownloadPDF() {
            if (!adminDownloadBankId) return;
            const withKunci = document.getElementById('pdf_admin_include_kunci').checked ? 1 : 0;
            window.location.href = `../../controllers/AdminController.php?action=download_bank_soal&format=pdf&bank_id=${adminDownloadBankId}&with_kunci=${withKunci}`;
        }
        function triggerAdminDownloadExcel() {
            if (!adminDownloadBankId) return;
            window.location.href = `../../controllers/AdminController.php?action=download_bank_soal&format=excel&bank_id=${adminDownloadBankId}`;
        }

        // Modal Utilities
        function openModal(id) {
            const m = document.getElementById(id);
            if (m) m.classList.add('active');
        }
        function closeModal(id) {
            const m = document.getElementById(id);
            if (m) m.classList.remove('active');
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

        // ----------------------------------------------------
        // LOGIKA MODAL BANK SOAL
        // ----------------------------------------------------
        function openModalAddBank() {
            openModal('modalAddBank');
        }

        function syncGuruFromSubject(sel) {
            const opt = sel.options[sel.selectedIndex];
            const guruId = opt.getAttribute('data-guru');
            if (guruId) {
                const gSel = document.getElementById('addBankGuruSelect');
                if (gSel) gSel.value = guruId;
            }
        }

        function toggleChipsAddBank(status) {
            document.querySelectorAll('.chk-chip-add').forEach(cb => {
                cb.checked = status;
                const label = document.getElementById('chip-label-add-' + cb.value);
                if (label) {
                    if (status) label.classList.add('checked');
                    else label.classList.remove('checked');
                }
            });
        }

        function handleChipAdd(cb) {
            const label = document.getElementById('chip-label-add-' + cb.value);
            if (label) {
                if (cb.checked) label.classList.add('checked');
                else label.classList.remove('checked');
            }
        }

        function openModalEditBank(data) {
            document.getElementById('edit_bank_id').value = data.id;
            document.getElementById('edit_bank_nama').value = data.nama_bank;
            document.getElementById('edit_bank_subject').value = data.subject_id;
            document.getElementById('edit_bank_guru').value = data.guru_id;
            openModal('modalEditBank');
        }

        function confirmDeleteBank(id, name) {
            Swal.fire({
                title: 'Hapus Paket Bank Soal?',
                text: `Paket '${name}' beserta SELURUH butir soal di dalamnya akan dihapus permanen!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus Paket Bank',
                cancelButtonText: 'Batal'
            }).then((res) => {
                if (res.isConfirmed) {
                    window.location.href = `../../controllers/AdminController.php?action=delete_bank_soal&id=${id}`;
                }
            });
        }

        // Modal Rilis Bank
        function openModalRilisBank(data) {
            document.getElementById('rilis_bank_id').value = data.id;
            document.getElementById('modalRilisTitle').innerHTML = '<i class="fa-solid fa-bullhorn" style="color: var(--primary);"></i> <span>Rilis: ' + escapeHtml(data.nama_bank) + '</span>';
            document.getElementById('modalRilisSubtitle').textContent = "Mapel: " + (data.nama_mapel || '-') + " • Pengampu: " + (data.nama_guru || 'Admin');

            const activeList = (data.kelas_rilis || '').split(',').map(s => s.trim());
            document.querySelectorAll('.chk-chip-rilis').forEach(cb => {
                const isChecked = activeList.includes(cb.value);
                cb.checked = isChecked;
                const label = document.getElementById('chip-label-rilis-' + cb.value);
                if (label) {
                    if (isChecked) label.classList.add('checked');
                    else label.classList.remove('checked');
                }
            });

            openModal('modalRilisBank');
        }

        function toggleChipsRilis(status) {
            document.querySelectorAll('.chk-chip-rilis').forEach(cb => {
                cb.checked = status;
                const label = document.getElementById('chip-label-rilis-' + cb.value);
                if (label) {
                    if (status) label.classList.add('checked');
                    else label.classList.remove('checked');
                }
            });
        }

        function handleChipRilis(cb) {
            const label = document.getElementById('chip-label-rilis-' + cb.value);
            if (label) {
                if (cb.checked) label.classList.add('checked');
                else label.classList.remove('checked');
            }
        }

        // ----------------------------------------------------
        // LOGIKA BUTIR SOAL & CBT PREVIEW
        // ----------------------------------------------------
        function onBankSelectInSoalModal(sel) {
            const opt = sel.options[sel.selectedIndex];
            if (opt) {
                const subId = opt.getAttribute('data-subject');
                const kelas = opt.getAttribute('data-kelas');
                if (subId) document.getElementById('formSoalSubjectId').value = subId;
                if (kelas) document.getElementById('formSoalKelas').value = kelas;
            }
        }

        function openAddModalSoal() {
            document.getElementById('modalSoalTitle').innerHTML = '<i class="fa-solid fa-plus" style="color: var(--primary);"></i> <span>Input Butir Soal Baru</span>';
            document.getElementById('soalForm').action = '../../controllers/AdminController.php?action=add_soal';
            document.getElementById('formSoalId').value = '';
            document.getElementById('formSoalPertanyaan').value = '';
            document.getElementById('formSoalA').value = '';
            document.getElementById('formSoalB').value = '';
            document.getElementById('formSoalC').value = '';
            document.getElementById('formSoalD').value = '';
            document.getElementById('formSoalKunci').value = 'A';

            const bankSel = document.getElementById('formSoalBank');
            if (bankSel && bankSel.value) onBankSelectInSoalModal(bankSel);

            openModal('soalModal');
        }

        function openEditModalSoal(data) {
            document.getElementById('modalSoalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square" style="color: var(--primary);"></i> <span>Edit Butir Soal</span>';
            document.getElementById('soalForm').action = '../../controllers/AdminController.php?action=edit_soal';
            document.getElementById('formSoalId').value = data.id || '';
            document.getElementById('formSoalBank').value = data.bank_soal_id || '';
            document.getElementById('formSoalSubjectId').value = data.subject_id || '';
            document.getElementById('formSoalKelas').value = data.kelas || '';
            document.getElementById('formSoalPertanyaan').value = data.pertanyaan || '';
            document.getElementById('formSoalA').value = data.pil_a || '';
            document.getElementById('formSoalB').value = data.pil_b || '';
            document.getElementById('formSoalC').value = data.pil_c || '';
            document.getElementById('formSoalD').value = data.pil_d || '';
            document.getElementById('formSoalKunci').value = (data.kunci_jawaban || 'A').toUpperCase();

            openModal('soalModal');
        }

        function confirmDeleteSoalSingle(id) {
            Swal.fire({
                title: 'Hapus Butir Soal Ini?',
                text: 'Butir soal beserta lampiran gambarnya akan dihapus permanen dari bank soal.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus Soal',
                cancelButtonText: 'Batal'
            }).then((res) => {
                if (res.isConfirmed) {
                    window.location.href = `../../controllers/AdminController.php?action=delete_soal&id=${id}&bank_id=<?= $filter_bank_id ?>`;
                }
            });
        }

        function previewCBTSoal(data) {
            document.getElementById('previewTitle').innerHTML = '<i class="fa-solid fa-desktop" style="color: var(--primary);"></i> <span>' + escapeHtml(data.nama_mapel || 'Mata Pelajaran') + ' • ' + escapeHtml(data.nama_bank || 'Bank Soal') + '</span>';
            document.getElementById('previewSubtitle').textContent = "Pengampu: " + (data.nama_guru || 'Admin') + " • Target Kelas: " + (data.kelas || '-');
            document.getElementById('previewPertanyaan').textContent = data.pertanyaan || '';

            const imgWrap = document.getElementById('previewImgPertanyaanWrap');
            const imgEl = document.getElementById('previewImgPertanyaan');
            if (data.img_pertanyaan && data.img_pertanyaan.trim() !== '') {
                imgEl.src = '../../assets/img/soal/' + data.img_pertanyaan;
                imgWrap.style.display = 'block';
            } else {
                imgWrap.style.display = 'none';
                imgEl.src = '';
            }

            const kunci = (data.kunci_jawaban || 'A').toUpperCase().trim();
            document.getElementById('previewKunciText').textContent = "Pilihan " + kunci;

            const options = [
                { key: 'A', text: data.pil_a, img: data.img_a },
                { key: 'B', text: data.pil_b, img: data.img_b },
                { key: 'C', text: data.pil_c, img: data.img_c },
                { key: 'D', text: data.pil_d, img: data.img_d }
            ];

            let html = '';
            options.forEach(opt => {
                const isCorrect = (opt.key === kunci);
                html += `
                    <div class="cbt-option-row ${isCorrect ? 'active-key' : ''}">
                        <div class="cbt-option-circle">${opt.key}</div>
                        <div style="flex: 1; font-size: 0.92rem; color: #1e293b;">
                            ${escapeHtml(opt.text || '-')}
                            ${opt.img ? `<div style="margin-top:6px;"><img src="../../assets/img/soal/${opt.img}" style="max-height:100px; border-radius:4px; border:1px solid #e2e8f0;"></div>` : ''}
                        </div>
                        ${isCorrect ? '<span style="font-size: 0.78rem; font-weight: 700; color: #065f46; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 2px 8px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;"><i class="fa-solid fa-check"></i> Kunci Benar</span>' : ''}
                    </div>
                `;
            });

            document.getElementById('previewOptions').innerHTML = html;
            openModal('previewModal');
        }

        // ----------------------------------------------------
        // FITUR SELECT & SELECT ALL & BULK DELETE SOAL
        // ----------------------------------------------------
        function updateSelectionState() {
            const allCheckboxes = document.querySelectorAll('.chk-soal');
            const checkedBoxes = document.querySelectorAll('.chk-soal:checked');
            const totalCount = allCheckboxes.length;
            const selectedCount = checkedBoxes.length;

            const toolbar = document.getElementById('bulkActionBar');
            const countBadge = document.getElementById('selectedCountBadge');
            const countText = document.getElementById('selectedCountText');
            const selectAllCb = document.getElementById('selectAllCheckbox');

            if (countBadge) countBadge.textContent = selectedCount;
            if (countText) countText.textContent = selectedCount;

            allCheckboxes.forEach(cb => {
                const row = document.getElementById('row-soal-' + cb.value);
                if (row) {
                    if (cb.checked) row.classList.add('row-selected');
                    else row.classList.remove('row-selected');
                }
            });

            if (toolbar) toolbar.style.display = (selectedCount > 0) ? 'flex' : 'none';

            if (selectAllCb) {
                if (selectedCount === 0) {
                    selectAllCb.checked = false;
                    selectAllCb.indeterminate = false;
                } else if (selectedCount === totalCount) {
                    selectAllCb.checked = true;
                    selectAllCb.indeterminate = false;
                } else {
                    selectAllCb.checked = false;
                    selectAllCb.indeterminate = true;
                }
            }
        }

        function toggleSelectAll(status) {
            document.querySelectorAll('.chk-soal').forEach(cb => { cb.checked = status; });
            updateSelectionState();
        }

        function selectAllVisible(status) {
            const selectAllCb = document.getElementById('selectAllCheckbox');
            if (selectAllCb) selectAllCb.checked = status;
            toggleSelectAll(status);
        }

        function onItemCheckChange() {
            updateSelectionState();
        }

        function confirmDeleteSelected() {
            const checkedBoxes = document.querySelectorAll('.chk-soal:checked');
            const count = checkedBoxes.length;

            if (count === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'Pilih Soal Terlebih Dahulu',
                    text: 'Silakan centang butir soal yang ingin dihapus.'
                });
                return;
            }

            Swal.fire({
                title: `Hapus ${count} Butir Soal Terpilih?`,
                text: `Sebanyak ${count} butir soal yang dicentang beserta gambar lampirannya akan dihapus permanen dari bank soal pusat.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: `Ya, Hapus ${count} Soal`,
                cancelButtonText: 'Batal'
            }).then((res) => {
                if (res.isConfirmed) {
                    const container = document.getElementById('bulkDeleteInputs');
                    container.innerHTML = '';
                    checkedBoxes.forEach(cb => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'ids[]';
                        input.value = cb.value;
                        container.appendChild(input);
                    });
                    document.getElementById('formBulkDelete').submit();
                }
            });
        }

        // ----------------------------------------------------
        // FITUR ACAK URUTAN SOAL ADMIN
        // ----------------------------------------------------
        function confirmAcakBankAdmin(bankId, namaBank, totalSoal, redirectTab = 'soal') {
            if (totalSoal <= 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'Bank Soal Kosong',
                    text: 'Tidak ada butir soal di dalam paket bank soal ini untuk diacak.'
                });
                return;
            }

            Swal.fire({
                title: 'Acak Urutan Soal?',
                html: `Urutan seluruh <b>${totalSoal} butir soal</b> pada paket <b>"${escapeHtml(namaBank)}"</b> akan diacak secara otomatis.<br><br><small style="color: #64748b;">Urutan acak ini akan langsung diterapkan saat siswa mengerjakan ujian CBT dan saat didownload/dicetak.</small>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fa-solid fa-shuffle"></i> Ya, Acak Sekarang!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `../../controllers/AdminController.php?action=acak_urutan_soal&bank_id=${bankId}&redirect_tab=${redirectTab}`;
                }
            });
        }

        function confirmResetUrutanAdmin(bankId, namaBank, redirectTab = 'soal') {
            Swal.fire({
                title: 'Reset Urutan Soal?',
                html: `Kembalikan urutan butir soal pada paket <b>"${escapeHtml(namaBank)}"</b> ke susunan default awal (berdasarkan nomor ID pendaftaran)?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0ea5e9',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fa-solid fa-arrow-rotate-left"></i> Ya, Reset Urutan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `../../controllers/AdminController.php?action=reset_urutan_soal&bank_id=${bankId}&redirect_tab=${redirectTab}`;
                }
            });
        }

        function confirmAcakSelectedAdmin() {
            const checkedBoxes = document.querySelectorAll('.chk-soal:checked');
            const count = checkedBoxes.length;

            if (count < 2) {
                Swal.fire({
                    icon: 'info',
                    title: 'Pilih Minimal 2 Soal',
                    text: 'Centang minimal 2 butir soal yang ingin diacak urutannya.'
                });
                return;
            }

            Swal.fire({
                title: `Acak ${count} Soal Terpilih?`,
                html: `Urutan dari <b>${count} butir soal</b> yang dicentang akan diacak posisinya secara acak.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fa-solid fa-shuffle"></i> Ya, Acak Terpilih',
                cancelButtonText: 'Batal'
            }).then((res) => {
                if (res.isConfirmed) {
                    const container = document.getElementById('bulkAcakInputs');
                    container.innerHTML = '';
                    checkedBoxes.forEach(cb => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'ids[]';
                        input.value = cb.value;
                        container.appendChild(input);
                    });
                    document.getElementById('formBulkAcak').submit();
                }
            });
        }

        // Klik baris untuk kemudahan centang tanpa mengganggu tombol aksi
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('tbody tr[id^="row-soal-"]').forEach(row => {
                row.addEventListener('click', function(e) {
                    if (['INPUT', 'BUTTON', 'A', 'I', 'SELECT', 'TEXTAREA'].includes(e.target.tagName)) return;
                    if (e.target.closest('button') || e.target.closest('a') || e.target.closest('input')) return;
                    const cb = row.querySelector('.chk-soal');
                    if (cb) {
                        cb.checked = !cb.checked;
                        updateSelectionState();
                    }
                });
            });
        });

        function escapeHtml(text) {
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    </script>

<?php include __DIR__ . '/footer.php'; ?>