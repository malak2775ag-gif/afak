-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 13, 2026 at 09:33 AM
-- Server version: 5.7.40
-- PHP Version: 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `afakdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_id` int(10) UNSIGNED NOT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('draft','pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_announcements_course` (`course_id`),
  KEY `idx_announcements_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `course_id`, `created_by`, `title`, `content`, `status`, `approved_by`, `approved_at`, `published_at`, `created_at`, `updated_at`) VALUES
(2, 1, 2, 'quizz', 'Kindly, my dear students, please find new updates', 'approved', NULL, NULL, '2026-05-09 13:37:10', '2026-05-09 13:37:10', '2026-05-09 13:37:10'),
(3, 1, 2, 'fdgdf', 'dsgshbtrfgbh', 'approved', NULL, NULL, '2026-05-09 13:56:24', '2026-05-09 13:56:24', '2026-05-09 13:56:24');

-- --------------------------------------------------------

--
-- Table structure for table `assessments`
--

DROP TABLE IF EXISTS `assessments`;
CREATE TABLE IF NOT EXISTS `assessments` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_id` int(10) UNSIGNED NOT NULL,
  `unit_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL = course-level quiz',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('unit_quiz','course_quiz','rubric') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `passing_score` decimal(5,2) NOT NULL DEFAULT '60.00',
  `max_attempts` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL = unlimited',
  `time_limit_minutes` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL = no limit',
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_assessments_course` (`course_id`),
  KEY `idx_assessments_unit` (`unit_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assessments`
--

INSERT INTO `assessments` (`id`, `course_id`, `unit_id`, `title`, `type`, `description`, `passing_score`, `max_attempts`, `time_limit_minutes`, `sort_order`, `created_at`, `updated_at`) VALUES
(2, 1, NULL, 'dvdxfb', 'course_quiz', NULL, '60.00', NULL, NULL, 0, '2026-04-18 16:07:56', '2026-04-18 16:07:56'),
(3, 5, 8, 'q1', 'unit_quiz', NULL, '60.00', NULL, NULL, 0, '2026-04-20 08:14:12', '2026-04-20 08:14:12'),
(4, 6, 10, 'q1', 'unit_quiz', NULL, '60.00', NULL, NULL, 0, '2026-04-20 09:06:32', '2026-04-20 09:06:32');

-- --------------------------------------------------------

--
-- Table structure for table `assessment_attempts`
--

DROP TABLE IF EXISTS `assessment_attempts`;
CREATE TABLE IF NOT EXISTS `assessment_attempts` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `enrollment_id` int(10) UNSIGNED NOT NULL,
  `assessment_id` int(10) UNSIGNED NOT NULL,
  `attempt_number` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `score` decimal(5,2) DEFAULT NULL,
  `max_score` decimal(5,2) NOT NULL,
  `percent_score` decimal(5,2) DEFAULT NULL,
  `passed` tinyint(1) DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `time_taken_seconds` int(10) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_attempts_enrollment` (`enrollment_id`),
  KEY `idx_attempts_assessment` (`assessment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assessment_attempts`
--

INSERT INTO `assessment_attempts` (`id`, `enrollment_id`, `assessment_id`, `attempt_number`, `score`, `max_score`, `percent_score`, `passed`, `started_at`, `submitted_at`, `time_taken_seconds`) VALUES
(4, 2, 2, 1, '0.00', '2.00', '0.00', 0, '2026-04-19 08:46:42', '2026-04-19 08:46:42', NULL),
(5, 2, 2, 1, '0.00', '2.00', '0.00', 0, '2026-04-19 17:02:36', '2026-04-19 17:02:36', NULL),
(6, 5, 3, 1, '0.00', '3.00', '0.00', 0, '2026-04-20 08:16:07', '2026-04-20 08:16:07', NULL),
(7, 5, 3, 1, '0.00', '3.00', '0.00', 0, '2026-04-20 10:20:15', '2026-04-20 10:20:15', NULL),
(8, 5, 3, 1, '1.00', '3.00', '33.33', 0, '2026-04-20 10:23:22', '2026-04-20 10:23:22', NULL),
(9, 5, 3, 1, '3.00', '4.00', '75.00', 1, '2026-04-20 10:38:03', '2026-04-20 10:38:03', NULL),
(10, 9, 4, 1, '0.00', '2.00', '0.00', 0, '2026-04-20 19:37:53', '2026-04-20 19:37:53', NULL),
(11, 2, 2, 1, '0.00', '2.00', '0.00', 0, '2026-04-23 09:25:26', '2026-04-23 09:25:26', NULL),
(12, 2, 2, 1, '0.00', '2.00', '0.00', 0, '2026-05-01 19:54:32', '2026-05-01 19:54:32', NULL),
(13, 2, 2, 1, '0.00', '2.00', '0.00', 0, '2026-05-01 20:00:41', '2026-05-01 20:00:41', NULL),
(14, 2, 2, 1, '0.00', '2.00', '0.00', 0, '2026-05-01 20:01:08', '2026-05-01 20:01:08', NULL),
(15, 2, 2, 1, '0.00', '2.00', '0.00', 0, '2026-05-01 20:01:16', '2026-05-01 20:01:16', NULL),
(16, 2, 2, 1, '0.00', '2.00', '0.00', 0, '2026-05-01 20:05:13', '2026-05-01 20:05:13', NULL),
(17, 9, 4, 1, '0.00', '3.00', '0.00', 0, '2026-05-01 20:27:48', '2026-05-01 20:27:48', NULL),
(18, 9, 4, 1, '2.00', '3.00', '66.67', 1, '2026-05-01 20:30:11', '2026-05-01 20:30:11', NULL),
(19, 13, 4, 1, '1.00', '3.00', '33.33', 0, '2026-05-03 08:01:33', '2026-05-03 08:01:33', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `attempt_answers`
--

DROP TABLE IF EXISTS `attempt_answers`;
CREATE TABLE IF NOT EXISTS `attempt_answers` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `attempt_id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `selected_option_id` int(10) UNSIGNED DEFAULT NULL,
  `text_answer` text COLLATE utf8mb4_unicode_ci,
  `points_earned` decimal(5,2) DEFAULT NULL,
  `feedback` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  KEY `selected_option_id` (`selected_option_id`),
  KEY `idx_answers_attempt` (`attempt_id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attempt_answers`
--

INSERT INTO `attempt_answers` (`id`, `attempt_id`, `question_id`, `selected_option_id`, `text_answer`, `points_earned`, `feedback`) VALUES
(1, 5, 1, NULL, 'uploads/submissions/sub_1_69e50aac49af5.pdf', '0.00', NULL),
(2, 6, 4, 3, NULL, '0.00', NULL),
(3, 7, 4, 3, NULL, '0.00', NULL),
(4, 8, 4, 2, NULL, '1.00', NULL),
(5, 9, 4, 2, NULL, '1.00', NULL),
(6, 9, 5, 5, NULL, '1.00', NULL),
(7, 9, 6, 8, NULL, '1.00', NULL),
(8, 10, 7, 11, NULL, '0.00', NULL),
(9, 10, 8, NULL, 'vkmhj,bnbmbm\r\nklhj,mhj,mhj,k,\r\nkjkj,b.jk', '0.00', NULL),
(10, 11, 1, NULL, 'uploads/submissions/sub_1_69e9e58671b34.docx', '0.00', NULL),
(11, 11, 2, NULL, 'uploads/submissions/sub_1_69e9e58671ea7.pdf', '0.00', NULL),
(12, 12, 1, NULL, 'uploads/submissions/sub_1_69f504f80ddef.docx', '0.00', NULL),
(13, 13, 1, NULL, 'uploads/submissions/sub_1_69f50669184e8.docx', '0.00', NULL),
(14, 14, 1, NULL, 'uploads/submissions/sub_1_69f5068499c53.docx', '0.00', NULL),
(15, 14, 2, NULL, 'uploads/submissions/sub_1_69f5068499e2f.pdf', '0.00', NULL),
(16, 15, 1, NULL, 'uploads/submissions/sub_1_69f5068c6113c.docx', '0.00', NULL),
(17, 15, 2, NULL, 'uploads/submissions/sub_1_69f5068c612f3.pdf', '0.00', NULL),
(18, 17, 7, 10, NULL, '0.00', NULL),
(19, 17, 8, NULL, 'ffikuhlfmi\r\n;oink;.b\r\nblijl', '0.00', NULL),
(20, 17, 9, NULL, 'the cpu', '0.00', NULL),
(21, 18, 7, 12, NULL, '1.00', NULL),
(22, 18, 8, NULL, 'ghgmjhhkk', '0.00', NULL),
(23, 18, 9, NULL, 'the cpu', '1.00', NULL),
(24, 19, 7, 10, NULL, '0.00', NULL),
(25, 19, 8, NULL, 'srri', '0.00', NULL),
(26, 19, 9, NULL, 'the cpu', '1.00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `parent_id` (`parent_id`),
  KEY `idx_categories_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `parent_id`, `created_at`, `updated_at`) VALUES
(9, 'Artificial Intelligence', 'artificial-intelligence', 'Explore the world of machine learning, neural networks, and AI ethics.', NULL, '2026-05-09 13:23:28', '2026-05-09 13:23:28'),
(10, 'Computer Science', 'computer-science', '', NULL, '2026-05-09 13:24:19', '2026-05-09 13:24:19'),
(11, 'React Framework', 'react-js-basics', 'Advanced front-end development using the React library.', 10, '2026-05-09 13:24:58', '2026-05-09 13:24:58'),
(12, 'IT', 'it', '', NULL, '2026-05-09 13:25:26', '2026-05-09 13:25:26'),
(13, 'Software Engineering', 'software-engineering', '', 12, '2026-05-09 13:25:50', '2026-05-09 13:25:50'),
(14, 'ML', 'ml', '', 9, '2026-05-09 13:26:15', '2026-05-09 13:26:15');

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

DROP TABLE IF EXISTS `certificates`;
CREATE TABLE IF NOT EXISTS `certificates` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `enrollment_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `certificate_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending_instructor','pending_admin','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_instructor',
  `instructor_approved_by` int(10) UNSIGNED DEFAULT NULL,
  `instructor_approved_at` timestamp NULL DEFAULT NULL,
  `admin_approved_by` int(10) UNSIGNED DEFAULT NULL,
  `admin_approved_at` timestamp NULL DEFAULT NULL,
  `issued_at` timestamp NULL DEFAULT NULL,
  `pdf_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificate_code` (`certificate_code`),
  UNIQUE KEY `unique_certificate_enrollment` (`enrollment_id`),
  KEY `course_id` (`course_id`),
  KEY `instructor_approved_by` (`instructor_approved_by`),
  KEY `admin_approved_by` (`admin_approved_by`),
  KEY `idx_certificates_student` (`student_id`),
  KEY `idx_certificates_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`id`, `enrollment_id`, `student_id`, `course_id`, `certificate_code`, `status`, `instructor_approved_by`, `instructor_approved_at`, `admin_approved_by`, `admin_approved_at`, `issued_at`, `pdf_url`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 1, 'AFAK-4F2D7DE8', 'approved', 2, '2026-04-23 09:22:16', 4, '2026-04-23 09:22:53', '2026-04-23 09:22:53', NULL, '2026-04-23 09:21:44', '2026-04-23 09:22:53');

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_conversations`
--

DROP TABLE IF EXISTS `chatbot_conversations`;
CREATE TABLE IF NOT EXISTS `chatbot_conversations` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `session_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chatbot_user` (`user_id`),
  KEY `idx_chatbot_session` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chatbot_conversations`
--

INSERT INTO `chatbot_conversations` (`id`, `user_id`, `session_id`, `created_at`, `updated_at`) VALUES
(1, 1, '510nj3r6740at15dos4iln6mfq', '2026-02-24 15:56:10', '2026-02-24 15:56:10'),
(2, 2, '510nj3r6740at15dos4iln6mfq', '2026-02-24 16:33:41', '2026-02-24 16:33:41'),
(3, 4, '510nj3r6740at15dos4iln6mfq', '2026-02-24 20:14:00', '2026-02-24 20:14:00'),
(4, 4, '0m867qobe83oehgud5inm4i6br', '2026-03-01 05:45:55', '2026-03-01 05:45:55'),
(5, 1, 'ltuvr5bhm851r5strg0kjil54k', '2026-03-04 07:48:07', '2026-03-04 07:48:07'),
(6, 1, 'ltuvr5bhm851r5strg0kjil54k', '2026-03-04 11:19:56', '2026-03-04 11:19:56'),
(7, 1, 'r407cjbegnlmo3je4tghehb3fe', '2026-03-05 22:40:09', '2026-03-05 22:40:09'),
(8, 4, 'ebcs879qkdpsifgt54bss43hud', '2026-03-14 00:55:33', '2026-03-14 00:55:33'),
(9, 1, 'v40qgvnt79p7bc7fs8lde8ubgk', '2026-03-24 19:29:09', '2026-03-24 19:29:09'),
(10, 1, 'bervmc15ajpc2onf8nom9lnrob', '2026-04-15 18:48:25', '2026-04-15 18:48:25'),
(11, 1, 'i1usfpu6jr449egsaou1s7o06p', '2026-04-18 11:45:54', '2026-04-18 11:45:54'),
(12, 6, 'gr350qbib1qvq33pba6bf9a59d', '2026-04-19 09:26:21', '2026-04-19 09:26:21'),
(13, 1, '93hg1naqe8bgoft9uoggn5a729', '2026-04-22 09:35:38', '2026-04-22 09:35:38'),
(14, 1, '93hg1naqe8bgoft9uoggn5a729', '2026-04-22 10:20:36', '2026-04-22 10:20:36'),
(15, 1, 'oeiaao5nlvatr12ghjmc030m4d', '2026-04-22 10:25:39', '2026-04-22 10:25:39'),
(16, 9, '93hg1naqe8bgoft9uoggn5a729', '2026-04-22 10:45:42', '2026-04-22 10:45:42'),
(17, 9, 'l13dsuhse9mbmnukatccrghm5e', '2026-04-22 11:11:03', '2026-04-22 11:11:03'),
(18, 1, '5bhua8mplsnrhh0nqn65rjb6fn', '2026-04-23 09:11:52', '2026-04-23 09:11:52'),
(19, 4, '5bhua8mplsnrhh0nqn65rjb6fn', '2026-04-23 09:18:42', '2026-04-23 09:18:42'),
(20, 1, '5mfjlfaosuc0ric3l7h8rqdk3a', '2026-05-01 20:00:03', '2026-05-01 20:00:03'),
(21, 1, '5mfjlfaosuc0ric3l7h8rqdk3a', '2026-05-01 20:31:53', '2026-05-01 20:31:53'),
(22, 4, 'bfm1n3jlmq0qn89nap2abjvv87', '2026-05-02 10:23:28', '2026-05-02 10:23:28'),
(23, 9, 'a6i33c9bd0vs7ftrujg2fkikrl', '2026-05-03 13:51:04', '2026-05-03 13:51:04'),
(24, 4, 'a6i33c9bd0vs7ftrujg2fkikrl', '2026-05-03 14:11:31', '2026-05-03 14:11:31'),
(25, 2, 'a6i33c9bd0vs7ftrujg2fkikrl', '2026-05-03 14:28:43', '2026-05-03 14:28:43'),
(26, 2, 'e8k9lu9mvdcqdke8i42pq8l1c1', '2026-05-09 12:44:08', '2026-05-09 12:44:08'),
(27, 9, 'e8k9lu9mvdcqdke8i42pq8l1c1', '2026-05-09 13:05:39', '2026-05-09 13:05:39'),
(28, 4, 'e8k9lu9mvdcqdke8i42pq8l1c1', '2026-05-09 13:15:11', '2026-05-09 13:15:11'),
(29, 1, 'e8k9lu9mvdcqdke8i42pq8l1c1', '2026-05-09 13:37:39', '2026-05-09 13:37:39');

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_messages`
--

DROP TABLE IF EXISTS `chatbot_messages`;
CREATE TABLE IF NOT EXISTS `chatbot_messages` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `role` enum('user','assistant') COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_messages_conversation` (`conversation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=136 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chatbot_messages`
--

INSERT INTO `chatbot_messages` (`id`, `conversation_id`, `role`, `content`, `created_at`) VALUES
(1, 1, 'user', 'hello', '2026-02-24 15:56:10'),
(2, 1, 'assistant', 'I\'m the AFAK learning assistant. I can help you with course questions. (AI integration coming soon!)', '2026-02-24 15:56:10'),
(3, 1, 'user', 'i want to know about the cources', '2026-02-24 15:56:35'),
(4, 1, 'assistant', 'I\'m the AFAK learning assistant. I can help you with course questions. (AI integration coming soon!)', '2026-02-24 15:56:35'),
(5, 2, 'user', 'hello', '2026-02-24 16:33:41'),
(6, 2, 'assistant', 'I\'m the AFAK learning assistant. I can help you with course questions. (AI integration coming soon!)', '2026-02-24 16:33:41'),
(7, 3, 'user', 'what are cource available', '2026-02-24 20:14:00'),
(8, 3, 'assistant', 'I\'m the AFAK learning assistant. I can help you with course questions. (AI integration coming soon!)', '2026-02-24 20:14:00'),
(9, 4, 'user', 'hello', '2026-03-01 05:45:55'),
(10, 4, 'assistant', 'I\'m the AFAK learning assistant. I can help you with course questions. (AI integration coming soon!)', '2026-03-01 05:45:55'),
(11, 5, 'user', 'hello', '2026-03-04 07:48:07'),
(12, 5, 'assistant', 'I\'m the AFAK learning assistant. I can help you with questions about courses, certificates, and quizzes. For a more detailed conversation, this bot would need to be connected to a real AI service.', '2026-03-04 07:48:07'),
(13, 5, 'user', 'how many cources', '2026-03-04 07:48:22'),
(14, 5, 'assistant', 'I\'m the AFAK learning assistant. I can help you with questions about courses, certificates, and quizzes. For a more detailed conversation, this bot would need to be connected to a real AI service.', '2026-03-04 07:48:22'),
(15, 5, 'user', 'courses', '2026-03-04 07:50:23'),
(16, 5, 'assistant', 'You can browse all courses from the Courses page. Enroll in any course to start learning.', '2026-03-04 07:50:23'),
(17, 5, 'user', 'hello', '2026-03-04 07:57:41'),
(18, 5, 'assistant', 'I\'m the AFAK learning assistant. To enable full AI capabilities, please add a Google Gemini API key in `config/ai.php` and install an HTTP client like Guzzle.', '2026-03-04 07:57:41'),
(19, 5, 'user', 'hi', '2026-03-04 08:03:50'),
(20, 5, 'assistant', 'I\'m the AFAK learning assistant. To enable full AI capabilities, please add a Google Gemini API key in `config/ai.php` and install an HTTP client like Guzzle.', '2026-03-04 08:03:50'),
(21, 6, 'user', 'hello', '2026-03-04 11:19:56'),
(22, 6, 'assistant', 'I\'m the AFAK learning assistant. To enable full AI capabilities, please add a Google Gemini API key in `config/ai.php` and install an HTTP client like Guzzle.', '2026-03-04 11:19:56'),
(23, 7, 'user', 'hello', '2026-03-05 22:40:09'),
(24, 7, 'assistant', 'I\'m the AFAK learning assistant. To enable full AI capabilities, please add a Google Gemini API key in `config/ai.php` and install an HTTP client like Guzzle.', '2026-03-05 22:40:09'),
(25, 7, 'user', 'hello', '2026-03-05 22:42:44'),
(26, 7, 'user', 'hello', '2026-03-05 22:43:17'),
(27, 7, 'user', 'hello', '2026-03-05 22:44:32'),
(28, 7, 'assistant', 'I\'m the AFAK learning assistant. To enable full AI capabilities, please ensure a Google Gemini API key is set and run `composer install` to load the required libraries.', '2026-03-05 22:44:32'),
(29, 7, 'user', 'hello', '2026-03-05 22:44:41'),
(30, 7, 'assistant', 'I\'m the AFAK learning assistant. To enable full AI capabilities, please ensure a Google Gemini API key is set and run `composer install` to load the required libraries.', '2026-03-05 22:44:41'),
(31, 7, 'user', 'hello', '2026-03-05 22:48:10'),
(32, 7, 'assistant', 'I\'m the AFAK learning assistant. To enable full AI capabilities, please run `composer install` to load the required libraries.', '2026-03-05 22:48:10'),
(33, 7, 'user', 'how many cources', '2026-03-05 22:53:34'),
(34, 7, 'assistant', 'I\'m having a little trouble connecting to my AI brain right now, but I can still help with basic questions. How can I assist you?', '2026-03-05 22:53:34'),
(35, 7, 'user', 'hello', '2026-03-05 22:53:52'),
(36, 7, 'assistant', 'I\'m having a little trouble connecting to my AI brain right now, but I can still help with basic questions. How can I assist you?', '2026-03-05 22:53:52'),
(37, 7, 'user', 'i', '2026-03-05 22:55:55'),
(38, 7, 'assistant', 'Error: Could not connect to the AI service. Please check the server\'s network connection. (cURL: SSL certificate problem: unable to get local issuer certificate)', '2026-03-05 22:55:55'),
(39, 7, 'user', 'what are cource available', '2026-03-05 22:56:00'),
(40, 7, 'assistant', 'Error: Could not connect to the AI service. Please check the server\'s network connection. (cURL: SSL certificate problem: unable to get local issuer certificate)', '2026-03-05 22:56:02'),
(41, 7, 'user', 'hello', '2026-03-05 22:58:00'),
(42, 7, 'assistant', 'Error: The AI service returned an error. Please check your API key and configuration. (API: models/gemini-pro is not found for API version v1beta, or is not supported for generateContent. Call ListModels to see the list of available models and their supported methods.)', '2026-03-05 22:58:01'),
(43, 7, 'user', 'hello?', '2026-03-05 22:59:58'),
(44, 7, 'assistant', 'Error: The AI service returned an error. Please check your API key and configuration. (API: models/gemini-1.5-flash is not found for API version v1beta, or is not supported for generateContent. Call ListModels to see the list of available models and their supported methods.)', '2026-03-05 23:00:00'),
(45, 7, 'user', 'hello', '2026-03-05 23:01:21'),
(46, 7, 'assistant', 'Error: The AI service returned an error. Please check your API key and configuration. (API: models/gemini-pro is not found for API version v1beta, or is not supported for generateContent. Call ListModels to see the list of available models and their supported methods.)', '2026-03-05 23:01:23'),
(47, 7, 'user', 'hello', '2026-03-05 23:07:05'),
(48, 7, 'assistant', 'Hello mallak! I am the AFAK automated assistant. I can help you check your courses, certificates, or find new content. Try asking \'my courses\' or \'how to get a certificate\'.', '2026-03-05 23:07:05'),
(49, 7, 'user', 'how to get a certificate', '2026-03-05 23:07:23'),
(50, 7, 'assistant', 'To earn a certificate, you must complete 100% of a course\'s material. Once finished, a \'Request Certificate\' button will appear on the course page. You can view your earned certificates <a href=\'my-certificates.php\'>here</a>.', '2026-03-05 23:07:23'),
(51, 7, 'user', 'how many cources', '2026-03-05 23:07:44'),
(52, 7, 'assistant', 'I\'m sorry, I didn\'t catch that. I can help with \'my courses\', \'certificates\', \'quizzes\', or \'profile\'. What would you like to know?', '2026-03-05 23:07:44'),
(53, 7, 'user', 'my courses', '2026-03-05 23:07:57'),
(54, 7, 'assistant', 'You are not enrolled in any active courses right now. <a href=\'courses.php\'>Browse courses</a> to get started.', '2026-03-05 23:07:57'),
(55, 8, 'user', 'hello', '2026-03-14 00:55:34'),
(56, 8, 'assistant', 'Hello mallak! I am the AFAK automated assistant. I can help you check your courses, certificates, or find new content. Try asking \'my courses\' or \'how to get a certificate\'.', '2026-03-14 00:55:34'),
(57, 8, 'user', 'my courses', '2026-03-14 00:55:50'),
(58, 8, 'assistant', 'You are not enrolled in any active courses right now. <a href=\'courses.php\'>Browse courses</a> to get started.', '2026-03-14 00:55:50'),
(59, 9, 'user', 'hello', '2026-03-24 19:29:09'),
(60, 9, 'assistant', 'Hello mallak! I am the AFAK assistant. Try: my courses, certificates, quizzes, or profile.', '2026-03-24 19:29:09'),
(61, 9, 'user', 'my courses', '2026-03-24 19:29:25'),
(62, 9, 'assistant', 'You are not enrolled in any active courses. Open the Courses page from the menu to browse and enroll.', '2026-03-24 19:29:25'),
(63, 9, 'user', 'skfspog', '2026-03-24 19:33:06'),
(64, 9, 'assistant', 'I did not understand that. I can help with: my courses, certificates, quizzes, or profile. What do you need?', '2026-03-24 19:33:06'),
(65, 9, 'user', 'hello', '2026-03-24 19:33:14'),
(66, 9, 'assistant', 'Hello mallak! I am the AFAK assistant. Try: my courses, certificates, quizzes, or profile.', '2026-03-24 19:33:14'),
(67, 9, 'user', 'my courses', '2026-03-24 19:33:26'),
(68, 9, 'assistant', 'You are not enrolled in any active courses. Open the Courses page from the menu to browse and enroll.', '2026-03-24 19:33:26'),
(69, 9, 'user', 'what is c++', '2026-03-24 19:33:41'),
(70, 9, 'assistant', 'I did not understand that. I can help with: my courses, certificates, quizzes, or profile. What do you need?', '2026-03-24 19:33:41'),
(71, 9, 'user', 'hello', '2026-03-24 19:46:27'),
(72, 9, 'assistant', 'Hello mallak! I am the AFAK assistant. Try: my courses, certificates, quizzes, or profile.', '2026-03-24 19:46:27'),
(73, 9, 'user', 'my courses', '2026-03-24 19:46:35'),
(74, 9, 'assistant', 'You are not enrolled in any active courses. Open the Courses page from the menu to browse and enroll.', '2026-03-24 19:46:35'),
(75, 10, 'user', 'hello', '2026-04-15 18:48:25'),
(76, 10, 'assistant', 'Hello mallak! I am the AFAK assistant. Try: my courses, certificates, quizzes, or profile.', '2026-04-15 18:48:25'),
(77, 11, 'user', 'hello', '2026-04-18 11:45:54'),
(78, 11, 'assistant', 'Hello mallak! I am the AFAK assistant. Try: my courses, certificates, quizzes, or profile.', '2026-04-18 11:45:54'),
(79, 12, 'user', 'hello', '2026-04-19 09:26:21'),
(80, 12, 'assistant', 'Hello utas! I am the AFAK assistant. Try: my courses, certificates, quizzes, or profile.', '2026-04-19 09:26:21'),
(81, 13, 'user', 'hello', '2026-04-22 09:35:38'),
(82, 13, 'assistant', 'Hello mallak! I am the AFAK assistant. Try: my courses, certificates, quizzes, profile, or report.', '2026-04-22 09:35:38'),
(83, 13, 'user', 'my courses', '2026-04-22 09:35:47'),
(84, 13, 'assistant', 'You are currently active in: Introduction to Management Accounting, c++. Keep going!', '2026-04-22 09:35:47'),
(85, 13, 'user', 'what are the available courses', '2026-04-22 09:36:19'),
(86, 13, 'assistant', 'Browse the full catalog on the Courses page. After you enroll, you can start learning right away.', '2026-04-22 09:36:19'),
(87, 13, 'user', '1+1?', '2026-04-22 09:36:30'),
(88, 13, 'assistant', 'I did not understand that. I can help with: my courses, certificates, quizzes, profile, or report. What do you need?', '2026-04-22 09:36:30'),
(89, 13, 'user', 'what courses can i take?', '2026-04-22 10:05:44'),
(90, 13, 'assistant', 'Browse the full catalog on the Courses page. After you enroll, you can start learning right away.', '2026-04-22 10:05:44'),
(91, 13, 'user', '1+1?', '2026-04-22 10:05:59'),
(92, 13, 'assistant', 'I did not understand that. I can help with: my courses, certificates, quizzes, profile, or report. What do you need?', '2026-04-22 10:06:00'),
(93, 13, 'user', 'hi', '2026-04-22 10:15:05'),
(94, 13, 'assistant', 'Hello mallak! I am the AFAK assistant. Try: my courses, certificates, quizzes, profile, or report.', '2026-04-22 10:15:05'),
(95, 13, 'user', 'my courses', '2026-04-22 10:15:15'),
(96, 13, 'assistant', 'You are currently active in: Introduction to Management Accounting, c++. Keep going!', '2026-04-22 10:15:15'),
(97, 13, 'user', '1+1?', '2026-04-22 10:15:27'),
(98, 13, 'assistant', 'I did not understand that. I can help with: my courses, certificates, quizzes, profile, or report. What do you need?', '2026-04-22 10:15:27'),
(99, 13, 'user', '1+1?', '2026-04-22 10:17:18'),
(100, 13, 'assistant', 'I did not understand that. I can help with: my courses, certificates, quizzes, profile, or report. What do you need?', '2026-04-22 10:17:18'),
(101, 13, 'user', '1+1?', '2026-04-22 10:17:26'),
(102, 13, 'assistant', 'I did not understand that. I can help with: my courses, certificates, quizzes, profile, or report. What do you need?', '2026-04-22 10:17:26'),
(103, 13, 'user', 'what is c++', '2026-04-22 10:17:40'),
(104, 13, 'assistant', 'I did not understand that. I can help with: my courses, certificates, quizzes, profile, or report. What do you need?', '2026-04-22 10:17:40'),
(105, 14, 'user', '1+1?', '2026-04-22 10:20:36'),
(106, 14, 'assistant', 'I did not understand that. I can help with: my courses, certificates, quizzes, or profile. What do you need?', '2026-04-22 10:20:36'),
(107, 14, 'user', '1+1?', '2026-04-22 10:22:08'),
(108, 14, 'assistant', 'I did not understand that. I can help with: my courses, certificates, quizzes, or profile. What do you need?', '2026-04-22 10:22:08'),
(109, 14, 'user', '2+2+8', '2026-04-22 10:23:06'),
(110, 14, 'assistant', 'I did not understand that. I can help with: my courses, certificates, quizzes, or profile. What do you need?', '2026-04-22 10:23:06'),
(111, 14, 'user', '1+1?', '2026-04-22 10:25:01'),
(112, 14, 'assistant', 'I did not understand that. I can help with: my courses, certificates, quizzes, or profile. What do you need?', '2026-04-22 10:25:01'),
(113, 15, 'user', '1+1', '2026-04-22 10:25:39'),
(114, 15, 'assistant', 'I did not understand that. I can help with: my courses, certificates, quizzes, or profile. What do you need?', '2026-04-22 10:25:39'),
(115, 15, 'user', '1+1', '2026-04-22 10:30:57'),
(116, 15, 'assistant', 'I did not understand that. I can help with: my courses, certificates, quizzes, or profile. What do you need?', '2026-04-22 10:30:57'),
(117, 15, 'user', '1+1', '2026-04-22 10:31:04'),
(118, 15, 'assistant', 'I did not understand that. I can help with: my courses, certificates, quizzes, or profile. What do you need?', '2026-04-22 10:31:04'),
(119, 15, 'user', '1+1', '2026-04-22 10:32:52'),
(120, 15, 'assistant', 'I did not understand that. I can help with: my courses, certificates, quizzes, or profile. What do you need?', '2026-04-22 10:32:52'),
(121, 16, 'user', '1+1?', '2026-04-22 10:45:42'),
(122, 16, 'assistant', 'I did not understand that. I can help with: my courses, certificates, quizzes, profile, or report. What do you need?', '2026-04-22 10:45:42'),
(123, 17, 'user', 'hello', '2026-04-22 11:11:03'),
(124, 17, 'assistant', 'Hello malak1! I am the AFAK assistant. Try: my courses, certificates, quizzes, profile, or report.', '2026-04-22 11:11:03'),
(125, 18, 'user', '1+1?', '2026-04-23 09:11:52'),
(126, 18, 'assistant', 'I did not understand that. I can help with: my courses, certificates, quizzes, profile, or report. What do you need?', '2026-04-23 09:11:52'),
(127, 18, 'user', 'hello', '2026-04-23 09:11:58'),
(128, 18, 'assistant', 'Hello mallak! I am the AFAK assistant. Try: my courses, certificates, quizzes, profile, or report.', '2026-04-23 09:11:58'),
(129, 18, 'user', 'my courses', '2026-04-23 09:12:13'),
(130, 18, 'assistant', 'You are currently active in: Introduction to Management Accounting, c++. Keep going!', '2026-04-23 09:12:13'),
(131, 19, 'user', '1+1?', '2026-04-23 09:18:42'),
(132, 19, 'assistant', 'I did not understand that. I can help with: my courses, certificates, quizzes, profile, or report. What do you need?', '2026-04-23 09:18:42'),
(133, 20, 'user', 'hello', '2026-05-01 20:00:09'),
(134, 25, 'user', 'hello', '2026-05-03 14:31:45'),
(135, 26, 'user', 'hello', '2026-05-09 12:44:54');

-- --------------------------------------------------------

--
-- Table structure for table `contact_us`
--

DROP TABLE IF EXISTS `contact_us`;
CREATE TABLE IF NOT EXISTS `contact_us` (
  `name` varchar(200) CHARACTER SET latin1 NOT NULL,
  `email` varchar(200) CHARACTER SET latin1 NOT NULL,
  `number` int(50) DEFAULT NULL,
  `comment` varchar(600) CHARACTER SET latin1 NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_us`
--

INSERT INTO `contact_us` (`name`, `email`, `number`, `comment`) VALUES
('mallak', '76J2127@ibrict.edu.om', 5465483, 'jkn,jcdn,v jk,ncv kj'),
('zlc.kdm,', '76J2127@ibrict.edu.om', 4240421, '252\r\n5dscvlzmd vf,');

-- --------------------------------------------------------

--
-- Table structure for table `content_reviews`
--

DROP TABLE IF EXISTS `content_reviews`;
CREATE TABLE IF NOT EXISTS `content_reviews` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `reviewable_type` enum('course','announcement') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reviewable_id` int(10) UNSIGNED NOT NULL,
  `reviewed_by` int(10) UNSIGNED NOT NULL,
  `action` enum('approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `reviewed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `reviewed_by` (`reviewed_by`),
  KEY `idx_reviews_type_id` (`reviewable_type`,`reviewable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
CREATE TABLE IF NOT EXISTS `courses` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thumbnail_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `instructor_id` int(10) UNSIGNED NOT NULL,
  `status` enum('draft','pending_review','approved','rejected','published') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `is_free` tinyint(1) NOT NULL DEFAULT '1',
  `price` decimal(10,2) DEFAULT NULL,
  `duration_hours` int(10) UNSIGNED DEFAULT NULL COMMENT 'Estimated total hours',
  `level` enum('beginner','intermediate','advanced','all') COLLATE utf8mb4_unicode_ci DEFAULT 'all',
  `learning_style` enum('visual','verbal','mixed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mixed',
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `cover_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Course cover image',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_courses_status` (`status`),
  KEY `idx_courses_instructor` (`instructor_id`),
  KEY `idx_courses_category` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `title`, `slug`, `description`, `short_description`, `thumbnail_url`, `category_id`, `instructor_id`, `status`, `is_free`, `price`, `duration_hours`, `level`, `learning_style`, `approved_by`, `approved_at`, `rejection_reason`, `created_at`, `updated_at`, `cover_url`) VALUES
(1, 'static', 'static', 'sfm;v', 'mathlkd', 'uploads/covers/cover_69dfe0a5e7bf29.15709928.jpg', NULL, 2, 'published', 1, '0.00', 241, 'intermediate', 'mixed', 4, '2026-03-05 23:25:05', NULL, '2026-02-25 23:39:33', '2026-04-15 19:22:17', NULL),
(4, 'Introduction to System Administration and OS Installation', 'introduction-to-system-administration-and-os-installation', 'System administrators are crucial to the smooth operation of any organization’s technology stack. They ensure that IT resources are reliable, secure, and optimized for productivity. A proactive sysadmin helps prevent downtime, maintain data integrity, and supports business continuity.', 'System administrators (sysadmins) manage both the hardware and software of an organization\'s IT infrastructure. Core duties include', 'uploads/covers/cover_69e50392769c23.56527578.webp', NULL, 2, 'published', 0, '5.00', 0, 'advanced', 'mixed', 4, '2026-04-19 16:44:07', NULL, '2026-04-19 16:32:18', '2026-04-27 16:17:10', NULL),
(5, 'Introduction to Management Accounting', 'introduction-to-management-accounting', 'Management accounting focuses on supporting internal decision-making rather than external reporting. Its primary purpose is to provide managers with relevant, timely, and accurate information to plan, control, and evaluate business operations effectively. Unlike financial accounting, which is primarily historical and aimed at external stakeholders, management accounting emphasizes future-oriented insights to guide strategic and operational decisions.', 'Management accounting is the process of providing financial and non-financial information to help managers make informed business decisions and improve organizational performance.', 'uploads/covers/cover_69e5056b857cc1.00404936.jpg', NULL, 2, 'published', 1, '0.00', 0, 'all', 'mixed', 4, '2026-04-19 16:44:05', NULL, '2026-04-19 16:40:11', '2026-04-19 16:44:21', NULL),
(6, 'c++', 'c', 'C programming language by providing object-oriented features such as classes, inheritance, and polymorphism, making it suitable for both procedural and object-oriented programming.', 'ssjskk', 'uploads/covers/cover_69e5eb48040209.99466975.webp', NULL, 2, 'published', 1, '0.00', 2, 'intermediate', 'verbal', 4, '2026-04-20 19:37:16', NULL, '2026-04-20 09:00:56', '2026-05-03 14:12:52', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `course_materials`
--

DROP TABLE IF EXISTS `course_materials`;
CREATE TABLE IF NOT EXISTS `course_materials` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `unit_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('video','pdf','document','slide','link','h5p') COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration_seconds` int(10) UNSIGNED DEFAULT NULL COMMENT 'For videos',
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `h5p_url` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_materials_unit` (`unit_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_materials`
--

INSERT INTO `course_materials` (`id`, `unit_id`, `title`, `type`, `content_url`, `file_path`, `duration_seconds`, `sort_order`, `created_at`, `updated_at`, `h5p_url`) VALUES
(3, 2, 'ghf', 'pdf', 'uploads/materials/mat_69e3a778395c16.63148831.pdf', NULL, NULL, 1, '2026-04-18 15:47:04', '2026-04-18 15:47:04', NULL),
(4, 2, 'slice', 'slide', 'uploads/materials/mat_69e3b331f28445.58928043.pptx', NULL, NULL, 2, '2026-04-18 16:37:05', '2026-04-18 16:37:05', NULL),
(5, 2, 'v', 'video', 'uploads/materials/mat_69e3b34be868c4.13370771.mp4', NULL, NULL, 3, '2026-04-18 16:37:31', '2026-04-18 16:37:31', NULL),
(7, 4, 'ch1', 'pdf', 'uploads/materials/mat_69e503d5cdd436.28411754.pdf', NULL, NULL, 1, '2026-04-19 16:33:25', '2026-04-19 16:33:25', NULL),
(8, 4, 'Introduction to System Administration', 'link', 'https://youtu.be/J4Ih0_No7-k?si=cii5JQaKv83PF6yW', NULL, NULL, 2, '2026-04-19 16:34:23', '2026-04-19 16:34:23', NULL),
(9, 5, 'ch2', 'pdf', 'uploads/materials/mat_69e50440ab99e0.11655897.pdf', NULL, NULL, 1, '2026-04-19 16:35:12', '2026-04-19 16:35:12', NULL),
(10, 8, 'intro', 'link', 'https://youtu.be/KCyg8-zM9bA?si=u0FgPGc__C_cc6dJ', NULL, NULL, 1, '2026-04-19 16:40:47', '2026-04-19 16:40:47', NULL),
(11, 8, 'ch1', 'pdf', 'uploads/materials/mat_69e505a58486a0.37822579.pdf', NULL, NULL, 2, '2026-04-19 16:41:09', '2026-04-19 16:41:09', NULL),
(12, 9, 'ch2', 'pdf', 'uploads/materials/mat_69e5060ff1fd31.21048356.pdf', NULL, NULL, 1, '2026-04-19 16:42:55', '2026-04-19 16:42:55', NULL),
(13, 10, 'ch1', 'pdf', 'uploads/materials/mat_69e5ec4f6374d6.65004002.pdf', NULL, NULL, 1, '2026-04-20 09:05:19', '2026-04-20 09:05:19', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `course_units`
--

DROP TABLE IF EXISTS `course_units`;
CREATE TABLE IF NOT EXISTS `course_units` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_course_units_course` (`course_id`),
  KEY `idx_course_units_order` (`course_id`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_units`
--

INSERT INTO `course_units` (`id`, `course_id`, `title`, `description`, `sort_order`, `created_at`, `updated_at`) VALUES
(2, 1, 'c2', NULL, 1, '2026-04-18 15:38:16', '2026-04-18 15:38:16'),
(4, 4, 'Introduction to System Administration and OS Installation', NULL, 1, '2026-04-19 16:33:01', '2026-04-19 16:33:01'),
(5, 4, 'Package Management', NULL, 2, '2026-04-19 16:34:52', '2026-04-19 16:34:52'),
(6, 4, 'Working With Files and Directories', NULL, 3, '2026-04-19 16:35:31', '2026-04-19 16:35:31'),
(7, 4, 'managing Process', NULL, 4, '2026-04-19 16:36:06', '2026-04-19 16:36:06'),
(8, 5, 'introduction', NULL, 1, '2026-04-19 16:40:29', '2026-04-19 16:40:29'),
(9, 5, 'Alternative Costing Methods', NULL, 2, '2026-04-19 16:42:39', '2026-04-19 16:42:39'),
(10, 6, 'ch1', NULL, 1, '2026-04-20 09:01:11', '2026-04-20 09:01:11'),
(11, 6, 'ch2', NULL, 2, '2026-04-20 09:05:32', '2026-04-20 09:05:32');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

DROP TABLE IF EXISTS `enrollments`;
CREATE TABLE IF NOT EXISTS `enrollments` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `status` enum('active','completed','dropped') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `enrolled_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  `progress_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `time_spent_seconds` int(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_enrollment` (`student_id`,`course_id`),
  KEY `idx_enrollments_student` (`student_id`),
  KEY `idx_enrollments_course` (`course_id`),
  KEY `idx_enrollments_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `student_id`, `course_id`, `status`, `enrolled_at`, `completed_at`, `progress_percent`, `time_spent_seconds`) VALUES
(2, 1, 1, 'completed', '2026-04-15 10:18:22', '2026-04-23 09:21:41', '100.00', 0),
(3, 6, 1, 'active', '2026-04-19 09:24:26', NULL, '66.67', 0),
(5, 1, 5, 'active', '2026-04-19 16:45:00', NULL, '0.00', 0),
(6, 8, 4, 'active', '2026-04-20 08:56:06', NULL, '33.33', 0),
(7, 8, 5, 'active', '2026-04-20 08:58:01', NULL, '0.00', 0),
(8, 8, 1, 'active', '2026-04-20 08:58:16', NULL, '0.00', 0),
(9, 1, 6, 'active', '2026-04-20 19:37:39', NULL, '0.00', 0),
(12, 1, 4, 'active', '2026-04-29 18:05:06', NULL, '0.00', 0),
(13, 9, 6, 'active', '2026-05-03 08:00:39', NULL, '0.00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
CREATE TABLE IF NOT EXISTS `feedback` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `attempt_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `instructor_id` int(10) UNSIGNED NOT NULL,
  `feedback_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `focus_areas` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON: weak areas to focus on',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `instructor_id` (`instructor_id`),
  KEY `idx_feedback_student` (`student_id`),
  KEY `idx_feedback_attempt` (`attempt_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `attempt_id`, `student_id`, `instructor_id`, `feedback_text`, `focus_areas`, `created_at`) VALUES
(1, 11, 1, 2, 'good', NULL, '2026-04-23 20:01:02'),
(2, 5, 1, 2, 'cbcg', NULL, '2026-04-23 20:01:14'),
(3, 10, 1, 2, 'idk', NULL, '2026-04-23 20:01:30'),
(4, 5, 1, 2, 'good', NULL, '2026-04-27 16:04:05');

-- --------------------------------------------------------

--
-- Table structure for table `material_progress`
--

DROP TABLE IF EXISTS `material_progress`;
CREATE TABLE IF NOT EXISTS `material_progress` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `enrollment_id` int(10) UNSIGNED NOT NULL,
  `material_id` int(10) UNSIGNED NOT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT '0',
  `time_spent_seconds` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `last_accessed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_material_progress` (`enrollment_id`,`material_id`),
  KEY `material_id` (`material_id`),
  KEY `idx_material_progress_enrollment` (`enrollment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `material_progress`
--

INSERT INTO `material_progress` (`id`, `enrollment_id`, `material_id`, `is_completed`, `time_spent_seconds`, `last_accessed_at`, `completed_at`) VALUES
(1, 2, 3, 1, 0, '2026-04-18 15:48:36', '2026-04-18 15:48:36'),
(2, 2, 5, 1, 0, '2026-04-18 16:43:08', '2026-04-18 16:43:08'),
(3, 3, 3, 1, 0, '2026-04-19 09:24:35', '2026-04-19 09:24:35'),
(4, 3, 5, 1, 0, '2026-04-19 09:25:31', '2026-04-19 09:25:31'),
(6, 6, 7, 1, 0, '2026-04-20 08:57:19', '2026-04-20 08:57:19'),
(7, 2, 4, 1, 0, '2026-04-23 09:21:41', '2026-04-23 09:21:41');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` enum('grade','feedback','certificate','content_approval','content_rejection','announcement','payment') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `link_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g. course, attempt, certificate',
  `related_id` int(10) UNSIGNED DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user` (`user_id`),
  KEY `idx_notifications_unread` (`user_id`,`is_read`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `link_url`, `related_type`, `related_id`, `is_read`, `read_at`, `created_at`) VALUES
(1, 2, 'content_approval', 'Course Approved', 'Your course has been approved.', NULL, 'course', 1, 0, NULL, '2026-02-25 08:08:16'),
(2, 2, 'content_approval', 'Course Approved', 'Your course has been approved.', NULL, 'course', 2, 0, NULL, '2026-03-05 23:24:51'),
(3, 2, 'content_approval', 'Course Approved', 'Your course has been approved.', NULL, 'course', 1, 0, NULL, '2026-03-05 23:25:05'),
(4, 1, 'grade', 'Quiz graded', 'You scored 0% on dvdxfb. Did not pass.', NULL, 'attempt', 1, 0, NULL, '2026-04-15 08:24:06'),
(5, 1, 'feedback', 'New Feedback', 'You have received feedback on your quiz.', NULL, 'attempt', 1, 0, NULL, '2026-04-15 08:38:55'),
(6, 1, 'grade', 'Quiz graded', 'You scored 0% on dvdxfb. Did not pass.', NULL, 'attempt', 2, 0, NULL, '2026-04-15 18:59:29'),
(7, 1, 'grade', 'Quiz graded', 'You scored 0% on dvdxfb. Did not pass.', NULL, 'attempt', 3, 0, NULL, '2026-04-18 11:46:33'),
(8, 1, 'grade', 'Quiz graded', 'You scored 0% on dvdxfb. Did not pass.', NULL, 'attempt', 4, 0, NULL, '2026-04-19 08:46:42'),
(9, 7, 'content_approval', 'Course Approved', 'Your course has been approved.', NULL, 'course', 3, 0, NULL, '2026-04-19 09:37:37'),
(10, 2, 'content_approval', 'Course Approved', 'Your course has been approved.', NULL, 'course', 5, 0, NULL, '2026-04-19 16:44:05'),
(11, 2, 'content_approval', 'Course Approved', 'Your course has been approved.', NULL, 'course', 4, 0, NULL, '2026-04-19 16:44:07'),
(12, 1, 'grade', 'Quiz graded', 'You scored 0% on dvdxfb. Did not pass.', NULL, 'attempt', 5, 0, NULL, '2026-04-19 17:02:36'),
(13, 1, 'grade', 'Quiz graded', 'You scored 0% on q1. Did not pass.', NULL, 'attempt', 6, 0, NULL, '2026-04-20 08:16:08'),
(14, 1, 'grade', 'Quiz graded', 'You scored 0% on q1. Did not pass.', NULL, 'attempt', 7, 0, NULL, '2026-04-20 10:20:15'),
(15, 1, 'grade', 'Quiz graded', 'You scored 33.33% on q1. Did not pass.', NULL, 'attempt', 8, 0, NULL, '2026-04-20 10:23:23'),
(16, 1, 'grade', 'Quiz graded', 'You scored 75% on q1. Passed!', NULL, 'attempt', 9, 0, NULL, '2026-04-20 10:38:03'),
(17, 2, 'content_approval', 'Course Approved', 'Your course has been approved.', NULL, 'course', 6, 0, NULL, '2026-04-20 19:37:16'),
(18, 1, 'grade', 'Quiz graded', 'You scored 0% on q1. Did not pass.', NULL, 'attempt', 10, 0, NULL, '2026-04-20 19:37:53'),
(19, 2, 'certificate', 'Certificate Approval Needed', 'A student completed \"static\" and requested a certificate approval.', '/AFAK-TEST/instructor/certificates.php', 'course', 1, 0, NULL, '2026-04-23 09:21:44'),
(20, 1, 'certificate', 'Certificate Ready', 'Your certificate has been approved and is ready to download.', NULL, 'certificate', 1, 0, NULL, '2026-04-23 09:22:53'),
(21, 1, 'grade', 'Quiz graded', 'You scored 0% on dvdxfb. Did not pass.', NULL, 'attempt', 11, 0, NULL, '2026-04-23 09:25:26'),
(22, 1, 'feedback', 'New Instructor Feedback', 'Feedback has been added to your assessment for static', '/AFAK-TEST/quiz-result.php?attempt=11', 'attempt', 11, 0, NULL, '2026-04-23 20:01:02'),
(23, 1, 'feedback', 'New Instructor Feedback', 'Feedback has been added to your assessment for static', '/AFAK-TEST/quiz-result.php?attempt=5', 'attempt', 5, 0, NULL, '2026-04-23 20:01:14'),
(24, 1, 'feedback', 'New Instructor Feedback', 'Feedback has been added to your assessment for c++', '/AFAK-TEST/quiz-result.php?attempt=10', 'attempt', 10, 0, NULL, '2026-04-23 20:01:30'),
(25, 1, 'feedback', 'New Instructor Feedback', 'Feedback has been added to your assessment for static', '/AFAK-TEST/quiz-result.php?attempt=5', 'attempt', 5, 0, NULL, '2026-04-27 16:04:05'),
(26, 9, 'payment', 'Payment Verified', 'Your payment has been verified. You can now access the course.', NULL, NULL, NULL, 0, NULL, '2026-04-27 17:16:23'),
(27, 9, 'payment', 'Payment Verified', 'Your payment has been verified. You can now access the course.', NULL, NULL, NULL, 0, NULL, '2026-04-27 17:40:10'),
(28, 1, 'payment', 'Payment Verified', 'Your payment has been verified. You can now access the course.', NULL, NULL, NULL, 0, NULL, '2026-04-29 18:05:06'),
(29, 1, 'grade', 'Quiz submitted', 'You scored 0% on dvdxfb. (Some questions require instructor review, your score might update later)', NULL, 'attempt', 12, 0, NULL, '2026-05-01 19:54:32'),
(30, 1, 'grade', 'Quiz submitted', 'You scored 0% on dvdxfb. (Some questions require instructor review, your score might update later)', NULL, 'attempt', 13, 0, NULL, '2026-05-01 20:00:41'),
(31, 1, 'grade', 'Quiz submitted', 'You scored 0% on dvdxfb. (Some questions require instructor review, your score might update later)', NULL, 'attempt', 14, 0, NULL, '2026-05-01 20:01:08'),
(32, 1, 'grade', 'Quiz submitted', 'You scored 0% on dvdxfb. (Some questions require instructor review, your score might update later)', NULL, 'attempt', 15, 0, NULL, '2026-05-01 20:01:16'),
(33, 1, 'grade', 'Quiz submitted', 'You scored 0% on dvdxfb. (Some questions require instructor review, your score might update later)', NULL, 'attempt', 16, 0, NULL, '2026-05-01 20:05:13'),
(34, 2, 'grade', 'New Quiz Pending Review', 'A student submitted \'dvdxfb\' which needs manual grading.', '/AFAK-TEST/instructor/grade-quiz.php?attempt=16', 'attempt', 16, 0, NULL, '2026-05-01 20:05:13'),
(35, 1, 'grade', 'Quiz submitted', 'You scored 0% on q1. (Some questions require instructor review, your score might update later)', NULL, 'attempt', 17, 0, NULL, '2026-05-01 20:27:48'),
(36, 2, 'grade', 'New Quiz Pending Review', 'A student submitted \'q1\' which needs manual grading.', '/AFAK-TEST/instructor/grade-quiz.php?attempt=17', 'attempt', 17, 0, NULL, '2026-05-01 20:27:48'),
(37, 1, 'grade', 'Quiz submitted', 'You scored 66.67% on q1. (Some questions require instructor review, your score might update later)', NULL, 'attempt', 18, 0, NULL, '2026-05-01 20:30:11'),
(38, 2, 'grade', 'New Quiz Pending Review', 'A student submitted \'q1\' which needs manual grading.', '/AFAK-TEST/instructor/grade-quiz.php?attempt=18', 'attempt', 18, 0, NULL, '2026-05-01 20:30:11'),
(39, 9, 'grade', 'Quiz submitted', 'You scored 33.33% on q1. (Some questions require instructor review, your score might update later)', NULL, 'attempt', 19, 0, NULL, '2026-05-03 08:01:33'),
(40, 2, 'grade', 'New Quiz Pending Review', 'A student submitted \'q1\' which needs manual grading.', '/AFAK/instructor/grade-quiz.php?attempt=19', 'attempt', 19, 0, NULL, '2026-05-03 08:01:33');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `idx_password_resets_email` (`email`),
  KEY `idx_password_resets_token` (`token`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `status` enum('pending','completed','failed','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proof_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verified_by` int(10) UNSIGNED DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `verified_by` (`verified_by`),
  KEY `idx_payments_student` (`student_id`),
  KEY `idx_payments_course` (`course_id`),
  KEY `idx_payments_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `student_id`, `course_id`, `amount`, `currency`, `status`, `payment_method`, `proof_file`, `transaction_id`, `verified_by`, `verified_at`, `created_at`, `updated_at`) VALUES
(3, 1, 4, '5.00', 'USD', 'completed', 'Bank Transfer', 'uploads/payments/pay_1_69f248126bdb0.pdf', '4555631', 4, '2026-04-29 18:05:06', '2026-04-29 18:04:02', '2026-04-29 18:05:06');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

DROP TABLE IF EXISTS `questions`;
CREATE TABLE IF NOT EXISTS `questions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `assessment_id` int(10) UNSIGNED NOT NULL,
  `question_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('multiple_choice','true_false','short_answer','essay','file_upload') COLLATE utf8mb4_unicode_ci NOT NULL,
  `points` decimal(5,2) NOT NULL DEFAULT '1.00',
  `feedback_text` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_questions_assessment` (`assessment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `assessment_id`, `question_text`, `type`, `points`, `feedback_text`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 2, 'gjvghkj,lbj', 'file_upload', '1.00', NULL, 1, '2026-04-18 16:22:35', '2026-04-18 16:22:35'),
(2, 2, 'fgchfgjhgkh', 'file_upload', '1.00', NULL, 2, '2026-04-18 16:22:54', '2026-04-18 16:22:54'),
(3, 3, '1. What is C++ primarily known for?', 'multiple_choice', '1.00', NULL, 1, '2026-04-20 08:14:53', '2026-04-20 08:14:53'),
(4, 3, 'erfg;lb,', 'multiple_choice', '1.00', NULL, 2, '2026-04-20 08:15:18', '2026-04-20 08:15:18'),
(5, 3, 't/f', 'true_false', '1.00', NULL, 3, '2026-04-20 08:15:34', '2026-04-20 08:15:34'),
(6, 3, 'ghmbhvmhg', 'true_false', '1.00', NULL, 4, '2026-04-20 10:27:48', '2026-04-20 10:27:48'),
(7, 4, '123456hhfcnc', 'multiple_choice', '1.00', NULL, 1, '2026-04-20 19:18:30', '2026-04-20 19:18:30'),
(8, 4, 'fghcghmjvjh,hj,v', 'essay', '1.00', NULL, 2, '2026-04-20 19:36:11', '2026-04-20 19:36:11'),
(9, 4, 'dyrutkiuvhjkhlvh', 'short_answer', '1.00', NULL, 3, '2026-05-01 20:27:17', '2026-05-01 20:27:17');

-- --------------------------------------------------------

--
-- Table structure for table `question_options`
--

DROP TABLE IF EXISTS `question_options`;
CREATE TABLE IF NOT EXISTS `question_options` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_id` int(10) UNSIGNED NOT NULL,
  `option_text` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_options_question` (`question_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `question_options`
--

INSERT INTO `question_options` (`id`, `question_id`, `option_text`, `is_correct`, `sort_order`) VALUES
(1, 4, '1', 0, 0),
(2, 4, '2', 1, 1),
(3, 4, '3', 0, 2),
(4, 4, '4', 0, 3),
(5, 5, 'True', 1, 0),
(6, 5, 'False', 0, 1),
(7, 6, 'True', 0, 0),
(8, 6, 'False', 1, 1),
(9, 7, '1', 0, 0),
(10, 7, '2', 0, 1),
(11, 7, '3', 0, 2),
(12, 7, '4', 1, 3),
(13, 9, 'cpu', 1, 0),
(14, 9, 'monitor', 1, 1),
(15, 9, 'kp', 1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `rubric_criteria`
--

DROP TABLE IF EXISTS `rubric_criteria`;
CREATE TABLE IF NOT EXISTS `rubric_criteria` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `assessment_id` int(10) UNSIGNED NOT NULL,
  `criterion_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `max_score` decimal(5,2) NOT NULL,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_rubric_assessment` (`assessment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rubric_scores`
--

DROP TABLE IF EXISTS `rubric_scores`;
CREATE TABLE IF NOT EXISTS `rubric_scores` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `attempt_id` int(10) UNSIGNED NOT NULL,
  `criterion_id` int(10) UNSIGNED NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `feedback` text COLLATE utf8mb4_unicode_ci,
  `graded_by` int(10) UNSIGNED DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_rubric_score` (`attempt_id`,`criterion_id`),
  KEY `criterion_id` (`criterion_id`),
  KEY `graded_by` (`graded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_learning_profiles`
--

DROP TABLE IF EXISTS `student_learning_profiles`;
CREATE TABLE IF NOT EXISTS `student_learning_profiles` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `major` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qualification_level` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `interested_field` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `interested_level` enum('beginner','intermediate','advanced') COLLATE utf8mb4_unicode_ci NOT NULL,
  `style_info_format` enum('visual','verbal') COLLATE utf8mb4_unicode_ci NOT NULL,
  `style_teaching` enum('visual','verbal') COLLATE utf8mb4_unicode_ci NOT NULL,
  `style_memory` enum('visual','auditory') COLLATE utf8mb4_unicode_ci NOT NULL,
  `style_data` enum('charts','text') COLLATE utf8mb4_unicode_ci NOT NULL,
  `style_course_type` enum('concrete','abstract') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_learning_profiles`
--

INSERT INTO `student_learning_profiles` (`id`, `user_id`, `major`, `qualification_level`, `interested_field`, `interested_level`, `style_info_format`, `style_teaching`, `style_memory`, `style_data`, `style_course_type`, `created_at`, `updated_at`) VALUES
(1, 9, 'it', 'diploma', 'it', 'intermediate', 'visual', 'verbal', 'auditory', 'charts', 'concrete', '2026-04-22 10:45:19', '2026-04-22 10:45:19'),
(2, 1, 'it', 'diploma', 'business', 'beginner', 'verbal', 'visual', 'visual', 'text', 'abstract', '2026-04-22 11:25:37', '2026-04-22 11:25:37');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('student','instructor','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'student',
  `avatar_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `survey_skipped` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `avatar_url`, `bio`, `is_active`, `email_verified_at`, `last_login_at`, `created_at`, `updated_at`, `survey_skipped`) VALUES
(1, 'mallak', 'mallak123@utas.edu.om', '$2y$10$PvE02eXH8V0UFzOKLvgYluBZiulaPNiEPyfYs1f8rks7ktWHNNcB6', 'mallak', 'Ahmed', 'student', 'uploads/avatars/avatar_1_1776524771.jfif', '', 1, NULL, '2026-05-09 13:57:05', '2026-02-24 15:55:53', '2026-05-09 13:57:05', 0),
(2, 'mallak22', 'mallak1234@utas.edu.om', '$2y$10$sFRVYbBjvpQU8ZUKuJKUfuOKWmn7usZYFPJZe/Y5Y2L1g93hEPAua', 'mallak', 'mallak', 'instructor', NULL, NULL, 1, NULL, '2026-05-09 13:56:39', '2026-02-24 16:04:31', '2026-05-09 13:56:39', 0),
(4, 'admin', 'mallak@utas.edu.om', '$2y$10$mRXUdA5mnUfw83Ttenfpfu.PiujTPfgGL12YpwJns0uV4b8.Yvryq', 'mallak', 'admin', 'admin', NULL, NULL, 1, NULL, '2026-05-09 13:54:27', '2026-02-24 16:18:41', '2026-05-09 13:54:27', 0),
(5, 'admin2', 'admin@gmail.com', '$2y$10$bW8bwMTRgMP1WF15ElYSvenNZu9gsFcrkM4D1cEoIbL.m/r/S6Go.', 'admin', '2', 'admin', NULL, NULL, 1, NULL, '2026-04-19 09:29:38', '2026-04-15 19:09:03', '2026-04-19 09:29:38', 0),
(6, 'zainb', '76J2027@utas.edu.om', '$2y$10$yel7ztfCVso7xzF8rXERQeyxRjeAcuXgL7bc.61pyPvVeelMvXM5m', 'utas', 'albadi', 'student', 'uploads/avatars/avatar_6_1776590594.PNG', 'zainab dusussi', 1, NULL, NULL, '2026-04-19 09:22:09', '2026-04-19 09:23:14', 0),
(7, 'rayan', '76j2020@utas.edu.om', '$2y$10$h3J4vdpnNlfds0PCxScmQOd.ZS9QjdY/a4Dep80EqjWNu86LRKerC', 'rayan', 'ahmed', 'instructor', NULL, NULL, 1, NULL, '2026-04-19 09:30:58', '2026-04-19 09:28:44', '2026-04-20 08:18:02', 0),
(8, 'zainab', 'zaina@utas.edu.om', '$2y$10$BkXFslQrDexdGg8JmLQBdO.4gwXU7/OjBeDuMmsxP.CIkCL.52W1K', 'zaimanbb', 'albadi', 'student', NULL, NULL, 1, NULL, NULL, '2026-04-20 08:54:22', '2026-04-20 08:54:22', 0),
(9, 'mallak s', 'malak528dfg@gmail.com', '$2y$10$AJAQguB3g8b6XR5h3AiY4.qNT/zej67mz9ltJky5K3NYFonQA71Sy', 'malak1', 'malak', 'student', NULL, NULL, 1, NULL, '2026-05-09 12:59:46', '2026-04-22 10:43:12', '2026-05-09 12:59:46', 0),
(10, 'student', 'stu123@utas.edu.om', '$2y$10$lJCJmMuGS366bZgPhLmpfuC0VFQGVq1Y3.sz0es9W0.yeRr/7cGTy', 'mallak', 'Ahmed', 'student', NULL, NULL, 1, NULL, '2026-05-03 13:52:22', '2026-05-01 14:24:56', '2026-05-03 13:52:22', 1);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcements_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcements_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `assessments`
--
ALTER TABLE `assessments`
  ADD CONSTRAINT `assessments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assessments_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `course_units` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assessment_attempts`
--
ALTER TABLE `assessment_attempts`
  ADD CONSTRAINT `assessment_attempts_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assessment_attempts_ibfk_2` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attempt_answers`
--
ALTER TABLE `attempt_answers`
  ADD CONSTRAINT `attempt_answers_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `assessment_attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attempt_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attempt_answers_ibfk_3` FOREIGN KEY (`selected_option_id`) REFERENCES `question_options` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `certificates_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `certificates_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `certificates_ibfk_4` FOREIGN KEY (`instructor_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `certificates_ibfk_5` FOREIGN KEY (`admin_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `chatbot_conversations`
--
ALTER TABLE `chatbot_conversations`
  ADD CONSTRAINT `chatbot_conversations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chatbot_messages`
--
ALTER TABLE `chatbot_messages`
  ADD CONSTRAINT `chatbot_messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `chatbot_conversations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `content_reviews`
--
ALTER TABLE `content_reviews`
  ADD CONSTRAINT `content_reviews_ibfk_1` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `courses_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `courses_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `course_materials`
--
ALTER TABLE `course_materials`
  ADD CONSTRAINT `course_materials_ibfk_1` FOREIGN KEY (`unit_id`) REFERENCES `course_units` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_units`
--
ALTER TABLE `course_units`
  ADD CONSTRAINT `course_units_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `assessment_attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_3` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `material_progress`
--
ALTER TABLE `material_progress`
  ADD CONSTRAINT `material_progress_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `material_progress_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `course_materials` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `question_options`
--
ALTER TABLE `question_options`
  ADD CONSTRAINT `question_options_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rubric_criteria`
--
ALTER TABLE `rubric_criteria`
  ADD CONSTRAINT `rubric_criteria_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rubric_scores`
--
ALTER TABLE `rubric_scores`
  ADD CONSTRAINT `rubric_scores_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `assessment_attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rubric_scores_ibfk_2` FOREIGN KEY (`criterion_id`) REFERENCES `rubric_criteria` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rubric_scores_ibfk_3` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_learning_profiles`
--
ALTER TABLE `student_learning_profiles`
  ADD CONSTRAINT `fk_learning_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
