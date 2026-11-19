<?php 
require_once '../../config/database.php';
require_once '../../config/security.php';

// Pastikan session sudah dimulai untuk mengecek role
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

try {
    // Ambil siswa (role_id = 3) yang terblokir (status_blokir = '1')
    $stmt = $pdo->query("SELECT id, username, nama_lengkap, kelas FROM users WHERE role_id = 3 AND status_blokir = '1' ORDER BY nama_lengkap ASC");
    $siswa = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($siswa);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([]);
}