-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 08, 2026 at 09:24 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `corms_upadate`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `organization` varchar(255) DEFAULT NULL,
  `date` date NOT NULL,
  `venue` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `participants_count` int(11) DEFAULT NULL,
  `communication_letter` varchar(255) DEFAULT NULL,
  `narrative_report` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `term` varchar(255) DEFAULT NULL,
  `school_year` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `basis_grading` varchar(255) DEFAULT NULL,
  `reject_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_reports`
--

CREATE TABLE `activity_reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `activity_request_id` bigint(20) UNSIGNED NOT NULL,
  `narrative_report` varchar(255) NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_requests`
--

CREATE TABLE `activity_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `gpoa_activity_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `venue` varchar(255) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `participants_count` int(11) DEFAULT NULL,
  `communication_letter` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `reject_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gpoas`
--

CREATE TABLE `gpoas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `term` varchar(255) NOT NULL,
  `school_year` varchar(255) NOT NULL,
  `college` varchar(255) DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `stored_at` timestamp NULL DEFAULT NULL,
  `reject_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gpoa_activities`
--

CREATE TABLE `gpoa_activities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `gpoa_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `venue` varchar(255) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `objectives` text DEFAULT NULL,
  `target_participants` varchar(255) DEFAULT NULL,
  `estimated_budget` decimal(15,2) DEFAULT NULL,
  `source_of_funds` varchar(255) DEFAULT NULL,
  `person_in_charge` varchar(255) DEFAULT NULL,
  `sdgs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sdgs`)),
  `preceding_activity` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `participants_count` int(11) DEFAULT NULL,
  `basis_grading` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_03_06_043759_add_role_to_users_table', 1),
(5, '2026_03_06_045132_create__a-activities_table', 1),
(6, '2026_03_06_045609_add_role_to_users_table', 1),
(7, '2026_03_06_061049_add_role_to_users_table', 1),
(8, '2026_03_08_000000_add_gpoa_fields_to_activities_table', 1),
(9, '2026_03_09_000000_rename_category_to_organization_in_activities_table', 1),
(10, '2026_03_10_000000_drop_beneficiaries_from_activities_table', 1),
(11, '2026_03_10_000001_drop_outcomes_from_activities_table', 1),
(12, '2026_03_11_000000_add_profile_photo_to_users_table', 1),
(13, '2026_03_30_000001_add_term_sy_fields', 1),
(14, '2026_03_31_000000_make_organization_nullable_in_activities', 1),
(15, '2026_07_11_000001_create_gpoa_workflow_tables', 1),
(16, '2026_07_13_000001_create_organization_workflow_tables', 1),
(17, '2026_07_15_000001_add_secreatary_fields_to_users_table', 1),
(18, '2026_07_18_000002_add_logo_path_to_organizations_table', 1),
(19, '2026_07_31_000002_add_osa_fields_to_gpoa_activities_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `monitoring_results`
--

CREATE TABLE `monitoring_results` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `activity_request_id` bigint(20) UNSIGNED NOT NULL,
  `gpoa_activity_id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` bigint(20) UNSIGNED NOT NULL,
  `compliance_status` varchar(255) NOT NULL,
  `compliance_notes` text DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `organizations`
--

CREATE TABLE `organizations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `college` varchar(255) DEFAULT NULL,
  `sc_president` varchar(255) DEFAULT NULL,
  `term` varchar(255) DEFAULT NULL,
  `school_year` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `organizations`
--

INSERT INTO `organizations` (`id`, `name`, `type`, `college`, `sc_president`, `term`, `school_year`, `description`, `logo_path`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'CTE-SC', 'Student Council', 'CTED', 'Browny James', '1st Term', '2026-2027', NULL, 'organization-logos/k6Iw31UqCBUFq96TT8AIIVdt7wABfxPC8D54ty7Z.jpg', 1, '2026-08-01 17:42:13', '2026-08-01 17:42:13'),
(2, 'ITOUCH-PUBLICATION', 'Specialized Student Organization', 'CICS', 'Jonni Brabo', '1st Term', '2026-2027', NULL, 'organization-logos/8LgBPTPqmDNjFz8eiNyAUyLnTM2ZdRUyzzTBYckQ.jpg', 1, '2026-08-01 19:45:02', '2026-08-01 19:45:02');

-- --------------------------------------------------------

--
-- Table structure for table `organization_workflows`
--

