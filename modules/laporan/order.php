<?php
include '../../config/koneksi.php';
include '../../auth/auth.php';

$filter = $_GET['tampil'] ?? 'proses';
$where = ($filter == 'utang') ? "status_pembayaran = 'Belum Lunas'" : "status_order != 'Done'";
$judul = ($filter == 'utang') ? "Belum Lunas" : "Order Dalam Proses";

$q = pg_query($conn, "SELECT t.*, p.nama, pr.nama_produk FROM transaksi t JOIN pelanggan p ON t.id_pelanggan=p.id_pelanggan JOIN produk pr ON t.id_produk=pr.id_produk WHERE $where ORDER BY t.waktu_order ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Laporan Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <?php include '../../components/navbar.php'; ?>
    <div class="container py-4">
        <h4 class="fw-bold mb-3"><?= $judul ?></h4>
        <div class="mb-3">
            <a href="order.php?tampil=proses" class="btn btn-sm btn-outline-primary">Proses</a>
            <a href="order.php?tampil=utang" class="btn btn-sm btn-outline-warning">Utang</a>
        </div>
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>ID</th><th>Pelanggan</th><th>Produk</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php while($r = pg_fetch_assoc($q)): ?>
                        <tr>
                            <td><?= $r['id_transaksi'] ?></td>
                            <td><?= $r['nama'] ?></td>
                            <td><?= $r['nama_produk'] ?></td>
                            <td>Rp <?= number_format($r['total_harga']) ?></td>
                            <td><?= $r['status_pembayaran'] ?> / <?= $r['status_order'] ?></td>
                            <td><a href="../transaksi/invoice.php?id=<?= $r['id_transaksi'] ?>" class="btn btn-sm btn-dark"><i class="bi bi-printer"></i></a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>