CREATE TABLE IF NOT EXISTS `article_inline_cta_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` enum('impression','click') NOT NULL DEFAULT 'impression',
  `article_slug` varchar(500) NOT NULL,
  `offer_id` int(11) NOT NULL,
  `variant` varchar(10) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `session_key` varchar(100) DEFAULT NULL,
  `click_stat_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_article_slug` (`article_slug`(100)),
  KEY `idx_offer_id` (`offer_id`),
  KEY `idx_variant` (`variant`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_click_stat_id` (`click_stat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
