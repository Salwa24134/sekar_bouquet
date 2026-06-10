<?php
session_start();
include 'koneksi.php';

/* =========================
   PROTEKSI LOGIN
========================= */
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['user']['username'];

/* =========================
   AMBIL DATA PESANAN USER
========================= */
$sql = "
SELECT *
FROM pesanan_header
WHERE nama = ?
ORDER BY id DESC
";

$stmt = sqlsrv_query($koneksi, $sql, [$username]);
?>

<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<title>Riwayat Pesanan - Sekar Bouquet</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #fff4f7;
}

h1, h2, h3, h4 {
    font-family: 'Playfair Display', serif;
    color: #b76e79;
}

.card-bouquet {
    background: white;
    border: none;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(183,110,121,0.15);
}

.table thead {
    background: #b76e79;
    color: white;
}

.btn-main {
    background: linear-gradient(135deg, #d88b9c, #b76e79);
    color: white;
    border: none;
    border-radius: 12px;
    padding: 8px 14px;
    font-weight: 600;
    text-decoration: none;
}

.btn-main:hover {
    color: white;
    transform: translateY(-2px);
}

.badge-status {
    padding: 6px 12px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
}

/* status warna */
.pending {
    background: #ffc107;
    color: black;
}

.proses {
    background: #0d6efd;
    color: white;
}

.selesai {
    background: #198754;
    color: white;
}
</style>
</head>

<body>

<?php include 'layout/header.php'; ?>

<div class="container py-5">

    <h1 class="text-center mb-4">Riwayat Pesanan 🌸</h1>

    <div class="card card-bouquet p-4">

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tanggal</th>
                        <th>Pembayaran</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($stmt && sqlsrv_has_rows($stmt)) { ?>

                    <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { ?>

                        <?php
                            $status = strtolower($row['status']);
                            $badgeClass = "pending";

                            if (strpos($status, "proses") !== false) {
                                $badgeClass = "proses";
                            } elseif (strpos($status, "selesai") !== false) {
                                $badgeClass = "selesai";
                            }
                        ?>

                        <tr>
                            <td>#<?= $row['id']; ?></td>

                            <td>
                                <?php 
                                if ($row['tanggal'] instanceof DateTime) {
                                    echo $row['tanggal']->format('Y-m-d H:i');
                                } else {
                                    echo $row['tanggal'];
                                }
                                ?>
                            </td>

                            <td><?= $row['pembayaran']; ?></td>

                            <td class="fw-bold text-danger">
                                Rp <?= number_format($row['total'], 0, ',', '.'); ?>
                            </td>

                            <td>
                                <span class="badge-status <?= $badgeClass; ?>">
                                    <?= $row['status']; ?>
                                </span>
                            </td>

                            <td>
                                <a href="detail_pesanan.php?id=<?= $row['id']; ?>" 
                                   class="btn btn-main btn-sm">
                                    Detail
                                </a>

                                <a href="cetak_pdf.php?id=<?= $row['id']; ?>" 
                                   class="btn btn-secondary btn-sm">
                                    Nota
                                </a>
                            </td>
                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            Belum ada pesanan 🌸
                        </td>
                    </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<?php include 'layout/footer.php'; ?>

</body>
</html>