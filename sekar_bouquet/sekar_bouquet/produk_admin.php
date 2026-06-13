<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';

/* =========================
   PROTEKSI ADMIN
========================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

/* =========================
   DELETE PRODUK (MySQLi)
========================= */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $stmtGet = $koneksi->prepare("SELECT gambar FROM produk WHERE id_produk = ?");
    $stmtGet->bind_param("i", $id);
    $stmtGet->execute();
    $resGet = $stmtGet->get_result();
    $dataProduk = $resGet->fetch_assoc();
    $stmtGet->close();

    if ($dataProduk && !empty($dataProduk['gambar'])) {
        $path = "assets/gambar/" . $dataProduk['gambar'];
        if (file_exists($path)) {
            unlink($path);
        }
    }

    $stmtDel = $koneksi->prepare("DELETE FROM produk WHERE id_produk = ?");
    $stmtDel->bind_param("i", $id);
    $stmtDel->execute();
    $stmtDel->close();

    header("Location: produk_admin.php");
    exit();
}

/* =========================
   INSERT PRODUK (MySQLi)
========================= */
if (isset($_POST['tambah'])) {
    $nama        = trim($_POST['nama']);
    $id_kategori = (int)$_POST['id_kategori'];
    $id_supplier = (int)$_POST['id_supplier'];
    $harga       = (int)$_POST['harga'];
    $stok        = (int)$_POST['stok'];
    $gambar      = "";

    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $gambar = "PRODUK_" . time() . "." . $ext;
        move_uploaded_file($_FILES['gambar']['tmp_name'], "assets/gambar/" . $gambar);
    }

    // PERBAIKAN: Menyertakan id_kategori dan id_supplier agar Foreign Key tidak gagal
    $stmtIns = $koneksi->prepare("INSERT INTO produk (nama_produk, id_kategori, id_supplier, harga_jual, stok, gambar) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtIns->bind_param("siiiis", $nama, $id_kategori, $id_supplier, $harga, $stok, $gambar);
    $stmtIns->execute();
    $stmtIns->close();

    header("Location: produk_admin.php");
    exit();
}

/* =========================
   EDIT PRODUK (MySQLi)
========================= */
if (isset($_POST['update'])) {
    $id          = (int)$_POST['id'];
    $nama        = trim($_POST['nama']);
    $id_kategori = (int)$_POST['id_kategori'];
    $id_supplier = (int)$_POST['id_supplier'];
    $harga       = (int)$_POST['harga'];
    $stok        = (int)$_POST['stok'];
    $gambar      = $_POST['old_gambar'];

    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        if (!empty($gambar)) {
            $oldPath = "assets/gambar/" . $gambar;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
        $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $gambar = "PRODUK_" . time() . "." . $ext;
        move_uploaded_file($_FILES['gambar']['tmp_name'], "assets/gambar/" . $gambar);
    }

    // PERBAIKAN: Update data id_kategori dan id_supplier
    $stmtUpd = $koneksi->prepare("UPDATE produk SET nama_produk=?, id_kategori=?, id_supplier=?, harga_jual=?, stok=?, gambar=? WHERE id_produk=?");
    $stmtUpd->bind_param("siiiisi", $nama, $id_kategori, $id_supplier, $harga, $stok, $gambar, $id);
    $stmtUpd->execute();
    $stmtUpd->close();

    header("Location: produk_admin.php");
    exit();
}

/* =========================
   DATA REFERENSI & UTAMA
========================= */
$list_kategori = $koneksi->query("SELECT * FROM kategori_produk ORDER BY id_kategori ASC");
$list_supplier = $koneksi->query("SELECT * FROM supplier ORDER BY id_supplier ASC");

