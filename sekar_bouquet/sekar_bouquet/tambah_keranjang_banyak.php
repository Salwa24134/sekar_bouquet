<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Validasi jika user menekan tombol tanpa mencentang satu pun produk
if (!isset($_POST['produk_id']) || empty($_POST['produk_id'])) {
    echo "<script>
        alert('Gagal! Kamu belum memilih komponen bouquet apapun. Silakan centang minimal satu item.');
        window.location='produk.php';
    </script>";
    exit();
}

$produk_dipilih = $_POST['produk_id']; // Berisi array ID produk yang dicentang
$daftar_jumlah = $_POST['jumlah'];    // Berisi array kuantitas input pembeli

// Inisialisasi session keranjang jika belum ada
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

// Iterasi dan masukkan seluruh item terpilih ke dalam keranjang session
foreach ($produk_dipilih as $id_produk) {
    $id_produk = (int)$id_produk;
    $qty = isset($daftar_jumlah[$id_produk]) ? (int)$daftar_jumlah[$id_produk] : 1;
    
    if ($qty <= 0) $qty = 1;

    // Cek stok riil ke database demi keamanan logika bisnis
    $stmt = $koneksi->prepare("SELECT nama_produk, stok FROM produk WHERE id_produk = ?");
    $stmt->bind_param("i", $id_produk);
    $stmt->execute();
    $produk = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($produk) {
        // Cek jika sudah ada data lama di keranjang, akumulasikan
        $qty_lama = isset($_SESSION['keranjang'][$id_produk]) ? $_SESSION['keranjang'][$id_produk] : 0;
        $total_qty_baru = $qty_lama + $qty;

        // Cegah jika penambahan melebihi kapasitas stok di gudang database
        if ($total_qty_baru > $produk['stok']) {
            echo "<script>
                alert('Stok komponen \"" . addslashes($produk['nama_produk']) . "\" tidak cukup. Sisa stok gudang: {$produk['stok']}.');
                window.location='produk.php';
            </script>";
            exit();
        }
        
        // Simpan ke session
        $_SESSION['keranjang'][$id_produk] = $total_qty_baru;
    }
}

// Lempar langsung ke halaman keranjang untuk melihat akumulasi rincian biaya
echo "<script>
    alert('Komponen pilihanmu berhasil ditambahkan ke keranjang racikan!');
    window.location='keranjang.php';
</script>";
exit();