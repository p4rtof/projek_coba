<?php 
include 'koneksi.php'; 

// --- LOGIC SIMPAN TRANSAKSI & POTONG STOK ---
if (isset($_POST['simpan'])) {
    $pelanggan_id = $_POST['pelanggan_id'];
    $produk_id    = $_POST['produk_id'];
    $jumlah       = $_POST['jumlah'];
    $status_bayar = $_POST['status_pembayaran'];
    $status_order = 'Proses'; // Default awal

    // 1. Cek Data Produk (Harga & Stok)
    $cek_produk = pg_fetch_assoc(pg_query($conn, "SELECT harga, stok_bahan FROM produk WHERE id = '$produk_id'"));
    $harga_satuan = $cek_produk['harga'];
    $stok_now     = $cek_produk['stok_bahan'];
    $total_bayar  = $harga_satuan * $jumlah;

    // 2. Validasi Stok
    if ($stok_now < $jumlah) {
        echo "<script>alert('GAGAL: Stok tidak cukup! Sisa stok: $stok_now');</script>";
    } else {
        // 3. Simpan Transaksi
        $query = "INSERT INTO transaksi (pelanggan_id, produk_id, tgl_order, jumlah, total_harga, status_pembayaran, status_order) 
                  VALUES ('$pelanggan_id', '$produk_id', CURRENT_DATE, '$jumlah', '$total_bayar', '$status_bayar', '$status_order')";
        
        if (pg_query($conn, $query)) {
            // 4. Update Stok Berkurang
            $stok_baru = $stok_now - $jumlah;
            pg_query($conn, "UPDATE produk SET stok_bahan = $stok_baru WHERE id = '$produk_id'");
            
            echo "<script>alert('Transaksi Berhasil Disimpan!'); window.location='index.php';</script>";
        } else {
            echo "Error: " . pg_last_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Input Transaksi Baru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>body{background:#f4f6f9}</style>
</head>
<body>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                
                <a href="index.php" class="btn btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>

                <div class="card shadow border-0">
                    <div class="card-header bg-primary text-white py-3">
                        <h4 class="mb-0 fw-bold"><i class="bi bi-plus-circle-fill"></i> Buat Transaksi Baru</h4>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Pilih Pelanggan</label>
                                <div class="input-group">
                                    <select name="pelanggan_id" class="form-select" required>
                                        <option value="">-- Cari Nama Pelanggan --</option>
                                        <?php
                                        $q = pg_query($conn, "SELECT * FROM pelanggan ORDER BY nama ASC");
                                        while ($p = pg_fetch_assoc($q)) {
                                            echo "<option value='{$p['id']}'>{$p['nama']} - {$p['hp']}</option>";
                                        }
                                        ?>
                                    </select>
                                    <a href="pelanggan.php" class="btn btn-outline-primary" title="Tambah Pelanggan Baru"><i class="bi bi-person-plus"></i></a>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Pilih Produk / Layanan</label>
                                <select name="produk_id" id="produk" class="form-select" onchange="hitung()" required>
                                    <option value="" data-harga="0">-- Pilih Produk --</option>
                                    <?php
                                    $q = pg_query($conn, "SELECT * FROM produk ORDER BY nama_produk ASC");
                                    while ($pr = pg_fetch_assoc($q)) {
                                        echo "<option value='{$pr['id']}' data-harga='{$pr['harga']}'>
                                                {$pr['nama_produk']} (Sisa Stok: {$pr['stok_bahan']})
                                              </option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Jumlah (Qty)</label>
                                    <input type="number" name="jumlah" id="qty" class="form-control" placeholder="0" oninput="hitung()" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Total Harga (Rp)</label>
                                    <input type="text" id="total_tampil" class="form-control bg-light fw-bold text-success" readonly value="0">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Status Pembayaran</label>
                                <select name="status_pembayaran" class="form-select" required>
                                    <option value="Belum Lunas">⏳ Belum Lunas (Utang)</option>
                                    <option value="Lunas">✅ Lunas (Bayar Cash)</option>
                                </select>
                            </div>

                            <button type="submit" name="simpan" class="btn btn-success w-100 py-2 fw-bold fs-5">
                                <i class="bi bi-save"></i> SIMPAN TRANSAKSI
                            </button>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function hitung() {
            let produk = document.getElementById('produk');
            let harga = produk.options[produk.selectedIndex].getAttribute('data-harga');
            let qty = document.getElementById('qty').value;
            let total = harga * qty;
            document.getElementById('total_tampil').value = new Intl.NumberFormat('id-ID').format(total);
        }
    </script>

</body>
</html>