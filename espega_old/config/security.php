<?php
// Mencegah akses langsung ke file
function protect_page() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        // Bersihkan sisa sesi yang sudah kedaluwarsa
        session_unset();
        session_destroy();
        header("Location: /espega2/index.php?pesan=sesi_habis");
        exit;
    }
}

// Proteksi untuk endpoint API/AJAX — mengembalikan JSON agar frontend bisa redirect
function protect_api() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        session_unset();
        session_destroy();
        header('Content-Type: application/json');
        echo json_encode([
            'error'    => 'session_expired',
            'message'  => 'Sesi login Anda telah habis. Silakan login kembali.',
            'redirect' => '/espega2/index.php?pesan=sesi_habis'
        ]);
        exit;
    }
}

// Filter Input
function input_filter($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>