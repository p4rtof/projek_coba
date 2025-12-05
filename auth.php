<?php
// Pastikan sesi sudah dimulai di koneksi.php
if (!isset($_SESSION['user_id'])) {
    // Jika tidak ada sesi user_id, redirect ke halaman login
    header("Location: login.php");
    exit();
}
?>