<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/database.php';
require_once '../../config/security.php';
protect_page(); 

$active_nav = 'dashboard';
$page_title = 'Dashboard Guru';

// 1. Ambil Data Guru
$guru_id = $_SESSION['user_id'] ?? 0;
$nama_guru = $_SESSION['nama'] ?? 'Guru'; 

$stmt = $pdo->prepare("SELECT nama_lengkap FROM users WHERE id = ?");
$stmt->execute([$guru_id]);
$guru_data = $stmt->fetch(PDO::FETCH_ASSOC);
if($guru_data) {
    $nama_guru = $guru_data['nama_lengkap'];
}

// 2. Ambil Daftar Mapel
$stmt_mapel = $pdo->prepare("SELECT * FROM subjects WHERE guru_id = ? ORDER BY nama_mapel ASC");
$stmt_mapel->execute([$guru_id]);
$daftar_mapel = $stmt_mapel->fetchAll(PDO::FETCH_ASSOC);

// 3. Cek Session Mapel Aktif
if ((!isset($_SESSION['active_subject_id']) || empty($_SESSION['active_subject_id'])) && count($daftar_mapel) > 0) {
    $_SESSION['active_subject_id'] = $daftar_mapel[0]['id'];
    $_SESSION['active_subject_name'] = $daftar_mapel[0]['nama_mapel'];
    $_SESSION['active_subject_kelas'] = $daftar_mapel[0]['kelas'];
}

// 4. Set Variabel Tampilan
$active_id = $_SESSION['active_subject_id'] ?? 0;
$nama_mapel_aktif = $_SESSION['active_subject_name'] ?? 'Belum Pilih Mapel';
$kelas_aktif = $_SESSION['active_subject_kelas'] ?? '-';

// 5. Hitung Statistik Real-time untuk Guru
$stmt_banks_count = $pdo->prepare("SELECT COUNT(*) FROM bank_soal WHERE guru_id = ?");
$stmt_banks_count->execute([$guru_id]);
$total_bank_guru = (int)$stmt_banks_count->fetchColumn();

$stmt_banks_rilis = $pdo->prepare("SELECT COUNT(*) FROM bank_soal WHERE guru_id = ? AND status_rilis = 'rilis'");
$stmt_banks_rilis->execute([$guru_id]);
$total_bank_rilis = (int)$stmt_banks_rilis->fetchColumn();

