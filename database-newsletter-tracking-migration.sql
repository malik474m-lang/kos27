-- Трекинг открытий и кликов рассылки
CREATE TABLE IF NOT EXISTS `newsletter_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `newsletter_id` int(11) NOT NULL,
  `subscriber_id` int(11) NOT NULL,
  `event_type` enum('open','click') NOT NULL,
  `url` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_newsletter_id` (`newsletter_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
