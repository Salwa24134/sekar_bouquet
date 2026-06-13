<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';

// Proteksi Halaman: Pastikan yang mengakses adalah user/pelanggan yang sudah login
$id_user = $_SESSION['id_pelanggan'] ?? $_SESSION['id_user'] ?? $_SESSION['id'] ?? null;
if (!$id_user) {
    header("Location: login.php");
    exit();
}

// SUDAH DIPERBAIKI: Menggunakan $_GET untuk mengambil ID dari URL
$id_pesanan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_pesanan <= 0) {
    die("ID Pesanan tidak valid.");
}

// QUERY NOTA USER: Mengambil data 1 pesanan spesifik yang dimiliki oleh user tersebut
$sql = "SELECT 
            p.id_pesanan, 
            p.tanggal, 
            p.total AS total_akhir, 
            p.status, 
            p.metode_pembayaran, 
            pl.nama AS nama_pelanggan,
            bc.ongkos_rakit,
            -- Subquery menghitung total komponen belanjaan murni
            (SELECT SUM(subtotal) FROM detail_pesanan dp WHERE dp.id_pesanan = p.id_pesanan) AS total_komponen
        FROM pesanan p
        JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
        LEFT JOIN bouquet_custom bc ON p.id_pesanan = bc.id_pesanan
        WHERE p.id_pesanan = ? AND p.id_pelanggan = ?";

$stmt = $koneksi->prepare($sql);
$stmt->bind_param("ii", $id_pesanan, $id_user);
$stmt->execute();
$result = $stmt->get_result();
$pesanan = $result->fetch_assoc();
$stmt->close();

// Jika data pesanan tidak ditemukan atau pesanan tersebut bukan milik user yang login
if (!$pesanan) {
    die("Nota tidak ditemukan atau Anda tidak memiliki hak akses untuk melihat nota ini.");
}

// Kalkulasi Nilai Biaya & Selisih Voucher secara Dinamis
$total_komponen   = (int)($pesanan['total_komponen'] ?? 0);
$ongkos_rakit     = (int)($pesanan['ongkos_rakit'] ?? 0);
$subtotal_asli    = $total_komponen + $ongkos_rakit;
$total_akhir_db   = (int)$pesanan['total_akhir'];

