<?php
require_once __DIR__ . '/../config/database.php'; 
require_once __DIR__ . '/../vendor/SimpleXLSX.php'; 
require_once __DIR__ . '/../vendor/SimpleXLSXGen.php';
require_once __DIR__ . '/BankSoalExporter.php';
require_once __DIR__ . '/../config/security.php';

use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLSXGen;
session_start();

// Cek sesi login — jika habis, redirect ke login dengan peringatan
if (!isset($_SESSION['user_id'])) {
    // Ini adalah request AJAX (JSON) atau biasa?
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

// Cek role — sesi ada tapi bukan guru/admin
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['guru', 'admin'])) {
    header("Location: /espega2/index.php?error=" . urlencode("Akses ditolak. Anda tidak memiliki hak akses ke halaman ini."));
    exit;
}

$action = $_GET['action'] ?? '';
$guru_id = $_SESSION['user_id'];

// --- PERUBAHAN MULAI DI SINI ---
// 2. Ambil SEMUA Mapel milik guru ini
$stmt_mapel = $pdo->prepare("SELECT * FROM subjects WHERE guru_id = ? ORDER BY nama_mapel ASC");
$stmt_mapel->execute([$guru_id]);
$daftar_mapel = $stmt_mapel->fetchAll(PDO::FETCH_ASSOC);

