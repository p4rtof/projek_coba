<?php
include '../../config/koneksi.php';
include '../../auth/auth.php';
date_default_timezone_set('Asia/Jakarta');
$id_transaksi = $_GET['id'] ?? '';

// --- 1. INISIALISASI DATA KE SESSION ---
if (!isset($_SESSION['edit_cart']) || $_SESSION['edit_id_trx'] != $id_transaksi) {
    $q_header = pg_query($conn, "SELECT * FROM transaksi WHERE id_transaksi = '$id_transaksi' LIMIT 1");
    $header = pg_fetch_assoc($q_header);

    if (!$header) { echo "<script>alert('Transaksi tidak ditemukan!'); window.location.href='../../index.php';</script>"; exit(); }

    $q_detail = pg_query($conn, "SELECT t.*, p.nama_produk, p.harga, p.jenis_satuan, p.stok_bahan FROM transaksi t JOIN produk p ON t.id_produk = p.id_produk WHERE t.id_transaksi = '$id_transaksi'");
    $items = [];
    while ($row = pg_fetch_assoc($q_detail)) {
        if ($row['jenis_satuan'] == 'Meter') { 
            $luas = ($row['panjang'] * $row['lebar']) ?: 1; 
            $harga_satuan = $row['total_harga'] / ($row['jumlah'] * $luas); 
        } else { 
            $harga_satuan = $row['total_harga'] / $row['jumlah']; 
        }
        $items[] = ['id_produk' => $row['id_produk'], 'nama_produk' => $row['nama_produk'], 'jumlah' => $row['jumlah'], 'harga_satuan' => $harga_satuan, 'panjang' => $row['panjang'], 'lebar' => $row['lebar'], 'total_harga' => $row['total_harga'], 'jenis_satuan' => $row['jenis_satuan']];
    }

    $_SESSION['edit_id_trx'] = $id_transaksi;
    $_SESSION['edit_cart'] = $items;
    
    // [REVISI] Menghapus status_pembayaran & status_order dari session header
    $_SESSION['edit_header'] = [
        'id_pelanggan' => $header['id_pelanggan'],
        'waktu_order' => $header['waktu_order'],
        'metode_pembayaran' => $header['metode_pembayaran'],
        'id_bank' => $header['id_bank'],
        'no_po' => $header['no_po']
    ];
}

// --- 2. LOGIC TAMBAH ITEM ---
if (isset($_POST['tambah_item'])) {
    $id_prod = $_POST['id_produk']; $jumlah = (int)$_POST['jumlah'];
    $q_p = pg_query($conn, "SELECT * FROM produk WHERE id_produk = '$id_prod'"); $prod = pg_fetch_assoc($q_p);
    
    if ($prod['jenis_satuan'] == 'Meter') { 
        $panjang = (float)($_POST['panjang'] ?? 1); 
        $lebar = (float)($_POST['lebar'] ?? 1); 
        $luas = ($panjang * $lebar) ?: 1; 
        $total = $luas * $prod['harga'] * $jumlah; 
    } else { 
        $panjang = 0; $lebar = 0; 
        $total = $prod['harga'] * $jumlah; 
    }

    $_SESSION['edit_cart'][] = ['id_produk' => $id_prod, 'nama_produk' => $prod['nama_produk'], 'jumlah' => $jumlah, 'harga_satuan' => $prod['harga'], 'panjang' => $panjang, 'lebar' => $lebar, 'total_harga' => $total, 'jenis_satuan' => $prod['jenis_satuan']];
    header("Location: edit.php?id=$id_transaksi"); exit();
}

// --- 3. LOGIC HAPUS ITEM ---
if (isset($_GET['hapus_idx'])) {
    $idx = $_GET['hapus_idx']; unset($_SESSION['edit_cart'][$idx]); $_SESSION['edit_cart'] = array_values($_SESSION['edit_cart']); 
    header("Location: edit.php?id=$id_transaksi"); exit();
}

