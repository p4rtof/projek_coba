<?php
include '../../config/koneksi.php';
include '../../auth/auth.php';

// --- LOGIKA MENANGKAP ID ---
$ids_to_print = [];

if (isset($_POST['ids']) && !empty($_POST['ids'])) {
    $ids_to_print = $_POST['ids'];
} elseif (isset($_GET['id']) && !empty($_GET['id'])) {
    $ids_to_print = [$_GET['id']];
} else {
    echo "<script>alert('Data transaksi tidak ditemukan!'); window.location.href='../../index.php';</script>";
    exit;
}

$ids_clean = array_map(function($id) use ($conn) {
    return pg_escape_string($conn, $id);
}, $ids_to_print);

$ids_string = "'" . implode("','", $ids_clean) . "'";

// --- QUERY DATA ---
$query = "
    SELECT 
        t.*, 
        p.nama AS p_nama, p.hp, p.alamat, 
        pr.nama_produk, pr.harga AS harga_satuan,
        b.nama_bank, b.no_rekening, b.atas_nama
    FROM transaksi t 
    JOIN pelanggan p ON t.id_pelanggan=p.id_pelanggan 
    JOIN produk pr ON t.id_produk=pr.id_produk 
    LEFT JOIN bank_akun b ON t.id_bank = b.id_bank
    WHERE t.id_transaksi IN ($ids_string)
    ORDER BY t.waktu_order ASC
";

$result = pg_query($conn, $query);

if (!$result || pg_num_rows($result) == 0) {
    die("Data tidak ditemukan.");
}

$first_row = pg_fetch_assoc($result);
pg_result_seek($result, 0); 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Invoice Cetak</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; font-family: sans-serif; -webkit-print-color-adjust: exact; }
        
        /* Padding box tetap kecil (20px) agar hemat ruang pinggir */
        .invoice-box { 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
        }
        
        .payment-box { 
            background: #f8f9fa; 
            border: 1px solid #dee2e6; 
            border-radius: 6px; 
            padding: 8px 12px; 
            font-size: 0.85rem;
        }
        .payment-box strong { color: #495057; }
        
        @media print {
            @page { margin: 0; size: auto; }
            body { background: white; margin: 1cm; }
            .no-print { display: none !important; }
            .invoice-box { box-shadow: none; border: none; padding: 0; width: 100%; }
            .container { max-width: 100%; padding: 0; }
        }
    </style>
</head>
<body>

<div class="container py-3">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <div class="text-end mb-3 no-print gap-2 d-flex justify-content-end">
                <a href="../../index.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
                <button onclick="window.print()" class="btn btn-sm btn-primary rounded-pill px-3"><i class="bi bi-printer-fill me-1"></i>Cetak</button>
            </div>

            <div class="invoice-box">
                <div class="d-flex justify-content-between align-items-start mb-3 border-bottom pb-2">
                    <div>
                        <h4 class="fw-bold text-primary mb-0">Zaddy Printing</h4>
                        <p class="text-muted small mb-0">Jl. Printing Digital No. 123, Jakarta</p>
                        <p class="text-muted small mb-0">WA: 0812-3456-7890</p>
                    </div>
                    <div class="text-end">
                        <h3 class="fw-bold text-dark mb-0">INVOICE</h3>
                        <span class="text-muted fw-bold small">
                            <?= count($ids_to_print) > 1 ? '#GABUNGAN' : '#' . $first_row['id_transaksi'] ?>
                        </span>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-6">
                        <small class="text-muted fw-bold text-uppercase">Kepada:</small>
                        <div class="fw-bold text-dark"><?= $first_row['p_nama'] ?></div>
                        <div class="small text-muted text-truncate">
                            <?= $first_row['alamat'] ?? '-' ?> | <?= $first_row['hp'] ?? '-' ?>
                        </div>
                    </div>
                    <div class="col-6 text-end">
                        <div class="small">
                            <span class="fw-bold">Tgl:</span> <?= date('d/m/Y H:i', strtotime($first_row['waktu_order'])) ?><br>
                            <span class="badge bg-<?= $first_row['status_pembayaran'] == 'Lunas' ? 'success' : 'danger' ?> text-uppercase" style="font-size: 0.7rem;">
                                <?= $first_row['status_pembayaran'] ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="payment-box">
                        <div class="d-flex align-items-center">
                            <small class="text-muted fw-bold text-uppercase me-2">Bayar via:</small>
                            <div class="small">
                                <?php if (($first_row['metode_pembayaran'] ?? '') == 'Transfer'): ?>
                                    <span><i class="bi bi-bank me-1"></i> <strong><?= $first_row['nama_bank'] ?></strong></span>
                                    <span class="mx-2">|</span>
                                    <span><strong><?= $first_row['no_rekening'] ?></strong></span>
                                <?php else: ?>
                                    <span><i class="bi bi-cash me-1"></i> Tunai (Cash)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered border-light mb-0 table-sm">
                        <thead class="table-light">
                            <tr>
                                <th class="py-2 ps-2">Produk</th>
                                <th class="py-2 text-center">Qty</th>
                                <th class="py-2 text-end">Harga</th>
                                <th class="py-2 text-end pe-2">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grand_total = 0;
                            while($row = pg_fetch_assoc($result)): 
                                $grand_total += $row['total_harga'];
                            ?>
                            <tr>
                                <td class="ps-2 py-2">
                                    <span class="fw-bold text-dark small"><?= $row['nama_produk'] ?></span>
                                    <?php if(count($ids_to_print) > 1): ?>
                                        <br><small class="text-muted" style="font-size: 0.65rem;">Ref: #<?= $row['id_transaksi'] ?></small>
                                    <?php endif; ?>
                                    <?php if($row['panjang'] > 0): ?>
                                        <br><small class="text-muted fst-italic" style="font-size: 0.7rem;"><?= floatval($row['panjang']) ?>m x <?= floatval($row['lebar']) ?>m</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center py-2 small"><?= number_format($row['jumlah']) ?></td>
                                <td class="text-end py-2 small">Rp <?= number_format($row['harga_satuan'], 0, ',','.') ?></td>
                                <td class="text-end pe-2 py-2 fw-bold small">Rp <?= number_format($row['total_harga'], 0, ',','.') ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="row mt-4">
                    <div class="col-7"></div>
                    <div class="col-5">
                        <div class="d-flex justify-content-between align-items-center mb-5 border-bottom pb-2">
                            <span class="fw-bold text-secondary">TOTAL TAGIHAN</span>
                            <span class="fw-bold text-primary fs-5">Rp <?= number_format($grand_total, 0, ',','.') ?></span>
                        </div>

                        <div class="text-center mt-4">
                            <p class="mb-5 fw-bold small text-muted">Hormat Kami,</p>
                            <br> <p class="fw-bold mb-0 text-decoration-underline text-dark">Zaddy Printing</p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3 pt-2 border-top">
                    <small class="text-muted fst-italic" style="font-size: 0.7rem;">Terima kasih atas kepercayaan Anda.</small>
                </div>

            </div>
        </div>
    </div>
</div>

</body>
</html>