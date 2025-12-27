<?php
include '../../config/koneksi.php';
include '../../auth/auth.php';

// --- LOGIKA MENANGKAP ID ---
$where_clause = "";
$ids_to_check = [];

$REQ_IDS = $_REQUEST['ids'] ?? [];
$REQ_ITEM_ID = $_REQUEST['item_id'] ?? '';
$REQ_ID = $_REQUEST['id'] ?? '';

if (!empty($REQ_ITEM_ID)) {
    $uid = pg_escape_string($conn, $REQ_ITEM_ID);
    $q_cek = pg_query($conn, "SELECT id_transaksi FROM transaksi WHERE id = '$uid'");
    if ($r = pg_fetch_assoc($q_cek)) {
        $ids_to_check[] = $r['id_transaksi'];
        $where_clause = "WHERE t.id = '$uid'";
    }
} elseif (!empty($REQ_IDS)) {
    $ids_to_check = $REQ_IDS;
    $ids_clean = array_map(function($id) use ($conn) { return pg_escape_string($conn, $id); }, $ids_to_check);
    $ids_string = "'" . implode("','", $ids_clean) . "'";
    $where_clause = "WHERE t.id_transaksi IN ($ids_string)";
} elseif (!empty($REQ_ID)) {
    $id_trx = pg_escape_string($conn, $REQ_ID);
    $ids_to_check[] = $id_trx;
    $where_clause = "WHERE t.id_transaksi = '$id_trx'";
} else {
    echo "<script>alert('Data transaksi tidak ditemukan!'); window.close();</script>";
    exit;
}

// --- QUERY DATA ---
$query = "SELECT t.*, pr.nama_produk 
          FROM transaksi t 
          JOIN produk pr ON t.id_produk=pr.id_produk 
          $where_clause 
          ORDER BY t.waktu_order ASC";

$result = pg_query($conn, $query);
if (!$result || pg_num_rows($result) == 0) { die("Data tidak ditemukan."); }

$first_row = pg_fetch_assoc($result);
pg_result_seek($result, 0);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Surat Jalan</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #525659; font-family: 'Arial', sans-serif; -webkit-print-color-adjust: exact; }
        
        .sj-box { 
            background: white; 
            padding: 20px; /* Padding minimalis */
            min-height: 29.7cm; 
            box-shadow: 0 0 20px rgba(0,0,0,0.5); 
            margin: 20px auto;
            max-width: 21cm; 
            position: relative;
        }

        /* HEADER STYLES */
        .kop-container { display: flex; align-items: center; gap: 15px; }
        .kop-text .tagline { font-size: 0.75rem; font-weight: 600; color: #333; margin-bottom: 2px; }
        .kop-text .pt-name { font-weight: 800; font-size: 0.95rem; color: #000; margin-bottom: 2px; letter-spacing: 0.5px; white-space: nowrap; }
        .kop-text .address { font-size: 0.8rem; color: #333; margin-bottom: 0; line-height: 1.2; }

        .sj-title { font-weight: 700; font-size: 2rem; text-transform: uppercase; color: #000; letter-spacing: 1px; margin-bottom: 5px; }
        
        /* KOTAK TANGGAL */
        .date-label {
            /* background-color: #b9cce6; */
            padding: 2px 15px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-block;
            margin-right: 15px;
        }
        .date-value {
            font-weight: bold;
            font-size: 1rem;
        }

        /* TABEL */
        .table-sj { width: 100%; border-collapse: collapse; border: 2px solid #000; margin-top: 20px; }
        .table-sj th {
            border: 1px solid #000;
            border-bottom: 2px solid #000;
            padding: 8px;
            text-align: center;
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        .table-sj td {
            border: 1px solid #000;
            padding: 5px 10px;
            font-size: 0.9rem;
            vertical-align: top;
        }
        
        .col-no { width: 50px; text-align: center; }
        .col-qty { width: 150px; text-align: center; }
        .col-ket { text-align: left; }

        /* PRINT SETTINGS */
        @media print {
            @page { size: A4; margin: 0; }
            
            body { 
                background: white; 
                margin: 1cm; /* Margin standar cetak */
            }
            
            .no-print { display: none !important; }
            
            .sj-box { 
                box-shadow: none; 
                margin: 0; 
                width: 100%; 
                max-width: 100%; 
                padding: 0; 
                min-height: auto; /* Tinggi otomatis ikut konten */
                page-break-inside: avoid; /* Usahakan tidak terpotong */
            }
            
            .table-sj { border: 2px solid #000 !important; }
            .table-sj th, .table-sj td { border: 1px solid #000 !important; }
        }
    </style>
</head>
<body>

<div class="container py-0 py-md-3">
    
    <div class="text-end mb-3 mt-2 no-print gap-2 d-flex justify-content-end sticky-top" style="top: 10px; z-index: 100;">
        <button onclick="window.close()" class="btn btn-sm btn-light border shadow-sm px-3 fw-bold">Tutup</button>
        <button onclick="window.print()" class="btn btn-sm btn-dark shadow-sm px-4 fw-bold"><i class="bi bi-printer-fill me-2"></i>Cetak</button>
    </div>

    <div class="sj-box">
        
        <div class="row align-items-start mb-2">
            <div class="col-7">
                <div class="kop-container">
                    <img src="../../image.png.jpeg" alt="Logo" style="height: 100px; object-fit: contain;">
                    <!-- <div class="kop-text">
                        <div class="tagline">Digital printing, Paper printing & Promosion</div>
                        <div class="pt-name">PT. RHAMIZA PERDANA INDONESIA</div>
                        <div class="address">Jl. Basuki Rahmat No A<br>Kec. Jatinegara, Jakarta Timur</div>
                    </div> -->
                </div>
            </div>

            <div class="col-5 text-end align-self-center">
                <h1 class="sj-title">SURAT JALAN</h1>
                <div class="mt-2">
                    <span class="date-label">Tanggal : </span>
                    <span class="date-value"><?= date('d /m /Y', strtotime($first_row['waktu_order'])) ?></span>
                </div>
            </div>
        </div>

        <div style="height: 10px;"></div>

        <table class="table-sj">
            <thead>
                <tr>
                    <th class="col-no">NO</th>
                    <th class="col-ket">KETERANGAN</th>
                    <th class="col-qty">QUANTITY</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                while($row = pg_fetch_assoc($result)): 
                    // Nama Produk & Detail Ukuran
                    $nama_barang = $row['nama_produk'];
                    if($row['panjang'] > 0) {
                        $nama_barang .= " (Ukuran: " . floatval($row['panjang']) . "m x " . floatval($row['lebar']) . "m)";
                    }
                ?>
                <tr>
                    <td class="col-no"><?= $no++ ?></td>
                    <td class="col-ket fw-bold"><?= $nama_barang ?></td>
                    <td class="col-qty fw-bold"><?= number_format($row['jumlah']) ?></td>
                </tr>
                <?php endwhile; ?>
                
                </tbody>
        </table>

        <div style="margin-top: 20px;"></div>

    </div>
</div>

</body>
</html>