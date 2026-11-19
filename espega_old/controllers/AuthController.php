<?php
require_once __DIR__ . '/../config/database.php';
session_start();

if (isset($_POST['login'])) {
    $username = htmlspecialchars($_POST['username']);
    $token = htmlspecialchars($_POST['token']);

    // Ambil user dan nama_role berdasarkan username
    $stmt = $pdo->prepare("SELECT u.*, r.nama_role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Verifikasi: User ada DAN token yang diinput sama dengan token di database
    if ($user && $token === $user['token']) { 
        
        // Cek jika akun terblokir
        if ($user['status_blokir'] == '1') {
            header("Location: ../index.php?error=Akun Anda terblokir. Hubungi Admin.");
            exit;
        }

        // Set Data Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['nama_role'];
        $_SESSION['nama'] = $user['nama_lengkap'];

        // Redirect sesuai role
        if ($user['nama_role'] == 'admin') {
            header("Location: ../views/admin/dashboard.php");
        } elseif ($user['nama_role'] == 'guru') {
            header("Location: ../views/guru/dashboard.php");
        } else {
            header("Location: ../views/siswa/dashboard.php");
        }
        exit;
    } else {
        header("Location: ../index.php?error=Username atau Token salah!");
        exit;
    }
}

// Fitur Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../index.php");
    exit;
}