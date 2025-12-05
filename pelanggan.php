<?php
include 'koneksi.php';

// --- LOGIC HAPUS (Tanpa Alert, Langsung Refresh) ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    pg_query($conn, "DELETE FROM transaksi WHERE pelanggan_id = $id");
    pg_query($conn, "DELETE FROM pelanggan WHERE id = $id");
    header("Location: pelanggan.php"); // Langsung refresh
}

// --- LOGIC SIMPAN (Tanpa Alert) ---
if (isset($_POST['simpan'])) {
    $nama = $_POST['nama'];
    $hp = $_POST['hp'];
    $alamat = $_POST['alamat'];

    if ($_POST['id_edit']) {
        $id = $_POST['id_edit'];
        $q = "UPDATE pelanggan SET nama='$nama', hp='$hp', alamat='$alamat' WHERE id=$id";
    } else {
        $q = "INSERT INTO pelanggan (nama, hp, alamat) VALUES ('$nama', '$hp', '$alamat')";
    }
    pg_query($conn, $q);
    header("Location: pelanggan.php"); // Langsung refresh
}

// Ambil Data Edit
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $edit_data = pg_fetch_assoc(pg_query($conn, "SELECT * FROM pelanggan WHERE id=$id"));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Data Pelanggan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-printer"></i> Zaddy Printing</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">Dashboard</a>
                <a class="nav-link active fw-bold" href="pelanggan.php">Pelanggan</a>
                <a class="nav-link" href="produk.php">Produk</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3"><?= $edit_data ? 'Edit Pelanggan' : 'Tambah Pelanggan' ?></h5>
                        <form method="POST">
                            <input type="hidden" name="id_edit" value="<?= $edit_data['id'] ?? '' ?>">
                            <div class="mb-3">
                                <label class="small text-muted">Nama</label>
                                <input type="text" name="nama" class="form-control" value="<?= $edit_data['nama'] ?? '' ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted">No. HP</label>
                                <input type="number" name="hp" class="form-control" value="<?= $edit_data['hp'] ?? '' ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted">Alamat</label>
                                <textarea name="alamat" class="form-control" rows="2" required><?= $edit_data['alamat'] ?? '' ?></textarea>
                            </div>
                            <button type="submit" name="simpan" class="btn btn-primary w-100 fw-bold">Simpan</button>
                            <?php if($edit_data): ?>
                                <a href="pelanggan.php" class="btn btn-light w-100 mt-2">Batal</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Daftar Pelanggan</h5>
                        <form method="GET" class="d-flex" style="width: 250px;">
                            <input type="text" name="q" class="form-control form-control-sm me-2" placeholder="Cari nama..." value="<?= $_GET['q'] ?? '' ?>">
                            <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
                        </form>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Nama</th>
                                        <th>Kontak</th>
                                        <th class="text-end pe-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // LOGIC QUERY SEARCH
                                    $keyword = $_GET['q'] ?? '';
                                    $q_tampil = "SELECT * FROM pelanggan WHERE nama ILIKE '%$keyword%' ORDER BY id DESC";
                                    $data_pelanggan = pg_query($conn, $q_tampil);

                                    while($row = pg_fetch_assoc($data_pelanggan)): 
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-bold"><?= $row['nama'] ?> <br> <small class="text-muted fw-normal"><?= $row['alamat'] ?></small></td>
                                        <td><?= $row['hp'] ?></td>
                                        <td class="text-end pe-4">
                                            <a href="pelanggan.php?edit=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                            <a href="pelanggan.php?hapus=<?= $row['id'] ?>" onclick="return confirm('Hapus pelanggan ini?')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
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