<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';
include 'layout/header.php';

/* ==========================================================================
   ENGINE SINKRONISASI CURSOR: AMBIL DATA VOUCHER KHUSUS USER YANG SEDANG LOGIN
   ========================================================================== */
$data_voucher = null;
if (isset($_SESSION['id_pelanggan'])) {
    $id_user_login = $_SESSION['id_pelanggan'];
    
    // Query memeriksa apakah user ini mendapatkan voucher aktif hasil olahan Cursor
    $query_voucher = $koneksi->prepare("
        SELECT * FROM voucher_pelanggan 
        WHERE id_pelanggan = ? AND status_aktif = 'Aktif'
        ORDER BY id_voucher DESC LIMIT 1
    ");
    $query_voucher->bind_param("i", $id_user_login);
    $query_voucher->execute();
    $result_voucher = $query_voucher->get_result();
    $data_voucher = $result_voucher->fetch_assoc();
    $query_voucher->close();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sekar Bouquet - Toko Bouquet Premium</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #fff8f9;
            color: #4a373a;
        }

        h1, h2, h3, h4 {
            font-family: 'Playfair Display', serif;
            color: #8d4f5c; 
        }

        /* --- HERO BANNER --- */
        .hero-section {
            background: linear-gradient(rgba(141, 79, 92, 0.85), rgba(183, 110, 121, 0.85)), url('assets/gambar/bg-bouquet.jpg') center/cover; 
            color: white;
            padding: 90px 0;
            border-bottom-left-radius: 40px;
            border-bottom-right-radius: 40px;
            box-shadow: 0 15px 30px rgba(183, 110, 121, 0.05);
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
        }

        .btn-main:hover {
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(183, 110, 121, 0.3);
        }

        .product-card {
            border-radius: 24px;
            border: none;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            background: white;
            box-shadow: 0 8px 25px rgba(183, 110, 121, 0.04);
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 35px rgba(183, 110, 121, 0.12);
        }

        /* --- SECTION TRIVIA / INFORMASI --- */
        .trivia-section {
            background: #fdf0f2;
            border-radius: 30px;
            padding: 50px 40px;
        }

        .icon-box-circle {
            width: 70px;
            height: 70px;
            background: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 8px 20px rgba(183, 110, 121, 0.08);
            color: #b76e79;
            font-size: 1.8rem;
        }
    </style>
</head>

<body>

<div class="hero-section text-center">
    <div class="container">
        <span class="badge bg-white text-dark px-3 py-2 rounded-pill mb-3 fw-bold shadow-sm" style="color: #b76e79 !important;">✨ Florist & Gift Premium No.1 Telang</span>
        <h1 class="display-4 fw-bold text-white text-uppercase mb-3" style="letter-spacing: 2px;">Sekar Bouquet 🌸</h1>
        <p class="lead text-light opacity-95 mb-4 px-3 mx-auto" style="max-width: 650px; font-weight: 300;">
            Mengabadikan perasaan dan momen berhargamu melalui keanggunan rangkaian bunga premium kustom kualitas terbaik di sekitar lingkungan Kampus UTM.
        </p>
        <a href="<?php echo isset($_SESSION['username']) ? 'produk.php' : 'login.php'; ?>" class="btn btn-light px-5 py-3 rounded-pill fw-bold text-dark shadow">
            <i class="fa fa-shopping-bag me-2" style="color: #b76e79;"></i> Jelajahi Katalog Premium
        </a>
    </div>
</div>

<?php if (!empty($data_voucher)): ?>
<div class="container mt-4 shadow-sm" style="max-width: 850px;">
    <div class="card border-0" style="background: linear-gradient(135deg, #fce4ec, #f8bbd0); border-radius: 16px;">
        <div class="card-body p-3 px-4">
            <div class="row align-items-center">
                
                <div class="col-md-7 d-flex align-items-center text-start gap-3">
                    <div class="d-none d-sm-flex align-items-center justify-content-center text-white" 
                         style="width: 55px; height: 55px; background: rgba(141, 79, 92, 0.15); border-radius: 12px; flex-shrink: 0;">
                        <i class="fa fa-gift fs-4" style="color: #8d4f5c;"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0" style="color: #8d4f5c; font-family: 'Playfair Display', serif; font-size: 1.15rem;">
                            🎉 Kejutan Spesial Loyalitas!
                        </h5>
                        <p class="text-muted mb-0 small" style="font-size: 0.85rem;">
                            Kamu berhak atas voucher belanja kustom 10% karena transaksi hebatmu bulan ini.
                        </p>
                    </div>
                </div>
                
                <div class="col-md-5 text-md-end text-start mt-3 mt-md-0 d-flex flex-column align-items-md-end justify-content-center">
                    <div class="bg-white px-3 py-1 mb-1 shadow-sm" style="border: 2px dashed #b76e79; border-radius: 8px; display: inline-block;">
                        <code style="font-size: 1.25rem; color: #b76e79; font-weight: 700; font-family: 'Poppins', sans-serif;">
                            <?= htmlspecialchars($data_voucher['kode_voucher']); ?>
                        </code>
                    </div>
                    <span class="fw-bold text-success small" style="font-size: 0.9rem;">
                        Potongan: Rp <?= number_format($data_voucher['potongan_harga'], 0, ',', '.'); ?>
                    </span>
                </div>

            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container my-5 pt-2">
    <?php if (isset($_SESSION['username'])): ?>
        <h2 class="text-center mb-2 fw-bold">Koleksi Terfavorit Bulan Ini 💐</h2>
        <p class="text-center text-muted small mb-5">Rangkaian bunga terlaris yang paling banyak dipesan pelanggan</p>

        <div class="row g-4">
            <?php
            $sql = "SELECT id_produk, nama_produk, harga_jual, gambar FROM produk LIMIT 4";
            $result = $koneksi->query($sql);

            if ($result && $result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
                    $gambar_produk = !empty($row['gambar']) ? $row['gambar'] : 'default.jpg';
            ?>
            <div class="col-md-3">
                <div class="card product-card h-100">
                    <img src="assets/gambar/<?php echo htmlspecialchars($gambar_produk); ?>"
                         class="card-img-top"
                         style="height:240px;object-fit:cover;"
                         alt="<?php echo htmlspecialchars($row['nama_produk']); ?>">

                    <div class="card-body text-center d-flex flex-column p-4">
                        <h5 class="fw-bold mb-2 text-dark" style="font-size: 1.15rem;"><?php echo htmlspecialchars($row['nama_produk']); ?></h5>
                        <p class="fw-bold mb-4" style="color: #b76e79; font-size: 1.2rem;">
                            Rp <?php echo number_format($row['harga_jual'], 0, ',', '.'); ?>
                        </p>
                        <a href="produk.php" class="btn btn-main w-100 mt-auto">
                            Pesan Sekarang <i class="fa fa-arrow-right small ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php
                endwhile;
            endif;
            ?>
        </div>
    <?php else: ?>
        <div class="card text-center p-5 mx-auto shadow-sm border-0" style="max-width: 750px; background: #fff; border-radius: 20px;">
            <div class="card-body">
                <i class="fa-solid fa-lock text-muted mb-3" style="font-size: 2.5rem; color: #b76e79 !important;"></i>
                <h4 class="fw-bold mb-2">Ingin Melihat Koleksi Cantik Kami?</h4>
                <p class="text-muted small mb-4 mx-auto" style="max-width: 500px;">
                    Silakan masuk atau daftarkan akun Anda terlebih dahulu untuk membuka katalog lengkap buket wisuda premium Sekar Bouquet khusus area Telang & Kamal.
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="login.php" class="btn btn-main px-4 py-2">Login</a>
                    <a href="register.php" class="btn btn-outline-secondary rounded-pill px-4 py-2">Daftar Akun</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="container my-5">
    <div class="trivia-section shadow-sm">
        <h2 class="text-center fw-bold mb-2">Mengapa Memilih Sekar Bouquet? 🌸</h2>
        <p class="text-center text-muted small mb-5 mx-auto" style="max-width: 600px;">Di balik keanggunan kelopak bunga, ada dedikasi tinggi dan filosofi mendalam yang kami rangkai khusus untuk kepuasan Anda.</p>
        
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="icon-box-circle">
                    <i class="fa-solid fa-seedling"></i>
                </div>
                <h5 class="fw-bold" style="color: #8d4f5c;">100% Bunga Segar & Higienis</h5>
                <p class="text-muted small px-2">Setiap tangkai bunga di Sekar Bouquet dipilih langsung dari perkebunan lokal terbaik setiap pagi untuk memastikan kesegaran optimal bertahan lama di tangan Anda.</p>
            </div>
            
            <div class="col-md-4">
                <div class="icon-box-circle">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <h5 class="fw-bold" style="color: #8d4f5c;">Filosofi Desain Eksklusif</h5>
                <p class="text-muted small px-2">Kombinasi warna kain wrapping premium (Cellophane) disesuaikan secara psikologis dengan tema momen Anda—baik itu wisuda UTM, lamaran, maupun kado sidang.</p>
            </div>
            
            <div class="col-md-4">
                <div class="icon-box-circle">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
                <h5 class="fw-bold" style="color: #8d4f5c;">Pengiriman Cepat Area Kampus</h5>
                <p class="text-muted small px-2">Kami melayani sistem pengiriman instan kilat langsung ke kos atau lokasi gedung wisuda di area Kamal, Bangkalan, dan sekitarnya tepat waktu.</p>
            </div>
        </div>
    </div>
</div>

<div class="text-center py-5 text-white shadow-inner mt-5" style="background: linear-gradient(135deg, #b76e79, #8d4f5c); border-top-left-radius: 40px; border-top-right-radius: 40px;">
    <h2 class="text-white fw-bold mb-2">Siap Memberikan Hadiah Terbaik?</h2>
    <p class="opacity-90 mb-4 small">Bikin momen wisuda dan perayaan orang tersayang jadi tak terlupakan.</p>
    <a href="<?php echo isset($_SESSION['username']) ? 'produk.php' : 'login.php'; ?>" class="btn btn-light px-5 py-3 rounded-pill fw-bold shadow-sm text-uppercase" style="letter-spacing: 1px; color: #8d4f5c;">
        Mulai Belanja Sekarang
    </a>
</div>

<?php include 'layout/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>