<?php
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain');

$subjects = $pdo->query("SELECT id, nama_mapel, kelas FROM subjects ORDER BY nama_mapel ASC")->fetchAll(PDO::FETCH_ASSOC);

echo "=== DAFTAR MAPEL DAN BANK SOALNYA ===\n";
foreach ($subjects as $s) {
    $banks = $pdo->query("SELECT bs.id, bs.nama_bank, bs.kelas_rilis, 
        (SELECT COUNT(*) FROM questions WHERE bank_soal_id = bs.id) as jml_soal
        FROM bank_soal bs WHERE bs.subject_id = {$s['id']}")->fetchAll(PDO::FETCH_ASSOC);
    
    $total_q = $pdo->query("SELECT COUNT(*) FROM questions WHERE subject_id = {$s['id']}")->fetchColumn();
    
    echo "Mapel: [{$s['id']}] {$s['nama_mapel']} (Kelas: {$s['kelas']}) - Total Soal Akumulasi: {$total_q}\n";
    if (empty($banks)) {
        echo "   -> Tidak ada bank soal di tabel bank_soal\n";
    } else {
        foreach ($banks as $b) {
            echo "   -> [Bank ID: {$b['id']}] '{$b['nama_bank']}' | Rilis: '{$b['kelas_rilis']}' | Soal: {$b['jml_soal']}\n";
        }
    }
}
