<?php
include 'koneksi.php'; // Pastikan koneksi aman

// --- LOGIC CRUD PELANGGAN ---
$edit_data = null;

// 1. Hapus
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Hapus transaksi terkait dulu biar aman
    pg_query($conn, "DELETE FROM transaksi WHERE pelanggan_id = $id");
    pg_query($conn, "DELETE FROM pelanggan WHERE id = $id");
    header("Location: pelanggan.php");
}

// 2. Simpan (Baru / Edit)
if (isset($_POST['simpan'])) {
    $nama = $_POST['nama'];
    $hp = $_POST['hp'];
    $alamat = $_POST['alamat'];

    if ($_POST['id_edit']) {
        // Edit
        $id = $_POST['id_edit'];
        $q = "UPDATE pelanggan SET nama='$nama', hp='$hp', alamat='$alamat' WHERE id=$id";
    } else {
        // Baru
        $q = "INSERT INTO pelanggan (nama, hp, alamat) VALUES ('$nama', '$hp', '$alamat')";
    }
    pg_query($conn, $q);
    header("Location: pelanggan.php");
}

// 3. Ambil Data Edit
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $edit_data = pg_fetch_assoc(pg_query($conn, "SELECT * FROM pelanggan WHERE id=$id"));
}

// 4. Tampil Data
$data_pelanggan = pg_query($conn, "SELECT * FROM pelanggan ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Pelanggan - Zaddy Printing</title>
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
                        <h5 class="fw-bold mb-3"><i class="bi bi-person-plus"></i> <?= $edit_data ? 'Edit Pelanggan' : 'Pelanggan Baru' ?></h5>
                        <form method="POST">
                            <input type="hidden" name="id_edit" value="<?= $edit_data['id'] ?? '' ?>">
                            
                            <div class="mb-3">
                                <label class="form-label small text-muted">Nama Lengkap</label>
                                <input type="text" name="nama" class="form-control" value="<?= $edit_data['nama'] ?? '' ?>" required placeholder="Contoh: Budi Santoso">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small text-muted">No. HP / WA</label>
                                <input type="number" name="hp" class="form-control" value="<?= $edit_data['hp'] ?? '' ?>" required placeholder="0812...">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small text-muted">Alamat</label>
                                <textarea name="alamat" class="form-control" rows="3" required placeholder="Alamat lengkap..."><?= $edit_data['alamat'] ?? '' ?></textarea>
                            </div>

                            <button type="submit" name="simpan" class="btn btn-primary w-100 fw-bold">Simpan Data</button>
                            <?php if($edit_data): ?>
                                <a href="pelanggan.php" class="btn btn-light w-100 mt-2">Batal Edit</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-people"></i> Daftar Pelanggan</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Nama Pelanggan</th>
                                        <th>Alamat</th> <th>Kontak (WA)</th>
                                        <th class="text-end pe-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = pg_fetch_assoc($data_pelanggan)): ?>
                                    
                                    <?php
                                        // LOGIKA WA: Ubah 08 jadi 628 biar link valid
                                        $hp_db = $row['hp'];
                                        // Hapus spasi/strip kalo ada
                                        $hp_clean = preg_replace('/[^0-9]/', '', $hp_db);
                                        
                                        if(substr($hp_clean, 0, 1) == '0'){
                                            $hp_wa = '62' . substr($hp_clean, 1);
                                        } else {
                                            $hp_wa = $hp_clean;
                                        }
                                    ?>

                                    <tr>
                                        <td class="ps-4 fw-bold"><?= $row['nama'] ?></td>
                                        
                                        <td class="small text-muted" style="max-width: 200px;"><?= $row['alamat'] ?></td>
                                        
                                        <td>
                                            <a href="https://wa.me/<?= $hp_wa ?>" target="_blank" class="badge text-decoration-none bg-success p-2">
                                                <i class="bi bi-whatsapp"></i> <?= $row['hp'] ?>
                                            </a>
                                        </td>
                                        
                                        <td class="text-end pe-4">
                                            <a href="pelanggan.php?edit=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                            <a href="pelanggan.php?hapus=<?= $row['id'] ?>" onclick="return confirm('Yakin hapus? Data transaksi juga akan hilang.')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
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