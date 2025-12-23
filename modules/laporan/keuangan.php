<?php
// Pastikan path include benar
include '../../config/koneksi.php';
include '../../auth/auth.php';

// --- LOGIC FILTER ---
$tahun = $_GET['tahun'] ?? date('Y');
$bulan = $_GET['bulan'] ?? date('m');

$list_bulan = [1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April', 5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus', 9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember'];

// --- QUERY DATA ---
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

$data_chart_tgl = [];
$data_chart_omset = [];
$tabel_data = [];
$total_bulan_ini = 0;
$total_transaksi_bulan_ini = 0;

while ($r = pg_fetch_assoc($q_harian)) {
    $tabel_data[] = $r;
    $data_chart_tgl[]   = "'" . date('d/m', strtotime($r['tgl_order'])) . "'";
    $data_chart_omset[] = $r['total_omset'];
    $total_bulan_ini += $r['total_omset'];
    $total_transaksi_bulan_ini += $r['jumlah_transaksi'];
}

$js_labels = implode(',', $data_chart_tgl);
$js_data   = implode(',', $data_chart_omset);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Laporan Keuangan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body { background: #f1f5f9; font-family: 'Inter', sans-serif; }
        
        .card-modern {
            background: white; border: 1px solid #e2e8f0; border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow: hidden;
        }
        
        .bg-gradient-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
        .bg-gradient-green { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        
        .circle-icon { position: absolute; right: 15px; bottom: 15px; font-size: 4rem; opacity: 0.2; transform: rotate(-15deg); }

        /* --- SETTING KHUSUS CETAK (PRINT) --- */
        @media print {
            /* Sembunyikan elemen yang tidak perlu */
            .navbar, .btn, form, .no-print { display: none !important; }
            
            /* Reset body agar putih bersih */
            body { background: white; -webkit-print-color-adjust: exact; margin: 0; padding: 0; }
            
            .container { max-width: 100%; width: 100%; padding: 0; }
            
            /* Card jadi flat (tanpa bayangan) */
            .card, .card-modern { 
                border: 1px solid #ddd !important; 
                box-shadow: none !important; 
                break-inside: avoid; /* Jangan potong card di tengah halaman */
            }
            
            /* Paksa Grid Bootstrap agar rapi di kertas */
            .col-md-6 { width: 50% !important; float: left; }
            .col-lg-8 { width: 60% !important; float: left; }
            .col-lg-4 { width: 40% !important; float: left; }
            
            /* Tampilkan Kop Surat saat Print */
            #print-header { display: block !important; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
            
            /* Atur ulang warna teks agar hitam pekat */
            .text-muted { color: #333 !important; }
            h2, h3, h6 { color: #000 !important; }
        }
        
        #print-header { display: none; } /* Sembunyikan Kop Surat di Layar Biasa */
    </style>
</head>
<body>
    
    <?php include '../../components/navbar.php'; ?>

    <div class="container py-5">
        
        <div id="print-header" class="text-center">
            <h2 class="fw-bold m-0">ZADDY PRINTING</h2>
            <p class="m-0 small">Jl. Percetakan Negara No. 123, Jakarta Indonesia</p>
            <p class="m-0 small">Telp: 0812-3456-7890 | Email: admin@zaddyprinting.com</p>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h4 class="fw-bold text-dark mb-0">Laporan Keuangan</h4>
                <p class="text-secondary mb-0">Periode: <?= $list_bulan[(int)$bulan] ?> <?= $tahun ?></p>
            </div>
            
            <div class="d-flex gap-2 no-print">
                <button onclick="window.print()" class="btn btn-dark rounded-pill px-4">
                    <i class="bi bi-printer me-2"></i> Cetak Laporan
                </button>
            </div>
        </div>

        <div class="card-modern mb-4 no-print p-3">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="fw-bold small text-secondary mb-1">Bulan</label>
                    <select name="bulan" class="form-select">
                        <?php foreach($list_bulan as $num => $nama): ?>
                            <option value="<?= $num ?>" <?= $bulan == $num ? 'selected' : '' ?>><?= $nama ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="fw-bold small text-secondary mb-1">Tahun</label>
                    <select name="tahun" class="form-select">
                        <?php for($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Filter</button>
                </div>
            </form>
        </div>

        <div class="row mb-4 g-4">
            <div class="col-md-6">
                <div class="card-modern bg-gradient-green p-4 position-relative">
                    <h6 class="text-uppercase mb-1 opacity-75">Total Pendapatan</h6>
                    <h2 class="fw-bold mb-0">Rp <?= number_format($total_bulan_ini, 0, ',', '.') ?></h2>
                    <i class="bi bi-cash-stack circle-icon"></i>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card-modern bg-gradient-blue p-4 position-relative">
                    <h6 class="text-uppercase mb-1 opacity-75">Transaksi Selesai</h6>
                    <h2 class="fw-bold mb-0"><?= $total_transaksi_bulan_ini ?> <span class="fs-6 fw-normal">Order</span></h2>
                    <i class="bi bi-bag-check circle-icon"></i>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card-modern h-100">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold m-0 text-dark"><i class="bi bi-graph-up me-2"></i>Grafik Harian</h6>
                    </div>
                    <div class="card-body">
                        <?php if(!empty($tabel_data)): ?>
                            <canvas id="omsetChart" style="max-height: 300px; width: 100%;"></canvas>
                        <?php else: ?>
                            <div class="text-center py-5 text-secondary">Tidak ada data.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-modern h-100">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold m-0 text-dark"><i class="bi bi-table me-2"></i>Rincian Harian</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0 text-center small">
                                <thead class="table-light">
                                    <tr>
                                        <th class="py-2">Tgl</th>
                                        <th class="py-2">Order</th>
                                        <th class="py-2 text-end pe-3">Omset</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($tabel_data)): ?>
                                        <?php foreach($tabel_data as $row): ?>
                                        <tr>
                                            <td><?= date('d/m', strtotime($row['tgl_order'])) ?></td>
                                            <td><?= $row['jumlah_transaksi'] ?></td>
                                            <td class="text-end pe-3 fw-bold text-dark">
                                                <?= number_format($row['total_omset'], 0, ',', '.') ?>
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
                </div>
            </div>
        </div>

        <div class="mt-4 text-end no-print">
            <small class="text-muted">Dicetak oleh: <?= $_SESSION['username'] ?? 'Admin' ?> pada <?= date('d M Y H:i') ?></small>
        </div>

    </div>

    <script>
        const ctx = document.getElementById('omsetChart');
        if(ctx) {
            new Chart(ctx, {
                type: 'line', 
                data: {
                    labels: [<?= $js_labels ?>],
                    datasets: [{
                        label: 'Omset',
                        data: [<?= $js_data ?>],
                        borderWidth: 2,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#2563eb',
                        pointRadius: 4,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { borderDash: [2, 4] } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>