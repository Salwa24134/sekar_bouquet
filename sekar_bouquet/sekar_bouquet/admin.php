<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';

// hanya admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

/* =========================
   STATISTIK DASHBOARD (MySQL)
========================= */

// total user
$user = $koneksi->query("SELECT COUNT(*) as total FROM users");
$totalUser = $user->fetch_assoc()['total'];

// total produk
$produk = $koneksi->query("SELECT COUNT(*) as total FROM produk");
$totalProduk = $produk->fetch_assoc()['total'];

// total pesanan (Disesuaikan dari pesanan_header menjadi pesanan)
$pesanan = $koneksi->query("SELECT COUNT(*) as total FROM pesanan");
$totalPesanan = $pesanan->fetch_assoc()['total'];

// pesanan terbaru (Menggunakan JOIN ke tabel pelanggan agar data nama & email bisa muncul)
$sqlRecent = "
    SELECT 
        p.id_pesanan, 
        pl.nama, 
        pl.email, 
        p.metode_pembayaran, 
        p.total, 
        p.status 
    FROM pesanan p
    JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    ORDER BY p.id_pesanan DESC 
    LIMIT 5
";

$recent = $koneksi->query($sqlRecent);

if ($recent === false) {
    die("Error Query: " . $koneksi->error);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Sekar Bouquet</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
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

        .sidebar {
            width: 250px;
            height: 100vh;
            background: linear-gradient(135deg, #b76e79, #8d4f5c);
            position: fixed;
            padding: 20px;
            color: white;
        }

        .sidebar h3 {
            color: white;
        }

        .sidebar a {
            display: block;
            color: white;
            padding: 10px;
            text-decoration: none;
            margin-bottom: 10px;
            border-radius: 10px;
        }

        .sidebar a:hover {
            background: rgba(255,255,255,0.2);
        }

        .main {
            margin-left: 260px;
            padding: 30px;
        }

        .card-box {
            border: none;
            border-radius: 18px;
            box-shadow: 0 10px 25px rgba(183,110,121,0.15);
            transition: 0.3s;
        }

        .card-box:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 30px;
            color: #b76e79;
        }

        .table thead {
            background: #b76e79;
            color: white;
        }

        .badge-status {
            background: #b76e79;
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
        }
    </style>
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="main">

    <h1 class="mb-4">Dashboard Admin 🌸</h1>

    <p>Selamat datang, <b><?php echo htmlspecialchars($_SESSION['username']); ?></b></p>

    <div class="row g-4 mb-5">

        <div class="col-md-4">
            <div class="card card-box p-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5>Total User</h5>
                        <h3><?php echo $totalUser; ?></h3>
                    </div>
                    <i class="fa fa-users stat-icon"></i>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-box p-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5>Total Produk</h5>
                        <h3><?php echo $totalProduk; ?></h3>
                    </div>
                    <i class="fa fa-box stat-icon"></i>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-box p-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5>Total Pesanan</h5>
                        <h3><?php echo $totalPesanan; ?></h3>
                    </div>
                    <i class="fa fa-receipt stat-icon"></i>
                </div>
            </div>
        </div>

    </div>

    <div class="card card-box p-4">

        <h4 class="mb-3">Pesanan Terbaru</h4>

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Pelanggan</th>
                        <th>Email</th>
                        <th>Metode Pembayaran</th>
                        <th>Total Harga</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                    <?php 
                    if ($recent->num_rows > 0) {
                        while ($row = $recent->fetch_assoc()) { 
                    ?>
                    <tr>
                        <td>#<?php echo $row['id_pesanan']; ?></td>
                        <td><?php echo htmlspecialchars($row['nama']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['metode_pembayaran'] ?? '-'); ?></td>
                        <td>Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></td>
                        <td>
                            <span class="badge badge-status">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php 
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center text-muted py-3'>Belum ada transaksi pesanan terbaru</td></tr>";
                    }
                    ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>
</html>