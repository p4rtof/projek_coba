<?php include '../../config/koneksi.php'; ?>
<h3>Cari Pelanggan</h3>
<form method="GET">
    <input type="text" name="q" placeholder="Nama..." required>
    <button>Cari</button>
</form>
<table border="1" cellpadding="5" style="margin-top:10px; width:100%">
    <?php
    $k = $_GET['q'] ?? '';
    $q = pg_query($conn, "SELECT * FROM pelanggan WHERE nama ILIKE '%$k%' ORDER BY nama ASC");
    while ($r = pg_fetch_assoc($q)) {
        echo "<tr>
            <td>{$r['nama']}</td>
            <td><a href='../transaksi/baru.php?id_pelanggan={$r['id_pelanggan']}'><button>Pilih</button></a></td>
        </tr>";
    }
    ?>
</table>