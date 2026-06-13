-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 13, 2026 at 08:50 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_audit_loyalitas_pelanggan` (IN `p_bulan` VARCHAR(7))   BEGIN
    -- 1. Deklarasi Variabel Penampung Data Cursor
    DECLARE v_id_pelanggan INT;
    DECLARE v_nama_pelanggan VARCHAR(100);
    DECLARE v_total_belanja INT;
    DECLARE v_kode_voucher VARCHAR(20);
    DECLARE v_potongan_harga INT;
    
    -- Variabel bendera status selesainya loop cursor
    DECLARE v_selesai INT DEFAULT 0;

    -- 2. Deklarasi CURSOR: Ditambahkan ORDER BY DESC dan LIMIT 5 (Maksimal 5 akun paling loyal)
    DECLARE cur_loyalitas CURSOR FOR 
        SELECT p.id_pelanggan, p.nama, SUM(pes.total) AS total_akumulasi
        FROM pelanggan p
        JOIN pesanan pes ON p.id_pelanggan = pes.id_pelanggan
        WHERE DATE_FORMAT(pes.tanggal, '%Y-%m') = p_bulan
          AND pes.status = 'Selesai'
        GROUP BY p.id_pelanggan
        HAVING total_akumulasi >= 250000 -- Hanya akun yang memenuhi syarat minimal tier Silver ke atas
        ORDER BY total_akumulasi DESC     -- Diurutkan dari yang belanja paling banyak
        LIMIT 5;                         -- REVISI: Hanya mengambil maksimal 5 akun per bulan

    -- 3. Deklarasi Handler untuk mengubah flag saat cursor mencapai baris terakhir
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_selesai = 1;

    -- 4. Membuka Pointer Cursor
    OPEN cur_loyalitas;

    -- 5. Proses Looping Data Baris demi Baris
    loyalitas_loop: LOOP
        -- Mengambil data baris saat ini ke dalam variabel penampung
        FETCH cur_loyalitas INTO v_id_pelanggan, v_nama_pelanggan, v_total_belanja;
        
        -- Keluar dari loop jika semua data selesai dipindai (atau jika kuota 5 data telah habis)
        IF v_selesai = 1 THEN 
            LEAVE loyalitas_loop;
        END IF;

        -- 6. Logika Evaluasi Tingkat Loyalitas & Penentuan Nilai Voucher
        IF v_total_belanja >= 1000000 THEN
            SET v_potongan_harga = 100000;
            SET v_kode_voucher = CONCAT('PLAT-', UPPER(SUBSTRING(MD5(RAND()), 1, 6)));
        ELSEIF v_total_belanja >= 500000 THEN
            SET v_potongan_harga = 50000;
            SET v_kode_voucher = CONCAT('GOLD-', UPPER(SUBSTRING(MD5(RAND()), 1, 6)));
        ELSEIF v_total_belanja >= 250000 THEN
            SET v_potongan_harga = 20000;
            SET v_kode_voucher = CONCAT('SLVR-', UPPER(SUBSTRING(MD5(RAND()), 1, 6)));
        ELSE
            SET v_potongan_harga = 0;
        END IF;

        -- 7. Eksekusi Insert Data Voucher ke Tabel dengan Proteksi Bulanan
        IF v_potongan_harga > 0 THEN
            -- REVISI: Mengunci agar 1 akun benar-benar maksimal hanya dapat 1 voucher dalam bulan berjalan
            IF NOT EXISTS (
                SELECT 1 FROM voucher_pelanggan 
                WHERE id_pelanggan = v_id_pelanggan 
                  -- Asumsi tabel voucher_pelanggan memiliki kolom tanggal untuk tracking (misal: 'tanggal_didapat' atau 'created_at')
                  -- Jika nama kolom tanggal Anda berbeda, silakan sesuaikan bagian 'tanggal_didapat' di bawah ini:
                  AND DATE_FORMAT(tanggal_didapat, '%Y-%m') = p_bulan
            ) THEN
                INSERT INTO voucher_pelanggan (id_pelanggan, kode_voucher, potongan_harga, status_aktif)
                VALUES (v_id_pelanggan, v_kode_voucher, v_potongan_harga, 'Aktif');
            END IF;
        END IF;

    END LOOP loyalitas_loop;

    -- 8. Menutup Koneksi Pointer Cursor dari Memori RAM
    CLOSE cur_loyalitas;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_closing_bulanan` (IN `p_bulan` VARCHAR(20))   BEGIN
    -- 1. Deklarasi Variabel untuk menampung data sementara dari Cursor
    DECLARE v_total INT;
    DECLARE v_id_pesanan INT;
    DECLARE done INT DEFAULT FALSE;
    
    -- 2. Deklarasi Variabel untuk akumulasi total laporan
    DECLARE total_omset_akumulasi INT DEFAULT 0;
    DECLARE total_laba_akumulasi INT DEFAULT 0;
    DECLARE laba_per_pesanan INT DEFAULT 0;

    -- 3. DEKLARASI CURSOR: Membaca baris demi baris pesanan Selesai pada bulan terpilih
    DECLARE cursor_pesanan CURSOR FOR 
        SELECT id_pesanan, total 
        FROM pesanan 
        WHERE status = 'Selesai' AND DATE_FORMAT(tanggal, '%Y-%m') = p_bulan;

    -- 4. Deklarasi Handler jika baris data di Cursor sudah habis dibaca
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    -- 5. Buka Cursor
    OPEN cursor_pesanan;

    -- 6. PERULANGAN CURSOR (Membaca data baris demi baris)
    read_loop: LOOP
        FETCH cursor_pesanan INTO v_id_pesanan, v_total;
        
        -- Jika baris habis, keluar dari perulangan
        IF done THEN
            LEAVE read_loop;
        END IF;

        -- Hitung Omset Akumulasi
        SET total_omset_akumulasi = total_omset_akumulasi + v_total;

        -- Sub-Query: Hitung total keuntungan bersih untuk ID pesanan yang sedang dibaca saat ini
        SELECT IFNULL(SUM(dp.jumlah * (dp.harga - p.harga_beli)), 0) INTO laba_per_pesanan
        FROM detail_pesanan dp
        JOIN produk p ON dp.id_produk = p.id_produk
        WHERE dp.id_pesanan = v_id_pesanan;

        -- Tambahkan ke akumulasi laba bulanan
        SET total_laba_akumulasi = total_laba_akumulasi + laba_per_pesanan;

    END LOOP;

    -- 7. Tutup Cursor
    CLOSE cursor_pesanan;

    -- 8. Masukkan hasil kalkulasi Cursor ke tabel laporan_bulanan
    -- Mencegah duplikasi data untuk bulan yang sama
    DELETE FROM laporan_bulanan WHERE bulan_tahun = p_bulan;
    
    INSERT INTO laporan_bulanan (bulan_tahun, total_omset, total_laba, waktu_generate)
    VALUES (p_bulan, total_omset_akumulasi, total_laba_akumulasi, NOW());

