<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Menyertakan koneksi MySQLi jika sewaktu-waktu dibutuhkan data dinamis
include 'koneksi.php'; 
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - Sekar Bouquet</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #fff4f7;
        }

        h1, h2, h3 {
            font-family: 'Playfair Display', serif;
            color: #b76e79;
        }

        .hero {
            background: linear-gradient(rgba(183,110,121,0.75), rgba(183,110,121,0.75)),
                        url('assets/gambar/bg-bouquet.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 20px;
            text-align: center;
        }

        .section-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(183,110,121,0.12);
            border: none;
        }

        .icon-box {
            font-size: 40px;
            color: #b76e79;
        }

        .btn-main {
            background: linear-gradient(135deg, #d88b9c, #b76e79);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            text-decoration: none;
        }

        .btn-main:hover {
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>

<?php include 'layout/header.php'; ?>

<div class="hero">
    <h1 class="display-4 fw-bold">Tentang Sekar Bouquet 🌸</h1>
    <p class="lead">Rangkaian bunga penuh makna untuk setiap momen spesialmu</p>
</div>

<div class="container py-5">

    <div class="card section-card p-4 mb-4">
        <h2 class="mb-3">Cerita Kami</h2>
        <p>
            Sekar Bouquet hadir untuk membawa keindahan bunga ke dalam setiap momen penting kehidupan.
            Dari hadiah ulang tahun, wisuda, hingga perayaan cinta — kami percaya setiap bunga punya cerita.
        </p>
        <p>
            Kami bekerja sama dengan perangkai bunga lokal terbaik untuk memastikan setiap bouquet
            dibuat dengan cinta, detail, dan kualitas terbaik.
        </p>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card section-card p-4 h-100 text-center">
                <div class="icon-box mb-3">
                    <i class="fa-solid fa-eye"></i>
                </div>
                <h4>Visi</h4>
                <p>
                    Menjadi toko bouquet terbaik yang menghadirkan kebahagiaan melalui rangkaian bunga di seluruh Indonesia.
                </p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card section-card p-4 h-100 text-center">
                <div class="icon-box mb-3">
                    <i class="fa-solid fa-bullseye"></i>
                </div>
                <h4>Misi</h4>
                <p>
                    Memberikan produk berkualitas tinggi, pelayanan cepat, dan pengalaman belanja yang mudah dan menyenangkan.
                </p>
            </div>
        </div>
    </div>

    <div class="text-center mt-5">
        <h3 class="mb-3">Siap memilih bouquet favoritmu? 🌷</h3>
        <a href="produk.php" class="btn btn-main btn-lg">
            Lihat Katalog
        </a>
    </div>

</div>

<?php include 'layout/footer.php'; ?>

</body>
</html>