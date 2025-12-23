<?php
include 'config/koneksi.php';
include 'auth/auth.php';

// --- LOGIC WIDGET DASHBOARD ---
$q_omset = pg_fetch_assoc(pg_query($conn, "SELECT SUM(total_harga) AS total FROM transaksi WHERE waktu_order::date = CURRENT_DATE AND status_pembayaran = 'Lunas'"));
$omset_hari_ini = $q_omset['total'] ?? 0;

$q_order = pg_fetch_assoc(pg_query($conn, "SELECT COUNT(*) AS total FROM transaksi WHERE waktu_order::date = CURRENT_DATE"));
$jumlah_order = $q_order['total'] ?? 0;

$q_utang = pg_fetch_assoc(pg_query($conn, "SELECT COUNT(*) AS total FROM transaksi WHERE status_pembayaran = 'Belum Lunas'"));
$jumlah_utang = $q_utang['total'] ?? 0;

// --- LOGIC SILENT UPDATE & DELETE ---
if (isset($_GET['naik_status'])) {
    $id = $_GET['id'];
    $st = $_GET['status'];
    $new = ($st == 'Proses') ? 'Selesai' : (($st == 'Selesai') ? 'Done' : '');
    if ($new) pg_query($conn, "UPDATE transaksi SET status_order = '$new' WHERE id_transaksi = '$id'"); 
    header("Location: index.php"); exit();
}
if (isset($_GET['hapus'])) {
    pg_query($conn, "DELETE FROM transaksi WHERE id_transaksi = '{$_GET['hapus']}'");
    header("Location: index.php"); exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Dashboard Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; font-family: sans-serif; }
        .card-hover:hover { transform: translateY(-5px); transition: 0.3s; }
        .table thead th { background-color: #f8f9fc; color: #4e73df; font-weight: 700; border-bottom: 2px solid #e3e6f0; }
    </style>
</head>
<body>
    <?php include 'components/navbar.php'; ?>

    <div class="container py-5">
        <div class="row mb-4 g-4">
            <div class="col-md-4">
                <div class="card card-hover shadow-sm h-100 border-start border-4 border-primary">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase text-primary fw-bold small">Omset Hari Ini</div>
                            <div class="h3 mb-0 fw-bold">Rp <?= number_format($omset_hari_ini, 0, ',', '.') ?></div>
                        </div>
                        <i class="bi bi-cash-coin fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-hover shadow-sm h-100 border-start border-4 border-success">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase text-success fw-bold small">Transaksi Hari Ini</div>
                            <div class="h3 mb-0 fw-bold"><?= $jumlah_order ?> Order</div>
                        </div>
                        <i class="bi bi-cart-check fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-hover shadow-sm h-100 border-start border-4 border-warning">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase text-warning fw-bold small">Belum Lunas</div>
                            <div class="h3 mb-0 fw-bold"><?= $jumlah_utang ?> Data</div>
                        </div>
                        <i class="bi bi-exclamation-triangle fs-1 text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row align-items-center mb-4 mt-5">
            <div class="col-md-4">
                <h4 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2"></i>Riwayat Transaksi</h4>
            </div>
            
            <div class="col-md-8 text-md-end mt-3 mt-md-0 d-flex gap-2 justify-content-md-end flex-wrap">
                <form method="GET" class="d-flex gap-2 flex-grow-1 justify-content-end">
                    
                    <input type="date" name="tgl" class="form-control shadow-sm" style="max-width: 160px;" 
                           value="<?= $_GET['tgl'] ?? '' ?>" title="Filter Tanggal">
                    
                    <div class="input-group shadow-sm" style="max-width: 300px;">
                        <input type="text" name="q" class="form-control" placeholder="Cari ID, Nama, Produk..." 
                               value="<?= $_GET['q'] ?? '' ?>">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                    </div>

                    <?php if(isset($_GET['q']) || isset($_GET['tgl'])): ?>
                        <a href="index.php" class="btn btn-light border shadow-sm" title="Reset Filter"><i class="bi bi-x-lg text-danger"></i></a>
                    <?php endif; ?>
                </form>
                
                <a href="modules/transaksi/baru.php" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center">
                    <i class="bi bi-plus-lg me-2"></i>Order
                </a>
            </div>
        </div>

        <div class="card shadow border-0 rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3">ID</th>
                            <th class="py-3">Tanggal</th>
                            <th class="py-3 text-start">Pelanggan</th>
                            <th class="py-3 text-start">Produk</th>
                            <th class="py-3">Qty</th>
                            <th class="py-3">Total</th>
                            <th class="py-3">Status</th>
                            <th class="py-3">Progress</th>
                            <th class="py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // --- LOGIC PENCARIAN & FILTER ---
                        $keyword = $_GET['q'] ?? '';
                        $tanggal = $_GET['tgl'] ?? '';
                        $conditions = [];

                        // 1. Filter Keyword (Nama, Produk, ID)
                        if (!empty($keyword)) {
                            $safe_key = pg_escape_string($conn, $keyword);
                            $conditions[] = "(
                                t.id_transaksi ILIKE '%$safe_key%' OR 
                                p.nama ILIKE '%$safe_key%' OR 
                                pr.nama_produk ILIKE '%$safe_key%'
                            )";
                        }

                        // 2. Filter Tanggal Spesifik
                        if (!empty($tanggal)) {
                            $safe_tgl = pg_escape_string($conn, $tanggal);
                            // Ambil hanya tanggalnya saja dari timestamp (DATE())
                            $conditions[] = "DATE(t.waktu_order) = '$safe_tgl'";
                        }

                        // Gabungkan Kondisi
                        $where_sql = "";
                        if (count($conditions) > 0) {
                            $where_sql = "WHERE " . implode(" AND ", $conditions);
                        }

                        // Query Utama
                        $query = "SELECT t.*, p.nama AS p_nama, pr.nama_produk 
                                  FROM transaksi t 
                                  JOIN pelanggan p ON t.id_pelanggan=p.id_pelanggan 
                                  JOIN produk pr ON t.id_produk=pr.id_produk 
                                  $where_sql
                                  ORDER BY t.waktu_order DESC LIMIT 50";
                        
                        $q = pg_query($conn, $query);
                        
                        if (pg_num_rows($q) > 0):
                            while ($r = pg_fetch_assoc($q)):
                        ?>
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td class="fw-bold text-primary"><?= $r['id_transaksi'] ?></td>
                            <td class="small text-muted fw-bold"><?= date('d/m/y H:i', strtotime($r['waktu_order'])) ?></td>
                            <td class="text-start fw-bold"><?= $r['p_nama'] ?></td>
                            <td class="text-start"><?= $r['nama_produk'] ?></td>
                            <td><span class="badge bg-secondary rounded-pill"><?= $r['jumlah'] ?></span></td>
                            <td class="fw-bold text-success">Rp <?= number_format($r['total_harga'], 0, ',', '.') ?></td>
                            <td>
                                <span class="badge rounded-pill bg-<?= $r['status_pembayaran']=='Lunas'?'success':'danger' ?> bg-opacity-10 text-<?= $r['status_pembayaran']=='Lunas'?'success':'danger' ?> border border-<?= $r['status_pembayaran']=='Lunas'?'success':'danger' ?> px-3">
                                    <?= $r['status_pembayaran'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if($r['status_order']!='Done'): ?>
                                    <a href="index.php?naik_status=true&id=<?= $r['id_transaksi'] ?>&status=<?= $r['status_order'] ?>" class="btn btn-sm btn-<?= $r['status_order']=='Proses'?'warning':'info' ?> text-white rounded-pill w-100" style="font-size: 0.75rem;">
                                        <?= $r['status_order'] ?> <i class="bi bi-chevron-right"></i>
                                    </a>
                                <?php else: ?>
                                    <div class="text-success fw-bold small"><i class="bi bi-check-all fs-6"></i> DONE</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="modules/transaksi/edit.php?id=<?= $r['id_transaksi'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                                    <a href="index.php?hapus=<?= $r['id_transaksi'] ?>" onclick="return confirm('Yakin hapus transaksi ini?')" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash-fill"></i></a>
                                    <a href="modules/transaksi/invoice.php?id=<?= $r['id_transaksi'] ?>" class="btn btn-sm btn-outline-dark" title="Print Invoice"><i class="bi bi-printer-fill"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-search fs-1 d-block mb-2 opacity-50"></i>
                                    Tidak ada data transaksi ditemukan.
                                    <?php if(!empty($keyword)) echo "<br>Keyword: <b>$keyword</b>"; ?>
                                    <?php if(!empty($tanggal)) echo "<br>Tanggal: <b>".date('d M Y', strtotime($tanggal))."</b>"; ?>
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