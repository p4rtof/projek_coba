<?php
include '../config/koneksi.php'; 
session_unset();
session_destroy();
header("Location: /projek_coba/auth/login.php");
exit();
?>