<?php
    session_start();
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
            $stmt->execute();
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
        $stmt->execute();
        header("Location: supplier.php");
        exit;
    }

    // --- PROSES HAPUS ---
    if (isset($_GET['hapus'])) {
        $id   = $_GET['hapus'];
        $stmt = $conn->prepare("DELETE FROM supplier WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: supplier.php");
        exit;
    }

    // --- AMBIL DATA SUPPLIER UNTUK DITAMPILKAN DI TABLE ---
    $supplierList = $conn->query("SELECT * FROM supplier ORDER BY id DESC");

    include '../public/templates/header.php';
    include '../public/templates/sidebar.php';
    include '../public/templates/navbar.php';
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-2 text-gray-800">Master Supplier</h1>
    <p class="mb-4">Daftar data supplier. Tambahkan, edit, atau hapus supplier sesuai kebutuhan.</p>

    <!-- Tambah Supplier Modal Trigger -->
    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#modalTambahSupplier">Tambah Supplier</button>

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
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        foreach ($supplierList as $row): ?>
                            <tr>
                                <td><?php echo $no++ ?></td>
                                <td><?php echo htmlspecialchars($row['nama_supplier']) ?></td>
                                <td><?php echo htmlspecialchars($row['alamat']) ?></td>
                                <td>
                                    <!-- Tombol Edit: Modal trigger -->
                                    <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalEditSupplier<?php echo $row['id'] ?>">Edit</button>
                                    <button type="button"
                                            class="btn btn-danger btn-sm btn-hapus"
                                            data-id="<?php echo $row['id']; ?>"
                                            data-nama="<?php echo htmlspecialchars($row['nama_supplier']); ?>">
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                            <!-- Modal Edit per-row -->
                            <div class="modal fade" id="modalEditSupplier<?php echo $row['id'] ?>" tabindex="-1" role="dialog">
                                <div class="modal-dialog" role="document">
                                    <form method="post">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Supplier</h5>
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
                                                <button type="submit" name="edit" class="btn btn-primary">Simpan</button>
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
                        <h5 class="modal-title">Tambah Supplier</h5>
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
                        <button type="submit" name="tambah" class="btn btn-success">Tambah</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Hapus -->
    <div class="modal fade" id="modalHapusSupplier" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="modalHapusBody">
                    Apakah Anda yakin ingin menghapus data ini?
                </div>
                <div class="modal-footer">
                    <input type="hidden" id="hapusId" value="">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="btnKonfirmasiHapus">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<?php include '../public/templates/footer.php'; ?>

<script>
    $(document).ready(function() {
        $('.btn-hapus').on('click', function() {
            var id = $(this).data('id');
            var nama = $(this).data('nama');
            $('#hapusId').val(id); // simpan ID untuk digunakan nanti
            $('#modalHapusBody').html('Apakah Anda yakin ingin menghapus supplier <b>' + nama + '</b>?');
            $('#modalHapusSupplier').modal('show');
        });

        // Ketika tombol Ya, Hapus diklik
        $('#btnKonfirmasiHapus').on('click', function() {
            var id = $('#hapusId').val();
            window.location.href = 'supplier.php?hapus=' + id;
        });
    });
</script>
