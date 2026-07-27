-- Добавляем sort_order в articles (если нет)
ALTER TABLE `articles` ADD COLUMN `sort_order` int(11) NOT NULL DEFAULT 0;
