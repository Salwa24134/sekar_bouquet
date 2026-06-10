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

    // Ambil info nama berkas gambar lama terlebih dahulu
    $stmtGet = $koneksi->prepare("SELECT gambar FROM produk WHERE id = ?");
    $stmtGet->bind_param("i", $id);
    $stmtGet->execute();
    $resGet = $stmtGet->get_result();
    $data = $resGet->fetch_assoc();
    $stmtGet->close();

    if ($data && !empty($data['gambar'])) {
        $path = "assets/gambar/" . $data['gambar'];
        if (file_exists($path)) {
            unlink($path);
        }
    }

    // Eksekusi hapus data dari tabel
    $stmtDel = $koneksi->prepare("DELETE FROM produk WHERE id = ?");
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

    $nama  = trim($_POST['nama']);
    $harga = (int)$_POST['harga'];
    $stok  = (int)$_POST['stok'];
    $gambar = "";

    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $gambar = "PRODUK_" . time() . "." . $ext;
        move_uploaded_file($_FILES['gambar']['tmp_name'], "assets/gambar/" . $gambar);
    }

    $stmtIns = $koneksi->prepare("INSERT INTO produk (nama, harga, stok, gambar) VALUES (?, ?, ?, ?)");
    $stmtIns->bind_param("siis", $nama, $harga, $stok, $gambar);
    $stmtIns->execute();
    $stmtIns->close();

    header("Location: produk_admin.php");
    exit();
}

/* =========================
   EDIT PRODUK (MySQLi)
========================= */
if (isset($_POST['update'])) {

    $id     = (int)$_POST['id'];
    $nama   = trim($_POST['nama']);
    $harga  = (int)$_POST['harga'];
    $stok   = (int)$_POST['stok'];
    $gambar = $_POST['old_gambar'];

    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        // Hapus file gambar lama jika ada sebelum diganti yang baru
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

    $stmtUpd = $koneksi->prepare("UPDATE produk SET nama=?, harga=?, stok=?, gambar=? WHERE id=?");
    $stmtUpd->bind_param("siisi", $nama, $harga, $stok, $gambar, $id);
    $stmtUpd->execute();
    $stmtUpd->close();

    header("Location: produk_admin.php");
    exit();
}

/* =========================
   DATA PRODUK (MySQLi)
========================= */
$data = $koneksi->query("SELECT * FROM produk ORDER BY id DESC");

$edit = null;
if (isset($_GET['edit'])) {
    $idEdit = (int)$_GET['edit'];
    $stmtEdit = $koneksi->prepare("SELECT * FROM produk WHERE id = ?");
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
        }

        h2, h3 {
            font-family: 'Playfair Display', serif;
            color: #b76e79;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background: linear-gradient(135deg, #b76e79, #8d4f5c);
            position: fixed;
            padding: 20px;
            color: white;
        }

        .sidebar a {
            display: block;
            color: white;
            padding: 10px;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .sidebar a:hover {
            background: rgba(255,255,255,0.2);
        }

        .main {
            margin-left: 260px;
            padding: 30px;
        }

        .card-box {
            border: none;
            border-radius: 18px;
            box-shadow: 0 10px 25px rgba(183,110,121,0.15);
        }

        .btn-main {
            background: linear-gradient(135deg, #d88b9c, #b76e79);
            color: white;
            border: none;
        }

        .btn-main:hover {
            color: white;
        }
    </style>
</head>

<body>

<div class="sidebar">
    <h3 class="mb-4">🌸 Sekar Admin</h3>
    <a href="admin.php">Dashboard</a>
    <a href="produk_admin.php">Produk</a>
    <a href="pesanan_admin.php">Pesanan</a>
    <a href="logout.php">Logout</a>
</div>

<div class="main">

    <h2 class="mb-4">Manajemen Produk 🌸</h2>

    <div class="card card-box p-4 mb-4">
        <form method="post" enctype="multipart/form-data">

            <?php if ($edit): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars($edit['id']) ?>">
                <input type="hidden" name="old_gambar" value="<?= htmlspecialchars($edit['gambar']) ?>">
                <h5>Edit Produk</h5>
            <?php else: ?>
                <h5>Tambah Produk</h5>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4">
                    <input type="text" name="nama" class="form-control mb-2"
                           placeholder="Nama bouquet"
                           value="<?= htmlspecialchars($edit['nama'] ?? '') ?>" required>
                </div>

                <div class="col-md-3">
                    <input type="number" name="harga" class="form-control mb-2"
                           placeholder="Harga"
                           value="<?= htmlspecialchars($edit['harga'] ?? '') ?>" required>
                </div>

                <div class="col-md-2">
                    <input type="number" name="stok" class="form-control mb-2"
                           placeholder="Stok"
                           value="<?= htmlspecialchars($edit['stok'] ?? '') ?>" required>
                </div>

                <div class="col-md-3">
                    <input type="file" name="gambar" class="form-control mb-2">
                </div>
            </div>

            <button type="submit"
                    name="<?= $edit ? 'update' : 'tambah' ?>"
                    class="btn btn-main">
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
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($data && $data->num_rows > 0):
                    while ($row = $data->fetch_assoc()) { 
                ?>
                <tr>
                    <td>
                        <?php if (!empty($row['gambar'])): ?>
                            <img src="assets/gambar/<?= htmlspecialchars($row['gambar']) ?>"
                                 width="60" height="60"
                                 style="border-radius:10px; object-fit:cover;"
                                 alt="Gambar Produk">
                        <?php else: ?>
                            <span class="text-muted small">No Image</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td>Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                    <td><?= htmlspecialchars($row['stok']) ?></td>
                    <td>
                        <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger"
                           onclick="return confirm('Hapus produk?')">
                           Hapus
                        </a>
                    </td>
                </tr>
                <?php 
                    }
                else: 
                ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">Belum ada data produk.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>