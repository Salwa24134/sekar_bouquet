<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'koneksi.php';

$id_user = $_SESSION['id_pelanggan'] ?? $_SESSION['id_user'] ?? $_SESSION['id'] ?? null;

if (!$id_user) {
    header("Location: login.php");
    exit();
}

// QUERY NORMAL (Mengambil seluruh data pesanan pelanggan)
$sql = "SELECT 
            p.*, 
            bc.ongkos_rakit,
            (SELECT SUM(subtotal) FROM detail_pesanan dp WHERE dp.id_pesanan = p.id_pesanan) AS total_komponen
        FROM pesanan p 
        LEFT JOIN bouquet_custom bc ON p.id_pesanan = bc.id_pesanan
        WHERE p.id_pelanggan = ? 
        ORDER BY p.id_pesanan DESC";

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
        body { background: #fff8f9; font-family: 'Poppins', sans-serif; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }
        .card-order { background: #fff; border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(183, 110, 121, 0.05); overflow: hidden; transition: all 0.3s ease; display: flex; flex-direction: column; justify-content: space-between; }
        .card-order:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(183, 110, 121, 0.12); }
        .header-img { width: 100%; height: 160px; object-fit: cover; }
        .content { padding: 22px; font-size: 13px; flex-grow: 1; display: flex; flex-direction: column; }
        .badge { font-size: 11px; padding: 6px 12px; border-radius: 30px; font-weight: 600; text-transform: capitalize; }
        
        /* Tema Warna Status Pastel */
        .status-pending { background: #fff3cd; color: #856404; }
        .status-diproses { background: #e0f0ff; color: #004085; }
        .status-selesai { background: #d4edda; color: #155724; }
        .status-dibatalkan { background: #f8d7da; color: #721c24; }

        .product-item { display: flex; align-items: center; gap: 12px; font-size: 13px; margin-bottom: 12px; background: #fffafb; padding: 8px; border-radius: 12px; border: 1px solid #fff0f2; }
        .product-img { width: 45px; height: 45px; border-radius: 10px; object-fit: cover; }
        .small-text { font-size: 12px; color: #8a7678; }
        .btn-custom-pink { background: linear-gradient(135deg, #d88b9c, #b76e79); color: white; border: none; border-radius: 12px; padding: 10px; font-weight: 500; font-size: 13px; width: 100%; display: block; text-align: center; text-decoration: none; transition: 0.3s; }
        .btn-custom-pink:hover { color: white; opacity: 0.9; transform: translateY(-1px); }
    </style>
</head>
<body>

<?php include 'layout/header.php'; ?>

<div class="container py-5" style="min-height: 80vh;">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h3 class="mb-0 fw-bold text-dark" style="font-family: 'Playfair Display', serif; color: #8d4f5c !important;">Riwayat Pesanan Anda 🌸</h3>
        <a href="index.php" class="btn btn-outline-secondary btn-sm px-3 py-2" style="border-radius: 10px;">
            <i class="fa fa-arrow-left me-1"></i> Kembali Belanja
        </a>
    </div>

    <?php if($result->num_rows === 0): ?>
        <div class="text-center py-5">
            <i class="fa-solid fa-basket-shopping text-muted mb-3" style="font-size: 3.5rem; opacity: 0.4;"></i>
            <p class="text-muted">Belum ada riwayat transaksi yang tercatat.</p>
        </div>
    <?php else: ?>
        <div class="grid">
        <?php while($row = $result->fetch_assoc()): ?>
            <?php
                $id = $row['id_pesanan'];
                $status_db = trim($row['status']);

                // LOGIKA BARU: Cek apakah ini pesanan kustom berdasarkan ongkos rakit
                $ongkos_rakit     = (int)($row['ongkos_rakit'] ?? 0);
                $info_pesanan     = ($ongkos_rakit > 0) ? "Pesanan Kustom ✨" : "Katalog Produk 🌸";

                // Normalisasi penentuan class badge CSS dinamis berdasarkan status di database
                $badge_class = "status-pending"; 
                if (strcasecmp($status_db, 'selesai') == 0) {
                    $badge_class = "status-selesai";
                } elseif (strcasecmp($status_db, 'diproses') == 0) {
                    $badge_class = "status-diproses";
                } elseif (strcasecmp($status_db, 'dibatalkan') == 0) {
                    $badge_class = "status-dibatalkan";
                }

                // Kalkulasi Perhitungan Potongan Voucher Secara Mandiri
                $total_komponen   = (int)($row['total_komponen'] ?? 0);
                $subtotal_asli    = $total_komponen + $ongkos_rakit;
                $total_akhir_db   = (int)$row['total'];

                // Rumus kalkulasi nilai selisih voucher
                $potongan_voucher = $subtotal_asli - $total_akhir_db;
                if ($potongan_voucher < 0) $potongan_voucher = 0;

                // Query mengambil item pertama sebagai wajah/banner utama card
                $q_first = $koneksi->prepare("SELECT pr.gambar FROM detail_pesanan dp JOIN produk pr ON pr.id_produk = dp.id_produk WHERE dp.id_pesanan = ? LIMIT 1");
                $q_first->bind_param("i", $id);
                $q_first->execute();
                $first = $q_first->get_result()->fetch_assoc();
                $q_first->close();
            ?>

            <div class="card-order">
                <?php if(!empty($first['gambar'])): ?>
                    <img src="assets/gambar/<?= htmlspecialchars($first['gambar']); ?>" class="header-img">
                <?php else: ?>
                    <div class="bg-secondary-subtle header-img d-flex align-items-center justify-content-center">
                        <i class="fa fa-image text-muted fs-3"></i>
                    </div>
                <?php endif; ?>

                <div class="content">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-dark fs-6" style="font-size: 14px !important; color: #b76e79 !important;">
                            <?= $info_pesanan; ?>
                        </span>
                        <span class="badge <?= $badge_class; ?>"><?= htmlspecialchars($status_db); ?></span>
                    </div>

                    <div class="small-text mb-3">
                        <i class="fa fa-calendar-alt me-1"></i> <?= date('d M Y, H:i', strtotime($row['tanggal'])); ?>
                    </div>

                    <hr class="my-2 opacity-25">
                    <div class="product-list-scroll mb-3" style="max-height: 160px; overflow-y: auto;">
                        <?php
                        $q_items = $koneksi->prepare("SELECT pr.nama_produk, pr.gambar, dp.jumlah FROM detail_pesanan dp JOIN produk pr ON pr.id_produk = dp.id_produk WHERE dp.id_pesanan = ?");
                        $q_items->bind_param("i", $id);
                        $q_items->execute();
                        $res_items = $q_items->get_result();
                        while($p = $res_items->fetch_assoc()):
                        ?>
                            <div class="product-item">
                                <img src="assets/gambar/<?= htmlspecialchars($p['gambar']); ?>" class="product-img">
                                <div class="flex-grow-1">
                                    <div class="fw-medium text-dark text-truncate" style="max-width: 180px;"><?= htmlspecialchars($p['nama_produk']); ?></div>
                                    <div class="small-text">Jumlah: <?= $p['jumlah']; ?>x</div>
                                </div>
                            </div>
                        <?php endwhile; $q_items->close(); ?>
                    </div>

                    <div class="mt-auto">
                        <div class="bg-light p-2 rounded mb-2 style-muted" style="font-size: 11px;">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Total Barang & Rakit:</span>
                                <span class="text-dark">Rp <?= number_format($subtotal_asli, 0, ',', '.'); ?></span>
                            </div>
                            <?php if ($potongan_voucher > 0): ?>
                            <div class="d-flex justify-content-between text-success fw-medium mt-1">
                                <span><i class="fa-solid fa-ticket me-1"></i>Potongan Voucher:</span>
                                <span>- Rp <?= number_format($potongan_voucher, 0, ',', '.'); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <span class="text-muted small fw-bold">Total Tagihan:</span>
                            <span class="fw-bold fs-6" style="color: #8d4f5c;">Rp <?= number_format($total_akhir_db, 0, ',', '.'); ?></span>
                        </div>

                        <div class="mt-3">
                            <?php if(strcasecmp($status_db, 'selesai') == 0): ?>
                                <a href="nota.php?id=<?= $id; ?>" class="btn btn-success btn-sm w-100 py-2" style="border-radius: 12px; font-weight: 500;">
                                    <i class="fa fa-print me-1"></i> Cetak Nota Pembelian
                                </a>
                            <?php elseif(strcasecmp($status_db, 'dibatalkan') == 0): ?>
                                <button class="btn btn-light btn-sm w-100 py-2 border text-muted" style="border-radius: 12px;" disabled>Pesanan Dibatalkan</button>
                            <?php elseif(strcasecmp($status_db, 'diproses') == 0): ?>
                                <div class="alert alert-info py-2 px-3 m-0 small text-center" style="border-radius: 12px; font-size: 12px;">
                                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Buket sedang dirangkai tim
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning py-2 px-3 m-0 mb-2 small text-center" style="border-radius: 12px; font-size: 12px;">
                                    <i class="fa fa-hourglass-half me-1"></i> Menunggu Konfirmasi Pembayaran
                                </div>
                            <?php endif; ?>

                            <a href="hapus_pesanan.php?id_pesanan=<?= $id; ?>" 
                               onclick="return confirm('Apakah Anda yakin ingin menghapus riwayat pesanan ini?')" 
                               class="btn btn-outline-danger btn-sm w-100 py-2 mt-1" 
                               style="border-radius: 12px; font-size: 12px; font-weight: 500;">
                               <i class="fa fa-trash me-1"></i> Hapus Riwayat Pesanan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'layout/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>