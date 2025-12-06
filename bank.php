<?php
include 'koneksi.php';
include 'auth.php';

// SIMPAN BANK
if (isset($_POST['simpan'])) {
    $bank = $_POST['nama_bank'];
    $norek = $_POST['no_rekening'];
    $an = $_POST['atas_nama'];
    pg_query($conn, "INSERT INTO bank_akun (nama_bank, no_rekening, atas_nama) VALUES ('$bank', '$norek', '$an')");
    header("Location: bank.php");
}

// HAPUS BANK
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    pg_query($conn, "DELETE FROM bank_akun WHERE id_bank = $id");
    header("Location: bank.php");
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
    <?php include 'navbar.php'; ?>
    <div class="container py-5">
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white"><h5 class="mb-0">Tambah Rekening Toko</h5></div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-2">
                                <label>Nama Bank</label>
                                <input type="text" name="nama_bank" class="form-control" placeholder="Contoh: BCA" required>
                            </div>
                            <div class="mb-2">
                                <label>No. Rekening</label>
                                <input type="number" name="no_rekening" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Atas Nama</label>
                                <input type="text" name="atas_nama" class="form-control" required>
                            </div>
                            <button type="submit" name="simpan" class="btn btn-primary w-100">Simpan</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5>Daftar Rekening Tersedia</h5>
                        <table class="table table-bordered">
                            <thead><tr><th>Bank</th><th>No. Rek</th><th>A.N</th><th>Aksi</th></tr></thead>
                            <tbody>
                                <?php
                                $q = pg_query($conn, "SELECT * FROM bank_akun ORDER BY id_bank ASC");
                                while ($r = pg_fetch_assoc($q)): ?>
                                <tr>
                                    <td><?= $r['nama_bank'] ?></td>
                                    <td><?= $r['no_rekening'] ?></td>
                                    <td><?= $r['atas_nama'] ?></td>
                                    <td><a href="bank.php?hapus=<?= $r['id_bank'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')">Hapus</a></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>