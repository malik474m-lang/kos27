CREATE TABLE IF NOT EXISTS `offer_link_checks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `offer_id` int(11) NOT NULL,
  `url` text NOT NULL,
  `http_code` int(11) DEFAULT NULL,
  `final_url` text DEFAULT NULL,
  `redirect_count` int(11) NOT NULL DEFAULT 0,
  `is_ok` tinyint(1) NOT NULL DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `checked_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_offer_id` (`offer_id`),
  KEY `idx_checked_at` (`checked_at`),
  KEY `idx_is_ok` (`is_ok`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
