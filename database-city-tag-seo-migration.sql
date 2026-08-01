-- SEO-тексты для страниц город + тег
CREATE TABLE IF NOT EXISTS `city_tag_seo_texts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `city_slug` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'microloans',
  `tag_slug` varchar(255) NOT NULL,
  `meta_title` varchar(500) DEFAULT NULL,
  `seo_h1` varchar(500) DEFAULT NULL,
  `seo_text` text DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `generated_by` enum('template','yandexgpt','manual') NOT NULL DEFAULT 'template',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `city_tag_cat` (`city_slug`, `category`, `tag_slug`),
  KEY `idx_city_slug` (`city_slug`),
  KEY `idx_tag_slug` (`tag_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
