-- Шаблоны полей для категорий
ALTER TABLE `categories` ADD COLUMN `field_templates` text DEFAULT NULL COMMENT 'JSON шаблоны доп. полей [{label,visible}]';

-- Начальные шаблоны
UPDATE `categories` SET `field_templates` = '[{"label":"Льготный период","visible":true}]' WHERE `slug` = 'zajmy';
UPDATE `categories` SET `field_templates` = '[{"label":"Льготный период","visible":true}]' WHERE `slug` = 'kredity';
UPDATE `categories` SET `field_templates` = '[{"label":"Льготный период","visible":true},{"label":"Кэшбэк/мес.","visible":true},{"label":"Годовое обслуживание","visible":true},{"label":"Бонусы","visible":false},{"label":"Выпуск карты","visible":false}]' WHERE `slug` = 'karty-kreditnye';
UPDATE `categories` SET `field_templates` = '[{"label":"Кэшбэк/мес.","visible":true},{"label":"Годовое обслуживание","visible":true},{"label":"Баллы/мес.","visible":false},{"label":"Бонусы","visible":false},{"label":"Выпуск карты","visible":false},{"label":"Обслуживание","visible":true}]' WHERE `slug` = 'karty-debetovye';
