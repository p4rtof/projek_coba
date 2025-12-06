<?php
include 'koneksi.php';
include 'auth.php';

// --- LOGIC HAPUS (Silent) ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Hapus transaksi terkait dulu biar ga error FK
    pg_query($conn, "DELETE FROM transaksi WHERE id_produk = '$id'"); 
    pg_query($conn, "DELETE FROM produk WHERE id_produk = '$id'");
    header("Location: produk.php");
}

// --- LOGIC SIMPAN (Tambah / Edit) ---
if (isset($_POST['simpan'])) {
    $nama  = $_POST['nama_produk'];
    $harga = $_POST['harga'];
    $stok  = $_POST['stok_bahan'];
    $jenis = $_POST['jenis_satuan']; // Ambil input jenis (Pcs/Meter)

    if (!empty($_POST['id_edit'])) {
        // --- MODE EDIT ---
        $id = $_POST['id_edit'];
        $q = "UPDATE produk SET nama_produk='$nama', harga=$harga, stok_bahan=$stok, jenis_satuan='$jenis' WHERE id_produk='$id'";
        pg_query($conn, $q);
    } else {
        // --- MODE TAMBAH BARU (Auto ID) ---
        // 1. Ambil ID terakhir (Misal: B005)
        $q_last = pg_query($conn, "SELECT id_produk FROM produk ORDER BY id_produk DESC LIMIT 1");
        $last_data = pg_fetch_assoc($q_last);
        
        // 2. Generate angka berikutnya
        // Menggunakan prefix 'B' (Barang) agar beda dengan 'P' (Pelanggan)
        $angka = $last_data ? (int)substr($last_data['id_produk'], 1) : 0;
        $id_baru = 'B' . str_pad($angka + 1, 3, '0', STR_PAD_LEFT); // Hasil: B001, B002, dst.

        $q = "INSERT INTO produk (id_produk, nama_produk, harga, stok_bahan, jenis_satuan) 
              VALUES ('$id_baru', '$nama', $harga, $stok, '$jenis')";
        pg_query($conn, $q);
    }
    
    header("Location: produk.php");
}

// --- AMBIL DATA EDIT ---
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
</head>
<body class="bg-light">

<?php include 'navbar.php'; ?>

    <div class="container py-4">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-primary text-white rounded-top-4">
                        <h5 class="fw-bold mb-0"><i class="bi bi-box-seam me-2"></i><?= $edit_data ? 'Edit Produk' : 'Produk Baru' ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="id_edit" value="<?= $edit_data['id_produk'] ?? '' ?>"> 
                            
                            <div class="mb-3">
                                <label class="small text-muted fw-bold">Nama Produk</label>
                                <input type="text" name="nama_produk" class="form-control" placeholder="Masukkan Nama Produk" value="<?= $edit_data['nama_produk'] ?? '' ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="small text-muted fw-bold">Jenis Satuan / Hitungan</label>
                                <select name="jenis_satuan" class="form-select border-primary" required>
                                    <option value="Pcs" <?= ($edit_data['jenis_satuan'] ?? '') == 'Pcs' ? 'selected' : '' ?>>📦 Pcs (Satuan Biasa)</option>
                                    <option value="Meter" <?= ($edit_data['jenis_satuan'] ?? '') == 'Meter' ? 'selected' : '' ?>>📏 Meter (Hitung Panjang x Lebar)</option>
                                </select>
                                <div class="form-text text-primary small fst-italic">
                                    Pilih "Meter" untuk produk seperti Spanduk/Banner agar sistem otomatis menghitung luas (PxL).
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="small text-muted fw-bold">Harga Dasar</label>
                                    <input type="number" name="harga" class="form-control" value="<?= $edit_data['harga'] ?? '' ?>" required>
                                    <div class="form-text small">Per Pcs atau Per Meter</div>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="small text-muted fw-bold">Stok Bahan</label>
                                    <input type="number" name="stok_bahan" class="form-control" value="<?= $edit_data['stok_bahan'] ?? '' ?>" required>
                                </div>
                            </div>

                            <button type="submit" name="simpan" class="btn btn-primary w-100 fw-bold rounded-pill">Simpan Data</button>
                            <?php if($edit_data): ?>
                                <a href="produk.php" class="btn btn-light w-100 mt-2 rounded-pill text-muted">Batal Edit</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center rounded-top-4">
                        <h5 class="mb-0 fw-bold text-dark">Daftar Produk</h5>
                        <form method="GET" class="d-flex" style="width: 250px;">
                            <input type="text" name="q" class="form-control form-control-sm me-2 rounded-pill ps-3" placeholder="Cari produk..." value="<?= $_GET['q'] ?? '' ?>">
                            <button type="submit" class="btn btn-sm btn-outline-primary rounded-circle"><i class="bi bi-search"></i></button>
                        </form>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4 py-3">Nama Produk</th>
                                        <th>Jenis</th>
                                        <th>Harga</th>
                                        <th>Stok</th>
                                        <th class="text-end pe-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // LOGIC QUERY SEARCH
                                    $keyword = $_GET['q'] ?? '';
                                    $q_tampil = "SELECT * FROM produk WHERE nama_produk ILIKE '%$keyword%' ORDER BY id_produk DESC";
                                    $data_produk = pg_query($conn, $q_tampil);

                                    if(pg_num_rows($data_produk) > 0):
                                        while($row = pg_fetch_assoc($data_produk)): 
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark"><?= $row['nama_produk'] ?></td>
                                        <td>
                                            <?php if($row['jenis_satuan'] == 'Meter'): ?>
                                                <span class="badge bg-warning text-dark"><i class="bi bi-rulers"></i> Meter</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><i class="bi bi-box"></i> Pcs</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-primary fw-bold">Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                                        <td>
                                            <span class="text-dark fw-bold text-center">
                                                <?= $row['stok_bahan'] ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="produk.php?edit=<?= $row['id_produk'] ?>" class="btn btn-sm btn-outline-primary rounded-circle me-1" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                                            <a href="produk.php?hapus=<?= $row['id_produk'] ?>" onclick="return confirm('Yakin hapus produk ini? History transaksi terkait juga akan terhapus!')" class="btn btn-sm btn-outline-danger rounded-circle" title="Hapus"><i class="bi bi-trash-fill"></i></a>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile; 
                                    else:
                                    ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted fst-italic">Belum ada data produk.</td></tr>
                                    <?php endif; ?>
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