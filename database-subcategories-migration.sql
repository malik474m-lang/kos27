CREATE TABLE IF NOT EXISTS `subcategories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `category` enum('microloans','credits','credit_cards','debit_cards') NOT NULL DEFAULT 'microloans',
  `icon` varchar(10) DEFAULT '📋',
  `h1` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `seo_text` text DEFAULT NULL,
  `filter_rules` text DEFAULT NULL COMMENT 'JSON: {"term_max_days":20,"free_term_days_min":1,...}',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug_category` (`slug`, `category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `subcategory_city_seo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subcategory_id` int(11) NOT NULL,
  `city_slug` varchar(100) NOT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `seo_h1` varchar(500) DEFAULT NULL,
  `seo_text` text DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `generated_by` enum('template','ai','manual') NOT NULL DEFAULT 'template',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `subcat_city` (`subcategory_id`, `city_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
