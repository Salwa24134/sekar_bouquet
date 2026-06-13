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

$id_pelanggan = $_SESSION['id_pelanggan'] ?? $_SESSION['id_user'] ?? $_SESSION['id'] ?? null; 

if (!$id_pelanggan) {
    echo "<script>
        alert('Sesi Anda telah habis atau Anda belum login. Silakan login kembali.');
        window.location='login.php';
    </script>";
    exit();
}

// Menangkap data input baru dari Form Checkout
$no_hp           = $_POST['no_hp'] ?? '';
$alamat          = $_POST['alamat'] ?? '';
$waktu_kirim     = $_POST['waktu_kirim'] ?? '';
$catatan_kartu   = $_POST['catatan_kartu'] ?? '';
$catatan_custom  = $_POST['catatan_custom'] ?? '';
$pembayaran      = $_POST['pembayaran'] ?? '';

/* ==========================================================================
   INTEGRASI TANGKAP VOUCHER LOYALITAS
   ========================================================================== */
$id_voucher       = isset($_POST['id_voucher']) ? (int)$_POST['id_voucher'] : 0;
$potongan_voucher = isset($_POST['potongan_voucher']) ? (int)$_POST['potongan_voucher'] : 0;

$total           = 0;
$detailData      = [];

// Gabungkan catatan kartu, kustomisasi, & waktu kirim agar masuk ke sistem riwayat admin
$gabung_catatan = "Catatan Custom: " . $catatan_custom;
if (!empty($catatan_kartu)) {
    $gabung_catatan .= " | Isi Kartu Ucapan: " . $catatan_kartu;
}
if (!empty($waktu_kirim)) {
    $gabung_catatan .= " | Batas Waktu Kirim: " . $waktu_kirim;
}

/* =====================================================
   1. VALIDASI + HITUNG PRODUK MURNI
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
    $total += $subtotal; // Menghitung total harga produk murni

    $detailData[] = [
        'id'       => $produk['id_produk'],
        'nama'     => $produk['nama_produk'],
        'harga'    => $produk['harga_jual'],
        'qty'      => $qty,
        'subtotal' => $subtotal
    ];
}

/* ==========================================================================
   ENGINE LOGIKA BISNIS: HITUNG ONGKOS RAKIT BERDASARKAN TOTAL BELANJA PRODUK
   ========================================================================== */
$total_produk = $total; // Mengamankan nominal harga produk asli

if ($total_produk < 200000) {
    $ongkos_rakit = 25000;
} elseif ($total_produk >= 200000 && $total_produk < 500000) {
    $ongkos_rakit = 50000;
} else {
    $ongkos_rakit = 75000;
}

// Tambahkan ongkos rakit bertingkat ke dalam akumulasi total akhir
$total += $ongkos_rakit; 

// Kurangi dengan potongan voucher loyalitas hasil tangkapan engine cursor
$total = $total - $potongan_voucher;

// Proteksi antitesis nilai minus aman kalkulasi
if ($total < 0) {
    $total = 0;
}

/* =====================================================
   2. UPLOAD BUKTI PEMBAYARAN (Ke Folder assets/bukti/)
===================================================== */
$buktiName = "";
$file = null;

if (!empty($_FILES['bukti_transfer']['name'])) {
    $file = $_FILES['bukti_transfer'];
} elseif (!empty($_FILES['bukti_qris']['name'])) {
    $file = $_FILES['bukti_qris'];
}

if ($file && $file['error'] == 0) {
    $folder = "assets/bukti/"; 
    if (!is_dir($folder)) mkdir($folder, 0777, true);

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $buktiName = "BUKTI_" . time() . "_" . uniqid() . "." . $ext;

    move_uploaded_file($file['tmp_name'], $folder . $buktiName);
}

/* =====================================================
   3. CEK / UPDATE DATA PELANGGAN
===================================================== */
$cekPelanggan = $koneksi->prepare("SELECT id_pelanggan FROM pelanggan WHERE id_pelanggan = ?");
$cekPelanggan->bind_param("i", $id_pelanggan);
$cekPelanggan->execute();
$resCek = $cekPelanggan->get_result();

if ($resCek->num_rows == 0) {
    $username = $_SESSION['username'];
    $email_input = $_POST['email'] ?? '';
    $stmtTambah = $koneksi->prepare("INSERT INTO pelanggan (id_pelanggan, nama, email, telepon, alamat) VALUES (?, ?, ?, ?, ?)");
    $stmtTambah->bind_param("issss", $id_pelanggan, $username, $email_input, $no_hp, $alamat);
    $stmtTambah->execute();
    $stmtTambah->close();
} else {
    $stmtUpdatePel = $koneksi->prepare("UPDATE pelanggan SET telepon = ?, alamat = ? WHERE id_pelanggan = ?");
    $stmtUpdatePel->bind_param("ssi", $no_hp, $alamat, $id_pelanggan);
    $stmtUpdatePel->execute();
    $stmtUpdatePel->close();
}
$cekPelanggan->close();

