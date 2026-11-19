<?php
require_once 'config/database.php';

$queryExams = "
    SELECT e.id, e.nama_ujian, e.target_kelas, e.subject_id, s.nama_mapel,
           (SELECT COUNT(*) FROM questions WHERE subject_id = e.subject_id) as old_total_soal,
           COALESCE(
               NULLIF((
                   SELECT COUNT(DISTINCT q.id)
                   FROM questions q
                   JOIN bank_soal bs ON q.bank_soal_id = bs.id
                   WHERE bs.subject_id = e.subject_id
                     AND (
                         bs.kelas_rilis = e.target_kelas
                         OR FIND_IN_SET(e.target_kelas, REPLACE(COALESCE(bs.kelas_rilis, ''), ' ', '')) > 0
                     )
               ), 0),
               NULLIF((
                   SELECT COUNT(DISTINCT q.id)
                   FROM questions q
                   WHERE q.subject_id = e.subject_id
                     AND (
                         q.kelas = e.target_kelas
                         OR FIND_IN_SET(e.target_kelas, REPLACE(COALESCE(q.kelas, ''), ' ', '')) > 0
                     )
               ), 0),
               NULLIF((
                   SELECT COUNT(DISTINCT q.id)
                   FROM questions q
                   JOIN bank_soal bs ON q.bank_soal_id = bs.id
                   WHERE bs.subject_id = e.subject_id
                     AND bs.nama_bank LIKE CONCAT('%Kelas ', SUBSTRING(e.target_kelas, 1, 1), '%')
               ), 0),
               NULLIF((
                   SELECT COUNT(DISTINCT q.id)
                   FROM questions q
                   WHERE q.bank_soal_id = (SELECT id FROM bank_soal WHERE subject_id = e.subject_id LIMIT 1)
               ), 0),
               (SELECT COUNT(*) FROM questions WHERE subject_id = e.subject_id),
               0
           ) as total_soal,
           (
               SELECT bs.nama_bank
               FROM bank_soal bs
               WHERE bs.subject_id = e.subject_id
                 AND (
                     bs.kelas_rilis = e.target_kelas
                     OR FIND_IN_SET(e.target_kelas, REPLACE(COALESCE(bs.kelas_rilis, ''), ' ', '')) > 0
                     OR bs.nama_bank LIKE CONCAT('%Kelas ', SUBSTRING(e.target_kelas, 1, 1), '%')
                 )
               LIMIT 1
           ) as nama_bank_terkait
    FROM exams e 
    LEFT JOIN subjects s ON e.subject_id = s.id 
    ORDER BY e.id DESC
";

$exams = $pdo->query($queryExams)->fetchAll(PDO::FETCH_ASSOC);
echo "Total Exams: " . count($exams) . "\n";
foreach ($exams as $e) {
    echo "Exam #{$e['id']} [{$e['nama_ujian']} | {$e['nama_mapel']} | Kelas {$e['target_kelas']}]: Old={$e['old_total_soal']} => New={$e['total_soal']} (Bank: '{$e['nama_bank_terkait']}')\n";
}
