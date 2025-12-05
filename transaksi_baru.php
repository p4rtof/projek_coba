<?php 
include 'koneksi.php'; 

$error_message = ''; // Variabel untuk menyimpan pesan error
// Variabel untuk mempertahankan nilai form
$form_pelanggan_id = $_POST['pelanggan_id'] ?? '';
$form_produk_id = $_POST['produk_id'] ?? '';
$form_jumlah = $_POST['jumlah'] ?? '';
$form_status_bayar = $_POST['status_pembayaran'] ?? 'Lunas';

// FUNGSI HELPER: Untuk generate ID baru (Contoh: T002, P003)
function generateNewId($conn, $table, $prefix, $id_column) {
    // Ambil ID terakhir dari tabel
    $q_last = pg_query($conn, "SELECT $id_column FROM $table ORDER BY $id_column DESC LIMIT 1");
    $last_id = pg_fetch_assoc($q_last);
    
    if ($last_id) {
        // Mengambil angka (contoh: "T001" menjadi 1)
        $last_id_num = (int)substr($last_id[$id_column], 1); 
        $new_id_num = $last_id_num + 1;
    } else {
        // Jika tabel kosong, mulai dari 1
        $new_id_num = 1;
    }
    
    // Format angka menjadi 3 digit dan digabung dengan prefix (contoh: 2 menjadi "T002")
    return $prefix . str_pad($new_id_num, 3, '0', STR_PAD_LEFT); 
}

if (isset($_POST['simpan'])) {
    // 1. Ambil data dari form
    $pelanggan_id = $_POST['pelanggan_id']; 
    $produk_id    = $_POST['produk_id'];
    $jumlah       = $_POST['jumlah'];
    $status_bayar = $_POST['status_pembayaran'];
    $status_order = 'Proses'; 

    // 2. Generate ID Transaksi baru
    $id_transaksi_baru = generateNewId($conn, 'transaksi', 'T', 'id_transaksi');

    // 3. Cek Produk dan Hitung Total
    // FIX: Gunakan id_produk dan ambil nama produk
    $cek_produk = pg_fetch_assoc(pg_query($conn, "SELECT harga, stok_bahan, nama_produk FROM produk WHERE id_produk = '$produk_id'"));
    $harga_satuan = $cek_produk['harga'];
    $stok_now     = $cek_produk['stok_bahan'];
    $total_bayar  = $harga_satuan * $jumlah;
    $nama_produk  = $cek_produk['nama_produk'];

    if ($stok_now < $jumlah) {
        // GANTI LOGIC ALERT DENGAN SETTING VARIABEL ERROR MESSAGE
        $error_message = "
            <div class='alert alert-danger fw-bold'>
                ❌ Stok {$nama_produk} Kurang! <br>
                Stok tersedia: {$stok_now} | Jumlah order: {$jumlah}.
            </div>
        ";
    } else {
        // Jika stok cukup, lakukan INSERT dan UPDATE
        $query = "INSERT INTO transaksi (id_transaksi, id_pelanggan, id_produk, waktu_order, jumlah, total_harga, status_pembayaran, status_order) 
                  VALUES ('$id_transaksi_baru', '$pelanggan_id', '$produk_id', CURRENT_TIMESTAMP, '$jumlah', '$total_bayar', '$status_bayar', '$status_order')";
        
        if (pg_query($conn, $query)) {
            $stok_baru = $stok_now - $jumlah;
            pg_query($conn, "UPDATE produk SET stok_bahan = $stok_baru WHERE id_produk = '$produk_id'");
            header("Location: index.php"); 
        } else {
            $error_message = "<div class='alert alert-danger'>Gagal menyimpan transaksi: " . pg_last_error($conn) . "</div>";
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

                        <?= $error_message ?> <form method="POST">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold text-secondary text-uppercase small">Pelanggan</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person-circle text-primary"></i></span>
                                    <select name="pelanggan_id" class="form-select border-start-0 ps-3" required>
                                        <option value="">-- Cari Nama --</option>
                                        <?php
                                        $q = pg_query($conn, "SELECT id_pelanggan, nama FROM pelanggan ORDER BY nama ASC");
                                        while ($p = pg_fetch_assoc($q)) {
                                            $sel = ($p['id_pelanggan'] == $form_pelanggan_id) ? 'selected' : ''; // Pertahankan nilai
                                            echo "<option value='{$p['id_pelanggan']}' $sel>{$p['nama']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold text-secondary text-uppercase small">Produk / Layanan</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-box-seam text-primary"></i></span>
                                    <select name="produk_id" id="produk" class="form-select border-start-0 ps-3" onchange="hitung()" required>
                                        <option value="" data-harga="0">-- Pilih Produk --</option>
                                        <?php
                                        $q = pg_query($conn, "SELECT id_produk, nama_produk, harga, stok_bahan FROM produk ORDER BY nama_produk ASC");
                                        while ($pr = pg_fetch_assoc($q)) {
                                            $sel = ($pr['id_produk'] == $form_produk_id) ? 'selected' : ''; // Pertahankan nilai
                                            echo "<option value='{$pr['id_produk']}' data-harga='{$pr['harga']}' $sel>{$pr['nama_produk']} (Stok: {$pr['stok_bahan']})</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-5 mb-4">
                                    <label class="form-label fw-bold text-secondary text-uppercase small">Qty</label>
                                    <input type="number" name="jumlah" id="qty" class="form-control text-center fw-bold" placeholder="0" oninput="hitung()" required value="<?= $form_jumlah ?>"> </div>
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
                                    <input type="radio" class="btn-check" name="status_pembayaran" id="option1" value="Lunas" required <?= ($form_status_bayar == 'Lunas') ? 'checked' : '' ?>> <label class="btn btn-outline-success w-50 fw-bold" for="option1"><i class="bi bi-check-circle me-1"></i> LUNAS</label>

                                    <input type="radio" class="btn-check" name="status_pembayaran" id="option2" value="Belum Lunas" <?= ($form_status_bayar != 'Lunas') ? 'checked' : '' ?>> <label class="btn btn-outline-warning w-50 fw-bold" for="option2"><i class="bi bi-hourglass-split me-1"></i> UTANG</label>
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
            // Ini untuk menampilkan format angka. Panggil juga saat halaman pertama kali load jika ada nilai
            document.getElementById('total_tampil').value = new Intl.NumberFormat('id-ID').format(h * q);
        }
        // Panggil hitung saat halaman dimuat untuk menampilkan total jika form mempertahankan nilai
        document.addEventListener('DOMContentLoaded', hitung); 
    </script>
</body>
</html>