<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
// Pastikan file SimpleXLSXGen.php ada di folder yang sama atau arahkan path-nya
require_once __DIR__ . '/../vendor/SimpleXLSXGen.php'; 

use Shuchkin\SimpleXLSXGen;

protect_page(); // Hanya yang sudah login bisa akses

$type = $_GET['type'] ?? '';

if ($type === 'template') {
    /**
     * FUNGSI 1: DOWNLOAD TEMPLATE IMPORT SISWA
     */
    $header = [
        ['<b>NIS (Username)</b>', '<b>Nama Lengkap</b>', '<b>Kelas</b>'],
        ['2024001', 'Ahmad Dhani', '9A'],
        ['2024002', 'Siti Aminah', '9B'],
    ];

    $xlsx = SimpleXLSXGen::fromArray($header);
    $xlsx->downloadAs('Template_Import_Siswa.xlsx');
    exit;

} elseif ($type === 'laporan' || empty($type)) {
    /**
     * FUNGSI 2: DOWNLOAD LAPORAN HASIL UJIAN
     */
    try {
        $stmt = $pdo->query("SELECT u.kelas, u.username, u.nama_lengkap, es.skor, es.status_siswa 
                             FROM exam_sessions es 
                             JOIN users u ON es.user_id = u.id 
                             ORDER BY u.kelas ASC, es.skor DESC");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [
            ['<center><b>LAPORAN HASIL UJIAN</b></center>', null, null, null, null], // Judul Merge
            ['<b>Kelas</b>', '<b>NIS</b>', '<b>Nama Lengkap</b>', '<b>Skor</b>', '<b>Status</b>']
        ];

        foreach ($results as $row) {
            $data[] = [
                $row['kelas'],
                $row['username'],
                $row['nama_lengkap'],
                $row['skor'],
                ($row['status_siswa'] == 'terblokir') ? 'PELANGGARAN' : 'SELESAI'
            ];
        }

        $xlsx = SimpleXLSXGen::fromArray($data);
        $xlsx->downloadAs('Laporan_Hasil_CBT_' . date('d-m-Y') . '.xlsx');
        exit;

    } catch (PDOException $e) {
        die("Gagal mengambil data: " . $e->getMessage());
    }
} else {
    die("Aksi tidak valid.");
}