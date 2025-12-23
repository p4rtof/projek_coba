<?php
include '../../config/koneksi.php';
include '../../auth/auth.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit(); }
$id = $_GET['id'];
$profil = pg_fetch_assoc(pg_query($conn, "SELECT * FROM pelanggan WHERE id_pelanggan = '$id'"));
$riwayat = pg_query($conn, "SELECT t.*, pr.nama_produk FROM transaksi t JOIN produk pr ON t.id_produk=pr.id_produk WHERE t.id_pelanggan='$id' ORDER BY t.waktu_order DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Riwayat - <?= $profil['nama'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <?php include '../../components/navbar.php'; ?>
    <div class="container py-4">
        <a href="index.php" class="btn btn-outline-secondary btn-sm mb-3"><i class="bi bi-arrow-left"></i> Kembali</a>
        <div class="card shadow-sm border-0 border-start border-5 border-primary mb-4">
            <div class="card-body">
                <h4 class="fw-bold text-primary mb-0"><?= $profil['nama'] ?></h4>
                <small class="text-muted">HP: <?= $profil['hp'] ?> | Alamat: <?= $profil['alamat'] ?></small>
            </div>
        </div>
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-bold">Riwayat Transaksi</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Tanggal</th><th>Produk</th><th>Total</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php while($r = pg_fetch_assoc($riwayat)): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($r['waktu_order'])) ?></td>
                            <td><?= $r['nama_produk'] ?> (<?= $r['jumlah'] ?> pcs)</td>
                            <td class="fw-bold">Rp <?= number_format($r['total_harga']) ?></td>
                            <td><span class="badge bg-<?= $r['status_pembayaran']=='Lunas'?'success':'danger' ?>"><?= $r['status_pembayaran'] ?></span></td>
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