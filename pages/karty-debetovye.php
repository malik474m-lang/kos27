<?php
require_once __DIR__ . '/../includes/offer-card.php';
require_once __DIR__ . '/../includes/autolinks.php';
require_once __DIR__ . '/../includes/sticky-cta.php';
require_once __DIR__ . '/../data/cities.php';

$db = getDB();
$offers = $db->query("SELECT * FROM offers WHERE is_active = 1 AND category = 'debit_cards' ORDER BY sort_order ASC")->fetchAll();
$debitTags = $db->query("SELECT * FROM offer_tags WHERE is_active = 1 AND category = 'debit_cards' ORDER BY sort_order ASC")->fetchAll();

$pageTitle = 'Дебетовые карты | ' . SITE_NAME;
$metaDescription = 'Сравните дебетовые карты с кэшбеком и процентом на остаток.';
$breadcrumbs = [breadcrumbItem('Главная', '/'), breadcrumbItem('Дебетовые карты', '/karty/debetovye')];

$debitFaqs = [
    ['q' => 'Чем дебетовая карта отличается от кредитной?', 'a' => 'Дебетовая карта позволяет тратить только собственные деньги на счёте, а кредитная — использовать средства банка в пределах установленного лимита. Для дебетовой карты не начисляются проценты за пользование кредитом, но важны условия обслуживания, кэшбэк и переводы.'],
    ['q' => 'На что смотреть при выборе дебетовой карты?', 'a' => 'В первую очередь сравнивайте стоимость обслуживания, кэшбэк по нужным категориям, процент на остаток, лимиты на переводы через СБП, условия снятия наличных и наличие платных уведомлений. Выгодная карта должна совпадать с вашим повседневным сценарием использования.'],
    ['q' => 'Что выгоднее: кэшбэк или процент на остаток?', 'a' => 'Если вы активно расплачиваетесь картой, чаще выгоднее кэшбэк. Если держите заметный остаток на счёте, полезнее может быть процент на остаток. Оптимальный вариант — карта, где есть и разумный кэшбэк, и понятные условия по хранению средств без скрытых ограничений.'],
    ['q' => 'Бывает ли бесплатное обслуживание у дебетовой карты?', 'a' => 'Да, многие банки предлагают бесплатное обслуживание навсегда или при выполнении условий: минимальный остаток, регулярные покупки, получение зарплаты или пенсии на карту. Перед оформлением важно уточнить, какие именно условия действуют.'],
    ['q' => 'Можно ли снимать наличные без комиссии?', 'a' => 'Да, но только в рамках тарифов банка. Обычно бесплатное снятие действует в банкоматах своего банка и иногда у партнёров. У некоторых карт есть ежемесячный лимит на бесплатное снятие или минимальная сумма операции.'],
    ['q' => 'Подходят ли дебетовые карты для переводов через СБП?', 'a' => 'Да, большинство современных дебетовых карт поддерживают переводы через Систему быстрых платежей. Но условия различаются: где-то переводы бесплатны полностью, а где-то есть лимиты без комиссии, после которых банк берёт процент.'],
];

ob_start();
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?= renderBreadcrumbs($breadcrumbs) ?>
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Дебетовые карты</h1>
    <p class="text-gray-600 mb-8">Сравните <?= count($offers) ?> дебетовых карт</p>
    <?php if ($debitTags): ?>
    <div class="flex flex-wrap gap-2 mb-6">
        <?php foreach ($debitTags as $lt): ?>
        <a href="/karty/debetovye/type/<?= e($lt['slug']) ?>" class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors"><?php if (!empty($lt['icon'])): ?><span><?= $lt['icon'] ?></span><?php endif; ?><?= e($lt['title']) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="grid gap-4">
        <?php foreach ($offers as $offer): echo renderOfferCard($offer); endforeach; ?>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 mt-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Дебетовые карты — как выбрать</h2>
        <div class="prose prose-sm text-gray-600 max-w-none">
            <p>При выборе дебетовой карты обратите внимание на процент на остаток, кэшбек-категории, стоимость обслуживания и наличие бесплатных переводов. Сравните условия на нашем сайте.</p>
        </div>
    </div>

    <div class="bg-gray-50 rounded-xl p-6 mt-8">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Карты по городам</h2>
        <div class="flex flex-wrap gap-2">
            <?php $shuffled = $cities; shuffle($shuffled); foreach (array_slice($shuffled, 0, 10) as $c): ?>
            <a href="/karty/<?= $c['slug'] ?>" class="inline-block bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors">Карты в <?= e($c['prep']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>


    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 mt-8">
        <h2 class="text-xl font-bold text-gray-900 mb-6">FAQ по дебетовым картам</h2>
        <div class="space-y-4">
            <?php foreach ($debitFaqs as $faq): ?>
            <details class="group border border-gray-200 rounded-lg">
                <summary class="flex justify-between items-center cursor-pointer p-4 font-medium text-gray-900 hover:bg-gray-50 rounded-lg">
                    <span><?= e($faq['q']) ?></span>
                    <svg class="w-5 h-5 text-gray-400 group-open:rotate-180 transition-transform flex-shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-4 pb-4 text-sm text-gray-600 leading-relaxed">
                    <?= safeAutoLink($faq['a'], 2) ?>
                </div>
            </details>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="flex flex-wrap gap-3 mt-6">
        <a href="/zajmy" class="inline-block bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors">Займы онлайн</a>
        <a href="/kredity" class="inline-block bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors">Кредиты</a>
        <a href="/karty/kreditnye" class="inline-block bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors">Кредитные карты</a>
        <a href="/compare" class="inline-block bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors">Сравнение предложений</a>
    </div>

</section>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb($breadcrumbs),
    jsonLdFAQ($debitFaqs),
];
// Schema.org ItemList
$_ilItems = [];
foreach ($offers as $_ii => $_io) {
    $_ilItems[] = [
        '@type' => 'ListItem',
        'position' => $_ii + 1,
        'name' => $_io['title'],
        'url' => SITE_URL . '/offer/' . $_io['slug'],
    ];
}
if ($_ilItems) {
    $jsonLdSchemas[] = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'Дебетовые карты — лучшие предложения',
        'numberOfItems' => count($_ilItems),
        'itemListElement' => $_ilItems,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
$canonicalUrl = SITE_URL . '/karty/debetovye';
$content = ob_get_clean();
$content .= renderStickyCta([
    'id' => 'list-sticky-cta',
    'href' => '/karty/debetovye',
    'label' => 'Смотреть дебетовые карты',
    'sub' => 'Подберите карту с кэшбэком и бонусами',
    'variant' => 'primary',
    'external' => false,
]);
require __DIR__ . '/../includes/layout.php';
