<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/database.php';
require_once '../../config/security.php'; 
protect_page(); 

$active_nav = 'bank_soal';
$page_title = 'Daftar Bank Soal';

$guru_id = $_SESSION['user_id'] ?? 0;
$nama_guru = $_SESSION['nama'] ?? 'Guru'; 

// 1. Ambil Semua Mapel milik guru
$stmt_mapel = $pdo->prepare("SELECT * FROM subjects WHERE guru_id = ? ORDER BY nama_mapel ASC");
$stmt_mapel->execute([$guru_id]);
$daftar_mapel = $stmt_mapel->fetchAll(PDO::FETCH_ASSOC);

// Mapel filter
$filter_mapel = isset($_GET['filter_mapel']) ? (int)$_GET['filter_mapel'] : 0;
$filter_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : 'all';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// 2. Query Daftar Bank Soal
$sql = "
    SELECT bs.*, s.nama_mapel, s.kelas as kelas_mapel,
           (SELECT COUNT(*) FROM questions WHERE bank_soal_id = bs.id) as jml_soal
    FROM bank_soal bs
    JOIN subjects s ON bs.subject_id = s.id
    WHERE bs.guru_id = :guru_id
";
$params = [':guru_id' => $guru_id];

if ($filter_mapel > 0) {
    $sql .= " AND bs.subject_id = :filter_mapel";
    $params[':filter_mapel'] = $filter_mapel;
}

if ($filter_status === 'rilis') {
    $sql .= " AND bs.status_rilis = 'rilis'";
} elseif ($filter_status === 'draft') {
    $sql .= " AND bs.status_rilis = 'draft'";
}

if (!empty($search_query)) {
    $sql .= " AND bs.nama_bank LIKE :q";
    $params[':q'] = "%$search_query%";
}

$sql .= " ORDER BY bs.id DESC";

$stmt_banks = $pdo->prepare($sql);
$stmt_banks->execute($params);
$daftar_bank = $stmt_banks->fetchAll(PDO::FETCH_ASSOC);

// 3. Statistik Global Guru
$stmt_total_bank = $pdo->prepare("SELECT COUNT(*) FROM bank_soal WHERE guru_id = ?");
$stmt_total_bank->execute([$guru_id]);
$stat_total_bank = (int)$stmt_total_bank->fetchColumn();

$stmt_rilis = $pdo->prepare("SELECT COUNT(*) FROM bank_soal WHERE guru_id = ? AND status_rilis = 'rilis'");
$stmt_rilis->execute([$guru_id]);
$stat_rilis_bank = (int)$stmt_rilis->fetchColumn();

$stat_draft_bank = $stat_total_bank - $stat_rilis_bank;

