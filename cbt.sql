-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 08, 2025 at 03:56 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 7.4.33

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
-- Table structure for table `d_ujian_soal`
--

CREATE TABLE `d_ujian_soal` (
  `id_d_ujian_soal` int(10) UNSIGNED NOT NULL,
  `id_ujian` int(11) NOT NULL,
  `id_soal` int(10) UNSIGNED NOT NULL,
  `nomor_urut` int(10) UNSIGNED NOT NULL COMMENT 'Nomor urut soal dalam ujian ini',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabel detail soal untuk setiap ujian';

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`id`, `name`, `description`) VALUES
(1, 'admin', 'Administrator'),
(2, 'guru', 'Guru Pengajar'),
(3, 'siswa', 'Siswa Peserta Ujian');

-- --------------------------------------------------------

--
-- Table structure for table `guru`
--

CREATE TABLE `guru` (
  `id_guru` int(11) NOT NULL,
  `nip` varchar(20) NOT NULL,
  `nama_guru` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guru`
--

INSERT INTO `guru` (`id_guru`, `nip`, `nama_guru`, `email`) VALUES
(19, '254156586525185268', 'Anggi Aisyadatina, M.Pd.', 'anggi@smpicsamarinda.sch.id'),
(23, '654125486585452156', 'Putro Setyoko, S.Kom.', 'putro@smpicsamarinda.sch.id');

-- --------------------------------------------------------

--
-- Table structure for table `guru_mapel_kelas_ajaran`
--

CREATE TABLE `guru_mapel_kelas_ajaran` (
  `id_gmka` int(11) NOT NULL,
  `guru_id` int(11) NOT NULL,
  `mapel_id` int(11) NOT NULL,
  `kelas_id` int(11) NOT NULL,
  `id_tahun_ajaran` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `h_ujian`
--

CREATE TABLE `h_ujian` (
  `id` int(11) NOT NULL,
  `ujian_id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `list_soal` text NOT NULL COMMENT 'JSON array dari id_soal dalam urutan pengerjaan',
  `list_jawaban` text DEFAULT NULL COMMENT 'JSON array jawaban siswa, urutan sesuai list_soal',
  `jml_benar` int(11) DEFAULT NULL COMMENT 'Bisa diartikan total poin jika pakai bobot',
  `nilai_bobot` int(11) DEFAULT NULL COMMENT 'Total bobot maksimal dari soal yang dikerjakan',
  `nilai` decimal(5,2) DEFAULT NULL COMMENT 'Nilai akhir 0-100',
  `tgl_mulai` datetime DEFAULT NULL,
  `tgl_selesai` datetime DEFAULT NULL,
  `status` enum('completed','unfinished','expired','sedang_dikerjakan') DEFAULT 'unfinished'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jenjang`
--

CREATE TABLE `jenjang` (
  `id_jenjang` int(11) NOT NULL,
  `nama_jenjang` varchar(50) NOT NULL COMMENT 'Contoh: Kelas 7, Kelas 8, Kelas 9',
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jenjang`
--

INSERT INTO `jenjang` (`id_jenjang`, `nama_jenjang`, `deskripsi`) VALUES
(15, 'VII', ''),
(16, 'VIII', ''),
(18, 'IX', '');

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id_kelas` int(11) NOT NULL,
  `nama_kelas` varchar(50) NOT NULL,
  `id_jenjang` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `login` varchar(100) DEFAULT NULL,
  `time` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mapel`
--

CREATE TABLE `mapel` (
  `id_mapel` int(11) NOT NULL,
  `nama_mapel` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mapel`
--

INSERT INTO `mapel` (`id_mapel`, `nama_mapel`) VALUES
(17, 'Bahasa Arab'),
(9, 'Bahasa Indonesia'),
(13, 'Bahasa Inggris'),
(11, 'Ilmu Pengetahuan Alam '),
(12, 'Ilmu Pengetahuan Sosial '),
(15, 'Informatika'),
(10, 'Matematika'),
(7, 'Pendidikan Agama Islam dan Budi Pekerti '),
(14, 'Pendidikan Jasmani, Olahraga dan Kesehatan '),
(8, 'Pendidikan Pancasila'),
(16, 'Prakarya'),
(19, 'Tahfidz'),
(18, 'Tahsin');

-- --------------------------------------------------------

--
-- Table structure for table `m_ujian`
--

CREATE TABLE `m_ujian` (
  `id_ujian` int(11) NOT NULL,
  `token` varchar(6) DEFAULT NULL,
  `nama_ujian` varchar(100) NOT NULL,
  `mapel_id` int(11) NOT NULL,
  `guru_id` int(11) NOT NULL,
  `id_tahun_ajaran` int(11) DEFAULT NULL,
  `id_jenjang_target` int(11) DEFAULT NULL COMMENT 'Jenjang yang menjadi target ujian ini',
  `jumlah_soal` int(11) NOT NULL,
  `waktu` int(11) NOT NULL COMMENT 'durasi dalam menit',
  `acak_soal` enum('Y','N') DEFAULT 'N',
  `acak_opsi` enum('Y','N') DEFAULT 'N',
  `aktif` enum('Y','N') DEFAULT 'Y',
  `tgl_mulai` datetime DEFAULT NULL,
  `terlambat` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penanggung_jawab_soal_ajaran`
--

CREATE TABLE `penanggung_jawab_soal_ajaran` (
  `id_pjsa` int(11) NOT NULL,
  `mapel_id` int(11) NOT NULL,
  `guru_id` int(11) NOT NULL,
  `id_tahun_ajaran` int(11) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `ditetapkan_pada` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id_siswa` int(11) NOT NULL,
  `nisn` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jenis_kelamin` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id_siswa`, `nisn`, `nama`, `jenis_kelamin`) VALUES
(12, '115796650', 'Afifatunaja Zarien Kholisul Maulana', 'Perempuan'),
(13, '124563076', 'Aira Nur Malika Putri', 'Perempuan');

-- --------------------------------------------------------

--
-- Table structure for table `siswa_kelas_ajaran`
--

CREATE TABLE `siswa_kelas_ajaran` (
  `id_ska` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `kelas_id` int(11) NOT NULL,
  `id_tahun_ajaran` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tahun_ajaran`
--

CREATE TABLE `tahun_ajaran` (
  `id_tahun_ajaran` int(11) NOT NULL,
  `nama_tahun_ajaran` varchar(50) NOT NULL COMMENT 'Contoh: 2024/2025 Ganjil, 2024/2025 Genap',
  `semester` enum('Ganjil','Genap') NOT NULL,
  `tgl_mulai` date NOT NULL,
  `tgl_selesai` date NOT NULL,
  `status` enum('aktif','tidak_aktif') NOT NULL DEFAULT 'tidak_aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tahun_ajaran`
--

INSERT INTO `tahun_ajaran` (`id_tahun_ajaran`, `nama_tahun_ajaran`, `semester`, `tgl_mulai`, `tgl_selesai`, `status`, `created_at`, `updated_at`) VALUES
(1, '2025/2026 Ganjil', 'Ganjil', '2025-07-14', '2025-12-20', 'aktif', '2025-05-30 09:50:03', '2025-06-07 14:32:14');

-- --------------------------------------------------------

--
-- Table structure for table `tb_soal`
--

CREATE TABLE `tb_soal` (
  `id_soal` int(11) UNSIGNED NOT NULL,
  `mapel_id` int(11) NOT NULL,
  `id_jenjang` int(11) DEFAULT NULL,
  `guru_id` int(11) NOT NULL,
  `soal` text NOT NULL,
  `file` varchar(255) DEFAULT NULL,
  `tipe_file` varchar(50) DEFAULT NULL,
  `opsi_a` text DEFAULT NULL,
  `opsi_b` text DEFAULT NULL,
  `opsi_c` text DEFAULT NULL,
  `opsi_d` text DEFAULT NULL,
  `opsi_e` text DEFAULT NULL,
  `file_a` varchar(255) DEFAULT NULL,
  `file_b` varchar(255) DEFAULT NULL,
  `file_c` varchar(255) DEFAULT NULL,
  `file_d` varchar(255) DEFAULT NULL,
  `file_e` varchar(255) DEFAULT NULL,
  `jawaban` enum('A','B','C','D','E') NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `created_on` int(11) UNSIGNED DEFAULT NULL,
  `updated_on` int(11) UNSIGNED DEFAULT NULL,
  `bobot` int(5) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `ip_address` varbinary(16) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `activation_selector` varchar(255) DEFAULT NULL,
  `activation_code` varchar(255) DEFAULT NULL,
  `forgotten_password_selector` varchar(255) DEFAULT NULL,
  `forgotten_password_code` varchar(255) DEFAULT NULL,
  `forgotten_password_time` datetime DEFAULT NULL,
  `remember_selector` varchar(255) DEFAULT NULL,
  `remember_code` varchar(255) DEFAULT NULL,
  `created_on` int(11) UNSIGNED NOT NULL,
  `last_login` int(11) UNSIGNED DEFAULT NULL,
  `active` tinyint(1) UNSIGNED DEFAULT 0,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `ip_address`, `username`, `password`, `email`, `activation_selector`, `activation_code`, `forgotten_password_selector`, `forgotten_password_code`, `forgotten_password_time`, `remember_selector`, `remember_code`, `created_on`, `last_login`, `active`, `first_name`, `last_name`, `company`, `phone`) VALUES
(3, 0x3a3a31, 'admin', '$2y$12$WItqyPN6/Mz8ZJoSdx7QtuWujT3xoaNarIhgAxZmg4W8OnlisNTJW', 'admin@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1748521982, 1749347545, 1, 'Super', 'Admin', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users_groups`
--

CREATE TABLE `users_groups` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `group_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users_groups`
--

INSERT INTO `users_groups` (`id`, `user_id`, `group_id`) VALUES
(3, 3, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `d_ujian_soal`
--
ALTER TABLE `d_ujian_soal`
  ADD PRIMARY KEY (`id_d_ujian_soal`),
  ADD UNIQUE KEY `uq_ujian_soal` (`id_ujian`,`id_soal`),
  ADD UNIQUE KEY `uq_ujian_nomor_urut` (`id_ujian`,`nomor_urut`),
  ADD KEY `fk_d_ujian_soal_soal` (`id_soal`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `guru`
--
ALTER TABLE `guru`
  ADD PRIMARY KEY (`id_guru`),
  ADD UNIQUE KEY `nip` (`nip`),
  ADD UNIQUE KEY `email_guru_unik` (`email`);

--
-- Indexes for table `guru_mapel_kelas_ajaran`
--
ALTER TABLE `guru_mapel_kelas_ajaran`
  ADD PRIMARY KEY (`id_gmka`),
  ADD UNIQUE KEY `guru_mapel_kelas_per_ajaran` (`guru_id`,`mapel_id`,`kelas_id`,`id_tahun_ajaran`),
  ADD KEY `fk_gmka_guru_idx` (`guru_id`),
  ADD KEY `fk_gmka_mapel_idx` (`mapel_id`),
  ADD KEY `fk_gmka_kelas_idx` (`kelas_id`),
  ADD KEY `fk_gmka_tahun_ajaran_idx` (`id_tahun_ajaran`);

--
-- Indexes for table `h_ujian`
--
ALTER TABLE `h_ujian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_h_ujian_ujian_idx` (`ujian_id`),
  ADD KEY `fk_h_ujian_siswa_idx` (`siswa_id`);

--
-- Indexes for table `jenjang`
--
ALTER TABLE `jenjang`
  ADD PRIMARY KEY (`id_jenjang`),
  ADD UNIQUE KEY `nama_jenjang_unik` (`nama_jenjang`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id_kelas`),
  ADD UNIQUE KEY `nama_kelas` (`nama_kelas`),
  ADD KEY `fk_kelas_jenjang_idx` (`id_jenjang`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mapel`
--
ALTER TABLE `mapel`
  ADD PRIMARY KEY (`id_mapel`),
  ADD UNIQUE KEY `nama_mapel_unik` (`nama_mapel`);

--
-- Indexes for table `m_ujian`
--
ALTER TABLE `m_ujian`
  ADD PRIMARY KEY (`id_ujian`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `fk_m_ujian_mapel_idx` (`mapel_id`),
  ADD KEY `fk_m_ujian_guru_idx` (`guru_id`),
  ADD KEY `fk_m_ujian_tahun_ajaran_idx` (`id_tahun_ajaran`),
  ADD KEY `fk_m_ujian_jenjang_target` (`id_jenjang_target`);

--
-- Indexes for table `penanggung_jawab_soal_ajaran`
--
ALTER TABLE `penanggung_jawab_soal_ajaran`
  ADD PRIMARY KEY (`id_pjsa`),
  ADD UNIQUE KEY `mapel_tahun_unik_pj` (`mapel_id`,`id_tahun_ajaran`),
  ADD UNIQUE KEY `guru_tahun_unik_pj` (`guru_id`,`id_tahun_ajaran`),
  ADD KEY `fk_pjsa_mapel_idx` (`mapel_id`),
  ADD KEY `fk_pjsa_guru_idx` (`guru_id`),
  ADD KEY `fk_pjsa_tahun_ajaran_idx` (`id_tahun_ajaran`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id_siswa`),
  ADD UNIQUE KEY `nisn_unik` (`nisn`);

--
-- Indexes for table `siswa_kelas_ajaran`
--
ALTER TABLE `siswa_kelas_ajaran`
  ADD PRIMARY KEY (`id_ska`),
  ADD UNIQUE KEY `siswa_kelas_per_ajaran` (`siswa_id`,`id_tahun_ajaran`),
  ADD KEY `fk_ska_siswa_idx` (`siswa_id`),
  ADD KEY `fk_ska_kelas_idx` (`kelas_id`),
  ADD KEY `fk_ska_tahun_ajaran_idx` (`id_tahun_ajaran`);

--
-- Indexes for table `tahun_ajaran`
--
ALTER TABLE `tahun_ajaran`
  ADD PRIMARY KEY (`id_tahun_ajaran`),
  ADD UNIQUE KEY `nama_tahun_ajaran_unik` (`nama_tahun_ajaran`);

--
-- Indexes for table `tb_soal`
--
ALTER TABLE `tb_soal`
  ADD PRIMARY KEY (`id_soal`),
  ADD KEY `fk_soal_mapel_idx` (`mapel_id`),
  ADD KEY `fk_soal_guru_idx` (`guru_id`),
  ADD KEY `fk_soal_jenjang_idx` (`id_jenjang`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `users_groups`
--
ALTER TABLE `users_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_group_unique` (`user_id`,`group_id`),
  ADD KEY `fk_users_groups_users1_idx` (`user_id`),
  ADD KEY `fk_users_groups_groups1_idx` (`group_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `d_ujian_soal`
--
ALTER TABLE `d_ujian_soal`
  MODIFY `id_d_ujian_soal` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `guru`
--
ALTER TABLE `guru`
  MODIFY `id_guru` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `guru_mapel_kelas_ajaran`
--
ALTER TABLE `guru_mapel_kelas_ajaran`
  MODIFY `id_gmka` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT for table `h_ujian`
--
ALTER TABLE `h_ujian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jenjang`
--
ALTER TABLE `jenjang`
  MODIFY `id_jenjang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id_kelas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mapel`
--
ALTER TABLE `mapel`
  MODIFY `id_mapel` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `m_ujian`
--
ALTER TABLE `m_ujian`
  MODIFY `id_ujian` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `penanggung_jawab_soal_ajaran`
--
ALTER TABLE `penanggung_jawab_soal_ajaran`
  MODIFY `id_pjsa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id_siswa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `siswa_kelas_ajaran`
--
ALTER TABLE `siswa_kelas_ajaran`
  MODIFY `id_ska` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `tahun_ajaran`
--
ALTER TABLE `tahun_ajaran`
  MODIFY `id_tahun_ajaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `tb_soal`
--
ALTER TABLE `tb_soal`
  MODIFY `id_soal` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `users_groups`
--
ALTER TABLE `users_groups`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `d_ujian_soal`
--
ALTER TABLE `d_ujian_soal`
  ADD CONSTRAINT `fk_d_ujian_soal_soal` FOREIGN KEY (`id_soal`) REFERENCES `tb_soal` (`id_soal`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_d_ujian_soal_ujian` FOREIGN KEY (`id_ujian`) REFERENCES `m_ujian` (`id_ujian`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `guru_mapel_kelas_ajaran`
--
ALTER TABLE `guru_mapel_kelas_ajaran`
  ADD CONSTRAINT `fk_gmka_guru` FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id_guru`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gmka_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gmka_mapel` FOREIGN KEY (`mapel_id`) REFERENCES `mapel` (`id_mapel`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gmka_tahun_ajaran` FOREIGN KEY (`id_tahun_ajaran`) REFERENCES `tahun_ajaran` (`id_tahun_ajaran`) ON DELETE CASCADE;

--
-- Constraints for table `h_ujian`
--
ALTER TABLE `h_ujian`
  ADD CONSTRAINT `fk_h_ujian_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_h_ujian_ujian` FOREIGN KEY (`ujian_id`) REFERENCES `m_ujian` (`id_ujian`) ON DELETE CASCADE;

--
-- Constraints for table `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `fk_kelas_jenjang` FOREIGN KEY (`id_jenjang`) REFERENCES `jenjang` (`id_jenjang`) ON DELETE SET NULL ON UPDATE NO ACTION;

--
-- Constraints for table `m_ujian`
--
ALTER TABLE `m_ujian`
  ADD CONSTRAINT `fk_m_ujian_guru` FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id_guru`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_m_ujian_jenjang_target` FOREIGN KEY (`id_jenjang_target`) REFERENCES `jenjang` (`id_jenjang`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_m_ujian_mapel` FOREIGN KEY (`mapel_id`) REFERENCES `mapel` (`id_mapel`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_m_ujian_tahun_ajaran` FOREIGN KEY (`id_tahun_ajaran`) REFERENCES `tahun_ajaran` (`id_tahun_ajaran`) ON DELETE SET NULL;

--
-- Constraints for table `penanggung_jawab_soal_ajaran`
--
ALTER TABLE `penanggung_jawab_soal_ajaran`
  ADD CONSTRAINT `fk_pjsa_guru` FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id_guru`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pjsa_mapel` FOREIGN KEY (`mapel_id`) REFERENCES `mapel` (`id_mapel`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pjsa_tahun_ajaran` FOREIGN KEY (`id_tahun_ajaran`) REFERENCES `tahun_ajaran` (`id_tahun_ajaran`) ON DELETE CASCADE;

--
-- Constraints for table `siswa_kelas_ajaran`
--
ALTER TABLE `siswa_kelas_ajaran`
  ADD CONSTRAINT `fk_ska_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ska_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ska_tahun_ajaran` FOREIGN KEY (`id_tahun_ajaran`) REFERENCES `tahun_ajaran` (`id_tahun_ajaran`) ON DELETE CASCADE;

--
-- Constraints for table `tb_soal`
--
ALTER TABLE `tb_soal`
  ADD CONSTRAINT `fk_soal_guru` FOREIGN KEY (`guru_id`) REFERENCES `guru` (`id_guru`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_soal_jenjang` FOREIGN KEY (`id_jenjang`) REFERENCES `jenjang` (`id_jenjang`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_soal_mapel` FOREIGN KEY (`mapel_id`) REFERENCES `mapel` (`id_mapel`) ON DELETE CASCADE;

--
-- Constraints for table `users_groups`
--
ALTER TABLE `users_groups`
  ADD CONSTRAINT `fk_users_groups_groups1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_users_groups_users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
