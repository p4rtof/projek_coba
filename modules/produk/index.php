<?php
include '../../config/koneksi.php';
include '../../auth/auth.php';

// --- LOGIC HAPUS ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Hapus transaksi terkait dulu biar ga error FK
    pg_query($conn, "DELETE FROM transaksi WHERE id_produk = '$id'"); 
    pg_query($conn, "DELETE FROM produk WHERE id_produk = '$id'");
    header("Location: index.php");
}

// --- LOGIC SIMPAN / EDIT ---
if (isset($_POST['simpan'])) {
    $nama  = pg_escape_string($conn, $_POST['nama_produk']);
    $harga = (int)$_POST['harga'];
    $stok  = (int)$_POST['stok_bahan'];
    $jenis = pg_escape_string($conn, $_POST['jenis_satuan']);

    if (!empty($_POST['id_edit'])) {
        $id = $_POST['id_edit'];
        $q = "UPDATE produk SET nama_produk='$nama', harga=$harga, stok_bahan=$stok, jenis_satuan='$jenis' WHERE id_produk='$id'";
    } else {
        $q_last = pg_query($conn, "SELECT id_produk FROM produk ORDER BY id_produk DESC LIMIT 1");
        $last_data = pg_fetch_assoc($q_last);
        
        $angka = $last_data ? (int)substr($last_data['id_produk'], 1) : 0;
        $id_baru = 'B' . str_pad($angka + 1, 3, '0', STR_PAD_LEFT); 

        $q = "INSERT INTO produk (id_produk, nama_produk, harga, stok_bahan, jenis_satuan) 
              VALUES ('$id_baru', '$nama', $harga, $stok, '$jenis')";
    }
    pg_query($conn, $q);
    header("Location: index.php");
}

// --- AMBIL DATA EDIT ---
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $edit_data = pg_fetch_assoc(pg_query($conn, "SELECT * FROM produk WHERE id_produk='$id'"));
}

