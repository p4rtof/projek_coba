<?php
include '../../config/koneksi.php';
include '../../auth/auth.php';

// --- LOGIC HAPUS ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $cek_transaksi = pg_fetch_assoc(pg_query($conn, "SELECT COUNT(*) AS total FROM transaksi WHERE id_pelanggan = '$id'"));
    if ($cek_transaksi['total'] > 0) {
        echo "<script>
            alert('🚫 GAGAL MENGHAPUS!\\n\\nPelanggan ini masih memiliki " . $cek_transaksi['total'] . " riwayat transaksi.');
            window.location.href = 'index.php';
        </script>";
        exit(); 
    } else {
        pg_query($conn, "DELETE FROM pelanggan WHERE id_pelanggan = '$id'");
        header("Location: index.php");
        exit();
    }
}

// --- LOGIC SIMPAN / EDIT ---
if (isset($_POST['simpan'])) {
    $nama = pg_escape_string($conn, $_POST['nama']);
    $hp = pg_escape_string($conn, $_POST['hp']);
    $alamat = pg_escape_string($conn, $_POST['alamat']);

    if (!empty($_POST['id_edit'])) {
        $id = $_POST['id_edit'];
        $q = "UPDATE pelanggan SET nama='$nama', hp='$hp', alamat='$alamat' WHERE id_pelanggan='$id'";
    } else {
        $q_last = pg_query($conn, "SELECT id_pelanggan FROM pelanggan ORDER BY length(id_pelanggan) DESC, id_pelanggan DESC LIMIT 1");
        $last_data = pg_fetch_assoc($q_last);
        $angka = $last_data ? (int) substr($last_data['id_pelanggan'], 1) : 0;
        $id_baru = 'P' . str_pad($angka + 1, 4, '0', STR_PAD_LEFT);
        $q = "INSERT INTO pelanggan (id_pelanggan, nama, hp, alamat) VALUES ('$id_baru', '$nama', '$hp', '$alamat')";
    }
    pg_query($conn, $q);
    header("Location: index.php");
}

$edit_data = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $edit_data = pg_fetch_assoc(pg_query($conn, "SELECT * FROM pelanggan WHERE id_pelanggan='$id'"));
}

