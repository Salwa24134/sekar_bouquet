<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

$sql = "SELECT * FROM pesanan WHERE id_pelanggan = ? ORDER BY id_pesanan DESC";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Riwayat Pesanan</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#fff5f7;
    font-family:poppins;
}

/* GRID */
.grid{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));
    gap:14px;
}

/* CARD */
.card-order{
    background:#fff;
    border:none;
    border-radius:16px;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
    overflow:hidden;
}

/* IMAGE */
.header-img{
    width:100%;
    height:120px;
    object-fit:cover;
}

/* CONTENT */
.content{
    padding:10px;
    font-size:13px;
}

.badge{
    font-size:11px;
    padding:4px 8px;
    border-radius:8px;
}

.pending{background:#ffc107;}
.proses{background:#0d6efd;color:#fff;}
.selesai{background:#198754;color:#fff;}

/* PRODUCT */
.product-item{
    display:flex;
    align-items:center;
    gap:8px;
    font-size:12px;
    margin-bottom:6px;
}

.product-img{
    width:32px;
    height:32px;
    border-radius:6px;
    object-fit:cover;
}

.small-text{
    font-size:12px;
    color:#666;
}

/* BUTTON FIX */
.btn-small{
    font-size:12px;
    border-radius:10px;
}
</style>
</head>

<body>

<?php include 'layout/header.php'; ?>

<div class="container py-4">

<!-- HEADER BUTTON -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Riwayat Pesanan 🌸</h5>

    <a href="index.php" class="btn btn-outline-dark btn-sm btn-small">
        ⬅ Kembali ke Beranda
    </a>
</div>

<div class="grid">

<?php while($row = $result->fetch_assoc()): ?>

<?php
$id = $row['id_pesanan'];

$status = strtolower(trim($row['status']));

if ($status == 'selesai') {
    $badge = "selesai";
} elseif ($status == 'diproses') {
    $badge = "proses";
} else {
    $badge = "pending";
}

$produk = $koneksi->query("
SELECT pr.nama_produk, pr.gambar, dp.jumlah
FROM detail_pesanan dp
JOIN produk pr ON pr.id_produk = dp.id_produk
WHERE dp.id_pesanan = $id
");

$first = $produk->fetch_assoc();
?>

<div class="card-order">

<?php if(!empty($first['gambar'])): ?>
<img src="assets/gambar/<?= $first['gambar']; ?>" class="header-img">
<?php endif; ?>

<div class="content">

<div class="d-flex justify-content-between">
    <b>#<?= $id; ?></b>
    <span class="badge <?= $badge; ?>"><?= $row['status']; ?></span>
</div>

<div class="small-text mt-1">
<?= $row['tanggal']; ?>
</div>

<hr>

<?php
$produk2 = $koneksi->query("
SELECT pr.nama_produk, pr.gambar, dp.jumlah
FROM detail_pesanan dp
JOIN produk pr ON pr.id_produk = dp.id_produk
WHERE dp.id_pesanan = $id
");

while($p = $produk2->fetch_assoc()):
?>

<div class="product-item">
    <img src="assets/gambar/<?= $p['gambar']; ?>" class="product-img">
    <div>
        <?= $p['nama_produk']; ?>
        <div class="small-text">x<?= $p['jumlah']; ?></div>
    </div>
</div>

<?php endwhile; ?>

<hr>

<div class="small-text">
Total: <b>Rp <?= number_format($row['total'],0,',','.'); ?></b>
</div>

<div class="mt-2">

<?php if(strtolower($row['status']) == 'selesai'): ?>
    <a href="nota.php?id=<?= $id; ?>" class="btn btn-success btn-sm w-100 btn-small">
        Cetak Nota
    </a>
<?php else: ?>
    <div class="text-warning small">
        Menunggu verifikasi admin
    </div>
<?php endif; ?>

</div>

</div>
</div>

<?php endwhile; ?>

</div>
</div>

<?php include 'layout/footer.php'; ?>

</body>
</html>