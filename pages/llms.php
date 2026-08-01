<?php
/**
 * Динамический llms.txt - генерируется из БД
 * Автоматически обновляется при изменении контента
 */

$db = getDB();
$settings = getSiteSettings();
$siteName = $settings['site_name'] ?? 'Космозайм';
$siteUrl = SITE_URL;

// Получаем данные из БД
$offers = $db->query("SELECT title, slug, category, description FROM offers WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 20")->fetchAll();
$articles = $db->query("SELECT title, slug, excerpt FROM articles WHERE is_published = 1 ORDER BY created_at DESC LIMIT 10")->fetchAll();
$tags = $db->query("SELECT title, slug, category, icon FROM offer_tags WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

// Динамические категории
$categories = $db->query("SELECT name, slug, description FROM categories WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

// Группируем теги по категориям
$tagsByCategory = [];
foreach ($tags as $tag) {
    $tagsByCategory[$tag['category']][] = $tag;
}

$catNames = [
    'microloans' => 'Займы',
    'credits' => 'Кредиты',
    'credit_cards' => 'Кредитные карты',
    'debit_cards' => 'Дебетовые карты'
];

$catUrls = [
    'microloans' => '/zajmy',
    'credits' => '/kredity',
    'credit_cards' => '/karty/kreditnye',
    'debit_cards' => '/karty/debetovye'
];

// Статистика
$totalOffers = $db->query("SELECT COUNT(*) as cnt FROM offers WHERE is_active = 1")->fetch()['cnt'];
$totalArticles = $db->query("SELECT COUNT(*) as cnt FROM articles WHERE is_published = 1")->fetch()['cnt'];

?>
# <?= $siteName ?> — сервис подбора финансовых продуктов

> <?= $siteName ?> — это агрегатор и сравнительный сервис для подбора займов, кредитов, кредитных и дебетовых карт в России. Помогаем пользователям найти лучшие финансовые предложения от проверенных МФО и банков.

## Статистика сайта

- Активных предложений: <?= $totalOffers ?>

- Полезных статей: <?= $totalArticles ?>

- Обновлено: <?= date('d.m.Y H:i') ?>


## Основные разделы

- [Займы онлайн](<?= $siteUrl ?>/zajmy): Микрозаймы от МФО, включая займы без отказа, с плохой кредитной историей, на карту мгновенно
- [Кредиты](<?= $siteUrl ?>/kredity): Банковские кредиты наличными и потребительские кредиты
- [Кредитные карты](<?= $siteUrl ?>/karty/kreditnye): Кредитные карты с льготным периодом и кэшбэком
- [Дебетовые карты](<?= $siteUrl ?>/karty/debetovye): Дебетовые карты с кэшбэком и процентом на остаток
- [Калькулятор займа](<?= $siteUrl ?>/calculator): Расчёт стоимости займа и подбор предложений
- [Статьи](<?= $siteUrl ?>/articles): Полезные материалы о финансах, кредитах и займах

## Актуальные предложения

<?php foreach ($offers as $offer): ?>
- [<?= $offer['title'] ?>](<?= $siteUrl ?>/offer/<?= $offer['slug'] ?>): <?= mb_substr($offer['description'] ?? '', 0, 100) ?>

<?php endforeach; ?>

## Типы продуктов

<?php foreach ($tagsByCategory as $cat => $catTags): ?>
### <?= $catNames[$cat] ?? $cat ?>

<?php foreach ($catTags as $tag): ?>
- [<?= $tag['title'] ?>](<?= $siteUrl ?><?= $catUrls[$cat] ?? '/zajmy' ?>/type/<?= $tag['slug'] ?>)
<?php endforeach; ?>

<?php endforeach; ?>

## Последние статьи

<?php foreach ($articles as $article): ?>
- [<?= $article['title'] ?>](<?= $siteUrl ?>/articles/<?= $article['slug'] ?>): <?= mb_substr($article['excerpt'] ?? '', 0, 150) ?>

<?php endforeach; ?>

## Что мы предлагаем

- Сравнение условий от разных организаций
- Актуальные процентные ставки и ПСК
- Информация о беспроцентных периодах
- Фильтры по сумме, сроку, категории заёмщика
- Калькулятор для расчёта переплаты
- Отзывы реальных клиентов
- Статьи с советами по финансовой грамотности

## Для кого

Сервис подходит для граждан России, ищущих:
- Быстрые деньги до зарплаты
- Займ без похода в банк
- Сравнение условий разных МФО
- Информацию о реальных ставках и условиях

## Важно

- Все организации проверены в реестре ЦБ РФ
- Мы не выдаём займы — только помогаем выбрать
- Информация на сайте носит справочный характер
- Перед оформлением читайте договор

## Контакты

- Сайт: <?= $siteUrl ?>

- Политика конфиденциальности: <?= $siteUrl ?>/privacy
- Пользовательское соглашение: <?= $siteUrl ?>/terms
