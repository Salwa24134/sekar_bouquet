<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';

/* ==================================
   PROTECTION: Only Admin
===================================== */
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

/* ==================================
   LOGIC: DATE FILTER
===================================== */
$tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-01'); 
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : date('Y-m-d'); 
$bulan_aktif = date('Y-m', strtotime($tgl_mulai));

/* ==================================
   1. GET INITIAL CAPITAL FROM DATABASE
===================================== */
$sql_cek_modal = "SELECT modal_awal FROM laporan_bulanan WHERE bulan_tahun = ?";
$stmt_modal = $koneksi->prepare($sql_cek_modal);
$stmt_modal->bind_param("s", $bulan_aktif);
$stmt_modal->execute();
$res_modal = $stmt_modal->get_result()->fetch_assoc();
$modal_awal = $res_modal['modal_awal'] ?? 0; 
$stmt_modal->close();

/* ==================================
   LOGIC: SAVE / UPDATE MODAL
===================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_modal'])) {
    $modal_input = (int)$_POST['modal_awal'];
    
    $sql_cek_ada = "SELECT id_laporan FROM laporan_bulanan WHERE bulan_tahun = ?";
    $stmt_cek = $koneksi->prepare($sql_cek_ada);
    $stmt_cek->bind_param("s", $bulan_aktif);
    $stmt_cek->execute();
    $ada = $stmt_cek->get_result()->fetch_assoc();
    $stmt_cek->close();

    if ($ada) {
        $sql_action = "UPDATE laporan_bulanan SET modal_awal = ? WHERE bulan_tahun = ?";
        $stmt_act = $koneksi->prepare($sql_action);
        $stmt_act->bind_param("is", $modal_input, $bulan_aktif);
    } else {
        $sql_action = "INSERT INTO laporan_bulanan (bulan_tahun, modal_awal, waktu_generate) VALUES (?, ?, NOW())";
        $stmt_act = $koneksi->prepare($sql_action);
        $stmt_act->bind_param("si", $bulan_aktif, $modal_input);
    }
    $stmt_act->execute();
    $stmt_act->close();
    
    header("Location: laporan_admin.php?tgl_mulai=$tgl_mulai&tgl_selesai=$tgl_selesai&status=success");
    exit();
}

/* ==================================
   2. TOTAL REVENUE (OMSET)
===================================== */
$sql_pendapatan = "SELECT SUM(total) as total_omset FROM pesanan WHERE status = 'Selesai' AND DATE(tanggal) BETWEEN ? AND ?";
$stmt1 = $koneksi->prepare($sql_pendapatan);
$stmt1->bind_param("ss", $tgl_mulai, $tgl_selesai);
$stmt1->execute();
$omset_data = $stmt1->get_result()->fetch_assoc();
$total_pendapatan = $omset_data['total_omset'] ?? 0;
$stmt1->close();

/* ==================================
   3. TOTAL SUPPLIER PURCHASES (GROSS)
===================================== */
$sql_pembelian = "SELECT SUM(total_beli) as total_modal_supplier FROM pembelian WHERE DATE(tanggal) BETWEEN ? AND ?";
$stmt2 = $koneksi->prepare($sql_pembelian);
$stmt2->bind_param("ss", $tgl_mulai, $tgl_selesai);
$stmt2->execute();
$pembelian_data = $stmt2->get_result()->fetch_assoc();
$total_belanja_supplier = $pembelian_data['total_modal_supplier'] ?? 0;
$stmt2->close();

/* ==================================
   4. NEW LOGIC: CALCULATE TOTAL RETUR TO SUPPLIER
===================================== */
$sql_retur = "SELECT SUM(total_dana_kembali) as total_refund FROM retur_supplier WHERE DATE(tanggal) BETWEEN ? AND ?";
$stmt_retur = $koneksi->prepare($sql_retur);
$stmt_retur->bind_param("ss", $tgl_mulai, $tgl_selesai);
$stmt_retur->execute();
$retur_data = $stmt_retur->get_result()->fetch_assoc();
$total_retur_supplier = $retur_data['total_refund'] ?? 0;
$stmt_retur->close();

/* ==================================
   5. FINANCIAL CALCULATION (NET BASIS)
===================================== */
// Belanja bersih = Total Belanja dikurangi Dana Refund dari Retur
$belanja_bersih_supplier = $total_belanja_supplier - $total_retur_supplier;

// Sisa Modal Kas = Modal Awal dikurangi Belanja Bersih
$sisa_modal_toko = $modal_awal - $belanja_bersih_supplier;

// Laba Bersih = Omset dikurangi Belanja Bersih
$laba_bersih = $total_pendapatan - $belanja_bersih_supplier;

