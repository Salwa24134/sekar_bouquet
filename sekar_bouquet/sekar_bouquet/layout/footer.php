<?php
// Memastikan zona waktu diatur ke Asia/Jakarta agar fungsi tahun dinamis berjalan akurat
date_default_timezone_set("Asia/Jakarta");
?>
<footer class="text-white py-5" 
        style="background: linear-gradient(135deg, #b76e79, #8d4f5c); border-top: 5px solid #f7d7dd;">
    
    <div class="container">
        <div class="row g-5">

            <div class="col-md-5">
                <h3 class="fw-bold d-flex align-items-center mb-3">
                    <img src="assets/gambar/logo.jpeg" 
                         height="50" 
                         width="50" 
                         class="rounded-circle me-3 border border-3 border-light shadow-sm" 
                         alt="Logo Sekar Bouquet">
                    <span style="font-family: 'Playfair Display', serif;">
                        SEKAR BOUQUET
                    </span>
                </h3>
                <p class="small opacity-75 lh-lg">
                    Sekar Bouquet menghadirkan rangkaian bunga elegan
                    dan penuh makna untuk setiap momen spesialmu 🌸
                    Mulai dari bouquet wisuda, ulang tahun, anniversary,
                    hingga hadiah spesial untuk orang tersayang.
                </p>
            </div>

            <div class="col-md-4">
                <h5 class="fw-bold mb-4 text-uppercase">
                    <i class="fa-solid fa-location-dot me-2"></i> Lokasi Kami
                </h5>
                <p class="small mb-3 d-flex align-items-start">
                    <i class="fa-solid fa-store mt-1 me-3 text-warning"></i>
                    <span>
                        Jl. Mawar Indah No. 12,<br>
                        Surabaya, Jawa Timur,<br>
                        Indonesia
                    </span>
                </p>
                <p class="small d-flex align-items-center">
                    <i class="fa-solid fa-clock me-3 text-warning"></i>
                    <span>
                        Buka Setiap Hari: 08.00 - 21.00 WIB
                    </span>
                </p>
            </div>

            <div class="col-md-3">
                <h5 class="fw-bold mb-4 text-uppercase">
                    <i class="fa-solid fa-heart me-2"></i> Keunggulan
                </h5>
                <div class="small">
                    <div class="d-flex align-items-start mb-4">
                        <i class="fa-solid fa-seedling me-3 text-warning mt-1"></i>
                        <div>
                            <span class="fw-bold d-block">Bunga Fresh</span>
                            <span class="opacity-75">
                                Menggunakan bunga pilihan berkualitas premium.
                            </span>
                        </div>
                    </div>

                    <div class="d-flex align-items-start">
                        <i class="fa-solid fa-gift me-3 text-warning mt-1"></i>
                        <div>
                            <span class="fw-bold d-block">Custom Bouquet</span>
                            <span class="opacity-75">
                                Bisa request desain sesuai keinginanmu.
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <hr class="my-4 border-light opacity-25">

        <div class="text-center">
            <p class="mb-2 small opacity-75">
                <i class="fa-solid fa-envelope me-2"></i> sekarbouquet@gmail.com
                <span class="mx-2">|</span>
                <i class="fa-brands fa-instagram me-2"></i> @sekarbouquet
            </p>
            <p class="mb-0 small opacity-50">
                &copy; <?php echo date("Y"); ?> Sekar Bouquet. All rights reserved.
            </p>
        </div>
    </div>

</footer>