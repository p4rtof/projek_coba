<?php
include '../../config/koneksi.php';
include '../../auth/auth.php';

// --- LOGIC FILTER ---
$filter = $_GET['tampil'] ?? 'proses';

if ($filter == 'utang') {
    $where = "status_pembayaran = 'Belum Lunas'";
    $judul = "Tagihan Belum Lunas";
    $desc  = "Daftar transaksi yang belum dibayar lunas oleh pelanggan.";
} else {
    $where = "status_order != 'Done'";
    $judul = "Order Dalam Proses";
    $desc  = "Daftar pesanan yang sedang dikerjakan atau menunggu diambil.";
}

// Query Data
$query = "SELECT t.*, p.nama, pr.nama_produk 
          FROM transaksi t 
          JOIN pelanggan p ON t.id_pelanggan=p.id_pelanggan 
          JOIN produk pr ON t.id_produk=pr.id_produk 
          WHERE $where 
          ORDER BY t.id_transaksi DESC";

$q = pg_query($conn, $query);
$total_data = pg_num_rows($q);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Laporan Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #E0E7FF;
            --primary-hover: #4338ca;
            --secondary: #64748b;
            --dark: #0f172a;
            --light: #f8fafc;
            --border: #e2e8f0;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
        }
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; color: var(--dark); }
        
        /* Card Style */
        .card-modern {
            background: white; border: 1px solid white; border-radius: 16px;
            box-shadow: var(--card-shadow); transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
        }
        .card-modern:hover { box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02); }

        /* Custom Tabs untuk Filter */
        .nav-pills-custom {
            background: white; padding: 6px; border-radius: 12px;
            border: 1px solid var(--border); display: inline-flex;
        }
        .nav-pills-custom .nav-link {
            border-radius: 8px; color: var(--secondary); font-weight: 600; font-size: 0.9rem; padding: 8px 20px;
            transition: all 0.2s;
        }
        .nav-pills-custom .nav-link.active {
            background-color: var(--primary); color: white; box-shadow: 0 2px 4px rgba(79, 70, 229, 0.2);
        }
        .nav-pills-custom .nav-link:hover:not(.active) { background-color: var(--light); color: var(--primary); }

        /* Table Style */
        .table-custom { margin: 0; }
        .table-custom thead th {
            background: #f8fafc; color: var(--secondary); font-size: 0.75rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.05em; padding: 16px 24px; border-bottom: 1px solid var(--border);
        }
        .table-custom tbody td { padding: 16px 24px; vertical-align: middle; font-size: 0.95rem; border-bottom: 1px solid var(--border); color: var(--dark); }
        .table-custom tbody tr:hover { background-color: #f8fafc; }

        /* Status Badges */
        .badge-status { padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
        .bg-soft-success { background: #dcfce7; color: #166534; }
        .bg-soft-danger { background: #fee2e2; color: #991b1b; }
        .bg-soft-warning { background: #fef3c7; color: #92400e; }
        .bg-soft-info { background: #e0f2fe; color: #075985; }

        /* Button Icon */
        .btn-icon {
            width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center;
            border-radius: 8px; border: 1px solid var(--border); background: white; color: var(--secondary);
            transition: all 0.2s; text-decoration: none;
        }
        .btn-icon:hover { background: var(--light); color: var(--primary); border-color: var(--primary); }
        
        .stats-pill { background: #e0e7ff; color: #4338ca; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; }
    </style>
</head>
<body>
    
    <?php include '../../components/navbar.php'; ?>

    <div class="container pb-5 pt-0 mt-4">
        
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="fw-bold m-0" style="letter-spacing: -0.5px;">Laporan Order</h3>
                <p class="text-secondary m-0 small"><?= $desc ?></p>
            </div>
            <div>
                <span class="stats-pill"><i class="bi bi-list-check me-2"></i><?= $total_data ?> Transaksi</span>
            </div>
        </div>

        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div class="nav nav-pills nav-pills-custom">
                <a class="nav-link <?= ($filter == 'proses') ? 'active' : '' ?>" href="order.php?tampil=proses">
                    <i class="bi bi-hourglass-split me-1"></i> Dalam Proses
                </a>
                <a class="nav-link <?= ($filter == 'utang') ? 'active' : '' ?>" href="order.php?tampil=utang">
                    <i class="bi bi-exclamation-circle me-1"></i> Belum Lunas
                </a>
            </div>
            
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-3 d-none d-md-block">
                <i class="bi bi-printer me-2"></i> Cetak Laporan
            </button>
        </div>

        <div class="card-modern">
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">ID Transaksi</th>
                            <th>Pelanggan</th>
                            <th>Detail Produk</th>
                            <th>Total Tagihan</th>
                            <th class="text-center">Status Pembayaran</th>
                            <th class="text-center">Progress</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($total_data > 0):
                            while($r = pg_fetch_assoc($q)): 
                        ?>
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold text-primary"><?= $r['id_transaksi'] ?></span><br>
                                <small class="text-secondary"><?= date('d M Y', strtotime($r['waktu_order'])) ?></small>
                            </td>
                            
                            <td class="fw-semibold text-dark"><?= $r['nama'] ?></td>
                            
                            <td>
                                <div class="text-dark fw-medium"><?= $r['nama_produk'] ?></div>
                                <div class="small text-secondary">Qty: <?= $r['jumlah'] ?></div>
                            </td>
                            
                            <td class="fw-bold text-dark">Rp <?= number_format($r['total_harga'], 0, ',', '.') ?></td>
                            
                            <td class="text-center">
                                <?php if($r['status_pembayaran'] == 'Lunas'): ?>
                                    <span class="badge badge-status bg-soft-success">Lunas</span>
                                <?php else: ?>
                                    <span class="badge badge-status bg-soft-danger">Belum Lunas</span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="text-center">
                                <?php if($r['status_order'] == 'Done'): ?>
                                    <span class="badge badge-status bg-soft-success">Selesai</span>
                                <?php elseif($r['status_order'] == 'Selesai'): ?>
                                    <span class="badge badge-status bg-soft-info">Siap Ambil</span>
                                <?php else: ?>
                                    <span class="badge badge-status bg-soft-warning">Proses</span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="text-end pe-4">
                                <a href="../transaksi/invoice.php?id=<?= $r['id_transaksi'] ?>" class="btn-icon" title="Cetak Invoice" target="_blank">
                                    <i class="bi bi-printer"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-secondary">
                                    <i class="bi bi-clipboard-check fs-1 d-block mb-2 opacity-25"></i>
                                    Tidak ada data untuk ditampilkan.
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