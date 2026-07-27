-- Расширяем click_stats: UTM-метки, IP, страна
ALTER TABLE `click_stats`
  ADD COLUMN `ip` varchar(45) DEFAULT NULL AFTER `referer`,
  ADD COLUMN `country` varchar(10) DEFAULT NULL AFTER `ip`,
  ADD COLUMN `utm_source` varchar(255) DEFAULT NULL AFTER `country`,
  ADD COLUMN `utm_medium` varchar(255) DEFAULT NULL AFTER `utm_source`,
  ADD COLUMN `utm_campaign` varchar(255) DEFAULT NULL AFTER `utm_medium`,
  ADD COLUMN `utm_content` varchar(255) DEFAULT NULL AFTER `utm_campaign`,
  ADD COLUMN `utm_term` varchar(255) DEFAULT NULL AFTER `utm_content`,
  ADD COLUMN `page_from` varchar(500) DEFAULT NULL AFTER `utm_term`;

-- Таблица просмотров страниц (для конверсии)
CREATE TABLE IF NOT EXISTS `page_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page` varchar(500) NOT NULL,
  `offer_id` int(11) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `viewed_at` timestamp NULL DEFAULT current_timestamp(),
  `utm_source` varchar(255) DEFAULT NULL,
  `utm_medium` varchar(255) DEFAULT NULL,
  `utm_campaign` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_viewed_at` (`viewed_at`),
  KEY `idx_offer_id` (`offer_id`),
  KEY `idx_page` (`page`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
