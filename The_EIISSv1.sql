-- =====================================================================
-- EIISS - Entrepreneur Ideas Investment Support System Database Schema
-- Exported as a Plain-Text SQL Script (Openable in any Text Editor)
-- File Name: The_EIISSv1.sql
-- Compatible with MySQL / MariaDB (XAMPP default server)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `The_EIISSv1` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `The_EIISSv1`;

-- ---------------------------------------------------------------------
-- Table: users
-- Stores account profiles for Entrepreneurs, Investors, and Admins
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'entrepreneur',
  `organization` VARCHAR(255) DEFAULT '',
  `sector` VARCHAR(100) DEFAULT '',
  `location` VARCHAR(255) DEFAULT '',
  `bio` TEXT,
  `id_type` VARCHAR(100) DEFAULT '',
  `id_number` VARCHAR(100) DEFAULT '',
  `phone_number` VARCHAR(100) DEFAULT '',
  `verified` INT DEFAULT 0,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `id_document` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: ideas
-- Stores notarized entrepreneurial pitch ideas and scoring breakdowns
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ideas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `sector` VARCHAR(100) NOT NULL DEFAULT '',
  `status` VARCHAR(50) NOT NULL DEFAULT 'Under Review',
  `score` DOUBLE DEFAULT 0,
  `views` INT DEFAULT 0,
  `interests` INT DEFAULT 0,
  `capital_required` DOUBLE DEFAULT 0,
  `expected_roi` DOUBLE DEFAULT 0,
  `blockchain_hash` VARCHAR(255) DEFAULT '',
  `submitted_date` VARCHAR(50) DEFAULT '',
  `access_type` VARCHAR(50) DEFAULT 'free',
  `access_price` DOUBLE DEFAULT 0,
  `attachment_price` DOUBLE DEFAULT 0,
  `earnings` DOUBLE DEFAULT 0,
  `entrepreneur_email` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `problem_statement` TEXT,
  `solution` TEXT,
  `target_market` TEXT,
  `competitive_advantage` TEXT,
  `timeline` INT DEFAULT 12,
  `risk_level` VARCHAR(50) DEFAULT 'Medium',
  `team_size` INT DEFAULT 1,
  `stage` VARCHAR(50) DEFAULT 'Concept',
  `score_market` DOUBLE DEFAULT 0,
  `score_innovation` DOUBLE DEFAULT 0,
  `score_feasibility` DOUBLE DEFAULT 0,
  `score_financial` DOUBLE DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`entrepreneur_email`) REFERENCES `users`(`email`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: idea_attachments
-- Stores links to uploaded business plan documents and proof attachments
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `idea_attachments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `idea_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `size` VARCHAR(50) DEFAULT '',
  `type` VARCHAR(50) DEFAULT '',
  FOREIGN KEY (`idea_id`) REFERENCES `ideas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: transactions
-- Records unlock fees paid by investors to access concept files
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `investor_name` VARCHAR(255) NOT NULL,
  `investor_email` VARCHAR(255) NOT NULL,
  `idea_id` INT NOT NULL,
  `idea_title` VARCHAR(255) NOT NULL,
  `amount` DOUBLE NOT NULL,
  `date` VARCHAR(50) NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `entrepreneur_email` VARCHAR(255) NOT NULL,
  `payment_method` VARCHAR(50) DEFAULT 'card',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: notifications
-- Tracks system alerts and investor message requests sent to users
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_email` VARCHAR(255) NOT NULL,
  `type` VARCHAR(50) DEFAULT 'info',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `time_ago` VARCHAR(50) DEFAULT 'Just now',
  `is_read` INT DEFAULT 0,
  `sender` VARCHAR(255) DEFAULT 'System Admin',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: chats
-- Coordinates communication sessions between Investors and Entrepreneurs
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chats` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `participant1_email` VARCHAR(255) NOT NULL,
  `participant2_email` VARCHAR(255) NOT NULL,
  `idea_title` VARCHAR(255) DEFAULT '',
  `last_message` TEXT,
  `time_ago` VARCHAR(50) DEFAULT '',
  `unread_investor` INT DEFAULT 0,
  `unread_entrepreneur` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: chat_messages
-- Stores text messages exchanged inside active chats
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `chat_id` INT NOT NULL,
  `sender_email` VARCHAR(255) NOT NULL,
  `text` TEXT NOT NULL,
  `sent_time` VARCHAR(50) DEFAULT '',
  `sent_date` VARCHAR(50) DEFAULT '',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`chat_id`) REFERENCES `chats`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: unlocked_ideas
-- Tracks which concepts and attachments are unlocked by specific investors
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `unlocked_ideas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `investor_email` VARCHAR(255) NOT NULL,
  `idea_id` INT NOT NULL,
  `unlock_type` VARCHAR(50) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_unlock` (`investor_email`, `idea_id`, `unlock_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: investor_preferences
-- Tracks matching criteria settings for automatic venture matchmaking
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `investor_preferences` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `investor_email` VARCHAR(255) UNIQUE NOT NULL,
  `min_investment` DOUBLE DEFAULT 0,
  `max_investment` DOUBLE DEFAULT 1000000,
  `preferred_sectors` TEXT,
  `risk_tolerance` VARCHAR(50) DEFAULT 'medium',
  `preferred_stages` TEXT,
  `min_roi` DOUBLE DEFAULT 0,
  `location` VARCHAR(255) DEFAULT '',
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SEEDING SAMPLE DATA
-- =================================------------------------------------

-- Seed Admin Profile
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `organization`, `sector`, `location`, `bio`, `id_type`, `id_number`, `phone_number`, `verified`)
VALUES (
  'System Administrator',
  'admin@eiiss.co.tz',
  '$2y$10$kGWuL4P3Abj1k9WfYOLBh.Pnl2QD.6ls97mhwF.zMaTc0qLFnpl2C', -- bcrypt hash for 'Admin@2026!'
  'admin',
  'EIISS Operations',
  'technology',
  'Dar es Salaam, Tanzania',
  'EIISS Platform Administrator',
  'Passport',
  'ADMIN-001',
  '+255 700 000 000',
  1
) ON DUPLICATE KEY UPDATE `id`=`id`;
