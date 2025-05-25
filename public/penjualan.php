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

    // Ambil data barang untuk dropdown (dengan stok > 0)
    $barangList = $conn->query("SELECT * FROM barang WHERE stok > 0 ORDER BY nama_barang ASC");

    // --- PROSES TAMBAH PENJUALAN ---
    $error   = '';
    $success = '';
    if (isset($_POST['tambah'])) {
        $tanggal     = $_POST['tanggal'];
        $barang      = $_POST['id_barang']; // array
        $jumlah      = $_POST['jumlah'];    // array
        $user_id     = $_SESSION['user_id'];
        $dibayar     = (float) $_POST['dibayar'];
        $total       = 0;
        $barang_info = [];

        // Validasi dan hitung total
        foreach ($barang as $i => $id_barang) {
            if (empty($id_barang)) {
                continue;
            }

            $jml = (int) $jumlah[$i];

            // Ambil data barang dan cek stok
            $stmt = $conn->prepare("SELECT nama_barang, harga_jual, stok FROM barang WHERE id = ?");
            $stmt->bind_param("i", $id_barang);
            $stmt->execute();
            $result      = $stmt->get_result();
            $barang_data = $result->fetch_assoc();

            if (! $barang_data) {
                $error = "Barang tidak ditemukan!";
                break;
            }

            if ($jml > $barang_data['stok']) {
                $error = "Stok barang <b>{$barang_data['nama_barang']}</b> tidak cukup! Tersedia: <b>{$barang_data['stok']}</b>, Diminta: <b>{$jml}</b>";
                break;
            }

            $subtotal = $barang_data['harga_jual'] * $jml;
            $total += $subtotal;

            $barang_info[] = [
                'id'            => $id_barang,
                'nama'          => $barang_data['nama_barang'],
                'harga'         => $barang_data['harga_jual'],
                'jumlah'        => $jml,
                'subtotal'      => $subtotal,
                'stok_tersedia' => $barang_data['stok'],
            ];
        }

        // Validasi pembayaran
        if (empty($error) && $dibayar < $total) {
            $error = "Uang yang dibayar kurang! Total: <b>" . number_format($total) . "</b>, Dibayar: <b>" . number_format($dibayar) . "</b>";
        }

        // Jika tidak ada error, lanjut proses
        if (empty($error)) {
            // Begin transaction
            $conn->begin_transaction();

            try {
                // Insert ke tabel penjualan
                $stmt = $conn->prepare("INSERT INTO penjualan (tanggal, id_user, total, dibayar) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sidd", $tanggal, $user_id, $total, $dibayar);
                $stmt->execute();
                $id_penjualan = $conn->insert_id;

                // Insert detail dan update stok
                foreach ($barang_info as $item) {
                    // Insert detail penjualan
                    $stmt = $conn->prepare("INSERT INTO detail_penjualan (id_penjualan, id_barang, harga_jual, jumlah, subtotal) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("iiidd", $id_penjualan, $item['id'], $item['harga'], $item['jumlah'], $item['subtotal']);
                    $stmt->execute();

                    // Update stok barang
                    $stmt = $conn->prepare("UPDATE barang SET stok = stok - ? WHERE id = ?");
                    $stmt->bind_param("ii", $item['jumlah'], $item['id']);
                    $stmt->execute();
                }

                $conn->commit();
                header("Location: penjualan.php?success=1");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Terjadi kesalahan saat menyimpan data: " . $e->getMessage();
            }
        }
    }

    // --- PROSES HAPUS PENJUALAN ---
    if (isset($_GET['hapus'])) {
        $id = (int) $_GET['hapus'];

        // Begin transaction
        $conn->begin_transaction();

        try {
            // Ambil detail penjualan untuk mengembalikan stok
            $detail_result = $conn->query("SELECT id_barang, jumlah FROM detail_penjualan WHERE id_penjualan = $id");

            // Kembalikan stok barang
            while ($detail = $detail_result->fetch_assoc()) {
                $stmt = $conn->prepare("UPDATE barang SET stok = stok + ? WHERE id = ?");
                $stmt->bind_param("ii", $detail['jumlah'], $detail['id_barang']);
                $stmt->execute();
            }

            // Hapus detail penjualan
            $conn->query("DELETE FROM detail_penjualan WHERE id_penjualan = $id");

            // Hapus header penjualan
            $conn->query("DELETE FROM penjualan WHERE id = $id");

            $conn->commit();
            header("Location: penjualan.php?deleted=1");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Terjadi kesalahan saat menghapus data: " . $e->getMessage();
        }
    }

    // Success message
    if (isset($_GET['success'])) {
        $success = "Penjualan berhasil disimpan!";
    }
    if (isset($_GET['deleted'])) {
        $success = "Penjualan berhasil dihapus!";
    }

    // --- AMBIL DATA PENJUALAN (Header) UNTUK TABEL UTAMA ---
    $dataList = $conn->query("
        SELECT p.*, u.nama_lengkap
        FROM penjualan p
        LEFT JOIN users u ON p.id_user = u.id
        ORDER BY p.id DESC
    ");

    // Function untuk mengambil detail penjualan
    function getDetailPenjualan($conn, $id_penjualan)
    {
        $list = [];
        $q    = $conn->query("
            SELECT d.*, b.nama_barang
            FROM detail_penjualan d
            LEFT JOIN barang b ON d.id_barang = b.id
            WHERE d.id_penjualan = $id_penjualan
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

    <h1 class="h3 mb-2 text-gray-800">Transaksi Penjualan</h1>
    <p class="mb-4">Input transaksi penjualan barang, stok akan otomatis berkurang.</p>

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
    <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#modalTambah">Tambah Penjualan</button>

    <!-- DataTable -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Penjualan</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="60">No</th>
                            <th>Tanggal</th>
                            <th>User</th>
                            <th>Total</th>
                            <th>Dibayar</th>
                            <th>Kembalian</th>
                            <th>Detail</th>
                            <th width="170">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1;
                            foreach ($dataList as $row):
                                $kembalian = $row['dibayar'] - $row['total'];
                            ?>
		                            <tr>
		                                <td><?php echo $no++ ?></td>
		                                <td><?php echo htmlspecialchars($row['tanggal']) ?></td>
		                                <td><?php echo htmlspecialchars($row['nama_lengkap']) ?></td>
		                                <td><?php echo number_format($row['total']) ?></td>
		                                <td><?php echo number_format($row['dibayar']) ?></td>
		                                <td><?php echo number_format($kembalian) ?></td>
		                                <td>
		                                    <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#modalDetail<?php echo $row['id'] ?>">Lihat</button>
		                                </td>
		                                <td>
		                                    <button type="button" class="btn btn-danger btn-sm btn-hapus"
		                                        data-id="<?php echo $row['id'] ?>" data-nama="Nota #<?php echo $row['id'] ?>">
		                                        Hapus
		                                    </button>
		                                    <button type="button" class="btn btn-secondary btn-sm btn-cetak-nota"
		                                        data-id="<?php echo $row['id'] ?>">Cetak Nota
		                                    </button>
		                                </td>
		                            </tr>
		                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Detail (letakkan di luar tabel) -->
    <?php foreach ($dataList as $row):
            $kembalian = $row['dibayar'] - $row['total'];
        ?>
		        <div class="modal fade" id="modalDetail<?php echo $row['id'] ?>" tabindex="-1" role="dialog">
		            <div class="modal-dialog modal-lg" role="document">
		                <div class="modal-content">
		                    <div class="modal-header">
		                        <h5 class="modal-title">Detail Penjualan #<?php echo $row['id'] ?></h5>
		                        <button type="button" class="close" data-dismiss="modal">&times;</button>
		                    </div>
		                    <div class="modal-body">
		                        <div class="row mb-3">
		                            <div class="col-md-6">
		                                <strong>Tanggal:</strong>		                                                         	                                                          <?php echo $row['tanggal'] ?><br>
		                                <strong>Kasir:</strong>		                                                       	                                                        <?php echo htmlspecialchars($row['nama_lengkap']) ?>
		                            </div>
		                            <div class="col-md-6">
		                                <strong>Total:</strong>		                                                       	                                                        <?php echo number_format($row['total']) ?><br>
		                                <strong>Dibayar:</strong>		                                                         	                                                          <?php echo number_format($row['dibayar']) ?><br>
		                                <strong>Kembalian:</strong>		                                                           	                                                            <?php echo number_format($kembalian) ?>
		                            </div>
		                        </div>
		                        <table class="table table-bordered">
		                            <thead>
		                                <tr>
		                                    <th>No</th>
		                                    <th>Barang</th>
		                                    <th>Harga</th>
		                                    <th>Jumlah</th>
		                                    <th>Subtotal</th>
		                                </tr>
		                            </thead>
		                            <tbody>
		                                <?php
                                                $no_detail = 1;
                                                foreach (getDetailPenjualan($conn, $row['id']) as $d):
                                            ?>
		                                    <tr>
		                                        <td><?php echo $no_detail++ ?></td>
		                                        <td><?php echo htmlspecialchars($d['nama_barang']) ?></td>
		                                        <td><?php echo number_format($d['harga_jual']) ?></td>
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
        <div id="nota-penjualan-<?php echo $row['id'] ?>" style="display:none">
            <?php
                $detail    = getDetailPenjualan($conn, $row['id']);
                $kembalian = $row['dibayar'] - $row['total'];
            ?>
            <div class="nota-struk">
                <div style="text-align:center;font-size:14px;font-weight:bold;">SIBAS Mini Market</div>
                <div style="text-align:center;font-size:11px;">Jl. Contoh Alamat No. 123<br>WA: 08xx-xxxx-xxxx</div>
                <hr style="margin:5px 0;">
                <div style="font-size:11px">
                    <div>Tanggal:                                                                   <?php echo $row['tanggal'] ?></div>
                    <div>Kasir:                                                               <?php echo htmlspecialchars($row['nama_lengkap']) ?></div>
                    <div>No. Nota:                                                                     <?php echo $row['id'] ?></div>
                </div>
                <hr style="margin:5px 0;">
                <table style="width:100%;font-size:11px;">
                    <thead>
                        <tr>
                            <th style="text-align:left;">Barang</th>
                            <th style="text-align:right;">Qty</th>
                            <th style="text-align:right;">Harga</th>
                            <th style="text-align:right;">Sub</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detail as $d): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($d['nama_barang']) ?></td>
                                <td style="text-align:right;"><?php echo $d['jumlah'] ?></td>
                                <td style="text-align:right;"><?php echo number_format($d['harga_jual']) ?></td>
                                <td style="text-align:right;"><?php echo number_format($d['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <hr style="margin:5px 0;">
                <div style="font-size:12px;">
                    <div style="display:flex;justify-content:space-between;">
                        <span>Total</span>
                        <span><?php echo number_format($row['total']) ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;">
                        <span>Dibayar</span>
                        <span><?php echo number_format($row['dibayar']) ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;">
                        <span>Kembali</span>
                        <span><?php echo number_format($kembalian) ?></span>
                    </div>
                </div>
                <hr style="margin:6px 0;">
                <div style="text-align:center;font-size:11px;">Terima kasih atas kunjungan Anda</div>
            </div>
        </div>
    <?php endforeach; ?>


    <!-- Modal Tambah Penjualan -->
    <div class="modal fade" id="modalTambah" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <form method="post" id="formTambahPenjualan">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Penjualan</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" required value="<?php echo date('Y-m-d') ?>">
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
                                                data-harga="<?php echo $barang['harga_jual'] ?>"
                                                data-stok="<?php echo $barang['stok'] ?>">
                                                <?php echo htmlspecialchars($barang['nama_barang']) ?> (Stok:<?php echo $barang['stok'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-2">
                                    <label>Harga</label>
                                    <input type="number" name="harga_display[]" class="form-control harga-display" readonly>
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
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><strong>Total Belanja</strong></label>
                                    <input type="number" id="totalBelanja" class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><strong>Dibayar</strong></label>
                                    <input type="number" name="dibayar" id="dibayar" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><strong>Kembalian</strong></label>
                            <input type="number" id="kembalian" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="tambah" class="btn btn-success">Simpan Penjualan</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade" id="modalHapusPenjualan" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="modalHapusBody">
                    Apakah Anda yakin ingin menghapus data ini? Stok barang akan dikembalikan.
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

<style>
    @media print {
        @page {
            size: 58mm auto;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .nota-struk {
            width: 58mm !important;
            font-size: 11px !important;
            margin: 0 auto;
        }
    }

    .nota-struk {
        width: 58mm;
        font-size: 11px;
        margin: 0 auto;
    }
</style>

<script>
    $(document).ready(function() {
        // Function untuk update harga ketika barang dipilih
        $(document).on('change', '.barang-select', function() {
            var selectedOption = $(this).find('option:selected');
            var harga = selectedOption.data('harga') || 0;
            var stok = selectedOption.data('stok') || 0;
            var row = $(this).closest('.barang-row');

            row.find('.harga-display').val(harga);
            row.find('.jumlah-input').attr('max', stok);

            hitungSubtotal(row);
        });

        // Function untuk hitung subtotal per baris
        function hitungSubtotal(row) {
            var harga = parseFloat(row.find('.harga-display').val()) || 0;
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
            hitungKembalian();
        }

        // Function untuk hitung kembalian
        function hitungKembalian() {
            var total = parseFloat($('#totalBelanja').val()) || 0;
            var dibayar = parseFloat($('#dibayar').val()) || 0;
            var kembalian = dibayar - total;
            $('#kembalian').val(kembalian);

            // Ubah warna jika kurang bayar
            if (kembalian < 0) {
                $('#kembalian').addClass('text-danger');
            } else {
                $('#kembalian').removeClass('text-danger');
            }
        }

        // Event listener untuk jumlah
        $(document).on('input', '.jumlah-input', function() {
            var row = $(this).closest('.barang-row');
            var stok = row.find('.barang-select option:selected').data('stok') || 0;
            var jumlah = parseInt($(this).val()) || 0;

            if (jumlah > stok) {
                alert('Jumlah melebihi stok yang tersedia (' + stok + ')');
                $(this).val(stok);
            }

            hitungSubtotal(row);
        });

        // Event listener untuk dibayar
        $('#dibayar').on('input', function() {
            hitungKembalian();
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
        $('#formTambahPenjualan').on('submit', function(e) {
            var total = parseFloat($('#totalBelanja').val()) || 0;
            var dibayar = parseFloat($('#dibayar').val()) || 0;

            if (total <= 0) {
                e.preventDefault();
                alert('Pilih minimal satu barang!');
                return;
            }

            if (dibayar < total) {
                e.preventDefault();
                alert('Uang yang dibayar kurang dari total belanja!');
                return;
            }
        });

        // Hapus penjualan
        $('.btn-hapus').on('click', function() {
            var id = $(this).data('id');
            var nama = $(this).data('nama');
            $('#hapusId').val(id);
            $('#modalHapusBody').html('Apakah Anda yakin ingin menghapus ' + nama + '? Stok barang akan dikembalikan.');
            $('#modalHapusPenjualan').modal('show');
        });

        // Konfirmasi hapus
        $('#btnKonfirmasiHapus').on('click', function() {
            var id = $('#hapusId').val();
            window.location.href = 'penjualan.php?hapus=' + id;
        });
    });

    $(document).ready(function() {
        // ... kode lain tetap ...

        $('.btn-cetak-nota').on('click', function() {
            var id = $(this).data('id');
            var printContent = $('#nota-penjualan-' + id).html();
            var printWindow = window.open('', '_blank', 'width=400,height=600');
            var css = `
            @media print {
                @page { size: 58mm auto; margin: 0; }
                body { margin: 0; padding: 0; background: #fff;}
                .nota-struk { width: 58mm !important; font-size: 11px !important; margin: 0 auto;}
            }
            .nota-struk { width: 58mm; font-size: 11px; margin: 0 auto;}
        `;
            printWindow.document.write('<html><head><title>Nota Penjualan</title>');
            printWindow.document.write('<style>' + css + '</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write(printContent);
            printWindow.document.write('<script>window.onload=function(){window.print();window.close();}<\/script>');
            printWindow.document.write('</body></html>');
            printWindow.document.close();
        });
    });
</script>