<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/database.php';
require_once '../../config/security.php';
protect_page(); 

$active_nav = 'kelola_soal';
$page_title = 'Kelola Butir Soal';

$guru_id = $_SESSION['user_id'] ?? 0;
$nama_guru = $_SESSION['nama'] ?? 'Guru'; 

// 1. Ambil Semua Bank Soal milik guru ini untuk dropdown switcher
$stmt_all_banks = $pdo->prepare("
    SELECT bs.*, s.nama_mapel, s.kelas as kelas_mapel,
           (SELECT COUNT(*) FROM questions WHERE bank_soal_id = bs.id) as total_soal
    FROM bank_soal bs
    JOIN subjects s ON bs.subject_id = s.id
    WHERE bs.guru_id = ?
    ORDER BY bs.id DESC
");
$stmt_all_banks->execute([$guru_id]);
$all_banks = $stmt_all_banks->fetchAll(PDO::FETCH_ASSOC);

// Jika guru belum memiliki bank soal sama sekali, arahkan ke pembuatan bank soal
if (empty($all_banks)) {
    header("Location: bank_soal.php?status=error&msg=" . urlencode("Anda belum memiliki Bank Soal. Silakan buat Bank Soal terlebih dahulu."));
    exit;
}

// 2. Tentukan Bank Soal yang sedang aktif dibuka
$bank_id = isset($_GET['bank_id']) ? (int)$_GET['bank_id'] : (int)($_SESSION['active_bank_soal_id'] ?? 0);

// Cari data bank soal yang dipilih
$current_bank = null;
foreach ($all_banks as $b) {
    if ($b['id'] == $bank_id) {
        $current_bank = $b;
        break;
    }
}

// Fallback jika ID tidak ditemukan, ambil bank soal pertama
if (!$current_bank) {
    $current_bank = $all_banks[0];
    $bank_id = $current_bank['id'];
}

// Set session sinkron
$_SESSION['active_bank_soal_id']   = $current_bank['id'];
$_SESSION['active_bank_soal_name'] = $current_bank['nama_bank'];
$_SESSION['active_subject_id']     = $current_bank['subject_id'];
$_SESSION['active_subject_name']   = $current_bank['nama_mapel'];
$_SESSION['active_subject_kelas']  = $current_bank['kelas_mapel'];

$subject_id = $current_bank['subject_id'];
$kelas_mapel = $current_bank['kelas_mapel'] ?? '';
$kelas_diampu = array_filter(array_map('trim', explode(',', $kelas_mapel)));

// 3. Setup Pagination & Ambil Soal pada Bank Soal ini
$limit = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Hitung total butir soal
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE bank_soal_id = ?");
$stmt_count->execute([$bank_id]);
$total_data = (int)$stmt_count->fetchColumn();
$total_pages = ceil($total_data / $limit);

// Ambil data soal
$stmt_soal = $pdo->prepare("
    SELECT id, urutan, pertanyaan, kelas, kunci_jawaban, img_pertanyaan,
           pil_a, pil_b, pil_c, pil_d, pil_e,
           img_a, img_b, img_c, img_d, img_e
    FROM questions
    WHERE bank_soal_id = ?
    ORDER BY (CASE WHEN urutan > 0 THEN urutan ELSE 9999999 END) ASC, id ASC
    LIMIT $limit OFFSET $offset
");
$stmt_soal->execute([$bank_id]);
$questions = $stmt_soal->fetchAll(PDO::FETCH_ASSOC);

function buildPageUrl($p, $bank_id) {
    $params = $_GET;
    $params['page'] = $p;
    $params['bank_id'] = $bank_id;
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($current_bank['nama_bank']) ?> - Kelola Soal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="guru_theme.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .page-header h1 {
            font-size: 1.55rem;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.02em;
        }

        .bank-banner {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 22px 26px;
            margin-bottom: 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 18px;
            box-shadow: var(--shadow-sm);
        }
        .bank-info-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .bank-info-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 6px;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .info-gambar-box {
            background: var(--adm-blue-50);
            border: 1px solid var(--adm-blue-100);
            border-radius: var(--radius-sm);
            padding: 12px 18px;
            margin-bottom: 22px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 0.85rem;
            color: var(--adm-slate-700);
            line-height: 1.5;
        }

        .soal-snippet {
            font-size: 0.88rem;
            color: var(--text-main);
            line-height: 1.5;
            max-width: 500px;
        }

        .badge-kunci {
            background: var(--adm-blue-50);
            color: var(--primary);
            border: 1px solid var(--adm-blue-100);
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 800;
            display: inline-block;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        @media (max-width: 640px) {
            .grid-2 { grid-template-columns: 1fr; }
        }

        /* Modal */
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
            padding: 24px 28px;
            border-radius: var(--radius-md);
            width: 92%;
            max-width: 680px;
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

        /* Bulk Action Bar & Selection */
        .bulk-action-bar {
            display: none;
            background: #ffffff;
            border: 1px solid var(--adm-blue-100);
            border-left: 4px solid var(--primary);
            border-radius: var(--radius-md);
            padding: 12px 20px;
            margin-bottom: 18px;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            box-shadow: var(--shadow-sm);
            animation: slideDown 0.2s ease-out;
            flex-wrap: wrap;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
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

    <!-- Page Header & Navigasi -->
    <div class="page-header">
        <div>
            <div style="margin-bottom: 6px;">
                <a href="bank_soal.php" style="text-decoration: none; color: var(--primary); font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Kembali ke Daftar Semua Bank Soal</span>
                </a>
            </div>
            <h1>Butir Soal & Media Gambar</h1>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <!-- Switcher Cepat Bank Soal -->
            <select onchange="window.location.href='kelola_soal.php?bank_id=' + this.value" style="padding: 7px 12px; font-size: 0.85rem; font-weight: 600; background: #ffffff;">
                <?php foreach ($all_banks as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= ($b['id'] == $bank_id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['nama_bank']) ?> (<?= (int)$b['total_soal'] ?> Soal)
                    </option>
                <?php endforeach; ?>
            </select>

            <a href="input_soal.php?bank_id=<?= $bank_id ?>" class="btn btn-outline">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>+ Input Manual</span>
            </a>
            <a href="import_soal.php?bank_id=<?= $bank_id ?>" class="btn btn-outline">
                <i class="fa-solid fa-file-excel"></i>
                <span>Import Excel</span>
            </a>
            <button type="button" class="btn btn-outline" onclick="confirmAcakBankGuru(<?= $bank_id ?>, '<?= htmlspecialchars(addslashes($current_bank['nama_bank'])) ?>', <?= $total_data ?>)" title="Acak urutan butir soal dalam bank soal ini">
                <i class="fa-solid fa-shuffle" style="color: var(--primary);"></i>
                <span>Acak Urutan Soal</span>
            </button>
            <button type="button" class="btn btn-outline" onclick="confirmResetUrutanGuru(<?= $bank_id ?>, '<?= htmlspecialchars(addslashes($current_bank['nama_bank'])) ?>')" title="Kembalikan urutan default (berdasarkan nomor ID)">
                <i class="fa-solid fa-arrow-rotate-left"></i>
                <span>Reset Urutan</span>
            </button>
            <button type="button" class="btn btn-outline" onclick="openModalDownloadSoal()">
                <i class="fa-solid fa-cloud-arrow-down"></i>
                <span>Download Soal</span>
            </button>
            <button type="button" class="btn btn-primary" onclick="openModalRilisBankThis()">
                <i class="fa-solid fa-bullhorn"></i>
                <span>Rilis ke Kelas</span>
            </button>
        </div>
    </div>

    <!-- Active Bank Banner Info -->
    <div class="bank-banner">
        <div>
            <div class="bank-info-title">
                <i class="fa-solid fa-layer-group" style="color: var(--primary);"></i>
                <span><?= htmlspecialchars($current_bank['nama_bank']) ?></span>
            </div>
            <div class="bank-info-meta">
                <span><i class="fa-solid fa-book"></i> Mapel: <strong><?= htmlspecialchars($current_bank['nama_mapel']) ?></strong></span>
                <span>&bull;</span>
                <span><i class="fa-solid fa-list-check"></i> Total: <strong><?= $total_data ?> Butir Soal</strong></span>
                <span>&bull;</span>
                <span>
                    <?php if ($current_bank['status_rilis'] === 'rilis' && !empty($current_bank['kelas_rilis'])): ?>
                        <span class="badge-rilis active-released">
                            <i class="fa-solid fa-circle-check"></i> Dirilis ke Kelas: <?= htmlspecialchars($current_bank['kelas_rilis']) ?>
                        </span>
                    <?php else: ?>
                        <span class="badge-rilis is-draft">
                            <i class="fa-solid fa-clock"></i> Draft (Belum Dirilis ke Siswa)
                        </span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        <div>
            <button type="button" class="btn btn-sm btn-outline" onclick="openModalRilisBankThis()">
                <i class="fa-solid fa-sliders"></i>
                <span>Ubah Kelas Rilis</span>
            </button>
        </div>
    </div>

    <!-- Panduan Gambar -->
    <div class="info-gambar-box">
        <i class="fa-solid fa-image" style="color: var(--primary); font-size: 1.25rem; margin-top: 2px;"></i>
        <div>
            <strong style="color: var(--primary); display: block; margin-bottom: 2px;">Kemudahan Soal Bergambar:</strong>
            Semua butir soal di bawah adalah bagian dari <strong><?= htmlspecialchars($current_bank['nama_bank']) ?></strong>. Jika soal memiliki gambar pertanyaan atau pilihan jawaban, Anda cukup mengunggahnya <strong>1 kali</strong> melalui tombol <strong>Edit & Gambar</strong>. Gambar otomatis tampil di semua kelas sasaran (<?= htmlspecialchars($current_bank['kelas_rilis'] ?: 'belum dirilis') ?>) tanpa perlu upload berulang kali.
        </div>
    </div>

    <!-- Hidden Bulk Delete & Bulk Acak Forms -->
    <form id="formBulkDelete" action="../../controllers/GuruController.php?action=hapus_banyak_soal" method="POST" style="display: none;">
        <input type="hidden" name="bank_id" value="<?= $bank_id ?>">
        <div id="bulkDeleteInputs"></div>
    </form>
    <form id="formBulkAcak" action="../../controllers/GuruController.php?action=bulk_acak_soal" method="POST" style="display: none;">
        <input type="hidden" name="bank_id" value="<?= $bank_id ?>">
        <div id="bulkAcakInputs"></div>
    </form>

    <!-- Bulk Action Toolbar (Tampil saat ada butir soal dipilih) -->
    <div id="bulkActionBar" class="bulk-action-bar">
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <span class="bulk-count-badge" id="selectedCountBadge">0</span>
            <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-main);">
                <span id="selectedCountText">0</span> Butir Soal Dipilih
            </span>
            <span style="color: var(--border-color); margin: 0 4px;">|</span>
            <button type="button" class="btn btn-sm btn-outline" onclick="selectAllVisible(true)" style="padding: 4px 10px; font-size: 0.78rem;">
                <i class="fa-solid fa-check-double"></i> Pilih Semua di Halaman Ini (<?= count($questions) ?>)
            </button>
            <button type="button" class="btn btn-sm btn-outline" onclick="selectAllVisible(false)" style="padding: 4px 10px; font-size: 0.78rem; color: var(--adm-slate-600);">
                <i class="fa-solid fa-xmark"></i> Batalkan Pilihan
            </button>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn btn-sm btn-outline" onclick="confirmAcakSelectedGuru()" style="border-color: var(--primary); color: var(--primary); font-weight: 700;">
                <i class="fa-solid fa-shuffle"></i>
                <span>Acak Urutan Terpilih</span>
            </button>
            <button type="button" class="btn btn-sm" onclick="confirmDeleteSelected()" style="background: var(--adm-danger); color: #ffffff; border: none; padding: 7px 16px; font-weight: 700; display: inline-flex; align-items: center; gap: 7px; border-radius: var(--radius-sm); cursor: pointer; box-shadow: 0 1px 2px rgba(239, 68, 68, 0.2);">
                <i class="fa-solid fa-trash-can"></i>
                <span>Hapus Terpilih</span>
            </button>
        </div>
    </div>

    <!-- Tabel Butir Soal -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <?php if (empty($questions)): ?>
            <div style="text-align: center; padding: 48px 20px;">
                <div style="font-size: 3rem; color: var(--adm-slate-400); margin-bottom: 12px;">
                    <i class="fa-solid fa-file-circle-plus"></i>
                </div>
                <h3 style="font-weight: 700; color: var(--text-main); margin-bottom: 6px;">Belum Ada Butir Soal</h3>
                <p style="color: var(--text-muted); font-size: 0.88rem; max-width: 480px; margin: 0 auto 20px;">
                    Bank Soal ini masih kosong. Silakan tambahkan butir soal secara manual atau import sekaligus menggunakan template Excel.
                </p>
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <a href="input_soal.php?bank_id=<?= $bank_id ?>" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i>
                        <span>Input Soal Manual</span>
                    </a>
                    <a href="import_soal.php?bank_id=<?= $bank_id ?>" class="btn btn-outline">
                        <i class="fa-solid fa-file-excel"></i>
                        <span>Import via Excel</span>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 44px; text-align: center;">
                                <input type="checkbox" id="selectAllCheckbox" class="custom-checkbox" onchange="toggleSelectAll(this.checked)" title="Pilih Semua di Halaman Ini">
                            </th>
                            <th style="width: 50px; text-align: center;">No</th>
                            <th>Pertanyaan Soal</th>
                            <th style="width: 130px; text-align: center;">Lampiran Media</th>
                            <th style="width: 80px; text-align: center;">Kunci</th>
                            <th style="width: 140px; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = $offset + 1;
                        foreach ($questions as $q): 
                            $hasImg = !empty($q['img_pertanyaan']) || !empty($q['img_a']) || !empty($q['img_b']) || !empty($q['img_c']) || !empty($q['img_d']) || !empty($q['img_e']);
                        ?>
                        <tr id="row-soal-<?= $q['id'] ?>">
                            <td style="text-align: center;">
                                <input type="checkbox" class="chk-soal custom-checkbox" value="<?= $q['id'] ?>" onchange="onItemCheckChange()" data-id="<?= $q['id'] ?>" title="Pilih butir soal ini">
                            </td>
                            <td style="text-align: center; font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: var(--text-muted);">
                                <?= $no++ ?>
                                <?php if (!empty($q['urutan'])): ?>
                                    <div style="font-size: 0.7rem; font-weight: 700; color: var(--primary); background: var(--adm-blue-50); border: 1px solid var(--adm-blue-100); border-radius: 4px; padding: 1px 4px; margin-top: 3px;" title="Urutan Acak: Nomor <?= $q['urutan'] ?>">#<?= $q['urutan'] ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="soal-snippet">
                                    <?= nl2br(htmlspecialchars(mb_strimwidth($q['pertanyaan'], 0, 160, '...'))) ?>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($hasImg): ?>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 4px; background: var(--adm-blue-50); color: var(--primary); font-size: 0.75rem; font-weight: 600;">
                                        <i class="fa-solid fa-image"></i> Bergambar
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.75rem;">Teks Saja</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge-kunci"><?= htmlspecialchars($q['kunci_jawaban']) ?></span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                    <button type="button" class="btn btn-sm btn-outline" onclick="openModalEditSoal(<?= htmlspecialchars(json_encode($q)) ?>)" title="Edit teks soal dan upload gambar">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                        <span>Edit & Gambar</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline" style="color: var(--adm-danger);" onclick="confirmDeleteSoal(<?= $q['id'] ?>)" title="Hapus soal ini">
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
            <?php if ($total_pages > 1): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-top: 1px solid var(--border-color); flex-wrap: wrap; gap: 10px;">
                    <span style="font-size: 0.82rem; color: var(--text-muted);">
                        Menampilkan halaman <strong><?= $page ?></strong> dari <strong><?= $total_pages ?></strong> (Total <?= $total_data ?> butir soal)
                    </span>
                    <div style="display: flex; gap: 4px;">
                        <?php if ($page > 1): ?>
                            <a href="<?= buildPageUrl($page - 1, $bank_id) ?>" class="btn-page">&laquo; Prev</a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="<?= buildPageUrl($i, $bank_id) ?>" class="btn-page <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="<?= buildPageUrl($page + 1, $bank_id) ?>" class="btn-page">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- MODAL 1: RILIS BANK SOAL KE KELAS -->
    <div id="modalRilisBankThis" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModalRilisBankThis()"><i class="fa-solid fa-xmark"></i></span>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 18px;">
                <div style="width: 38px; height: 38px; border-radius: 8px; background: var(--adm-blue-50); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main); margin: 0;">Rilis: <?= htmlspecialchars($current_bank['nama_bank']) ?></h3>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Mata Pelajaran: <?= htmlspecialchars($current_bank['nama_mapel']) ?></p>
                </div>
            </div>

            <form action="../../controllers/GuruController.php?action=rilis_bank_soal" method="POST">
                <input type="hidden" name="bank_id" value="<?= $bank_id ?>">
                <input type="hidden" name="redirect_to" value="kelola_soal">

                <div style="background: var(--adm-blue-50); border: 1px solid var(--adm-blue-100); border-radius: var(--radius-sm); padding: 12px 16px; margin-bottom: 18px; font-size: 0.82rem; color: var(--adm-slate-700); line-height: 1.5;">
                    <i class="fa-solid fa-circle-info" style="color: var(--primary); margin-right: 4px;"></i>
                    Centang kelas-kelas yang akan menerima butir soal pada Bank Soal ini. Seluruh butir soal (beserta gambarnya) akan otomatis aktif untuk kelas terpilih saat ujian CBT berlangsung.
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label style="font-weight: 700; font-size: 0.85rem; color: var(--adm-slate-800); margin: 0;">Pilih Kelas Sasaran Rilis:</label>
                        <div style="display: flex; gap: 6px;">
                            <button type="button" class="btn btn-sm btn-outline" onclick="toggleSelectAllRilisThis(true)" style="padding: 2px 8px; font-size: 0.72rem;">
                                Pilih Semua
                            </button>
                            <button type="button" class="btn btn-sm btn-outline" onclick="toggleSelectAllRilisThis(false)" style="padding: 2px 8px; font-size: 0.72rem;">
                                Kosongkan (Draft)
                            </button>
                        </div>
                    </div>

                    <div class="chip-checkbox-group">
                        <?php 
                        $activeKelasArr = $current_bank['kelas_rilis'] ? array_map('trim', explode(',', strtoupper($current_bank['kelas_rilis']))) : [];
                        foreach ($kelas_diampu as $k): 
                            $isChecked = in_array(strtoupper($k), $activeKelasArr);
                        ?>
                            <label class="chip-checkbox-label <?= $isChecked ? 'checked' : '' ?>">
                                <input type="checkbox" name="target_kelas[]" value="<?= htmlspecialchars($k) ?>" class="chk-rilis-this" <?= $isChecked ? 'checked' : '' ?> onchange="handleChipChange(this)">
                                <span>Kelas <?= htmlspecialchars($k) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <small style="color: var(--text-muted); font-size: 0.78rem; display: block; margin-top: 8px;">
                        Tip: Jika dikosongkan, Bank Soal akan disimpan sebagai <strong>Draft</strong> (belum dirilis ke siswa).
                    </small>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 16px; border-top: 1px solid var(--border-color);">
                    <button type="button" class="btn btn-outline" onclick="closeModalRilisBankThis()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="padding: 10px 22px;">
                        <i class="fa-solid fa-check"></i>
                        <span>Simpan Status Rilis</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: EDIT BUTIR SOAL & MEDIA GAMBAR -->
    <div id="modalEditSoal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModalEditSoal()"><i class="fa-solid fa-xmark"></i></span>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 18px;">
                <div style="width: 38px; height: 38px; border-radius: 8px; background: var(--adm-blue-50); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main); margin: 0;">Edit Butir Soal & Media Gambar</h3>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">Gambar yang diunggah akan otomatis berlaku di semua kelas yang dirilis.</p>
                </div>
            </div>

            <form action="../../controllers/GuruController.php?action=update_soal" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_soal" id="edit_id_soal">
                <input type="hidden" name="bank_id" value="<?= $bank_id ?>">

                <div class="form-group" style="margin-bottom: 16px;">
                    <label style="font-weight: 600; font-size: 0.85rem; color: var(--adm-slate-700); display: block; margin-bottom: 6px;">
                        Teks Pertanyaan Soal <span style="color: red;">*</span>
                    </label>
                    <textarea name="pertanyaan" id="edit_soal_pertanyaan" rows="3" required></textarea>

                    <div style="margin-top: 10px;">
                        <small style="color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 4px;">Lampiran Gambar Pertanyaan:</small>
                        <img id="preview_img_pertanyaan" src="" alt="Gambar Pertanyaan" style="max-width: 220px; max-height: 140px; display: none; border: 1px solid var(--border-color); border-radius: 6px; margin-bottom: 6px;">
                        <input type="file" name="img_pertanyaan" accept="image/*" style="font-size: 0.82rem;">
                    </div>
                </div>

                <div class="grid-2" style="margin-bottom: 16px;">
                    <div class="form-group">
                        <label style="font-weight: 600; font-size: 0.82rem; color: var(--adm-slate-700);">Pilihan A</label>
                        <textarea name="pil_a" id="edit_soal_pil_a" rows="2"></textarea>
                        <img id="preview_img_a" src="" alt="Gambar A" style="max-width: 90px; max-height: 70px; display: none; border: 1px solid var(--border-color); border-radius: 4px; margin: 4px 0;">
                        <input type="file" name="img_a" accept="image/*" style="font-size: 0.78rem;">
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 600; font-size: 0.82rem; color: var(--adm-slate-700);">Pilihan B</label>
                        <textarea name="pil_b" id="edit_soal_pil_b" rows="2"></textarea>
                        <img id="preview_img_b" src="" alt="Gambar B" style="max-width: 90px; max-height: 70px; display: none; border: 1px solid var(--border-color); border-radius: 4px; margin: 4px 0;">
                        <input type="file" name="img_b" accept="image/*" style="font-size: 0.78rem;">
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 600; font-size: 0.82rem; color: var(--adm-slate-700);">Pilihan C</label>
                        <textarea name="pil_c" id="edit_soal_pil_c" rows="2"></textarea>
                        <img id="preview_img_c" src="" alt="Gambar C" style="max-width: 90px; max-height: 70px; display: none; border: 1px solid var(--border-color); border-radius: 4px; margin: 4px 0;">
                        <input type="file" name="img_c" accept="image/*" style="font-size: 0.78rem;">
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 600; font-size: 0.82rem; color: var(--adm-slate-700);">Pilihan D</label>
                        <textarea name="pil_d" id="edit_soal_pil_d" rows="2"></textarea>
                        <img id="preview_img_d" src="" alt="Gambar D" style="max-width: 90px; max-height: 70px; display: none; border: 1px solid var(--border-color); border-radius: 4px; margin: 4px 0;">
                        <input type="file" name="img_d" accept="image/*" style="font-size: 0.78rem;">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label style="font-weight: 600; font-size: 0.82rem; color: var(--text-muted);">Pilihan E (Opsional)</label>
                    <textarea name="pil_e" id="edit_soal_pil_e" rows="2"></textarea>
                    <img id="preview_img_e" src="" alt="Gambar E" style="max-width: 90px; max-height: 70px; display: none; border: 1px solid var(--border-color); border-radius: 4px; margin: 4px 0;">
                    <input type="file" name="img_e" accept="image/*" style="font-size: 0.78rem;">
                </div>

                <div class="form-group" style="margin-bottom: 22px;">
                    <label style="font-weight: 600; font-size: 0.85rem; color: var(--adm-slate-700); display: block; margin-bottom: 6px;">
                        Kunci Jawaban Resmi <span style="color: red;">*</span>
                    </label>
                    <select name="kunci_jawaban" id="edit_soal_kunci" required style="font-weight: 700; color: var(--primary); background: var(--adm-blue-50);">
                        <option value="A">Pilihan A</option>
                        <option value="B">Pilihan B</option>
                        <option value="C">Pilihan C</option>
                        <option value="D">Pilihan D</option>
                        <option value="E">Pilihan E</option>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 16px; border-top: 1px solid var(--border-color);">
                    <button type="button" class="btn btn-outline" onclick="closeModalEditSoal()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="padding: 10px 22px;">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL DOWNLOAD SOAL (PDF & EXCEL) -->
    <div id="modalDownloadSoal" class="modal">
        <div class="modal-content" style="max-width: 580px;">
            <span class="close-btn" onclick="closeModalDownloadSoal()"><i class="fa-solid fa-xmark"></i></span>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                <div style="width: 44px; height: 44px; border-radius: 10px; background: var(--adm-blue-50); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                    <i class="fa-solid fa-cloud-arrow-down"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--text-main); margin: 0;">Download Naskah Bank Soal</h3>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin: 2px 0 0 0;">Pilih format dokumen untuk mengunduh naskah butir soal.</p>
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
                                <input type="checkbox" id="pdf_include_kunci" checked style="accent-color: var(--primary); width: 16px; height: 16px; cursor: pointer;">
                                <span>Sertakan Lembar Kunci Jawaban di Halaman Terpisah</span>
                            </label>
                        </div>
                        <button type="button" class="btn btn-outline" onclick="downloadPDF()" style="width: 100%; justify-content: center; border-color: #dc2626; color: #dc2626;">
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
                        <button type="button" class="btn btn-outline" onclick="downloadExcel()" style="width: 100%; justify-content: center; border-color: #059669; color: #059669;">
                            <i class="fa-solid fa-file-arrow-down"></i>
                            <span>Download Format Excel (.xlsx)</span>
                        </button>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeModalDownloadSoal()">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        // Modal Download Soal
        function openModalDownloadSoal() {
            document.getElementById('modalDownloadSoal').style.display = 'flex';
        }
        function closeModalDownloadSoal() {
            document.getElementById('modalDownloadSoal').style.display = 'none';
        }
        function downloadPDF() {
            const withKunci = document.getElementById('pdf_include_kunci').checked ? 1 : 0;
            window.location.href = `../../controllers/GuruController.php?action=download_bank_soal&format=pdf&bank_id=<?= $bank_id ?>&with_kunci=${withKunci}`;
        }
        function downloadExcel() {
            window.location.href = `../../controllers/GuruController.php?action=download_bank_soal&format=excel&bank_id=<?= $bank_id ?>`;
        }

        // Modal Rilis Bank Soal
        function openModalRilisBankThis() {
            document.getElementById('modalRilisBankThis').style.display = 'flex';
        }
        function closeModalRilisBankThis() {
            document.getElementById('modalRilisBankThis').style.display = 'none';
        }

        function toggleSelectAllRilisThis(status) {
            document.querySelectorAll('.chk-rilis-this').forEach(cb => {
                cb.checked = status;
                const label = cb.closest('.chip-checkbox-label');
                if (label) {
                    if (status) label.classList.add('checked');
                    else label.classList.remove('checked');
                }
            });
        }

        function handleChipChange(cb) {
            const label = cb.closest('.chip-checkbox-label');
            if (label) {
                if (cb.checked) label.classList.add('checked');
                else label.classList.remove('checked');
            }
        }

        // Modal Edit Soal
        function openModalEditSoal(data) {
            document.getElementById('edit_id_soal').value = data.id || '';
            document.getElementById('edit_soal_pertanyaan').value = data.pertanyaan || '';
            document.getElementById('edit_soal_pil_a').value = data.pil_a || '';
            document.getElementById('edit_soal_pil_b').value = data.pil_b || '';
            document.getElementById('edit_soal_pil_c').value = data.pil_c || '';
            document.getElementById('edit_soal_pil_d').value = data.pil_d || '';
            document.getElementById('edit_soal_pil_e').value = data.pil_e || '';
            document.getElementById('edit_soal_kunci').value = (data.kunci_jawaban || 'A').toUpperCase();

            const folderGambar = "../../assets/img/soal/";
            const listGambar = ['img_pertanyaan', 'img_a', 'img_b', 'img_c', 'img_d', 'img_e'];
            
            listGambar.forEach(field => {
                const imgElement = document.getElementById('preview_' + field);
                if (imgElement) {
                    if (data[field] && data[field].trim() !== "") {
                        imgElement.src = folderGambar + data[field];
                        imgElement.style.display = "block"; 
                    } else {
                        imgElement.src = "";
                        imgElement.style.display = "none";  
                    }
                }
            });

            document.getElementById('modalEditSoal').style.display = 'flex';
        }

        function closeModalEditSoal() {
            document.getElementById('modalEditSoal').style.display = 'none';
        }

        // Konfirmasi Hapus Soal Tunggal
        function confirmDeleteSoal(id) {
            Swal.fire({
                title: 'Hapus Butir Soal Ini?',
                text: 'Butir soal ini akan dihapus permanen dari bank soal.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus Soal',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `../../controllers/GuruController.php?action=hapus_soal&id=${id}&bank_id=<?= $bank_id ?>`;
                }
            });
        }

        // ==========================================
        // FITUR SELECT & SELECT ALL & BULK DELETE
        // ==========================================
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

            // Highlight baris tabel yang dipilih
            allCheckboxes.forEach(cb => {
                const row = document.getElementById('row-soal-' + cb.value);
                if (row) {
                    if (cb.checked) {
                        row.classList.add('row-selected');
                    } else {
                        row.classList.remove('row-selected');
                    }
                }
            });

            // Tampilkan / sembunyikan toolbar
            if (toolbar) {
                toolbar.style.display = (selectedCount > 0) ? 'flex' : 'none';
            }

            // Sinkronisasi status checkbox master (Select All)
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
            document.querySelectorAll('.chk-soal').forEach(cb => {
                cb.checked = status;
            });
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
                    text: 'Silakan centang satu atau lebih butir soal yang ingin dihapus.'
                });
                return;
            }

            Swal.fire({
                title: `Hapus ${count} Butir Soal Terpilih?`,
                text: `Sebanyak ${count} butir soal yang dicentang beserta gambar lampirannya akan dihapus permanen dari Bank Soal ini.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: `Ya, Hapus ${count} Soal`,
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
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

        // --- Konfirmasi Acak Soal Guru ---
        function confirmAcakBankGuru(bankId, namaBank, totalSoal) {
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
                html: `Urutan seluruh <b>${totalSoal} butir soal</b> pada paket <b>"${namaBank}"</b> akan diacak secara otomatis.<br><br><small style="color: #64748b;">Urutan baru ini akan berlaku saat siswa mengerjakan ujian CBT dan saat bank soal dicetak/didownload.</small>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fa-solid fa-shuffle"></i> Ya, Acak Sekarang!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `../../controllers/GuruController.php?action=acak_urutan_soal&bank_id=${bankId}&redirect_to=kelola_soal`;
                }
            });
        }

        function confirmResetUrutanGuru(bankId, namaBank) {
            Swal.fire({
                title: 'Reset Urutan Soal?',
                html: `Kembalikan urutan butir soal pada paket <b>"${namaBank}"</b> ke susunan default awal (berdasarkan nomor ID)?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0ea5e9',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fa-solid fa-arrow-rotate-left"></i> Ya, Reset Urutan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `../../controllers/GuruController.php?action=reset_urutan_soal&bank_id=${bankId}&redirect_to=kelola_soal`;
                }
            });
        }

        function confirmAcakSelectedGuru() {
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

        // Close on escape & click outside
        window.onclick = function(e) {
            ['modalRilisBankThis', 'modalEditSoal', 'modalDownloadSoal'].forEach(id => {
                const m = document.getElementById(id);
                if (e.target === m) m.style.display = 'none';
            });
        };
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                ['modalRilisBankThis', 'modalEditSoal', 'modalDownloadSoal'].forEach(id => {
                    const m = document.getElementById(id);
                    if (m && m.style.display === 'flex') m.style.display = 'none';
                });
            }
        });
    </script>

<?php include __DIR__ . '/footer.php'; ?>