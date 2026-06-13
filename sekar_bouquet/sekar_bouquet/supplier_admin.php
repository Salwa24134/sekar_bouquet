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

$msg = "";
$msg_type = "";

/* =========================
   REVISI: DELETE SUPPLIER (Safe Mode)
========================= */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    try {
        $stmtDel = $koneksi->prepare("DELETE FROM supplier WHERE id_supplier = ?");
        $stmtDel->bind_param("i", $id);
        
        if ($stmtDel->execute()) {
            header("Location: supplier_admin.php?status=deleted");
            exit();
        }
        $stmtDel->close();
    } catch (mysqli_sql_exception $e) {
        header("Location: supplier_admin.php?status=restricted");
        exit();
    }
}

/* =========================
   REVISI: INSERT SUPPLIER (+ EMAIL)
========================= */
if (isset($_POST['tambah'])) {
    $nama      = trim($_POST['nama_supplier']);
    $telepon   = trim($_POST['telepon']); 
    $email     = trim($_POST['email']); // Menangkap input email baru
    $alamat    = trim($_POST['alamat']);

    $stmtIns = $koneksi->prepare("INSERT INTO supplier (nama_supplier, telepon, email, alamat) VALUES (?, ?, ?, ?)");
    $stmtIns->bind_param("ssss", $nama, $telepon, $email, $alamat);
    $stmtIns->execute();
    $stmtIns->close();

    header("Location: supplier_admin.php?status=inserted");
    exit();
}

/* =========================
   REVISI: EDIT SUPPLIER (+ EMAIL)
========================= */
if (isset($_POST['update'])) {
    $id        = (int)$_POST['id'];
    $nama      = trim($_POST['nama_supplier']);
    $telepon   = trim($_POST['telepon']); 
    $email     = trim($_POST['email']); // Menangkap input email edit
    $alamat    = trim($_POST['alamat']);

    $stmtUpd = $koneksi->prepare("UPDATE supplier SET nama_supplier=?, telepon=?, email=?, alamat=? WHERE id_supplier=?");
    $stmtUpd->bind_param("ssssi", $nama, $telepon, $email, $alamat, $id);
    $stmtUpd->execute();
    $stmtUpd->close();

    header("Location: supplier_admin.php?status=updated");
    exit();
}

/* =========================
   NOTIFIKASI STATUS HANDLER
========================= */
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'inserted') {
        $msg = "Data supplier baru berhasil ditambahkan!";
        $msg_type = "success";
    } elseif ($_GET['status'] == 'updated') {
        $msg = "Data perubahan supplier berhasil disimpan!";
        $msg_type = "info";
    } elseif ($_GET['status'] == 'deleted') {
        $msg = "Supplier berhasil dihapus dari sistem.";
        $msg_type = "success";
    } elseif ($_GET['status'] == 'restricted') {
        $msg = "Gagal menghapus! Supplier ini tidak boleh dihapus karena riwayat restok/retur miliknya masih tercatat di laporan keuangan toko.";
        $msg_type = "danger";
    }
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

    <h2 class="mb-4">Manajemen Supplier 🚚</h2>

    <?php if ($msg != ""): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show card-box" role="alert">
            <i class="fa <?= $msg_type == 'danger' ? 'fa-exclamation-triangle' : 'fa-check-circle' ?> me-2"></i>
            <?= $msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card card-box p-4 mb-4 bg-white">
        <form method="post">

            <?php if ($edit): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars($edit['id_supplier']) ?>">
                <h5>Edit Data Supplier</h5>
            <?php else: ?>
                <h5>Tambah Supplier Baru</h5>
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold">Nama Supplier</label>
                    <input type="text" name="nama_supplier" class="form-control"
                           placeholder="Contoh: PT. Bunga Indah"
                           value="<?= isset($edit['nama_supplier']) ? htmlspecialchars($edit['nama_supplier']) : '' ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold">No. Telepon</label>
                    <input type="text" name="telepon" class="form-control"
                           placeholder="Contoh: 08123456xxx"
                           value="<?= isset($edit['telepon']) ? htmlspecialchars($edit['telepon']) : '' ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold">Email Supplier</label>
                    <input type="email" name="email" class="form-control"
                           placeholder="Contoh: vendor@email.com"
                           value="<?= isset($edit['email']) ? htmlspecialchars($edit['email']) : '' ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold">Alamat Toko / Gudang</label>
                    <input type="text" name="alamat" class="form-control"
                           placeholder="Masukkan alamat lengkap"
                           value="<?= isset($edit['alamat']) ? htmlspecialchars($edit['alamat']) : '' ?>" required>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit"
                        name="<?= $edit ? 'update' : 'tambah' ?>"
                        class="btn btn-main">
                    <i class="fa <?= $edit ? 'fa-save' : 'fa-plus-circle' ?> me-1"></i> <?= $edit ? 'Update' : 'Tambah' ?> Supplier
                </button>

                <?php if ($edit): ?>
                    <a href="supplier_admin.php" class="btn btn-secondary"><i class="fa fa-times me-1"></i> Batal</a>
                <?php endif; ?>
            </div>

        </form>
    </div>

    <div class="card card-box p-4 bg-white">
        <h5 class="mb-3 fw-bold">Daftar Rekanan Supplier</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle m-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nama Supplier</th>
                        <th>No. Telepon</th>
                        <th>Email</th>
                        <th>Alamat Vendor</th>
                        <th class="text-center">Aksi Manajemen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($data && $data->num_rows > 0):
                        while ($row = $data->fetch_assoc()) { 
                    ?>
                    <tr>
                        <td><span class="badge bg-dark">#<?= htmlspecialchars($row['id_supplier']) ?></span></td>
                        <td><span class="text-dark fw-semibold"><?= htmlspecialchars($row['nama_supplier']) ?></span></td>
                        <td><i class="fa fa-phone text-muted small me-1"></i> <?= htmlspecialchars($row['telepon']) ?></td>
                        <td><i class="fa fa-envelope text-muted small me-1"></i> <?= htmlspecialchars($row['email']) ?></td>
                        <td><i class="fa fa-map-marker-alt text-muted small me-1"></i> <?= htmlspecialchars($row['alamat']) ?></td>
                        <td class="text-center">
                            <a href="?edit=<?= $row['id_supplier'] ?>" class="btn btn-sm btn-warning"><i class="fa fa-edit"></i> Edit</a>
                            <a href="?delete=<?= $row['id_supplier'] ?>" class="btn btn-sm btn-danger"
                               onclick="return confirm('Apakah Anda yakin ingin menghapus vendor supplier ini dari sistem?')">
                               <i class="fa fa-trash"></i> Hapus
                            </a>
                        </td>
                    </tr>
                    <?php 
                        }
                    else: 
                    ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Belum ada mitra supplier yang terdaftar.</td>
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