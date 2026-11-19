<?php
/**
 * Script untuk mereset sesi ujian yang terlanjur dibuat dengan status 'pengerjaan'
 * tapi siswa belum benar-benar mengerjakan (belum ada jawaban).
 * 
 * Jalankan sekali via browser: http://localhost/espega2/fix_sessions.php
 * Hapus file ini setelah selesai.
 */
require_once 'config/database.php';

// 1. Cari semua sesi berstatus 'pengerjaan' yang BELUM punya jawaban sama sekali
$stmt = $pdo->query("
    SELECT es.id, es.exam_id, es.user_id, es.status_siswa, es.sisa_waktu
    FROM exam_sessions es
    WHERE es.status_siswa = 'pengerjaan'
    AND NOT EXISTS (SELECT 1 FROM exam_answers ea WHERE ea.session_id = es.id)
");
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Sesi 'pengerjaan' tanpa jawaban (akan direset ke 'belum'):</h2>";
echo "<p>Ditemukan: " . count($sessions) . " sesi</p>";

if (count($sessions) > 0) {
    // 2. Reset semua sesi tersebut ke status 'belum'
    $update = $pdo->prepare("UPDATE exam_sessions SET status_siswa = 'belum', mulai_ujian = NULL, sisa_waktu = NULL WHERE id = ?");
    
    echo "<table border='1' cellpadding='5'><tr><th>Session ID</th><th>Exam ID</th><th>User ID</th><th>Status</th></tr>";
    foreach ($sessions as $s) {
        $update->execute([$s['id']]);
        echo "<tr><td>{$s['id']}</td><td>{$s['exam_id']}</td><td>{$s['user_id']}</td><td>pengerjaan → belum ✅</td></tr>";
    }
    echo "</table>";
    echo "<p style='color: green; font-weight: bold;'>✅ Berhasil mereset " . count($sessions) . " sesi ke status 'belum'.</p>";
} else {
    echo "<p>Tidak ada sesi yang perlu direset.</p>";
}

echo "<hr><p><strong>⚠️ HAPUS FILE INI SETELAH SELESAI!</strong></p>";
echo "<p><a href='views/siswa/dashboard.php'>← Kembali ke Dashboard Siswa</a></p>";
