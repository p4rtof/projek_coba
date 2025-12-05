<?php
include 'koneksi.php';

// Ambil bulan dan tahun dari input, default ke bulan dan tahun sekarang
$tahun = $_GET['tahun'] ?? date('Y');
$bulan = $_GET['bulan'] ?? date('m');

// Query untuk rekapitulasi harian dalam satu bulan
$q_harian = pg_query($conn, "
    SELECT 
        tgl_order, 
        SUM(total_harga) AS total_omset
    FROM transaksi 
    WHERE 
        EXTRACT(YEAR FROM tgl_order) = $tahun AND 
        EXTRACT(MONTH FROM tgl_order) = $bulan AND
        status_pembayaran = 'Lunas'
    GROUP BY tgl_order
    ORDER BY tgl_order ASC
");

// Query untuk rekapitulasi bulanan dalam satu tahun (untuk total di bawah)
$q_bulanan = pg_fetch_assoc(pg_query($conn, "
    SELECT 
        SUM(total_harga) AS total_omset_bulan
    FROM transaksi 
    WHERE 
        EXTRACT(YEAR FROM tgl_order) = $tahun AND 
        EXTRACT(MONTH FROM tgl_order) = $bulan AND
        status_pembayaran = 'Lunas'
"));
$total_bulan_ini = $q_bulanan['total_omset_bulan'] ?? 0;

// Daftar bulan untuk dropdown
$list_bulan = [1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April', 5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus', 9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Laporan Keuangan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style> /* ... (Ambil Style dari index.php) ... */ </style>
</head>
<body>
    <?php include 'navbar.php'; // Atau copy navbar dari index.php ?>

    <div class="container py-5">
        <h4 class="fw-bold text-dark mb-4">
            <i class="bi bi-bar-chart-line-fill me-2"></i> Rekap Pendapatan
        </h4>
        
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="small text-muted">Filter Bulan</label>
                        <select name="bulan" class="form-select">
                            <?php foreach($list_bulan as $num => $nama): ?>
                                <option value="<?= $num ?>" <?= $bulan == $num ? 'selected' : '' ?>><?= $nama ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted">Filter Tahun</label>
                        <select name="tahun" class="form-select">
                            <?php for($y = date('Y'); $y >= 2020; $y--): ?>
                                <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
                    </div>
                </form>
            </div>
        </div>

        <h5 class="mt-4 mb-3 fw-bold">Detail Harian Bulan <?= $list_bulan[(int)$bulan] ?> <?= $tahun ?></h5>

        <div class="card shadow border-0 rounded-4 overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="py-3 ps-4">Tanggal</th>
                                <th class="py-3 text-end pe-4">Total Pendapatan (Lunas)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($r = pg_fetch_assoc($q_harian)) : ?>
                            <tr>
                                <td class="ps-4 fw-bold"><?= date('d F Y', strtotime($r['tgl_order'])) ?></td>
                                <td class="text-end pe-4 fw-bold text-success">Rp <?= number_format($r['total_omset'], 0, ',','.') ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                             <tr>
                                <td class="ps-4 fw-bold">TOTAL BULAN INI (LUNAS)</td>
                                <td class="text-end pe-4 text-primary fw-bold fs-5">Rp <?= number_format($total_bulan_ini, 0, ',','.') ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>