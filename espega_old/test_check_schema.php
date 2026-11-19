<?php
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain');

echo "=== SUBJECTS SAMPLE ===\n";
$stmt = $pdo->query("SELECT s.id, s.nama_mapel, s.kelas, 
    (SELECT COUNT(*) FROM bank_soal WHERE subject_id = s.id) as jml_bank,
    (SELECT COUNT(*) FROM questions WHERE subject_id = s.id) as total_q
    FROM subjects s LIMIT 10");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== BANK SOAL FOR SUBJECT 78 (IPA) ===\n";
$stmt = $pdo->query("SELECT bs.id, bs.nama_bank, bs.kelas_rilis, 
    (SELECT COUNT(*) FROM questions WHERE bank_soal_id = bs.id) as q_in_bank
    FROM bank_soal bs WHERE bs.subject_id = 78");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== BANK SOAL FOR SUBJECT 27 (Bahasa Inggris) ===\n";
$stmt = $pdo->query("SELECT bs.id, bs.nama_bank, bs.kelas_rilis, 
    (SELECT COUNT(*) FROM questions WHERE bank_soal_id = bs.id) as q_in_bank
    FROM bank_soal bs WHERE bs.subject_id = 27");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== SAMPLE RUNNING EXAMS ===\n";
$stmt = $pdo->query("SELECT e.id, e.nama_ujian, e.subject_id, e.target_kelas,
    (SELECT COUNT(*) FROM questions WHERE subject_id = e.subject_id) as old_total_soal
    FROM exams e LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
