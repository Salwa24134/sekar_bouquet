<?php
// Memastikan zona waktu diatur ke Asia/Jakarta agar fungsi tahun dinamis berjalan akurat
date_default_timezone_set("Asia/Jakarta");
?>
<footer class="text-white py-5" 
        style="background: linear-gradient(135deg, #b76e79, #8d4f5c); border-top: 5px solid #fce4ec;">
    
    <div class="container">
        <div class="row g-5 text-start">

            <div class="col-md-5">
                <h3 class="fw-bold d-flex align-items-center mb-3 text-white">
                    <img src="assets/gambar/logo.jpeg" 
                         height="45" 
                         width="45" 
                         class="rounded-circle me-3 border border-2 border-light shadow-sm" 
                         style="object-fit: cover;"
                         alt="Logo Sekar Bouquet">
                    <span style="font-family: 'Playfair Display', serif; letter-spacing: 1px; color: #ffffff !important;">
                        SEKAR BOUQUET
                    </span>
                </h3>
                <p class="small opacity-75 lh-lg" style="font-size: 0.9rem; color: #ffffff !important;">
                    Sekar Bouquet menghadirkan rangkaian bunga elegan dan penuh makna untuk setiap momen spesialmu 🌸 
                    Kami berdedikasi menciptakan karya seni hand-tied bouquet premium demi mengabadikan kebahagiaan Anda.
                </p>
            </div>

            <div class="col-md-4">
                <h5 class="fw-bold mb-4 text-uppercase" style="font-size: 1rem; letter-spacing: 1px; color: #ffffff !important;">
                    <i class="fa-solid fa-location-dot me-2"></i> Lokasi Kami
                </h5>
                <p class="small mb-3 d-flex align-items-start opacity-90" style="font-size: 0.9rem; color: #ffffff !important;">
                    <i class="fa-solid fa-store mt-1 me-3" style="color: #fce4ec;"></i>
                    <span>
                        <strong>Store Sekar Bouquet</strong><br>
                        Jl. Raya Telang No. 45 (Depan Gerbang Utama UTM),<br>
                        Desa Telang, Kec. Kamal, Kab. Bangkalan,<br>
                        Madura, Jawa Timur<br>
                    </span>
                </p>
                <p class="small d-flex align-items-center opacity-90" style="font-size: 0.9rem; color: #ffffff !important;">
                    <i class="fa-solid fa-clock me-3" style="color: #fce4ec;"></i>
                    <span>
                        Buka Setiap Hari: 08.00 - 21.00 WIB
                    </span>
                </p>
            </div>

            <div class="col-md-3">
                <h5 class="fw-bold mb-4 text-uppercase" style="font-size: 1rem; letter-spacing: 1px; color: #ffffff !important;">
                    <i class="fa-solid fa-star me-2"></i> Keunggulan
                </h5>
                <div class="small opacity-90" style="font-size: 0.9rem; color: #ffffff !important;">
                    <div class="d-flex align-items-start mb-3">
                        <i class="fa-solid fa-seedling me-3 mt-1" style="color: #fce4ec;"></i>
                        <div>
                            <span class="fw-bold d-block mb-1">100% Bunga Fresh</span>
                            <span class="opacity-75">Dipilih langsung dari perkebunan lokal setiap pagi.</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-start">
                        <i class="fa-solid fa-gift me-3 mt-1" style="color: #fce4ec;"></i>
                        <div>
                            <span class="fw-bold d-block mb-1">Custom Wrapping</span>
                            <span class="opacity-75">Bebas request kombinasi warna kain Cellophane.</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <hr class="my-4 border-light opacity-25">

        <div class="text-center" style="color: #ffffff !important;">
            <p class="mb-2 small opacity-75" style="font-size: 0.85rem;">
                <i class="fa-solid fa-envelope me-2"></i> sekarbouquet@gmail.com
                <span class="mx-3">|</span>
                <i class="fa-brands fa-instagram me-2"></i> @sekarbouquet.ofc
            </p>
            <p class="mb-0 small opacity-50" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                &copy; <?php echo date("Y"); ?> Sekar Bouquet Telang. All rights reserved.
            </p>
        </div>
    </div>
</footer>