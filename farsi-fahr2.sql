-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 05, 2025 at 07:19 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `farsi-fahr2`
--

DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS `cleanup_old_logs`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `cleanup_old_logs` ()   BEGIN
    -- حذف لاگ‌های قدیمی‌تر از 90 روز
    DELETE FROM user_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
    
    -- حذف تلاش‌های ورود قدیمی‌تر از 30 روز
    DELETE FROM login_attempts 
    WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- حذف سشن‌های منقضی شده
    DELETE FROM sessions 
    WHERE last_activity < DATE_SUB(NOW(), INTERVAL 7 DAY);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email_ip` (`email`,`ip_address`),
  KEY `idx_attempted_at` (`attempted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `recent_successful_logins`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `recent_successful_logins`;
CREATE TABLE IF NOT EXISTS `recent_successful_logins` (
`id` int
,`name` varchar(100)
,`email` varchar(255)
,`ip_address` varchar(45)
,`login_time` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('user','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `google_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT '0',
  `verification_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `idx_google_id` (`google_id`),
  KEY `idx_reset_token` (`reset_token`),
  KEY `idx_verification_token` (`verification_token`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `name`, `role`, `google_id`, `email_verified`, `verification_token`, `reset_token`, `reset_expires`, `created_at`, `updated_at`) VALUES
(1, 'admin@example.com', '$2y$10$ziReKmEFU/6yFqo.CMBYF.vzyWij73PAcLyFaYuYIzYb6aGSAj7Tq', 'مدیر سیستم', 'admin', NULL, 1, NULL, NULL, NULL, '2025-07-31 18:40:38', '2025-07-31 18:55:11'),
(2, 'miadaleali@gmail.com', '$2y$10$yOs37tfxLgz95jdg8LS7U.b5J4pyV/a4dhyjUYBJ87rIPgJhSSjTm', 'miad', 'user', NULL, 0, '7e8a29dd8c30eaf68c11c46813f7843f3aa2d10a7dc5d29a7095d6d74ebb20e6', NULL, NULL, '2025-07-31 18:49:37', '2025-08-04 20:42:10'),
(12, 'miadhouse@gmail.com', '$2y$10$YmKDelia74Y7p1dEjm6b9u.XRJfR5Wi.DRIl1zeUOMyUYCPiAUZ5e', 'miad', 'user', NULL, 1, NULL, NULL, NULL, '2025-08-04 09:34:20', '2025-08-04 09:34:53');

-- --------------------------------------------------------

--
-- Table structure for table `user_logs`
--

DROP TABLE IF EXISTS `user_logs`;
CREATE TABLE IF NOT EXISTS `user_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `status` enum('success','failed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_logs`
--

INSERT INTO `user_logs` (`id`, `user_id`, `email`, `action`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES
(1, NULL, 'miadaleali@gmail.com', 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'failed', '2025-07-31 18:48:09'),
(44, 2, 'miadaleali@gmail.com', 'password_reset', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'success', '2025-08-04 20:42:10');

-- --------------------------------------------------------

--
-- Structure for view `recent_successful_logins`
--
DROP TABLE IF EXISTS `recent_successful_logins`;

DROP VIEW IF EXISTS `recent_successful_logins`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `recent_successful_logins`  AS SELECT `u`.`id` AS `id`, `u`.`name` AS `name`, `u`.`email` AS `email`, `ul`.`ip_address` AS `ip_address`, `ul`.`created_at` AS `login_time` FROM (`user_logs` `ul` join `users` `u` on((`ul`.`user_id` = `u`.`id`))) WHERE ((`ul`.`action` = 'login') AND (`ul`.`status` = 'success')) ORDER BY `ul`.`created_at` DESC ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_logs`
--
ALTER TABLE `user_logs`
  ADD CONSTRAINT `fk_user_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

DELIMITER $$
--
-- Events
--
DROP EVENT IF EXISTS `auto_cleanup`$$
CREATE DEFINER=`root`@`localhost` EVENT `auto_cleanup` ON SCHEDULE EVERY 1 DAY STARTS '2025-08-01 02:00:00' ON COMPLETION NOT PRESERVE ENABLE DO CALL cleanup_old_logs()$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
