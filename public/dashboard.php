<?php
session_start();
$activePage = 'dashboard.php';
include '../app/config.php';
include '../public/templates/header.php';
include '../public/templates/sidebar.php';
include '../public/templates/navbar.php';

if (! isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Query Data Dashboard
// Jumlah barang
$jml_barang    = $conn->query("SELECT COUNT(*) FROM barang")->fetch_row()[0];
// Jumlah supplier
$jml_supplier  = $conn->query("SELECT COUNT(*) FROM supplier")->fetch_row()[0];
// Jumlah user (opsional, hanya admin)
$jml_user      = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];

// Pendapatan bulan ini (penjualan)
$now_month     = date('Y-m');
$sql_bulan     = "SELECT COALESCE(SUM(total),0) FROM penjualan WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$now_month'";
$pendapatan_bulan = $conn->query($sql_bulan)->fetch_row()[0];

// Pendapatan tahun ini (penjualan)
$now_year      = date('Y');
$sql_tahun     = "SELECT COALESCE(SUM(total),0) FROM penjualan WHERE YEAR(tanggal) = '$now_year'";
$pendapatan_tahun = $conn->query($sql_tahun)->fetch_row()[0];

// Jumlah pembelian bulan ini (untuk pie chart sumber pengeluaran toko)
$sql_pembelian_bulan = "SELECT COALESCE(SUM(total),0) FROM pembelian WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$now_month'";
$pembelian_bulan = $conn->query($sql_pembelian_bulan)->fetch_row()[0];

// Penjualan per bulan (12 bulan terakhir) untuk grafik area
$data_penjualan_bulanan = [];
for ($i = 11; $i >= 0; $i--) {
    $label_bulan = date('M Y', strtotime("-$i months"));
    $bulan_sql = date('Y-m', strtotime("-$i months"));
    $total     = $conn->query("SELECT COALESCE(SUM(total),0) FROM penjualan WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_sql'")->fetch_row()[0];
    $data_penjualan_bulanan[] = [
        'label' => $label_bulan,
        'total' => $total
    ];
}
?>
<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 text-gray-800">Dashboard</h1>
            <h2 class="mt-4 mb-1">Selamat Datang, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
        </div>
        <!-- Tombol Generate Report (Bisa ganti link/export) -->
        <a href="laporan.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-file-alt fa-sm text-white-50"></i> Lihat Laporan
        </a>
    </div>

    <!-- Content Row (Kartu Stat)-->
    <div class="row">
        <!-- Total Barang -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body d-flex align-items-center">
                    <div class="mr-3">
                        <i class="fas fa-box fa-2x text-gray-300"></i>
                    </div>
                    <div>
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Barang</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $jml_barang ?></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Total Supplier -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body d-flex align-items-center">
                    <div class="mr-3">
                        <i class="fas fa-truck fa-2x text-gray-300"></i>
                    </div>
                    <div>
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Supplier</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $jml_supplier ?></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Pendapatan Bulan Ini -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body d-flex align-items-center">
                    <div class="mr-3">
                        <i class="fas fa-cash-register fa-2x text-gray-300"></i>
                    </div>
                    <div>
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Pendapatan Bulan Ini</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Rp<?= number_format($pendapatan_bulan,0,',','.') ?></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Pendapatan Tahun Ini -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body d-flex align-items-center">
                    <div class="mr-3">
                        <i class="fas fa-calendar fa-2x text-gray-300"></i>
                    </div>
                    <div>
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Pendapatan Tahun Ini</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Rp<?= number_format($pendapatan_tahun,0,',','.') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row (Charts)-->
    <div class="row">
        <!-- Area Chart Penjualan Bulanan -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Grafik Penjualan 12 Bulan Terakhir</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="areaPenjualan"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <!-- Pie Chart (Perbandingan Penjualan vs Pembelian Bulan Ini) -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Perbandingan Bulan Ini</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="piePendapatan"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="mr-2"><i class="fas fa-circle text-success"></i> Penjualan</span>
                        <span class="mr-2"><i class="fas fa-circle text-danger"></i> Pembelian</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../public/templates/footer.php'; ?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
/* Area Chart Penjualan Bulanan */
const areaPenjualan = document.getElementById('areaPenjualan').getContext('2d');
const areaChart = new Chart(areaPenjualan, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($data_penjualan_bulanan, 'label')) ?>,
        datasets: [{
            label: 'Total Penjualan',
            data: <?= json_encode(array_column($data_penjualan_bulanan, 'total')) ?>,
            backgroundColor: 'rgba(78, 115, 223, 0.2)',
            borderColor: 'rgba(78, 115, 223, 1)',
            borderWidth: 2,
            pointRadius: 3,
            pointBackgroundColor: 'rgba(78, 115, 223, 1)',
            fill: true,
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});

/* Pie Chart: Penjualan vs Pembelian Bulan Ini */
const piePendapatan = document.getElementById('piePendapatan').getContext('2d');
const pieChart = new Chart(piePendapatan, {
    type: 'pie',
    data: {
        labels: ['Penjualan', 'Pembelian'],
        datasets: [{
            data: [
                <?= (int) $pendapatan_bulan ?>,
                <?= (int) $pembelian_bulan ?>
            ],
            backgroundColor: ['#1cc88a', '#e74a3b']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>
