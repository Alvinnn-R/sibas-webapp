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
        $nama_jenis = trim($_POST['nama_jenis']);
        if ($nama_jenis != "") {
            $stmt = $conn->prepare("INSERT INTO jenis_barang (nama_jenis) VALUES (?)");
            $stmt->bind_param("s", $nama_jenis);
            $stmt->execute();
        }
        header("Location: jenis_barang.php");
        exit;
    }

    // --- PROSES EDIT ---
    if (isset($_POST['edit'])) {
        $id         = $_POST['id'];
        $nama_jenis = trim($_POST['nama_jenis']);
        $stmt       = $conn->prepare("UPDATE jenis_barang SET nama_jenis=? WHERE id=?");
        $stmt->bind_param("si", $nama_jenis, $id);
        $stmt->execute();
        header("Location: jenis_barang.php");
        exit;
    }

    // --- PROSES HAPUS ---
    if (isset($_GET['hapus'])) {
        $id   = $_GET['hapus'];
        $stmt = $conn->prepare("DELETE FROM jenis_barang WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: jenis_barang.php");
        exit;
    }

    // --- AMBIL DATA JENIS UNTUK DITAMPILKAN DI TABLE ---
    $jenisList = $conn->query("SELECT * FROM jenis_barang ORDER BY id DESC");

    include '../public/templates/header.php';
    include '../public/templates/sidebar.php';
    include '../public/templates/navbar.php';
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-2 text-gray-800">Master Jenis Barang</h1>
    <p class="mb-4">Daftar data jenis barang. Tambahkan, edit, atau hapus jenis sesuai kebutuhan.</p>

    <!-- Tambah Jenis Modal Trigger -->
    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#modalTambahJenis">Tambah Jenis</button>

    <!-- DataTable Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Data Jenis Barang</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="60">No</th>
                            <th>Nama Jenis Barang</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        foreach ($jenisList as $row): ?>
                            <tr>
                                <td><?php echo $no++ ?></td>
                                <td><?php echo htmlspecialchars($row['nama_jenis']) ?></td>
                                <td>
                                    <!-- Tombol Edit: Modal trigger -->
                                    <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalEditJenis<?php echo $row['id'] ?>">Edit</button>
                                    <button type="button"
                                        class="btn btn-danger btn-sm btn-hapus"
                                        data-id="<?php echo $row['id']; ?>"
                                        data-nama="<?php echo htmlspecialchars($row['nama_jenis']); ?>">
                                        Hapus
                                    </button>


                                </td>
                            </tr>
                            <!-- Modal Edit per-row -->
                            <div class="modal fade" id="modalEditJenis<?php echo $row['id'] ?>" tabindex="-1" role="dialog">
                                <div class="modal-dialog" role="document">
                                    <form method="post">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Jenis Barang</h5>
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="id" value="<?php echo $row['id'] ?>">
                                                <div class="form-group">
                                                    <label>Nama Jenis Barang</label>
                                                    <input type="text" name="nama_jenis" class="form-control" required value="<?php echo htmlspecialchars($row['nama_jenis']) ?>">
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

    <!-- Modal Tambah -->
    <div class="modal fade" id="modalTambahJenis" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form method="post">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Jenis Barang</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Jenis Barang</label>
                            <input type="text" name="nama_jenis" class="form-control" required>
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
    <div class="modal fade" id="modalHapusJenis" tabindex="-1" role="dialog">
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
            $('#modalHapusBody').html('Apakah Anda yakin ingin menghapus data <b>' + nama + '</b>?');
            $('#modalHapusJenis').modal('show');
        });

        // Ketika tombol Ya, Hapus diklik
        $('#btnKonfirmasiHapus').on('click', function() {
            var id = $('#hapusId').val();
            window.location.href = 'jenis_barang.php?hapus=' + id;
        });
    });
</script>