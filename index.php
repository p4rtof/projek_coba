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

// --- LOGIC: TANDAI LUNAS ---
if (isset($_GET['lunasi'])) {
    $id = $_GET['id'];
    pg_query($conn, "UPDATE transaksi SET status_pembayaran = 'Lunas' WHERE id_transaksi = '$id'");
    header("Location: index.php"); exit();
}

// --- LOGIC: UPDATE STATUS ---
if (isset($_GET['naik_status'])) {
    $id = $_GET['id'];
    $st = $_GET['status'];
    $new = ($st == 'Proses') ? 'Selesai' : (($st == 'Selesai') ? 'Done' : '');
    if ($new) pg_query($conn, "UPDATE transaksi SET status_order = '$new' WHERE id_transaksi = '$id'"); 
    header("Location: index.php"); exit();
}

// --- LOGIC: HAPUS ---
if (isset($_GET['hapus'])) {
    pg_query($conn, "DELETE FROM transaksi WHERE id_transaksi = '{$_GET['hapus']}'");
    header("Location: index.php"); exit();
}

// --- LOGIC PAGINATION & FILTER ---
$limit = 10; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$keyword = $_GET['q'] ?? '';
$tanggal = $_GET['tgl'] ?? '';
$conditions = [];

if (!empty($keyword)) {
    $safe_key = pg_escape_string($conn, $keyword);
    $conditions[] = "(t.id_transaksi ILIKE '%$safe_key%' OR p.nama ILIKE '%$safe_key%' OR pr.nama_produk ILIKE '%$safe_key%')";
}
if (!empty($tanggal)) {
    $safe_tgl = pg_escape_string($conn, $tanggal);
    $conditions[] = "DATE(t.waktu_order) = '$safe_tgl'";
}

$where_sql = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

// 1. Hitung Total Data (Untuk Pagination)
$query_count = "SELECT COUNT(*) AS total 
                FROM transaksi t 
                JOIN pelanggan p ON t.id_pelanggan=p.id_pelanggan 
                JOIN produk pr ON t.id_produk=pr.id_produk 
                $where_sql";
$total_data = pg_fetch_assoc(pg_query($conn, $query_count))['total'];
$total_pages = ceil($total_data / $limit);

// 2. Query Data dengan LIMIT & OFFSET
$query_main = "SELECT t.*, p.nama AS p_nama, pr.nama_produk 
               FROM transaksi t 
               JOIN pelanggan p ON t.id_pelanggan=p.id_pelanggan 
               JOIN produk pr ON t.id_produk=pr.id_produk 
               $where_sql
               ORDER BY t.id_transaksi DESC 
               LIMIT $limit OFFSET $offset";

