-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 20, 2025 at 01:33 AM
-- Server version: 8.0.30
-- PHP Version: 8.3.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_sibas`
--

-- --------------------------------------------------------

--
-- Table structure for table `barang`
--

CREATE TABLE `barang` (
  `id` int NOT NULL,
  `kode_barang` varchar(30) NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `id_satuan` int NOT NULL,
  `id_jenis` int NOT NULL,
  `harga_jual` int NOT NULL DEFAULT '0',
  `harga_beli` int NOT NULL DEFAULT '0',
  `stok` int NOT NULL DEFAULT '0',
  `status` enum('1','0') NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `barang_keluar`
--

CREATE TABLE `barang_keluar` (
  `id` int NOT NULL,
  `tanggal` date NOT NULL,
  `id_barang` int NOT NULL,
  `jumlah` int NOT NULL,
  `tujuan` varchar(100) NOT NULL,
  `keterangan` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Triggers `barang_keluar`
--
DELIMITER $$
CREATE TRIGGER `trg_after_insert_barang_keluar` AFTER INSERT ON `barang_keluar` FOR EACH ROW BEGIN
  UPDATE barang SET stok = stok - NEW.jumlah WHERE id = NEW.id_barang;
END
$$
DELIMITER ;

-- TRIGGER Koreksi stok saat UPDATE (edit data)
DELIMITER $$
CREATE TRIGGER trg_after_update_barang_keluar
AFTER UPDATE ON barang_keluar
FOR EACH ROW
BEGIN
  IF OLD.id_barang = NEW.id_barang THEN
    UPDATE barang SET stok = stok + OLD.jumlah - NEW.jumlah WHERE id = NEW.id_barang;
  ELSE
    UPDATE barang SET stok = stok + OLD.jumlah WHERE id = OLD.id_barang;
    UPDATE barang SET stok = stok - NEW.jumlah WHERE id = NEW.id_barang;
  END IF;
END
$$
DELIMITER ;

-- TRIGGER Tambah stok (balik stok saat DELETE)
DELIMITER $$
CREATE TRIGGER trg_after_delete_barang_keluar
AFTER DELETE ON barang_keluar
FOR EACH ROW
BEGIN
  UPDATE barang SET stok = stok + OLD.jumlah WHERE id = OLD.id_barang;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `barang_masuk`
--

CREATE TABLE `barang_masuk` (
  `id` int NOT NULL,
  `tanggal` date NOT NULL,
  `id_barang` int NOT NULL,
  `jumlah` int NOT NULL,
  `id_supplier` int NOT NULL,
  `keterangan` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Triggers `barang_masuk`
--
DELIMITER $$
CREATE TRIGGER `trg_after_insert_barang_masuk` AFTER INSERT ON `barang_masuk` FOR EACH ROW BEGIN
  UPDATE barang SET stok = stok + NEW.jumlah WHERE id = NEW.id_barang;
END
$$
DELIMITER ;

-- TRIGGER: Koreksi stok saat UPDATE (edit data)
DELIMITER $$
CREATE TRIGGER trg_after_update_barang_masuk
AFTER UPDATE ON barang_masuk
FOR EACH ROW
BEGIN
  -- Jika id_barang tidak berubah
  IF OLD.id_barang = NEW.id_barang THEN
    UPDATE barang SET stok = stok - OLD.jumlah + NEW.jumlah WHERE id = NEW.id_barang;
  ELSE
    -- Kembalikan stok lama
    UPDATE barang SET stok = stok - OLD.jumlah WHERE id = OLD.id_barang;
    -- Tambahkan stok baru
    UPDATE barang SET stok = stok + NEW.jumlah WHERE id = NEW.id_barang;
  END IF;
END
$$
DELIMITER ;

-- TRIGGER: Kurangi stok saat DELETE
DELIMITER $$
CREATE TRIGGER trg_after_delete_barang_masuk
AFTER DELETE ON barang_masuk
FOR EACH ROW
BEGIN
  UPDATE barang SET stok = stok - OLD.jumlah WHERE id = OLD.id_barang;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `detail_pembelian`
--

CREATE TABLE `detail_pembelian` (
  `id` int NOT NULL,
  `id_pembelian` int NOT NULL,
  `id_barang` int NOT NULL,
  `harga_beli` int NOT NULL,
  `jumlah` int NOT NULL,
  `subtotal` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `detail_penjualan`
--

CREATE TABLE `detail_penjualan` (
  `id` int NOT NULL,
  `id_penjualan` int NOT NULL,
  `id_barang` int NOT NULL,
  `harga_jual` int NOT NULL,
  `jumlah` int NOT NULL,
  `subtotal` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `jenis_barang`
--

CREATE TABLE `jenis_barang` (
  `id` int NOT NULL,
  `nama_jenis` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `aktivitas` text NOT NULL,
  `entitas` varchar(50) NOT NULL,
  `entitas_id` int NOT NULL,
  `waktu` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `pembelian`
--

CREATE TABLE `pembelian` (
  `id` int NOT NULL,
  `tanggal` date NOT NULL,
  `id_supplier` int NOT NULL,
  `id_user` int NOT NULL,
  `total` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `penjualan`
--

CREATE TABLE `penjualan` (
  `id` int NOT NULL,
  `tanggal` date NOT NULL,
  `id_user` int NOT NULL,
  `total` double NOT NULL,
  `dibayar` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `satuan`
--

CREATE TABLE `satuan` (
  `id` int NOT NULL,
  `nama_satuan` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `id` int NOT NULL,
  `nama_supplier` varchar(60) NOT NULL,
  `alamat` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `profile` varchar(255) DEFAULT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','petugas','viewer') NOT NULL DEFAULT 'viewer',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `profile`, `nama_lengkap`, `username`, `password`, `role`, `created_at`) VALUES
(1, NULL, 'Riky Hermanto', 'riky', '$2y$10$4SWOXEtw7cekwymBGDnRbegrflGpgw9abywKsCBx4vUtzPz0EMB7y', 'petugas', '2025-05-19 14:09:54'),
(2, NULL, 'Alvin Rama', 'alvin', '$2y$10$FfT1WI9lXYfPT/JA0pgY7.rpcRArsAm7U79/APsdQvLpmpsbjtBfu', 'admin', '2025-05-26 18:26:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_barang` (`kode_barang`),
  ADD KEY `fk_barang_satuan` (`id_satuan`),
  ADD KEY `fk_barang_jenis` (`id_jenis`);

