<?php
include 'koneksi.php';
include 'auth.php';

if (!isset($_GET['id'])) {
    header("Location: pelanggan.php");
    exit();
}

$id_pelanggan = $_GET['id'];

// 1. Ambil Data Profil Pelanggan
$q_profil = pg_query($conn, "SELECT * FROM pelanggan WHERE id_pelanggan = '$id_pelanggan'");
$profil = pg_fetch_assoc($q_profil);

if (!$profil) {
    echo "Pelanggan tidak ditemukan.";
    exit();
}

// 2. Ambil Riwayat Transaksi Pelanggan Tersebut
// Kita JOIN ke tabel produk untuk ambil nama produk
$q_riwayat = pg_query($conn, "
    SELECT t.*, pr.nama_produk, pr.jenis_satuan 
    FROM transaksi t
    JOIN produk pr ON t.id_produk = pr.id_produk
    WHERE t.id_pelanggan = '$id_pelanggan'
    ORDER BY t.waktu_order DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Riwayat - <?= $profil['nama'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<?php include 'navbar.php'; ?>

<div class="container py-5">
    
    <div class="row mb-4">
        <div class="col-md-12">
            <a href="pelanggan.php" class="btn btn-outline-secondary btn-sm mb-3"><i class="bi bi-arrow-left"></i> Kembali</a>
            <div class="card shadow-sm border-0 border-start border-5 border-primary">
                <div class="card-body">
                    <h4 class="fw-bold text-primary mb-1"><?= $profil['nama'] ?></h4>
                    <div class="text-muted mb-2"><small>ID: <?= $profil['id_pelanggan'] ?></small></div>
                    <div class="row">
                        <div class="col-md-6">
                            <i class="bi bi-telephone me-2"></i> <?= $profil['hp'] ?>
                            </div>
                        <div class="col-md-6">
                            <i class="bi bi-geo-alt me-2"></i> <?= $profil['alamat'] ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h5 class="fw-bold mb-3"><i class="bi bi-clock-history"></i> Riwayat Transaksi</h5>
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 py-3">Tanggal</th>
                            <th>Produk</th>
                            <th>Detail Order</th>
                            <th>Total</th>
                            <th>Status Bayar</th>
                            <th>Status Order</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (pg_num_rows($q_riwayat) > 0): ?>
                            <?php while ($row = pg_fetch_assoc($q_riwayat)): ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold d-block"><?= date('d M Y', strtotime($row['waktu_order'])) ?></span>
                                    <small class="text-muted"><?= date('H:i', strtotime($row['waktu_order'])) ?></small>
                                </td>
                                <td>
                                    <span class="fw-bold text-primary"><?= $row['nama_produk'] ?></span>
                                </td>
                                <td>
                                    <div class="d-flex flex-column small">
                                        <span class="fw-bold">Qty: <?= $row['jumlah'] ?></span>
                                        <?php if($row['jenis_satuan'] == 'Meter' || ($row['panjang'] > 0)): ?>
                                            <span class="text-muted">Ukuran: <?= floatval($row['panjang']) ?>m x <?= floatval($row['lebar']) ?>m</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="fw-bold">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                                <td>
                                    <span class="badge rounded-pill bg-<?= $row['status_pembayaran'] == 'Lunas' ? 'success' : 'danger' ?>">
                                        <?= $row['status_pembayaran'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= $row['status_order'] ?></span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">Belum ada riwayat transaksi.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</body>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

</html>