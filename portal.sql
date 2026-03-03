-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 28, 2019 at 07:16 AM
-- Server version: 10.1.38-MariaDB
-- PHP Version: 7.3.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `applicants`
--

CREATE TABLE `applicants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `applicant_u_id` varchar(255) DEFAULT NULL,
  `applicant_user_id` bigint(20) UNSIGNED NOT NULL,
  `applicant_job_title` varchar(255) NOT NULL,
  `applicant_name` varchar(255) NOT NULL,
  `applicant_email` varchar(255) NOT NULL,
  `applicant_postcode` varchar(255) NOT NULL,
  `applicant_phone` bigint(20) NOT NULL,
  `applicant_homePhone` bigint(20) NOT NULL,
  `job_category` varchar(255) NOT NULL,
  `applicant_source` varchar(255) NOT NULL,
  `applicant_cv` varchar(255) DEFAULT NULL,
  `lat` float(10,6) DEFAULT NULL,
  `lng` float(10,6) DEFAULT NULL,
  `is_cv_in_quality` varchar(255) NOT NULL DEFAULT 'no',
  `is_cv_in_quality_clear` varchar(255) NOT NULL DEFAULT 'no',
  `is_CV_sent` varchar(255) NOT NULL DEFAULT 'no',
  `is_CV_reject` varchar(255) DEFAULT 'no',
  `is_interview_confirm` varchar(255) NOT NULL DEFAULT 'no',
  `is_interview_attend` varchar(255) NOT NULL DEFAULT 'no',
  `is_in_crm_request` varchar(255) NOT NULL DEFAULT 'no',
  `is_in_crm_reject` varchar(255) NOT NULL DEFAULT 'no',
  `is_in_crm_request_reject` varchar(255) NOT NULL DEFAULT 'no',
  `is_crm_request_confirm` varchar(255) NOT NULL DEFAULT 'no',
  `is_crm_interview_attended` varchar(255) NOT NULL DEFAULT 'pending',
  `is_in_crm_start_date` varchar(255) NOT NULL DEFAULT 'no',
  `is_in_crm_invoice` varchar(255) NOT NULL DEFAULT 'no',
  `is_in_crm_start_date_hold` varchar(255) NOT NULL DEFAULT 'no',
  `is_in_crm_paid` varchar(255) NOT NULL DEFAULT 'no',
  `is_in_crm_dispute` varchar(255) NOT NULL DEFAULT 'no',
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `updated_at` timestamp(6) NOT NULL DEFAULT '0000-00-00 00:00:00.000000'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `applicants`
--

INSERT INTO `applicants` (`id`, `applicant_u_id`, `applicant_user_id`, `applicant_job_title`, `applicant_name`, `applicant_email`, `applicant_postcode`, `applicant_phone`, `applicant_homePhone`, `job_category`, `applicant_source`, `applicant_cv`, `lat`, `lng`, `is_cv_in_quality`, `is_cv_in_quality_clear`, `is_CV_sent`, `is_CV_reject`, `is_interview_confirm`, `is_interview_attend`, `is_in_crm_request`, `is_in_crm_reject`, `is_in_crm_request_reject`, `is_crm_request_confirm`, `is_crm_interview_attended`, `is_in_crm_start_date`, `is_in_crm_invoice`, `is_in_crm_start_date_hold`, `is_in_crm_paid`, `is_in_crm_dispute`, `status`, `created_at`, `updated_at`) VALUES
(1, 'c4ca4238a0b923820dcc509a6f75849b', 1, 'rgn', 'RGN Applicant', 'app01@mail.com', 'Muir of Ord IV6 7UT, UK', 3245085480, 556600621, 'nurse', 'add new applicant for testing', 'uploads\\image_1558858364.png', 57.518417, -4.461075, 'no', 'no', 'no', 'no', 'no', 'no', 'no', 'no', 'no', 'no', 'pending', 'no', 'no', 'no', 'no', 'no', 'active', '2019-05-26 08:29:05.862339', '2019-05-26 03:29:05.862361'),
(2, 'c81e728d9d4c2f636f067f89cc14862c', 1, 'registered manager', 'Manager Applicant', 'manager@mail.com', 'BH31 7AH', 3245454, 4234567890, 'non-nurse', 'Adding Manager Applicant with another test data', 'uploads\\logo_light_1558858508.png', 50.880901, -1.872968, 'no', 'yes', 'no', 'no', 'no', 'no', 'no', 'no', 'no', 'no', 'pending', 'no', 'no', 'no', 'no', 'yes', 'active', '2019-05-27 21:07:53.209753', '2019-05-27 16:07:53.210030');

-- --------------------------------------------------------

--
-- Table structure for table `applicants_pivot_sales`
--

CREATE TABLE `applicants_pivot_sales` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `applicants_pivot_sales_uid` varchar(255) DEFAULT NULL,
  `applicant_id` bigint(20) UNSIGNED NOT NULL,
  `sales_id` bigint(20) UNSIGNED NOT NULL,
  `is_interested` varchar(255) NOT NULL DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `client_uid` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `client_name` varchar(255) NOT NULL,
  `client_email` varchar(255) NOT NULL,
  `client_postcode` varchar(255) NOT NULL,
  `client_phone` bigint(20) NOT NULL,
  `client_landline` varchar(255) NOT NULL,
  `client_website` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `updated_at` timestamp(6) NOT NULL DEFAULT '0000-00-00 00:00:00.000000'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `client_uid`, `user_id`, `client_name`, `client_email`, `client_postcode`, `client_phone`, `client_landline`, `client_website`, `status`, `created_at`, `updated_at`) VALUES
(1, 'c4ca4238a0b923820dcc509a6f75849b', 1, 'john doe', 'cl@mail.com', 'CF45 3LJ', 123132, '0123213123', 'www.google.com', 'active', '2019-05-26 09:41:30.971704', '2019-05-26 04:41:30.971869');

-- --------------------------------------------------------

--
-- Table structure for table `crm_notes`
--

CREATE TABLE `crm_notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `crm_notes_uid` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `applicant_id` bigint(20) UNSIGNED NOT NULL,
  `sales_id` bigint(20) UNSIGNED NOT NULL,
  `details` longtext NOT NULL,
  `moved_tab_to` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `crm_notes`
