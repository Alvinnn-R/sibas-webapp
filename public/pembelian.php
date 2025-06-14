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

    // Ambil data barang dan supplier untuk dropdown
    $barangList   = $conn->query("SELECT * FROM barang ORDER BY nama_barang ASC");
    $supplierList = $conn->query("SELECT * FROM supplier ORDER BY nama_supplier ASC");

    // --- PROSES TAMBAH PEMBELIAN ---
    $error   = '';
    $success = '';
    if (isset($_POST['tambah'])) {
        $tanggal     = $_POST['tanggal'];
        $id_supplier = (int) $_POST['id_supplier'];
        $barang      = $_POST['id_barang'];  // array
        $jumlah      = $_POST['jumlah'];     // array
        $harga_beli  = $_POST['harga_beli']; // array
        $user_id     = $_SESSION['user_id'];
        $total       = 0;
        $barang_info = [];

        // Validasi dan hitung total
        foreach ($barang as $i => $id_barang) {
            if (empty($id_barang)) {
                continue;
            }

            $jml = (int) $jumlah[$i];
            $hrg = (int) $harga_beli[$i];

            // Ambil data barang
            $stmt = $conn->prepare("SELECT nama_barang FROM barang WHERE id = ?");
            $stmt->bind_param("i", $id_barang);
            $stmt->execute();
            $result      = $stmt->get_result();
            $barang_data = $result->fetch_assoc();

            if (! $barang_data) {
                $error = "Barang tidak ditemukan!";
                break;
            }
            if ($jml <= 0 || $hrg <= 0) {
                $error = "Jumlah dan harga beli harus lebih dari 0!";
                break;
            }

            $subtotal = $hrg * $jml;
            $total += $subtotal;

            $barang_info[] = [
                'id'       => $id_barang,
                'nama'     => $barang_data['nama_barang'],
                'harga'    => $hrg,
                'jumlah'   => $jml,
                'subtotal' => $subtotal,
            ];
        }

        if (empty($id_supplier)) {
            $error = "Supplier harus dipilih!";
        }

        // Jika tidak ada error, lanjut proses
        if (empty($error)) {
            $conn->begin_transaction();

            try {
                // Insert ke tabel pembelian
                $stmt = $conn->prepare("INSERT INTO pembelian (tanggal, id_supplier, id_user, total) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("siid", $tanggal, $id_supplier, $user_id, $total);
                $stmt->execute();
                $id_pembelian = $conn->insert_id;

                // Insert detail dan update stok
                foreach ($barang_info as $item) {
                    // Insert detail pembelian
                    $stmt = $conn->prepare("INSERT INTO detail_pembelian (id_pembelian, id_barang, harga_beli, jumlah, subtotal) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("iiidd", $id_pembelian, $item['id'], $item['harga'], $item['jumlah'], $item['subtotal']);
                    $stmt->execute();

                    // Update stok barang (+)
                    $stmt = $conn->prepare("UPDATE barang SET stok = stok + ? WHERE id = ?");
                    $stmt->bind_param("ii", $item['jumlah'], $item['id']);
                    $stmt->execute();
                }

                $conn->commit();
                header("Location: pembelian.php?success=1");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Terjadi kesalahan saat menyimpan data: " . $e->getMessage();
            }
        }
    }

    // --- PROSES HAPUS PEMBELIAN ---
    if (isset($_GET['hapus'])) {
        $id = (int) $_GET['hapus'];
        $conn->begin_transaction();
        try {
            // Ambil detail pembelian untuk mengurangi stok
            $detail_result = $conn->query("SELECT id_barang, jumlah FROM detail_pembelian WHERE id_pembelian = $id");
            while ($detail = $detail_result->fetch_assoc()) {
                $stmt = $conn->prepare("UPDATE barang SET stok = stok - ? WHERE id = ?");
                $stmt->bind_param("ii", $detail['jumlah'], $detail['id_barang']);
                $stmt->execute();
            }
            // Hapus detail pembelian
            $conn->query("DELETE FROM detail_pembelian WHERE id_pembelian = $id");
            // Hapus header pembelian
            $conn->query("DELETE FROM pembelian WHERE id = $id");
            $conn->commit();
            header("Location: pembelian.php?deleted=1");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Terjadi kesalahan saat menghapus data: " . $e->getMessage();
        }
    }

    if (isset($_GET['success'])) {
        $success = "Pembelian berhasil disimpan!";
    }

    if (isset($_GET['deleted'])) {
        $success = "Pembelian berhasil dihapus!";
    }

    // --- AMBIL DATA PEMBELIAN (Header) UNTUK TABEL UTAMA ---
    $dataList = $conn->query("
    SELECT p.*, s.nama_supplier, u.nama_lengkap
    FROM pembelian p
    LEFT JOIN supplier s ON p.id_supplier = s.id
    LEFT JOIN users u ON p.id_user = u.id
    ORDER BY p.id DESC
");

    // Function untuk mengambil detail pembelian
    function getDetailPembelian($conn, $id_pembelian)
    {
        $list = [];
        $q    = $conn->query("
        SELECT d.*, b.nama_barang
        FROM detail_pembelian d
        LEFT JOIN barang b ON d.id_barang = b.id
        WHERE d.id_pembelian = $id_pembelian
    ");
        while ($row = $q->fetch_assoc()) {
            $list[] = $row;
        }
        return $list;
    }

    // HEADER
    include '../public/templates/header.php';
    include '../public/templates/sidebar.php';
    include '../public/templates/navbar.php';
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <h1 class="h3 mb-2 text-gray-800">Transaksi Pembelian</h1>
    <p class="mb-4">Input transaksi pembelian barang dari supplier, stok akan otomatis bertambah.</p>

    <!-- Alert Messages -->
    <?php if (! empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <?php if (! empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <!-- Tambah Modal Trigger -->
    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#modalTambah">Tambah Pembelian</button>

    <!-- DataTable -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Pembelian</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="60">No</th>
                            <th>Tanggal</th>
                            <th>Supplier</th>
                            <th>User</th>
                            <th>Total</th>
                            <th>Detail</th>
                            <th width="120">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                        foreach ($dataList as $row): ?>
	                        <tr>
	                            <td><?php echo $no++ ?></td>
	                            <td><?php echo htmlspecialchars($row['tanggal']) ?></td>
	                            <td><?php echo htmlspecialchars($row['nama_supplier']) ?></td>
	                            <td><?php echo htmlspecialchars($row['nama_lengkap']) ?></td>
	                            <td><?php echo number_format($row['total']) ?></td>
	                            <td>
	                                <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#modalDetail<?php echo $row['id'] ?>">Lihat</button>
	                            </td>
	                            <td width="170">
	                                <button type="button" class="btn btn-danger btn-sm btn-hapus"
	                                    data-id="<?php echo $row['id'] ?>" data-nama="Nota #<?php echo $row['id'] ?>">
	                                    Hapus
	                                </button>
                                    <button class="btn btn-secondary btn-sm btn-cetak" data-id="<?php echo $row['id'] ?>">Cetak Nota</button>
	                            </td>
	                        </tr>
	                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Detail (letakkan di luar tabel) -->
    <?php foreach ($dataList as $row): ?>
	    <div class="modal fade" id="modalDetail<?php echo $row['id'] ?>" tabindex="-1" role="dialog">
	        <div class="modal-dialog modal-lg" role="document">
	            <div class="modal-content">
	                <div class="modal-header">
	                    <h5 class="modal-title">Detail Pembelian #<?php echo $row['id'] ?></h5>
	                    <button type="button" class="close" data-dismiss="modal">&times;</button>
	                </div>
	                <div class="modal-body">
	                    <div class="row mb-3">
	                        <div class="col-md-6">
	                            <strong>Tanggal:</strong>	                                                     	                                                     	                                                     	                                                     	                                                     	                                                      <?php echo $row['tanggal'] ?><br>
	                            <strong>Supplier:</strong>	                                                      	                                                      	                                                      	                                                      	                                                      	                                                       <?php echo htmlspecialchars($row['nama_supplier']) ?><br>
	                            <strong>User Input:</strong>	                                                        	                                                        	                                                        	                                                        	                                                        	                                                         <?php echo htmlspecialchars($row['nama_lengkap']) ?>
	                        </div>
	                        <div class="col-md-6">
	                            <strong>Total:</strong>	                                                   	                                                   	                                                   	                                                   	                                                   	                                                    <?php echo number_format($row['total']) ?><br>
	                        </div>
	                    </div>
	                    <table class="table table-bordered">
	                        <thead>
	                            <tr>
	                                <th>No</th>
	                                <th>Barang</th>
	                                <th>Harga Beli</th>
	                                <th>Jumlah</th>
	                                <th>Subtotal</th>
	                            </tr>
	                        </thead>
	                        <tbody>
	                            <?php
                                    $no_detail = 1;
                                    foreach (getDetailPembelian($conn, $row['id']) as $d):
                                ?>
	                            <tr>
	                                <td><?php echo $no_detail++ ?></td>
	                                <td><?php echo htmlspecialchars($d['nama_barang']) ?></td>
	                                <td><?php echo number_format($d['harga_beli']) ?></td>
	                                <td><?php echo $d['jumlah'] ?></td>
	                                <td><?php echo number_format($d['subtotal']) ?></td>
	                            </tr>
	                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php foreach ($dataList as $row): ?>
<?php $detail = getDetailPembelian($conn, $row['id']); ?>
    <div id="nota-pembelian-<?php echo $row['id']; ?>" class="nota-print-area" style="display:none;">
        <div class="nota-struk">
            <div class="nota-header">
                <div class="nota-title">NOTA PEMBELIAN</div>
                <hr>
            </div>
            <div class="nota-info">
                <div>No Nota: <b><?php echo $row['id']; ?></b></div>
                <div>Tanggal:                                                                                                                     <?php echo $row['tanggal']; ?></div>
                <div>Supplier:                                                                                                                         <?php echo htmlspecialchars($row['nama_supplier']); ?></div>
                <div>User:                                                                                                         <?php echo htmlspecialchars($row['nama_lengkap']); ?></div>
            </div>
            <hr>
            <table class="nota-tabel">
                <thead>
                    <tr>
                        <th>Barang</th>
                        <th style="text-align:right;">Jml</th>
                        <th style="text-align:right;">Harga</th>
                        <th style="text-align:right;">Subt.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detail as $d): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($d['nama_barang']); ?></td>
                        <td style="text-align:right;"><?php echo $d['jumlah']; ?></td>
                        <td style="text-align:right;"><?php echo number_format($d['harga_beli']); ?></td>
                        <td style="text-align:right;"><?php echo number_format($d['subtotal']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <hr>
            <div class="nota-total">
                Total: <b><?php echo number_format($row['total']); ?></b>
            </div>
            <hr>
            <div class="nota-footer" style="text-align:center; font-size:10px; margin-top:10px;">
                --- Terima Kasih ---
            </div>
        </div>
    </div>
<?php endforeach; ?>

    <!-- Modal Tambah Pembelian -->
    <div class="modal fade" id="modalTambah" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
          <form method="post" id="formTambahPembelian">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Pembelian</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" required value="<?php echo date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label>Supplier</label>
                        <select name="id_supplier" class="form-control" required>
                            <option value="">-- Pilih Supplier --</option>
                            <?php foreach ($supplierList as $supp): ?>
                                <option value="<?php echo $supp['id'] ?>"><?php echo htmlspecialchars($supp['nama_supplier']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="barangRows">
                        <div class="form-row align-items-end barang-row mb-2">
                            <div class="col-5">
                                <label>Barang</label>
                                <select name="id_barang[]" class="form-control barang-select" required>
                                    <option value="">-- Pilih Barang --</option>
                                    <?php
                                        $barangList->data_seek(0); // Reset pointer
                                        foreach ($barangList as $barang):
                                    ?>
                                        <option value="<?php echo $barang['id'] ?>"
                                            data-harga="<?php echo $barang['harga_beli'] ?>">
                                            <?php echo htmlspecialchars($barang['nama_barang']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-2">
                                <label>Harga Beli</label>
                                <input type="number" name="harga_beli[]" class="form-control harga-beli-input" required>
                            </div>
                            <div class="col-2">
                                <label>Jumlah</label>
                                <input type="number" name="jumlah[]" class="form-control jumlah-input" required min="1">
                            </div>
                            <div class="col-2">
                                <label>Subtotal</label>
                                <input type="number" class="form-control subtotal-display" readonly>
                            </div>
                            <div class="col-1">
                                <button type="button" class="btn btn-danger btn-sm btn-remove-row">×</button>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-secondary btn-sm" id="btnTambahBarang">+ Tambah Barang</button>

                    <hr>
                    <div class="form-group">
                        <label><strong>Total Pembelian</strong></label>
                        <input type="number" id="totalBelanja" class="form-control" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="tambah" class="btn btn-success">Simpan Pembelian</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
          </form>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade" id="modalHapusPembelian" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="modalHapusBody">
                    Apakah Anda yakin ingin menghapus data ini? Stok barang akan dikurangi.
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
    // Function untuk update harga ketika barang dipilih
    $(document).on('change', '.barang-select', function() {
        var selectedOption = $(this).find('option:selected');
        var harga = selectedOption.data('harga') || 0;
        var row = $(this).closest('.barang-row');
        row.find('.harga-beli-input').val(harga);
        hitungSubtotal(row);
    });

    // Function untuk hitung subtotal per baris
    function hitungSubtotal(row) {
        var harga = parseFloat(row.find('.harga-beli-input').val()) || 0;
        var jumlah = parseInt(row.find('.jumlah-input').val()) || 0;
        var subtotal = harga * jumlah;

        row.find('.subtotal-display').val(subtotal);
        hitungTotal();
    }

    // Function untuk hitung total keseluruhan
    function hitungTotal() {
        var total = 0;
        $('.subtotal-display').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $('#totalBelanja').val(total);
    }

    // Event listener untuk harga/jumlah
    $(document).on('input', '.harga-beli-input, .jumlah-input', function() {
        var row = $(this).closest('.barang-row');
        hitungSubtotal(row);
    });

    // Tambah baris barang
    $('#btnTambahBarang').on('click', function() {
        var newRow = $('.barang-row:first').clone();
        newRow.find('select').val('');
        newRow.find('input').val('');
        $('#barangRows').append(newRow);
    });

    // Hapus baris barang
    $(document).on('click', '.btn-remove-row', function() {
        if ($('.barang-row').length > 1) {
            $(this).closest('.barang-row').remove();
            hitungTotal();
        } else {
            alert('Minimal harus ada satu barang!');
        }
    });

    // Validasi form sebelum submit
    $('#formTambahPembelian').on('submit', function(e) {
        var total = parseFloat($('#totalBelanja').val()) || 0;
        if (total <= 0) {
            e.preventDefault();
            alert('Isi data barang dan supplier dengan benar!');
            return;
        }
    });

    // Hapus pembelian
    $('.btn-hapus').on('click', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        $('#hapusId').val(id);
        $('#modalHapusBody').html('Apakah Anda yakin ingin menghapus ' + nama + '? Stok barang akan dikurangi.');
        $('#modalHapusPembelian').modal('show');
    });

    // Konfirmasi hapus
    $('#btnKonfirmasiHapus').on('click', function() {
        var id = $('#hapusId').val();
        window.location.href = 'pembelian.php?hapus=' + id;
    });
});

$('.btn-cetak').on('click', function() {
    var id = $(this).data('id');
    var printContent = $('#nota-pembelian-' + id).html();
    var printWindow = window.open('', '_blank', 'width=400,height=600');
    printWindow.document.write('<html><head><title>Nota Pembelian</title>');
    // Inline CSS agar style tetap
    printWindow.document.write(`
        <style>
        body{margin:0;padding:0;}
        .nota-struk{font-family:monospace,'Courier New',Courier;width:210px;font-size:11px;margin:0 auto;}
        .nota-title{text-align:center;font-size:14px;font-weight:bold;}
        .nota-info, .nota-total{font-size:11px;}
        .nota-tabel{width:100%;border-collapse:collapse;font-size:11px;}
        .nota-tabel th, .nota-tabel td{border:none;padding:0 2px;vertical-align:top;}
        .nota-struk hr{margin:4px 0;}
        @media print{body{margin:0;padding:0;background:#fff;}.nota-struk{margin:0 auto;width:210px!important;font-size:11px!important;}.nota-struk hr{margin:4px 0;}}
        </style>
    `);
    printWindow.document.write('</head><body>');
    printWindow.document.write(printContent);
    printWindow.document.write('<script>window.onload=function(){window.print();window.close();}<\/script>');
    printWindow.document.write('</body></html>');
    printWindow.document.close();
});


</script>


