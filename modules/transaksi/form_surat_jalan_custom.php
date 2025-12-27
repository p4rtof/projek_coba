<?php
include '../../config/koneksi.php';
include '../../auth/auth.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Buat Surat Jalan Custom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f1f5f9; font-family: sans-serif; }
        .card-modern { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: none; }
        .header-gradient { background: linear-gradient(135deg, #2c21faff 0%, #6063ffff 100%); color: white; padding: 15px 20px; border-radius: 12px 12px 0 0; }
        .table-input th { background-color: #f8fafc; font-size: 0.85rem; }
        .form-control-sm { border-radius: 4px; }
        .btn-add-col { border-style: dashed; border-width: 2px; }
    </style>
</head>
<body>

<div class="container py-4">
    <form action="cetak_surat_jalan_custom.php" method="POST" target="_blank">
        
        <div class="d-flex align-items-center mb-3">
            <a href="../../index.php" class="btn btn-light rounded-circle shadow-sm me-3 border"><i class="bi bi-arrow-left"></i></a>
            <h4 class="fw-bold m-0">Buat Surat Jalan Manual</h4>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card-modern h-100">
                    <div class="header-gradient"><i class="bi bi-info-circle me-2"></i> Informasi Surat</div>
                    <div class="card-body p-3">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Lokasi</label>
                            <input type="text" name="judul_surat" class="form-control" placeholder="Contoh: East Java 3" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nama Penerima</label>
                            <input type="text" name="penerima_nama" class="form-control" placeholder="Nama..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">No. Telp</label>
                            <input type="text" name="penerima_hp" class="form-control" placeholder="08...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Alamat Penerima</label>
                            <textarea name="penerima_alamat" class="form-control" rows="3" ></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card-modern h-100">
                    <div class="header-gradient bg-dark d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-table me-2"></i> Isi Tabel Barang</span>
                        <button type="button" class="btn btn-sm btn-light text-primary fw-bold shadow-sm" onclick="addCustomColumn()">
                            <i class="bi bi-layout-three-columns me-1"></i> + Tambah Kolom
                        </button>
                    </div>
                    <div class="card-body p-3">
                        <div class="table-responsive">
                            <table class="table table-bordered table-input" id="dynamicTable">
                                <thead>
                                    <tr id="headerRow">
                                        <th style="width: 50px;" class="text-center">No</th>
                                        <th style="min-width: 200px;">Packing Item (Nama Barang)</th>
                                        <th style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="bodyRows">
                                    <tr>
                                        <td class="text-center bg-light">1</td>
                                        <td><input type="text" name="items[]" class="form-control form-control-sm" placeholder="Nama Barang..." required></td>
                                        <td class="text-center"><button type="button" class="btn btn-sm btn-danger py-0 px-2" onclick="removeRow(this)">x</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-add-col w-100 py-2 fw-bold" onclick="addRow()">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Baris Barang
                        </button>
                    </div>
                    <div class="card-footer bg-white border-top p-3 text-end">
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-bold shadow">
                            <i class="bi bi-printer-fill me-2"></i> CETAK SURAT JALAN
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    let colCount = 0;

    function addCustomColumn() {
        colCount++;
        const headerRow = document.getElementById('headerRow');
        const lastTh = headerRow.lastElementChild; // Kolom aksi (hapus)
        
        // Buat Header Baru (Inputan nama kolom)
        const newTh = document.createElement('th');
        newTh.style.minWidth = "100px";
        newTh.innerHTML = `<input type="text" name="headers[]" class="form-control form-control-sm fw-bold text-center border-warning bg-warning bg-opacity-10" placeholder="Nama Kolom (Cth: 9209C)" required><button type="button" class="btn btn-link text-danger p-0 small text-decoration-none w-100" style="font-size:10px" onclick="removeCol(this, ${colCount})">Hapus Kolom</button>`;
        
        // Sisipkan sebelum kolom terakhir
        headerRow.insertBefore(newTh, lastTh);

        // Tambahkan Input Kosong ke SETIAP Baris yang sudah ada
        const rows = document.querySelectorAll('#bodyRows tr');
        rows.forEach((row, index) => {
            const lastTd = row.lastElementChild;
            const newTd = document.createElement('td');
            // Name array harus multidimensi: values[nomor_baris][]
            newTd.innerHTML = `<input type="text" name="values[${index}][]" class="form-control form-control-sm text-center" placeholder="-">`;
            row.insertBefore(newTd, lastTd);
        });
    }

    function removeCol(btn, colIndex) {
        // Cari index kolom dari tombol yang diklik
        const th = btn.parentElement;
        const tr = th.parentElement;
        const index = Array.from(tr.children).indexOf(th);

        // Hapus Header
        th.remove();

        // Hapus Kolom di setiap baris body
        const rows = document.querySelectorAll('#bodyRows tr');
        rows.forEach(row => {
            if (row.children[index]) {
                row.children[index].remove();
            }
        });
    }

    function addRow() {
        const tbody = document.getElementById('bodyRows');
        const rowCount = tbody.children.length; // Untuk index array values
        const newRow = document.createElement('tr');
        
        // Hitung berapa kolom custom yang sedang aktif
        // Total kolom header - 2 (No & Item) - 1 (Aksi)
        const headerCells = document.getElementById('headerRow').children.length;
        const customCols = headerCells - 3; 

        let customInputs = '';
        for (let i = 0; i < customCols; i++) {
            customInputs += `<td><input type="text" name="values[${rowCount}][]" class="form-control form-control-sm text-center" placeholder="-"></td>`;
        }

        newRow.innerHTML = `
            <td class="text-center bg-light">${rowCount + 1}</td>
            <td><input type="text" name="items[]" class="form-control form-control-sm" placeholder="Nama Barang..." required></td>
            ${customInputs}
            <td class="text-center"><button type="button" class="btn btn-sm btn-danger py-0 px-2" onclick="removeRow(this)">x</button></td>
        `;
        tbody.appendChild(newRow);
    }

    function removeRow(btn) {
        const row = btn.closest('tr');
        const tbody = document.getElementById('bodyRows');
        if (tbody.children.length > 1) {
            row.remove();
            // Re-numbering row index for arrays is tricky, 
            // but PHP handles index auto-reorder on non-assoc arrays mostly fine 
            // or we just re-render numbers for visual
            Array.from(tbody.children).forEach((tr, idx) => {
                tr.firstElementChild.innerText = idx + 1;
                // Fix name attributes index if strictly needed
                const inputs = tr.querySelectorAll('input[name^="values"]');
                inputs.forEach(inp => {
                    inp.name = inp.name.replace(/values\[\d+\]/, `values[${idx}]`);
                });
            });
        } else {
            alert("Minimal satu baris harus ada.");
        }
    }
</script>

</body>
</html>