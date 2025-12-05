<?php
include 'koneksi.php';

$tampil = $_GET['tampil'] ?? 'proses'; // Default tampil: proses (Belum Diambil)

$judul = ($tampil == 'utang') ? 'Daftar Transaksi Belum Lunas' : 'Daftar Order Belum Diambil';

if ($tampil == 'utang') {
    // REKAP BELUM LUNAS (Hutang Customer)
    $kondisi = "t.status_pembayaran = 'Belum Lunas'";
} else {
    // REKAP BELUM DIAMBIL (Order yang masih Proses/Selesai/Diambil, belum Done)
    $kondisi = "t.status_order != 'Done'";
}

$q = pg_query($conn, "
    SELECT t.*, p.nama AS p_nama, pr.nama_produk 
    FROM transaksi t 
    JOIN pelanggan p ON t.pelanggan_id=p.id 
    JOIN produk pr ON t.produk_id=pr.id 
    WHERE $kondisi
    ORDER BY t.tgl_order ASC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title><?= $judul ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style> /* ... (Ambil Style dari index.php) ... */ </style>
</head>
<body>
    <?php include 'navbar.php'; // Atau copy navbar dari index.php ?>

    <div class="container py-5">
        <h4 class="fw-bold text-dark mb-4">
            <i class="bi bi-list-check me-2"></i> <?= $judul ?>
        </h4>
        
        <div class="mb-3 d-flex gap-2">
            <a href="laporan_order.php?tampil=proses" class="btn btn-sm <?= $tampil == 'proses' ? 'btn-primary' : 'btn-outline-primary' ?> fw-bold">Belum Diambil</a>
            <a href="laporan_order.php?tampil=utang" class="btn btn-sm <?= $tampil == 'utang' ? 'btn-warning' : 'btn-outline-warning' ?> fw-bold">Belum Lunas</a>
        </div>

        <div class="card shadow border-0 rounded-4 overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead>
                            <tr>
                                <th class="py-3">ID</th>
                                <th class="py-3">Tanggal</th>
                                <th class="py-3 text-start ps-4">Pelanggan</th>
                                <th class="py-3">Total</th>
                                <th class="py-3">Pembayaran</th>
                                <th class="py-3">Progress</th>
                                <th class="py-3">Invoice</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_rekap = 0;
                            while ($r = pg_fetch_assoc($q)) :
                                if ($r['status_pembayaran'] == 'Belum Lunas') $total_rekap += $r['total_harga'];
                            ?>
                            <tr>
                                <td class="fw-bold text-primary">#<?= sprintf("%03d", $r['id']) ?></td>
                                <td><?= date('d M Y', strtotime($r['tgl_order'])) ?></td>
                                <td class="text-start ps-4 fw-bold"><?= $r['p_nama'] ?></td>
                                <td class="fw-bold text-success">Rp <?= number_format($r['total_harga'], 0, ',','.') ?></td>
                                <td><span class="badge rounded-pill bg-<?= $r['status_pembayaran'] == 'Lunas' ? 'success' : 'danger' ?>"><?= $r['status_pembayaran'] ?></span></td>
                                <td><span class="badge bg-secondary"><?= $r['status_order'] ?></span></td>
                                <td><a href="invoice.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-info text-white"><i class="bi bi-receipt"></i></a></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <?php if ($tampil == 'utang'): // Tampilkan Total Hanya untuk Belum Lunas ?>
                        <tfoot>
                             <tr>
                                <td colspan="3" class="text-end fw-bold">TOTAL PIUTANG</td>
                                <td colspan="4" class="text-danger fw-bold fs-5">Rp <?= number_format($total_rekap, 0, ',','.') ?></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>