// --- 4. LOGIC SIMPAN PERUBAHAN ---
if (isset($_POST['simpan_perubahan'])) {
    // A. Kembalikan Stok Lama
    $q_old_items = pg_query($conn, "SELECT id_produk, jumlah FROM transaksi WHERE id_transaksi = '$id_transaksi'");
    while ($old = pg_fetch_assoc($q_old_items)) { 
        pg_query($conn, "UPDATE produk SET stok_bahan = stok_bahan + {$old['jumlah']} WHERE id_produk = '{$old['id_produk']}'"); 
    }

    // B. Hapus Data Lama
    pg_query($conn, "DELETE FROM transaksi WHERE id_transaksi = '$id_transaksi'");

    // C. Siapkan Data Baru
    $pelanggan_id = $_POST['pelanggan_id'];
    $tgl_input = $_POST['tgl_input'];
    $no_po = pg_escape_string($conn, $_POST['no_po']);
    $jam_sekarang = date('H:i:s'); 
    $waktu_fix = $tgl_input . ' ' . $jam_sekarang;

    // [REVISI] Menghapus variabel status bayar & status order
    $metode = $_POST['metode_pembayaran']; 
    $id_bank = ($metode == 'Transfer') ? $_POST['bank_id'] : 'NULL';
    $error = false;
    
    foreach ($_SESSION['edit_cart'] as $item) {
        $id_p = $item['id_produk']; $qty = $item['jumlah']; $tot = $item['total_harga']; $p = $item['panjang']; $l = $item['lebar'];
        
        // [REVISI] Query Insert tanpa kolom status
        $q_ins = "INSERT INTO transaksi (id_transaksi, id_pelanggan, id_produk, waktu_order, jumlah, total_harga, panjang, lebar, metode_pembayaran, id_bank, no_po) 
                  VALUES ('$id_transaksi', '$pelanggan_id', '$id_p', '$waktu_fix', $qty, $tot, $p, $l, '$metode', $id_bank, '$no_po')";
        
        if (pg_query($conn, $q_ins)) { 
            pg_query($conn, "UPDATE produk SET stok_bahan = stok_bahan - $qty WHERE id_produk = '$id_p'"); 
        } else { 
            $error = true; 
        }
    }

    if (!$error) { 
        unset($_SESSION['edit_cart']); unset($_SESSION['edit_id_trx']); unset($_SESSION['edit_header']); 
        echo "<script>alert('Perubahan berhasil disimpan!'); window.location.href='../../index.php';</script>"; 
    } else { 
        echo "Gagal menyimpan perubahan."; 
    }
}

