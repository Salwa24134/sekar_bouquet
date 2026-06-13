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
    $status = trim($_GET['status']); 

    if (!empty($status)) {
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
$sql = "
    SELECT 
        p.id_pesanan, 
        pl.nama, 
        pl.email, 
        p.metode_pembayaran, 
        p.total AS total_akhir, 
        p.status,
        p.bukti,
        bc.ongkos_rakit,
        (SELECT SUM(subtotal) FROM detail_pesanan dp WHERE dp.id_pesanan = p.id_pesanan) AS total_komponen
    FROM pesanan p
    JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    LEFT JOIN bouquet_custom bc ON p.id_pesanan = bc.id_pesanan
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

        .sidebar {
            width: 260px;
            height: 100vh;
            background: #b26a7a; 
            position: fixed;
            top: 0;
            left: 0;
            padding: 30px 24px;
            color: white;
            z-index: 1000;
            overflow-y: auto; 
        }

        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-track { background: rgba(0, 0, 0, 0.05); }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.25); border-radius: 10px; }

        .sidebar h3 {
            color: white !important;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 2rem !important;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            color: #f5e6e8; 
            padding: 12px 16px;
            text-decoration: none;
            margin-bottom: 12px;
            border-radius: 14px;
            font-weight: 500;
            font-size: 1.05rem;
            transition: all 0.2s ease;
        }
        .sidebar a:hover { background: rgba(255, 255, 255, 0.15); color: white; }

        .main { 
            margin-left: 260px; 
            padding: 40px; 
        }
        .card-box { 
            border: none; 
            border-radius: 18px; 
            box-shadow: 0 10px 25px rgba(183,110,121,0.08); 
            background: white;
        }
        .table-responsive {
            background: white;
            border-radius: 12px;
            padding: 10px;
        }

        .badge-wait { background-color: #fff3cd; color: #856404; padding: 6px 12px; border-radius: 20px; font-size: 12px; }
        .badge-process { background-color: #cff4fc; color: #055160; padding: 6px 12px; border-radius: 20px; font-size: 12px; }
        .badge-done { background-color: #d1e7dd; color: #0f5132; padding: 6px 12px; border-radius: 20px; font-size: 12px; }

        /* Kunci Sinkronisasi Ukuran Tombol Aksi */
        .btn-status-action {
            min-width: 65px; /* Menentukan batas lebar minimal yang sama rata */
            text-align: center;
        }
    </style>
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="main">

    <h2 class="mb-4">Manajemen Pesanan 📦</h2>

    <div class="card card-box p-4 table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th width="8%">ID</th>
                    <th width="22%">Nama Pelanggan & Pesanan</th>
                    <th width="15%">Email</th>
                    <th width="12%">Pembayaran</th>
                    <th width="13%" class="text-success text-center">Potongan Voucher</th>
                    <th width="13%">Total Bayar</th>
                    <th width="10%">Status</th>
                    <th width="17%">Aksi</th> </tr>
            </thead>
            <tbody>
                <?php 
                if ($data && $data->num_rows > 0):
                    while ($row = $data->fetch_assoc()) { 
                        $total_komponen = (int)($row['total_komponen'] ?? 0);
                        $ongkos_rakit   = (int)($row['ongkos_rakit'] ?? 0);
                        $subtotal_awal  = $total_komponen + $ongkos_rakit;
                        $total_akhir    = (int)$row['total_akhir'];

                        $potongan_voucher = $subtotal_awal - $total_akhir;
                        if ($potongan_voucher < 0) $potongan_voucher = 0;
                        
                        $status_cek = trim($row['status']);
                ?>
                <tr>
                    <td><strong>#<?= htmlspecialchars($row['id_pesanan']) ?></strong></td>
                    <td>
                        <span class="fw-bold text-dark"><?= htmlspecialchars($row['nama']) ?></span>
                        <div class="text-muted small mt-1" style="font-size: 11px; line-height: 1.4;">
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
                    <td class="small text-muted"><?= htmlspecialchars($row['email']) ?></td>
                    <td class="small"><?= htmlspecialchars($row['metode_pembayaran'] ?? '-') ?></td>
                    
                    <td class="text-center">
                        <?php if ($potongan_voucher > 0): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 11px;">
                                -Rp <?= number_format($potongan_voucher, 0, ',', '.') ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted opacity-50">-</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <span class="fw-bold text-dark">Rp <?= number_format($total_akhir, 0, ',', '.') ?></span>
                        <br>
                        <button class="btn btn-sm btn-dark px-2 py-0 mt-1" style="font-size: 10px;" data-bs-toggle="modal" data-bs-target="#bukti<?= $row['id_pesanan'] ?>">
                            <i class="fa-solid fa-image me-1"></i>Bukti
                        </button>
                    </td>
                    <td>
                        <?php if (strcasecmp($status_cek, 'Pending') == 0 || strcasecmp($status_cek, 'Menunggu Verifikasi') == 0 || empty($status_cek)) { ?>
                            <span class="badge badge-wait">Pending</span>
                        <?php } elseif (strcasecmp($status_cek, 'Diproses') == 0) { ?>
                            <span class="badge badge-process">Diproses</span>
                        <?php } else { ?>
                            <span class="badge badge-done">Selesai</span>
                        <?php } ?>
                    </td>
                    <td>
                        <div class="d-flex flex-wrap gap-1 align-items-center">
                            <a href="pesanan_admin.php?id=<?= $row['id_pesanan'] ?>&status=Pending" class="btn btn-sm btn-warning text-dark px-2 py-1 fw-semibold btn-status-action" style="font-size: 11px;">
                                Pending
                            </a>
                            <a href="pesanan_admin.php?id=<?= $row['id_pesanan'] ?>&status=Diproses" class="btn btn-sm btn-primary text-white px-2 py-1 fw-semibold btn-status-action" style="font-size: 11px;">
                                Proses
                            </a>
                            <a href="pesanan_admin.php?id=<?= $row['id_pesanan'] ?>&status=Selesai" class="btn btn-sm btn-success text-white px-2 py-1 fw-semibold btn-status-action" style="font-size: 11px;">
                                Selesai
                            </a>
                            <a href="hapus_pesanan.php?id_pesanan=<?= $row['id_pesanan']; ?>"
                                onclick="return confirm('Yakin ingin menghapus pesanan ini?')"
                                class="btn btn-sm btn-danger px-2 py-1 ms-1" style="font-size: 11px;" title="Hapus">
                                <i class="fa fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php 
                    } 
                else:
                ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">Belum ada pesanan masuk.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
if ($data && $data->num_rows > 0) {
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
                    <img src="assets/bukti/<?= htmlspecialchars($b['bukti']) ?>" class="img-fluid rounded shadow-sm">
                <?php } else { ?>
                    <p class="text-muted py-3"><i class="fa-regular fa-image mb-2 d-block fs-3 opacity-50"></i>Tidak ada berkas bukti transfer yang diunggah.</p>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<?php 
    }
} 
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>