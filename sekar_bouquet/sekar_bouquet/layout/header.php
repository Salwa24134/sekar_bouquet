<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sekar Bouquet</title>

    <!-- BOOTSTRAP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FONT -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

    <!-- ICON -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .navbar {
            z-index: 1050;
        }

        .nav-link {
            border-radius: 10px;
            transition: 0.3s;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.15);
        }

        .dropdown-menu {
            z-index: 9999;
            border-radius: 16px;
        }

        .dropdown-item:hover {
            background: #fceef1;
            padding-left: 18px;
        }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-lg"
     style="background: linear-gradient(135deg, #b76e79, #8d4f5c);">

    <div class="container">

        <!-- LOGO -->
        <a class="navbar-brand fw-bold d-flex align-items-center"
           href="index.php"
           style="font-family: 'Playfair Display', serif;">

            <img src="assets/gambar/logo.jpeg"
                 class="rounded-circle me-2 border border-2 border-light"
                 style="width:45px;height:45px;object-fit:cover;">

            Sekar Bouquet
        </a>

        <!-- TOGGLER -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- MENU -->
        <div class="collapse navbar-collapse" id="nav">

            <ul class="navbar-nav mx-auto">

                <li class="nav-item">
                    <a class="nav-link text-white" href="index.php">
                        <i class="fa fa-house me-1"></i> Beranda
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link text-white" href="produk.php">
                        <i class="fa fa-seedling me-1"></i> Bouquet
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link text-white" href="tentang.php">
                        <i class="fa fa-heart me-1"></i> Tentang
                    </a>
                </li>

            </ul>

            <ul class="navbar-nav ms-auto align-items-center">

                <!-- =========================
                     CEK LOGIN USER
                ========================== -->
                <?php if (isset($_SESSION['user']['username'])): ?>

                    <?php $user = $_SESSION['user']; ?>

                    <!-- ADMIN BUTTON -->
                    <?php if ($user['role'] === 'admin'): ?>
                        <a href="admin.php"
                           class="btn btn-warning rounded-pill me-3 fw-bold">
                            <i class="fa fa-gear me-1"></i> Admin
                        </a>
                    <?php endif; ?>

                    <!-- DROPDOWN USER -->
                    <li class="nav-item dropdown">

                        <a class="nav-link dropdown-toggle text-white d-flex align-items-center"
                           href="#"
                           data-bs-toggle="dropdown">

                            <?php
                            $foto = $user['foto'] ?? '';
                            $fotoPath = (!empty($foto) && file_exists("assets/foto/".$foto))
                                ? "assets/foto/".$foto
                                : "assets/foto/default.png";
                            ?>

                            <img src="<?php echo $fotoPath; ?>"
                                 class="rounded-circle me-2 border border-2 border-light"
                                 style="width:38px;height:38px;object-fit:cover;">

                            Hai, <?php echo htmlspecialchars($user['username']); ?> 🌸
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3">

                            <li>
                                <a class="dropdown-item py-2" href="profil_user.php">
                                    <i class="fa fa-user me-2"></i> Profil
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item py-2" href="riwayat_pesanan.php">
                                    <i class="fa fa-bag-shopping me-2"></i> Riwayat Pesanan
                                </a>
                            </li>

                            <li><hr class="dropdown-divider"></li>

                            <li>
                                <a class="dropdown-item py-2 text-danger fw-bold" href="logout.php">
                                    <i class="fa fa-right-from-bracket me-2"></i> Logout
                                </a>
                            </li>

                        </ul>
                    </li>

                <?php else: ?>

                    <!-- LOGIN -->
                    <a href="login.php"
                       class="btn btn-outline-light rounded-pill me-2">
                        Login
                    </a>

                    <!-- REGISTER -->
                    <a href="register.php"
                       class="btn btn-light rounded-pill fw-bold"
                       style="color:#b76e79;">
                        Register
                    </a>

                <?php endif; ?>

            </ul>

        </div>
    </div>
</nav>

<!-- BOOTSTRAP JS (WAJIB BIAR DROPDOWN HIDUP) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>