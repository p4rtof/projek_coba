<?php include 'includes/koneksi.php'; ?>

<h3>Cari Pelanggan</h3>
<form method="GET">
    <input type="text" name="q" placeholder="Nama Pelanggan..." required>
    <button type="submit">Cari</button>
</form>

<table border="1" cellpadding="8" cellspacing="0" style="margin-top:10px;">
    <tr>
        <th>ID</th>
        <th>Nama</th>
        <th>Aksi</th>
    </tr>

    <?php
    $keyword = isset($_GET['q']) ? $_GET['q'] : '';
    
    // Pake ILIKE buat Postgres (Case Insensitive)
    $query = "SELECT * FROM pelanggan WHERE nama ILIKE '%$keyword%' ORDER BY nama ASC";
    $result = pg_query($conn, $query);

    while ($row = pg_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['nama'] . "</td>";
        // INI TOMBOL AJAIBNYA
        // Dia lempar 'id_pelanggan' ke file transaksi_baru.php
        echo "<td>
                <a href='transaksi_baru.php?id_pelanggan=" . $row['id'] . "'>
                   <button>Pilih buat Order</button>
                </a>
              </td>";
        echo "</tr>";
    }
    ?>
</table>