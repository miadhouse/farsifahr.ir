-- ===================================================
-- FarsiFahr Live Chat System - Database Migration
-- Run this SQL in your database
-- ===================================================

CREATE TABLE IF NOT EXISTS `chat_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_token` VARCHAR(64) NOT NULL UNIQUE,
    `user_id` INT UNSIGNED NULL COMMENT 'NULL = guest',
    `guest_name` VARCHAR(100) NULL,
    `guest_email` VARCHAR(191) NULL,
    `status` ENUM('waiting','active','closed') NOT NULL DEFAULT 'waiting',
    `is_online` TINYINT(1) NOT NULL DEFAULT 1,
    `last_seen` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `admin_joined` TINYINT(1) NOT NULL DEFAULT 0,
    `unread_admin` INT NOT NULL DEFAULT 0 COMMENT 'Messages unread by admin',
    `unread_user` INT NOT NULL DEFAULT 0 COMMENT 'Messages unread by user',
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `page_url` VARCHAR(500) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_session_token` (`session_token`),
    INDEX `idx_status` (`status`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_last_seen` (`last_seen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat_messages` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id` INT UNSIGNED NOT NULL,
    `sender_type` ENUM('user','admin','system') NOT NULL DEFAULT 'user',
    `sender_id` INT UNSIGNED NULL COMMENT 'user_id for user, NULL for admin/system',
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_session_id` (`session_id`),
    INDEX `idx_created_at` (`created_at`),
    FOREIGN KEY (`session_id`) REFERENCES `chat_sessions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat_quick_replies` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(100) NOT NULL,
    `message` TEXT NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default quick replies
INSERT INTO `chat_quick_replies` (`title`, `message`, `sort_order`) VALUES
('خوش‌آمدگویی', 'سلام! خوش‌آمدید به فارسی‌فهر. چطور می‌توانم کمکتان کنم؟', 1),
('لطفاً صبر کنید', 'ممنون که تماس گرفتید. لطفاً چند لحظه صبر کنید.', 2),
('مشکل را بررسی می‌کنم', 'مشکل شما را بررسی می‌کنم و به زودی پاسخ می‌دهم.', 3),
('اشتراک VIP', 'برای خرید اشتراک VIP می‌توانید به صفحه اشتراک مراجعه کنید یا از طریق واتس‌اپ با ما در تماس باشید.', 4),
('پایان چت', 'متشکرم که با ما در تماس بودید. موفق باشید! 🙏', 5);
