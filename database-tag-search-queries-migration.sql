ALTER TABLE `offer_tags` ADD COLUMN `search_queries` text DEFAULT NULL COMMENT 'Фразы для автоперелинковки, по одной на строку';
