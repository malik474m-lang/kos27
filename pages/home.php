<?php
require_once __DIR__ . '/../includes/offer-card.php';

$db = getDB();

$topOffers = $db->query("SELECT * FROM offers WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 6")->fetchAll();
$latestArticles = $db->query("SELECT * FROM articles WHERE is_published = 1 ORDER BY created_at DESC LIMIT 3")->fetchAll();

$pageTitle = pageMetaTitle('Подбор займов, кредитов и банковских карт онлайн', false) . ' | ' . SITE_NAME;
$metaDescription = 'Сравните лучшие предложения по займам, кредитам, кредитным и дебетовым картам. Калькулятор займа, удобные фильтры и актуальные условия.';
$breadcrumbs = [breadcrumbItem('Главная', '/')];

$jsonLd = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'Космозайм',
    'url' => SITE_URL,
    'description' => 'Сервис подбора займов, кредитов и банковских карт',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

ob_start();
?>

<!-- Hero -->
<section class="gradient-hero text-white py-16 sm:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-3xl sm:text-5xl font-extrabold mb-6 leading-tight">
            Подберите лучший займ, кредит<br class="hidden sm:block"> или банковскую карту
        </h1>
        <p class="text-lg sm:text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
            Сравнивайте условия от проверенных партнёров. Используйте калькулятор и фильтры для подбора идеального предложения.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="/calculator" class="bg-accent text-white px-8 py-4 rounded-lg font-semibold hover:bg-accent-dark transition-colors text-lg">🧮 Калькулятор займа</a>
            <a href="/zajmy" class="bg-white/20 backdrop-blur text-white px-8 py-4 rounded-lg font-semibold hover:bg-white/30 transition-colors text-lg">Все предложения →</a>
        </div>
    </div>
</section>

<!-- Categories -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-10 relative z-10">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php
        $cats = [
            ['href'=>'/zajmy','icon'=>'💵','title'=>'Займы','desc'=>'Быстрые микрозаймы онлайн'],
            ['href'=>'/kredity','icon'=>'🏦','title'=>'Кредиты','desc'=>'Банковские кредиты'],
            ['href'=>'/karty/kreditnye','icon'=>'💳','title'=>'Кредитные карты','desc'=>'Карты с кредитным лимитом'],
            ['href'=>'/karty/debetovye','icon'=>'🪪','title'=>'Дебетовые карты','desc'=>'Карты с кэшбеком'],
        ];
        foreach ($cats as $cat):
        ?>
        <a href="<?= $cat['href'] ?>" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 text-center card-hover block">
            <span class="text-3xl block mb-2"><?= $cat['icon'] ?></span>
            <h2 class="font-bold text-gray-900"><?= $cat['title'] ?></h2>
            <p class="text-xs text-gray-500 mt-1"><?= $cat['desc'] ?></p>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- Top Offers -->
<?php if ($topOffers): ?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-16">
    <div class="flex items-center justify-between mb-8">
        <h2 class="text-2xl font-bold text-gray-900">Лучшие предложения</h2>
        <a href="/zajmy" class="text-primary hover:underline font-medium text-sm">Все предложения →</a>
    </div>
    <div class="grid gap-4">
        <?php foreach ($topOffers as $offer): ?>
        <?= renderOfferCard($offer) ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Calculator Banner -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-16">
    <div class="bg-gradient-to-r from-primary to-purple-600 rounded-2xl p-8 sm:p-12 text-white text-center">
        <h2 class="text-2xl sm:text-3xl font-bold mb-4">Калькулятор займа</h2>
        <p class="text-blue-100 mb-6 max-w-xl mx-auto">Рассчитайте стоимость займа и подберите подходящие предложения по вашим параметрам</p>
        <a href="/calculator" class="inline-block bg-white text-primary px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">Открыть калькулятор</a>
    </div>
</section>

