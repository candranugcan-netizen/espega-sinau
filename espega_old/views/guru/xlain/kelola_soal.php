<?php 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/database.php';
require_once '../../config/security.php';
protect_page(); 

function display_alerts() {
    if (isset($_GET['status'])) {
        $type = $_GET['status'] == 'success' ? 'alert-success' : 'alert-error';
        $icon = $_GET['status'] == 'success' ? '✅' : '❌';
        $msg = htmlspecialchars($_GET['msg'] ?? '');
        echo "
        <div class='alert-container' id='alertBox'>
            <div class='alert-toast $type'>
                <span>$icon</span>
                <div>
                    <strong style='display:block'>Informasi</strong>
                    <small>$msg</small>
                </div>
            </div>
        </div>
        <script>
            setTimeout(() => { 
                const box = document.getElementById('alertBox');
                if(box) box.style.display = 'none';
            }, 4000);
        </script>";
    }
}

// --- LOGIKA BARU UNTUK MULTI MAPEL ---
$guru_id = $_SESSION['user_id'];
$nama_guru = $_SESSION['nama']; 

// 1. Ambil daftar semua mapel milik guru ini (untuk diisi ke Dropdown)
$stmt_mapel = $pdo->prepare("SELECT * FROM subjects WHERE guru_id = ?");
$stmt_mapel->execute([$guru_id]);
$daftar_mapel = $stmt_mapel->fetchAll();

// 2. Validasi Session Mapel Aktif (Jaga-jaga jika login baru pertama kali)
// Jika belum ada session mapel aktif, set default ke mapel pertama yg ditemukan
if (!isset($_SESSION['active_subject_id']) && count($daftar_mapel) > 0) {
    $_SESSION['active_subject_id'] = $daftar_mapel[0]['id'];
    $_SESSION['active_subject_name'] = $daftar_mapel[0]['nama_mapel'];
    $_SESSION['active_subject_kelas'] = $daftar_mapel[0]['kelas'];
}

// 3. Siapkan variabel untuk ditampilkan
$active_id = $_SESSION['active_subject_id'] ?? 0;
$nama_mapel_aktif = $_SESSION['active_subject_name'] ?? 'Belum Ada Mapel';
$kelas_aktif = $_SESSION['active_subject_kelas'] ?? '-';

$stmt_sub = $pdo->prepare("SELECT id, nama_mapel FROM subjects WHERE guru_id = ?");
$stmt_sub->execute([$guru_id]);
$subject = $stmt_sub->fetch();
$subject_id = $subject['id'] ?? 0;
$nama_mapel = $subject['nama_mapel'] ?? 'Tidak Diketahui';

