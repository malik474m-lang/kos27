<?php
/**
 * Страница «Спасибо» после клика на оффер
 * Показывает: подтверждение, похожие офферы, форму подписки
 */
require_once __DIR__ . '/../includes/offer-card.php';

$db = getDB();
$offerId = (int)($_GET['offer'] ?? 0);

// Получаем оффер по которому кликнули
$clickedOffer = null;
if ($offerId) {
    $stmt = $db->prepare("SELECT * FROM offers WHERE id = ? AND is_active = 1");
    $stmt->execute([$offerId]);
    $clickedOffer = $stmt->fetch();
}

// Похожие офферы (другие активные, исключая текущий)
$similarOffers = [];
if ($clickedOffer) {
    $simStmt = $db->prepare("
        SELECT * FROM offers 
        WHERE is_active = 1 AND id != ? AND category = ?
        ORDER BY rating DESC, sort_order ASC 
        LIMIT 3
    ");
    $simStmt->execute([$offerId, $clickedOffer['category']]);
    $similarOffers = $simStmt->fetchAll();
}

// Если нет похожих по категории — берём любые
if (count($similarOffers) < 3) {
    $excludeIds = array_merge([$offerId], array_column($similarOffers, 'id'));
    $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
    $moreStmt = $db->prepare("
        SELECT * FROM offers 
        WHERE is_active = 1 AND id NOT IN ({$placeholders})
        ORDER BY rating DESC, sort_order ASC 
        LIMIT " . (3 - count($similarOffers))
    );
    $moreStmt->execute($excludeIds);
    $similarOffers = array_merge($similarOffers, $moreStmt->fetchAll());
}

$offerTitle = $clickedOffer ? $clickedOffer['title'] : 'предложение';
$thankyouCatMeta = ['label' => 'Предложения', 'url' => '/zajmy'];
if ($clickedOffer) {
    $thankyouCatMeta = match ($clickedOffer['category']) {
        'credits' => ['label' => 'Кредиты', 'url' => '/kredity'],
        'credit_cards' => ['label' => 'Кредитные карты', 'url' => '/karty/kreditnye'],
        'debit_cards' => ['label' => 'Дебетовые карты', 'url' => '/karty/debetovye'],
        default => ['label' => 'Займы', 'url' => '/zajmy'],
    };
}
$pageTitle = pageMetaTitle('Заявка отправлена');
$metaDescription = 'Ваша заявка отправлена. Пока ждёте ответ, посмотрите другие выгодные предложения.';
$breadcrumbs = [breadcrumbItem('Главная', '/')];
if ($clickedOffer) {
    $breadcrumbs[] = breadcrumbItem($thankyouCatMeta['label'], $thankyouCatMeta['url']);
    $breadcrumbs[] = breadcrumbItem($clickedOffer['title'], '/offer/' . $clickedOffer['slug']);
}
$breadcrumbs[] = breadcrumbItem('Заявка отправлена', '/thankyou');

ob_start();
?>
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <?= renderBreadcrumbs($breadcrumbs) ?>

    <!-- Блок подтверждения -->
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-2xl p-8 sm:p-12 text-center mb-12 border border-green-100">
        <div class="w-20 h-20 mx-auto mb-6 bg-green-100 rounded-full flex items-center justify-center">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3">Вы перешли к оформлению!</h1>
        <p class="text-gray-600 text-lg mb-2">
            <?php if ($clickedOffer): ?>
                Заявка в <strong><?= e($offerTitle) ?></strong> открыта в новой вкладке.
            <?php else: ?>
                Заявка открыта в новой вкладке.
            <?php endif; ?>
        </p>
        <p class="text-gray-500 text-sm">Заполните анкету на сайте партнёра и дождитесь решения.</p>
        
        <div class="flex flex-wrap justify-center gap-4 mt-8">
            <div class="bg-white rounded-lg px-4 py-3 border border-green-200 text-center">
                <p class="text-2xl font-bold text-green-600">5 мин</p>
                <p class="text-xs text-gray-500">Среднее время</p>
            </div>
            <div class="bg-white rounded-lg px-4 py-3 border border-green-200 text-center">
                <p class="text-2xl font-bold text-green-600">97%</p>
                <p class="text-xs text-gray-500">Одобрение</p>
            </div>
            <div class="bg-white rounded-lg px-4 py-3 border border-green-200 text-center">
                <p class="text-2xl font-bold text-green-600">24/7</p>
                <p class="text-xs text-gray-500">Круглосуточно</p>
            </div>
        </div>
    </div>

    <!-- Советы пока ждёте -->
    <div class="bg-blue-50 rounded-xl p-6 mb-12 border border-blue-100">
        <h2 class="font-bold text-gray-900 mb-3 flex items-center gap-2">
            <span>💡</span> Пока ждёте ответ
        </h2>
        <ul class="text-sm text-gray-700 space-y-2">
            <li class="flex items-start gap-2">
                <span class="text-blue-500 mt-0.5">✓</span>
                <span><strong>Отправьте 2-3 заявки</strong> — это увеличит шансы на одобрение и позволит выбрать лучшие условия.</span>
            </li>
            <li class="flex items-start gap-2">
                <span class="text-blue-500 mt-0.5">✓</span>
                <span><strong>Проверьте телефон</strong> — некоторые компании звонят для подтверждения.</span>
            </li>
            <li class="flex items-start gap-2">
                <span class="text-blue-500 mt-0.5">✓</span>
                <span><strong>Подготовьте паспорт</strong> — данные могут понадобиться для анкеты.</span>
            </li>
        </ul>
    </div>

    <!-- Похожие предложения -->
    <?php if ($similarOffers): ?>
    <div class="mb-12">
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">Подайте ещё заявку — увеличьте шансы!</h2>
        <p class="text-gray-500 text-sm mb-6">Опытные заёмщики подают 2-3 заявки и выбирают лучшее предложение</p>
        <div class="grid gap-4">
            <?php foreach ($similarOffers as $sim): ?>
                <?= renderOfferCard($sim) ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Подписка -->
    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-2xl p-8 text-center border border-purple-100">
        <h2 class="text-xl font-bold text-gray-900 mb-2">🔔 Получайте лучшие предложения первым</h2>
        <p class="text-gray-500 text-sm mb-6">Новые акции, 0% займы и выгодные условия — раз в неделю на email</p>
        <form id="ty-subscribe-form" class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
            <input type="email" id="ty-email" placeholder="Ваш email" required
                   class="flex-1 px-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold text-sm transition-colors whitespace-nowrap">
                Подписаться
            </button>
        </form>
        <p id="ty-subscribe-msg" class="text-sm mt-3 hidden"></p>
    </div>

    <!-- Ссылки -->
    <div class="mt-8 text-center">
        <a href="/zajmy" class="text-primary hover:underline font-medium text-sm">← Все предложения</a>
        <span class="text-gray-300 mx-3">|</span>
        <a href="/calculator" class="text-primary hover:underline font-medium text-sm">🧮 Калькулятор</a>
        <span class="text-gray-300 mx-3">|</span>
        <a href="/" class="text-primary hover:underline font-medium text-sm">🏠 Главная</a>
    </div>

</section>

<script>
document.getElementById('ty-subscribe-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var email = document.getElementById('ty-email').value;
    var msg = document.getElementById('ty-subscribe-msg');
    var btn = this.querySelector('button');
    btn.disabled = true;
    btn.textContent = '...';
    
    fetch('/api/subscribe', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({email: email, source: 'thankyou'})
    }).then(function(r) { return r.json(); }).then(function(d) {
        msg.classList.remove('hidden');
        if (d.success || d.ok) {
            msg.className = 'text-sm mt-3 text-green-600';
            msg.textContent = '✅ Вы подписаны! Проверьте почту.';
            document.getElementById('ty-email').value = '';
        } else {
            msg.className = 'text-sm mt-3 text-gray-600';
            msg.textContent = d.error || 'Вы уже подписаны';
        }
    }).catch(function() {
        msg.classList.remove('hidden');
        msg.className = 'text-sm mt-3 text-red-600';
        msg.textContent = 'Ошибка. Попробуйте позже.';
    }).finally(function() {
        btn.disabled = false;
        btn.textContent = 'Подписаться';
    });
});
</script>
<?php
$canonicalUrl = pageCanonical('/thankyou');
$jsonLdSchemas = [jsonLdBreadcrumb($breadcrumbs)];
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';
