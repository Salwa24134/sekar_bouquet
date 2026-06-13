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
   DELETE USER (MySQLi)
========================= */
if (isset($_GET['delete'])) {
    // Pastikan ID berupa integer untuk keamanan tambahan
    $id = (int)$_GET['delete']; 

    // Ambil data untuk validasi role sebelum dihapus (safety check)
    $cekSql = "SELECT role FROM users WHERE id = ?";
    $stmtCheck = $koneksi->prepare($cekSql);
    $stmtCheck->bind_param("i", $id);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    $data = $resultCheck->fetch_assoc();
    $stmtCheck->close();

    // Jangan boleh hapus jika user bermenu role 'admin'
    if ($data && $data['role'] != 'admin') {
        $delSql = "DELETE FROM users WHERE id = ?";
        $stmtDel = $koneksi->prepare($delSql);
        $stmtDel->bind_param("i", $id);
        $stmtDel->execute();
        $stmtDel->close();
    }

    header("Location: users_admin.php");
    exit();
}

/* =========================
   DATA USERS (MySQLi)
========================= */
$sql = "SELECT * FROM users ORDER BY id DESC";
$resultData = $koneksi->query($sql);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            background-color: #0d6efd;
            color: white;
        }

        .badge-admin {
            background-color: #b76e79;
            color: white;
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

<?php include 'sidebar.php'; ?>

<div class="main">

    <h2 class="mb-4">Manajemen User 👥</h2>

    <div class="card card-box p-4">
        <div class="table-responsive">
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

                <?php 
                if ($resultData && $resultData->num_rows > 0) {
                    while ($row = $resultData->fetch_assoc()) { 
                ?>
                        <tr>
                            <td>
                                <img src="assets/foto/<?= !empty($row['foto']) ? htmlspecialchars($row['foto']) : 'default.png'; ?>"
                                     class="user-avatar" alt="Avatar">
                            </td>

                            <td><?= htmlspecialchars($row['username']); ?></td>
                            <td><?= htmlspecialchars($row['email']); ?></td>
                            <td><?= htmlspecialchars($row['telp'] ?? '-'); ?></td>

                            <td>
                                <?php if ($row['role'] == 'admin') { ?>
                                    <span class="badge badge-admin">Admin</span>
                                <?php } else { ?>
                                    <span class="badge badge-user">User</span>
                                <?php } ?>
                            </td>

                            <td>
                                <?php if ($row['role'] != 'admin') { ?>
                                    <a href="?delete=<?= urlencode($row['id']); ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Hapus user ini?')">
                                        Hapus
                                    </a>
                                <?php } else { ?>
                                    <span class="text-muted">Protected</span>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php 
                    }
                } else { 
                ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Tidak ada data user.</td>
                    </tr>
                <?php } ?>

                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>