/* ==================================
   6. AUTO-SYNC TO LAPORAN_BULANAN TABLE
===================================== */
$sql_sync = "SELECT id_laporan FROM laporan_bulanan WHERE bulan_tahun = ?";
$stmt_sync_cek = $koneksi->prepare($sql_sync);
$stmt_sync_cek->bind_param("s", $bulan_aktif);
$stmt_sync_cek->execute();
$sync_ada = $stmt_sync_cek->get_result()->fetch_assoc();
$stmt_sync_cek->close();

if ($sync_ada) {
    $sql_update_sync = "UPDATE laporan_bulanan SET total_omset = ?, sisa_modal = ?, total_laba = ?, waktu_generate = NOW() WHERE bulan_tahun = ?";
    $stmt_up = $koneksi->prepare($sql_update_sync);
    $stmt_up->bind_param("iiis", $total_pendapatan, $sisa_modal_toko, $laba_bersih, $bulan_aktif);
    $stmt_up->execute();
    $stmt_up->close();
}

/* ==================================
   7. CASH FLOW JOURNAL TRUCKING (UNION ALL CONCAT)
===================================== */
$sql_kas = "
    SELECT DISTINCT p.id_pesanan as id, pl.nama as keterangan, p.tanggal, p.total as jumlah, 'Pendapatan (Order)' as tipe
    FROM pesanan p
    JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    WHERE p.status = 'Selesai' AND DATE(p.tanggal) BETWEEN ? AND ?
    UNION ALL
    SELECT b.id_pembelian as id, s.nama_supplier as keterangan, b.tanggal, b.total_beli as jumlah, 'Pengeluaran (Supplier)' as tipe
    FROM pembelian b
    JOIN supplier s ON b.id_supplier = s.id_supplier
    WHERE DATE(b.tanggal) BETWEEN ? AND ?
    UNION ALL
    SELECT r.id_retur as id, sup.nama_supplier as keterangan, CONVERT_TZ(r.tanggal, '+00:00', '+00:00') as tanggal, r.total_dana_kembali as jumlah, 'Retur (Uang Masuk)' as tipe
    FROM retur_supplier r
    JOIN supplier sup ON r.id_supplier = sup.id_supplier
    WHERE DATE(r.tanggal) BETWEEN ? AND ?
    ORDER BY tanggal DESC
";

