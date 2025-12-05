<nav class="navbar navbar-expand-lg navbar-dark shadow-sm py-3" style="background: linear-gradient(90deg, #0d6efd 0%, #0a58ca 100%);">
    <div class="container">
        <a class="navbar-brand fw-bold fs-4" href="index.php"><i class="bi bi-printer-fill me-2"></i> Zaddy Printing</a>
        <div class="navbar-nav ms-auto gap-3">
            
            <a class="nav-link fw-semibold" href="index.php">Dashboard</a>
            
            <li class="nav-item dropdown">
                <a class="nav-link fw-semibold dropdown-toggle" href="#" id="navbarDropdownLaporan" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Laporan
                </a>
                <ul class="dropdown-menu" aria-labelledby="navbarDropdownLaporan">
                    <li><a class="dropdown-item" href="laporan_order.php?tampil=proses"><i class="bi bi-clock-history me-2"></i> Order Belum Diambil</a></li>
                    <li><a class="dropdown-item" href="laporan_order.php?tampil=utang"><i class="bi bi-wallet-fill me-2"></i> Piutang Pelanggan</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="laporan_keuangan.php"><i class="bi bi-graph-up-arrow me-2"></i> Rekap Pendapatan</a></li>
                </ul>
            </li>
            
            <a class="nav-link fw-semibold" href="pelanggan.php">Pelanggan</a>
            <a class="nav-link fw-semibold" href="produk.php">Produk</a>
        </div>
    </div>
</nav>