-- ============================================
-- AFAK Online Learning Platform - Database Schema
-- MySQL 5.7+ / MariaDB 10.2+
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Fix for MySQL index size limit (767 bytes) with utf8mb4 (4 bytes/char)
-- VARCHAR(191) = 764 bytes max for indexed columns

-- --------------------------------------------
-- 1. USERS & AUTHENTICATION
-- --------------------------------------------

CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `role` ENUM('student', 'instructor', 'admin') NOT NULL DEFAULT 'student',
    `avatar_url` VARCHAR(500) DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
    `last_login_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `survey_skipped` TINYINT(1) DEFAULT 0,
    INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------
-- 2. COURSES
-- --------------------------------------------

CREATE TABLE `categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `parent_id` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
    INDEX `idx_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `courses` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(191) NOT NULL UNIQUE,
    `description` TEXT NOT NULL,
    `short_description` VARCHAR(500) DEFAULT NULL,
    `thumbnail_url` VARCHAR(500) DEFAULT NULL,
    `category_id` INT UNSIGNED DEFAULT NULL,
    `instructor_id` INT UNSIGNED NOT NULL,
    `status` ENUM('draft', 'pending_review', 'approved', 'rejected', 'published') NOT NULL DEFAULT 'draft',
    `is_free` TINYINT(1) NOT NULL DEFAULT 1,
    `price` DECIMAL(10, 2) DEFAULT NULL,
    `duration_hours` INT UNSIGNED DEFAULT NULL COMMENT 'Estimated total hours',
    `level` ENUM('beginner', 'intermediate', 'advanced', 'all') DEFAULT 'all',
    `approved_by` INT UNSIGNED DEFAULT NULL,
    `approved_at` TIMESTAMP NULL DEFAULT NULL,
    `rejection_reason` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`instructor_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_courses_status` (`status`),
    INDEX `idx_courses_instructor` (`instructor_id`),
    INDEX `idx_courses_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------
-- 3. COURSE STRUCTURE (Units/Modules)
-- --------------------------------------------

