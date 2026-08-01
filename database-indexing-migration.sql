-- Таблица для отслеживания URL и ускорения индексации
CREATE TABLE IF NOT EXISTS `url_index_tracker` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(500) NOT NULL,
  `url_type` enum('offer','article','city','city_tag','category','static') NOT NULL DEFAULT 'static',
  `first_seen` timestamp NULL DEFAULT current_timestamp(),
  `last_modified` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `indexed_yandex` tinyint(1) NOT NULL DEFAULT 0,
  `indexed_google` tinyint(1) NOT NULL DEFAULT 0,
  `submitted_yandex` timestamp NULL DEFAULT NULL,
  `submitted_google` timestamp NULL DEFAULT NULL,
  `priority` decimal(2,1) NOT NULL DEFAULT 0.5,
  `changefreq` enum('always','hourly','daily','weekly','monthly','yearly','never') NOT NULL DEFAULT 'weekly',
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`),
  KEY `idx_type` (`url_type`),
  KEY `idx_submitted` (`submitted_yandex`, `submitted_google`),
  KEY `idx_last_modified` (`last_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Лог отправок на индексацию
CREATE TABLE IF NOT EXISTS `indexing_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service` enum('yandex','google','bing') NOT NULL,
  `action` enum('submit','reindex','check') NOT NULL DEFAULT 'submit',
  `urls_count` int(11) NOT NULL DEFAULT 0,
  `status` enum('success','partial','error') NOT NULL DEFAULT 'success',
  `response` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_service_date` (`service`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
