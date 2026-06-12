<?php
session_start();
include 'koneksi.php';

/* =========================
   PROTEKSI ADMIN
========================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

/* =========================
   VALIDASI ID
========================= */
if (!isset($_GET['id'])) {
    header("Location: pesanan_admin.php");
    exit();
}

$id = (int)$_GET['id']; // Casting ke int untuk keamanan tambahan

/* =====================================================
   DATA HEADER PESANAN (REVISI: Mengubah id menjadi id_pesanan)
===================================================== */
$stmtHeader = $koneksi->prepare("SELECT * FROM pesanan_header WHERE id_pesanan = ?");
$stmtHeader->bind_param("i", $id);
$stmtHeader->execute();
$resHeader = $stmtHeader->get_result();
$header = $resHeader->fetch_assoc();

if (!$header) {
    echo "Pesanan tidak ditemukan";
    exit();
}
$stmtHeader->close();

/* =====================================================
   DATA DETAIL PESANAN (REVISI: Sesuai p.id_produk & p.nama_produk)
===================================================== */
$sqlDetail = "
    SELECT d.*, p.nama_produk 
    FROM pesanan_detail d
    JOIN produk p ON d.produk_id = p.id_produk
    WHERE d.id_pesanan = ?
";

$stmtDetail = $koneksi->prepare($sqlDetail);
$stmtDetail->bind_param("i", $id);
$stmtDetail->execute();
$resDetail = $stmtDetail->get_result();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan - Sekar Bouquet</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #fff8f9; /* REVISI: Diselaraskan dengan tema warna utama pink lembut */
        }

        h2, h4, h5 {
            font-family: 'Playfair Display', serif;
            color: #8d4f5c; /* REVISI: Menggunakan warna plum tua agar serasi */
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
            box-shadow: 0 10px 25px rgba(183,110,121,0.1);
            background: white;
        }

        .badge-status {
            background: #b76e79;
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
        }

        .info-box {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 10px 25px rgba(183,110,121,0.06);
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
    <h3 class="mb-4 text-white fw-bold">🌸 Sekar Admin</h3>
    <a href="admin.php">Dashboard</a>
    <a href="produk_admin.php">Produk</a>
    <a href="pesanan_admin.php">Pesanan</a>
    <a href="users_admin.php">User</a>
    <a href="logout.php">Logout</a>
</div>

<div class="main">

    <h2 class="mb-4 fw-bold text-uppercase">Detail Pesanan 📦</h2>

    <a href="pesanan_admin.php" class="btn btn-secondary mb-3 rounded-pill px-3">
        <i class="fa-solid fa-arrow-left me-2"></i>Kembali
    </a>

    <div class="row g-4">

        <div class="col-md-4">
            <div class="info-box">
                <h5 class="mb-3 fw-bold">Informasi Pesanan</h5>

                <p><b>ID:</b> #<?= htmlspecialchars($header['id_pesanan']) ?></p>
                <p><b>Nama Pemesan:</b> <?= htmlspecialchars($header['nama']) ?></p>
                <p><b>Email:</b> <?= htmlspecialchars($header['email']) ?></p>
                <p><b>Pembayaran:</b> <?= htmlspecialchars($header['pembayaran']) ?></p>
                
                <p><b>Tanggal:</b> <?= date('d-m-Y H:i', strtotime($header['tanggal'])) ?></p>

                <p class="mb-3">
                    <b>Status:</b>
                    <span class="badge badge-status">
                        <?= htmlspecialchars($header['status']) ?>
                    </span>
                </p>

                <p class="border-top pt-3 mb-0"><b>Total Tagihan:</b>
                    <span class="fw-bold fs-5" style="color: #8d4f5c;">
                        Rp <?= number_format($header['total'], 0, ',', '.') ?>
                    </span>
                </p>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card card-box p-4">
                <h5 class="mb-3 fw-bold">Komponen & Produk Dipesan</h5>

                <table class="table table-hover align-middle">
                    <thead>
                        <tr class="text-secondary">
                            <th>Komponen</th>
                            <th>Harga Satuan</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $resDetail->fetch_assoc()) { ?>
                        <tr>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($row['nama_produk']) ?></td>
                            <td>Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                            <td class="text-center"><?= htmlspecialchars($row['jumlah']) ?>x</td>
                            <td class="text-end fw-bold" style="color: #b76e79;">
                                Rp <?= number_format($row['subtotal'], 0, ',', '.') ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="card card-box p-4 mt-4">
        <h5 class="fw-bold mb-3">Lampiran Bukti Pembayaran</h5>
        <?php if (!empty($header['bukti'])) { ?>
            <div class="border d-inline-block p-2 rounded bg-light shadow-sm">
                <img src="assets/gambar/<?= htmlspecialchars($header['bukti']) ?>"
                     class="img-fluid rounded"
                     style="max-width:320px; max-height:450px; object-fit: contain;" 
                     alt="Bukti Pembayaran">
            </div>
        <?php } else { ?>
            <p class="text-muted mb-0"><i class="fa-solid fa-circle-info me-2"></i>Belum ada unggahan bukti pembayaran.</p>
        <?php } ?>
    </div>

</div>

<?php $stmtDetail->close(); ?>
</body>
</html>