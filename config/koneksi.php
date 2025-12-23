<?php
session_start(); // Wajib di baris pertama

$host = "localhost";
$port = "5432";
$dbname = "projek_coba";
$user = "postgres";
$password = "jasuke412"; // <-- Cek passwordmu

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    die("Koneksi Gagal: " . pg_last_error());
}
?>