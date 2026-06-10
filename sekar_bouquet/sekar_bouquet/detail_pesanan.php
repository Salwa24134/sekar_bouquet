<?php
session_start();
include 'koneksi.php';

/* =========================
   PROTEKSI ADMIN
========================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

/* =========================
   VALIDASI ID
========================= */
if (!isset($_GET['id'])) {
    header("Location: pesanan_admin.php");
    exit();
}

$id = $_GET['id'];

/* =========================
   DATA HEADER PESANAN
========================= */
$sqlHeader = "SELECT * FROM pesanan_header WHERE id = ?";
$resHeader = sqlsrv_query($koneksi, $sqlHeader, [$id]);
$header = sqlsrv_fetch_array($resHeader, SQLSRV_FETCH_ASSOC);

if (!$header) {
    echo "Pesanan tidak ditemukan";
    exit();
}

/* =========================
   DATA DETAIL PESANAN
========================= */
$sqlDetail = "
SELECT d.*, p.nama
FROM pesanan_detail d
JOIN produk p ON d.produk_id = p.id
WHERE d.id_pesanan = ?
";

$resDetail = sqlsrv_query($koneksi, $sqlDetail, [$id]);
?>

<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<title>Detail Pesanan - Sekar Bouquet</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #fff4f7;
}

h2, h4 {
    font-family: 'Playfair Display', serif;
    color: #b76e79;
}

/* SIDEBAR */
.sidebar {
    width: 250px;
    height: 100vh;
    background: linear-gradient(135deg, #b76e79, #8d4f5c);
    position: fixed;
    padding: 20px;
    color: white;
}

.sidebar a {
    display: block;
    color: white;
    padding: 10px;
    text-decoration: none;
    border-radius: 10px;
    margin-bottom: 10px;
}

.sidebar a:hover {
    background: rgba(255,255,255,0.2);
}

/* MAIN */
.main {
    margin-left: 260px;
    padding: 30px;
}

.card-box {
    border: none;
    border-radius: 18px;
    box-shadow: 0 10px 25px rgba(183,110,121,0.15);
}

.badge-status {
    background: #b76e79;
    color: white;
}

.info-box {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
}

.btn-main {
    background: linear-gradient(135deg, #d88b9c, #b76e79);
    color: white;
    border: none;
}

.btn-main:hover {
    color: white;
}
</style>

</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h3 class="mb-4">🌸 Sekar Admin</h3>

    <a href="admin.php">Dashboard</a>
    <a href="produk_admin.php">Produk</a>
    <a href="pesanan_admin.php">Pesanan</a>
    <a href="users_admin.php">User</a>
    <a href="logout.php">Logout</a>
</div>

<!-- MAIN -->
<div class="main">

<h2 class="mb-4">Detail Pesanan 📦</h2>

<a href="pesanan_admin.php" class="btn btn-secondary mb-3">
    ← Kembali
</a>

<div class="row g-4">

<!-- INFO PESANAN -->
<div class="col-md-4">

<div class="info-box">

<h5 class="mb-3">Informasi Pesanan</h5>

<p><b>ID:</b> #<?= $header['id'] ?></p>
<p><b>Nama:</b> <?= $header['nama'] ?></p>
<p><b>Email:</b> <?= $header['email'] ?></p>
<p><b>Pembayaran:</b> <?= $header['pembayaran'] ?></p>
<p><b>Tanggal:</b> <?= $header['tanggal']->format('Y-m-d H:i') ?></p>

<p>
<b>Status:</b>
<span class="badge badge-status">
    <?= $header['status'] ?>
</span>
</p>

<p><b>Total:</b>
    <span class="fw-bold text-danger">
        Rp <?= number_format($header['total'],0,',','.') ?>
    </span>
</p>

</div>

</div>

<!-- DETAIL PRODUK -->
<div class="col-md-8">

<div class="card card-box p-4">

<h5 class="mb-3">Produk Dipesan</h5>

<table class="table table-hover">

<thead>
<tr>
    <th>Produk</th>
    <th>Harga</th>
    <th>Qty</th>
    <th>Subtotal</th>
</tr>
</thead>

<tbody>

<?php while ($row = sqlsrv_fetch_array($resDetail, SQLSRV_FETCH_ASSOC)) { ?>

<tr>

<td><?= $row['nama'] ?></td>

<td>Rp <?= number_format($row['harga'],0,',','.') ?></td>

<td><?= $row['jumlah'] ?></td>

<td class="text-danger fw-bold">
    Rp <?= number_format($row['subtotal'],0,',','.') ?>
</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</div>

<!-- BUKTI PEMBAYARAN -->
<div class="card card-box p-4 mt-4">

<h5>Bukti Pembayaran</h5>

<?php if ($header['bukti']) { ?>

    <img src="assets/gambar/<?= $header['bukti'] ?>"
         class="img-fluid rounded"
         style="max-width:300px;">

<?php } else { ?>

    <p class="text-muted">Tidak ada bukti pembayaran</p>

<?php } ?>

</div>

</div>

</body>
</html>