CREATE TABLE `course_units` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `course_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    INDEX `idx_course_units_course` (`course_id`),
    INDEX `idx_course_units_order` (`course_id`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `course_materials` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `unit_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `type` ENUM('video', 'pdf', 'document', 'slide', 'link') NOT NULL,
    `content_url` VARCHAR(500) NOT NULL,
    `file_path` VARCHAR(500) DEFAULT NULL,
    `duration_seconds` INT UNSIGNED DEFAULT NULL COMMENT 'For videos',
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`unit_id`) REFERENCES `course_units`(`id`) ON DELETE CASCADE,
    INDEX `idx_materials_unit` (`unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------
-- 4. ENROLLMENTS & PROGRESS
-- --------------------------------------------

CREATE TABLE `enrollments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT UNSIGNED NOT NULL,
    `course_id` INT UNSIGNED NOT NULL,
    `status` ENUM('active', 'completed', 'dropped') NOT NULL DEFAULT 'active',
    `enrolled_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    `progress_percent` DECIMAL(5, 2) NOT NULL DEFAULT 0,
    `time_spent_seconds` INT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY `unique_enrollment` (`student_id`, `course_id`),
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    INDEX `idx_enrollments_student` (`student_id`),
    INDEX `idx_enrollments_course` (`course_id`),
    INDEX `idx_enrollments_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `material_progress` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `enrollment_id` INT UNSIGNED NOT NULL,
    `material_id` INT UNSIGNED NOT NULL,
    `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
    `time_spent_seconds` INT UNSIGNED NOT NULL DEFAULT 0,
    `last_accessed_at` TIMESTAMP NULL DEFAULT NULL,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `unique_material_progress` (`enrollment_id`, `material_id`),
    FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`material_id`) REFERENCES `course_materials`(`id`) ON DELETE CASCADE,
    INDEX `idx_material_progress_enrollment` (`enrollment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------
-- 5. ASSESSMENTS (Quizzes, Unit Evaluations)
-- --------------------------------------------

CREATE TABLE `assessments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `course_id` INT UNSIGNED NOT NULL,
    `unit_id` INT UNSIGNED DEFAULT NULL COMMENT 'NULL = course-level quiz',
    `title` VARCHAR(255) NOT NULL,
    `type` ENUM('unit_quiz', 'course_quiz', 'rubric') NOT NULL,
    `description` TEXT DEFAULT NULL,
    `passing_score` DECIMAL(5, 2) NOT NULL DEFAULT 60.00,
    `max_attempts` INT UNSIGNED DEFAULT NULL COMMENT 'NULL = unlimited',
    `time_limit_minutes` INT UNSIGNED DEFAULT NULL COMMENT 'NULL = no limit',
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`unit_id`) REFERENCES `course_units`(`id`) ON DELETE CASCADE,
    INDEX `idx_assessments_course` (`course_id`),
    INDEX `idx_assessments_unit` (`unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `questions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `assessment_id` INT UNSIGNED NOT NULL,
    `question_text` TEXT NOT NULL,
    `type` ENUM('multiple_choice', 'true_false', 'short_answer', 'essay', 'file_upload') NOT NULL,
    `points` DECIMAL(5, 2) NOT NULL DEFAULT 1.00,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`assessment_id`) REFERENCES `assessments`(`id`) ON DELETE CASCADE,
    INDEX `idx_questions_assessment` (`assessment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `question_options` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `question_id` INT UNSIGNED NOT NULL,
    `option_text` VARCHAR(500) NOT NULL,
    `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE,
    INDEX `idx_options_question` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rubric_criteria` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `assessment_id` INT UNSIGNED NOT NULL,
    `criterion_name` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `max_score` DECIMAL(5, 2) NOT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (`assessment_id`) REFERENCES `assessments`(`id`) ON DELETE CASCADE,
    INDEX `idx_rubric_assessment` (`assessment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------
-- 6. ASSESSMENT ATTEMPTS & GRADES
-- --------------------------------------------

CREATE TABLE `assessment_attempts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `enrollment_id` INT UNSIGNED NOT NULL,
    `assessment_id` INT UNSIGNED NOT NULL,
    `attempt_number` INT UNSIGNED NOT NULL DEFAULT 1,
    `score` DECIMAL(5, 2) DEFAULT NULL,
    `max_score` DECIMAL(5, 2) NOT NULL,
    `percent_score` DECIMAL(5, 2) DEFAULT NULL,
    `passed` TINYINT(1) DEFAULT NULL,
    `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `submitted_at` TIMESTAMP NULL DEFAULT NULL,
    `time_taken_seconds` INT UNSIGNED DEFAULT NULL,
    FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assessment_id`) REFERENCES `assessments`(`id`) ON DELETE CASCADE,
    INDEX `idx_attempts_enrollment` (`enrollment_id`),
    INDEX `idx_attempts_assessment` (`assessment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `attempt_answers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `attempt_id` INT UNSIGNED NOT NULL,
    `question_id` INT UNSIGNED NOT NULL,
    `selected_option_id` INT UNSIGNED DEFAULT NULL,
    `text_answer` TEXT DEFAULT NULL,
    `points_earned` DECIMAL(5, 2) DEFAULT NULL,
    `feedback` TEXT DEFAULT NULL,
    FOREIGN KEY (`attempt_id`) REFERENCES `assessment_attempts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`selected_option_id`) REFERENCES `question_options`(`id`) ON DELETE SET NULL,
    INDEX `idx_answers_attempt` (`attempt_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rubric_scores` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `attempt_id` INT UNSIGNED NOT NULL,
    `criterion_id` INT UNSIGNED NOT NULL,
    `score` DECIMAL(5, 2) NOT NULL,
    `feedback` TEXT DEFAULT NULL,
    `graded_by` INT UNSIGNED DEFAULT NULL,
    `graded_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`attempt_id`) REFERENCES `assessment_attempts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`criterion_id`) REFERENCES `rubric_criteria`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`graded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `unique_rubric_score` (`attempt_id`, `criterion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------
-- 7. FEEDBACK
-- --------------------------------------------

CREATE TABLE `feedback` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `attempt_id` INT UNSIGNED NOT NULL,
    `student_id` INT UNSIGNED NOT NULL,
    `instructor_id` INT UNSIGNED NOT NULL,
    `feedback_text` TEXT NOT NULL,
    `focus_areas` TEXT DEFAULT NULL COMMENT 'JSON: weak areas to focus on',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`attempt_id`) REFERENCES `assessment_attempts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`instructor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_feedback_student` (`student_id`),
    INDEX `idx_feedback_attempt` (`attempt_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------
-- 8. CERTIFICATES
-- --------------------------------------------

CREATE TABLE `certificates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `enrollment_id` INT UNSIGNED NOT NULL,
    `student_id` INT UNSIGNED NOT NULL,
    `course_id` INT UNSIGNED NOT NULL,
    `certificate_code` VARCHAR(50) NOT NULL UNIQUE,
    `status` ENUM('pending_instructor', 'pending_admin', 'approved', 'rejected') NOT NULL DEFAULT 'pending_instructor',
    `instructor_approved_by` INT UNSIGNED DEFAULT NULL,
    `instructor_approved_at` TIMESTAMP NULL DEFAULT NULL,
    `admin_approved_by` INT UNSIGNED DEFAULT NULL,
    `admin_approved_at` TIMESTAMP NULL DEFAULT NULL,
    `issued_at` TIMESTAMP NULL DEFAULT NULL,
    `pdf_url` VARCHAR(500) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_certificate_enrollment` (`enrollment_id`),
    FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`instructor_approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`admin_approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_certificates_student` (`student_id`),
    INDEX `idx_certificates_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------
-- 9. PAYMENTS (for paid courses)
-- --------------------------------------------

CREATE TABLE `payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT UNSIGNED NOT NULL,
    `course_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'USD',
    `status` ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    `payment_method` VARCHAR(50) DEFAULT NULL,
    `transaction_id` VARCHAR(255) DEFAULT NULL,
    `verified_by` INT UNSIGNED DEFAULT NULL,
    `verified_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_payments_student` (`student_id`),
    INDEX `idx_payments_course` (`course_id`),
    INDEX `idx_payments_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------
-- 10. ANNOUNCEMENTS
-- --------------------------------------------

CREATE TABLE `announcements` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `course_id` INT UNSIGNED NOT NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `status` ENUM('draft', 'pending', 'approved', 'rejected') NOT NULL DEFAULT 'draft',
    `approved_by` INT UNSIGNED DEFAULT NULL,
    `approved_at` TIMESTAMP NULL DEFAULT NULL,
    `published_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_announcements_course` (`course_id`),
    INDEX `idx_announcements_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------
-- 11. CONTENT REVIEW (Admin approval workflow)
-- --------------------------------------------

CREATE TABLE `content_reviews` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reviewable_type` ENUM('course', 'announcement') NOT NULL,
    `reviewable_id` INT UNSIGNED NOT NULL,
    `reviewed_by` INT UNSIGNED NOT NULL,
    `action` ENUM('approved', 'rejected') NOT NULL,
    `comments` TEXT DEFAULT NULL,
    `reviewed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_reviews_type_id` (`reviewable_type`, `reviewable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------
-- 12. NOTIFICATIONS
-- --------------------------------------------

CREATE TABLE `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `type` ENUM('grade', 'feedback', 'certificate', 'content_approval', 'content_rejection', 'announcement', 'payment') NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT DEFAULT NULL,
    `link_url` VARCHAR(500) DEFAULT NULL,
    `related_type` VARCHAR(50) DEFAULT NULL COMMENT 'e.g. course, attempt, certificate',
    `related_id` INT UNSIGNED DEFAULT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_notifications_user` (`user_id`),
    INDEX `idx_notifications_unread` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------
-- 13. CHATBOT (conversation history)
-- --------------------------------------------

CREATE TABLE `chatbot_conversations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `session_id` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_chatbot_user` (`user_id`),
    INDEX `idx_chatbot_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `chatbot_messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` INT UNSIGNED NOT NULL,
    `role` ENUM('user', 'assistant') NOT NULL,
    `content` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`conversation_id`) REFERENCES `chatbot_conversations`(`id`) ON DELETE CASCADE,
    INDEX `idx_messages_conversation` (`conversation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------
-- 14. PASSWORD RESETS (optional, for auth)
-- --------------------------------------------

CREATE TABLE `password_resets` (
    `email` VARCHAR(191) NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    INDEX `idx_password_resets_email` (`email`(191)),
    INDEX `idx_password_resets_token` (`token`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------
-- 15. CONTACT FORM (public inquiries)
-- --------------------------------------------

CREATE TABLE IF NOT EXISTS `contact_us` (
  `name` varchar(200) CHARACTER SET latin1 NOT NULL,
  `email` varchar(200) CHARACTER SET latin1 NOT NULL,
  `number` int(50) DEFAULT NULL,
  `comment` varchar(600) CHARACTER SET latin1 NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
