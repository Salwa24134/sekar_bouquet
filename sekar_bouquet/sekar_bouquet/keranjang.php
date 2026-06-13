<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Aksi Hapus Item Individu
if (isset($_GET['action']) && $_GET['action'] == 'hapus') {
    $id_hapus = (int)$_GET['id'];
    unset($_SESSION['keranjang'][$id_hapus]);
    header("Location: keranjang.php");
    exit();
}

$total_belanja = 0;
$potongan_voucher = 0;
$kode_voucher_aktif = "";

/* ==========================================================================
   1. HITUNG TOTAL BELANJA KOMPONEN TERLEBIH DAHULU (UNTUK MENENTUKAN ONGKOS RAKIT)
   ========================================================================== */
if (!empty($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $id_produk => $qty) {
        $stmt = $koneksi->prepare("SELECT harga_jual FROM produk WHERE id_produk = ?");
        $stmt->bind_param("i", $id_produk);
        $stmt->execute();
        $produk = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($produk) {
            $total_belanja += ($produk['harga_jual'] * $qty);
        }
    }
}

/* ==========================================================================
   2. LOGIKA DINAMIS ONGKOS RAKIT BERDASARKAN TOTAL BELANJA
   ========================================================================== */
if ($total_belanja < 200000) {
    $ongkos_rakit = 25000;
} elseif ($total_belanja >= 200000 && $total_belanja <= 499000) {
    $ongkos_rakit = 50000;
} else {
    $ongkos_rakit = 75000;
}

/* ==========================================================================
   3. ENGINE SINKRONISASI REWARD VOUCHER LOYALITAS HASIL CALCULATE DATABASE CURSOR
   ========================================================================== */
