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
   DELETE SUPPLIER (MySQLi)
========================= */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $stmtDel = $koneksi->prepare("DELETE FROM supplier WHERE id_supplier = ?");
    $stmtDel->bind_param("i", $id);
    $stmtDel->execute();
    $stmtDel->close();

    header("Location: supplier_admin.php");
    exit();
}

/* =========================
   INSERT SUPPLIER (MySQLi)
========================= */
if (isset($_POST['tambah'])) {
    $nama      = trim($_POST['nama_supplier']);
    $telepon   = trim($_POST['telepon']); // Mengambil dari input form
    $alamat    = trim($_POST['alamat']);

    // Perbaikan: Kolom kontak diubah menjadi telepon sesuai database Anda
    $stmtIns = $koneksi->prepare("INSERT INTO supplier (nama_supplier, telepon, alamat) VALUES (?, ?, ?)");
    $stmtIns->bind_param("sss", $nama, $telepon, $alamat);
    $stmtIns->execute();
    $stmtIns->close();

    header("Location: supplier_admin.php");
    exit();
}

/* =========================
   EDIT SUPPLIER (MySQLi)
========================= */
if (isset($_POST['update'])) {
    $id        = (int)$_POST['id'];
    $nama      = trim($_POST['nama_supplier']);
    $telepon   = trim($_POST['telepon']); // Mengambil dari input form
    $alamat    = trim($_POST['alamat']);

    // Perbaikan: Kolom kontak diubah menjadi telepon sesuai database Anda
    $stmtUpd = $koneksi->prepare("UPDATE supplier SET nama_supplier=?, telepon=?, alamat=? WHERE id_supplier=?");
    $stmtUpd->bind_param("sssi", $nama, $telepon, $alamat, $id);
    $stmtUpd->execute();
    $stmtUpd->close();

    header("Location: supplier_admin.php");
    exit();
}

/* =========================
   DATA SUPPLIER (MySQLi)
========================= */
$data = $koneksi->query("SELECT * FROM supplier ORDER BY id_supplier DESC");

$edit = null;
if (isset($_GET['edit'])) {
    $idEdit = (int)$_GET['edit'];
    $stmtEdit = $koneksi->prepare("SELECT * FROM supplier WHERE id_supplier = ?");
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
    <title>Supplier Admin - Sekar Bouquet</title>

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

        .sidebar h3 {
            color: white;
        }

        .sidebar a {
            display: block;
            color: white;
            padding: 10px;
            text-decoration: none;
            margin-bottom: 10px;
            border-radius: 10px;
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

<?php include 'sidebar.php'; ?>

<div class="main">

    <h2 class="mb-4">Manajemen Supplier 🚚</h2>

    <div class="card card-box p-4 mb-4">
        <form method="post">

            <?php if ($edit): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars($edit['id_supplier']) ?>">
                <h5>Edit Supplier</h5>
            <?php else: ?>
                <h5>Tambah Supplier</h5>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Nama Supplier</label>
                    <input type="text" name="nama_supplier" class="form-control mb-2"
                           placeholder="Contoh: PT. Bunga Indah"
                           value="<?= isset($edit['nama_supplier']) ? htmlspecialchars($edit['nama_supplier']) : '' ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted">No. Telepon</label>
                    <input type="text" name="telepon" class="form-control mb-2"
                           placeholder="Contoh: 08123456xxx"
                           value="<?= isset($edit['telepon']) ? htmlspecialchars($edit['telepon']) : '' ?>" required>
                </div>

                <div class="col-md-5">
                    <label class="form-label small text-muted">Alamat</label>
                    <input type="text" name="alamat" class="form-control mb-2"
                           placeholder="Masukkan alamat lengkap supplier"
                           value="<?= isset($edit['alamat']) ? htmlspecialchars($edit['alamat']) : '' ?>" required>
                </div>
            </div>

            <div class="mt-2">
                <button type="submit"
                        name="<?= $edit ? 'update' : 'tambah' ?>"
                        class="btn btn-main">
                    <?= $edit ? 'Update' : 'Tambah' ?> Supplier
                </button>

                <?php if ($edit): ?>
                    <a href="supplier_admin.php" class="btn btn-secondary">Batal</a>
                <?php endif; ?>
            </div>

        </form>
    </div>

    <div class="card card-box p-4">
        <h5>Daftar Supplier</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Supplier</th>
                        <th>No. Telepon</th>
                        <th>Alamat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($data && $data->num_rows > 0):
                        while ($row = $data->fetch_assoc()) { 
                    ?>
                    <tr>
                        <td>#<?= htmlspecialchars($row['id_supplier']) ?></td>
                        <td><b><?= htmlspecialchars($row['nama_supplier']) ?></b></td>
                        <td><?= htmlspecialchars($row['telepon']) ?></td>
                        <td><?= htmlspecialchars($row['alamat']) ?></td>
                        <td>
                            <a href="?edit=<?= $row['id_supplier'] ?>" class="btn btn-sm btn-warning">Edit</a>
                            <a href="?delete=<?= $row['id_supplier'] ?>" class="btn btn-sm btn-danger"
                               onclick="return confirm('Hapus data supplier ini?')">
                               Hapus
                            </a>
                        </td>
                    </tr>
                    <?php 
                        }
                    else: 
                    ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">Belum ada data supplier.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>