END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `barang_rusak`
--

CREATE TABLE `barang_rusak` (
  `id_rusak` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `tanggal` date DEFAULT NULL,
  `jumlah` int(11) DEFAULT NULL,
  `keterangan` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `barang_rusak`
--
DELIMITER $$
CREATE TRIGGER `kurang_stok_karena_rusak` AFTER INSERT ON `barang_rusak` FOR EACH ROW BEGIN
    UPDATE produk
    SET stok = stok - NEW.jumlah
    WHERE id_produk = NEW.id_produk;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `bouquet_custom`
--

CREATE TABLE `bouquet_custom` (
  `id_bouquet` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `nama_bouquet` varchar(100) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `ongkos_rakit` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bouquet_custom`
--

INSERT INTO `bouquet_custom` (`id_bouquet`, `id_pesanan`, `nama_bouquet`, `catatan`, `ongkos_rakit`) VALUES
(1, 1, 'Custom Bouquet #1', 'Catatan Custom: wrapping tema pink putih  | Isi Kartu Ucapan: hbd | Batas Waktu Kirim: 2026-06-16T10:00', 75000),
(2, 2, 'Custom Bouquet #2', 'Catatan Custom:  | Isi Kartu Ucapan: maaf sayang | Batas Waktu Kirim: 2026-06-15T07:00', 25000),
(4, 4, 'Custom Bouquet #4', 'Catatan Custom:  | Isi Kartu Ucapan: happy gradution!!! | Batas Waktu Kirim: 2026-06-15T09:30', 50000),
(5, 5, 'Custom Bouquet #5', 'Catatan Custom: tema hitam gold | Isi Kartu Ucapan: be happy | Batas Waktu Kirim: 2026-06-15T22:04', 50000),
(6, 6, 'Custom Bouquet #6', 'Catatan Custom: tema hitam merah | Isi Kartu Ucapan: hbd pak rektor | Batas Waktu Kirim: 2026-06-17T22:10', 75000);

-- --------------------------------------------------------

--
-- Table structure for table `detail_bouquet`
--

CREATE TABLE `detail_bouquet` (
  `id_detail_bouquet` int(11) NOT NULL,
  `id_bouquet` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_bouquet`
--

INSERT INTO `detail_bouquet` (`id_detail_bouquet`, `id_bouquet`, `id_produk`, `jumlah`) VALUES
(1, 1, 26, 20),
(2, 1, 20, 20),
(3, 1, 18, 20),
(4, 1, 15, 20),
(5, 1, 31, 30),
(6, 1, 49, 2),
(7, 2, 12, 15),
(8, 2, 32, 5),
(9, 2, 50, 1),
(14, 4, 69, 10),
(15, 4, 58, 10),
(16, 4, 23, 10),
(17, 4, 32, 10),
(18, 5, 65, 20),
(19, 5, 23, 10),
(20, 5, 35, 5),
(21, 6, 12, 100),
(22, 6, 35, 50),
(23, 6, 52, 1);

-- --------------------------------------------------------

--
-- Table structure for table `detail_pembelian`
--

CREATE TABLE `detail_pembelian` (
  `id_detail_beli` int(11) NOT NULL,
  `id_pembelian` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_beli` int(11) NOT NULL,
  `subtotal` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_pembelian`
--

INSERT INTO `detail_pembelian` (`id_detail_beli`, `id_pembelian`, `id_produk`, `jumlah`, `harga_beli`, `subtotal`) VALUES
(1, 1, 39, 100, 80000, 8000000),
(2, 2, 40, 150, 85000, 12750000),
(3, 3, 41, 100, 80000, 8000000),
(4, 4, 42, 100, 90000, 9000000),
(5, 5, 43, 100, 85000, 8500000),
(6, 6, 45, 100, 140000, 14000000),
(7, 7, 46, 100, 190000, 19000000),
(8, 8, 44, 100, 115000, 11500000),
(9, 9, 38, 1000, 1500, 1500000),
(10, 10, 37, 1000, 2000, 2000000),
(11, 11, 36, 1000, 2000, 2000000),
(12, 12, 35, 1000, 2500, 2500000),
(13, 13, 34, 1000, 1000, 1000000),
(14, 14, 33, 1000, 1500, 1500000),
(15, 15, 32, 1000, 1500, 1500000),
(16, 16, 31, 1000, 1500, 1500000),
(17, 17, 52, 1000, 3500, 3500000),
(18, 18, 51, 1000, 750, 750000),
(19, 19, 50, 1000, 1500, 1500000),
(20, 20, 49, 1000, 1750, 1750000),
(21, 21, 48, 100, 3000, 300000),
(22, 22, 47, 100, 2000, 200000),
(23, 23, 70, 1000, 4000, 4000000),
(24, 24, 69, 1000, 10000, 10000000),
(25, 25, 68, 1000, 5000, 5000000),
(26, 26, 67, 1000, 4000, 4000000),
(27, 27, 66, 1000, 8000, 8000000),
(28, 28, 65, 1000, 10000, 10000000),
(29, 29, 64, 1000, 4000, 4000000),
(30, 30, 63, 1000, 8000, 8000000),
(31, 31, 62, 1000, 5000, 5000000),
(32, 32, 61, 1000, 4000, 4000000),
(33, 33, 60, 1000, 6000, 6000000),
(34, 34, 59, 1000, 10000, 10000000),
(35, 35, 58, 1000, 5000, 5000000),
(36, 36, 57, 1000, 4000, 4000000),
(37, 37, 56, 1000, 8000, 8000000),
(38, 38, 55, 1000, 6000, 6000000),
(39, 39, 54, 1000, 6000, 6000000),
(40, 40, 53, 1000, 8000, 8000000),
(41, 41, 30, 1000, 3000, 3000000),
(42, 42, 29, 1000, 5000, 5000000),
(43, 43, 28, 1000, 8000, 8000000),
(44, 44, 27, 1000, 3000, 3000000),
(45, 45, 26, 1000, 4000, 4000000),
(46, 46, 25, 1000, 5000, 5000000),
(47, 47, 24, 1000, 6000, 6000000),
(48, 48, 23, 1000, 2000, 2000000),
(49, 49, 22, 1000, 3000, 3000000),
(50, 50, 21, 1000, 8000, 8000000),
(51, 51, 20, 1000, 4000, 4000000),
(52, 52, 19, 1000, 5000, 5000000),
(53, 53, 18, 1000, 3000, 3000000),
(54, 54, 17, 1000, 6000, 6000000),
(55, 55, 16, 1000, 6000, 6000000),
(56, 56, 15, 1000, 6000, 6000000),
(57, 57, 14, 1000, 3000, 3000000),
(58, 58, 13, 1000, 3000, 3000000),
(59, 59, 12, 1000, 3000, 3000000);

--
-- Triggers `detail_pembelian`
--
DELIMITER $$
CREATE TRIGGER `tambah_stok_dari_supplier` AFTER INSERT ON `detail_pembelian` FOR EACH ROW BEGIN
    UPDATE produk
    SET stok = stok + NEW.jumlah
    WHERE id_produk = NEW.id_produk;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `detail_pesanan`
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
-- Dumping data for table `detail_pesanan`
--

INSERT INTO `detail_pesanan` (`id_detail`, `id_pesanan`, `id_produk`, `jumlah`, `harga`, `subtotal`) VALUES
(1, 1, 26, 20, 6000, 120000),
(2, 1, 20, 20, 6000, 120000),
(3, 1, 18, 20, 5000, 100000),
(4, 1, 15, 20, 8000, 160000),
(5, 1, 31, 30, 3000, 90000),
(6, 1, 49, 2, 2500, 5000),
(7, 2, 12, 15, 5000, 75000),
(8, 2, 32, 5, 3000, 15000),
(9, 2, 50, 1, 3000, 3000),
(14, 4, 69, 10, 12000, 120000),
(15, 4, 58, 10, 7000, 70000),
(16, 4, 23, 10, 4000, 40000),
(17, 4, 32, 10, 3000, 30000),
(18, 5, 65, 20, 12000, 240000),
(19, 5, 23, 10, 4000, 40000),
(20, 5, 35, 5, 5000, 25000),
(21, 6, 12, 100, 5000, 500000),
(22, 6, 35, 50, 5000, 250000),
(23, 6, 52, 1, 7000, 7000);

--
-- Triggers `detail_pesanan`
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
-- Table structure for table `kategori_produk`
--

CREATE TABLE `kategori_produk` (
  `id_kategori` int(11) NOT NULL,
  `nama_kategori` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori_produk`
--

INSERT INTO `kategori_produk` (`id_kategori`, `nama_kategori`) VALUES
(1, 'Bunga'),
(2, 'Wrapping Paper'),
(3, 'Boneka'),
(4, 'Pita'),
(5, 'Aksesoris');

-- --------------------------------------------------------

--
-- Table structure for table `laporan_bulanan`
--

CREATE TABLE `laporan_bulanan` (
  `id_laporan` int(11) NOT NULL,
  `bulan_tahun` varchar(20) NOT NULL,
  `modal_awal` int(11) DEFAULT 0,
  `total_omset` int(11) DEFAULT 0,
  `sisa_modal` int(11) DEFAULT 0,
  `total_laba` int(11) DEFAULT 0,
  `waktu_generate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laporan_bulanan`
--

INSERT INTO `laporan_bulanan` (`id_laporan`, `bulan_tahun`, `modal_awal`, `total_omset`, `sisa_modal`, `total_laba`, `waktu_generate`) VALUES
(1, '2026-06', 315000000, 2235000, 1750000, -311015000, '2026-06-14 01:00:30');

-- --------------------------------------------------------

--
-- Table structure for table `pelanggan`
--

CREATE TABLE `pelanggan` (
  `id_pelanggan` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pelanggan`
--

INSERT INTO `pelanggan` (`id_pelanggan`, `nama`, `email`, `telepon`, `alamat`) VALUES
(1, 'Salwa', 'salwa@gmail.com', '082245612723', 'Malang'),
(2, 'salwa', 'salwa@gmail.com', '082245612723', 'universitas trunojoyo madura, telang, kamal'),
(3, 'fifi', 'afkryy7@gmail.com', '087772014430', 'tanah merah, bangkalan');

-- --------------------------------------------------------

--
-- Table structure for table `pembelian`
--

CREATE TABLE `pembelian` (
  `id_pembelian` int(11) NOT NULL,
  `id_supplier` int(11) NOT NULL,
  `tanggal` datetime DEFAULT current_timestamp(),
  `total_beli` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pembelian`
--

INSERT INTO `pembelian` (`id_pembelian`, `id_supplier`, `tanggal`, `total_beli`) VALUES
(1, 3, '2026-06-13 20:15:26', 8000000),
(2, 3, '2026-06-13 20:15:53', 12750000),
(3, 3, '2026-06-13 20:16:45', 8000000),
(4, 3, '2026-06-13 20:17:10', 9000000),
(5, 3, '2026-06-13 20:17:36', 8500000),
(6, 3, '2026-06-13 20:18:00', 14000000),
(7, 3, '2026-06-13 20:18:31', 19000000),
(8, 3, '2026-06-13 20:18:51', 11500000),
(9, 2, '2026-06-13 20:20:17', 1500000),
(10, 2, '2026-06-13 20:20:36', 2000000),
(11, 2, '2026-06-13 20:20:54', 2000000),
(12, 2, '2026-06-13 20:21:13', 2500000),
(13, 2, '2026-06-13 20:21:53', 1000000),
(14, 2, '2026-06-13 20:22:10', 1500000),
(15, 2, '2026-06-13 20:22:27', 1500000),
(16, 2, '2026-06-13 20:22:42', 1500000),
(17, 6, '2026-06-13 20:23:46', 3500000),
(18, 6, '2026-06-13 20:24:26', 750000),
(19, 6, '2026-06-13 20:25:24', 1500000),
(20, 6, '2026-06-13 20:25:45', 1750000),
(21, 6, '2026-06-13 20:26:51', 300000),
(22, 6, '2026-06-13 20:27:14', 200000),
(23, 5, '2026-06-13 20:31:57', 4000000),
(24, 5, '2026-06-13 20:32:21', 10000000),
(25, 5, '2026-06-13 20:32:58', 5000000),
(26, 5, '2026-06-13 20:33:27', 4000000),
(27, 5, '2026-06-13 20:33:48', 8000000),
(28, 5, '2026-06-13 20:34:08', 10000000),
(29, 5, '2026-06-13 20:34:25', 4000000),
(30, 5, '2026-06-13 20:34:48', 8000000),
(31, 5, '2026-06-13 20:35:12', 5000000),
(32, 5, '2026-06-13 20:35:34', 4000000),
(33, 5, '2026-06-13 20:35:57', 6000000),
(34, 5, '2026-06-13 20:36:12', 10000000),
(35, 5, '2026-06-13 20:36:44', 5000000),
(36, 5, '2026-06-13 20:37:02', 4000000),
(37, 5, '2026-06-13 20:37:34', 8000000),
(38, 5, '2026-06-13 20:38:01', 6000000),
(39, 5, '2026-06-13 20:38:30', 6000000),
(40, 5, '2026-06-13 20:38:44', 8000000),
(41, 4, '2026-06-13 20:39:06', 3000000),
(42, 4, '2026-06-13 20:39:26', 5000000),
(43, 4, '2026-06-13 20:40:19', 8000000),
(44, 4, '2026-06-13 20:40:44', 3000000),
(45, 4, '2026-06-13 20:41:09', 4000000),
(46, 4, '2026-06-13 20:41:27', 5000000),
(47, 4, '2026-06-13 20:41:48', 6000000),
(48, 1, '2026-06-13 20:42:06', 2000000),
(49, 4, '2026-06-13 20:42:32', 3000000),
(50, 4, '2026-06-13 20:42:52', 8000000),
(51, 4, '2026-06-13 20:43:18', 4000000),
(52, 4, '2026-06-13 20:43:36', 5000000),
(53, 1, '2026-06-13 20:43:55', 3000000),
(54, 1, '2026-06-13 20:44:13', 6000000),
(55, 1, '2026-06-13 20:44:32', 6000000),
(56, 1, '2026-06-13 20:44:47', 6000000),
(57, 1, '2026-06-13 20:45:07', 3000000),
(58, 1, '2026-06-13 20:45:39', 3000000),
(59, 1, '2026-06-13 20:46:00', 3000000);

-- --------------------------------------------------------

--
-- Table structure for table `pesanan`
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
-- Dumping data for table `pesanan`
--

INSERT INTO `pesanan` (`id_pesanan`, `id_pelanggan`, `tanggal`, `total`, `status`, `metode_pembayaran`, `bukti`) VALUES
(1, 3, '2026-06-13 21:01:04', 670000, 'Selesai', 'QRIS', 'BUKTI_1781359264_6a2d62a052ed4.jpeg'),
(2, 2, '2026-06-13 21:11:02', 118000, 'Selesai', 'Transfer Bank', 'BUKTI_1781359862_6a2d64f652f1c.jpeg'),
(4, 3, '2026-06-13 21:26:25', 310000, 'Selesai', 'Transfer Bank', 'BUKTI_1781360785_6a2d689137129.jpeg'),
(5, 3, '2026-06-13 22:05:05', 305000, 'Selesai', 'QRIS', 'BUKTI_1781363105_6a2d71a1a6895.jpeg'),
(6, 2, '2026-06-13 22:11:40', 832000, 'Selesai', 'Transfer Bank', 'BUKTI_1781363500_6a2d732ce8064.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `produk`
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
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`id_produk`, `id_kategori`, `id_supplier`, `nama_produk`, `warna`, `ukuran`, `harga_beli`, `harga_jual`, `stok`, `stok_minimum`, `tanggal_masuk`, `tanggal_expired`, `gambar`) VALUES
(12, 1, 1, 'Red Rose', NULL, NULL, 0, 5000, 770, 10, NULL, NULL, 'PRODUK_1781212571.jpg'),
(13, 1, 1, 'White Rose', NULL, NULL, 0, 5000, 1000, 10, NULL, NULL, 'PRODUK_1781212695.jpg'),
(14, 1, 1, 'Pink Rose', NULL, NULL, 0, 5000, 1000, 10, NULL, NULL, 'PRODUK_1781212769.jpg'),
(15, 1, 4, 'White Lily', NULL, NULL, 0, 8000, 960, 10, NULL, NULL, 'PRODUK_1781212945.jpg'),
(16, 1, 4, 'Pink Lily', NULL, NULL, 0, 8000, 1000, 10, NULL, NULL, 'PRODUK_1781212972.jpg'),
(17, 1, 4, 'Yellow Lily', NULL, NULL, 0, 8000, 1000, 10, NULL, NULL, 'PRODUK_1781213007.jpg'),
(18, 1, 1, 'Baby Breath', NULL, NULL, 0, 5000, 960, 10, NULL, NULL, 'PRODUK_1781213074.jpg'),
(19, 1, 4, 'Pink Dahlia', NULL, NULL, 0, 7000, 1000, 10, NULL, NULL, 'PRODUK_1781213118.jpg'),
(20, 1, 5, 'Pink Daisy', NULL, NULL, 0, 6000, 960, 10, NULL, NULL, 'PRODUK_1781213223.jpg'),
(21, 1, 5, 'Pink Tulip', NULL, NULL, 0, 10000, 1000, 10, NULL, NULL, 'PRODUK_1781213278.jpg'),
(22, 1, 1, 'Purple Pompom', NULL, NULL, 0, 5000, 1000, 10, NULL, NULL, 'PRODUK_1781213316.jpg'),
(23, 1, 1, 'Sedap Malam', NULL, NULL, 0, 4000, 950, 10, NULL, NULL, 'PRODUK_1781213348.jpg'),
(24, 1, 4, 'Sunflower', NULL, NULL, 0, 8000, 1000, 10, NULL, NULL, 'PRODUK_1781213378.jpg'),
(25, 1, 4, 'White Dahlia', NULL, NULL, 0, 7000, 1000, 10, NULL, NULL, 'PRODUK_1781213525.jpg'),
(26, 1, 5, 'White Daisy', NULL, NULL, 0, 6000, 960, 10, NULL, NULL, 'PRODUK_1781213572.jpg'),
(27, 1, 1, 'White Pompom', NULL, NULL, 0, 5000, 1000, 10, NULL, NULL, 'PRODUK_1781213608.jpg'),
(28, 1, 5, 'White Tulip', NULL, NULL, 0, 10000, 1000, 10, NULL, NULL, 'PRODUK_1781213678.jpg'),
(29, 1, 4, 'Yellow Dahlia', NULL, NULL, 0, 7000, 1000, 10, NULL, NULL, 'PRODUK_1781213719.jpg'),
(30, 1, 1, 'Yellow Pompom', NULL, NULL, 0, 5000, 1000, 10, NULL, NULL, 'PRODUK_1781213755.jpg'),
(31, 2, 2, 'Craft Paper', NULL, NULL, 0, 3000, 940, 10, NULL, NULL, 'PRODUK_1781214984.jpg'),
(32, 2, 2, 'Mica Transparan', NULL, NULL, 0, 3000, 960, 10, NULL, NULL, 'PRODUK_1781215013.webp'),
(33, 2, 2, 'Polynet Paper', NULL, NULL, 0, 3000, 1000, 10, NULL, NULL, 'PRODUK_1781215042.jpg'),
(34, 2, 2, 'Tissue Paper', NULL, NULL, 0, 2000, 1000, 10, NULL, NULL, 'PRODUK_1781215088.jpg'),
(35, 2, 2, 'Wrapping Paper Korean', NULL, NULL, 0, 5000, 890, 10, NULL, NULL, 'PRODUK_1781215167.jpg'),
(36, 2, 2, 'Wrapping Paper Motif Kotak', NULL, NULL, 0, 4000, 1000, 10, NULL, NULL, 'PRODUK_1781215222.jpg'),
(37, 2, 2, 'Wrapping Paper Motif Random', NULL, NULL, 0, 4000, 1000, 10, NULL, NULL, 'PRODUK_1781215289.jpg'),
(38, 2, 2, 'Wrapping Paper Polos', NULL, NULL, 0, 3000, 1000, 10, NULL, NULL, 'PRODUK_1781215320.jpg'),
(39, 3, 3, 'Bunny Doll', NULL, NULL, 0, 90000, 100, 10, NULL, NULL, 'PRODUK_1781215701.jpg'),
(40, 3, 3, 'Capybara Doll', NULL, NULL, 0, 95000, 150, 10, NULL, NULL, 'PRODUK_1781215731.jpg'),
(41, 3, 3, 'Duck Doll', NULL, NULL, 0, 90000, 100, 10, NULL, NULL, 'PRODUK_1781215763.jpg'),
(42, 3, 3, 'Nailong Doll', NULL, NULL, 0, 100000, 100, 10, NULL, NULL, 'PRODUK_1781215797.jpg'),
(43, 3, 3, 'Piggy Doll', NULL, NULL, 0, 95000, 100, 10, NULL, NULL, 'PRODUK_1781215829.jpg'),
(44, 3, 3, 'Stitch', NULL, NULL, 0, 125000, 100, 10, NULL, NULL, 'PRODUK_1781215864.webp'),
(45, 3, 3, 'Teddy Bear Doll', NULL, NULL, 0, 150000, 100, 10, NULL, NULL, 'PRODUK_1781215892.jpg'),
(46, 3, 3, 'Teddy Grad', NULL, NULL, 0, 200000, 100, 10, NULL, NULL, 'PRODUK_1781215925.jpg'),
(47, 5, 6, 'Fake Butterfly', NULL, NULL, 0, 3000, 100, 10, NULL, NULL, 'PRODUK_1781216833.jpg'),
(48, 5, 6, 'Mini Crown', NULL, NULL, 0, 5000, 100, 10, NULL, NULL, 'PRODUK_1781216883.jpg'),
(49, 4, 6, 'Korean Reborn', NULL, NULL, 0, 2500, 996, 10, NULL, NULL, 'PRODUK_1781216931.jpg'),
(50, 4, 6, 'Large Satin Reborn', NULL, NULL, 0, 3000, 998, 10, NULL, NULL, 'PRODUK_1781216977.jpg'),
(51, 4, 6, 'Small Satin Reborn', NULL, NULL, 0, 1500, 1000, 10, NULL, NULL, 'PRODUK_1781217012.jpg'),
(52, 4, 6, 'Custom Message Reborn', NULL, NULL, 0, 7000, 998, 10, NULL, NULL, 'PRODUK_1781217073.jpg'),
(53, 1, 4, 'Blue Anemone', NULL, NULL, 0, 10000, 1000, 10, NULL, NULL, 'PRODUK_1781218159.jpg'),
(54, 1, 5, 'Yellow Ranunculus', NULL, NULL, 0, 8000, 1000, 10, NULL, NULL, 'PRODUK_1781218194.jpg'),
(55, 1, 5, 'Orange Ranunculus', NULL, NULL, 0, 8000, 1000, 10, NULL, NULL, 'PRODUK_1781218223.webp'),
(56, 1, 4, 'Pink Anemone', NULL, NULL, 0, 10000, 1000, 10, NULL, NULL, 'PRODUK_1781218251.jpg'),
(57, 1, 1, 'Pink Carnation', NULL, NULL, 0, 6000, 1000, 10, NULL, NULL, 'PRODUK_1781218278.jpg'),
(58, 1, 5, 'Pink Lisianthus', NULL, NULL, 0, 7000, 970, 10, NULL, NULL, 'PRODUK_1781218319.jpg'),
(59, 1, 4, 'Pink Peony', NULL, NULL, 0, 12000, 1000, 10, NULL, NULL, 'PRODUK_1781218348.webp'),
(60, 1, 5, 'Pink Ranunculus', NULL, NULL, 0, 8000, 1000, 10, NULL, NULL, 'PRODUK_1781218379.webp'),
(61, 1, 1, 'Purple Carnation', NULL, NULL, 0, 6000, 1000, 10, NULL, NULL, 'PRODUK_1781218423.jpg'),
(62, 1, 5, 'Purple Lisianthus', NULL, NULL, 0, 7000, 1000, 10, NULL, NULL, 'PRODUK_1781218464.jpg'),
(63, 1, 4, 'Red Anemone', NULL, NULL, 0, 10000, 1000, 10, NULL, NULL, 'PRODUK_1781218495.webp'),
(64, 1, 1, 'Red Carnation', NULL, NULL, 0, 6000, 1000, 10, NULL, NULL, 'PRODUK_1781218532.jpg'),
(65, 1, 4, 'Red Peony', NULL, NULL, 0, 12000, 960, 10, NULL, NULL, 'PRODUK_1781218558.webp'),
(66, 1, 4, 'White Anemone', NULL, NULL, 0, 10000, 1000, 10, NULL, NULL, 'PRODUK_1781218591.webp'),
(67, 1, 1, 'White Carnation', NULL, NULL, 0, 6000, 1000, 10, NULL, NULL, 'PRODUK_1781218618.jpg'),
(68, 1, 5, 'White Lisianthus', NULL, NULL, 0, 7000, 1000, 10, NULL, NULL, 'PRODUK_1781218676.jpg'),
(69, 1, 4, 'White Peony', NULL, NULL, 0, 12000, 970, 10, NULL, NULL, 'PRODUK_1781218702.jpg'),
(70, 1, 1, 'Yellow Carnation', NULL, NULL, 0, 6000, 1000, 10, NULL, NULL, 'PRODUK_1781218736.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `retur_supplier`
--

CREATE TABLE `retur_supplier` (
  `id_retur` int(11) NOT NULL,
  `id_supplier` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `jumlah` int(11) DEFAULT NULL,
  `total_dana_kembali` int(11) DEFAULT 0,
  `alasan` varchar(255) DEFAULT NULL,
  `tanggal` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `id_supplier` int(11) NOT NULL,
  `nama_supplier` varchar(100) NOT NULL,
  `alamat` text DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`id_supplier`, `nama_supplier`, `alamat`, `telepon`, `email`) VALUES
(1, 'CV Bunga Nusantara', 'Surabaya', '081111111111', 'CVBungaNusantara@gmail.com'),
(2, 'PT Wrapping Cantik', 'Bangkalan', '082222222222', 'PTWrappingCantik@gmail.com'),
(3, 'Boneka Indonesia', 'Malang', '083333333333', 'BonekaIndonesia@gmail.com'),
(4, 'PT Bunga Mekar Berseri', 'Surabaya', '0899999991', 'BungaMekarSari@gmail.com'),
(5, 'PT Harum Abadi', 'Malang', '0811122334455', 'HarumAbadi@gmail.com'),
(6, 'Gift Decoration Center', 'Bangkalan', '0877664411891', 'GiftDecorationCenter@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `users`
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
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `telp`, `password`, `role`, `foto`) VALUES
(1, 'admin', 'admin@gmail.com', '081234567890', 'admin123', 'admin', 'default_admin.jpg'),
(2, 'salwa', 'salwa@gmail.com', NULL, '$2y$10$VX7KQkkG3rxk5K2M0fQQr.abAs9c2dwWiqmfSfE3Pi2j2kaCgJQHu', 'user', '1781294008_contoh_pp.jpg'),
(3, 'fifi', 'afkryy7@gmail.com', NULL, '$2y$10$JzGOz.mzqZ9pV61Mq5cDi.YKqzvH0vi2OmEDEt6DVgbBxStN5MC2G', 'user', '1781334978_rose_pink.jfif');

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_laba`
-- (See below for the actual view)
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
-- Table structure for table `voucher_pelanggan`
--

CREATE TABLE `voucher_pelanggan` (
  `id_voucher` int(11) NOT NULL,
  `id_pelanggan` int(11) NOT NULL,
  `kode_voucher` varchar(20) NOT NULL,
  `potongan_harga` int(11) NOT NULL,
  `status_aktif` varchar(10) DEFAULT 'Aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `voucher_pelanggan`
--

INSERT INTO `voucher_pelanggan` (`id_voucher`, `id_pelanggan`, `kode_voucher`, `potongan_harga`, `status_aktif`) VALUES
(2, 3, 'SLVR-3-2606', 50000, 'Tidak Akti'),
(3, 2, 'GOLD-A6B302', 50000, 'Aktif');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_riwayat_pesanan`
-- (See below for the actual view)
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
-- Structure for view `view_laba`
--
DROP TABLE IF EXISTS `view_laba`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_laba`  AS SELECT `p`.`id_produk` AS `id_produk`, `p`.`nama_produk` AS `nama_produk`, sum(`dp`.`jumlah`) AS `total_terjual`, `p`.`harga_beli` AS `harga_beli`, `p`.`harga_jual` AS `harga_jual`, sum(`dp`.`jumlah`) * (`p`.`harga_jual` - `p`.`harga_beli`) AS `laba` FROM (`detail_pesanan` `dp` join `produk` `p` on(`dp`.`id_produk` = `p`.`id_produk`)) GROUP BY `p`.`id_produk` ;

-- --------------------------------------------------------

--
-- Structure for view `v_riwayat_pesanan`
--
DROP TABLE IF EXISTS `v_riwayat_pesanan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_riwayat_pesanan`  AS SELECT `p`.`id_pesanan` AS `id_pesanan`, `p`.`id_pelanggan` AS `id_pelanggan`, `p`.`tanggal` AS `tanggal`, `p`.`total` AS `total`, `p`.`status` AS `status`, `p`.`metode_pembayaran` AS `metode_pembayaran`, `pr`.`nama_produk` AS `nama_produk`, `dp`.`jumlah` AS `jumlah` FROM ((`pesanan` `p` join `detail_pesanan` `dp` on(`p`.`id_pesanan` = `dp`.`id_pesanan`)) join `produk` `pr` on(`dp`.`id_produk` = `pr`.`id_produk`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang_rusak`
--
ALTER TABLE `barang_rusak`
  ADD PRIMARY KEY (`id_rusak`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indexes for table `bouquet_custom`
--
ALTER TABLE `bouquet_custom`
  ADD PRIMARY KEY (`id_bouquet`),
  ADD KEY `id_pesanan` (`id_pesanan`);

--
-- Indexes for table `detail_bouquet`
--
ALTER TABLE `detail_bouquet`
  ADD PRIMARY KEY (`id_detail_bouquet`),
  ADD KEY `id_bouquet` (`id_bouquet`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indexes for table `detail_pembelian`
--
ALTER TABLE `detail_pembelian`
  ADD PRIMARY KEY (`id_detail_beli`),
  ADD KEY `id_pembelian` (`id_pembelian`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indexes for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_pesanan` (`id_pesanan`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indexes for table `kategori_produk`
--
ALTER TABLE `kategori_produk`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indexes for table `laporan_bulanan`
--
ALTER TABLE `laporan_bulanan`
  ADD PRIMARY KEY (`id_laporan`);

--
-- Indexes for table `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`id_pelanggan`);

--
-- Indexes for table `pembelian`
--
ALTER TABLE `pembelian`
  ADD PRIMARY KEY (`id_pembelian`),
  ADD KEY `id_supplier` (`id_supplier`);

--
-- Indexes for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id_pesanan`),
  ADD KEY `id_pelanggan` (`id_pelanggan`);

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id_produk`),
  ADD KEY `id_kategori` (`id_kategori`),
  ADD KEY `id_supplier` (`id_supplier`);

--
-- Indexes for table `retur_supplier`
--
ALTER TABLE `retur_supplier`
  ADD PRIMARY KEY (`id_retur`),
  ADD KEY `id_supplier` (`id_supplier`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`id_supplier`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `voucher_pelanggan`
--
ALTER TABLE `voucher_pelanggan`
  ADD PRIMARY KEY (`id_voucher`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barang_rusak`
--
ALTER TABLE `barang_rusak`
  MODIFY `id_rusak` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bouquet_custom`
--
ALTER TABLE `bouquet_custom`
  MODIFY `id_bouquet` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `detail_bouquet`
--
ALTER TABLE `detail_bouquet`
  MODIFY `id_detail_bouquet` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `detail_pembelian`
--
ALTER TABLE `detail_pembelian`
  MODIFY `id_detail_beli` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `kategori_produk`
--
ALTER TABLE `kategori_produk`
  MODIFY `id_kategori` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `laporan_bulanan`
--
ALTER TABLE `laporan_bulanan`
  MODIFY `id_laporan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pelanggan`
--
ALTER TABLE `pelanggan`
  MODIFY `id_pelanggan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pembelian`
--
ALTER TABLE `pembelian`
  MODIFY `id_pembelian` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id_pesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `produk`
--
ALTER TABLE `produk`
  MODIFY `id_produk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `retur_supplier`
--
ALTER TABLE `retur_supplier`
  MODIFY `id_retur` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier`
--
ALTER TABLE `supplier`
  MODIFY `id_supplier` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `voucher_pelanggan`
--
ALTER TABLE `voucher_pelanggan`
  MODIFY `id_voucher` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barang_rusak`
--
ALTER TABLE `barang_rusak`
  ADD CONSTRAINT `barang_rusak_ibfk_1` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`);

--
-- Constraints for table `bouquet_custom`
--
ALTER TABLE `bouquet_custom`
  ADD CONSTRAINT `bouquet_custom_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`);

--
-- Constraints for table `detail_bouquet`
--
ALTER TABLE `detail_bouquet`
  ADD CONSTRAINT `detail_bouquet_ibfk_1` FOREIGN KEY (`id_bouquet`) REFERENCES `bouquet_custom` (`id_bouquet`),
  ADD CONSTRAINT `detail_bouquet_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`);

--
-- Constraints for table `detail_pembelian`
--
ALTER TABLE `detail_pembelian`
  ADD CONSTRAINT `detail_pembelian_ibfk_1` FOREIGN KEY (`id_pembelian`) REFERENCES `pembelian` (`id_pembelian`),
  ADD CONSTRAINT `detail_pembelian_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`);

--
-- Constraints for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `detail_pesanan_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`),
  ADD CONSTRAINT `detail_pesanan_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`);

--
-- Constraints for table `pembelian`
--
ALTER TABLE `pembelian`
  ADD CONSTRAINT `pembelian_ibfk_1` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`);

--
-- Constraints for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `pesanan_ibfk_1` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`);

--
-- Constraints for table `produk`
--
ALTER TABLE `produk`
  ADD CONSTRAINT `produk_ibfk_1` FOREIGN KEY (`id_kategori`) REFERENCES `kategori_produk` (`id_kategori`),
  ADD CONSTRAINT `produk_ibfk_2` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`);

--
-- Constraints for table `retur_supplier`
--
ALTER TABLE `retur_supplier`
  ADD CONSTRAINT `retur_supplier_ibfk_1` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`),
  ADD CONSTRAINT `retur_supplier_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