<!-- Articles -->
<?php if ($latestArticles): ?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-16">
    <div class="flex items-center justify-between mb-8">
        <h2 class="text-2xl font-bold text-gray-900">Полезные статьи</h2>
        <a href="/articles" class="text-primary hover:underline font-medium text-sm">Все статьи →</a>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($latestArticles as $article):
            $cover = normalizeMediaUrl($article['cover_image'] ?? '');
        ?>
        <a href="/articles/<?= e($article['slug']) ?>" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden card-hover block">
            <?php if ($cover): ?>
            <div class="h-40 bg-gray-100"><img src="<?= e($cover) ?>" alt="<?= e($article['title']) ?>" class="w-full h-full object-cover" loading="lazy"></div>
            <?php endif; ?>
            <div class="p-5">
                <h3 class="font-bold text-gray-900 mb-2 line-clamp-2"><?= e($article['title']) ?></h3>
                <?php if (!empty($article['excerpt'])): ?>
                <p class="text-sm text-gray-600 line-clamp-3"><?= e($article['excerpt']) ?></p>
                <?php endif; ?>
                <p class="text-xs text-gray-400 mt-3"><?= date('d.m.Y', strtotime($article['created_at'])) ?></p>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- SEO Text -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-16 mb-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Подбор финансовых продуктов онлайн</h2>
        <div class="prose prose-sm text-gray-600 max-w-none">
            <p><strong>Космозайм</strong> — это удобный сервис сравнения финансовых предложений от проверенных банков и микрофинансовых организаций. На нашем сайте вы можете подобрать выгодный займ, кредит, кредитную или дебетовую карту, используя удобные фильтры и калькулятор.</p>
            <p>Мы собираем актуальные условия по ставкам, суммам, срокам и полной стоимости кредита (ПСК), чтобы вы могли сделать осознанный выбор. Все предложения содержат прозрачную информацию о процентных ставках, беспроцентных периодах и категориях заёмщиков.</p>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-16 mb-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Часто задаваемые вопросы</h2>
        <div class="space-y-4">
            <?php
            $homeFaqs = [
                ['q' => 'Как быстро получить займ онлайн?', 'a' => 'Оформление займа занимает от 5 до 15 минут. Заполните заявку на сайте МФО, укажите паспортные данные и банковскую карту. При одобрении деньги поступят на карту в течение нескольких минут. Первый займ во многих МФО предоставляется под 0%.'],
                ['q' => 'Какие документы нужны для получения займа?', 'a' => 'Для оформления микрозайма в большинстве случаев достаточно паспорта гражданина РФ. Некоторые МФО могут запросить СНИЛС или ИНН. Справка о доходах обычно не требуется — решение принимается на основе скоринга.'],
                ['q' => 'Безопасно ли оформлять займ через интернет?', 'a' => 'Да, если вы обращаетесь в лицензированную МФО. Все организации, представленные на нашем сайте, имеют действующую лицензию ЦБ РФ и внесены в государственный реестр. Проверить наличие лицензии можно на сайте Банка России.'],
                ['q' => 'Что такое ПСК и почему это важно?', 'a' => 'ПСК (Полная стоимость кредита) — это все расходы заёмщика, выраженные в процентах годовых. ПСК включает проценты, комиссии и обязательные страховки. По закону ПСК не может превышать установленный ЦБ РФ предел. Сравнивайте именно ПСК, а не рекламную ставку.'],
                ['q' => 'Можно ли получить займ с плохой кредитной историей?', 'a' => 'Да, многие МФО работают с заёмщиками с любой кредитной историей. Условия могут отличаться: сумма первого займа обычно меньше, а ставка выше. При своевременном погашении кредитная история улучшается, и условия следующих займов становятся лучше.'],
                ['q' => 'Чем займ отличается от кредита?', 'a' => 'Микрозайм — это небольшая сумма (до 100 000 ₽) на короткий срок (до 1 года), выдаваемая МФО. Кредит — это более крупная сумма от банка на длительный срок. Займы оформляются быстрее и с минимумом документов, но имеют более высокую процентную ставку.'],
            ];
            foreach ($homeFaqs as $faq): ?>
            <details class="group border border-gray-200 rounded-lg">
                <summary class="flex justify-between items-center cursor-pointer p-4 font-medium text-gray-900 hover:bg-gray-50 rounded-lg">
                    <span><?= e($faq['q']) ?></span>
                    <svg class="w-5 h-5 text-gray-400 group-open:rotate-180 transition-transform flex-shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <p class="px-4 pb-4 text-sm text-gray-600 leading-relaxed"><?= e($faq['a']) ?></p>
            </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php
$canonicalUrl = pageCanonical('/');

// Schema.org: FAQPage (Google показывает в выдаче!)
$homeFaqSchema = [];
foreach ($homeFaqs as $faq) {
    $homeFaqSchema[] = ['q' => $faq['q'], 'a' => $faq['a']];
}

// Schema.org: ItemList для топ офферов (Google карусель)
$itemListItems = [];
foreach ($topOffers as $i => $offer) {
    $itemListItems[] = [
        '@type' => 'ListItem',
        'position' => $i + 1,
        'name' => $offer['title'],
        'url' => SITE_URL . '/offer/' . $offer['slug'],
    ];
}
$jsonLdSchemas = [jsonLdBreadcrumb($breadcrumbs)];
if ($homeFaqSchema) {
    $jsonLdSchemas[] = jsonLdFAQ($homeFaqSchema);
}
if ($itemListItems) {
    $jsonLdSchemas[] = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'Лучшие предложения по займам, кредитам и картам',
        'numberOfItems' => count($itemListItems),
        'itemListElement' => $itemListItems,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';
