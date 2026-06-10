<?php
session_start();
include 'koneksi.php';

// JIKA BELUM LOGIN
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// AMBIL DATA
$produkDipilih = $_POST['produk_id'] ?? [];
$jumlahDipilih = $_POST['jumlah'] ?? [];

// kalau tidak pilih apa-apa
if (empty($produkDipilih)) {
    echo "<script>
        alert('Belum ada produk yang dipilih!');
        window.location='produk.php';
    </script>";
    exit();
}

$daftarProduk = [];
$total = 0;

// =====================================================
// AMBIL DATA SEKALIGUS + VALIDASI STOK + HITUNG TOTAL
// =====================================================
foreach ($produkDipilih as $id) {

    $sql = "SELECT id, nama, harga, stok FROM produk WHERE id = ?";
    $stmt = sqlsrv_query($koneksi, $sql, [$id]);

    $produk = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if ($produk) {

        $qty = isset($jumlahDipilih[$id]) ? (int)$jumlahDipilih[$id] : 1;

        if ($qty < 1) $qty = 1;

        // CEK STOK
        if ($qty > $produk['stok']) {
            echo "<script>
                alert('Stok {$produk['nama']} tidak cukup! Sisa: {$produk['stok']}');
                window.location='produk.php';
            </script>";
            exit();
        }

        $subtotal = $produk['harga'] * $qty;

        $daftarProduk[] = [
            'id' => $produk['id'],
            'nama' => $produk['nama'],
            'harga' => $produk['harga'],
            'jumlah' => $qty,
            'subtotal' => $subtotal
        ];

        $total += $subtotal;
    }
}

