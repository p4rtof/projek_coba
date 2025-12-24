<?php
include '../../config/koneksi.php';
include '../../auth/auth.php';

// Cek ID Pelanggan
if (!isset($_GET['id'])) { header("Location: index.php"); exit(); }
$id_pelanggan = $_GET['id'];

// --- LOGIC AKSI ---
if (isset($_GET['lunasi_item']) && isset($_GET['uid'])) {
    $uid = $_GET['uid']; pg_query($conn, "UPDATE transaksi SET status_pembayaran = 'Lunas' WHERE id = '$uid'");
    header("Location: riwayat.php?id=" . $id_pelanggan); exit();
}
if (isset($_GET['lunasi_nota']) && isset($_GET['id_trx'])) {
    $id_trx = $_GET['id_trx']; pg_query($conn, "UPDATE transaksi SET status_pembayaran = 'Lunas' WHERE id_transaksi = '$id_trx'");
    header("Location: riwayat.php?id=" . $id_pelanggan); exit();
}
if (isset($_GET['naik_status']) && isset($_GET['uid']) && isset($_GET['status'])) {
    $uid = $_GET['uid'];
    $st = $_GET['status'];
    $new = ($st == 'Proses') ? 'Selesai' : (($st == 'Selesai') ? 'Done' : '');
    if ($new) pg_query($conn, "UPDATE transaksi SET status_order = '$new' WHERE id = '$uid'"); 
    header("Location: riwayat.php?id=" . $id_pelanggan); exit();
}
if (isset($_GET['hapus']) && isset($_GET['uid'])) {
    $uid = $_GET['uid']; pg_query($conn, "DELETE FROM transaksi WHERE id = '$uid'");
    header("Location: riwayat.php?id=" . $id_pelanggan); exit();
}

// --- DATA PELANGGAN ---
$q_pelanggan = pg_query($conn, "SELECT * FROM pelanggan WHERE id_pelanggan = '$id_pelanggan'");
$p = pg_fetch_assoc($q_pelanggan);
if (!$p) { echo "<script>alert('Pelanggan tidak ditemukan!'); window.location.href='index.php';</script>"; exit(); }

// --- PERSIAPAN NOMOR WA (Untuk info pill saja) ---
$hp_display = $p['hp'] ?? '-';
$hp_link = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $hp_display));

