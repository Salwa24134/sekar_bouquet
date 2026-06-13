<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php'; 
?>
<style>
    .navbar {
        z-index: 1050;
        font-family: 'Poppins', sans-serif;
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

<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-lg"
     style="background: linear-gradient(135deg, #b76e79, #8d4f5c);">

    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center"
           href="index.php"
           style="font-family: 'Playfair Display', serif;">
            <img src="assets/gambar/logo.jpeg"
                 class="rounded-circle me-2 border border-2 border-light"
                 style="width:45px;height:45px;object-fit:cover;"
                 alt="Logo Sekar">
            Sekar Bouquet
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <ul class="navbar-nav mx-auto align-items-center">
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
                <li class="nav-item">
                    <?php 
                    $total_jenis_item = isset($_SESSION['keranjang']) ? count($_SESSION['keranjang']) : 0;
                    ?>
                    <a class="nav-link text-white position-relative px-2" href="keranjang.php" title="Lihat Keranjang Racikan">
                        <i class="fa fa-shopping-basket fs-5"></i>  Keranjang
                        <?php if ($total_jenis_item > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light" style="font-size: 0.65rem; padding: 0.25em 0.5em;">
                                <?= $total_jenis_item; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto align-items-center">
            <?php if (isset($_SESSION['username'])): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white d-flex align-items-center"
                       href="#"
                       id="userMenu"
                       role="button"
                       data-bs-toggle="dropdown"
                       aria-expanded="false">
                        <i class="fa fa-user-circle fs-5 me-2"></i>
                        Hai, <?= htmlspecialchars($_SESSION['username']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="userMenu">
                        <li>
                            <a class="dropdown-item" href="riwayat_pesanan.php">
                                <i class="fa fa-bag-shopping me-2 text-muted"></i>
                                Riwayat Pesanan
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="logout.php">
                                <i class="fa fa-right-from-bracket me-2"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </li>
            <?php else: ?>
                <li class="nav-item me-2">
                    <a href="login.php" class="btn btn-outline-light rounded-pill px-3">
                        Login
                    </a>
                </li>
                <li class="nav-item">
                    <a href="register.php" class="btn btn-light rounded-pill fw-bold px-3" style="color:#b76e79;">
                        Register
                    </a>
                </li>
            <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>