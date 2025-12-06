<?php
include 'koneksi.php';

// 1. AMBIL DATA
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    // FIX: Change id to id_transaksi and quote $id
    $data = pg_fetch_assoc(pg_query($conn, "SELECT * FROM transaksi WHERE id_transaksi = '$id'"));
}

// 2. PROSES UPDATE
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $pelanggan_id = $_POST['pelanggan_id'];
    $produk_id = $_POST['produk_id'];
    $jumlah = $_POST['jumlah'];
    $status_bayar = $_POST['status_pembayaran'];
    $status_order = $_POST['status_order']; // Tangkap input detail baru

    // Hitung ulang total
    // FIX: Change id to id_produk
    $cek_harga = pg_fetch_assoc(pg_query($conn, "SELECT harga FROM produk WHERE id_produk = '$produk_id'"));
    $total_baru = $cek_harga['harga'] * $jumlah;

    // FIX: Change column names
    $query = "UPDATE transaksi SET 
              id_pelanggan='$pelanggan_id', 
              id_produk='$produk_id', 
              jumlah='$jumlah', 
              total_harga='$total_baru', 
              status_pembayaran='$status_bayar',
              status_order='$status_order'
              WHERE id_transaksi='$id'";

    if (pg_query($conn, $query)) {
        echo "<script>window.location='index.php';</script>";
    } else {
        echo "Gagal: " . pg_last_error($conn);
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
                <h5 class="mb-0">Edit Transaksi #<?= $data['id_transaksi'] ?></h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="id" value="<?= $data['id_transaksi'] ?>">
                    <div class="mb-3">
                        <label class="fw-bold small">Pelanggan</label>
                        <select name="pelanggan_id" class="form-select">
                            <?php
                            $p_query = pg_query($conn, "SELECT * FROM pelanggan");
                            while ($p = pg_fetch_assoc($p_query)) {
                                // FIX: id_pelanggan
                                $sel = ($p['id_pelanggan'] == $data['id_pelanggan']) ? 'selected' : '';
                                echo "<option value='{$p['id_pelanggan']}' $sel>{$p['nama']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold small">Produk</label>
                        <select name="produk_id" class="form-select">
                            <?php
                            $pr_query = pg_query($conn, "SELECT * FROM produk");
                            while ($pr = pg_fetch_assoc($pr_query)) {
                                // FIX: id_produk
                                $sel = ($pr['id_produk'] == $data['id_produk']) ? 'selected' : '';
                                echo "<option value='{$pr['id_produk']}' $sel>{$pr['nama_produk']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold small">Qty</label>
                        <input type="number" name="jumlah" class="form-control" value="<?= $data['jumlah'] ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold small">Pembayaran</label>
                            <select name="status_pembayaran" class="form-select">
                                <option value="Lunas" <?= ($data['status_pembayaran'] == 'Lunas') ? 'selected' : '' ?>>✅
                                    Lunas</option>
                                <option value="Belum Lunas" <?= ($data['status_pembayaran'] != 'Lunas') ? 'selected' : '' ?>>⏳ Belum</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="fw-bold small">Status Order (Detail)</label>
                            <select name="status_order" class="form-select bg-light">
                                <?php
                                // UBAH ARRAY INI: Hapus 'Diambil'
                                $opts = ['Proses', 'Selesai', 'Done'];

                                foreach ($opts as $o) {
                                    $sel = ($data['status_order'] == $o) ? 'selected' : '';
                                    echo "<option value='$o' $sel>$o</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="update" class="btn btn-success w-100">Simpan Perubahan</button>
                    <a href="index.php" class="btn btn-secondary w-100 mt-2">Batal</a>
                </form>
            </div>
        </div>
    </div>

</body>

</html>