-- =========================================
-- DATABASE : sekar_bouquet
-- MySQL
-- =========================================

CREATE DATABASE IF NOT EXISTS sekar_bouquet;
USE sekar_bouquet;

-- =========================================
-- USERS
-- =========================================
CREATE TABLE users (
id INT AUTO_INCREMENT PRIMARY KEY,
username VARCHAR(50) NOT NULL,
email VARCHAR(100) NOT NULL,
telp VARCHAR(20),
password VARCHAR(255) NOT NULL,
role ENUM('admin','user') DEFAULT 'user',
foto VARCHAR(255)
);

-- =========================================
-- SUPPLIER
-- =========================================
CREATE TABLE supplier (
id_supplier INT AUTO_INCREMENT PRIMARY KEY,
nama_supplier VARCHAR(100) NOT NULL,
alamat TEXT,
telepon VARCHAR(20),
email VARCHAR(100)
);

-- =========================================
-- KATEGORI PRODUK
-- =========================================
CREATE TABLE kategori_produk (
id_kategori INT AUTO_INCREMENT PRIMARY KEY,
nama_kategori VARCHAR(50) NOT NULL
);

-- =========================================
-- PRODUK
-- =========================================
CREATE TABLE produk (
id_produk INT AUTO_INCREMENT PRIMARY KEY,
id_kategori INT NOT NULL,
id_supplier INT NOT NULL,

nama_produk VARCHAR(100) NOT NULL,
warna VARCHAR(50),
ukuran VARCHAR(30),

harga_beli INT NOT NULL,
harga_jual INT NOT NULL,

stok INT DEFAULT 0,
stok_minimum INT DEFAULT 10,

tanggal_masuk DATE,
tanggal_expired DATE,

gambar VARCHAR(255),

FOREIGN KEY (id_kategori)
    REFERENCES kategori_produk(id_kategori),

FOREIGN KEY (id_supplier)
    REFERENCES supplier(id_supplier)
);

-- =========================================
-- PELANGGAN
-- =========================================
CREATE TABLE pelanggan (
id_pelanggan INT AUTO_INCREMENT PRIMARY KEY,
nama VARCHAR(100),
email VARCHAR(100),
telepon VARCHAR(20),
alamat TEXT
);

-- =========================================
-- PESANAN
-- =========================================
CREATE TABLE pesanan (
id_pesanan INT AUTO_INCREMENT PRIMARY KEY,
id_pelanggan INT NOT NULL,

tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,

total INT DEFAULT 0,

status ENUM(
    'Menunggu Pembayaran',
    'Diproses',
    'Selesai',
    'Dibatalkan'
) DEFAULT 'Menunggu Pembayaran',

metode_pembayaran VARCHAR(50),
bukti VARCHAR(255),

FOREIGN KEY (id_pelanggan)
    REFERENCES pelanggan(id_pelanggan)
);

-- =========================================
-- DETAIL PESANAN
-- =========================================
CREATE TABLE detail_pesanan (
id_detail INT AUTO_INCREMENT PRIMARY KEY,
id_pesanan INT NOT NULL,
id_produk INT NOT NULL,

jumlah INT NOT NULL,
harga INT NOT NULL,
subtotal INT NOT NULL,

FOREIGN KEY (id_pesanan)
    REFERENCES pesanan(id_pesanan),

FOREIGN KEY (id_produk)
    REFERENCES produk(id_produk)
);

-- =========================================
-- BOUQUET CUSTOM
-- =========================================
CREATE TABLE bouquet_custom (
id_bouquet INT AUTO_INCREMENT PRIMARY KEY,
id_pesanan INT NOT NULL,


nama_bouquet VARCHAR(100),
catatan TEXT,
ongkos_rakit INT DEFAULT 0,

FOREIGN KEY (id_pesanan)
    REFERENCES pesanan(id_pesanan)


);

-- =========================================
-- DETAIL BOUQUET
-- =========================================
CREATE TABLE detail_bouquet (
id_detail_bouquet INT AUTO_INCREMENT PRIMARY KEY,
id_bouquet INT NOT NULL,
id_produk INT NOT NULL,

jumlah INT NOT NULL,

FOREIGN KEY (id_bouquet)
    REFERENCES bouquet_custom(id_bouquet),

FOREIGN KEY (id_produk)
    REFERENCES produk(id_produk)

);

-- =========================================
-- PEMBELIAN
-- =========================================
CREATE TABLE pembelian (
id_pembelian INT AUTO_INCREMENT PRIMARY KEY,
id_supplier INT NOT NULL,

tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,

total_beli INT DEFAULT 0,

FOREIGN KEY (id_supplier)
    REFERENCES supplier(id_supplier)

);

