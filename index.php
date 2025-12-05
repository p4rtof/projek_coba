<?php 
include 'koneksi.php'; 

// --- 1. LOGIC HAPUS ---
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    pg_query($conn, "DELETE FROM transaksi WHERE id = '$id_hapus'");
    echo "<script>alert('Data berhasil dihapus!'); window.location='index.php';</script>";
}

// --- 2. LOGIC TAMBAH TRANSAKSI ---
if (isset($_POST['simpan'])) {
    $pelanggan_id = $_POST['pelanggan_id'];
    $produk_id    = $_POST['produk_id'];
    $jumlah       = $_POST['jumlah'];
    $status_bayar = $_POST['status_pembayaran'];

    $cek_harga = pg_fetch_assoc(pg_query($conn, "SELECT harga FROM produk WHERE id = '$produk_id'"));
    $total     = $cek_harga['harga'] * $jumlah;

    $query = "INSERT INTO transaksi (pelanggan_id, produk_id, tgl_order, jumlah, total_harga, status_pembayaran) 
              VALUES ('$pelanggan_id', '$produk_id', CURRENT_DATE, '$jumlah', '$total', '$status_bayar')";
    
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

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-printer"></i> Zaddy Printing</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link active fw-bold" href="index.php">Transaksi</a>
                <a class="nav-link" href="pelanggan.php">Pelanggan</a>
                <a class="nav-link" href="produk.php">Produk</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4"> <div class="row">
            
            <div class="col-md-3 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-plus-circle"></i> Transaksi Baru</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="small text-muted fw-bold">PELANGGAN</label>
                                <select name="pelanggan_id" class="form-select" required>
                                    <option value="">-- Pilih --</option>
                                    <?php
                                    $q_pel = pg_query($conn, "SELECT * FROM pelanggan ORDER BY nama ASC");
                                    while ($p = pg_fetch_assoc($q_pel)) {
                                        echo "<option value='{$p['id']}'>{$p['nama']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted fw-bold">PRODUK</label>
                                <select name="produk_id" id="produk" class="form-select" onchange="hitungTotal()" required>
                                    <option value="" data-harga="0">-- Pilih --</option>
                                    <?php
                                    $q_prod = pg_query($conn, "SELECT * FROM produk ORDER BY nama_produk ASC");
                                    while ($pr = pg_fetch_assoc($q_prod)) {
                                        echo "<option value='{$pr['id']}' data-harga='{$pr['harga']}'>{$pr['nama_produk']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted fw-bold">QTY</label>
                                <input type="number" name="jumlah" id="qty" class="form-control" placeholder="0" oninput="hitungTotal()" required>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted fw-bold">TOTAL (Rp)</label>
                                <input type="text" id="tampilan_total" class="form-control bg-light fw-bold text-success" readonly value="0">
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted fw-bold">STATUS</label>
                                <select name="status_pembayaran" class="form-select" required>
                                    <option value="Lunas">✅ Lunas</option>
                                    <option value="Belum Lunas">⏳ Belum Lunas</option>
                                </select>
                            </div>
                            <button type="submit" name="simpan" class="btn btn-primary w-100 fw-bold">SIMPAN</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-table"></i> Riwayat Transaksi</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nama Pelanggan</th>
                                        <th>Produk</th>
                                        <th>Qty</th>
                                        <th>Harga Total</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query_list = "SELECT t.*, p.nama AS nama_pelanggan, pr.nama_produk 
                                                   FROM transaksi t 
                                                   JOIN pelanggan p ON t.pelanggan_id = p.id 
                                                   JOIN produk pr ON t.produk_id = pr.id 
                                                   ORDER BY t.id DESC";
                                    $tampil = pg_query($conn, $query_list);
                                    while ($r = pg_fetch_assoc($tampil)) :
                                    ?>
                                    <tr>
                                        <td>#<?= $r['id'] ?></td>
                                        <td class="fw-bold text-start"><?= $r['nama_pelanggan'] ?></td>
                                        <td class="text-start"><?= $r['nama_produk'] ?></td>
                                        <td><span class="badge bg-secondary"><?= $r['jumlah'] ?></span></td>
                                        <td class="fw-bold text-success">Rp <?= number_format($r['total_harga'], 0, ',', '.') ?></td>
                                        
                                        <td>
                                            <?php if($r['status_pembayaran'] == 'Lunas'): ?>
                                                <span class="badge bg-success">Lunas</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Belum Lunas</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <a href="edit_transaksi.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <a href="index.php?hapus=<?= $r['id'] ?>" onclick="return confirm('Hapus data ini?')" class="btn btn-sm btn-danger">
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

    <script>
        function hitungTotal() {
            var produk = document.getElementById('produk');
            var qtyInput = document.getElementById('qty');
            var tampilanTotal = document.getElementById('tampilan_total');
            var hargaSatuan = produk.options[produk.selectedIndex].getAttribute('data-harga');
            var qty = qtyInput.value;
            var total = hargaSatuan * qty;
            tampilanTotal.value = new Intl.NumberFormat('id-ID').format(total);
        }
    </script>

</body>
</html>