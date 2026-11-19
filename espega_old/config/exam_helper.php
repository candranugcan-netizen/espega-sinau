<?php
/**
 * Helper Ujian CBT ESPAGA
 * Menyediakan utilitas waktu ujian, auto-migrasi skema, dan auto-submit sesi ujian.
 */

date_default_timezone_set('Asia/Jakarta');

// Pastikan tabel exams memiliki kolom waktu_selesai
if (isset($pdo)) {
    try {
        $checkCol = $pdo->query("SHOW COLUMNS FROM exams LIKE 'waktu_selesai'")->fetch();
        if (!$checkCol) {
            $pdo->exec("ALTER TABLE exams ADD COLUMN waktu_selesai DATETIME NULL AFTER waktu_mulai");
        }
        // Pastikan semua exam memiliki waktu_selesai yang valid jika masih NULL
        $pdo->exec("UPDATE exams SET waktu_selesai = DATE_ADD(waktu_mulai, INTERVAL COALESCE(durasi, 90) MINUTE) WHERE (waktu_selesai IS NULL OR waktu_selesai = '0000-00-00 00:00:00') AND waktu_mulai IS NOT NULL");
    } catch (Exception $e) {
        // Abaikan jika migrasi sudah berjalan atau ada kendala perizinan
    }
}

if (!function_exists('get_exam_end_time')) {
    /**
     * Mengambil timestamp integer akhir ujian
     */
    function get_exam_end_time($exam) {
        if (!empty($exam['waktu_selesai']) && $exam['waktu_selesai'] !== '0000-00-00 00:00:00') {
            return is_numeric($exam['waktu_selesai']) ? (int)$exam['waktu_selesai'] : strtotime($exam['waktu_selesai']);
        }
        $start = !empty($exam['waktu_mulai']) ? (is_numeric($exam['waktu_mulai']) ? (int)$exam['waktu_mulai'] : strtotime($exam['waktu_mulai'])) : time();
        $durasi = !empty($exam['durasi']) ? (int)$exam['durasi'] : 90;
        return $start + ($durasi * 60);
    }
}

if (!function_exists('finalize_exam_session')) {
    /**
     * Menyelesaikan dan menilai sesi ujian secara permanen (auto-submit maupun manual)
     */
    function finalize_exam_session($pdo, $session_id) {
        if (!$session_id) return false;
        
        try {
            $stmtInfo = $pdo->prepare("
                SELECT es.id, es.user_id, es.status_siswa, es.sisa_waktu, e.durasi, e.subject_id, e.waktu_mulai, e.waktu_selesai, u.kelas 
                FROM exam_sessions es 
                JOIN exams e ON es.exam_id = e.id 
                JOIN users u ON es.user_id = u.id 
                WHERE es.id = ?
            ");
            $stmtInfo->execute([$session_id]);
            $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

            if (!$info || $info['status_siswa'] === 'selesai') {
                return false;
            }

            // Ambil seluruh soal yang dirilis untuk mapel & kelas siswa
            $stmtTotal = $pdo->prepare("
                SELECT q.id, q.kunci_jawaban 
                FROM questions q
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
            ");
            $stmtTotal->execute([$info['subject_id'], $info['kelas'], $info['kelas'], $info['kelas'], $info['kelas']]);
            $allQuestions = $stmtTotal->fetchAll(PDO::FETCH_ASSOC);
            $totalSoal = count($allQuestions);

            // Ambil jawaban yang telah tersimpan
            $stmtAns = $pdo->prepare("SELECT question_id, jawaban_siswa FROM exam_answers WHERE session_id = ?");
            $stmtAns->execute([$session_id]);
            $studentAnswers = $stmtAns->fetchAll(PDO::FETCH_KEY_PAIR);

            $benar = 0;
            foreach ($allQuestions as $q) {
                $kunci = strtoupper(trim($q['kunci_jawaban'] ?? ''));
                $jawab = isset($studentAnswers[$q['id']]) ? strtoupper(trim($studentAnswers[$q['id']])) : '';
                
                if ($jawab === $kunci && $jawab !== '') {
                    $benar++;
                    $pdo->prepare("UPDATE exam_answers SET is_correct = 1 WHERE session_id = ? AND question_id = ?")->execute([$session_id, $q['id']]);
                } else {
                    $pdo->prepare("UPDATE exam_answers SET is_correct = 0 WHERE session_id = ? AND question_id = ?")->execute([$session_id, $q['id']]);
                }
            }

            $skor = ($totalSoal > 0) ? round(($benar / $totalSoal) * 100, 2) : 0;

            $pdo->prepare("UPDATE exam_sessions SET skor = ?, status_siswa = 'selesai', selesai_ujian = NOW(), sisa_waktu = 0 WHERE id = ?")
                ->execute([$skor, $session_id]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('auto_finalize_expired_sessions')) {
    /**
     * Memeriksa dan otomatis submit seluruh sesi pengerjaan yang durasi pengerjaannya telah habis
     */
    function auto_finalize_expired_sessions($pdo, $exam_id = null, $user_id = null) {
        $now = time();
        $sql = "
            SELECT es.id, es.sisa_waktu, es.mulai_ujian, e.waktu_mulai, e.waktu_selesai, e.durasi
            FROM exam_sessions es
            JOIN exams e ON es.exam_id = e.id
            WHERE es.status_siswa = 'pengerjaan'
        ";
        $params = [];
        if ($exam_id) {
            $sql .= " AND es.exam_id = ?";
            $params[] = $exam_id;
        }
        if ($user_id) {
            $sql .= " AND es.user_id = ?";
            $params[] = $user_id;
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($sessions as $s) {
                // 1. Jika countdown sisa waktu pengerjaan siswa <= 0
                if ($s['sisa_waktu'] !== null && (int)$s['sisa_waktu'] <= 0) {
                    finalize_exam_session($pdo, $s['id']);
                    continue;
                }

                // 2. Jika siswa offline / meninggalkan ujian dan durasi penuh sejak mulai_ujian telah terlewati (+ 5 menit toleransi)
                if (!empty($s['mulai_ujian'])) {
                    $waktuMulaiSiswa = strtotime($s['mulai_ujian']);
                    $durasiDetik = ((int)$s['durasi']) * 60;
                    if ($now >= ($waktuMulaiSiswa + $durasiDetik + 300)) {
                        finalize_exam_session($pdo, $s['id']);
                    }
                }
            }
        } catch (Exception $e) {
            // Ignore error
        }
    }
}
