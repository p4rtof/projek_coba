<?php 
include '../../config/koneksi.php'; 
include '../../auth/auth.php'; 

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
    
    // --- LOGIC TANGGAL MANUAL & JAM OTOMATIS ---
    $tgl_input    = $_POST['tgl_input']; 
    $jam_sekarang = date('H:i:s');       
    $waktu_fix    = $tgl_input . ' ' . $jam_sekarang; 
    
    // DATA PEMBAYARAN BARU
    $metode       = $_POST['metode_pembayaran'];
    $id_bank      = ($metode == 'Transfer') ? $_POST['bank_id'] : 'NULL'; 
    
    $panjang = !empty($_POST['panjang']) ? str_replace(',', '.', $_POST['panjang']) : 1;
    $lebar   = !empty($_POST['lebar']) ? str_replace(',', '.', $_POST['lebar']) : 1;

    // Cek Stok Terupdate Saat Submit
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
        $error_message = "<div class='alert alert-danger fw-bold shadow-sm border-0 py-2'><i class='bi bi-exclamation-triangle-fill me-2'></i> Gagal! Stok Sisa: {$stok_now}, Diminta: {$jumlah}</div>";
    } else {
        $id_transaksi_baru = generateNewId($conn, 'transaksi', 'T', 'id_transaksi');
        
        $query = "INSERT INTO transaksi (id_transaksi, id_pelanggan, id_produk, waktu_order, jumlah, total_harga, status_pembayaran, status_order, panjang, lebar, metode_pembayaran, id_bank) 
                  VALUES ('$id_transaksi_baru', '$pelanggan_id', '$produk_id', '$waktu_fix', '$jumlah', '$total_bayar', '$status_bayar', '$status_order', '$panjang', '$lebar', '$metode', $id_bank)";
        
        if (pg_query($conn, $query)) {
            $stok_baru = $stok_now - $jumlah;
            pg_query($conn, "UPDATE produk SET stok_bahan = $stok_baru WHERE id_produk = '$produk_id'");
            header("Location: ../../index.php"); 
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --secondary: #64748b;
            --dark: #0f172a;
            --light: #f8fafc;
            --border: #e2e8f0;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; color: var(--dark); font-size: 0.9rem; }
        
        /* Layout Modern & Compact */
        .card-modern {
            background: white; border: 1px solid white; border-radius: 12px;
            box-shadow: var(--card-shadow); transition: all 0.2s;
            overflow: hidden;
        }
        .form-label { font-size: 0.8rem; font-weight: 600; color: var(--secondary); margin-bottom: 0.2rem; }
        
        .form-control-modern, .form-select-modern {
            border: 1px solid var(--border); border-radius: 8px; padding: 8px 12px;
            font-size: 0.9rem; background-color: var(--light); transition: all 0.2s;
        }
        .form-control-modern:focus, .form-select-modern:focus { 
            background-color: white; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); 
        }

        .btn-modern {
            background: var(--primary); color: white; border: none; padding: 10px;
            border-radius: 8px; font-weight: 700; width: 100%; transition: all 0.2s;
            box-shadow: 0 2px 4px -1px rgba(79, 70, 229, 0.2);
            letter-spacing: 0.5px; font-size: 0.95rem;
        }
        .btn-modern:hover { background: var(--primary-hover); transform: translateY(-1px); color: white; }

        .header-gradient {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: white; padding: 15px 20px;
        }

        /* --- STYLE RADIO BUTTON: HORIZONTAL & COMPACT (Solusi Hemat Ruang) --- */
        .radio-card-input { display: none; }
        
        .radio-card-label {
            display: flex;             /* Mode Fleksibel */
            flex-direction: row;       /* BERJEJER KE SAMPING (Kiri-Kanan) */
            align-items: center;       /* Rata Tengah Vertikal */
            justify-content: flex-start; /* Rata Kiri */
            
            cursor: pointer; border: 1px solid var(--border);
            border-radius: 10px; 
            padding: 10px 15px;        /* Padding pas, gak sempit gak lebar */
            background: white; transition: all 0.2s ease;
            height: 100%;              /* Tinggi seragam */
            text-align: left;          /* Teks Rata Kiri */
        }
        
        .radio-card-label:hover { border-color: #cbd5e1; background: #f8fafc; transform: translateY(-1px); }
        
        /* Warna saat dipilih */
        .radio-card-input:checked + .radio-card-label.label-lunas {
            border-color: #10b981; background-color: #ecfdf5; color: #059669;
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.1);
        }
        .radio-card-input:checked + .radio-card-label.label-hutang {
            border-color: #f59e0b; background-color: #fffbeb; color: #d97706;
            box-shadow: 0 2px 4px rgba(245, 158, 11, 0.1);
        }
        .radio-card-input:checked + .radio-card-label.label-bank {
            border-color: var(--primary); background-color: #eef2ff; color: var(--primary);
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.1);
        }
        
        /* Ikon di sebelah Kiri */
        .radio-icon { 
            font-size: 1.8rem;      /* Ukuran Ikon Pas */
            margin-right: 15px;     /* Jarak Ikon ke Teks */
            margin-bottom: 0;       /* Hapus margin bawah */
            line-height: 1;
        }
        
    </style>
