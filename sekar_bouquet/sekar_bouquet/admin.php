<?php
session_start();
include 'koneksi.php';

// hanya admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

/* =========================
   STATISTIK DASHBOARD
========================= */

// total user
$user = sqlsrv_query($koneksi, "SELECT COUNT(*) as total FROM users");
$totalUser = sqlsrv_fetch_array($user, SQLSRV_FETCH_ASSOC)['total'];

// total produk
$produk = sqlsrv_query($koneksi, "SELECT COUNT(*) as total FROM produk");
$totalProduk = sqlsrv_fetch_array($produk, SQLSRV_FETCH_ASSOC)['total'];

// total pesanan
$pesanan = sqlsrv_query($koneksi, "SELECT COUNT(*) as total FROM pesanan_header");
$totalPesanan = sqlsrv_fetch_array($pesanan, SQLSRV_FETCH_ASSOC)['total'];

// pesanan terbaru
$sqlRecent = "
SELECT TOP 5 *
FROM pesanan_header
ORDER BY id DESC
";

$recent = sqlsrv_query($koneksi, $sqlRecent);
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
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h3 class="mb-4">🌸 Sekar Admin</h3>

    <a href="admin.php"><i class="fa fa-home me-2"></i> Dashboard</a>
    <a href="produk_admin.php"><i class="fa fa-box me-2"></i> Produk</a>
    <a href="pesanan_admin.php"><i class="fa fa-receipt me-2"></i> Pesanan</a>
    <a href="users_admin.php"><i class="fa fa-users me-2"></i> User</a>
    <a href="logout.php"><i class="fa fa-sign-out me-2"></i> Logout</a>
</div>

<!-- MAIN -->
<div class="main">

    <h1 class="mb-4">Dashboard Admin 🌸</h1>

    <p>Selamat datang, <b><?php echo $_SESSION['username']; ?></b></p>

    <!-- STATISTIK -->
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

    <!-- TABEL PESANAN -->
    <div class="card card-box p-4">

        <h4 class="mb-3">Pesanan Terbaru</h4>

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Pembayaran</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                    <?php while ($row = sqlsrv_fetch_array($recent, SQLSRV_FETCH_ASSOC)) { ?>

                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><?php echo $row['nama']; ?></td>
                        <td><?php echo $row['email']; ?></td>
                        <td><?php echo $row['pembayaran']; ?></td>
                        <td>Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></td>
                        <td>
                            <span class="badge badge-status">
                                <?php echo $row['status']; ?>
                            </span>
                        </td>
                    </tr>

                    <?php } ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>
</html>