-- Динамические категории
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Название: Займы, Кредиты, Страховка...',
  `slug` varchar(255) NOT NULL COMMENT 'URL slug: zajmy, kredity, strahovka...',
  `icon` varchar(20) DEFAULT NULL COMMENT 'Эмодзи иконка',
  `h1` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `meta_title` varchar(500) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `seo_text` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL COMMENT 'Родительская категория для подкатегорий',
  `show_in_header` tinyint(1) NOT NULL DEFAULT 1,
  `show_in_footer` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Меняем category в offers с enum на varchar для поддержки произвольных категорий
ALTER TABLE `offers` MODIFY COLUMN `category` varchar(100) NOT NULL DEFAULT 'microloans';

-- Начальные категории (из текущего хардкода)
INSERT INTO `categories` (`name`, `slug`, `icon`, `h1`, `meta_title`, `meta_description`, `show_in_header`, `show_in_footer`, `sort_order`) VALUES
('Займы', 'zajmy', '💵', 'Займы онлайн', 'Займы онлайн — Подбор микрозаймов на карту | Космозайм', 'Подберите выгодный микрозайм онлайн.', 1, 1, 1),
('Кредиты', 'kredity', '🏦', 'Кредиты онлайн', 'Кредиты онлайн — Сравнение банковских кредитов | Космозайм', 'Сравните условия банковских кредитов.', 1, 1, 2),
('Кредитные карты', 'karty-kreditnye', '💳', 'Кредитные карты', 'Кредитные карты онлайн — Сравнение | Космозайм', 'Сравните кредитные карты с льготным периодом.', 1, 1, 3),
('Дебетовые карты', 'karty-debetovye', '🪪', 'Дебетовые карты', 'Дебетовые карты — Космозайм', 'Сравните дебетовые карты с кэшбеком.', 1, 1, 4)
ON DUPLICATE KEY UPDATE name=VALUES(name);
