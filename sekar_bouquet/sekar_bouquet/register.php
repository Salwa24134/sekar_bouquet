<?php
session_start();
include 'koneksi.php';

if (isset($_POST['register'])) {

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = "user";

    $uploadDir = "assets/foto/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // CEK USERNAME
    $checkSql = "SELECT username FROM users WHERE username = ?";
    $checkParams = array($username);

    $checkResult = sqlsrv_query($koneksi, $checkSql, $checkParams);

    if ($checkResult && sqlsrv_fetch_array($checkResult, SQLSRV_FETCH_ASSOC)) {
        $error = "Username sudah digunakan!";
    } else {

        // CEK FILE UPLOAD
        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] != 0) {
            $error = "Foto wajib diupload!";
        } else {

            $fotoName = time() . "_" . preg_replace("/\s+/", "_", $_FILES['foto']['name']);
            $tmp = $_FILES['foto']['tmp_name'];

            if (move_uploaded_file($tmp, $uploadDir . $fotoName)) {

                $sql = "INSERT INTO users (username, email, password, role, foto)
                        VALUES (?, ?, ?, ?, ?)";

                $params = array($username, $email, $password, $role, $fotoName);

                $result = sqlsrv_query($koneksi, $sql, $params);

                if ($result) {
                    $success = "Akun berhasil dibuat!";
                } else {
                    $error = "Gagal membuat akun!";
                }

            } else {
                $error = "Upload foto gagal!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <title>Register - Sekar Bouquet</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@700&display=swap"
          rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>

        body {
            background:
                linear-gradient(rgba(255,255,255,0.55),
                rgba(255,255,255,0.55)),
                url('assets/gambar/bg-bouquet.jpg');

            background-size: cover;
            background-position: center;
            font-family: 'Poppins', sans-serif;
        }

        .register-card {
            background: rgba(255,255,255,0.93);
            backdrop-filter: blur(12px);
            border: none;
            border-radius: 28px;
            padding: 40px 35px;
            box-shadow: 0 15px 40px rgba(181, 131, 141, 0.25);
        }

        .register-logo {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #f7d7dd;
            margin-bottom: 15px;
        }

        .register-title {
            font-family: 'Playfair Display', serif;
            color: #b76e79;
            font-size: 2.2rem;
            font-weight: 700;
        }

        .register-subtitle {
            color: #8a6f77;
            font-size: 0.95rem;
        }

        .form-label {
            color: #a15c6d;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .input-group {
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #f1c9d2;
            transition: 0.3s;
            background: white;
        }

        .input-group:focus-within {
            border-color: #d88b9c;
            box-shadow: 0 0 0 0.25rem rgba(216, 139, 156, 0.2);
        }

        .input-group-text {
            background: white;
            border: none;
            color: #c17b8c;
        }

        .form-control {
            border: none;
            padding: 12px;
        }

        .form-control:focus {
            box-shadow: none;
        }

        .btn-register {
            background: linear-gradient(135deg, #d88b9c, #b76e79);
            border: none;
            color: white;
            padding: 12px;
            border-radius: 14px;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(183, 110, 121, 0.35);
        }

        .login-link {
            color: #b76e79;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link:hover {
            color: #9c5b6b;
            text-decoration: underline;
        }

        .alert {
            border-radius: 12px;
        }

        .form-text {
            color: #8a6f77;
        }

    </style>

</head>

<body class="d-flex flex-column min-vh-100">

<?php include 'layout/header.php'; ?>

<div class="container flex-grow-1 d-flex align-items-center justify-content-center py-5">

    <div class="row w-100 justify-content-center">

        <div class="col-md-7 col-lg-5">

            <div class="card register-card">

                <div class="text-center mb-4">

                    <img src="assets/gambar/logo.jpeg"
                         alt="Sekar Bouquet"
                         class="register-logo">

                    <h2 class="register-title">
                        Buat Akun Baru
                    </h2>

                    <p class="register-subtitle">
                        Gabung bersama Sekar Bouquet dan temukan bunga favoritmu 🌷
                    </p>

                </div>

                <?php if (isset($success)) : ?>

                    <div class="alert alert-success text-center fw-bold small">

                        <i class="fa-solid fa-circle-check me-2"></i>

                        <?php echo $success; ?>

                        <div class="mt-2">

                            <a href="login.php"
                               class="login-link">

                                Login sekarang

                            </a>

                        </div>

                    </div>

                <?php endif; ?>

                <?php if (isset($error)) : ?>

                    <div class="alert alert-danger text-center fw-bold small">

                        <i class="fa-solid fa-circle-exclamation me-2"></i>

                        <?php echo $error; ?>

                    </div>

                <?php endif; ?>

                <form method="post" enctype="multipart/form-data">

                    <div class="mb-3">

                        <label class="form-label">
                            Username
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                <i class="fa-solid fa-user"></i>
                            </span>

                            <input
                                type="text"
                                name="username"
                                class="form-control"
                                placeholder="Masukkan username"
                                required>

                        </div>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Email
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                <i class="fa-solid fa-envelope"></i>
                            </span>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                placeholder="Masukkan email aktif"
                                required>

                        </div>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Password
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                <i class="fa-solid fa-lock"></i>
                            </span>

                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                placeholder="Buat password"
                                required>

                        </div>

                    </div>

                    <div class="mb-4">

                        <label class="form-label">
                            Foto Profil
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                <i class="fa-solid fa-image"></i>
                            </span>

                            <input
                                type="file"
                                name="foto"
                                class="form-control"
                                accept="image/*"
                                required>

                        </div>

                        <div class="form-text small mt-2">

                            Gunakan foto terbaikmu untuk profil akun 🌸

                        </div>

                    </div>

                    <button
                        type="submit"
                        name="register"
                        class="btn btn-register w-100 mb-4">

                        <i class="fa-solid fa-user-plus me-2"></i>

                        Daftar Sekarang

                    </button>

                </form>

                <div class="text-center">

                    <p class="small text-muted mb-0">

                        Sudah punya akun?

                        <a href="login.php"
                           class="login-link">

                            Login di sini

                        </a>

                    </p>

                </div>

            </div>

        </div>

    </div>

</div>

<?php include 'layout/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>