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

// --- LOGIC AKSI ---
if (isset($_GET['lunasi_item']) && isset($_GET['uid'])) {
    $uid = $_GET['uid']; pg_query($conn, "UPDATE transaksi SET status_pembayaran = 'Lunas' WHERE id = '$uid'");
    header("Location: index.php"); exit();
}
if (isset($_GET['lunasi_nota']) && isset($_GET['id_trx'])) {
    $id_trx = $_GET['id_trx']; pg_query($conn, "UPDATE transaksi SET status_pembayaran = 'Lunas' WHERE id_transaksi = '$id_trx'");
    header("Location: index.php"); exit();
}
if (isset($_GET['naik_status']) && isset($_GET['uid']) && isset($_GET['status'])) {
    $uid = $_GET['uid']; $st = $_GET['status'];
    $new = ($st == 'Proses') ? 'Selesai' : (($st == 'Selesai') ? 'Done' : '');
    if ($new) pg_query($conn, "UPDATE transaksi SET status_order = '$new' WHERE id = '$uid'"); 
    header("Location: index.php"); exit();
}
if (isset($_GET['hapus']) && isset($_GET['uid'])) {
    $uid = $_GET['uid']; pg_query($conn, "DELETE FROM transaksi WHERE id = '$uid'");
    header("Location: index.php"); exit();
}

