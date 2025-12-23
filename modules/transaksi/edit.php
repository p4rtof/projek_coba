<?php
include 'koneksi.php';
include 'auth.php'; 

// 1. AMBIL DATA
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $data = pg_fetch_assoc(pg_query($conn, "SELECT * FROM transaksi WHERE id_transaksi = '$id'"));
}

// 2. PROSES UPDATE
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $pelanggan_id = $_POST['pelanggan_id'];
    $produk_id = $_POST['produk_id'];
    $jumlah = $_POST['jumlah'];
    $status_bayar = $_POST['status_pembayaran'];
    $status_order = $_POST['status_order'];
    
    // DATA BARU: Metode & Bank
    $metode = $_POST['metode_pembayaran'];
    $id_bank = ($metode == 'Transfer') ? $_POST['bank_id'] : 'NULL';

    $cek_harga = pg_fetch_assoc(pg_query($conn, "SELECT harga FROM produk WHERE id_produk = '$produk_id'"));
    $total_baru = $cek_harga['harga'] * $jumlah;

    $query = "UPDATE transaksi SET 
              id_pelanggan='$pelanggan_id', 
              id_produk='$produk_id', 
              jumlah='$jumlah', 
              total_harga='$total_baru', 
              status_pembayaran='$status_bayar',
              status_order='$status_order',
              metode_pembayaran='$metode',
              id_bank=$id_bank
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

                    <div class="mb-3 bg-light p-2 border rounded">
                        <label class="fw-bold small">Metode Pembayaran</label>
                        <div class="d-flex gap-3 mb-2">
                            <label><input type="radio" name="metode_pembayaran" value="Cash" onclick="toggleBank()" <?= $data['metode_pembayaran']=='Cash'?'checked':'' ?>> Cash</label>
                            <label><input type="radio" name="metode_pembayaran" value="Transfer" onclick="toggleBank()" <?= $data['metode_pembayaran']=='Transfer'?'checked':'' ?>> Transfer</label>
                        </div>
                        
                        <div id="div_bank" style="display: <?= $data['metode_pembayaran']=='Transfer'?'block':'none' ?>;">
                            <select name="bank_id" class="form-select form-select-sm">
                                <option value="">-- Pilih Bank --</option>
                                <?php
                                $q_bank = pg_query($conn, "SELECT * FROM bank_akun");
                                while($b = pg_fetch_assoc($q_bank)){
                                    $sel = ($b['id_bank'] == $data['id_bank']) ? 'selected' : '';
                                    echo "<option value='{$b['id_bank']}' $sel>{$b['nama_bank']} - {$b['no_rekening']}</option>";
                                } 
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold small">Pembayaran</label>
                            <select name="status_pembayaran" class="form-select">
                                <option value="Lunas" <?= ($data['status_pembayaran'] == 'Lunas') ? 'selected' : '' ?>>✅ Lunas</option>
                                <option value="Belum Lunas" <?= ($data['status_pembayaran'] != 'Lunas') ? 'selected' : '' ?>>⏳ Belum</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="fw-bold small">Status Order</label>
                            <select name="status_order" class="form-select bg-light">
                                <?php
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

    <script>
        function toggleBank() {
            let val = document.querySelector('input[name="metode_pembayaran"]:checked').value;
            document.getElementById('div_bank').style.display = (val === 'Transfer') ? 'block' : 'none';
        }
    </script>
</body>
</html>