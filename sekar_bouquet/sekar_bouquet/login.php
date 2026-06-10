<?php
session_start();
include 'koneksi.php';

$error = "";

if (isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $sql = "SELECT * FROM users WHERE username = ?";
    $res = sqlsrv_query($koneksi, $sql, [$username]);

    if ($res && sqlsrv_has_rows($res)) {

    $user = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);

    $loginOK = false;

    if (password_verify($password, $user['password'])) {
        $loginOK = true;
    } elseif ($password === $user['password']) {
        $loginOK = true;
    }

    if ($loginOK) {

        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['foto'] = $user['foto'];

        if (strtolower($user['role']) === 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: index.php");
        }

        exit();

    } else {
        $error = "Password salah!";
    }

} else {
    $error = "Username tidak ditemukan!";
}
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<title>Login - Sekar Bouquet</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

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

/* === CARD STYLE SAMA PERSIS REGISTER === */
.login-card {
    background: rgba(255,255,255,0.93);
    backdrop-filter: blur(12px);
    border: none;
    border-radius: 28px;
    padding: 40px 35px;
    box-shadow: 0 15px 40px rgba(181, 131, 141, 0.25);
}

.login-logo {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #f7d7dd;
    margin-bottom: 15px;
}

.login-title {
    font-family: 'Playfair Display', serif;
    color: #b76e79;
    font-size: 2.2rem;
    font-weight: 700;
}

.login-subtitle {
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

.btn-login {
    background: linear-gradient(135deg, #d88b9c, #b76e79);
    border: none;
    color: white;
    padding: 12px;
    border-radius: 14px;
    font-weight: 600;
    transition: 0.3s;
}

.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(183, 110, 121, 0.35);
}

.register-link {
    color: #b76e79;
    text-decoration: none;
    font-weight: 600;
}

.register-link:hover {
    color: #9c5b6b;
    text-decoration: underline;
}

.alert {
    border-radius: 12px;
}

</style>

</head>

<body class="d-flex flex-column min-vh-100">

<?php include 'layout/header.php'; ?>

<div class="container flex-grow-1 d-flex align-items-center justify-content-center py-5">

    <div class="row w-100 justify-content-center">

        <div class="col-md-7 col-lg-5">

            <div class="card login-card">

                <div class="text-center mb-4">

                    <img src="assets/gambar/logo.jpeg"
                         class="login-logo"
                         alt="Sekar Bouquet">

                    <h2 class="login-title">
                        Selamat Datang
                    </h2>

                    <p class="login-subtitle">
                        Masuk ke akun Sekar Bouquet 🌸
                    </p>

                </div>

                <?php if ($error != "") { ?>
                    <div class="alert alert-danger text-center fw-bold small">
                        <i class="fa-solid fa-circle-exclamation me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php } ?>

                <form method="post">

                    <div class="mb-3">

                        <label class="form-label">Username</label>

                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fa-solid fa-user"></i>
                            </span>

                            <input type="text"
                                   name="username"
                                   class="form-control"
                                   placeholder="Masukkan username"
                                   required>
                        </div>

                    </div>

                    <div class="mb-4">

                        <label class="form-label">Password</label>

                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fa-solid fa-lock"></i>
                            </span>

                            <input type="password"
                                   name="password"
                                   class="form-control"
                                   placeholder="Masukkan password"
                                   required>
                        </div>

                    </div>

                    <button type="submit"
                            name="login"
                            class="btn btn-login w-100 mb-4">

                        <i class="fa-solid fa-right-to-bracket me-2"></i>
                        Login Sekarang

                    </button>

                </form>

                <div class="text-center">

                    <p class="small text-muted mb-0">

                        Belum punya akun?

                        <a href="register.php" class="register-link">
                            Daftar di sini
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