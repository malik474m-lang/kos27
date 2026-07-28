-- Раздел футера для категории: products | tools
ALTER TABLE `categories` ADD COLUMN `footer_section` varchar(20) NOT NULL DEFAULT 'products';

-- Раскладываем текущие навигационные элементы
UPDATE `categories` SET `footer_section` = 'products' WHERE `slug` IN ('zajmy','kredity','karty-kreditnye','karty-debetovye','strahovka','vklady');
UPDATE `categories` SET `footer_section` = 'tools' WHERE `slug` IN ('compare','calculator','articles','faq','glossary','novye-mfo');
