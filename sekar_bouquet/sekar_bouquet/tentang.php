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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #fff8f9; /* Disamakan dengan index dan produk */
            color: #4a373a;
        }

        h1, h2, h3, h4 {
            font-family: 'Playfair Display', serif;
            color: #8d4f5c; /* Disamakan dengan palet warna utama */
        }

        .hero {
            background: linear-gradient(rgba(141, 79, 92, 0.75), rgba(141, 79, 92, 0.75)),
                        url('assets/gambar/bg-bouquet.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 80px 20px;
            text-align: center;
        }

        .section-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(183, 110, 121, 0.1);
            border: none;
        }

        .icon-box {
            font-size: 40px;
            color: #d88b9c;
        }

        .btn-main {
            background: linear-gradient(135deg, #d88b9c, #b76e79);
            color: white;
            border: none;
            font-weight: 600;
            padding: 12px 30px;
            border-radius: 30px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(183, 110, 121, 0.2);
            text-decoration: none;
        }

        .btn-main:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(183, 110, 121, 0.3);
        }
    </style>
</head>

<body>

<?php include 'layout/header.php'; ?>

<div class="hero">
    <h1 class="display-5 fw-bold text-white">Tentang Sekar Bouquet 🌸</h1>
    <p class="lead text-light opacity-90">Wujudkan kreasi bouquet impianmu untuk setiap momen berharga</p>
</div>

<div class="container py-5">

    <div class="card section-card p-4 p-md-5 mb-4">
        <h2 class="mb-3 fw-bold">Cerita Kami</h2>
        <p class="text-muted" style="line-height: 1.8;">
            <strong>Sekar Bouquet</strong> hadir di wilayah Telang, Bangkalan sebagai solusi kreatif untuk mempermudah Anda merancang hadiah terbaik bagi orang-orang tercinta. Kami memahami bahwa setiap momen perayaan—mulai dari yudisium, seminar hasil, hingga wisuda akbar di Universitas Trunojoyo Madura—memerlukan apresiasi yang tulus dan berkesan. Oleh karena itu, kami bukan sekadar toko bunga biasa; kami adalah wadah personalisasi kebahagiaan Anda.
        </p>
        <p class="text-muted" style="line-height: 1.8;">
            Kami menyediakan segala kebutuhan pembuatan bouquet secara lengkap dan terstruktur—mulai dari bunga pilihan yang terjamin kesegarannya, boneka wisuda lucu berbagai ukuran, kertas wrapping premium (cellophane) beraneka warna, pita estetik, hingga kartu ucapan kustom. Di Sekar Bouquet, Anda dibebaskan memilih komponen sendiri untuk menciptakan karya seni hadiah yang unik, bermakna, dan tiada duanya.
        </p>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card section-card p-4 h-100 text-center">
                <div class="icon-box mb-3">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <h4 class="fw-bold" style="color: #8d4f5c;">Visi</h4>
                <p class="text-muted small mb-0">
                    Menjadi pusat penyedia komponen dan kreasi bouquet kustom terpercaya di Madura yang menginspirasi setiap orang untuk mengekspresikan kasih sayang dan apresiasi lewat karya hadiah yang personal.
                </p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card section-card p-4 h-100 text-center">
                <div class="icon-box mb-3">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <h4 class="fw-bold" style="color: #8d4f5c;">Misi</h4>
                <p class="text-muted small mb-0">
                    Menyediakan pilihan material bouquet berkualitas tinggi yang lengkap, menghadirkan sistem pemilihan komponen online yang transparan dan mudah, serta memberikan pelayanan pengiriman yang tepat waktu demi menjaga kepuasan pelanggan.
                </p>
            </div>
        </div>
    </div>

    <div class="text-center mt-5 py-4">
        <h3 class="mb-3 fw-bold">Mulai Rancang Komponen Bouquet-mu ✨</h3>
        <p class="text-muted mb-4">Pilih kombinasi bunga, boneka, wrapping, dan pita sesukamu.</p>
        <a href="<?php echo isset($_SESSION['username']) ? 'produk.php' : 'login.php'; ?>" class="btn btn-main btn-lg rounded-pill shadow-sm">
            <i class="fa-solid fa-basket-shopping me-2"></i>Mulai Kreasi di Katalog
        </a>
    </div>

</div>

<?php include 'layout/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>