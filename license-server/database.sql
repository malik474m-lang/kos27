-- KosmoEngine License Server Database
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `totp_secret` VARCHAR(64) DEFAULT NULL,
    `totp_enabled` TINYINT(1) DEFAULT 0,
    `backup_codes` TEXT DEFAULT NULL,
    `last_login` DATETIME DEFAULT NULL,
    `last_ip` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `plans` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `description` TEXT,
    `price` DECIMAL(10,2) DEFAULT 0.00,
    `duration_days` INT DEFAULT NULL,
    `max_domains` INT DEFAULT 1,
    `features` JSON DEFAULT NULL,
    `is_trial` TINYINT(1) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `licenses` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `license_key` VARCHAR(64) NOT NULL UNIQUE,
    `plan_id` INT UNSIGNED NOT NULL,
    `domain` VARCHAR(255) DEFAULT NULL,
    `owner_name` VARCHAR(255) DEFAULT NULL,
    `owner_email` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('pending','active','suspended','expired','blocked') DEFAULT 'pending',
    `activated_at` DATETIME DEFAULT NULL,
    `expires_at` DATETIME DEFAULT NULL,
    `last_check` DATETIME DEFAULT NULL,
    `last_check_ip` VARCHAR(45) DEFAULT NULL,
    `check_count` INT UNSIGNED DEFAULT 0,
    `block_reason` VARCHAR(255) DEFAULT NULL,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_key` (`license_key`),
    INDEX `idx_domain` (`domain`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `license_checks` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `license_id` INT UNSIGNED DEFAULT NULL,
    `license_key` VARCHAR(64) NOT NULL,
    `domain` VARCHAR(255) NOT NULL,
    `ip` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `status` ENUM('success','invalid_key','domain_mismatch','expired','suspended','blocked') NOT NULL,
    `response_code` VARCHAR(50) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) DEFAULT NULL,
    `ip` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `success` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50) DEFAULT NULL,
    `entity_id` INT UNSIGNED DEFAULT NULL,
    `old_data` JSON DEFAULT NULL,
    `new_data` JSON DEFAULT NULL,
    `ip` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `plans` (`name`, `slug`, `description`, `price`, `duration_days`, `is_trial`, `sort_order`) VALUES
('Trial', 'trial', 'Пробный период 14 дней', 0, 14, 1, 1),
('Starter', 'starter', 'Базовый на 1 месяц', 990, 30, 0, 2),
('Professional', 'professional', 'Профессиональный на 6 месяцев', 4990, 180, 0, 3),
('Business', 'business', 'Бизнес на 1 год', 9990, 365, 0, 4),
('Unlimited', 'unlimited', 'Безлимитная лицензия', 29990, NULL, 0, 5);

-- Пароль: admin123 (ОБЯЗАТЕЛЬНО СМЕНИТЬ!)
INSERT INTO `admins` (`username`, `password_hash`) VALUES
('admin', '$2y$10$N9qo8uLOickgx2ZMRZoMy.MqrqRqEuBv1WPn4MZKNqNxdPaXmNeDy');
