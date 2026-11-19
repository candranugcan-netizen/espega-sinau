<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/database.php';
require_once '../../config/security.php'; 
protect_page(); 

$active_nav = 'nilai';
$page_title = 'Rekap Nilai Siswa';

$guru_id = $_SESSION['user_id'] ?? 0;
$nama_guru = $_SESSION['nama'] ?? 'Guru'; 

// 1. Ambil daftar semua mapel milik guru ini
$stmt_mapel = $pdo->prepare("SELECT * FROM subjects WHERE guru_id = ? ORDER BY nama_mapel ASC");
$stmt_mapel->execute([$guru_id]);
$daftar_mapel = $stmt_mapel->fetchAll(PDO::FETCH_ASSOC);

// 2. Validasi Session Mapel Aktif
if ((!isset($_SESSION['active_subject_id']) || empty($_SESSION['active_subject_id'])) && count($daftar_mapel) > 0) {
    $_SESSION['active_subject_id'] = $daftar_mapel[0]['id'];
    $_SESSION['active_subject_name'] = $daftar_mapel[0]['nama_mapel'];
    $_SESSION['active_subject_kelas'] = $daftar_mapel[0]['kelas'];
}

$active_id = $_SESSION['active_subject_id'] ?? 0;
$nama_mapel_aktif = $_SESSION['active_subject_name'] ?? 'Belum Ada Mapel';
$kelas_aktif = $_SESSION['active_subject_kelas'] ?? '-';

// Ambil filter kelas dari URL
$filter_kelas = trim($_GET['kelas'] ?? '');