// --- FILTER & PAGINATION ---
$limit = 10;
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
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; color: var(--dark); }
        .card-modern { background: white; border: 1px solid white; border-radius: 16px; box-shadow: var(--card-shadow); transition: all 0.2s; height: 100%; }
        .card-modern:hover { box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05); }
        .icon-box-stat { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .icon-blue { background: #e0e7ff; color: #4338ca; } .icon-green { background: #dcfce7; color: #166534; } .icon-orange { background: #ffedd5; color: #9a3412; }
        .form-control-modern { border: 1px solid var(--border); border-radius: 10px; padding: 10px 14px; background: var(--light); }
        .btn-modern { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 600; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2); }
        .btn-modern:hover { background: var(--primary-hover); transform: translateY(-2px); color: white; }
        .table-custom th { background: #f8fafc; color: var(--secondary); font-size: 0.75rem; text-transform: uppercase; padding: 16px 24px; }
        .table-custom td { padding: 16px 24px; vertical-align: middle; border-bottom: 1px solid var(--border); }
        .badge-status { padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; cursor: pointer; }
        .bg-soft-success { background: #dcfce7; color: #166534; } .bg-soft-danger { background: #fee2e2; color: #991b1b; } .bg-soft-warning { background: #fef3c7; color: #92400e; } .bg-soft-info { background: #e0f2fe; color: #075985; }
        .btn-icon { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; color: white !important; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .btn-icon:hover { transform: translateY(-2px); }
        .btn-green { background: #11cf57; } .btn-blue { background: #3b83f6; } .btn-gray { background: #64748b; } .btn-red { background: #ef4444; }
        .page-link { border: none; color: var(--secondary); font-weight: 600; margin: 0 2px; border-radius: 8px; }
        .page-item.active .page-link { background: var(--primary); color: white; }
        .dropdown-item { font-size: 0.9rem; padding: 8px 16px; color: var(--secondary); }
        .dropdown-item:hover { background-color: var(--light); color: var(--primary); }

        /* --- CSS PAGINATION BARU (ANIMASI) --- */
        .pagination .page-link {
            border: none; margin: 0 3px; border-radius: 8px; 
            color: var(--secondary); font-weight: 600; font-size: 0.9rem;
            transition: all 0.2s ease;
        }
        .pagination .page-item.active .page-link {
            background-color: var(--primary); color: white; 
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.3);
            transform: translateY(-1px);
        }
        .pagination .page-link:hover { 
            background-color: var(--light); color: var(--primary); 
            transform: translateY(-1px);
        }
        .pagination .page-item.disabled .page-link {
            background-color: transparent; color: #cbd5e1;
        }
    </style>
</head>
<body>
    
    <?php include 'components/navbar.php'; ?>

    <div class="container pb-5 pt-0 mt-4">
        
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div><h3 class="fw-bold m-0" style="letter-spacing: -0.5px;">Dashboard Admin</h3></div>
            <div><div class="bg-white px-3 py-2 rounded-3 border text-secondary small fw-medium shadow-sm"><i class="bi bi-calendar-event me-2 text-primary"></i> <?= date('l, d F Y') ?></div></div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4"><div class="card-modern p-4 d-flex align-items-center justify-content-between"><div><div class="text-secondary fw-bold small text-uppercase mb-1">Pendapatan Hari Ini</div><h2 class="fw-bold m-0 text-dark">Rp <?= number_format($omset_hari_ini, 0, ',', '.') ?></h2></div><div class="icon-box-stat icon-blue"><i class="bi bi-wallet2"></i></div></div></div>
            <div class="col-md-4"><div class="card-modern p-4 d-flex align-items-center justify-content-between"><div><div class="text-secondary fw-bold small text-uppercase mb-1">Order Masuk</div><h2 class="fw-bold m-0 text-dark"><?= $jumlah_order ?> <span class="fs-6 fw-normal text-secondary">Pesanan</span></h2></div><div class="icon-box-stat icon-green"><i class="bi bi-bag-check-fill"></i></div></div></div>
            <div class="col-md-4"><div class="card-modern p-4 d-flex align-items-center justify-content-between"><div><div class="text-secondary fw-bold small text-uppercase mb-1">Belum Lunas</div><h2 class="fw-bold m-0 text-dark"><?= $jumlah_utang ?> <span class="fs-6 fw-normal text-secondary">Data</span></h2></div><div class="icon-box-stat icon-orange"><i class="bi bi-exclamation-triangle-fill"></i></div></div></div>
        </div>

        <div class="card-modern mb-4 p-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-2"><i class="bi bi-clock-history text-primary fs-5"></i><h5 class="fw-bold m-0">Riwayat Transaksi</h5></div>
                <div class="d-flex gap-2 w-95 w-md-auto justify-content-end align-items-center">
                    <form method="GET" class="d-flex gap-2 w-90 w-md-auto">
                        <input type="date" name="tgl" class="form-control form-control-modern" style="width: auto;" value="<?= $tanggal ?>" onchange="this.form.submit()"> 
                        <div class="position-relative flex-grow-1"><input type="text" name="q" class="form-control form-control-modern" placeholder="Cari Transaksi..." value="<?= $keyword ?>"></div>
                        <button type="submit" class="btn btn-light border" style="border-radius: 10px;"><i class="bi bi-search"></i></button>
                    </form>
                    <button type="button" id="btnToggleCetak" class="btn btn-dark d-flex align-items-center gap-2 shadow-sm px-3 py-2" style="border-radius: 10px;"><i class="bi bi-printer-fill"></i> <span class="d-none d-md-inline small fw-bold">Print Invoice</span></button>
                    <a href="modules/transaksi/keranjang.php" class="btn btn-modern d-flex align-items-center gap-2 shadow-sm px-4 py-2" style="background-color: #4f46e5; color: white;"><i class="bi bi-plus-lg"></i> <span class="d-none d-md-inline fw-bold">Buat Transaksi Baru</span></a>
                </div>
            </div>
        </div>

        <form action="modules/transaksi/invoice.php" method="POST" id="formCetakInvoice">
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
                                <th class="text-start text-nowrap">ID & Tanggal</th>
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
                                    <td class="ps-4 text-center col-checkbox" style="display: none;">
                                        <input type="checkbox" name="ids[]" value="<?= $r['id_transaksi'] ?>" class="form-check-input check-item" style="cursor: pointer;">
                                    </td>
                                    <td class="text-start text-nowrap">
                                        <div class="fw-bold text-primary"><?= $r['id_transaksi'] ?></div>
                                        <div class="small text-secondary" style="font-size: 0.75rem;"><?= date('d/m/y H:i', strtotime($r['waktu_order'])) ?></div>
                                    </td>
                                    <td class="text-start" style="min-width: 150px; max-width: 200px;">
                                        <div class="fw-semibold text-dark"><?= $r['p_nama'] ?></div>
                                    </td>
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
                                        <?php $st = $r['status_order']; if($st == 'Done'): ?>
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
                                            <a href="index.php?hapus=true&uid=<?= $r['id'] ?>" onclick="return confirm('⛔⛔⛔ PERHATIAN!\n\nApakah Anda yakin ingin menghapus TRANSAKSI ini?')" class="btn-icon btn-red" title="Hapus"><i class="bi bi-trash3"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="9" class="text-center py-5 text-secondary">Tidak ada data transaksi ditemukan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>

        <?php if ($total_pages > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-3 px-2">
            <small class="text-muted">Halaman <?= $page ?> dari <?= $total_pages ?></small>
            <nav>
                <ul class="pagination mb-0">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page-1 ?>&q=<?= $keyword ?>&tgl=<?= $tanggal ?>"><i class="bi bi-chevron-left"></i></a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&q=<?= $keyword ?>&tgl=<?= $tanggal ?>"><?= $i ?></a>
                        </li>
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
            if (isCetakMode) { colsCheckbox.forEach(el => el.style.display = 'table-cell'); toolbarCetak.style.display = 'flex'; btnToggle.innerHTML = '<i class="bi bi-x-lg"></i> <span class="small fw-bold">Batal</span>'; btnToggle.classList.replace('btn-dark', 'btn-light'); btnToggle.classList.add('text-danger', 'border'); } 
            else { colsCheckbox.forEach(el => el.style.display = 'none'); toolbarCetak.style.display = 'none'; btnToggle.innerHTML = '<i class="bi bi-printer-fill"></i> <span class="d-none d-md-inline small fw-bold">Cetak Gabungan</span>'; btnToggle.classList.replace('btn-light', 'btn-dark'); btnToggle.classList.remove('text-danger', 'border'); }
        });
        const checkboxes = document.querySelectorAll('.check-item');
        checkboxes.forEach(box => { box.addEventListener('change', function() { const idToMatch = this.value; const isChecked = this.checked; checkboxes.forEach(otherBox => { if (otherBox.value === idToMatch) { otherBox.checked = isChecked; } }); }); });
        document.getElementById('checkAll').addEventListener('change', function() { checkboxes.forEach(chk => chk.checked = this.checked); });
    </script>
</body>
</html>