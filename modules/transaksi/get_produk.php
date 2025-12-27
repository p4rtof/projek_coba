<?php
include '../../config/koneksi.php';
include '../../auth/auth.php';

// Ambil data produk terbaru
$query = "SELECT id_produk, nama_produk, harga, jenis_satuan, stok_bahan FROM produk ORDER BY nama_produk ASC";
$result = pg_query($conn, $query);

echo '<option value="" data-harga="0" data-jenis="" data-stok="0">-- Pilih Produk --</option>';

while ($pr = pg_fetch_assoc($result)) {
    echo "<option value='{$pr['id_produk']}' 
            data-nama='{$pr['nama_produk']}' 
            data-harga='{$pr['harga']}' 
            data-jenis='{$pr['jenis_satuan']}' 
            data-stok='{$pr['stok_bahan']}'>
            {$pr['nama_produk']}
          </option>";
}
?>