if (isset($_SESSION['id_pelanggan'])) {
    $id_user_login = $_SESSION['id_pelanggan'];
    $query_v = $koneksi->prepare("
        SELECT kode_voucher, potongan_harga FROM voucher_pelanggan 
        WHERE id_pelanggan = ? AND status_aktif = 'Aktif'
        ORDER BY id_voucher DESC LIMIT 1
    ");
    $query_v->bind_param("i", $id_user_login);
    $query_v->execute();
    $res_v = $query_v->get_result();
    if ($row_v = $res_v->fetch_assoc()) {
        $potongan_voucher = (int)$row_v['potongan_harga'];
        $kode_voucher_aktif = $row_v['kode_voucher'];
    }
    $query_v->close();
}

// Reset ulang untuk hitungan loop di tabel HTML di bawah agar tidak double
$total_belanja_tabel = 0; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Sekar Bouquet</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #fff8f9; color: #4a373a; }
        h1, h5 { font-family: 'Playfair Display', serif; color: #8d4f5c; font-weight: 700; }
        .cart-card { background: white; border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(183,110,121,0.06); }
        
        .btn-checkout { 
            background: linear-gradient(135deg, #d88b9c, #b76e79); 
            color: white; 
            border: none; 
            border-radius: 30px; 
            padding: 12px; 
            font-weight: 600; 
            text-decoration: none; 
            display: block; 
            text-align: center; 
            transition: all 0.3s ease; 
            box-shadow: 0 5px 15px rgba(183, 110, 121, 0.2);
        }
        .btn-checkout:hover { transform: translateY(-2px); color: white; box-shadow: 0 8px 20px rgba(183,110,121,0.3); }
        
        .voucher-badge-alert {
            background-color: #fff0f2;
            border: 1px dashed #b76e79;
            border-radius: 12px;
            padding: 12px;
        }
    </style>
</head>
<body>

<?php include 'layout/header.php'; ?>

<div class="container my-5" style="min-height: 70vh;">
    <h1 class="mb-4"><i class="fa-solid fa-basket-shopping me-2" style="color: #b76e79;"></i> Keranjang Bouquet 🌸</h1>

    <?php if (empty($_SESSION['keranjang'])): ?>
        <div class="card cart-card p-5 text-center mx-auto" style="max-width: 700px;">
            <i class="fa-solid fa-folder-open fs-1 text-muted mb-3" style="color: #b76e79 !important; opacity: 0.6;"></i>
            <h5>Keranjang belanjamu masih kosong nih.</h5>
            <p class="text-muted small">Yuk, pilih komponen bunga segar dan kertas wrapping kustom kesukaanmu terlebih dahulu.</p>
            <div class="d-flex justify-content-center mt-3">
                <a href="produk.php" class="btn btn-checkout px-5">Pilih Komponen</a>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card cart-card p-4">
                    <form method="post" action="checkout.php">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr class="text-secondary small text-uppercase" style="letter-spacing: 0.5px;">
                                        <th>Komponen / Produk</th>
                                        <th>Harga Satuan</th>
                                        <th class="text-center">Jumlah</th>
                                        <th>Subtotal</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    foreach ($_SESSION['keranjang'] as $id_produk => $qty): 
                                        $stmt = $koneksi->prepare("SELECT id_produk, nama_produk, harga_jual FROM produk WHERE id_produk = ?");
                                        $stmt->bind_param("i", $id_produk);
                                        $stmt->execute();
                                        $produk = $stmt->get_result()->fetch_assoc();
                                        $stmt->close();

                                        if (!$produk) continue;

                                        $subtotal = $produk['harga_jual'] * $qty;
                                        $total_belanja_tabel += $subtotal;
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-dark d-block" style="font-size: 0.95rem;"><?php echo htmlspecialchars($produk['nama_produk']); ?></span>
                                            <input type="hidden" name="produk_id[]" value="<?php echo $produk['id_produk']; ?>">
                                            <input type="hidden" name="jumlah[<?php echo $produk['id_produk']; ?>]" value="<?php echo $qty; ?>">
                                        </td>
                                        <td style="font-size: 0.9rem;">Rp <?php echo number_format($produk['harga_jual'], 0, ',', '.'); ?></td>
                                        <td class="text-center fw-semibold text-muted" style="font-size: 0.9rem;"><?php echo $qty; ?>x</td>
                                        <td class="fw-bold" style="color: #b76e79; font-size: 0.95rem;">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                                        <td class="text-center">
                                            <a href="keranjang.php?action=hapus&id=<?php echo $produk['id_produk']; ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Hapus item komponen ini dari racikan?')">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card cart-card p-4">
                    <h5 class="mb-3 border-bottom pb-2">Ringkasan Pembayaran</h5>
                    
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Total Harga Komponen</span>
                        <span class="fw-semibold">Rp <?php echo number_format($total_belanja_tabel, 0, ',', '.'); ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Ongkos Jasa Rangkai (Rakit)</span>
                        <span class="fw-semibold text-dark">+ Rp <?php echo number_format($ongkos_rakit, 0, ',', '.'); ?></span>
                        <input type="hidden" name="ongkos_rakit" value="<?php echo $ongkos_rakit; ?>">
                    </div>

                    <?php if ($potongan_voucher > 0): ?>
                        <div class="d-flex justify-content-between mb-2 small text-success">
                            <span>Voucher Loyalitas (<?= htmlspecialchars($kode_voucher_aktif); ?>)</span>
                            <span class="fw-bold">- Rp <?php echo number_format($potongan_voucher, 0, ',', '.'); ?></span>
                        </div>
                        <div class="voucher-badge-alert my-3 small text-start">
                            <span class="fw-bold text-dark d-block mb-1"><i class="fa fa-ticket-alt me-1" style="color: #b76e79;"></i> Kupon Berhasil Dipasang!</span>
                            <p class="text-muted mb-0" style="font-size: 0.8rem;">Potongan kustom hasil audit loyalitas database Anda langsung memotong nota tagihan ini.</p>
                        </div>
                    <?php endif; ?>

                    <?php 
                    // KALKULASI FINAL TOTAL AKHIR
                    $final_total_bayar = ($total_belanja_tabel + $ongkos_rakit) - $potongan_voucher;
                    if($final_total_bayar < 0) $final_total_bayar = 0;
                    ?>

                    <div class="d-flex justify-content-between my-3 pt-2 border-top">
                        <span class="fw-bold text-dark">Total Akhir</span>
                        <span class="fw-bold fs-5" style="color: #8d4f5c;">Rp <?php echo number_format($final_total_bayar, 0, ',', '.'); ?></span>
                    </div>
                    
                    <button type="submit" class="btn btn-checkout w-100 shadow-sm">
                        Lanjut ke Checkout <i class="fa-solid fa-arrow-right ms-1"></i>
                    </button>
                    <a href="produk.php" class="btn btn-light w-100 mt-2 border text-muted" style="border-radius: 30px; font-size: 0.9rem; font-weight: 500;">
                        <i class="fa-solid fa-plus small me-1"></i> Tambah Racikan Lain
                    </a>
                    </form> 
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'layout/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>