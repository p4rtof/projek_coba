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
    $ids_clean = array_map(function ($id) use ($conn) {
        return pg_escape_string($conn, $id);
    }, $ids_to_check);
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
$query = "SELECT t.*, p.nama AS p_nama, pr.nama_produk 
          FROM transaksi t 
          JOIN pelanggan p ON t.id_pelanggan=p.id_pelanggan 
          JOIN produk pr ON t.id_produk=pr.id_produk 
          $where_clause 
          ORDER BY t.waktu_order ASC";

$result = pg_query($conn, $query);
if (!$result || pg_num_rows($result) == 0) {
    die("Data tidak ditemukan.");
}

$first_row = pg_fetch_assoc($result);
pg_result_seek($result, 0);

// --- [LOGIKA PILIH LOGO & DEFAULT TEXT] ---
// Daftar Logo yang Tersedia
$available_logos = [
    'Sriwijaya' => '../../logo/image.png.jpeg',
    'Awab Print' => '../../logo/awabprint_suratjalan.jpeg',
];

// Set Default Logo (Penting biar gambar gak broken)
$logo_src = $available_logos['Sriwijaya'];

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <title>Surat Jalan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background: #525659;
            font-family: 'Arial', sans-serif;
            -webkit-print-color-adjust: exact;
        }

        .sj-box {
            background: white;
            padding: 20px;
            min-height: 29.7cm;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
            margin: 20px auto;
            max-width: 21cm;
            position: relative;
        }

        .kop-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .sj-title {
            font-weight: 700;
            font-size: 2rem;
            text-transform: uppercase;
            color: #000;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .date-label {
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

        .table-sj {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            margin-top: 20px;
        }

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
            padding: 3.5px 10px;
            font-size: 0.9rem;
            vertical-align: top;
        }

        .col-no {
            width: 50px;
            text-align: center;
        }

        .col-qty {
            width: 150px;
            text-align: center;
        }

        .col-ket {
            text-align: left;
        }

        @media print {
            @page {
                size: A4;
                margin: 0;
            }

            body {
                background: white;
                margin: 1cm;
            }

            .no-print {
                display: none !important;
            }

            .sj-box {
                box-shadow: none;
                margin: 0;
                width: 100%;
                max-width: 100%;
                padding: 0;
                min-height: auto;
                page-break-inside: avoid;
            }

            .editable:hover {
                background: none;
                outline: none;
            }

            .table-sj {
                border: 2px solid #000 !important;
            }

            .table-sj th,
            .table-sj td {
                border: 1px solid #000 !important;
            }
        }
    </style>
</head>

<body>

    <div class="container py-0 py-md-3">

        <div class="text-end mb-3 mt-2 no-print sticky-top" style="top: 10px; z-index: 100;">
            <div
                class="d-flex justify-content-end align-items-center gap-2 bg-white p-2 rounded shadow-sm border d-inline-flex">

                <div class="dropdown">
                    <button class="btn btn-sm btn-warning fw-bold dropdown-toggle text-dark" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-image me-1"></i> Ganti Logo
                    </button>
                    <ul class="dropdown-menu">
                        <?php foreach ($available_logos as $name => $path): ?>
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="#"
                                    onclick="changeLogo('<?= $path ?>'); return false;">
                                    <img src="<?= $path ?>"
                                        style="width: 30px; height: 30px; object-fit: contain; margin-right: 10px;">
                                    <?= $name ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <button onclick="window.close()"
                    class="btn btn-sm btn-light border shadow-sm px-3 fw-bold">Tutup</button>
                <button onclick="window.print()" class="btn btn-sm btn-dark shadow-sm px-4 fw-bold"><i
                        class="bi bi-printer-fill me-2"></i>Cetak</button>
            </div>
            <!-- <div class="no-print mt-1"><small class="text-white fst-italic" style="font-size: 11px;">*Klik teks header -->
                    <!-- untuk mengedit</small></div> -->
        </div>

        <div class="sj-box">

            <div class="row align-items-start mb-2">
                <div class="col-7">
                    <div class="kop-container">
                        <img id="mainLogo" src="<?= $logo_src ?>" alt="Logo" style="width: 230px; object-fit: contain;">
                    </div>
                </div>

                <div class="col-5 text-end align-self-center">
                    <h1 class="sj-title">SURAT JALAN</h1>

                    <table
                        style="width: auto; margin-left: auto; text-align: left; margin-top: 15px; font-size: 0.95rem;">
                        <tr>
                            <td class="fw-bold text-secondary pe-3 pb-1">Tanggal</td>
                            <td class="fw-bold text-dark pb-1">:
                                <?php
                                // Format Tanggal Indo (12 Desember 2025)
                                $ts = strtotime($first_row['waktu_order']);
                                $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                                echo date('d', $ts) . ' ' . $bulan[date('n', $ts)] . ' ' . date('Y', $ts);
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-secondary pe-3 pb-1">No. Invoice</td>
                            <td class="fw-bold text-dark pb-1">: <?= $first_row['id_transaksi'] ?></td>
                        </tr>
                        <?php if (!empty($first_row['no_po']) && $first_row['no_po'] !== '-'): ?>
                            <tr>
                                <td class="fw-bold text-secondary pe-3 pb-1">No. PO</td>
                                <td class="fw-bold text-dark pb-1">: <?= $first_row['no_po'] ?></td>
                            </tr>
                        <?php endif; ?>
                    </table>
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
                    while ($row = pg_fetch_assoc($result)):
                        $nama_barang = $row['nama_produk'];
                        if ($row['panjang'] > 0) {
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

    <script>
        function changeLogo(path) {
            document.getElementById('mainLogo').src = path;
        }
    </script>

</body>

</html>