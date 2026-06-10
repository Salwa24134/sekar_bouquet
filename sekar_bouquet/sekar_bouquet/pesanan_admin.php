<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';

/* =========================
   PROTEKSI ADMIN
========================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

/* =========================
   UPDATE STATUS PESANAN (MySQLi)
========================= */
if (isset($_GET['status']) && isset($_GET['id'])) {

    $id = (int)$_GET['id']; // Casting ke int untuk keamanan tambahan
    $status = $_GET['status'];

    // Menghindari SQL injection menggunakan Prepared Statements MySQLi
    $stmtUpdate = $koneksi->prepare("UPDATE pesanan_header SET status = ? WHERE id = ?");
    $stmtUpdate->bind_param("si", $status, $id);
    $stmtUpdate->execute();
    $stmtUpdate->close();

    header("Location: pesanan_admin.php");
    exit();
}

/* =========================
   DATA PESANAN (MySQLi)
========================= */
$sql = "
    SELECT *
    FROM pesanan_header
    ORDER BY id DESC
";

$data = $koneksi->query($sql);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Admin - Sekar Bouquet</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #fff4f7;
        }

        h2, h3 {
            font-family: 'Playfair Display', serif;
            color: #b76e79;
        }

        /* SIDEBAR */
        .sidebar {
            width: 250px;
            height: 100vh;
            background: linear-gradient(135deg, #b76e79, #8d4f5c);
            position: fixed;
            padding: 20px;
            color: white;
        }

        .sidebar a {
            display: block;
            color: white;
            padding: 10px;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .sidebar a:hover {
            background: rgba(255,255,255,0.2);
        }

        /* MAIN */
        .main {
            margin-left: 260px;
            padding: 30px;
        }

        .card-box {
            border: none;
            border-radius: 18px;
            box-shadow: 0 10px 25px rgba(183,110,121,0.15);
        }

        /* STATUS */
        .badge-wait {
            background: #ffc107;
            color: black;
        }

        .badge-process {
            background: #0d6efd;
            color: white;
        }

        .badge-done {
            background: #198754;
            color: white;
        }

        .btn-main {
            background: linear-gradient(135deg, #d88b9c, #b76e79);
            color: white;
            border: none;
        }

        .btn-main:hover {
            color: white;
        }
    </style>
</head>

<body>

<div class="sidebar">
    <h3 class="mb-4">🌸 Sekar Admin</h3>
    <a href="admin.php">Dashboard</a>
    <a href="produk_admin.php">Produk</a>
    <a href="pesanan_admin.php">Pesanan</a>
    <a href="users_admin.php">User</a>
    <a href="logout.php">Logout</a>
</div>

<div class="main">

    <h2 class="mb-4">Manajemen Pesanan 📦</h2>

    <div class="card card-box p-4">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Pembayaran</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Bukti</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($data && $data->num_rows > 0):
                    while ($row = $data->fetch_assoc()) { 
                ?>
                <tr>
                    <td>#<?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['pembayaran']) ?></td>
                    <td>Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                    <td>
                        <?php if ($row['status'] == 'Menunggu Verifikasi') { ?>
                            <span class="badge badge-wait"><?= htmlspecialchars($row['status']) ?></span>
                        <?php } elseif ($row['status'] == 'Diproses') { ?>
                            <span class="badge badge-process"><?= htmlspecialchars($row['status']) ?></span>
                        <?php } else { ?>
                            <span class="badge badge-done"><?= htmlspecialchars($row['status']) ?></span>
                        <?php } ?>
                    </td>
                    <td>
                        <?php if (!empty($row['bukti'])) { ?>
                            <a href="assets/gambar/<?= htmlspecialchars($row['bukti']) ?>"
                               target="_blank"
                               class="btn btn-sm btn-info text-white">
                                Lihat
                            </a>
                        <?php } else { ?>
                            <span class="text-muted">-</span>
                        <?php } ?>
                    </td>
                    <td>
                        <a href="?id=<?= $row['id'] ?>&status=Menunggu Verifikasi"
                           class="btn btn-sm btn-warning mb-1">
                           Pending
                        </a>
                        <a href="?id=<?= $row['id'] ?>&status=Diproses"
                           class="btn btn-sm btn-primary mb-1">
                           Proses
                        </a>
                        <a href="?id=<?= $row['id'] ?>&status=Selesai"
                           class="btn btn-sm btn-success mb-1">
                           Selesai
                        </a>
                    </td>
                </tr>
                <?php 
                    } 
                else:
                ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-3">Belum ada pesanan masuk.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>