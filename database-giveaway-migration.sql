-- Розыгрыши
CREATE TABLE IF NOT EXISTS `giveaways` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(500) NOT NULL,
  `description` text DEFAULT NULL,
  `page_subtitle` varchar(500) DEFAULT 'Оформи и получи одобрение любого партнера на сайте и получи приз!',
  `page_steps` text DEFAULT NULL COMMENT 'JSON шагов условий',
  `page_rules` text DEFAULT NULL COMMENT 'JSON обязательных условий',
  `prize_percent` decimal(5,2) NOT NULL DEFAULT 10.00 COMMENT 'Процент от суммы конверсий',
  `start_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  `draw_at` datetime DEFAULT NULL COMMENT 'Дата и время розыгрыша в прямом эфире',
  `status` enum('planned','active','drawing','finished','cancelled') NOT NULL DEFAULT 'planned',
  `winner_id` int(11) DEFAULT NULL,
  `prize_amount` decimal(10,2) DEFAULT 0 COMMENT 'Финальная сумма приза',
  `total_conversions_amount` decimal(10,2) DEFAULT 0 COMMENT 'Общая сумма конверсий за период',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_dates` (`start_at`, `end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Участники розыгрыша
CREATE TABLE IF NOT EXISTS `giveaway_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `giveaway_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(255) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `offer_id` int(11) NOT NULL,
  `offer_title` varchar(255) DEFAULT NULL,
  `conversion_id` int(11) DEFAULT NULL,
  `payout` decimal(10,2) DEFAULT 0,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_conv` (`giveaway_id`, `conversion_id`),
  KEY `idx_giveaway` (`giveaway_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
