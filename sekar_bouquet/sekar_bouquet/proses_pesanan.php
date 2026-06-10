<?php
session_start();
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

$nama = $_POST['nama'];
$email = $_POST['email'];
$pembayaran = $_POST['pembayaran'];

$total = 0;
$detailData = [];

/* =========================
   1. VALIDASI + HITUNG
========================= */
foreach ($produkDipilih as $id) {

    $sql = "SELECT id, nama, harga, stok FROM produk WHERE id = ?";
    $res = sqlsrv_query($koneksi, $sql, [$id]);
    $produk = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);

    if (!$produk) continue;

    $qty = isset($jumlahDipilih[$id]) ? (int)$jumlahDipilih[$id] : 1;
    if ($qty < 1) $qty = 1;

    if ($qty > $produk['stok']) {
        echo "<script>
            alert('Stok {$produk['nama']} tidak cukup (sisa: {$produk['stok']})');
            window.location='produk.php';
        </script>";
        exit();
    }

    $subtotal = $produk['harga'] * $qty;
    $total += $subtotal;

    $detailData[] = [
        'id' => $produk['id'],
        'nama' => $produk['nama'],
        'harga' => $produk['harga'],
        'qty' => $qty,
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
   3. INSERT HEADER (AMBIL ID AMAN)
========================= */
$sqlInsert = "
INSERT INTO pesanan_header (nama, email, pembayaran, tanggal, total, status, bukti)
VALUES (?, ?, ?, GETDATE(), ?, 'Menunggu Verifikasi', ?)
";

$paramsInsert = [$nama, $email, $pembayaran, $total, $buktiName];

sqlsrv_query($koneksi, $sqlInsert, $paramsInsert);

/* AMBIL ID YANG BARU DI-INSERT (AMAN) */
$sqlId = "SELECT @@IDENTITY AS id";
$resId = sqlsrv_query($koneksi, $sqlId);
$rowId = sqlsrv_fetch_array($resId, SQLSRV_FETCH_ASSOC);

$id_pesanan = $rowId['id'];

/* =========================
   4. INSERT DETAIL + UPDATE STOK
========================= */
foreach ($detailData as $p) {

    sqlsrv_query($koneksi,
        "INSERT INTO pesanan_detail (id_pesanan, produk_id, jumlah, harga, subtotal)
         VALUES (?, ?, ?, ?, ?)",
        [
            $id_pesanan,
            $p['id'],
            $p['qty'],
            $p['harga'],
            $p['subtotal']
        ]
    );

    sqlsrv_query($koneksi,
        "UPDATE produk SET stok = stok - ? WHERE id = ?",
        [$p['qty'], $p['id']]
    );
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pesanan Berhasil - Sekar Bouquet</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>

<body class="bg-light">

<?php include 'layout/header.php'; ?>

<div class="container text-center py-5">

    <h1 class="text-success">Pesanan Berhasil 🎉</h1>

    <p>Terima kasih, <?php echo $nama; ?>. Pesanan kamu sudah masuk.</p>

    <a href="cetak_pdf.php?id=<?php echo $id_pesanan; ?>"
       class="btn btn-primary mt-3">

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