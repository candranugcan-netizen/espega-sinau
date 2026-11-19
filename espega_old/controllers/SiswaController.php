<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
session_start();

protect_api();

$action = $_GET['action'] ?? '';

// --- VERIFIKASI TOKEN UJIAN ---
if ($action == 'verify_token') {
    header('Content-Type: application/json');
    $exam_id = $_POST['exam_id'] ?? 0;
    $token_input = strtoupper(trim($_POST['token'] ?? ''));
    $user_id = $_SESSION['user_id'];

    if (!$exam_id || empty($token_input)) {
        echo json_encode(['status' => 'error', 'message' => 'Token ujian wajib diisi.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT e.id, e.token_ujian, e.target_kelas, e.waktu_mulai, e.waktu_selesai, e.durasi, u.kelas 
            FROM exams e, users u 
            WHERE e.id = ? AND u.id = ?
        ");
        $stmt->execute([$exam_id, $user_id]);
        $exam = $stmt->fetch();

        if (!$exam) {
            echo json_encode(['status' => 'error', 'message' => 'Ujian tidak ditemukan.']);
            exit;
        }

        if ($exam['target_kelas'] !== $exam['kelas']) {
            echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk ujian kelas ini.']);
            exit;
        }

        date_default_timezone_set('Asia/Jakarta');
        $now = time();
        $startTime = strtotime($exam['waktu_mulai']);
        $endTime = get_exam_end_time($exam);

        if ($now < $startTime) {
            echo json_encode(['status' => 'error', 'message' => 'Ujian belum dimulai! Jadwal ujian dibuka pukul ' . date('H:i', $startTime) . ' WIB.']);
            exit;
        }

        if ($now >= $endTime) {
            echo json_encode(['status' => 'error', 'message' => 'Waktu ujian telah lewat! Jam akhir ujian telah berakhir (' . date('H:i', $endTime) . ' WIB).']);
            exit;
        }

        if ($token_input !== strtoupper(trim($exam['token_ujian']))) {
            echo json_encode(['status' => 'error', 'message' => 'Token ujian salah! Silakan periksa kembali token dari pengawas.']);
            exit;
        }

        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
    }
    exit;
}

// --- AMBIL SOAL ---
if ($action == 'get_soal') {
    $subject_id = $_GET['subject_id'] ?? 0;
    $user_id = $_SESSION['user_id'];
    $session_id = $_GET['session_id'] ?? 0;
    
    try {
        $userStmt = $pdo->prepare("SELECT kelas FROM users WHERE id = ?");
        $userStmt->execute([$user_id]);
        $user = $userStmt->fetch();
        $kelas_siswa = $user['kelas'];

        // Ambil soal berdasarkan mapel & kelas (hanya soal dari Bank Soal yang telah dirilis ke kelas siswa)
        $stmt = $pdo->prepare("
            SELECT q.*, ea.jawaban_siswa 
            FROM questions q 
            LEFT JOIN exam_answers ea ON q.id = ea.question_id AND ea.session_id = ?
            LEFT JOIN bank_soal bs ON q.bank_soal_id = bs.id
            WHERE q.subject_id = ? 
              AND (q.bank_soal_id IS NULL OR bs.status_rilis = 'rilis')
              AND (
                  q.kelas = ? 
                  OR FIND_IN_SET(?, REPLACE(q.kelas, ' ', '')) > 0 
                  OR (bs.kelas_rilis IS NOT NULL AND (FIND_IN_SET(?, REPLACE(bs.kelas_rilis, ' ', '')) > 0 OR bs.kelas_rilis = ?))
                  OR q.kelas = 'ALL' 
                  OR q.kelas = 'Semua Kelas'
              )
            ORDER BY (CASE WHEN q.urutan > 0 THEN q.urutan ELSE 9999999 END) ASC, q.id ASC
        ");
        $stmt->execute([$session_id, $subject_id, $kelas_siswa, $kelas_siswa, $kelas_siswa, $kelas_siswa]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// --- UPDATE TIMER (HEARTBEAT) ---
if ($action == 'update_timer') {
    $session_id = $_POST['session_id'] ?? null;
    $sisa_waktu = $_POST['sisa_waktu'] ?? 0;
    if ($session_id) {
        $pdo->prepare("UPDATE exam_sessions SET sisa_waktu = ? WHERE id = ?")->execute([$sisa_waktu, $session_id]);

        // Cek jika durasi pengerjaan siswa habis, auto-finalize
        if ((int)$sisa_waktu <= 0) {
            finalize_exam_session($pdo, $session_id);
        }
    }
    exit;
}

// --- SIMPAN JAWABAN ---
if ($action == 'simpan_jawaban') {
    $soal_id = $_POST['soal_id'] ?? null;
    $jawaban = $_POST['jawaban'] ?? null;
    $session_id = $_POST['session_id'] ?? null;
    $sisa_waktu = $_POST['sisa_waktu'] ?? null;

    if ($soal_id && $session_id) {
        // Simpan Jawaban
        $check = $pdo->prepare("SELECT id FROM exam_answers WHERE session_id = ? AND question_id = ?");
        $check->execute([$session_id, $soal_id]);
        $exists = $check->fetch();

        if ($exists) {
            $pdo->prepare("UPDATE exam_answers SET jawaban_siswa = ? WHERE id = ?")->execute([$jawaban, $exists['id']]);
        } else {
            $pdo->prepare("INSERT INTO exam_answers (session_id, question_id, jawaban_siswa) VALUES (?, ?, ?)")->execute([$session_id, $soal_id, $jawaban]);
        }
        
        // Update sisa waktu sekaligus
        if($sisa_waktu !== null) {
            $pdo->prepare("UPDATE exam_sessions SET sisa_waktu = ? WHERE id = ?")->execute([$sisa_waktu, $session_id]);
        }
    }
    exit;
}

// --- SELESAI UJIAN & HITUNG SKOR ---
if ($action == 'selesai_ujian') {
    header('Content-Type: application/json'); // PENTING: Paksa format response ke JSON
    
    $session_id = $_POST['session_id'] ?? null;
    $client_sisa_waktu = isset($_POST['sisa_waktu']) ? intval($_POST['sisa_waktu']) : null;
    $is_auto = (isset($_POST['auto']) && $_POST['auto'] == '1');
    $user_id = $_SESSION['user_id'];

    if ($session_id) {
        // 1. Ambil Info Mapel, Kelas, Durasi, dan Sisa Waktu dari Sesi
        $stmtInfo = $pdo->prepare("
            SELECT es.sisa_waktu, e.durasi, e.waktu_mulai, e.waktu_selesai, e.subject_id, u.kelas 
            FROM exam_sessions es 
            JOIN exams e ON es.exam_id = e.id 
            JOIN users u ON es.user_id = u.id 
            WHERE es.id = ? AND es.user_id = ?
        ");
        $stmtInfo->execute([$session_id, $user_id]);
        $info = $stmtInfo->fetch();

        if (!$info) {
            echo json_encode(['status' => 'error', 'message' => 'Sesi ujian tidak valid.']);
            exit;
        }

        $cek_sisa = ($client_sisa_waktu !== null) ? $client_sisa_waktu : ($info['sisa_waktu'] ?? 0);
        $is_time_up = ($cek_sisa <= 0);

        // Validasi tombol submit manual sebelum sisa waktu <= 15 menit (hanya berlaku jika ujian belum habis)
        if (!$is_auto && !$is_time_up) {
            if ($info['durasi'] > 15 && $cek_sisa > 915 && ($info['sisa_waktu'] === null || $info['sisa_waktu'] > 915)) {
                echo json_encode(['status' => 'error', 'message' => 'Ujian belum dapat diselesaikan. Submit hanya diperbolehkan ketika sisa waktu tinggal 15 menit atau kurang.']);
                exit;
            }
        }

        // Finalisasi & submit sesi ujian secara permanen
        finalize_exam_session($pdo, $session_id);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Session ID tidak ditemukan']);
    }
    exit;
}

// --- LOG PELANGGARAN ---
if ($action == 'log_pelanggaran') {
    $session_id = $_POST['session_id'] ?? null;
    $user_id = $_SESSION['user_id'];
    if ($session_id) {    
        $pdo->prepare("UPDATE users SET status_blokir = '1' WHERE id = ?")->execute([$user_id]);
        // Status sesi tidak diubah ke selesai, agar tombol tetap "Lanjutkan" saat unblock
    }
    exit;
}