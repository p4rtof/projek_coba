<?php
include '../../config/koneksi.php';
include '../../auth/auth.php';

// --- LOGIKA MENANGKAP ID ---
$where_clause = "";
$mode_judul = "";
$ids_to_check = [];
$params_sj = [];

if (isset($_GET['item_id'])) {
    $uid = pg_escape_string($conn, $_GET['item_id']);
    $q_cek = pg_query($conn, "SELECT id_transaksi FROM transaksi WHERE id = '$uid'");
    if ($r = pg_fetch_assoc($q_cek)) {
        $ids_to_check[] = $r['id_transaksi'];
        $where_clause = "WHERE t.id = '$uid'";
        $params_sj[] = "item_id=" . urlencode($_GET['item_id']);
    }
} elseif (isset($_POST['ids']) && !empty($_POST['ids'])) {
    $ids_to_check = $_POST['ids'];
    $ids_clean = array_map(function ($id) use ($conn) {
        return pg_escape_string($conn, $id);
    }, $ids_to_check);
    $ids_string = "'" . implode("','", $ids_clean) . "'";
    $where_clause = "WHERE t.id_transaksi IN ($ids_string)";
    foreach ($_POST['ids'] as $id)
        $params_sj[] = "ids[]=" . urlencode($id);
} elseif (isset($_GET['id'])) {
    $id_trx = pg_escape_string($conn, $_GET['id']);
    $ids_to_check[] = $id_trx;
    $where_clause = "WHERE t.id_transaksi = '$id_trx'";
    $params_sj[] = "id=" . urlencode($_GET['id']);
} else {
    echo "<script>alert('Data transaksi tidak ditemukan!'); history.back();</script>";
    exit;
}

$unique_ids = array_unique($ids_to_check);
$mode_judul = (count($unique_ids) === 1) ? "#" . reset($unique_ids) : "#GABUNGAN";
$link_surat_jalan = "surat_jalan.php?" . implode("&", $params_sj);

// --- QUERY DATA UTAMA ---
$query = "SELECT t.*, p.nama AS p_nama, p.hp, p.alamat, pr.nama_produk, pr.harga AS harga_satuan, b.nama_bank, b.no_rekening, b.atas_nama 
          FROM transaksi t 
          JOIN pelanggan p ON t.id_pelanggan=p.id_pelanggan 
          JOIN produk pr ON t.id_produk=pr.id_produk 
          LEFT JOIN bank_akun b ON t.id_bank = b.id_bank 
          $where_clause 
          ORDER BY t.waktu_order ASC";

$result = pg_query($conn, $query);
if (!$result || pg_num_rows($result) == 0) {
    die("Data invoice tidak ditemukan.");
}

$first_row = pg_fetch_assoc($result);
pg_result_seek($result, 0);

// --- [LOGIKA PILIH LOGO & TEKS DEFAULT] ---
$nama_pelanggan = strtoupper(trim($first_row['p_nama']));
$is_bayer = (strpos($nama_pelanggan, 'PT. BAYER INDONESIA') !== false);

$available_logos = [
    'Sriwijaya Print' => '../../logo.png.jpeg',
    'Awab Print' => '../../awabprint.jpeg',
    // 'Bayer' => '../../logo_bayer.png'
];