--
-- Indexes for table `barang_keluar`
--
ALTER TABLE `barang_keluar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_barangkeluar_barang` (`id_barang`);

--
-- Indexes for table `barang_masuk`
--
ALTER TABLE `barang_masuk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_barangmasuk_barang` (`id_barang`),
  ADD KEY `fk_barangmasuk_supplier` (`id_supplier`);

--
-- Indexes for table `detail_pembelian`
--
ALTER TABLE `detail_pembelian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_detailpembelian_pembelian` (`id_pembelian`),
  ADD KEY `fk_detailpembelian_barang` (`id_barang`);

--
-- Indexes for table `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_detailpenjualan_penjualan` (`id_penjualan`),
  ADD KEY `fk_detailpenjualan_barang` (`id_barang`);

--
-- Indexes for table `jenis_barang`
--
ALTER TABLE `jenis_barang`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_logaktivitas_user` (`user_id`);

--
-- Indexes for table `pembelian`
--
ALTER TABLE `pembelian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pembelian_supplier` (`id_supplier`),
  ADD KEY `fk_pembelian_user` (`id_user`);

--
-- Indexes for table `penjualan`
--
ALTER TABLE `penjualan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_penjualan_user` (`id_user`);

--
-- Indexes for table `satuan`
--
ALTER TABLE `satuan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barang`
--
ALTER TABLE `barang`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `barang_keluar`
--
ALTER TABLE `barang_keluar`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `barang_masuk`
--
ALTER TABLE `barang_masuk`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `detail_pembelian`
--
ALTER TABLE `detail_pembelian`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jenis_barang`
--
ALTER TABLE `jenis_barang`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pembelian`
--
ALTER TABLE `pembelian`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `penjualan`
--
ALTER TABLE `penjualan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `satuan`
--
ALTER TABLE `satuan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier`
--
ALTER TABLE `supplier`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barang`
--
ALTER TABLE `barang`
  ADD CONSTRAINT `fk_barang_jenis` FOREIGN KEY (`id_jenis`) REFERENCES `jenis_barang` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_barang_satuan` FOREIGN KEY (`id_satuan`) REFERENCES `satuan` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `barang_keluar`
--
ALTER TABLE `barang_keluar`
  ADD CONSTRAINT `fk_barangkeluar_barang` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `barang_masuk`
--
ALTER TABLE `barang_masuk`
  ADD CONSTRAINT `fk_barangmasuk_barang` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_barangmasuk_supplier` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `detail_pembelian`
--
ALTER TABLE `detail_pembelian`
  ADD CONSTRAINT `fk_detailpembelian_barang` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detailpembelian_pembelian` FOREIGN KEY (`id_pembelian`) REFERENCES `pembelian` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  ADD CONSTRAINT `fk_detailpenjualan_barang` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detailpenjualan_penjualan` FOREIGN KEY (`id_penjualan`) REFERENCES `penjualan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD CONSTRAINT `fk_logaktivitas_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `pembelian`
--
ALTER TABLE `pembelian`
  ADD CONSTRAINT `fk_pembelian_supplier` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pembelian_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `penjualan`
--
ALTER TABLE `penjualan`
  ADD CONSTRAINT `fk_penjualan_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