$cart = $_SESSION['edit_cart']; $head = $_SESSION['edit_header'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Edit Transaksi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4f46e5; --primary-hover: #4338ca; --secondary: #64748b; --dark: #0f172a; --light: #f8fafc; --border: #e2e8f0; --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; color: var(--dark); font-size: 0.9rem; }
        .card-modern { background: white; border: 1px solid white; border-radius: 12px; box-shadow: var(--card-shadow); transition: all 0.2s; overflow: hidden; }
        .form-label { font-size: 0.8rem; font-weight: 600; color: var(--secondary); margin-bottom: 0.2rem; }
        .form-control-modern, .form-select-modern { border: 1px solid var(--border); border-radius: 8px; padding: 8px 12px; font-size: 0.9rem; background-color: var(--light); transition: all 0.2s; }
        .form-control-modern:focus, .form-select-modern:focus { background-color: white; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        .btn-modern { background: var(--primary); color: white; border: none; padding: 10px; border-radius: 8px; font-weight: 700; width: 100%; transition: all 0.2s; box-shadow: 0 2px 4px -1px rgba(79, 70, 229, 0.2); letter-spacing: 0.5px; font-size: 0.95rem; }
        .btn-modern:hover { background: var(--primary-hover); transform: translateY(-1px); color: white; }
        .header-gradient { background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); color: white; padding: 15px 20px; }
        .radio-card-input { display: none; }
        .radio-card-label { display: flex; flex-direction: row; align-items: center; justify-content: flex-start; cursor: pointer; border: 1px solid var(--border); border-radius: 10px; padding: 10px 15px; background: white; transition: all 0.2s ease; height: 100%; text-align: left; }
        .radio-card-label:hover { border-color: #cbd5e1; background: #f8fafc; transform: translateY(-1px); }
        .radio-card-input:checked + .radio-card-label.label-bank { border-color: var(--primary); background-color: #eef2ff; color: var(--primary); box-shadow: 0 2px 4px rgba(79, 70, 229, 0.1); }
        .radio-icon { font-size: 1.6rem; margin-right: 12px; margin-bottom: 0; line-height: 1; }
        .table-cart th { font-size: 0.75rem; text-transform: uppercase; color: #64748b; background: #f8fafc; border-bottom: 1px solid var(--border); }
        .table-cart td { vertical-align: middle; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
    </style>
</head>
<body>

    <div class="container py-3">
        <div class="d-flex align-items-center mb-3">
            <a href="../../index.php" class="btn btn-light rounded-circle shadow-sm me-3 border" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;"><i class="bi bi-arrow-left text-dark fs-6"></i></a>
            <div><h5 class="fw-bold m-0 text-dark">Edit Transaksi <span class="text-primary">#<?= $id_transaksi ?></span></h5><small class="text-secondary">Ubah item, tanggal, atau metode pembayaran</small></div>
        </div>

        <form method="POST" id="formTransaksi">
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="card-modern mb-3">
                        <div class="header-gradient d-flex justify-content-between align-items-center"><span class="fw-bold"><i class="bi bi-person me-2"></i>Data Utama</span><span class="badge bg-white bg-opacity-25 border border-white border-opacity-25">Edit Mode</span></div>
                        <div class="card-body p-3">
                            <div class="mb-3">
                                <label class="form-label">Tanggal Order</label>
                                <input type="date" name="tgl_input" class="form-control form-control-modern" value="<?= date('Y-m-d', strtotime($head['waktu_order'])) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nomor PO</label>
                                <input type="text" name="no_po" class="form-control form-control-modern" value="<?= $head['no_po'] ?? '' ?>">
                            </div>
                            <div>
                                <label class="form-label">Pilih Pelanggan</label>
                                <div class="input-group">
                                    <select name="pelanggan_id" class="form-select form-select-modern" required>
                                        <?php
                                        $q = pg_query($conn, "SELECT id_pelanggan, nama FROM pelanggan ORDER BY nama ASC");
                                        while ($p = pg_fetch_assoc($q)) {
                                            $sel = ($p['id_pelanggan'] == $head['id_pelanggan']) ? 'selected' : '';
                                            echo "<option value='{$p['id_pelanggan']}' $sel>{$p['nama']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-modern">
                        <div class="p-3 border-bottom bg-light"><h6 class="fw-bold m-0 text-primary"><i class="bi bi-cart-plus me-2"></i>Tambah / Edit Item</h6></div>
                        <div class="card-body p-3">
                            <div class="mb-3">
                                <label class="form-label">Produk / Layanan</label>
                                <select name="id_produk" id="input_produk" class="form-select form-select-modern" onchange="cekProduk()">
                                    <option value="" data-harga="0" data-jenis="" data-stok="0">-- Pilih Produk --</option>
                                    <?php
                                    $qp = pg_query($conn, "SELECT id_produk, nama_produk, harga, jenis_satuan, stok_bahan FROM produk ORDER BY nama_produk ASC");
                                    while ($pr = pg_fetch_assoc($qp)) {
                                        echo "<option value='{$pr['id_produk']}' data-nama='{$pr['nama_produk']}' data-harga='{$pr['harga']}' data-jenis='{$pr['jenis_satuan']}' data-stok='{$pr['stok_bahan']}'>{$pr['nama_produk']}</option>";
                                    }
                                    ?>
                                </select>
                                <div id="info_stok" class="mt-2"></div>
                            </div>
                            <div id="area_ukuran" class="bg-warning bg-opacity-10 p-2 rounded-3 mb-3 border border-warning border-opacity-25" style="display:none;">
                                <div class="mb-1 text-warning fw-bold small"><i class="bi bi-rulers me-1"></i> Ukuran Custom (Meter)</div>
                                <div class="row g-2">
                                    <div class="col-6"><div class="input-group input-group-sm"><span class="input-group-text bg-white border-end-0 text-secondary">P</span><input type="number" step="0.01" name="panjang" id="input_p" class="form-control form-control-modern border-start-0 ps-1" value="1"><span class="input-group-text bg-transparent border-0 text-secondary small">m</span></div></div>
                                    <div class="col-6"><div class="input-group input-group-sm"><span class="input-group-text bg-white border-end-0 text-secondary">L</span><input type="number" step="0.01" name="lebar" id="input_l" class="form-control form-control-modern border-start-0 ps-1" value="1"><span class="input-group-text bg-transparent border-0 text-secondary small">m</span></div></div>
                                </div>
                            </div>
                            <div class="row g-2 align-items-end">
                                <div class="col-4"><label class="form-label">Qty</label><input type="number" name="jumlah" id="input_qty" class="form-control form-control-modern text-center fw-bold" value="1" min="1" oninput="validasiStok()"> </div>
                                <div class="col-8"><button type="submit" name="tambah_item" id="btn_tambah" class="btn btn-dark w-100" style="padding: 10px; border-radius: 8px; font-weight: 600;" formnovalidate><i class="bi bi-plus-lg me-1"></i> Tambah Item</button></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card-modern h-100 d-flex flex-column">
                        <div class="header-gradient bg-dark"><div class="d-flex justify-content-between"><span><i class="bi bi-basket me-2"></i> Rincian Order</span><span id="total_items_badge" class="badge bg-white text-primary"><?= count($cart) ?> Item</span></div></div>
                        <div class="card-body p-0 flex-grow-1 table-responsive" style="min-height: 200px;">
                            <table class="table table-cart table-hover mb-0">
                                <thead><tr><th class="ps-4">Produk</th><th>Detail</th><th class="text-center">Qty</th><th class="text-end">Subtotal</th><th class="text-center pe-3">Aksi</th></tr></thead>
                                <tbody id="tabel_keranjang">
                                    <?php if(empty($cart)): ?>
                                        <tr id="row_kosong"><td colspan="5" class="text-center py-5 text-muted small fst-italic"><i class="bi bi-inbox fs-4 d-block mb-1 opacity-50"></i> Keranjang edit kosong.</td></tr>
                                    <?php else: ?>
                                        <?php $grandTotal = 0; foreach($cart as $idx => $item): $grandTotal += $item['total_harga']; $detail = ($item['jenis_satuan'] == 'Meter') ? "Ukuran: {$item['panjang']}m x {$item['lebar']}m" : "-"; ?>
                                        <tr><td class="ps-4 fw-bold"><?= $item['nama_produk'] ?></td><td class="small text-muted"><?= $detail ?></td><td class="text-center"><?= $item['jumlah'] ?></td><td class="text-end fw-bold text-dark">Rp <?= number_format($item['total_harga'], 0, ',', '.') ?></td><td class="text-center pe-3"><a href="edit.php?id=<?= $id_transaksi ?>&hapus_idx=<?= $idx ?>" class="btn btn-sm btn-light text-danger border"><i class="bi bi-trash"></i></a></td></tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 bg-light border-top">
                            <div class="d-flex justify-content-between align-items-end mb-3 px-1"><div class="text-secondary fw-bold small">Total Tagihan</div><h4 class="fw-bold text-success m-0" id="grand_total_display">Rp <?= number_format($grandTotal ?? 0, 0, ',', '.') ?></h4></div>
                            <div class="mb-3">
                                <label class="form-label d-block mb-2">Metode Pembayaran</label>
                                <div class="row g-2">
                                    <div class="col-6"><input type="radio" class="radio-card-input" name="metode_pembayaran" id="metode1" value="Cash" onclick="cekMetode()" <?= $head['metode_pembayaran']=='Cash'?'checked':'' ?>><label class="radio-card-label label-bank" for="metode1"><i class="bi bi-cash-coin radio-icon"></i> <div class="fw-bold small">Tunai</div></label></div>
                                    <div class="col-6"><input type="radio" class="radio-card-input" name="metode_pembayaran" id="metode2" value="Transfer" onclick="cekMetode()" <?= $head['metode_pembayaran']=='Transfer'?'checked':'' ?>><label class="radio-card-label label-bank" for="metode2"><i class="bi bi-bank radio-icon"></i> <div class="fw-bold small">Transfer</div></label></div>
                                </div>
                                <div id="area_bank" class="mt-2" style="display: none;">
                                    <div class="p-2 bg-white rounded-3 border"><label class="form-label small mb-2 text-secondary px-1">Pilih Rekening:</label>
                                        <div class="row g-2">
                                            <?php
                                            $q_bank = pg_query($conn, "SELECT * FROM bank_akun");
                                            while ($b = pg_fetch_assoc($q_bank)) {
                                                $sel = ($b['id_bank'] == $head['id_bank']) ? 'checked' : '';
                                                echo "<div class='col-md-6'><input type='radio' class='radio-card-input' name='bank_id' id='bank_{$b['id_bank']}' value='{$b['id_bank']}' $sel><label class='radio-card-label label-bank' for='bank_{$b['id_bank']}'><div class='text-primary me-2'><i class='bi bi-credit-card-2-front fs-4'></i></div><div style='line-height: 1.1;'><div class='fw-bold text-dark text-truncate small'>{$b['nama_bank']}</div><div class='text-secondary' style='font-size: 0.7rem;'>{$b['no_rekening']}</div></div></label></div>";
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" name="simpan_perubahan" class="btn-modern py-3" onclick="return confirm('Yakin simpan perubahan ini? Data lama akan ditimpa.')"><i class="bi bi-save-fill me-2"></i> SIMPAN PERUBAHAN</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        function cekProduk() {
            let select = document.getElementById('input_produk');
            let option = select.options[select.selectedIndex];
            let jenis = option.getAttribute('data-jenis');
            let stok = parseInt(option.getAttribute('data-stok')) || 0;
            let areaInfo = document.getElementById('info_stok');

            if (select.value !== "") {
                if (stok > 0) areaInfo.innerHTML = `<span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="bi bi-box-seam me-1"></i> Stok Tersedia: <b>${stok}</b></span>`;
                else areaInfo.innerHTML = `<span class="badge bg-danger bg-opacity-10 text-danger border border-danger"><i class="bi bi-x-circle me-1"></i> Stok Habis!</span>`;
            } else areaInfo.innerHTML = "";

            if (jenis === 'Meter') document.getElementById('area_ukuran').style.display = 'block';
            else document.getElementById('area_ukuran').style.display = 'none';
            
            document.getElementById('input_qty').value = 1;
            validasiStok();
        }

        function validasiStok() {
            let select = document.getElementById('input_produk');
            let qtyInput = document.getElementById('input_qty');
            let btnTambah = document.getElementById('btn_tambah');
            let infoStok = document.getElementById('info_stok');
            let stok = parseInt(select.options[select.selectedIndex].getAttribute('data-stok')) || 0;
            let qty = parseInt(qtyInput.value) || 0;

            if (select.value !== "") {
                if (qty > stok) {
                    qtyInput.classList.add('is-invalid');
                    infoStok.innerHTML = `<span class="badge bg-danger text-white"><i class="bi bi-exclamation-triangle-fill me-1"></i> Stok Kurang! Sisa: ${stok}</span>`;
                    btnTambah.disabled = true;
                    btnTambah.classList.add('opacity-50');
                } else {
                    qtyInput.classList.remove('is-invalid');
                    infoStok.innerHTML = `<span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="bi bi-box-seam me-1"></i> Stok Tersedia: <b>${stok}</b></span>`;
                    btnTambah.disabled = false;
                    btnTambah.classList.remove('opacity-50');
                }
            }
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

        document.addEventListener('DOMContentLoaded', () => {
            if(document.querySelector('input[name="metode_pembayaran"]:checked')) cekMetode();
        });
    </script>
</body>
</html>