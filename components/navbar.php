<style>
    /* CSS Khusus untuk Navbar Estetik */
    .navbar {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
    }

    .navbar-brand {
        letter-spacing: -0.5px;
    }

    .nav-link {
        color: #6c757d !important;
        font-weight: 500;
        transition: all 0.3s ease;
        position: relative;
        /* Penting buat posisi garis */
    }

    .nav-link:hover,
    .nav-link.active {
        color: #0d6efd !important;
        transform: translateY(-1px);
    }

    /* PERBAIKAN: Ganti ::after jadi ::before biar gak bentrok sama panah dropdown */
    .nav-link::before {
        content: '';
        position: absolute;
        width: 0;
        height: 2px;
        bottom: 0px;
        /* Posisi garis di paling bawah */
        left: 0;
        background-color: #0d6efd;
        transition: width 0.3s ease-in-out;
    }

    .nav-link:hover::before {
        width: 100%;
    }

    /* Dropdown Menu Cantik */
    .dropdown-menu {
        border: none;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        margin-top: 15px;
        animation: fadeIn 0.3s ease;
    }

    .dropdown-item {
        padding: 10px 20px;
        font-size: 0.9rem;
        border-radius: 8px;
        margin: 0 5px;
        width: auto;
    }

    .dropdown-item:hover {
        background-color: #f0f7ff;
        color: #0d6efd;
    }

    .btn-logout {
        border-radius: 50px;
        padding: 8px 24px;
        border: 1px solid #fee2e2;
        background-color: #fff1f2;
        color: #e11d48;
        transition: all 0.3s;
    }

    .btn-logout:hover {
        background-color: #e11d48;
        color: white;
        border-color: #e11d48;
        box-shadow: 0 4px 12px rgba(225, 29, 72, 0.2);
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<nav class="navbar navbar-expand-lg navbar-light shadow-sm py-10px sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold fs-4 text-primary" href="/projek_coba/index.php">
            <a class="navbar-brand fw-bold fs-4 text-primary" href="/projek_coba/index.php">
                <img src="/projek_coba/awabprint.jpeg" alt="Print" style="height: auto; width: 200px;">
            </a>
            <!-- <i class="bi me-2 bg-primary text-white p-2 rounded-3 fs-6"></i> Printing -->
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse"
            data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto gap-lg-4 align-items-center mt-3 mt-lg-0">

                <li class="nav-item">
                    <a class="nav-link" href="/projek_coba/index.php">Dashboard</a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        Laporan
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="/projek_coba/modules/laporan/order.php?tampil=proses">
                                <i class="bi bi-clock text-warning me-2"></i>Order Belum Diambil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/projek_coba/modules/laporan/order.php?tampil=utang">
                                <i class="bi bi-wallet2 text-danger me-2"></i>Tagihan Belum Lunas
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item" href="/projek_coba/modules/laporan/keuangan.php">
                                <i class="bi bi-graph-up-arrow text-success me-2"></i>Rekap Keuangan
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="/projek_coba/modules/pelanggan/index.php">Pelanggan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/projek_coba/modules/produk/index.php">Produk</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/projek_coba/modules/bank/index.php">Pembayaran</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="/projek_coba/modules/transaksi/form_surat_jalan_custom.php">
                        <i class="bi me-1"></i>Surat Jalan
                    </a>
                </li>
                <!-- 
                <li class="nav-item">
                    <a class="nav-link" href="/projek_coba/modules/transaksi/keranjang.php">
                        <i class="bi bi-basket me-1"></i> Keranjang Order
                    </a>
                </li> -->

                <li class="nav-item ms-lg-2">
                    <a class="btn btn-logout fw-bold text-decoration-none small" href="/projek_coba/auth/logout.php">
                        Logout <i class="bi bi-box-arrow-right ms-1"></i>
                    </a>
                </li>

            </ul>
        </div>
    </div>
</nav>