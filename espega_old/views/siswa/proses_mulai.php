<?php
require_once '../../config/database.php';
require_once '../../config/security.php';
protect_page();

date_default_timezone_set('Asia/Jakarta');
$now = time();

$user_id = $_SESSION['user_id'];
$exam_id = $_POST['id'] ?? $_GET['id'] ?? null;
$token_input = strtoupper(trim($_POST['token'] ?? $_GET['token'] ?? ''));

if (!$exam_id) { 
    header("Location: dashboard.php"); 
    exit; 
}

// 1. Cek Kelas & Data Ujian (termasuk token_ujian, waktu_mulai, waktu_selesai, durasi)
$stmtCheck = $pdo->prepare("SELECT e.target_kelas, e.token_ujian, e.durasi, e.waktu_mulai, e.waktu_selesai, u.kelas FROM exams e, users u WHERE e.id = ? AND u.id = ?");
$stmtCheck->execute([$exam_id, $user_id]);
$data = $stmtCheck->fetch();

if (!$data) {
    header("Location: dashboard.php?error=invalid_exam");
    exit;
}

if ($data['target_kelas'] !== $data['kelas']) {
    header("Location: dashboard.php?error=unauthorized_class");
    exit;
}

// Validasi Waktu Pelaksanaan Ujian
$startTime = strtotime($data['waktu_mulai']);
$endTime = get_exam_end_time($data);

// 2. Ambil Sesi Ujian (Buat otomatis jika belum ada - Pengaktifan sesi bersifat opsional)
$stmt = $pdo->prepare("SELECT id, status_siswa, sisa_waktu FROM exam_sessions WHERE user_id = ? AND exam_id = ?");
$stmt->execute([$user_id, $exam_id]);
$session = $stmt->fetch();

if (!$session) {
    // Sesi dibuat otomatis on the fly!
    $stmtInsert = $pdo->prepare("INSERT INTO exam_sessions (exam_id, user_id, status_siswa, skor, mulai_ujian) VALUES (?, ?, 'belum', 0, NOW())");
    $stmtInsert->execute([$exam_id, $user_id]);
    $sessionId = $pdo->lastInsertId();
    $session = [
        'id' => $sessionId,
        'status_siswa' => 'belum',
        'sisa_waktu' => null
    ];
}

if ($session['status_siswa'] === 'selesai') {
    header("Location: hasil.php");
    exit;
}

if ($now < $startTime) {
    header("Location: dashboard.php?error=exam_not_started");
    exit;
}

// Jam akhir ujian adalah batas gerbang masuk pengerjaan ujian bagi siswa yang belum mulai
if ($session['status_siswa'] === 'belum' && $now >= $endTime) {
    header("Location: dashboard.php?error=exam_expired");
    exit;
}

// 3. Verifikasi Token Ujian jika status siswa masih 'belum'
if ($session['status_siswa'] === 'belum') {
    // Validasi token wajib diisi dan sesuai dengan token ujian di database
    if (empty($token_input) || $token_input !== strtoupper(trim($data['token_ujian']))) {
        header("Location: dashboard.php?error=invalid_token");
        exit;
    }

    // Inisialisasi durasi penuh pengerjaan siswa (dalam detik)
    // Siswa yang masuk sebelum jam akhir ujian tetap mendapat durasi penuh pengerjaan
    $detik_durasi = (int)$data['durasi'] * 60;

    $pdo->prepare("UPDATE exam_sessions SET status_siswa = 'pengerjaan', mulai_ujian = NOW(), sisa_waktu = ? WHERE id = ?")
        ->execute([$detik_durasi, $session['id']]);
}

header("Location: ujian.php?id=" . $exam_id);
exit;