$potongan_voucher = $subtotal_asli - $total_akhir_db;
if ($potongan_voucher < 0) $potongan_voucher = 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Resmi #<?= $pesanan['id_pesanan']; ?> - Sekar Bouquet</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        body { background: #fff8f9; font-family: 'Poppins', sans-serif; color: #333; }
        .nota-card {
            max-width: 750px;
            margin: 40px auto;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(183, 110, 121, 0.08);
            padding: 40px;
            border: 1px solid #fff0f2;
        }
        .text-pink-theme { color: #8d4f5c; }
        .table th { background-color: #fff5f6 !important; color: #8d4f5c; font-weight: 600; }
        
        /* --- CSS PRINT MEDIA: Menyembunyikan tombol saat dicetak ke kertas/PDF --- */
        @media print {
            body { background: #fff; }
            .nota-card { box-shadow: none; border: none; padding: 0; margin: 0; max-width: 100%; }
            .no-print { display: none !important; }
            .table th { background-color: #f8f9fa !important; color: #000 !important; }
        }
    </style>
</head>
<body>

<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mx-auto mb-3 no-print" style="max-width: 750px;">
        <a href="riwayat_pesanan.php" class="btn btn-outline-secondary btn-sm rounded-3 px-3">
            <i class="fa fa-arrow-left me-1"></i> Kembali ke Riwayat
        </a>
        <button onclick="window.print();" class="btn btn-dark btn-sm rounded-3 px-4">
            <i class="fa fa-print me-1"></i> Cetak / Simpan PDF
        </button>
    </div>

    <div class="nota-card">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-4 mb-4">
            <div>
                <h3 class="fw-bold text-pink-theme mb-1" style="font-family: 'Playfair Display', serif;">Sekar Bouquet 🌸</h3>
                <p class="text-muted small mb-0">Hubungi: sekarbouquet@gmail.com</p>
            </div>
            <div class="text-end">
                <h5 class="text-uppercase text-muted fw-bold small mb-1">Nota Pembelian</h5>
                <span class="fs-4 fw-bold text-dark">#<?= $pesanan['id_pesanan']; ?></span>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-sm-6 mb-3 mb-sm-0">
                <span class="text-muted d-block small text-uppercase">Nama Pelanggan:</span>
                <strong class="text-dark fs-6"><?= htmlspecialchars($pesanan['nama_pelanggan']); ?></strong>
            </div>
            <div class="col-sm-6 text-sm-end">
                <p class="mb-1"><span class="text-muted small">Tanggal:</span> <strong class="text-dark"><?= date('d M Y, H:i', strtotime($pesanan['tanggal'])); ?></strong></p>
                <p class="mb-1"><span class="text-muted small">Metode Bayar:</span> <strong class="text-dark"><?= htmlspecialchars($pesanan['metode_pembayaran'] ?: 'Transfer'); ?></strong></p>
                <p class="mb-0"><span class="text-muted small">Status Pesanan:</span> <span class="badge bg-success-subtle text-success px-2 py-1"><?= htmlspecialchars($pesanan['status']); ?></span></p>
            </div>
        </div>

        <div class="table-responsive mb-4">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Nama Produk / Komponen</th>
                        <th class="text-end" width="20%">Harga</th>
                        <th class="text-center" width="15%">Qty</th>
                        <th class="text-end" width="25%">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $q_items = $koneksi->prepare("
                        SELECT pr.nama_produk, dp.harga, dp.jumlah, dp.subtotal 
                        FROM detail_pesanan dp 
                        JOIN produk pr ON pr.id_produk = dp.id_produk 
                        WHERE dp.id_pesanan = ?
                    ");
                    $q_items->bind_param("i", $id_pesanan);
                    $q_items->execute();
                    $res_items = $q_items->get_result();

                    while($item = $res_items->fetch_assoc()):
                    ?>
                    <tr>
                        <td class="fw-medium text-dark"><?= htmlspecialchars($item['nama_produk']); ?></td>
                        <td class="text-end">Rp <?= number_format($item['harga'], 0, ',', '.'); ?></td>
                        <td class="text-center"><?= $item['jumlah']; ?>x</td>
                        <td class="text-end fw-medium text-dark">Rp <?= number_format($item['subtotal'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php endwhile; $q_items->close(); ?>
                </tbody>
            </table>
        </div>

        <div class="row justify-content-end">
            <div class="col-md-6 col-sm-8 text-end">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Total Komponen:</span>
                    <span class="text-dark fw-medium">Rp <?= number_format($total_komponen, 0, ',', '.'); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Ongkos Jasa Rangkai:</span>
                    <span class="text-dark fw-medium">+ Rp <?= number_format($ongkos_rakit, 0, ',', '.'); ?></span>
                </div>
                
                <?php if ($potongan_voucher > 0): ?>
                <div class="d-flex justify-content-between mb-2 text-success fw-medium">
                    <span><i class="fa-solid fa-ticket me-1"></i>Potongan Voucher:</span>
                    <span>- Rp <?= number_format($potongan_voucher, 0, ',', '.'); ?></span>
                </div>
                <?php endif; ?>
                
                <hr class="my-2">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark">Total Pembayaran:</span>
                    <span class="fw-bold fs-5 text-pink-theme">Rp <?= number_format($total_akhir_db, 0, ',', '.'); ?></span>
                </div>
            </div>
        </div>

        <div class="text-center mt-5 pt-4 border-top text-muted small">
            <p class="mb-1">Terima kasih telah berbelanja di Sekar Bouquet!</p>
            <p class="mb-0">Nota digital ini sah dan diterbitkan otomatis oleh sistem sebagai bukti pembayaran resmi.</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>