$q_transaksi = pg_query($conn, $query_main);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Dashboard Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4f46e5;
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
            height: 100%;
        }
        .card-modern:hover { box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02); }

        /* Icon Boxes */
        .icon-box-stat {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 24px;
        }
        .icon-blue { background: #e0e7ff; color: #4338ca; }
        .icon-green { background: #dcfce7; color: #166534; }
        .icon-orange { background: #ffedd5; color: #9a3412; }

        /* Form Controls */
        .form-control-modern {
            border: 1px solid var(--border); border-radius: 10px; padding: 10px 14px;
            font-size: 0.95rem; background-color: var(--light); transition: all 0.2s;
        }
        .form-control-modern:focus { background-color: white; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); }
        
        /* Buttons Main */
        .btn-modern {
            background: var(--primary); color: white; border: none; padding: 10px 20px;
            border-radius: 10px; font-weight: 600; transition: all 0.2s;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
        }
        .btn-modern:hover { background: var(--primary-hover); transform: translateY(-2px); color: white; }

        /* Table Style */
        .table-custom { margin: 0; }
        .table-custom thead th {
            background: #f8fafc; color: var(--secondary); font-size: 0.75rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.05em; padding: 16px 24px; border-bottom: 1px solid var(--border);
        }
        .table-custom tbody td { 
            padding: 16px 24px; vertical-align: middle; font-size: 0.95rem; 
            border-bottom: 1px solid var(--border); color: var(--dark);
        }
        .table-custom tbody tr:hover { background-color: #f8fafc; }

        /* Badges */
        .badge-status { padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.5px; }
        .bg-soft-success { background: #dcfce7; color: #166534; }
        .bg-soft-danger { background: #fee2e2; color: #991b1b; }
        .bg-soft-warning { background: #fef3c7; color: #92400e; }
        .bg-soft-info { background: #e0f2fe; color: #075985; }

        /* --- NEW BUTTON ICON STYLES (COLORED BACKGROUNDS) --- */
        .btn-icon {
            width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center;
            border-radius: 8px; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none;
            color: white !important; /* Paksa teks putih */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-icon:hover { transform: translateY(-2px); box-shadow: 0 4px 6px rgba(0,0,0,0.15); }

        /* Warna Hijau (Lunas) */
        .btn-green { background-color: #11cf579f; } 
        .btn-green:hover { background-color: #1db956ff; }

        /* Warna Biru (Edit) */
        .btn-blue { background-color: #3b83f6b3; }
        .btn-blue:hover { background-color: #2563eb; }

        /* Warna Abu-abu (Print) */
        .btn-gray { background-color: #64748bbe; }
        .btn-gray:hover { background-color: #475569; }

        /* Warna Merah (Hapus) */
        .btn-red { background-color: #ef4444ad; }
        .btn-red:hover { background-color: #dc2626; }
        /* -------------------------------------------------- */


        /* Pagination Style */
        .page-link { border: none; color: var(--secondary); font-weight: 600; margin: 0 2px; border-radius: 8px; }
        .page-item.active .page-link { background-color: var(--primary); color: white; box-shadow: 0 4px 6px rgba(79, 70, 229, 0.3); }
        .page-link:hover { background-color: var(--light); color: var(--primary); }
    </style>
</head>
<body>
    
    <?php include 'components/navbar.php'; ?>

    <div class="container pb-5 pt-0 mt-4">
        
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="fw-bold m-0" style="letter-spacing: -0.5px;">Dashboard</h3>
                <p class="text-secondary m-0 small">Pantau performa bisnismu hari ini.</p>
            </div>
            <div>
                <div class="bg-white px-3 py-2 rounded-3 border text-secondary small fw-medium shadow-sm">
                    <i class="bi bi-calendar-event me-2 text-primary"></i> <?= date('l, d F Y') ?>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card-modern p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary fw-bold small text-uppercase mb-1">Pendapatan Hari Ini</div>
                        <h2 class="fw-bold m-0 text-dark">Rp <?= number_format($omset_hari_ini, 0, ',', '.') ?></h2>
                    </div>
                    <div class="icon-box-stat icon-blue"><i class="bi bi-wallet2"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-modern p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary fw-bold small text-uppercase mb-1">Order Masuk</div>
                        <h2 class="fw-bold m-0 text-dark"><?= $jumlah_order ?> <span class="fs-6 fw-normal text-secondary">Pesanan</span></h2>
                    </div>
                    <div class="icon-box-stat icon-green"><i class="bi bi-bag-check-fill"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-modern p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary fw-bold small text-uppercase mb-1">Belum Lunas</div>
                        <h2 class="fw-bold m-0 text-dark"><?= $jumlah_utang ?> <span class="fs-6 fw-normal text-secondary">Data</span></h2>
                    </div>
                    <div class="icon-box-stat icon-orange"><i class="bi bi-exclamation-triangle-fill"></i></div>
                </div>
            </div>
        </div>

        <div class="card-modern mb-4 p-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-primary fs-5"></i>
                    <h5 class="fw-bold m-0">Riwayat Transaksi</h5>
                </div>

                <div class="d-flex gap-2 w-80 w-md-auto justify-content-end align-items-center">
                    <form method="GET" class="d-flex gap-2 w-90 w-md-auto">
                        <div class="position-relative">
                            <input type="date" name="tgl" class="form-control form-control-modern" 
                                   style="width: auto;" 
                                   value="<?= $tanggal ?>" 
                                   title="Filter Tanggal"
                                   onchange="this.form.submit()"> 
                        </div>

                        <div class="position-relative flex-grow-1">
                            <input type="text" name="q" class="form-control form-control-modern" 
                                   placeholder="Cari Transaksi..." 
                                   value="<?= $keyword ?>">
                        </div>
                        
                        <?php if($keyword || $tanggal): ?>
                            <a href="index.php" class="btn btn-light border text-danger d-flex align-items-center justify-content-center" style="width: 42px; border-radius: 10px;" title="Reset Filter">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        <?php endif; ?>
                    </form>

                    <a href="modules/transaksi/baru.php" class="btn btn-modern d-flex align-items-center gap-2 text-nowrap">
                        <i class="bi bi-plus-lg"></i> <span class="d-none d-md-inline">Order Baru</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="card-modern overflow-hidden">
            <div class="table-responsive">
                <table class="table table-custom mb-0 text-center">
                    <thead>
                        <tr>
                            <th class="ps-4 text-start text-nowrap">ID & Tanggal</th>
                            <th class="text-start">Pelanggan</th>
                            <th class="text-start">Produk</th>
                            <th class="text-nowrap">Qty</th>
                            <th class="text-nowrap">Total</th>
                            <th class="text-nowrap">Status</th>
                            <th class="text-nowrap">Progress</th>
                            <th class="text-end pe-4 text-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (pg_num_rows($q_transaksi) > 0): ?>
                            <?php while ($r = pg_fetch_assoc($q_transaksi)): ?>
                            <tr>
                                <td class="ps-4 text-start text-nowrap">
                                    <div class="fw-bold text-primary"><?= $r['id_transaksi'] ?></div>
                                    <div class="small text-secondary" style="font-size: 0.75rem;">
                                        <?= date('d/m/y H:i', strtotime($r['waktu_order'])) ?>
                                    </div>
                                </td>
                                
                                <td class="text-start fw-semibold text-dark" style="min-width: 150px; max-width: 200px;">
                                    <?= $r['p_nama'] ?>
                                </td>
                                
                                <td class="text-start text-secondary" style="min-width: 150px; max-width: 250px;">
                                    <?= $r['nama_produk'] ?>
                                </td>
                                
                                <td class="text-nowrap"><span class="badge bg-light text-dark border"><?= $r['jumlah'] ?></span></td>
                                
                                <td class="fw-bold text-dark text-nowrap">Rp <?= number_format($r['total_harga'], 0, ',', '.') ?></td>
                                
                                <td class="text-nowrap">
                                    <?php if($r['status_pembayaran'] == 'Lunas'): ?>
                                        <span class="badge badge-status bg-soft-success">Lunas</span>
                                    <?php else: ?>
                                        <span class="badge badge-status bg-soft-danger">Belum Lunas</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-nowrap">
                                    <?php 
                                    $st = $r['status_order'];
                                    if($st == 'Done'): ?>
                                        <span class="text-success fw-bold small"><i class="bi bi-check-all fs-5"></i> DONE</span>
                                    <?php elseif($st == 'Selesai'): ?>
                                        <a href="index.php?naik_status=true&id=<?= $r['id_transaksi'] ?>&status=<?= $st ?>" 
                                           class="badge badge-status bg-soft-info text-decoration-none">Siap Ambil <i class="bi bi-chevron-right"></i></a>
                                    <?php else: ?>
                                        <a href="index.php?naik_status=true&id=<?= $r['id_transaksi'] ?>&status=<?= $st ?>" 
                                           class="badge badge-status bg-soft-warning text-decoration-none">Proses <i class="bi bi-chevron-right"></i></a>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-end pe-4 text-nowrap">
                                    <div class="d-flex justify-content-end gap-2">
                                        <?php if ($r['status_pembayaran'] == 'Belum Lunas'): ?>
                                            <a href="index.php?lunasi=true&id=<?= $r['id_transaksi'] ?>" 
                                               onclick="return confirm('Tandai transaksi <?= $r['id_transaksi'] ?> sebagai LUNAS?')" 
                                               class="btn-icon btn-green" title="Tandai Lunas">
                                                <i class="bi bi-check-lg"></i>
                                            </a>
                                        <?php endif; ?>

                                        <a href="modules/transaksi/edit.php?id=<?= $r['id_transaksi'] ?>" class="btn-icon btn-blue" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        
                                        <a href="modules/transaksi/invoice.php?id=<?= $r['id_transaksi'] ?>" target="_blank" class="btn-icon btn-gray" title="Print Invoice">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        
                                        <a href="index.php?hapus=<?= $r['id_transaksi'] ?>" onclick="return confirm('Hapus transaksi?')" class="btn-icon btn-red" title="Hapus">
                                            <i class="bi bi-trash3"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-secondary">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                                    Tidak ada data transaksi ditemukan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light">
                <small class="text-muted fw-bold ms-2">Halaman <?= $page ?> dari <?= $total_pages ?></small>
                <nav>
                    <ul class="pagination mb-0 me-2">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page-1 ?>&q=<?= $keyword ?>&tgl=<?= $tanggal ?>"><i class="bi bi-chevron-left"></i></a>
                        </li>
                        
                        <?php 
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        
                        if($start > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        
                        for ($i = $start; $i <= $end; $i++): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&q=<?= $keyword ?>&tgl=<?= $tanggal ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; 
                        
                        if($end < $total_pages) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        ?>

                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page+1 ?>&q=<?= $keyword ?>&tgl=<?= $tanggal ?>"><i class="bi bi-chevron-right"></i></a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>