<?php 
include 'koneksi.php'; 

$error_message = ''; 
$form_pelanggan_id = $_POST['pelanggan_id'] ?? '';
$form_produk_id = $_POST['produk_id'] ?? '';
$form_jumlah = $_POST['jumlah'] ?? '1';
$form_status_bayar = $_POST['status_pembayaran'] ?? 'Lunas';
// Default nilai form ukuran
$form_p = $_POST['panjang'] ?? '';
$form_l = $_POST['lebar'] ?? '';

// FUNGSI HELPER ID
function generateNewId($conn, $table, $prefix, $id_column) {
    $q_last = pg_query($conn, "SELECT $id_column FROM $table ORDER BY $id_column DESC LIMIT 1");
    $last_id = pg_fetch_assoc($q_last);
    $new_id_num = $last_id ? (int)substr($last_id[$id_column], 1) + 1 : 1;
    return $prefix . str_pad($new_id_num, 3, '0', STR_PAD_LEFT); 
}

if (isset($_POST['simpan'])) {
    $pelanggan_id = $_POST['pelanggan_id']; 
    $produk_id    = $_POST['produk_id'];
    $jumlah       = $_POST['jumlah'];
    $status_bayar = $_POST['status_pembayaran'];
    $status_order = 'Proses'; 
    
    // Ambil input ukuran (default 1 jika kosong/produk pcs)
    $panjang = !empty($_POST['panjang']) ? str_replace(',', '.', $_POST['panjang']) : 1;
    $lebar   = !empty($_POST['lebar']) ? str_replace(',', '.', $_POST['lebar']) : 1;

    // Cek Data Produk
    $cek_produk = pg_fetch_assoc(pg_query($conn, "SELECT harga, stok_bahan, nama_produk, jenis_satuan FROM produk WHERE id_produk = '$produk_id'"));
    $harga_base   = $cek_produk['harga'];
    $stok_now     = $cek_produk['stok_bahan'];
    $jenis        = $cek_produk['jenis_satuan']; // Ambil jenis (Pcs/Meter)

    // --- LOGIKA HITUNG HARGA ---
    if ($jenis == 'Meter') {
        // Rumus: (P x L x Harga_per_meter) * Jumlah_Pcs
        // Contoh: (2m x 1m x 20.000) * 1 spanduk = 40.000
        $harga_satuan_fix = $panjang * $lebar * $harga_base;
    } else {
        // Rumus Biasa
        $harga_satuan_fix = $harga_base;
        // Reset P & L jadi 0 biar rapi di DB kalau bukan meteran
        $panjang = 0; $lebar = 0;
    }
    
    $total_bayar = $harga_satuan_fix * $jumlah;
    // ---------------------------

    if ($stok_now < $jumlah) {
        $error_message = "<div class='alert alert-danger fw-bold'>❌ Stok Kurang! Sisa: {$stok_now}</div>";
    } else {
        $id_transaksi_baru = generateNewId($conn, 'transaksi', 'T', 'id_transaksi');
        
        // Query Insert (Ditambah kolom panjang & lebar)
        $query = "INSERT INTO transaksi (id_transaksi, id_pelanggan, id_produk, waktu_order, jumlah, total_harga, status_pembayaran, status_order, panjang, lebar) 
                  VALUES ('$id_transaksi_baru', '$pelanggan_id', '$produk_id', CURRENT_TIMESTAMP, '$jumlah', '$total_bayar', '$status_bayar', '$status_order', '$panjang', '$lebar')";
        
        if (pg_query($conn, $query)) {
            // Kurangi stok
            $stok_baru = $stok_now - $jumlah;
            pg_query($conn, "UPDATE produk SET stok_bahan = $stok_baru WHERE id_produk = '$produk_id'");
            header("Location: index.php"); 
        } else {
            $error_message = "<div class='alert alert-danger'>Gagal: " . pg_last_error($conn) . "</div>";
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
    <style>
        body { background: #f0f2f5; font-family: sans-serif; }
        /* Animasi biar smooth pas munculin input ukuran */
        #area_ukuran { transition: all 0.3s ease-in-out; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <a href="index.php" class="text-decoration-none text-muted mb-3 d-inline-block fw-bold"><i class="bi bi-arrow-left"></i> Kembali</a>
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header bg-primary text-white py-3 rounded-top-4">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-cart-plus me-2"></i>Buat Transaksi Baru</h5>
                    </div>
                    <div class="card-body p-4">
                        <?= $error_message ?> 
                        <form method="POST">
                            
                            <div class="mb-3">
                                <label class="fw-bold small text-muted">Pelanggan</label>
                                <select name="pelanggan_id" class="form-select" required>
                                    <option value="">-- Pilih Pelanggan --</option>
                                    <?php
                                    $q = pg_query($conn, "SELECT id_pelanggan, nama FROM pelanggan ORDER BY nama ASC");
                                    while ($p = pg_fetch_assoc($q)) {
                                        $sel = ($p['id_pelanggan'] == $form_pelanggan_id) ? 'selected' : '';
                                        // Tampilkan ID di form biar admin yang ambil ID manual dari URL nggak bingung
                                        if (isset($_GET['id_pelanggan']) && $_GET['id_pelanggan'] == $p['id_pelanggan']) $sel = 'selected';
                                        echo "<option value='{$p['id_pelanggan']}' $sel>{$p['nama']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="fw-bold small text-muted">Produk</label>
                                <select name="produk_id" id="produk" class="form-select" onchange="cekJenisProduk()" required>
                                    <option value="" data-harga="0" data-jenis="Pcs">-- Pilih Produk --</option>
                                    <?php
                                    // PENTING: Ambil kolom jenis_satuan
                                    $q = pg_query($conn, "SELECT id_produk, nama_produk, harga, stok_bahan, jenis_satuan FROM produk ORDER BY nama_produk ASC");
                                    while ($pr = pg_fetch_assoc($q)) {
                                        $sel = ($pr['id_produk'] == $form_produk_id) ? 'selected' : '';
                                        // Masukkan jenis ke data-attribute biar JS bisa baca
                                        echo "<option value='{$pr['id_produk']}' data-harga='{$pr['harga']}' data-jenis='{$pr['jenis_satuan']}' $sel>
                                                {$pr['nama_produk']} (Stok: {$pr['stok_bahan']})
                                              </option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div id="area_ukuran" class="row bg-warning bg-opacity-10 p-2 rounded mb-3 border border-warning" style="display:none;">
                                <div class="col-12 mb-2"><small class="text-warning fw-bold"><i class="bi bi-rulers"></i> Masukkan Ukuran (Meter)</small></div>
                                <div class="col-6">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">P</span>
                                        <input type="number" step="0.01" name="panjang" id="panjang" class="form-control fw-bold" placeholder="0" value="<?= $form_p ?>" oninput="hitung()">
                                        <span class="input-group-text">m</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">L</span>
                                        <input type="number" step="0.01" name="lebar" id="lebar" class="form-control fw-bold" placeholder="0" value="<?= $form_l ?>" oninput="hitung()">
                                        <span class="input-group-text">m</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-4">
                                    <label class="fw-bold small text-muted">Qty (Pcs)</label>
                                    <input type="number" name="jumlah" id="qty" class="form-control text-center fw-bold" value="<?= $form_jumlah ?>" oninput="hitung()" required> 
                                </div>
                                <div class="col-8">
                                    <label class="fw-bold small text-muted">Total Bayar</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-success text-white fw-bold">Rp</span>
                                        <input type="text" id="total_tampil" class="form-control bg-success bg-opacity-10 fw-bold text-success" readonly value="0">
                                    </div>
                                    <small id="info_rumus" class="text-muted fst-italic" style="font-size: 0.75rem;"></small>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="fw-bold small text-muted d-block">Status Pembayaran</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="status_pembayaran" id="bayar1" value="Lunas" <?= ($form_status_bayar == 'Lunas') ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-success fw-bold" for="bayar1">LUNAS</label>

                                    <input type="radio" class="btn-check" name="status_pembayaran" id="bayar2" value="Belum Lunas" <?= ($form_status_bayar != 'Lunas') ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-warning fw-bold" for="bayar2">UTANG</label>
                                </div>
                            </div>

                            <button type="submit" name="simpan" class="btn btn-primary w-100 fw-bold py-2">SIMPAN ORDER</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function cekJenisProduk() {
            let select = document.getElementById('produk');
            let option = select.options[select.selectedIndex];
            let jenis = option.getAttribute('data-jenis');
            let areaUkuran = document.getElementById('area_ukuran');
            let info = document.getElementById('info_rumus');
            
            // Jika jenisnya 'Meter', tampilkan input P x L
            if (jenis === 'Meter') {
                areaUkuran.style.display = 'flex';
                // Set default 1 jika kosong biar hitungan gak nol
                if(document.getElementById('panjang').value == '') document.getElementById('panjang').value = 1;
                if(document.getElementById('lebar').value == '') document.getElementById('lebar').value = 1;
                info.innerText = "Rumus: (Panjang x Lebar x Harga) x Qty";
            } else {
                areaUkuran.style.display = 'none';
                info.innerText = "Rumus: Harga x Qty";
            }
            hitung();
        }

        function hitung() {
            let select = document.getElementById('produk');
            let option = select.options[select.selectedIndex];
            
            let harga = parseFloat(option.getAttribute('data-harga')) || 0;
            let jenis = option.getAttribute('data-jenis');
            let qty = parseFloat(document.getElementById('qty').value) || 0;
            
            let total = 0;

            if (jenis === 'Meter') {
                let p = parseFloat(document.getElementById('panjang').value) || 0;
                let l = parseFloat(document.getElementById('lebar').value) || 0;
                // Hitung Luas x Harga x Qty
                total = (p * l * harga) * qty;
            } else {
                // Hitung Biasa
                total = harga * qty;
            }

            document.getElementById('total_tampil').value = new Intl.NumberFormat('id-ID').format(total);
        }

        // Jalankan saat load (untuk handle error post/back)
        document.addEventListener('DOMContentLoaded', () => {
            cekJenisProduk();
        });
    </script>
</body>
</html>