$stmt_soal = $pdo->prepare("
    SELECT COUNT(q.id) 
    FROM questions q 
    JOIN subjects s ON q.subject_id = s.id 
    WHERE s.guru_id = ?
");
$stmt_soal->execute([$guru_id]);
$total_soal_guru = (int)$stmt_soal->fetchColumn();

$stmt_peserta = $pdo->prepare("
    SELECT COUNT(es.id) 
    FROM exam_sessions es 
    JOIN exams e ON es.exam_id = e.id 
    WHERE e.subject_id = ?
");
$stmt_peserta->execute([$active_id]);
$total_peserta_mapel = (int)$stmt_peserta->fetchColumn();

$total_mapel_guru = count($daftar_mapel);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Guru - Espega Sinau</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="guru_theme.css">
    <style>
        .welcome-hero {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 24px 28px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .welcome-hero h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.02em;
        }
        .welcome-hero p {
            color: var(--text-muted);
            font-size: 0.92rem;
            margin-top: 4px;
        }
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 18px;
            margin-top: 8px;
        }
        .action-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 22px;
            box-shadow: var(--shadow-sm);
            text-decoration: none;
            color: inherit;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary);
        }
        .action-card-header {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 14px;
        }
        .action-icon {
            width: 44px;
            height: 44px;
            border-radius: var(--radius-sm);
            background: var(--adm-blue-50);
            color: var(--primary);
            border: 1px solid var(--adm-blue-100);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
            transition: 0.2s;
        }
        .action-card:hover .action-icon {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }
        .action-meta h4 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 4px;
        }
        .action-meta p {
            font-size: 0.82rem;
            color: var(--text-muted);
            line-height: 1.45;
        }
        .action-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 14px;
            border-top: 1px dashed var(--border-color);
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--primary);
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

    <!-- Hero Selamat Datang -->
    <div class="welcome-hero">
        <div>
            <h1>Selamat Datang, <?= htmlspecialchars(explode(' ', trim($nama_guru))[0]) ?></h1>
            <p>Kelola paket Bank Soal, masukkan butir soal pertanyaan, dan rilis ke kelas sasaran dengan mudah.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="bank_soal.php" class="btn btn-primary">
                <i class="fa-solid fa-layer-group"></i>
                <span>Kelola Bank Soal</span>
            </a>
            <a href="kelola_soal.php" class="btn btn-outline">
                <i class="fa-solid fa-list-check"></i>
                <span>Butir Soal & Media</span>
            </a>
        </div>
    </div>

    <!-- Statistik Cepat Guru -->
    <div class="adm-stats-grid">
        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= number_format($total_bank_guru) ?></div>
                <div class="adm-stat-label">Paket Bank Soal (<?= $total_bank_rilis ?> Dirilis)</div>
            </div>
        </div>

        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-list-check"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= number_format($total_soal_guru) ?></div>
                <div class="adm-stat-label">Total Butir Soal</div>
            </div>
        </div>

        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= $total_mapel_guru ?></div>
                <div class="adm-stat-label">Mapel Diampu</div>
            </div>
        </div>

        <div class="adm-stat-card">
            <div class="adm-stat-icon">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div class="adm-stat-num"><?= number_format($total_peserta_mapel) ?></div>
                <div class="adm-stat-label">Peserta Ujian Mapel Aktif</div>
            </div>
        </div>
    </div>

    <!-- Modul Aksi Cepat Guru -->
    <div class="action-grid">
        <a href="bank_soal.php" class="action-card">
            <div>
                <div class="action-card-header">
                    <div class="action-icon">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <div class="action-meta">
                        <h4>Daftar Bank Soal</h4>
                        <p>Buat paket bank soal baru (misal: "Bank Soal IPA Kelas 7") dan rilis ke kelas yang diampu.</p>
                    </div>
                </div>
            </div>
            <div class="action-footer">
                <span>Buka Bank Soal</span>
                <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>

        <a href="kelola_soal.php" class="action-card">
            <div>
                <div class="action-card-header">
                    <div class="action-icon">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <div class="action-meta">
                        <h4>Butir Soal & Media Gambar</h4>
                        <p>Kelola butir soal pada bank soal aktif, serta unggah ilustrasi gambar 1 kali untuk seluruh kelas.</p>
                    </div>
                </div>
            </div>
            <div class="action-footer">
                <span>Kelola Butir Soal</span>
                <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>

        <a href="input_soal.php" class="action-card">
            <div>
                <div class="action-card-header">
                    <div class="action-icon">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </div>
                    <div class="action-meta">
                        <h4>Input Soal Manual</h4>
                        <p>Tambahkan soal pertanyaan baru satu per satu lengkap dengan pilihan A sampai D serta kunci.</p>
                    </div>
                </div>
            </div>
            <div class="action-footer">
                <span>Tulis Soal Baru</span>
                <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>

        <a href="import_soal.php" class="action-card">
            <div>
                <div class="action-card-header">
                    <div class="action-icon">
                        <i class="fa-solid fa-file-excel"></i>
                    </div>
                    <div class="action-meta">
                        <h4>Import Excel</h4>
                        <p>Unggah file spreadsheet template Excel untuk memasukkan puluhan soal sekaligus dalam hitungan detik.</p>
                    </div>
                </div>
            </div>
            <div class="action-footer">
                <span>Unggah File Excel</span>
                <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>

        <a href="nilai.php" class="action-card">
            <div>
                <div class="action-card-header">
                    <div class="action-icon">
                        <i class="fa-solid fa-chart-simple"></i>
                    </div>
                    <div class="action-meta">
                        <h4>Hasil & Rekap Nilai</h4>
                        <p>Pantau skor ujian siswa per kelas dan unduh lembar nilai rekapitulasi ke format Excel.</p>
                    </div>
                </div>
            </div>
            <div class="action-footer">
                <span>Lihat Hasil Ujian</span>
                <i class="fa-solid fa-arrow-right"></i>
            </div>
        </a>
    </div>

<?php include __DIR__ . '/footer.php'; ?>