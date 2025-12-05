<?php 
include 'koneksi.php'; 
// (Logic PHP bagian atas TETAP SAMA seperti sebelumnya, copy dari file lamamu)
// Langsung ke bagian HTML-nya biar keren:

if (isset($_POST['simpan'])) {
    // ... Copy logic PHP simpan & update stok dari file sebelumnya di sini ...
    // (Biar gak kepanjangan di chat, intinya logic PHP-nya sama persis)
    $pelanggan_id = $_POST['pelanggan_id'];
    $produk_id    = $_POST['produk_id'];
    $jumlah       = $_POST['jumlah'];
    $status_bayar = $_POST['status_pembayaran'];
    $status_order = 'Proses'; 

    $cek_produk = pg_fetch_assoc(pg_query($conn, "SELECT harga, stok_bahan FROM produk WHERE id = '$produk_id'"));
    $harga_satuan = $cek_produk['harga'];
    $stok_now     = $cek_produk['stok_bahan'];
    $total_bayar  = $harga_satuan * $jumlah;

    if ($stok_now < $jumlah) {
        echo "<script>alert('Stok Kurang!');</script>";
    } else {
        $query = "INSERT INTO transaksi (pelanggan_id, produk_id, tgl_order, jumlah, total_harga, status_pembayaran, status_order) 
                  VALUES ('$pelanggan_id', '$produk_id', CURRENT_DATE, '$jumlah', '$total_bayar', '$status_bayar', '$status_order')";
        
        if (pg_query($conn, $query)) {
            $stok_baru = $stok_now - $jumlah;
            pg_query($conn, "UPDATE produk SET stok_bahan = $stok_baru WHERE id = '$produk_id'");
            header("Location: index.php"); 
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Order Baru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Poppins', sans-serif; }
        .form-control, .form-select { padding: 0.75rem 1rem; border-radius: 0.5rem; }
        .form-control:focus, .form-select:focus { box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15); border-color: #0d6efd; }
        .card { border: none; border-radius: 1rem; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                
                <a href="index.php" class="text-decoration-none text-muted mb-3 d-inline-block fw-bold">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                </a>

                <div class="card shadow-lg">
                    <div class="card-header bg-primary text-white py-3 text-center rounded-top-4">
                        <h4 class="mb-0 fw-bold">Buat Transaksi Baru</h4>
                        <small class="opacity-75">Isi data pesanan dengan teliti ya</small>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <form method="POST">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold text-secondary text-uppercase small">Pelanggan</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person-circle text-primary"></i></span>
                                    <select name="pelanggan_id" class="form-select border-start-0 ps-0" required>
                                        <option value="">-- Cari Nama --</option>
                                        <?php
                                        $q = pg_query($conn, "SELECT * FROM pelanggan ORDER BY nama ASC");
                                        while ($p = pg_fetch_assoc($q)) echo "<option value='{$p['id']}'>{$p['nama']}</option>";
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold text-secondary text-uppercase small">Produk / Layanan</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-box-seam text-primary"></i></span>
                                    <select name="produk_id" id="produk" class="form-select border-start-0 ps-0" onchange="hitung()" required>
                                        <option value="" data-harga="0">-- Pilih Produk --</option>
                                        <?php
                                        $q = pg_query($conn, "SELECT * FROM produk ORDER BY nama_produk ASC");
                                        while ($pr = pg_fetch_assoc($q)) echo "<option value='{$pr['id']}' data-harga='{$pr['harga']}'>{$pr['nama_produk']} (Stok: {$pr['stok_bahan']})</option>";
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-5 mb-4">
                                    <label class="form-label fw-bold text-secondary text-uppercase small">Qty</label>
                                    <input type="number" name="jumlah" id="qty" class="form-control text-center fw-bold" placeholder="0" oninput="hitung()" required>
                                </div>
                                <div class="col-7 mb-4">
                                    <label class="form-label fw-bold text-secondary text-uppercase small">Estimasi Total</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-success text-white border-0 fw-bold">Rp</span>
                                        <input type="text" id="total_tampil" class="form-control bg-success bg-opacity-10 text-success fw-bold border-0" readonly value="0">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold text-secondary text-uppercase small">Pembayaran</label>
                                <div class="d-flex gap-2">
                                    <input type="radio" class="btn-check" name="status_pembayaran" id="option1" value="Lunas" required checked>
                                    <label class="btn btn-outline-success w-50 fw-bold" for="option1"><i class="bi bi-check-circle me-1"></i> LUNAS</label>

                                    <input type="radio" class="btn-check" name="status_pembayaran" id="option2" value="Belum Lunas">
                                    <label class="btn btn-outline-warning w-50 fw-bold" for="option2"><i class="bi bi-hourglass-split me-1"></i> UTANG</label>
                                </div>
                            </div>

                            <button type="submit" name="simpan" class="btn btn-primary w-100 py-3 fw-bold shadow-sm rounded-pill">
                                <i class="bi bi-save me-2"></i> SIMPAN ORDER
                            </button>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function hitung() {
            let p = document.getElementById('produk');
            let h = p.options[p.selectedIndex].getAttribute('data-harga');
            let q = document.getElementById('qty').value;
            document.getElementById('total_tampil').value = new Intl.NumberFormat('id-ID').format(h * q);
        }
    </script>
</body>
</html>