$total_pelanggan = pg_fetch_assoc(pg_query($conn, "SELECT COUNT(*) AS total FROM pelanggan"))['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Data Pelanggan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { --primary: #4f46e5; --primary-hover: #4338ca; --secondary: #64748b; --dark: #0f172a; --light: #f8fafc; --border: #e2e8f0; --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025); }
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; color: var(--dark); }
        
        .card-modern { background: white; border: 1px solid white; border-radius: 16px; box-shadow: var(--card-shadow); transition: transform 0.2s, box-shadow 0.2s; }
        .form-label { font-size: 0.85rem; font-weight: 600; color: var(--secondary); margin-bottom: 0.4rem; }
        .form-control-modern { border: 1px solid var(--border); border-radius: 10px; padding: 10px 14px; font-size: 0.95rem; background-color: var(--light); transition: all 0.2s; }
        .form-control-modern:focus { background-color: white; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); }
        .btn-modern { background: var(--primary); color: white; border: none; padding: 12px; border-radius: 10px; font-weight: 600; width: 100%; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2); }
        .btn-modern:hover { background: var(--primary-hover); transform: translateY(-2px); }
        .table-custom { margin: 0; }
        .table-custom thead th { background: #f8fafc; color: var(--secondary); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; padding: 16px 24px; border-bottom: 1px solid var(--border); }
        .table-custom tbody td { padding: 16px 24px; vertical-align: middle; font-size: 0.95rem; border-bottom: 1px solid var(--border); color: var(--dark); }
        .avatar-circle { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; color: white; font-size: 16px; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); box-shadow: 0 4px 6px rgba(99, 102, 241, 0.2); }
        .btn-icon { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: none; transition: all 0.2s; cursor: pointer; text-decoration: none; color: white !important; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .btn-icon:hover { transform: translateY(-2px); box-shadow: 0 4px 6px rgba(0,0,0,0.15); }
        .btn-gray { background-color: #38be4eff; } .btn-blue { background-color: #3b82f6; } .btn-red { background-color: #ef4444; }
        .stats-pill { background: #e0e7ff; color: #4338ca; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; }

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
    
    <?php include '../../components/navbar.php'; ?>

    <div class="container pb-5 pt-0 mt-4">
        
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div><h3 class="fw-bold m-0" style="letter-spacing: -0.5px;">Data Pelanggan</h3></div>
            <div><span class="stats-pill"><i class="bi bi-people-fill me-2"></i><?= $total_pelanggan ?> Total Pelanggan</span></div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card-modern p-4 sticky-top" style="top: 90px; z-index: 1;">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 me-3"><i class="bi <?= $edit_data ? 'bi-pencil-square' : 'bi-person-plus-fill' ?> fs-4"></i></div>
                        <h5 class="fw-bold m-0"><?= $edit_data ? 'Edit Pelanggan' : 'Tambah Baru' ?></h5>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="id_edit" value="<?= $edit_data['id_pelanggan'] ?? '' ?>">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control form-control-modern" placeholder="Cth: Budi Santoso" value="<?= $edit_data['nama'] ?? '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nomor WhatsApp / HP</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-secondary" style="border: 1px solid var(--border); border-radius: 10px 0 0 10px;"><i class="bi bi-telephone"></i></span>
                                <input type="number" name="hp" class="form-control form-control-modern border-start-0 ps-2" style="border-radius: 0 10px 10px 0;" placeholder="0812..." value="<?= $edit_data['hp'] ?? '' ?>" >
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Alamat Lengkap</label>
                            <textarea name="alamat" class="form-control form-control-modern" rows="3" placeholder="Jalan, RT/RW, Kota..." ><?= $edit_data['alamat'] ?? '' ?></textarea>
                        </div>
                        <button type="submit" name="simpan" class="btn-modern"><i class="bi bi-check-lg me-2"></i> <?= $edit_data ? 'Simpan Perubahan' : 'Simpan Pelanggan' ?></button>
                        <?php if ($edit_data): ?>
                            <a href="index.php" class="btn btn-light w-100 mt-2 text-secondary fw-bold" style="border-radius: 10px; padding: 10px;">Batal Edit</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card-modern mb-3 p-2">
                    <form method="GET" class="d-flex align-items-center">
                        <i class="bi bi-search ms-3 text-secondary"></i>
                        <input type="text" name="q" class="form-control border-0 bg-transparent shadow-none" placeholder="Cari nama atau alamat..." value="<?= $_GET['q'] ?? '' ?>">
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
                                    <th class="ps-4">Pelanggan</th>
                                    <th>Kontak & Alamat</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $limit = 15;
                                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                                $offset = ($page - 1) * $limit;
                                $keyword = $_GET['q'] ?? '';
                                $safe_key = pg_escape_string($conn, $keyword);
                                $q_count = pg_fetch_assoc(pg_query($conn, "SELECT COUNT(*) as total FROM pelanggan WHERE nama ILIKE '%$safe_key%' OR alamat ILIKE '%$safe_key%'"));
                                $total_data = $q_count['total'];
                                $total_pages = ceil($total_data / $limit);
                                $q = pg_query($conn, "SELECT * FROM pelanggan WHERE nama ILIKE '%$safe_key%' OR alamat ILIKE '%$safe_key%' ORDER BY nama ASC LIMIT $limit OFFSET $offset");
                                
                                if(pg_num_rows($q) > 0):
                                    while ($row = pg_fetch_assoc($q)): 
                                        $inisial = strtoupper(substr($row['nama'], 0, 1));
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-3"><?= $inisial ?></div>
                                            <div>
                                                <div class="fw-bold text-dark"><?= $row['nama'] ?></div>
                                                <div class="small text-secondary" style="font-size: 0.75rem;">ID: <?= $row['id_pelanggan'] ?></div>
                                            </div>
                                        </div>
                                    </td>
<td>
    <?php if (!empty($row['hp']) && $row['hp'] !== '-'): ?>
        <div class="d-flex align-items-center mb-1">
            <i class="bi bi-whatsapp text-success me-2"></i>
            <a href="https://wa.me/<?= $row['hp'] ?>" target="_blank" class="text-decoration-none text-dark fw-medium small">
                <?= $row['hp'] ?>
            </a>
        </div>
    <?php endif; ?>

    <?php if (!empty($row['alamat'])): ?>
        <div class="text-secondary small lh-sm">
            <i class="bi bi-geo-alt me-1 opacity-50"></i> <?= $row['alamat'] ?>
        </div>
    <?php else: ?>
        <span class="text-muted small">-</span>
    <?php endif; ?>
</td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="riwayat.php?id=<?= $row['id_pelanggan'] ?>" class="btn-icon btn-gray" title="Riwayat"><i class="bi bi-clock-history"></i></a>
                                            <a href="index.php?edit=<?= $row['id_pelanggan'] ?>" class="btn-icon btn-blue" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                            <a href="index.php?hapus=<?= $row['id_pelanggan'] ?>" onclick="return confirm('Anda yakin menghapus pelanggan <?= $row['nama'] ?>?')" class="btn-icon btn-red" title="Hapus"><i class="bi bi-trash3"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center py-5 text-secondary"><i class="bi bi-emoji-frown fs-1 d-block mb-2 opacity-25"></i>Tidak ada data ditemukan.</td></tr>
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