// Mengambil data produk gabungan untuk menampilkan nama kategori asli di tabel list bawah
$data = $koneksi->query("
    SELECT p.*, k.nama_kategori 
    FROM produk p
    LEFT JOIN kategori_produk k ON p.id_kategori = k.id_kategori
    ORDER BY p.id_produk DESC
");

$edit = null;
if (isset($_GET['edit'])) {
    $idEdit = (int)$_GET['edit'];
    $stmtEdit = $koneksi->prepare("SELECT * FROM produk WHERE id_produk = ?");
    $stmtEdit->bind_param("i", $idEdit);
    $stmtEdit->execute();
    $resEdit = $stmtEdit->get_result();
    $edit = $resEdit->fetch_assoc();
    $stmtEdit->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk Admin - Sekar Bouquet</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
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
    <h2 class="mb-4">Manajemen Produk 🌸</h2>

    <div class="card card-box p-4 mb-4">
        <form method="post" enctype="multipart/form-data">
            <?php if ($edit): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars($edit['id_produk']) ?>">
                <input type="hidden" name="old_gambar" value="<?= htmlspecialchars($edit['gambar']) ?>">
                <h5>Edit Produk</h5>
            <?php else: ?>
                <h5>Tambah Produk</h5>
            <?php endif; ?>

            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <label class="small text-muted">Nama Bouquet</label>
                    <input type="text" name="nama" class="form-control" placeholder="Nama bouquet" value="<?= isset($edit['nama_produk']) ? htmlspecialchars($edit['nama_produk']) : '' ?>" required>
                </div>
                
                <div class="col-md-4">
                    <label class="small text-muted">Kategori</label>
                    <select name="id_kategori" class="form-select" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php 
                        if ($list_kategori && $list_kategori->num_rows > 0) {
                            // Reset pointer data biar select box bisa diulang dengan aman
                            $list_kategori->data_seek(0); 
                            while($k = $list_kategori->fetch_assoc()) {
                                $selected = (isset($edit['id_kategori']) && $edit['id_kategori'] == $k['id_kategori']) ? 'selected' : '';
                                echo '<option value="'. $k['id_kategori'] .'" '. $selected .'>'. htmlspecialchars($k['nama_kategori']) .'</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="small text-muted">Supplier</label>
                    <select name="id_supplier" class="form-select" required>
                        <option value="">-- Pilih Supplier --</option>
                        <?php 
                        if ($list_supplier && $list_supplier->num_rows > 0) {
                            // Reset pointer data supplier
                            $list_supplier->data_seek(0); 
                            while($s = $list_supplier->fetch_assoc()) {
                                $selected = (isset($edit['id_supplier']) && $edit['id_supplier'] == $s['id_supplier']) ? 'selected' : '';
                                echo '<option value="'. $s['id_supplier'] .'" '. $selected .'>'. htmlspecialchars($s['nama_supplier']) .'</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <label class="small text-muted">Harga Jual</label>
                    <input type="number" name="harga" class="form-control" placeholder="Harga Jual" value="<?= isset($edit['harga_jual']) ? htmlspecialchars($edit['harga_jual']) : '' ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="small text-muted">Stok Berjalan</label>
                    <input type="number" name="stok" class="form-control" placeholder="Stok" value="<?= isset($edit['stok']) ? htmlspecialchars($edit['stok']) : '' ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="small text-muted">Berkas Gambar</label>
                    <input type="file" name="gambar" class="form-control">
                </div>
            </div>

            <button type="submit" name="<?= $edit ? 'update' : 'tambah' ?>" class="btn btn-main">
                <?= $edit ? 'Update' : 'Tambah' ?> Produk
            </button>
            <?php if ($edit): ?>
                <a href="produk_admin.php" class="btn btn-secondary">Batal</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card card-box p-4">
        <h5>Daftar Produk</h5>
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Gambar</th>
                    <th>Nama</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data && $data->num_rows > 0): ?>
                    <?php while ($row = $data->fetch_assoc()) { ?>
                    <tr>
                        <td>
                            <?php if (!empty($row['gambar'])): ?>
                                <img src="assets/gambar/<?= htmlspecialchars($row['gambar']) ?>" width="60" height="60" style="border-radius:10px; object-fit:cover;" alt="Gambar">
                            <?php else: ?>
                                <span class="text-muted small">No Image</span>
                            <?php endif; ?>
                        </td>
                        <td><?= isset($row['nama_produk']) ? htmlspecialchars($row['nama_produk']) : '' ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($row['nama_kategori'] ?? 'Umum') ?></span></td>
                        <td>Rp <?= number_format($row['harga_jual'], 0, ',', '.') ?></td>
                        <td><?= htmlspecialchars($row['stok']) ?></td>
                        <td>
                            <a href="?edit=<?= $row['id_produk'] ?>" class="btn btn-sm btn-warning">Edit</a>
                            <a href="?delete=<?= $row['id_produk'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus produk?')">Hapus</a>
                        </td>
                    </tr>
                    <?php } ?>
                <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-3">Belum ada data produk.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>