// STATISTIK TOTAL PRODUK
$total_produk = pg_fetch_assoc(pg_query($conn, "SELECT COUNT(*) AS total FROM produk"))['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Data Produk</title>
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
        
        /* Card Styling */
        .card-modern {
            background: white; border: 1px solid white; border-radius: 16px;
            box-shadow: var(--card-shadow); transition: transform 0.2s, box-shadow 0.2s;
        }
        .card-modern:hover { box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02); }

        /* Form Elements */
        .form-label { font-size: 0.85rem; font-weight: 600; color: var(--secondary); margin-bottom: 0.4rem; }
        .form-control-modern, .form-select-modern {
            border: 1px solid var(--border); border-radius: 10px; padding: 10px 14px;
            font-size: 0.95rem; background-color: var(--light); transition: all 0.2s;
        }
        .form-control-modern:focus, .form-select-modern:focus { 
            background-color: white; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); 
        }

        /* Button Custom */
        .btn-modern {
            background: var(--primary); color: white; border: none; padding: 12px;
            border-radius: 10px; font-weight: 600; width: 100%; transition: all 0.2s;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
        }
        .btn-modern:hover { background: var(--primary-hover); transform: translateY(-2px); }

        /* Table Styling */
        .table-custom { margin: 0; }
        .table-custom thead th {
            background: #f8fafc; color: var(--secondary); font-size: 0.75rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.05em; padding: 16px 24px; border-bottom: 1px solid var(--border);
        }
        .table-custom tbody td { padding: 16px 24px; vertical-align: middle; font-size: 0.95rem; border-bottom: 1px solid var(--border); color: var(--dark); }
        .table-custom tbody tr:hover { background-color: #f8fafc; }

        /* Badges */
        .badge-satuan { padding: 6px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 600; }
        .badge-meter { background: #fffbeb; color: #b45309; border: 1px solid #fcd34d; }
        .badge-pcs { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

        /* Action Buttons */
        .btn-icon {
            width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center;
            border-radius: 8px; border: 1px solid var(--border); background: white; color: var(--secondary);
            transition: all 0.2s; cursor: pointer; text-decoration: none;
        }
        .btn-icon:hover { background: var(--light); color: var(--primary); border-color: var(--primary); }
        .btn-icon.delete:hover { color: #ef4444; border-color: #ef4444; background: #fef2f2; }
        
        .stats-pill { background: #e0e7ff; color: #4338ca; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; }

        /* Pagination */
        .pagination .page-link {
            border: none; margin: 0 3px; border-radius: 8px; color: var(--secondary); font-weight: 600; font-size: 0.9rem;
        }
        .pagination .page-item.active .page-link {
            background-color: var(--primary); color: white; box-shadow: 0 4px 6px rgba(79, 70, 229, 0.3);
        }
        .pagination .page-link:hover { background-color: var(--light); color: var(--primary); }
    </style>
</head>
<body>

    <?php include '../../components/navbar.php'; ?>

    <div class="container pb-5 pt-0 mt-4">
        
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="fw-bold m-0" style="letter-spacing: -0.5px;">Data Produk</h3>
                <!-- <p class="text-secondary m-0 small">Kelola inventaris barang dan harga.</p> -->
            </div>
            <div>
                <span class="stats-pill"><i class="bi bi-box-seam-fill me-2"></i><?= $total_produk ?> Total Item</span>
            </div>
        </div>

        <div class="row g-4">
            
            <div class="col-lg-4">
                <div class="card-modern p-4 sticky-top" style="top: 90px; z-index: 1;">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 me-3">
                            <i class="bi <?= $edit_data ? 'bi-pencil-square' : 'bi-plus-square-fill' ?> fs-4"></i>
                        </div>
                        <h5 class="fw-bold m-0"><?= $edit_data ? 'Edit Produk' : 'Tambah Baru' ?></h5>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="id_edit" value="<?= $edit_data['id_produk'] ?? '' ?>"> 
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Produk</label>
                            <input type="text" name="nama_produk" class="form-control form-control-modern" placeholder="Cth: Spanduk Flexy" value="<?= $edit_data['nama_produk'] ?? '' ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jenis Satuan</label>
                            <select name="jenis_satuan" class="form-select form-select-modern" required>
                                <option value="Pcs" <?= ($edit_data['jenis_satuan'] ?? '') == 'Pcs' ? 'selected' : '' ?>>📦 Pcs (Satuan Biasa)</option>
                                <option value="Meter" <?= ($edit_data['jenis_satuan'] ?? '') == 'Meter' ? 'selected' : '' ?>>📏 Meter (Panjang x Lebar)</option>
                            </select>
                        </div>

                        <div class="row mb-4">
                            <div class="col-6">
                                <label class="form-label">Harga Satuan</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-secondary" style="border: 1px solid var(--border); border-radius: 10px 0 0 10px; font-size: 0.8rem;">Rp</span>
                                    <input type="number" name="harga" class="form-control form-control-modern border-start-0 ps-1" style="border-radius: 0 10px 10px 0;" value="<?= $edit_data['harga'] ?? '' ?>" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Stok</label>
                                <input type="number" name="stok_bahan" class="form-control form-control-modern" value="<?= $edit_data['stok_bahan'] ?? '' ?>" required>
                            </div>
                        </div>

                        <button type="submit" name="simpan" class="btn-modern">
                            <i class="bi bi-check-lg me-2"></i> <?= $edit_data ? 'Simpan Perubahan' : 'Simpan Produk' ?>
                        </button>
                        
                        <?php if($edit_data): ?>
                            <a href="index.php" class="btn btn-light w-100 mt-2 text-secondary fw-bold" style="border-radius: 10px; padding: 10px;">Batal Edit</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card-modern mb-3 p-2">
                    <form method="GET" class="d-flex align-items-center">
                        <i class="bi bi-search ms-3 text-secondary"></i>
                        <input type="text" name="q" class="form-control border-0 bg-transparent shadow-none" placeholder="Cari nama produk..." value="<?= $_GET['q'] ?? '' ?>" style="font-size: 0.95rem;">
                        <?php if(isset($_GET['q']) && $_GET['q'] != ''): ?>
                            <a href="index.php" class="btn btn-sm btn-light rounded-circle me-2"><i class="bi bi-x"></i></a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="card-modern overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Nama Produk</th>
                                    <th class="text-center">Satuan</th>
                                    <th class="text-center">Qty</th>
                                    <th>Harga</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // --- PAGINATION & QUERY ---
                                $limit = 10;
                                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                                $offset = ($page - 1) * $limit;

                                $keyword = $_GET['q'] ?? '';
                                $safe_key = pg_escape_string($conn, $keyword);
                                
                                // Query Total
                                $q_count = pg_fetch_assoc(pg_query($conn, "SELECT COUNT(*) as total FROM produk WHERE nama_produk ILIKE '%$safe_key%'"));
                                $total_data = $q_count['total'];
                                $total_pages = ceil($total_data / $limit);

                                // Query Data
                                $q_tampil = "SELECT * FROM produk WHERE nama_produk ILIKE '%$safe_key%' ORDER BY nama_produk ASC LIMIT $limit OFFSET $offset";
                                $data_produk = pg_query($conn, $q_tampil);

                                if(pg_num_rows($data_produk) > 0):
                                    while($row = pg_fetch_assoc($data_produk)): 
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= $row['nama_produk'] ?></div>
                                        <div class="small text-secondary" style="font-size: 0.75rem;">ID: <?= $row['id_produk'] ?></div>
                                    </td>
                                    
                                    <td class="text-center">
                                        <?php if($row['jenis_satuan'] == 'Meter'): ?>
                                            <span class="badge-satuan badge-meter">METER</span>
                                        <?php else: ?>
                                            <span class="badge-satuan badge-pcs">PCS</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center">
                                        <strong class="<?= $row['stok_bahan'] < 10 ? 'text-danger' : 'text-dark' ?>">
                                            <?= $row['stok_bahan'] ?>
                                        </strong>
                                    </td>

                                    <td class="fw-bold text-dark">Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>

                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="index.php?edit=<?= $row['id_produk'] ?>" class="btn-icon" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                            <a href="index.php?hapus=<?= $row['id_produk'] ?>" onclick="return confirm('Yakin hapus produk ini?')" class="btn-icon delete" title="Hapus"><i class="bi bi-trash3"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-5 text-secondary">Data produk tidak ditemukan.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-between align-items-center mt-3 px-2">
                    <small class="text-muted">Halaman <?= $page ?> dari <?= $total_pages ?></small>
                    <nav>
                        <ul class="pagination mb-0">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page-1 ?>&q=<?= $keyword ?>"><i class="bi bi-chevron-left"></i></a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&q=<?= $keyword ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page+1 ?>&q=<?= $keyword ?>"><i class="bi bi-chevron-right"></i></a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>