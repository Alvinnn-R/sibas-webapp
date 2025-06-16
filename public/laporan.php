<?php
    session_start();
    $activePage = 'laporan.php';
    include '../app/config.php';

    if (! isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    include '../public/templates/header.php';
    include '../public/templates/sidebar.php';
    include '../public/templates/navbar.php';

    // --- Ambil data laporan Barang Masuk ---
    $qMasuk = "
    SELECT bm.*, b.nama_barang, s.nama_supplier
    FROM barang_masuk bm
    LEFT JOIN barang b ON bm.id_barang = b.id
    LEFT JOIN supplier s ON bm.id_supplier = s.id
    ORDER BY bm.tanggal DESC
";
    $dataMasuk = $conn->query($qMasuk);

    // --- Ambil data laporan Barang Keluar ---
    $qKeluar = "
    SELECT bk.*, b.nama_barang
    FROM barang_keluar bk
    LEFT JOIN barang b ON bk.id_barang = b.id
    ORDER BY bk.tanggal DESC
";
    $dataKeluar = $conn->query($qKeluar);

    // --- Ambil data stok barang ---
    $qStok = "
    SELECT b.*, s.nama_satuan, j.nama_jenis
    FROM barang b
    LEFT JOIN satuan s ON b.id_satuan = s.id
    LEFT JOIN jenis_barang j ON b.id_jenis = j.id
    ORDER BY b.nama_barang ASC
";
    $dataStok = $conn->query($qStok);

    // Filter Barang Masuk
    $whereMasuk = [];
    if (! empty($_GET['filter_barang'])) {
        $whereMasuk[] = "bm.id_barang=" . (int) $_GET['filter_barang'];
    }

    if (! empty($_GET['filter_supplier'])) {
        $whereMasuk[] = "bm.id_supplier=" . (int) $_GET['filter_supplier'];
    }

    if (! empty($_GET['tgl_dari'])) {
        $whereMasuk[] = "bm.tanggal >= '" . $conn->real_escape_string($_GET['tgl_dari']) . "'";
    }

    if (! empty($_GET['tgl_sampai'])) {
        $whereMasuk[] = "bm.tanggal <= '" . $conn->real_escape_string($_GET['tgl_sampai']) . "'";
    }

    $filterSqlMasuk = $whereMasuk ? "WHERE " . implode(" AND ", $whereMasuk) : "";
    $qMasuk         = "
    SELECT bm.*, b.nama_barang, s.nama_supplier
    FROM barang_masuk bm
    LEFT JOIN barang b ON bm.id_barang = b.id
    LEFT JOIN supplier s ON bm.id_supplier = s.id
    $filterSqlMasuk
    ORDER BY bm.tanggal DESC
";
    $dataMasuk = $conn->query($qMasuk);

    // Filter Barang Keluar
    $whereKeluar = [];
    if (! empty($_GET['filter_barang'])) {
        $whereKeluar[] = "bk.id_barang=" . (int) $_GET['filter_barang'];
    }

    if (! empty($_GET['filter_tujuan'])) {
        $whereKeluar[] = "bk.tujuan LIKE '%" . $conn->real_escape_string($_GET['filter_tujuan']) . "%'";
    }

    if (! empty($_GET['tgl_dari'])) {
        $whereKeluar[] = "bk.tanggal >= '" . $conn->real_escape_string($_GET['tgl_dari']) . "'";
    }

    if (! empty($_GET['tgl_sampai'])) {
        $whereKeluar[] = "bk.tanggal <= '" . $conn->real_escape_string($_GET['tgl_sampai']) . "'";
    }

    $filterSqlKeluar = $whereKeluar ? "WHERE " . implode(" AND ", $whereKeluar) : "";
    $qKeluar         = "
    SELECT bk.*, b.nama_barang
    FROM barang_keluar bk
    LEFT JOIN barang b ON bk.id_barang = b.id
    $filterSqlKeluar
    ORDER BY bk.tanggal DESC
";
    $dataKeluar = $conn->query($qKeluar);

    // Filter Barang (DIPERBAIKI)
    $whereBarang = [];
    if (! empty($_GET['filter_satuan'])) {
        $whereBarang[] = "b.id_satuan=" . (int) $_GET['filter_satuan'];
    }

    if (! empty($_GET['filter_jenis'])) {
        $whereBarang[] = "b.id_jenis=" . (int) $_GET['filter_jenis'];
    }

    $filterSqlBarang = $whereBarang ? "WHERE " . implode(" AND ", $whereBarang) : "";
    $qBarang         = "
    SELECT b.*, s.nama_satuan, j.nama_jenis
    FROM barang b
    LEFT JOIN satuan s ON b.id_satuan = s.id
    LEFT JOIN jenis_barang j ON b.id_jenis = j.id
    $filterSqlBarang
    ORDER BY b.nama_barang ASC
";
    $dataBarang = $conn->query($qBarang);

?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <h1 class="h3 mb-3 text-gray-800">Laporan Inventaris</h1>
    <p class="mb-4">Halaman laporan rekap transaksi & stok barang.</p>

    <!-- Nav tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link                               <?php if (! isset($_GET['tab']) || $_GET['tab'] == 'masuk') {
                                       echo 'active';
                               }
                               ?>" id="tab-masuk" data-toggle="tab" href="#masuk">Barang Masuk</a>
        </li>
        <li class="nav-item">
            <a class="nav-link                               <?php if (isset($_GET['tab']) && $_GET['tab'] == 'keluar') {
                                       echo 'active';
                               }
                               ?>" id="tab-keluar" data-toggle="tab" href="#keluar">Barang Keluar</a>
        </li>
        <li class="nav-item">
            <a class="nav-link                               <?php if (isset($_GET['tab']) && $_GET['tab'] == 'barang') {
                                       echo 'active';
                               }
                               ?>" id="tab-barang" data-toggle="tab" href="#barang">Stok Barang</a>
        </li>
    </ul>


    <div class="tab-content" id="laporanTabContent">
        <!-- TAB BARANG MASUK -->
        <div class="tab-pane fade                                  <?php if (! isset($_GET['tab']) || $_GET['tab'] == 'masuk') {
                                          echo 'show active';
                                  }
                                  ?>" id="masuk" role="tabpanel">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Laporan Barang Masuk</h6>
                    <form class="form-inline" method="get" action="laporan.php#masuk">
                        <!-- TAMBAHKAN HIDDEN INPUT UNTUK TAB -->
                        <input type="hidden" name="tab" value="masuk">

                        <label class="mr-2">Barang</label>
                        <select name="filter_barang" class="form-control form-control-sm mr-2">
                            <option value="">Semua</option>
                            <?php
                                // Ambil semua barang untuk dropdown
                                $barangOption = $conn->query("SELECT * FROM barang ORDER BY nama_barang");
                                while ($br = $barangOption->fetch_assoc()):
                            ?>
                                <option value="<?php echo $br['id'] ?>"<?php if (isset($_GET['filter_barang']) && $_GET['filter_barang'] == $br['id']) {
        echo 'selected';
}
?>>
                                    <?php echo htmlspecialchars($br['nama_barang']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <label class="mr-2">Supplier</label>
                        <select name="filter_supplier" class="form-control form-control-sm mr-2">
                            <option value="">Semua</option>
                            <?php
                                $suppOption = $conn->query("SELECT * FROM supplier ORDER BY nama_supplier");
                                while ($sp = $suppOption->fetch_assoc()):
                            ?>
                                <option value="<?php echo $sp['id'] ?>"<?php if (isset($_GET['filter_supplier']) && $_GET['filter_supplier'] == $sp['id']) {
        echo 'selected';
}
?>>
                                    <?php echo htmlspecialchars($sp['nama_supplier']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <label class="mr-2">Tanggal</label>
                        <input type="date" name="tgl_dari" class="form-control form-control-sm mr-1" value="<?php echo isset($_GET['tgl_dari']) ? $_GET['tgl_dari'] : '' ?>">
                        <span class="mx-1">-</span>
                        <input type="date" name="tgl_sampai" class="form-control form-control-sm mr-2" value="<?php echo isset($_GET['tgl_sampai']) ? $_GET['tgl_sampai'] : '' ?>">
                        <button class="btn btn-sm btn-primary mr-2" type="submit">Filter</button>
                        <a href="laporan.php?tab=masuk#masuk" class="btn btn-sm btn-secondary">Reset</a>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="tableMasuk" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th width="40">No</th>
                                    <th>Tanggal</th>
                                    <th>Nama Barang</th>
                                    <th>Jumlah</th>
                                    <th>Supplier</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $no = 1;
                                    foreach ($dataMasuk as $row):
                                ?>
                                    <tr>
                                        <td><?php echo $no++ ?></td>
                                        <td><?php echo htmlspecialchars($row['tanggal']) ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_barang']) ?></td>
                                        <td><?php echo htmlspecialchars($row['jumlah']) ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_supplier']) ?></td>
                                        <td><?php echo htmlspecialchars($row['keterangan']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB BARANG KELUAR -->
        <div class="tab-pane fade                                  <?php if (isset($_GET['tab']) && $_GET['tab'] == 'keluar') {
                                          echo 'show active';
                                  }
                                  ?>" id="keluar" role="tabpanel">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Laporan Barang Keluar</h6>
                    <form class="form-inline" method="get" action="laporan.php#keluar">
                        <!-- TAMBAHKAN HIDDEN INPUT UNTUK TAB -->
                        <input type="hidden" name="tab" value="keluar">

                        <label class="mr-2">Barang</label>
                        <select name="filter_barang" class="form-control form-control-sm mr-2">
                            <option value="">Semua</option>
                            <?php
                                // Ambil semua barang untuk dropdown
                                $barangOption = $conn->query("SELECT * FROM barang ORDER BY nama_barang");
                                while ($br = $barangOption->fetch_assoc()):
                            ?>
                                <option value="<?php echo $br['id'] ?>"<?php if (isset($_GET['filter_barang']) && $_GET['filter_barang'] == $br['id']) {
        echo 'selected';
}
?>>
                                    <?php echo htmlspecialchars($br['nama_barang']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <label class="mr-2">Tujuan</label>
                        <input type="text" name="filter_tujuan" class="form-control form-control-sm mr-2" value="<?php echo isset($_GET['filter_tujuan']) ? htmlspecialchars($_GET['filter_tujuan']) : '' ?>" placeholder="Tujuan">
                        <label class="mr-2">Tanggal</label>
                        <input type="date" name="tgl_dari" class="form-control form-control-sm mr-1" value="<?php echo isset($_GET['tgl_dari']) ? $_GET['tgl_dari'] : '' ?>">
                        <span class="mx-1">-</span>
                        <input type="date" name="tgl_sampai" class="form-control form-control-sm mr-2" value="<?php echo isset($_GET['tgl_sampai']) ? $_GET['tgl_sampai'] : '' ?>">
                        <button class="btn btn-sm btn-primary mr-2" type="submit">Filter</button>
                        <a href="laporan.php?tab=keluar#keluar" class="btn btn-sm btn-secondary">Reset</a>
                    </form>

                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="tableKeluar" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th width="40">No</th>
                                    <th>Tanggal</th>
                                    <th>Nama Barang</th>
                                    <th>Jumlah</th>
                                    <th>Tujuan</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $no = 1;
                                    foreach ($dataKeluar as $row):
                                ?>
                                    <tr>
                                        <td><?php echo $no++ ?></td>
                                        <td><?php echo htmlspecialchars($row['tanggal']) ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_barang']) ?></td>
                                        <td><?php echo htmlspecialchars($row['jumlah']) ?></td>
                                        <td><?php echo htmlspecialchars($row['tujuan']) ?></td>
                                        <td><?php echo htmlspecialchars($row['keterangan']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB STOK BARANG -->
        <div class="tab-pane fade                                  <?php if (isset($_GET['tab']) && $_GET['tab'] == 'barang') {
                                          echo 'show active';
                                  }
                                  ?>" id="barang" role="tabpanel">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Stok Barang Saat Ini</h6>
                    <form class="form-inline" method="get" action="laporan.php#barang">
                        <!-- TAMBAHKAN HIDDEN INPUT UNTUK TAB -->
                        <input type="hidden" name="tab" value="barang">

                        <label class="mr-2">Satuan</label>
                        <select name="filter_satuan" class="form-control form-control-sm mr-2">
                            <option value="">Semua</option>
                            <?php
                                $satuanOpt = $conn->query("SELECT * FROM satuan ORDER BY nama_satuan");
                                while ($st = $satuanOpt->fetch_assoc()):
                            ?>
                                <option value="<?php echo $st['id'] ?>"<?php if (isset($_GET['filter_satuan']) && $_GET['filter_satuan'] == $st['id']) {
        echo 'selected';
}
?>>
                                    <?php echo htmlspecialchars($st['nama_satuan']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <label class="mr-2">Jenis</label>
                        <select name="filter_jenis" class="form-control form-control-sm mr-2">
                            <option value="">Semua</option>
                            <?php
                                $jenisOpt = $conn->query("SELECT * FROM jenis_barang ORDER BY nama_jenis");
                                while ($jn = $jenisOpt->fetch_assoc()):
                            ?>
                                <option value="<?php echo $jn['id'] ?>"<?php if (isset($_GET['filter_jenis']) && $_GET['filter_jenis'] == $jn['id']) {
        echo 'selected';
}
?>>
                                    <?php echo htmlspecialchars($jn['nama_jenis']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <button class="btn btn-sm btn-primary mr-2" type="submit">Filter</button>
                        <a href="laporan.php?tab=barang#barang" class="btn btn-sm btn-secondary">Reset</a>
                    </form>

                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="tableStok" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th width="40">No</th>
                                    <th>Kode Barang</th>
                                    <th>Nama Barang</th>
                                    <th>Jenis</th>
                                    <th>Harga Beli</th>
                                    <th>Harga Jual</th>
                                    <th>Stok</th>
                                    <th>Satuan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1;
                                    // GUNAKAN $dataBarang BUKAN $dataStok
                                foreach ($dataBarang as $row): ?>
                                    <tr>
                                        <td><?php echo $no++ ?></td>
                                        <td><?php echo htmlspecialchars($row['kode_barang']) ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_barang']) ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_jenis']) ?></td>
                                        <td><?php echo number_format($row['harga_beli']) ?></td>
                                        <td><?php echo number_format($row['harga_jual']) ?></td>
                                        <td><?php echo htmlspecialchars($row['stok']) ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_satuan']) ?></td>
                                        <td>
                                            <?php if ($row['status'] == '1'): ?>
                                                <span class="badge badge-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Non-Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../public/templates/footer.php'; ?>

<script>
    $(document).ready(function() {
        $('#tableMasuk').DataTable();
        $('#tableKeluar').DataTable();
        $('#tableStok').DataTable();
    });
</script>

<script>
    $(document).ready(function() {
        // Cek hash pada url, lalu aktifkan tab yang sesuai
        var hash = window.location.hash;
        if (hash) {
            $('.nav-tabs a[href="' + hash + '"]').tab('show');
        }

        // Saat tab diklik, tambahkan hash ke URL (tanpa reload)
        $('.nav-tabs a').on('shown.bs.tab', function(e) {
            history.replaceState(null, null, e.target.hash);
        });
    });
</script>