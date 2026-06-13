<?php

// 1. Password asli yang ingin di-hash
$password_asli = "admin123";

// 2. Proses hashing menggunakan algoritma Bcrypt (default standar PHP)
$password_hash = password_hash($password_asli, PASSWORD_DEFAULT);

// 3. Tampilkan hasilnya
echo "Password Asli: " . $password_asli . "<br>";
echo "Hasil Hash (60 karakter): " . $password_hash;

?>