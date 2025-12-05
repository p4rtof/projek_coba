<?php 
include 'koneksi.php'; // Sambung ke database

// --- 1. LOGIC HAPUS (DELETE) ---
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    pg_query($conn, "DELETE FROM transaksi WHERE id = '$id_hapus'");
    echo "<script>alert('Data berhasil dihapus!'); window.location='index.php';</script>";
}

// --- 2. LOGIC TAMBAH TRANSAKSI (CREATE) ---
if (isset($_POST['simpan'])) {
    $pelanggan_id = $_POST['pelanggan_id'];
    $produk_id    = $_POST['produk_id'];
    $jumlah       = $_POST['jumlah'];

    // Ambil harga produk buat hitung total
    $cek_harga = pg_fetch_assoc(pg_query($conn, "SELECT harga FROM produk WHERE id = '$produk_id'"));
    $total     = $cek_harga['harga'] * $jumlah;

    $query = "INSERT INTO transaksi (pelanggan_id, produk_id, tgl_order, jumlah, total_harga) 
              VALUES ('$pelanggan_id', '$produk_id', CURRENT_DATE, '$jumlah', '$total')";
    
    if (pg_query($conn, $query)) {
        echo "<script>alert('Order Berhasil Disimpan!'); window.location='index.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Admin Dashboard - Percetakan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>body{background:#f4f6f9}</style>
</head>
<body>

    <nav class="navbar navbar-dark bg-primary mb-4 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-printer"></i> Admin Percetakan</a>
            <div>
                <a href="pelanggan.php" class="btn btn-sm btn-light fw-bold text-primary">Data Pelanggan</a>
                <a href="produk.php" class="btn btn-sm btn-light fw-bold text-primary">Data Produk</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-plus-circle"></i> Transaksi Baru</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            
                            <div class="mb-3">
                                <label class="small text-muted fw-bold">PELANGGAN</label>
                                <select name="pelanggan_id" class="form-select" required>
                                    <option value="">-- Pilih Pelanggan --</option>
                                    <?php
                                    $q_pel = pg_query($conn, "SELECT * FROM pelanggan ORDER BY nama ASC");
                                    while ($p = pg_fetch_assoc($q_pel)) {
                                        echo "<option value='{$p['id']}'>{$p['nama']}</option>";
                                    }
                                    ?>
                                </select>
                                <a href="pelanggan.php" class="small text-decoration-none">+ Tambah Pelanggan Baru</a>
                            </div>

                            <div class="mb-3">
                                <label class="small text-muted fw-bold">PRODUK / LAYANAN</label>
                                <select name="produk_id" class="form-select" required>
                                    <option value="">-- Pilih Produk --</option>
                                    <?php
                                    $q_prod = pg_query($conn, "SELECT * FROM produk ORDER BY nama_produk ASC");
                                    while ($pr = pg_fetch_assoc($q_prod)) {
                                        echo "<option value='{$pr['id']}'>{$pr['nama_produk']} (Rp " . number_format($pr['harga']) . ")</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="small text-muted fw-bold">JUMLAH ORDER</label>
                                <input type="number" name="jumlah" class="form-control" placeholder="0" required>
                            </div>

                            <button type="submit" name="simpan" class="btn btn-primary w-100 fw-bold">SIMPAN TRANSAKSI</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-receipt"></i> Riwayat Transaksi</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Tanggal</th>
                                        <th>Pelanggan</th>
                                        <th>Item</th>
                                        <th>Total</th>
                                        <th class="text-end pe-3">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Join 3 Tabel (Transaksi + Pelanggan + Produk) biar namanya muncul
                                    $query_list = "SELECT t.id, t.tgl_order, t.jumlah, t.total_harga, 
                                                          p.nama AS nama_pelanggan, 
                                                          pr.nama_produk 
                                                   FROM transaksi t 
                                                   JOIN pelanggan p ON t.pelanggan_id = p.id 
                                                   JOIN produk pr ON t.produk_id = pr.id 
                                                   ORDER BY t.id DESC";
                                    
                                    $tampil = pg_query($conn, $query_list);
                                    
                                    while ($r = pg_fetch_assoc($tampil)) :
                                    ?>
                                    <tr>
                                        <td class="ps-3 small text-muted"><?= $r['tgl_order'] ?></td>
                                        <td class="fw-bold"><?= $r['nama_pelanggan'] ?></td>
                                        <td>
                                            <?= $r['nama_produk'] ?> <br>
                                            <span class="badge bg-secondary rounded-pill"><?= $r['jumlah'] ?> pcs</span>
                                        </td>
                                        <td class="text-success fw-bold">Rp <?= number_format($r['total_harga'], 0, ',', '.') ?></td>
                                        <td class="text-end pe-3">
                                            <a href="index.php?hapus=<?= $r['id'] ?>" 
                                               onclick="return confirm('Yakin mau hapus data ini?')" 
                                               class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </a>
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