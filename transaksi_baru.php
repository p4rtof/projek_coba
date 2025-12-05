<?php 
include 'includes/koneksi.php'; 

// Tangkap ID dari tombol tadi (kalo ada)
$selected_id = isset($_GET['id_pelanggan']) ? $_GET['id_pelanggan'] : ''; 
?>

<h3>Input Transaksi</h3>
<form method="POST" action="proses_transaksi.php">
    <label>Pelanggan:</label><br>
    <select name="pelanggan_id">
        <option value="">-- Pilih Pelanggan --</option>
        <?php
        $q = pg_query($conn, "SELECT * FROM pelanggan");
        while ($r = pg_fetch_assoc($q)) {
            // Cek: Kalo ID-nya sama kayak yang dikirim, tambahin 'selected'
            $pilih = ($r['id'] == $selected_id) ? "selected" : "";
            
            echo "<option value='{$r['id']}' $pilih>{$r['nama']}</option>";
        }
        ?>
    </select>
    
    </form>