<?php
include '../../config/koneksi.php';
include '../../auth/auth.php';

// Cek ID Pelanggan
if (!isset($_GET['id'])) { header("Location: index.php"); exit(); }
$id = $_GET['id'];

// Ambil Data Profil Pelanggan
$profil = pg_fetch_assoc(pg_query($conn, "SELECT * FROM pelanggan WHERE id_pelanggan = '$id'"));

// Ambil Riwayat Transaksi (t.id_transaksi WAJIB diambil buat link invoice)
$riwayat = pg_query($conn, "SELECT t.*, pr.nama_produk FROM transaksi t JOIN produk pr ON t.id_produk=pr.id_produk WHERE t.id_pelanggan='$id' ORDER BY t.waktu_order DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Riwayat - <?= $profil['nama'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        /* Bikin tabel lebih lega */
        .table > :not(caption) > * > * { padding: 1rem 1rem; }
        /* Hover effect baris tabel */
        tr:hover { background-color: #f8f9fa; }
        /* Icon box buat profil */
        .avatar-box {
            width: 50px; height: 50px; 
            background: #e0e7ff; color: #4338ca;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
        }
    </style>
</head>
<body class="bg-light">
    
    <?php include '../../components/navbar.php'; ?>
    
    <div class="container py-4">
        
        <div class="mb-3">
            <a href="index.php" class="btn btn-outline-secondary btn-sm border-0 text-dark fw-bold">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Data Pelanggan
            </a>
        </div>

        <div class="card shadow-sm border-0 mb-4 rounded-4 overflow-hidden">
            <div class="card-body p-4 d-flex align-items-center gap-3">
                <div class="avatar-box flex-shrink-0">
                    <i class="bi bi-person-circle"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-dark mb-1"><?= $profil['nama'] ?></h4>
                    <div class="text-muted small">
                        <i class="bi bi-telephone me-1"></i> <?= $profil['hp'] ?> 
                        <span class="mx-2">|</span> 
                        <i class="bi bi-geo-alt me-1"></i> <?= $profil['alamat'] ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 px-4 border-bottom">
                <h6 class="mb-0 fw-bold text-primary">
                    <i class="bi bi-clock-history me-2"></i>Riwayat Pesanan
                </h6>
            </div>
            
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">Tanggal Order</th>
                            <th>Produk</th>
                            <th>Total Belanja</th>
                            <th>Status Pembayaran</th>
                            <th class="text-end pe-4">Cetak</th> </tr>
                    </thead>
                    <tbody>
                        <?php if(pg_num_rows($riwayat) > 0): ?>
                            <?php while($r = pg_fetch_assoc($riwayat)): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= date('d M Y', strtotime($r['waktu_order'])) ?></div>
                                    <div class="small text-muted"><?= date('H:i', strtotime($r['waktu_order'])) ?> WIB</div>
                                </td>
                                
                                <td>
                                    <span class="fw-semibold text-dark"><?= $r['nama_produk'] ?></span>
                                    <div class="small text-muted">Jumlah: <?= $r['jumlah'] ?> pcs</div>
                                </td>
                                
                                <td class="fw-bold text-success">
                                    Rp <?= number_format($r['total_harga'], 0, ',', '.') ?>
                                </td>
                                
                                <td>
                                    <?php if($r['status_pembayaran'] == 'Lunas'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill border border-success border-opacity-25">
                                            <i class="bi bi-check-circle-fill me-1"></i> Lunas
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill border border-danger border-opacity-25">
                                            <i class="bi bi-exclamation-circle-fill me-1"></i> Belum Lunas
                                        </span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-end pe-4">
                                    <a href="../transaksi/invoice.php?id=<?= $r['id_transaksi'] ?>" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-3" title="Cetak Nota">
                                        <i class="bi bi-printer me-1"></i> Invoice
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted fst-italic">
                                    Belum ada riwayat transaksi untuk pelanggan ini.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>