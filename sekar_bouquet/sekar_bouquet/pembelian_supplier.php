<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';

/* Proteksi Akses Admin */
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

/* PROSES SIMPAN TRANSAKSI PEMBELIAN */
if (isset($_POST['simpan_pembelian'])) {
    $id_supplier = (int)$_POST['id_supplier'];
    $id_produk = (int)$_POST['id_produk'];
    $jumlah = (int)$_POST['jumlah'];
    $harga_beli = (int)$_POST['harga_beli'];
    $subtotal = $jumlah * $harga_beli;

    // 1. Masukkan data ke tabel induk: pembelian
    $sql_pembelian = "INSERT INTO pembelian (id_supplier, tanggal, total_beli) VALUES (?, NOW(), ?)";
    $stmt1 = $koneksi->prepare($sql_pembelian);
    $stmt1->bind_param("ii", $id_supplier, $subtotal);
    
    if ($stmt1->execute()) {
        $id_pembelian = $koneksi->insert_id; // Mengambil ID pembelian barusan
        $stmt1->close();

        // 2. Masukkan data ke tabel anak: detail_pembelian
        // (Memicu TRIGGER otomatis menambah stok di tabel produk)
        $sql_detail = "INSERT INTO detail_pembelian (id_pembelian, id_produk, jumlah, harga_beli, subtotal) VALUES (?, ?, ?, ?, ?)";
        $stmt2 = $koneksi->prepare($sql_detail);
        $stmt2->bind_param("iiiii", $id_pembelian, $id_produk, $jumlah, $harga_beli, $subtotal);
        $stmt2->execute();
        $stmt2->close();

        header("Location: pembelian_supplier.php?status=success");
        exit();
    }
}

// Ambil Data Supplier & Produk untuk Form Dropdown Select
$suppliers = $koneksi->query("SELECT id_supplier, nama_supplier FROM supplier ORDER BY nama_supplier ASC");
$produks = $koneksi->query("SELECT id_produk, nama_produk FROM produk ORDER BY nama_produk ASC");

// Ambil Data Histori yang sudah diinput untuk ditampilkan di tabel bawah
$histori_pembelian = $koneksi->query("
    SELECT p.id_pembelian, p.tanggal, s.nama_supplier, pr.nama_produk, dp.jumlah, dp.harga_beli, p.total_beli 
    FROM pembelian p
    JOIN supplier s ON p.id_supplier = s.id_supplier
    JOIN detail_pembelian dp ON p.id_pembelian = dp.id_pembelian
    JOIN produk pr ON dp.id_produk = pr.id_produk
    ORDER BY p.id_pembelian DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembelian Supplier - Sekar Bouquet</title>
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
    <h2 class="mb-4">Restok Barang & Pembelian Supplier 📦</h2>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Berhasil!</strong> Transaksi restok berhasil dicatat dan kuantitas gudang otomatis bertambah.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card card-box p-4 bg-white mb-4">
        <h5 class="fw-bold mb-3"><i class="fa fa-plus-circle me-1"></i> Catat Nota Belanja Gudang</h5>
        <form method="POST" action="">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Pilih Mitra Supplier</label>
                    <select name="id_supplier" class="form-select" required>
                        <option value="">-- Pilih Supplier --</option>
                        <?php while($s = $suppliers->fetch_assoc()): ?>
                            <option value="<?= $s['id_supplier'] ?>"><?= htmlspecialchars($s['nama_supplier']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Pilih Komoditas Bunga/Barang</label>
                    <select name="id_produk" class="form-select" required>
                        <option value="">-- Pilih Produk --</option>
                        <?php while($p = $produks->fetch_assoc()): ?>
                            <option value="<?= $p['id_produk'] ?>"><?= htmlspecialchars($p['nama_produk']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">Jumlah Pasokan (Qty)</label>
                    <input type="number" name="jumlah" class="form-control" min="1" placeholder="Contoh: 100" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">Harga Beli per Satuan (Rp)</label>
                    <input type="number" name="harga_beli" class="form-control" min="0" placeholder="Contoh: 2000" required>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" name="simpan_pembelian" class="btn btn-main w-100">
                        <i class="fa fa-save me-1"></i> Simpan Transaksi Belanja
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="card card-box p-4 bg-white">
        <h5 class="fw-bold mb-3">Arus Jurnal Belanja Restok</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle m-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal Belanja</th>
                        <th>Mitra Supplier</th>
                        <th>Produk Terbeli</th>
                        <th>Kuantitas</th>
                        <th>Harga Satuan</th>
                        <th>Total Pengeluaran</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($histori_pembelian && $histori_pembelian->num_rows > 0): ?>
                        <?php while ($row = $histori_pembelian->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('d M Y, H:i', strtotime($row['tanggal'])) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($row['nama_supplier']) ?></span></td>
                            <td><b><?= htmlspecialchars($row['nama_produk']) ?></b></td>
                            <td><?= number_format($row['jumlah'], 0, ',', '.') ?> pcs</td>
                            <td>Rp <?= number_format($row['harga_beli'], 0, ',', '.') ?></td>
                            <td class="text-danger fw-bold">Rp <?= number_format($row['total_beli'], 0, ',', '.') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada pencatatan pengeluaran supplier masuk.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>