<?php
// Pastikan sesi sudah dimulai (karena koneksi.php sudah session_start, ini buat jaga-jaga)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek sesi
if (!isset($_SESSION['user_id'])) {
    // Arahkan ke folder auth/login.php dengan jalur absolut
    header("Location: /projek_coba/auth/login.php");
    exit();
}
?>