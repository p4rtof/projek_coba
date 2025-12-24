<?php
include '../../config/koneksi.php';
include '../../auth/auth.php';

// Cek data kiriman dari Keranjang
if (!isset($_POST['ids']) || empty($_POST['ids'])) {
    echo "Tidak ada data transaksi yang dipilih.";
    exit;
}

$ids = $_POST['ids'];

// Escape string untuk keamanan
$ids_clean = array_map(function($id) use ($conn) {
    return pg_escape_string($conn, $id);
}, $ids);
$ids_string = "'" . implode("','", $ids_clean) . "'";

// Query Ambil Semua Data Transaksi berdasarkan ID
$query = "SELECT t.*, p.nama_produk, pel.nama as nama_pelanggan, pel.alamat, pel.no_hp 
          FROM transaksi t 
          JOIN produk p ON t.id_produk = p.id_produk 
          JOIN pelanggan pel ON t.id_pelanggan = pel.id_pelanggan 
          WHERE t.id_transaksi IN ($ids_string)
          ORDER BY t.waktu_order ASC";

$result = pg_query($conn, $query);

if (pg_num_rows($result) == 0) { echo "Data tidak ditemukan."; exit; }

// Ambil info pelanggan dari baris pertama (karena 1 invoice = 1 pelanggan)
$first_row = pg_fetch_assoc($result);
pg_result_seek($result, 0); // Reset pointer
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Invoice Gabungan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f3f4f6; color: #333; font-family: 'Courier New', Courier, monospace; }
        .invoice-box {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            border: 1px solid #eee;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
        }
        .header { border-bottom: 2px dashed #333; padding-bottom: 20px; margin-bottom: 20px; }
        .table-items th { border-bottom: 1px solid #ddd; padding: 10px 0; text-transform: uppercase; font-size: 0.9rem; }
        .table-items td { padding: 10px 0; border-bottom: 1px solid #eee; }
        
        @media print {
            body { background: white; }
            .invoice-box { box-shadow: none; border: none; margin: 0; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="invoice-box">
    
    <div class="header d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold m-0">ZADDY PRINTING</h4>
            <small>Jl. Percetakan Negara No. 1, Jakarta</small><br>
            <small>WA: 0812-3456-7890</small>
        </div>
        <div class="text-end">
            <h5 class="fw-bold text-uppercase">INVOICE</h5>
            <small><?= date('d/m/Y H:i') ?></small>
        </div>
    </div>

    <div class="mb-4">
        <table class="table table-borderless table-sm w-auto">
            <tr>
                <td class="ps-0 text-secondary">Pelanggan</td>
                <td class="fw-bold">: <?= $first_row['nama_pelanggan'] ?></td>
            </tr>
            <tr>
                <td class="ps-0 text-secondary">Alamat</td>
                <td>: <?= $first_row['alamat'] ?? '-' ?></td>
            </tr>
        </table>
    </div>

    <table class="table table-items w-100 mb-4">
        <thead>
            <tr>
                <th>Item / Produk</th>
                <th class="text-center">Ukuran</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $grand_total = 0;
            while($row = pg_fetch_assoc($result)): 
                $grand_total += $row['total_harga'];
                
                $ukuran = "-";
                if($row['panjang'] > 0) $ukuran = $row['panjang'] . " x " . $row['lebar'] . " m";
            ?>
            <tr>
                <td>
                    <div class="fw-bold"><?= $row['nama_produk'] ?></div>
                    <div class="small text-muted" style="font-size: 0.75rem;">Ref: #<?= $row['id_transaksi'] ?></div>
                </td>
                <td class="text-center small"><?= $ukuran ?></td>
                <td class="text-center"><?= $row['jumlah'] ?></td>
                <td class="text-end fw-bold">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-end py-3 text-uppercase small">Grand Total</td>
                <td class="text-end py-3 fs-5 fw-bold bg-light">Rp <?= number_format($grand_total, 0, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="row mt-5">
        <div class="col-7">
            <small class="text-muted d-block mb-2">Metode Pembayaran:</small>
            <div class="border p-2 rounded small text-secondary d-inline-block">
                BCA: 123-456-7890 (Zaddy Print)<br>
                BRI: 987-654-3210 (Zaddy Print)
            </div>
            <div class="mt-3">
                Status: 
                <span class="badge bg-dark text-white text-uppercase"><?= $first_row['status_pembayaran'] ?></span>
            </div>
        </div>
        <div class="col-5 text-center d-flex flex-column justify-content-end">
            <div style="height: 60px;"></div>
            <p class="fw-bold text-decoration-underline mb-0">Admin Kasir</p>
        </div>
    </div>

    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="btn btn-dark w-100 rounded-pill"><i class="bi bi-printer me-2"></i> Cetak Invoice Gabungan</button>
    </div>

</div>

</body>
</html>