$stmt_total_soal = $pdo->prepare("
    SELECT COUNT(q.id) 
    FROM questions q 
    JOIN subjects s ON q.subject_id = s.id 
    WHERE s.guru_id = ?
");
$stmt_total_soal->execute([$guru_id]);
$stat_total_soal = (int)$stmt_total_soal->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Soal Guru - Espega Sinau</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="guru_theme.css">
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

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: var(--shadow-sm);
        }
        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: var(--radius-sm);
            background: var(--adm-blue-50);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        .stat-num {
            font-size: 1.55rem;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1.1;
        }
        .stat-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 3px;
            font-weight: 600;
        }

        .filter-bar {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 16px 20px;
            margin-bottom: 22px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }
        .filter-left {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            flex: 1;
        }

        .bank-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 18px;
        }
        .bank-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 22px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
            position: relative;
        }
        .bank-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--adm-blue-100);
        }
        .bank-title {
            font-size: 1.12rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 8px;
            line-height: 1.4;
        }
        .bank-mapel-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 10px;
            background: var(--adm-slate-100);
            color: var(--adm-slate-700);
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            margin-bottom: 14px;
        }
        .bank-stats-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 14px;
            background: var(--adm-slate-50);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
            margin-bottom: 16px;
        }
        .bank-soal-count {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .bank-card-actions {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            padding-top: 14px;
            border-top: 1px solid var(--border-color);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(3px);
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background-color: #ffffff;
            margin: auto;
            padding: 26px 28px;
            border-radius: var(--radius-md);
            width: 92%;
            max-width: 540px;
            box-shadow: var(--shadow-lg);
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }
        .close-btn {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 1.25rem;
            cursor: pointer;
            color: var(--text-muted);
            transition: color 0.15s;
        }
        .close-btn:hover {
            color: var(--adm-danger);
        }
    </style>
</head>
<body class="admin-body">

<?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Notifikasi Alert -->
    <?php if (isset($_GET['status'])): ?>
        <div class="alert <?= $_GET['status'] === 'success' ? 'alert-success' : 'alert-error' ?>" id="alertBox">
            <i class="fa-solid <?= $_GET['status'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
            <div>
                <strong>Informasi</strong> &bull; <?= htmlspecialchars($_GET['msg'] ?? 'Operasi berhasil diproses.') ?>
            </div>
        </div>
        <script>
            setTimeout(() => { 
                const box = document.getElementById('alertBox');
                if(box) {
                    box.style.transition = '0.5s';
                    box.style.opacity = '0';
                    setTimeout(() => box.remove(), 500);
                }
            }, 4000);
        </script>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1>Kelola Bank Soal</h1>
            <p>Buat paket bank soal, isi butir soal, dan rilis ke kelas-kelas yang diampu tanpa perlu mengulang input soal bergambar.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button type="button" class="btn btn-primary" onclick="openModalTambahBank()">
                <i class="fa-solid fa-plus"></i>
                <span>Buat Bank Soal Baru</span>
            </button>
        </div>
    </div>

    <!-- Stat Highlights -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
                <div class="stat-num"><?= $stat_total_bank ?></div>
                <div class="stat-label">Total Paket Bank Soal</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--adm-success-bg); color: var(--adm-success);">
                <i class="fa-solid fa-bullhorn"></i>
            </div>
            <div>
                <div class="stat-num" style="color: #065f46;"><?= $stat_rilis_bank ?></div>
                <div class="stat-label">Bank Soal Dirilis</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--adm-warning-bg); color: #d97706;">
                <i class="fa-solid fa-file-pen"></i>
            </div>
            <div>
                <div class="stat-num" style="color: #92400e;"><?= $stat_draft_bank ?></div>
                <div class="stat-label">Bank Soal Draft</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fa-solid fa-list-check"></i>
            </div>
            <div>
                <div class="stat-num"><?= $stat_total_soal ?></div>
                <div class="stat-label">Total Butir Soal Terinput</div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <form method="GET" action="bank_soal.php" class="filter-bar">
        <div class="filter-left">
            <div>
                <select name="filter_mapel" onchange="this.form.submit()" style="padding: 7px 12px; font-size: 0.85rem;">
                    <option value="0">Semua Mata Pelajaran</option>
                    <?php foreach ($daftar_mapel as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= ($filter_mapel == $m['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['nama_mapel']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <select name="filter_status" onchange="this.form.submit()" style="padding: 7px 12px; font-size: 0.85rem;">
                    <option value="all" <?= ($filter_status === 'all') ? 'selected' : '' ?>>Semua Status Rilis</option>
                    <option value="rilis" <?= ($filter_status === 'rilis') ? 'selected' : '' ?>>Hanya Dirilis (Aktif)</option>
                    <option value="draft" <?= ($filter_status === 'draft') ? 'selected' : '' ?>>Hanya Draft</option>
                </select>
            </div>
            <div style="display: flex; gap: 6px;">
                <input type="text" name="q" value="<?= htmlspecialchars($search_query) ?>" placeholder="Cari nama bank soal..." style="padding: 7px 12px; font-size: 0.85rem; width: 220px;">
                <button type="submit" class="btn btn-sm btn-outline">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </div>
        </div>
        <div>
            <?php if ($filter_mapel > 0 || $filter_status !== 'all' || !empty($search_query)): ?>
                <a href="bank_soal.php" class="btn btn-sm btn-outline" style="color: var(--adm-danger);">
                    <i class="fa-solid fa-rotate-left"></i> Reset Filter
                </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Bank Soal Card Grid -->
    <?php if (empty($daftar_bank)): ?>
        <div class="card" style="text-align: center; padding: 48px 24px;">
            <div style="font-size: 3rem; color: var(--adm-slate-400); margin-bottom: 12px;">
                <i class="fa-solid fa-box-open"></i>
            </div>
            <h3 style="font-weight: 700; color: var(--text-main); margin-bottom: 6px;">Belum Ada Bank Soal</h3>
            <p style="color: var(--text-muted); font-size: 0.88rem; max-width: 480px; margin: 0 auto 20px;">
                Anda belum memiliki paket bank soal. Klik tombol di bawah untuk membuat Bank Soal pertama Anda (misal: "Bank Soal IPA Kelas 7").
            </p>
            <button type="button" class="btn btn-primary" onclick="openModalTambahBank()">
                <i class="fa-solid fa-plus"></i>
                <span>Buat Bank Soal Baru</span>
            </button>
        </div>
    <?php else: ?>
        <div class="bank-card-grid">
            <?php foreach ($daftar_bank as $b): 
                $isRilis = ($b['status_rilis'] === 'rilis' && !empty($b['kelas_rilis']));
                $kelasRilis = $b['kelas_rilis'] ?? '';
            ?>
            <div class="bank-card">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                        <span class="bank-mapel-badge">
                            <i class="fa-solid fa-book"></i>
                            <?= htmlspecialchars($b['nama_mapel']) ?>
                        </span>
                        <?php if ($isRilis): ?>
                            <span class="badge-rilis active-released" title="Soal aktif dan dapat dikerjakan siswa di kelas ini">
                                <i class="fa-solid fa-circle-check"></i> Dirilis ke: <?= htmlspecialchars($kelasRilis) ?>
                            </span>
                        <?php else: ?>
                            <span class="badge-rilis is-draft" title="Soal belum dirilis ke kelas manapun">
                                <i class="fa-solid fa-clock"></i> Draft (Belum Dirilis)
                            </span>
                        <?php endif; ?>
                    </div>

                    <h3 class="bank-title"><?= htmlspecialchars($b['nama_bank']) ?></h3>

                    <div class="bank-stats-row">
                        <div class="bank-soal-count">
                            <i class="fa-solid fa-list-check" style="color: var(--primary);"></i>
                            <span><?= (int)$b['jml_soal'] ?> Butir Soal</span>
                        </div>
                        <span style="font-size: 0.78rem; color: var(--text-muted);">
                            ID #<?= $b['id'] ?>
                        </span>
                    </div>
                </div>

                <div class="bank-card-actions">
                    <a href="kelola_soal.php?bank_id=<?= $b['id'] ?>" class="btn btn-sm btn-primary" style="flex: 1; justify-content: center;" title="Buka dan kelola butir soal di dalam bank soal ini">
                        <i class="fa-solid fa-folder-open"></i>
                        <span>Kelola Soal</span>
                    </a>
                    
                    <button type="button" class="btn btn-sm btn-outline" onclick="openModalRilis(<?= htmlspecialchars(json_encode($b)) ?>)" title="Rilis bank soal ini ke kelas yang diampu">
                        <i class="fa-solid fa-bullhorn" style="color: var(--primary);"></i>
                        <span>Rilis</span>
                    </button>

                    <button type="button" class="btn btn-sm btn-outline" onclick="confirmAcakBankGuru(<?= $b['id'] ?>, '<?= htmlspecialchars(addslashes($b['nama_bank'])) ?>', <?= (int)$b['jml_soal'] ?>)" title="Acak urutan butir soal dalam bank soal ini">
                        <i class="fa-solid fa-shuffle"></i>
                    </button>

                    <button type="button" class="btn btn-sm btn-outline" onclick="openModalDownloadBank(<?= $b['id'] ?>, '<?= htmlspecialchars(addslashes($b['nama_bank'])) ?>', <?= (int)$b['jml_soal'] ?>)" title="Download Bank Soal (PDF / Excel)">
                        <i class="fa-solid fa-cloud-arrow-down"></i>
                    </button>

                    <button type="button" class="btn btn-sm btn-outline" onclick="openModalEdit(<?= htmlspecialchars(json_encode($b)) ?>)" title="Edit nama bank soal">
                        <i class="fa-solid fa-pen"></i>
                    </button>

                    <button type="button" class="btn btn-sm btn-outline" style="color: var(--adm-danger);" onclick="confirmDeleteBank(<?= $b['id'] ?>, '<?= htmlspecialchars(addslashes($b['nama_bank'])) ?>', <?= (int)$b['jml_soal'] ?>)" title="Hapus bank soal">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- MODAL 1: BUAT BANK SOAL BARU -->
    <div id="modalTambahBank" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModalTambahBank()"><i class="fa-solid fa-xmark"></i></span>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 18px;">
                <div style="width: 38px; height: 38px; border-radius: 8px; background: var(--adm-blue-50); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">
                    <i class="fa-solid fa-folder-plus"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main); margin: 0;">Buat Bank Soal Baru</h3>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Tentukan nama paket bank soal dan mata pelajaran pengampu.</p>
                </div>
            </div>

            <form action="../../controllers/GuruController.php?action=tambah_bank_soal" method="POST">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label style="font-weight: 600; font-size: 0.85rem; color: var(--adm-slate-700); display: block; margin-bottom: 6px;">
                        Nama Bank Soal <span style="color: red;">*</span>
                    </label>
                    <input type="text" name="nama_bank" required placeholder="Misal: Bank Soal IPA Kelas 7" autofocus>
                    <small style="color: var(--text-muted); font-size: 0.78rem; display: block; margin-top: 4px;">
                        Gunakan nama yang jelas seperti: <em>Bank Soal IPA Kelas 7</em>, <em>ASTS Matematika 8</em>, dll.
                    </small>
                </div>

                <div class="form-group" style="margin-bottom: 22px;">
                    <label style="font-weight: 600; font-size: 0.85rem; color: var(--adm-slate-700); display: block; margin-bottom: 6px;">
                        Mata Pelajaran <span style="color: red;">*</span>
                    </label>
                    <select name="subject_id" required>
                        <?php foreach ($daftar_mapel as $m): ?>
                            <option value="<?= $m['id'] ?>">
                                <?= htmlspecialchars($m['nama_mapel']) ?> (Kelas Diampu: <?= htmlspecialchars($m['kelas']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 16px; border-top: 1px solid var(--border-color);">
                    <button type="button" class="btn btn-outline" onclick="closeModalTambahBank()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="padding: 10px 22px;">
                        <i class="fa-solid fa-check"></i>
                        <span>Buat Bank Soal</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: EDIT BANK SOAL -->
    <div id="modalEditBank" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModalEditBank()"><i class="fa-solid fa-xmark"></i></span>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 18px;">
                <div style="width: 38px; height: 38px; border-radius: 8px; background: var(--adm-blue-50); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main); margin: 0;">Edit Paket Bank Soal</h3>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Ubah nama bank soal atau mata pelajaran terkait.</p>
                </div>
            </div>

            <form action="../../controllers/GuruController.php?action=edit_bank_soal" method="POST">
                <input type="hidden" name="bank_id" id="edit_bank_id">

                <div class="form-group" style="margin-bottom: 16px;">
                    <label style="font-weight: 600; font-size: 0.85rem; color: var(--adm-slate-700); display: block; margin-bottom: 6px;">
                        Nama Bank Soal <span style="color: red;">*</span>
                    </label>
                    <input type="text" name="nama_bank" id="edit_nama_bank" required>
                </div>

                <div class="form-group" style="margin-bottom: 22px;">
                    <label style="font-weight: 600; font-size: 0.85rem; color: var(--adm-slate-700); display: block; margin-bottom: 6px;">
                        Mata Pelajaran <span style="color: red;">*</span>
                    </label>
                    <select name="subject_id" id="edit_subject_id" required>
                        <?php foreach ($daftar_mapel as $m): ?>
                            <option value="<?= $m['id'] ?>">
                                <?= htmlspecialchars($m['nama_mapel']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 16px; border-top: 1px solid var(--border-color);">
                    <button type="button" class="btn btn-outline" onclick="closeModalEditBank()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="padding: 10px 22px;">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: RILIS BANK SOAL KE KELAS -->
    <div id="modalRilisBank" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModalRilis()"><i class="fa-solid fa-xmark"></i></span>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 18px;">
                <div style="width: 38px; height: 38px; border-radius: 8px; background: var(--adm-blue-50); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main); margin: 0;" id="rilisModalTitle">Rilis Bank Soal ke Kelas</h3>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;" id="rilisModalSubtitle">Tentukan kelas mana saja yang dapat mengakses soal ini.</p>
                </div>
            </div>

            <form action="../../controllers/GuruController.php?action=rilis_bank_soal" method="POST">
                <input type="hidden" name="bank_id" id="rilis_bank_id">
                <input type="hidden" name="redirect_to" value="bank_soal">

                <div style="background: var(--adm-blue-50); border: 1px solid var(--adm-blue-100); border-radius: var(--radius-sm); padding: 12px 16px; margin-bottom: 18px; font-size: 0.82rem; color: var(--adm-slate-700); line-height: 1.5;">
                    <i class="fa-solid fa-circle-info" style="color: var(--primary); margin-right: 4px;"></i>
                    Ketika Bank Soal dirilis ke kelas terpilih, seluruh butir soal (dan gambar) di dalamnya otomatis aktif untuk siswa kelas tersebut saat ujian CBT berlangsung.
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label style="font-weight: 700; font-size: 0.85rem; color: var(--adm-slate-800); margin: 0;">Pilih Kelas Sasaran:</label>
                        <div style="display: flex; gap: 6px;">
                            <button type="button" class="btn btn-sm btn-outline" onclick="toggleSelectAllRilis(true)" style="padding: 2px 8px; font-size: 0.72rem;">
                                Pilih Semua
                            </button>
                            <button type="button" class="btn btn-sm btn-outline" onclick="toggleSelectAllRilis(false)" style="padding: 2px 8px; font-size: 0.72rem;">
                                Kosongkan (Draft)
                            </button>
                        </div>
                    </div>

                    <div class="chip-checkbox-group" id="rilisClassContainer">
                        <!-- Dimuat dinamis via JS sesuai kelas diampu pada mapel terkait -->
                    </div>

                    <small style="color: var(--text-muted); font-size: 0.78rem; display: block; margin-top: 8px;">
                        Tip: Jika tidak ada kelas yang dicentang, Bank Soal akan disimpan sebagai <strong>Draft</strong>.
                    </small>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 16px; border-top: 1px solid var(--border-color);">
                    <button type="button" class="btn btn-outline" onclick="closeModalRilis()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="padding: 10px 22px;">
                        <i class="fa-solid fa-check"></i>
                        <span>Simpan Status Rilis</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: DOWNLOAD BANK SOAL -->
    <div id="modalDownloadBank" class="modal">
        <div class="modal-content" style="max-width: 580px;">
            <span class="close-btn" onclick="closeModalDownloadBank()"><i class="fa-solid fa-xmark"></i></span>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                <div style="width: 44px; height: 44px; border-radius: 10px; background: var(--adm-blue-50); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                    <i class="fa-solid fa-cloud-arrow-down"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--text-main); margin: 0;" id="downloadModalTitle">Download Bank Soal</h3>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin: 2px 0 0 0;" id="downloadModalSubtitle">Pilih format dokumen untuk mengunduh butir soal.</p>
                </div>
            </div>

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
                            Naskah soal pilihan ganda <strong>2 kolom (kiri & kanan)</strong> standar naskah ujian dengan <strong>Kop SMP Negeri 3 Ngawen</strong> serta lembar kunci jawaban terpisah di halaman akhir.
                        </p>
                        <div style="margin-bottom: 14px; background: #ffffff; border: 1px solid var(--border-color); border-radius: 6px; padding: 8px 12px;">
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 0.82rem; font-weight: 600; color: var(--adm-slate-700); cursor: pointer; margin: 0;">
                                <input type="checkbox" id="pdf_bank_include_kunci" checked style="accent-color: var(--primary); width: 16px; height: 16px; cursor: pointer;">
                                <span>Sertakan Lembar Kunci Jawaban di Halaman Terpisah</span>
                            </label>
                        </div>
                        <button type="button" class="btn btn-outline" onclick="triggerDownloadPDF()" style="width: 100%; justify-content: center; border-color: #dc2626; color: #dc2626;">
                            <i class="fa-solid fa-file-arrow-down"></i>
                            <span>Download Format PDF (2 Kolom)</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Option 2: Excel Template Import -->
            <div style="border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 18px; margin-bottom: 20px; background: #fafbfc;">
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
                            Format kolom template CBT: <em>Pertanyaan, Pilihan A, B, C, D, E, dan Kunci Jawaban</em>. Sangat sesuai untuk backup atau diedit kembali.
                        </p>
                        <button type="button" class="btn btn-outline" onclick="triggerDownloadExcel()" style="width: 100%; justify-content: center; border-color: #059669; color: #059669;">
                            <i class="fa-solid fa-file-arrow-down"></i>
                            <span>Download Format Excel (.xlsx)</span>
                        </button>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeModalDownloadBank()">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        // --- Modal Download Bank ---
        let currentDownloadBankId = 0;
        function openModalDownloadBank(id, nama, jmlSoal) {
            currentDownloadBankId = id;
            document.getElementById('downloadModalTitle').innerText = 'Download: ' + nama;
            document.getElementById('downloadModalSubtitle').innerText = (jmlSoal || 0) + ' Butir Soal Terdaftar';
            document.getElementById('modalDownloadBank').style.display = 'flex';
        }
        function closeModalDownloadBank() {
            document.getElementById('modalDownloadBank').style.display = 'none';
        }
        function triggerDownloadPDF() {
            if (!currentDownloadBankId) return;
            const withKunci = document.getElementById('pdf_bank_include_kunci').checked ? 1 : 0;
            window.location.href = `../../controllers/GuruController.php?action=download_bank_soal&format=pdf&bank_id=${currentDownloadBankId}&with_kunci=${withKunci}`;
        }
        function triggerDownloadExcel() {
            if (!currentDownloadBankId) return;
            window.location.href = `../../controllers/GuruController.php?action=download_bank_soal&format=excel&bank_id=${currentDownloadBankId}`;
        }

        // --- Modal Tambah Bank ---
        function openModalTambahBank() {
            document.getElementById('modalTambahBank').style.display = 'flex';
        }
        function closeModalTambahBank() {
            document.getElementById('modalTambahBank').style.display = 'none';
        }

        // --- Modal Edit Bank ---
        function openModalEdit(data) {
            document.getElementById('edit_bank_id').value = data.id;
            document.getElementById('edit_nama_bank').value = data.nama_bank;
            document.getElementById('edit_subject_id').value = data.subject_id;
            document.getElementById('modalEditBank').style.display = 'flex';
        }
        function closeModalEditBank() {
            document.getElementById('modalEditBank').style.display = 'none';
        }

        // --- Modal Rilis Bank ---
        function openModalRilis(data) {
            document.getElementById('rilis_bank_id').value = data.id;
            document.getElementById('rilisModalTitle').innerText = 'Rilis: ' + data.nama_bank;
            document.getElementById('rilisModalSubtitle').innerText = 'Mata Pelajaran: ' + data.nama_mapel;

            // Kelas yang diampu pada mapel ini
            const kelasMapelStr = data.kelas_mapel || '';
            const kelasList = kelasMapelStr.split(',').map(s => s.trim()).filter(Boolean);

            // Kelas yang saat ini dirilis
            const activeKelas = data.kelas_rilis ? data.kelas_rilis.split(',').map(s => s.trim().toUpperCase()) : [];

            const container = document.getElementById('rilisClassContainer');
            container.innerHTML = '';

            if (kelasList.length === 0) {
                container.innerHTML = '<span style="color: var(--adm-danger); font-size: 0.85rem;">Tidak ada daftar kelas pada mapel ini.</span>';
            } else {
                kelasList.forEach(k => {
                    const isChecked = activeKelas.includes(k.toUpperCase());
                    const label = document.createElement('label');
                    label.className = 'chip-checkbox-label' + (isChecked ? ' checked' : '');
                    label.innerHTML = `
                        <input type="checkbox" name="target_kelas[]" value="${k}" class="chk-rilis" ${isChecked ? 'checked' : ''} onchange="handleRilisChipChange(this)">
                        <span>Kelas ${k}</span>
                    `;
                    container.appendChild(label);
                });
            }

            document.getElementById('modalRilisBank').style.display = 'flex';
        }

        function closeModalRilis() {
            document.getElementById('modalRilisBank').style.display = 'none';
        }

        function handleRilisChipChange(cb) {
            const label = cb.closest('.chip-checkbox-label');
            if (label) {
                if (cb.checked) label.classList.add('checked');
                else label.classList.remove('checked');
            }
        }

        function toggleSelectAllRilis(status) {
            document.querySelectorAll('.chk-rilis').forEach(cb => {
                cb.checked = status;
                const label = cb.closest('.chip-checkbox-label');
                if (label) {
                    if (status) label.classList.add('checked');
                    else label.classList.remove('checked');
                }
            });
        }

        // --- Konfirmasi Hapus Bank Soal ---
        function confirmDeleteBank(id, nama, jmlSoal) {
            const extraWarning = jmlSoal > 0 ? `<br><br><span style="color:#ef4444; font-weight:600;">Peringatan: Seluruh ${jmlSoal} butir soal di dalam bank soal ini akan ikut terhapus permanen!</span>` : '';
            Swal.fire({
                title: 'Hapus Bank Soal Ini?',
                html: `Apakah Anda yakin ingin menghapus <strong>"${nama}"</strong>?` + extraWarning,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus Bank Soal',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `../../controllers/GuruController.php?action=hapus_bank_soal&id=${id}`;
                }
            });
        }

        // --- Konfirmasi Acak Bank Soal Guru ---
        function confirmAcakBankGuru(id, nama, jmlSoal) {
            if (jmlSoal <= 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'Bank Soal Kosong',
                    text: 'Tidak ada butir soal di dalam paket bank soal ini untuk diacak.'
                });
                return;
            }

            Swal.fire({
                title: 'Acak Urutan Soal?',
                html: `Urutan seluruh <b>${jmlSoal} butir soal</b> pada <b>"${nama}"</b> akan diacak secara otomatis.<br><br><small style="color: #64748b;">Urutan baru ini akan berlaku saat siswa mengerjakan ujian CBT dan saat bank soal dicetak/didownload.</small>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fa-solid fa-shuffle"></i> Ya, Acak Sekarang!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `../../controllers/GuruController.php?action=acak_urutan_soal&bank_id=${id}&redirect_to=bank_soal`;
                }
            });
        }

        // Close on escape & click outside
        window.onclick = function(e) {
            ['modalTambahBank', 'modalEditBank', 'modalRilisBank', 'modalDownloadBank'].forEach(id => {
                const m = document.getElementById(id);
                if (e.target === m) m.style.display = 'none';
            });
        };
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                ['modalTambahBank', 'modalEditBank', 'modalRilisBank', 'modalDownloadBank'].forEach(id => {
                    const m = document.getElementById(id);
                    if (m && m.style.display === 'flex') m.style.display = 'none';
                });
            }
        });
    </script>

<?php include __DIR__ . '/footer.php'; ?>
