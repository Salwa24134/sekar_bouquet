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
   DELETE USER
========================= */
if (isset($_GET['delete'])) {

    $id = $_GET['delete'];

    // jangan boleh hapus admin utama (opsional safety)
    $cek = sqlsrv_query($koneksi, "SELECT role FROM users WHERE id = ?", [$id]);
    $data = sqlsrv_fetch_array($cek, SQLSRV_FETCH_ASSOC);

    if ($data && $data['role'] != 'admin') {
        sqlsrv_query($koneksi, "DELETE FROM users WHERE id = ?", [$id]);
    }

    header("Location: users_admin.php");
    exit();
}

/* =========================
   DATA USERS
========================= */
$sql = "SELECT * FROM users ORDER BY id DESC";
$data = sqlsrv_query($koneksi, $sql);
?>

<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<title>Users Admin - Sekar Bouquet</title>

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

/* SIDEBAR */
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

/* MAIN */
.main {
    margin-left: 260px;
    padding: 30px;
}

.card-box {
    border: none;
    border-radius: 18px;
    box-shadow: 0 10px 25px rgba(183,110,121,0.15);
}

/* ROLE BADGE */
.badge-user {
    background: #0d6efd;
}

.badge-admin {
    background: #b76e79;
}

.btn-main {
    background: linear-gradient(135deg, #d88b9c, #b76e79);
    color: white;
    border: none;
}

.btn-main:hover {
    color: white;
}

.user-avatar {
    width: 45px;
    height: 45px;
    object-fit: cover;
    border-radius: 50%;
    border: 2px solid #f1c9d2;
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
    <a href="users_admin.php">User</a>
    <a href="logout.php">Logout</a>
</div>

<!-- MAIN -->
<div class="main">

<h2 class="mb-4">Manajemen User 👥</h2>

<div class="card card-box p-4">

<table class="table table-hover align-middle">

<thead>
<tr>
    <th>Foto</th>
    <th>Username</th>
    <th>Email</th>
    <th>Telp</th>
    <th>Role</th>
    <th>Aksi</th>
</tr>
</thead>

<tbody>

<?php while ($row = sqlsrv_fetch_array($data, SQLSRV_FETCH_ASSOC)) { ?>

<tr>

<td>
    <img src="assets/gambar/<?= $row['foto'] ? $row['foto'] : 'default.png' ?>"
         class="user-avatar">
</td>

<td><?= $row['username'] ?></td>

<td><?= $row['email'] ?></td>

<td><?= $row['telp'] ?? '-' ?></td>

<td>
    <?php if ($row['role'] == 'admin') { ?>
        <span class="badge badge-admin">Admin</span>
    <?php } else { ?>
        <span class="badge badge-user">User</span>
    <?php } ?>
</td>

<td>

<?php if ($row['role'] != 'admin') { ?>
    <a href="?delete=<?= $row['id'] ?>"
       class="btn btn-sm btn-danger"
       onclick="return confirm('Hapus user ini?')">
        Hapus
    </a>
<?php } else { ?>
    <span class="text-muted">Protected</span>
<?php } ?>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</body>
</html>