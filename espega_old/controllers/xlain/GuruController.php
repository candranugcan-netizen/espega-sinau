<?php
require_once __DIR__ . '/../../config/database.php'; 
require_once __DIR__ . '/../../vendor/SimpleXLSX.php'; 
require_once __DIR__ . '/../../vendor/SimpleXLSXGen.php';
require_once __DIR__ . '/../../config/security.php';

use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLSXGen;
session_start();

// Cek sesi login
if (!isset($_SESSION['user_id'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'error'    => 'session_expired',
            'message'  => 'Sesi login Anda telah habis. Silakan login kembali.',
            'redirect' => '/espega2/index.php?pesan=sesi_habis'
        ]);
        exit;
    }
    session_unset();
    session_destroy();
    header("Location: /espega2/index.php?pesan=sesi_habis");
    exit;
}

// Cek role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guru') {
    header("Location: /espega2/index.php?error=" . urlencode("Akses ditolak. Anda tidak memiliki hak akses."));
    exit;
}

$action = $_GET['action'] ?? '';
$guru_id = $_SESSION['user_id'];

// --- PERUBAHAN MULAI DI SINI ---
// 2. Ambil SEMUA Mapel milik guru ini (PERBAIKAN: Pakai fetchAll agar $daftar_mapel terdefinisi)
$stmt_mapel = $pdo->prepare("SELECT * FROM subjects WHERE guru_id = ?");
$stmt_mapel->execute([$guru_id]);
// PERBAIKAN: Gunakan FETCH_ASSOC agar array key ('nama_mapel', dll) terbaca jelas
$daftar_mapel = $stmt_mapel->fetchAll(PDO::FETCH_ASSOC);

// Ambil ID default jika belum ada sesi (ambil dari mapel pertama yang ditemukan)
$subject_id = $_SESSION['active_subject_id'] ?? ($daftar_mapel[0]['id'] ?? null);

// 3. Logika Ganti Mapel (Jika Dropdown dipilih)
if (isset($_GET['pilih_mapel'])) {
    $id_dipilih = $_GET['pilih_mapel'];
    
    // Validasi: Pastikan ID mapel benar milik guru ini
    $valid = false;
    foreach ($daftar_mapel as $m) {
        if ($m['id'] == $id_dipilih) {
            $_SESSION['active_subject_id'] = $m['id'];
            $_SESSION['active_subject_name'] = $m['nama_mapel'];
            $_SESSION['active_subject_kelas'] = $m['kelas'];
            $valid = true;
            break;
        }
    }

    // Redirect kembali ke halaman asal (Dashboard/Kelola Soal)
    // Hapus parameter ?pilih_mapel=... agar URL bersih
    $referer = $_SERVER['HTTP_REFERER'] ?? '../views/guru/dashboard.php';
    $url_parts = parse_url($referer);
    $path = $url_parts['path'] ?? '../views/guru/dashboard.php';
    
    header("Location: " . $path . "?status=success&msg=Mapel aktif diubah");
    exit;
}

// 4. Inisialisasi Default (Jika belum ada session mapel)
if ((!isset($_SESSION['active_subject_id']) || empty($_SESSION['active_subject_id'])) && count($daftar_mapel) > 0) {
    $_SESSION['active_subject_id'] = $daftar_mapel[0]['id'];
    $_SESSION['active_subject_name'] = $daftar_mapel[0]['nama_mapel'];
    $_SESSION['active_subject_kelas'] = $daftar_mapel[0]['kelas'];
}

// 5. TETAPKAN GLOBAL VARIABLE $subject_id
// Variabel inilah yang digunakan oleh fungsi CRUD di bawah (add_soal, import, dll)
$subject_id = $_SESSION['active_subject_id'] ?? null;

// PENTING: Jangan ada lagi query "SELECT ... LIMIT 1" di bawah baris ini
// yang menimpa variabel $subject_id!

// Jika guru tidak punya mapel sama sekali
if (!$subject_id && $action !== 'logout') {
    // Jangan die(), biarkan script jalan tapi $subject_id null, 
    // nanti dashboard yang menangani tampilannya.
}

