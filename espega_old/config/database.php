<?php
date_default_timezone_set('Asia/Jakarta');
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'espega_baru';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '+07:00'");
    require_once __DIR__ . '/exam_helper.php';
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}
?>