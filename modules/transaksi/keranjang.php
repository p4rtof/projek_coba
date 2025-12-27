<?php
include '../../config/koneksi.php';
include '../../auth/auth.php';

// ... (Bagian PHP atas SAMA PERSIS, tidak perlu diubah) ...
date_default_timezone_set('Asia/Jakarta');
$selected_pelanggan = $_GET['id_pelanggan'] ?? '';

if (isset($_POST['simpan_transaksi'])) {
    // ... (Logika simpan SAMA PERSIS) ...
    $pelanggan_id = $_POST['pelanggan_id'];
    $tgl_input = $_POST['tgl_input'];
    $no_po = pg_escape_string($conn, $_POST['no_po']);
    $id_gabungan = pg_escape_string($conn, $_POST['id_transaksi']);
    $jam_sekarang = date('H:i:s');
    $waktu_fix = $tgl_input . ' ' . $jam_sekarang;
    $metode = $_POST['metode_pembayaran'];
    $id_bank = ($metode == 'Transfer') ? $_POST['bank_id'] : 'NULL';

    if (!isset($_POST['produk_id'])) {
        echo "<script>alert('Keranjang kosong! Masukkan item dulu.'); window.history.back();</script>";
        exit;
    }

    // ... (Logika loop insert SAMA PERSIS) ...
    $items = $_POST['produk_id'];
    $error_db = false;
    foreach ($items as $key => $prod_id) {
        $jumlah = $_POST['jumlah'][$key];
        $panjang = $_POST['panjang'][$key];
        $lebar = $_POST['lebar'][$key];
        $cek_produk = pg_fetch_assoc(pg_query($conn, "SELECT harga, stok_bahan, jenis_satuan FROM produk WHERE id_produk = '$prod_id'"));
        $harga_base = $cek_produk['harga'];
        $stok_now = $cek_produk['stok_bahan'];
        $jenis = $cek_produk['jenis_satuan'];

        if ($stok_now < $jumlah) {
            echo "<script>alert('Gagal! Stok tidak cukup.'); window.history.back();</script>";
            exit;
        }

        if ($jenis == 'Meter') {
            $harga_fix = $panjang * $lebar * $harga_base;
        } else {
            $harga_fix = $harga_base;
            $panjang = 0; $lebar = 0;
        }
        $subtotal = $harga_fix * $jumlah;

        $query = "INSERT INTO transaksi (id_transaksi, id_pelanggan, id_produk, waktu_order, jumlah, total_harga, panjang, lebar, metode_pembayaran, id_bank, no_po) 
                  VALUES ('$id_gabungan', '$pelanggan_id', '$prod_id', '$waktu_fix', '$jumlah', '$subtotal', '$panjang', '$lebar', '$metode', $id_bank, '$no_po')";

        if (pg_query($conn, $query)) {
            $stok_baru = $stok_now - $jumlah;
            pg_query($conn, "UPDATE produk SET stok_bahan = $stok_baru WHERE id_produk = '$prod_id'");
        } else {
            $error_db = true;
        }
    }

    if (!$error_db) {
        echo "<script>window.location.href = 'invoice.php?id=$id_gabungan';</script>";
    } else {
        echo "Gagal menyimpan data.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Input Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ... CSS SAMA ... */
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
        .radio-card-input:checked+.radio-card-label.label-bank { border-color: var(--primary); background-color: #eef2ff; color: var(--primary); box-shadow: 0 2px 4px rgba(79, 70, 229, 0.1); }
        .radio-icon { font-size: 1.6rem; margin-right: 12px; margin-bottom: 0; line-height: 1; }
        .table-cart th { font-size: 0.75rem; text-transform: uppercase; color: #64748b; background: #f8fafc; border-bottom: 1px solid var(--border); }
        .table-cart td { vertical-align: middle; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
    </style>
</head>

<body>
    <div class="container py-3">
        <form method="POST" id="formTransaksi">
            <div class="d-flex align-items-center mb-3">
                <a href="../../index.php" class="btn btn-light rounded-circle shadow-sm me-3 border" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;"><i class="bi bi-arrow-left text-dark fs-6"></i></a>
                <div><h5 class="fw-bold m-0 text-dark">Buat Transaksi</h5><small class="text-secondary">Mode Keranjang</small></div>
            </div>

            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="card-modern mb-3">
                        <div class="header-gradient d-flex justify-content-between align-items-center"><span class="fw-bold"><i class="bi bi-person me-2"></i>Data Pelanggan</span><span class="badge bg-white bg-opacity-25 border border-white border-opacity-25"><?= date('d M Y') ?></span></div>
                        <div class="card-body p-3">
                            <div class="mb-3"><label class="form-label">Tanggal Order</label><input type="date" name="tgl_input" class="form-control form-control-modern" value="<?= date('Y-m-d') ?>" required></div>
                            <div class="mb-3"><label class="form-label">Nomor PO</label><input type="text" name="no_po" class="form-control form-control-modern" placeholder="Masukkan No PO"></div>
                            <div class="mb-3"><label class="form-label fw-bold">No. Invoice</label><input type="text" name="id_transaksi" class="form-control form-control-modern border-primary" placeholder="Masukan No Invoice..." required></div>
                            <div>
                                <label class="form-label">Pilih Pelanggan</label>
                                <div class="input-group">
                                    <select name="pelanggan_id" class="form-select form-select-modern" required>
                                        <option value="">-- Cari Pelanggan --</option>
                                        <?php
                                        $q = pg_query($conn, "SELECT id_pelanggan, nama FROM pelanggan ORDER BY nama ASC");
                                        while ($p = pg_fetch_assoc($q)) {
                                            $sel = ($p['id_pelanggan'] == $selected_pelanggan) ? 'selected' : '';
                                            echo "<option value='{$p['id_pelanggan']}' $sel>{$p['nama']}</option>";
                                        }
                                        ?>
                                    </select>
                                    <a href="../pelanggan/index.php" target="_blank" class="btn btn-light border btn-sm pt-2" title="Tambah Pelanggan Baru"><i class="bi bi-plus-lg"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-modern">
                        <div class="p-3 border-bottom bg-light"><h6 class="fw-bold m-0 text-primary"><i class="bi bi-cart-plus me-2"></i>Input Item</h6></div>
                        <div class="card-body p-3">
                            <div class="mb-3">
                                <label class="form-label">Produk / Layanan</label>
                                <div class="input-group">
                                    
                                    <select id="input_produk" class="form-select form-select-modern" onchange="cekProduk()">
                                        <option value="" data-harga="0" data-jenis="" data-stok="0">-- Pilih Produk --</option>
                                        <?php
                                        $qp = pg_query($conn, "SELECT id_produk, nama_produk, harga, jenis_satuan, stok_bahan FROM produk ORDER BY nama_produk ASC");
                                        while ($pr = pg_fetch_assoc($qp)) {
                                            echo "<option value='{$pr['id_produk']}' data-nama='{$pr['nama_produk']}' data-harga='{$pr['harga']}' data-jenis='{$pr['jenis_satuan']}' data-stok='{$pr['stok_bahan']}'>{$pr['nama_produk']}</option>";
                                        }
                                        ?>
                                    </select>

                                    <a href="../produk/index.php" target="_blank" class="btn btn-light border pt-2" title="Tambah Produk Baru"><i class="bi bi-plus-lg"></i></a>
                                </div>

                                <div class="text-end mt-1">
                                    <small><a href="javascript:void(0)" onclick="refreshProduk()" class="text-decoration-none text-secondary" style="font-size: 11px;"><i class="bi bi-arrow-clockwise"></i> Refresh List Produk</a></small>
                                </div>

                                <div id="info_stok" class="mt-2"></div>
                            </div>

                            <div id="area_ukuran" class="bg-warning bg-opacity-10 p-2 rounded-3 mb-3 border border-warning border-opacity-25" style="display:none;">
                                <div class="mb-1 text-warning fw-bold small"><i class="bi bi-rulers me-1"></i> Ukuran Custom (Meter)</div>
                                <div class="row g-2">
                                    <div class="col-6"><div class="input-group input-group-sm"><span class="input-group-text bg-white border-end-0 text-secondary">P</span><input type="number" step="0.01" id="input_p" class="form-control form-control-modern border-start-0 ps-1" value="1"><span class="input-group-text bg-transparent border-0 text-secondary small">m</span></div></div>
                                    <div class="col-6"><div class="input-group input-group-sm"><span class="input-group-text bg-white border-end-0 text-secondary">L</span><input type="number" step="0.01" id="input_l" class="form-control form-control-modern border-start-0 ps-1" value="1"><span class="input-group-text bg-transparent border-0 text-secondary small">m</span></div></div>
                                </div>
                            </div>

                            <div class="row g-2 align-items-end">
                                <div class="col-4"><label class="form-label">Qty</label><input type="number" id="input_qty" class="form-control form-control-modern text-center fw-bold" value="1" min="1" oninput="validasiStok()"></div>
                                <div class="col-8"><button type="button" id="btn_tambah" class="btn btn-dark w-100" style="padding: 10px; border-radius: 8px; font-weight: 600;" onclick="tambahKeKeranjang()"><i class="bi bi-plus-lg me-1"></i> Tambah ke Keranjang</button></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card-modern h-100 d-flex flex-column">
                        <div class="header-gradient bg-dark"><div class="d-flex justify-content-between"><span><i class="bi bi-basket me-2"></i> Daftar Belanjaan</span><span id="total_items_badge" class="badge bg-white text-primary">0 Item</span></div></div>
                        <div class="card-body p-0 flex-grow-1 table-responsive" style="min-height: 200px;">
                            <table class="table table-cart table-hover mb-0">
                                <thead><tr><th class="ps-4">Produk</th><th>Detail</th><th class="text-center">Qty</th><th class="text-end">Subtotal</th><th class="text-center pe-3">Aksi</th></tr></thead>
                                <tbody id="tabel_keranjang">
                                    <tr id="row_kosong"><td colspan="5" class="text-center py-5 text-muted small fst-italic"><i class="bi bi-inbox fs-4 d-block mb-1 opacity-50"></i> Belum ada item ditambahkan.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 bg-light border-top">
                            <div class="d-flex justify-content-between align-items-end mb-3 px-1"><div class="text-secondary fw-bold small">Total Tagihan</div><h4 class="fw-bold text-success m-0" id="grand_total_display">Rp 0</h4></div>
                            <div class="mb-3">
                                <label class="form-label d-block mb-2">Metode Pembayaran</label>
                                <div class="row g-2">
                                    <div class="col-6"><input type="radio" class="radio-card-input" name="metode_pembayaran" id="metode1" value="Cash" onclick="cekMetode()" checked><label class="radio-card-label label-bank" for="metode1"><i class="bi bi-cash-coin radio-icon"></i><div class="fw-bold small">Tunai</div></label></div>
                                    <div class="col-6"><input type="radio" class="radio-card-input" name="metode_pembayaran" id="metode2" value="Transfer" onclick="cekMetode()"><label class="radio-card-label label-bank" for="metode2"><i class="bi bi-bank radio-icon"></i><div class="fw-bold small">Transfer</div></label></div>
                                </div>
                                <div id="area_bank" class="mt-2" style="display: none;">
                                    <div class="p-2 bg-white rounded-3 border"><label class="form-label small mb-2 text-secondary px-1">Pilih Rekening:</label>
                                        <div class="row g-2">
                                            <?php
                                            $q_bank = pg_query($conn, "SELECT * FROM bank_akun");
                                            while ($b = pg_fetch_assoc($q_bank)) {
                                                echo "<div class='col-md-6'><input type='radio' class='radio-card-input' name='bank_id' id='bank_{$b['id_bank']}' value='{$b['id_bank']}'><label class='radio-card-label label-bank' for='bank_{$b['id_bank']}'><div class='text-primary me-2'><i class='bi bi-credit-card-2-front fs-4'></i></div><div style='line-height: 1.1;'><div class='fw-bold text-dark text-truncate small'>{$b['nama_bank']}</div><div class='text-secondary' style='font-size: 0.7rem;'>{$b['no_rekening']}</div></div></label></div>";
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="simpan_transaksi" class="btn-modern py-3"><i class="bi bi-printer-fill me-2"></i> SIMPAN & CETAK INVOICE</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        // --- 1. FUNGSI REFRESH PRODUK (AJAX) ---
        function refreshProduk() {
            let select = document.getElementById('input_produk');
            let info = document.getElementById('info_stok');
            
            // Ubah text sementara
            info.innerHTML = '<span class="text-secondary small fst-italic">Sedang memuat data terbaru...</span>';

            // Panggil file get_produk.php
            fetch('get_produk.php')
                .then(response => response.text())
                .then(html => {
                    select.innerHTML = html; // Ganti isi select dengan data baru
                    info.innerHTML = '<span class="text-success small fw-bold"><i class="bi bi-check-circle"></i> List produk berhasil diperbarui!</span>';
                    setTimeout(() => info.innerHTML = '', 2000); // Hapus pesan setelah 2 detik
                    
                    // Reset pilihan
                    select.value = "";
                    cekProduk(); 
                })
                .catch(err => {
                    console.error('Gagal refresh:', err);
                    info.innerHTML = '<span class="text-danger small fw-bold">Gagal memuat data!</span>';
                });
        }

        // --- FUNGSI BAWAAN LAINNYA (TETAP SAMA) ---
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

        let grandTotal = 0;
        let itemCount = 0;

        function tambahKeKeranjang() {
            let select = document.getElementById('input_produk');
            let option = select.options[select.selectedIndex];
            if (select.value === "") { alert("Pilih produk dulu!"); return; }

            let idProduk = select.value;
            let namaProduk = option.getAttribute('data-nama');
            let hargaBase = parseFloat(option.getAttribute('data-harga'));
            let jenis = option.getAttribute('data-jenis');
            let qty = parseFloat(document.getElementById('input_qty').value) || 1;
            let p = 1, l = 1, detailText = "-";
            let subtotal = 0;

            if (jenis === 'Meter') {
                p = parseFloat(document.getElementById('input_p').value) || 1;
                l = parseFloat(document.getElementById('input_l').value) || 1;
                subtotal = (p * l * hargaBase) * qty;
                detailText = `Ukuran: ${p}m x ${l}m`;
            } else {
                subtotal = hargaBase * qty;
                p = 0; l = 0;
            }

            let rowKosong = document.getElementById('row_kosong');
            if (rowKosong) rowKosong.remove();

            let tbody = document.getElementById('tabel_keranjang');
            let row = document.createElement('tr');
            row.innerHTML = `<td class="ps-4 fw-bold">${namaProduk}<input type="hidden" name="produk_id[]" value="${idProduk}"></td><td class="small text-muted">${detailText}<input type="hidden" name="panjang[]" value="${p}"><input type="hidden" name="lebar[]" value="${l}"></td><td class="text-center">${qty}<input type="hidden" name="jumlah[]" value="${qty}"></td><td class="text-end fw-bold text-dark">Rp ${new Intl.NumberFormat('id-ID').format(subtotal)}</td><td class="text-center pe-3"><button type="button" class="btn btn-sm btn-light text-danger border" onclick="hapusItem(this, ${subtotal})"><i class="bi bi-trash"></i></button></td>`;
            tbody.appendChild(row);
            grandTotal += subtotal;
            itemCount++;
            updateTotalDisplay();

            document.getElementById('input_qty').value = 1;
            if (document.getElementById('input_p')) document.getElementById('input_p').value = 1;
            if (document.getElementById('input_l')) document.getElementById('input_l').value = 1;
        }

        function hapusItem(btn, subtotal) {
            btn.closest('tr').remove();
            grandTotal -= subtotal;
            itemCount--;
            updateTotalDisplay();
        }

        function updateTotalDisplay() {
            document.getElementById('grand_total_display').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(grandTotal);
            document.getElementById('total_items_badge').innerText = itemCount + ' Item';
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (typeof cekProduk === "function") cekProduk();
            if (document.querySelector('input[name="metode_pembayaran"]:checked')) cekMetode();
        });
    </script>
</body>
</html>