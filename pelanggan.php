<?php
include 'koneksi.php';
include 'auth.php'; // Tambahkan proteksi

// --- LOGIC HAPUS (Tanpa Alert, Langsung Refresh) ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // FIX: Change to id_pelanggan and quote $id
    pg_query($conn, "DELETE FROM transaksi WHERE id_pelanggan = '$id'");
    pg_query($conn, "DELETE FROM pelanggan WHERE id_pelanggan = '$id'");
    header("Location: pelanggan.php"); // Langsung refresh
}

// --- LOGIC SIMPAN (Tanpa Alert) ---
if (isset($_POST['simpan'])) {
    $nama = $_POST['nama'];
    $hp = $_POST['hp'];
    $alamat = $_POST['alamat'];

    if ($_POST['id_edit']) {
        $id = $_POST['id_edit'];
        // FIX: Change id to id_pelanggan and quote $id
        $q = "UPDATE pelanggan SET nama='$nama', hp='$hp', alamat='$alamat' WHERE id_pelanggan='$id'";
    } else {
        // PERHATIAN: INSERT ini akan gagal karena kolom id_pelanggan (CHAR(5)) wajib diisi dan bukan auto-increment.
        // Tambahkan logic untuk generate ID baru di sini.
        $q = "INSERT INTO pelanggan (nama, hp, alamat) VALUES ('$nama', '$hp', '$alamat')";
    }
    pg_query($conn, $q);
    header("Location: pelanggan.php"); // Langsung refresh
}

// Ambil Data Edit
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    // FIX: Change id to id_pelanggan and quote $id
    $edit_data = pg_fetch_assoc(pg_query($conn, "SELECT * FROM pelanggan WHERE id_pelanggan='$id'"));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Data Pelanggan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        /* Gaya tambahan untuk tombol WhatsApp */
        .btn-whatsapp { 
            background-color: #0b973eff; 
            color: white; 
            border: none;
            padding: 2px 10px;
            border-radius: 20px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .btn-whatsapp:hover {
            background-color: #128C7E;
            color: white;
        }
    </style>
</head>
<body class="bg-light">

<?php include 'navbar.php'; ?>

    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3"><?= $edit_data ? 'Edit Pelanggan' : 'Tambah Pelanggan' ?></h5>
                        <form method="POST">
                            <input type="hidden" name="id_edit" value="<?= $edit_data['id_pelanggan'] ?? '' ?>">
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
                                        <th>Alamat</th>
                                        <th>Kontak (HP)</th>
                                        <th class="text-end pe-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // LOGIC QUERY SEARCH
                                    $keyword = $_GET['q'] ?? '';
                                    $q_tampil = "SELECT * FROM pelanggan WHERE nama ILIKE '%$keyword%' ORDER BY id_pelanggan DESC";
                                    $data_pelanggan = pg_query($conn, $q_tampil);

                                    while($row = pg_fetch_assoc($data_pelanggan)): 
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-bold"><?= $row['nama'] ?></td>
                                        <td><?= $row['alamat'] ?></td> <td>
                                            <a href="https://wa.me/<?= $row['hp'] ?>" target="_blank" class="btn-whatsapp">
                                                <i class="bi bi-whatsapp me-1"></i> <?= $row['hp'] ?>
                                            </a>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="pelanggan.php?edit=<?= $row['id_pelanggan'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                            <a href="pelanggan.php?hapus=<?= $row['id_pelanggan'] ?>" onclick="return confirm('Hapus pelanggan ini?')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
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

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</html>