<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if (empty($_POST['produk_id'])) {
    header("Location: produk.php");
    exit();
}

$produkDipilih = $_POST['produk_id'];
$jumlahDipilih = $_POST['jumlah'] ?? [];

// Ambil ID Pelanggan dari session login (karena tabelmu pakai id_pelanggan, bukan nama)
// Jika di session login kamu menyimpannya dengan nama lain (misal $_SESSION['id_user']), silakan sesuaikan ganti bagian ini.
// Sesuaikan isi di dalam tanda petik dengan nama session login milikmu
$id_pelanggan = $_SESSION['id_pelanggan'] ?? $_SESSION['id_user'] ?? $_SESSION['id'] ?? null; 

// Jika session benar-benar kosong/user belum login, tendang ke halaman login
if (!$id_pelanggan) {
    echo "<script>
        alert('Sesi Anda telah habis atau Anda belum login. Silakan login kembali.');
        window.location='login.php';
    </script>";
    exit();
}

$pembayaran   = $_POST['pembayaran'] ?? '';
$total        = 0;
$detailData   = [];

/* =====================================================
   1. VALIDASI + HITUNG PRODUK
===================================================== */
foreach ($produkDipilih as $id) {
    $id = (int)$id;

    $stmt = $koneksi->prepare("SELECT id_produk, nama_produk, harga_jual, stok FROM produk WHERE id_produk = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $produk = $result->fetch_assoc();
    $stmt->close();

    if (!$produk) continue;

    $qty = isset($jumlahDipilih[$id]) ? (int)$jumlahDipilih[$id] : 1;
    if ($qty < 1) $qty = 1;

    if ($qty > $produk['stok']) {
        echo "<script>
            alert('Stok " . addslashes($produk['nama_produk']) . " tidak cukup (sisa: {$produk['stok']})');
            window.location='produk.php';
        </script>";
        exit();
    }

    $subtotal = $produk['harga_jual'] * $qty;
    $total += $subtotal;

    $detailData[] = [
        'id'       => $produk['id_produk'],
        'nama'     => $produk['nama_produk'],
        'harga'    => $produk['harga_jual'],
        'qty'      => $qty,
        'subtotal' => $subtotal
    ];
}

/* =====================================================
   2. UPLOAD BUKTI PEMBAYARAN
===================================================== */
$buktiName = "";
$file = $_FILES['bukti_transfer'] ?? null;

if (!$file || $file['error'] != 0) {
    $file = $_FILES['bukti_qris'] ?? null;
}

if ($file && $file['error'] == 0) {
    $folder = "assets/gambar/";
    if (!is_dir($folder)) mkdir($folder, 0777, true);

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $buktiName = "BUKTI_" . time() . "_" . uniqid() . "." . $ext;

    move_uploaded_file($file['tmp_name'], $folder . $buktiName);
}


/* =====================================================
   3. INSERT KE TABEL UTAMA (pesanan) -> FIXED SESUAI STRUKTUR KAMU
===================================================== */
// Kolom pas dari phpMyAdmin kamu: id_pelanggan, tanggal, total, status, metode_pembayaran, bukti
$sqlInsert = "
INSERT INTO pesanan
(id_pelanggan, tanggal, total, status, metode_pembayaran, bukti)
VALUES
(?, NOW(), ?, 'Menunggu Pembayaran', ?, ?)
";

$cekPelanggan = $koneksi->prepare(
    "SELECT id_pelanggan
     FROM pelanggan
     WHERE id_pelanggan=?"
);

$cekPelanggan->bind_param("i", $id_pelanggan);
$cekPelanggan->execute();

$resCek = $cekPelanggan->get_result();

if ($resCek->num_rows == 0) {

    $username = $_SESSION['username'];

    $stmtTambah = $koneksi->prepare(
        "INSERT INTO pelanggan
        (id_pelanggan,nama,email,telepon,alamat)
        VALUES (?, ?, '', '', '')"
    );

    $stmtTambah->bind_param(
        "is",
        $id_pelanggan,
        $username
    );

    $stmtTambah->execute();
    $stmtTambah->close();
}

$cekPelanggan->close();

$stmtHeader = $koneksi->prepare($sqlInsert);

if (!$stmtHeader) {
    die("<b>Gagal Prepare Query Tabel Utama!</b> Error MySQL: " . $koneksi->error);
}

// "iiss" artinya: int (id_pelanggan), int (total), string (pembayaran), string (buktiName)
$stmtHeader->bind_param("iiss", $id_pelanggan, $total, $pembayaran, $buktiName);


if (!$stmtHeader->execute()) {
    die(
        "Gagal simpan pesanan : "
        . $stmtHeader->error
    );
}

$id_pesanan = $koneksi->insert_id;
$stmtHeader->close();

/* =========================
   ANTI DOUBLE ORDER (WAJIB)
========================= */
if (isset($_SESSION['last_order_id']) && $_SESSION['last_order_id'] == $id_pesanan) {
    header("Location: riwayat_pesanan.php");
    exit();
}

$_SESSION['last_order_id'] = $id_pesanan;

/* =====================================================
   4. INSERT KE DETAIL (detail_pesanan) -> FIXED SESUAI STRUKTUR KAMU
===================================================== */
if ($id_pesanan > 0) {
    foreach ($detailData as $p) {
        
        // Memakai kolom valid kamu: id_pesanan, id_produk, jumlah, harga, subtotal
        $sqlDetail = "
            INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, harga, subtotal) 
            VALUES (?, ?, ?, ?, ?)
        ";
        
        $stmtDetail = $koneksi->prepare($sqlDetail);
        
        if (!$stmtDetail) {
            die("<b>Gagal simpan rincian pesanan!</b> Error MySQL: " . $koneksi->error);
        }

        $stmtDetail->bind_param("iiiii", $id_pesanan, $p['id'], $p['qty'], $p['harga'], $p['subtotal']);
        $stmtDetail->execute();
        $stmtDetail->close();

        // Mengurangi stok item di tabel produk
        $stmtStok = $koneksi->prepare("UPDATE produk SET stok = stok - ? WHERE id_produk = ?");
        $stmtStok->bind_param("ii", $p['qty'], $p['id']);
        $stmtStok->execute();
        $stmtStok->close();
    }
} else {
    die("Gagal mendapatkan ID Utama transaksi.");
}

