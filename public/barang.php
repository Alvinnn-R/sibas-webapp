<?php
    session_start();
    $activePage = 'barang.php';
    include '../app/config.php';

    // Hanya admin yang boleh akses halaman ini
    if (! isset($_SESSION['user_id']) || ! in_array($_SESSION['user_role'], ['admin', 'petugas'])) {
        header("Location: dashboard.php");
        exit;
    }

    // --- FUNCTION: Generate kode barang ---
    function generateKodeBarang($conn)
    {
        $result = $conn->query("SELECT kode_barang FROM barang ORDER BY id DESC LIMIT 1");
        if ($row = $result->fetch_assoc()) {
            $num = intval(substr($row['kode_barang'], 1)) + 1;
            return 'B' . str_pad($num, 3, '0', STR_PAD_LEFT);
        } else {
            return 'B001';
        }
    }

    // --- AMBIL DATA SATUAN & JENIS ---
    $satuanList = $conn->query("SELECT * FROM satuan ORDER BY nama_satuan ASC");
    $jenisList  = $conn->query("SELECT * FROM jenis_barang ORDER BY nama_jenis ASC");

    // --- PROSES TAMBAH ---
    if (isset($_POST['tambah'])) {
        $kode_barang = trim($_POST['kode_barang']);
        $nama_barang = trim($_POST['nama_barang']);
        $id_satuan   = intval($_POST['id_satuan']);
        $id_jenis    = intval($_POST['id_jenis']);
        $harga_jual  = intval($_POST['harga_jual']);
        $harga_beli  = intval($_POST['harga_beli']);
        $stok        = intval($_POST['stok']);
        $status      = isset($_POST['status']) ? $_POST['status'] : '0';

        $stmt = $conn->prepare("INSERT INTO barang (kode_barang, nama_barang, id_satuan, id_jenis, harga_jual, harga_beli, stok, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiiiiss", $kode_barang, $nama_barang, $id_satuan, $id_jenis, $harga_jual, $harga_beli, $stok, $status);
        $stmt->execute();
        $_SESSION['success_message'] = "Barang berhasil ditambahkan.";
        header("Location: barang.php");
        exit;
    }

    // --- PROSES EDIT ---
    if (isset($_POST['edit'])) {
        $id          = intval($_POST['id']);
        $nama_barang = trim($_POST['nama_barang']);
        $id_satuan   = intval($_POST['id_satuan']);
        $id_jenis    = intval($_POST['id_jenis']);
        $harga_jual  = intval($_POST['harga_jual']);
        $harga_beli  = intval($_POST['harga_beli']);
        $stok        = intval($_POST['stok']);
        $status      = isset($_POST['status']) ? $_POST['status'] : '0';

        $stmt = $conn->prepare("UPDATE barang SET nama_barang=?, id_satuan=?, id_jenis=?, harga_jual=?, harga_beli=?, stok=?, status=? WHERE id=?");
        $stmt->bind_param("siiiiisi", $nama_barang, $id_satuan, $id_jenis, $harga_jual, $harga_beli, $stok, $status, $id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Barang berhasil diupdate.";
        }
        header("Location: barang.php");
        exit;
    }

    // --- PROSES HAPUS ---
    if (isset($_GET['hapus'])) {
        $id    = intval($_GET['hapus']);
        $force = isset($_GET['force']) ? $_GET['force'] : 0;

        // Cek apakah barang masih digunakan di tabel-tabel terkait
        $total_relasi  = 0;
        $detail_relasi = [];

        // Cek di tabel barang_masuk
        $stmt_check = $conn->prepare("SELECT COUNT(*) as jumlah FROM barang_masuk WHERE id_barang = ?");
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        $row    = $result->fetch_assoc();
        if ($row['jumlah'] > 0) {
            $total_relasi += $row['jumlah'];
            $detail_relasi[] = $row['jumlah'] . " data barang masuk";
        }

        // Cek di tabel barang_keluar
        $stmt_check = $conn->prepare("SELECT COUNT(*) as jumlah FROM barang_keluar WHERE id_barang = ?");
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        $row    = $result->fetch_assoc();
        if ($row['jumlah'] > 0) {
            $total_relasi += $row['jumlah'];
            $detail_relasi[] = $row['jumlah'] . " data barang keluar";
        }

        // Cek di tabel detail_pembelian
        $stmt_check = $conn->prepare("SELECT COUNT(*) as jumlah FROM detail_pembelian WHERE id_barang = ?");
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        $row    = $result->fetch_assoc();
        if ($row['jumlah'] > 0) {
            $total_relasi += $row['jumlah'];
            $detail_relasi[] = $row['jumlah'] . " data detail pembelian";
        }

        // Cek di tabel detail_penjualan
        $stmt_check = $conn->prepare("SELECT COUNT(*) as jumlah FROM detail_penjualan WHERE id_barang = ?");
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        $row    = $result->fetch_assoc();
        if ($row['jumlah'] > 0) {
            $total_relasi += $row['jumlah'];
            $detail_relasi[] = $row['jumlah'] . " data detail penjualan";
        }

        if ($total_relasi > 0 && $force != 1) {
            // Jika masih ada data yang menggunakan barang ini
            $_SESSION['error_message'] = "Barang tidak dapat dihapus karena masih digunakan di " . implode(", ", $detail_relasi) . ". Silakan hapus atau ubah data terkait terlebih dahulu.";
            $_SESSION['error_detail']  = ['id' => $id, 'count' => $total_relasi, 'relations' => $detail_relasi];
        } else {
            // Jika tidak ada data yang menggunakan barang ini, atau force delete
            try {
                if ($force == 1) {
                    // Hapus paksa - hapus semua data terkait terlebih dahulu
                    $conn->prepare("DELETE FROM barang_masuk WHERE id_barang = ?")->execute([$id]);
                    $conn->prepare("DELETE FROM barang_keluar WHERE id_barang = ?")->execute([$id]);
                    $conn->prepare("DELETE FROM detail_pembelian WHERE id_barang = ?")->execute([$id]);
                    $conn->prepare("DELETE FROM detail_penjualan WHERE id_barang = ?")->execute([$id]);
                }

                $stmt = $conn->prepare("DELETE FROM barang WHERE id=?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    if ($force == 1) {
                        $_SESSION['success_message'] = "Barang berhasil dihapus (dipaksa hapus beserta semua data terkait).";
                    } else {
                        $_SESSION['success_message'] = "Barang berhasil dihapus.";
                    }
                } else {
                    $_SESSION['error_message'] = "Gagal menghapus barang.";
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Error: " . $e->getMessage();
            }
        }

        header("Location: barang.php");
        exit;
    }

    // --- AMBIL DATA BARANG ---
    $barangList = $conn->query(
        "SELECT barang.*, satuan.nama_satuan, jenis_barang.nama_jenis,
        (SELECT COUNT(*) FROM barang_masuk WHERE id_barang = barang.id) +
        (SELECT COUNT(*) FROM barang_keluar WHERE id_barang = barang.id) +
        (SELECT COUNT(*) FROM detail_pembelian WHERE id_barang = barang.id) +
        (SELECT COUNT(*) FROM detail_penjualan WHERE id_barang = barang.id) as total_transaksi
        FROM barang
        JOIN satuan ON barang.id_satuan = satuan.id
        JOIN jenis_barang ON barang.id_jenis = jenis_barang.id
        ORDER BY barang.id DESC"
    );

    // Include template
    include '../public/templates/header.php';
    include '../public/templates/sidebar.php';
    include '../public/templates/navbar.php';
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-2 text-gray-800">Master Barang</h1>
    <p class="mb-4">Daftar data barang. Tambahkan, edit, atau hapus barang sesuai kebutuhan.</p>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong><i class="fas fa-exclamation-triangle"></i> Error!</strong>                                                                                                                                                               <?php echo $_SESSION['error_message']; ?>

            <?php if (isset($_SESSION['error_detail'])): ?>
                <hr>
                <div class="mt-2">
                    <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="showForceDeleteModal(<?php echo $_SESSION['error_detail']['id']; ?>,<?php echo $_SESSION['error_detail']['count']; ?>, '<?php echo implode(", ", $_SESSION['error_detail']['relations']); ?>')">
                        <i class="fas fa-trash"></i> Paksa Hapus
                    </button>
                    <small class="text-muted ml-2">Perhatian: Memaksa hapus akan menghapus SEMUA data terkait!</small>
                </div>
                <?php unset($_SESSION['error_detail']); ?>
