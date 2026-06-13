<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: voucher.php");
    exit();
}

/* PROSES EKSEKUSI CURSOR AUDIT LOYALITAS */
if (isset($_POST['proses_closing'])) {
    $bulan_pilihan = $_POST['bulan']; // Format: YYYY-MM

    // Memanggil Stored Procedure berbasis Cursor Baru
    $sql_call = "CALL sp_audit_loyalitas_pelanggan(?)";
    $stmt = $koneksi->prepare($sql_call);
    $stmt->bind_param("s", $bulan_pilihan);
    
    if ($stmt->execute()) {
        header("Location: closing_admin.php?status=success");
        exit();
    } else {
        die("Gagal menjalankan Cursor: " . $koneksi->error);
    }
}

// Ambil data hasil pembagian voucher oleh Cursor untuk ditampilkan di tabel
$vouchers = $koneksi->query("
    SELECT v.*, p.nama, p.email 
    FROM voucher_pelanggan v 
    JOIN pelanggan p ON v.id_pelanggan = p.id_pelanggan 
    ORDER BY v.id_voucher DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reward Pelanggan (Cursor) - Sekar Bouquet</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background: #fff4f7; 
            margin: 0;
            padding: 0;
        }
        h2, h3, h4, h5 { 
            font-family: 'Playfair Display', serif; 
            color: #b76e79; 
        }

        /* --- STYLE SIDEBAR SINKRON (SAMA RATA) + SCROLLABLE --- */
        .sidebar {
            width: 260px;
            height: 100vh;
            background: #b26a7a; /* Warna mauve/pink gelap sesuai gambar */
            position: fixed;
            top: 0;
            left: 0;
            padding: 30px 24px;
            color: white;
            z-index: 1000;
            
            /* FIX 1: Mengaktifkan scroll vertikal jika menu meluber melebihi tinggi layar */
            overflow-y: auto; 
        }

        /* FIX 2: Modifikasi Kustom Desain Batang Scrollbar Sidebar Agar Cantik & Elegan */
        .sidebar::-webkit-scrollbar {
            width: 6px; /* Ketebalan scrollbar tipis minimalis */
        }
        .sidebar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05); /* Latar belakang track transparan */
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.25); /* Warna pill scrollbar putih transparan masi senada */
            border-radius: 10px;
        }
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.45); /* Warna sedikit lebih terang saat disorot */
        }

        .sidebar h3 {
            color: white !important;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 2rem !important;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            color: #f5e6e8; /* Teks putih agak soft */
            padding: 12px 16px;
            text-decoration: none;
            margin-bottom: 12px;
            border-radius: 14px;
            font-weight: 500;
            font-size: 1.05rem;
            transition: all 0.2s ease;
        }
        .sidebar a i {
            font-size: 1.2rem;
            width: 30px; /* Jarak icon seragam */
        }
        /* Efek hover lembut saat kursor menyentuh menu */
        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        /* --- STYLE KONTEN UTAMA --- */
        .main { 
            margin-left: 260px; 
            padding: 40px; 
        }
        .card-box { 
            border: none; 
            border-radius: 18px; 
            box-shadow: 0 10px 25px rgba(183,110,121,0.08); 
        }
        .bg-gradient-pink { 
            background: linear-gradient(135deg, #d88b9c, #b76e79); 
            color: white; 
        }
        .btn-main { 
            background: linear-gradient(135deg, #d88b9c, #b76e79); 
            color: white; 
            border: none; 
            border-radius: 12px;
            padding: 10px 20px;
        }
        .btn-main:hover { 
            color: white; 
            opacity: 0.9; 
        }
        .table-responsive {
            background: white;
            border-radius: 12px;
            padding: 10px;
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <h2 class="mb-4">Pembagian Voucher Bulanan (Fitur Cursor) ⚙️</h2>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Sukses Audit!</strong> Engine <b>Cursor</b> database telah memeriksa transaksi tiap pelanggan satu per satu dan berhasil menjerat pelanggan loyal untuk diberikan voucher diskon khusus.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card card-box p-4 bg-white mb-4">
        <h5 class="fw-bold mb-3"><i class="fa fa-gift me-1"></i> Jalankan Engine Cursor Evaluasi Loyalitas</h5>
        <form method="POST" action="">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Pilih Bulan Transaksi Pelanggan</label>
                    <input type="month" name="bulan" class="form-control" required value="<?= date('Y-m') ?>">
                </div>
                <div class="col-md-6">
                    <button type="submit" name="proses_closing" class="btn btn-main w-100">
                        <i class="fa fa-sync fa-spin me-1"></i> Audit & Generate Voucher Via Cursor
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="card card-box p-4 bg-white">
        <h5 class="fw-bold mb-3">Daftar Voucher Hasil Cetakan Sinkronisasi Cursor</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle m-0">
                <thead class="table-light">
                    <tr>
                        <th>ID Voucher</th>
                        <th>Nama Pelanggan</th>
                        <th>Email</th>
                        <th>Kode Voucher Kustom</th>
                        <th>Nilai Potongan (Rp)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($vouchers && $vouchers->num_rows > 0): ?>
                        <?php while ($row = $vouchers->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $row['id_voucher'] ?></td>
                            <td><b><?= htmlspecialchars($row['nama']) ?></b></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><code style="font-size: 1.1rem; color: #b76e79;"><?= $row['kode_voucher'] ?></code></td>
                            <td class="text-success fw-bold">Rp <?= number_format($row['potongan_harga'], 0, ',', '.') ?></td>
                            <td><span class="badge bg-success"><?= $row['status_aktif'] ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada voucher yang digenerate oleh Cursor bulan ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>