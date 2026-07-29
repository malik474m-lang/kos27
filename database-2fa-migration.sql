-- Двухфакторная авторизация
ALTER TABLE `admin_users` ADD COLUMN `totp_secret` varchar(64) DEFAULT NULL COMMENT 'TOTP секрет (base32)';
ALTER TABLE `admin_users` ADD COLUMN `totp_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT '2FA включена';
ALTER TABLE `admin_users` ADD COLUMN `totp_backup_codes` text DEFAULT NULL COMMENT 'Резервные коды (JSON)';
