<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';

if (!isset($_GET['id'])) {
    die("ID pesanan tidak ditemukan");
}

$id_pesanan = (int) $_GET['id'];

/* PESANAN */
$stmt = $koneksi->prepare("
    SELECT p.*, u.username, u.id AS id_user
    FROM pesanan p
    LEFT JOIN users u ON p.id_pelanggan = u.id
    WHERE p.id_pesanan = ?
");
$stmt->bind_param("i", $id_pesanan);
$stmt->execute();
$pesanan = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* DETAIL */
$stmt = $koneksi->prepare("
    SELECT d.*, pr.nama_produk 
    FROM detail_pesanan d
    JOIN produk pr ON d.id_produk = pr.id_produk
    WHERE d.id_pesanan = ?
");
$stmt->bind_param("i", $id_pesanan);
$stmt->execute();
$detail = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Nota Sekar Bouquet</title>

<style>
body{
    margin:0;
    background:#f5f5f5;
    font-family: "Courier New", monospace; /* 🔥 FONT KASIR REAL */
}

/* TOP BUTTON */
.topbar{
    display:flex;
    gap:10px;
    padding:12px;
}

.btn{
    padding:8px 12px;
    border:none;
    cursor:pointer;
    font-size:12px;
    border-radius:6px;
}

.back{background:#555;color:white;}
.print{background:#b76e79;color:white;}

/* WRAPPER */
.container{
    display:flex;
    justify-content:center;
    padding:10px;
}

/* STRUK */
.receipt{
    width:360px;
    background:white;
    padding:15px;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
}

/* HEADER TOKO */
.header{
    text-align:center;
    border-bottom:1px dashed #000;
    padding-bottom:10px;
}

.header h2{
    margin:0;
    font-size:16px;
}

.header small{
    font-size:11px;
}

/* INFO */
.info{
    font-size:11px;
    margin-top:10px;
    line-height:1.4;
}

/* ITEM */
.item{
    display:flex;
    justify-content:space-between;
    font-size:12px;
    margin:4px 0;
}

/* LINE */
.line{
    border-top:1px dashed #000;
    margin:10px 0;
}

/* TOTAL */
.total{
    text-align:right;
    font-weight:bold;
    font-size:13px;
}

/* FOOTER */
.footer{
    text-align:center;
    font-size:11px;
    margin-top:10px;
}

/* PRINT MODE REAL STRUK */
@media print{

    body{
        background:white;
    }

    .topbar{
        display:none;
    }

    .container{
        padding:0;
    }

    .receipt{
        width:58mm; /* 🔥 STRUK REAL PRINTER */
        box-shadow:none;
    }
}
</style>
</head>

<body>

<!-- BUTTON LUAR -->
<div class="topbar" >
    <button class="btn back" onclick="window.location.href='riwayat_pesanan.php'">
        ⬅ Kembali
    </button>

    <button class="btn print" onclick="window.print()">
        🖨 Cetak Nota
    </button>
</div>

<div class="container">

<div class="receipt">

    <!-- TOKO -->
    <div class="header">
        <h2>SEKAR BOUQUET</h2>
        <small>Buket Bunga Premium & Custom</small><br>
        <small>Jl. Mawar No. 12, Surabaya</small>
    </div>

    <!-- INFO -->
    <div class="info">
        <b>ID Pesanan:</b> #<?= $pesanan['id_pesanan']; ?><br>
        <b>ID Pelanggan:</b> <?= $pesanan['id_pelanggan']; ?><br>
        <b>Nama:</b> <?= $pesanan['username']; ?><br>
        <b>Kasir:</b> Admin<br>
        <b>Status:</b> <?= $pesanan['status']; ?><br>
        <b>Pembayaran:</b> <?= $pesanan['metode_pembayaran']; ?><br>
        <b>Tanggal:</b> <?= $pesanan['tanggal']; ?>
    </div>

    <div class="line"></div>

    <!-- ITEM -->
    <?php while($row = $detail->fetch_assoc()): ?>
        <div class="item">
            <span><?= $row['nama_produk']; ?> x<?= $row['jumlah']; ?></span>
            <span>Rp<?= number_format($row['subtotal']); ?></span>
        </div>
    <?php endwhile; ?>

    <div class="line"></div>

    <div class="total">
        TOTAL: Rp <?= number_format($pesanan['total']); ?>
    </div>

    <div class="footer">
        Terima kasih telah berbelanja 🌸<br>
        Barang yang sudah dibeli tidak dapat ditukar
    </div>

</div>

</div>

</body>
</html>