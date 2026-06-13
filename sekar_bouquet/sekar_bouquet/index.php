<?php
session_start();
include 'koneksi.php';
include 'layout/header.php';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sekar Bouquet - Toko Bouquet Premium</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #fff8f9;
        }

        h1, h2, h3 {
            font-family: 'Playfair Display', serif;
            color: #8d4f5c; /* Menyamakan warna judul utama */
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
            background: white;
            border: none;
            border-radius: 16px;
            box-shadow: 0 5px 15px rgba(183, 110, 121, 0.05);
        }

        .product-card {
            border-radius: 16px;
            border: none;
            overflow: hidden;
            transition: 0.3s;
            background: white;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(183, 110, 121, 0.15);
        }

        footer {
            background: #b76e79;
        }
    </style>
</head>

<body>

<div class="text-center py-5 shadow-sm" style="background: linear-gradient(rgba(183, 110, 121, 0.82), rgba(141, 79, 92, 0.82)), url('assets/gambar/bg-bouquet.jpg') center/cover; color: white;">
    <div class="container py-3">
        <h1 class="fw-bold text-white text-uppercase">Sekar Bouquet 🌸</h1>
        <p class="text-light opacity-95 mb-4">
            Komponen & Rangkaian Bouquet Premium untuk Setiap Momen Spesialmu
        </p>
        <a href="produk.php" class="btn btn-light text-dark px-5 rounded-pill fw-bold shadow-sm">
            Lihat Katalog
        </a>
    </div>
</div>

<div class="container my-5">
    <div class="row text-center g-4">
        <div class="col-md-4">
            <div class="card feature-card p-4 shadow-sm">
                <i class="fa-solid fa-heart fs-1 mb-3" style="color: #d88b9c;"></i>
                <h5 class="fw-bold" style="color: #8d4f5c;">Bahan Berkualitas</h5>
                <p class="text-muted small">Variasi bunga, boneka, dan wrapping pilihan terbaik.</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card feature-card p-4 shadow-sm">
                <i class="fa-solid fa-gift fs-1 mb-3" style="color: #d88b9c;"></i>
                <h5 class="fw-bold" style="color: #8d4f5c;">Custom Bouquet</h5>
                <p class="text-muted small">Bebas pilih komponen sesuai selera kreatif kamu.</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card feature-card p-4 shadow-sm">
                <i class="fa-solid fa-truck fs-1 mb-3" style="color: #d88b9c;"></i>
                <h5 class="fw-bold" style="color: #8d4f5c;">Fast Delivery</h5>
                <p class="text-muted small">Pengiriman cepat area Jombang & sekitarnya.</p>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <h2 class="text-center mb-4 fw-bold">Bouquet Pilihan 💐</h2>

    <div class="row g-4">
        <?php
        $sql = "SELECT id_produk, nama_produk, harga_jual, gambar FROM produk LIMIT 4";
        $result = $koneksi->query($sql);

        if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
                $gambar_produk = !empty($row['gambar']) ? $row['gambar'] : 'default.jpg';
        ?>
        <div class="col-md-3">
            <div class="card product-card shadow-sm h-100">
                <img src="assets/gambar/<?php echo htmlspecialchars($gambar_produk); ?>"
                     class="card-img-top"
                     style="height:220px;object-fit:cover;"
                     alt="<?php echo htmlspecialchars($row['nama_produk']); ?>">

                <div class="card-body text-center d-flex flex-column">
                    <h5 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($row['nama_produk']); ?></h5>
                    <p class="fw-bold mb-3" style="color: #d88b9c;">
                        Rp <?php echo number_format($row['harga_jual'], 0, ',', '.'); ?>
                    </p>
                    <a href="produk.php" class="btn btn-main w-100 rounded-pill mt-auto">
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

<div class="text-center py-5 text-white" style="background: linear-gradient(135deg, #b76e79, #8d4f5c);">
    <h2 class="text-white fw-bold">Pesan Bouquet Sekarang 🌸</h2>
    <p class="opacity-90">Abadikan momen spesial dengan kombinasi kreasi terbaik</p>
    <a href="produk.php" class="btn btn-light px-5 rounded-pill fw-bold shadow-sm">
        Mulai Belanja
    </a>
</div>

<div class="container my-5 text-center py-3">
    <h2 class="fw-bold">Tentang Sekar Bouquet</h2>
    <p class="text-muted mx-auto" style="max-width: 700px;">
        Sekar Bouquet adalah pusat penyedia komponen dan kreasi bouquet kustom terpercaya yang menghadirkan rangkaian bunga, boneka, wrapping, dan aksesoris berkualitas untuk melengkapi momen kebahagiaan Anda.
    </p>
</div>

<?php include 'layout/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>