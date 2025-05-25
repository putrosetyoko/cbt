-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 25, 2025 at 02:47 AM
-- Server version: 10.4.22-MariaDB
-- PHP Version: 7.4.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cbt`
--

-- --------------------------------------------------------

--
-- Table structure for table `dosen`
--

CREATE TABLE `dosen` (
  `id_dosen` int(11) NOT NULL,
  `nip` varchar(20) NOT NULL,
  `nama_dosen` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `matkul_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `dosen`
--

INSERT INTO `dosen` (`id_dosen`, `nip`, `nama_dosen`, `email`, `matkul_id`) VALUES
(1, '0012345678', 'Prof. Dr. Ir. Cahyo Utomo', 'cahyo.utomo@example.com', NULL),
(2, '0087654321', 'Dr. Siti Nurjanah, M.Kom.', 'siti.nurjanah@example.com', NULL),
(3, '12121212', 'Kurniawan', 'dosen@gmail.com', 3);

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`id`, `name`, `description`) VALUES
(1, 'admin', 'Administrator'),
(2, 'dosen', 'Dosen Pengajar'),
(3, 'mahasiswa', 'Mahasiswa Peserta Ujian');

-- --------------------------------------------------------

--
-- Table structure for table `h_ujian`
--

CREATE TABLE `h_ujian` (
  `id` int(11) NOT NULL,
  `ujian_id` int(11) NOT NULL,
  `mahasiswa_id` int(11) NOT NULL,
  `waktu_mulai` datetime DEFAULT current_timestamp(),
  `waktu_selesai` datetime DEFAULT NULL,
  `status` enum('completed','unfinished','expired') DEFAULT 'unfinished',
  `jml_benar` int(11) DEFAULT NULL,
  `nilai` float DEFAULT NULL,
  `list_jawaban` text DEFAULT NULL,
  `token` varchar(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `jawaban_mahasiswa`
--

CREATE TABLE `jawaban_mahasiswa` (
  `id` int(11) NOT NULL,
  `id_h_ujian` int(11) NOT NULL,
  `id_soal` int(11) NOT NULL,
  `jawaban_dipilih` enum('A','B','C','D','E','EMPTY') DEFAULT 'EMPTY',
  `is_correct` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `jurusan`
--

CREATE TABLE `jurusan` (
  `id_jurusan` int(11) NOT NULL,
  `nama_jurusan` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `jurusan`
--

INSERT INTO `jurusan` (`id_jurusan`, `nama_jurusan`) VALUES
(4, 'IPA'),
(5, 'IPS');

-- --------------------------------------------------------

--
-- Table structure for table `jurusan_matkul`
--

CREATE TABLE `jurusan_matkul` (
  `id` int(11) NOT NULL,
  `jurusan_id` int(11) NOT NULL,
  `matkul_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `jurusan_matkul`
--

INSERT INTO `jurusan_matkul` (`id`, `jurusan_id`, `matkul_id`) VALUES
(7, 4, 3);

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id_kelas` int(11) NOT NULL,
  `nama_kelas` varchar(50) NOT NULL,
  `jurusan_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `kelas`
--

INSERT INTO `kelas` (`id_kelas`, `nama_kelas`, `jurusan_id`) VALUES
(1, 'TI-A Pagi', NULL),
(2, 'SI-B Malam', NULL),
(3, 'MN-C Reguler', NULL),
(4, '1A', 4),
(5, '2A', 5);

-- --------------------------------------------------------

--
-- Table structure for table `kelas_dosen`
--

CREATE TABLE `kelas_dosen` (
  `id` int(11) NOT NULL,
  `kelas_id` int(11) NOT NULL,
  `dosen_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `kelas_dosen`
--

INSERT INTO `kelas_dosen` (`id`, `kelas_id`, `dosen_id`) VALUES
(4, 4, 3);

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `login` varchar(100) DEFAULT NULL,
  `time` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `ip_address`, `login`, `time`) VALUES
(1, '127.0.0.1', 'wronguser@example.com', 1748063789),
(2, '127.0.0.1', 'admin@example.com', 1748067089),
(3, '::1', 'admin', 1748132761),
(4, '::1', 'admin@gmail.com', 1748132769);

-- --------------------------------------------------------

--
-- Table structure for table `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `id_mahasiswa` int(11) NOT NULL,
  `nim` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `kelas_id` int(11) DEFAULT NULL,
  `jurusan_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `mahasiswa`
--

INSERT INTO `mahasiswa` (`id_mahasiswa`, `nim`, `nama`, `email`, `kelas_id`, `jurusan_id`) VALUES
(1, '20230001', 'Ani Setiawati', 'ani.s@example.com', 1, NULL),
(2, '20230002', 'Budi Hartono', 'budi.h@example.com', 1, NULL),
(3, '20230003', 'Citra Dewi', 'citra.d@example.com', 2, NULL),
(4, '20230004', 'Dedi Kurniawan', 'dedi.k@example.com', 2, NULL),
(5, '20230005', 'Eka Putri', 'eka.p@example.com', 3, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `matkul`
--

CREATE TABLE `matkul` (
  `id_matkul` int(11) NOT NULL,
  `nama_matkul` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `matkul`
--

INSERT INTO `matkul` (`id_matkul`, `nama_matkul`) VALUES
(3, 'Algoritma dan Struktur Data');

-- --------------------------------------------------------

--
-- Table structure for table `m_ujian`
--

CREATE TABLE `m_ujian` (
  `id_ujian` int(11) NOT NULL,
  `token` varchar(6) DEFAULT NULL,
  `nama_ujian` varchar(100) NOT NULL,
  `id_matkul` int(11) NOT NULL,
  `jumlah_soal` int(11) NOT NULL,
  `waktu` int(11) NOT NULL COMMENT 'durasi dalam menit',
  `acak_soal` enum('Y','N') DEFAULT 'N',
  `acak_opsi` enum('Y','N') DEFAULT 'N',
  `aktif` enum('Y','N') DEFAULT 'Y',
  `tgl_mulai` datetime DEFAULT current_timestamp(),
  `jenis` enum('acak','urut') DEFAULT 'acak',
  `dosen_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `m_ujian`
--

INSERT INTO `m_ujian` (`id_ujian`, `token`, `nama_ujian`, `id_matkul`, `jumlah_soal`, `waktu`, `acak_soal`, `acak_opsi`, `aktif`, `tgl_mulai`, `jenis`, `dosen_id`, `created_at`, `updated_at`) VALUES
(3, 'KLMNO3', 'Ujian Akhir Semester Algoritma', 3, 10, 90, 'Y', 'Y', 'N', '2025-06-10 09:00:00', 'acak', 1, '2025-05-24 06:16:29', '2025-05-24 06:16:29');

-- --------------------------------------------------------

--
-- Table structure for table `tb_soal`
--

CREATE TABLE `tb_soal` (
  `id_soal` int(11) NOT NULL,
  `id_matkul` int(11) NOT NULL,
  `dosen_id` int(11) NOT NULL,
  `soal` text NOT NULL,
  `file` varchar(255) DEFAULT NULL,
  `tipe_file` varchar(50) DEFAULT NULL,
  `opsi_a` text DEFAULT NULL,
  `opsi_b` text DEFAULT NULL,
  `opsi_c` text DEFAULT NULL,
  `opsi_d` text DEFAULT NULL,
  `opsi_e` text DEFAULT NULL,
  `jawaban` enum('A','B','C','D','E') NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `created_on` int(10) UNSIGNED DEFAULT NULL,
  `updated_on` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tb_soal`
--

INSERT INTO `tb_soal` (`id_soal`, `id_matkul`, `dosen_id`, `soal`, `file`, `tipe_file`, `opsi_a`, `opsi_b`, `opsi_c`, `opsi_d`, `opsi_e`, `jawaban`, `gambar`, `created_on`, `updated_on`) VALUES
(5, 3, 1, 'Manakah yang bukan merupakan algoritma sorting?', NULL, NULL, 'Bubble Sort', 'Quick Sort', 'Merge Sort', 'Tree Sort', 'Depth-First Search', 'E', NULL, 1748067389, 1748067389);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `ip_address` varbinary(16) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `activation_selector` varchar(255) DEFAULT NULL,
  `activation_code` varchar(255) DEFAULT NULL,
  `forgotten_password_selector` varchar(255) DEFAULT NULL,
  `forgotten_password_code` varchar(255) DEFAULT NULL,
  `forgotten_password_time` int(10) UNSIGNED DEFAULT NULL,
  `remember_selector` varchar(255) DEFAULT NULL,
  `remember_code` varchar(255) DEFAULT NULL,
  `created_on` int(10) UNSIGNED NOT NULL,
  `last_login` int(10) UNSIGNED DEFAULT NULL,
  `active` tinyint(1) UNSIGNED DEFAULT 1,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `ip_address`, `username`, `password`, `email`, `activation_selector`, `activation_code`, `forgotten_password_selector`, `forgotten_password_code`, `forgotten_password_time`, `remember_selector`, `remember_code`, `created_on`, `last_login`, `active`, `first_name`, `last_name`, `company`, `phone`) VALUES
(1, 0x32313330373036343333, 'admin', '$2y$12$YfiRyW1YQFpo4IQ.wEnYhO/Smj4eUTTRnEQ36WySZzJL1RjJiaCAa', 'admin@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1748067389, 1748133942, 1, 'Super', 'Admin', 'Ujian Online', '081234567890');

-- --------------------------------------------------------

--
-- Table structure for table `users_groups`
--

CREATE TABLE `users_groups` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `group_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users_groups`
--

INSERT INTO `users_groups` (`user_id`, `group_id`) VALUES
(1, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dosen`
--
ALTER TABLE `dosen`
  ADD PRIMARY KEY (`id_dosen`),
  ADD UNIQUE KEY `nip` (`nip`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `matkul_id` (`matkul_id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `h_ujian`
--
ALTER TABLE `h_ujian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ujian_id` (`ujian_id`),
  ADD KEY `mahasiswa_id` (`mahasiswa_id`);

--
-- Indexes for table `jawaban_mahasiswa`
--
ALTER TABLE `jawaban_mahasiswa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_h_ujian` (`id_h_ujian`),
  ADD KEY `id_soal` (`id_soal`);

--
-- Indexes for table `jurusan`
--
ALTER TABLE `jurusan`
  ADD PRIMARY KEY (`id_jurusan`),
  ADD UNIQUE KEY `nama_jurusan` (`nama_jurusan`);

--
-- Indexes for table `jurusan_matkul`
--
ALTER TABLE `jurusan_matkul`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `jurusan_id` (`jurusan_id`,`matkul_id`),
  ADD KEY `matkul_id` (`matkul_id`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id_kelas`),
  ADD UNIQUE KEY `nama_kelas` (`nama_kelas`),
  ADD KEY `jurusan_id` (`jurusan_id`);

--
-- Indexes for table `kelas_dosen`
--
ALTER TABLE `kelas_dosen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kelas_id` (`kelas_id`,`dosen_id`),
  ADD KEY `dosen_id` (`dosen_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`id_mahasiswa`),
  ADD UNIQUE KEY `nim` (`nim`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `kelas_id` (`kelas_id`),
  ADD KEY `jurusan_id` (`jurusan_id`);

--
-- Indexes for table `matkul`
--
ALTER TABLE `matkul`
  ADD PRIMARY KEY (`id_matkul`),
  ADD UNIQUE KEY `nama_matkul` (`nama_matkul`);

--
-- Indexes for table `m_ujian`
--
ALTER TABLE `m_ujian`
  ADD PRIMARY KEY (`id_ujian`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `id_matkul` (`id_matkul`),
  ADD KEY `dosen_id` (`dosen_id`);

--
-- Indexes for table `tb_soal`
--
ALTER TABLE `tb_soal`
  ADD PRIMARY KEY (`id_soal`),
  ADD KEY `id_matkul` (`id_matkul`),
  ADD KEY `dosen_id` (`dosen_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `email_2` (`email`);

--
-- Indexes for table `users_groups`
--
ALTER TABLE `users_groups`
  ADD PRIMARY KEY (`user_id`,`group_id`),
  ADD KEY `group_id` (`group_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dosen`
--
ALTER TABLE `dosen`
  MODIFY `id_dosen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `h_ujian`
--
ALTER TABLE `h_ujian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `jawaban_mahasiswa`
--
ALTER TABLE `jawaban_mahasiswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `jurusan`
--
ALTER TABLE `jurusan`
  MODIFY `id_jurusan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `jurusan_matkul`
--
ALTER TABLE `jurusan_matkul`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id_kelas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `kelas_dosen`
--
ALTER TABLE `kelas_dosen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id_mahasiswa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `matkul`
--
ALTER TABLE `matkul`
  MODIFY `id_matkul` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `m_ujian`
--
ALTER TABLE `m_ujian`
  MODIFY `id_ujian` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tb_soal`
--
ALTER TABLE `tb_soal`
  MODIFY `id_soal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dosen`
--
ALTER TABLE `dosen`
  ADD CONSTRAINT `dosen_ibfk_1` FOREIGN KEY (`matkul_id`) REFERENCES `matkul` (`id_matkul`) ON DELETE SET NULL;

--
-- Constraints for table `h_ujian`
--
ALTER TABLE `h_ujian`
  ADD CONSTRAINT `h_ujian_ibfk_1` FOREIGN KEY (`ujian_id`) REFERENCES `m_ujian` (`id_ujian`) ON DELETE CASCADE,
  ADD CONSTRAINT `h_ujian_ibfk_2` FOREIGN KEY (`mahasiswa_id`) REFERENCES `mahasiswa` (`id_mahasiswa`) ON DELETE CASCADE;

--
-- Constraints for table `jawaban_mahasiswa`
--
ALTER TABLE `jawaban_mahasiswa`
  ADD CONSTRAINT `jawaban_mahasiswa_ibfk_1` FOREIGN KEY (`id_h_ujian`) REFERENCES `h_ujian` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jawaban_mahasiswa_ibfk_2` FOREIGN KEY (`id_soal`) REFERENCES `tb_soal` (`id_soal`) ON DELETE CASCADE;

--
-- Constraints for table `jurusan_matkul`
--
ALTER TABLE `jurusan_matkul`
  ADD CONSTRAINT `jurusan_matkul_ibfk_1` FOREIGN KEY (`jurusan_id`) REFERENCES `jurusan` (`id_jurusan`) ON DELETE CASCADE,
  ADD CONSTRAINT `jurusan_matkul_ibfk_2` FOREIGN KEY (`matkul_id`) REFERENCES `matkul` (`id_matkul`) ON DELETE CASCADE;

--
-- Constraints for table `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `kelas_ibfk_1` FOREIGN KEY (`jurusan_id`) REFERENCES `jurusan` (`id_jurusan`) ON DELETE SET NULL;

--
-- Constraints for table `kelas_dosen`
--
ALTER TABLE `kelas_dosen`
  ADD CONSTRAINT `kelas_dosen_ibfk_1` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE,
  ADD CONSTRAINT `kelas_dosen_ibfk_2` FOREIGN KEY (`dosen_id`) REFERENCES `dosen` (`id_dosen`) ON DELETE CASCADE;

--
-- Constraints for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD CONSTRAINT `mahasiswa_ibfk_1` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id_kelas`) ON DELETE SET NULL,
  ADD CONSTRAINT `mahasiswa_ibfk_2` FOREIGN KEY (`jurusan_id`) REFERENCES `jurusan` (`id_jurusan`) ON DELETE SET NULL;

--
-- Constraints for table `m_ujian`
--
ALTER TABLE `m_ujian`
  ADD CONSTRAINT `m_ujian_ibfk_1` FOREIGN KEY (`id_matkul`) REFERENCES `matkul` (`id_matkul`) ON DELETE CASCADE,
  ADD CONSTRAINT `m_ujian_ibfk_2` FOREIGN KEY (`dosen_id`) REFERENCES `dosen` (`id_dosen`) ON DELETE CASCADE;

--
-- Constraints for table `tb_soal`
--
ALTER TABLE `tb_soal`
  ADD CONSTRAINT `tb_soal_ibfk_1` FOREIGN KEY (`id_matkul`) REFERENCES `matkul` (`id_matkul`) ON DELETE CASCADE,
  ADD CONSTRAINT `tb_soal_ibfk_2` FOREIGN KEY (`dosen_id`) REFERENCES `dosen` (`id_dosen`) ON DELETE CASCADE;

--
-- Constraints for table `users_groups`
--
ALTER TABLE `users_groups`
  ADD CONSTRAINT `users_groups_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_groups_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
