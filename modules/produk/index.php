<?php
include '../../config/koneksi.php';
include '../../auth/auth.php';

// --- LOGIC HAPUS ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    pg_query($conn, "DELETE FROM transaksi WHERE id_produk = '$id'"); 
    pg_query($conn, "DELETE FROM produk WHERE id_produk = '$id'");
    header("Location: index.php");
}

// --- LOGIC SIMPAN / EDIT ---
if (isset($_POST['simpan'])) {
    $nama  = $_POST['nama_produk'];
    $harga = $_POST['harga'];
    $stok  = $_POST['stok_bahan'];
    $jenis = $_POST['jenis_satuan']; 

    if (!empty($_POST['id_edit'])) {
        // Edit
        $id = $_POST['id_edit'];
        $q = "UPDATE produk SET nama_produk='$nama', harga=$harga, stok_bahan=$stok, jenis_satuan='$jenis' WHERE id_produk='$id'";
        pg_query($conn, $q);
    } else {
        // Tambah (Auto ID Bxxx)
        $q_last = pg_query($conn, "SELECT id_produk FROM produk ORDER BY id_produk DESC LIMIT 1");
        $last_data = pg_fetch_assoc($q_last);
        $angka = $last_data ? (int)substr($last_data['id_produk'], 1) : 0;
        $id_baru = 'B' . str_pad($angka + 1, 3, '0', STR_PAD_LEFT); 

        $q = "INSERT INTO produk (id_produk, nama_produk, harga, stok_bahan, jenis_satuan) 
              VALUES ('$id_baru', '$nama', $harga, $stok, '$jenis')";
        pg_query($conn, $q);
    }
    header("Location: index.php");
}

// Ambil Data Edit
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $edit_data = pg_fetch_assoc(pg_query($conn, "SELECT * FROM produk WHERE id_produk='$id'"));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Data Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #2563eb;
            --dark: #0f172a;
            --gray: #64748b;
            --bg: #f8fafc;
            --border: #e2e8f0;
        }
        body { background-color: var(--bg); font-family: 'Inter', sans-serif; color: var(--dark); }
        
        .card-clean {
            background: white; border: 1px solid var(--border);
            border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            overflow: hidden;
        }
        .form-label { font-size: 13px; font-weight: 600; color: var(--gray); }
        .form-control-clean {
            border: 1px solid var(--border); border-radius: 8px;
            padding: 10px 12px; font-size: 14px;
        }
        .form-control-clean:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .form-select-clean {
            border: 1px solid var(--border); border-radius: 8px; padding: 10px 12px; font-size: 14px;
        }
        
        .btn-primary-custom {
            background: var(--dark); color: white; border: none;
            padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px;
            width: 100%; transition: all 0.2s;
        }
        .btn-primary-custom:hover { background: #334155; transform: translateY(-1px); }

        .table thead th {
            background: #f1f5f9; color: var(--gray); font-weight: 600;
            font-size: 12px; text-transform: uppercase; padding: 12px 16px;
            border-bottom: 1px solid var(--border);
        }
        .table tbody td {
            padding: 10px 16px; vertical-align: middle; font-size: 14px;
            border-bottom: 1px solid var(--border);
        }
        .btn-action {
            width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;
            border-radius: 6px; border: 1px solid var(--border); background: white; color: var(--gray);
        }
        .btn-action:hover { background: var(--bg); color: var(--dark); }

        /* Badge Custom */
        .badge-satuan { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; }
        .badge-meter { background: #fef9c3; color: #854d0e; border: 1px solid #fde047; }
        .badge-pcs { background: #e2e8f0; color: #475569; border: 1px solid #cbd5e1; }
    </style>
</head>
<body>

    <?php include '../../components/navbar.php'; ?>

    <div class="container pb-5 pt-0 mt-4">
        <div class="row g-4">
            
            <div class="col-md-4">
                <div class="card-clean p-4">
                    <h5 class="fw-bold mb-4"><?= $edit_data ? 'Edit Produk' : 'Tambah Produk Baru' ?></h5>
                    
                    <form method="POST">
                        <input type="hidden" name="id_edit" value="<?= $edit_data['id_produk'] ?? '' ?>"> 
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Produk</label>
                            <input type="text" name="nama_produk" class="form-control form-control-clean" placeholder="Contoh: Spanduk Flexy" value="<?= $edit_data['nama_produk'] ?? '' ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jenis Satuan</label>
                            <select name="jenis_satuan" class="form-select form-select-clean" required>
                                <option value="Pcs" <?= ($edit_data['jenis_satuan'] ?? '') == 'Pcs' ? 'selected' : '' ?>>📦 Pcs (Satuan Biasa)</option>
                                <option value="Meter" <?= ($edit_data['jenis_satuan'] ?? '') == 'Meter' ? 'selected' : '' ?>>📏 Meter (Panjang x Lebar)</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-4">
                                <label class="form-label">Harga Satuan</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-secondary" style="border-color: var(--border);">Rp</span>
                                    <input type="number" name="harga" class="form-control form-control-clean border-start-0" value="<?= $edit_data['harga'] ?? '' ?>" required>
                                </div>
                            </div>
                            <div class="col-6 mb-4">
                                <label class="form-label">Stok Bahan</label>
                                <input type="number" name="stok_bahan" class="form-control form-control-clean" value="<?= $edit_data['stok_bahan'] ?? '' ?>" required>
                            </div>
                        </div>

                        <button type="submit" name="simpan" class="btn-primary-custom">
                            <i class="bi bi-box-seam me-2"></i> Simpan Produk
                        </button>
                        
                        <?php if($edit_data): ?>
                            <a href="index.php" class="btn btn-light w-100 mt-2 border" style="border-radius: 8px;">Batal Edit</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold m-0">Daftar Produk</h5>
                    <form method="GET" class="d-flex" style="max-width: 250px;">
                        <input type="text" name="q" class="form-control form-control-clean" placeholder="Cari produk..." value="<?= $_GET['q'] ?? '' ?>" style="padding: 8px 12px;">
                    </form>
                </div>

                <div class="card-clean">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Nama Produk</th>
                                    <th>Satuan</th>
                                    <th>Stok</th>
                                    <th>Harga</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $keyword = $_GET['q'] ?? '';
                                $safe_key = pg_escape_string($conn, $keyword);
                                $q_tampil = "SELECT * FROM produk WHERE nama_produk ILIKE '%$safe_key%' ORDER BY id_produk ASC";
                                $data_produk = pg_query($conn, $q_tampil);

                                if(pg_num_rows($data_produk) > 0):
                                    while($row = pg_fetch_assoc($data_produk)): 
                                ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-dark"><?= $row['nama_produk'] ?></td>
                                    
                                    <td>
                                        <?php if($row['jenis_satuan'] == 'Meter'): ?>
                                            <span class="badge-satuan badge-meter"><i class="bi bi-rulers me-1"></i> Meter</span>
                                        <?php else: ?>
                                            <span class="badge-satuan badge-pcs"><i class="bi bi-box-seam me-1"></i> Pcs</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-secondary fw-medium"><?= $row['stok_bahan'] ?></td>

                                    <td class="fw-bold text-dark">Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>

                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="index.php?edit=<?= $row['id_produk'] ?>" class="btn-action text-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                            <a href="index.php?hapus=<?= $row['id_produk'] ?>" onclick="return confirm('Yakin hapus produk ini?')" class="btn-action text-danger" title="Hapus"><i class="bi bi-trash3"></i></a>
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
            </div>

        </div>
    </div>
</body>
</html>