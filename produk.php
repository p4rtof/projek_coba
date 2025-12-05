<?php
include 'koneksi.php'; // Koneksi

// --- LOGIC CRUD PRODUK ---
$edit_data = null;

// 1. Hapus
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Hapus transaksi terkait dulu biar aman
    pg_query($conn, "DELETE FROM transaksi WHERE produk_id = $id");
    pg_query($conn, "DELETE FROM produk WHERE id = $id");
    header("Location: produk.php");
}

// 2. Simpan (Baru / Edit)
if (isset($_POST['simpan'])) {
    $nama = $_POST['nama_produk'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok_bahan'];

    if ($_POST['id_edit']) {
        // Edit
        $id = $_POST['id_edit'];
        $q = "UPDATE produk SET nama_produk='$nama', harga=$harga, stok_bahan=$stok WHERE id=$id";
    } else {
        // Baru
        $q = "INSERT INTO produk (nama_produk, harga, stok_bahan) VALUES ('$nama', $harga, $stok)";
    }
    pg_query($conn, $q);
    header("Location: produk.php");
}

// 3. Ambil Data Edit
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $edit_data = pg_fetch_assoc(pg_query($conn, "SELECT * FROM produk WHERE id=$id"));
}

// 4. Tampil Data
$data_produk = pg_query($conn, "SELECT * FROM produk ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Produk - Zaddy Printing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>body{background:#f4f6f9}</style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-printer"></i> Zaddy Printing</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">Transaksi</a>
                <a class="nav-link" href="pelanggan.php">Pelanggan</a>
                <a class="nav-link active fw-bold" href="produk.php">Produk</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3"><i class="bi bi-box-seam"></i> <?= $edit_data ? 'Edit Produk' : 'Produk Baru' ?></h5>
                        <form method="POST">
                            <input type="hidden" name="id_edit" value="<?= $edit_data['id'] ?? '' ?>">
                            
                            <div class="mb-3">
                                <label class="form-label small text-muted">Nama Layanan / Produk</label>
                                <input type="text" name="nama_produk" class="form-control" value="<?= $edit_data['nama_produk'] ?? '' ?>" required placeholder="Misal: Spanduk Flexy">
                            </div>
                            
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label small text-muted">Harga (Rp)</label>
                                    <input type="number" name="harga" class="form-control" value="<?= $edit_data['harga'] ?? '' ?>" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label small text-muted">Stok/Kuota</label>
                                    <input type="number" name="stok_bahan" class="form-control" value="<?= $edit_data['stok_bahan'] ?? '' ?>" required>
                                </div>
                            </div>

                            <button type="submit" name="simpan" class="btn btn-primary w-100 fw-bold">Simpan Produk</button>
                            <?php if($edit_data): ?>
                                <a href="produk.php" class="btn btn-light w-100 mt-2">Batal Edit</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-tags"></i> Daftar Harga</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Nama Produk</th>
                                        <th>Harga</th>
                                        <th>Stok</th>
                                        <th class="text-end pe-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = pg_fetch_assoc($data_produk)): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold"><?= $row['nama_produk'] ?></td>
                                        <td class="text-primary fw-bold">Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                                        <td>
                                            <span class="badge bg-secondary"><?= $row['stok_bahan'] ?> unit</span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="produk.php?edit=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                            <a href="produk.php?hapus=<?= $row['id'] ?>" onclick="return confirm('Hapus produk ini?')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>