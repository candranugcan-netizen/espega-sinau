<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/database.php';
require_once '../../config/security.php'; 
protect_page(); 

$active_nav = 'input_soal';
$page_title = 'Input Soal Manual';

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
    header("Location: bank_soal.php?status=error&msg=" . urlencode("Anda belum memiliki Bank Soal. Silakan buat Bank Soal terlebih dahulu sebelum input soal."));
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
    <title>Input Soal Manual - Espega Sinau</title>
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

        .form-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .opsi-box {
            margin-bottom: 16px;
            padding: 16px 18px;
            background: var(--adm-slate-50);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
        }
        .opsi-box-title {
            font-weight: 700;
            color: var(--primary);
            font-size: 0.88rem;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 8px;
        }

        .upload-compact {
            background: #ffffff;
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            border: 1px dashed var(--border-color);
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 8px;
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
            <h1>Input Soal Manual</h1>
            <p>Tuliskan butir soal pertanyaan dan kunci jawaban ke dalam Bank Soal terpilih.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="kelola_soal.php?bank_id=<?= $target_bank_id ?>" class="btn btn-outline">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Kembali ke Bank Soal</span>
            </a>
            <a href="import_soal.php?bank_id=<?= $target_bank_id ?>" class="btn btn-outline">
                <i class="fa-solid fa-file-excel"></i>
                <span>Import via Excel</span>
            </a>
        </div>
    </div>

    <div class="form-container">
        <div class="card">
            <form action="../../controllers/GuruController.php?action=simpan_soal" method="POST" enctype="multipart/form-data">
                
                <!-- Pilihan Bank Soal Sasaran -->
                <div style="background: var(--adm-slate-50); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 18px; margin-bottom: 22px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                        <label style="font-weight: 700; font-size: 0.88rem; color: var(--adm-slate-800); margin: 0; display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-layer-group" style="color: var(--primary);"></i>
                            <span>Pilih Bank Soal Sasaran <span style="color:red;">*</span></span>
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
                    <small style="color: var(--text-muted); font-size: 0.8rem; display: block; margin-top: 6px;">
                        Soal yang disimpan otomatis masuk ke dalam paket Bank Soal ini dan siap digunakan pada kelas yang dirilis.
                    </small>
                </div>

                <div class="form-group">
                    <label style="font-weight: 700;">Teks Pertanyaan Soal <span style="color:red;">*</span></label>
                    <textarea name="pertanyaan" rows="4" placeholder="Tuliskan butir pertanyaan di sini..." required></textarea>
                    
                    <div style="margin-top: 14px;">
                        <label style="font-weight: 600; font-size: 0.85rem;">Lampiran Gambar Pertanyaan (Opsional)</label>
                        <div class="upload-container" onclick="document.getElementById('img_pertanyaan').click()">
                            <div id="placeholder-pertanyaan">
                                <div class="upload-icon">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                </div>
                                <div style="font-size: 0.85rem; color: var(--text-muted);">
                                    <strong style="color: var(--primary);">Klik untuk memilih berkas gambar</strong>
                                    <div>Format JPG, PNG, atau WEBP (Maksimal 2MB)</div>
                                </div>
                            </div>
                            <img id="preview-pertanyaan" src="" style="max-width: 100%; max-height: 180px; display: none; margin: auto; border-radius: 6px; border: 1px solid var(--border-color);">
                            <input type="file" name="img_pertanyaan" id="img_pertanyaan" accept="image/*" style="display:none;" onchange="previewImage(this, 'preview-pertanyaan', 'placeholder-pertanyaan')">
                        </div>
                    </div>
                </div>

                <div style="margin: 28px 0 20px 0; padding-bottom: 8px; border-bottom: 1px solid var(--border-color);">
                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-list-check" style="color: var(--primary);"></i>
                        <span>Pilihan Jawaban (A, B, C, D, E)</span>
                    </h4>
                </div>

                <?php 
                $opsi = ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D', 'e' => 'E'];
                foreach($opsi as $key => $label): 
                ?>
                <div class="opsi-box">
                    <div class="opsi-box-title">
                        <span style="width: 22px; height: 22px; border-radius: 4px; background: var(--adm-blue-100); color: var(--primary); display: inline-flex; align-items: center; justify-content: center; font-family: 'JetBrains Mono', monospace; font-size: 0.8rem; font-weight: 800;"><?= $label ?></span>
                        <span>Pilihan <?= $label ?> <?= in_array($label, ['A','B','C','D']) ? '<span style="color:red;">*</span>' : '<span style="color:var(--text-muted); font-size:0.75rem; font-weight:normal;">(Opsional)</span>' ?></span>
                    </div>
                    <input type="text" name="pil_<?= $key ?>" placeholder="Teks pilihan jawaban <?= $label ?>..." <?= in_array($label, ['A','B','C','D']) ? 'required' : '' ?>>
                    
                    <div class="upload-compact">
                        <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('img_<?= $key ?>').click()">
                            <i class="fa-solid fa-image"></i>
                            <span>Pilih Gambar</span>
                        </button>
                        <input type="file" name="img_<?= $key ?>" id="img_<?= $key ?>" accept="image/*" style="display:none;" onchange="previewImage(this, 'preview-<?= $key ?>', 'placeholder-<?= $key ?>')">
                        <div id="placeholder-<?= $key ?>" style="font-size: 0.8rem; color: var(--text-muted); flex: 1;">Tidak ada gambar terpilih</div>
                        <img id="preview-<?= $key ?>" src="" style="height: 36px; width: 36px; object-fit: cover; display: none; border-radius: 4px; border: 1px solid var(--border-color);">
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="form-group" style="margin-top: 24px;">
                    <label style="font-weight: 700;">Kunci Jawaban Resmi <span style="color:red;">*</span></label>
                    <select name="kunci_jawaban" required style="font-weight: 700; background: var(--adm-blue-50); border-color: var(--adm-blue-100); color: var(--primary);">
                        <option value="">-- Tentukan Kunci Jawaban Benar --</option>
                        <option value="A">Pilihan A</option>
                        <option value="B">Pilihan B</option>
                        <option value="C">Pilihan C</option>
                        <option value="D">Pilihan D</option>
                        <option value="E">Pilihan E</option>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border-color);">
                    <a href="kelola_soal.php?bank_id=<?= $target_bank_id ?>" class="btn btn-outline">Batal</a>
                    <button type="submit" class="btn btn-primary" style="padding: 11px 24px;">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Simpan ke Bank Soal</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function previewImage(input, previewId, placeholderId) {
        const preview = document.getElementById(previewId);
        const placeholder = document.getElementById(placeholderId);
        const file = input.files[0];

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                if(placeholder) {
                    if(placeholder.tagName === 'DIV' && !placeholder.querySelector('.upload-icon')) {
                        placeholder.innerText = file.name;
                    } else {
                        placeholder.style.display = 'none';
                    }
                }
            };
            reader.readAsDataURL(file);
        }
    }
    </script>

<?php include __DIR__ . '/footer.php'; ?>