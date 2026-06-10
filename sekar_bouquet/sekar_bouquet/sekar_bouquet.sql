-- =========================================
-- DATABASE : sekar_bouquet
-- SQL Server 2022 (SSMS)
-- =========================================

CREATE DATABASE sekar_bouquet;
GO

USE sekar_bouquet;
GO


-- =========================================
-- TABLE : users
-- =========================================
CREATE TABLE users (
    id INT IDENTITY(1,1) PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telp VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    otp_code VARCHAR(6),
    otp_expiry DATETIME,
    role VARCHAR(10) CHECK (role IN ('admin', 'user')),
    foto VARCHAR(255)
);
GO


-- =========================================
-- TABLE : produk
-- =========================================
CREATE TABLE produk (
    id INT IDENTITY(1,1) PRIMARY KEY,
    nama VARCHAR(100),
    harga INT,
    gambar VARCHAR(200),
    stok INT DEFAULT 0
);
GO


-- =========================================
-- TABLE : pesanan_header
-- =========================================
CREATE TABLE pesanan_header (
    id INT IDENTITY(1,1) PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    pembayaran VARCHAR(50) NOT NULL,
    tanggal DATETIME DEFAULT GETDATE(),
    total INT NOT NULL,
    status VARCHAR(50) DEFAULT 'Menunggu Pembayaran',
    bukti VARCHAR(255)
);
GO


-- =========================================
-- TABLE : pesanan_detail
-- =========================================
CREATE TABLE pesanan_detail (
    id INT IDENTITY(1,1) PRIMARY KEY,
    id_pesanan INT NOT NULL,
    produk_id INT NOT NULL,
    jumlah INT NOT NULL,
    harga INT NOT NULL,
    subtotal INT NOT NULL,

    CONSTRAINT FK_PesananHeader
        FOREIGN KEY (id_pesanan)
        REFERENCES pesanan_header(id),

    CONSTRAINT FK_Produk
        FOREIGN KEY (produk_id)
        REFERENCES produk(id)
);
GO


-- =========================================
-- DATA PRODUK
-- =========================================
INSERT INTO produk (nama, harga, gambar, stok)
VALUES
('Bouquet Mawar Pink', 75000, 'mawarpink.jpg', 50),
('Bouquet Lily White', 85000, 'lilywhite.jpg', 50),
('Bouquet Wisuda Premium', 120000, 'wisuda.jpg', 50),
('Bouquet Tulip Pastel', 95000, 'tulip.jpg', 50),
('Bouquet Sunflower', 80000, 'sunflower.jpg', 50),
('Bouquet Snack Special', 90000, 'snack.jpg', 50);
GO


-- =========================================
-- DATA USERS
-- =========================================
INSERT INTO users
(username, email, telp, password, role, foto)
VALUES
('admin', 'admin@sekarbouquet.com', NULL, 'admin123', 'admin', NULL),
('amelia', 'amelia@gmail.com', '08123456789', 'amelia123', 'user', 'profil.jpg');
GO


-- =========================================
-- DATA PESANAN HEADER
-- =========================================
INSERT INTO pesanan_header
(nama, email, pembayaran, total, status, bukti)
VALUES
('Amelia', 'amelia@gmail.com', 'QRIS', 75000, 'Terverifikasi', 'bukti1.png');
GO


-- =========================================
-- DATA PESANAN DETAIL
-- =========================================
INSERT INTO pesanan_detail
(id_pesanan, produk_id, jumlah, harga, subtotal)
VALUES
(1, 1, 1, 75000, 75000);
GO


-- =========================================
-- VIEW : view_produk
-- Menampilkan daftar produk bouquet
-- =========================================
CREATE VIEW view_produk AS
SELECT
    id,
    nama,
    harga,
    stok,
    gambar
FROM produk;
GO


-- =========================================
-- VIEW : view_pesanan
-- Menampilkan data pesanan lengkap
-- =========================================
CREATE VIEW view_pesanan AS
SELECT
    ph.id AS id_pesanan,
    ph.nama,
    ph.email,
    ph.pembayaran,
    ph.total,
    ph.status,
    pd.produk_id,
    p.nama AS nama_produk,
    pd.jumlah,
    pd.subtotal
FROM pesanan_header ph
JOIN pesanan_detail pd
    ON ph.id = pd.id_pesanan
JOIN produk p
    ON pd.produk_id = p.id;
GO


-- =========================================
-- STORED PROCEDURE : tambah_produk
-- Menambahkan produk baru
-- =========================================
CREATE PROCEDURE tambah_produk
    @nama VARCHAR(100),
    @harga INT,
    @gambar VARCHAR(200),
    @stok INT
AS
BEGIN

    INSERT INTO produk (nama, harga, gambar, stok)
    VALUES (@nama, @harga, @gambar, @stok);

END
GO


-- =========================================
-- STORED PROCEDURE : tampil_produk
-- Menampilkan seluruh produk
-- =========================================
CREATE PROCEDURE tampil_produk
AS
BEGIN

    SELECT * FROM produk;

END
GO


-- =========================================
-- STORED PROCEDURE : update_stok
-- Mengubah stok produk
-- =========================================
CREATE PROCEDURE update_stok
    @id INT,
    @stok INT
AS
BEGIN

    UPDATE produk
    SET stok = @stok
    WHERE id = @id;

END
GO


-- =========================================
-- TRIGGER : trigger_kurangi_stok
-- Mengurangi stok otomatis saat ada pesanan
-- =========================================
CREATE TRIGGER trigger_kurangi_stok
ON pesanan_detail
AFTER INSERT
AS
BEGIN

    UPDATE produk
    SET stok = stok - inserted.jumlah
    FROM produk
    JOIN inserted
        ON produk.id = inserted.produk_id;

END
GO


-- =========================================
-- TRIGGER : trigger_tambah_stok
-- Menambah stok otomatis saat pesanan dihapus
-- =========================================
CREATE TRIGGER trigger_tambah_stok
ON pesanan_detail
AFTER DELETE
AS
BEGIN

    UPDATE produk
    SET stok = stok + deleted.jumlah
    FROM produk
    JOIN deleted
        ON produk.id = deleted.produk_id;

END
GO


-- =========================================
-- CURSOR : cursor_produk
-- Menampilkan nama produk satu per satu
-- =========================================
DECLARE @nama_produk VARCHAR(100);

DECLARE cursor_produk CURSOR FOR
SELECT nama FROM produk;

OPEN cursor_produk;

FETCH NEXT FROM cursor_produk
INTO @nama_produk;

WHILE @@FETCH_STATUS = 0
BEGIN

    PRINT 'Nama Bouquet : ' + @nama_produk;

    FETCH NEXT FROM cursor_produk
    INTO @nama_produk;

END

CLOSE cursor_produk;
DEALLOCATE cursor_produk;


-- =========================================
-- TEST VIEW
-- =========================================
SELECT * FROM view_produk;
GO

SELECT * FROM view_pesanan;
GO


-- =========================================
-- TEST STORED PROCEDURE
-- =========================================
EXEC tambah_produk
    'Bouquet Baby Breath',
    100000,
    'babybreath.jpg',
    30;
GO

EXEC tampil_produk;
GO


-- =========================================
-- TEST TRIGGER
-- =========================================
INSERT INTO pesanan_detail
(id_pesanan, produk_id, jumlah, harga, subtotal)
VALUES
(1, 2, 2, 85000, 170000);
GO

SELECT * FROM produk;
GO

SELECT * FROM users;