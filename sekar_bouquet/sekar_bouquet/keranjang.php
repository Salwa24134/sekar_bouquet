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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Sekar Bouquet</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #fff8f9; }
        h1, h5 { font-family: 'Playfair Display', serif; color: #8d4f5c; font-weight: 700; }
        .cart-card { background: white; border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(183,110,121,0.08); }
        .btn-checkout { background: linear-gradient(135deg, #d88b9c, #b76e79); color: white; border: none; border-radius: 12px; padding: 12px; font-weight: 600; text-decoration: none; display: block; text-align: center; transition: 0.3s; }
        .btn-checkout:hover { transform: translateY(-2px); color: white; box-shadow: 0 8px 15px rgba(183,110,121,0.2); }
    </style>
</head>
<body>

<?php include 'layout/header.php'; ?>

<div class="container my-5">
    <h1 class="mb-4"><i class="fa-solid fa-basket-shopping me-2"></i> Keranjang Bouquet 🌸</h1>

    <?php if (empty($_SESSION['keranjang'])): ?>
        <div class="card cart-card p-5 text-center">
            <i class="fa-solid fa-folder-open fs-1 text-muted mb-3"></i>
            <h5>Keranjang belanjamu masih kosong nih.</h5>
            <p class="text-muted">Yuk, pilih komponen bunga dan kertas wrapping favoritmu terlebih dahulu.</p>
            <div class="d-flex justify-content-center">
                <a href="produk.php" class="btn btn-checkout px-4">Pilih Komponen</a>
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
                                    <tr class="text-secondary">
                                        <th>Komponen / Produk</th>
                                        <th>Harga</th>
                                        <th style="width: 100px;">Jumlah</th>
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
                                        $total_belanja += $subtotal;
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($produk['nama_produk']); ?></span>
                                            <input type="hidden" name="produk_id[]" value="<?php echo $produk['id_produk']; ?>">
                                            <input type="hidden" name="jumlah[<?php echo $produk['id_produk']; ?>]" value="<?php echo $qty; ?>">
                                        </td>
                                        <td>Rp <?php echo number_format($produk['harga_jual'], 0, ',', '.'); ?></td>
                                        <td class="text-center fw-semibold"><?php echo $qty; ?>x</td>
                                        <td class="fw-bold" style="color: #b76e79;">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                                        <td class="text-center">
                                            <a href="keranjang.php?action=hapus&id=<?php echo $produk['id_produk']; ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Hapus item ini?')">
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
                    <h5 class="mb-3 border-bottom pb-2">Ringkasan Pesanan</h5>
                    <div class="d-flex justify-content-between mb-4">
                        <span class="text-muted">Total Harga Komponen</span>
                        <span class="fw-bold fs-5" style="color: #8d4f5c;">Rp <?php echo number_format($total_belanja, 0, ',', '.'); ?></span>
                    </div>
                    
                    <button type="submit" class="btn btn-checkout w-100">
                        Lanjut ke Checkout <i class="fa-solid fa-arrow-right ms-1"></i>
                    </button>
                    <a href="produk.php" class="btn btn-light w-100 mt-2 border text-muted" style="border-radius: 12px;">
                        <i class="fa-solid fa-plus small me-1"></i> Tambah Bunga Lain
                    </a>
                    </form> </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'layout/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>