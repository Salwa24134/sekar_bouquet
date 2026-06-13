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

/* PROSES SIMPAN BARANG RUSAK / LAYU */
if (isset($_POST['simpan_rusak'])) {
    $id_produk = (int)$_POST['id_produk'];
    $jumlah = (int)$_POST['jumlah'];
    $keterangan = $_POST['keterangan'];

    // Input ke tabel barang_rusak (Trigger akan otomatis memotong stok produk)
    $sql = "INSERT INTO barang_rusak (id_produk, tanggal, jumlah, keterangan) VALUES (?, NOW(), ?, ?)";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("iis", $id_produk, $jumlah, $keterangan);
    
    if ($stmt->execute()) {
        header("Location: barang_rusak.php?status=success");
        exit();
    } else {
        die("Gagal mencatat data: " . $koneksi->error);
    }
}

// Ambil Data Produk untuk Dropdown Select
$produks = $koneksi->query("SELECT id_produk, nama_produk, harga_jual FROM produk ORDER BY nama_produk ASC");

// Ambil Histori Barang Rusak & Hitung Kerugian Berdasarkan Harga Jual/Beli
$histori_rusak = $koneksi->query("
    SELECT r.id_rusak, r.tanggal, r.jumlah, r.keterangan, p.nama_produk, p.harga_jual
    FROM barang_rusak r
    JOIN produk p ON r.id_produk = p.id_produk
    ORDER BY r.id_rusak DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Sekar Bouquet</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

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

        /* --- STYLE SIDEBAR SINKRON (SAMA RATA) --- */
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
    <h2 class="mb-4">Pencatatan Bunga Rusak / Layu 🥀</h2>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Berhasil Dicatat!</strong> Stok bunga di gudang otomatis dikurangi karena layu/rusak.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card card-box p-4 bg-white mb-4">
        <h5 class="fw-bold mb-3"><i class="fa fa-trash-can me-1"></i> Input Bunga Rusak/Layu</h5>
        <form method="POST" action="">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">Pilih Bunga/Barang</label>
                    <select name="id_produk" class="form-select" required>
                        <option value="">-- Pilih Produk --</option>
                        <?php while($p = $produks->fetch_assoc()): ?>
                            <option value="<?= $p['id_produk'] ?>"><?= htmlspecialchars($p['nama_produk']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Jumlah (Qty)</label>
                    <input type="number" name="jumlah" class="form-control" min="1" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">Keterangan / Alasan</label>
                    <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Bunga Layu / Tangkai Patah" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" name="simpan_rusak" class="btn btn-main w-100">
                        <i class="fa fa-save me-1"></i> Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="card card-box p-4 bg-white">
        <h5 class="fw-bold mb-3">Log Proteksi Kerugian Penyusutan Bunga</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle m-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama Produk</th>
                        <th>Jumlah Rusak</th>
                        <th>Estimasi Kerugian (Rp)</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_rugi_keseluruhan = 0;
                    if ($histori_rusak && $histori_rusak->num_rows > 0): 
                        while ($row = $histori_rusak->fetch_assoc()): 
                            $estimasi_rugi = $row['jumlah'] * $row['harga_jual'];
                            $total_rugi_keseluruhan += $estimasi_rugi;
                    ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                            <td><b><?= htmlspecialchars($row['nama_produk']) ?></b></td>
                            <td class="text-danger fw-bold"><?= $row['jumlah'] ?> pcs</td>
                            <td class="text-danger">Rp <?= number_format($estimasi_rugi, 0, ',', '.') ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($row['keterangan']) ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                        <tr class="table-danger fw-bold">
                            <td colspan="3" class="text-end">TOTAL KERUGIAN AKIBAT WASTAGE:</td>
                            <td colspan="2" class="text-danger" style="font-size: 1.1rem;">Rp <?= number_format($total_rugi_keseluruhan, 0, ',', '.') ?></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Alhamdulillah, belum ada log bunga layu/rusak saat ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>