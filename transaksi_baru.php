<?php 
include 'koneksi.php'; 
include 'auth.php'; 

$error_message = ''; 
$form_pelanggan_id = $_POST['pelanggan_id'] ?? '';
$form_produk_id = $_POST['produk_id'] ?? '';
$form_jumlah = $_POST['jumlah'] ?? '1';
$form_status_bayar = $_POST['status_pembayaran'] ?? 'Lunas';
$form_metode = $_POST['metode_pembayaran'] ?? 'Cash';
$form_bank = $_POST['bank_id'] ?? '';

// Default nilai form ukuran
$form_p = $_POST['panjang'] ?? '';
$form_l = $_POST['lebar'] ?? '';

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
    
    // DATA PEMBAYARAN BARU
    $metode       = $_POST['metode_pembayaran'];
    // Jika Cash, id_bank harus NULL. Jika Transfer, ambil id_bank
    $id_bank      = ($metode == 'Transfer') ? $_POST['bank_id'] : 'NULL'; 
    
    $panjang = !empty($_POST['panjang']) ? str_replace(',', '.', $_POST['panjang']) : 1;
    $lebar   = !empty($_POST['lebar']) ? str_replace(',', '.', $_POST['lebar']) : 1;

    $cek_produk = pg_fetch_assoc(pg_query($conn, "SELECT harga, stok_bahan, nama_produk, jenis_satuan FROM produk WHERE id_produk = '$produk_id'"));
    $harga_base   = $cek_produk['harga'];
    $stok_now     = $cek_produk['stok_bahan'];
    $jenis        = $cek_produk['jenis_satuan']; 

    if ($jenis == 'Meter') {
        $harga_satuan_fix = $panjang * $lebar * $harga_base;
    } else {
        $harga_satuan_fix = $harga_base;
        $panjang = 0; $lebar = 0;
    }
    
    $total_bayar = $harga_satuan_fix * $jumlah;

    if ($stok_now < $jumlah) {
        $error_message = "<div class='alert alert-danger fw-bold'>❌ Stok Kurang! Sisa: {$stok_now}</div>";
    } else {
        $id_transaksi_baru = generateNewId($conn, 'transaksi', 'T', 'id_transaksi');
        
        // QUERY INSERT UPDATE: Tambah metode_pembayaran dan id_bank
        $query = "INSERT INTO transaksi (id_transaksi, id_pelanggan, id_produk, waktu_order, jumlah, total_harga, status_pembayaran, status_order, panjang, lebar, metode_pembayaran, id_bank) 
                  VALUES ('$id_transaksi_baru', '$pelanggan_id', '$produk_id', CURRENT_TIMESTAMP, '$jumlah', '$total_bayar', '$status_bayar', '$status_order', '$panjang', '$lebar', '$metode', $id_bank)";
        
        if (pg_query($conn, $query)) {
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
        #area_ukuran, #area_bank { transition: all 0.3s ease-in-out; }
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
                                    $q = pg_query($conn, "SELECT id_produk, nama_produk, harga, stok_bahan, jenis_satuan FROM produk ORDER BY nama_produk ASC");
                                    while ($pr = pg_fetch_assoc($q)) {
                                        $sel = ($pr['id_produk'] == $form_produk_id) ? 'selected' : '';
                                        echo "<option value='{$pr['id_produk']}' data-harga='{$pr['harga']}' data-jenis='{$pr['jenis_satuan']}' $sel>{$pr['nama_produk']} (Stok: {$pr['stok_bahan']})</option>";
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
                                    <label class="fw-bold small text-muted">Qty</label>
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

                            <hr>

                            <div class="mb-3">
                                <label class="fw-bold small text-muted d-block">Metode Pembayaran</label>
                                <div class="btn-group w-100 mb-2" role="group">
                                    <input type="radio" class="btn-check" name="metode_pembayaran" id="metode1" value="Cash" onclick="cekMetode()" <?= ($form_metode == 'Cash') ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-secondary fw-bold" for="metode1"><i class="bi bi-cash"></i> Tunai (Cash)</label>

                                    <input type="radio" class="btn-check" name="metode_pembayaran" id="metode2" value="Transfer" onclick="cekMetode()" <?= ($form_metode == 'Transfer') ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-primary fw-bold" for="metode2"><i class="bi bi-bank"></i> Transfer</label>
                                </div>

                                <div id="area_bank" style="display: none;">
                                    <select name="bank_id" id="bank_id" class="form-select border-primary bg-primary bg-opacity-10 text-primary fw-bold">
                                        <option value="">-- Pilih Bank Tujuan Transfer --</option>
                                        <?php
                                        $q_bank = pg_query($conn, "SELECT * FROM bank_akun");
                                        while ($b = pg_fetch_assoc($q_bank)) {
                                            $sel = ($b['id_bank'] == $form_bank) ? 'selected' : '';
                                            echo "<option value='{$b['id_bank']}' $sel>{$b['nama_bank']} - {$b['no_rekening']} (a.n {$b['atas_nama']})</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="fw-bold small text-muted d-block">Status Pembayaran</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="status_pembayaran" id="bayar1" value="Lunas" <?= ($form_status_bayar == 'Lunas') ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-success fw-bold" for="bayar1">LUNAS</label>

                                    <input type="radio" class="btn-check" name="status_pembayaran" id="bayar2" value="Belum Lunas" <?= ($form_status_bayar != 'Lunas') ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-warning fw-bold" for="bayar2">BELUM LUNAS</label>
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
            
            if (jenis === 'Meter') {
                areaUkuran.style.display = 'flex';
                if(document.getElementById('panjang').value == '') document.getElementById('panjang').value = 1;
                if(document.getElementById('lebar').value == '') document.getElementById('lebar').value = 1;
                info.innerText = "Rumus: (Panjang x Lebar x Harga) x Qty";
            } else {
                areaUkuran.style.display = 'none';
                info.innerText = "Rumus: Harga x Qty";
            }
            hitung();
        }

        // FUNGSI TOGGLE BANK
        function cekMetode() {
            let metode = document.querySelector('input[name="metode_pembayaran"]:checked').value;
            let areaBank = document.getElementById('area_bank');
            let selectBank = document.getElementById('bank_id');

            if (metode === 'Transfer') {
                areaBank.style.display = 'block';
                selectBank.setAttribute('required', 'required'); // Wajib pilih jika transfer
            } else {
                areaBank.style.display = 'none';
                selectBank.removeAttribute('required');
                selectBank.value = ""; // Reset pilihan
            }
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
                total = (p * l * harga) * qty;
            } else {
                total = harga * qty;
            }
            document.getElementById('total_tampil').value = new Intl.NumberFormat('id-ID').format(total);
        }

        document.addEventListener('DOMContentLoaded', () => {
            cekJenisProduk();
            cekMetode(); // Cek metode pas load (buat handle error/back button)
        });
    </script>
</body>
</html>