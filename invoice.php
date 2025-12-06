<?php
include 'koneksi.php';
include 'auth.php'; // WAJIB: Proteksi halaman ini dengan login admin

if (!isset($_GET['id'])) {
    header("Location: index.php"); exit();
}

$id = $_GET['id'];
$query = "
    SELECT 
        t.*, 
        p.nama AS p_nama, p.hp, p.alamat, 
        pr.nama_produk, pr.harga AS harga_satuan
    FROM transaksi t 
    JOIN pelanggan p ON t.id_pelanggan=p.id_pelanggan 
    JOIN produk pr ON t.id_produk=pr.id_produk 
    WHERE t.id_transaksi = '$id'
";

$q = pg_query($conn, $query);

// TAMBAH: Cek jika query GAGAL
if ($q === false) {
    $pg_error = pg_last_error($conn);
    // Tampilkan error dan hentikan eksekusi
    die("<div style='padding: 20px; font-family: sans-serif; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'>
            🚨 ERROR QUERY DATABASE 🚨<br>
            Mohon cek kolom dan tabel. Detail: <b>{$pg_error}</b>
         </div>");
}

$data = pg_fetch_assoc($q);

// Cek jika data transaksi tidak ditemukan
if (!$data) {
    die("<div style='padding: 20px; font-family: sans-serif; background: #fff3cd; color: #856404; border: 1px solid #ffeeba;'>
            ⚠️ DATA TIDAK DITEMUKAN ⚠️<br>
            Transaksi dengan ID: <b>{$id}</b> tidak ada.
         </div>");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Invoice <?= $data['id_transaksi'] ?></title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; font-family: sans-serif; }
        .invoice-box { background: white; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); }
        .header { border-bottom: 2px solid #0d6efd; padding-bottom: 10px; margin-bottom: 20px; }
        @media print {
            .no-print { display: none; }
            .invoice-box { border: 0; box-shadow: none; padding: 0; }
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">

            <div class="text-end mb-4 no-print">
                <button onclick="window.print()" class="btn btn-success"><i class="bi bi-printer me-2"></i>Cetak Invoice</button>
                <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-2"></i>Kembali</a>
            </div>

            <div class="invoice-box">
                <div class="header d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="fw-bold text-primary mb-0">Zaddy Printing</h2>
                        <small class="text-muted">Jl. Printing Digital No. 123</small>
                    </div>
                    <div class="text-end">
                        <h4 class="mb-0">INVOICE</h4>
                        <span class="text-muted"><?= $data['id_transaksi'] ?></span> 
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-6">
                        <small class="text-muted">Kepada Yth:</small>
                        <h6 class="fw-bold mb-0"><?= $data['p_nama'] ?></h6>
                        <small class="d-block"><?= $data['alamat'] ?></small>
                        <small class="d-block">Telp: <?= $data['hp'] ?></small>
                    </div>
                    <div class="col-6 text-end">
                        <small class="text-muted">ID Transaksi:</small>
                        <h6 class="fw-bold mb-0"><?= $data['id_transaksi'] ?></h6> 
                        <small class="text-muted">Tanggal Transaksi:</small>
                        <h6 class="fw-bold mb-0"><?= date('d F Y', strtotime($data['waktu_order'])) ?></h6> 
                        <!-- <small class="d-block">Status Bayar: 
                            <span class="badge bg-<?= $data['status_pembayaran'] == 'Lunas' ? 'success' : 'danger' ?>"><?= $data['status_pembayaran'] ?></span>
                        </small> -->
                    </div>
                </div>

                <table class="table table-bordered mb-4">
                    <thead>
                        <tr class="table-light">
                            <th style="width: 5%;"></th>
                            <th>Deskripsi Produk</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td><?= $data['nama_produk'] ?></td>
                            <td class="text-center"><?= number_format($data['jumlah']) ?></td>
                            <td class="text-end">Rp <?= number_format($data['harga_satuan'], 0, ',','.') ?></td>
                            <td class="text-end">Rp <?= number_format($data['total_harga'], 0, ',','.') ?></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold table-light">
                            <td colspan="4" class="text-end">TOTAL TAGIHAN</td>
                            <td class="text-end fs-5 text-primary">Rp <?= number_format($data['total_harga'], 0, ',','.') ?></td>
                        </tr>
                    </tfoot>
                </table>

                <!-- <p class="text-muted small mt-5">Terima kasih atas pesanannya. Mohon segera diselesaikan jika status pembayaran masih "Belum Lunas".</p> -->
            </div>
        </div>
    </div>
</div>

</body>
</html>