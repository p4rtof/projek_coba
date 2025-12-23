<?php
include '../../config/koneksi.php';
include '../../auth/auth.php';

// Hapus Pelanggan
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    pg_query($conn, "DELETE FROM transaksi WHERE id_pelanggan = '$id'");
    pg_query($conn, "DELETE FROM pelanggan WHERE id_pelanggan = '$id'");
    header("Location: index.php");
}

// Simpan / Edit Pelanggan
if (isset($_POST['simpan'])) {
    $nama = $_POST['nama'];
    $hp = $_POST['hp'];
    $alamat = $_POST['alamat'];

    if (!empty($_POST['id_edit'])) {
        $id = $_POST['id_edit'];
        $q = "UPDATE pelanggan SET nama='$nama', hp='$hp', alamat='$alamat' WHERE id_pelanggan='$id'";
    } else {
        // Auto ID P000X
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Data Pelanggan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <?php include '../../components/navbar.php'; ?>

    <div class="container py-4">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3"><?= $edit_data ? 'Edit Pelanggan' : 'Tambah Pelanggan' ?></h5>
                        <form method="POST">
                            <input type="hidden" name="id_edit" value="<?= $edit_data['id_pelanggan'] ?? '' ?>">
                            <div class="mb-2"><label>Nama</label><input type="text" name="nama" class="form-control" value="<?= $edit_data['nama'] ?? '' ?>" required></div>
                            <div class="mb-2"><label>No. HP</label><input type="text" name="hp" class="form-control" value="<?= $edit_data['hp'] ?? '' ?>" required></div>
                            <div class="mb-3"><label>Alamat</label><textarea name="alamat" class="form-control" rows="2" required><?= $edit_data['alamat'] ?? '' ?></textarea></div>
                            <button type="submit" name="simpan" class="btn btn-primary w-100">Simpan</button>
                            <?php if ($edit_data): ?><a href="index.php" class="btn btn-light w-100 mt-2">Batal</a><?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Daftar Pelanggan</h5>
                        <form method="GET" class="d-flex w-50">
                            <input type="text" name="q" class="form-control form-control-sm me-2" placeholder="Cari nama..." value="<?= $_GET['q'] ?? '' ?>">
                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th class="ps-3">Nama</th><th>Alamat</th><th>Kontak</th><th class="text-end pe-3">Aksi</th></tr></thead>
                            <tbody>
                                <?php
                                $keyword = $_GET['q'] ?? '';
                                $q = pg_query($conn, "SELECT * FROM pelanggan WHERE nama ILIKE '%$keyword%' ORDER BY nama ASC");
                                while ($row = pg_fetch_assoc($q)): ?>
                                <tr>
                                    <td class="ps-3 fw-bold"><?= $row['nama'] ?></td>
                                    <td><?= $row['alamat'] ?></td>
                                    <td><a href="https://wa.me/<?= $row['hp'] ?>" target="_blank" class="btn btn-sm btn-success rounded-pill px-2 py-0"><i class="bi bi-whatsapp"></i> <?= $row['hp'] ?></a></td>
                                    <td class="text-end pe-3">
                                        <a href="riwayat.php?id=<?= $row['id_pelanggan'] ?>" class="btn btn-sm btn-info text-white"><i class="bi bi-clock-history"></i></a>
                                        <a href="index.php?edit=<?= $row['id_pelanggan'] ?>" class="btn btn-sm btn-secondary"><i class="bi bi-pencil"></i></a>
                                        <a href="index.php?hapus=<?= $row['id_pelanggan'] ?>" onclick="return confirm('Hapus?')" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></a>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>