// kalau semua produk gagal terbaca
if (empty($daftarProduk)) {
    echo "<script>
        alert('Produk tidak valid!');
        window.location='produk.php';
    </script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <title>Checkout - Sekar Bouquet</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@700&display=swap"
          rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>

        body {
            font-family: 'Poppins', sans-serif;
            background: #fff8f9;
            min-height: 100vh;
        }

        h1, h4, h5 {
            font-family: 'Playfair Display', serif;
            color: #b76e79;
            font-weight: 700;
        }

        .checkout-card {
            background: white;
            border: none;
            border-radius: 24px;
            box-shadow: 0 12px 35px rgba(183,110,121,0.12);
        }

        .form-label {
            font-weight: 600;
            color: #8d4f5c;
        }

        .btn-main {
            background: linear-gradient(135deg, #d88b9c, #b76e79);
            color: white;
            border: none;
            border-radius: 14px;
            padding: 14px;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-main:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(183,110,121,0.25);
            color: white;
        }

        .btn-back {
            border: 2px solid #d88b9c;
            color: #b76e79;
            background: transparent;
            border-radius: 14px;
            padding: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
            display: block;
            text-align: center;
        }

        .btn-back:hover {
            background: #fff0f3;
            color: #8d4f5c;
        }

        .bank-option {
            border-radius: 14px;
            border: 2px solid #f1c9d2;
            cursor: pointer;
            transition: 0.3s;
            font-weight: 600;
        }

        .bank-option.active {
            background: #b76e79;
            color: white;
            border-color: #b76e79;
        }

        .box-pembayaran {
            border-radius: 18px;
            padding: 24px;
            background: #fff;
        }

    </style>

</head>

<body class="d-flex flex-column min-vh-100">

<?php include 'layout/header.php'; ?>

<div class="container my-5 flex-grow-1">

    <h1 class="text-center mb-5">

        Checkout Bouquet 🌸

    </h1>

    <?php if (empty($daftarProduk)) { ?>

        <div class="alert alert-danger text-center shadow-sm border-0 py-5 rounded-4">

            <i class="fa-solid fa-cart-shopping fs-1 mb-3"></i>

            <h5 class="fw-bold">

                Belum Ada Bouquet Dipilih

            </h5>

            <p>

                Silakan pilih bouquet terlebih dahulu 💐

            </p>

            <a href="produk.php"
               class="btn btn-main px-5 mt-3 text-decoration-none">

                Kembali ke Katalog

            </a>

        </div>

    <?php } else { ?>

        <div class="row justify-content-center">

            <div class="col-lg-8">

                <div class="card checkout-card p-4 p-md-5">

                    <!-- RINGKASAN -->
                    <h4 class="mb-4 border-bottom pb-3">

                        <i class="fa-solid fa-receipt me-2"></i>

                        Ringkasan Pesanan

                    </h4>

                    <ul class="list-group mb-4">

                        <?php foreach ($daftarProduk as $p) { ?>

                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 border-bottom py-3">

                                <div>

                                    <h6 class="fw-bold mb-1">

                                        <?php echo $p['nama']; ?>

                                    </h6>

                                    <small class="text-muted">

                                        Jumlah: <?php echo $p['jumlah']; ?>x

                                    </small>

                                </div>

                                <span class="fw-bold text-danger">

                                    Rp <?php echo number_format($p['subtotal'], 0, ',', '.'); ?>

                                </span>

                            </li>

                        <?php } ?>

                        <li class="list-group-item d-flex justify-content-between align-items-center border-0 pt-4">

                            <h5 class="mb-0">

                                Total Pembayaran

                            </h5>

                            <h4 class="mb-0 fw-bold text-danger">

                                Rp <?php echo number_format($total, 0, ',', '.'); ?>

                            </h4>

                        </li>

                    </ul>

                    <!-- INFO -->
                    <div class="alert border-0 shadow-sm mb-4"
                         style="background: #fff0f3; border-radius: 18px;">

                        <div class="d-flex align-items-center">

                            <i class="fa-solid fa-location-dot fs-3 me-3 text-danger"></i>

                            <div>

                                <h6 class="fw-bold mb-1">

                                    Lokasi Sekar Bouquet

                                </h6>

                                <p class="mb-0 small">

                                    Surabaya, Jawa Timur 🌸

                                </p>

                            </div>

                        </div>

                    </div>

                    <!-- FORM -->
                    <form method="post"
                          action="proses_pesanan.php"
                          enctype="multipart/form-data"
                          id="formCheckout">

                        <?php foreach ($daftarProduk as $p) { ?>

                            <input type="hidden"
                                   name="produk_id[]"
                                   value="<?php echo $p['id']; ?>">

                            <input type="hidden"
                                   name="jumlah[<?php echo $p['id']; ?>]"
                                   value="<?php echo $p['jumlah']; ?>">

                        <?php } ?>

                        <input type="hidden"
                               name="total_harga"
                               value="<?php echo $total; ?>">

                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <label class="form-label">

                                    Nama Pemesan

                                </label>

                                <input type="text"
                                       name="nama"
                                       class="form-control"
                                       value="<?php echo $_SESSION['username']; ?>"
                                       required>

                            </div>

                            <div class="col-md-6 mb-4">

                                <label class="form-label">

                                    Email Konfirmasi

                                </label>

                                <input type="email"
                                       name="email"
                                       class="form-control"
                                       placeholder="email@gmail.com"
                                       required>

                            </div>

                        </div>

                        <!-- METODE -->
                        <div class="mb-4">

                            <label class="form-label">

                                Pilih Metode Pembayaran

                            </label>

                            <select name="pembayaran"
                                    id="metode_pembayaran"
                                    class="form-select fw-semibold"
                                    onchange="tampilMetode()"
                                    required>

                                <option value="">
                                    -- Pilih Pembayaran --
                                </option>

                                <option value="Transfer Bank">
                                    🏦 Transfer Bank
                                </option>

                                <option value="QRIS">
                                    📱 QRIS
                                </option>

                            </select>

                        </div>

                        <!-- BANK -->
                        <div id="bank_section"
                             style="display:none;"
                             class="mb-4 text-center">

                            <label class="form-label d-block text-start">

                                Pilih Bank

                            </label>

                            <div class="row g-2">

                                <div class="col-4">
                                    <div id="bank_bca"
                                         class="card p-3 bank-option"
                                         onclick="pilihBank('BCA')">

                                        BCA

                                    </div>
                                </div>

                                <div class="col-4">
                                    <div id="bank_bri"
                                         class="card p-3 bank-option"
                                         onclick="pilihBank('BRI')">

                                        BRI

                                    </div>
                                </div>

                                <div class="col-4">
                                    <div id="bank_mandiri"
                                         class="card p-3 bank-option"
                                         onclick="pilihBank('Mandiri')">

                                        Mandiri

                                    </div>
                                </div>

                            </div>

                            <input type="hidden"
                                   name="bank_nama"
                                   id="bank_nama_input">

                        </div>

                        <!-- DETAIL -->
                        <div id="detail_rekening"
                             style="display:none;"
                             class="box-pembayaran border border-info mb-4 shadow-sm text-start">

                            <h5 id="nama_bank"
                                class="text-info mb-1"></h5>

                            <h4 id="no_rek"
                                class="fw-bold mb-3"></h4>

                            <label class="form-label small">

                                Upload Bukti Transfer

                            </label>

                            <input type="file"
                                   name="bukti_transfer"
                                   id="bukti_transfer"
                                   class="form-control"
                                   accept="image/*">

                        </div>

                        <!-- QRIS -->
                        <div id="ewallet_section"
                             style="display:none;"
                             class="box-pembayaran border border-success mb-4 shadow-sm text-center">

                            <h5 class="text-success fw-bold mb-3">

                                Scan QRIS Sekar Bouquet

                            </h5>

                            <img src="assets/gambar/qris.png"
                                 width="180"
                                 class="img-fluid mb-3 border rounded">

                            <label class="form-label d-block text-start small">

                                Upload Bukti QRIS

                            </label>

                            <input type="file"
                                   name="bukti_qris"
                                   id="bukti_qris"
                                   class="form-control"
                                   accept="image/*">

                        </div>

                        <!-- BUTTON -->
                        <button type="submit"
                                class="btn btn-main w-100 btn-lg shadow mt-3">

                            <i class="fa-solid fa-circle-check me-2"></i>

                            Selesaikan Pesanan

                        </button>

                        <a href="produk.php"
                           class="btn btn-back w-100 btn-lg mt-3">

                            <i class="fa-solid fa-arrow-left me-2"></i>

                            Kembali Pilih Bouquet

                        </a>

                    </form>

                </div>

            </div>

        </div>

    <?php } ?>

</div>

<?php include 'layout/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>

function tampilMetode() {

    var metode = document.getElementById("metode_pembayaran").value;

    var bankSection = document.getElementById("bank_section");

    var ewalletSection = document.getElementById("ewallet_section");

    var inputQris = document.getElementById("bukti_qris");

    var inputBank = document.getElementById("bukti_transfer");

    bankSection.style.display =
        (metode == "Transfer Bank")
        ? "block"
        : "none";

    ewalletSection.style.display =
        (metode == "QRIS")
        ? "block"
        : "none";

    document.getElementById("detail_rekening").style.display = "none";

    inputQris.required = false;
    inputBank.required = false;

    if (metode == "QRIS") {

        inputQris.required = true;

    }
}

function pilihBank(bank) {

    document.getElementById("detail_rekening").style.display = "block";

    document.getElementById("bukti_transfer").required = true;

    document.getElementById("bank_nama_input").value = bank;

    var options = document.getElementsByClassName("bank-option");

    for (var i = 0; i < options.length; i++) {

        options[i].classList.remove("active");

    }

    if (bank == "BCA") {

        document.getElementById("nama_bank").innerText = "Bank BCA";

        document.getElementById("no_rek").innerText = "1234 5678 90";

        document.getElementById("bank_bca").classList.add("active");

    }

    else if (bank == "BRI") {

        document.getElementById("nama_bank").innerText = "Bank BRI";

        document.getElementById("no_rek").innerText = "9876 5432 10";

        document.getElementById("bank_bri").classList.add("active");

    }

    else if (bank == "Mandiri") {

        document.getElementById("nama_bank").innerText = "Bank Mandiri";

        document.getElementById("no_rek").innerText = "1122 3344 55";

        document.getElementById("bank_mandiri").classList.add("active");

    }
}

document.getElementById("formCheckout").onsubmit = function() {

    var metode = document.getElementById("metode_pembayaran").value;

    var bankNama = document.getElementById("bank_nama_input").value;

    if (metode == "Transfer Bank" && !bankNama) {

        alert("Silakan pilih bank terlebih dahulu!");

        return false;
    }

    return true;
};

</script>

</body>
</html>