// --- QUERY DATA ---
$keyword = $_GET['q'] ?? '';
$conditions = ["t.id_pelanggan = '$id_pelanggan'"];
if (!empty($keyword)) {
    $safe_key = pg_escape_string($conn, $keyword);
    $conditions[] = "(t.id_transaksi ILIKE '%$safe_key%' OR pr.nama_produk ILIKE '%$safe_key%')";
}
$where_sql = "WHERE " . implode(" AND ", $conditions);
$query_transaksi = "SELECT t.*, pr.nama_produk FROM transaksi t JOIN produk pr ON t.id_produk = pr.id_produk $where_sql ORDER BY t.waktu_order DESC, t.id_transaksi DESC";
$q_riwayat = pg_query($conn, $query_transaksi);
$total_riwayat = pg_num_rows($q_riwayat);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Riwayat - <?= $p['nama'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4f46e5; --secondary: #64748b; --dark: #0f172a; --light: #f8fafc; --border: #e2e8f0; --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025); }
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; color: var(--dark); }
        
        /* FIXED: Added position relative */
        .card-modern { background: white; border: 1px solid white; border-radius: 16px; box-shadow: var(--card-shadow); transition: transform 0.2s, box-shadow 0.2s; height: 100%; overflow: hidden; position: relative; }
        
        .customer-header h1 { font-weight: 800; letter-spacing: -1px; color: var(--dark); }
        .info-pill { background: white; border: 1px solid var(--border); padding: 8px 16px; border-radius: 50px; font-size: 0.9rem; color: var(--secondary); display: inline-flex; align-items: center; gap: 8px; font-weight: 500; text-decoration: none; transition: all 0.2s ease; }
        a.info-pill:hover { background-color: #dcfce7; border-color: #86efac; color: #166534; transform: translateY(-2px); box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        
        .icon-box-stat { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        .icon-blue { background: #e0e7ff; color: #4338ca; } .icon-green { background: #dcfce7; color: #166534; } .icon-orange { background: #ffedd5; color: #9a3412; }
        
        .btn-modern { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 600; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2); }
        .btn-modern:hover { background: #4338ca; transform: translateY(-2px); color: white; }
        
        /* FIXED: Added relative & z-index */
        .btn-icon { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; color: white !important; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: relative; z-index: 10; }
        .btn-icon:hover { transform: translateY(-2px); box-shadow: 0 4px 6px rgba(0,0,0,0.15); }
        .btn-green { background: #11cf57; } .btn-blue { background: #3b83f6; } .btn-gray { background: #64748b; } .btn-red { background: #ef4444; }
        
        .table-custom th { background: #f8fafc; color: var(--secondary); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; padding: 16px 24px; }
        .table-custom td { padding: 16px 24px; vertical-align: middle; border-bottom: 1px solid var(--border); }
        
        /* FIXED: Added relative & z-index */
        .badge-status { padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.5px; cursor: pointer; position: relative; z-index: 10; }
        .bg-soft-success { background: #dcfce7; color: #166534; } .bg-soft-danger { background: #fee2e2; color: #991b1b; } .bg-soft-warning { background: #fef3c7; color: #92400e; } .bg-soft-info { background: #e0f2fe; color: #075985; }
        .form-control-modern { border: 1px solid var(--border); border-radius: 10px; padding: 10px 14px; background: var(--light); }
        .dropdown-item { font-size: 0.9rem; padding: 8px 16px; color: var(--secondary); }
        .dropdown-item:hover { background-color: var(--light); color: var(--primary); }
    </style>
</head>
<body>

    <div class="container pb-5 pt-0 mt-4">
        
        <div class="mb-4">
            <a href="index.php" class="btn btn-light border rounded-pill shadow-sm px-3 mb-3 d-inline-flex align-items-center fw-bold text-secondary" style="font-size: 0.85rem;">
                <i class="bi bi-arrow-left me-2"></i> Kembali ke Daftar
            </a>
            <div class="row align-items-center">
                <div class="col-md-7 customer-header">
                    <h1 class="display-5 mb-2"><?= $p['nama'] ?></h1>
                    <div class="d-flex gap-2 flex-wrap">
                        <div class="info-pill text-dark"><i class="bi bi-person-badge text-primary"></i> ID: <?= $p['id_pelanggan'] ?></div>
                        
                        <?php if($hp_display && $hp_display != '-'): ?>
                            <a href="https://wa.me/<?= $hp_link ?>" target="_blank" class="info-pill" title="Chat WhatsApp">
                                <i class="bi bi-whatsapp text-success"></i> <?= $hp_display ?>
                            </a>
                        <?php else: ?>
                            <div class="info-pill text-muted"><i class="bi bi-telephone-x"></i> -</div>
                        <?php endif; ?>

                        <div class="info-pill text-dark"><i class="bi bi-geo-alt-fill text-danger"></i> <?= $p['alamat'] ?? '-' ?></div>
                    </div>
                </div>
                
                <div class="col-md-5 mt-3 mt-md-0 text-md-end">
                    <a href="../transaksi/keranjang.php?id_pelanggan=<?= $id_pelanggan ?>" class="btn btn-modern d-inline-flex align-items-center gap-2 py-3 px-4 shadow-sm" style="width: auto;">
                        <i class="bi bi-plus-lg fs-5"></i> <span>Buat Order Baru</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4"><div class="card-modern p-4 d-flex align-items-center justify-content-between"><div><div class="text-secondary fw-bold small mb-1">TOTAL PESANAN</div><h2 class="fw-bold m-0"><?= $total_riwayat ?> <span class="fs-6 fw-normal">Kali</span></h2></div><div class="icon-box-stat icon-blue"><i class="bi bi-bag-check-fill"></i></div></div></div>
            <div class="col-md-4">
                <?php $lunas = pg_fetch_assoc(pg_query($conn, "SELECT COUNT(*) AS c FROM transaksi WHERE id_pelanggan='$id_pelanggan' AND status_pembayaran='Lunas'")); ?>
                <div class="card-modern p-4 d-flex align-items-center justify-content-between"><div><div class="text-secondary fw-bold small mb-1">LUNAS</div><h2 class="fw-bold m-0"><?= $lunas['c'] ?> <span class="fs-6 fw-normal">Nota</span></h2></div><div class="icon-box-stat icon-green"><i class="bi bi-check-circle-fill"></i></div></div>
            </div>
            <div class="col-md-4">
                <?php $utang = pg_fetch_assoc(pg_query($conn, "SELECT COUNT(*) AS c FROM transaksi WHERE id_pelanggan='$id_pelanggan' AND status_pembayaran='Belum Lunas'")); ?>
                <div class="card-modern p-4 d-flex align-items-center justify-content-between"><div><div class="text-secondary fw-bold small mb-1">BELUM LUNAS</div><h2 class="fw-bold m-0"><?= $utang['c'] ?> <span class="fs-6 fw-normal">Nota</span></h2></div><div class="icon-box-stat icon-orange"><i class="bi bi-exclamation-triangle-fill"></i></div></div>
            </div>
        </div>

        <div class="card-modern mb-4 p-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-2"><i class="bi bi-clock-history text-primary fs-5"></i><h5 class="fw-bold m-0">Riwayat Transaksi</h5></div>
                <div class="d-flex gap-2 w-95 w-md-auto justify-content-end align-items-center">
                    <form method="GET" class="d-flex gap-2 w-90 w-md-auto">
                        <input type="hidden" name="id" value="<?= $id_pelanggan ?>">
                        <div class="position-relative flex-grow-1"><input type="text" name="q" class="form-control form-control-modern" placeholder="Cari ID / Produk..." value="<?= $keyword ?>"></div>
                        <button type="submit" class="btn btn-light border" style="border-radius: 10px;"><i class="bi bi-search"></i></button>
                    </form>
                    <button type="button" id="btnToggleCetak" class="btn btn-dark d-flex align-items-center gap-2 shadow-sm px-3 py-2" style="border-radius: 10px;">
                        <i class="bi bi-printer-fill"></i> <span class="d-none d-md-inline small fw-bold"> Print Invoice</span>
                    </button>
                </div>
            </div>
        </div>

        <form action="../transaksi/invoice.php" method="POST" target="_blank">
            <div class="card-modern overflow-hidden">
                <div id="toolbarCetak" class="p-3 border-bottom bg-warning bg-opacity-10 d-flex align-items-center justify-content-between" style="display:none;">
                    <div class="d-flex align-items-center gap-2 text-warning-emphasis fw-bold"><i class="bi bi-info-circle-fill"></i><span>Mode Print Invoice: Centang satu ID, semua item dengan ID sama otomatis terpilih.</span></div>
                    <button type="submit" class="btn btn-sm btn-dark rounded-pill px-4 shadow-sm fw-bold"><i class="bi bi-printer me-2"></i>PRINT SEKARANG</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom mb-0 text-center">
                        <thead>
                            <tr>
                                <th class="ps-4 text-center col-checkbox" style="width: 50px; display: none;"><input type="checkbox" id="checkAll" style="cursor: pointer;"></th>
                                <th class="text-start text-nowrap">ID & Tanggal</th>
                                <th class="text-start">Produk</th>
                                <th class="text-nowrap">Qty</th>
                                <th class="text-nowrap">Total</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-nowrap">Progress</th>
                                <th class="text-end pe-4 text-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total_riwayat > 0): ?>
                                <?php while ($r = pg_fetch_assoc($q_riwayat)): ?>
                                <tr>
                                    <td class="ps-4 text-center col-checkbox" style="display: none;">
                                        <input type="checkbox" name="ids[]" value="<?= $r['id_transaksi'] ?>" class="form-check-input check-item" style="cursor: pointer;">
                                    </td>
                                    <td class="text-start text-nowrap">
                                        <div class="fw-bold text-primary">#<?= $r['id_transaksi'] ?></div>
                                        <div class="small text-secondary" style="font-size: 0.75rem;"><?= date('d/m/y H:i', strtotime($r['waktu_order'])) ?></div>
                                    </td>
                                    <td class="text-start text-secondary" style="min-width: 150px; max-width: 250px;">
                                        <?= $r['nama_produk'] ?>
                                        <?php if($r['panjang'] > 0): ?>
                                            <div class="small fst-italic text-muted mt-1" style="font-size: 0.75rem;">
                                                <i class="bi bi-rulers"></i> <?= (float)$r['panjang'] ?>m x <?= (float)$r['lebar'] ?>m
                                            </div>
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
                                            <a href="riwayat.php?id=<?= $id_pelanggan ?>&naik_status=true&uid=<?= $r['id'] ?>&status=<?= $st ?>" 
                                               class="badge badge-status bg-soft-info text-decoration-none">
                                               Siap Ambil <i class="bi bi-chevron-right"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="riwayat.php?id=<?= $id_pelanggan ?>&naik_status=true&uid=<?= $r['id'] ?>&status=<?= $st ?>" 
                                               class="badge badge-status bg-soft-warning text-decoration-none">
                                               Proses <i class="bi bi-chevron-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-end pe-4 text-nowrap">
                                        <div class="d-flex justify-content-end gap-2">
                                            
                                            <?php if ($r['status_pembayaran'] == 'Belum Lunas'): ?>
                                                <div class="dropdown d-inline-block">
                                                    <button class="btn-icon btn-green dropdown-toggle" type="button" data-bs-toggle="dropdown" style="border:none;" title="Pelunasan">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                    <ul class="dropdown-menu shadow-sm border-0">
                                                        <li><a class="dropdown-item" href="riwayat.php?id=<?= $id_pelanggan ?>&lunasi_item=true&uid=<?= $r['id'] ?>" onclick="return confirm('Yakin LUNASI item ini saja?')">Lunasi Item Ini</a></li>
                                                        <li><a class="dropdown-item" href="riwayat.php?id=<?= $id_pelanggan ?>&lunasi_nota=true&id_trx=<?= $r['id_transaksi'] ?>" onclick="return confirm('Yakin LUNASI semua item di Nota #<?= $r['id_transaksi'] ?>?')">Lunasi Satu Nota</a></li>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>

                                            <a href="../transaksi/edit.php?id=<?= $r['id_transaksi'] ?>" class="btn-icon btn-blue" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                            <a href="../transaksi/invoice.php?item_id=<?= $r['id'] ?>" class="btn-icon btn-gray" target="_blank" title="Cetak Item"><i class="bi bi-printer"></i></a>
                                            <a href="riwayat.php?id=<?= $id_pelanggan ?>&hapus=true&uid=<?= $r['id'] ?>" class="btn-icon btn-red" onclick="return confirm('Hapus item ini?')" title="Hapus"><i class="bi bi-trash3"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="text-center py-5 text-secondary">Belum ada riwayat pesanan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>

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
                btnToggle.innerHTML = '<i class="bi bi-printer-fill"></i> <span class="d-none d-md-inline small fw-bold"> Print Invoice</span>';
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
            checkboxes.forEach(chk => chk.checked = this.checked);
        });
    </script>
</body>
</html>