if (!isset($id_pesanan)) {
    header("Location: produk.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil - Sekar Bouquet</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<?php include 'layout/header.php'; ?>

<div class="container text-center py-5">
    <div class="card p-5 shadow-sm mx-auto" style="max-width: 600px; border-radius: 20px; background: white;">
        <h1 class="text-success fw-bold mb-3">Pesanan Berhasil 🎉</h1>
        <p class="fs-5 text-muted">Terima kasih. Pesanan buket kamu sedang kami proses.</p>
        
        <div class="my-4 p-3 bg-light rounded text-start">
            <p class="mb-1"><b>ID Pesanan:</b> #<?php echo $id_pesanan; ?></p>
            <p class="mb-1"><b>Metode Pembayaran:</b> <?php echo htmlspecialchars($pembayaran); ?></p>
            <p class="mb-0"><b>Total Transfer:</b> Rp <?php echo number_format($total, 0, ',', '.'); ?></p>
        </div>

        <div class="alert alert-warning">
            <strong>Menunggu Verifikasi Admin</strong><br>
            Bukti pembayaran Anda sedang diperiksa.
            Nota dapat dicetak setelah pembayaran diverifikasi admin.
        </div>

        <a href="riwayat_pesanan.php"
        class="btn btn-primary btn-lg w-100 mb-3">
            lihat riwayat pesanan
        </a>
        <a href="index.php" class="btn btn-outline-secondary w-100" style="border-radius: 12px;">
            Kembali ke Beranda
        </a>
    </div>
</div>

<?php include 'layout/footer.php'; ?>

</body>
</html>