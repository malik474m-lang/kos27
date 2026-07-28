-- Профили postback (партнёрки)
CREATE TABLE IF NOT EXISTS `postback_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Название партнёрки',
  `slug` varchar(100) NOT NULL COMMENT 'Краткий ID для URL',
  `url_template` text DEFAULT NULL COMMENT 'Шаблон URL с макросами для справки',
  `notes` text DEFAULT NULL COMMENT 'Заметки по настройке',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Поле source в postback_conversions
ALTER TABLE `postback_conversions` ADD COLUMN `source` varchar(100) DEFAULT NULL COMMENT 'Название партнёрки';

-- Начальный профиль
INSERT INTO `postback_profiles` (`name`, `slug`, `notes`) VALUES
('Leads.su', 'leads', 'Глобальный postback в Инструменты → Глобальный postback')
ON DUPLICATE KEY UPDATE name=VALUES(name);
