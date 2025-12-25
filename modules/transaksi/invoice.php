<?php
include '../../config/koneksi.php';
include '../../auth/auth.php';

// --- 1. LOGIKA MENANGKAP ID ---
$where_clause = "";
$mode_judul = "";
$ids_to_check = []; // Array untuk menyimpan ID Transaksi yang akan dicek

if (isset($_GET['item_id'])) {
    // Kasus: Print per Item (UID) -> Ambil ID Transaksinya dulu
    $uid = pg_escape_string($conn, $_GET['item_id']);
    $q_cek = pg_query($conn, "SELECT id_transaksi FROM transaksi WHERE id = '$uid'");
    if ($r = pg_fetch_assoc($q_cek)) {
        $ids_to_check[] = $r['id_transaksi'];
        $where_clause = "WHERE t.id = '$uid'"; // Filter query utama tetap by UID agar cuma item itu yg muncul
    }
} elseif (isset($_POST['ids']) && !empty($_POST['ids'])) {
    // Kasus: Print Banyak (Checkbox) -> ID Transaksi sudah ada di POST
    $ids_to_check = $_POST['ids'];
    $ids_clean = array_map(function($id) use ($conn) { return pg_escape_string($conn, $id); }, $ids_to_check);
    $ids_string = "'" . implode("','", $ids_clean) . "'";
    $where_clause = "WHERE t.id_transaksi IN ($ids_string)";
} elseif (isset($_GET['id'])) {
    // Kasus: Print Satu Nota Full
    $id_trx = pg_escape_string($conn, $_GET['id']);
    $ids_to_check[] = $id_trx;
    $where_clause = "WHERE t.id_transaksi = '$id_trx'";
} else {
    echo "<script>alert('Data transaksi tidak ditemukan!'); history.back();</script>";
    exit;
}

// --- 2. TENTUKAN JUDUL INVOICE ---
// Cek apakah semua ID dalam array itu sama (unik)
$unique_ids = array_unique($ids_to_check);

if (count($unique_ids) === 1) {
    // Jika isinya cuma 1 jenis ID Transaksi (misal T035 semua), pakai ID itu
    $mode_judul = "#" . reset($unique_ids);
} else {
    // Jika isinya campuran (T035 dan T036), baru pakai #GABUNGAN
    $mode_judul = "#GABUNGAN";
}

// --- 3. QUERY DATA UTAMA ---
$query = "SELECT t.*, p.nama AS p_nama, p.hp, p.alamat, pr.nama_produk, pr.harga AS harga_satuan, b.nama_bank, b.no_rekening, b.atas_nama 
          FROM transaksi t 
          JOIN pelanggan p ON t.id_pelanggan=p.id_pelanggan 
          JOIN produk pr ON t.id_produk=pr.id_produk 
          LEFT JOIN bank_akun b ON t.id_bank = b.id_bank 
          $where_clause 
          ORDER BY t.waktu_order ASC";

$result = pg_query($conn, $query);
if (!$result || pg_num_rows($result) == 0) { die("Data invoice tidak ditemukan."); }

$first_row = pg_fetch_assoc($result);
pg_result_seek($result, 0); 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Invoice <?= $mode_judul ?></title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; font-family: sans-serif; -webkit-print-color-adjust: exact; }
        .invoice-box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .payment-box { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 8px 12px; font-size: 0.85rem; }
        @media print {
            @page { margin: 0; size: auto; } body { background: white; margin: 1cm; }
            .no-print { display: none !important; } .invoice-box { box-shadow: none; border: none; padding: 0; width: 100%; } .container { max-width: 100%; padding: 0; }
        }
    </style>
</head>
<body>
<div class="container py-3">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="text-end mb-3 no-print gap-2 d-flex justify-content-end">
                <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
                <button onclick="window.print()" class="btn btn-sm btn-primary rounded-pill px-3"><i class="bi bi-printer-fill me-1"></i>Cetak</button>
            </div>
            <div class="invoice-box">
                <div class="d-flex justify-content-between align-items-start mb-3 border-bottom pb-2">
                    <div><h4 class="fw-bold text-primary mb-0">Printing</h4><p class="text-muted small mb-0">Jl. Printing Digital No. XXX, Jakarta</p><p class="text-muted small mb-0">WA: 08XX-XXXX-XXXX</p></div>
                    <div class="text-end"><h3 class="fw-bold text-dark mb-0">INVOICE</h3><span class="text-muted fw-bold small"><?= $mode_judul ?></span></div>
                </div>
                <div class="row mb-3">
                    <div class="col-6"><small class="text-muted fw-bold text-uppercase">Kepada:</small><div class="fw-bold text-dark"><?= $first_row['p_nama'] ?></div><div class="small text-muted text-truncate"><?= $first_row['alamat'] ?? '-' ?> | <?= $first_row['hp'] ?? '-' ?></div></div>
                    <div class="col-6 text-end"><div class="small"><span class="fw-bold">Tgl:</span> <?= date('d/m/y H:i', strtotime($first_row['waktu_order'])) ?><br><span class="badge bg-<?= $first_row['status_pembayaran'] == 'Lunas' ? 'success' : 'danger' ?> text-uppercase" style="font-size: 0.7rem;"><?= $first_row['status_pembayaran'] ?></span></div></div>
                </div>
                <div class="mb-3"><div class="payment-box"><div class="d-flex align-items-center"><small class="text-muted fw-bold text-uppercase me-2">Bayar via:</small><div class="small"><?php if (($first_row['metode_pembayaran'] ?? '') == 'Transfer'): ?><span><i class="bi bi-bank me-1"></i> <strong><?= $first_row['nama_bank'] ?></strong></span><span class="mx-2">|</span><span><strong><?= $first_row['no_rekening'] ?></strong></span><?php else: ?><span><i class="bi bi-cash me-1"></i> Tunai (Cash)</span><?php endif; ?></div></div></div></div>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered border-light mb-0 table-sm">
                        <thead class="table-light"><tr><th class="py-2 ps-2">Produk</th><th class="py-2 text-center">Qty</th><th class="py-2 text-end">Harga</th><th class="py-2 text-end pe-2">Subtotal</th></tr></thead>
                        <tbody>
                            <?php $grand_total = 0; while($row = pg_fetch_assoc($result)): $grand_total += $row['total_harga']; ?>
                            <tr>
                                <td class="ps-2 py-2"><span class="fw-bold text-dark small"><?= $row['nama_produk'] ?></span><?php if(isset($_GET['item_id'])): ?><br><small class="text-muted" style="font-size: 0.65rem;">Ref: #<?= $row['id_transaksi'] ?></small><?php endif; ?><?php if($row['panjang'] > 0): ?><br><small class="text-muted fst-italic" style="font-size: 0.7rem;">Ukuran: <?= floatval($row['panjang']) ?>m x <?= floatval($row['lebar']) ?>m</small><?php endif; ?></td>
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
                        <div class="d-flex justify-content-between align-items-center mb-5 border-bottom pb-2"><span class="fw-bold text-secondary">TOTAL</span><span class="fw-bold text-primary fs-5">Rp <?= number_format($grand_total, 0, ',','.') ?></span></div>
                        <div class="text-center mt-4"><p class="mb-5 fw-bold small text-muted">Hormat Kami,</p><br><p class="fw-bold mb-0 text-decoration-underline text-dark small">XXX Printing</p></div>
                    </div>
                </div>
                <div class="text-center mt-3 pt-2 border-top"><small class="text-muted fst-italic" style="font-size: 0.7rem;">Terima kasih.</small></div>
            </div>
        </div>
    </div>
</div>
</body>
</html>