// Ambil daftar kelas yang ada pada mapel ini
$list_kelas = [];
if (!empty($kelas_aktif) && $kelas_aktif !== '-') {
    $raw_kls = explode(',', $kelas_aktif);
    foreach ($raw_kls as $rk) {
        $rk = trim($rk);
        if ($rk !== '' && !in_array($rk, $list_kelas)) {
            $list_kelas[] = $rk;
        }
    }
}
if (empty($list_kelas)) {
    $stmt_k = $pdo->prepare("SELECT DISTINCT u.kelas 
                            FROM exam_sessions es 
                            JOIN users u ON es.user_id = u.id
                            JOIN exams e ON es.exam_id = e.id
                            WHERE e.subject_id = ? AND u.kelas IS NOT NULL AND u.kelas != ''
                            ORDER BY u.kelas ASC");
    $stmt_k->execute([$active_id]);
    $list_kelas = $stmt_k->fetchAll(PDO::FETCH_COLUMN);
}

// Query data siswa (didukung filter kelas)
$sql = "SELECT u.username, u.nama_lengkap, u.kelas, es.skor, es.status_siswa, e.nama_ujian 
        FROM exam_sessions es 
        JOIN users u ON es.user_id = u.id
        JOIN exams e ON es.exam_id = e.id
        JOIN subjects s ON e.subject_id = s.id
        WHERE s.id = ?";
$params = [$active_id];

if ($filter_kelas !== '') {
    $sql .= " AND (u.kelas = ? OR (u.kelas IS NULL AND e.target_kelas = ?))";
    $params[] = $filter_kelas;
    $params[] = $filter_kelas;
}

$sql .= " ORDER BY u.kelas ASC, es.skor DESC, u.nama_lengkap ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung Statistik Nilai
$total_peserta = count($results);
$total_selesai = 0;
$total_pengerjaan = 0;
$total_skor = 0;
foreach($results as $r) {
    if ($r['status_siswa'] === 'selesai') $total_selesai++;
    else $total_pengerjaan++;
    $total_skor += (float)$r['skor'];
}
$rata_rata = $total_peserta > 0 ? round($total_skor / $total_peserta, 1) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Ujian Siswa - Espega Sinau</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="guru_theme.css">
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

        .student-info { display: flex; flex-direction: column; }
        .student-info strong { font-size: 0.88rem; color: var(--text-main); font-weight: 700; }
        .student-info small { color: var(--text-muted); font-size: 0.78rem; font-family: 'JetBrains Mono', monospace; }

        .score-pill {
            display: inline-block;
            padding: 4px 12px;
            background: var(--adm-blue-50);
            color: var(--primary);
            border: 1px solid var(--adm-blue-100);
            border-radius: 6px;
            font-weight: 800;
            font-size: 0.95rem;
            font-family: 'JetBrains Mono', monospace;
        }

        .badge-kelas {
            background: var(--adm-slate-100);
            color: var(--adm-slate-800);
            border: 1px solid var(--border-color);
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
        }
    </style>
</head>
<body class="admin-body">

<?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Notifikasi Status -->
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
            <h1>Hasil & Rekapitulasi Nilai</h1>
            <p>Pantau perolehan skor peserta ujian untuk mapel <strong><?= htmlspecialchars($nama_mapel_aktif) ?></strong>.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <?php if ($filter_kelas !== ''): ?>
                <a href="../../controllers/GuruController.php?action=download_nilai&subject_id=<?= $active_id ?>&kelas=<?= urlencode($filter_kelas) ?>" 
                   class="btn btn-primary">
                   <i class="fa-solid fa-file-excel"></i>
                   <span>Download Excel (Kelas <?= htmlspecialchars($filter_kelas) ?>)</span>
                </a>
                <a href="../../controllers/GuruController.php?action=download_nilai&subject_id=<?= $active_id ?>" 
                   class="btn btn-outline">
                   <i class="fa-solid fa-table-list"></i>
                   <span>Download Semua Kelas</span>
                </a>
            <?php else: ?>
                <a href="../../controllers/GuruController.php?action=download_nilai&subject_id=<?= $active_id ?>" 
                   class="btn btn-primary">
                   <i class="fa-solid fa-file-excel"></i>
                   <span>Download Excel Lengkap</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Stats Rekapitulasi -->
    <div class="adm-stats-grid">
        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= number_format($total_peserta) ?></div>
                <div class="adm-stat-label">Total Peserta</div>
            </div>
        </div>

        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-award"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= $rata_rata ?></div>
                <div class="adm-stat-label">Rata-rata Skor</div>
            </div>
        </div>

        <div class="adm-stat-card">
            <div class="adm-stat-icon" style="color: var(--adm-success);">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= $total_selesai ?></div>
                <div class="adm-stat-label">Ujian Selesai</div>
            </div>
        </div>

        <div class="adm-stat-card">
            <div class="adm-stat-icon" style="color: var(--adm-warning);">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= $total_pengerjaan ?></div>
                <div class="adm-stat-label">Sedang Pengerjaan</div>
            </div>
        </div>
    </div>

    <!-- Filter Kelas Bar -->
    <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px 18px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <form method="GET" action="nilai.php" style="display: inline-flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <label style="font-size: 0.85rem; font-weight: 600; color: var(--adm-slate-700); margin: 0;">Saring Berdasarkan Kelas:</label>
            <select name="kelas" onchange="this.form.submit()" 
                    style="padding: 7px 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); background: #ffffff; color: var(--text-main); font-weight: 600; font-size: 0.82rem; cursor: pointer; width: auto;">
                <option value="">Semua Kelas (<?= count($list_kelas) ?> Kelas)</option>
                <?php foreach($list_kelas as $k): ?>
                    <option value="<?= htmlspecialchars($k) ?>" <?= ($filter_kelas === $k) ? 'selected' : '' ?>>
                        Kelas <?= htmlspecialchars($k) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($filter_kelas !== ''): ?>
                <a href="nilai.php" style="font-size: 0.8rem; color: var(--adm-danger); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                    <i class="fa-solid fa-rotate-left"></i> Reset Filter
                </a>
            <?php endif; ?>
        </form>

        <div style="font-size: 0.82rem; color: var(--text-muted);">
            Menampilkan <strong><?= count($results) ?></strong> data siswa
        </div>
    </div>

    <!-- Tabel Nilai Siswa -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">No</th>
                    <th>Identitas Siswa</th>
                    <th style="width: 110px; text-align: center;">Kelas</th>
                    <th>Nama Paket Ujian</th>
                    <th style="width: 130px; text-align: center;">Skor Akhir</th>
                    <th style="width: 140px; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($results) > 0): ?>
                    <?php 
                    $no = 1;
                    foreach($results as $row): 
                    ?>
                    <tr>
                        <td style="text-align: center; color: var(--text-muted); font-size: 0.8rem; font-weight: 600;"><?= $no++ ?></td>
                        <td>
                            <div class="student-info">
                                <strong><?= htmlspecialchars($row['nama_lengkap']) ?></strong>
                                <small>NIS / Username: <?= htmlspecialchars($row['username']) ?></small>
                            </div>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-kelas">Kelas <?= htmlspecialchars($row['kelas'] ?: '-') ?></span>
                        </td>
                        <td>
                            <span style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($row['nama_ujian']) ?></span>
                        </td>
                        <td style="text-align: center;">
                            <div class="score-pill"><?= htmlspecialchars($row['skor']) ?></div>
                        </td>
                        <td style="text-align: center;">
                            <?php if($row['status_siswa'] == 'selesai'): ?>
                                <span class="badge badge-success">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <span>Selesai</span>
                                </span>
                            <?php else: ?>
                                <span class="badge badge-warning">
                                    <i class="fa-solid fa-clock"></i>
                                    <span>Pengerjaan</span>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 50px 20px;">
                            <div style="font-size: 2.5rem; color: var(--adm-slate-400); margin-bottom: 10px;">
                                <i class="fa-solid fa-folder-open"></i>
                            </div>
                            <h4 style="color: var(--text-main); font-weight: 700; margin-bottom: 4px;">Belum Ada Hasil Ujian</h4>
                            <p style="font-size: 0.85rem; color: var(--text-muted);">Belum ada data pengerjaan ujian siswa yang tersimpan untuk kriteria ini.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php include __DIR__ . '/footer.php'; ?>