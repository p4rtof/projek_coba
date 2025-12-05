<?php
// Wajib menyertakan koneksi.php karena di dalamnya ada session_start()
include 'koneksi.php'; 

// Hancurkan semua data sesi
session_unset();
session_destroy();

// Redirect ke halaman login
header("Location: login.php");
exit();
?>