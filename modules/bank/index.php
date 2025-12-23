<?php
include '../../config/koneksi.php';
include '../../auth/auth.php';

// SIMPAN DATA (Tambah Bank)
if (isset($_POST['simpan'])) {
    // Pakai pg_escape_string biar aman dari tanda kutip
    $bank = pg_escape_string($conn, $_POST['bank']);
    $rek  = pg_escape_string($conn, $_POST['rek']);
    $an   = pg_escape_string($conn, $_POST['an']);
    
    pg_query($conn, "INSERT INTO bank_akun (nama_bank, no_rekening, atas_nama) VALUES ('$bank', '$rek', '$an')");
    header("Location: index.php");
}

// HAPUS DATA
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    pg_query($conn, "DELETE FROM bank_akun WHERE id_bank = '$id'");
    header("Location: index.php");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Kelola Bank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    
    <?php include '../../components/navbar.php'; ?>

    <div class="container py-4">
        <div class="row">
            <div class="col-md-4">
                <div class="card p-4 mb-3 shadow-sm border-0 rounded-4">
                    <form method="POST">
                        <h5 class="mb-3 fw-bold text-primary"><i class="bi bi-bank me-2"></i>Tambah Bank</h5>
                        <div class="mb-2">
                            <input type="text" name="bank" class="form-control" placeholder="Nama Bank (Contoh: BCA)" required>
                        </div>
                        <div class="mb-2">
                            <input type="number" name="rek" class="form-control" placeholder="No. Rekening" required>
                        </div>
                        <div class="mb-3">
                            <input type="text" name="an" class="form-control" placeholder="Atas Nama" required>
                        </div>
                        <button type="submit" name="simpan" class="btn btn-primary w-100 fw-bold rounded-pill">Simpan</button>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-dark">Daftar Rekening</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Bank</th>
                                    <th>No. Rekening</th>
                                    <th>Atas Nama</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $q = pg_query($conn, "SELECT * FROM bank_akun ORDER BY id_bank ASC");
                                if (pg_num_rows($q) > 0):
                                    while($r = pg_fetch_assoc($q)): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-primary"><?= $r['nama_bank'] ?></td>
                                        <td class="fw-bold"><?= $r['no_rekening'] ?></td>
                                        <td><?= $r['atas_nama'] ?></td>
                                        <td class="text-end pe-4">
                                            <a href="index.php?hapus=<?= $r['id_bank'] ?>" onclick="return confirm('Hapus rekening ini?')" class="btn btn-sm btn-outline-danger rounded-circle" title="Hapus">
                                                <i class="bi bi-trash-fill"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; 
                                else: ?>
                                    <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada data bank.</td></tr>
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