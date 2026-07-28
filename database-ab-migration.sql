-- A/B тесты кнопки Оформить
CREATE TABLE IF NOT EXISTS `ab_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ab_variants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `test_id` int(11) NOT NULL,
  `label` varchar(100) NOT NULL COMMENT 'Текст кнопки',
  `color` varchar(50) NOT NULL DEFAULT '#059669' COMMENT 'Цвет фона',
  `impressions` int(11) NOT NULL DEFAULT 0,
  `clicks` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_test_id` (`test_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Поле ab_variant в click_stats
ALTER TABLE `click_stats` ADD COLUMN `ab_variant_id` int(11) DEFAULT NULL;

-- Начальный тест с вариантами
INSERT INTO `ab_tests` (`name`, `is_active`) VALUES ('Кнопка Оформить', 1);
SET @test_id = LAST_INSERT_ID();
INSERT INTO `ab_variants` (`test_id`, `label`, `color`) VALUES
(@test_id, 'Оформить', '#059669'),
(@test_id, 'Получить деньги', '#1a56db'),
(@test_id, 'Оформить заявку', '#7c3aed'),
(@test_id, 'Получить займ', '#dc2626');
