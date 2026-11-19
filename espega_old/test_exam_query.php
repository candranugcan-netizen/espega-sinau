<?php
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain');

$sql = "
    SELECT e.id, e.nama_ujian, e.target_kelas, s.nama_mapel,
           (SELECT COUNT(*) FROM questions WHERE subject_id = e.subject_id) as old_total,
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
           ) as new_total,
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
           ) as matched_bank_name
    FROM exams e
    LEFT JOIN subjects s ON e.subject_id = s.id
    ORDER BY e.id DESC
    LIMIT 20
";

$stmt = $pdo->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $r) {
    echo "ID: {$r['id']} | {$r['nama_ujian']} | Mapel: {$r['nama_mapel']} | Kelas: {$r['target_kelas']} | Bank: {$r['matched_bank_name']} | Old Total: {$r['old_total']} -> New Total: {$r['new_total']}\n";
}
