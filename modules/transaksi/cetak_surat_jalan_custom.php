<?php
// Tangkap Data dari Form
$judul_surat = $_POST['judul_surat'] ?? 'SURAT JALAN';
$tanggal = $_POST['tanggal'] ?? date('Y-m-d');
$penerima_nama = $_POST['penerima_nama'] ?? '-';
$penerima_hp = $_POST['penerima_hp'] ?? '-';
$penerima_alamat = $_POST['penerima_alamat'] ?? '-';

// Data Tabel Dinamis
$headers = $_POST['headers'] ?? [];
$items = $_POST['items'] ?? [];
$values = $_POST['values'] ?? [];

// --- [BARU] FORMAT TANGGAL INDONESIA (12 Desember 2025) ---
$ts = strtotime($tanggal);
$bulan_indo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$tanggal_indo = date('d', $ts) . ' ' . $bulan_indo[date('n', $ts)] . ' ' . date('Y', $ts);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <title>Cetak Surat Jalan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background: white;
            font-family: 'Arial', sans-serif;
            -webkit-print-color-adjust: exact;
            font-size: 14px;
        }

        .container {
            max-width: 100%;
            padding: 0 30px;
        }

        /* STYLE TABEL INFO PENERIMA */
        .info-table {
            width: 100%;
            font-size: 14px;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .label-col {
            width: 120px;
            color: #333;
        }

        .sep-col {
            width: 20px;
            text-align: center;
        }

        /* JUDUL SURAT JALAN BESAR */
        .main-title {
            text-align: left;
            font-weight: 800;
            font-size: 24px;
            text-transform: uppercase;
            margin-top: 20px;
            margin-bottom: 15px;
        }

        /* TABEL DATA CUSTOM */
        .table-custom {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-top: 10px;
        }

        .table-custom thead th {
            background-color: #FFFF00 !important;
            /* KUNING TERANG */
            color: black !important;
            border: 1px solid #000;
            text-align: center;
            padding: 8px 5px;
            font-weight: bold;
            font-size: 14px;
            vertical-align: middle;
        }

        .table-custom tbody td {
            border: 1px solid #000;
            padding: 4px 8px;
            vertical-align: middle;
            font-size: 13px;
        }

        .col-item {
            text-align: left;
        }

        .col-data {
            text-align: center;
            font-weight: bold;
        }

        /* --- SETTING CETAK (PRINT) --- */
        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }

            body {
                margin: 0;
                padding: 0;
                background: white;
            }

            .container {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 1cm 1.5cm !important;
            }

            .no-print {
                display: none !important;
            }

            .table-custom,
            .table-custom th,
            .table-custom td {
                border: 1px solid #000 !important;
            }
        }
    </style>
</head>

<body>

    <div class="container py-4">

        <div class="no-print position-fixed top-0 end-0 p-3" style="z-index:999;">
            <button onclick="history.back()" class="btn  shadow-sm me-2"><a href="../../index.php">Kembali</a></button>
            <button onclick="window.print()" class="btn btn-primary shadow-sm"><i class="bi bi-printer"></i>
                Cetak</button>
        </div>

        <div class="row mb-4">
            <div class="col-6">
                <div class="d-flex align-items-center mb-2">
                    <img src="../../logo/image.png.jpeg" alt="Logo" style="width: 280px; object-fit: contain;">
                </div>
            </div>

            <div class="col-6 text-end">
                <h1 class="main-title">SURAT JALAN</h1>

                <div class="text-start d-inline-block" style="min-width: 100%; max-width: 400px;">
                    <table class="info-table">
                        <tr>
                            <td class="label-col">Nama Penerima</td>
                            <td class="sep-col">:</td>
                            <td class="fw-bold"><?= $penerima_nama ?></td>
                        </tr>

                        <?php if (!empty($penerima_hp) && $penerima_hp !== '-'): ?>
                            <tr>
                                <td class="label-col">No. Telp</td>
                                <td class="sep-col">:</td>
                                <td><?= $penerima_hp ?></td>
                            </tr>
                        <?php endif; ?>

                        <?php if (!empty($penerima_alamat) && $penerima_alamat !== '-'): ?>
                            <tr>
                                <td class="label-col">Alamat</td>
                                <td class="sep-col">:</td>
                                <td><?= $penerima_alamat ?></td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-end mb-2">
            <h4 class="fw-bold m-0 text-uppercase"><?= $judul_surat ?></h4>
            <div style="font-size: 14px;">Tanggal : <b><?= $tanggal_indo ?></b></div>
        </div>

        <table class="table-custom">
            <thead>
                <tr>
                    <th style="width: 30%; text-align: left; padding-left: 10px;">Packing Item</th>
                    <?php foreach ($headers as $h): ?>
                        <th><?= $h ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($items) > 0):
                    foreach ($items as $index => $itemName):
                        ?>
                        <tr>
                            <td class="col-item fw-bold"><?= $itemName ?></td>
                            <?php
                            if (isset($values[$index])) {
                                foreach ($values[$index] as $val) {
                                    echo "<td class='col-data'>" . ($val == '' ? '-' : $val) . "</td>";
                                }
                            } else {
                                foreach ($headers as $h) {
                                    echo "<td class='col-data'>-</td>";
                                }
                            }
                            ?>
                        </tr>
                    <?php
                    endforeach;
                else:
                    ?>
                    <tr>
                        <td colspan="<?= count($headers) + 1 ?>" class="text-center py-3">- Tidak ada item -</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="row mt-5" style="page-break-inside: avoid;">
            <div class="col-6 text-center">
                <p class="mb-5 fw-bold" style="margin-bottom: 80px !important;">Tanda Terima</p>
                </div>
            <div class="col-6 text-center">
                <p class="mb-5 fw-bold" style="margin-bottom: 80px !important;">Hormat Kami</p>
                </div>
        </div>

    </div>

</body>

</html>