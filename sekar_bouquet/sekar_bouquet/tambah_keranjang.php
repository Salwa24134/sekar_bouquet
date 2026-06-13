<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';

// Wajib login untuk masuk ke keranjang
if (!isset($_SESSION['username'])) {
    echo "<script>
        alert('Silakan login terlebih dahulu untuk menambahkan produk!');
        window.location='login.php';
    </script>";
    exit();
}

$id_produk = isset($_POST['id_produk']) ? (int)$_POST['id_produk'] : 0;
$qty = issetPOST['jumlah'] ? (int)$_POST['jumlah'] : 1;

if ($id_produk <= 0 || $qty <= 0) {
    header("Location: produk.php");
    exit();
}

// Cek apakah produknya beneran ada di database dan cek stoknya
$stmt = $koneksi->prepare("SELECT nama_produk, stok FROM produk WHERE id_produk = ?");
$stmt->bind_param("i", $id_produk);
$stmt->execute();
$result = $stmt->get_result();
$produk = $result->fetch_assoc();
$stmt->close();

if (!$produk) {
    echo "<script>alert('Produk tidak ditemukan!'); window.location='produk.php';</script>";
    exit();
}

// Cek jika jumlah yang diminta melebihi stok gudang
if ($qty > $produk['stok']) {
    echo "<script>
        alert('Stok " . addslashes($produk['nama_produk']) . " tidak mencukupi! Sisa stok: {$produk['stok']}');
        window.history.back();
    </script>";
    exit();
}

// Struktur Session Keranjang: $_SESSION['keranjang'][id_produk] = jumlah_beli
if (isset($_SESSION['keranjang'][$id_produk])) {
    // Jika produk sudah ada di keranjang, akumulasikan jumlahnya
    $total_qty_baru = $_SESSION['keranjang'][$id_produk] + $qty;
    
    if ($total_qty_baru > $produk['stok']) {
        echo "<script>alert('Gagal menambah jumlah. Total di keranjangmu melebihi stok yang tersedia!'); window.location='keranjang.php';</script>";
        exit();
    }
    $_SESSION['keranjang'][$id_produk] = $total_qty_baru;
} else {
    // Jika belum ada, masukkan data baru
    $_SESSION['keranjang'][$id_produk] = $qty;
}

echo "<script>
    alert('Berhasil dimasukkan ke keranjang belanja!');
    window.location='keranjang.php';
</script>";
exit();