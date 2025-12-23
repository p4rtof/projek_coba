<?php
include '../../config/koneksi.php';
include '../../auth/auth.php';

// --- LOGIC SIMPAN DATA ---
if (isset($_POST['simpan'])) {
    $bank = pg_escape_string($conn, $_POST['bank']);
    $rek  = pg_escape_string($conn, $_POST['rek']);
    $an   = pg_escape_string($conn, $_POST['an']);
    
    pg_query($conn, "INSERT INTO bank_akun (nama_bank, no_rekening, atas_nama) VALUES ('$bank', '$rek', '$an')");
    header("Location: index.php");
}

// --- LOGIC HAPUS DATA ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    pg_query($conn, "DELETE FROM bank_akun WHERE id_bank = '$id'");
    header("Location: index.php");
}

// Hitung Total Bank
$total_bank = pg_fetch_assoc(pg_query($conn, "SELECT COUNT(*) AS total FROM bank_akun"))['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Kelola Bank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --secondary: #64748b;
            --dark: #0f172a;
            --light: #f8fafc;
            --border: #e2e8f0;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
        }
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; color: var(--dark); }
        
        /* Card Styling - TANPA GARIS BIRU DI ATAS */
        .card-modern {
            background: white; 
            border: 1px solid white; 
            border-radius: 16px;
            box-shadow: var(--card-shadow); 
            transition: transform 0.2s, box-shadow 0.2s;
            /* Border-top dihapus agar bersih */
        }
        .card-modern:hover { box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02); }

        /* Form Elements */
        .form-label { font-size: 0.85rem; font-weight: 600; color: var(--secondary); margin-bottom: 0.4rem; }
        .form-control-modern {
            border: 1px solid var(--border); border-radius: 10px; padding: 10px 14px;
            font-size: 0.95rem; background-color: var(--light); transition: all 0.2s;
        }
        .form-control-modern:focus { 
            background-color: white; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); 
        }

        /* Button Custom */
        .btn-modern {
            background: var(--primary); color: white; border: none; padding: 12px;
            border-radius: 10px; font-weight: 600; width: 100%; transition: all 0.2s;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
        }
        .btn-modern:hover { background: var(--primary-hover); transform: translateY(-2px); }

        /* Table Styling */
        .table-custom { margin: 0; }
        .table-custom thead th {
            background: #f8fafc; color: var(--secondary); font-size: 0.75rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.05em; padding: 16px 24px; border-bottom: 1px solid var(--border);
        }
        .table-custom tbody td { padding: 16px 24px; vertical-align: middle; font-size: 0.95rem; border-bottom: 1px solid var(--border); color: var(--dark); }
        .table-custom tbody tr:hover { background-color: #f8fafc; }

        /* Icon Circle */
        .icon-circle {
            width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
            font-size: 18px; color: white;
            background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
            box-shadow: 0 4px 6px rgba(14, 165, 233, 0.2);
        }

        /* Action Buttons */
        .btn-icon {
            width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center;
            border-radius: 8px; border: 1px solid var(--border); background: white; color: var(--secondary);
            transition: all 0.2s; cursor: pointer; text-decoration: none;
        }
        .btn-icon.delete:hover { color: #ef4444; border-color: #ef4444; background: #fef2f2; }
        
        .stats-pill { background: #e0e7ff; color: #4338ca; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; }
    </style>
</head>
<body>
    
    <?php include '../../components/navbar.php'; ?>

    <div class="container pb-5 pt-0 mt-4">
        
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h3 class="fw-bold m-0" style="letter-spacing: -0.5px;">Metode Pembayaran</h3>
                <!-- <p class="text-secondary m-0 small">Kelola rekening bank untuk pembayaran transfer.</p> -->
            </div>
            <div>
                <span class="stats-pill"><i class="bi bi-bank me-2"></i><?= $total_bank ?> Rekening</span>
            </div>
        </div>

        <div class="row g-4">
            
            <div class="col-lg-4">
                <div class="card-modern p-4 sticky-top" style="top: 90px; z-index: 1;">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 me-3">
                            <i class="bi bi-credit-card-2-front-fill fs-4"></i>
                        </div>
                        <h5 class="fw-bold m-0">Tambah Bank</h5>
                    </div>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nama Bank</label>
                            <input type="text" name="bank" class="form-control form-control-modern" placeholder="Contoh: BCA, Mandiri..." required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nomor Rekening</label>
                            <input type="number" name="rek" class="form-control form-control-modern" placeholder="1234567890" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Atas Nama</label>
                            <input type="text" name="an" class="form-control form-control-modern" placeholder="Nama Pemilik Rekening" required>
                        </div>

                        <button type="submit" name="simpan" class="btn-modern">
                            <i class="bi bi-plus-lg me-2"></i> Simpan Rekening
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card-modern overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Nama Bank</th>
                                    <th>Informasi Rekening</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $q = pg_query($conn, "SELECT * FROM bank_akun ORDER BY id_bank ASC");
                                if (pg_num_rows($q) > 0):
                                    while($r = pg_fetch_assoc($q)): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="icon-circle me-3">
                                                    <i class="bi bi-bank"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark"><?= $r['nama_bank'] ?></div>
                                                    <div class="small text-secondary" style="font-size: 0.75rem;">ID: <?= $r['id_bank'] ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <td>
                                            <div class="fw-bold text-dark mb-1" style="font-family: monospace; font-size: 1rem;">
                                                <?= $r['no_rekening'] ?>
                                            </div>
                                            <div class="text-secondary small">
                                                a.n <span class="text-dark fw-medium"><?= $r['atas_nama'] ?></span>
                                            </div>
                                        </td>

                                        <td class="text-end pe-4">
                                            <a href="index.php?hapus=<?= $r['id_bank'] ?>" onclick="return confirm('Yakin hapus rekening ini?')" class="btn-icon delete" title="Hapus">
                                                <i class="bi bi-trash3"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; 
                                else: ?>
                                    <tr><td colspan="3" class="text-center py-5 text-secondary">
                                        <i class="bi bi-wallet2 fs-1 d-block mb-2 opacity-25"></i>
                                        Belum ada data rekening.
                                    </td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>