--

INSERT INTO `crm_notes` (`id`, `crm_notes_uid`, `user_id`, `applicant_id`, `sales_id`, `details`, `moved_tab_to`, `status`, `created_at`, `updated_at`) VALUES
(1, 'c4ca4238a0b923820dcc509a6f75849b', 1, 2, 1, 'to request', 'cv_sent_request', 'active', '2019-05-27 21:06:03', '2019-05-27 16:06:03'),
(2, 'c81e728d9d4c2f636f067f89cc14862c', 1, 2, 1, 'to confirmation', 'request_confirm', 'active', '2019-05-27 21:06:27', '2019-05-27 16:06:27'),
(3, 'eccbc87e4b5ce2fe28308fd9f2a7baf3', 1, 2, 1, 'to attended!', 'interview_not_attended', 'active', '2019-05-27 21:06:44', '2019-05-27 16:06:43'),
(4, 'a87ff679a2f3e71d9181a67b7542122c', 1, 2, 1, 'attended', 'interview_attended', 'active', '2019-05-27 21:06:58', '2019-05-27 16:06:58'),
(5, 'e4da3b7fbbce2345d7772b0674a318d5', 1, 2, 1, 'strt date', 'start_date', 'active', '2019-05-27 21:07:10', '2019-05-27 16:07:10'),
(6, '1679091c5a880faf6fb5e6087eb1b2dc', 1, 2, 1, 'strt hold', 'start_date_hold', 'active', '2019-05-27 21:07:21', '2019-05-27 16:07:21'),
(7, '8f14e45fceea167a5a36dedd4bea2543', 1, 2, 1, 'retrn', 'start_date_back', 'active', '2019-05-27 21:07:30', '2019-05-27 16:07:30'),
(8, 'c9f0f895fb98ab9159f51fd0297e236d', 1, 2, 1, 'invoices', 'invoice', 'active', '2019-05-27 21:07:40', '2019-05-27 16:07:40'),
(9, '45c48cce2e2d7fbdea1afc51c7c6ad26', 1, 2, 1, 'dispute', 'dispute', 'active', '2019-05-27 21:07:53', '2019-05-27 16:07:53');

-- --------------------------------------------------------

--
-- Table structure for table `crm_rejected_cv`
--

CREATE TABLE `crm_rejected_cv` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `crm_rejected_cv_uid` varchar(255) DEFAULT NULL,
  `applicant_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `crm_note_id` bigint(20) UNSIGNED NOT NULL,
  `reason` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cv_notes`
