-- SEO-тексты для городов
CREATE TABLE IF NOT EXISTS `city_seo_texts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `city_slug` varchar(255) NOT NULL,
  `category` enum('microloans','credits','credit_cards','debit_cards') NOT NULL DEFAULT 'microloans',
  `seo_title` varchar(500) DEFAULT NULL,
  `seo_h1` varchar(500) DEFAULT NULL,
  `seo_text` text DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `generated_by` enum('template','yandexgpt','manual') NOT NULL DEFAULT 'template',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `city_cat` (`city_slug`, `category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
