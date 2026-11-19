<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../vendor/SimpleXLSX.php'; 
require_once __DIR__ . '/../vendor/SimpleXLSXGen.php';
require_once __DIR__ . '/../vendor/fpdf/fpdf.php';
require_once __DIR__ . '/BankSoalExporter.php';

use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLSXGen;
session_start();

/**
 * BAGIAN 1: PROSES LOGIN (Jika form login mengarah ke sini)
 */
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $input_token = $_POST['token'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && $input_token === $user['token']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama'] = $user['nama_lengkap'];
            
            // Set role ke lowercase agar konsisten
            if ($user['role_id'] == 1) $_SESSION['role'] = 'admin';
            elseif ($user['role_id'] == 2) $_SESSION['role'] = 'guru';
            else $_SESSION['role'] = 'siswa';

            header("Location: ../views/" . $_SESSION['role'] . "/dashboard.php");
            exit;
        } else {
            header("Location: ../index.php?error=Kredensial Token Salah!");
            exit;
        }
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}

/**
 * BAGIAN 2: PROTEKSI AKSES (PENTING!)
 * Diubah menjadi strtolower agar 'Admin' atau 'admin' tetap lolos.
 */
$action = $_GET['action'] ?? '';

if ($action != '') {
    // Cek sesi login terlebih dahulu
    if (!isset($_SESSION['user_id'])) {
        // Cek apakah ini request AJAX
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
    // Cek role — sesi ada tapi bukan admin
    if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
        header("Location: /espega2/index.php?error=" . urlencode("Akses ditolak. Anda tidak memiliki otoritas sebagai admin."));
        exit;
    }
}

/**
 * BAGIAN 3: AKSI ADMIN
 */
