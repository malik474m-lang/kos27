-- ============================================================
-- Сервер лицензирования — Схема БД
-- MySQL 5.7+ / MariaDB 10.3+
-- ============================================================

CREATE DATABASE IF NOT EXISTS `license_server` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `license_server`;

-- Лицензии
CREATE TABLE IF NOT EXISTS `licenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `license_key` varchar(64) NOT NULL COMMENT 'Уникальный ключ лицензии',
  `domain` varchar(255) NOT NULL COMMENT 'Привязанный домен (без www, без протокола)',
  `product` varchar(100) NOT NULL DEFAULT 'kosmozaim' COMMENT 'Продукт',
  `plan` enum('trial','basic','pro','enterprise') NOT NULL DEFAULT 'basic' COMMENT 'Тарифный план',
  `status` enum('active','suspended','expired','revoked') NOT NULL DEFAULT 'active',
  `owner_name` varchar(255) DEFAULT NULL COMMENT 'Имя владельца',
  `owner_email` varchar(255) DEFAULT NULL COMMENT 'Email владельца',
  `issued_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'NULL = бессрочная',
  `last_check_at` timestamp NULL DEFAULT NULL COMMENT 'Последний heartbeat',
  `last_check_ip` varchar(45) DEFAULT NULL,
  `activations_count` int(11) NOT NULL DEFAULT 0,
  `max_activations` int(11) NOT NULL DEFAULT 1,
  `features` JSON DEFAULT NULL COMMENT 'Доступные функции {"autogen":true,"analytics":true}',
  `notes` text DEFAULT NULL COMMENT 'Заметки администратора',
  `hardware_hash` varchar(64) DEFAULT NULL COMMENT 'Хэш окружения для привязки',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_license_key` (`license_key`),
  KEY `idx_domain` (`domain`),
  KEY `idx_status` (`status`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Лог активаций/проверок
CREATE TABLE IF NOT EXISTS `license_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `license_id` int(11) DEFAULT NULL,
  `license_key` varchar(64) DEFAULT NULL,
  `action` enum('activate','verify','deactivate','heartbeat','denied','error') NOT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `request_data` text DEFAULT NULL,
  `response_code` int(11) DEFAULT NULL,
  `message` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_license` (`license_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`),
  KEY `idx_ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Rate-limit таблица
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `endpoint` varchar(100) NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 1,
  `window_start` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ip_endpoint` (`ip`, `endpoint`),
  KEY `idx_window` (`window_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Администраторы сервера лицензий
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `totp_secret` varchar(64) DEFAULT NULL,
  `totp_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Начальный админ (пароль: LicAdmin2024! — СМЕНИТЬ после установки!)
INSERT INTO `admins` (`username`, `password_hash`) VALUES 
('admin', '$2y$12$K8GpQxVhXvYkJzKlMnOpOeR7sT3uW1xYzA2bC4dE6fG8hI0jK2lM')
ON DUPLICATE KEY UPDATE username=username;
