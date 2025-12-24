<?php
include '../../config/koneksi.php'; 
include '../../auth/auth.php'; 

$id = $_GET['id'] ?? '';

// 1. AMBIL DATA LAMA
$q_old = pg_query($conn, "SELECT * FROM transaksi WHERE id_transaksi = '$id'");
$data = pg_fetch_assoc($q_old);

if (!$data) {
    header("Location: ../../index.php"); exit();
}

// 2. PROSES UPDATE
if (isset($_POST['update'])) {
    $pelanggan_id = $_POST['pelanggan_id'];
    $produk_id    = $_POST['produk_id'];
    $waktu        = $_POST['waktu_order'];
    $jumlah       = $_POST['jumlah'];
    
    $panjang      = !empty($_POST['panjang']) ? str_replace(',', '.', $_POST['panjang']) : 0;
    $lebar        = !empty($_POST['lebar']) ? str_replace(',', '.', $_POST['lebar']) : 0;
    
    $cek_produk   = pg_fetch_assoc(pg_query($conn, "SELECT harga, jenis_satuan FROM produk WHERE id_produk = '$produk_id'"));
    $harga_base   = $cek_produk['harga'];
    
    if ($cek_produk['jenis_satuan'] == 'Meter') {
        $harga_satuan_fix = $panjang * $lebar * $harga_base;
    } else {
        $harga_satuan_fix = $harga_base;
        $panjang = 0; $lebar = 0;
    }
    $total_baru = $harga_satuan_fix * $jumlah;

    $status_bayar = $_POST['status_pembayaran'];
    $status_order = $_POST['status_order'];
    $metode       = $_POST['metode_pembayaran'];
    $id_bank      = ($metode == 'Transfer') ? $_POST['bank_id'] : 'NULL';

    // Logic Stok
    $stok_balik = $data['jumlah'];
    $prod_lama  = $data['id_produk'];
    pg_query($conn, "UPDATE produk SET stok_bahan = stok_bahan + $stok_balik WHERE id_produk = '$prod_lama'");

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
        pg_query($conn, "UPDATE produk SET stok_bahan = stok_bahan - $jumlah WHERE id_produk = '$produk_id'");
        header("Location: ../../index.php");
    } else {
        echo "Gagal update: " . pg_last_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Edit Transaksi - #<?= $data['id_transaksi'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { --primary: #4f46e5; --secondary: #64748b; --dark: #0f172a; --light: #f8fafc; --border: #e2e8f0; --card-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; color: var(--dark); font-size: 0.9rem; }
        
        .card-modern { background: white; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: var(--card-shadow); overflow: hidden; }
        .header-gradient { background: #fff; border-bottom: 1px solid var(--border); padding: 15px 20px; }
        
        .form-label { font-size: 0.8rem; font-weight: 600; color: var(--secondary); margin-bottom: 0.3rem; }
        .form-control-modern, .form-select-modern { border: 1px solid var(--border); border-radius: 8px; padding: 8px 12px; font-size: 0.9rem; background-color: var(--light); transition: all 0.2s; }
        .form-control-modern:focus, .form-select-modern:focus { background-color: white; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        
        /* Radio Card Style Compact */
        .radio-card-input { display: none; }
        .radio-card-label { display: flex; align-items: center; justify-content: center; cursor: pointer; border: 1px solid var(--border); border-radius: 8px; padding: 8px; background: white; transition: all 0.2s; height: 100%; text-align: center; gap: 6px; font-weight: 600; font-size: 0.85rem; color: var(--secondary); }
        .radio-card-label:hover { background: #f8fafc; border-color: #cbd5e1; }
        
        .radio-card-input:checked + .radio-card-label.label-primary { border-color: var(--primary); background-color: #eef2ff; color: var(--primary); }
        .radio-card-input:checked + .radio-card-label.label-success { border-color: #10b981; background-color: #ecfdf5; color: #059669; }
        .radio-card-input:checked + .radio-card-label.label-warning { border-color: #f59e0b; background-color: #fffbeb; color: #d97706; }

        .btn-modern { background: var(--primary); color: white; border: none; padding: 10px; border-radius: 8px; font-weight: 700; width: 100%; transition: all 0.2s; box-shadow: 0 2px 4px -1px rgba(79, 70, 229, 0.2); }
        .btn-modern:hover { background: #4338ca; transform: translateY(-1px); color: white; }
        
        .price-display { font-size: 1.5rem; font-weight: 800; color: var(--primary); letter-spacing: -0.5px; }
    </style>

    <script>
        function cekJenisProduk() {
            let select = document.getElementById('produk');
            let option = select.options[select.selectedIndex];
            let jenis = option.getAttribute('data-jenis');
            let areaUkuran = document.getElementById('area_ukuran');
            
            if (jenis === 'Meter') {
                areaUkuran.style.display = 'flex';
            } else {
                areaUkuran.style.display = 'none';
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
                if(p==0) p=1; if(l==0) l=1;
                total = (p * l * harga) * qty;
            } else {
                total = harga * qty;
            }
            document.getElementById('total_tampil').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
        }

        function cekMetode() {
            let metode = document.querySelector('input[name="metode_pembayaran"]:checked').value;
            document.getElementById('area_bank').style.display = (metode === 'Transfer') ? 'block' : 'none';
        }

        window.onload = function() {
            cekJenisProduk();
            cekMetode();
            hitung();
        };
    </script>
</head>
<body>

    <div class="container py-4">
        
        <div class="row justify-content-center">
            <div class="col-lg-10"> <div class="d-flex align-items-center mb-3">
                    <a href="../../index.php" class="btn btn-light border rounded-circle shadow-sm me-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-arrow-left text-dark"></i>
                    </a>
                    <div>
                        <h5 class="fw-bold m-0 text-dark">Edit Transaksi</h5>
                        <span class="text-secondary small">ID: #<?= $data['id_transaksi'] ?></span>
                    </div>
                </div>

                <form method="POST">
                    <div class="row g-3">
                        
                        <div class="col-md-7">
                            <div class="card-modern h-100">
                                <div class="header-gradient">
                                    <h6 class="fw-bold m-0 text-dark"><i class="bi bi-pencil-square me-2 text-primary"></i>Rincian Pesanan</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Tanggal</label>
                                            <input type="datetime-local" name="waktu_order" class="form-control form-control-modern" 
                                                   value="<?= date('Y-m-d\TH:i', strtotime($data['waktu_order'])) ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Pelanggan</label>
                                            <select name="pelanggan_id" class="form-select form-select-modern" required>
                                                <?php
                                                $qp = pg_query($conn, "SELECT * FROM pelanggan ORDER BY nama ASC");
                                                while ($p = pg_fetch_assoc($qp)) {
                                                    $sel = ($p['id_pelanggan'] == $data['id_pelanggan']) ? 'selected' : '';
                                                    echo "<option value='{$p['id_pelanggan']}' $sel>{$p['nama']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-12">
                                            <label class="form-label">Produk</label>
                                            <select name="produk_id" id="produk" class="form-select form-select-modern" onchange="cekJenisProduk()" required>
                                                <?php
                                                $qpr = pg_query($conn, "SELECT * FROM produk ORDER BY nama_produk ASC");
                                                while ($pr = pg_fetch_assoc($qpr)) {
                                                    $sel = ($pr['id_produk'] == $data['id_produk']) ? 'selected' : '';
                                                    echo "<option value='{$pr['id_produk']}' data-harga='{$pr['harga']}' data-jenis='{$pr['jenis_satuan']}' $sel>{$pr['nama_produk']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <div class="col-12" id="area_ukuran" style="display: none;">
                                            <div class="p-2 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded w-100">
                                                <small class="text-warning fw-bold d-block mb-1">Ukuran Custom (Meter)</small>
                                                <div class="row g-2">
                                                    <div class="col-6">
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text bg-white border-end-0">P</span>
                                                            <input type="number" step="0.01" name="panjang" id="panjang" class="form-control form-control-modern border-start-0 ps-1" value="<?= floatval($data['panjang']) ?>" oninput="hitung()">
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text bg-white border-end-0">L</span>
                                                            <input type="number" step="0.01" name="lebar" id="lebar" class="form-control form-control-modern border-start-0 ps-1" value="<?= floatval($data['lebar']) ?>" oninput="hitung()">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Qty</label>
                                            <input type="number" name="jumlah" id="qty" class="form-control form-control-modern text-center fw-bold" value="<?= $data['jumlah'] ?>" oninput="hitung()" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="card-modern h-100 d-flex flex-column">
                                <div class="card-body p-4 d-flex flex-column justify-content-between">
                                    
                                    <div class="text-center mb-4 pb-3 border-bottom">
                                        <small class="text-secondary fw-bold text-uppercase ls-1">Estimasi Total</small>
                                        <div id="total_tampil" class="price-display mt-1">Rp 0</div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Metode Pembayaran</label>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <input type="radio" class="radio-card-input" name="metode_pembayaran" id="m1" value="Cash" onclick="cekMetode()" <?= $data['metode_pembayaran']=='Cash'?'checked':'' ?>>
                                                <label class="radio-card-label label-primary" for="m1"><i class="bi bi-cash-coin"></i> Tunai</label>
                                            </div>
                                            <div class="col-6">
                                                <input type="radio" class="radio-card-input" name="metode_pembayaran" id="m2" value="Transfer" onclick="cekMetode()" <?= $data['metode_pembayaran']=='Transfer'?'checked':'' ?>>
                                                <label class="radio-card-label label-primary" for="m2"><i class="bi bi-bank"></i> Transfer</label>
                                            </div>
                                        </div>
                                        <div id="area_bank" class="mt-2" style="display:none;">
                                            <select name="bank_id" class="form-select form-select-modern form-select-sm">
                                                <option value="">-- Pilih Rekening --</option>
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

                                    <div class="row g-2 mb-4">
                                        <div class="col-6">
                                            <label class="form-label">Pembayaran</label>
                                            <select name="status_pembayaran" class="form-select form-select-modern">
                                                <option value="Lunas" <?= $data['status_pembayaran']=='Lunas'?'selected':'' ?>>✅ Lunas</option>
                                                <option value="Belum Lunas" <?= $data['status_pembayaran']!='Lunas'?'selected':'' ?>>⏳ Belum</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Progress</label>
                                            <select name="status_order" class="form-select form-select-modern">
                                                <option value="Proses" <?= $data['status_order']=='Proses'?'selected':'' ?>>⚙️ Proses</option>
                                                <option value="Selesai" <?= $data['status_order']=='Selesai'?'selected':'' ?>>📦 Siap</option>
                                                <option value="Done" <?= $data['status_order']=='Done'?'selected':'' ?>>✅ Selesai </option>
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <button type="submit" name="update" class="btn-modern py-2 mb-2 shadow-sm">
                                            <i class="bi bi-save2-fill me-2"></i> SIMPAN PERUBAHAN
                                        </button>
                                        <a href="../../index.php" class="btn btn-light w-100 border text-secondary fw-bold" style="border-radius:8px;">Batal</a>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>