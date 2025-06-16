<?php
    session_start();
    $activePage = 'satuan.php';

    include '../app/config.php';
    // include '../app/auth.php'; // fungsi cek login

    // include '../templates/footer.php';

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
        $nama_satuan = trim($_POST['nama_satuan']);
        if ($nama_satuan != "") {
            $stmt = $conn->prepare("INSERT INTO satuan (nama_satuan) VALUES (?)");
            $stmt->bind_param("s", $nama_satuan);
            $stmt->execute();
            $_SESSION['success_message'] = "Satuan berhasil ditambahkan.";
        }
        header("Location: satuan.php");
        exit;
    }

    // --- PROSES EDIT ---
    if (isset($_POST['edit'])) {
        $id          = $_POST['id'];
        $nama_satuan = trim($_POST['nama_satuan']);
        $stmt        = $conn->prepare("UPDATE satuan SET nama_satuan=? WHERE id=?");
        $stmt->bind_param("si", $nama_satuan, $id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Satuan berhasil diupdate.";
        }
        header("Location: satuan.php");
        exit;
    }

    // --- PROSES HAPUS ---
    if (isset($_GET['hapus'])) {
        $id    = $_GET['hapus'];
        $force = isset($_GET['force']) ? $_GET['force'] : 0;

        // Cek apakah satuan masih digunakan di tabel barang
        $stmt_check = $conn->prepare("SELECT COUNT(*) as jumlah FROM barang WHERE id_satuan = ?");
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        $row    = $result->fetch_assoc();

        if ($row['jumlah'] > 0 && $force != 1) {
            // Jika masih ada barang yang menggunakan satuan ini
            $_SESSION['error_message'] = "Satuan tidak dapat dihapus karena masih digunakan oleh " . $row['jumlah'] . " barang. Silakan hapus atau ubah satuan pada barang tersebut terlebih dahulu.";
            $_SESSION['error_detail']  = ['id' => $id, 'count' => $row['jumlah']];
        } else {
            // Jika tidak ada barang yang menggunakan satuan ini, atau force delete
            try {
                $stmt = $conn->prepare("DELETE FROM satuan WHERE id=?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    if ($force == 1) {
                        $_SESSION['success_message'] = "Satuan berhasil dihapus (dipaksa hapus meskipun ada relasi).";
                    } else {
                        $_SESSION['success_message'] = "Satuan berhasil dihapus.";
                    }
                } else {
                    $_SESSION['error_message'] = "Gagal menghapus satuan.";
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Error: " . $e->getMessage();
            }
        }

        header("Location: satuan.php");
        exit;
    }

    // --- AMBIL DATA SATUAN UNTUK DITAMPILKAN DI TABLE ---
    $satuanList = $conn->query("SELECT s.*,
                               (SELECT COUNT(*) FROM barang WHERE id_satuan = s.id) as jumlah_barang
                               FROM satuan s ORDER BY s.id DESC");

    include '../public/templates/header.php';
    include '../public/templates/sidebar.php';
    include '../public/templates/navbar.php';
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-2 text-gray-800">Master Satuan</h1>
    <p class="mb-4">Daftar data satuan barang. Tambahkan, edit, atau hapus satuan sesuai kebutuhan.</p>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong><i class="fas fa-exclamation-triangle"></i> Error!</strong>                                                                                                                                                                                                                                                                                                                             <?php echo $_SESSION['error_message']; ?>

            <?php if (isset($_SESSION['error_detail'])): ?>
                <hr>
                <div class="mt-2">
                    <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="showForceDeleteModal(<?php echo $_SESSION['error_detail']['id']; ?>,<?php echo $_SESSION['error_detail']['count']; ?>)">
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
            <strong><i class="fas fa-check-circle"></i> Berhasil!</strong>                                                                                                                                                                                                                                                                                                         <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

    <!-- Tambah Satuan Modal Trigger -->
    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#modalTambahSatuan">
        <i class="fas fa-plus"></i> Tambah Satuan
    </button>

    <!-- DataTable Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Data Satuan</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="60">No</th>
                            <th>Nama Satuan</th>
                            <th width="120">Jumlah Barang</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;foreach ($satuanList as $row): ?>
                        <tr>
                            <td><?php echo $no++ ?></td>
                            <td><?php echo htmlspecialchars($row['nama_satuan']) ?></td>
                            <td>
                                <?php if ($row['jumlah_barang'] > 0): ?>
                                    <span class="badge badge-info"><?php echo $row['jumlah_barang']; ?> barang</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">0 barang</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <!-- Tombol Edit -->
                                <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalEditSatuan<?php echo $row['id'] ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>

                                <!-- Tombol Hapus -->
                                <?php if ($row['jumlah_barang'] > 0): ?>
                                    <button type="button" class="btn btn-danger btn-sm"
                                            onclick="showWarningModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['nama_satuan']); ?>',<?php echo $row['jumlah_barang']; ?>)">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-danger btn-sm"
                                            onclick="showNormalDeleteModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['nama_satuan']); ?>')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Modal Edit per-row -->
                        <div class="modal fade" id="modalEditSatuan<?php echo $row['id'] ?>" tabindex="-1" role="dialog">
                            <div class="modal-dialog" role="document">
                              <form method="post">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Satuan</h5>
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="id" value="<?php echo $row['id'] ?>">
                                        <div class="form-group">
                                            <label>Nama Satuan</label>
                                            <input type="text" name="nama_satuan" class="form-control" required
                                                   value="<?php echo htmlspecialchars($row['nama_satuan']) ?>">
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
    <div class="modal fade" id="modalTambahSatuan" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
          <form method="post">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Tambah Satuan</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Satuan</label>
                        <input type="text" name="nama_satuan" class="form-control" required>
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
            '<p>Apakah Anda yakin ingin menghapus satuan <strong>' + nama + '</strong>?</p>' +
            '<small class="text-muted">Satuan ini tidak digunakan oleh barang manapun.</small>' +
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
            '<strong>Perhatian!</strong> Satuan <strong>' + nama + '</strong> masih digunakan oleh <strong>' + jumlah + '</strong> barang.' +
            '</div>' +
            '<div class="alert alert-info">' +
            '<h6><i class="fas fa-info-circle"></i> Saran:</h6>' +
            '<ul class="mb-0">' +
            '<li>Ubah satuan pada barang-barang tersebut terlebih dahulu</li>' +
            '<li>Atau hapus barang-barang yang menggunakan satuan ini</li>' +
            '</ul>' +
            '</div>' +
            '<div class="alert alert-danger">' +
            '<h6><i class="fas fa-exclamation-triangle"></i> Peringatan:</h6>' +
            '<p class="mb-0">Anda tidak bisa menghapus, karena hal ini dapat menyebabkan:</p>' +
            '<ul class="mb-0">' +
            '<li>Error pada sistem</li>' +
            '<li>Data barang menjadi tidak konsisten</li>' +
            '<li>Masalah pada laporan</li>' +
            '</ul>' +
            '</div>' +
            '<p class="text-center font-weight-bold text-danger">Apakah Anda yakin ingin tetap menghapus?</p>'
        );
        $('#btnPaksaHapus').data('id', id);
        $('#modalPeningatanRelasi').modal('show');
    }

    // Fungsi untuk menampilkan modal force delete dari alert
    function showForceDeleteModal(id, jumlah) {
        deleteId = id;
        $('#modalForceDeleteBody').html(
            '<div class="alert alert-danger">' +
            '<i class="fas fa-exclamation-triangle"></i> ' +
            '<strong>PERINGATAN KERAS!</strong>' +
            '</div>' +
            '<p>Anda akan memaksa menghapus satuan yang masih digunakan oleh <strong>' + jumlah + '</strong> barang.</p>' +
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
                window.location.href = 'satuan.php?hapus=' + deleteId;
            }
        });

        // Event handler untuk paksa hapus dari modal peringatan
        $('#btnPaksaHapus').on('click', function() {
            var id = $(this).data('id');
            if (id > 0) {
                window.location.href = 'satuan.php?hapus=' + id + '&force=1';
            }
        });

        // Event handler untuk konfirmasi force delete
        $('#btnKonfirmasiForceDelete').on('click', function() {
            var id = $(this).data('id');
            if (id > 0) {
                window.location.href = 'satuan.php?hapus=' + id + '&force=1';
            }
        });

        // Auto hide alerts after 8 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 8000);

    });
</script>