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

// --- LOGIC: TANDAI LUNAS (FITUR BARU) ---
if (isset($_GET['lunasi'])) {
    $id = $_GET['id'];
    // Update status pembayaran jadi Lunas
    pg_query($conn, "UPDATE transaksi SET status_pembayaran = 'Lunas' WHERE id_transaksi = '$id'");
    header("Location: index.php"); 
    exit();
}

// --- LOGIC: UPDATE STATUS PENGERJAAN ---
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Dashboard Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        body { 
            background: #f0f2f5; 
            font-family: 'Poppins', sans-serif; 
        }

        /* KARTU ATAS (Gradient Estetik) */
        .card-stat { 
            border: none; 
            border-radius: 15px; 
            transition: transform 0.3s; 
            color: white; 
            overflow: hidden; 
            position: relative; 
            height: 100%;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .card-stat:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 10px 20px rgba(0,0,0,0.1); 
        }
        .bg-gradient-primary { background: linear-gradient(45deg, #4e73df, #224abe); }
        .bg-gradient-success { background: linear-gradient(45deg, #1cc88a, #13855c); }
        .bg-gradient-warning { background: linear-gradient(45deg, #f6c23e, #dda20a); }
        .circle-icon { 
            position: absolute; right: 10px; bottom: 10px; font-size: 5rem; opacity: 0.2; transform: rotate(-15deg); 
        }

        /* FILTER & SEARCH */
        .form-control-search {
            border-radius: 50px;
            border: 1px solid #e3e6f0;
            padding-left: 1.2rem;
        }
        .form-control-search:focus {
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.1);
        }

        /* TABEL */
        .card-table {
            border-radius: 15px;
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.05);
        }
        .table thead th { 
            background-color: #f8f9fc; 
            color: #4e73df; 
            font-weight: 700; 
            border-bottom: 2px solid #e3e6f0; 
        }
    </style>
</head>
<body>
    
    <?php include 'components/navbar.php'; ?>

    <div class="container pb-5 pt-0 mt-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-0">Dashboard Overview</h3>
                <p class="text-muted mb-0">Pantau performa harianmu di sini.</p>
            </div>
            <div class="d-none d-md-block text-end">
                <span class="badge bg-white text-dark shadow-sm py-2 px-3 fw-normal">
                    <i class="bi bi-calendar-event me-2 text-primary"></i> <?= date('l, d F Y') ?>
                </span>
            </div>
        </div>

        <div class="row mb-5 g-4">
            <div class="col-md-4">
                <div class="card card-stat bg-gradient-primary">
                    <div class="card-body p-4">
                        <div class="text-uppercase fw-bold small opacity-75 mb-1">Omset Hari Ini</div>
                        <h2 class="fw-bold mb-0">Rp <?= number_format($omset_hari_ini, 0, ',', '.') ?></h2>
                        <i class="bi bi-wallet2 circle-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stat bg-gradient-success">
                    <div class="card-body p-4">
                        <div class="text-uppercase fw-bold small opacity-75 mb-1">Transaksi Hari Ini</div>
                        <h2 class="fw-bold mb-0"><?= $jumlah_order ?> <span class="fs-6 fw-normal">Order</span></h2>
                        <i class="bi bi-cart-check circle-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stat bg-gradient-warning">
                    <div class="card-body p-4">
                        <div class="text-uppercase fw-bold small opacity-75 mb-1">Belum Lunas</div>
                        <h2 class="fw-bold mb-0"><?= $jumlah_utang ?> <span class="fs-6 fw-normal">Data</span></h2>
                        <i class="bi bi-exclamation-triangle circle-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
            <h5 class="fw-bold text-dark m-0"><i class="bi bi-clock-history me-2"></i>Riwayat Transaksi</h5>
            
            <div class="d-flex gap-2 flex-wrap justify-content-end w-80 w-md-auto">
                <form method="GET" class="d-flex gap-2 flex-grow-1 justify-content-end">
                    <input type="date" name="tgl" class="form-control form-control-search shadow-sm" style="max-width: 160px;" 
                           value="<?= $_GET['tgl'] ?? '' ?>" title="Filter Tanggal">
                    
                    <div class="input-group shadow-sm rounded-pill" style="max-width: 320px;">
                        <input type="text" name="q" class="form-control border-0 ps-4" placeholder="Cari nama, produk..." 
                               value="<?= $_GET['q'] ?? '' ?>">
                        <button type="submit" class="btn btn-white bg-white border-0 pe-3 text-primary"><i class="bi bi-search"></i></button>
                    </div>

                    <?php if(isset($_GET['q']) || isset($_GET['tgl'])): ?>
                        <a href="index.php" class="btn btn-light shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" title="Reset Filter"><i class="bi bi-arrow-counterclockwise text-danger"></i></a>
                    <?php endif; ?>
                </form>
                
                <a href="modules/transaksi/baru.php" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center">
                    <i class="bi bi-plus-lg me-2"></i> Order
                </a>
            </div>
        </div>

        <div class="card card-table overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead>
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
                        // FILTER
                        $keyword = $_GET['q'] ?? '';
                        $tanggal = $_GET['tgl'] ?? '';
                        $conditions = [];

                        if (!empty($keyword)) {
                            $safe_key = pg_escape_string($conn, $keyword);
                            $conditions[] = "(
                                t.id_transaksi ILIKE '%$safe_key%' OR 
                                p.nama ILIKE '%$safe_key%' OR 
                                pr.nama_produk ILIKE '%$safe_key%'
                            )";
                        }
                        if (!empty($tanggal)) {
                            $safe_tgl = pg_escape_string($conn, $tanggal);
                            $conditions[] = "DATE(t.waktu_order) = '$safe_tgl'";
                        }

                        $where_sql = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

                        // Query
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
                                    
                                    <?php if ($r['status_pembayaran'] == 'Belum Lunas'): ?>
                                        <a href="index.php?lunasi=true&id=<?= $r['id_transaksi'] ?>" 
                                           onclick="return confirm('Konfirmasi: Ubah status transaksi <?= $r['id_transaksi'] ?> menjadi LUNAS?')" 
                                           class="btn btn-sm btn-outline-success" title="Tandai Lunas">
                                            <i class="bi bi-check-lg"></i>
                                        </a>
                                    <?php endif; ?>

                                    <a href="modules/transaksi/edit.php?id=<?= $r['id_transaksi'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                                    <a href="modules/transaksi/invoice.php?id=<?= $r['id_transaksi'] ?>" class="btn btn-sm btn-outline-dark" title="Print Invoice"><i class="bi bi-printer-fill"></i></a>
                                    <a href="index.php?hapus=<?= $r['id_transaksi'] ?>" onclick="return confirm('Yakin hapus transaksi ini?')" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash-fill"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-search fs-1 d-block mb-2 opacity-50"></i>
                                    Tidak ada data transaksi ditemukan.
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