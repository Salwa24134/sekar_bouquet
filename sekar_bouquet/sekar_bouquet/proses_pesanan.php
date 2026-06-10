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

// Memastikan variabel POST tersedia untuk menghindari error "undefined array key"
$nama       = $_POST['nama'] ?? '';
$email      = $_POST['email'] ?? '';
$pembayaran = $_POST['pembayaran'] ?? '';

$total      = 0;
$detailData = [];

/* =========================
   1. VALIDASI + HITUNG (MySQLi)
========================= */
foreach ($produkDipilih as $id) {
    $id = (int)$id;

    $stmt = $koneksi->prepare("SELECT id, nama, harga, stok FROM produk WHERE id = ?");
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
            alert('Stok " . addslashes($produk['nama']) . " tidak cukup (sisa: {$produk['stok']})');
            window.location='produk.php';
        </script>";
        exit();
    }

    $subtotal = $produk['harga'] * $qty;
    $total += $subtotal;

    $detailData[] = [
        'id'       => $produk['id'],
        'nama'     => $produk['nama'],
        'harga'    => $produk['harga'],
        'qty'      => $qty,
        'subtotal' => $subtotal
    ];
}

/* =========================
   2. UPLOAD BUKTI
========================= */
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

/* =========================
   3. INSERT HEADER (Menggunakan insert_id MySQLi)
========================= */
// GETDATE() di SQL Server diganti menjadi NOW() di MySQL
$sqlInsert = "
    INSERT INTO pesanan_header (nama, email, pembayaran, tanggal, total, status, bukti)
    VALUES (?, ?, ?, NOW(), ?, 'Menunggu Verifikasi', ?)
";

$stmtHeader = $koneksi->prepare($sqlInsert);
$stmtHeader->bind_param("sssis", $nama, $email, $pembayaran, $total, $buktiName);
$stmtHeader->execute();

// Cara mutakhir & aman mengambil ID Auto-Increment terakhir di MySQLi
$id_pesanan = $koneksi->insert_id; 
$stmtHeader->close();

/* =========================
   4. INSERT DETAIL + UPDATE STOK (MySQLi)
========================= */
if ($id_pesanan > 0) {
    foreach ($detailData as $p) {
        // Insert ke pesanan_detail
        $stmtDetail = $koneksi->prepare("INSERT INTO pesanan_detail (id_pesanan, produk_id, jumlah, harga, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stmtDetail->bind_param("iiiii", $id_pesanan, $p['id'], $p['qty'], $p['harga'], $p['subtotal']);
        $stmtDetail->execute();
        $stmtDetail->close();

        // Potong stok produk
        $stmtStok = $koneksi->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
        $stmtStok->bind_param("ii", $p['qty'], $p['id']);
        $stmtStok->execute();
        $stmtStok->close();
    }
} else {
    die("Gagal memproses pembuatan ID Transaksi Header.");
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

    <h1 class="text-success">Pesanan Berhasil 🎉</h1>
    <p>Terima kasih, <?php echo htmlspecialchars($nama); ?>. Pesanan kamu sudah masuk.</p>

    <a href="cetak_pdf.php?id=<?php echo (int)$id_pesanan; ?>" class="btn btn-primary mt-3">
        Download Nota
    </a>

    <br><br>

    <a href="index.php" class="btn btn-secondary">
        Kembali ke Beranda
    </a>

</div>

<?php include 'layout/footer.php'; ?>

</body>
</html>