<?php endif; ?>

            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong><i class="fas fa-check-circle"></i> Berhasil!</strong>                                                                                                                                                     <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

    <!-- Tambah Barang Modal Trigger -->
    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#modalTambahBarang">
        <i class="fas fa-plus"></i> Tambah Barang
    </button>

    <!-- DataTable Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Data Barang</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="60">No</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Jenis</th>
                            <th>Harga Jual</th>
                            <th>Harga Beli</th>
                            <th>Stok</th>
                            <th>Satuan</th>
                            <th>Status</th>
                            <th width="100">Transaksi</th>
                            <th width="160">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;foreach ($barangList as $row): ?>
                        <tr>
                            <td><?php echo $no++ ?></td>
                            <td><?php echo htmlspecialchars($row['kode_barang']) ?></td>
                            <td><?php echo htmlspecialchars($row['nama_barang']) ?></td>
                            <td><?php echo htmlspecialchars($row['nama_jenis']) ?></td>
                            <td><?php echo number_format($row['harga_jual']) ?></td>
                            <td><?php echo number_format($row['harga_beli']) ?></td>
                            <td><?php echo $row['stok'] ?></td>
                            <td><?php echo htmlspecialchars($row['nama_satuan']) ?></td>
                            <td>
                                <span class="badge badge-<?php echo $row['status'] == '1' ? 'success' : 'secondary' ?>">
                                    <?php echo $row['status'] == '1' ? 'Aktif' : 'Nonaktif' ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['total_transaksi'] > 0): ?>
                                    <span class="badge badge-info"><?php echo $row['total_transaksi']; ?> transaksi</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">0 transaksi</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <!-- Tombol Edit -->
                                <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalEditBarang<?php echo $row['id'] ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>

                                <!-- Tombol Hapus -->
                                <?php if ($row['total_transaksi'] > 0): ?>
                                    <button type="button" class="btn btn-danger btn-sm"
                                            onclick="showWarningModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['nama_barang']); ?>',<?php echo $row['total_transaksi']; ?>)">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-danger btn-sm"
                                            onclick="showNormalDeleteModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['nama_barang']); ?>')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Modal Edit Barang -->
                        <div class="modal fade" id="modalEditBarang<?php echo $row['id'] ?>" tabindex="-1" role="dialog">
                            <div class="modal-dialog" role="document">
                                <form method="post">
                                    <input type="hidden" name="id" value="<?php echo $row['id'] ?>">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Barang</h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label>Kode Barang</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['kode_barang']) ?>" readonly>
                                            </div>
                                            <div class="form-group">
                                                <label>Nama Barang</label>
                                                <input type="text" name="nama_barang" class="form-control" required value="<?php echo htmlspecialchars($row['nama_barang']) ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Satuan</label>
                                                <select name="id_satuan" class="form-control" required>
                                                    <?php
                                                        $satuanOpt = $conn->query("SELECT * FROM satuan ORDER BY nama_satuan ASC");
                                                    while ($sat = $satuanOpt->fetch_assoc()): ?>
                                                        <option value="<?php echo $sat['id'] ?>"<?php echo $row['id_satuan'] == $sat['id'] ? 'selected' : '' ?>><?php echo htmlspecialchars($sat['nama_satuan']) ?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Jenis</label>
                                                <select name="id_jenis" class="form-control" required>
                                                    <?php
                                                        $jenisOpt = $conn->query("SELECT * FROM jenis_barang ORDER BY nama_jenis ASC");
                                                    while ($jen = $jenisOpt->fetch_assoc()): ?>
                                                        <option value="<?php echo $jen['id'] ?>"<?php echo $row['id_jenis'] == $jen['id'] ? 'selected' : '' ?>><?php echo htmlspecialchars($jen['nama_jenis']) ?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Harga Jual</label>
                                                <input type="number" name="harga_jual" class="form-control" required value="<?php echo $row['harga_jual'] ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Harga Beli</label>
                                                <input type="number" name="harga_beli" class="form-control" required value="<?php echo $row['harga_beli'] ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Stok</label>
                                                <input type="number" name="stok" class="form-control" required value="<?php echo $row['stok'] ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select name="status" class="form-control">
                                                    <option value="1"                                                                                                                                           <?php echo $row['status'] == '1' ? 'selected' : '' ?>>Aktif</option>
                                                    <option value="0"                                                                                                                                           <?php echo $row['status'] == '0' ? 'selected' : '' ?>>Nonaktif</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" name="edit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Simpan
                                            </button>
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Tambah -->
    <div class="modal fade" id="modalTambahBarang" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form method="post">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus"></i> Tambah Barang</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <?php $autoKode = generateKodeBarang($conn); ?>
                        <div class="form-group">
                            <label>Kode Barang</label>
                            <input type="text" name="kode_barang" class="form-control" value="<?php echo $autoKode ?>" readonly required>
                        </div>
                        <div class="form-group">
                            <label>Nama Barang</label>
                            <input type="text" name="nama_barang" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Satuan</label>
                            <select name="id_satuan" class="form-control" required>
                                <option value="">Pilih Satuan</option>
                                <?php foreach ($satuanList as $sat): ?>
                                    <option value="<?php echo $sat['id'] ?>"><?php echo htmlspecialchars($sat['nama_satuan']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Jenis</label>
                            <select name="id_jenis" class="form-control" required>
                                <option value="">Pilih Jenis</option>
                                <?php foreach ($jenisList as $jen): ?>
                                    <option value="<?php echo $jen['id'] ?>"><?php echo htmlspecialchars($jen['nama_jenis']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Harga Jual</label>
                            <input type="number" name="harga_jual" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Harga Beli</label>
                            <input type="number" name="harga_beli" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Stok</label>
                            <input type="number" name="stok" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="tambah" class="btn btn-success">
                            <i class="fas fa-plus"></i> Tambah
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Hapus Normal -->
    <div class="modal fade" id="modalHapusNormal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-trash"></i> Konfirmasi Hapus</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="modalHapusNormalBody">
                    <!-- Konten akan diisi JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="btnKonfirmasiHapusNormal">
                        <i class="fas fa-trash"></i> Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Peringatan Relasi -->
    <div class="modal fade" id="modalPeningatanRelasi" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-exclamation-triangle"></i> Peringatan: Barang Memiliki Transaksi
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="modalPeningatanBody">
                    <!-- Konten akan diisi JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="button" class="btn btn-danger" id="btnPaksaHapus">
                        <i class="fas fa-exclamation-triangle"></i> Tetap Hapus (Berbahaya!)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Force Delete -->
    <div class="modal fade" id="modalForceDelete" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content border-danger">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-exclamation-triangle"></i> Konfirmasi Paksa Hapus
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="modalForceDeleteBody">
                    <!-- Konten akan diisi JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="btnKonfirmasiForceDelete">
                        <i class="fas fa-trash"></i> Ya, Paksa Hapus!
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<?php include '../public/templates/footer.php'; ?>

<script>
    // Variabel global untuk menyimpan ID yang akan dihapus
    var deleteId = 0;

    // Fungsi untuk menampilkan modal hapus normal (tidak ada relasi)
    function showNormalDeleteModal(id, nama) {
        deleteId = id;
        $('#modalHapusNormalBody').html(
            '<div class="text-center">' +
            '<i class="fas fa-question-circle fa-3x text-warning mb-3"></i>' +
            '<p>Apakah Anda yakin ingin menghapus barang <strong>' + nama + '</strong>?</p>' +
            '<small class="text-muted">Barang ini tidak memiliki riwayat transaksi.</small>' +
            '</div>'
        );
        $('#modalHapusNormal').modal('show');
    }

    // Fungsi untuk menampilkan modal peringatan (ada relasi)
    function showWarningModal(id, nama, jumlah) {
        deleteId = id;
        $('#modalPeningatanBody').html(
            '<div class="alert alert-warning">' +
            '<i class="fas fa-exclamation-triangle"></i> ' +
            '<strong>Perhatian!</strong> Barang <strong>' + nama + '</strong> memiliki <strong>' + jumlah + '</strong> riwayat transaksi.' +
            '</div>' +
            '<div class="alert alert-info">' +
            '<h6><i class="fas fa-info-circle"></i> Saran:</h6>' +
            '<ul class="mb-0">' +
            '<li>Hapus riwayat transaksi yang terkait terlebih dahulu</li>' +
            '<li>Atau nonaktifkan status barang saja</li>' +
            '</ul>' +
            '</div>' +
            '<div class="alert alert-danger">' +
            '<h6><i class="fas fa-exclamation-triangle"></i> Peringatan:</h6>' +
            '<p class="mb-0">Menghapus paksa dapat menyebabkan:</p>' +
            '<ul class="mb-0">' +
            '<li>Hilangnya data riwayat transaksi</li>' +
            '<li>Error pada laporan keuangan</li>' +
            '<li>Ketidakkonsistenan data stok</li>' +
            '</ul>' +
            '</div>' +
            '<p class="text-center font-weight-bold text-danger">Apakah Anda yakin ingin tetap menghapus?</p>'
        );
        $('#btnPaksaHapus').data('id', id);
        $('#modalPeningatanRelasi').modal('show');
    }

    // Fungsi untuk menampilkan modal force delete dari alert
    function showForceDeleteModal(id, jumlah, detail) {
        deleteId = id;
        $('#modalForceDeleteBody').html(
            '<div class="alert alert-danger">' +
            '<i class="fas fa-exclamation-triangle"></i> ' +
            '<strong>PERINGATAN KERAS!</strong>' +
            '</div>' +
            '<p>Anda akan memaksa menghapus barang yang masih memiliki <strong>' + jumlah + '</strong> riwayat transaksi:</p>' +
            '<div class="alert alert-info">' +
            '<small><strong>Detail:</strong> ' + detail + '</small>' +
            '</div>' +
            '<div class="alert alert-warning">' +
            '<small><strong>Konsekuensi:</strong> Semua data transaksi terkait akan dihapus dan tidak dapat dikembalikan!</small>' +
            '</div>' +
            '<p class="text-center"><strong>Apakah Anda benar-benar yakin?</strong></p>'
        );
        $('#btnKonfirmasiForceDelete').data('id', id);
        $('#modalForceDelete').modal('show');
    }

    $(document).ready(function() {
        // Event handler untuk konfirmasi hapus normal
        $('#btnKonfirmasiHapusNormal').on('click', function() {
            if (deleteId > 0) {
                window.location.href = 'barang.php?hapus=' + deleteId;
            }
        });

        // Event handler untuk paksa hapus dari modal peringatan
        $('#btnPaksaHapus').on('click', function() {
            var id = $(this).data('id');
            if (id > 0) {
                window.location.href = 'barang.php?hapus=' + id + '&force=1';
            }
        });

        // Event handler untuk konfirmasi force delete
        $('#btnKonfirmasiForceDelete').on('click', function() {
            var id = $(this).data('id');
            if (id > 0) {
                window.location.href = 'barang.php?hapus=' + id + '&force=1';
            }
        });

        // Auto hide alerts after 8 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 8000);
    });
</script>

<?php
    // Tutup koneksi database
$conn->close();
?>