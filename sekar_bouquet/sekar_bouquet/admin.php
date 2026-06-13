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
            margin: 0;
            padding: 0;
        }
        h2, h3, h4, h5 { 
            font-family: 'Playfair Display', serif; 
            color: #b76e79; 
        }

        /* --- STYLE SIDEBAR SINKRON (SAMA RATA) + SCROLLABLE --- */
        .sidebar {
            width: 260px;
            height: 100vh;
            background: #b26a7a; /* Warna mauve/pink gelap sesuai gambar */
            position: fixed;
            top: 0;
            left: 0;
            padding: 30px 24px;
            color: white;
            z-index: 1000;
            
            /* FIX 1: Mengaktifkan scroll vertikal jika menu meluber melebihi tinggi layar */
            overflow-y: auto; 
        }

        /* FIX 2: Modifikasi Kustom Desain Batang Scrollbar Sidebar Agar Cantik & Elegan */
        .sidebar::-webkit-scrollbar {
            width: 6px; /* Ketebalan scrollbar tipis minimalis */
        }
        .sidebar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05); /* Latar belakang track transparan */
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.25); /* Warna pill scrollbar putih transparan masi senada */
            border-radius: 10px;
        }
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.45); /* Warna sedikit lebih terang saat disorot */
        }

        .sidebar h3 {
            color: white !important;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 2rem !important;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            color: #f5e6e8; /* Teks putih agak soft */
            padding: 12px 16px;
            text-decoration: none;
            margin-bottom: 12px;
            border-radius: 14px;
            font-weight: 500;
            font-size: 1.05rem;
            transition: all 0.2s ease;
        }
        .sidebar a i {
            font-size: 1.2rem;
            width: 30px; /* Jarak icon seragam */
        }
        /* Efek hover lembut saat kursor menyentuh menu */
        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        /* --- STYLE KONTEN UTAMA --- */
        .main { 
            margin-left: 260px; 
            padding: 40px; 
        }
        .card-box { 
            border: none; 
            border-radius: 18px; 
            box-shadow: 0 10px 25px rgba(183,110,121,0.08); 
        }
        .bg-gradient-pink { 
            background: linear-gradient(135deg, #d88b9c, #b76e79); 
            color: white; 
        }
        .btn-main { 
            background: linear-gradient(135deg, #d88b9c, #b76e79); 
            color: white; 
            border: none; 
            border-radius: 12px;
            padding: 10px 20px;
        }
        .btn-main:hover { 
            color: white; 
            opacity: 0.9; 
        }
        .table-responsive {
            background: white;
            border-radius: 12px;
            padding: 10px;
        }
    </style>
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="main">

    <h2 class="mb-4">Dashboard Admin 🌸</h2>

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