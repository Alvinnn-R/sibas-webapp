<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="sidebar-brand-text mx-3">SIBAS <sup>v1</sup></div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item active">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Master Data
            </div>

            <!-- Nav Items Master Data (langsung, tanpa dropdown) -->
            <li class="nav-item">
                <a class="nav-link" href="users.php">
                    <i class="fas fa-fw fa-users"></i>
                    <span>Data Pengguna</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="barang.php">
                    <i class="fas fa-fw fa-box"></i>
                    <span>Data Barang</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="satuan.php">
                    <i class="fas fa-fw fa-balance-scale"></i>
                    <span>Satuan Barang</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="jenis_barang.php">
                    <i class="fas fa-fw fa-list"></i>
                    <span>Jenis Barang</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="supplier.php">
                    <i class="fas fa-fw fa-truck"></i>
                    <span>Data Supplier</span>
                </a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Transaksi
            </div>

            <!-- Nav Item - Transaksi Barang Collapse Menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTransaksi"
                    aria-expanded="true" aria-controls="collapseTransaksi">
                    <i class="fas fa-fw fa-exchange-alt"></i>
                    <span>Transaksi Barang</span>
                </a>
                <div id="collapseTransaksi" class="collapse" aria-labelledby="headingTransaksi" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item" href="barang_masuk.php">Barang Masuk</a>
                        <a class="collapse-item" href="barang_keluar.php">Barang Keluar</a>
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Laporan
            </div>

            <li class="nav-item">
                <a class="nav-link" href="laporan.php">
                    <i class="fas fa-fw fa-file-alt"></i>
                    <span>Laporan</span>
                </a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

            <!-- Sidebar Message -->
            <div class="sidebar-card d-none d-lg-flex">
                <!-- <img class="sidebar-card-illustration mb-2" src="img/undraw_rocket.svg" alt="Rocket Illustration"> -->
                <p class="text-center mb-2"><strong>SIBAS</strong> Sistem Inventaris Barang & Supplier</p>
                <a class="btn btn-success btn-sm" href="#">Learn More</a>
            </div>

        </ul>
        <!-- End of Sidebar -->