/* =====================================================
   4. INSERT KE TABEL UTAMA (pesanan) WITH TRANSACTION
===================================================== */
$koneksi->begin_transaction();

try {
    // Nilai $total di bawah sudah akurat (Harga komponen + Ongkos rakit dinamis - Potongan Voucher)
    $sqlInsert = "INSERT INTO pesanan (id_pelanggan, tanggal, total, status, metode_pembayaran, bukti) VALUES (?, NOW(), ?, 'Pending', ?, ?)";
    $stmtHeader = $koneksi->prepare($sqlInsert);
    $stmtHeader->bind_param("iiss", $id_pelanggan, $total, $pembayaran, $buktiName);
    $stmtHeader->execute();
    $id_pesanan = $koneksi->insert_id;
    $stmtHeader->close();

    /* ==========================================================================
       ENGINE DEAKTIVASI VOUCHER (Sinkronisasi Penggunaan Voucher Agar Tidak Hang)
       ========================================================================== */
    if ($id_voucher > 0 && $potongan_voucher > 0) {
        $stmtUpdateVoucher = $koneksi->prepare("UPDATE voucher_pelanggan SET status_aktif = 'Tidak Aktif' WHERE id_voucher = ? AND id_pelanggan = ?");
        $stmtUpdateVoucher->bind_param("ii", $id_voucher, $id_pelanggan);
        $stmtUpdateVoucher->execute();
        $stmtUpdateVoucher->close();
    }

    /* =====================================================
       5. INSERT KE TABEL BUKET CUSTOM (bouquet_custom)
    ===================================================== */
    $nama_bouquet = "Custom Bouquet #" . $id_pesanan;

    $sqlCustom = "INSERT INTO bouquet_custom (id_pesanan, nama_bouquet, catatan, ongkos_rakit) VALUES (?, ?, ?, ?)";
    $stmtCustom = $koneksi->prepare($sqlCustom);
    if ($stmtCustom) {
        // Menyimpan nilai ongkos rakit dinamis (25k / 50k / 75k) hasil kalkulasi engine ke database
        $stmtCustom->bind_param("issi", $id_pesanan, $nama_bouquet, $gabung_catatan, $ongkos_rakit);
        $stmtCustom->execute();
        $id_bouquet = $stmtCustom->insert_id; 
        $stmtCustom->close();
    }

    /* =====================================================
       6. DETAIL PESANAN & POTONG STOK OTOMATIS
    ===================================================== */
    foreach ($detailData as $p) {
        $sqlDetail = "INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, harga, subtotal) VALUES (?, ?, ?, ?, ?)";
        $stmtDetail = $koneksi->prepare($sqlDetail);
        $stmtDetail->bind_param("iiiii", $id_pesanan, $p['id'], $p['qty'], $p['harga'], $p['subtotal']);
        $stmtDetail->execute();
        $stmtDetail->close();

        $sqlUpdateStok = "UPDATE produk SET stok = stok - ? WHERE id_produk = ?";
        $stmtStok = $koneksi->prepare($sqlUpdateStok);
        $stmtStok->bind_param("ii", $p['qty'], $p['id']);
        $stmtStok->execute();
        $stmtStok->close();

        if (isset($id_bouquet) && $id_bouquet > 0) {
            $sqlDetailBuket = "INSERT INTO detail_bouquet (id_bouquet, id_produk, jumlah) VALUES (?, ?, ?)";
            $stmtDetailBuket = $koneksi->prepare($sqlDetailBuket);
            if ($stmtDetailBuket) {
                $stmtDetailBuket->bind_param("iii", $id_bouquet, $p['id'], $p['qty']);
                $stmtDetailBuket->execute();
                $stmtDetailBuket->close();
            }
        }
    }

    $koneksi->commit();
    unset($_SESSION['keranjang']);
    $_SESSION['last_order_id'] = $id_pesanan;

} catch (Exception $e) {
    $koneksi->rollback();
    echo "<script>alert('Gagal memproses pesanan: " . addslashes($e->getMessage()) . "'); window.location='produk.php';</script>";
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #fff8f9; }
        .font-serif { font-family: 'Playfair Display', serif; color: #8d4f5c; }
        .success-card { background: #ffffff; border: none; border-radius: 24px; box-shadow: 0 15px 35px rgba(183, 110, 121, 0.1); }
        .icon-bounce { animation: bounce 2s infinite; display: inline-block; color: #b76e79; }
        @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        .invoice-box { background-color: #fffafb; border: 2px dashed #f2d1d7; border-radius: 16px; }
        .btn-primary-custom { background: linear-gradient(135deg, #d88b9c, #b76e79); color: white; border: none; border-radius: 12px; padding: 12px 24px; font-weight: 600; transition: all 0.3s ease; }
        .btn-primary-custom:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(183, 110, 121, 0.25); color: white; }
        .btn-outline-custom { border: 2px solid #d88b9c; color: #b76e79; background: transparent; border-radius: 12px; padding: 11px 24px; font-weight: 600; transition: all 0.3s ease; }
        .btn-outline-custom:hover { background: #fff0f3; color: #8d4f5c; }
        .alert-custom { background-color: #fff3cd; border-left: 4px solid #ffc107; color: #664d03; border-radius: 12px; }
    </style>
</head>
<body>

<?php include 'layout/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card success-card p-4 p-sm-5 text-center">
                <div class="mb-3">
                    <span class="icon-bounce" style="font-size: 4.5rem;">
                        <i class="bi bi-check-circle-fill"></i>
                    </span>
                </div>
                
                <h1 class="font-serif fw-bold mb-2">Pesanan Berhasil! 🎉</h1>
                <p class="text-muted small px-3 mb-4">Terima kasih banyak. Buket kustom pilihanmu sudah masuk antrean sistem dan siap dirangkai oleh tim kami.</p>
                
                <div class="invoice-box p-4 text-start mb-4">
                    <h5 class="fw-semibold mb-3 text-dark d-flex align-items-center" style="font-family: 'Playfair Display', serif;">
                        <i class="bi bi-receipt me-2 text-secondary"></i> Rincian Transaksi
                    </h5>
                    <hr class="text-muted opacity-25 my-2">
                    
                    <div class="row g-2 pt-2 small">
                        <div class="col-5 text-muted">ID Pesanan</div>
                        <div class="col-7 fw-bold text-dark text-end">#<?php echo $id_pesanan; ?></div>
                        
                        <div class="col-5 text-muted">Metode Bayar</div>
                        <div class="col-7 fw-medium text-dark text-end">
                            <span class="badge bg-white text-dark border px-2 py-1"><?php echo htmlspecialchars($pembayaran ?: 'Transfer Bank'); ?></span>
                        </div>

                        <div class="col-5 text-muted">Total Komponen</div>
                        <div class="col-7 fw-medium text-dark text-end">Rp <?php echo number_format($total_produk, 0, ',', '.'); ?></div>

                        <div class="col-5 text-muted">Ongkos Rakit Buket</div>
                        <div class="col-7 fw-medium text-danger-emphasis text-end">+ Rp <?php echo number_format($ongkos_rakit, 0, ',', '.'); ?></div>
                        
                        <?php if ($potongan_voucher > 0): ?>
                        <div class="col-5 text-success fw-medium">Potongan Voucher</div>
                        <div class="col-7 fw-bold text-success text-end">- Rp <?php echo number_format($potongan_voucher, 0, ',', '.'); ?></div>
                        <?php endif; ?>

                        <hr class="text-muted opacity-25 my-1">
                        
                        <div class="col-5 text-muted align-self-center">Total Pembayaran</div>
                        <div class="col-7 fw-bold text-end fs-5" style="color: #8d4f5c;">Rp <?php echo number_format($total, 0, ',', '.'); ?></div>
                    </div>
                    
                    <?php if (!empty($catatan_custom) || !empty($catatan_kartu) || !empty($waktu_kirim)): ?>
                        <hr class="text-muted opacity-25 my-3">
                        <div class="bg-white p-3 rounded border border-light">
                            <?php if(!empty($waktu_kirim)): ?>
                                <p class="mb-1 small"><strong><i class="bi bi-calendar-event me-1"></i> Waktu Kirim:</strong></p>
                                <p class="text-muted small mb-2"><?php echo htmlspecialchars($waktu_kirim); ?></p>
                            <?php endif; ?>

                            <?php if(!empty($catatan_custom)): ?>
                                <p class="mb-1 small"><strong><i class="bi bi-pencil-square me-1"></i> Req Custom:</strong></p>
                                <p class="text-muted small mb-2">"<?php echo htmlspecialchars($catatan_custom); ?>"</p>
                            <?php endif; ?>
                            
                            <?php if(!empty($catatan_kartu)): ?>
                                <p class="mb-1 small"><strong><i class="bi bi-card-text me-1"></i> Isi Kartu Ucapan:</strong></p>
                                <p class="text-muted small mb-0">"<?php echo htmlspecialchars($catatan_kartu); ?>"</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="alert alert-custom text-start d-flex align-items-start gap-3 p-3 mb-4">
                    <i class="bi bi-hourglass-split fs-4 mt-1 flex-shrink-0" style="color: #b58105"></i>
                    <div>
                        <strong class="d-block mb-1 text-warning-emphasis">Menunggu Verifikasi Admin</strong>
                        <span class="small opacity-90 d-block" style="font-size: 11px; line-height: 1.4;">Bukti pembayaran kamu sedang kami validasi. Status akan diperbarui secara berkala pada halaman riwayat.</span>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <a href="riwayat_pesanan.php" class="btn btn-primary-custom btn-lg d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-clock-history"></i> Lihat Riwayat Pesanan
                    </a>
                    <a href="index.php" class="btn btn-outline-custom d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-house-door"></i> Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>
</body>
</html>