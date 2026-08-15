-- Статистика PWA приложения
CREATE TABLE IF NOT EXISTS `pwa_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `platform` varchar(50) DEFAULT NULL,
  `device_model` varchar(100) DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `os_version` varchar(50) DEFAULT NULL,
  `is_standalone` tinyint(1) DEFAULT 0,
  `screen_width` int(11) DEFAULT NULL,
  `screen_height` int(11) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `page_url` varchar(500) DEFAULT NULL,
  `referrer` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_platform` (`platform`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
