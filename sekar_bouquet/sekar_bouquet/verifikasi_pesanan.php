<?php
include 'koneksi.php';

$id = (int)$_GET['id'];

mysqli_query(
    $koneksi,
    "UPDATE pesanan
     SET status='Selesai'
     WHERE id_pesanan='$id'"
);

header("Location: admin_pesanan.php");
exit();
?>