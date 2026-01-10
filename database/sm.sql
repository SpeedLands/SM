-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 17-12-2025 a las 09:05:33
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` varchar(50) NOT NULL, 
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL, 
  `role` enum('ADMIN','TEACHER','PARENT','STUDENT') NOT NULL DEFAULT 'STUDENT',
  `status` enum('ACTIVE','FORCE_PASSWORD_CHANGE','BLOCKED') DEFAULT 'FORCE_PASSWORD_CHANGE',
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `current_team_id` bigint(20) UNSIGNED DEFAULT NULL,
  `profile_photo_path` varchar(2048) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `job_batches`;
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
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cycles`;
CREATE TABLE `cycles` (
  `id` bigint(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `class_groups`;
CREATE TABLE `class_groups` (
  `id` char(36) NOT NULL,
  `cycle_id` bigint(20) NOT NULL,
  `grade` varchar(10) NOT NULL,
  `section` varchar(10) NOT NULL,
  `tutor_teacher_id` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_group` (`cycle_id`,`grade`,`section`),
  KEY `tutor_teacher_id` (`tutor_teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` varchar(50) NOT NULL,
  `curp` varchar(18) NOT NULL,
  `name` varchar(100) NOT NULL,
  `birth_date` date NOT NULL,
  `grade` varchar(10) NOT NULL,
  `group_name` varchar(10) NOT NULL,
  `turn` enum('MATUTINO','VESPERTINO') NOT NULL,
  `siblings_count` int(11) DEFAULT 0,
  `birth_order` int(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `curp` (`curp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `community_services`;
CREATE TABLE `community_services` (
  `id` varchar(50) NOT NULL,
  `cycle_id` bigint(20) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `assigned_by_id` varchar(50) NOT NULL,
  `activity` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `scheduled_date` date NOT NULL,
  `status` enum('PENDING','COMPLETED','MISSED') DEFAULT 'PENDING',
  `parent_signature` tinyint(1) DEFAULT 0,
  `parent_signed_at` datetime DEFAULT NULL,
  `authority_signature_id` varchar(50) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cycle_id` (`cycle_id`),
  KEY `student_id` (`student_id`),
  KEY `assigned_by_id` (`assigned_by_id`),
  KEY `authority_signature_id` (`authority_signature_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `infractions`;
CREATE TABLE `infractions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(255) NOT NULL,
  `severity` enum('NORMAL','GRAVE') DEFAULT 'NORMAL',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `notices`;
CREATE TABLE `notices` (
  `id` char(36) NOT NULL,
  `cycle_id` bigint(20) NOT NULL,
  `author_id` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `type` enum('GENERAL','URGENT','EVENT') DEFAULT 'GENERAL',
  `target_audience` enum('ALL','TEACHERS','PARENTS') DEFAULT 'ALL',
  `requires_authorization` tinyint(1) DEFAULT 0,
  `event_date` date DEFAULT NULL,
  `event_time` time DEFAULT NULL,
  `date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`,`cycle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
PARTITION BY RANGE (`cycle_id`)
(
PARTITION p_legacy VALUES LESS THAN (20240801) ENGINE=InnoDB,
PARTITION p_2024_2025 VALUES LESS THAN (20250801) ENGINE=InnoDB,
PARTITION p_future VALUES LESS THAN MAXVALUE ENGINE=InnoDB
);

DROP TABLE IF EXISTS `notice_signatures`;
CREATE TABLE `notice_signatures` (
  `id` char(36) NOT NULL,
  `notice_id` char(36) NOT NULL,
  `parent_id` varchar(50) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `signed_at` datetime DEFAULT current_timestamp(),
  `authorized` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notice` (`notice_id`),
  KEY `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `regulations`;
CREATE TABLE `regulations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `reports`;
CREATE TABLE `reports` (
  `id` char(36) NOT NULL,
  `cycle_id` bigint(20) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `teacher_id` varchar(50) NOT NULL,
  `infraction_id` int(11) NOT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `date` datetime NOT NULL,
  `status` enum('PENDING_SIGNATURE','SIGNED') DEFAULT 'PENDING_SIGNATURE',
  `signed_at` datetime DEFAULT NULL,
  `signed_by_parent_id` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`,`cycle_id`),
  KEY `idx_student` (`student_id`),
  KEY `teacher_id` (`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
PARTITION BY RANGE (`cycle_id`)
(
PARTITION p_legacy VALUES LESS THAN (20240801) ENGINE=InnoDB,
PARTITION p_2024_2025 VALUES LESS THAN (20250801) ENGINE=InnoDB,
PARTITION p_future VALUES LESS THAN MAXVALUE ENGINE=InnoDB
);

DROP TABLE IF EXISTS `student_cycle_association`;
CREATE TABLE `student_cycle_association` (
  `id` char(36) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `cycle_id` bigint(20) NOT NULL,
  `class_group_id` char(36) NOT NULL,
  `status` enum('ACTIVE','DROPPED','GRADUATED') DEFAULT 'ACTIVE',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_student_cycle` (`student_id`,`cycle_id`),
  KEY `class_group_id` (`class_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `student_parents`;
CREATE TABLE `student_parents` (
  `student_id` varchar(50) NOT NULL,
  `parent_id` varchar(50) NOT NULL,
  `relationship` enum('PADRE','MADRE','TUTOR') NOT NULL DEFAULT 'TUTOR',
  PRIMARY KEY (`student_id`,`parent_id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `student_pii`;
CREATE TABLE `student_pii` (
  `student_id` varchar(50) NOT NULL,
  `address_encrypted` varbinary(512) DEFAULT NULL,
  `contact_phone_encrypted` varbinary(256) DEFAULT NULL,
  `allergies_encrypted` varbinary(1024) DEFAULT NULL,
  `medical_conditions_encrypted` varbinary(1024) DEFAULT NULL,
  `emergency_contact_encrypted` varbinary(512) DEFAULT NULL,
  PRIMARY KEY (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `citations`;
CREATE TABLE `citations` (
  `id` char(36) NOT NULL,
  `cycle_id` bigint(20) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `teacher_id` varchar(50) NOT NULL,
  `reason` text NOT NULL,
  `citation_date` datetime NOT NULL COMMENT 'Fecha y hora de la cita',
  `status` enum('PENDING', 'ATTENDED', 'NO_SHOW') DEFAULT 'PENDING',
  `parent_signature` tinyint(1) DEFAULT 0 COMMENT 'Firma de enterado del citatorio',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `teacher_id` (`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `exam_schedules`;
CREATE TABLE `exam_schedules` (
  `id` char(36) NOT NULL,
  `cycle_id` bigint(20) NOT NULL,
  `grade` varchar(10) NOT NULL,
  `group_name` varchar(10) NOT NULL,
  `period` enum('1','2','3') NOT NULL COMMENT 'Trimestre',
  `subject` varchar(100) NOT NULL,
  `exam_date` date NOT NULL,
  `day_of_week` enum('Lunes','Martes','Miércoles','Jueves','Viernes') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `community_services`
  ADD CONSTRAINT `community_services_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `cycles` (`id`),
  ADD CONSTRAINT `community_services_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `community_services_ibfk_3` FOREIGN KEY (`assigned_by_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `community_services_ibfk_4` FOREIGN KEY (`authority_signature_id`) REFERENCES `users` (`id`);

ALTER TABLE `student_cycle_association`
  ADD CONSTRAINT `student_cycle_association_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_cycle_association_ibfk_2` FOREIGN KEY (`class_group_id`) REFERENCES `class_groups` (`id`);

ALTER TABLE `student_parents`
  ADD CONSTRAINT `student_parents_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_parents_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `student_pii`
  ADD CONSTRAINT `fk_student_pii` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

ALTER TABLE `reports`
  ADD CONSTRAINT `reports_teacher_fk` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`);

ALTER TABLE `class_groups`
  ADD CONSTRAINT `class_groups_tutor_fk` FOREIGN KEY (`tutor_teacher_id`) REFERENCES `users` (`id`);

ALTER TABLE `citations`
  ADD CONSTRAINT `citations_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `citations_teacher_fk` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;