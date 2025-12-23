<?php
// Pastikan path include benar (mundur 2 langkah)
include '../../config/koneksi.php';
include '../../auth/auth.php';

// --- LOGIC FILTER ---
$tahun = $_GET['tahun'] ?? date('Y');
$bulan = $_GET['bulan'] ?? date('m');

// Daftar nama bulan
$list_bulan = [1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April', 5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus', 9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember'];

// --- QUERY DATA ---

// 1. Ambil Data Harian untuk Tabel & Grafik
$q_harian = pg_query($conn, "
    SELECT 
        DATE(waktu_order) AS tgl_order, 
        SUM(total_harga) AS total_omset,
        COUNT(id_transaksi) AS jumlah_transaksi
    FROM transaksi 
    WHERE 
        EXTRACT(YEAR FROM waktu_order) = $tahun AND 
        EXTRACT(MONTH FROM waktu_order) = $bulan AND
        status_pembayaran = 'Lunas'
    GROUP BY DATE(waktu_order)
    ORDER BY tgl_order ASC
");

// Siapkan array untuk Grafik & Tabel
$data_chart_tgl = [];
$data_chart_omset = [];
$tabel_data = [];
$total_bulan_ini = 0;
$total_transaksi_bulan_ini = 0;

while ($r = pg_fetch_assoc($q_harian)) {
    // Masukkan ke array untuk diproses nanti
    $tabel_data[] = $r;
    
    // Data untuk Chart JS (Label Tanggal & Value Omset)
    $data_chart_tgl[]   = "'" . date('d M', strtotime($r['tgl_order'])) . "'"; // Format: '01 Jan'
    $data_chart_omset[] = $r['total_omset'];
    
    // Hitung Total Keseluruhan
    $total_bulan_ini += $r['total_omset'];
    $total_transaksi_bulan_ini += $r['jumlah_transaksi'];
}

// Gabungkan array untuk jadi string Javascript
$js_labels = implode(',', $data_chart_tgl);
$js_data   = implode(',', $data_chart_omset);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Laporan Keuangan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body { background: #f0f2f5; font-family: 'Poppins', sans-serif; }
        .card-stat { border: none; border-radius: 15px; transition: transform 0.3s; color: white; overflow: hidden; position: relative; }
        .card-stat:hover { transform: translateY(-5px); }
        .bg-gradient-blue { background: linear-gradient(45deg, #4e73df, #224abe); }
        .bg-gradient-green { background: linear-gradient(45deg, #1cc88a, #13855c); }
        .circle-icon { position: absolute; right: 10px; bottom: 10px; font-size: 5rem; opacity: 0.2; transform: rotate(-15deg); }
        
        @media print {
            .no-print { display: none !important; }
            .card { border: 1px solid #ddd !important; box-shadow: none !important; }
        }
    </style>
</head>
<body>
    
    <?php include '../../components/navbar.php'; ?>

    <div class="container py-5">
        
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-0">Laporan Keuangan</h3>
                <p class="text-muted mb-0">Periode: <?= $list_bulan[(int)$bulan] ?> <?= $tahun ?></p>
            </div>
            
            <div class="d-flex gap-2 no-print">
                <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-printer me-2"></i> Cetak
                </button>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 mb-4 no-print">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="fw-bold small text-muted mb-1">Pilih Bulan</label>
                        <select name="bulan" class="form-select bg-light">
                            <?php foreach($list_bulan as $num => $nama): ?>
                                <option value="<?= $num ?>" <?= $bulan == $num ? 'selected' : '' ?>><?= $nama ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="fw-bold small text-muted mb-1">Pilih Tahun</label>
                        <select name="tahun" class="form-select bg-light">
                            <?php for($y = date('Y'); $y >= 2020; $y--): ?>
                                <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 fw-bold">Tampilkan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row mb-4 g-4">
            <div class="col-md-6">
                <div class="card card-stat bg-gradient-green shadow h-100 p-3">
                    <div class="card-body">
                        <h6 class="text-uppercase mb-2 opacity-75">Total Pendapatan (Lunas)</h6>
                        <h2 class="fw-bold mb-0">Rp <?= number_format($total_bulan_ini, 0, ',', '.') ?></h2>
                        <i class="bi bi-cash-stack circle-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-stat bg-gradient-blue shadow h-100 p-3">
                    <div class="card-body">
                        <h6 class="text-uppercase mb-2 opacity-75">Total Transaksi Selesai</h6>
                        <h2 class="fw-bold mb-0"><?= $total_transaksi_bulan_ini ?> <span class="fs-6 fw-normal">Order</span></h2>
                        <i class="bi bi-bag-check circle-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card shadow border-0 rounded-4 h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold m-0 text-primary"><i class="bi bi-graph-up me-2"></i>Grafik Harian</h6>
                    </div>
                    <div class="card-body">
                        <?php if(!empty($tabel_data)): ?>
                            <canvas id="omsetChart" style="max-height: 300px;"></canvas>
                        <?php else: ?>
                            <div class="text-center py-5 text-muted">Tidak ada data untuk ditampilkan grafik.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="card shadow border-0 rounded-4 h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold m-0 text-dark"><i class="bi bi-table me-2"></i>Rincian Harian</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 330px; overflow-y: auto;">
                            <table class="table table-striped table-hover mb-0 text-center small">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="py-2">Tanggal</th>
                                        <th class="py-2">Jml Order</th>
                                        <th class="py-2 text-end pe-3">Omset</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($tabel_data)): ?>
                                        <?php foreach($tabel_data as $row): ?>
                                        <tr>
                                            <td><?= date('d/m', strtotime($row['tgl_order'])) ?></td>
                                            <td><span class="badge bg-secondary rounded-pill"><?= $row['jumlah_transaksi'] ?></span></td>
                                            <td class="text-end pe-3 fw-bold text-success">
                                                <?= number_format($row['total_omset'] / 1000, 0) ?>k
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" class="py-4">Data Kosong</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white text-muted small fst-italic">
                        *Omset dalam ribuan (k)
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        const ctx = document.getElementById('omsetChart');
        if(ctx) {
            new Chart(ctx, {
                type: 'line', // Bisa ganti 'bar' kalau mau diagram batang
                data: {
                    labels: [<?= $js_labels ?>],
                    datasets: [{
                        label: 'Omset Harian (Rp)',
                        data: [<?= $js_data ?>],
                        borderWidth: 2,
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78, 115, 223, 0.1)',
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#4e73df',
                        pointRadius: 4,
                        fill: true,
                        tension: 0.3 // Biar garisnya agak melengkung halus
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { borderDash: [2, 4] }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>