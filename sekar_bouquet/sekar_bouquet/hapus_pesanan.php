<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';

// Pastikan pengguna sudah login
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id_pesanan'])) {
    $id_pesanan = (int)$_GET['id_pesanan'];

    // 1. Hapus relasi detail produk terlebih dahulu
    $stmt1 = $koneksi->prepare("DELETE FROM detail_pesanan WHERE id_pesanan = ?");
    $stmt1->bind_param("i", $id_pesanan);
    $stmt1->execute();
    $stmt1->close();

    // 2. AMBIL ID BOUQUET terlebih dahulu sebelum menghapus bouquet_custom
    // Ini diperlukan untuk menghapus isi dari detail_bouquet
    $id_bouquet = null;
    $stmt_get = $koneksi->prepare("SELECT id_bouquet FROM bouquet_custom WHERE id_pesanan = ?");
    $stmt_get->bind_param("i", $id_pesanan);
    $stmt_get->execute();
    $result_get = $stmt_get->get_result();
    if ($row_get = $result_get->fetch_assoc()) {
        $id_bouquet = $row_get['id_bouquet'];
    }
    $stmt_get->close();

    // 3. Jika id_bouquet ditemukan, hapus isi detail_bouquet terlebih dahulu (SOLUSI ERROR)
    if ($id_bouquet !== null) {
        $stmt_detail_b = $koneksi->prepare("DELETE FROM detail_bouquet WHERE id_bouquet = ?");
        $stmt_detail_b->bind_param("i", $id_bouquet);
        $stmt_detail_b->execute();
        $stmt_detail_b->close();
    }

    // 4. Hapus relasi buket kustom utama setelah anaknya (detail_bouquet) bersih
    $stmt2 = $koneksi->prepare("DELETE FROM bouquet_custom WHERE id_pesanan = ?");
    $stmt2->bind_param("i", $id_pesanan);
    $stmt2->execute();
    $stmt2->close();

    // 5. Terakhir, hapus data utama pada tabel pesanan
    $stmt3 = $koneksi->prepare("DELETE FROM pesanan WHERE id_pesanan = ?");
    $stmt3->bind_param("i", $id_pesanan);
    $stmt3->execute();
    $stmt3->close();
}

// Kembalikan ke halaman asal berdasarkan role yang menghapus
if ($_SESSION['role'] === 'admin') {
    header("Location: pesanan_admin.php");
} else {
    header("Location: riwayat_pesanan.php");
}
exit();
?>