$stmt3 = $koneksi->prepare($sql_kas);
$stmt3->bind_param("ssssss", $tgl_mulai, $tgl_selesai, $tgl_mulai, $tgl_selesai, $tgl_mulai, $tgl_selesai);
$stmt3->execute();
$riwayat_kas = $stmt3->get_result();
$stmt3->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan - Sekar Bouquet</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background: #fff4f7; 
            margin: 0;
            padding: 0;
        }
        h2, h3, h4, h5 { 
            font-family: 'Playfair Display', serif; 
            color: #b76e79; 
        }

        /* --- STYLE SIDEBAR SINKRON (SAMA RATA) + SCROLLABLE --- */
        .sidebar {
            width: 260px;
            height: 100vh;
            background: #b26a7a; /* Warna mauve/pink gelap sesuai gambar */
            position: fixed;
            top: 0;
            left: 0;
            padding: 30px 24px;
            color: white;
            z-index: 1000;
            
            /* FIX 1: Mengaktifkan scroll vertikal jika menu meluber melebihi tinggi layar */
            overflow-y: auto; 
        }

        /* FIX 2: Modifikasi Kustom Desain Batang Scrollbar Sidebar Agar Cantik & Elegan */
        .sidebar::-webkit-scrollbar {
            width: 6px; /* Ketebalan scrollbar tipis minimalis */
        }
        .sidebar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05); /* Latar belakang track transparan */
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.25); /* Warna pill scrollbar putih transparan masi senada */
            border-radius: 10px;
        }
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.45); /* Warna sedikit lebih terang saat disorot */
        }

        .sidebar h3 {
            color: white !important;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 2rem !important;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            color: #f5e6e8; /* Teks putih agak soft */
            padding: 12px 16px;
            text-decoration: none;
            margin-bottom: 12px;
            border-radius: 14px;
            font-weight: 500;
            font-size: 1.05rem;
            transition: all 0.2s ease;
        }
        .sidebar a i {
            font-size: 1.2rem;
            width: 30px; /* Jarak icon seragam */
        }
        /* Efek hover lembut saat kursor menyentuh menu */
        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        /* --- STYLE KONTEN UTAMA --- */
        .main { 
            margin-left: 260px; 
            padding: 40px; 
        }
        .card-box { 
            border: none; 
            border-radius: 18px; 
            box-shadow: 0 10px 25px rgba(183,110,121,0.08); 
        }
        .bg-gradient-pink { 
            background: linear-gradient(135deg, #d88b9c, #b76e79); 
            color: white; 
        }
        .btn-main { 
            background: linear-gradient(135deg, #d88b9c, #b76e79); 
            color: white; 
            border: none; 
            border-radius: 12px;
            padding: 10px 20px;
        }
        .btn-main:hover { 
            color: white; 
            opacity: 0.9; 
        }
        .table-responsive {
            background: white;
            border-radius: 12px;
            padding: 10px;
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <h2 class="mb-4">Laporan & Pembukuan Toko (Plus Sistem Retur) 💸</h2>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Berhasil!</strong> Modal awal bulan <strong><?= $bulan_aktif ?></strong> berhasil dikunci ke database laporan.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-md-7">
            <div class="card card-box p-4 bg-white h-100 justify-content-center">
                <form method="GET" action="" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small text-muted fw-bold">Tanggal Mulai</label>
                        <input type="date" name="tgl_mulai" class="form-control" value="<?= htmlspecialchars($tgl_mulai) ?>">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small text-muted fw-bold">Tanggal Selesai</label>
                        <input type="date" name="tgl_selesai" class="form-control" value="<?= htmlspecialchars($tgl_selesai) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-main w-100"><i class="fa fa-filter"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card card-box p-4 bg-white h-100 justify-content-center">
                <form method="POST" action="" class="row g-2 align-items-end">
                    <input type="hidden" name="update_modal" value="1">
                    <div class="col-md-8">
                        <label class="form-label small text-muted fw-bold">Set Modal Awal Bulan Ini (<?= $bulan_aktif ?>)</label>
                        <input type="number" name="modal_awal" class="form-control" value="<?= $modal_awal ?>" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-dark w-100"><i class="fa fa-save me-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card card-box p-4 bg-white border-start border-success border-5">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold">TOTAL PENDAPATAN (OMSET)</span>
                        <h4 class="text-success mt-1 fw-bold">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></h4>
                    </div>
                    <i class="fa fa-arrow-down text-success fa-2x"></i>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-box p-4 bg-white border-start border-danger border-5">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold">SISA SALDO KAS MODAL</span>
                        <h4 class="text-danger mt-1 fw-bold">Rp <?= number_format($sisa_modal_toko, 0, ',', '.') ?></h4>
                        <small class="text-muted" style="font-size:0.75rem;">
                            (Modal: Rp <?= number_format($modal_awal, 0, ',', '.') ?> - Net Belanja: Rp <?= number_format($belanja_bersih_supplier, 0, ',', '.') ?>)
                        </small>
                    </div>
                    <i class="fa fa-wallet text-danger fa-2x"></i>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-box p-4 bg-gradient-pink text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small fw-bold text-light">LABA BERSIH BERJALAN</span>
                        <h4 class="mt-1 fw-bold">Rp <?= number_format($laba_bersih, 0, ',', '.') ?></h4>
                        <small class="text-light" style="font-size:0.70rem;">(Omset - Belanja Bersih)</small>
                    </div>
                    <i class="fa fa-chart-line fa-2x text-light"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-box p-4 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold m-0">Arus Jurnal Transaksi Berjalan</h5>
            <button onclick="window.print()" class="btn btn-sm btn-outline-dark"><i class="fa fa-print me-1"></i> Cetak Laporan</button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle m-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Kategori</th>
                        <th>Keterangan / Partner</th>
                        <th>Nominal Kas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($riwayat_kas && $riwayat_kas->num_rows > 0): ?>
                        <?php while ($row = $riwayat_kas->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('d M Y, H:i', strtotime($row['tanggal'])) ?></td>
                            <td>
                                <?php if ($row['tipe'] == 'Pendapatan (Order)'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Masuk</span>
                                <?php elseif ($row['tipe'] == 'Retur (Uang Masuk)'): ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">Retur (Masuk)</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Keluar</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="fw-semibold"><?= htmlspecialchars($row['keterangan']) ?></span>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">ID Transaksi: #<?= $row['id'] ?></small>
                            </td>
                            <td class="fw-bold <?= ($row['tipe'] == 'Pendapatan (Order)' || $row['tipe'] == 'Retur (Uang Masuk)') ? 'text-success' : 'text-danger' ?>">
                                <?= ($row['tipe'] == 'Pendapatan (Order)' || $row['tipe'] == 'Retur (Uang Masuk)') ? '+' : '-' ?> Rp <?= number_format($row['jumlah'], 0, ',', '.') ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Tidak ada lalu lintas kas pada periode ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>