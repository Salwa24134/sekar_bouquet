<?php
session_start();
include 'koneksi.php';

// WAJIB LOGIN
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <title>Katalog Bouquet - Sekar Bouquet</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <link rel="stylesheet"
          href="assets/css/style.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@700&display=swap"
          rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #fff8f9;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        h1 {
            font-family: 'Playfair Display', serif;
        }

        .promo-banner {
            background:
                linear-gradient(rgba(183, 110, 121, 0.82),
                rgba(141, 79, 92, 0.82)),
                url('assets/gambar/bg-bouquet.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 90px 0;
            margin-bottom: 60px;
        }

        .promo-title {
            font-size: 3rem;
            font-weight: 700;
        }

        .promo-subtitle {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        .product-card {
            border: none;
            border-radius: 24px;
            overflow: hidden;
            transition: 0.35s;
            background: white;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 18px 35px rgba(183, 110, 121, 0.2);
        }

        .product-image {
            height: 280px;
            object-fit: cover;
        }

        .card-body {
            padding: 24px;
        }

        .product-name {
            font-weight: 700;
            color: #8d4f5c;
            font-size: 1.2rem;
        }

        .product-price {
            color: #d88b9c;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .qty-input {
            width: 80px;
            text-align: center;
            border-radius: 12px;
            border: 2px solid #e6b8c1;
            font-weight: 600;
        }

        .custom-check {
            transform: scale(1.3);
            cursor: pointer;
        }

        .btn-main {
            background: linear-gradient(135deg, #d88b9c, #b76e79);
            color: white;
            border: none;
            border-radius: 16px;
            padding: 14px 30px;
            font-weight: 600;
        }
    </style>

</head>

<body>

<?php include 'layout/header.php'; ?>

<!-- BANNER -->
<div class="promo-banner text-center shadow">
    <div class="container">
        <h1 class="promo-title text-uppercase mb-3">
            Koleksi Bouquet Spesial 🌸
        </h1>
        <p class="promo-subtitle mb-0">
            Temukan rangkaian bunga terbaik untuk setiap momen 💐
        </p>
    </div>
</div>

<!-- PRODUK -->
<div class="container mb-5 flex-grow-1">

    <form method="post" action="checkout.php">

        <div class="row justify-content-center g-4">

            <?php
            $sql = "SELECT * FROM produk";
            $result = sqlsrv_query($koneksi, $sql);

            if ($result === false) {
                die(print_r(sqlsrv_errors(), true));
            }

            if (sqlsrv_has_rows($result)) {

                while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
            ?>

                <div class="col-lg-4 col-md-6 col-sm-12">

                    <div class="card product-card h-100 shadow-sm">

                        <img src="assets/gambar/<?php echo $row['gambar']; ?>"
                             class="card-img-top product-image"
                             alt="<?php echo $row['nama']; ?>">

                        <div class="card-body d-flex flex-column text-center">

                            <h5 class="product-name mb-2">
                                <?php echo $row['nama']; ?>
                            </h5>

                            <p class="product-price mb-4">
                                Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?>
                            </p>

                            <div class="mt-auto">

                                <div class="d-flex justify-content-center align-items-center mb-3 py-3 border rounded-4 bg-light shadow-sm">

                                    <input class="form-check-input custom-check me-2"
                                           type="checkbox"
                                           name="produk_id[]"
                                           value="<?php echo $row['id']; ?>">

                                    <label class="fw-bold">
                                        Pilih Bouquet
                                    </label>

                                </div>

                                <div class="d-flex justify-content-center align-items-center gap-3">

                                    <span class="fw-semibold text-muted">Jumlah</span>

                                    <input type="number"
                                           name="jumlah[<?php echo $row['id']; ?>]"
                                           class="form-control qty-input"
                                           min="1"
                                           value="1">

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            <?php
                }
            } else {
                echo "<div class='text-center text-muted'>Belum ada produk</div>";
            }
            ?>

        </div>

        <div class="text-center mt-5">

            <button type="submit" class="btn btn-main btn-lg px-5 shadow">
                <i class="fa-solid fa-bag-shopping me-2"></i>
                Lanjut ke Checkout
            </button>

        </div>

    </form>

</div>

<?php include 'layout/footer.php'; ?>

</body>
</html>