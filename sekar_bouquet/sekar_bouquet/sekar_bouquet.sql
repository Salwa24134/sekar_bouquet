-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 13 Jun 2026 pada 08.38
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.3.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sekar_bouquet`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `barang_rusak`
--

CREATE TABLE `barang_rusak` (
  `id_rusak` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `tanggal` date DEFAULT NULL,
  `jumlah` int(11) DEFAULT NULL,
  `keterangan` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `bouquet_custom`
--

CREATE TABLE `bouquet_custom` (
  `id_bouquet` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `nama_bouquet` varchar(100) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `ongkos_rakit` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_bouquet`
--

CREATE TABLE `detail_bouquet` (
  `id_detail_bouquet` int(11) NOT NULL,
  `id_bouquet` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_pembelian`
--

CREATE TABLE `detail_pembelian` (
  `id_detail_beli` int(11) NOT NULL,
  `id_pembelian` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_beli` int(11) NOT NULL,
  `subtotal` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id_detail` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga` int(11) NOT NULL,
  `subtotal` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `detail_pesanan`
--

INSERT INTO `detail_pesanan` (`id_detail`, `id_pesanan`, `id_produk`, `jumlah`, `harga`, `subtotal`) VALUES
(1, 4, 68, 1, 7000, 7000),
(2, 6, 69, 1, 12000, 12000),
(3, 7, 68, 1, 7000, 7000),
(4, 8, 63, 1, 10000, 10000),
(5, 8, 14, 10, 5000, 50000),
(6, 8, 45, 1, 150000, 150000),
(7, 8, 38, 1, 3000, 3000),
(8, 9, 63, 1, 10000, 10000),
(9, 9, 14, 10, 5000, 50000),
(10, 9, 45, 1, 150000, 150000),
(11, 9, 38, 1, 3000, 3000),
(12, 10, 63, 1, 10000, 10000),
(13, 10, 14, 10, 5000, 50000),
(14, 10, 45, 1, 150000, 150000),
(15, 10, 38, 1, 3000, 3000),
(16, 11, 68, 1, 7000, 7000),
(17, 12, 64, 1, 6000, 6000),
(18, 13, 69, 5, 12000, 60000),
(19, 13, 27, 1, 5000, 5000),
(20, 13, 19, 1, 7000, 7000),
(21, 13, 17, 1, 8000, 8000),
(22, 13, 35, 6, 5000, 30000),
(23, 14, 21, 8, 10000, 80000),
(24, 14, 14, 8, 5000, 40000),
(25, 14, 13, 8, 5000, 40000),
(26, 14, 35, 5, 5000, 25000),
(27, 15, 67, 10, 6000, 60000),
(28, 15, 60, 2, 8000, 16000),
(29, 15, 36, 4, 4000, 16000),
(30, 16, 55, 1, 8000, 8000),
(31, 16, 29, 1, 7000, 7000),
(32, 16, 26, 2, 6000, 12000),
(33, 16, 24, 1, 8000, 8000),
(34, 16, 23, 1, 4000, 4000),
(35, 16, 18, 4, 5000, 20000),
(36, 16, 17, 1, 8000, 8000),
(37, 16, 35, 6, 5000, 30000),
(38, 16, 52, 1, 7000, 7000),
(39, 17, 18, 1, 5000, 5000),
(40, 17, 15, 1, 8000, 8000),
(41, 17, 41, 1, 90000, 90000),
(42, 17, 32, 1, 3000, 3000),
(43, 18, 15, 10, 8000, 80000),
(44, 18, 44, 1, 125000, 125000),
(45, 18, 51, 1, 1500, 1500);

--
-- Trigger `detail_pesanan`
--
DELIMITER $$
CREATE TRIGGER `kurang_stok` AFTER INSERT ON `detail_pesanan` FOR EACH ROW BEGIN
    UPDATE produk
    SET stok = stok - NEW.jumlah
    WHERE id_produk = NEW.id_produk;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tambah_stok` AFTER DELETE ON `detail_pesanan` FOR EACH ROW BEGIN
    UPDATE produk
    SET stok = stok + OLD.jumlah
    WHERE id_produk = OLD.id_produk;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori_produk`
--

CREATE TABLE `kategori_produk` (
  `id_kategori` int(11) NOT NULL,
  `nama_kategori` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kategori_produk`
--

INSERT INTO `kategori_produk` (`id_kategori`, `nama_kategori`) VALUES
(1, 'Bunga'),
(2, 'Wrapping Paper'),
(3, 'Boneka'),
(4, 'Pita'),
(5, 'Aksesoris');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pelanggan`
--

CREATE TABLE `pelanggan` (
  `id_pelanggan` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pelanggan`
--

INSERT INTO `pelanggan` (`id_pelanggan`, `nama`, `email`, `telepon`, `alamat`) VALUES
(1, 'Salwa', 'salwa@gmail.com', '082245612723', 'Malang'),
(2, 'salwa', 'salwa@gmail.com', NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pembelian`
--

CREATE TABLE `pembelian` (
  `id_pembelian` int(11) NOT NULL,
  `id_supplier` int(11) NOT NULL,
  `tanggal` datetime DEFAULT current_timestamp(),
  `total_beli` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanan`
--

CREATE TABLE `pesanan` (
  `id_pesanan` int(11) NOT NULL,
  `id_pelanggan` int(11) NOT NULL,
  `tanggal` datetime DEFAULT current_timestamp(),
  `total` int(11) DEFAULT 0,
  `status` enum('Menunggu Pembayaran','Diproses','Selesai','Dibatalkan') DEFAULT 'Menunggu Pembayaran',
  `metode_pembayaran` varchar(50) DEFAULT NULL,
  `bukti` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pesanan`
--

INSERT INTO `pesanan` (`id_pesanan`, `id_pelanggan`, `tanggal`, `total`, `status`, `metode_pembayaran`, `bukti`) VALUES
(4, 1, '2026-06-12 14:48:03', 7000, '', 'Transfer Bank', 'BUKTI_1781250483_6a2bb9b3e75b0.png'),
(6, 2, '2026-06-13 03:01:28', 12000, '', 'Transfer Bank', 'BUKTI_1781294488_6a2c6598bacbd.jpeg'),
(7, 2, '2026-06-13 03:17:49', 7000, 'Menunggu Pembayaran', 'Transfer Bank', 'BUKTI_1781295469_6a2c696d52113.jpeg'),
(8, 2, '2026-06-13 04:19:37', 213000, 'Menunggu Pembayaran', 'Transfer Bank', 'BUKTI_1781299177_6a2c77e9a887c.jpeg'),
(9, 2, '2026-06-13 04:20:05', 213000, 'Menunggu Pembayaran', 'Transfer Bank', 'BUKTI_1781299205_6a2c780545233.jpeg'),
(10, 2, '2026-06-13 04:20:18', 213000, 'Menunggu Pembayaran', 'Transfer Bank', 'BUKTI_1781299218_6a2c7812f11ca.jpeg'),
(11, 2, '2026-06-13 04:27:23', 7000, '', 'Transfer Bank', 'BUKTI_1781299643_6a2c79bb5057f.jpeg'),
(12, 2, '2026-06-13 04:29:02', 6000, 'Diproses', 'Transfer Bank', 'BUKTI_1781299742_6a2c7a1eb7cdc.jpeg'),
(13, 2, '2026-06-13 05:06:25', 110000, 'Menunggu Pembayaran', 'Transfer Bank', 'BUKTI_1781301985_6a2c82e149841.jpeg'),
(14, 2, '2026-06-13 05:19:36', 185000, '', 'Transfer Bank', 'BUKTI_1781302776_6a2c85f87c687.jpeg'),
(15, 2, '2026-06-13 05:23:07', 92000, 'Diproses', 'Transfer Bank', 'BUKTI_1781302987_6a2c86cb25f63.jpeg'),
(16, 2, '2026-06-13 05:26:57', 104000, '', 'Transfer Bank', 'BUKTI_1781303217_6a2c87b1d72de.jpeg'),
(17, 2, '2026-06-13 10:20:35', 106000, 'Diproses', 'Transfer Bank', 'BUKTI_1781320835_6a2ccc8333a89.jpeg'),
(18, 2, '2026-06-13 12:25:24', 206500, 'Selesai', 'Transfer Bank', 'BUKTI_1781328324_6a2ce9c48dafb.jpeg');

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk`
--

CREATE TABLE `produk` (
  `id_produk` int(11) NOT NULL,
  `id_kategori` int(11) NOT NULL,
  `id_supplier` int(11) NOT NULL,
  `nama_produk` varchar(100) NOT NULL,
  `warna` varchar(50) DEFAULT NULL,
  `ukuran` varchar(30) DEFAULT NULL,
  `harga_beli` int(11) NOT NULL,
  `harga_jual` int(11) NOT NULL,
  `stok` int(11) DEFAULT 0,
  `stok_minimum` int(11) DEFAULT 10,
  `tanggal_masuk` date DEFAULT NULL,
  `tanggal_expired` date DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `produk`
--

INSERT INTO `produk` (`id_produk`, `id_kategori`, `id_supplier`, `nama_produk`, `warna`, `ukuran`, `harga_beli`, `harga_jual`, `stok`, `stok_minimum`, `tanggal_masuk`, `tanggal_expired`, `gambar`) VALUES
(12, 1, 1, 'Red Rose', NULL, NULL, 0, 5000, 1499, 10, NULL, NULL, 'PRODUK_1781212571.jpg'),
(13, 1, 1, 'White Rose', NULL, NULL, 0, 5000, 1487, 10, NULL, NULL, 'PRODUK_1781212695.jpg'),
(14, 1, 1, 'Pink Rose', NULL, NULL, 0, 5000, 1458, 10, NULL, NULL, 'PRODUK_1781212769.jpg'),
(15, 1, 4, 'White Lily', NULL, NULL, 0, 8000, 977, 10, NULL, NULL, 'PRODUK_1781212945.jpg'),
(16, 1, 4, 'Pink Lily', NULL, NULL, 0, 8000, 999, 10, NULL, NULL, 'PRODUK_1781212972.jpg'),
(17, 1, 4, 'Yellow Lily', NULL, NULL, 0, 8000, 998, 10, NULL, NULL, 'PRODUK_1781213007.jpg'),
(18, 1, 1, 'Baby Breath', NULL, NULL, 0, 5000, 1994, 10, NULL, NULL, 'PRODUK_1781213074.jpg'),
(19, 1, 4, 'Pink Dahlia', NULL, NULL, 0, 7000, 499, 10, NULL, NULL, 'PRODUK_1781213118.jpg'),
(20, 1, 5, 'Pink Daisy', NULL, NULL, 0, 6000, 500, 10, NULL, NULL, 'PRODUK_1781213223.jpg'),
(21, 1, 5, 'Pink Tulip', NULL, NULL, 0, 10000, 992, 10, NULL, NULL, 'PRODUK_1781213278.jpg'),
(22, 1, 1, 'Purple Pompom', NULL, NULL, 0, 5000, 500, 10, NULL, NULL, 'PRODUK_1781213316.jpg'),
(23, 1, 1, 'Sedap Malam', NULL, NULL, 0, 4000, 1499, 10, NULL, NULL, 'PRODUK_1781213348.jpg'),
(24, 1, 4, 'Sunflower', NULL, NULL, 0, 8000, 1499, 10, NULL, NULL, 'PRODUK_1781213378.jpg'),
(25, 1, 4, 'White Dahlia', NULL, NULL, 0, 7000, 500, 10, NULL, NULL, 'PRODUK_1781213525.jpg'),
(26, 1, 5, 'White Daisy', NULL, NULL, 0, 6000, 498, 10, NULL, NULL, 'PRODUK_1781213572.jpg'),
(27, 1, 1, 'White Pompom', NULL, NULL, 0, 5000, 499, 10, NULL, NULL, 'PRODUK_1781213608.jpg'),
(28, 1, 5, 'White Tulip', NULL, NULL, 0, 10000, 1000, 10, NULL, NULL, 'PRODUK_1781213678.jpg'),
(29, 1, 4, 'Yellow Dahlia', NULL, NULL, 0, 7000, 499, 10, NULL, NULL, 'PRODUK_1781213719.jpg'),
(30, 1, 1, 'Yellow Pompom', NULL, NULL, 0, 5000, 500, 10, NULL, NULL, 'PRODUK_1781213755.jpg'),
(31, 2, 2, 'Craft Paper', NULL, NULL, 0, 3000, 2500, 10, NULL, NULL, 'PRODUK_1781214984.jpg'),
(32, 2, 2, 'Mica Transparan', NULL, NULL, 0, 3000, 998, 10, NULL, NULL, 'PRODUK_1781215013.webp'),
(33, 2, 2, 'Polynet Paper', NULL, NULL, 0, 3000, 1500, 10, NULL, NULL, 'PRODUK_1781215042.jpg'),
(34, 2, 2, 'Tissue Paper', NULL, NULL, 0, 2000, 1991, 10, NULL, NULL, 'PRODUK_1781215088.jpg'),
(35, 2, 2, 'Wrapping Paper Korean', NULL, NULL, 0, 5000, 2483, 10, NULL, NULL, 'PRODUK_1781215167.jpg'),
(36, 2, 2, 'Wrapping Paper Motif Kotak', NULL, NULL, 0, 4000, 1496, 10, NULL, NULL, 'PRODUK_1781215222.jpg'),
(37, 2, 2, 'Wrapping Paper Motif Random', NULL, NULL, 0, 4000, 1500, 10, NULL, NULL, 'PRODUK_1781215289.jpg'),
(38, 2, 2, 'Wrapping Paper Polos', NULL, NULL, 0, 3000, 1997, 10, NULL, NULL, 'PRODUK_1781215320.jpg'),
(39, 3, 3, 'Bunny Doll', NULL, NULL, 0, 90000, 100, 10, NULL, NULL, 'PRODUK_1781215701.jpg'),
(40, 3, 3, 'Capybara Doll', NULL, NULL, 0, 95000, 150, 10, NULL, NULL, 'PRODUK_1781215731.jpg'),
(41, 3, 3, 'Duck Doll', NULL, NULL, 0, 90000, 98, 10, NULL, NULL, 'PRODUK_1781215763.jpg'),
(42, 3, 3, 'Nailong Doll', NULL, NULL, 0, 100000, 150, 10, NULL, NULL, 'PRODUK_1781215797.jpg'),
(43, 3, 3, 'Piggy Doll', NULL, NULL, 0, 95000, 100, 10, NULL, NULL, 'PRODUK_1781215829.jpg'),
(44, 3, 3, 'Stitch', NULL, NULL, 0, 125000, 98, 10, NULL, NULL, 'PRODUK_1781215864.webp'),
(45, 3, 3, 'Teddy Bear Doll', NULL, NULL, 0, 150000, 147, 10, NULL, NULL, 'PRODUK_1781215892.jpg'),
(46, 3, 3, 'Teddy Grad', NULL, NULL, 0, 200000, 150, 10, NULL, NULL, 'PRODUK_1781215925.jpg'),
(47, 5, 6, 'Fake Butterfly', NULL, NULL, 0, 3000, 2000, 10, NULL, NULL, 'PRODUK_1781216833.jpg'),
(48, 5, 6, 'Mini Crown', NULL, NULL, 0, 5000, 1500, 10, NULL, NULL, 'PRODUK_1781216883.jpg'),
(49, 4, 6, 'Korean Reborn', NULL, NULL, 0, 2500, 2000, 10, NULL, NULL, 'PRODUK_1781216931.jpg'),
(50, 4, 6, 'Large Satin Reborn', NULL, NULL, 0, 3000, 1500, 10, NULL, NULL, 'PRODUK_1781216977.jpg'),
(51, 4, 6, 'Small Satin Reborn', NULL, NULL, 0, 1500, 1997, 10, NULL, NULL, 'PRODUK_1781217012.jpg'),
(52, 4, 6, 'Custom Message Reborn', NULL, NULL, 0, 7000, 1999, 10, NULL, NULL, 'PRODUK_1781217073.jpg'),
(53, 1, 4, 'Blue Anemone', NULL, NULL, 0, 10000, 700, 10, NULL, NULL, 'PRODUK_1781218159.jpg'),
(54, 1, 5, 'Yellow Ranunculus', NULL, NULL, 0, 8000, 700, 10, NULL, NULL, 'PRODUK_1781218194.jpg'),
(55, 1, 5, 'Orange Ranunculus', NULL, NULL, 0, 8000, 699, 10, NULL, NULL, 'PRODUK_1781218223.webp'),
(56, 1, 4, 'Pink Anemone', NULL, NULL, 0, 10000, 700, 10, NULL, NULL, 'PRODUK_1781218251.jpg'),
(57, 1, 1, 'Pink Carnation', NULL, NULL, 0, 6000, 700, 10, NULL, NULL, 'PRODUK_1781218278.jpg'),
(58, 1, 5, 'Pink Lisianthus', NULL, NULL, 0, 7000, 700, 10, NULL, NULL, 'PRODUK_1781218319.jpg'),
(59, 1, 4, 'Pink Peony', NULL, NULL, 0, 12000, 700, 10, NULL, NULL, 'PRODUK_1781218348.webp'),
(60, 1, 5, 'Pink Ranunculus', NULL, NULL, 0, 8000, 698, 10, NULL, NULL, 'PRODUK_1781218379.webp'),
(61, 1, 1, 'Purple Carnation', NULL, NULL, 0, 6000, 700, 10, NULL, NULL, 'PRODUK_1781218423.jpg'),
(62, 1, 5, 'Purple Lisianthus', NULL, NULL, 0, 7000, 700, 10, NULL, NULL, 'PRODUK_1781218464.jpg'),
(63, 1, 4, 'Red Anemone', NULL, NULL, 0, 10000, 697, 10, NULL, NULL, 'PRODUK_1781218495.webp'),
(64, 1, 1, 'Red Carnation', NULL, NULL, 0, 6000, 699, 10, NULL, NULL, 'PRODUK_1781218532.jpg'),
(65, 1, 4, 'Red Peony', NULL, NULL, 0, 12000, 700, 10, NULL, NULL, 'PRODUK_1781218558.webp'),
(66, 1, 4, 'White Anemone', NULL, NULL, 0, 10000, 700, 10, NULL, NULL, 'PRODUK_1781218591.webp'),
(67, 1, 1, 'White Carnation', NULL, NULL, 0, 6000, 690, 10, NULL, NULL, 'PRODUK_1781218618.jpg'),
(68, 1, 5, 'White Lisianthus', NULL, NULL, 0, 7000, 697, 10, NULL, NULL, 'PRODUK_1781218676.jpg'),
(69, 1, 4, 'White Peony', NULL, NULL, 0, 12000, 693, 10, NULL, NULL, 'PRODUK_1781218702.jpg'),
(70, 1, 1, 'Yellow Carnation', NULL, NULL, 0, 6000, 700, 10, NULL, NULL, 'PRODUK_1781218736.jpg');

-- --------------------------------------------------------

--
-- Struktur dari tabel `retur_supplier`
--

CREATE TABLE `retur_supplier` (
  `id_retur` int(11) NOT NULL,
  `id_supplier` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `jumlah` int(11) DEFAULT NULL,
  `alasan` varchar(255) DEFAULT NULL,
  `tanggal` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `supplier`
--

CREATE TABLE `supplier` (
  `id_supplier` int(11) NOT NULL,
  `nama_supplier` varchar(100) NOT NULL,
  `alamat` text DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `supplier`
--

INSERT INTO `supplier` (`id_supplier`, `nama_supplier`, `alamat`, `telepon`, `email`) VALUES
(1, 'CV Bunga Nusantara', 'Surabaya', '081111111111', 'bunga@gmail.com'),
(2, 'PT Wrapping Cantik', 'Sidoarjo', '082222222222', 'wrap@gmail.com'),
(3, 'Boneka Indonesia', 'Malang', '083333333333', 'boneka@gmail.com'),
(4, 'PT Bunga Mekar Berseri', 'Surabaya', '0899999991', NULL),
(5, 'PT Harum Abadi', 'Malang', '0811122334455', NULL),
(6, 'Gift Decoration Center', 'Surabaya', '0877664411891', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telp` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `telp`, `password`, `role`, `foto`) VALUES
(1, 'admin', 'admin@gmail.com', '081234567890', 'admin123', 'admin', 'default_admin.jpg'),
(2, 'salwa', 'salwa@gmail.com', NULL, '$2y$10$VX7KQkkG3rxk5K2M0fQQr.abAs9c2dwWiqmfSfE3Pi2j2kaCgJQHu', 'user', '1781294008_contoh_pp.jpg');

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `view_laba`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `view_laba` (
`id_produk` int(11)
,`nama_produk` varchar(100)
,`total_terjual` decimal(32,0)
,`harga_beli` int(11)
,`harga_jual` int(11)
,`laba` decimal(43,0)
);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `v_riwayat_pesanan`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `v_riwayat_pesanan` (
`id_pesanan` int(11)
,`id_pelanggan` int(11)
,`tanggal` datetime
,`total` int(11)
,`status` enum('Menunggu Pembayaran','Diproses','Selesai','Dibatalkan')
,`metode_pembayaran` varchar(50)
,`nama_produk` varchar(100)
,`jumlah` int(11)
);

-- --------------------------------------------------------

--
-- Struktur untuk view `view_laba`
--
DROP TABLE IF EXISTS `view_laba`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_laba`  AS SELECT `p`.`id_produk` AS `id_produk`, `p`.`nama_produk` AS `nama_produk`, sum(`dp`.`jumlah`) AS `total_terjual`, `p`.`harga_beli` AS `harga_beli`, `p`.`harga_jual` AS `harga_jual`, sum(`dp`.`jumlah`) * (`p`.`harga_jual` - `p`.`harga_beli`) AS `laba` FROM (`detail_pesanan` `dp` join `produk` `p` on(`dp`.`id_produk` = `p`.`id_produk`)) GROUP BY `p`.`id_produk` ;

-- --------------------------------------------------------

--
-- Struktur untuk view `v_riwayat_pesanan`
--
DROP TABLE IF EXISTS `v_riwayat_pesanan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_riwayat_pesanan`  AS SELECT `p`.`id_pesanan` AS `id_pesanan`, `p`.`id_pelanggan` AS `id_pelanggan`, `p`.`tanggal` AS `tanggal`, `p`.`total` AS `total`, `p`.`status` AS `status`, `p`.`metode_pembayaran` AS `metode_pembayaran`, `pr`.`nama_produk` AS `nama_produk`, `dp`.`jumlah` AS `jumlah` FROM ((`pesanan` `p` join `detail_pesanan` `dp` on(`p`.`id_pesanan` = `dp`.`id_pesanan`)) join `produk` `pr` on(`dp`.`id_produk` = `pr`.`id_produk`)) ;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `barang_rusak`
--
ALTER TABLE `barang_rusak`
  ADD PRIMARY KEY (`id_rusak`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indeks untuk tabel `bouquet_custom`
--
ALTER TABLE `bouquet_custom`
  ADD PRIMARY KEY (`id_bouquet`),
  ADD KEY `id_pesanan` (`id_pesanan`);

--
-- Indeks untuk tabel `detail_bouquet`
--
ALTER TABLE `detail_bouquet`
  ADD PRIMARY KEY (`id_detail_bouquet`),
  ADD KEY `id_bouquet` (`id_bouquet`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indeks untuk tabel `detail_pembelian`
--
ALTER TABLE `detail_pembelian`
  ADD PRIMARY KEY (`id_detail_beli`),
  ADD KEY `id_pembelian` (`id_pembelian`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indeks untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_pesanan` (`id_pesanan`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indeks untuk tabel `kategori_produk`
--
ALTER TABLE `kategori_produk`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indeks untuk tabel `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`id_pelanggan`);

--
-- Indeks untuk tabel `pembelian`
--
ALTER TABLE `pembelian`
  ADD PRIMARY KEY (`id_pembelian`),
  ADD KEY `id_supplier` (`id_supplier`);

--
-- Indeks untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id_pesanan`),
  ADD KEY `id_pelanggan` (`id_pelanggan`);

--
-- Indeks untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id_produk`),
  ADD KEY `id_kategori` (`id_kategori`),
  ADD KEY `id_supplier` (`id_supplier`);

--
-- Indeks untuk tabel `retur_supplier`
--
ALTER TABLE `retur_supplier`
  ADD PRIMARY KEY (`id_retur`),
  ADD KEY `id_supplier` (`id_supplier`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indeks untuk tabel `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`id_supplier`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `barang_rusak`
--
ALTER TABLE `barang_rusak`
  MODIFY `id_rusak` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `bouquet_custom`
--
ALTER TABLE `bouquet_custom`
  MODIFY `id_bouquet` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `detail_bouquet`
--
ALTER TABLE `detail_bouquet`
  MODIFY `id_detail_bouquet` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `detail_pembelian`
--
ALTER TABLE `detail_pembelian`
  MODIFY `id_detail_beli` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT untuk tabel `kategori_produk`
--
ALTER TABLE `kategori_produk`
  MODIFY `id_kategori` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `pelanggan`
--
ALTER TABLE `pelanggan`
  MODIFY `id_pelanggan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `pembelian`
--
ALTER TABLE `pembelian`
  MODIFY `id_pembelian` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id_pesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT untuk tabel `produk`
--
ALTER TABLE `produk`
  MODIFY `id_produk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT untuk tabel `retur_supplier`
--
ALTER TABLE `retur_supplier`
  MODIFY `id_retur` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `supplier`
--
ALTER TABLE `supplier`
  MODIFY `id_supplier` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `barang_rusak`
--
ALTER TABLE `barang_rusak`
  ADD CONSTRAINT `barang_rusak_ibfk_1` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`);

--
-- Ketidakleluasaan untuk tabel `bouquet_custom`
--
ALTER TABLE `bouquet_custom`
  ADD CONSTRAINT `bouquet_custom_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`);

--
-- Ketidakleluasaan untuk tabel `detail_bouquet`
--
ALTER TABLE `detail_bouquet`
  ADD CONSTRAINT `detail_bouquet_ibfk_1` FOREIGN KEY (`id_bouquet`) REFERENCES `bouquet_custom` (`id_bouquet`),
  ADD CONSTRAINT `detail_bouquet_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`);

--
-- Ketidakleluasaan untuk tabel `detail_pembelian`
--
ALTER TABLE `detail_pembelian`
  ADD CONSTRAINT `detail_pembelian_ibfk_1` FOREIGN KEY (`id_pembelian`) REFERENCES `pembelian` (`id_pembelian`),
  ADD CONSTRAINT `detail_pembelian_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`);

--
-- Ketidakleluasaan untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `detail_pesanan_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`),
  ADD CONSTRAINT `detail_pesanan_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`);

--
-- Ketidakleluasaan untuk tabel `pembelian`
--
ALTER TABLE `pembelian`
  ADD CONSTRAINT `pembelian_ibfk_1` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`);

--
-- Ketidakleluasaan untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `pesanan_ibfk_1` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`);

--
-- Ketidakleluasaan untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD CONSTRAINT `produk_ibfk_1` FOREIGN KEY (`id_kategori`) REFERENCES `kategori_produk` (`id_kategori`),
  ADD CONSTRAINT `produk_ibfk_2` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`);

--
-- Ketidakleluasaan untuk tabel `retur_supplier`
--
ALTER TABLE `retur_supplier`
  ADD CONSTRAINT `retur_supplier_ibfk_1` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`),
  ADD CONSTRAINT `retur_supplier_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