</head>
<body>

    <div class="container py-3">
        <div class="row justify-content-center">
            <div class="col-lg-7"> 
                
                <div class="d-flex align-items-center mb-3">
                    <a href="../../index.php" class="btn btn-light rounded-circle shadow-sm me-3 border" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-arrow-left text-dark fs-6"></i>
                    </a>
                    <div>
                        <h5 class="fw-bold m-0 text-dark">Buat Transaksi</h5>
                    </div>
                </div>

                <?= $error_message ?> 

                <div class="card-modern">
                    <div class="header-gradient">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold fs-5"><i class="bi bi-cart-plus me-2"></i>Form Order</span>
                            <span class="badge bg-white bg-opacity-25 border border-white border-opacity-25"><?= date('d M Y') ?></span>
                        </div>
                    </div>
                    
                    <div class="card-body p-3 p-lg-4">
                        <form method="POST">
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-5">
                                    <label class="form-label">Tanggal Order</label>
                                    <input type="date" name="tgl_input" class="form-control form-control-modern" 
                                           value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label">Pilih Pelanggan</label>
                                    <div class="input-group">
                                        <select name="pelanggan_id" class="form-select form-select-modern" required>
                                            <option value="">-- Cari Pelanggan --</option>
                                            <?php
                                            $q = pg_query($conn, "SELECT id_pelanggan, nama FROM pelanggan ORDER BY nama ASC");
                                            while ($p = pg_fetch_assoc($q)) {
                                                $sel = ($p['id_pelanggan'] == $form_pelanggan_id) ? 'selected' : '';
                                                if (isset($_GET['id_pelanggan']) && $_GET['id_pelanggan'] == $p['id_pelanggan']) $sel = 'selected';
                                                echo "<option value='{$p['id_pelanggan']}' $sel>{$p['nama']}</option>";
                                            }
                                            ?>
                                        </select>
                                        <a href="../pelanggan/index.php" class="btn btn-light border btn-sm pt-2" title="Tambah Pelanggan Baru"><i class="bi bi-plus-lg"></i></a>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Produk / Layanan</label>
                                <select name="produk_id" id="produk" class="form-select form-select-modern" onchange="cekJenisProduk()" required>
                                    <option value="" data-harga="0" data-jenis="Pcs" data-stok="0">-- Pilih Produk --</option>
                                    <?php
                                    $q = pg_query($conn, "SELECT id_produk, nama_produk, harga, stok_bahan, jenis_satuan FROM produk ORDER BY nama_produk ASC");
                                    while ($pr = pg_fetch_assoc($q)) {
                                        $sel = ($pr['id_produk'] == $form_produk_id) ? 'selected' : '';
                                        echo "<option value='{$pr['id_produk']}' 
                                                data-harga='{$pr['harga']}' 
                                                data-jenis='{$pr['jenis_satuan']}' 
                                                data-stok='{$pr['stok_bahan']}'
                                                $sel>
                                                {$pr['nama_produk']} (Stok: {$pr['stok_bahan']})
                                              </option>";
                                    }
                                    ?>
                                </select>
                                <div id="info_stok" class="mt-1" style="font-size: 0.8rem;"></div>
                            </div>

                            <div id="area_ukuran" class="bg-warning bg-opacity-10 p-2 rounded-3 mb-3 border border-warning border-opacity-25" style="display:none;">
                                <div class="mb-1 text-warning fw-bold small"><i class="bi bi-rulers me-1"></i> Ukuran Custom (Meter)</div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white border-end-0 text-secondary">P</span>
                                            <input type="number" step="0.01" name="panjang" id="panjang" class="form-control form-control-modern border-start-0 ps-1" placeholder="0" value="<?= $form_p ?>" oninput="hitung()">
                                            <span class="input-group-text bg-transparent border-0 text-secondary small">m</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white border-end-0 text-secondary">L</span>
                                            <input type="number" step="0.01" name="lebar" id="lebar" class="form-control form-control-modern border-start-0 ps-1" placeholder="0" value="<?= $form_l ?>" oninput="hitung()">
                                            <span class="input-group-text bg-transparent border-0 text-secondary small">m</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3 align-items-end">
                                <div class="col-4">
                                    <label class="form-label">Jumlah (Qty)</label>
                                    <input type="number" name="jumlah" id="qty" class="form-control form-control-modern text-center fw-bold" value="<?= $form_jumlah ?>" oninput="hitung()" required min="1"> 
                                </div>
                                <div class="col-8">
                                    <label class="form-label text-success">Total Estimasi</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-success text-white fw-bold border-0 px-3" style="font-size: 0.9rem;">Rp</span>
                                        <input type="text" id="total_tampil" class="form-control form-control-modern bg-success bg-opacity-10 text-success fw-bold border-success border-opacity-25" readonly value="0">
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3 border-light">

                            <div class="mb-3">
                                <label class="form-label d-block mb-2">Metode Pembayaran</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="radio" class="radio-card-input" name="metode_pembayaran" id="metode1" value="Cash" onclick="cekMetode()" <?= ($form_metode == 'Cash') ? 'checked' : '' ?>>
                                        <label class="radio-card-label label-bank" for="metode1">
                                            <i class="bi bi-cash-coin radio-icon"></i> 
                                            <div class="fw-bold small">Tunai</div>
                                        </label>
                                    </div>
                                    <div class="col-6">
                                        <input type="radio" class="radio-card-input" name="metode_pembayaran" id="metode2" value="Transfer" onclick="cekMetode()" <?= ($form_metode == 'Transfer') ? 'checked' : '' ?>>
                                        <label class="radio-card-label label-bank" for="metode2">
                                            <i class="bi bi-bank radio-icon"></i> 
                                            <div class="fw-bold small">Transfer</div>
                                        </label>
                                    </div>
                                </div>

                                <div id="area_bank" class="mt-3" style="display: none;">
                                    <div class="p-3 bg-light rounded-3 border">
                                        <label class="form-label small mb-2 text-secondary">Pilih Rekening Tujuan:</label>
                                        <div class="row g-2">
                                            <?php
                                            $q_bank = pg_query($conn, "SELECT * FROM bank_akun");
                                            while ($b = pg_fetch_assoc($q_bank)) {
                                                $cek = ($b['id_bank'] == $form_bank) ? 'checked' : '';
                                                ?>
                                                <div class="col-md-6">
                                                    <input type="radio" class="radio-card-input" name="bank_id" id="bank_<?= $b['id_bank'] ?>" value="<?= $b['id_bank'] ?>" <?= $cek ?>>
                                                    
                                                    <label class="radio-card-label label-bank" for="bank_<?= $b['id_bank'] ?>">
                                                        <div class="text-primary me-3">
                                                            <i class="bi bi-credit-card-2-front fs-2"></i>
                                                        </div>
                                                        
                                                        <div style="line-height: 1.2;">
                                                            <div class="fw-bold text-dark text-truncate" style="font-size: 0.9rem;"><?= $b['nama_bank'] ?></div>
                                                            <div class="text-secondary fw-medium" style="font-size: 0.8rem;"><?= $b['no_rekening'] ?></div>
                                                            <div class="text-muted small" style="font-size: 0.7rem;">a.n <?= $b['atas_nama'] ?></div>
                                                        </div>
                                                    </label>
                                                </div>
                                                <?php 
                                            } 
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label mb-2">Status Pembayaran</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="radio" class="radio-card-input" name="status_pembayaran" id="status_lunas" value="Lunas" <?= ($form_status_bayar == 'Lunas') ? 'checked' : '' ?>>
                                        <label class="radio-card-label label-lunas" for="status_lunas">
                                            <i class="bi bi-check-circle-fill radio-icon"></i>
                                            <div>
                                                <div class="fw-bold small">LUNAS</div>
                                                <div class="small opacity-75" style="font-size: 0.7rem;">Sudah Bayar</div>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-6">
                                        <input type="radio" class="radio-card-input" name="status_pembayaran" id="status_hutang" value="Belum Lunas" <?= ($form_status_bayar != 'Lunas') ? 'checked' : '' ?>>
                                        <label class="radio-card-label label-hutang" for="status_hutang">
                                            <i class="bi bi-hourglass-split radio-icon"></i>
                                            <div>
                                                <div class="fw-bold small">BELUM</div>
                                                <div class="small opacity-75" style="font-size: 0.7rem;">Catat Utang</div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="simpan" id="btn_simpan" class="btn-modern py-2">
                                <i class="bi bi-check-circle-fill me-2"></i> SIMPAN ORDER
                            </button>
                            <div class="text-center mt-2">
                                <a href="../../index.php" class="text-secondary small text-decoration-none">Batal</a>
                            </div>

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
            let stok = parseInt(option.getAttribute('data-stok')) || 0;
            
            let areaUkuran = document.getElementById('area_ukuran');
            let infoStok = document.getElementById('info_stok');
            
            if(option.value !== "") {
                infoStok.innerHTML = `<span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 rounded-pill"><i class="bi bi-box-seam me-1"></i> Stok: ${stok}</span>`;
            } else {
                infoStok.innerHTML = "";
            }

            if (jenis === 'Meter') {
                areaUkuran.style.display = 'block';
                if(document.getElementById('panjang').value == '') document.getElementById('panjang').value = 1;
                if(document.getElementById('lebar').value == '') document.getElementById('lebar').value = 1;
            } else {
                areaUkuran.style.display = 'none';
            }
            hitung();
        }

        function cekMetode() {
            let metode = document.querySelector('input[name="metode_pembayaran"]:checked').value;
            let areaBank = document.getElementById('area_bank');
            let bankRadios = document.querySelectorAll('input[name="bank_id"]');

            if (metode === 'Transfer') {
                areaBank.style.display = 'block';
                bankRadios.forEach(radio => radio.setAttribute('required', 'required'));
            } else {
                areaBank.style.display = 'none';
                bankRadios.forEach(radio => {
                    radio.removeAttribute('required');
                    radio.checked = false;
                });
            }
        }

        function hitung() {
            let select = document.getElementById('produk');
            let option = select.options[select.selectedIndex];
            
            let harga = parseFloat(option.getAttribute('data-harga')) || 0;
            let jenis = option.getAttribute('data-jenis');
            let stok  = parseInt(option.getAttribute('data-stok')) || 0;
            
            let qtyInput = document.getElementById('qty');
            let qty = parseFloat(qtyInput.value) || 0;
            let btnSimpan = document.getElementById('btn_simpan');
            let infoStok = document.getElementById('info_stok');

            if (qty > stok && option.value !== "") {
                infoStok.innerHTML = `<span class="badge bg-danger bg-opacity-10 text-danger px-2 py-1 rounded-pill"><i class="bi bi-x-circle me-1"></i> Stok Kurang! (Max: ${stok})</span>`;
                qtyInput.classList.add('is-invalid');
                btnSimpan.classList.add('disabled');
            } else if (option.value !== "") {
                infoStok.innerHTML = `<span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 rounded-pill"><i class="bi bi-box-seam me-1"></i> Stok: ${stok}</span>`;
                qtyInput.classList.remove('is-invalid');
                btnSimpan.classList.remove('disabled');
            }

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
            if(document.querySelector('input[name="metode_pembayaran"]:checked')) {
                cekMetode();
            }
        });
    </script>
</body>
</html>