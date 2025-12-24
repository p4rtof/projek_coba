<?php
include '../../config/koneksi.php';
include '../../auth/auth.php';

// 1. Cek apakah ada data ID yang dikirim
if (!isset($_POST['ids']) || empty($_POST['ids'])) {
    echo "<script>alert('Tidak ada data transaksi yang dipilih!'); window.close();</script>";
    exit;
}

$ids = $_POST['ids'];

// 2. Ubah Array ID menjadi String untuk Query SQL (contoh: 'T001','T002','T003')
// Kita escape string dulu biar aman
$ids_clean = array_map(function($id) use ($conn) {
    return pg_escape_string($conn, $id);
}, $ids);
$ids_string = "'" . implode("','", $ids_clean) . "'";

// 3. Ambil Semua Data Transaksi berdasarkan ID tersebut
$query = "SELECT t.*, p.nama_produk, pel.nama as nama_pelanggan, pel.alamat, pel.no_hp 
          FROM transaksi t 
          JOIN produk p ON t.id_produk = p.id_produk 
          JOIN pelanggan pel ON t.id_pelanggan = pel.id_pelanggan 
          WHERE t.id_transaksi IN ($ids_string)
          ORDER BY t.waktu_order ASC";

$result = pg_query($conn, $query);

if (pg_num_rows($result) == 0) {
    echo "Data tidak ditemukan.";
    exit;
}

// Ambil data baris pertama untuk Info Pelanggan (Asumsi 1 pelanggan sama)
$first_row = pg_fetch_assoc($result);

// Kembalikan pointer data ke awal supaya bisa di-looping lagi di tabel
pg_result_seek($result, 0); 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Invoice Gabungan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f3f4f6; color: #333; font-family: 'Courier New', Courier, monospace; }
        .invoice-box {
            max-width: 850px;
            margin: 30px auto;
            padding: 40px;
            border: 1px solid #eee;
            background: white;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        }
        .header-title { font-size: 24px; font-weight: bold; letter-spacing: 1px; }
        .table-items thead { border-top: 2px dashed #333; border-bottom: 2px dashed #333; }
        .table-items th { padding: 10px 0; font-size: 14px; text-transform: uppercase; }
        .table-items td { padding: 12px 0; vertical-align: top; border-bottom: 1px solid #f0f0f0; }
        
        @media print {
            body { background: white; }
            .invoice-box { box-shadow: none; border: none; margin: 0; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="invoice-box">
    
    <div class="row mb-5 align-items-center">
        <div class="col-7">
            <h1 class="header-title mb-1">ZADDY PRINTING</h1>
            <div class="small text-muted">
                Jl. Percetakan Negara No. 123, Jakarta<br>
                WA: 0812-3456-7890 | Email: admin@zaddy.com
            </div>
        </div>
        <div class="col-5 text-end">
            <h4 class="fw-bold text-uppercase mb-1">INVOICE</h4>
            <div class="small text-muted">
                Tgl Cetak: <?= date('d/m/Y H:i') ?><br>
                <strong>GABUNGAN (<?= count($ids) ?> Item)</strong>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="p-3 bg-light rounded border">
                <table class="w-100">
                    <tr>
                        <td width="100" class="text-secondary small fw-bold text-uppercase">Kepada Yth:</td>
                        <td class="fw-bold fs-5"><?= $first_row['nama_pelanggan'] ?></td>
                    </tr>
                    <tr>
                        <td class="text-secondary small fw-bold text-uppercase">Telepon:</td>
                        <td><?= $first_row['no_hp'] ?? '-' ?></td>
                    </tr>
                    <tr>
                        <td class="text-secondary small fw-bold text-uppercase">Alamat:</td>
                        <td><?= $first_row['alamat'] ?? '-' ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <table class="table table-items w-100 mb-4 table-borderless">
        <thead>
            <tr>
                <th width="40%">Item / Produk</th>
                <th width="20%" class="text-center">Ukuran</th>
                <th width="10%" class="text-center">Qty</th>
                <th width="30%" class="text-end">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $grand_total = 0;
            while($row = pg_fetch_assoc($result)): 
                $grand_total += $row['total_harga'];
                
                // Format Ukuran
                $ukuran = "-";
                if($row['panjang'] > 0 && $row['lebar'] > 0) {
                    $ukuran = floatval($row['panjang']) . " x " . floatval($row['lebar']) . " m";
                }
            ?>
            <tr>
                <td>
                    <div class="fw-bold"><?= $row['nama_produk'] ?></div>
                    <div class="small text-muted">No. Order: #<?= $row['id_transaksi'] ?></div>
                </td>
                <td class="text-center small text-secondary"><?= $ukuran ?></td>
                <td class="text-center fw-bold"><?= $row['jumlah'] ?></td>
                <td class="text-end fw-bold">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot style="border-top: 2px dashed #333;">
            <tr>
                <td colspan="3" class="text-end py-4 fw-bold fs-5 text-uppercase">Total Tagihan</td>
                <td class="text-end py-4 fw-bold fs-4 bg-light">Rp <?= number_format($grand_total, 0, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="row mt-5">
        <div class="col-7">
            <div class="small text-muted mb-2">Info Pembayaran:</div>
            <div class="border p-2 rounded small d-inline-block bg-white">
                BCA: 123-456-789 (Zaddy Print)<br>
                BRI: 987-654-321 (Zaddy Print)
            </div>
            
            <div class="mt-3">
                Status: 
                <?php if($first_row['status_pembayaran'] == 'Lunas'): ?>
                    <span class="badge bg-success text-uppercase px-3">LUNAS</span>
                <?php else: ?>
                    <span class="badge bg-danger text-uppercase px-3">BELUM LUNAS</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-5 text-center d-flex flex-column justify-content-end">
            <div style="height: 80px;"></div>
            <div class="border-top border-dark w-75 mx-auto pt-2 fw-bold">Admin / Kasir</div>
        </div>
    </div>

    <div class="no-print mt-5 text-center">
        <button onclick="window.print()" class="btn btn-dark btn-lg rounded-pill shadow px-5">
            <i class="bi bi-printer-fill me-2"></i> Cetak Invoice
        </button>
        <div class="mt-3">
            <a href="../../index.php" class="text-secondary small text-decoration-none">Kembali ke Dashboard</a>
        </div>
    </div>

</div>

</body>
</html>