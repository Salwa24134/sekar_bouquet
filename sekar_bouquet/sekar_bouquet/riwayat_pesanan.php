<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'koneksi.php';

// Menyesuaikan key session dengan halaman proses_pesanan (id_pelanggan / id_user / id)
$id_user = $_SESSION['id_pelanggan'] ?? $_SESSION['id_user'] ?? $_SESSION['id'] ?? null;

if (!$id_user) {
    header("Location: login.php");
    exit();
}

$sql = "SELECT * FROM pesanan WHERE id_pelanggan = ? ORDER BY id_pesanan DESC";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan - Sekar Bouquet</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            background: #fff5f7;
            font-family: 'Poppins', sans-serif; /* Perbaikan font fallback */
        }

        /* GRID */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        /* CARD */
        .card-order {
            background: #fff;
            border: none;
            border-radius: 18px;
            box-shadow: 0 10px 25px rgba(183, 110, 121, 0.08);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        .card-order:hover {
            transform: translateY(-5px);
        }

        /* IMAGE */
        .header-img {
            width: 100%;
            height: 140px;
            object-fit: cover;
        }

        /* CONTENT */
        .content {
            padding: 20px;
            font-size: 13px;
        }

        .badge {
            font-size: 11px;
            padding: 6px 10px;
            border-radius: 8px;
            font-weight: 500;
        }

        .pending { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .proses { background: #cce5ff; color: #004085; border: 1px solid #b8daff; }
        .selesai { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .dibatalkan { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* PRODUCT */
        .product-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            margin-bottom: 10px;
        }

        .product-img {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #eee;
        }

        .small-text {
            font-size: 12px;
            color: #777;
        }

        /* BUTTON FIX */
        .btn-small {
            font-size: 13px;
            border-radius: 10px;
            padding: 8px 12px;
        }
        .btn-custom-pink {
            background: #b76e79;
            color: white;
            border: none;
        }
        .btn-custom-pink:hover {
            background: #a35c67;
            color: white;
        }
    </style>
</head>

<body>

<?php include 'layout/header.php'; ?>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold text-dark" style="font-family: 'Playfair Display', serif;">Riwayat Pesanan 🌸</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm btn-small">
            <i class="fa fa-arrow-left me-1"></i> Beranda
        </a>
    </div>

    <div class="grid">

    <?php while($row = $result->fetch_assoc()): ?>
    <?php
        $id = $row['id_pesanan'];
        $status = strtolower(trim($row['status']));

        // Penentuan kelas badge warna yang lebih estetik pastel
        if ($status == 'selesai') {
            $badge = "selesai";
        } elseif ($status == 'diproses') {
            $badge = "proses";
        } elseif ($status == 'dibatalkan') {
            $badge = "dibatalkan";
        } else {
            $badge = "pending";
        }

        // Ambil data produk pertama sebagai gambar banner card
        $produk = $koneksi->query("
            SELECT pr.nama_produk, pr.gambar, dp.jumlah
            FROM detail_pesanan dp
            JOIN produk pr ON pr.id_produk = dp.id_produk
            WHERE dp.id_pesanan = $id LIMIT 1
        ");
        $first = $produk->fetch_assoc();
    ?>

        <div class="card-order">
            <?php if(!empty($first['gambar'])): ?>
                <img src="assets/gambar/<?= $first['gambar']; ?>" class="header-img">
            <?php else: ?>
                <div class="bg-secondary-subtle header-img d-flex align-items-center justify-content-center">
                    <i class="fa fa-image text-muted fs-3"></i>
                </div>
            <?php endif; ?>

            <div class="content">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark fs-6">#<?= $id; ?></span>
                    <span class="badge <?= $badge; ?>"><?= $row['status']; ?></span>
                </div>

                <div class="small-text mt-1">
                    <i class="fa fa-calendar-alt me-1"></i> <?= date('d M Y, H:i', strtotime($row['tanggal'])); ?>
                </div>

                <hr class="my-3 opacity-25">

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
                        <div class="flex-grow-1">
                            <div class="fw-medium text-dark"><?= $p['nama_produk']; ?></div>
                            <div class="small-text">x<?= $p['jumlah']; ?></div>
                        </div>
                    </div>
                <?php endwhile; ?>

                <hr class="my-3 opacity-25">

                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">Total Belanja:</span>
                    <span class="fw-bold fs-6 text-dark">Rp <?= number_format($row['total'],0,',','.'); ?></span>
                </div>

                <div class="mt-3">
                    <?php if($status == 'selesai'): ?>
                        <a href="nota.php?id=<?= $id; ?>" class="btn btn-success btn-sm w-100 btn-small d-flex align-items-center justify-content-center gap-1">
                            <i class="fa fa-print"></i> Cetak Nota Resmi
                        </a>
                    <?php elseif($status == 'dibatalkan'): ?>
                        <button class="btn btn-secondary btn-sm w-100 btn-small" disabled>Pesanan Hangus</button>
                    <?php else: ?>
                        <div class="alert alert-warning py-2 px-3 m-0 small text-center rounded-3">
                            <i class="fa fa-hourglass-half me-1"></i> Menunggu Verifikasi Admin
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php endwhile; ?>

    </div>
</div>

<?php include 'layout/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>