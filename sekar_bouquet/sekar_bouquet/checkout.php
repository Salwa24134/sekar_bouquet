<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

$stmt = $koneksi->prepare("SELECT id_produk, nama_produk, harga_jual, stok FROM produk WHERE id_produk = ?");

foreach ($produkDipilih as $id) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $produk = $result->fetch_assoc();

    if ($produk) {
        $qty = isset($jumlahDipilih[$id]) ? (int)$jumlahDipilih[$id] : 1;

        if ($qty < 1) $qty = 1;

        // CEK STOK
        if ($qty > $produk['stok']) {
            echo "<script>
                alert('Stok " . addslashes($produk['nama_produk']) . " tidak cukup! Sisa: {$produk['stok']}');
                window.location='produk.php';
            </script>";
            exit();
        }

        $subtotal = $produk['harga_jual'] * $qty;

        $daftarProduk[] = [
            'id' => $produk['id_produk'],
            'nama' => $produk['nama_produk'],
            'harga' => $produk['harga_jual'],
            'jumlah' => $qty,
            'subtotal' => $subtotal
        ];

        $total += $subtotal;
    }
}
$stmt->close();

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Sekar Bouquet</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #fff8f9; min-height: 100vh; }
        h1, h4, h5 { font-family: 'Playfair Display', serif; color: #8d4f5c; font-weight: 700; }
        .checkout-card { background: white; border: none; border-radius: 24px; box-shadow: 0 12px 35px rgba(183,110,121,0.12); }
        .form-label { font-weight: 600; color: #8d4f5c; }
        .btn-main { background: linear-gradient(135deg, #d88b9c, #b76e79); color: white; border: none; border-radius: 14px; padding: 14px; font-weight: 600; transition: 0.3s; }
        .btn-main:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(183,110,121,0.25); color: white; }
        .btn-back { border: 2px solid #d88b9c; color: #b76e79; background: transparent; border-radius: 14px; padding: 14px; font-weight: 600; text-decoration: none; transition: 0.3s; display: block; text-align: center; }
        .btn-back:hover { background: #fff0f3; color: #8d4f5c; }
        .bank-option { border-radius: 14px; border: 2px solid #f1c9d2; cursor: pointer; transition: 0.3s; font-weight: 600; color: #8d4f5c; }
        .bank-option.active { background: #b76e79; color: white; border-color: #b76e79; }
        .box-pembayaran { border-radius: 18px; padding: 24px; background: #fff; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<?php include 'layout/header.php'; ?>

<div class="container my-5 flex-grow-1">
    <h1 class="text-center mb-5 text-uppercase">Checkout Bouquet 🌸</h1>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card checkout-card p-4 p-md-5">
                <h4 class="mb-4 border-bottom pb-3"><i class="fa-solid fa-receipt me-2"></i> Ringkasan Komponen & Produk</h4>
                <ul class="list-group mb-4">
                    <?php foreach ($daftarProduk as $p) { ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center border-0 border-bottom py-3">
                            <div>
                                <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($p['nama']); ?></h6>
                                <small class="text-muted">Jumlah: <?php echo $p['jumlah']; ?>x</small>
                            </div>
                            <span class="fw-bold" style="color: #b76e79;">Rp <?php echo number_format($p['subtotal'], 0, ',', '.'); ?></span>
                        </li>
                    <?php } ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center border-0 pt-4">
                        <h5 class="mb-0">Total Pembayaran</h5>
                        <h4 class="mb-0 fw-bold" style="color: #8d4f5c;">Rp <?php echo number_format($total, 0, ',', '.'); ?></h4>
                    </li>
                </ul>

                <form method="post" action="proses_pesanan.php" enctype="multipart/form-data" id="formCheckout">
                    <?php foreach ($daftarProduk as $p) { ?>
                        <input type="hidden" name="produk_id[]" value="<?php echo $p['id']; ?>">
                        <input type="hidden" name="jumlah[<?php echo $p['id']; ?>]" value="<?php echo $p['jumlah']; ?>">
                    <?php } ?>

                    <h5 class="mb-3 mt-2 text-muted"><i class="fa-solid fa-user me-2"></i> Data Pemesan & Pengiriman</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Pemesan</label>
                            <input type="text" name="nama" class="form-control" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nomor WhatsApp / HP</label>
                            <input type="tel" name="no_hp" class="form-control" placeholder="Contoh: 08123456789" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Konfirmasi</label>
                            <input type="email" name="email" class="form-control" placeholder="email@gmail.com" required>
                        </div>
                        <?php 
                        date_default_timezone_set('Asia/Jakarta');
                        $waktu_minimal = date('Y-m-d\TH:i', strtotime('+24 hours'));
                        ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-secondary">Waktu Pengiriman Buket 📅</label>
                            <input type="datetime-local" 
                                name="waktu_kirim" 
                                id="waktu_pengiriman" 
                                class="form-control" 
                                min="<?php echo $waktu_minimal; ?>" 
                                required>
                            <div class="form-text text-danger">
                                * Paling cepat buket dikirim 24 jam dari waktu sekarang (butuh proses merangkai).
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat Lengkap Pengiriman</label>
                        <textarea name="alamat" class="form-control" rows="3" placeholder="Tuliskan alamat lengkap..." required></textarea>
                    </div>

                    <h5 class="mb-3 mt-4 text-muted"><i class="fa-solid fa-wand-magic-sparkles me-2"></i> Request Kustomisasi</h5>
                    <div class="mb-3">
                        <label class="form-label">Isi Kartu Ucapan (Opsional)</label>
                        <textarea name="catatan_kartu" class="form-control" rows="3" placeholder="Tuliskan ucapan..."></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Catatan Custom Bouquet (Opsional)</label>
                        <textarea name="catatan_custom" class="form-control" rows="3" placeholder="Request warna wrapping..."></textarea>
                    </div>

                    <h5 class="mb-3 mt-4 text-muted"><i class="fa-solid fa-credit-card me-2"></i> Metode Pembayaran</h5>
                    <div class="mb-4">
                        <select name="pembayaran" id="metode_pembayaran" class="form-select fw-semibold" onchange="tampilMetode()" required>
                            <option value="">-- Pilih Pembayaran --</option>
                            <option value="Transfer Bank">🏦 Transfer Bank</option>
                            <option value="QRIS">📱 QRIS</option>
                        </select>
                    </div>

                    <div id="bank_section" style="display:none;" class="mb-4 text-center">
                        <label class="form-label d-block text-start">Pilih Bank Tujuan</label>
                        <div class="row g-2">
                            <div class="col-4"><div id="bank_bca" class="card p-3 bank-option" onclick="pilihBank('BCA')">BCA</div></div>
                            <div class="col-4"><div id="bank_bri" class="card p-3 bank-option" onclick="pilihBank('BRI')">BRI</div></div>
                            <div class="col-4"><div id="bank_mandiri" class="card p-3 bank-option" onclick="pilihBank('Mandiri')">Mandiri</div></div>
                        </div>
                        <input type="hidden" name="bank_nama" id="bank_nama_input">
                    </div>

                    <div id="detail_rekening" style="display:none;" class="box-pembayaran border mb-4 shadow-sm text-start">
                        <h5 id="nama_bank" class="mb-1" style="color: #8d4f5c;"></h5>
                        <h4 id="no_rek" class="fw-bold mb-3" style="color: #b76e79;"></h4>
                        <label class="form-label small">Upload Bukti Transfer</label>
                        <input type="file" name="bukti_transfer" id="bukti_transfer" class="form-control" accept="image/*">
                    </div>

                    <div id="ewallet_section" style="display:none;" class="box-pembayaran border mb-4 shadow-sm text-center">
                        <h5 class="fw-bold mb-3" style="color: #8d4f5c;">Scan QRIS Sekar Bouquet</h5>
                        <img src="assets/gambar/qris.png" width="180" class="img-fluid mb-3 border rounded" alt="QRIS Code">
                        <label class="form-label d-block text-start small">Upload Bukti QRIS</label>
                        <input type="file" name="bukti_qris" id="bukti_qris" class="form-control" accept="image/*">
                    </div>

                    <button type="submit" class="btn btn-main w-100 btn-lg shadow mt-3"><i class="fa-solid fa-circle-check me-2"></i> Selesaikan Pesanan</button>
                    <a href="produk.php" class="btn btn-back w-100 btn-lg mt-3"><i class="fa-solid fa-arrow-left me-2"></i> Kembali Pilih Komponen</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>

<script>
function tampilMetode() {
    var metode = document.getElementById("metode_pembayaran").value;
    var bankSection = document.getElementById("bank_section");
    var ewalletSection = document.getElementById("ewallet_section");
    var inputQris = document.getElementById("bukti_qris");
    var inputBank = document.getElementById("bukti_transfer");

    bankSection.style.display = (metode == "Transfer Bank") ? "block" : "none";
    ewalletSection.style.display = (metode == "QRIS") ? "block" : "none";
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
    for (var i = 0; i < options.length; i++) { options[i].classList.remove("active"); }

    if (bank == "BCA") {
        document.getElementById("nama_bank").innerText = "Bank BCA";
        document.getElementById("no_rek").innerText = "1234 5678 90";
        document.getElementById("bank_bca").classList.add("active");
    } else if (bank == "BRI") {
        document.getElementById("nama_bank").innerText = "Bank BRI";
        document.getElementById("no_rek").innerText = "9876 5432 10";
        document.getElementById("bank_bri").classList.add("active");
    } else if (bank == "Mandiri") {
        document.getElementById("nama_bank").innerText = "Bank Mandiri";
        document.getElementById("no_rek").innerText = "1122 3344 55";
        document.getElementById("bank_mandiri").classList.add("active");
    }
}

// PERBAIKAN VALIDASI UTAMA: Mengunci Form Onsubmit agar Jam Tidak Bisa Diakali Inspect Element
document.getElementById("formCheckout").onsubmit = function() {
    var metode = document.getElementById("metode_pembayaran").value;
    var bankNama = document.getElementById("bank_nama_input").value;
    var inputWaktu = document.getElementById("waktu_pengiriman").value;

    // 1. Cek pengisian metode pembayaran
    if (metode == "Transfer Bank" && !bankNama) {
        alert("Silakan pilih bank terlebih dahulu!");
        return false;
    }

    // 2. Kunci Jam Real-time (JavaScript Unix Timestamp)
    if (!inputWaktu) {
        alert("Waktu pengiriman wajib ditentukan!");
        return false;
    }

    var waktuUser = new Date(inputWaktu).getTime();
    var waktuSekarang = new Date().getTime();
    
    // Batas minimal: Sekarang + 24 jam (24 jam * 60 menit * 60 detik * 1000 milidetik)
    var batasMinimal = waktuSekarang + (24 * 60 * 60 * 1000);

    if (waktuUser < waktuSekarang) {
        alert("Cacat Logika! Waktu pengiriman tidak boleh di masa lalu.");
        return false;
    }

    if (waktuUser < batasMinimal) {
        alert("Gagal! Pemesanan bouquet kustom wajib dilakukan minimal H-24 jam sebelum pengiriman agar tim punya waktu merangkai.");
        return false;
    }

    return true;
};
</script>
</body>
</html>