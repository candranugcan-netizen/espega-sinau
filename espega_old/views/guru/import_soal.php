<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/database.php';
require_once '../../config/security.php'; 
protect_page(); 

$active_nav = 'import_soal';
$page_title = 'Import Soal Excel';

$guru_id = $_SESSION['user_id'] ?? 0;
$nama_guru = $_SESSION['nama'] ?? 'Guru'; 

// 1. Ambil daftar semua Bank Soal milik guru
$stmt_banks = $pdo->prepare("
    SELECT bs.*, s.nama_mapel, s.kelas as kelas_mapel,
           (SELECT COUNT(*) FROM questions WHERE bank_soal_id = bs.id) as total_soal
    FROM bank_soal bs
    JOIN subjects s ON bs.subject_id = s.id
    WHERE bs.guru_id = ?
    ORDER BY bs.id DESC
");
$stmt_banks->execute([$guru_id]);
$daftar_bank = $stmt_banks->fetchAll(PDO::FETCH_ASSOC);

// Jika belum ada bank soal, arahkan ke pembuatan bank soal
if (empty($daftar_bank)) {
    header("Location: bank_soal.php?status=error&msg=" . urlencode("Anda belum memiliki Bank Soal. Silakan buat Bank Soal terlebih dahulu sebelum import soal."));
    exit;
}

// 2. Tentukan Bank Soal Sasaran
$target_bank_id = isset($_GET['bank_id']) ? (int)$_GET['bank_id'] : (int)($_SESSION['active_bank_soal_id'] ?? ($daftar_bank[0]['id'] ?? 0));

$selected_bank = null;
foreach ($daftar_bank as $b) {
    if ($b['id'] == $target_bank_id) {
        $selected_bank = $b;
        break;
    }
}
if (!$selected_bank) {
    $selected_bank = $daftar_bank[0];
    $target_bank_id = $selected_bank['id'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Soal Excel - Espega Sinau</title>
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

        .import-layout {
            max-width: 800px;
            margin: 0 auto;
        }

        .info-box {
            background: var(--adm-blue-50);
            border: 1px solid var(--adm-blue-100);
            border-radius: var(--radius-sm);
            padding: 16px 20px;
            margin-bottom: 24px;
            font-size: 0.85rem;
            color: var(--adm-slate-700);
            line-height: 1.5;
        }

        .upload-dropzone {
            border: 2px dashed var(--border-color);
            border-radius: var(--radius-md);
            padding: 40px 20px;
            text-align: center;
            background: var(--adm-slate-50);
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 24px;
            position: relative;
        }
        .upload-dropzone:hover {
            border-color: var(--primary);
            background: var(--adm-blue-50);
        }
        .dropzone-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 12px;
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
            <h1>Import Soal via Excel</h1>
            <p>Unggah berkas spreadsheet format .xlsx untuk memasukkan butir soal sekaligus ke dalam Bank Soal terpilih.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="kelola_soal.php?bank_id=<?= $target_bank_id ?>" class="btn btn-outline">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Kembali ke Bank Soal</span>
            </a>
            <a href="../../controllers/GuruController.php?action=download_template" class="btn btn-outline">
                <i class="fa-solid fa-file-arrow-down"></i>
                <span>Unduh Format Template</span>
            </a>
        </div>
    </div>

    <div class="import-layout">
        <!-- Panduan & Format Template -->
        <div class="info-box">
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <i class="fa-solid fa-layer-group" style="color: var(--primary); font-size: 1.25rem; margin-top: 2px;"></i>
                <div>
                    <strong style="color: var(--text-main); font-size: 0.92rem; display: block; margin-bottom: 4px;">Alur Import Bank Soal:</strong>
                    1. Seluruh butir soal dari berkas Excel akan dimasukkan ke dalam Bank Soal yang Anda pilih di bawah.<br>
                    2. <strong>Bebas Repot untuk Soal Bergambar:</strong> Setelah import selesai, Anda cukup menambahkan/mengedit gambar soal <strong>1 kali saja</strong> pada menu Kelola Soal. Gambar otomatis langsung tampil di semua kelas yang dirilis!<br>
                    3. Format kolom Excel wajib: <strong>Pertanyaan, Pilihan A, Pilihan B, Pilihan C, Pilihan D, Pilihan E, Kunci Jawaban</strong> (A/B/C/D/E).
                </div>
            </div>
        </div>

        <div class="card">
            <form action="../../controllers/GuruController.php?action=import_excel" method="POST" enctype="multipart/form-data">
                
                <!-- Pilihan Bank Soal Sasaran -->
                <div style="background: var(--adm-slate-50); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 18px; margin-bottom: 22px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                        <label style="font-weight: 700; font-size: 0.88rem; color: var(--adm-slate-800); margin: 0; display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-layer-group" style="color: var(--primary);"></i>
                            <span>Pilih Bank Soal Sasaran Import <span style="color:red;">*</span></span>
                        </label>
                        <a href="bank_soal.php" style="font-size: 0.8rem; font-weight: 600; color: var(--primary); text-decoration: none;">
                            + Buat Bank Soal Baru
                        </a>
                    </div>
                    <select name="bank_soal_id" required style="font-weight: 700; font-size: 0.95rem; padding: 10px 14px; background: #ffffff;">
                        <?php foreach ($daftar_bank as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= ($b['id'] == $target_bank_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($b['nama_bank']) ?> &bull; Mapel: <?= htmlspecialchars($b['nama_mapel']) ?> (<?= $b['status_rilis'] === 'rilis' ? 'Dirilis: ' . $b['kelas_rilis'] : 'Draft' ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="upload-dropzone" onclick="document.getElementById('f').click()">
                    <input type="file" name="file_excel" accept=".xlsx" required id="f" style="display:none">
                    <div class="dropzone-icon">
                        <i class="fa-solid fa-file-excel"></i>
                    </div>
                    <div id="dropzoneText">
                        <strong style="font-size: 0.95rem; color: var(--text-main); display: block; margin-bottom: 4px;">Klik di sini untuk memilih berkas Excel</strong>
                        <span style="font-size: 0.8rem; color: var(--text-muted);">Mendukung format file dokumen .xlsx</span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 13px; font-size: 0.95rem;">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <span>Mulai Proses Import ke Bank Soal</span>
                </button>
            </form>

            <div style="text-align: center; margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border-color);">
                <a href="../../controllers/GuruController.php?action=download_template" 
                   style="text-decoration: none; color: var(--primary); font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-download"></i>
                    <span>Unduh Template Spreadsheet Excel Kosong (.xlsx)</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('f').onchange = function() {
            if (this.files && this.files[0]) {
                const dzText = document.getElementById('dropzoneText');
                dzText.innerHTML = `
                    <strong style="font-size: 0.95rem; color: var(--primary); display: block; margin-bottom: 4px;">
                        <i class="fa-solid fa-check"></i> ${this.files[0].name}
                    </strong>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">${(this.files[0].size / 1024).toFixed(1)} KB &bull; Siap diunggah</span>
                `;
            }
        };
    </script>

<?php include __DIR__ . '/footer.php'; ?>