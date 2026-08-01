<?php
require_once __DIR__ . '/../includes/offer-card.php';
require_once __DIR__ . '/../includes/sticky-cta.php';
require_once __DIR__ . '/../includes/recommendation.php';
require_once __DIR__ . '/../data/cities.php';

$db = getDB();

// Теги из БД
$tagStmt = $db->query("SELECT * FROM offer_tags WHERE is_active = 1 AND category = 'microloans' ORDER BY sort_order ASC");
$loanTypes = $tagStmt->fetchAll();

// Фильтры
$fAmount = isset($_GET['amount']) ? (int)$_GET['amount'] : 0;
$fTerm = isset($_GET['term']) ? (int)$_GET['term'] : 0;
$fBorrower = $_GET['borrower'] ?? '';

$sql = "SELECT * FROM offers WHERE is_active = 1 AND category = 'microloans'";
$params = [];

if ($fAmount > 0) {
    $sql .= " AND amount_min <= ? AND amount_max >= ?";
    $params[] = $fAmount;
    $params[] = $fAmount;
}
if ($fTerm > 0) {
    $sql .= " AND term_min_days <= ? AND term_max_days >= ?";
    $params[] = $fTerm;
    $params[] = $fTerm;
}
if ($fBorrower && $fBorrower !== 'any') {
    $sql .= " AND (borrower_category = ? OR borrower_category = 'any')";
    $params[] = $fBorrower;
}

$sql .= " ORDER BY sort_order ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$offers = $stmt->fetchAll();

$pageTitle = 'Займы онлайн — Подбор микрозаймов на карту | ' . SITE_NAME;
$metaDescription = 'Подберите выгодный микрозайм онлайн. Сравните ставки, суммы и сроки от проверенных МФО.';
$metaKeywords = 'займы онлайн, микрозаймы, займ на карту, быстрый займ, МФО';

$borrowerLabels = [''=>'Все категории','employed'=>'Работающий','unemployed'=>'Безработный','pensioner'=>'Пенсионер','student'=>'Студент','self_employed'=>'Самозанятый'];
$bestOffer = getBestOfferByCategory('microloans', ['amount'=>$fAmount, 'term'=>$fTerm, 'borrower'=>$fBorrower]);

ob_start();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6"><a href="/" class="hover:text-primary">Главная</a> → Займы онлайн</nav>
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Займы онлайн</h1>
    <p class="text-gray-600 mb-6">Подберите выгодный микрозайм на карту. Сравните условия от надёжных МФО.</p>

    <?= renderBestOfferRecommendation($bestOffer, 'Самый выгодный вариант по займам') ?>

    <!-- Фильтр -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Фильтр подбора</h2>
        <form method="GET" action="/zajmy" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Сумма (₽)</label>
                <input type="number" name="amount" value="<?= $fAmount ?: '' ?>" placeholder="50000" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Срок (дней)</label>
                <input type="number" name="term" value="<?= $fTerm ?: '' ?>" placeholder="30" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Категория</label>
                <select name="borrower" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <?php foreach ($borrowerLabels as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $fBorrower === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-primary text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-primary-dark transition-colors">Подобрать</button>
            </div>
        </form>
    </div>

    <!-- Типы займов (из БД) -->
    <?php if ($loanTypes): ?>
    <div class="flex flex-wrap gap-2 mb-8">
        <?php foreach ($loanTypes as $lt): ?>
        <a href="/zajmy/type/<?= e($lt['slug']) ?>" class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors"><?php if (!empty($lt['icon'])): ?><span><?= $lt['icon'] ?></span><?php endif; ?><?= e($lt['title']) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Офферы -->
    <?php if ($offers): ?>
    <div class="grid gap-4">
        <?php foreach ($offers as $offer): echo renderOfferCard($offer); endforeach; ?>
    </div>
    <?php else: ?>
    <div class="text-center py-12 bg-white rounded-xl">
        <p class="text-gray-500 text-lg">Предложения не найдены</p>
        <p class="text-gray-400 text-sm mt-2">Попробуйте изменить параметры фильтра</p>
    </div>
    <?php endif; ?>

    <!-- SEO текст -->
    <div class="mt-12 bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Как получить займ онлайн?</h2>
        <div class="prose prose-sm text-gray-600 max-w-none">
            <p>Микрозаймы — это быстрый способ получить деньги на короткий срок. Вы можете оформить займ онлайн не выходя из дома. Деньги поступят на вашу банковскую карту в течение нескольких минут после одобрения заявки.</p>
            <p>Используйте фильтры для подбора займа по нужной сумме, сроку и категории заёмщика. Обращайте внимание на ставку и полную стоимость кредита (ПСК).</p>
        </div>
    </div>

    <!-- Перелинковка: города -->
    <div class="bg-gray-50 rounded-xl p-6 mt-8">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Займы по городам России</h2>
        <div class="flex flex-wrap gap-2">
            <?php
            $shuffledCities = getCities();
            shuffle($shuffledCities);
            foreach (array_slice($shuffledCities, 0, 15) as $c):
            ?>
            <a href="/zajmy/<?= $c['slug'] ?>" class="inline-block bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors">Займы в <?= e($c['prep']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>'Займы онлайн','url'=>'/zajmy']]),
];
$canonicalUrl = SITE_URL . '/zajmy';
$content = ob_get_clean();
$content .= renderStickyCta([
    'id' => 'list-sticky-cta',
    'href' => '/calculator',
    'label' => 'Подобрать займ',
    'sub' => 'Фильтр и калькулятор по вашим параметрам',
    'variant' => 'primary',
    'external' => false,
]);
require __DIR__ . '/../includes/layout.php';
