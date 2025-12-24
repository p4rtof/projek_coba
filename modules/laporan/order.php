<?php
include '../../config/koneksi.php';
include '../../auth/auth.php';

// --- LOGIC FILTER ---
$filter = $_GET['tampil'] ?? 'proses';

if ($filter == 'utang') {
    $where = "status_pembayaran = 'Belum Lunas'";
    $judul = "Tagihan Belum Lunas";
    $desc = "Daftar transaksi yang belum dibayar lunas oleh pelanggan.";
} else {
    $where = "status_order != 'Done'";
    $judul = "Order Dalam Proses";
    $desc = "Daftar pesanan yang sedang dikerjakan atau menunggu diambil.";
}

// Query Data
$query = "SELECT t.*, p.nama, pr.nama_produk 
          FROM transaksi t 
          JOIN pelanggan p ON t.id_pelanggan=p.id_pelanggan 
          JOIN produk pr ON t.id_produk=pr.id_produk 
          WHERE $where 
          ORDER BY t.id_transaksi DESC";

$q = pg_query($conn, $query);
$total_data = pg_num_rows($q);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <title>Laporan Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #E0E7FF;
            --primary-hover: #4338ca;
            --secondary: #64748b;
            --dark: #0f172a;
            --light: #f8fafc;
            --border: #e2e8f0;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
        }

        body {
            background-color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            color: var(--dark);
        }

        /* Card Style */
        .card-modern {
            background: white;
            border: 1px solid white;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
        }

        .card-modern:hover {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
        }

        /* Custom Tabs untuk Filter */
        .nav-pills-custom {
            background: white;
            padding: 6px;
            border-radius: 12px;
            border: 1px solid var(--border);
            display: inline-flex;
        }

        .nav-pills-custom .nav-link {
            border-radius: 8px;
            color: var(--secondary);
            font-weight: 600;
            font-size: 0.9rem;
            padding: 8px 20px;
            transition: all 0.2s;
        }

        .nav-pills-custom .nav-link.active {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.2);
        }

        .nav-pills-custom .nav-link:hover:not(.active) {
            background-color: var(--light);
            color: var(--primary);
        }

        /* Table Style */
        .table-custom {
            margin: 0;
        }

        .table-custom thead th {
            background: #f8fafc;
            color: var(--secondary);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 16px 24px;
            border-bottom: 1px solid var(--border);
        }

        .table-custom tbody td {
            padding: 16px 24px;
            vertical-align: middle;
            font-size: 0.95rem;
            border-bottom: 1px solid var(--border);
            color: var(--dark);
        }

        .table-custom tbody tr:hover {
            background-color: #f8fafc;
        }

        /* Status Badges */
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .bg-soft-success {
            background: #dcfce7;
            color: #166534;
        }

        .bg-soft-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .bg-soft-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .bg-soft-info {
            background: #e0f2fe;
            color: #075985;
        }

        /* Button Icon */
        .btn-icon {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: white;
            color: var(--secondary);
            transition: all 0.2s;
        }

        .btn-icon:hover {
            background: var(--light);
            color: var(--primary);
            border-color: var(--primary);
        }

        .stats-pill {
            background: #e0e7ff;
            color: #4338ca;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
        }
    </style>
</head>

<body>

    <?php include '../../components/navbar.php'; ?>

    <div class="container pb-5 pt-0 mt-4">

        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="fw-bold m-0" style="letter-spacing: -0.5px;">Laporan Order</h3>
                <p class="text-secondary m-0 small"><?= $desc ?></p>
            </div>
            <div>
                <span class="stats-pill"><i class="bi bi-list-check me-2"></i><?= $total_data ?> Transaksi</span>
            </div>
        </div>

        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div class="nav nav-pills nav-pills-custom">
                <a class="nav-link <?= ($filter == 'proses') ? 'active' : '' ?>" href="order.php?tampil=proses">
                    <i class="bi bi-hourglass-split me-1"></i> Dalam Proses
                </a>
                <a class="nav-link <?= ($filter == 'utang') ? 'active' : '' ?>" href="order.php?tampil=utang">
                    <i class="bi bi-exclamation-circle me-1"></i> Belum Lunas
                </a>
            </div>

            <button onclick="window.print()"
                class="btn btn-outline-secondary btn-sm rounded-pill px-3 d-none d-md-block">
                <i class="bi bi-printer me-2"></i> Cetak Laporan
            </button>
        </div>

        <div class="card-modern">
            <form action="../transaksi/invoice_gabungan.php" method="POST" target="_blank" id="formCetak">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="m-0 fw-bold">Data Transaksi</h6>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-printer-fill me-2"></i>Cetak Invoice Gabungan
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" width="40">
                                    <input type="checkbox" id="checkAll" class="form-check-input"
                                        style="cursor: pointer;">
                                </th>
                                <th>ID</th>
                                <th>Tanggal</th>
                                <th>Pelanggan</th>
                                <th>Produk</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Query tetap sama seperti sebelumnya
                            $query = "SELECT t.*, p.nama_produk, pel.nama as nama_pelanggan 
                          FROM transaksi t 
                          JOIN produk p ON t.id_produk = p.id_produk 
                          JOIN pelanggan pel ON t.id_pelanggan = pel.id_pelanggan 
                          ORDER BY t.waktu_order DESC";
                            $result = pg_query($conn, $query);

                            while ($row = pg_fetch_assoc($result)):
                                ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" name="ids[]" value="<?= $row['id_transaksi'] ?>"
                                            class="form-check-input check-item" style="cursor: pointer;">
                                    </td>
                                    <td class="small fw-bold text-secondary"><?= $row['id_transaksi'] ?></td>
                                    <td class="small"><?= date('d/m/y H:i', strtotime($row['waktu_order'])) ?></td>
                                    <td class="fw-bold"><?= $row['nama_pelanggan'] ?></td>
                                    <td><?= $row['nama_produk'] ?> <span
                                            class="badge bg-light text-dark border ms-1"><?= $row['jumlah'] ?></span></td>
                                    <td class="fw-bold text-success">Rp
                                        <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                                    <td>
                                        <?php if ($row['status_pembayaran'] == 'Lunas'): ?>
                                            <span
                                                class="badge bg-success bg-opacity-10 text-success px-2 py-1 rounded-pill">Lunas</span>
                                        <?php else: ?>
                                            <span
                                                class="badge bg-warning bg-opacity-10 text-warning px-2 py-1 rounded-pill">Belum
                                                Lunas</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="../transaksi/invoice.php?id=<?= $row['id_transaksi'] ?>"
                                            class="btn-icon btn-light border" title="Print Satuan">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <script>
                document.getElementById('checkAll').addEventListener('change', function () {
                    let checkboxes = document.querySelectorAll('.check-item');
                    checkboxes.forEach((checkbox) => {
                        checkbox.checked = this.checked;
                    });
                });
            </script>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>