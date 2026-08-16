-- КосмоБонус: бонусная программа

-- Акция КосмоБонус для офферов
ALTER TABLE `offers`
    ADD COLUMN `kosmobonus_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `license`,
    ADD COLUMN `kosmobonus_amount` INT NOT NULL DEFAULT 0 COMMENT 'Сумма бонуса в рублях' AFTER `kosmobonus_enabled`,
    ADD COLUMN `kosmobonus_conditions` TEXT DEFAULT NULL COMMENT 'Условия акции для этого оффера' AFTER `kosmobonus_amount`;

-- Бонусный баланс пользователей
ALTER TABLE `users`
    ADD COLUMN `bonus_balance` INT NOT NULL DEFAULT 0 COMMENT 'Баланс бонусов (1 бонус = 1 рубль)' AFTER `agreed_finance`;

-- Лог бонусных операций
CREATE TABLE IF NOT EXISTS `bonus_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `offer_id` int(11) DEFAULT NULL,
  `click_stat_id` int(11) DEFAULT NULL,
  `postback_id` int(11) DEFAULT NULL,
  `amount` int NOT NULL COMMENT 'Сумма бонусов (+/-)',
  `type` enum('accrual','withdrawal','manual','reversal') NOT NULL DEFAULT 'accrual',
  `status` enum('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  `description` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `confirmed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_click_stat_id` (`click_stat_id`),
  KEY `idx_postback_id` (`postback_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
