<?php
include 'koneksi.php';

// 1. AMBIL DATA YANG MAU DIEDIT
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $data = pg_fetch_assoc(pg_query($conn, "SELECT * FROM transaksi WHERE id = '$id'"));
}

// 2. PROSES UPDATE
if (isset($_POST['update'])) {
    $id           = $_POST['id'];
    $pelanggan_id = $_POST['pelanggan_id'];
    $produk_id    = $_POST['produk_id'];
    $jumlah       = $_POST['jumlah'];
    $status       = $_POST['status_pembayaran'];

    // Hitung ulang total harga (takutnya ganti produk/jumlah)
    $cek_harga = pg_fetch_assoc(pg_query($conn, "SELECT harga FROM produk WHERE id = '$produk_id'"));
    $total_baru = $cek_harga['harga'] * $jumlah;

    $query = "UPDATE transaksi SET 
              pelanggan_id='$pelanggan_id', 
              produk_id='$produk_id', 
              jumlah='$jumlah', 
              total_harga='$total_baru', 
              status_pembayaran='$status' 
              WHERE id='$id'";

    if (pg_query($conn, $query)) {
        echo "<script>alert('Update Berhasil!'); window.location='index.php';</script>";
    } else {
        echo "Gagal update: " . pg_last_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Transaksi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow col-md-6 mx-auto">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Edit Transaksi #<?= $data['id'] ?></h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="id" value="<?= $data['id'] ?>">

                <div class="mb-3">
                    <label>Pelanggan</label>
                    <select name="pelanggan_id" class="form-select">
                        <?php
                        $p_query = pg_query($conn, "SELECT * FROM pelanggan");
                        while($p = pg_fetch_assoc($p_query)){
                            $sel = ($p['id'] == $data['pelanggan_id']) ? 'selected' : '';
                            echo "<option value='{$p['id']}' $sel>{$p['nama']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Produk</label>
                    <select name="produk_id" class="form-select">
                        <?php
                        $pr_query = pg_query($conn, "SELECT * FROM produk");
                        while($pr = pg_fetch_assoc($pr_query)){
                            $sel = ($pr['id'] == $data['produk_id']) ? 'selected' : '';
                            echo "<option value='{$pr['id']}' $sel>{$pr['nama_produk']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Qty / Jumlah</label>
                    <input type="number" name="jumlah" class="form-control" value="<?= $data['jumlah'] ?>">
                </div>

                <div class="mb-3">
                    <label>Status Pembayaran</label>
                    <select name="status_pembayaran" class="form-select">
                        <option value="Lunas" <?= ($data['status_pembayaran'] == 'Lunas') ? 'selected' : '' ?>>✅ Lunas</option>
                        <option value="Belum Lunas" <?= ($data['status_pembayaran'] != 'Lunas') ? 'selected' : '' ?>>⏳ Belum Lunas</option>
                    </select>
                </div>

                <button type="submit" name="update" class="btn btn-success w-100">Simpan Perubahan</button>
                <a href="index.php" class="btn btn-secondary w-100 mt-2">Batal</a>
            </form>
        </div>
    </div>
</div>

</body>
</html>