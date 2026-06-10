<?php
session_start();
include 'koneksi.php';

/* =========================
   PROTEKSI ADMIN
========================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

/* =========================
   DELETE PRODUK
========================= */
if (isset($_GET['delete'])) {

    $id = $_GET['delete'];

    // ambil gambar dulu
    $get = sqlsrv_query($koneksi, "SELECT gambar FROM produk WHERE id = ?", [$id]);
    $data = sqlsrv_fetch_array($get, SQLSRV_FETCH_ASSOC);

    if ($data && $data['gambar']) {
        $path = "assets/gambar/" . $data['gambar'];
        if (file_exists($path)) {
            unlink($path);
        }
    }

    sqlsrv_query($koneksi, "DELETE FROM produk WHERE id = ?", [$id]);

    header("Location: produk_admin.php");
    exit();
}

/* =========================
   INSERT PRODUK
========================= */
if (isset($_POST['tambah'])) {

    $nama = $_POST['nama'];
    $harga = $_POST['harga'];
    $stok  = $_POST['stok'];

    $gambar = "";

    if ($_FILES['gambar']['error'] == 0) {

        $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $gambar = "PRODUK_" . time() . "." . $ext;

        move_uploaded_file($_FILES['gambar']['tmp_name'], "assets/gambar/" . $gambar);
    }

    sqlsrv_query($koneksi,
        "INSERT INTO produk (nama, harga, stok, gambar) VALUES (?, ?, ?, ?)",
        [$nama, $harga, $stok, $gambar]
    );

    header("Location: produk_admin.php");
    exit();
}

/* =========================
   EDIT PRODUK
========================= */
if (isset($_POST['update'])) {

    $id    = $_POST['id'];
    $nama  = $_POST['nama'];
    $harga = $_POST['harga'];
    $stok  = $_POST['stok'];

    $gambar = $_POST['old_gambar'];

    if ($_FILES['gambar']['error'] == 0) {

        $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $gambar = "PRODUK_" . time() . "." . $ext;

        move_uploaded_file($_FILES['gambar']['tmp_name'], "assets/gambar/" . $gambar);
    }

    sqlsrv_query($koneksi,
        "UPDATE produk SET nama=?, harga=?, stok=?, gambar=? WHERE id=?",
        [$nama, $harga, $stok, $gambar, $id]
    );

    header("Location: produk_admin.php");
    exit();
}

/* =========================
   DATA PRODUK
========================= */
$data = sqlsrv_query($koneksi, "SELECT * FROM produk ORDER BY id DESC");

$edit = null;

if (isset($_GET['edit'])) {
    $res = sqlsrv_query($koneksi, "SELECT * FROM produk WHERE id = ?", [$_GET['edit']]);
    $edit = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
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

<!-- SIDEBAR -->
<div class="sidebar">
    <h3 class="mb-4">🌸 Sekar Admin</h3>

    <a href="admin.php">Dashboard</a>
    <a href="produk_admin.php">Produk</a>
    <a href="pesanan_admin.php">Pesanan</a>
    <a href="logout.php">Logout</a>
</div>

<div class="main">

<h2 class="mb-4">Manajemen Produk 🌸</h2>

<!-- FORM -->
<div class="card card-box p-4 mb-4">

<form method="post" enctype="multipart/form-data">

<?php if ($edit): ?>
    <input type="hidden" name="id" value="<?= $edit['id'] ?>">
    <input type="hidden" name="old_gambar" value="<?= $edit['gambar'] ?>">
    <h5>Edit Produk</h5>
<?php else: ?>
    <h5>Tambah Produk</h5>
<?php endif; ?>

<div class="row">

    <div class="col-md-4">
        <input type="text" name="nama" class="form-control mb-2"
               placeholder="Nama bouquet"
               value="<?= $edit['nama'] ?? '' ?>" required>
    </div>

    <div class="col-md-3">
        <input type="number" name="harga" class="form-control mb-2"
               placeholder="Harga"
               value="<?= $edit['harga'] ?? '' ?>" required>
    </div>

    <div class="col-md-2">
        <input type="number" name="stok" class="form-control mb-2"
               placeholder="Stok"
               value="<?= $edit['stok'] ?? '' ?>" required>
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

<!-- TABLE -->
<div class="card card-box p-4">

<h5>Daftar Produk</h5>

<table class="table table-hover">

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

<?php while ($row = sqlsrv_fetch_array($data, SQLSRV_FETCH_ASSOC)) { ?>

<tr>

<td>
    <img src="assets/gambar/<?= $row['gambar'] ?>"
         width="60"
         style="border-radius:10px;">
</td>

<td><?= $row['nama'] ?></td>
<td>Rp <?= number_format($row['harga'],0,',','.') ?></td>
<td><?= $row['stok'] ?></td>

<td>
    <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
    <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger"
       onclick="return confirm('Hapus produk?')">
       Hapus
    </a>
</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</body>
</html>