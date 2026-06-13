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
            background: #b26a7a; 
            position: fixed;
            top: 0;
            left: 0;
            padding: 30px 24px;
            color: white;
            z-index: 1000;
            overflow-y: auto; 
        }

        .sidebar::-webkit-scrollbar {
            width: 6px; 
        }
        .sidebar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05); 
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.25); 
            border-radius: 10px;
        }
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.45); 
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
            color: #f5e6e8; 
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
            width: 30px; 
        }
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
            background: white;
        }
        .table-responsive {
            background: white;
            border-radius: 12px;
            padding: 10px;
        }

        /* --- FIX FIX: STYLE FOTO PROFILE AVATAR YANG RUSAK --- */
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e8b4bc;
            box-shadow: 0 4px 8px rgba(183,110,121,0.15);
        }

        /* STYLE CUSTOM BADGE STATUS WARNA MAUVE MATCHING */
        .badge-admin {
            background-color: #b76e79 !important;
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 500;
        }
        .badge-user {
            background-color: #e8b4bc !important;
            color: #5c3a40;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 500;
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
                                <?php 
                                $foto_path = 'assets/foto/' . $row['foto'];
                                if (!empty($row['foto']) && file_exists($foto_path)) {
                                    $gambar_tampil = $foto_path;
                                } else {
                                    // Menggunakan gambar placeholder avatar bawaan jika file tidak ada di folder
                                    $gambar_tampil = 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
                                }
                                ?>
                                <img src="<?= $gambar_tampil; ?>" class="user-avatar" alt="Avatar">
                            </td>

                            <td><b><?= htmlspecialchars($row['username']); ?></b></td>
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
                                       style="border-radius: 8px;"
                                       onclick="return confirm('Hapus user ini?')">
                                        <i class="fa fa-trash me-1"></i> Hapus
                                    </a>
                                <?php } else { ?>
                                    <span class="badge bg-light text-muted p-2" style="border-radius: 8px;"><i class="fa fa-lock me-1"></i> Protected</span>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>