CREATE TABLE `organization_workflows` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `term` varchar(255) NOT NULL,
  `school_year` varchar(255) NOT NULL,
  `current_stage` varchar(255) NOT NULL DEFAULT 'gpoa_pending',
  `completion_percentage` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `reopened_by` bigint(20) UNSIGNED DEFAULT NULL,
  `reopened_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `organization_workflows`
--

INSERT INTO `organization_workflows` (`id`, `user_id`, `term`, `school_year`, `current_stage`, `completion_percentage`, `is_completed`, `is_locked`, `reopened_by`, `reopened_at`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 5, '1st Term', '2026-2027', 'gpoa_pending', 0, 0, 0, NULL, NULL, NULL, '2026-08-01 17:43:52', '2026-08-01 17:43:52'),
(2, 6, '1st Term', '2026-2027', 'gpoa_pending', 0, 0, 0, NULL, NULL, NULL, '2026-08-01 19:45:20', '2026-08-01 19:45:20');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `student_number` varchar(255) DEFAULT NULL,
  `profile_photo_path` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'user',
  `term` varchar(255) DEFAULT NULL,
  `school_year` varchar(255) DEFAULT NULL,
  `sc_president` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `org_name` varchar(255) DEFAULT NULL,
  `org_type` varchar(255) DEFAULT NULL,
  `college` varchar(255) DEFAULT NULL,
  `organization_id` bigint(20) UNSIGNED DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `username`, `student_number`, `profile_photo_path`, `email_verified_at`, `password`, `role`, `term`, `school_year`, `sc_president`, `position`, `org_name`, `org_type`, `college`, `organization_id`, `remember_token`, `created_at`, `updated_at`, `last_login_at`) VALUES
(1, 'Admin', 'osdw@gmail.com', NULL, NULL, NULL, NULL, '$2y$12$cM4yeZR3e/EXY752o.MR6e57fDG8cgkSWLkbzt9WPAkfmmJ48MN82', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-01 17:39:07', '2026-08-01 17:39:07', NULL),
(2, 'paolo contist', 'paolo@gmail.com', NULL, NULL, NULL, NULL, '$2y$12$2IoGMobQEru0ibMay87lLObDO5zmx4rRcWYCV7XUj7/3TYjfFHjO2', 'user', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-01 17:39:07', '2026-08-01 17:39:07', NULL),
(3, 'jade Unciano', 'jade@gmail.com', NULL, NULL, NULL, NULL, '$2y$12$OMiDbtuIOUvL6y2P5ZfK5ezEBifmHzwK4tKbY.n2uKZ1rd12NBKb6', 'user', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-01 17:39:08', '2026-08-01 17:39:08', NULL),
(4, 'adrian Villena', 'jums@gmail.com', NULL, NULL, NULL, NULL, '$2y$12$.rBcsytjPoUqzf2gl0BolOy07q15LUfq8nBG3Fpd4/DKm3Kd22tgK', 'user', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-01 17:39:08', '2026-08-01 17:39:08', NULL),
(5, 'Browny James', 'ctesc@gmail.com', 'CTE-SC', '23-12155', 'profile-photos/SfYVF2Di5c7mNwxzoa6D9lYZ70cxsPKPWOl3zhTN.jpg', NULL, '$2y$12$Juvi3R4Wene6dYgN2vRiO.13NyLfdN.icJd8rjwXeou/m/UTh.f2i', 'user', NULL, NULL, NULL, 'Secretary', 'CTE-SC', 'Student Council', 'CTED', 1, NULL, '2026-08-01 17:42:14', '2026-08-01 17:44:17', NULL),
(6, 'Jonni Brabo', 'itouch@gmail.com', 'Jonni Brabo', '23-14244', 'profile-photos/sCIbX0Hf6lgFP9A0mgaVHxljvScO5RnvtDbljer2.jpg', NULL, '$2y$12$WTwYF9xs2gUIJfNoS55DU.7xYEYDyFk/sP8qVxntsGVGRZy7mnXdW', 'user', NULL, NULL, NULL, 'Secretary', 'ITOUCH-PUBLICATION', 'Specialized Student Organization', 'CICS', 2, NULL, '2026-08-01 19:45:02', '2026-08-01 19:45:45', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_notifications`
--

CREATE TABLE `user_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workflow_events`
--

CREATE TABLE `workflow_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `organization_workflow_id` bigint(20) UNSIGNED NOT NULL,
  `workflow_submission_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `event_type` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workflow_submissions`
--

CREATE TABLE `workflow_submissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `organization_workflow_id` bigint(20) UNSIGNED NOT NULL,
  `document_type` varchar(255) NOT NULL,
  `version` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `gpoa_id` bigint(20) UNSIGNED DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approval_remarks` text DEFAULT NULL,
  `reject_reason` text DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activities_user_id_foreign` (`user_id`);

--
-- Indexes for table `activity_reports`
--
ALTER TABLE `activity_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_reports_activity_request_id_foreign` (`activity_request_id`);

