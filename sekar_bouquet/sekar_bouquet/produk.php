<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';

// WAJIB LOGIN UNTUK MELIHAT KATALOG
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$kategori_katalog = [
    1 => 'Bunga',
    3 => 'Boneka',
    2 => 'Wrapping',
    4 => 'Pita',
    5 => 'Aksesoris'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Bouquet - Sekar Bouquet</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #fff8f9;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        h1, .category-title {
            font-family: 'Playfair Display', serif;
        }
        .category-block {
            margin-bottom: 40px;
        }
        .category-title {
            color: #8d4f5c;
            font-weight: 700;
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }
        .category-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -4px;
            width: 40%;
            height: 3px;
            background: #b76e79;
            border-radius: 2px;
        }
        .promo-banner {
            background: linear-gradient(rgba(183, 110, 121, 0.82), rgba(141, 79, 92, 0.82)), url('assets/gambar/bg-bouquet.jpg') center/cover;
            color: white;
            padding: 50px 0;
            margin-bottom: 40px;
        }
        .product-card {
            border: none;
            border-radius: 18px;
            overflow: hidden;
            transition: 0.35s;
            background: white;
        }
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(183, 110, 121, 0.15);
        }
        .product-image {
            height: 180px;
            object-fit: cover;
        }
        .qty-input {
            width: 60px;
            text-align: center;
            border-radius: 8px;
            border: 2px solid #e6b8c1;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .btn-main {
            background: linear-gradient(135deg, #d88b9c, #b76e79);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 10px 24px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<?php include 'layout/header.php'; ?>

<div class="promo-banner text-center shadow-sm">
    <div class="container">
        <h1 class="fw-bold text-uppercase mb-2 text-white">Koleksi Bouquet Spesial 🌸</h1>
        <p class="mb-0 opacity-95 text-light">Temukan rangkaian komponen terbaik untuk kreasi bouquet-mu 💐</p>
    </div>
</div>

<div class="container mb-5 flex-grow-1">
    <form method="post" action="tambah_keranjang_banyak.php">
        <?php 
        $ada_produk_total = false;
        foreach ($kategori_katalog as $id_kategori => $nama_tampilan) {
            $sql = "SELECT id_produk, nama_produk, harga_jual, gambar FROM produk WHERE id_kategori = ? ORDER BY id_produk DESC";
            $stmt = $koneksi->prepare($sql);
            $stmt->bind_param("i", $id_kategori);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $ada_produk_total = true;
        ?>
                <div class="category-block">
                    <h3 class="category-title"><?= htmlspecialchars($nama_tampilan); ?></h3>
                    <div class="row g-3">
                        <?php 
                        while ($row = $result->fetch_assoc()) {
                            $gambar_produk = !empty($row['gambar']) ? $row['gambar'] : 'default.jpg';
                        ?>
                            <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                                <div class="card product-card h-100 shadow-sm">
                                    <img src="assets/gambar/<?php echo htmlspecialchars($gambar_produk); ?>" class="card-img-top product-image" alt="Produk">
                                    <div class="card-body d-flex flex-column text-center">
                                        <h6 class="fw-bold mb-1 text-dark text-truncate"><?php echo htmlspecialchars($row['nama_produk']); ?></h6>
                                        <p class="small fw-bold mb-3" style="color:#d88b9c;">Rp <?php echo number_format($row['harga_jual'], 0, ',', '.'); ?></p>
                                        
                                        <div class="mt-auto">
                                            <div class="d-flex justify-content-center align-items-center mb-2 py-2 border rounded-3 bg-light shadow-sm">
                                                <input class="form-check-input me-2" type="checkbox" name="produk_id[]" value="<?php echo $row['id_produk']; ?>">
                                                <label class="fw-bold small text-muted">Pilih Item</label>
                                            </div>
                                            <div class="d-flex justify-content-center align-items-center gap-2">
                                                <span class="small text-muted">Jumlah</span>
                                                <input type="number" name="jumlah[<?php echo $row['id_produk']; ?>]" class="form-control qty-input p-1" min="1" value="1">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php 
                        } 
                        ?>
                    </div>
                </div>
        <?php 
            }
            $stmt->close();
        } 
        if (!$ada_produk_total) {
            echo "<div class='text-center text-muted py-5'><h5>Belum ada produk tersedia di katalog</h5></div>";
        }
        ?>

        <?php if ($ada_produk_total): ?>
            <div class="text-center mt-5">
                <button type="submit" class="btn btn-main btn-lg px-5 shadow">
                    <i class="fa-solid fa-basket-shopping me-2"></i> Masukkan ke Keranjang Belanja
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php include 'layout/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>