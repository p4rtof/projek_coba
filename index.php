<?php
include 'config/koneksi.php';
include 'auth/auth.php';

// --- LOGIC WIDGET DASHBOARD ---

// 1. Sedang Proses
$q_proses = pg_fetch_assoc(pg_query($conn, "SELECT COUNT(*) AS total FROM transaksi WHERE status_order = 'Proses'"));
$jumlah_proses = $q_proses['total'] ?? 0;

// 2. Order Masuk Hari Ini
$q_order = pg_fetch_assoc(pg_query($conn, "SELECT COUNT(*) AS total FROM transaksi WHERE waktu_order::date = CURRENT_DATE"));
$jumlah_order = $q_order['total'] ?? 0;

// 3. Belum Lunas
$q_utang = pg_fetch_assoc(pg_query($conn, "SELECT COUNT(*) AS total FROM transaksi WHERE status_pembayaran = 'Belum Lunas'"));
$jumlah_utang = $q_utang['total'] ?? 0;

// --- LOGIC AKSI ---

// 1. LUNASI PER ITEM
if (isset($_GET['lunasi_item']) && isset($_GET['uid'])) {
    $uid = $_GET['uid']; 
    pg_query($conn, "UPDATE transaksi SET status_pembayaran = 'Lunas' WHERE id = '$uid'");
    header("Location: index.php"); exit();
}

// 2. LUNASI SATU NOTA
if (isset($_GET['lunasi_nota']) && isset($_GET['id_trx'])) {
    $id_trx = $_GET['id_trx']; 
    pg_query($conn, "UPDATE transaksi SET status_pembayaran = 'Lunas' WHERE id_transaksi = '$id_trx'");
    header("Location: index.php"); exit();
}

// 3. UPDATE PROGRESS
if (isset($_GET['naik_status']) && isset($_GET['uid']) && isset($_GET['status'])) {
    $uid = $_GET['uid']; $st = $_GET['status'];
    $new = ($st == 'Proses') ? 'Selesai' : (($st == 'Selesai') ? 'Done' : '');
    if ($new) pg_query($conn, "UPDATE transaksi SET status_order = '$new' WHERE id = '$uid'"); 
    header("Location: index.php"); exit();
}

// 4. HAPUS
if (isset($_GET['hapus']) && isset($_GET['uid'])) {
    $uid = $_GET['uid']; pg_query($conn, "DELETE FROM transaksi WHERE id = '$uid'");
    header("Location: index.php"); exit();
}

// --- FILTER & PAGINATION ---
$limit = 25;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$keyword = $_GET['q'] ?? '';
$tanggal = $_GET['tgl'] ?? '';
$conditions = [];

if (!empty($keyword)) {
    $safe_key = pg_escape_string($conn, $keyword);
    $conditions[] = "(t.id_transaksi ILIKE '%$safe_key%' OR t.no_po ILIKE '%$safe_key%' OR p.nama ILIKE '%$safe_key%' OR pr.nama_produk ILIKE '%$safe_key%')";
}
if (!empty($tanggal)) {
    $safe_tgl = pg_escape_string($conn, $tanggal);
    $conditions[] = "DATE(t.waktu_order) = '$safe_tgl'";
}

$where_sql = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

// Hitung Total
$query_count = "SELECT COUNT(*) AS total 
                FROM transaksi t 
                JOIN pelanggan p ON t.id_pelanggan=p.id_pelanggan 
                JOIN produk pr ON t.id_produk=pr.id_produk 
                $where_sql";
$total_data = pg_fetch_assoc(pg_query($conn, $query_count))['total'];
$total_pages = ceil($total_data / $limit);

