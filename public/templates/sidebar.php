<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
                <div class="sidebar-brand-icon">
                    <img src="./assets/logo_sibas.png" alt="Logo SIBAS" class="img-fluid" style="height:65px; width:auto;">
                </div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item <?php echo ($activePage == 'dashboard.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            
            
            <!-- MENU KHUSUS ADMIN -->
            <?php if ($_SESSION['user_role'] == 'admin'): ?>
                <!-- Divider -->
                <hr class="sidebar-divider">
                <!-- Heading -->
                <div class="sidebar-heading">
                    Master Data
                </div>
                <li class="nav-item <?php echo ($activePage == 'users.php') ? 'active' : ''; ?>">
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-fw fa-users"></i>
                        <span>Data Pengguna</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- MENU ADMIN & PETUGAS -->
            <?php if (in_array($_SESSION['user_role'], ['admin', 'petugas'])): ?>
                <li class="nav-item <?php echo ($activePage == 'barang.php') ? 'active' : ''; ?>">
                    <a class="nav-link" href="barang.php">
                        <i class="fas fa-fw fa-box"></i>
                        <span>Data Barang</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activePage == 'satuan.php') ? 'active' : ''; ?>">
                    <a class="nav-link" href="satuan.php">
                        <i class="fas fa-fw fa-balance-scale"></i>
                        <span>Satuan Barang</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activePage == 'jenis_barang.php') ? 'active' : ''; ?>">
                    <a class="nav-link" href="jenis_barang.php">
                        <i class="fas fa-fw fa-list"></i>
                        <span>Jenis Barang</span>
                    </a>
                </li>
                <li class="nav-item <?php echo ($activePage == 'supplier.php') ? 'active' : ''; ?>">
                    <a class="nav-link" href="supplier.php">
                        <i class="fas fa-fw fa-truck"></i>
                        <span>Data Supplier</span>
                    </a>
                </li>
            <?php endif; ?>

            
            <!-- Transaksi Barang (Admin & Petugas) -->
            <?php if (in_array($_SESSION['user_role'], ['admin', 'petugas'])): ?>
                <!-- Divider -->
                <hr class="sidebar-divider">
    
                <!-- Heading -->
                <div class="sidebar-heading">
                    Transaksi
                </div>
            <li class="nav-item">
                <a class="nav-link collapsed <?php echo (isset($activePage) && ($activePage == 'barang_masuk.php' || $activePage == 'barang_keluar.php')) ? 'active' : ''; ?>" 
                    href="#" 
                    data-toggle="collapse" 
                    data-target="#collapseTransaksi"
                    aria-expanded="<?php echo (isset($activePage) && ($activePage == 'barang_masuk.php' || $activePage == 'barang_keluar.php')) ? 'true' : 'false'; ?>" 
                    aria-controls="collapseTransaksi">
                    <i class="fas fa-fw fa-exchange-alt"></i>
                    <span>Transaksi Barang</span>
                </a>
                <div id="collapseTransaksi" class="collapse <?php echo (isset($activePage) && ($activePage == 'barang_masuk.php' || $activePage == 'barang_keluar.php')) ? 'show' : ''; ?>" aria-labelledby="headingTransaksi" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo (isset($activePage) && $activePage == 'barang_masuk.php') ? 'active' : ''; ?>" href="barang_masuk.php">Barang Masuk</a>
                        <a class="collapse-item <?php echo (isset($activePage) && $activePage == 'barang_keluar.php') ? 'active' : ''; ?>" href="barang_keluar.php">Barang Keluar</a>
                    </div>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link collapsed <?php echo (isset($activePage) && ($activePage == 'penjualan.php' || $activePage == 'pembelian.php')) ? 'active' : ''; ?>"
                    href="#"
                    data-toggle="collapse"
                    data-target="#collapseKasir"
                    aria-expanded="<?php echo (isset($activePage) && ($activePage == 'penjualan.php' || $activePage == 'pembelian.php')) ? 'true' : 'false'; ?>"
                    aria-controls="collapseKasir">
                    <i class="fas fa-fw fa-cash-register"></i>
                    <span>Transaksi Kasir</span>
                </a>
                <div id="collapseKasir" class="collapse <?php echo (isset($activePage) && ($activePage == 'penjualan.php' || $activePage == 'pembelian.php')) ? 'show' : ''; ?>" aria-labelledby="headingKasir" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item <?php echo (isset($activePage) && $activePage == 'penjualan.php') ? 'active' : ''; ?>" href="penjualan.php">Penjualan</a>
                        <a class="collapse-item <?php echo (isset($activePage) && $activePage == 'pembelian.php') ? 'active' : ''; ?>" href="pembelian.php">Pembelian</a>
                    </div>
                </div>
            </li>
            <?php endif; ?>

            <!-- Viewer hanya dapat lihat laporan -->
            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Laporan
            </div>

            <li class="nav-item <?php echo ($activePage == 'laporan.php') ? 'active' : ''; ?>">
                <a class="nav-link" href="laporan.php">
                    <i class="fas fa-fw fa-file-alt"></i>
                    <span>Laporan</span>
                </a>
            </li>

            <!-- Log aktivitas hanya admin
            <?php if ($_SESSION['user_role'] == 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="log_aktivitas.php">
                        <i class="fas fa-fw fa-history"></i>
                        <span>Log Aktivitas</span>
                    </a>
                </li>
            <?php endif; ?> -->

            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

            <!-- Sidebar Message -->
            <div class="sidebar-card d-none d-lg-flex">
                <p class="text-center mb-2"><strong>SIBAS</strong> Sistem Inventaris Barang & Supplier</p>
                <a class="btn btn-success btn-sm" href="#">Learn More</a>
            </div>

        </ul>
        <!-- End of Sidebar -->