// 3. Ambil SEMUA Bank Soal milik guru ini
$stmt_banks = $pdo->prepare("
    SELECT bs.*, s.nama_mapel, s.kelas as kelas_mapel,
           (SELECT COUNT(*) FROM questions WHERE bank_soal_id = bs.id) as total_soal
    FROM bank_soal bs
    JOIN subjects s ON bs.subject_id = s.id
    WHERE bs.guru_id = ?
    ORDER BY bs.id DESC
");
$stmt_banks->execute([$guru_id]);
$daftar_bank_soal = $stmt_banks->fetchAll(PDO::FETCH_ASSOC);

// 4. Logika Ganti Bank Soal Aktif (jika dipilih melalui dropdown / URL)
if (isset($_GET['pilih_bank_soal'])) {
    $id_bank_dipilih = (int)$_GET['pilih_bank_soal'];
    foreach ($daftar_bank_soal as $b) {
        if ($b['id'] == $id_bank_dipilih) {
            $_SESSION['active_bank_soal_id']   = $b['id'];
            $_SESSION['active_bank_soal_name'] = $b['nama_bank'];
            $_SESSION['active_subject_id']     = $b['subject_id'];
            $_SESSION['active_subject_name']   = $b['nama_mapel'];
            $_SESSION['active_subject_kelas']  = $b['kelas_mapel'];
            break;
        }
    }
    $referer = $_SERVER['HTTP_REFERER'] ?? '../views/guru/kelola_soal.php';
    header("Location: " . $referer);
    exit;
}

// 5. Logika Ganti Mapel (Jika Dropdown mapel dipilih)
if (isset($_GET['pilih_mapel'])) {
    $id_dipilih = $_GET['pilih_mapel'];
    foreach ($daftar_mapel as $m) {
        if ($m['id'] == $id_dipilih) {
            $_SESSION['active_subject_id'] = $m['id'];
            $_SESSION['active_subject_name'] = $m['nama_mapel'];
            $_SESSION['active_subject_kelas'] = $m['kelas'];
            
            // Cari bank soal pertama pada mapel ini jika ada
            foreach ($daftar_bank_soal as $b) {
                if ($b['subject_id'] == $m['id']) {
                    $_SESSION['active_bank_soal_id'] = $b['id'];
                    $_SESSION['active_bank_soal_name'] = $b['nama_bank'];
                    break;
                }
            }
            break;
        }
    }

    $referer = $_SERVER['HTTP_REFERER'] ?? '../views/guru/dashboard.php';
    header("Location: " . $referer);
    exit;
}

// 6. Inisialisasi Default Mapel & Bank Soal jika belum ada
if (!isset($_SESSION['active_subject_id']) && count($daftar_mapel) > 0) {
    $_SESSION['active_subject_id'] = $daftar_mapel[0]['id'];
    $_SESSION['active_subject_name'] = $daftar_mapel[0]['nama_mapel'];
    $_SESSION['active_subject_kelas'] = $daftar_mapel[0]['kelas'];
}

if (!isset($_SESSION['active_bank_soal_id']) && count($daftar_bank_soal) > 0) {
    $_SESSION['active_bank_soal_id'] = $daftar_bank_soal[0]['id'];
    $_SESSION['active_bank_soal_name'] = $daftar_bank_soal[0]['nama_bank'];
}

$subject_id   = $_SESSION['active_subject_id'] ?? null;
$bank_soal_id = $_SESSION['active_bank_soal_id'] ?? null;

function uploadImage($fileKey) {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $uploadDir = __DIR__ . '/../assets/img/soal/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $tmpFile = $_FILES[$fileKey]['tmp_name'];
    $originalName = $_FILES[$fileKey]['name'];
    $maxWidth = 1200;  // Lebar maksimal gambar (px)
    $webpQuality = 80; // Kualitas WebP (0-100)

    // Cek apakah GD tersedia dan mendukung WebP
    $gdAvailable = extension_loaded('gd') && function_exists('imagecreatefromstring');
    $webpSupported = $gdAvailable && function_exists('imagewebp');

    if ($gdAvailable) {
        // Baca data gambar mentah
        $imageData = file_get_contents($tmpFile);
        if ($imageData === false) {
            return null;
        }

        // Deteksi tipe gambar
        $imageInfo = @getimagesizefromstring($imageData);
        if ($imageInfo === false) {
            // Bukan file gambar valid, simpan apa adanya sebagai fallback
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowedRaw = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
            if (!in_array($ext, $allowedRaw)) {
                return null; // Ekstensi tidak diizinkan
            }
            $newFileName = uniqid('img_') . '.' . $ext;
            if (move_uploaded_file($tmpFile, $uploadDir . $newFileName)) {
                return $newFileName;
            }
            return null;
        }

        $mimeType = $imageInfo['mime'];
        $origWidth = $imageInfo[0];
        $origHeight = $imageInfo[1];

        // Buat GD resource dari data gambar
        $srcImage = @imagecreatefromstring($imageData);
        if (!$srcImage) {
            // Gagal membuat GD resource, simpan file asli
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $newFileName = uniqid('img_') . '.' . $ext;
            if (move_uploaded_file($tmpFile, $uploadDir . $newFileName)) {
                return $newFileName;
            }
            return null;
        }

        // Pertahankan transparansi untuk PNG/GIF/WebP
        imagealphablending($srcImage, true);
        imagesavealpha($srcImage, true);

        // Resize jika terlalu lebar
        $finalImage = $srcImage;
        if ($origWidth > $maxWidth && $origHeight > 0) {
            $ratio = $maxWidth / $origWidth;
            $newWidth = $maxWidth;
            $newHeight = (int)round($origHeight * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            if ($resized) {
                // Pertahankan transparansi pada gambar hasil resize
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
                imagefill($resized, 0, 0, $transparent);

                imagecopyresampled($resized, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
                imagedestroy($srcImage);
                $finalImage = $resized;
            }
        }

        // Simpan sebagai WebP jika didukung
        if ($webpSupported) {
            $newFileName = uniqid('img_') . '.webp';
            $targetFile = $uploadDir . $newFileName;

            if (@imagewebp($finalImage, $targetFile, $webpQuality)) {
                imagedestroy($finalImage);
                // Verifikasi ukuran file (kadang imagewebp menghasilkan file 0 byte)
                if (filesize($targetFile) > 0) {
                    return $newFileName;
                }
                // Jika gagal, hapus dan fallback ke bawah
                @unlink($targetFile);
            }
        }

        // Fallback: simpan sebagai JPEG terkompresi jika WebP gagal
        $newFileName = uniqid('img_') . '.jpg';
        $targetFile = $uploadDir . $newFileName;

        // Buat background putih untuk gambar transparan sebelum simpan JPEG
        $jpgImage = imagecreatetruecolor(imagesx($finalImage), imagesy($finalImage));
        $white = imagecolorallocate($jpgImage, 255, 255, 255);
        imagefill($jpgImage, 0, 0, $white);
        imagecopy($jpgImage, $finalImage, 0, 0, 0, 0, imagesx($finalImage), imagesy($finalImage));
        imagedestroy($finalImage);

        if (@imagejpeg($jpgImage, $targetFile, $webpQuality)) {
            imagedestroy($jpgImage);
            return $newFileName;
        }
        imagedestroy($jpgImage);
        return null;

    } else {
        // GD tidak tersedia — simpan file asli tanpa kompresi
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        if (!in_array($ext, $allowedExt)) {
            return null;
        }
        $newFileName = uniqid('img_') . '.' . $ext;
        $targetFile = $uploadDir . $newFileName;

        if (move_uploaded_file($tmpFile, $targetFile)) {
            return $newFileName;
        }
        return null;
    }
}

switch ($action) {
    // ==========================================
    // ACTION: MANAJEMEN BANK SOAL
    // ==========================================
    case 'tambah_bank_soal':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nama_bank = trim($_POST['nama_bank'] ?? '');
            $subj_id   = (int)($_POST['subject_id'] ?? $subject_id);

            if (empty($nama_bank) || empty($subj_id)) {
                header("Location: ../views/guru/bank_soal.php?status=error&msg=" . urlencode("Nama bank soal dan mata pelajaran wajib diisi."));
                exit;
            }

            try {
                // Verifikasi subject milik guru ini
                $stmtCek = $pdo->prepare("SELECT * FROM subjects WHERE id = ? AND guru_id = ?");
                $stmtCek->execute([$subj_id, $guru_id]);
                $subjData = $stmtCek->fetch(PDO::FETCH_ASSOC);

                if (!$subjData) {
                    header("Location: ../views/guru/bank_soal.php?status=error&msg=" . urlencode("Mata pelajaran tidak valid."));
                    exit;
                }

                $stmt = $pdo->prepare("INSERT INTO bank_soal (nama_bank, subject_id, guru_id, kelas_rilis, status_rilis) VALUES (?, ?, ?, NULL, 'draft')");
                $stmt->execute([$nama_bank, $subj_id, $guru_id]);
                $new_bank_id = $pdo->lastInsertId();

                // Set sebagai bank soal aktif
                $_SESSION['active_bank_soal_id']   = $new_bank_id;
                $_SESSION['active_bank_soal_name'] = $nama_bank;
                $_SESSION['active_subject_id']     = $subj_id;
                $_SESSION['active_subject_name']   = $subjData['nama_mapel'];
                $_SESSION['active_subject_kelas']  = $subjData['kelas'];

                header("Location: ../views/guru/kelola_soal.php?bank_id=$new_bank_id&status=success&msg=" . urlencode("Bank Soal '$nama_bank' berhasil dibuat! Silakan input butir soal di bawah."));
            } catch (PDOException $e) {
                header("Location: ../views/guru/bank_soal.php?status=error&msg=" . urlencode("Gagal membuat bank soal: " . $e->getMessage()));
            }
        }
        break;

    case 'edit_bank_soal':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $bank_id   = (int)($_POST['bank_id'] ?? 0);
            $nama_bank = trim($_POST['nama_bank'] ?? '');
            $subj_id   = (int)($_POST['subject_id'] ?? 0);

            if (empty($nama_bank) || !$bank_id || !$subj_id) {
                header("Location: ../views/guru/bank_soal.php?status=error&msg=" . urlencode("Data tidak lengkap."));
                exit;
            }

            try {
                // Pastikan bank soal dan subject milik guru ini
                $stmt = $pdo->prepare("UPDATE bank_soal SET nama_bank = ?, subject_id = ? WHERE id = ? AND guru_id = ?");
                $stmt->execute([$nama_bank, $subj_id, $bank_id, $guru_id]);

                // Update juga subject_id di questions
                $pdo->prepare("UPDATE questions SET subject_id = ? WHERE bank_soal_id = ?")->execute([$subj_id, $bank_id]);

                if (isset($_SESSION['active_bank_soal_id']) && $_SESSION['active_bank_soal_id'] == $bank_id) {
                    $_SESSION['active_bank_soal_name'] = $nama_bank;
                    $_SESSION['active_subject_id'] = $subj_id;
                }

                header("Location: ../views/guru/bank_soal.php?status=success&msg=" . urlencode("Bank Soal '$nama_bank' berhasil diperbarui."));
            } catch (PDOException $e) {
                header("Location: ../views/guru/bank_soal.php?status=error&msg=" . urlencode("Gagal memperbarui bank soal: " . $e->getMessage()));
            }
        }
        break;

    case 'hapus_bank_soal':
        $bank_id = (int)($_GET['id'] ?? 0);
        if ($bank_id) {
            try {
                // Ambil info nama
                $stmtName = $pdo->prepare("SELECT nama_bank FROM bank_soal WHERE id = ? AND guru_id = ?");
                $stmtName->execute([$bank_id, $guru_id]);
                $nama = $stmtName->fetchColumn() ?: 'Bank Soal';

                // Hapus soal di dalamnya
                $stmtDelQ = $pdo->prepare("DELETE FROM questions WHERE bank_soal_id = ?");
                $stmtDelQ->execute([$bank_id]);

                // Hapus bank soal
                $stmtDel = $pdo->prepare("DELETE FROM bank_soal WHERE id = ? AND guru_id = ?");
                $stmtDel->execute([$bank_id, $guru_id]);

                if (isset($_SESSION['active_bank_soal_id']) && $_SESSION['active_bank_soal_id'] == $bank_id) {
                    unset($_SESSION['active_bank_soal_id'], $_SESSION['active_bank_soal_name']);
                }

                header("Location: ../views/guru/bank_soal.php?status=success&msg=" . urlencode("'$nama' dan seluruh butir soalnya berhasil dihapus."));
            } catch (PDOException $e) {
                header("Location: ../views/guru/bank_soal.php?status=error&msg=" . urlencode("Gagal menghapus: " . $e->getMessage()));
            }
        }
        break;

    case 'rilis_bank_soal':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $bank_id = (int)($_POST['bank_id'] ?? 0);
            $redirect_to = $_POST['redirect_to'] ?? 'bank_soal'; // 'bank_soal' atau 'kelola_soal'

            $kelas_raw = $_POST['target_kelas'] ?? [];
            if (is_array($kelas_raw)) {
                $target_kelas_str = implode(', ', array_filter(array_map('trim', $kelas_raw)));
            } else {
                $target_kelas_str = trim($kelas_raw);
            }

            try {
                // Cek kepemilikan bank soal
                $stmtB = $pdo->prepare("SELECT nama_bank FROM bank_soal WHERE id = ? AND guru_id = ?");
                $stmtB->execute([$bank_id, $guru_id]);
                $namaBank = $stmtB->fetchColumn();

                if (!$namaBank) {
                    header("Location: ../views/guru/bank_soal.php?status=error&msg=" . urlencode("Bank Soal tidak ditemukan."));
                    exit;
                }

                if (!empty($target_kelas_str)) {
                    // Rilis ke kelas terpilih
                    $stmtUpdB = $pdo->prepare("UPDATE bank_soal SET kelas_rilis = ?, status_rilis = 'rilis' WHERE id = ? AND guru_id = ?");
                    $stmtUpdB->execute([$target_kelas_str, $bank_id, $guru_id]);

                    // Update seluruh butir soal di bank ini
                    $stmtUpdQ = $pdo->prepare("UPDATE questions SET kelas = ? WHERE bank_soal_id = ?");
                    $stmtUpdQ->execute([$target_kelas_str, $bank_id]);

                    $msg = "'$namaBank' berhasil dirilis ke kelas: $target_kelas_str. Siswa di kelas tersebut dapat mengakses soal saat ujian berlangsung.";
                } else {
                    // Ubah status ke Draft
                    $stmtUpdB = $pdo->prepare("UPDATE bank_soal SET kelas_rilis = NULL, status_rilis = 'draft' WHERE id = ? AND guru_id = ?");
                    $stmtUpdB->execute([$bank_id, $guru_id]);

                    $stmtUpdQ = $pdo->prepare("UPDATE questions SET kelas = NULL WHERE bank_soal_id = ?");
                    $stmtUpdQ->execute([$bank_id]);

                    $msg = "'$namaBank' disimpan sebagai status Draft (belum dirilis ke siswa).";
                }

                $targetUrl = ($redirect_to === 'kelola_soal') 
                    ? "../views/guru/kelola_soal.php?bank_id=$bank_id&status=success&msg=" . urlencode($msg)
                    : "../views/guru/bank_soal.php?status=success&msg=" . urlencode($msg);

                header("Location: " . $targetUrl);
            } catch (PDOException $e) {
                header("Location: ../views/guru/bank_soal.php?status=error&msg=" . urlencode("Gagal merilis bank soal: " . $e->getMessage()));
            }
        }
        break;

    // ==========================================
    // ACTION: MANAJEMEN BUTIR SOAL
    // ==========================================
    case 'simpan_soal':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $target_bank_id = (int)($_POST['bank_soal_id'] ?? ($bank_soal_id ?? 0));
            
            // Ambil info bank soal & subject
            $stmtBank = $pdo->prepare("SELECT * FROM bank_soal WHERE id = ? AND guru_id = ?");
            $stmtBank->execute([$target_bank_id, $guru_id]);
            $bankData = $stmtBank->fetch(PDO::FETCH_ASSOC);

            if (!$bankData) {
                header("Location: ../views/guru/input_soal.php?status=error&msg=" . urlencode("Bank Soal tidak valid. Silakan pilih atau buat bank soal terlebih dahulu."));
                exit;
            }

            $current_subject_id = $bankData['subject_id'];
            $current_kelas      = $bankData['kelas_rilis'];

            $pertanyaan = $_POST['pertanyaan'] ?? '';
            $pil_a      = $_POST['pil_a'] ?? '';
            $pil_b      = $_POST['pil_b'] ?? '';
            $pil_c      = $_POST['pil_c'] ?? '';
            $pil_d      = $_POST['pil_d'] ?? '';
            $pil_e      = $_POST['pil_e'] ?? '';
            $kunci      = $_POST['kunci_jawaban'] ?? 'A';

            // Proses Upload Gambar
            $img_pertanyaan = uploadImage('img_pertanyaan');
            $img_a          = uploadImage('img_a');
            $img_b          = uploadImage('img_b');
            $img_c          = uploadImage('img_c');
            $img_d          = uploadImage('img_d');
            $img_e          = uploadImage('img_e');

            try {
                $sql = "INSERT INTO questions (bank_soal_id, subject_id, kelas, pertanyaan, img_pertanyaan, pil_a, img_a, pil_b, img_b, pil_c, img_c, pil_d, img_d, pil_e, img_e, kunci_jawaban) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $target_bank_id, $current_subject_id, $current_kelas, $pertanyaan, $img_pertanyaan, 
                    $pil_a, $img_a, $pil_b, $img_b, $pil_c, $img_c, 
                    $pil_d, $img_d, $pil_e, $img_e, $kunci
                ]);

                $_SESSION['active_bank_soal_id'] = $target_bank_id;
                header("Location: ../views/guru/kelola_soal.php?bank_id=$target_bank_id&status=success&msg=" . urlencode("Butir soal berhasil ditambahkan ke " . $bankData['nama_bank']));
            } catch (PDOException $e) {
                header("Location: ../views/guru/input_soal.php?bank_id=$target_bank_id&status=error&msg=" . urlencode("Gagal menyimpan: " . $e->getMessage()));
            }
        }
        break;

    case 'import_excel':
        if (isset($_FILES['file_excel'])) {
            $target_bank_id = (int)($_POST['bank_soal_id'] ?? ($bank_soal_id ?? 0));
            
            // Ambil info bank soal & subject
            $stmtBank = $pdo->prepare("SELECT * FROM bank_soal WHERE id = ? AND guru_id = ?");
            $stmtBank->execute([$target_bank_id, $guru_id]);
            $bankData = $stmtBank->fetch(PDO::FETCH_ASSOC);

            if (!$bankData) {
                header("Location: ../views/guru/import_soal.php?status=error&msg=" . urlencode("Pilih Bank Soal sasaran yang valid."));
                exit;
            }

            $current_subject_id = $bankData['subject_id'];
            $current_kelas      = $bankData['kelas_rilis'];

            if ($xlsx = SimpleXLSX::parse($_FILES['file_excel']['tmp_name'])) {
                $rows = $xlsx->rows();
                $count = 0;

                $sql = "INSERT INTO questions (bank_soal_id, subject_id, kelas, pertanyaan, pil_a, pil_b, pil_c, pil_d, pil_e, kunci_jawaban) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                
                $pdo->beginTransaction();

                foreach ($rows as $index => $row) {
                    if ($index == 0) continue; // Lewati header

                    $pertanyaan = trim($row[0] ?? '');
                    $kunci      = strtoupper(trim($row[6] ?? ''));

                    if (!empty($pertanyaan) && !empty($kunci)) {
                        $stmt->execute([
                            $target_bank_id,
                            $current_subject_id, 
                            $current_kelas,
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
                
                $pdo->commit();
                $_SESSION['active_bank_soal_id'] = $target_bank_id;
                header("Location: ../views/guru/kelola_soal.php?bank_id=$target_bank_id&status=success&msg=" . urlencode("$count Soal berhasil diimport ke " . $bankData['nama_bank'] . ". Silakan tambahkan gambar jika butir soal memiliki ilustrasi/gambar."));
            } else {
                header("Location: ../views/guru/import_soal.php?bank_id=$target_bank_id&status=error&msg=" . urlencode("Gagal membaca file Excel."));
            }
        } else {
            header("Location: ../views/guru/import_soal.php?status=error&msg=" . urlencode("Pilih file Excel terlebih dahulu."));
        }
        break;

    case 'hapus_soal':
        if (isset($_GET['id'])) {
            $id_soal = (int)$_GET['id'];
            $bank_id = (int)($_GET['bank_id'] ?? ($bank_soal_id ?? 0));
            try {
                // Ambil info gambar untuk dibersihkan & verifikasi kepemilikan
                $stmtFind = $pdo->prepare("
                    SELECT q.id, q.img_pertanyaan, q.img_a, q.img_b, q.img_c, q.img_d, q.img_e 
                    FROM questions q
                    LEFT JOIN bank_soal bs ON q.bank_soal_id = bs.id
                    JOIN subjects s ON q.subject_id = s.id
                    WHERE q.id = ? AND (bs.guru_id = ? OR s.guru_id = ?)
                ");
                $stmtFind->execute([$id_soal, $guru_id, $guru_id]);
                $qItem = $stmtFind->fetch(PDO::FETCH_ASSOC);

                if ($qItem) {
                    $imgDir = __DIR__ . '/../assets/img/soal/';
                    foreach (['img_pertanyaan', 'img_a', 'img_b', 'img_c', 'img_d', 'img_e'] as $field) {
                        if (!empty($qItem[$field])) {
                            $filePath = $imgDir . $qItem[$field];
                            if (file_exists($filePath)) {
                                @unlink($filePath);
                            }
                        }
                    }

                    $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
                    $stmt->execute([$id_soal]);

                    header("Location: ../views/guru/kelola_soal.php?bank_id=$bank_id&status=success&msg=" . urlencode("Butir soal berhasil dihapus."));
                    exit;
                } else {
                    header("Location: ../views/guru/kelola_soal.php?bank_id=$bank_id&status=error&msg=" . urlencode("Soal tidak ditemukan atau Anda tidak memiliki akses."));
                    exit;
                }
            } catch (PDOException $e) {
                header("Location: ../views/guru/kelola_soal.php?bank_id=$bank_id&status=error&msg=" . urlencode("Gagal menghapus: " . $e->getMessage()));
                exit;
            }
        }
        break;

    case 'hapus_banyak_soal':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $bank_id = (int)($_POST['bank_id'] ?? ($bank_soal_id ?? 0));
            $ids = $_POST['ids'] ?? ($_POST['selected_soal'] ?? []);

            if (is_string($ids)) {
                $ids = explode(',', $ids);
            }

            $ids = array_filter(array_map('intval', (array)$ids));

            if (empty($ids)) {
                header("Location: ../views/guru/kelola_soal.php?bank_id=$bank_id&status=error&msg=" . urlencode("Tidak ada butir soal yang dipilih untuk dihapus."));
                exit;
            }

            try {
                // Ambil daftar gambar & verifikasi kepemilikan soal oleh guru ini
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmtFind = $pdo->prepare("
                    SELECT q.id, q.img_pertanyaan, q.img_a, q.img_b, q.img_c, q.img_d, q.img_e 
                    FROM questions q
                    LEFT JOIN bank_soal bs ON q.bank_soal_id = bs.id
                    JOIN subjects s ON q.subject_id = s.id
                    WHERE q.id IN ($placeholders) AND (bs.guru_id = ? OR s.guru_id = ?)
                ");
                $params = array_merge($ids, [$guru_id, $guru_id]);
                $stmtFind->execute($params);
                $questionsToDelete = $stmtFind->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($questionsToDelete)) {
                    $validIds = array_column($questionsToDelete, 'id');
                    $imgDir = __DIR__ . '/../assets/img/soal/';

                    // Bersihkan file gambar jika ada
                    foreach ($questionsToDelete as $qItem) {
                        foreach (['img_pertanyaan', 'img_a', 'img_b', 'img_c', 'img_d', 'img_e'] as $field) {
                            if (!empty($qItem[$field])) {
                                $filePath = $imgDir . $qItem[$field];
                                if (file_exists($filePath)) {
                                    @unlink($filePath);
                                }
                            }
                        }
                    }

                    // Hapus dari database
                    $validPlaceholders = implode(',', array_fill(0, count($validIds), '?'));
                    $stmtDel = $pdo->prepare("DELETE FROM questions WHERE id IN ($validPlaceholders)");
                    $stmtDel->execute($validIds);

                    $deletedCount = count($validIds);
                    header("Location: ../views/guru/kelola_soal.php?bank_id=$bank_id&status=success&msg=" . urlencode("$deletedCount butir soal terpilih berhasil dihapus."));
                    exit;
                } else {
                    header("Location: ../views/guru/kelola_soal.php?bank_id=$bank_id&status=error&msg=" . urlencode("Soal tidak ditemukan atau Anda tidak memiliki akses untuk menghapusnya."));
                    exit;
                }
            } catch (PDOException $e) {
                header("Location: ../views/guru/kelola_soal.php?bank_id=$bank_id&status=error&msg=" . urlencode("Gagal menghapus soal: " . $e->getMessage()));
                exit;
            }
        }
        break;

    case 'acak_urutan_soal':
        $bank_id = (int)($_POST['bank_id'] ?? ($_GET['bank_id'] ?? 0));
        $redirect_to = $_POST['redirect_to'] ?? ($_GET['redirect_to'] ?? 'kelola_soal');

        if (!$bank_id) {
            header("Location: ../views/guru/bank_soal.php?status=error&msg=" . urlencode("Bank Soal tidak valid."));
            exit;
        }

        try {
            $stmtCek = $pdo->prepare("SELECT id, nama_bank FROM bank_soal WHERE id = ? AND guru_id = ?");
            $stmtCek->execute([$bank_id, $guru_id]);
            $bank = $stmtCek->fetch(PDO::FETCH_ASSOC);

            if (!$bank) {
                header("Location: ../views/guru/bank_soal.php?status=error&msg=" . urlencode("Bank Soal tidak ditemukan atau Anda tidak berwenang."));
                exit;
            }

            $stmtQ = $pdo->prepare("SELECT id FROM questions WHERE bank_soal_id = ? ORDER BY id ASC");
            $stmtQ->execute([$bank_id]);
            $ids = $stmtQ->fetchAll(PDO::FETCH_COLUMN);

            if (empty($ids)) {
                $targetUrl = ($redirect_to === 'bank_soal') 
                    ? "../views/guru/bank_soal.php?status=error&msg=" . urlencode("Tidak ada butir soal di paket ini untuk diacak.")
                    : "../views/guru/kelola_soal.php?bank_id=$bank_id&status=error&msg=" . urlencode("Tidak ada butir soal di paket ini untuk diacak.");
                header("Location: $targetUrl");
                exit;
            }

            shuffle($ids);

            $pdo->beginTransaction();
            $upd = $pdo->prepare("UPDATE questions SET urutan = ? WHERE id = ?");
            $orderNum = 1;
            foreach ($ids as $qid) {
                $upd->execute([$orderNum, $qid]);
                $orderNum++;
            }
            $pdo->commit();

            $count = count($ids);
            $msg = "Urutan $count butir soal pada '{$bank['nama_bank']}' berhasil diacak!";
            $targetUrl = ($redirect_to === 'bank_soal')
                ? "../views/guru/bank_soal.php?status=success&msg=" . urlencode($msg)
                : "../views/guru/kelola_soal.php?bank_id=$bank_id&status=success&msg=" . urlencode($msg);

            header("Location: $targetUrl");
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            header("Location: ../views/guru/kelola_soal.php?bank_id=$bank_id&status=error&msg=" . urlencode("Gagal mengacak soal: " . $e->getMessage()));
            exit;
        }
        break;

    case 'reset_urutan_soal':
        $bank_id = (int)($_POST['bank_id'] ?? ($_GET['bank_id'] ?? 0));
        $redirect_to = $_POST['redirect_to'] ?? ($_GET['redirect_to'] ?? 'kelola_soal');

        if (!$bank_id) {
            header("Location: ../views/guru/bank_soal.php?status=error&msg=" . urlencode("Bank Soal tidak valid."));
            exit;
        }

        try {
            $stmtCek = $pdo->prepare("SELECT id, nama_bank FROM bank_soal WHERE id = ? AND guru_id = ?");
            $stmtCek->execute([$bank_id, $guru_id]);
            $bank = $stmtCek->fetch(PDO::FETCH_ASSOC);

            if (!$bank) {
                header("Location: ../views/guru/bank_soal.php?status=error&msg=" . urlencode("Bank Soal tidak ditemukan atau Anda tidak berwenang."));
                exit;
            }

            $stmt = $pdo->prepare("UPDATE questions SET urutan = 0 WHERE bank_soal_id = ?");
            $stmt->execute([$bank_id]);

            $msg = "Urutan butir soal pada '{$bank['nama_bank']}' berhasil dikembalikan ke urutan default (berdasarkan nomor ID).";
            $targetUrl = ($redirect_to === 'bank_soal')
                ? "../views/guru/bank_soal.php?status=success&msg=" . urlencode($msg)
                : "../views/guru/kelola_soal.php?bank_id=$bank_id&status=success&msg=" . urlencode($msg);

            header("Location: $targetUrl");
            exit;
        } catch (Exception $e) {
            header("Location: ../views/guru/kelola_soal.php?bank_id=$bank_id&status=error&msg=" . urlencode("Gagal mereset urutan: " . $e->getMessage()));
            exit;
        }
        break;

    case 'bulk_acak_soal':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $bank_id = (int)($_POST['bank_id'] ?? 0);
            $ids = $_POST['ids'] ?? [];
            if (is_string($ids)) $ids = explode(',', $ids);
            $ids = array_filter(array_map('intval', (array)$ids));

            if (empty($ids) || count($ids) < 2) {
                header("Location: ../views/guru/kelola_soal.php?bank_id=$bank_id&status=error&msg=" . urlencode("Pilih minimal 2 butir soal untuk diacak!"));
                exit;
            }

            try {
                $stmtCek = $pdo->prepare("SELECT id FROM bank_soal WHERE id = ? AND guru_id = ?");
                $stmtCek->execute([$bank_id, $guru_id]);
                if (!$stmtCek->fetch()) {
                    header("Location: ../views/guru/kelola_soal.php?bank_id=$bank_id&status=error&msg=" . urlencode("Akses ditolak."));
                    exit;
                }

                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("SELECT id, urutan FROM questions WHERE id IN ($placeholders) AND bank_soal_id = ? ORDER BY (CASE WHEN urutan > 0 THEN urutan ELSE 9999999 END) ASC, id ASC");
                $params = array_merge($ids, [$bank_id]);
                $stmt->execute($params);
                $currentRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($currentRows) !== count($ids)) {
                    header("Location: ../views/guru/kelola_soal.php?bank_id=$bank_id&status=error&msg=" . urlencode("Beberapa butir soal tidak valid."));
                    exit;
                }

                $slots = [];
                $hasZero = false;
                foreach ($currentRows as $row) {
                    if ($row['urutan'] > 0) $slots[] = (int)$row['urutan'];
                    else $hasZero = true;
                }

                if ($hasZero || count($slots) !== count($ids)) {
                    $slots = range(1, count($ids));
                } else {
                    sort($slots);
                }

                $shuffledIds = array_column($currentRows, 'id');
                shuffle($shuffledIds);

                $pdo->beginTransaction();
                $upd = $pdo->prepare("UPDATE questions SET urutan = ? WHERE id = ?");
                for ($i = 0; $i < count($shuffledIds); $i++) {
                    $upd->execute([$slots[$i], $shuffledIds[$i]]);
                }
                $pdo->commit();

                $count = count($shuffledIds);
                header("Location: ../views/guru/kelola_soal.php?bank_id=$bank_id&status=success&msg=" . urlencode("Urutan $count butir soal terpilih berhasil diacak."));
                exit;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                header("Location: ../views/guru/kelola_soal.php?bank_id=$bank_id&status=error&msg=" . urlencode("Gagal mengacak soal: " . $e->getMessage()));
                exit;
            }
        }
        break;

    case 'update_soal':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_soal    = (int)$_POST['id_soal'];
            $bank_id    = (int)($_POST['bank_id'] ?? ($bank_soal_id ?? 0));
            $pertanyaan = $_POST['pertanyaan'];
            $pil_a      = $_POST['pil_a'];
            $pil_b      = $_POST['pil_b'];
            $pil_c      = $_POST['pil_c'];
            $pil_d      = $_POST['pil_d'];
            $pil_e      = $_POST['pil_e'];
            $kunci      = $_POST['kunci_jawaban'];

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

            $sql = "UPDATE questions q 
                    JOIN subjects s ON q.subject_id = s.id 
                    SET $updates 
                    WHERE q.id = ? AND s.guru_id = ?";
            $params[] = $id_soal;
            $params[] = $guru_id;

            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                header("Location: ../views/guru/kelola_soal.php?bank_id=$bank_id&status=success&msg=" . urlencode("Butir soal berhasil diperbarui."));
            } catch (PDOException $e) {
                header("Location: ../views/guru/kelola_soal.php?bank_id=$bank_id&status=error&msg=" . urlencode("Gagal update: " . $e->getMessage()));
            }
        }
        break;

    case 'rilis_soal':
        // Legacy support
        header("Location: ../views/guru/bank_soal.php");
        break;

    case 'download_template':
        $header = [['Pertanyaan', 'Pilihan A', 'Pilihan B', 'Pilihan C', 'Pilihan D', 'Pilihan E', 'Kunci (A/B/C/D/E)']];
        $xlsx = SimpleXLSXGen::fromArray($header);
        $xlsx->downloadAs('Template_Soal_CBT.xlsx');
        break;

    case 'download_bank_soal':
    case 'download_bank_excel':
    case 'download_bank_pdf':
        $target_bank_id = (int)($_GET['bank_id'] ?? ($bank_soal_id ?? 0));
        $format = strtolower(trim($_GET['format'] ?? ''));
        if ($action === 'download_bank_excel') $format = 'excel';
        if ($action === 'download_bank_pdf') $format = 'pdf';
        if (empty($format)) $format = 'pdf';

        $withKey = isset($_GET['kunci']) ? (bool)$_GET['kunci'] : true;
        $userRole = $_SESSION['role'] ?? 'guru';

        if ($format === 'excel') {
            BankSoalExporter::exportExcel($pdo, $target_bank_id, $guru_id, $userRole);
        } else {
            BankSoalExporter::exportPdf($pdo, $target_bank_id, $guru_id, $userRole, $withKey);
        }
        break;
        
        
        
    case 'download_nilai':
        $target_subject_id = $_GET['subject_id'] ?? $subject_id;
        $target_kelas = trim($_GET['kelas'] ?? '');

        if ($target_subject_id) {
            // 1. Ambil Informasi Mapel
            $stmt_mapel_info = $pdo->prepare("SELECT nama_mapel, kelas FROM subjects WHERE id = ?");
            $stmt_mapel_info->execute([$target_subject_id]);
            $mapel_info = $stmt_mapel_info->fetch(PDO::FETCH_ASSOC);
            $nama_mapel = $mapel_info['nama_mapel'] ?? 'Mata Pelajaran';
            $kelas_mapel = $mapel_info['kelas'] ?? '';

            // 2. Ambil Semua Soal yang Terkait dengan Mapel ini
            $stmt_q = $pdo->prepare("SELECT id, pertanyaan, kunci_jawaban, kelas FROM questions WHERE subject_id = ? ORDER BY id ASC");
            $stmt_q->execute([$target_subject_id]);
            $all_questions = $stmt_q->fetchAll(PDO::FETCH_ASSOC);

            // Grouping Soal Berdasarkan Kelas (Normalisasi huruf besar & trim)
            $questions_by_class = [];
            foreach ($all_questions as $q) {
                $k = strtoupper(trim($q['kelas'] ?? ''));
                if ($k === '') {
                    $k = 'UMUM';
                }
                $questions_by_class[$k][] = $q;
            }

            // 3. Ambil Data Sesi & Nilai Siswa
            $sql = "SELECT es.id AS session_id, es.user_id, es.exam_id, es.skor, es.status_siswa, es.selesai_ujian,
                           u.nama_lengkap, u.username, u.kelas, e.nama_ujian, e.target_kelas 
                    FROM exam_sessions es 
                    JOIN users u ON es.user_id = u.id
                    JOIN exams e ON es.exam_id = e.id
                    JOIN subjects s ON e.subject_id = s.id
                    WHERE s.id = ?";
            $params = [$target_subject_id];

            if ($target_kelas !== '') {
                $sql .= " AND (u.kelas = ? OR (u.kelas IS NULL AND e.target_kelas = ?))";
                $params[] = $target_kelas;
                $params[] = $target_kelas;
            }

            $sql .= " ORDER BY u.kelas ASC, u.nama_lengkap ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 4. Ambil Jawaban Siswa dari Tabel exam_answers
            $session_ids = array_column($results, 'session_id');
            $answers_map = [];
            if (!empty($session_ids)) {
                $placeholders = implode(',', array_fill(0, count($session_ids), '?'));
                $stmt_ans = $pdo->prepare("SELECT session_id, question_id, jawaban_siswa, is_correct FROM exam_answers WHERE session_id IN ($placeholders)");
                $stmt_ans->execute($session_ids);
                $raw_answers = $stmt_ans->fetchAll(PDO::FETCH_ASSOC);
                foreach ($raw_answers as $ans) {
                    $answers_map[$ans['session_id']][$ans['question_id']] = $ans;
                }
            }

            // Helper untuk mengambil daftar soal untuk suatu kelas tertentu
            $get_class_questions = function($kls) use (&$questions_by_class, &$all_questions) {
                $kls = strtoupper(trim($kls));
                if (isset($questions_by_class[$kls]) && !empty($questions_by_class[$kls])) {
                    return $questions_by_class[$kls];
                }
                if (isset($questions_by_class['UMUM']) && !empty($questions_by_class['UMUM'])) {
                    return $questions_by_class['UMUM'];
                }
                // Jika tidak ada pembagian kelas pada soal, gunakan semua soal
                return $all_questions;
            };

            // 5. Kalkulasi Jawaban, Benar, Salah, Kosong, dan Skor untuk Setiap Siswa
            // PENTING: Dihitung HANYA berdasarkan bank soal kelas siswa tersebut!
            $calc_detail = [];
            $calc_benar = [];
            $calc_salah = [];
            $calc_kosong = [];
            $calc_total_soal = [];
            $calc_skor = [];

            // Statistik per kelas dan per soal: [KELAS => [qid => count]]
            $stat_benar_by_class = [];
            $stat_salah_by_class = [];
            $stat_kosong_by_class = [];

            foreach ($results as $row) {
                $sid = $row['session_id'];
                $s_kelas = strtoupper(trim($row['kelas'] ?? ($row['target_kelas'] ?? '')));
                $s_questions = $get_class_questions($s_kelas);
                $total_soal_siswa = count($s_questions);

                $jml_b = 0;
                $jml_s = 0;
                $jml_k = 0;

                foreach ($s_questions as $q) {
                    $qid = $q['id'];
                    $kunci = strtoupper(trim($q['kunci_jawaban'] ?? ''));

                    $has_ans = isset($answers_map[$sid][$qid]);
                    $jwb_raw = $has_ans ? trim($answers_map[$sid][$qid]['jawaban_siswa']) : '';
                    $jwb = strtoupper($jwb_raw);

                    if ($jwb !== '' && $jwb !== '-') {
                        if ($jwb === $kunci) {
                            $jml_b++;
                            $st = 'Benar';
                            $stat_benar_by_class[$s_kelas][$qid] = ($stat_benar_by_class[$s_kelas][$qid] ?? 0) + 1;
                        } else {
                            $jml_s++;
                            $st = 'Salah';
                            $stat_salah_by_class[$s_kelas][$qid] = ($stat_salah_by_class[$s_kelas][$qid] ?? 0) + 1;
                        }
                    } else {
                        $jwb = '-';
                        $jml_k++;
                        $st = 'Kosong';
                        $stat_kosong_by_class[$s_kelas][$qid] = ($stat_kosong_by_class[$s_kelas][$qid] ?? 0) + 1;
                    }

                    $calc_detail[$sid][$qid] = [
                        'jawaban' => $jwb,
                        'status' => $st
                    ];
                }

                $calc_benar[$sid] = $jml_b;
                $calc_salah[$sid] = $jml_s;
                $calc_kosong[$sid] = $jml_k;
                $calc_total_soal[$sid] = $total_soal_siswa;

                if ($row['status_siswa'] === 'selesai' && isset($row['skor']) && is_numeric($row['skor']) && $row['skor'] > 0) {
                    $calc_skor[$sid] = round((float)$row['skor'], 1);
                } else {
                    $calc_skor[$sid] = ($total_soal_siswa > 0) ? round(($jml_b / $total_soal_siswa) * 100, 1) : 0;
                }
            }

            // Helper function untuk membangun sheet tabel per kelas
            $build_class_sheet = function($nama_sheet_title, $kelas_name, $students_in_class, $class_questions) 
                use (&$calc_detail, &$calc_benar, &$calc_salah, &$calc_kosong, &$calc_skor, 
                     &$stat_benar_by_class, &$stat_salah_by_class, &$stat_kosong_by_class, $nama_mapel) {
                
                $sheet = [];
                $sheet[] = ['<b><style font-size="14">' . $nama_sheet_title . '</style></b>'];
                $sheet[] = [
                    '<b>Mata Pelajaran:</b> ' . htmlspecialchars($nama_mapel) . 
                    ' | <b>Kelas:</b> ' . htmlspecialchars($kelas_name) . 
                    ' | <b>Tanggal Export:</b> ' . date('d-m-Y H:i') . 
                    ' | <b>Total Siswa:</b> ' . count($students_in_class) . 
                    ' | <b>Total Soal:</b> ' . count($class_questions)
                ];
                $sheet[] = []; // Baris pemisah

                $header = [
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>No</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>NIS / Username</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Nama Lengkap Siswa</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Kelas</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Nama Ujian</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Jml Soal</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Benar</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Salah</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Kosong</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Nilai Akhir</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Status</center></b></style>'
                ];
                foreach ($class_questions as $idx => $q) {
                    $no_s = $idx + 1;
                    $kunci = strtoupper(trim($q['kunci_jawaban'] ?? '-'));
                    $header[] = '<style bgcolor="#334155" color="#ffffff"><b><center>Soal ' . $no_s . '<br>[Kunci: ' . $kunci . ']</center></b></style>';
                }
                $sheet[] = $header;

                if (!empty($class_questions)) {
                    // Baris Kunci Jawaban
                    $row_kunci = [
                        '<style bgcolor="#fef08a" color="#854d0e"><center><b>-</b></center></style>',
                        '<style bgcolor="#fef08a" color="#854d0e"><center><b>-</b></center></style>',
                        '<style bgcolor="#fef08a" color="#854d0e"><b>KUNCI JAWABAN RESMI</b></style>',
                        '<style bgcolor="#fef08a" color="#854d0e"><center><b>' . htmlspecialchars($kelas_name) . '</b></center></style>',
                        '<style bgcolor="#fef08a" color="#854d0e"><center><b>-</b></center></style>',
                        '<style bgcolor="#fef08a" color="#854d0e"><center><b>' . count($class_questions) . '</b></center></style>',
                        '<style bgcolor="#fef08a" color="#854d0e"><center><b>-</b></center></style>',
                        '<style bgcolor="#fef08a" color="#854d0e"><center><b>-</b></center></style>',
                        '<style bgcolor="#fef08a" color="#854d0e"><center><b>-</b></center></style>',
                        '<style bgcolor="#fef08a" color="#854d0e"><center><b>-</b></center></style>',
                        '<style bgcolor="#fef08a" color="#854d0e"><center><b>-</b></center></style>'
                    ];
                    foreach ($class_questions as $q) {
                        $kunci = strtoupper(trim($q['kunci_jawaban'] ?? '-'));
                        $row_kunci[] = '<style bgcolor="#fef08a" color="#854d0e"><b><center>Kunci: ' . $kunci . '</center></b></style>';
                    }
                    $sheet[] = $row_kunci;
                }

                // Data Siswa
                $no = 1;
                foreach ($students_in_class as $row) {
                    $sid = $row['session_id'];
                    $status_label = ($row['status_siswa'] === 'selesai') ? 'Selesai' : (($row['status_siswa'] === 'terblokir') ? 'Terblokir' : 'Pengerjaan');
                    $status_color = ($row['status_siswa'] === 'selesai') ? '#15803d' : '#b45309';
                    $status_bg = ($row['status_siswa'] === 'selesai') ? '#dcfce7' : '#fef3c7';

                    $row_data = [
                        '<center>' . ($no++) . '</center>',
                        '<center>' . htmlspecialchars($row['username']) . '</center>',
                        htmlspecialchars($row['nama_lengkap']),
                        '<center>' . htmlspecialchars($row['kelas']) . '</center>',
                        htmlspecialchars($row['nama_ujian']),
                        '<center>' . count($class_questions) . '</center>',
                        '<style color="#15803d"><b><center>' . ($calc_benar[$sid] ?? 0) . '</center></b></style>',
                        '<style color="#b91c1c"><b><center>' . ($calc_salah[$sid] ?? 0) . '</center></b></style>',
                        '<style color="#64748b"><center>' . ($calc_kosong[$sid] ?? 0) . '</center></style>',
                        '<style bgcolor="#e0e7ff" color="#4338ca"><b><center>' . ($calc_skor[$sid] ?? 0) . '</center></b></style>',
                        '<style bgcolor="' . $status_bg . '" color="' . $status_color . '"><b><center>' . $status_label . '</center></b></style>'
                    ];

                    foreach ($class_questions as $q) {
                        $qid = $q['id'];
                        $detail = $calc_detail[$sid][$qid] ?? ['jawaban' => '-', 'status' => 'Kosong'];
                        $jwb = $detail['jawaban'];
                        $st = $detail['status'];

                        if ($st === 'Benar') {
                            $row_data[] = '<style bgcolor="#dcfce7" color="#166534"><b><center>' . $jwb . ' (Benar)</center></b></style>';
                        } elseif ($st === 'Salah') {
                            $row_data[] = '<style bgcolor="#fee2e2" color="#991b1b"><b><center>' . $jwb . ' (Salah)</center></b></style>';
                        } else {
                            $row_data[] = '<style bgcolor="#f1f5f9" color="#64748b"><center>- (Kosong)</center></style>';
                        }
                    }
                    $sheet[] = $row_data;
                }

                // Statistik Bawah Sheet Kelas (Tingkat Kesukaran & Ketuntasan)
                $n_siswa = count($students_in_class);
                $k_key = strtoupper(trim($kelas_name));
                if ($n_siswa > 0 && !empty($class_questions)) {
                    $sheet[] = []; // spasi pemisah

                    $row_b = ['', '', '<b><style bgcolor="#e2e8f0" color="#1e293b">TOTAL SISWA JAWAB BENAR (B)</style></b>', '', '', '', '', '', '', '', ''];
                    foreach ($class_questions as $q) {
                        $qid = $q['id'];
                        $b_val = $stat_benar_by_class[$k_key][$qid] ?? 0;
                        $row_b[] = '<style bgcolor="#dcfce7" color="#166534"><b><center>' . $b_val . '</center></b></style>';
                    }
                    $sheet[] = $row_b;

                    $row_s = ['', '', '<b><style bgcolor="#e2e8f0" color="#1e293b">TOTAL SISWA JAWAB SALAH / KOSONG (S)</style></b>', '', '', '', '', '', '', '', ''];
                    foreach ($class_questions as $q) {
                        $qid = $q['id'];
                        $s_val = ($stat_salah_by_class[$k_key][$qid] ?? 0) + ($stat_kosong_by_class[$k_key][$qid] ?? 0);
                        $row_s[] = '<style bgcolor="#fee2e2" color="#991b1b"><b><center>' . $s_val . '</center></b></style>';
                    }
                    $sheet[] = $row_s;

                    $row_p = ['', '', '<b><style bgcolor="#e2e8f0" color="#1e293b">PERSENTASE KETUNTASAN SOAL (%)</style></b>', '', '', '', '', '', '', '', ''];
                    foreach ($class_questions as $q) {
                        $qid = $q['id'];
                        $b_val = $stat_benar_by_class[$k_key][$qid] ?? 0;
                        $pct = round(($b_val / $n_siswa) * 100, 1);
                        $row_p[] = '<style bgcolor="#e0e7ff" color="#3730a3"><b><center>' . $pct . '%</center></b></style>';
                    }
                    $sheet[] = $row_p;

                    $row_diff = ['', '', '<b><style bgcolor="#e2e8f0" color="#1e293b">INDEKS KESUKARAN (P = B / N)</style></b>', '', '', '', '', '', '', '', ''];
                    foreach ($class_questions as $q) {
                        $qid = $q['id'];
                        $b_val = $stat_benar_by_class[$k_key][$qid] ?? 0;
                        $p_val = round($b_val / $n_siswa, 2);
                        $row_diff[] = '<style bgcolor="#f1f5f9" color="#1e293b"><b><center>' . number_format($p_val, 2) . '</center></b></style>';
                    }
                    $sheet[] = $row_diff;

                    $row_kat = ['', '', '<b><style bgcolor="#e2e8f0" color="#1e293b">KATEGORI TINGKAT KESUKARAN</style></b>', '', '', '', '', '', '', '', ''];
                    foreach ($class_questions as $q) {
                        $qid = $q['id'];
                        $b_val = $stat_benar_by_class[$k_key][$qid] ?? 0;
                        $p_val = $b_val / $n_siswa;
                        if ($p_val >= 0.70) {
                            $kat = 'Mudah'; $bg = '#dcfce7'; $col = '#15803d';
                        } elseif ($p_val >= 0.30) {
                            $kat = 'Sedang'; $bg = '#fef9c3'; $col = '#854d0e';
                        } else {
                            $kat = 'Sukar'; $bg = '#fee2e2'; $col = '#b91c1c';
                        }
                        $row_kat[] = '<style bgcolor="' . $bg . '" color="' . $col . '"><b><center>' . $kat . '</center></b></style>';
                    }
                    $sheet[] = $row_kat;
                }

                return $sheet;
            };

            $xlsx = new SimpleXLSXGen();

            if ($target_kelas !== '') {
                // ==========================================
                // KASUS 1: Download Khusus 1 Kelas Tertentu
                // ==========================================
                $filtered_students = $results;
                $c_questions = $get_class_questions($target_kelas);

                // Sheet 1: Rekap dan Koreksi Jawaban Kelas Tersebut
                $s1 = $build_class_sheet(
                    'REKAPITULASI HASIL UJIAN & ANALISIS JAWABAN - KELAS ' . htmlspecialchars($target_kelas),
                    $target_kelas,
                    $filtered_students,
                    $c_questions
                );
                $xlsx->addSheet($s1, 'Rekap dan Koreksi');

                // Sheet 2: Matriks Jawaban Siswa (Huruf A-B-C-D-E)
                $sheet2 = [];
                $sheet2[] = ['<b><style font-size="13">MATRIKS JAWABAN SISWA (PILIHAN GANDA) - KELAS ' . htmlspecialchars($target_kelas) . '</style></b>'];
                $sheet2[] = ['Mata Pelajaran: ' . htmlspecialchars($nama_mapel) . ' | Kelas: ' . htmlspecialchars($target_kelas)];
                $sheet2[] = [];
                $h2 = [
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>No</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>NIS</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Nama Siswa</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Kelas</center></b></style>'
                ];
                foreach ($c_questions as $idx => $q) {
                    $h2[] = '<style bgcolor="#334155" color="#ffffff"><b><center>S' . ($idx + 1) . '</center></b></style>';
                }
                $sheet2[] = $h2;

                if (!empty($c_questions)) {
                    $k2 = ['', '', '<style bgcolor="#fef08a" color="#854d0e"><b>KUNCI</b></style>', ''];
                    foreach ($c_questions as $q) {
                        $k2[] = '<style bgcolor="#fef08a" color="#854d0e"><b><center>' . strtoupper(trim($q['kunci_jawaban'] ?? '-')) . '</center></b></style>';
                    }
                    $sheet2[] = $k2;
                }

                $no2 = 1;
                foreach ($filtered_students as $row) {
                    $sid = $row['session_id'];
                    $r2 = [
                        '<center>' . ($no2++) . '</center>',
                        '<center>' . htmlspecialchars($row['username']) . '</center>',
                        htmlspecialchars($row['nama_lengkap']),
                        '<center>' . htmlspecialchars($row['kelas']) . '</center>'
                    ];
                    foreach ($c_questions as $q) {
                        $qid = $q['id'];
                        $jwb = $calc_detail[$sid][$qid]['jawaban'] ?? '-';
                        $st = $calc_detail[$sid][$qid]['status'] ?? 'Kosong';
                        if ($st === 'Benar') {
                            $r2[] = '<style color="#15803d"><b><center>' . $jwb . '</center></b></style>';
                        } else {
                            $r2[] = '<style color="#b91c1c"><center>' . $jwb . '</center></style>';
                        }
                    }
                    $sheet2[] = $r2;
                }
                if (!empty($c_questions)) {
                    $xlsx->addSheet($sheet2, 'Jawaban Siswa');
                }

                // Sheet 3: Analisis Butir Soal (Matriks 1-0)
                $sheet3 = [];
                $sheet3[] = ['<b><style font-size="13">ANALISIS BUTIR SOAL & TINGKAT KESUKARAN (SKOR 1 / 0) - KELAS ' . htmlspecialchars($target_kelas) . '</style></b>'];
                $sheet3[] = ['Mata Pelajaran: ' . htmlspecialchars($nama_mapel) . ' | Kelas: ' . htmlspecialchars($target_kelas)];
                $sheet3[] = [];
                $h3 = [
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>No</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>NIS</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Nama Siswa</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Kelas</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Total Benar</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Total Salah</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Nilai</center></b></style>'
                ];
                foreach ($c_questions as $idx => $q) {
                    $h3[] = '<style bgcolor="#334155" color="#ffffff"><b><center>S' . ($idx + 1) . '</center></b></style>';
                }
                $sheet3[] = $h3;

                $no3 = 1;
                foreach ($filtered_students as $row) {
                    $sid = $row['session_id'];
                    $r3 = [
                        '<center>' . ($no3++) . '</center>',
                        '<center>' . htmlspecialchars($row['username']) . '</center>',
                        htmlspecialchars($row['nama_lengkap']),
                        '<center>' . htmlspecialchars($row['kelas']) . '</center>',
                        '<style color="#15803d"><b><center>' . ($calc_benar[$sid] ?? 0) . '</center></b></style>',
                        '<style color="#b91c1c"><b><center>' . (($calc_salah[$sid] ?? 0) + ($calc_kosong[$sid] ?? 0)) . '</center></b></style>',
                        '<style bgcolor="#e0e7ff" color="#4338ca"><b><center>' . ($calc_skor[$sid] ?? 0) . '</center></b></style>'
                    ];
                    foreach ($c_questions as $q) {
                        $qid = $q['id'];
                        $st = $calc_detail[$sid][$qid]['status'] ?? 'Kosong';
                        if ($st === 'Benar') {
                            $r3[] = '<style bgcolor="#dcfce7" color="#15803d"><b><center>1</center></b></style>';
                        } else {
                            $r3[] = '<style bgcolor="#fee2e2" color="#b91c1c"><center>0</center></style>';
                        }
                    }
                    $sheet3[] = $r3;
                }

                // Tambahkan ringkasan analisis di bagian bawah Sheet 3
                $n3 = count($filtered_students);
                if ($n3 > 0 && !empty($c_questions)) {
                    $sheet3[] = [];
                    $k_key = strtoupper(trim($target_kelas));

                    $r3_b = ['', '', '<b><style bgcolor="#e2e8f0" color="#1e293b">TOTAL BENAR (1)</style></b>', '', '', '', ''];
                    foreach ($c_questions as $q) {
                        $qid = $q['id'];
                        $b_val = $stat_benar_by_class[$k_key][$qid] ?? 0;
                        $r3_b[] = '<style bgcolor="#dcfce7" color="#15803d"><b><center>' . $b_val . '</center></b></style>';
                    }
                    $sheet3[] = $r3_b;

                    $r3_diff = ['', '', '<b><style bgcolor="#e2e8f0" color="#1e293b">INDEKS KESUKARAN (P = B / N)</style></b>', '', '', '', ''];
                    foreach ($c_questions as $q) {
                        $qid = $q['id'];
                        $b_val = $stat_benar_by_class[$k_key][$qid] ?? 0;
                        $p_val = round($b_val / $n3, 2);
                        $r3_diff[] = '<style bgcolor="#f1f5f9" color="#1e293b"><b><center>' . number_format($p_val, 2) . '</center></b></style>';
                    }
                    $sheet3[] = $r3_diff;

                    $r3_kat = ['', '', '<b><style bgcolor="#e2e8f0" color="#1e293b">KATEGORI TINGKAT KESUKARAN</style></b>', '', '', '', ''];
                    foreach ($c_questions as $q) {
                        $qid = $q['id'];
                        $b_val = $stat_benar_by_class[$k_key][$qid] ?? 0;
                        $p_val = $b_val / $n3;
                        if ($p_val >= 0.70) {
                            $kat = 'Mudah'; $bg = '#dcfce7'; $col = '#15803d';
                        } elseif ($p_val >= 0.30) {
                            $kat = 'Sedang'; $bg = '#fef9c3'; $col = '#854d0e';
                        } else {
                            $kat = 'Sukar'; $bg = '#fee2e2'; $col = '#b91c1c';
                        }
                        $r3_kat[] = '<style bgcolor="' . $bg . '" color="' . $col . '"><b><center>' . $kat . '</center></b></style>';
                    }
                    $sheet3[] = $r3_kat;
                }

                if (!empty($c_questions)) {
                    $xlsx->addSheet($sheet3, 'Analisis Butir Soal');
                }

                $clean_title = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nama_mapel . '_' . $target_kelas);
                $nama_file = 'Rekap_Nilai_' . $clean_title . '_' . date('Y-m-d_H-i') . '.xlsx';

            } else {
                // ==========================================
                // KASUS 2: Download Semua Kelas
                // ==========================================
                $distinct_classes = [];
                foreach ($results as $r) {
                    $k = trim($r['kelas'] ?? ($r['target_kelas'] ?? ''));
                    if ($k !== '' && !in_array($k, $distinct_classes)) {
                        $distinct_classes[] = $k;
                    }
                }
                sort($distinct_classes);

                $students_by_class = [];
                foreach ($results as $r) {
                    $k = trim($r['kelas'] ?? ($r['target_kelas'] ?? ''));
                    $students_by_class[$k][] = $r;
                }

                if (count($distinct_classes) <= 1) {
                    // Hanya ada 1 kelas
                    $single_k = $distinct_classes[0] ?? ($kelas_mapel ?: 'Umum');
                    $c_questions = $get_class_questions($single_k);

                    $s1 = $build_class_sheet(
                        'REKAPITULASI HASIL UJIAN & ANALISIS JAWABAN',
                        $single_k,
                        $results,
                        $c_questions
                    );
                    $xlsx->addSheet($s1, 'Rekap dan Koreksi');
                } else {
                    // ADA BANYAK KELAS (Contoh: 7A, 7B, 8A, 8B, 9A, 9B)
                    // SHEET 1: REKAPITULASI SEMUA SISWA
                    // PENTING: Benar, Salah, Total Soal dihitung akurat berdasarkan soal kelas masing-masing!
                    $sheet_rekap_all = [];
                    $sheet_rekap_all[] = ['<b><style font-size="14">REKAPITULASI NILAI HASIL UJIAN (SEMUA KELAS)</style></b>'];
                    $sheet_rekap_all[] = [
                        '<b>Mata Pelajaran:</b> ' . htmlspecialchars($nama_mapel) . 
                        ' | <b>Tanggal Export:</b> ' . date('d-m-Y H:i') . 
                        ' | <b>Total Siswa:</b> ' . count($results) .
                        ' | <b>Total Kelas:</b> ' . count($distinct_classes)
                    ];
                    $sheet_rekap_all[] = [];

                    $h_all = [
                        '<style bgcolor="#1e293b" color="#ffffff"><b><center>No</center></b></style>',
                        '<style bgcolor="#1e293b" color="#ffffff"><b><center>NIS / Username</center></b></style>',
                        '<style bgcolor="#1e293b" color="#ffffff"><b><center>Nama Lengkap Siswa</center></b></style>',
                        '<style bgcolor="#1e293b" color="#ffffff"><b><center>Kelas</center></b></style>',
                        '<style bgcolor="#1e293b" color="#ffffff"><b><center>Nama Ujian</center></b></style>',
                        '<style bgcolor="#1e293b" color="#ffffff"><b><center>Total Soal</center></b></style>',
                        '<style bgcolor="#1e293b" color="#ffffff"><b><center>Benar</center></b></style>',
                        '<style bgcolor="#1e293b" color="#ffffff"><b><center>Salah</center></b></style>',
                        '<style bgcolor="#1e293b" color="#ffffff"><b><center>Kosong</center></b></style>',
                        '<style bgcolor="#1e293b" color="#ffffff"><b><center>Nilai Akhir</center></b></style>',
                        '<style bgcolor="#1e293b" color="#ffffff"><b><center>Status</center></b></style>'
                    ];
                    $sheet_rekap_all[] = $h_all;

                    $no_all = 1;
                    foreach ($results as $row) {
                        $sid = $row['session_id'];
                        $status_label = ($row['status_siswa'] === 'selesai') ? 'Selesai' : (($row['status_siswa'] === 'terblokir') ? 'Terblokir' : 'Pengerjaan');
                        $status_color = ($row['status_siswa'] === 'selesai') ? '#15803d' : '#b45309';
                        $status_bg = ($row['status_siswa'] === 'selesai') ? '#dcfce7' : '#fef3c7';

                        $sheet_rekap_all[] = [
                            '<center>' . ($no_all++) . '</center>',
                            '<center>' . htmlspecialchars($row['username']) . '</center>',
                            htmlspecialchars($row['nama_lengkap']),
                            '<center><b>' . htmlspecialchars($row['kelas']) . '</b></center>',
                            htmlspecialchars($row['nama_ujian']),
                            '<center>' . ($calc_total_soal[$sid] ?? 0) . '</center>',
                            '<style color="#15803d"><b><center>' . ($calc_benar[$sid] ?? 0) . '</center></b></style>',
                            '<style color="#b91c1c"><b><center>' . ($calc_salah[$sid] ?? 0) . '</center></b></style>',
                            '<style color="#64748b"><center>' . ($calc_kosong[$sid] ?? 0) . '</center></style>',
                            '<style bgcolor="#e0e7ff" color="#4338ca"><b><center>' . ($calc_skor[$sid] ?? 0) . '</center></b></style>',
                            '<style bgcolor="' . $status_bg . '" color="' . $status_color . '"><b><center>' . $status_label . '</center></b></style>'
                        ];
                    }
                    $xlsx->addSheet($sheet_rekap_all, 'Rekap Semua Siswa');

                    // SHEET 2, 3, ...: SATU SHEET KHUSUS PER KELAS DENGAN SOAL, JAWABAN & ANALISIS TINGKAT KESUKARAN AKURAT
                    foreach ($distinct_classes as $kls) {
                        $c_students = $students_by_class[$kls] ?? [];
                        $c_questions = $get_class_questions($kls);

                        $class_sheet = $build_class_sheet(
                            'REKAPITULASI HASIL UJIAN & ANALISIS JAWABAN - KELAS ' . htmlspecialchars($kls),
                            $kls,
                            $c_students,
                            $c_questions
                        );
                        $clean_kls = preg_replace('/[\\\\\\/\\?\\*\\:\\[\\]]/', '', $kls);
                        $sheet_name = mb_substr('Kelas ' . $clean_kls, 0, 31);
                        $xlsx->addSheet($class_sheet, $sheet_name);
                    }
                }

                $clean_title = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nama_mapel);
                $nama_file = 'Rekap_Nilai_' . $clean_title . '_Semua_Kelas_' . date('Y-m-d_H-i') . '.xlsx';
            }

            $xlsx->downloadAs($nama_file);
            exit;
        } else {
             header("Location: ../views/guru/nilai.php?status=error&msg=Mapel tidak ditemukan.");
             exit;
        }
    break;

    // --- FITUR DOWNLOAD BANK SOAL (EXCEL & PDF 2 KOLOM KOP SMPN 3 NGAWEN) ---
    case 'download_bank_soal':
    case 'download_bank_excel':
    case 'download_bank_pdf':
        $bank_id = (int)($_GET['bank_id'] ?? 0);
        $format = strtolower($_GET['format'] ?? '');
        if ($action === 'download_bank_excel') $format = 'excel';
        if ($action === 'download_bank_pdf') $format = 'pdf';
        $with_kunci = isset($_GET['with_kunci']) ? (bool)(int)$_GET['with_kunci'] : true;

        if ($bank_id <= 0) {
            die("ID Bank Soal tidak valid.");
        }

        if ($format === 'excel') {
            BankSoalExporter::exportExcel($pdo, $bank_id, $guru_id, $_SESSION['role']);
        } else {
            BankSoalExporter::exportPDF($pdo, $bank_id, $with_kunci, $guru_id, $_SESSION['role']);
        }
        exit;
        break;
}