// Query Data
$query_main = "SELECT t.*, p.nama AS p_nama, pr.nama_produk 
               FROM transaksi t 
               JOIN pelanggan p ON t.id_pelanggan=p.id_pelanggan 
               JOIN produk pr ON t.id_produk=pr.id_produk 
               $where_sql
               ORDER BY t.waktu_order DESC, t.id_transaksi DESC 
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
        :root { --primary: #4f46e5; --secondary: #64748b; --dark: #0f172a; --light: #f8fafc; --border: #e2e8f0; --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025); }
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; color: var(--dark); overflow-x: hidden; }
        
        /* CARD & GENERAL STYLES */
        .card-modern { background: white; border: 1px solid white; border-radius: 16px; box-shadow: var(--card-shadow); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); height: 100%; position: relative; overflow: hidden; }
        .card-modern:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
        
        /* ICONS & BADGES */
        .icon-box-stat { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: transform 0.3s ease; }
        .card-modern:hover .icon-box-stat { transform: scale(1.1) rotate(5deg); }
        .icon-blue { background: #e0e7ff; color: #4338ca; } .icon-green { background: #dcfce7; color: #166534; } .icon-orange { background: #ffedd5; color: #9a3412; }
        
        /* INPUTS & BUTTONS */
        .form-control-modern { border: 1px solid var(--border); border-radius: 10px; padding: 10px 14px; background: var(--light); transition: all 0.3s ease; }
        .form-control-modern:focus { background: white; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); transform: scale(1.01); }
        
        .btn-modern { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 600; transition: all 0.3s; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2); }
        .btn-modern:hover { background: #4338ca; transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3); color: white; }
        
        /* TABLE & BADGES */
        .table-custom th { background: #f8fafc; color: var(--secondary); font-size: 0.75rem; text-transform: uppercase; padding: 16px 24px; letter-spacing: 0.05em; }
        .table-custom td { padding: 16px 24px; vertical-align: middle; border-bottom: 1px solid var(--border); }
        .badge-status { padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: all 0.2s; }
        .badge-status:hover { transform: scale(1.05); }
        .bg-soft-success { background: #dcfce7; color: #166534; } .bg-soft-danger { background: #fee2e2; color: #991b1b; } .bg-soft-warning { background: #fef3c7; color: #92400e; } .bg-soft-info { background: #e0f2fe; color: #075985; }

        /* --- ANIMASI KEREN (Keyframes) --- */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Class Animasi */
        .animate-fade-1 { animation: fadeInUp 0.3s ease-out forwards; opacity: 0; }
        .animate-fade-2 { animation: fadeInUp 0.3s ease-out 0.2s forwards; opacity: 0; } /* Delay dikit */
        .animate-fade-3 { animation: fadeInUp 0.3s ease-out 0.4s forwards; opacity: 0; } /* Delay lagi */

        /* Search Box Glow Animation */
        .search-group:focus-within { transform: scale(1.02); }
        .search-group { transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }

        /* Pagination Keren */
        .pagination .page-item { margin: 0 4px; }
        .pagination .page-link { border: none; border-radius: 50% !important; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-weight: 600; color: var(--secondary); transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); }
        .pagination .page-item.active .page-link { background: var(--primary); color: white; box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4); transform: translateY(-2px); }
        .pagination .page-link:hover:not(.active) { background: #e0e7ff; color: var(--primary); transform: translateY(-2px); }
        .pagination .page-item.disabled .page-link { background: transparent; color: #cbd5e1; cursor: not-allowed; }

        /* Button Action Icons */
        .btn-icon { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; color: white !important; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .btn-icon:hover { transform: translateY(-3px) rotate(5deg); box-shadow: 0 4px 8px rgba(0,0,0,0.15); }
        .btn-green { background: #11cf57; } .btn-blue { background: #3b83f6; } .btn-gray { background: #64748b; } .btn-red { background: #ef4444; }
    </style>
</head>
<body>
    
    <?php include 'components/navbar.php'; ?>

    <div class="container pb-5 pt-0 mt-4">
        
        <div class="d-flex justify-content-between align-items-end mb-4 animate-fade-1">
            <div><h3 class="fw-bold m-0" style="letter-spacing: -0.5px;">Dashboard Admin</h3></div>
            <div><div class="bg-white px-3 py-2 rounded-3 border text-secondary small fw-medium shadow-sm"><i class="bi bi-calendar-event me-2 text-primary"></i> <?= date('l, d F Y') ?></div></div>
        </div>

        <div class="row g-4 mb-4 animate-fade-1">
            <div class="col-md-4">
                <div class="card-modern p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary fw-bold small text-uppercase mb-1">Sedang Proses</div>
                        <h2 class="fw-bold m-0 text-dark"><?= $jumlah_proses ?> <span class="fs-6 fw-normal text-secondary">Pesanan</span></h2>
                    </div>
                    <div class="icon-box-stat icon-blue"><i class="bi bi-hourglass-split"></i></div>
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

        <div class="card-modern mb-4 p-3 animate-fade-2">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-2"><i class="bi bi-clock-history text-primary fs-5"></i><h5 class="fw-bold m-0">Riwayat Transaksi</h5></div>
                
                <div class="d-flex gap-2 w-90 w-md-auto justify-content-end align-items-center flex-wrap">
                    <form method="GET" class="d-flex gap-2 flex-grow-1 flex-md-grow-0 search-group">
                        <input type="date" name="tgl" class="form-control form-control-modern" style="width: auto;" value="<?= $tanggal ?>" onchange="this.form.submit()"> 
                        <div class="position-relative flex-grow-1">
                            <input type="text" name="q" class="form-control form-control-modern ps-4" placeholder="Cari ID / PO / Produk..." value="<?= $keyword ?>">
                            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-2 text-secondary" style="font-size: 0.8rem;"></i>
                        </div>
                    </form>
                    
                    <button type="button" id="btnToggleCetak" class="btn btn-dark d-flex align-items-center gap-2 shadow-sm px-3 py-2 btn-hover-effect" style="border-radius: 10px; transition: all 0.2s;">
                        <i class="bi bi-printer-fill"></i> <span class="d-none d-md-inline small fw-bold">Print Invoice</span>
                    </button>
                    
                    <a href="modules/transaksi/keranjang.php" class="btn btn-modern d-flex align-items-center gap-2 shadow-sm px-4 py-2" style="background-color: #4f46e5; color: white;">
                        <i class="bi bi-plus-lg"></i> <span class="d-none d-md-inline fw-bold">Transaksi Baru</span>
                    </a>
                </div>
            </div>
        </div>

        <form action="modules/transaksi/invoice.php" method="POST" id="formCetakInvoice" class="animate-fade-3">
            <div class="card-modern overflow-hidden">
                <div id="toolbarCetak" class="p-3 border-bottom bg-warning bg-opacity-10 d-flex align-items-center justify-content-between" style="display:none;">
                    <div class="d-flex align-items-center gap-2 text-warning-emphasis fw-bold"><i class="bi bi-info-circle-fill"></i> <span>Mode Print Invoice: Centang satu ID, semua item dengan ID sama otomatis terpilih.</span></div>
                    <button type="submit" class="btn btn-sm btn-dark rounded-pill px-4 shadow-sm fw-bold"><i class="bi bi-printer me-2"></i>PRINT SEKARANG</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom mb-0 text-center">
                        <thead>
                            <tr>
                                <th class="ps-4 text-center col-checkbox" style="width: 50px; display: none;"><input type="checkbox" class="form-check-input" id="checkAll" style="cursor: pointer;"></th>
                                <th class="text-start text-nowrap">ID, PO & Tanggal</th>
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
                                <tr class="align-middle">
                                    <td class="ps-4 text-center col-checkbox" style="display: none;">
                                        <input type="checkbox" name="ids[]" value="<?= $r['id_transaksi'] ?>" class="form-check-input check-item" style="cursor: pointer;">
                                    </td>
                                    <td class="text-start text-nowrap">
                                        <div class="fw-bold text-primary"><?= $r['id_transaksi'] ?></div>
                                        <?php if(!empty($r['no_po'])): ?>
                                            <div class="small fw-bold text-dark mt-1" style="font-size: 0.8rem;">PO: <?= $r['no_po'] ?></div>
                                        <?php endif; ?>
                                        <div class="small text-secondary mt-1" style="font-size: 0.75rem;"><?= date('d/m/y H:i', strtotime($r['waktu_order'])) ?></div>
                                    </td>
                                    <td class="text-start"><div class="fw-semibold text-dark"><?= $r['p_nama'] ?></div></td>
                                    <td class="text-start text-secondary" style="min-width: 150px; max-width: 250px;">
                                        <?= $r['nama_produk'] ?>
                                        <?php if($r['panjang'] > 0): ?>
                                            <div class="small fst-italic text-muted mt-1" style="font-size: 0.75rem;"><i class="bi bi-rulers"></i> <?= (float)$r['panjang'] ?>m x <?= (float)$r['lebar'] ?>m</div>
                                        <?php endif; ?>
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
                                            <span class="text-success fw-bold small"><i class="bi bi-check-all fs-5"></i> Selesai</span>
                                        <?php elseif($st == 'Selesai'): ?>
                                            <a href="index.php?naik_status=true&uid=<?= $r['id'] ?>&status=<?= $st ?>" class="badge badge-status bg-soft-info text-decoration-none">Siap Ambil <i class="bi bi-chevron-right"></i></a>
                                        <?php else: ?>
                                            <a href="index.php?naik_status=true&uid=<?= $r['id'] ?>&status=<?= $st ?>" class="badge badge-status bg-soft-warning text-decoration-none">Proses <i class="bi bi-chevron-right"></i></a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4 text-nowrap">
                                        <div class="d-flex justify-content-end gap-2">
                                            <?php if ($r['status_pembayaran'] == 'Belum Lunas'): ?>
                                                <div class="dropdown d-inline-block">
                                                    <button class="btn-icon btn-green dropdown-toggle" type="button" data-bs-toggle="dropdown" style="border:none;" title="Pelunasan"><i class="bi bi-check-lg"></i></button>
                                                    <ul class="dropdown-menu shadow-sm border-0">
                                                        <li><a class="dropdown-item" href="index.php?lunasi_item=true&uid=<?= $r['id'] ?>" onclick="return confirm('Lunasi ITEM ini saja?')">Lunasi Item Ini</a></li>
                                                        <li><a class="dropdown-item" href="index.php?lunasi_nota=true&id_trx=<?= $r['id_transaksi'] ?>" onclick="return confirm('Lunasi SEMUA di nota ini?')">Lunasi Satu Nota</a></li>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>
                                            <a href="modules/transaksi/edit.php?id=<?= $r['id_transaksi'] ?>" class="btn-icon btn-blue" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                            <a href="modules/transaksi/invoice.php?item_id=<?= $r['id'] ?>" class="btn-icon btn-gray" title="Print Item Ini"><i class="bi bi-printer"></i></a>
                                            <a href="index.php?hapus=true&uid=<?= $r['id'] ?>" onclick="return confirm('Hapus item ini?')" class="btn-icon btn-red" title="Hapus"><i class="bi bi-trash3"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="9" class="text-center py-5 text-secondary"><i class="bi bi-receipt-cutoff fs-1 d-block mb-2 opacity-25"></i>Tidak ada data transaksi ditemukan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>

        <?php if ($total_pages > 1): ?>
        <div class="d-flex justify-content-center mt-4 animate-fade-3">
            <nav>
                <ul class="pagination shadow-sm bg-white rounded-pill p-1">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page-1 ?>&q=<?= $keyword ?>&tgl=<?= $tanggal ?>"><i class="bi bi-chevron-left"></i></a>
                    </li>
                    
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 1 && $i <= $page + 1)): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&q=<?= $keyword ?>&tgl=<?= $tanggal ?>"><?= $i ?></a>
                            </li>
                        <?php elseif ($i == 2 || $i == $total_pages - 1): ?>
                            <li class="page-item disabled"><span class="page-link border-0">...</span></li>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page+1 ?>&q=<?= $keyword ?>&tgl=<?= $tanggal ?>"><i class="bi bi-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const btnToggle = document.getElementById('btnToggleCetak');
        const toolbarCetak = document.getElementById('toolbarCetak');
        const colsCheckbox = document.querySelectorAll('.col-checkbox');
        let isCetakMode = false;

        btnToggle.addEventListener('click', function() {
            isCetakMode = !isCetakMode;
            if (isCetakMode) {
                colsCheckbox.forEach(el => el.style.display = 'table-cell');
                toolbarCetak.style.display = 'flex';
                btnToggle.innerHTML = '<i class="bi bi-x-lg"></i> <span class="small fw-bold">Batal</span>';
                btnToggle.classList.replace('btn-dark', 'btn-light');
                btnToggle.classList.add('text-danger', 'border');
            } else {
                colsCheckbox.forEach(el => el.style.display = 'none');
                toolbarCetak.style.display = 'none';
                btnToggle.innerHTML = '<i class="bi bi-printer-fill"></i> <span class="d-none d-md-inline small fw-bold">Invoice</span>';
                btnToggle.classList.replace('btn-light', 'btn-dark');
                btnToggle.classList.remove('text-danger', 'border');
            }
        });

        const checkboxes = document.querySelectorAll('.check-item');
        checkboxes.forEach(box => {
            box.addEventListener('change', function() {
                const idToMatch = this.value;
                const isChecked = this.checked;
                checkboxes.forEach(otherBox => {
                    if (otherBox.value === idToMatch) {
                        otherBox.checked = isChecked;
                    }
                });
            });
        });
        
        document.getElementById('checkAll').addEventListener('change', function() {
            let checkboxes = document.querySelectorAll('.check-item');
            checkboxes.forEach(chk => chk.checked = this.checked);
        });
    </script>
</body>
</html>