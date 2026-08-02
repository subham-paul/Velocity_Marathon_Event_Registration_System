-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 02, 2026 at 05:53 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `marathon_live_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int UNSIGNED NOT NULL,
  `username` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Administrator',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`, `full_name`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2y$10$q7LHDdYnoB.1I2n0NF35guHRixF2.J9Yd3il21GbVfsUeyXpS8VtK', 'Marathon Admin', '2026-08-02 11:23:34', '2026-07-13 15:58:49');

-- --------------------------------------------------------

--
-- Table structure for table `otp_verifications`
--

CREATE TABLE `otp_verifications` (
  `id` int UNSIGNED NOT NULL,
  `token` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `otp_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `resend_count` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `verified` tinyint(1) NOT NULL DEFAULT '0',
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `otp_verifications`
--

INSERT INTO `otp_verifications` (`id`, `token`, `email`, `otp_hash`, `payload`, `attempts`, `resend_count`, `verified`, `expires_at`, `created_at`) VALUES
(11, '693ed93852a770e6c47ce87be172d7d43e8698b5ecd8d4b3ec44e19c4dffef80', 'subhampaul@gmail.com', '$2y$10$7YgxYTC80ZTsIBOPpGxFu..Rq7QWZO33Hctc2eWowk2yCfiY.37MO', '{\"dob\": \"2006-05-20\", \"city\": \"Kolkata\", \"email\": \"subhampaul@gmail.com\", \"phone\": \"9433340388\", \"state\": \"West Bengal\", \"gender\": \"Male\", \"address\": \"Sethbagan\", \"category\": \"5K Fun Run\", \"last_name\": \"Paul\", \"first_name\": \"Subham\", \"blood_group\": \"A+\", \"tshirt_size\": \"S\", \"emergency_name\": \"Subham Paul\", \"emergency_phone\": \"9433340387\"}', 0, 0, 0, '2026-08-02 01:03:49', '2026-08-01 19:23:49');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int UNSIGNED NOT NULL,
  `order_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signature` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(130) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` int UNSIGNED NOT NULL COMMENT 'in paise',
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'INR',
  `status` enum('created','paid','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'created',
  `reg_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `paid_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `payment_id`, `signature`, `token`, `email`, `name`, `category`, `amount`, `currency`, `status`, `reg_id`, `created_at`, `paid_at`) VALUES
(3, 'order_TDIuUduG5HvmE3', 'pay_TDIunhFhq1kryc', '588d3064c6d4afcb26edfea915ed55a5f42d9d64c601c4dd61fe3241ab58d0a0', 'e574e561d6f233b81546e2ecb1dcb06aa3167d8bc7917324e31ec8c782eca63c', 'subhampa77@gmail.com', 'Subham Paul', '5K Fun Run', 49900, 'INR', 'paid', 'VM26-NTM69P', '2026-07-14 07:42:09', '2026-07-14 13:12:45'),
(4, 'order_TDJ3DtaQpwpIfj', 'pay_TDJ3OzUtc8iVHf', 'f62c1b125e093eccc3fa8d34f16644a24b5a6c543a3326f72a6ab5fb513432b6', '076bddc306ee5acea5008d122b49cab50563d5317909a311402379b3006a509a', 'surajitchakraborty823@gmail.com', 'Surajit Chakraborty', '42K Full Marathon', 169900, 'INR', 'paid', 'VM26-VB9Q6U', '2026-07-14 07:50:25', '2026-07-14 13:20:52'),
(5, 'order_TKbmsAvn8KoZ2s', 'pay_TKbnESlPfELihQ', 'a535fbf3b3e163d8ca5a9dda1d01049b3c387c7f5438f597c38775f37b080917', '78a11a1e6004da5c8d10e348f5d4113d501639b83bc3219c03dae2bfd0b5e2ac', 'subham2paul@gmail.com', 'Subham Paul', '10K Challenge', 79900, 'INR', 'paid', 'VM26-4L6BXA', '2026-08-01 18:43:08', '2026-08-02 00:13:47');

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE `registrations` (
  `id` int UNSIGNED NOT NULL,
  `reg_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` enum('Male','Female','Other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `dob` date NOT NULL,
  `category` enum('5K Fun Run','10K Challenge','21K Half Marathon','42K Full Marathon') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tshirt_size` enum('XS','S','M','L','XL','XXL') COLLATE utf8mb4_unicode_ci NOT NULL,
  `blood_group` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `emergency_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `emergency_phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qr_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `status` enum('confirmed','checked_in') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'confirmed',
  `checked_in_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `registrations`
--

INSERT INTO `registrations` (`id`, `reg_id`, `first_name`, `last_name`, `email`, `phone`, `gender`, `dob`, `category`, `tshirt_size`, `blood_group`, `emergency_name`, `emergency_phone`, `city`, `state`, `address`, `qr_path`, `status`, `checked_in_at`, `created_at`) VALUES
(2, 'VM26-VPT5TM', 'Subham', 'Paul', 'subhampaul456@gmail.com', '9433340388', 'Male', '2004-03-19', '5K Fun Run', 'S', 'A+', 'Subham Paul', '9433340377', 'Kolkata', 'West Bengal', 'Sethbagan', 'uploads/qrcodes/VM26-VPT5TM.png', 'checked_in', '2026-07-13 22:42:15', '2026-07-13 16:13:43'),
(4, 'VM26-LC9UD6', 'Subham', 'Paul', 'bppcs.11500118002@gmail.com', '9433340388', 'Male', '2000-02-20', '21K Half Marathon', 'XXL', 'A+', 'Subham Paul', '9433340321', 'Kolkata', 'West Bengal', 'Sethbagan', 'uploads/qrcodes/VM26-LC9UD6.png', 'confirmed', NULL, '2026-07-13 17:09:44'),
(8, 'VM26-VB9Q6U', 'Surajit', 'Chakraborty', 'surajitchakraborty823@gmail.com', '9658741236', 'Male', '2005-06-20', '42K Full Marathon', 'XXL', 'O+', 'SUBHAM PAUL', '9433340388', 'Kolkata', 'West Bengal', '45/5 Seth Bagan Road, Dum Dum', 'uploads/qrcodes/VM26-VB9Q6U.png', 'confirmed', NULL, '2026-07-14 07:50:52'),
(9, 'VM26-4L6BXA', 'Subham', 'Paul', 'subham2paul@gmail.com', '9433340388', 'Male', '2005-05-20', '10K Challenge', 'S', 'A+', 'Sayan Das', '9658741236', 'Kolkata', 'West Bengal', 'Seth Bagan Road, Dum Dum', 'uploads/qrcodes/VM26-4L6BXA.png', 'checked_in', '2026-08-02 00:18:48', '2026-08-01 18:43:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_reg` (`reg_id`);

--
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reg_id` (`reg_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_gender` (`gender`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
