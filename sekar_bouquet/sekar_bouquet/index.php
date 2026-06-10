<?php
session_start();
include 'koneksi.php';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Sekar Bouquet - Toko Bouquet Premium</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #fff8f9;
        }

        h1, h2, h3 {
            font-family: 'Playfair Display', serif;
            color: #b76e79;
        }

        .btn-main {
            background: linear-gradient(135deg, #d88b9c, #b76e79);
            color: white;
            border: none;
            font-weight: 600;
        }

        .btn-main:hover {
            color: white;
            transform: translateY(-2px);
        }

        .feature-card {
            background: #fff0f3;
            border: none;
            border-radius: 16px;
        }

        .product-card {
            border-radius: 16px;
            overflow: hidden;
            transition: 0.3s;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }

        footer {
            background: #b76e79;
        }
    </style>
</head>

<body>

<?php include 'layout/header.php'; ?>

<!-- HERO -->
<div class="text-center py-5" style="background:#fff0f3;">
    <div class="container">
        <h1 class="fw-bold">Sekar Bouquet 🌸</h1>
        <p class="text-muted">
            Toko bouquet bunga premium untuk setiap momen spesialmu
        </p>
        <a href="produk.php" class="btn btn-main btn-lg px-5 rounded-pill">
            Lihat Katalog
        </a>
    </div>
</div>

<!-- FEATURE -->
<div class="container my-5">
    <div class="row text-center g-4">
        <div class="col-md-4">
            <div class="card feature-card p-4">
                <i class="fa-solid fa-heart fs-1 text-danger mb-3"></i>
                <h5>Fresh Flower</h5>
                <p class="text-muted">Bunga segar langsung dari florist terbaik.</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card feature-card p-4">
                <i class="fa-solid fa-gift fs-1 text-danger mb-3"></i>
                <h5>Custom Bouquet</h5>
                <p class="text-muted">Bisa request sesuai momen spesial kamu.</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card feature-card p-4">
                <i class="fa-solid fa-truck fs-1 text-danger mb-3"></i>
                <h5>Fast Delivery</h5>
                <p class="text-muted">Pengiriman cepat area Jombang & sekitarnya.</p>
            </div>
        </div>
    </div>
</div>

<!-- PRODUK -->
<div class="container my-5">
    <h2 class="text-center mb-4">Bouquet Terlaris 💐</h2>

    <div class="row g-4">

        <?php
        $sql = "SELECT TOP 4 * FROM produk";
        $result = sqlsrv_query($koneksi, $sql);

        if ($result):
            while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)):
        ?>

        <div class="col-md-3">
            <div class="card product-card shadow-sm h-100">
                <img src="assets/gambar/<?php echo $row['gambar']; ?>"
                     class="card-img-top"
                     style="height:220px;object-fit:cover;">

                <div class="card-body text-center">
                    <h5 class="fw-bold"><?php echo htmlspecialchars($row['nama']); ?></h5>
                    <p class="text-danger fw-bold">
                        Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?>
                    </p>
                    <a href="produk.php" class="btn btn-main w-100 rounded-pill">
                        Pesan Sekarang
                    </a>
                </div>
            </div>
        </div>

        <?php
            endwhile;
        endif;
        ?>

    </div>
</div>

<!-- CTA -->
<div class="text-center py-5" style="background:#b76e79; color:white;">
    <h2 class="text-white">Pesan Bouquet Sekarang 🌸</h2>
    <p>Abadikan momen spesial dengan bunga terbaik</p>
    <a href="produk.php" class="btn btn-light px-5 rounded-pill fw-bold">
        Mulai Belanja
    </a>
</div>

<!-- ABOUT -->
<div class="container my-5 text-center">
    <h2>Tentang Sekar Bouquet</h2>
    <p class="text-muted">
        Sekar Bouquet adalah toko bouquet bunga yang menghadirkan rangkaian bunga
        untuk hadiah, perayaan, dan momen spesial dengan kualitas terbaik.
    </p>
</div>

<?php include 'layout/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>