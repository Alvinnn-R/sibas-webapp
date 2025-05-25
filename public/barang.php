<?php
    session_start();
    include '../app/config.php';

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
        $stmt->execute();
        header("Location: barang.php");
        exit;
    }

    // --- PROSES HAPUS ---
    if (isset($_GET['hapus'])) {
        $id   = intval($_GET['hapus']);
        $stmt = $conn->prepare("DELETE FROM barang WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: barang.php");
        exit;
    }

    // --- AMBIL DATA BARANG ---
    $barangList = $conn->query(
        "SELECT barang.*, satuan.nama_satuan, jenis_barang.nama_jenis
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

    <!-- Tambah Barang Modal Trigger -->
    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#modalTambahBarang">Tambah Barang</button>

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
                            <th>Satuan</th>
                            <th>Jenis</th>
                            <th>Harga Jual</th>
                            <th>Harga Beli</th>
                            <th>Stok</th>
                            <th>Status</th>
                            <th width="160">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;foreach ($barangList as $row): ?>
                        <tr>
                            <td><?php echo $no++?></td>
                            <td><?php echo htmlspecialchars($row['kode_barang'])?></td>
                            <td><?php echo htmlspecialchars($row['nama_barang'])?></td>
                            <td><?php echo htmlspecialchars($row['nama_satuan'])?></td>
                            <td><?php echo htmlspecialchars($row['nama_jenis'])?></td>
                            <td><?php echo number_format($row['harga_jual'])?></td>
                            <td><?php echo number_format($row['harga_beli'])?></td>
                            <td><?php echo $row['stok']?></td>
                            <td>
                                <span class="badge badge-<?php echo $row['status'] == '1' ? 'success' : 'secondary'?>">
                                    <?php echo $row['status'] == '1' ? 'Aktif' : 'Nonaktif'?>
                                </span>
                            </td>
                            <td>
                                <!-- Tombol Edit -->
                                <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalEditBarang<?php echo $row['id']?>">Edit</button>
                                <!-- Tombol Hapus -->
                                <button type="button" class="btn btn-danger btn-sm btn-hapus"
                                        data-id="<?php echo $row['id'];?>"
                                        data-nama="<?php echo htmlspecialchars($row['nama_barang']);?>">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                        <!-- Modal Edit Barang -->
                        <div class="modal fade" id="modalEditBarang<?php echo $row['id']?>" tabindex="-1" role="dialog">
                            <div class="modal-dialog" role="document">
                                <form method="post">
                                    <input type="hidden" name="id" value="<?php echo $row['id']?>">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Barang</h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label>Kode Barang</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['kode_barang'])?>" readonly>
                                            </div>
                                            <div class="form-group">
                                                <label>Nama Barang</label>
                                                <input type="text" name="nama_barang" class="form-control" required value="<?php echo htmlspecialchars($row['nama_barang'])?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Satuan</label>
                                                <select name="id_satuan" class="form-control" required>
                                                    <?php
                                                        $satuanOpt = $conn->query("SELECT * FROM satuan ORDER BY nama_satuan ASC");
                                                    while ($sat = $satuanOpt->fetch_assoc()): ?>
                                                        <option value="<?php echo $sat['id']?>" <?php echo $row['id_satuan'] == $sat['id'] ? 'selected' : ''?>><?php echo htmlspecialchars($sat['nama_satuan'])?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Jenis</label>
                                                <select name="id_jenis" class="form-control" required>
                                                    <?php
                                                        $jenisOpt = $conn->query("SELECT * FROM jenis_barang ORDER BY nama_jenis ASC");
                                                    while ($jen = $jenisOpt->fetch_assoc()): ?>
                                                        <option value="<?php echo $jen['id']?>" <?php echo $row['id_jenis'] == $jen['id'] ? 'selected' : ''?>><?php echo htmlspecialchars($jen['nama_jenis'])?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Harga Jual</label>
                                                <input type="number" name="harga_jual" class="form-control" required value="<?php echo $row['harga_jual']?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Harga Beli</label>
                                                <input type="number" name="harga_beli" class="form-control" required value="<?php echo $row['harga_beli']?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Stok</label>
                                                <input type="number" name="stok" class="form-control" required value="<?php echo $row['stok']?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select name="status" class="form-control">
                                                    <option value="1" <?php echo $row['status'] == '1' ? 'selected' : ''?>>Aktif</option>
                                                    <option value="0" <?php echo $row['status'] == '0' ? 'selected' : ''?>>Nonaktif</option>
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
                        <h5 class="modal-title">Tambah Barang</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <?php $autoKode = generateKodeBarang($conn); ?>
                        <div class="form-group">
                            <label>Kode Barang</label>
                            <input type="text" name="kode_barang" class="form-control" value="<?php echo $autoKode?>" readonly required>
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
                                    <option value="<?php echo $sat['id']?>"><?php echo htmlspecialchars($sat['nama_satuan'])?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Jenis</label>
                            <select name="id_jenis" class="form-control" required>
                                <option value="">Pilih Jenis</option>
                                <?php foreach ($jenisList as $jen): ?>
                                    <option value="<?php echo $jen['id']?>"><?php echo htmlspecialchars($jen['nama_jenis'])?></option>
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
                        <button type="submit" name="tambah" class="btn btn-success">Tambah</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade" id="modalHapusBarang" tabindex="-1" role="dialog">
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
            document.getElementById('modalHapusBody').innerHTML = 'Apakah Anda yakin ingin menghapus data <b>' + nama + '</b>?';
            $('#modalHapusBarang').modal('show'); // Gunakan jQuery agar compatible BS4
        });
    });

    document.getElementById('btnKonfirmasiHapus').addEventListener('click', function() {
        var id = document.getElementById('hapusId').value;
        window.location.href = 'barang.php?hapus=' + id;
    });
});
</script>
