<?php
include 'koneksi.php';
include 'auth.php';

// HAPUS (Silent)
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // FIX: Change to id_produk and quote $id
    pg_query($conn, "DELETE FROM transaksi WHERE id_produk = '$id'"); 
    pg_query($conn, "DELETE FROM produk WHERE id_produk = '$id'");
    header("Location: produk.php");
}

// SIMPAN (Silent)
if (isset($_POST['simpan'])) {
    $nama = $_POST['nama_produk'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok_bahan'];

    if ($_POST['id_edit']) {
        $id = $_POST['id_edit'];
        // FIX: Change id to id_produk and quote $id
        $q = "UPDATE produk SET nama_produk='$nama', harga=$harga, stok_bahan=$stok WHERE id_produk='$id'";
    } else {
        // PERHATIAN: INSERT ini akan gagal karena kolom id_produk (CHAR(5)) wajib diisi dan bukan auto-increment.
        // Tambahkan logic untuk generate ID baru di sini.
        $q = "INSERT INTO produk (nama_produk, harga, stok_bahan) VALUES ('$nama', $harga, $stok)";
    }
    pg_query($conn, $q);
    header("Location: produk.php");
}

// Edit Data
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    // FIX: Change id to id_produk and quote $id
    $edit_data = pg_fetch_assoc(pg_query($conn, "SELECT * FROM produk WHERE id_produk='$id'"));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Data Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<?php include 'navbar.php'; ?>

    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3"><?= $edit_data ? 'Edit Produk' : 'Produk Baru' ?></h5>
                        <form method="POST">
                            <input type="hidden" name="id_edit" value="<?= $edit_data['id_produk'] ?? '' ?>"> <div class="mb-3">
                                <label class="small text-muted">Nama Produk</label>
                                <input type="text" name="nama_produk" class="form-control" value="<?= $edit_data['nama_produk'] ?? '' ?>" required>
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="small text-muted">Harga</label>
                                    <input type="number" name="harga" class="form-control" value="<?= $edit_data['harga'] ?? '' ?>" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="small text-muted">Stok</label>
                                    <input type="number" name="stok_bahan" class="form-control" value="<?= $edit_data['stok_bahan'] ?? '' ?>" required>
                                </div>
                            </div>
                            <button type="submit" name="simpan" class="btn btn-primary w-100 fw-bold">Simpan</button>
                            <?php if($edit_data): ?>
                                <a href="produk.php" class="btn btn-light w-100 mt-2">Batal</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Daftar Produk</h5>
                        <form method="GET" class="d-flex" style="width: 250px;">
                            <input type="text" name="q" class="form-control form-control-sm me-2" placeholder="Cari produk..." value="<?= $_GET['q'] ?? '' ?>">
                            <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
                        </form>
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
                                    <?php 
                                    // LOGIC QUERY SEARCH
                                    // FIX: Change id to id_produk
                                    $keyword = $_GET['q'] ?? '';
                                    $q_tampil = "SELECT * FROM produk WHERE nama_produk ILIKE '%$keyword%' ORDER BY id_produk DESC";
                                    $data_produk = pg_query($conn, $q_tampil);

                                    while($row = pg_fetch_assoc($data_produk)): 
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-bold"><?= $row['nama_produk'] ?></td>
                                        <td class="text-primary fw-bold">Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                                        <td><span class="text-style fw-bold"><?= $row['stok_bahan'] ?></span></td>
                                        <td class="text-end pe-4">
                                            <a href="produk.php?edit=<?= $row['id_produk'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                            <a href="produk.php?hapus=<?= $row['id_produk'] ?>" onclick="return confirm('Hapus produk?')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
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

</div>
</body>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</html>