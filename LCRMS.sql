-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for lcrms
CREATE DATABASE IF NOT EXISTS `lcrms` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `lcrms`;

-- Dumping structure for table lcrms.activity_logs
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `log_id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT '0',
  `action` varchar(100) NOT NULL DEFAULT '',
  `description` text,
  `log_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `action_type` varchar(50) NOT NULL DEFAULT 'USER_ACTION',
  `username_affected` varchar(150) NOT NULL DEFAULT '',
  `admin_username` varchar(150) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `case_number` varchar(20) NOT NULL DEFAULT '',
  `case_title` varchar(255) NOT NULL DEFAULT '',
  `performed_by` varchar(150) NOT NULL DEFAULT '',
  `date_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table lcrms.activity_logs: ~26 rows (approximately)
DELETE FROM `activity_logs`;
INSERT INTO `activity_logs` (`log_id`, `user_id`, `action`, `description`, `log_date`, `action_type`, `username_affected`, `admin_username`, `created_at`, `case_number`, `case_title`, `performed_by`, `date_time`) VALUES
	(1, 0, 'Case Created', NULL, '2026-06-09 21:34:18', 'CASE_CREATED', 'L-2026-0001', 'JOYCE OLONAN', '2026-06-09 13:34:18', 'L-2026-0001', 'DEBUG TEST CASE VS SAMPLE RESPONDENT', 'JOYCE OLONAN', '2026-06-09 13:34:18'),
	(2, 0, 'Case Created', NULL, '2026-06-09 21:36:39', 'CASE_CREATED', 'L-2026-0002', 'JOYCE OLONAN', '2026-06-09 13:36:39', 'L-2026-0002', 'API TEST CASE VS SAMPLE RESPONDENT', 'JOYCE OLONAN', '2026-06-09 13:36:39'),
	(3, 0, 'Case Created', NULL, '2026-06-09 21:46:17', 'CASE_CREATED', 'L-2026-0003', 'JOYCE OLONAN', '2026-06-09 13:46:17', 'L-2026-0003', 'ELSA VS. PEPITO MANALOTO', 'JOYCE OLONAN', '2026-06-09 13:46:17'),
	(4, 0, '', NULL, '2026-06-10 08:12:18', 'UPDATE_USER', 'admin', 'admin', '2026-06-10 00:12:18', '', '', '', '2026-06-10 08:12:18'),
	(5, 0, '', NULL, '2026-06-10 08:13:17', 'UPDATE_USER', 'admin', 'admin', '2026-06-10 00:13:17', '', '', '', '2026-06-10 08:13:17'),
	(6, 0, '', NULL, '2026-06-10 08:13:29', 'UPDATE_USER', 'user', 'admin', '2026-06-10 00:13:29', '', '', '', '2026-06-10 08:13:29'),
	(7, 0, 'Case Created', NULL, '2026-06-10 08:33:47', 'CASE_CREATED', 'L-2026-0004', 'JOYCE OLONAN', '2026-06-10 00:33:47', 'L-2026-0004', 'PINO VS. DARCY', 'JOYCE OLONAN', '2026-06-10 00:33:47'),
	(8, 0, 'Case Created', NULL, '2026-06-10 08:40:03', 'CASE_CREATED', 'L-2026-0005', 'JOYCE OLONAN', '2026-06-10 00:40:03', 'L-2026-0005', 'JOSEPH AQUINO VS. EDWARD LOCSIN', 'JOYCE OLONAN', '2026-06-10 00:40:03'),
	(9, 0, 'Case Created', NULL, '2026-06-10 08:41:24', 'CASE_CREATED', 'L-2026-0006', 'JOYCE OLONAN', '2026-06-10 00:41:24', 'L-2026-0006', 'ERLINDA VS. SIR REY', 'JOYCE OLONAN', '2026-06-10 00:41:24'),
	(10, 0, 'Case Created', NULL, '2026-06-10 08:42:06', 'CASE_CREATED', 'L-2026-0007', 'JOYCE OLONAN', '2026-06-10 00:42:06', 'L-2026-0007', 'JOSHUA GARCIA VS. MARIA', 'JOYCE OLONAN', '2026-06-10 00:42:06'),
	(11, 0, 'Case Created', NULL, '2026-06-10 08:49:47', 'CASE_CREATED', 'L-2026-0008', 'JOYCE OLONAN', '2026-06-10 00:49:47', 'L-2026-0008', 'SHAII VS. CY', 'JOYCE OLONAN', '2026-06-10 00:49:47'),
	(12, 0, '', NULL, '2026-06-10 13:32:23', 'UPDATE_USER', 'admin', 'admin', '2026-06-10 05:32:23', '', '', '', '2026-06-10 13:32:23'),
	(13, 0, '', NULL, '2026-06-10 13:56:07', 'UPDATE_ADMIN_PROFILE', 'admin', 'admin', '2026-06-10 05:56:07', '', '', '', '2026-06-10 13:56:07'),
	(14, 0, '', NULL, '2026-06-10 13:56:37', 'UPDATE_USER', 'user', 'admin', '2026-06-10 05:56:37', '', '', '', '2026-06-10 13:56:37'),
	(15, 0, '', NULL, '2026-06-10 14:03:08', 'UPDATE_USER', 'user', 'admin', '2026-06-10 06:03:08', '', '', '', '2026-06-10 14:03:08'),
	(16, 0, '', NULL, '2026-06-10 14:16:44', 'UPDATE_ADMIN_PROFILE', 'admin', 'admin', '2026-06-10 06:16:44', '', '', '', '2026-06-10 14:16:44'),
	(17, 0, '', NULL, '2026-06-10 14:18:25', 'UPDATE_ADMIN_PROFILE', 'admin', 'admin', '2026-06-10 06:18:25', '', '', '', '2026-06-10 14:18:25'),
	(18, 0, '', NULL, '2026-06-10 14:18:54', 'UPDATE_ADMIN_PROFILE', 'admin', 'admin', '2026-06-10 06:18:54', '', '', '', '2026-06-10 14:18:54'),
	(19, 0, '', NULL, '2026-06-10 14:18:56', 'UPDATE_ADMIN_PROFILE', 'admin', 'admin', '2026-06-10 06:18:56', '', '', '', '2026-06-10 14:18:56'),
	(20, 0, '', NULL, '2026-06-10 14:19:00', 'UPDATE_ADMIN_PROFILE', 'admin', 'admin', '2026-06-10 06:19:00', '', '', '', '2026-06-10 14:19:00'),
	(21, 0, '', NULL, '2026-06-10 14:21:01', 'UPDATE_ADMIN_PROFILE', 'admin', 'admin', '2026-06-10 06:21:01', '', '', '', '2026-06-10 14:21:01'),
	(22, 0, '', NULL, '2026-06-10 14:21:58', 'UPDATE_ADMIN_PROFILE', 'admin', 'admin', '2026-06-10 06:21:58', '', '', '', '2026-06-10 14:21:58'),
	(23, 0, '', NULL, '2026-06-10 14:46:30', 'UPDATE_ADMIN_PROFILE', 'admin', 'admin', '2026-06-10 06:46:30', '', '', '', '2026-06-10 14:46:30'),
	(24, 0, '', NULL, '2026-06-10 14:46:45', 'UPDATE_ADMIN_PROFILE', 'admin', 'admin', '2026-06-10 06:46:45', '', '', '', '2026-06-10 14:46:45'),
	(25, 0, '', NULL, '2026-06-10 14:47:20', 'UPDATE_ADMIN_PROFILE', 'admin1', 'admin1', '2026-06-10 06:47:20', '', '', '', '2026-06-10 14:47:20'),
	(26, 0, '', NULL, '2026-06-10 14:47:26', 'UPDATE_ADMIN_PROFILE', 'admin', 'admin', '2026-06-10 06:47:26', '', '', '', '2026-06-10 14:47:26'),
	(27, 0, '', NULL, '2026-06-10 14:59:07', 'UPDATE_ADMIN_PROFILE', 'admin', 'admin', '2026-06-10 06:59:07', '', '', '', '2026-06-10 14:59:07'),
	(28, 0, '', NULL, '2026-06-10 14:59:09', 'UPDATE_ADMIN_PROFILE', 'admin', 'admin', '2026-06-10 06:59:09', '', '', '', '2026-06-10 14:59:09'),
	(29, 0, '', NULL, '2026-06-10 14:59:34', 'UPDATE_ADMIN_PROFILE', 'admin', 'admin', '2026-06-10 06:59:34', '', '', '', '2026-06-10 14:59:34');

-- Dumping structure for table lcrms.admin
CREATE TABLE IF NOT EXISTS `admin` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table lcrms.admin: ~3 rows (approximately)
DELETE FROM `admin`;
INSERT INTO `admin` (`admin_id`, `username`, `password`) VALUES
	(1, 'admin', '$2y$10$76SCPNd8snycqmcaC.jVK..I4ed1KSqaAnPzRZr16yEP2jbyP7UaW'),
	(2, 'Edmer', '$2y$10$TaaO8eFlPcFoz7TyKj9iHOk6bATPJ8WLK58W6TLezTxbqMpELuUpu'),
	(3, 'Darcy Ogale', '$2y$10$lIaEpoGA.u5eziGlmqR0k.8xK/W1KuETlPNaIBVxiS2wA/JazYB66');

-- Dumping structure for table lcrms.cases
CREATE TABLE IF NOT EXISTS `cases` (
  `case_id` int unsigned NOT NULL AUTO_INCREMENT,
  `main_point_of_agreement` text,
  `created_by` varchar(150) NOT NULL DEFAULT 'System',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `case_number` varchar(50) NOT NULL DEFAULT '',
  `case_title` varchar(255) NOT NULL DEFAULT '',
  `compliant_name` varchar(255) NOT NULL DEFAULT '',
  `nature_of_case` varchar(100) NOT NULL DEFAULT '',
  `date_filed` date NOT NULL,
  `initial_confrontion_page` date DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'CFA (Certificate of File Action)',
  `settlement_date` date DEFAULT NULL,
  `execution_date` date DEFAULT NULL,
  `case_description` text,
  `complainant_title` varchar(255) NOT NULL DEFAULT '',
  `date_initial_confrontation` date DEFAULT NULL,
  `case_status` varchar(50) NOT NULL DEFAULT 'CFA (Certificate of File Action)',
  `date_settlement_award` date DEFAULT NULL,
  `date_execution` date DEFAULT NULL,
  `detailed_case_description` text,
  `complainant_full_name` varchar(255) NOT NULL DEFAULT '',
  `complainant_address` text,
  `complainant_status` varchar(100) NOT NULL DEFAULT '',
  `complainant_religion` varchar(100) NOT NULL DEFAULT '',
  `complainant_birthdate` date DEFAULT NULL,
  `complainant_age` int unsigned DEFAULT NULL,
  `complainant_government_id` varchar(150) NOT NULL DEFAULT '',
  `complainant_contact_number` varchar(50) NOT NULL DEFAULT '',
  `respondent_full_name` varchar(255) NOT NULL DEFAULT '',
  `respondent_address` text,
  `respondent_contact_number` varchar(50) NOT NULL DEFAULT '',
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by_user_id` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`case_id`),
  UNIQUE KEY `case_number` (`case_number`),
  UNIQUE KEY `cases_case_number_unique` (`case_number`),
  KEY `cases_created_by_user_id_index` (`created_by_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table lcrms.cases: ~3 rows (approximately)
DELETE FROM `cases`;
INSERT INTO `cases` (`case_id`, `main_point_of_agreement`, `created_by`, `created_at`, `updated_at`, `case_number`, `case_title`, `compliant_name`, `nature_of_case`, `date_filed`, `initial_confrontion_page`, `status`, `settlement_date`, `execution_date`, `case_description`, `complainant_title`, `date_initial_confrontation`, `case_status`, `date_settlement_award`, `date_execution`, `detailed_case_description`, `complainant_full_name`, `complainant_address`, `complainant_status`, `complainant_religion`, `complainant_birthdate`, `complainant_age`, `complainant_government_id`, `complainant_contact_number`, `respondent_full_name`, `respondent_address`, `respondent_contact_number`, `date_created`, `created_by_user_id`) VALUES
	(1, '', 'JOYCE OLONAN', '2026-06-09 21:34:18', NULL, 'L-2026-0001', 'DEBUG TEST CASE VS SAMPLE RESPONDENT', '', 'Civil', '2026-06-09', NULL, 'CFA (Certificate of File Action)', NULL, NULL, NULL, 'Debug Complainant', NULL, 'CFA (Certificate of File Action)', NULL, NULL, 'Temporary diagnostic record created to verify Add Case backend persistence.', '', NULL, '', '', NULL, NULL, '', '', '', NULL, '', '2026-06-09 13:34:18', 0),
	(2, '', 'JOYCE OLONAN', '2026-06-09 21:36:39', NULL, 'L-2026-0002', 'API TEST CASE VS SAMPLE RESPONDENT', '', 'Civil', '2026-06-09', NULL, 'CFA (Certificate of File Action)', NULL, NULL, NULL, 'API Complainant', NULL, 'CFA (Certificate of File Action)', NULL, NULL, 'Temporary API diagnostic record created to verify cases_api.php, controller, session, model, database insert, and activity logging.', '', NULL, '', '', NULL, NULL, '', '', '', NULL, '', '2026-06-09 13:36:39', 0),
	(3, '', 'JOYCE OLONAN', '2026-06-09 21:46:17', NULL, 'L-2026-0003', 'ELSA VS. PEPITO MANALOTO', '', 'Civil', '2026-06-09', NULL, 'CFA (Certificate of File Action)', NULL, NULL, NULL, 'Utang', NULL, 'CFA (Certificate of File Action)', NULL, NULL, 'Ayaw magbayad utang eh', '', NULL, '', '', NULL, NULL, '', '', '', NULL, '', '2026-06-09 13:46:17', 0),
	(4, '', 'JOYCE OLONAN', '2026-06-10 08:33:47', NULL, 'L-2026-0004', 'PINO VS. DARCY', '', 'Civil', '2026-06-10', NULL, 'CFA (Certificate of File Action)', NULL, NULL, NULL, 'Mobile legendz bang bang', NULL, 'CFA (Certificate of File Action)', NULL, NULL, 'trashtalker sa ml, report report dark system', '', NULL, '', '', NULL, NULL, '', '', '', NULL, '', '2026-06-10 00:33:47', 15),
	(5, '', 'JOYCE OLONAN', '2026-06-10 08:40:03', NULL, 'L-2026-0005', 'JOSEPH AQUINO VS. EDWARD LOCSIN', '', 'Civil', '2026-06-10', NULL, 'CFA (Certificate of File Action)', NULL, NULL, NULL, 'Ashley tignan mo ako', NULL, 'CFA (Certificate of File Action)', NULL, NULL, 'inagaw ni joseph si ashley', '', NULL, '', '', NULL, NULL, '', '', '', NULL, '', '2026-06-10 00:40:03', 15),
	(6, '', 'JOYCE OLONAN', '2026-06-10 08:41:24', NULL, 'L-2026-0006', 'ERLINDA VS. SIR REY', '', 'Civil', '2026-06-10', NULL, 'CFA (Certificate of File Action)', NULL, NULL, NULL, 'clearance', NULL, 'CFA (Certificate of File Action)', NULL, NULL, 'ayaw pumirma ng clearance', '', NULL, '', '', NULL, NULL, '', '', '', NULL, '', '2026-06-10 00:41:24', 15),
	(7, '', 'JOYCE OLONAN', '2026-06-10 08:42:06', NULL, 'L-2026-0007', 'JOSHUA GARCIA VS. MARIA', '', 'Civil', '2026-06-10', NULL, 'CFA (Certificate of File Action)', NULL, NULL, NULL, 'Loan', NULL, 'CFA (Certificate of File Action)', NULL, NULL, 'vnjknnvxjnj', '', NULL, '', '', NULL, NULL, '', '', '', NULL, '', '2026-06-10 00:42:06', 15),
	(8, '', 'JOYCE OLONAN', '2026-06-10 08:49:47', NULL, 'L-2026-0008', 'SHAII VS. CY', '', 'Civil', '2026-06-10', NULL, 'CFA (Certificate of File Action)', NULL, NULL, NULL, 'HOTDOG', '2026-06-10', 'Conciliation', '2026-06-10', '2026-06-10', 'ggggg', '', NULL, '', '', NULL, NULL, '', '', '', NULL, '', '2026-06-10 00:49:47', 15);

-- Dumping structure for table lcrms.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_code` varchar(32) NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `full_name` varchar(100) NOT NULL DEFAULT '0',
  `role` varchar(50) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `username` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `username_unique` (`username`),
  UNIQUE KEY `user_code_unique` (`user_code`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table lcrms.users: ~4 rows (approximately)
DELETE FROM `users`;
INSERT INTO `users` (`id`, `user_code`, `fullname`, `full_name`, `role`, `created_at`, `username`, `password`) VALUES
	(11, 'LCRMS-20262730', 'Idmir Lucedu', '0', 'ADMIN', '2026-06-09 06:02:34', 'admin', '$2y$10$rtlKWyku5CAs5.LjJOSvt.HaiFr362t7ofNRDe7UYglvX0Ad3io7q'),
	(15, 'LCRMS-20267123', 'JOYCE OLONAN', '0', 'USER', '2026-06-09 06:24:11', 'user', '$2y$10$Xks7ZskHKnla2TZn87MryubU8nXWpeYg1YFldvavDfMkFCwHGTnCa');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
