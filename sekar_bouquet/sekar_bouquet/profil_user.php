<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';

/* =========================
   PROTEKSI LOGIN
========================= */
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['user']['username'];

/* =========================
   AMBIL DATA USER (MySQLi)
========================= */
$sql = "SELECT * FROM users WHERE username = ?";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "User tidak ditemukan";
    exit();
}

/* =========================
   UPDATE PROFIL
========================= */
$success = "";
$error = "";

if (isset($_POST['update'])) {

    $new_username = trim($_POST['username']);
    $email = trim($_POST['email']);

    /* =========================
       UPLOAD FOTO (optional)
    ========================= */
    $fotoName = $user['foto'];

    if (!empty($_FILES['foto']['name'])) {

        $folder = "assets/gambar/";
        if (!is_dir($folder)) mkdir($folder, 0777, true);

        // Jika user punya foto lama dan bukan default, hapus agar storage hemat
        if (!empty($user['foto']) && file_exists($folder . $user['foto'])) {
            unlink($folder . $user['foto']);
        }

        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $fotoName = "USER_" . time() . "." . $ext;

        move_uploaded_file($_FILES['foto']['tmp_name'], $folder . $fotoName);
    }

    /* =========================
       UPDATE DATABASE (MySQLi)
    ========================= */
    $sqlUpdate = "
        UPDATE users
        SET username = ?, email = ?, foto = ?
        WHERE id = ?
    ";

    $stmtUpdate = $koneksi->prepare($sqlUpdate);
    $stmtUpdate->bind_param("sssi", $new_username, $email, $fotoName, $user['id']);
    
    if ($stmtUpdate->execute()) {

        $_SESSION['user']['username'] = $new_username;
        $_SESSION['user']['foto'] = $fotoName;

        $success = "Profil berhasil diperbarui 🌸";

        $stmtUpdate->close();
        // Refresh data agar perubahan langsung terlihat di halaman
        header("Refresh:0");
        exit();
    } else {
        $error = "Gagal update profil!";
        $stmtUpdate->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Sekar Bouquet</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #fff4f7;
        }

        h1, h2, h3 {
            font-family: 'Playfair Display', serif;
            color: #b76e79;
        }

        .card-bouquet {
            background: white;
            border: none;
            border-radius: 22px;
            box-shadow: 0 10px 30px rgba(183,110,121,0.15);
        }

        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #f7d7dd;
        }

        .btn-main {
            background: linear-gradient(135deg, #d88b9c, #b76e79);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 10px;
            font-weight: 600;
        }

        .btn-main:hover {
            color: white;
            transform: translateY(-2px);
        }

        .form-control {
            border-radius: 12px;
            padding: 10px;
        }

        .label {
            font-weight: 600;
            color: #a15c6d;
            font-size: 14px;
        }

        .alert {
            border-radius: 12px;
        }
    </style>
</head>

<body>

<?php include 'layout/header.php'; ?>

<div class="container py-5">

    <h1 class="text-center mb-4">Profil Saya 🌸</h1>

    <div class="row justify-content-center">
        <div class="col-md-7">

            <div class="card card-bouquet p-4 text-center">

                <img src="assets/gambar/<?= !empty($user['foto']) ? htmlspecialchars($user['foto']) : 'default.png'; ?>" 
                     class="profile-img mb-3" alt="Foto Profil">

                <h4 class="fw-bold"><?= htmlspecialchars($user['username']); ?></h4>
                <p class="text-muted"><?= htmlspecialchars($user['email']); ?></p>

                <?php if ($success) { ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($success); ?>
                    </div>
                <?php } ?>

                <?php if ($error) { ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error); ?>
                    </div>
                <?php } ?>

                <form method="post" enctype="multipart/form-data" class="text-start mt-3">

                    <div class="mb-3">
                        <label class="label">Username</label>
                        <input type="text" name="username" 
                               value="<?= htmlspecialchars($user['username']); ?>"
                               class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="label">Email</label>
                        <input type="email" name="email" 
                               value="<?= htmlspecialchars($user['email']); ?>"
                               class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="label">Foto Profil</label>
                        <input type="file" name="foto" class="form-control">
                        <small class="text-muted">Kosongkan jika tidak ingin ganti foto</small>
                    </div>

                    <button type="submit" name="update" class="btn btn-main w-100">
                        Simpan Perubahan
                    </button>

                </form>

            </div>

        </div>
    </div>

</div>

<?php include 'layout/footer.php'; ?>

</body>
</html>