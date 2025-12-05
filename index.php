<?php 
include 'koneksi.php'; 

// --- 1. LOGIC UPDATE STATUS (Tombol Ajaib) ---
if (isset($_GET['naik_status'])) {
    $id = $_GET['id'];
    $status_sekarang = $_GET['status'];
    $status_baru = '';

    if ($status_sekarang == 'Proses') $status_baru = 'Selesai';
    elseif ($status_sekarang == 'Selesai') $status_baru = 'Diambil';
    elseif ($status_sekarang == 'Diambil') $status_baru = 'Done';

    if ($status_baru != '') {
        pg_query($conn, "UPDATE transaksi SET status_order = '$status_baru' WHERE id = '$id'");
    }
    header("Location: index.php");
    exit();
}

// --- 2. LOGIC HAPUS ---
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    pg_query($conn, "DELETE FROM transaksi WHERE id = '$id_hapus'");
    echo "<script>alert('Data dihapus!'); window.location='index.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Dashboard Admin - Zaddy Printing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body{background:#f4f6f9} 
        .btn-status{transition:0.3s;} 
        .btn-status:hover{transform:scale(1.05);}
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-printer-fill"></i> Zaddy Printing</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link active" href="index.php">Dashboard</a>
                <a class="nav-link" href="pelanggan.php">Pelanggan</a>
                <a class="nav-link" href="produk.php">Produk</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-5"> <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-0">📊 Riwayat Transaksi</h3>
                <p class="text-muted small">Monitor semua pesanan yang masuk hari ini.</p>
            </div>
            <a href="transaksi_baru.php" class="btn btn-success btn-lg shadow fw-bold">
                <i class="bi bi-plus-lg"></i> Tambah Transaksi Baru
            </a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center table-bordered">
                        <thead class="table-light">
                            <tr style="height: 50px; vertical-align: middle;">
                                <th>ID Order</th>
                                <th>Tanggal</th>
                                <th class="text-start ps-3" style="width: 20%;">Pelanggan</th>
                                <th class="text-start ps-3" style="width: 25%;">Detail Produk</th>
                                <th>Qty</th>
                                <th>Total Harga</th>
                                <th>Pembayaran</th>
                                <th>Status Pengerjaan</th>
                                <th style="width: 10%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Query Join Lengkap
                            $q_list = pg_query($conn, "SELECT t.*, p.nama AS p_nama, pr.nama_produk 
                                                       FROM transaksi t 
                                                       JOIN pelanggan p ON t.pelanggan_id=p.id 
                                                       JOIN produk pr ON t.produk_id=pr.id 
                                                       ORDER BY t.id DESC");
                            
                            while ($r = pg_fetch_assoc($q_list)) :
                                $id_keren = "T" . sprintf("%03d", $r['id']);
                            ?>
                            <tr>
                                <td class="fw-bold text-primary"><?= $id_keren ?></td>
                                <td class="small text-muted"><?= date('d/m/Y', strtotime($r['tgl_order'])) ?></td>
                                
                                <td class="text-start ps-3 fw-bold"><?= $r['p_nama'] ?></td>
                                
                                <td class="text-start ps-3 text-muted">
                                    <i class="bi bi-box-seam"></i> <?= $r['nama_produk'] ?>
                                </td>
                                
                                <td><span class="badge bg-secondary rounded-pill"><?= $r['jumlah'] ?></span></td>
                                
                                <td class="fw-bold text-success">Rp <?= number_format($r['total_harga'], 0, ',', '.') ?></td>
                                
                                <td>
                                    <?php if($r['status_pembayaran'] == 'Lunas'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Lunas</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="bi bi-exclamation-circle"></i> Belum</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php 
                                    $st = $r['status_order'];
                                    $cls = 'secondary'; $icn = 'gear';
                                    
                                    if($st=='Proses') { $cls='warning text-dark'; $icn='hourglass-split'; }
                                    elseif($st=='Selesai') { $cls='info text-white'; $icn='box-seam'; }
                                    elseif($st=='Diambil') { $cls='primary'; $icn='person-check'; }
                                    elseif($st=='Done') { $cls='success'; $icn='check-circle-fill'; }
                                    ?>

                                    <?php if($st != 'Done'): ?>
                                        <a href="index.php?naik_status=true&id=<?= $r['id'] ?>&status=<?= $st ?>" 
                                           class="btn btn-sm btn-<?= $cls ?> btn-status rounded-pill px-3 w-100 fw-bold">
                                            <i class="bi bi-<?= $icn ?>"></i> <?= $st ?>
                                        </a>
                                    <?php else: ?>
                                        <div class="badge bg-success px-3 py-2 w-100"><i class="bi bi-check-all"></i> DONE</div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="btn-group">
                                        <a href="edit_transaksi.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <a href="index.php?hapus=<?= $r['id'] ?>" onclick="return confirm('Yakin hapus?')" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</body>
</html>