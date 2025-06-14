<?php
    session_start();
    include '../app/config.php';

    // Hanya admin yang boleh akses halaman ini
    if (! isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
        header("Location: dashboard.php");
        exit;
    }

    // --- PROSES TAMBAH ---
    if (isset($_POST['tambah'])) {
        $nama         = trim($_POST['nama_lengkap']);
        $username     = trim($_POST['username']);
        $role         = $_POST['role'];
        $password     = $_POST['password'];
        $profile_path = null;

        // Upload profile (optional)
        if (isset($_FILES['profile']) && $_FILES['profile']['error'] == 0) {
            $ext   = pathinfo($_FILES['profile']['name'], PATHINFO_EXTENSION);
            $fname = uniqid() . '.' . $ext;
            $dir   = 'uploads/profiles/';
            if (! is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            move_uploaded_file($_FILES['profile']['tmp_name'], $dir . $fname);
            $profile_path = $dir . $fname;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (profile, nama_lengkap, username, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $profile_path, $nama, $username, $hash, $role);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "User berhasil ditambahkan.";
        }
        header("Location: users.php");
        exit;
    }

    // --- PROSES EDIT ---
    if (isset($_POST['edit'])) {
        $id           = intval($_POST['id']);
        $nama         = trim($_POST['nama_lengkap']);
        $username     = trim($_POST['username']);
        $role         = $_POST['role'];
        $profile_path = $_POST['profile_old'];

        // Upload profile (optional)
        if (isset($_FILES['profile']) && $_FILES['profile']['error'] == 0) {
            $ext   = pathinfo($_FILES['profile']['name'], PATHINFO_EXTENSION);
            $fname = uniqid() . '.' . $ext;
            $dir   = 'uploads/profiles/';
            if (! is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            move_uploaded_file($_FILES['profile']['tmp_name'], $dir . $fname);
            $profile_path = $dir . $fname;
        }

        // Jika ganti password, update. Jika tidak, tetap lama
        if (! empty($_POST['password'])) {
            $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET profile=?, nama_lengkap=?, username=?, password=?, role=? WHERE id=?");
            $stmt->bind_param("sssssi", $profile_path, $nama, $username, $hash, $role, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET profile=?, nama_lengkap=?, username=?, role=? WHERE id=?");
            $stmt->bind_param("ssssi", $profile_path, $nama, $username, $role, $id);
        }
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "User berhasil diupdate.";
        }
        header("Location: users.php");
        exit;
    }

    // --- PROSES HAPUS ---
    if (isset($_GET['hapus'])) {
        $id    = intval($_GET['hapus']);
        $force = isset($_GET['force']) ? $_GET['force'] : 0;

        // Cek apakah user tidak menghapus diri sendiri
        if ($id == $_SESSION['user_id']) {
            $_SESSION['error_message'] = "Anda tidak dapat menghapus akun Anda sendiri.";
            header("Location: users.php");
            exit;
        }

        // Cek relasi dengan tabel penjualan, pembelian, dan log_aktivitas
        $total_relasi  = 0;
        $detail_relasi = [];

        // Cek penjualan
        $stmt_penjualan = $conn->prepare("SELECT COUNT(*) as jumlah FROM penjualan WHERE id_user = ?");
        $stmt_penjualan->bind_param("i", $id);
        $stmt_penjualan->execute();
        $result_penjualan = $stmt_penjualan->get_result();
        $penjualan_count  = $result_penjualan->fetch_assoc()['jumlah'];
        if ($penjualan_count > 0) {
            $total_relasi += $penjualan_count;
            $detail_relasi[] = $penjualan_count . " penjualan";
        }

        // Cek pembelian
        $stmt_pembelian = $conn->prepare("SELECT COUNT(*) as jumlah FROM pembelian WHERE id_user = ?");
        $stmt_pembelian->bind_param("i", $id);
        $stmt_pembelian->execute();
        $result_pembelian = $stmt_pembelian->get_result();
        $pembelian_count  = $result_pembelian->fetch_assoc()['jumlah'];
        if ($pembelian_count > 0) {
            $total_relasi += $pembelian_count;
            $detail_relasi[] = $pembelian_count . " pembelian";
        }

        // Cek log aktivitas
        $stmt_log = $conn->prepare("SELECT COUNT(*) as jumlah FROM log_aktivitas WHERE user_id = ?");
        $stmt_log->bind_param("i", $id);
        $stmt_log->execute();
        $result_log = $stmt_log->get_result();
        $log_count  = $result_log->fetch_assoc()['jumlah'];
        if ($log_count > 0) {
            $total_relasi += $log_count;
            $detail_relasi[] = $log_count . " log aktivitas";
        }

        if ($total_relasi > 0 && $force != 1) {
            // Jika masih ada relasi
            $detail_text               = implode(", ", $detail_relasi);
            $_SESSION['error_message'] = "User tidak dapat dihapus karena masih memiliki " . $detail_text . ". Silakan hapus atau transfer data tersebut terlebih dahulu.";
            $_SESSION['error_detail']  = ['id' => $id, 'count' => $total_relasi, 'detail' => $detail_text];
        } else {
            // Jika tidak ada relasi, atau force delete
            try {
                $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    if ($force == 1) {
                        $_SESSION['success_message'] = "User berhasil dihapus (dipaksa hapus meskipun ada relasi).";
                    } else {
                        $_SESSION['success_message'] = "User berhasil dihapus.";
                    }
                } else {
                    $_SESSION['error_message'] = "Gagal menghapus user.";
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Error: " . $e->getMessage();
            }
        }

        header("Location: users.php");
        exit;
    }

    // --- AMBIL DATA USER ---
    $userList = $conn->query("SELECT u.*,
                             (SELECT COUNT(*) FROM penjualan WHERE id_user = u.id) as jumlah_penjualan,
                             (SELECT COUNT(*) FROM pembelian WHERE id_user = u.id) as jumlah_pembelian,
                             (SELECT COUNT(*) FROM log_aktivitas WHERE user_id = u.id) as jumlah_log
                             FROM users as u ORDER BY u.id DESC");

    include '../public/templates/header.php';
    include '../public/templates/sidebar.php';
    include '../public/templates/navbar.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Data User</h1>
    <p class="mb-4">Admin bisa menambah, mengedit, atau menghapus user (admin, petugas, viewer).</p>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong><i class="fas fa-exclamation-triangle"></i> Error!</strong>
            <?php echo $_SESSION['error_message']; ?>

            <?php if (isset($_SESSION['error_detail'])): ?>
                <hr>
                <div class="mt-2">
                    <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="showForceDeleteModal(<?php echo $_SESSION['error_detail']['id']; ?>, '<?php echo $_SESSION['error_detail']['detail']; ?>')">
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

    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#modalTambahUser">
        <i class="fas fa-plus"></i> Tambah User
    </button>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Data Users</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="60">No</th>
                            <th>Foto</th>
                            <th>Nama Lengkap</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;foreach ($userList as $row):
                                $total_aktivitas                 = $row['jumlah_penjualan'] + $row['jumlah_pembelian'] + $row['jumlah_log'];
                            ?>
								                        <tr>
								                            <td><?php echo $no++ ?></td>
								                            <td>
								                                <?php if ($row['profile']): ?>
								                                    <img src="<?php echo $row['profile'] ?>" style="height:40px;width:40px;object-fit:cover;border-radius:50%;">
								                                <?php else: ?>
                                    <img src="../vendor/img/undraw_profile.svg" style="height:40px;width:40px;object-fit:cover;border-radius:50%;">
                                <?php endif?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['nama_lengkap']) ?>
<?php if ($row['id'] == $_SESSION['user_id']): ?>
                                    <small class="badge badge-info">Anda</small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['username']) ?></td>
                            <td>
                                <span class="badge badge-<?php echo $row['role'] == 'admin' ? 'success' : ($row['role'] == 'petugas' ? 'primary' : 'secondary') ?>">
                                    <?php echo ucfirst($row['role']) ?>
                                </span>
                            </td>
                            <td>
                                <!-- Tombol Edit -->
                                <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalEditUser<?php echo $row['id'] ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>

                                <!-- Tombol Hapus -->
                                <?php if ($row['id'] == $_SESSION['user_id']): ?>
                                    <!-- User tidak bisa hapus diri sendiri -->
                                    <button type="button" class="btn btn-secondary btn-sm" disabled title="Tidak bisa hapus diri sendiri">
                                        <i class="fas fa-ban"></i> Hapus
                                    </button>
                                <?php elseif ($total_aktivitas > 0): ?>
                                    <button type="button" class="btn btn-danger btn-sm"
                                            onclick="showWarningModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['nama_lengkap']); ?>',<?php echo $total_aktivitas; ?>)">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-danger btn-sm"
                                            onclick="showNormalDeleteModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['nama_lengkap']); ?>')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Modal Edit per-row -->
                        <div class="modal fade" id="modalEditUser<?php echo $row['id'] ?>" tabindex="-1" role="dialog">
                            <div class="modal-dialog" role="document">
                                <form method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="id" value="<?php echo $row['id'] ?>">
                                    <input type="hidden" name="profile_old" value="<?php echo htmlspecialchars($row['profile']) ?>">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><i class="fas fa-edit"></i> Edit User</h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group text-center">
                                                <img src="<?php echo $row['profile'] ? $row['profile'] : '../vendor/img/undraw_profile.svg' ?>" style="height:70px;width:70px;object-fit:cover;border-radius:50%;">
                                            </div>
                                            <div class="form-group">
                                                <label>Foto Profil</label>
                                                <input type="file" name="profile" class="form-control">
                                            </div>
                                            <div class="form-group">
                                                <label>Nama Lengkap</label>
                                                <input type="text" name="nama_lengkap" class="form-control" required value="<?php echo htmlspecialchars($row['nama_lengkap']) ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Username</label>
                                                <input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($row['username']) ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Password (Kosongkan jika tidak diganti)</label>
                                                <input type="password" name="password" class="form-control">
                                            </div>
                                            <div class="form-group">
                                                <label>Role</label>
                                                <select name="role" class="form-control" required>
                                                    <option value="admin"                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         <?php echo $row['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                                    <option value="petugas"                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         <?php echo $row['role'] == 'petugas' ? 'selected' : '' ?>>Petugas</option>
                                                    <option value="viewer"                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 <?php echo $row['role'] == 'viewer' ? 'selected' : '' ?>>Viewer</option>
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
                        <?php endforeach?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Tambah -->
    <div class="modal fade" id="modalTambahUser" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus"></i> Tambah User</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Foto Profil</label>
                            <input type="file" name="profile" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role" class="form-control" required>
                                <option value="admin">Admin</option>
                                <option value="petugas">Petugas</option>
                                <option value="viewer">Viewer</option>
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
            '<p>Apakah Anda yakin ingin menghapus user <strong>' + nama + '</strong>?</p>' +
            '<small class="text-muted">User ini tidak memiliki aktivitas apapun.</small>' +
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
            '<strong>Perhatian!</strong> User <strong>' + nama + '</strong> masih memiliki <strong>' + jumlah + '</strong> aktivitas (penjualan, pembelian, atau log).' +
            '</div>' +
            '<div class="alert alert-info">' +
            '<h6><i class="fas fa-info-circle"></i> Saran:</h6>' +
            '<ul class="mb-0">' +
            '<li>Transfer data penjualan dan pembelian ke user lain terlebih dahulu</li>' +
            '<li>Atau hapus data yang terkait dengan user ini</li>' +
            '</ul>' +
            '</div>' +
            '<div class="alert alert-danger">' +
            '<h6><i class="fas fa-exclamation-triangle"></i> Peringatan:</h6>' +
            '<p class="mb-0">Menghapus user dengan aktivitas dapat menyebabkan:</p>' +
            '<ul class="mb-0">' +
            '<li>Kehilangan data historis transaksi</li>' +
            '<li>Error pada sistem</li>' +
            '<li>Masalah pada laporan dan audit trail</li>' +
            '</ul>' +
            '</div>' +
            '<p class="text-center font-weight-bold text-danger">Apakah Anda yakin ingin tetap menghapus?</p>'
        );
        $('#btnPaksaHapus').data('id', id);
        $('#modalPeningatanRelasi').modal('show');
    }

    // Fungsi untuk menampilkan modal force delete dari alert
    function showForceDeleteModal(id, detail) {
        deleteId = id;
        $('#modalForceDeleteBody').html(
            '<div class="alert alert-danger">' +
            '<i class="fas fa-exclamation-triangle"></i> ' +
            '<strong>PERINGATAN KERAS!</strong>' +
            '</div>' +
            '<p>Anda akan memaksa menghapus user yang masih memiliki aktivitas: <strong>' + detail + '</strong>.</p>' +
            '<div class="alert alert-warning">' +
            '<small><strong>Konsekuensi:</strong> Tindakan ini dapat menyebabkan error sistem, kehilangan data historis, dan masalah integritas data.</small>' +
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
                window.location.href = 'users.php?hapus=' + deleteId;
            }
        });

        // Event handler untuk paksa hapus dari modal peringatan
        $('#btnPaksaHapus').on('click', function() {
            var id = $(this).data('id');
            if (id > 0) {
                window.location.href = 'users.php?hapus=' + id + '&force=1';
            }
        });

        // Event handler untuk konfirmasi force delete
        $('#btnKonfirmasiForceDelete').on('click', function() {
            var id = $(this).data('id');
            if (id > 0) {
                window.location.href = 'users.php?hapus=' + id + '&force=1';
            }
        });

        // Auto hide alerts after 8 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 8000);
    });
</script>