switch ($action) {
    case 'unblock':
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            try {
                $stmt = $pdo->prepare("UPDATE users SET status_blokir = 0 WHERE id = ?");
                $stmt->execute([$id]);
                
                $referer = $_SERVER['HTTP_REFERER'] ?? '../views/admin/monitoring.php?status=unblocked';
                if (strpos($referer, 'status=') !== false) {
                    $referer = preg_replace('/([?&])status=[^&]+(&|$)/', '$1', $referer);
                    $referer = rtrim($referer, '?&');
                }
                $separator = (strpos($referer, '?') !== false) ? '&' : '?';
                header("Location: " . $referer . $separator . "status=unblocked");
                exit;
            } catch (PDOException $e) {
                die("Gagal membuka blokir: " . $e->getMessage());
            }
        }
        break;

    // --- FITUR IMPORT EXCEL (BARU) ---
    case 'import_excel':
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file_excel'])) {
            if ($xlsx = SimpleXLSX::parse($_FILES['file_excel']['tmp_name'])) {
                $rows = $xlsx->rows();
                // Lewati baris pertama (header)
                for ($i = 1; $i < count($rows); $i++) {
                    $username = $rows[$i][0]; // Kolom A: NIS/Username
                    $nama_lengkap = $rows[$i][1]; // Kolom B: Nama
                    $kelas = $rows[$i][2]; // Kolom C: Kelas
                    
                    if (empty($username)) continue;

                    // Generate token otomatis 6 digit
                    $token = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

                    try {
                        // Role ID 3 = Siswa, Status Blokir 0 = Aktif
                        $stmt = $pdo->prepare("INSERT INTO users (username, nama_lengkap, kelas, token, role_id, status_blokir) 
                                             VALUES (?, ?, ?, ?, 3, '0') 
                                             ON DUPLICATE KEY UPDATE nama_lengkap = ?, kelas = ?");
                        $stmt->execute([$username, $nama_lengkap, $kelas, $token, $nama_lengkap, $kelas]);
                    } catch (PDOException $e) {
                        // Skip jika error
                        continue;
                    }
                }
                header("Location: ../views/admin/siswa.php?status=imported");
            } else {
                header("Location: ../views/admin/siswa.php?error=" . SimpleXLSX::parseError());
            }
            exit;
        }
        break;

    // --- FITUR EXPORT EXCEL (BARU) ---
    case 'export_siswa':
        try {
            $stmt = $pdo->query("SELECT username, nama_lengkap, kelas, token FROM users WHERE role_id = 3 ORDER BY kelas ASC, nama_lengkap ASC");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $excelData = [];
            // Header Excel
            $excelData[] = ['NIS / Username', 'Nama Lengkap', 'Kelas', 'Kosongi Saja'];

            foreach ($data as $row) {
                $excelData[] = [
                    $row['username'],
                    $row['nama_lengkap'],
                    $row['kelas'],
                    $row['token']
                ];
            }

            $xlsx = SimpleXLSXGen::fromArray($excelData);
            $xlsx->downloadAs('Data_Siswa_Espega_' . date('Y-m-d') . '.xlsx');
            exit;
        } catch (PDOException $e) {
            die("Gagal Export: " . $e->getMessage());
        }
        break;

    case 'add_siswa':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = $_POST['username'];
            $nama_lengkap = $_POST['nama_lengkap'];
            $kelas = $_POST['kelas'] ?? '';
            $token = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, nama_lengkap, kelas, token, role_id, status_blokir) VALUES (?, ?, ?, ?, 3, 0)");
                $stmt->execute([$username, $nama_lengkap, $kelas, $token]);
                header("Location: ../views/admin/siswa.php?status=success");
                exit;
            } catch (PDOException $e) {
                header("Location: ../views/admin/siswa.php?error=NIS sudah terdaftar!");
                exit;
            }
        }
        break;

    case 'edit_siswa':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id'] ?? null;
            $username = trim($_POST['username'] ?? '');
            $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
            $kelas = trim($_POST['kelas'] ?? '');

            if (!$id || empty($username) || empty($nama_lengkap) || empty($kelas)) {
                header("Location: ../views/admin/siswa.php?error=Semua kolom wajib diisi!");
                exit;
            }

            try {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, nama_lengkap = ?, kelas = ? WHERE id = ? AND role_id = 3");
                $stmt->execute([$username, $nama_lengkap, $kelas, $id]);
                header("Location: ../views/admin/siswa.php?status=updated");
                exit;
            } catch (PDOException $e) {
                header("Location: ../views/admin/siswa.php?error=Gagal mengupdate siswa (NIS mungkin sudah dipakai)!");
                exit;
            }
        }
        break;

    case 'reset_token_siswa':
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $newToken = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            try {
                $stmt = $pdo->prepare("UPDATE users SET token = ? WHERE id = ? AND role_id = 3");
                $stmt->execute([$newToken, $id]);
                $referer = $_SERVER['HTTP_REFERER'] ?? '../views/admin/siswa.php';
                if (strpos($referer, 'status=') !== false) {
                    $referer = preg_replace('/([?&])status=[^&]+(&|$)/', '$1', $referer);
                    $referer = rtrim($referer, '?&');
                }
                $separator = (strpos($referer, '?') !== false) ? '&' : '?';
                header("Location: " . $referer . $separator . "status=token_reset");
                exit;
            } catch (PDOException $e) {
                die("Gagal reset token: " . $e->getMessage());
            }
        }
        break;
        // --- MANAJEMEN GURU ---
        case 'add_guru':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $nama = trim($_POST['nama_lengkap'] ?? '');
            $user = trim($_POST['username'] ?? '');
            $token = trim($_POST['token'] ?? '');
            if (empty($token)) {
                $token = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            }

            try {
                $pdo->beginTransaction();

                // Simpan User (Guru) - Role ID 2
                $stmtUser = $pdo->prepare("INSERT INTO users (nama_lengkap, username, token, role_id) VALUES (?, ?, ?, 2)");
                $stmtUser->execute([$nama, $user, $token]);
                $guru_id = $pdo->lastInsertId();

                $stmtSub = $pdo->prepare("INSERT INTO subjects (nama_mapel, kelas, guru_id) VALUES (?, ?, ?)");

                // Cek apakah dikirim via array mapel[] (Form Dinamis)
                if (isset($_POST['mapel']) && is_array($_POST['mapel'])) {
                    foreach ($_POST['mapel'] as $idx => $mName) {
                        $mName = trim($mName);
                        $mKelas = trim($_POST['mapel_kelas'][$idx] ?? '');
                        if (!empty($mName)) {
                            $stmtSub->execute([$mName, $mKelas, $guru_id]);
                        }
                    }
                } else {
                    // Fallback jika menggunakan input teks lama
                    $mapel_input = $_POST['nama_mapel'] ?? ''; 
                    $kelas_input = $_POST['kelas'] ?? '';
                    $mapel_list = array_map('trim', explode(',', $mapel_input)); 
                    $kelas_list = array_map('trim', explode('|', $kelas_input));

                    foreach ($mapel_list as $index => $mapel) {
                        if (!empty($mapel)) {
                            $kelas_sasaran = isset($kelas_list[$index]) ? $kelas_list[$index] : ($kelas_list[0] ?? '');
                            $stmtSub->execute([$mapel, $kelas_sasaran, $guru_id]);
                        }
                    }
                }

                $pdo->commit();
                header("Location: ../views/admin/manajemen_guru.php?status=success");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                header("Location: ../views/admin/manajemen_guru.php?error=" . urlencode("Gagal menambah guru: " . $e->getMessage()));
                exit;
            }
        }
        break;

    case 'edit_guru':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $guru_id = $_POST['id'] ?? null;
            $nama = trim($_POST['nama_lengkap'] ?? '');
            $user = trim($_POST['username'] ?? '');
            $token = trim($_POST['token'] ?? '');

            if (!$guru_id || empty($nama) || empty($user)) {
                header("Location: ../views/admin/manajemen_guru.php?error=Nama dan Username/NIP wajib diisi!");
                exit;
            }

            try {
                $pdo->beginTransaction();

                // 1. Update data guru di tabel users
                if (!empty($token)) {
                    $stmtUser = $pdo->prepare("UPDATE users SET nama_lengkap = ?, username = ?, token = ? WHERE id = ? AND role_id = 2");
                    $stmtUser->execute([$nama, $user, $token, $guru_id]);
                } else {
                    $stmtUser = $pdo->prepare("UPDATE users SET nama_lengkap = ?, username = ? WHERE id = ? AND role_id = 2");
                    $stmtUser->execute([$nama, $user, $guru_id]);
                }

                // 2. Jika ada data mapel[] dan mapel_kelas[]
                if (isset($_POST['mapel']) && is_array($_POST['mapel'])) {
                    // Ambil daftar id mapel lama milik guru ini
                    $stmtOld = $pdo->prepare("SELECT id FROM subjects WHERE guru_id = ?");
                    $stmtOld->execute([$guru_id]);
                    $oldSubjectIds = $stmtOld->fetchAll(PDO::FETCH_COLUMN);

                    $keptIds = [];
                    $stmtInsertSub = $pdo->prepare("INSERT INTO subjects (nama_mapel, kelas, guru_id) VALUES (?, ?, ?)");
                    $stmtUpdateSub = $pdo->prepare("UPDATE subjects SET nama_mapel = ?, kelas = ? WHERE id = ? AND guru_id = ?");

                    foreach ($_POST['mapel'] as $idx => $mName) {
                        $mName = trim($mName);
                        $mKelas = trim($_POST['mapel_kelas'][$idx] ?? '');
                        $subId = !empty($_POST['subject_id'][$idx]) ? (int)$_POST['subject_id'][$idx] : 0;

                        if (!empty($mName)) {
                            if ($subId > 0 && in_array($subId, $oldSubjectIds)) {
                                $stmtUpdateSub->execute([$mName, $mKelas, $subId, $guru_id]);
                                $keptIds[] = $subId;
                            } else {
                                $stmtInsertSub->execute([$mName, $mKelas, $guru_id]);
                                $keptIds[] = $pdo->lastInsertId();
                            }
                        }
                    }

                    // Hapus mapel yang dibuang oleh admin
                    $toDelete = array_diff($oldSubjectIds, $keptIds);
                    if (!empty($toDelete)) {
                        $delPlaceholders = implode(',', array_fill(0, count($toDelete), '?'));
                        $pdo->prepare("DELETE FROM subjects WHERE id IN ($delPlaceholders) AND guru_id = ?")
                            ->execute(array_merge(array_values($toDelete), [$guru_id]));
                    }
                }

                $pdo->commit();
                header("Location: ../views/admin/manajemen_guru.php?status=updated");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                header("Location: ../views/admin/manajemen_guru.php?error=" . urlencode("Gagal memperbarui guru: " . $e->getMessage()));
                exit;
            }
        }
        break;

    case 'reset_token_guru':
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $newToken = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            try {
                $stmt = $pdo->prepare("UPDATE users SET token = ? WHERE id = ? AND role_id = 2");
                $stmt->execute([$newToken, $id]);
                header("Location: ../views/admin/manajemen_guru.php?status=token_reset");
                exit;
            } catch (PDOException $e) {
                die("Gagal reset token guru: " . $e->getMessage());
            }
        }
        break;
    case 'delete_guru':
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            try {
                $pdo->beginTransaction(); // Mulai transaksi

                // 1. Cari dulu ID Mapel yang dimiliki guru ini
                $stmtGetMapel = $pdo->prepare("SELECT id FROM subjects WHERE guru_id = ?");
                $stmtGetMapel->execute([$id]);
                $mapel_ids = $stmtGetMapel->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($mapel_ids)) {
                    // Buat string placeholder (?,?,?) untuk query IN
                    $placeholders = implode(',', array_fill(0, count($mapel_ids), '?'));

                    // 2. Hapus SOAL (Questions) yang terkait dengan mapel guru ini
                    // (Langkah ini memperbaiki error Foreign Key constraint yang Anda alami)
                    $stmtDelSoal = $pdo->prepare("DELETE FROM questions WHERE subject_id IN ($placeholders)");
                    $stmtDelSoal->execute($mapel_ids);

                    // 3. Hapus UJIAN (Exams) yang terkait dengan mapel guru ini
                    // (Langkah preventif agar tidak error lagi setelah soal dihapus)
                    $stmtDelUjian = $pdo->prepare("DELETE FROM exams WHERE subject_id IN ($placeholders)");
                    $stmtDelUjian->execute($mapel_ids);
                    
                    // 4. Hapus MAPEL (Subjects)
                    // Sekarang aman dihapus karena child-nya (soal & ujian) sudah bersih
                    $stmtSub = $pdo->prepare("DELETE FROM subjects WHERE guru_id = ?");
                    $stmtSub->execute([$id]);
                }

                // 5. Terakhir, Hapus AKUN GURU (Users)
                $stmtUser = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmtUser->execute([$id]);

                $pdo->commit(); // Simpan perubahan permanen
                header("Location: ../views/admin/manajemen_guru.php?status=deleted");
            } catch (Exception $e) {
                $pdo->rollBack(); // Batalkan jika ada error
                // Tampilkan pesan error yang lebih jelas jika masih gagal
                die("Gagal menghapus data guru. Pastikan ujian yang dibuat guru ini sudah tidak memiliki sesi aktif/nilai siswa. Detail Error: " . $e->getMessage());
            }
        }
        break;
        
        
        case 'add_sesi':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $exam_id = $_POST['exam_id'];
            $kelas = $_POST['kelas']; // Mengambil variabel kelas dari form

            try {
                // Ambil semua siswa yang berada di kelas tersebut (Role 3 = Siswa)
                $stmtSiswa = $pdo->prepare("SELECT id FROM users WHERE kelas = ? AND role_id = 3");
                $stmtSiswa->execute([$kelas]);
                $siswas = $stmtSiswa->fetchAll();

                if ($siswas) {
                    foreach ($siswas as $s) {
                        // Cek duplikasi agar siswa tidak punya 2 sesi di ujian yang sama
                        $check = $pdo->prepare("SELECT id FROM exam_sessions WHERE exam_id = ? AND user_id = ?");
                        $check->execute([$exam_id, $s['id']]);
                        
                        if (!$check->fetch()) {
                            $stmt = $pdo->prepare("INSERT INTO exam_sessions (exam_id, user_id, status_siswa, skor) VALUES (?, ?, 'belum', 0)");
                            $stmt->execute([$exam_id, $s['id']]);
                        }
                    }
                    header("Location: ../views/admin/kelola_sesi.php?status=add_success");
                } else {
                    header("Location: ../views/admin/kelola_sesi.php?error=no_students");
                }
                exit;
            } catch (PDOException $e) {
                die("Gagal menambah sesi: " . $e->getMessage());
            }
        }
        break;

    case 'reset_sesi': // Berfungsi sebagai Reset (menghapus jawaban saja/mengulang)
        if (isset($_GET['id'])) {
            $session_id = $_GET['id'];
            try {
                $pdo->prepare("DELETE FROM exam_answers WHERE session_id = ?")->execute([$session_id]);
                $pdo->prepare("UPDATE exam_sessions SET status_siswa = 'belum', skor = 0, sisa_waktu = NULL, mulai_ujian = NULL, selesai_ujian = NULL WHERE id = ?")->execute([$session_id]);
                header("Location: ../views/admin/kelola_sesi.php?status=reset_success");
                exit;
            } catch (PDOException $e) {
                die("Gagal mereset sesi: " . $e->getMessage());
            }
        }
        break;

    case 'delete_sesi': // Berfungsi sebagai Hapus Permanen
        if (isset($_GET['id'])) {
            $session_id = $_GET['id'];
            try {
                $pdo->prepare("DELETE FROM exam_answers WHERE session_id = ?")->execute([$session_id]);
                $pdo->prepare("DELETE FROM exam_sessions WHERE id = ?")->execute([$session_id]);
                header("Location: ../views/admin/kelola_sesi.php?status=delete_success");
                exit;
            } catch (PDOException $e) {
                die("Gagal menghapus sesi: " . $e->getMessage());
            }
        }
        break;

    case 'force_submit_sesi':
        $session_id = $_GET['id'] ?? $_POST['id'] ?? null;
        if ($session_id) {
            try {
                finalize_exam_session($pdo, (int)$session_id);
                $referer = $_SERVER['HTTP_REFERER'] ?? '../views/admin/kelola_sesi.php';
                $cleanReferer = preg_replace('/([?&])status=[^&]+(&|$)/', '$1', $referer);
                $cleanReferer = rtrim($cleanReferer, '?&');
                $separator = (strpos($cleanReferer, '?') !== false) ? '&' : '?';
                header("Location: " . $cleanReferer . $separator . "status=force_submit_success");
                exit;
            } catch (Exception $e) {
                die("Gagal melakukan submit paksa: " . $e->getMessage());
            }
        }
        break;

    case 'bulk_force_submit_sesi':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $session_ids = $_POST['session_ids'] ?? [];
            if (!empty($session_ids) && is_array($session_ids)) {
                $count = 0;
                foreach ($session_ids as $sid) {
                    $sid = (int)$sid;
                    if ($sid > 0) {
                        if (finalize_exam_session($pdo, $sid)) {
                            $count++;
                        }
                    }
                }
                $referer = $_SERVER['HTTP_REFERER'] ?? '../views/admin/kelola_sesi.php';
                $cleanReferer = preg_replace('/([?&])(status|count)=[^&]+(&|$)/', '$1', $referer);
                $cleanReferer = rtrim($cleanReferer, '?&');
                $separator = (strpos($cleanReferer, '?') !== false) ? '&' : '?';
                header("Location: " . $cleanReferer . $separator . "status=bulk_force_submit_success&count=" . $count);
                exit;
            }
        }
        header("Location: ../views/admin/kelola_sesi.php?error=no_session_selected");
        exit;
        break;
    case 'add_exam':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $nama = trim($_POST['nama_ujian'] ?? '');
            $subject_id = $_POST['subject_id'] ?? null;
            $target_kelas = trim($_POST['target_kelas'] ?? '');
            $mulai = $_POST['waktu_mulai'] ?? '';
            $selesai = $_POST['waktu_selesai'] ?? '';
            $durasi = (int)($_POST['durasi'] ?? 0);
            $token = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

            if (empty($nama) || empty($subject_id) || empty($target_kelas) || empty($mulai)) {
                header("Location: ../views/admin/kelola_ujian.php?error=Semua kolom wajib diisi dengan benar!");
                exit;
            }

            $mulaiTime = strtotime($mulai);
            $selesaiTime = !empty($selesai) ? strtotime($selesai) : 0;

            // Jika jam selesai belum diisi tapi durasi ada, hitung jam selesai dari durasi
            if (empty($selesai) && $durasi > 0) {
                $selesaiTime = $mulaiTime + ($durasi * 60);
            }

            if ($selesaiTime <= $mulaiTime) {
                header("Location: ../views/admin/kelola_ujian.php?error=Jam akhir ujian harus lebih besar dari waktu mulai!");
                exit;
            }

            // Normalisasi ke format DATETIME MySQL standar
            $mulai = date('Y-m-d H:i:s', $mulaiTime);
            $selesai = date('Y-m-d H:i:s', $selesaiTime);

            // Jika durasi kosong atau <= 0, hitung otomatis dari selisih waktu mulai dan jam selesai
            if ($durasi <= 0) {
                $durasi = max(5, round(($selesaiTime - $mulaiTime) / 60));
            }

            $stmt = $pdo->prepare("INSERT INTO exams (nama_ujian, subject_id, target_kelas, waktu_mulai, waktu_selesai, durasi, token_ujian) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nama, $subject_id, $target_kelas, $mulai, $selesai, $durasi, $token]);
            
            header("Location: ../views/admin/kelola_ujian.php?status=success");
            exit;
        }
        break;

    case 'edit_exam':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id'] ?? null;
            $nama = trim($_POST['nama_ujian'] ?? '');
            $subject_id = $_POST['subject_id'] ?? null;
            $target_kelas = trim($_POST['target_kelas'] ?? '');
            $mulai = $_POST['waktu_mulai'] ?? '';
            $selesai = $_POST['waktu_selesai'] ?? '';
            $durasi = (int)($_POST['durasi'] ?? 0);
            $token = strtoupper(trim($_POST['token_ujian'] ?? ''));

            if (!$id || empty($nama) || empty($subject_id) || empty($target_kelas) || empty($mulai)) {
                header("Location: ../views/admin/kelola_ujian.php?error=Semua kolom wajib diisi dengan benar!");
                exit;
            }

            $mulaiTime = strtotime($mulai);
            $selesaiTime = !empty($selesai) ? strtotime($selesai) : 0;

            if (empty($selesai) && $durasi > 0) {
                $selesaiTime = $mulaiTime + ($durasi * 60);
            }

            if ($selesaiTime <= $mulaiTime) {
                header("Location: ../views/admin/kelola_ujian.php?error=Jam akhir ujian harus lebih besar dari waktu mulai!");
                exit;
            }

            // Normalisasi ke format DATETIME MySQL standar
            $mulai = date('Y-m-d H:i:s', $mulaiTime);
            $selesai = date('Y-m-d H:i:s', $selesaiTime);

            if ($durasi <= 0) {
                $durasi = max(5, round(($selesaiTime - $mulaiTime) / 60));
            }

            try {
                if (!empty($token)) {
                    $stmt = $pdo->prepare("UPDATE exams SET nama_ujian = ?, subject_id = ?, target_kelas = ?, waktu_mulai = ?, waktu_selesai = ?, durasi = ?, token_ujian = ? WHERE id = ?");
                    $stmt->execute([$nama, $subject_id, $target_kelas, $mulai, $selesai, $durasi, $token, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE exams SET nama_ujian = ?, subject_id = ?, target_kelas = ?, waktu_mulai = ?, waktu_selesai = ?, durasi = ? WHERE id = ?");
                    $stmt->execute([$nama, $subject_id, $target_kelas, $mulai, $selesai, $durasi, $id]);
                }
                header("Location: ../views/admin/kelola_ujian.php?status=updated");
                exit;
            } catch (PDOException $e) {
                die("Gagal memperbarui ujian: " . $e->getMessage());
            }
        }
        break;

    case 'delete_exam':
        if (isset($_GET['id'])) {
            $pdo->prepare("DELETE FROM exams WHERE id = ?")->execute([$_GET['id']]);
            header("Location: ../views/admin/kelola_ujian.php?status=deleted");
            exit;
        }
        break;

    case 'refresh_token_exam':
        if (isset($_GET['id'])) {
            $newToken = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $pdo->prepare("UPDATE exams SET token_ujian = ? WHERE id = ?")->execute([$newToken, $_GET['id']]);
            header("Location: ../views/admin/kelola_ujian.php?status=token_updated");
            exit;
        }
        break;

    case 'delete_user':
        if (isset($_GET['id'])) {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$_GET['id']]);
            $referer = $_SERVER['HTTP_REFERER'] ?? '../views/admin/dashboard.php';
            header("Location: $referer");
            exit;
        }
        break;

    // ==========================================
    // ACTION: MANAJEMEN BANK SOAL (ADMIN PUSAT)
    // ==========================================
    case 'add_bank_soal':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nama_bank = trim($_POST['nama_bank'] ?? '');
            $subject_id = (int)($_POST['subject_id'] ?? 0);
            $guru_id = (int)($_POST['guru_id'] ?? 0);
            $target_kelas = $_POST['target_kelas'] ?? [];
            if (is_array($target_kelas)) {
                $kelas_str = implode(', ', array_filter(array_map('trim', $target_kelas)));
            } else {
                $kelas_str = trim($target_kelas);
            }
            $status_rilis = !empty($kelas_str) ? 'rilis' : 'draft';

            if (empty($nama_bank) || empty($subject_id)) {
                header("Location: ../views/admin/manajemen_soal.php?tab=bank&error=" . urlencode("Nama bank soal dan mata pelajaran wajib diisi!"));
                exit;
            }

            if (empty($guru_id)) {
                $stmtSub = $pdo->prepare("SELECT guru_id FROM subjects WHERE id = ?");
                $stmtSub->execute([$subject_id]);
                $guru_id = (int)$stmtSub->fetchColumn();
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO bank_soal (nama_bank, subject_id, guru_id, kelas_rilis, status_rilis) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nama_bank, $subject_id, $guru_id, !empty($kelas_str) ? $kelas_str : null, $status_rilis]);

                header("Location: ../views/admin/manajemen_soal.php?tab=bank&status=bank_created");
                exit;
            } catch (PDOException $e) {
                header("Location: ../views/admin/manajemen_soal.php?tab=bank&error=" . urlencode("Gagal menambah bank soal: " . $e->getMessage()));
                exit;
            }
        }
        break;

    case 'edit_bank_soal':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $bank_id = (int)($_POST['bank_id'] ?? 0);
            $nama_bank = trim($_POST['nama_bank'] ?? '');
            $subject_id = (int)($_POST['subject_id'] ?? 0);
            $guru_id = (int)($_POST['guru_id'] ?? 0);

            if (!$bank_id || empty($nama_bank) || empty($subject_id)) {
                header("Location: ../views/admin/manajemen_soal.php?tab=bank&error=" . urlencode("Data bank soal tidak lengkap!"));
                exit;
            }

            if (empty($guru_id)) {
                $stmtSub = $pdo->prepare("SELECT guru_id FROM subjects WHERE id = ?");
                $stmtSub->execute([$subject_id]);
                $guru_id = (int)$stmtSub->fetchColumn();
            }

            try {
                $stmt = $pdo->prepare("UPDATE bank_soal SET nama_bank = ?, subject_id = ?, guru_id = ? WHERE id = ?");
                $stmt->execute([$nama_bank, $subject_id, $guru_id, $bank_id]);

                // Update juga subject_id di questions dalam bank soal ini
                $pdo->prepare("UPDATE questions SET subject_id = ? WHERE bank_soal_id = ?")->execute([$subject_id, $bank_id]);

                header("Location: ../views/admin/manajemen_soal.php?tab=bank&status=bank_updated");
                exit;
            } catch (PDOException $e) {
                header("Location: ../views/admin/manajemen_soal.php?tab=bank&error=" . urlencode("Gagal memperbarui bank soal: " . $e->getMessage()));
                exit;
            }
        }
        break;

    case 'delete_bank_soal':
        if (isset($_GET['id'])) {
            $bank_id = (int)$_GET['id'];
            try {
                // Bersihkan gambar pertanyaan & pilihan yang terkait
                $stmtImg = $pdo->prepare("SELECT img_pertanyaan, img_a, img_b, img_c, img_d, img_e FROM questions WHERE bank_soal_id = ?");
                $stmtImg->execute([$bank_id]);
                $soals = $stmtImg->fetchAll(PDO::FETCH_ASSOC);
                $imgDir = __DIR__ . '/../assets/img/soal/';
                foreach ($soals as $s) {
                    foreach (['img_pertanyaan', 'img_a', 'img_b', 'img_c', 'img_d', 'img_e'] as $f) {
                        if (!empty($s[$f])) {
                            $fp = $imgDir . $s[$f];
                            if (file_exists($fp)) @unlink($fp);
                        }
                    }
                }

                $pdo->prepare("DELETE FROM questions WHERE bank_soal_id = ?")->execute([$bank_id]);
                $pdo->prepare("DELETE FROM bank_soal WHERE id = ?")->execute([$bank_id]);

                header("Location: ../views/admin/manajemen_soal.php?tab=bank&status=bank_deleted");
                exit;
            } catch (PDOException $e) {
                header("Location: ../views/admin/manajemen_soal.php?tab=bank&error=" . urlencode("Gagal menghapus bank soal: " . $e->getMessage()));
                exit;
            }
        }
        break;

    case 'rilis_bank_soal':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $bank_id = (int)($_POST['bank_id'] ?? 0);
            $redirect_tab = $_POST['redirect_tab'] ?? 'bank';
            $kelas_raw = $_POST['target_kelas'] ?? [];
            if (is_array($kelas_raw)) {
                $target_kelas_str = implode(', ', array_filter(array_map('trim', $kelas_raw)));
            } else {
                $target_kelas_str = trim($kelas_raw);
            }

            try {
                if (!empty($target_kelas_str)) {
                    $stmtB = $pdo->prepare("UPDATE bank_soal SET kelas_rilis = ?, status_rilis = 'rilis' WHERE id = ?");
                    $stmtB->execute([$target_kelas_str, $bank_id]);

                    $stmtQ = $pdo->prepare("UPDATE questions SET kelas = ? WHERE bank_soal_id = ?");
                    $stmtQ->execute([$target_kelas_str, $bank_id]);
                    $msg = "Bank soal berhasil dirilis ke kelas: $target_kelas_str.";
                } else {
                    $stmtB = $pdo->prepare("UPDATE bank_soal SET kelas_rilis = NULL, status_rilis = 'draft' WHERE id = ?");
                    $stmtB->execute([$bank_id]);

                    $stmtQ = $pdo->prepare("UPDATE questions SET kelas = NULL WHERE bank_soal_id = ?");
                    $stmtQ->execute([$bank_id]);
                    $msg = "Bank soal disimpan sebagai status Draft (belum dirilis ke siswa).";
                }

                header("Location: ../views/admin/manajemen_soal.php?tab=$redirect_tab&status=custom&msg=" . urlencode($msg));
                exit;
            } catch (PDOException $e) {
                header("Location: ../views/admin/manajemen_soal.php?tab=$redirect_tab&error=" . urlencode("Gagal merilis bank soal: " . $e->getMessage()));
                exit;
            }
        }
        break;

    // ==========================================
    // ACTION: BUTIR SOAL (ADMIN PUSAT)
    // ==========================================
    case 'add_soal':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $bank_soal_id = (int)($_POST['bank_soal_id'] ?? 0);
            $subject_id = (int)($_POST['subject_id'] ?? 0);
            $kelas = trim($_POST['kelas'] ?? '');
            $pertanyaan = trim($_POST['pertanyaan'] ?? '');
            $pil_a = trim($_POST['pil_a'] ?? '');
            $pil_b = trim($_POST['pil_b'] ?? '');
            $pil_c = trim($_POST['pil_c'] ?? '');
            $pil_d = trim($_POST['pil_d'] ?? '');
            $pil_e = trim($_POST['pil_e'] ?? '');
            $kunci = strtoupper(trim($_POST['kunci_jawaban'] ?? 'A'));

            if ($bank_soal_id > 0) {
                $stmtB = $pdo->prepare("SELECT subject_id, kelas_rilis FROM bank_soal WHERE id = ?");
                $stmtB->execute([$bank_soal_id]);
                $bData = $stmtB->fetch(PDO::FETCH_ASSOC);
                if ($bData) {
                    $subject_id = (int)$bData['subject_id'];
                    if (empty($kelas) && !empty($bData['kelas_rilis'])) {
                        $kelas = $bData['kelas_rilis'];
                    }
                }
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO questions (bank_soal_id, subject_id, pertanyaan, pil_a, pil_b, pil_c, pil_d, pil_e, kunci_jawaban, kelas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $bank_soal_id ?: null, $subject_id, $pertanyaan,
                    $pil_a, $pil_b, $pil_c, $pil_d, $pil_e,
                    $kunci, $kelas
                ]);

                $redirectUrl = "../views/admin/manajemen_soal.php?tab=soal" . ($bank_soal_id ? "&bank_id=$bank_soal_id" : "") . "&status=success";
                header("Location: $redirectUrl");
                exit;
            } catch (PDOException $e) {
                header("Location: ../views/admin/manajemen_soal.php?tab=soal&error=" . urlencode("Gagal menambah soal: " . $e->getMessage()));
                exit;
            }
        }
        break;

    case 'edit_soal':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $bank_soal_id = (int)($_POST['bank_soal_id'] ?? 0);
            $subject_id = (int)($_POST['subject_id'] ?? 0);
            $kelas = trim($_POST['kelas'] ?? '');
            $pertanyaan = trim($_POST['pertanyaan'] ?? '');
            $pil_a = trim($_POST['pil_a'] ?? '');
            $pil_b = trim($_POST['pil_b'] ?? '');
            $pil_c = trim($_POST['pil_c'] ?? '');
            $pil_d = trim($_POST['pil_d'] ?? '');
            $pil_e = trim($_POST['pil_e'] ?? '');
            $kunci = strtoupper(trim($_POST['kunci_jawaban'] ?? 'A'));

            if ($bank_soal_id > 0) {
                $stmtB = $pdo->prepare("SELECT subject_id, kelas_rilis FROM bank_soal WHERE id = ?");
                $stmtB->execute([$bank_soal_id]);
                $bData = $stmtB->fetch(PDO::FETCH_ASSOC);
                if ($bData) {
                    $subject_id = (int)$bData['subject_id'];
                    if (empty($kelas) && !empty($bData['kelas_rilis'])) {
                        $kelas = $bData['kelas_rilis'];
                    }
                }
            }

            try {
                $stmt = $pdo->prepare("UPDATE questions SET bank_soal_id=?, subject_id=?, pertanyaan=?, pil_a=?, pil_b=?, pil_c=?, pil_d=?, pil_e=?, kunci_jawaban=?, kelas=? WHERE id=?");
                $stmt->execute([
                    $bank_soal_id ?: null, $subject_id, $pertanyaan,
                    $pil_a, $pil_b, $pil_c, $pil_d, $pil_e,
                    $kunci, $kelas, $id
                ]);

                $redirectUrl = "../views/admin/manajemen_soal.php?tab=soal" . ($bank_soal_id ? "&bank_id=$bank_soal_id" : "") . "&status=updated";
                header("Location: $redirectUrl");
                exit;
            } catch (PDOException $e) {
                header("Location: ../views/admin/manajemen_soal.php?tab=soal&error=" . urlencode("Gagal mengupdate soal: " . $e->getMessage()));
                exit;
            }
        }
        break;

    case 'delete_soal':
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $bank_id = (int)($_GET['bank_id'] ?? 0);

            try {
                $stmtFind = $pdo->prepare("SELECT img_pertanyaan, img_a, img_b, img_c, img_d, img_e FROM questions WHERE id = ?");
                $stmtFind->execute([$id]);
                $qItem = $stmtFind->fetch(PDO::FETCH_ASSOC);
                if ($qItem) {
                    $imgDir = __DIR__ . '/../assets/img/soal/';
                    foreach (['img_pertanyaan', 'img_a', 'img_b', 'img_c', 'img_d', 'img_e'] as $f) {
                        if (!empty($qItem[$f])) {
                            $fp = $imgDir . $qItem[$f];
                            if (file_exists($fp)) @unlink($fp);
                        }
                    }
                }

                $pdo->prepare("DELETE FROM questions WHERE id = ?")->execute([$id]);
                $redirectUrl = "../views/admin/manajemen_soal.php?tab=soal" . ($bank_id ? "&bank_id=$bank_id" : "") . "&status=deleted";
                header("Location: $redirectUrl");
                exit;
            } catch (PDOException $e) {
                header("Location: ../views/admin/manajemen_soal.php?tab=soal&error=" . urlencode("Gagal menghapus: " . $e->getMessage()));
                exit;
            }
        }
        break;

    case 'bulk_delete_soal':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $bank_id = (int)($_POST['bank_id'] ?? 0);
            $ids = $_POST['ids'] ?? [];
            if (is_string($ids)) $ids = explode(',', $ids);
            $ids = array_filter(array_map('intval', (array)$ids));

            if (empty($ids)) {
                header("Location: ../views/admin/manajemen_soal.php?tab=soal&error=" . urlencode("Tidak ada butir soal yang dipilih!"));
                exit;
            }

            try {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmtFind = $pdo->prepare("SELECT id, img_pertanyaan, img_a, img_b, img_c, img_d, img_e FROM questions WHERE id IN ($placeholders)");
                $stmtFind->execute($ids);
                $items = $stmtFind->fetchAll(PDO::FETCH_ASSOC);

                $imgDir = __DIR__ . '/../assets/img/soal/';
                foreach ($items as $item) {
                    foreach (['img_pertanyaan', 'img_a', 'img_b', 'img_c', 'img_d', 'img_e'] as $f) {
                        if (!empty($item[$f])) {
                            $fp = $imgDir . $item[$f];
                            if (file_exists($fp)) @unlink($fp);
                        }
                    }
                }

                $stmtDel = $pdo->prepare("DELETE FROM questions WHERE id IN ($placeholders)");
                $stmtDel->execute($ids);

                $count = count($ids);
                $redirectUrl = "../views/admin/manajemen_soal.php?tab=soal" . ($bank_id ? "&bank_id=$bank_id" : "") . "&status=custom&msg=" . urlencode("$count butir soal berhasil dihapus sekaligus.");
                header("Location: $redirectUrl");
                exit;
            } catch (PDOException $e) {
                header("Location: ../views/admin/manajemen_soal.php?tab=soal&error=" . urlencode("Gagal menghapus soal: " . $e->getMessage()));
                exit;
            }
        }
        break;

    case 'acak_urutan_soal':
        $bank_id = (int)($_POST['bank_id'] ?? ($_GET['bank_id'] ?? 0));
        $redirect_tab = $_POST['redirect_tab'] ?? ($_GET['redirect_tab'] ?? ($bank_id ? 'soal' : 'bank'));

        if (!$bank_id) {
            header("Location: ../views/admin/manajemen_soal.php?tab=$redirect_tab&error=" . urlencode("Bank Soal tidak valid!"));
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT id FROM questions WHERE bank_soal_id = ? ORDER BY id ASC");
            $stmt->execute([$bank_id]);
            $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($ids)) {
                header("Location: ../views/admin/manajemen_soal.php?tab=$redirect_tab" . ($bank_id ? "&bank_id=$bank_id" : "") . "&error=" . urlencode("Tidak ada butir soal di dalam paket bank soal ini untuk diacak."));
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
            $redirectUrl = "../views/admin/manajemen_soal.php?tab=$redirect_tab" . ($bank_id ? "&bank_id=$bank_id" : "") . "&status=custom&msg=" . urlencode("Berhasil mengacak urutan $count butir soal.");
            header("Location: $redirectUrl");
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            header("Location: ../views/admin/manajemen_soal.php?tab=$redirect_tab" . ($bank_id ? "&bank_id=$bank_id" : "") . "&error=" . urlencode("Gagal mengacak soal: " . $e->getMessage()));
            exit;
        }
        break;

    case 'reset_urutan_soal':
        $bank_id = (int)($_POST['bank_id'] ?? ($_GET['bank_id'] ?? 0));
        $redirect_tab = $_POST['redirect_tab'] ?? ($_GET['redirect_tab'] ?? ($bank_id ? 'soal' : 'bank'));

        if (!$bank_id) {
            header("Location: ../views/admin/manajemen_soal.php?tab=$redirect_tab&error=" . urlencode("Bank Soal tidak valid!"));
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE questions SET urutan = 0 WHERE bank_soal_id = ?");
            $stmt->execute([$bank_id]);

            $redirectUrl = "../views/admin/manajemen_soal.php?tab=$redirect_tab" . ($bank_id ? "&bank_id=$bank_id" : "") . "&status=custom&msg=" . urlencode("Urutan butir soal berhasil dikembalikan ke urutan default (berdasarkan nomor ID).");
            header("Location: $redirectUrl");
            exit;
        } catch (Exception $e) {
            header("Location: ../views/admin/manajemen_soal.php?tab=$redirect_tab" . ($bank_id ? "&bank_id=$bank_id" : "") . "&error=" . urlencode("Gagal mereset urutan: " . $e->getMessage()));
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
                header("Location: ../views/admin/manajemen_soal.php?tab=soal" . ($bank_id ? "&bank_id=$bank_id" : "") . "&error=" . urlencode("Pilih minimal 2 butir soal untuk diacak!"));
                exit;
            }

            try {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("SELECT id, urutan FROM questions WHERE id IN ($placeholders) ORDER BY (CASE WHEN urutan > 0 THEN urutan ELSE 9999999 END) ASC, id ASC");
                $stmt->execute($ids);
                $currentRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                header("Location: ../views/admin/manajemen_soal.php?tab=soal" . ($bank_id ? "&bank_id=$bank_id" : "") . "&status=custom&msg=" . urlencode("Urutan $count butir soal terpilih berhasil diacak."));
                exit;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                header("Location: ../views/admin/manajemen_soal.php?tab=soal" . ($bank_id ? "&bank_id=$bank_id" : "") . "&error=" . urlencode("Gagal mengacak soal: " . $e->getMessage()));
                exit;
            }
        }
        break;

    // --- CETAK KARTU UJIAN (PDF) ---
    case 'cetak_kartu':
        // 1. Ambil Filter (Sama seperti logika Export/View)
        $search = $_GET['q'] ?? '';
        $filter_kelas = $_GET['kelas'] ?? '';

        // 2. Query Data Siswa
        $queryStr = "SELECT * FROM users WHERE role_id = 3";
        $params = [];

        if ($search) { 
            $queryStr .= " AND (nama_lengkap LIKE :search OR username LIKE :search)"; 
            $params['search'] = "%$search%";
        }
        if ($filter_kelas) {
            $queryStr .= " AND kelas = :kelas";
            $params['kelas'] = $filter_kelas;
        }
        $queryStr .= " ORDER BY kelas ASC, nama_lengkap ASC";

        $stmt = $pdo->prepare($queryStr);
        $stmt->execute($params);
        $siswa = $stmt->fetchAll();

        // 3. Mulai Pembuatan PDF
        $pdf = new FPDF('P','mm','A4');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        // Konfigurasi Ukuran Kartu
        $cardW = 90; // Lebar Kartu
        $cardH = 55; // Tinggi Kartu
        $gapX  = 10; // Jarak antar kartu horizontal
        $gapY  = 10; // Jarak antar kartu vertikal
        
        $startX = 10;
        $startY = 10;
        
        $x = $startX;
        $y = $startY;
        
        $count = 0;

        foreach ($siswa as $s) {
            // Cek pindah halaman (10 kartu per halaman: 2 kolom x 5 baris)
            if ($count > 0 && $count % 8 == 0) {
                $pdf->AddPage();
                $x = $startX;
                $y = $startY;
            }

            // --- DESAIN KARTU ---
            
            // 1. Bingkai Kartu
            $pdf->SetDrawColor(0, 0, 0); // Hitam
            $pdf->SetLineWidth(0.2);
            $pdf->Rect($x, $y, $cardW, $cardH);

            // 2. Header Kartu (Background Abu-abu)
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Rect($x + 1, $y + 1, $cardW - 2, 12, 'F');
            
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetXY($x, $y + 2);
            $pdf->Cell($cardW, 5, 'KARTU PESERTA UJIAN', 0, 1, 'C');
            
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetXY($x, $y + 7);
            $pdf->Cell($cardW, 4, 'ESPEGA SINAU', 0, 1, 'C');

            // 3. Konten Data Siswa
            $pdf->SetFont('Arial', '', 9);
            $contentY = $y + 16;
            $lineHeight = 5;
            $labelW = 25;

            // Nama
            $pdf->SetXY($x + 3, $contentY);
            $pdf->Cell($labelW, $lineHeight, 'Nama', 0, 0);
            $pdf->Cell(2, $lineHeight, ':', 0, 0);
            $pdf->SetFont('Arial', 'B', 9);
            // Potong nama jika terlalu panjang
            $nama = (strlen($s['nama_lengkap']) > 22) ? substr($s['nama_lengkap'], 0, 22) . '...' : $s['nama_lengkap'];
            $pdf->Cell(50, $lineHeight, $nama, 0, 1);

            // Kelas
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetXY($x + 3, $contentY + $lineHeight);
            $pdf->Cell($labelW, $lineHeight, 'Kelas', 0, 0);
            $pdf->Cell(2, $lineHeight, ':', 0, 0);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(50, $lineHeight, $s['kelas'], 0, 1);

            // Username (NIS)
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetXY($x + 3, $contentY + ($lineHeight * 2));
            $pdf->Cell($labelW, $lineHeight, 'Username', 0, 0);
            $pdf->Cell(2, $lineHeight, ':', 0, 0);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(50, $lineHeight, $s['username'], 0, 1);

            // Token (Password/Akses) - Menggunakan kolom token dari database Anda
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetXY($x + 3, $contentY + ($lineHeight * 3));
            $pdf->Cell($labelW, $lineHeight, 'Token', 0, 0);
            $pdf->Cell(2, $lineHeight, ':', 0, 0);
            $pdf->SetFont('Arial', 'B', 12); // Font lebih besar untuk Token agar jelas
            $pdf->Cell(50, $lineHeight, $s['token'], 0, 1);

            // 4. Footer (Garis putus-putus untuk tanda tangan pengawas - Opsional)
            $pdf->SetLineWidth(0.1);
            $pdf->Line($x + 5, $y + 45, $x + $cardW - 5, $y + 45);
            $pdf->SetFont('Arial', 'I', 7);
            $pdf->SetXY($x, $y + 46);
            $pdf->Cell($cardW, 4, '*Bawa kartu ini saat ujian berlangsung', 0, 0, 'C');

            // --- LOGIKA POSISI GRID (2 Kolom) ---
            // Jika posisi sekarang di kolom kiri, pindah ke kanan
            if ($x == $startX) {
                $x += $cardW + $gapX;
            } else {
                // Jika sudah di kanan, reset ke kiri dan turun ke baris bawah
                $x = $startX;
                $y += $cardH + $gapY;
            }
            
            $count++;
        }

        $pdf->Output('D', 'Kartu_Ujian_' . ($filter_kelas ? $filter_kelas : 'Semua') . '.pdf');
        exit;
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
            BankSoalExporter::exportExcel($pdo, $bank_id);
        } else {
            BankSoalExporter::exportPDF($pdo, $bank_id, $with_kunci);
        }
        exit;
        break;

    case 'logout':
        session_destroy();
        header("Location: ../index.php");
        exit;
}