$stmt = $pdo->prepare("SELECT u.nama_lengkap, s.nama_mapel FROM users u 
                       LEFT JOIN subjects s ON u.id = s.guru_id 
                       WHERE u.id = ?");
$stmt->execute([$guru_id]);
$data = $stmt->fetch();
$nama_guru = $data['nama_lengkap'] ?? $_SESSION['nama'];
$mapel = $data['nama_mapel'] ?? 'Belum Menampung Mapel';

// Ambil semua soal berdasarkan subject_id
$stmt_soal = $pdo->prepare("SELECT * FROM questions WHERE subject_id = ? ORDER BY id DESC LIMIT 50");
// PERBAIKAN: Gunakan $active_id (dari sesi/dropdown), BUKAN $subject_id
$stmt_soal->execute([$active_id]); 
$questions = $stmt_soal->fetchAll();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Bank Soal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style_guru.css">
    <style>
        /* Gaya Tambahan Khusus Halaman Ini */
        .table-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 0.9rem; }
        th { background-color: #f8fafc; color: #64748b; font-weight: 600; }
        tr:hover { background-color: #f1f5f9; }
        
        .btn-action { padding: 6px 12px; border-radius: 5px; text-decoration: none; font-size: 0.8rem; margin-right: 5px; display: inline-block; cursor: pointer; border: none;}
        .btn-edit { background: #fbbf24; color: #fff; }
        .btn-del { background: #ef4444; color: #fff; }
        
        .badge { padding: 4px 8px; border-radius: 4px; background: #e0f2fe; color: #0284c7; font-size: 0.75rem; font-weight: bold; }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: white; margin: 5% auto; padding: 25px; border-radius: 10px; width: 60%; max-width: 800px; max-height: 90vh; overflow-y: auto; position: relative; }
        .close { float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input[type="text"], .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    </style>
</head>
<body>
    <?php display_alerts(); ?>
    
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header"><h3>ESPEGA SINAU</h3></div>
        <div class="sidebar-profile">
            <p>Guru Pengampu:</p><h4><?=$nama_guru?></h4>
        </div>

       <div style="padding: 15px; border-bottom: 1px solid #194fa5; background: rgba(0,0,0,0.1);">
            <small style="color: #c6dcff; display:block; margin-bottom:8px;">Mapel Aktif:</small>
            
            <form action="../../controllers/GuruController.php" method="GET">
                <select name="pilih_mapel" onchange="this.form.submit()" 
                        style="width: 100%; padding: 8px; border-radius: 6px; border: none; background: #f1f5f9; color: #1e293b; font-weight: 600; font-size: 14px; cursor: pointer;">
                    
                    <?php if(empty($daftar_mapel)): ?>
                        <option value="">- Belum ada Mapel -</option>
                    <?php else: ?>
                        <?php foreach($daftar_mapel as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= ($m['id'] == $active_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['nama_mapel']) ?> (<?= htmlspecialchars($m['kelas']) ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                
                </select>
            </form>
        </div> 

        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link">🏠 Dashboard</a>
            <a href="input_soal.php" class="nav-link">✍️ Input Soal Manual</a>
            <a href="import_soal.php" class="nav-link">📊 Import Excel</a>
            <a href="kelola_soal.php" class="nav-link active">📚 Kelola Soal</a>
            <a href="nilai.php" class="nav-link">📈 Hasil Ujian</a>
        </nav>
        <a href="../../controllers/AuthController.php?logout=1" class="btn-logout">🚪 Keluar Sistem</a>
    </div>

        <div class="main-content" id="main-content">
        <div class="top-bar">
            <div class="toggle-btn" id="menuBtn">☰</div>
            <h2>Bank Soal: <?= htmlspecialchars($nama_mapel_aktif) ?></h2>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="40%">Pertanyaan</th>
                        <th width="10%">Kelas</th>
                        <th width="10%">Kunci</th>
                        <th width="15%">Gambar</th>
                        <th width="20%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($questions) > 0): ?>
                        <?php foreach($questions as $index => $q): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars(substr($q['pertanyaan'], 0, 100)) ?>...</td>
                            <td><span class="badge"><?= htmlspecialchars($q['kelas']) ?></span></td>
                            <td style="text-align:center; font-weight:bold;"><?= $q['kunci_jawaban'] ?></td>
                            <td><?= $q['img_pertanyaan'] ? '✅ Ada' : '-' ?></td>
                            <td>
                                <button class="btn-action btn-edit" onclick="openEditModal(<?= htmlspecialchars(json_encode($q), ENT_QUOTES, 'UTF-8') ?>)">✏️ Edit</button>
                                <a href="../../controllers/GuruController.php?action=hapus_soal&id=<?= $q['id'] ?>" 
                                   class="btn-action btn-del" 
                                   onclick="return confirm('Yakin ingin menghapus soal ini?')">🗑️ Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;">Belum ada soal. Silakan Input atau Import.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Edit Soal</h3>
            <form action="../../controllers/GuruController.php?action=update_soal" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_soal" id="edit_id">
                
                <div class="form-group">
                    <label>Pertanyaan</label>
                    <textarea name="pertanyaan" id="edit_pertanyaan" rows="3" required></textarea>
                    
                    <div style="margin-top: 8px; margin-bottom: 8px;">
                        <small style="display:block; color:#64748b;">Gambar saat ini:</small>
                        <img id="preview_img_pertanyaan" src="" alt="Gambar Pertanyaan" style="max-width: 200px; max-height: 150px; display: none; border: 1px solid #ddd; border-radius: 5px; margin-top: 5px;">
                    </div>
                    
                    <small>Ganti Gambar Soal (Opsional):</small>
                    <input type="file" name="img_pertanyaan" accept="image/*">
                </div>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label>Pilihan A</label>
                        <textarea name="pil_a" id="edit_pil_a" rows="2"></textarea>
                        <img id="preview_img_a" src="" alt="Gambar A" style="max-width: 100px; max-height: 80px; display: none; border: 1px solid #ddd; border-radius: 4px; margin: 5px 0;">
                        <input type="file" name="img_a" accept="image/*" style="font-size:0.8rem">
                    </div>
                    
                    <div class="form-group">
                        <label>Pilihan B</label>
                        <textarea name="pil_b" id="edit_pil_b" rows="2"></textarea>
                        <img id="preview_img_b" src="" alt="Gambar B" style="max-width: 100px; max-height: 80px; display: none; border: 1px solid #ddd; border-radius: 4px; margin: 5px 0;">
                        <input type="file" name="img_b" accept="image/*" style="font-size:0.8rem">
                    </div>
                    
                    <div class="form-group">
                        <label>Pilihan C</label>
                        <textarea name="pil_c" id="edit_pil_c" rows="2"></textarea>
                        <img id="preview_img_c" src="" alt="Gambar C" style="max-width: 100px; max-height: 80px; display: none; border: 1px solid #ddd; border-radius: 4px; margin: 5px 0;">
                        <input type="file" name="img_c" accept="image/*" style="font-size:0.8rem">
                    </div>
                    
                    <div class="form-group">
                        <label>Pilihan D</label>
                        <textarea name="pil_d" id="edit_pil_d" rows="2"></textarea>
                        <img id="preview_img_d" src="" alt="Gambar D" style="max-width: 100px; max-height: 80px; display: none; border: 1px solid #ddd; border-radius: 4px; margin: 5px 0;">
                        <input type="file" name="img_d" accept="image/*" style="font-size:0.8rem">
                    </div>
                    
                    <div class="form-group">
                        <label>Pilihan E</label>
                        <textarea name="pil_e" id="edit_pil_e" rows="2"></textarea>
                        <img id="preview_img_e" src="" alt="Gambar E" style="max-width: 100px; max-height: 80px; display: none; border: 1px solid #ddd; border-radius: 4px; margin: 5px 0;">
                        <input type="file" name="img_e" accept="image/*" style="font-size:0.8rem">
                    </div>
                    
                    <div class="form-group">
                        <label>Kunci Jawaban</label>
                        <select name="kunci_jawaban" id="edit_kunci" required>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                        </select>
                    </div>
                </div>

                <button type="submit" style="background:var(--primary, #2563eb); color:white; padding:10px 20px; border:none; border-radius:5px; width:100%; cursor:pointer; font-weight:bold;">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById("editModal");

        function openEditModal(data) {
            // Isi data teks ke form modal
            if(document.getElementById('edit_id')) document.getElementById('edit_id').value = data.id;
            if(document.getElementById('edit_kelas')) document.getElementById('edit_kelas').value = data.kelas;
            if(document.getElementById('edit_pertanyaan')) document.getElementById('edit_pertanyaan').value = data.pertanyaan;
            if(document.getElementById('edit_pil_a')) document.getElementById('edit_pil_a').value = data.pil_a;
            if(document.getElementById('edit_pil_b')) document.getElementById('edit_pil_b').value = data.pil_b;
            if(document.getElementById('edit_pil_c')) document.getElementById('edit_pil_c').value = data.pil_c;
            if(document.getElementById('edit_pil_d')) document.getElementById('edit_pil_d').value = data.pil_d;
            if(document.getElementById('edit_pil_e')) document.getElementById('edit_pil_e').value = data.pil_e;
            if(document.getElementById('edit_kunci')) document.getElementById('edit_kunci').value = data.kunci_jawaban;
            
            // Path folder gambar
            const folderGambar = "../../assets/img/soal/";
            const listGambar = ['img_pertanyaan', 'img_a', 'img_b', 'img_c', 'img_d', 'img_e'];
            
            listGambar.forEach(field => {
                const imgElement = document.getElementById('preview_' + field);
                
                // PROTEKSI: Cek apakah elemen img ada di HTML, jika ada baru diproses
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
            
            // PROTEKSI: Pastikan elemen modal ditemukan baru ditampilkan
            const modalElement = document.getElementById("editModal");
            if (modalElement) {
                modalElement.style.display = "block";
            } else {
                console.error("Elemen dengan ID 'editModal' tidak ditemukan di HTML.");
            }
        }

        function closeModal() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }

        const menuBtn = document.getElementById('menuBtn');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        menuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        });
    </script>
</body>
</html>