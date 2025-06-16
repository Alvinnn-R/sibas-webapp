<?php
    session_start();
    $activePage = 'barang_keluar.php';
    include '../app/config.php';

    if (! isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    if (! isset($_SESSION['user_id']) || ! in_array($_SESSION['user_role'], ['admin', 'petugas'])) {
        header("Location: dashboard.php");
        exit;
    }

    // Ambil data untuk dropdown
    $barangList = $conn->query("SELECT * FROM barang ORDER BY nama_barang ASC");

    // --- PROSES TAMBAH ---
    $error = '';
    if (isset($_POST['tambah'])) {
        $tanggal    = $_POST['tanggal'];
        $id_barang  = $_POST['id_barang'];
        $jumlah     = (int) $_POST['jumlah'];
        $tujuan     = trim($_POST['tujuan']);
        $keterangan = trim($_POST['keterangan']);

        // CEK STOK SAAT INI
        $stmt = $conn->prepare("SELECT stok, nama_barang FROM barang WHERE id=?");
        $stmt->bind_param("i", $id_barang);
        $stmt->execute();
        $result = $stmt->get_result();
        $barang = $result->fetch_assoc();

        if ($barang && $jumlah > $barang['stok']) {
            // Stok tidak cukup!
            $error = "Stok barang <b>{$barang['nama_barang']}</b> hanya tersedia <b>{$barang['stok']}</b>. Tidak dapat mengeluarkan <b>{$jumlah}</b>.";
        } else {
            // Lanjut proses insert jika stok cukup
            $stmt = $conn->prepare("INSERT INTO barang_keluar (tanggal, id_barang, jumlah, tujuan, keterangan) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("siiss", $tanggal, $id_barang, $jumlah, $tujuan, $keterangan);
            $stmt->execute();

            header("Location: barang_keluar.php");
            exit;
        }
    }

    // --- PROSES EDIT ---
    if (isset($_POST['edit'])) {
        $id          = $_POST['id'];
        $tanggal     = $_POST['tanggal'];
        $id_barang   = $_POST['id_barang'];
        $jumlah_baru = (int) $_POST['jumlah'];
        $tujuan      = trim($_POST['tujuan']);
        $keterangan  = trim($_POST['keterangan']);

        // Ambil jumlah lama & id_barang lama
        $stmt = $conn->prepare("SELECT jumlah, id_barang FROM barang_keluar WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result    = $stmt->get_result();
        $data_lama = $result->fetch_assoc();

        if ($data_lama) {
            $jumlah_lama    = (int) $data_lama['jumlah'];
            $id_barang_lama = $data_lama['id_barang'];
        } else {
            $jumlah_lama    = 0;
            $id_barang_lama = $id_barang;
        }

        // Jika barang yang di-edit sama dengan barang lama
        if ($id_barang == $id_barang_lama) {
            // Ambil stok saat ini
            $stmt = $conn->prepare("SELECT stok, nama_barang FROM barang WHERE id=?");
            $stmt->bind_param("i", $id_barang);
            $stmt->execute();
            $result = $stmt->get_result();
            $barang = $result->fetch_assoc();

            $stok_efektif = $barang['stok'] + $jumlah_lama;

            if ($jumlah_baru > $stok_efektif) {
                $error = "Stok barang <b>{$barang['nama_barang']}</b> hanya tersedia <b>{$stok_efektif}</b>. Tidak dapat mengeluarkan <b>{$jumlah_baru}</b>.";
            } else {
                $stmt = $conn->prepare("UPDATE barang_keluar SET tanggal=?, id_barang=?, jumlah=?, tujuan=?, keterangan=? WHERE id=?");
                $stmt->bind_param("siissi", $tanggal, $id_barang, $jumlah_baru, $tujuan, $keterangan, $id);
                $stmt->execute();

                header("Location: barang_keluar.php");
                exit;
            }
        } else {
            // Jika barang diubah, cek stok barang baru!
            $stmt = $conn->prepare("SELECT stok, nama_barang FROM barang WHERE id=?");
            $stmt->bind_param("i", $id_barang);
            $stmt->execute();
            $result = $stmt->get_result();
            $barang = $result->fetch_assoc();

            if ($jumlah_baru > $barang['stok']) {
                $error = "Stok barang <b>{$barang['nama_barang']}</b> hanya tersedia <b>{$barang['stok']}</b>. Tidak dapat mengeluarkan <b>{$jumlah_baru}</b>.";
            } else {
                $stmt = $conn->prepare("UPDATE barang_keluar SET tanggal=?, id_barang=?, jumlah=?, tujuan=?, keterangan=? WHERE id=?");
                $stmt->bind_param("siissi", $tanggal, $id_barang, $jumlah_baru, $tujuan, $keterangan, $id);
                $stmt->execute();

                header("Location: barang_keluar.php");
                exit;
            }
        }
    }

    // --- PROSES HAPUS ---
    if (isset($_GET['hapus'])) {
        $id   = $_GET['hapus'];
        $stmt = $conn->prepare("DELETE FROM barang_keluar WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        header("Location: barang_keluar.php");
        exit;
    }

    // --- AMBIL DATA BARANG KELUAR UNTUK TABEL ---
    $dataList = $conn->query(
        "SELECT bk.*, b.nama_barang
     FROM barang_keluar bk
     LEFT JOIN barang b ON bk.id_barang = b.id
     ORDER BY bk.id DESC"
    );

    include '../public/templates/header.php';
    include '../public/templates/sidebar.php';
    include '../public/templates/navbar.php';
?>

<!-- Begin Page Content -->
<div class="container-fluid">

<!-- Alert error stok minus -->

    <h1 class="h3 mb-2 text-gray-800">Transaksi Barang Keluar</h1>
    <p class="mb-4">Catatan transaksi barang keluar, stok akan otomatis berkurang.</p>

    <?php if (! empty($error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $error; ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

    <!-- Tambah Modal Trigger -->
    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#modalTambah">Tambah Barang Keluar</button>

    <!-- DataTable -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Barang Keluar</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="60">No</th>
                            <th>Tanggal</th>
                            <th>Nama Barang</th>
                            <th>Jumlah</th>
                            <th>Tujuan</th>
                            <th>Keterangan</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;foreach ($dataList as $row): ?>
                        <tr>
                            <td><?php echo $no++ ?></td>
                            <td><?php echo htmlspecialchars($row['tanggal']) ?></td>
                            <td><?php echo htmlspecialchars($row['nama_barang']) ?></td>
                            <td><?php echo htmlspecialchars($row['jumlah']) ?></td>
                            <td><?php echo htmlspecialchars($row['tujuan']) ?></td>
                            <td><?php echo htmlspecialchars($row['keterangan']) ?></td>
                            <td>
                                <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalEdit<?php echo $row['id'] ?>">Edit</button>
                                <button type="button"
                                    class="btn btn-danger btn-sm btn-hapus"
                                    data-id="<?php echo $row['id'] ?>"
                                    data-nama="<?php echo htmlspecialchars($row['nama_barang']) ?>">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                        <!-- Modal Edit per-row -->
                        <div class="modal fade" id="modalEdit<?php echo $row['id'] ?>" tabindex="-1" role="dialog">
                            <div class="modal-dialog" role="document">
                              <form method="post">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Barang Keluar</h5>
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="id" value="<?php echo $row['id'] ?>">
                                        <div class="form-group">
                                            <label>Tanggal</label>
                                            <input type="date" name="tanggal" class="form-control" required value="<?php echo htmlspecialchars($row['tanggal']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Nama Barang</label>
                                            <select name="id_barang" class="form-control" required>
                                                <option value="">-- Pilih Barang --</option>
                                                <?php foreach ($barangList as $barang): ?>
                                                    <option value="<?php echo $barang['id'] ?>"<?php echo $barang['id'] == $row['id_barang'] ? 'selected' : '' ?>>
                                                        <?php echo htmlspecialchars($barang['nama_barang']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Jumlah</label>
                                            <input type="number" name="jumlah" class="form-control" required value="<?php echo htmlspecialchars($row['jumlah']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Tujuan</label>
                                            <input type="text" name="tujuan" class="form-control" required value="<?php echo htmlspecialchars($row['tujuan']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Keterangan</label>
                                            <textarea name="keterangan" class="form-control"><?php echo htmlspecialchars($row['keterangan']) ?></textarea>
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
    <div class="modal fade" id="modalTambah" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
          <form method="post">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Barang Keluar</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Nama Barang</label>
                        <select name="id_barang" class="form-control" required>
                            <option value="">-- Pilih Barang --</option>
                            <?php foreach ($barangList as $barang): ?>
                                <option value="<?php echo $barang['id'] ?>"><?php echo htmlspecialchars($barang['nama_barang']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Jumlah</label>
                        <input type="number" name="jumlah" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Tujuan</label>
                        <input type="text" name="tujuan" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="keterangan" class="form-control"></textarea>
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
    <div class="modal fade" id="modalHapusBarangKeluar" tabindex="-1" role="dialog">
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

<?php include '../public/templates/footer.php'; ?>

<script>
$(document).ready(function() {
    $('.btn-hapus').on('click', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        $('#hapusId').val(id); // simpan ID untuk digunakan nanti
        $('#modalHapusBody').html('Apakah Anda yakin ingin menghapus data <b>' + nama + '</b>?');
        $('#modalHapusBarangKeluar').modal('show');
    });

    // Ketika tombol Ya, Hapus diklik
    $('#btnKonfirmasiHapus').on('click', function() {
        var id = $('#hapusId').val();
        window.location.href = 'barang_keluar.php?hapus=' + id;
    });
});
</script>