// --- PERUBAHAN SELESAI ---

function uploadImage($fileKey) {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $uploadDir = __DIR__ . '/../../assets/img/soal/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileExtension = pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION);
    $newFileName = uniqid('img_') . '.' . $fileExtension;
    $targetFile = $uploadDir . $newFileName;

    if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetFile)) {
        return $newFileName;
    }
    return null;
}

switch ($action) {
    case 'simpan_soal':
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $subject_id) {
            $kelas_pilihan = $_POST['kelas'];
            $pertanyaan    = $_POST['pertanyaan'] ?? '';
            $pil_a         = $_POST['pil_a'] ?? '';
            $pil_b         = $_POST['pil_b'] ?? '';
            $pil_c         = $_POST['pil_c'] ?? '';
            $pil_d         = $_POST['pil_d'] ?? '';
            $pil_e         = $_POST['pil_e'] ?? '';
            $kunci         = $_POST['kunci_jawaban'];

            // Proses Upload Gambar
            $img_pertanyaan = uploadImage('img_pertanyaan');
            $img_a          = uploadImage('img_a');
            $img_b          = uploadImage('img_b');
            $img_c          = uploadImage('img_c');
            $img_d          = uploadImage('img_d');
            $img_e          = uploadImage('img_e');

            try {
                $sql = "INSERT INTO questions (subject_id, kelas, pertanyaan, img_pertanyaan, pil_a, img_a, pil_b, img_b, pil_c, img_c, pil_d, img_d, pil_e, img_e, kunci_jawaban) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $subject_id, $kelas_pilihan, $pertanyaan, $img_pertanyaan, 
                    $pil_a, $img_a, $pil_b, $img_b, $pil_c, $img_c, 
                    $pil_d, $img_d, $pil_e, $img_e, $kunci
                ]);

                header("Location: ../views/guru/input_soal.php?status=success&msg=Soal berhasil disimpan.");
                exit;
            } catch (PDOException $e) {
                header("Location: ../views/guru/input_soal.php?status=error&msg=Gagal menyimpan: " . $e->getMessage());
            }
        }
        break;

    case 'import_excel':
        if (isset($_FILES['file_excel']) && $subject_id) {
            $kelas_pilihan = $_POST['kelas']; // Ambil kelas dari form import
            if ($xlsx = SimpleXLSX::parse($_FILES['file_excel']['tmp_name'])) {
                $rows = $xlsx->rows();
                $count = 0;

                $sql = "INSERT INTO questions (subject_id, kelas, pertanyaan, pil_a, pil_b, pil_c, pil_d, pil_e, kunci_jawaban) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);

                foreach ($rows as $index => $row) {
                    if ($index == 0) continue; // Lewati header

                    $pertanyaan = trim($row[0] ?? '');
                    $kunci      = strtoupper(trim($row[6] ?? ''));

                    if (!empty($pertanyaan) && !empty($kunci)) {
                        $stmt->execute([
                            $subject_id, 
                            $kelas_pilihan,
                            $pertanyaan, 
                            trim($row[1] ?? ''), 
                            trim($row[2] ?? ''), 
                            trim($row[3] ?? ''), 
                            trim($row[4] ?? ''), 
                            trim($row[5] ?? ''), 
                            $kunci
                        ]);
                        $count++;
                    }
                }
                header("Location: ../views/guru/import_soal.php?status=success&msg=$count Soal berhasil diimport ke kelas $kelas_pilihan.");
            } else {
                header("Location: ../views/guru/import_soal.php?status=error&msg=Gagal membaca file Excel.");
            }
        } else {
            header("Location: ../views/guru/import_soal.php?status=error&msg=Pilih file dan kelas terlebih dahulu.");
        }
        break;

        case 'hapus_soal':
        if (isset($_GET['id']) && $subject_id) {
            $id_soal = $_GET['id'];
            try {
                // Hapus hanya jika soal milik subject guru yang sedang login (Mencegah IDOR)
                $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ? AND subject_id = ?");
                $stmt->execute([$id_soal, $subject_id]);
                
                header("Location: ../views/guru/kelola_soal.php?status=success&msg=Soal berhasil dihapus.");
            } catch (PDOException $e) {
                header("Location: ../views/guru/kelola_soal.php?status=error&msg=Gagal menghapus: " . $e->getMessage());
            }
        }
        break;

    case 'update_soal':
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $subject_id) {
            $id_soal    = $_POST['id_soal'];
            $pertanyaan = $_POST['pertanyaan'];
            $pil_a      = $_POST['pil_a'];
            $pil_b      = $_POST['pil_b'];
            $pil_c      = $_POST['pil_c'];
            $pil_d      = $_POST['pil_d'];
            $pil_e      = $_POST['pil_e'];
            $kunci      = $_POST['kunci_jawaban'];

            // 🔑 PERBAIKAN: Kolom 'kelas=?' dihapus dari string query agar mempertahankan kelas yang lama
            $updates = "pertanyaan=?, pil_a=?, pil_b=?, pil_c=?, pil_d=?, pil_e=?, kunci_jawaban=?";
            $params = [$pertanyaan, $pil_a, $pil_b, $pil_c, $pil_d, $pil_e, $kunci];

            // Helper array untuk cek gambar
            $img_fields = ['img_pertanyaan', 'img_a', 'img_b', 'img_c', 'img_d', 'img_e'];
            
            foreach ($img_fields as $field) {
                if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                    $new_img = uploadImage($field);
                    if ($new_img) {
                        $updates .= ", $field = ?";
                        $params[] = $new_img;
                    }
                }
            }

            $sql = "UPDATE questions SET $updates WHERE id = ? AND subject_id = ?";
            $params[] = $id_soal;
            $params[] = $subject_id;

            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                header("Location: ../views/guru/kelola_soal.php?status=success&msg=Soal berhasil diperbarui.");
                exit;
            } catch (PDOException $e) {
                header("Location: ../views/guru/kelola_soal.php?status=error&msg=Gagal update: " . $e->getMessage());
                exit;
            }
        }
        break;
    case 'download_template':
        $header = [['Pertanyaan', 'Pilihan A', 'Pilihan B', 'Pilihan C', 'Pilihan D', 'Pilihan E', 'Kunci (A/B/C/D/E)']];
        $xlsx = SimpleXLSXGen::fromArray($header);
        $xlsx->downloadAs('Template_Soal_CBT.xlsx');
        break;
        
        
        
    case 'download_nilai':
        if ($subject_id) {
            // Ambil data nilai berdasarkan mapel aktif ($subject_id)
            $sql = "SELECT u.nama_lengkap, u.username, u.kelas, e.nama_ujian, es.skor, es.status_siswa 
                    FROM exam_sessions es 
                    JOIN users u ON es.user_id = u.id
                    JOIN exams e ON es.exam_id = e.id
                    JOIN subjects s ON e.subject_id = s.id
                    WHERE s.id = ? 
                    ORDER BY u.kelas ASC, u.nama_lengkap ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$subject_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Siapkan Header Excel
            $excelData = [
                ['No', 'Nama Siswa', 'NIS/Username', 'Kelas', 'Nama Ujian', 'Skor Akhir', 'Status']
            ];

            // Masukkan Data ke Array
            $no = 1;
            foreach ($results as $row) {
                $excelData[] = [
                    $no++,
                    $row['nama_lengkap'],
                    $row['username'],
                    $row['kelas'],
                    $row['nama_ujian'],
                    $row['skor'],
                    $row['status_siswa']
                ];
            }

            // Nama file dinamis
            $nama_file = 'Rekap_Nilai_' . date('Y-m-d_H-i') . '.xlsx';

            // Generate & Download
            $xlsx = SimpleXLSXGen::fromArray($excelData);
            $xlsx->downloadAs($nama_file);
            exit;
        } else {
             header("Location: ../views/guru/nilai.php?status=error&msg=Mapel tidak ditemukan.");
        }
    break;
}