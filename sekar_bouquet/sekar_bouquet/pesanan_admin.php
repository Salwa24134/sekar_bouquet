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

    $id = (int) $_GET['id'];
    $status = $_GET['status'];

    // paksa status hanya 3 ini (biar konsisten)
    if ($status == 'Pending' || $status == 'Diproses' || $status == 'Selesai') {

        $stmt = $koneksi->prepare("UPDATE pesanan SET status = ? WHERE id_pesanan = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: pesanan_admin.php");
    exit();
}

/* =========================
   DATA PESANAN (MySQLi)
========================= */
// Menggunakan JOIN ke pelanggan agar kolom nama & email bisa ditarik secara dinamis
$sql = "
    SELECT 
        p.id_pesanan, 
        pl.nama, 
        pl.email, 
        p.metode_pembayaran, 
        p.total, 
        p.status,
        p.bukti
    FROM pesanan p
    JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    ORDER BY p.id_pesanan DESC
";

$data = $koneksi->query($sql);

if ($data === false) {
    die("Error Query: " . $koneksi->error);
}
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

    <h2 class="mb-4">Manajemen Pesanan 📦</h2>

    <div class="card card-box p-4">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Pelanggan dan Pesanan</th>
                    <th>Email</th>
                    <th>Metode Pembayaran</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($data && $data->num_rows > 0):
                    while ($row = $data->fetch_assoc()) { 
                ?>
                <tr>
                    <td>#<?= htmlspecialchars($row['id_pesanan']) ?></td>
                    <td>
                        <?= htmlspecialchars($row['nama']) ?>

                        <div class="text-muted small">
                            <?php
                            $id = $row['id_pesanan'];

                            $produk = $koneksi->query("
                                SELECT pr.nama_produk, dp.jumlah
                                FROM detail_pesanan dp
                                JOIN produk pr ON pr.id_produk = dp.id_produk
                                WHERE dp.id_pesanan = $id
                            ");

                            while($p = $produk->fetch_assoc()){
                                echo "• ".$p['nama_produk']." x".$p['jumlah']."<br>";
                            }
                            ?>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['metode_pembayaran'] ?? '-') ?></td>
                    <td>Rp <?= number_format($row['total'], 0, ',', '.') ?>
                <br>
                <button class="btn btn-sm btn-dark mt-1" data-bs-toggle="modal" data-bs-target="#bukti<?= $row['id_pesanan'] ?>">
                    Lihat Bukti
                </button></td>
                    <td>
                        <?php if ($row['status'] == 'Menunggu Verifikasi' || $row['status'] == 'Pending') { ?>
                            <span class="badge badge-wait"><?= htmlspecialchars($row['status']) ?></span>
                        <?php } elseif ($row['status'] == 'Diproses') { ?>
                            <span class="badge badge-process"><?= htmlspecialchars($row['status']) ?></span>
                        <?php } else { ?>
                            <span class="badge badge-done"><?= htmlspecialchars($row['status']) ?></span>
                        <?php } ?>
                    </td>
                    <td>
                        <a href="?id=<?= $row['id_pesanan'] ?>&status=Pending" class="btn btn-sm btn-warning">
                        Pending
                        </a>

                        <a href="?id=<?= $row['id_pesanan'] ?>&status=Diproses" class="btn btn-sm btn-primary">
                        Proses
                        </a>

                        <a href="?id=<?= $row['id_pesanan'] ?>&status=Selesai" class="btn btn-sm btn-success">
                        Selesai
                        </a>
                        <a href="hapus_pesanan.php?id_pesanan=<?= $row['id_pesanan']; ?>"
                            onclick="return confirm('Yakin ingin menghapus pesanan ini? Data tidak bisa dikembalikan!')"
                            class="btn btn-sm btn-danger">
                            Hapus
                        </a>
                    </td>
                </tr>
                <?php 
                    } 
                else:
                ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-3">Belum ada pesanan masuk.</td>
                </tr>
                <?php endif; ?>

                <?php
                $data->data_seek(0);
                while ($b = $data->fetch_assoc()) {
                ?>
                <div class="modal fade" id="bukti<?= $b['id_pesanan'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title">Bukti Pembayaran #<?= $b['id_pesanan'] ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body text-center">
                        <?php if (!empty($b['bukti'])) { ?>
                            <img src="assets/bukti/<?= htmlspecialchars($b['bukti']) ?>" class="img-fluid rounded">
                        <?php } else { ?>
                            <p class="text-muted">Tidak ada bukti</p>
                        <?php } ?>
                    </div>

                    </div>
                </div>
                </div>
                <?php } ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>