<?php
    session_start();
    $activePage = 'supplier.php';
    include '../app/config.php';

    if (! isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    if (! isset($_SESSION['user_id']) || ! in_array($_SESSION['user_role'], ['admin', 'petugas'])) {
        header("Location: dashboard.php");
        exit;
    }

    // --- PROSES TAMBAH ---
    if (isset($_POST['tambah'])) {
        $nama_supplier = trim($_POST['nama_supplier']);
        $alamat        = trim($_POST['alamat']);
        if ($nama_supplier != "" && $alamat != "") {
            $stmt = $conn->prepare("INSERT INTO supplier (nama_supplier, alamat) VALUES (?, ?)");
            $stmt->bind_param("ss", $nama_supplier, $alamat);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Supplier berhasil ditambahkan.";
            }
        }
        header("Location: supplier.php");
        exit;
    }

    // --- PROSES EDIT ---
    if (isset($_POST['edit'])) {
        $id            = $_POST['id'];
        $nama_supplier = trim($_POST['nama_supplier']);
        $alamat        = trim($_POST['alamat']);
        $stmt          = $conn->prepare("UPDATE supplier SET nama_supplier=?, alamat=? WHERE id=?");
        $stmt->bind_param("ssi", $nama_supplier, $alamat, $id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Supplier berhasil diupdate.";
        }
        header("Location: supplier.php");
        exit;
    }

    // --- PROSES HAPUS ---
    if (isset($_GET['hapus'])) {
        $id    = $_GET['hapus'];
        $force = isset($_GET['force']) ? $_GET['force'] : 0;

        // Cek apakah supplier masih digunakan di tabel pembelian dan barang_masuk
        $stmt_check_pembelian = $conn->prepare("SELECT COUNT(*) as jumlah FROM pembelian WHERE id_supplier = ?");
        $stmt_check_pembelian->bind_param("i", $id);
        $stmt_check_pembelian->execute();
        $result_pembelian = $stmt_check_pembelian->get_result();
        $row_pembelian    = $result_pembelian->fetch_assoc();

        $stmt_check_barang_masuk = $conn->prepare("SELECT COUNT(*) as jumlah FROM barang_masuk WHERE id_supplier = ?");
        $stmt_check_barang_masuk->bind_param("i", $id);
        $stmt_check_barang_masuk->execute();
        $result_barang_masuk = $stmt_check_barang_masuk->get_result();
        $row_barang_masuk    = $result_barang_masuk->fetch_assoc();

        $total_relasi = $row_pembelian['jumlah'] + $row_barang_masuk['jumlah'];

        if ($total_relasi > 0 && $force != 1) {
            // Jika masih ada relasi yang menggunakan supplier ini
            $error_details = [];
            if ($row_pembelian['jumlah'] > 0) {
                $error_details[] = $row_pembelian['jumlah'] . " data pembelian";
            }
            if ($row_barang_masuk['jumlah'] > 0) {
                $error_details[] = $row_barang_masuk['jumlah'] . " data barang masuk";
            }

            $_SESSION['error_message'] = "Supplier tidak dapat dihapus karena masih digunakan oleh " . implode(" dan ", $error_details) . ". Silakan hapus atau ubah supplier pada data tersebut terlebih dahulu.";
            $_SESSION['error_detail']  = [
                'id'           => $id,
                'pembelian'    => $row_pembelian['jumlah'],
                'barang_masuk' => $row_barang_masuk['jumlah'],
                'total'        => $total_relasi,
            ];
        } else {
            // Jika tidak ada relasi yang menggunakan supplier ini, atau force delete
            try {
                $stmt = $conn->prepare("DELETE FROM supplier WHERE id=?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    if ($force == 1) {
                        $_SESSION['success_message'] = "Supplier berhasil dihapus (dipaksa hapus meskipun ada relasi).";
                    } else {
                        $_SESSION['success_message'] = "Supplier berhasil dihapus.";
                    }
                } else {
                    $_SESSION['error_message'] = "Gagal menghapus supplier.";
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Error: " . $e->getMessage();
            }
        }

        header("Location: supplier.php");
        exit;
    }

    // --- AMBIL DATA SUPPLIER UNTUK DITAMPILKAN DI TABLE ---
    $supplierList = $conn->query("SELECT s.*,
                                 (SELECT COUNT(*) FROM pembelian WHERE id_supplier = s.id) as jumlah_pembelian,
                                 (SELECT COUNT(*) FROM barang_masuk WHERE id_supplier = s.id) as jumlah_barang_masuk
                                 FROM supplier s ORDER BY s.id DESC");

    include '../public/templates/header.php';
    include '../public/templates/sidebar.php';
    include '../public/templates/navbar.php';
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-2 text-gray-800">Master Supplier</h1>
    <p class="mb-4">Daftar data supplier. Tambahkan, edit, atau hapus supplier sesuai kebutuhan.</p>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong><i class="fas fa-exclamation-triangle"></i> Error!</strong>
            <?php echo $_SESSION['error_message']; ?>

            <?php if (isset($_SESSION['error_detail'])): ?>
                <hr>
                <div class="mt-2">
                    <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="showForceDeleteModal(<?php echo $_SESSION['error_detail']['id']; ?>,<?php echo $_SESSION['error_detail']['pembelian']; ?>,<?php echo $_SESSION['error_detail']['barang_masuk']; ?>)">
                        <i class="fas fa-trash"></i> Paksa Hapus
                    </button>
                    <small class="text-muted ml-2">Perhatian: Memaksa hapus dapat menyebabkan masalah data!</small>
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
            <strong><i class="fas fa-check-circle"></i> Berhasil!</strong>
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

    <!-- Tambah Supplier Modal Trigger -->
    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#modalTambahSupplier">
        <i class="fas fa-plus"></i> Tambah Supplier
    </button>

    <!-- DataTable Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Data Supplier</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="60">No</th>
                            <th>Nama Supplier</th>
                            <th>Alamat</th>
                            <th width="150">Relasi Data</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                            foreach ($supplierList as $row):
                                $total_relasi = $row['jumlah_pembelian'] + $row['jumlah_barang_masuk'];
                            ?>
			                            <tr>
			                                <td><?php echo $no++ ?></td>
			                                <td><?php echo htmlspecialchars($row['nama_supplier']) ?></td>
			                                <td><?php echo htmlspecialchars($row['alamat']) ?></td>
			                                <td>
			                                    <?php if ($total_relasi > 0): ?>
			                                        <div>
			                                            <?php if ($row['jumlah_pembelian'] > 0): ?>
			                                                <span class="badge badge-info"><?php echo $row['jumlah_pembelian']; ?> pembelian</span><br>
			                                            <?php endif; ?>
<?php if ($row['jumlah_barang_masuk'] > 0): ?>
                                                <span class="badge badge-warning"><?php echo $row['jumlah_barang_masuk']; ?> barang masuk</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Tidak ada relasi</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <!-- Tombol Edit -->
                                    <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalEditSupplier<?php echo $row['id'] ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>

                                    <!-- Tombol Hapus -->
                                    <?php if ($total_relasi > 0): ?>
                                        <button type="button" class="btn btn-danger btn-sm"
                                                onclick="showWarningModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['nama_supplier']); ?>',<?php echo $row['jumlah_pembelian']; ?>,<?php echo $row['jumlah_barang_masuk']; ?>)">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-danger btn-sm"
                                                onclick="showNormalDeleteModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['nama_supplier']); ?>')">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <!-- Modal Edit per-row -->
                            <div class="modal fade" id="modalEditSupplier<?php echo $row['id'] ?>" tabindex="-1" role="dialog">
                                <div class="modal-dialog" role="document">
                                    <form method="post">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Supplier</h5>
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="id" value="<?php echo $row['id'] ?>">
                                                <div class="form-group">
                                                    <label>Nama Supplier</label>
                                                    <input type="text" name="nama_supplier" class="form-control" required value="<?php echo htmlspecialchars($row['nama_supplier']) ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Alamat</label>
                                                    <input type="text" name="alamat" class="form-control" required value="<?php echo htmlspecialchars($row['alamat']) ?>">
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

    <!-- Modal Tambah Supplier -->
    <div class="modal fade" id="modalTambahSupplier" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form method="post">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus"></i> Tambah Supplier</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Supplier</label>
                            <input type="text" name="nama_supplier" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Alamat</label>
                            <input type="text" name="alamat" class="form-control" required>
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
                        <i class="fas fa-exclamation-triangle"></i> Peringatan: Data Berelasi
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
            '<p>Apakah Anda yakin ingin menghapus supplier <strong>' + nama + '</strong>?</p>' +
            '<small class="text-muted">Supplier ini tidak digunakan pada data pembelian atau barang masuk.</small>' +
            '</div>'
        );
        $('#modalHapusNormal').modal('show');
    }

    // Fungsi untuk menampilkan modal peringatan (ada relasi)
    function showWarningModal(id, nama, jumlahPembelian, jumlahBarangMasuk) {
        deleteId = id;
        var relasiText = [];
        if (jumlahPembelian > 0) {
            relasiText.push('<strong>' + jumlahPembelian + '</strong> data pembelian');
        }
        if (jumlahBarangMasuk > 0) {
            relasiText.push('<strong>' + jumlahBarangMasuk + '</strong> data barang masuk');
        }

        $('#modalPeningatanBody').html(
            '<div class="alert alert-warning">' +
            '<i class="fas fa-exclamation-triangle"></i> ' +
            '<strong>Perhatian!</strong> Supplier <strong>' + nama + '</strong> masih digunakan oleh ' + relasiText.join(' dan ') + '.' +
            '</div>' +
            '<div class="alert alert-info">' +
            '<h6><i class="fas fa-info-circle"></i> Saran:</h6>' +
            '<ul class="mb-0">' +
            '<li>Ubah supplier pada data pembelian dan barang masuk tersebut terlebih dahulu</li>' +
            '<li>Atau hapus data pembelian dan barang masuk yang menggunakan supplier ini</li>' +
            '</ul>' +
            '</div>' +
            '<div class="alert alert-danger">' +
            '<h6><i class="fas fa-exclamation-triangle"></i> Peringatan:</h6>' +
            '<p class="mb-0">Memaksa hapus dapat menyebabkan:</p>' +
            '<ul class="mb-0">' +
            '<li>Error pada sistem</li>' +
            '<li>Data pembelian dan barang masuk menjadi tidak konsisten</li>' +
            '<li>Masalah pada laporan</li>' +
            '</ul>' +
            '</div>' +
            '<p class="text-center font-weight-bold text-danger">Apakah Anda yakin ingin tetap menghapus?</p>'
        );
        $('#btnPaksaHapus').data('id', id);
        $('#modalPeningatanRelasi').modal('show');
    }

    // Fungsi untuk menampilkan modal force delete dari alert
    function showForceDeleteModal(id, jumlahPembelian, jumlahBarangMasuk) {
        deleteId = id;
        var relasiText = [];
        if (jumlahPembelian > 0) {
            relasiText.push('<strong>' + jumlahPembelian + '</strong> data pembelian');
        }
        if (jumlahBarangMasuk > 0) {
            relasiText.push('<strong>' + jumlahBarangMasuk + '</strong> data barang masuk');
        }

        $('#modalForceDeleteBody').html(
            '<div class="alert alert-danger">' +
            '<i class="fas fa-exclamation-triangle"></i> ' +
            '<strong>PERINGATAN KERAS!</strong>' +
            '</div>' +
            '<p>Anda akan memaksa menghapus supplier yang masih digunakan oleh ' + relasiText.join(' dan ') + '.</p>' +
            '<div class="alert alert-warning">' +
            '<small><strong>Konsekuensi:</strong> Tindakan ini dapat menyebabkan error sistem dan data menjadi tidak konsisten.</small>' +
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
                window.location.href = 'supplier.php?hapus=' + deleteId;
            }
        });

        // Event handler untuk paksa hapus dari modal peringatan
        $('#btnPaksaHapus').on('click', function() {
            var id = $(this).data('id');
            if (id > 0) {
                window.location.href = 'supplier.php?hapus=' + id + '&force=1';
            }
        });

        // Event handler untuk konfirmasi force delete
        $('#btnKonfirmasiForceDelete').on('click', function() {
            var id = $(this).data('id');
            if (id > 0) {
                window.location.href = 'supplier.php?hapus=' + id + '&force=1';
            }
        });

        // Auto hide alerts after 8 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 8000);
    });
</script>