// Set Logo Awal
if ($is_bayer && isset($available_logos['Bayer'])) {
    $logo_src = $available_logos['Bayer'];
} else {
    $logo_src = reset($available_logos);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <title>Invoice <?= $mode_judul ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        /* STYLE LAYAR */
        body {
            background: #525659;
            font-family: sans-serif;
            -webkit-print-color-adjust: exact;
        }

        .container {
            max-width: 820px;
        }

        .invoice-box {
            background: white;
            padding: 30px 60px;
            min-height: 29.7cm;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
            margin: 20px auto;
        }

        /* KOP SURAT */
        .kop-surat .tagline {
            font-size: 0.75rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 3px;
        }

        .kop-surat .pt-name {
            font-weight: 700;
            font-size: 0.8rem;
            color: #000;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .kop-surat .address {
            font-size: 0.8rem;
            color: #333;
            margin-bottom: 0;
            line-height: 1.4;
        }

        /* FITUR EDITABLE */
        .editable:hover {
            background-color: #fffeca;
            cursor: text;
            outline: 1px dashed #ccc;
        }

        .table-sm td,
        .table-sm th {
            font-size: 0.85rem;
            vertical-align: middle; /* Ubah ke middle biar rapi */
            padding: 4px 5px !important;
            border-color: #ffffffff !important;
        }

        /* SETTING PRINT */
        @media print {
            @page {
                size: A4;
                margin: 0;
            }

            body {
                background: white;
                margin: 1cm 2.5cm;
            }

            .no-print {
                display: none !important;
            }

            .container {
                max-width: 100%;
                width: 100%;
                padding: 0;
                margin: 0;
            }

            .invoice-box {
                box-shadow: none;
                border: none;
                padding: 0;
                margin: 0;
                width: 100%;
                min-height: auto;
            }

            .editable:hover {
                background: none;
                outline: none;
            }

            .pt-name {
                font-size: 11pt !important;
            }

            .address {
                font-size: 9pt !important;
            }

            td,
            th {
                font-size: 9pt !important;
                padding: 2px 4px !important;
            }

            img {
                height: 50px !important;
            }

            h2 {
                font-size: 1.5rem !important;
            }

            .mb-3,
            .mb-4 {
                margin-bottom: 10px !important;
            }
        }
    </style>
</head>

<body>
    <div class="container py-0 py-md-3">
        <div class="row justify-content-center">
            <div class="col-12">

                <div class="mb-3 mt-2 no-print sticky-top bg-transparent" style="top: 10px; z-index: 100;">
                    <div
                        class="d-flex justify-content-between align-items-center bg-white p-2 rounded shadow-sm border">

                        <div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-warning fw-bold dropdown-toggle text-dark"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
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

                            <small class="text-muted ms-2 fst-italic no-print" style="font-size: 10px;">*Teks kop surat
                                bisa diklik & diedit</small>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="javascript:history.back()" class="btn btn-sm btn-light border fw-bold"><i
                                    class="bi bi-arrow-left me-1"></i>Kembali</a>
                            <a href="<?= $link_surat_jalan ?>" target="_blank" class="btn btn-sm btn-dark fw-bold"><i
                                    class="bi bi-truck me-1"></i>Surat Jalan</a>
                            <button onclick="window.print()" class="btn btn-sm btn-primary fw-bold"><i
                                    class="bi bi-printer-fill me-1"></i>Cetak</button>
                        </div>
                    </div>
                </div>

                <div class="invoice-box">
                    <div class="d-flex justify-content-between align-items-start mb-4 border-bottom pb-3"
                        style="border-bottom: 2px solid #000 !important;">
                        <div class="d-flex align-items-center">
                            <img id="mainLogo" src="<?= $logo_src ?>" alt="Logo"
                                style="height: 40px; object-fit: contain; margin-right: 20px;">

                            <div class="kop-surat">
                                <div class="pt-name editable" contenteditable="true">PT. RHAMIZA PERDANA INDONESIA</div>
                                <p class="address editable" contenteditable="true">Jl. Basuki Rahmat No A<br>Kec.
                                    Jatinegara, Jakarta Timur</p>
                            </div>
                        </div>
                        <div class="text-end align-self-center">
                            <h2 class="fw-bold text-dark mb-0" style="letter-spacing: 1px;">INVOICE</h2>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-7">
                            <small class="text-secondary fw-bold text-uppercase" style="font-size: 0.7rem;">Kepada
                                Yth:</small>
                            <div class="fw-bold text-dark fs-6 mt-1"><?= $first_row['p_nama'] ?></div>

                            <?php if (!empty($first_row['hp']) && $first_row['hp'] !== '-'): ?>
                                <div class="text-dark small mt-1">
                                    <span class="fw-bold">Telp:</span> <?= $first_row['hp'] ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($first_row['alamat']) && $first_row['alamat'] !== '-'): ?>
                                <div class="text-dark small mt-1"
                                    style="white-space: normal; line-height: 1.4; width: 90%;">
                                    <?= $first_row['alamat'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-5 text-end">
                            <table style="width: auto; margin-left: auto; text-align: left; font-size: 0.9rem;">
                                <tr>
                                    <td class="fw-bold text-secondary pe-3 pb-1">Tanggal</td>
                                    <td class="fw-bold text-dark pb-1">:
                                        <?php
                                        // Format Tanggal Indo
                                        $ts = strtotime($first_row['waktu_order']);
                                        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                                        echo date('d', $ts) . ' ' . $bulan[date('n', $ts)] . ' ' . date('Y', $ts);
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-secondary pe-3 pb-1">No. Invoice</td>
                                    <td class="fw-bold text-dark pb-1">: <?= str_replace('#', '', $mode_judul) ?></td>
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

                    <div class="mb-4">
                        <small class="text-secondary fw-bold text-uppercase" style="font-size: 0.7rem;">Metode Pembayaran :</small>
                        <div class="fw-bold text-dark small mt-1">
                            <?php if (($first_row['metode_pembayaran'] ?? '') == 'Transfer'): ?>
                                <i class="bi bi-bank me-1"></i> <?= $first_row['nama_bank'] ?> 
                                <span class="mx-1">|</span> 
                                <?= $first_row['no_rekening'] ?> 
                                <span class="mx-1">|</span> 
                                <span class="fst-italic text-secondary">a.n <?= $first_row['atas_nama'] ?></span>
                            <?php else: ?>
                                <i class="bi bi-cash me-1"></i> Tunai (Cash)
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="table-responsive mb-4">
                        <table class="table table-bordered border-dark mb-0 table-sm">
                            <thead style="background-color: #eee;">
                                <tr>
                                    <th class="text-start text-dark" style="width: 52%;">Deskripsi Produk</th>
                                    <th class="text-center text-dark" style="width: 8%;">Qty</th>
                                    <th class="text-end text-dark" style="width: 20%;">Harga</th>
                                    <th class="text-end text-dark" style="width: 20%;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $grand_total = 0;
                                while ($row = pg_fetch_assoc($result)):
                                    $grand_total += $row['total_harga']; 
                                    
                                    // [MODIFIKASI] Gabungkan Nama Produk dan Ukuran
                                    $nama_barang = $row['nama_produk'];
                                    if($row['panjang'] > 0) {
                                        $nama_barang .= " (Ukuran: " . floatval($row['panjang']) . "m x " . floatval($row['lebar']) . "m)";
                                    }
                                ?>
                                    <tr style="height: 5px;">
                                        <td class="text-start">
                                            <span class="fw-bold text-dark small"><?= $nama_barang ?></span>
                                            <?php if (isset($_GET['item_id'])): ?><br><small class="text-muted"
                                                    style="font-size: 0.65rem;">Ref:
                                                    #<?= $row['id_transaksi'] ?></small><?php endif; ?>
                                        </td>
                                        <td class="text-center small fw-bold"><?= number_format($row['jumlah']) ?></td>
                                        <td class="text-end small">Rp
                                            <?= number_format($row['harga_satuan'], 0, ',', '.') ?>
                                        </td>
                                        <td class="text-end small fw-bold text-dark">Rp
                                            <?= number_format($row['total_harga'], 0, ',', '.') ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot style="border-top: 2px solid #000;">
                                <tr>
                                    <td colspan="3" class="text-end fw-bold small pe-2">TOTAL TAGIHAN</td>
                                    <td class="text-end fw-bold fs-6 text-dark">Rp
                                        <?= number_format($grand_total, 0, ',', '.') ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="row mt-5">
                        <div class="col-6 text-center">
                            <p class="mb-5 fw-bold small text-dark" style="font-size: 0.8rem;">HORMAT KAMI</p>
                            <br>
                            <p class="fw-bold mb-0 text-decoration-underline text-dark small">(FARZA)</p>
                        </div>
                    </div>

                    <div class="text-center mt-5 pt-3 border-top"><small class="text-secondary fst-italic"
                            style="font-size: 0.7rem;">Terima kasih atas kepercayaan Anda.</small></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function changeLogo(path) {
            document.getElementById('mainLogo').src = path;
        }
    </script>

</body>

</html>