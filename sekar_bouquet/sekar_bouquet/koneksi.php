<?php
$host     = "localhost";
$username = "root";       // Username default MySQL (kosongkan atau sesuaikan jika ada password)
$password = "";           // Password default MySQL (biasanya kosong di XAMPP)
$database = "sekar_bouquet";

// Membuat koneksi ke database MySQL
$koneksi = new mysqli($host, $username, $password, $database);

// Memeriksa apakah koneksi berhasil
if ($koneksi->connect_error) {
    die("Koneksi ke database MySQL gagal: " . $koneksi->connect_error);
}

// Mengatur charset ke UTF-8 agar karakter emoji/simbol aman dimasukkan ke DB
$koneksi->set_charset("utf8mb4");
?>