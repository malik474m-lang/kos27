CREATE TABLE IF NOT EXISTS `offer_link_checks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `offer_id` int(11) NOT NULL,
  `url` text NOT NULL,
  `http_code` int(11) DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'unknown' COMMENT 'ok|redirect|broken|timeout|error',
  `final_url` text DEFAULT NULL,
  `response_time_ms` int(11) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `checked_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_offer_id` (`offer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_checked_at` (`checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
