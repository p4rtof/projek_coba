<?php
// TAMBAHKAN INI DI BARIS PERTAMA
session_start();

$host = "localhost";
$port = "5432";
$dbname = "projek_coba";
$user = "postgres";
$password = "admin"; // Sesuaikan passwordmu

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    echo "Waduh, koneksi gagal nih.";
} 
// else {
//     echo "Koneksi berhasil, yuk lanjut!";
// }
?>