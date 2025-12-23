<?php
include '../../config/koneksi.php'; 
include '../../auth/auth.php'; 

$id = $_GET['id'] ?? '';

// 1. AMBIL DATA LAMA (Sebelum diedit)
$q_old = pg_query($conn, "SELECT * FROM transaksi WHERE id_transaksi = '$id'");
$data = pg_fetch_assoc($q_old);

if (!$data) {
    header("Location: ../../index.php"); exit();
}

// 2. PROSES SIMPAN PERUBAHAN
if (isset($_POST['update'])) {
    $pelanggan_id = $_POST['pelanggan_id'];
    $produk_id    = $_POST['produk_id'];
    $waktu        = $_POST['waktu_order']; // Input Tanggal Baru
    $jumlah       = $_POST['jumlah'];
    
    // Ambil Data Ukuran (jika ada)
    $panjang      = !empty($_POST['panjang']) ? str_replace(',', '.', $_POST['panjang']) : 0;
    $lebar        = !empty($_POST['lebar']) ? str_replace(',', '.', $_POST['lebar']) : 0;
    
    // Hitung Ulang Total Harga
    $cek_produk   = pg_fetch_assoc(pg_query($conn, "SELECT harga, jenis_satuan FROM produk WHERE id_produk = '$produk_id'"));
    $harga_base   = $cek_produk['harga'];
    
    if ($cek_produk['jenis_satuan'] == 'Meter') {
        $harga_satuan_fix = $panjang * $lebar * $harga_base;
    } else {
        $harga_satuan_fix = $harga_base;
        $panjang = 0; $lebar = 0; // Reset ukuran jika bukan meter
    }
    $total_baru = $harga_satuan_fix * $jumlah;

    // Status & Pembayaran
    $status_bayar = $_POST['status_pembayaran'];
    $status_order = $_POST['status_order'];
    $metode       = $_POST['metode_pembayaran'];
    $id_bank      = ($metode == 'Transfer') ? $_POST['bank_id'] : 'NULL';

    // --- LOGIC STOK (PENTING!) ---
    // 1. Balikin stok lama dulu (Cancel transaksi lama secara stok)
    $stok_balik = $data['jumlah'];
    $prod_lama  = $data['id_produk'];
    pg_query($conn, "UPDATE produk SET stok_bahan = stok_bahan + $stok_balik WHERE id_produk = '$prod_lama'");

    // 2. Update Transaksi
    $query = "UPDATE transaksi SET 
              id_pelanggan='$pelanggan_id', 
              id_produk='$produk_id', 
              waktu_order='$waktu',
              jumlah='$jumlah', 
              panjang='$panjang',
              lebar='$lebar',
              total_harga='$total_baru', 
              status_pembayaran='$status_bayar',
              status_order='$status_order',
              metode_pembayaran='$metode',
              id_bank=$id_bank
              WHERE id_transaksi='$id'";
    
    if (pg_query($conn, $query)) {
        // 3. Potong stok baru (Sesuai order editan)
        pg_query($conn, "UPDATE produk SET stok_bahan = stok_bahan - $jumlah WHERE id_produk = '$produk_id'");
        
        // Balik ke dashboard
        header("Location: ../../index.php");
    } else {
        echo "Gagal update: " . pg_last_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Edit Transaksi Lengkap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        // Copy Paste Logic JS dari baru.php biar bisa hitung otomatis
        function cekJenisProduk() {
            let select = document.getElementById('produk');
            let option = select.options[select.selectedIndex];
            let jenis = option.getAttribute('data-jenis');
            let areaUkuran = document.getElementById('area_ukuran');
            
            if (jenis === 'Meter') {
                areaUkuran.style.display = 'flex';
            } else {
                areaUkuran.style.display = 'none';
                // Reset nilai jika hidden biar hitungan benar
                document.getElementById('panjang').value = 0;
                document.getElementById('lebar').value = 0;
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
                let p = parseFloat(document.getElementById('panjang').value) || 1;
                let l = parseFloat(document.getElementById('lebar').value) || 1;
                // Kalau P atau L 0 (belum diisi), anggap 1 dulu buat estimasi, atau 0
                if(p==0) p=1; if(l==0) l=1;
                total = (p * l * harga) * qty;
            } else {
                total = harga * qty;
            }
            document.getElementById('total_tampil').value = new Intl.NumberFormat('id-ID').format(total);
        }

        function cekMetode() {
            let metode = document.querySelector('input[name="metode_pembayaran"]:checked').value;
            document.getElementById('area_bank').style.display = (metode === 'Transfer') ? 'block' : 'none';
        }

        // Jalanin saat halaman dimuat (biar data lama terisi benar tampilannya)
        window.onload = function() {
            cekJenisProduk();
            cekMetode();
            hitung(); // Hitung ulang untuk memastikan tampilan harga benar
        };
    </script>
</head>
<body class="bg-light">

    <div class="container py-5">
        <div class="card shadow mx-auto" style="max-width: 700px;">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Edit Transaksi #<?= $data['id_transaksi'] ?></h5>
                <a href="../../index.php" class="btn btn-sm btn-light text-primary fw-bold">Kembali</a>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    
                    <div class="mb-3">
                        <label class="fw-bold small">Waktu Order</label>
                        <input type="datetime-local" name="waktu_order" class="form-control" 
                               value="<?= date('Y-m-d\TH:i', strtotime($data['waktu_order'])) ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold small">Pelanggan</label>
                            <select name="pelanggan_id" class="form-select" required>
                                <?php
                                $qp = pg_query($conn, "SELECT * FROM pelanggan ORDER BY nama ASC");
                                while ($p = pg_fetch_assoc($qp)) {
                                    $sel = ($p['id_pelanggan'] == $data['id_pelanggan']) ? 'selected' : '';
                                    echo "<option value='{$p['id_pelanggan']}' $sel>{$p['nama']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold small">Produk</label>
                            <select name="produk_id" id="produk" class="form-select" onchange="cekJenisProduk()" required>
                                <?php
                                $qpr = pg_query($conn, "SELECT * FROM produk ORDER BY nama_produk ASC");
                                while ($pr = pg_fetch_assoc($qpr)) {
                                    $sel = ($pr['id_produk'] == $data['id_produk']) ? 'selected' : '';
                                    echo "<option value='{$pr['id_produk']}' data-harga='{$pr['harga']}' data-jenis='{$pr['jenis_satuan']}' $sel>{$pr['nama_produk']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div id="area_ukuran" class="row bg-warning bg-opacity-10 p-2 rounded mb-3 border border-warning" style="display:none;">
                        <div class="col-12 mb-2"><small class="text-warning fw-bold">Ukuran (Meter)</small></div>
                        <div class="col-6">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">P</span>
                                <input type="number" step="0.01" name="panjang" id="panjang" class="form-control" value="<?= floatval($data['panjang']) ?>" oninput="hitung()">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">L</span>
                                <input type="number" step="0.01" name="lebar" id="lebar" class="form-control" value="<?= floatval($data['lebar']) ?>" oninput="hitung()">
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-4">
                            <label class="fw-bold small">Qty</label>
                            <input type="number" name="jumlah" id="qty" class="form-control" value="<?= $data['jumlah'] ?>" oninput="hitung()" required>
                        </div>
                        <div class="col-8">
                            <label class="fw-bold small">Total Harga (Otomatis)</label>
                            <input type="text" id="total_tampil" class="form-control fw-bold text-success" readonly>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="fw-bold small d-block">Metode Pembayaran</label>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="metode_pembayaran" id="m1" value="Cash" onclick="cekMetode()" <?= $data['metode_pembayaran']=='Cash'?'checked':'' ?>>
                            <label class="btn btn-outline-secondary btn-sm" for="m1">Cash</label>

                            <input type="radio" class="btn-check" name="metode_pembayaran" id="m2" value="Transfer" onclick="cekMetode()" <?= $data['metode_pembayaran']=='Transfer'?'checked':'' ?>>
                            <label class="btn btn-outline-secondary btn-sm" for="m2">Transfer</label>
                        </div>
                        
                        <div id="area_bank" class="mt-2" style="display:none;">
                            <select name="bank_id" class="form-select form-select-sm">
                                <option value="">-- Pilih Bank --</option>
                                <?php
                                $qb = pg_query($conn, "SELECT * FROM bank_akun");
                                while($b = pg_fetch_assoc($qb)){
                                    $sel = ($b['id_bank'] == $data['id_bank']) ? 'selected' : '';
                                    echo "<option value='{$b['id_bank']}' $sel>{$b['nama_bank']} - {$b['no_rekening']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-6">
                            <label class="fw-bold small">Status Bayar</label>
                            <select name="status_pembayaran" class="form-select">
                                <option value="Lunas" <?= $data['status_pembayaran']=='Lunas'?'selected':'' ?>>Lunas</option>
                                <option value="Belum Lunas" <?= $data['status_pembayaran']!='Lunas'?'selected':'' ?>>Belum Lunas</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="fw-bold small">Status Pengerjaan</label>
                            <select name="status_order" class="form-select">
                                <option value="Proses" <?= $data['status_order']=='Proses'?'selected':'' ?>>Proses</option>
                                <option value="Selesai" <?= $data['status_order']=='Selesai'?'selected':'' ?>>Selesai</option>
                                <option value="Done" <?= $data['status_order']=='Done'?'selected':'' ?>>Done</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="update" class="btn btn-success w-100 fw-bold">SIMPAN PERUBAHAN</button>
                    <a href="../../index.php" class="btn btn-secondary w-100 mt-2">Batal</a>
                </form>
            </div>
        </div>
    </div>

</body>
</html>