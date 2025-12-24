<?php 
include '../../config/koneksi.php'; 
include '../../auth/auth.php'; 

// Fungsi ID Generator
function generateNewId($conn, $table, $prefix, $id_column) {
    $q_last = pg_query($conn, "SELECT $id_column FROM $table ORDER BY $id_column DESC LIMIT 1");
    $last_id = pg_fetch_assoc($q_last);
    $new_id_num = $last_id ? (int)substr($last_id[$id_column], 1) + 1 : 1;
    return $prefix . str_pad($new_id_num, 3, '0', STR_PAD_LEFT); 
}

if (isset($_POST['proses_transaksi'])) {
    $pelanggan_id = $_POST['pelanggan_id'];
    $tgl_input    = $_POST['tgl_input'];
    $jam_sekarang = date('H:i:s');
    $waktu_fix    = $tgl_input . ' ' . $jam_sekarang;
    
    $status_bayar = $_POST['status_pembayaran'];
    $metode       = $_POST['metode_pembayaran'];
    $id_bank      = ($metode == 'Transfer') ? $_POST['bank_id'] : 'NULL'; 
    $status_order = 'Proses';

    // Array untuk menampung ID transaksi yang baru dibuat
    $list_id_baru = [];
    $error_db = false;

    // Loop data dari keranjang (Array Input)
    $produk_ids = $_POST['produk_id']; // Array
    
    foreach ($produk_ids as $key => $prod_id) {
        $jumlah  = $_POST['jumlah'][$key];
        $panjang = $_POST['panjang'][$key];
        $lebar   = $_POST['lebar'][$key];
        
        // Hitung ulang harga di backend biar aman (Sama seperti logika JS)
        $cek_produk = pg_fetch_assoc(pg_query($conn, "SELECT harga, stok_bahan, jenis_satuan FROM produk WHERE id_produk = '$prod_id'"));
        $harga_base = $cek_produk['harga'];
        $jenis      = $cek_produk['jenis_satuan'];
        $stok_now   = $cek_produk['stok_bahan'];

        // Cek Stok dulu
        if($stok_now < $jumlah) {
            echo "<script>alert('Stok tidak cukup untuk salah satu item!'); window.history.back();</script>";
            exit;
        }

        if ($jenis == 'Meter') {
            $harga_satuan_fix = $panjang * $lebar * $harga_base;
        } else {
            $harga_satuan_fix = $harga_base;
            // Pastikan panjang lebar 0 kalau bukan meter
            $panjang = 0; $lebar = 0;
        }
        $total_harga_item = $harga_satuan_fix * $jumlah;

        // Generate ID Baru
        $id_transaksi_baru = generateNewId($conn, 'transaksi', 'T', 'id_transaksi');
        
        // Insert ke Database
        $query = "INSERT INTO transaksi (id_transaksi, id_pelanggan, id_produk, waktu_order, jumlah, total_harga, status_pembayaran, status_order, panjang, lebar, metode_pembayaran, id_bank) 
                  VALUES ('$id_transaksi_baru', '$pelanggan_id', '$prod_id', '$waktu_fix', '$jumlah', '$total_harga_item', '$status_bayar', '$status_order', '$panjang', '$lebar', '$metode', $id_bank)";
        
        if (pg_query($conn, $query)) {
            // Kurangi Stok
            $stok_baru = $stok_now - $jumlah;
            pg_query($conn, "UPDATE produk SET stok_bahan = $stok_baru WHERE id_produk = '$prod_id'");
            
            // Simpan ID ke array
            $list_id_baru[] = $id_transaksi_baru;
        } else {
            $error_db = true;
        }
    }

    if (!$error_db) {
        // Redirect langsung ke Invoice Gabungan dengan membawa ID-ID tadi
        // Kita buat form hidden submit otomatis pake JS
        echo '<form id="redirectForm" action="invoice_gabungan.php" method="POST" target="_blank">';
        foreach($list_id_baru as $id) {
            echo '<input type="hidden" name="ids[]" value="'.$id.'">';
        }
        echo '</form>';
        echo '<script>
                document.getElementById("redirectForm").submit();
                // Setelah tab invoice terbuka, redirect halaman ini kembali ke index
                setTimeout(function(){ window.location.href = "../../index.php"; }, 1000);
              </script>';
    } else {
        echo "Gagal menyimpan data.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Transaksi Banyak Item</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4f46e5; --light: #f8fafc; --border: #e2e8f0; }
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; font-size: 0.9rem; }
        
        .card-modern { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid white; }
        .form-control-modern, .form-select-modern { border: 1px solid var(--border); border-radius: 8px; padding: 8px 12px; background-color: var(--light); }
        .form-control-modern:focus { background-color: white; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        .btn-modern { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; }
        .btn-modern:hover { background: #4338ca; color: white; }
        .header-gradient { background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); color: white; padding: 15px 20px; border-radius: 12px 12px 0 0; }
        
        /* Table Style */
        .table-cart th { font-size: 0.8rem; text-transform: uppercase; color: #64748b; background: #f8fafc; }
        .table-cart td { vertical-align: middle; }
    </style>
</head>
<body>

<div class="container py-4">
    <form method="POST" id="formTransaksi">
        <div class="row g-4">
            
            <div class="col-lg-5">
                <div class="d-flex align-items-center mb-3">
                    <a href="../../index.php" class="btn btn-light border rounded-circle me-3"><i class="bi bi-arrow-left"></i></a>
                    <h5 class="fw-bold m-0">Input Order (Keranjang)</h5>
                </div>

                <div class="card-modern mb-3">
                    <div class="header-gradient"><i class="bi bi-person me-2"></i> Data Pelanggan</div>
                    <div class="card-body p-3">
                        <div class="mb-2">
                            <label class="small fw-bold text-muted">Tanggal</label>
                            <input type="date" name="tgl_input" class="form-control form-control-modern" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div>
                            <label class="small fw-bold text-muted">Pilih Pelanggan</label>
                            <select name="pelanggan_id" class="form-select form-select-modern" required>
                                <option value="">-- Cari Pelanggan --</option>
                                <?php
                                $q = pg_query($conn, "SELECT id_pelanggan, nama FROM pelanggan ORDER BY nama ASC");
                                while ($p = pg_fetch_assoc($q)) {
                                    echo "<option value='{$p['id_pelanggan']}'>{$p['nama']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card-modern">
                    <div class="p-3 border-bottom bg-light">
                        <h6 class="fw-bold m-0 text-primary"><i class="bi bi-cart-plus me-2"></i>Tambah Item</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="mb-2">
                            <label class="small fw-bold text-muted">Produk</label>
                            <select id="input_produk" class="form-select form-select-modern" onchange="cekProduk()">
                                <option value="" data-harga="0" data-jenis="">-- Pilih Produk --</option>
                                <?php
                                $qp = pg_query($conn, "SELECT id_produk, nama_produk, harga, jenis_satuan FROM produk ORDER BY nama_produk ASC");
                                while ($pr = pg_fetch_assoc($qp)) {
                                    echo "<option value='{$pr['id_produk']}' data-nama='{$pr['nama_produk']}' data-harga='{$pr['harga']}' data-jenis='{$pr['jenis_satuan']}'>{$pr['nama_produk']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div id="area_ukuran" class="row g-2 mb-2" style="display:none;">
                            <div class="col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">P</span>
                                    <input type="number" id="input_p" class="form-control" placeholder="0" step="0.01" value="1">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">L</span>
                                    <input type="number" id="input_l" class="form-control" placeholder="0" step="0.01" value="1">
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 align-items-end">
                            <div class="col-4">
                                <label class="small fw-bold text-muted">Qty</label>
                                <input type="number" id="input_qty" class="form-control form-control-modern" value="1" min="1">
                            </div>
                            <div class="col-8">
                                <button type="button" class="btn btn-dark w-100 py-2" onclick="tambahKeKeranjang()">
                                    <i class="bi bi-plus-lg me-1"></i> Masukkan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card-modern h-100 d-flex flex-column">
                    <div class="header-gradient bg-dark">
                        <div class="d-flex justify-content-between">
                            <span><i class="bi bi-basket me-2"></i> Daftar Belanjaan</span>
                            <span id="total_items_badge" class="badge bg-white text-primary">0 Item</span>
                        </div>
                    </div>
                    
                    <div class="card-body p-0 flex-grow-1 table-responsive">
                        <table class="table table-cart table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Produk</th>
                                    <th>Detail</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Subtotal</th>
                                    <th class="text-center pe-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tabel_keranjang">
                                <tr id="row_kosong">
                                    <td colspan="5" class="text-center py-5 text-muted fst-italic">
                                        Belum ada item yang ditambahkan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 bg-light border-top">
                        <div class="d-flex justify-content-between align-items-end mb-3">
                            <div class="text-muted">Total Tagihan:</div>
                            <h3 class="fw-bold text-success m-0" id="grand_total_display">Rp 0</h3>
                        </div>

                        <div class="row g-2 mb-3">
                             <div class="col-md-6">
                                <label class="small fw-bold text-muted">Metode Bayar</label>
                                <select name="metode_pembayaran" class="form-select form-select-modern" onchange="cekMetode()" required>
                                    <option value="Cash">Tunai (Cash)</option>
                                    <option value="Transfer">Transfer Bank</option>
                                </select>
                             </div>
                             <div class="col-md-6" id="area_bank" style="display:none;">
                                <label class="small fw-bold text-muted">Pilih Bank</label>
                                <select name="bank_id" id="bank_id" class="form-select form-select-modern">
                                    <option value="">-- Pilih --</option>
                                    <?php
                                    $qb = pg_query($conn, "SELECT * FROM bank_akun");
                                    while ($b = pg_fetch_assoc($qb)) { echo "<option value='{$b['id_bank']}'>{$b['nama_bank']}</option>"; }
                                    ?>
                                </select>
                             </div>
                             <div class="col-12 mt-2">
                                <label class="small fw-bold text-muted">Status</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status_pembayaran" value="Lunas" checked>
                                        <label class="form-check-label fw-bold text-success">LUNAS</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status_pembayaran" value="Belum Lunas">
                                        <label class="form-check-label fw-bold text-warning">BELUM LUNAS</label>
                                    </div>
                                </div>
                             </div>
                        </div>

                        <button type="submit" name="proses_transaksi" class="btn btn-modern w-100 py-3 fs-6">
                            <i class="bi bi-printer-fill me-2"></i> SIMPAN & CETAK INVOICE
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<script>
    // --- LOGIKA JAVASCRIPT ---
    
    function cekProduk() {
        let select = document.getElementById('input_produk');
        let option = select.options[select.selectedIndex];
        let jenis = option.getAttribute('data-jenis');
        
        // Tampilkan input P x L kalau jenisnya Meter
        if (jenis === 'Meter') {
            document.getElementById('area_ukuran').style.display = 'flex';
        } else {
            document.getElementById('area_ukuran').style.display = 'none';
        }
    }

    function cekMetode() {
        let metode = document.querySelector('select[name="metode_pembayaran"]').value;
        let bankSelect = document.getElementById('bank_id');
        if (metode === 'Transfer') {
            document.getElementById('area_bank').style.display = 'block';
            bankSelect.setAttribute('required', 'required');
        } else {
            document.getElementById('area_bank').style.display = 'none';
            bankSelect.removeAttribute('required');
        }
    }

    let grandTotal = 0;
    let itemCount = 0;

    function tambahKeKeranjang() {
        // Ambil data dari input
        let select = document.getElementById('input_produk');
        let option = select.options[select.selectedIndex];
        
        if (select.value === "") { alert("Pilih produk dulu!"); return; }

        let idProduk = select.value;
        let namaProduk = option.getAttribute('data-nama');
        let hargaBase = parseFloat(option.getAttribute('data-harga'));
        let jenis = option.getAttribute('data-jenis');
        
        let qty = parseFloat(document.getElementById('input_qty').value) || 1;
        let p = 1, l = 1;
        let detailText = "-";

        // Hitung Subtotal
        let subtotal = 0;
        if (jenis ===