--

CREATE TABLE `cv_notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `sale_id` bigint(20) UNSIGNED NOT NULL,
  `applicant_id` bigint(20) UNSIGNED NOT NULL,
  `cv_uid` varchar(255) DEFAULT NULL,
  `details` longtext NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `cv_notes`
--

INSERT INTO `cv_notes` (`id`, `user_id`, `sale_id`, `applicant_id`, `cv_uid`, `details`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2, 'c4ca4238a0b923820dcc509a6f75849b', 'cvs in sent', 'active', '2019-05-26 21:08:43', '2019-05-26 16:08:43');

-- --------------------------------------------------------

--
-- Table structure for table `interviews`
--

CREATE TABLE `interviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `interview_uid` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `applicant_id` bigint(20) UNSIGNED NOT NULL,
  `sale_id` bigint(20) UNSIGNED NOT NULL,
  `schedule_time` varchar(255) NOT NULL,
  `schedule_date` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `interviews`
--

INSERT INTO `interviews` (`id`, `interview_uid`, `user_id`, `applicant_id`, `sale_id`, `schedule_time`, `schedule_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 'c4ca4238a0b923820dcc509a6f75849b', 1, 2, 1, '02:06', '29 May, 2019', 'active', '2019-05-27 21:06:15', '2019-05-27 16:06:15');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_resets_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `note_uid` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `client_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `unit_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `office_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `sale_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `note_title` varchar(255) NOT NULL,
  `note_description` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `notes_for_range_applicants`
--

CREATE TABLE `notes_for_range_applicants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `range_uid` varchar(255) DEFAULT NULL,
  `applicants_pivot_sales_id` bigint(20) UNSIGNED NOT NULL,
  `reason` longtext NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `offices`
--

CREATE TABLE `offices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `office_uid` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `office_name` varchar(255) NOT NULL,
  `office_postcode` varchar(255) NOT NULL,
  `office_type` varchar(255) NOT NULL,
  `office_contact_name` varchar(255) NOT NULL,
  `office_contact_phone` bigint(20) NOT NULL,
  `office_contact_landline` varchar(255) NOT NULL,
  `office_email` varchar(255) NOT NULL,
  `office_website` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `updated_at` timestamp(6) NOT NULL DEFAULT '0000-00-00 00:00:00.000000'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `offices`
--

INSERT INTO `offices` (`id`, `office_uid`, `user_id`, `office_name`, `office_postcode`, `office_type`, `office_contact_name`, `office_contact_phone`, `office_contact_landline`, `office_email`, `office_website`, `status`, `created_at`, `updated_at`) VALUES
(1, 'c4ca4238a0b923820dcc509a6f75849b', 1, 'Morocco Branch', 'Bath BA2 6EN, UK', 'psl', 'Morocco White', 123456, '067890', 'mwhite@gmail.com', 'www.white.com', 'active', '2019-05-26 09:55:53.296700', '2019-05-26 04:55:53.296227'),
(2, 'c81e728d9d4c2f636f067f89cc14862c', 1, 'Morocco Branch clone', 'Bath BA2 6EN, UK', 'non psl', 'test name', 123123, '012313123', 'tr@mail.com', 'ibstec.com', 'active', '2019-05-26 10:01:16.894467', '2019-05-26 05:01:16.830768');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quality_notes`
--

CREATE TABLE `quality_notes` (
  `id` bigint(20) NOT NULL,
  `quality_notes_uid` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `applicant_id` bigint(20) UNSIGNED NOT NULL,
  `sale_id` bigint(20) UNSIGNED NOT NULL,
  `details` longtext NOT NULL,
  `moved_tab_to` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `quality_notes`
--

INSERT INTO `quality_notes` (`id`, `quality_notes_uid`, `user_id`, `applicant_id`, `sale_id`, `details`, `moved_tab_to`, `status`, `created_at`, `updated_at`) VALUES
(2, 'c81e728d9d4c2f636f067f89cc14862c', 1, 2, 1, 'acleared', 'cleared', 'active', '2019-05-26 21:40:16', '2019-05-26 16:40:16');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sale_uid` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `head_office` bigint(20) NOT NULL,
  `head_office_unit` bigint(20) NOT NULL,
  `job_category` varchar(255) NOT NULL,
  `job_title` varchar(255) NOT NULL,
  `postcode` varchar(255) NOT NULL,
  `job_type` varchar(255) NOT NULL,
  `timing` varchar(255) NOT NULL,
  `salary` varchar(255) NOT NULL,
  `experience` varchar(255) NOT NULL,
  `qualification` varchar(255) NOT NULL,
  `benefits` longtext NOT NULL,
  `posted_date` date NOT NULL,
  `lat` float(10,6) DEFAULT NULL,
  `lng` float(10,6) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `updated_at` timestamp(6) NOT NULL DEFAULT '0000-00-00 00:00:00.000000'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `sale_uid`, `user_id`, `head_office`, `head_office_unit`, `job_category`, `job_title`, `postcode`, `job_type`, `timing`, `salary`, `experience`, `qualification`, `benefits`, `posted_date`, `lat`, `lng`, `status`, `created_at`, `updated_at`) VALUES
(1, 'c4ca4238a0b923820dcc509a6f75849b', 1, 1, 1, 'nurse', 'nurse manager', 'BH16 5NJ', 'part time', '45rtg', 'sadads', '2 years', 'RGN/ RMN', 'testing for opening sale', '2019-05-26', 53.478348, -1.307420, 'active', '2019-05-26 19:22:58.495614', '2019-05-26 14:22:58.480396'),
(2, 'c81e728d9d4c2f636f067f89cc14862c', 1, 2, 2, 'nonnurse', 'deputy manager', 'Dorchester Rd, Poole BH16 5NJ, UK', 'full time', '11:10am', '45 pounds an hour', '2 years in a nursing home', 'bscs', 'testing for antoher', '2019-05-26', 50.738804, -2.031138, 'active', '2019-05-26 19:31:39.222582', '2019-05-26 14:31:39.128864'),
(3, 'eccbc87e4b5ce2fe28308fd9f2a7baf3', 1, 1, 1, 'nurse', 'rmn/rnld', 'High St, Langton Matravers, Swanage BH19 3HB, UK', 'full time', '11:10am', '16 pounds an hour', '3 years', 'bscs', 'asdasdasd', '2019-05-26', 50.609482, -2.003477, 'active', '2019-05-26 19:32:26.362791', '2019-05-26 14:32:26.117881');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `unit_uid` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `head_office` varchar(255) NOT NULL,
  `unit_name` varchar(255) NOT NULL,
  `unit_postcode` varchar(255) NOT NULL,
  `contact_name` varchar(255) NOT NULL,
  `contact_phone_number` bigint(20) NOT NULL,
  `contact_landline` varchar(255) NOT NULL,
  `contact_email` varchar(255) NOT NULL,
  `website` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `updated_at` timestamp(6) NOT NULL DEFAULT '0000-00-00 00:00:00.000000'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `unit_uid`, `user_id`, `head_office`, `unit_name`, `unit_postcode`, `contact_name`, `contact_phone_number`, `contact_landline`, `contact_email`, `website`, `status`, `created_at`, `updated_at`) VALUES
(1, 'c4ca4238a0b923820dcc509a6f75849b', 1, '1', 'Morocco Manager', 'Swainswick Gardens, Bath BA1 6TL, UK', 'Unit Manager', 12312312, '012312312', 'a@mail.com', 'www.white.com', 'active', '2019-05-26 10:24:44.216010', '2019-05-26 05:13:11.467795'),
(2, 'c81e728d9d4c2f636f067f89cc14862c', 1, '2', 'clone manager', 'Lymore Ave, Bath BA2 1AY, UK', 'Unit Manager2', 12312, '0121312', 'dd@mail.com', 'www.white.com', 'active', '2019-05-26 10:16:01.129145', '2019-05-26 05:16:00.940201');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'developers@ibstec.com', NULL, '$2y$10$Y78vMMlamKMkT1krRSgbU.KnSzBC4.WCgqZmCheiOeuHYq7jPvDKm', NULL, '2019-03-06 13:26:31', '2019-03-06 13:26:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applicants`
--
ALTER TABLE `applicants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `applicant_user_id` (`applicant_user_id`);

--
-- Indexes for table `applicants_pivot_sales`
--
ALTER TABLE `applicants_pivot_sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `applicant_id` (`applicant_id`),
  ADD KEY `sales_id` (`sales_id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `crm_notes`
--
ALTER TABLE `crm_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `applicant_id` (`applicant_id`),
  ADD KEY `sales_id` (`sales_id`);

--
-- Indexes for table `crm_rejected_cv`
--
ALTER TABLE `crm_rejected_cv`
  ADD PRIMARY KEY (`id`),
  ADD KEY `applicant_id` (`applicant_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `crm_note_id` (`crm_note_id`);

--
-- Indexes for table `cv_notes`
--
ALTER TABLE `cv_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `job_id` (`sale_id`),
  ADD KEY `applicant_id` (`applicant_id`);

--
-- Indexes for table `interviews`
--
ALTER TABLE `interviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `applicant_id` (`applicant_id`),
  ADD KEY `sale_id` (`sale_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `client_id` (`client_id`),
  ADD KEY `notes_appl_fk` (`user_id`),
  ADD KEY `unit_id` (`unit_id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `office_id` (`office_id`);

--
-- Indexes for table `notes_for_range_applicants`
--
ALTER TABLE `notes_for_range_applicants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `applicants_pivot_sales_id` (`applicants_pivot_sales_id`);

--
-- Indexes for table `offices`
--
ALTER TABLE `offices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `quality_notes`
--
ALTER TABLE `quality_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `applicant_id` (`applicant_id`),
  ADD KEY `sale_id` (`sale_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `applicants_pivot_sales`
--
ALTER TABLE `applicants_pivot_sales`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `crm_notes`
--
ALTER TABLE `crm_notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `crm_rejected_cv`
--
ALTER TABLE `crm_rejected_cv`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cv_notes`
--
ALTER TABLE `cv_notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `interviews`
--
ALTER TABLE `interviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notes_for_range_applicants`
--
ALTER TABLE `notes_for_range_applicants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `quality_notes`
--
ALTER TABLE `quality_notes`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applicants`
--
ALTER TABLE `applicants`
  ADD CONSTRAINT `applicant_user_fk` FOREIGN KEY (`applicant_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `applicants_pivot_sales`
--
ALTER TABLE `applicants_pivot_sales`
  ADD CONSTRAINT `applicants_pivot_sales_to_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`),
  ADD CONSTRAINT `applicants_pivot_sales_to_sales` FOREIGN KEY (`sales_id`) REFERENCES `sales` (`id`);

--
-- Constraints for table `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `clients_users_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `crm_notes`
--
ALTER TABLE `crm_notes`
  ADD CONSTRAINT `crm_notes_to_sale_fk_key` FOREIGN KEY (`sales_id`) REFERENCES `sales` (`id`),
  ADD CONSTRAINT `crm_to_applicant_fk` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`),
  ADD CONSTRAINT `crm_to_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `crm_rejected_cv`
--
ALTER TABLE `crm_rejected_cv`
  ADD CONSTRAINT `rejected_to_applicant_fk` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`),
  ADD CONSTRAINT `rejected_to_crm_note_fk` FOREIGN KEY (`crm_note_id`) REFERENCES `crm_notes` (`id`),
  ADD CONSTRAINT `rejected_to_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `cv_notes`
--
ALTER TABLE `cv_notes`
  ADD CONSTRAINT `note_applicant_fk` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`),
  ADD CONSTRAINT `note_sale_fk` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  ADD CONSTRAINT `note_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `interviews`
--
ALTER TABLE `interviews`
  ADD CONSTRAINT `interviews_applicant_fk` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`),
  ADD CONSTRAINT `interviews_sale_fk` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  ADD CONSTRAINT `interviews_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_appl_fk` FOREIGN KEY (`user_id`) REFERENCES `applicants` (`id`),
  ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`),
  ADD CONSTRAINT `notes_ibfk_3` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  ADD CONSTRAINT `notes_ibfk_4` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`);

--
-- Constraints for table `notes_for_range_applicants`
--
ALTER TABLE `notes_for_range_applicants`
  ADD CONSTRAINT `applicants_pivot_sales_to_range_notes_fk` FOREIGN KEY (`applicants_pivot_sales_id`) REFERENCES `applicants_pivot_sales` (`id`);

--
-- Constraints for table `offices`
--
ALTER TABLE `offices`
  ADD CONSTRAINT `office_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `quality_notes`
--
ALTER TABLE `quality_notes`
  ADD CONSTRAINT `quality_to_applicant_fk` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`),
  ADD CONSTRAINT `quality_to_sale_fk` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  ADD CONSTRAINT `quality_to_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `units`
--
ALTER TABLE `units`
  ADD CONSTRAINT `units_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
