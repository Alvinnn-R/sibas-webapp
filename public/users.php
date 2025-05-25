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
        $stmt->execute();
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
        $stmt->execute();
        header("Location: users.php");
        exit;
    }

    // --- PROSES HAPUS ---
    if (isset($_GET['hapus'])) {
        $id   = intval($_GET['hapus']);
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: users.php");
        exit;
    }

    // --- AMBIL DATA USER ---
    $userList = $conn->query("SELECT * FROM users ORDER BY id DESC");

    include '../public/templates/header.php';
    include '../public/templates/sidebar.php';
    include '../public/templates/navbar.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Data User</h1>
    <p class="mb-4">Admin bisa menambah, mengedit, atau menghapus user (admin, petugas, viewer).</p>

    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#modalTambahUser">Tambah User</button>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Tabel Users</h6>
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
                        <?php $no = 1;foreach ($userList as $row): ?>
                        <tr>
                            <td><?php echo $no++?></td>
                            <td>
                                <?php if ($row['profile']): ?>
                                    <img src="<?php echo $row['profile']?>" style="height:40px;width:40px;object-fit:cover;border-radius:50%;">
                                <?php else: ?>
                                    <img src="../vendor/img/undraw_profile.svg" style="height:40px;width:40px;object-fit:cover;border-radius:50%;">
                                <?php endif?>
                            </td>
                            <td><?php echo htmlspecialchars($row['nama_lengkap'])?></td>
                            <td><?php echo htmlspecialchars($row['username'])?></td>
                            <td>
                                <span class="badge badge-<?php echo $row['role'] == 'admin' ? 'success' : ($row['role'] == 'petugas' ? 'primary' : 'secondary')?>">
                                    <?php echo ucfirst($row['role'])?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalEditUser<?php echo $row['id']?>">Edit</button>
                                <button type="button" class="btn btn-danger btn-sm btn-hapus"
                                    data-id="<?php echo $row['id'];?>"
                                    data-nama="<?php echo htmlspecialchars($row['nama_lengkap']);?>">
                                    Hapus
                                </button>
                            </td>
                        </tr>

                        <!-- Modal Edit per-row -->
                        <div class="modal fade" id="modalEditUser<?php echo $row['id']?>" tabindex="-1" role="dialog">
                            <div class="modal-dialog" role="document">
                                <form method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="id" value="<?php echo $row['id']?>">
                                    <input type="hidden" name="profile_old" value="<?php echo htmlspecialchars($row['profile'])?>">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit User</h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group text-center">
                                                <img src="<?php echo $row['profile'] ? $row['profile'] : '../vendor/img/undraw_profile.svg'?>" style="height:70px;width:70px;object-fit:cover;border-radius:50%;">
                                            </div>
                                            <div class="form-group">
                                                <label>Foto Profil</label>
                                                <input type="file" name="profile" class="form-control">
                                            </div>
                                            <div class="form-group">
                                                <label>Nama Lengkap</label>
                                                <input type="text" name="nama_lengkap" class="form-control" required value="<?php echo htmlspecialchars($row['nama_lengkap'])?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Username</label>
                                                <input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($row['username'])?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Password (Kosongkan jika tidak diganti)</label>
                                                <input type="password" name="password" class="form-control">
                                            </div>
                                            <div class="form-group">
                                                <label>Role</label>
                                                <select name="role" class="form-control" required>
                                                    <option value="admin" <?php echo $row['role'] == 'admin' ? 'selected' : ''?>>Admin</option>
                                                    <option value="petugas" <?php echo $row['role'] == 'petugas' ? 'selected' : ''?>>Petugas</option>
                                                    <option value="viewer" <?php echo $row['role'] == 'viewer' ? 'selected' : ''?>>Viewer</option>
                                                </select>
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
                        <h5 class="modal-title">Tambah User</h5>
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
                        <button type="submit" name="tambah" class="btn btn-success">Tambah</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade" id="modalHapusUser" tabindex="-1" role="dialog">
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
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-hapus').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var nama = this.getAttribute('data-nama');
            document.getElementById('hapusId').value = id;
            document.getElementById('modalHapusBody').innerHTML = 'Apakah Anda yakin ingin menghapus user <b>' + nama + '</b>?';
            $('#modalHapusUser').modal('show'); // Pakai jQuery karena modal BS4
        });
    });
    document.getElementById('btnKonfirmasiHapus').addEventListener('click', function() {
        var id = document.getElementById('hapusId').value;
        window.location.href = 'users.php?hapus=' + id;
    });
});
</script>