-- =========================================
-- DETAIL PEMBELIAN
-- =========================================
CREATE TABLE detail_pembelian (
id_detail_beli INT AUTO_INCREMENT PRIMARY KEY,
id_pembelian INT NOT NULL,
id_produk INT NOT NULL,

jumlah INT NOT NULL,
harga_beli INT NOT NULL,
subtotal INT NOT NULL,

FOREIGN KEY (id_pembelian)
    REFERENCES pembelian(id_pembelian),

FOREIGN KEY (id_produk)
    REFERENCES produk(id_produk)

);

-- =========================================
-- BARANG RUSAK / LAYU
-- =========================================
CREATE TABLE barang_rusak (
id_rusak INT AUTO_INCREMENT PRIMARY KEY,
id_produk INT NOT NULL,

tanggal DATE,
jumlah INT,
keterangan VARCHAR(100),

FOREIGN KEY (id_produk)
    REFERENCES produk(id_produk)

);

-- =========================================
-- RETUR SUPPLIER
-- =========================================
CREATE TABLE retur_supplier (
id_retur INT AUTO_INCREMENT PRIMARY KEY,

id_supplier INT NOT NULL,
id_produk INT NOT NULL,

jumlah INT,
alasan VARCHAR(255),
tanggal DATE,

FOREIGN KEY (id_supplier)
    REFERENCES supplier(id_supplier),

FOREIGN KEY (id_produk)
    REFERENCES produk(id_produk)

);

-- =========================================
-- DATA KATEGORI
-- =========================================
INSERT INTO kategori_produk(nama_kategori)
VALUES
('Bunga'),
('Wrapping Paper'),
('Boneka'),
('Pita'),
('Aksesoris');

-- =========================================
-- DATA SUPPLIER
-- =========================================
INSERT INTO supplier
(nama_supplier,alamat,telepon,email)
VALUES
('CV Bunga Nusantara','Surabaya','081111111111','[bunga@gmail.com](mailto:bunga@gmail.com)'),
('PT Wrapping Cantik','Sidoarjo','082222222222','[wrap@gmail.com](mailto:wrap@gmail.com)'),
('Boneka Indonesia','Malang','083333333333','[boneka@gmail.com](mailto:boneka@gmail.com)');

-- =========================================
-- DATA PRODUK
-- =========================================
INSERT INTO produk
(id_kategori,id_supplier,nama_produk,warna,ukuran,harga_beli,harga_jual,stok)
VALUES

(1,1,'Mawar','Merah','Tangkai',4000,7000,500),
(1,1,'Mawar','Putih','Tangkai',4000,7000,400),
(1,1,'Lily','Kuning','Tangkai',6000,10000,300),

(2,2,'Wrapping Korean','Pink','Lembar',2000,5000,200),
(2,2,'Wrapping Korean','Hitam','Lembar',2000,5000,200),

(3,3,'Boneka Teddy','Coklat S','S',15000,25000,50),
(3,3,'Boneka Teddy','Coklat M','M',25000,40000,40),
(3,3,'Boneka Teddy','Coklat L','L',35000,55000,30);

-- =========================================
-- VIEW LABA
-- =========================================
CREATE VIEW view_laba AS
SELECT
p.id_produk,
p.nama_produk,
SUM(dp.jumlah) AS total_terjual,
p.harga_beli,
p.harga_jual,
SUM(dp.jumlah)*(p.harga_jual-p.harga_beli) AS laba
FROM detail_pesanan dp
JOIN produk p
ON dp.id_produk = p.id_produk
GROUP BY p.id_produk;


-- baru 13/06/2026--
-- VIEW (RIWAYAT PESANAN + NAMA BARANG)
CREATE VIEW v_riwayat_pesanan AS
SELECT 
    p.id_pesanan,
    p.id_pelanggan,
    p.tanggal,
    p.total,
    p.status,
    p.metode_pembayaran,
    pr.nama_produk,
    dp.jumlah
FROM pesanan p
JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
JOIN produk pr ON dp.id_produk = pr.id_produk;

-- TRIGGER (OTOMATIS KURANG STOK)
DELIMITER //

CREATE TRIGGER kurang_stok
AFTER INSERT ON detail_pesanan
FOR EACH ROW
BEGIN
    UPDATE produk
    SET stok = stok - NEW.jumlah
    WHERE id_produk = NEW.id_produk;
END//

DELIMITER ;

-- TRIGGER TAMBAH STOK JIKA PESANAN DIBATALKAN
DELIMITER //

CREATE TRIGGER tambah_stok
AFTER DELETE ON detail_pesanan
FOR EACH ROW
BEGIN
    UPDATE produk
    SET stok = stok + OLD.jumlah
    WHERE id_produk = OLD.id_produk;
END//

DELIMITER ;


