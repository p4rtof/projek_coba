<?php 
include 'koneksi.php'; 
include 'auth.php';

// --- LOGIC WIDGET DASHBOARD (Query Ringkasan) ---
// 1. Total Omset Hari Ini (Kolom waktu_order sudah benar)
$q_omset = pg_fetch_assoc(pg_query($conn, "SELECT SUM(total_harga) AS total FROM transaksi WHERE waktu_order::date = CURRENT_DATE AND status_pembayaran = 'Lunas'"));
$omset_hari_ini = $q_omset['total'] ?? 0;

// 2. Jumlah Order Hari Ini (Kolom waktu_order sudah benar)
$q_order = pg_fetch_assoc(pg_query($conn, "SELECT COUNT(*) AS total FROM transaksi WHERE waktu_order::date = CURRENT_DATE"));
$jumlah_order = $q_order['total'] ?? 0;

// 3. Belum Lunas (Semua Waktu)
$q_utang = pg_fetch_assoc(pg_query($conn, "SELECT COUNT(*) AS total FROM transaksi WHERE status_pembayaran = 'Belum Lunas'"));
$jumlah_utang = $q_utang['total'] ?? 0;

// --- LOGIC SILENT UPDATE & DELETE ---
if (isset($_GET['naik_status'])) {
    $id = $_GET['id'];
    $st = $_GET['status'];
    $new = ($st=='Proses')?'Selesai':(($st=='Selesai')?'Diambil':(($st=='Diambil')?'Done':''));
    if($new) pg_query($conn, "UPDATE transaksi SET status_order = '$new' WHERE id_transaksi = '$id'"); // ID kolom ganti ke id_transaksi dan dikutip
    header("Location: index.php"); exit();
}
if (isset($_GET['hapus'])) {
    // ID kolom ganti ke id_transaksi dan dikutip
    pg_query($conn, "DELETE FROM transaksi WHERE id_transaksi = '{$_GET['hapus']}'"); 
    header("Location: index.php"); exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Dashboard Admin - Zaddy Printing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { background: #f0f2f5; font-family: 'Poppins', sans-serif; }
        .navbar { background: linear-gradient(90deg, #0d6efd 0%, #0a58ca 100%); }
        .card-hover { transition: all 0.3s ease; border: none; }
        .card-hover:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
        .btn-status { transition: 0.2s; font-size: 0.85rem; }
        .btn-status:hover { transform: scale(1.05); }
        .bg-gradient-primary { background: linear-gradient(45deg, #4e73df, #224abe); color: white; }
        .bg-gradient-success { background: linear-gradient(45deg, #1cc88a, #13855c); color: white; }
        .bg-gradient-warning { background: linear-gradient(45deg, #f6c23e, #dda20a); color: white; }
        .table thead th { background-color: #f8f9fc; color: #4e73df; font-weight: 700; border-bottom: 2px solid #e3e6f0; }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

    <div class="container py-5">
        
        <div class="row mb-4 g-4">
            <div class="col-md-4">
                <div class="card card-hover shadow-sm h-100 bg-white border-start border-4 border-primary">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-uppercase text-primary fw-bold small mb-1">Omset Hari Ini</div>
                            <div class="h3 mb-0 fw-bold text-gray-800">Rp <?= number_format($omset_hari_ini, 0, ',', '.') ?></div>
                        </div>
                        <div class="fs-1 text-gray-300 text-primary opacity-25"><i class="bi bi-cash-coin"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-hover shadow-sm h-100 bg-white border-start border-4 border-success">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-uppercase text-success fw-bold small mb-1">Transaksi Hari Ini</div>
                            <div class="h3 mb-0 fw-bold text-gray-800"><?= $jumlah_order ?> Order</div>
                        </div>
                        <div class="fs-1 text-gray-300 text-success opacity-25"><i class="bi bi-cart-check"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-hover shadow-sm h-100 bg-white border-start border-4 border-warning">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-uppercase text-warning fw-bold small mb-1">Belum Lunas</div>
                            <div class="h3 mb-0 fw-bold text-gray-800"><?= $jumlah_utang ?> Data</div>
                        </div>
                        <div class="fs-1 text-gray-300 text-warning opacity-25"><i class="bi bi-exclamation-triangle"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 mt-5">
            <h4 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat Transaksi Terkini</h4>
            <a href="transaksi_baru.php" class="btn btn-primary shadow-sm rounded-pill px-4 py-2 fw-bold">
                <i class="bi bi-plus-lg me-2"></i>Order Baru
            </a>
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
                                <th class="py-3 text-start ps-4">Produk</th>
                                <th class="py-3 text-start ps-4">QTY</th>
                                <th class="py-3">Total</th>
                                <th class="py-3">Status</th>
                                <th class="py-3">Progress</th>
                                <th class="py-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // FIX: Perbaiki kunci JOIN dan ORDER BY ke id_transaksi
                            $q = pg_query($conn, "SELECT t.*, p.nama AS p_nama, pr.nama_produk FROM transaksi t JOIN pelanggan p ON t.id_pelanggan=p.id_pelanggan JOIN produk pr ON t.id_produk=pr.id_produk ORDER BY t.id_transaksi DESC LIMIT 50");
                            while ($r = pg_fetch_assoc($q)) :
                            ?>
                            <tr style="border-bottom: 1px solid #f0f0f0;">
                                <td class="fw-bold text-primary"><?= $r['id_transaksi'] ?></td> <td class="small text-muted"><?= date('d M y (H:i)', strtotime($r['waktu_order'])) ?></td> <td class="text-start ps-4 fw-bold"><?= $r['p_nama'] ?></td>
                                <td class="text-start fw-semibold"><?= $r['nama_produk'] ?></td>
                                <td class="text-center fw-semibold"><?= $r['jumlah'] ?> pcs</td>
                                <td class="fw-bold text-success">Rp <?= number_format($r['total_harga'], 0, ',','.') ?></td>
                                <td>
                                    <?= ($r['status_pembayaran'] == 'Lunas') 
                                        ? '<span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success px-3">Lunas</span>' 
                                        : '<span class="badge rounded-pill bg-danger bg-opacity-10 text-danger border border-danger px-3">Utang</span>' 
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $st = $r['status_order'];
                                    $btn_c = ($st=='Proses')?'warning':(($st=='Selesai')?'info':(($st=='Diambil')?'primary':'success'));
                                    if($st != 'Done'): ?>
                                        <a href="index.php?naik_status=true&id=<?= $r['id_transaksi'] ?>&status=<?= $st ?>" 
                                           class="btn btn-sm btn-<?= $btn_c ?> btn-status rounded-pill w-100 text-white shadow-sm">
                                           <?= $st ?> <i class="bi bi-chevron-right ms-1"></i>
                                        </a>
                                    <?php else: ?>
                                        <div class="text-success fw-bold"><i class="bi bi-check-all fs-5"></i> DONE</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit_transaksi.php?id=<?= $r['id_transaksi'] ?>" class="btn btn-sm btn-light text-primary border rounded-circle" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                                    <a href="index.php?hapus=<?= $r['id_transaksi'] ?>" onclick="return confirm('Hapus?')" class="btn btn-sm btn-light text-danger border rounded-circle" title="Hapus"><i class="bi bi-trash-fill"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
</body>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</html>

</body>
</html>