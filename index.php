<!-- <?php include 'includes/koneksi.php'; ?> -->
<?php
include 'koneksi.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sistem Coba</title>
</head>
<body>

    <h2>🖨️ Dashboard Percetakan</h2>
    
    <a href="index.php">Home</a> | 
    <a href="transaksi_baru.php">Input Order Manual</a> | 
    <a href="tambah_pelanggan.php">Tambah Pelanggan Baru</a>
    <hr>

    <div style="padding: 20px; background: #f0f0f0; border: 1px solid #ccc;">
        <h3>🔍 Cari Pelanggan Buat Order</h3>
        <form method="GET" action="index.php">
            <input type="text" name="q" placeholder="Ketik nama pelanggan..." 
                   value="<?= isset($_GET['q']) ? $_GET['q'] : '' ?>" required>
            <button type="submit">Cari</button>
            <a href="index.php"><button type="button">Reset</button></a>
        </form>
    </div>

    <br>

    <?php
    if (isset($_GET['q'])) {
        $keyword = $_GET['q'];
        echo "<h4>Hasil Pencarian: '$keyword'</h4>";

        // Query Cari (Limit 10 biar gak kepanjangn)
        $query = "SELECT * FROM pelanggan WHERE nama ILIKE '%$keyword%' LIMIT 10";
        $result = pg_query($conn, $query);

        if (pg_num_rows($result) > 0) {
            echo "<table border='1' cellpadding='10' cellspacing='0' width='100%'>";
            echo "<tr style='background:#ddd;'>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>No. HP</th>
                    <th>Aksi</th>
                  </tr>";

            while ($row = pg_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td><b>" . $row['nama'] . "</b></td>";
                echo "<td>" . $row['hp'] . "</td>";
                // Tombol langsung gas ke transaksi
                echo "<td align='center'>
                        <a href='transaksi_baru.php?id_pelanggan=" . $row['id'] . "'>
                            <button style='cursor:pointer; background:green; color:white;'>PILIH ORDER ➡️</button>
                        </a>
                      </td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color:red;'>Pelanggan gak ketemu. Coba tambah baru?</p>";
        }
    } else {
        echo "<p>Silakan cari nama pelanggan di atas buat mulai transaksi.</p>";
    }
    ?>

</body>
</html>