--
-- Indexes for table `activity_requests`
--
ALTER TABLE `activity_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_requests_user_id_foreign` (`user_id`),
  ADD KEY `activity_requests_gpoa_activity_id_foreign` (`gpoa_activity_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `gpoas`
--
ALTER TABLE `gpoas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gpoas_user_id_foreign` (`user_id`),
  ADD KEY `gpoas_approved_by_foreign` (`approved_by`);

--
-- Indexes for table `gpoa_activities`
--
ALTER TABLE `gpoa_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gpoa_activities_gpoa_id_foreign` (`gpoa_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `monitoring_results`
--
ALTER TABLE `monitoring_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `monitoring_results_activity_request_id_foreign` (`activity_request_id`),
  ADD KEY `monitoring_results_gpoa_activity_id_foreign` (`gpoa_activity_id`),
  ADD KEY `monitoring_results_admin_id_foreign` (`admin_id`);

--
-- Indexes for table `organizations`
--
ALTER TABLE `organizations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `organization_workflows`
--
ALTER TABLE `organization_workflows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `org_wf_user_term_sy_unique` (`user_id`,`term`,`school_year`),
  ADD KEY `organization_workflows_reopened_by_foreign` (`reopened_by`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD KEY `users_organization_id_foreign` (`organization_id`);

--
-- Indexes for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_notifications_user_id_foreign` (`user_id`);

--
-- Indexes for table `workflow_events`
--
ALTER TABLE `workflow_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workflow_events_organization_workflow_id_foreign` (`organization_workflow_id`),
  ADD KEY `workflow_events_workflow_submission_id_foreign` (`workflow_submission_id`),
  ADD KEY `workflow_events_user_id_foreign` (`user_id`);

--
-- Indexes for table `workflow_submissions`
--
ALTER TABLE `workflow_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workflow_submissions_gpoa_id_foreign` (`gpoa_id`),
  ADD KEY `workflow_submissions_reviewed_by_foreign` (`reviewed_by`),
  ADD KEY `wf_subs_doc_current_idx` (`organization_workflow_id`,`document_type`,`is_current`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_reports`
--
ALTER TABLE `activity_reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_requests`
--
ALTER TABLE `activity_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gpoas`
--
ALTER TABLE `gpoas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gpoa_activities`
--
ALTER TABLE `gpoa_activities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `monitoring_results`
--
ALTER TABLE `monitoring_results`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `organizations`
--
ALTER TABLE `organizations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `organization_workflows`
--
ALTER TABLE `organization_workflows`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workflow_events`
--
ALTER TABLE `workflow_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workflow_submissions`
--
ALTER TABLE `workflow_submissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `activities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_reports`
--
ALTER TABLE `activity_reports`
  ADD CONSTRAINT `activity_reports_activity_request_id_foreign` FOREIGN KEY (`activity_request_id`) REFERENCES `activity_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_requests`
--
ALTER TABLE `activity_requests`
  ADD CONSTRAINT `activity_requests_gpoa_activity_id_foreign` FOREIGN KEY (`gpoa_activity_id`) REFERENCES `gpoa_activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `activity_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gpoas`
--
ALTER TABLE `gpoas`
  ADD CONSTRAINT `gpoas_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `gpoas_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gpoa_activities`
--
ALTER TABLE `gpoa_activities`
  ADD CONSTRAINT `gpoa_activities_gpoa_id_foreign` FOREIGN KEY (`gpoa_id`) REFERENCES `gpoas` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `monitoring_results`
--
ALTER TABLE `monitoring_results`
  ADD CONSTRAINT `monitoring_results_activity_request_id_foreign` FOREIGN KEY (`activity_request_id`) REFERENCES `activity_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `monitoring_results_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `monitoring_results_gpoa_activity_id_foreign` FOREIGN KEY (`gpoa_activity_id`) REFERENCES `gpoa_activities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `organization_workflows`
--
ALTER TABLE `organization_workflows`
  ADD CONSTRAINT `organization_workflows_reopened_by_foreign` FOREIGN KEY (`reopened_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `organization_workflows_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD CONSTRAINT `user_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workflow_events`
--
ALTER TABLE `workflow_events`
  ADD CONSTRAINT `workflow_events_organization_workflow_id_foreign` FOREIGN KEY (`organization_workflow_id`) REFERENCES `organization_workflows` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workflow_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `workflow_events_workflow_submission_id_foreign` FOREIGN KEY (`workflow_submission_id`) REFERENCES `workflow_submissions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `workflow_submissions`
--
ALTER TABLE `workflow_submissions`
  ADD CONSTRAINT `workflow_submissions_gpoa_id_foreign` FOREIGN KEY (`gpoa_id`) REFERENCES `gpoas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `workflow_submissions_organization_workflow_id_foreign` FOREIGN KEY (`organization_workflow_id`) REFERENCES `organization_workflows` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workflow_submissions_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
