<?php
require_once __DIR__ . '/../includes/offer-card.php';
require_once __DIR__ . '/../includes/autolinks.php';
require_once __DIR__ . '/../includes/sticky-cta.php';

$db = getDB();
$stmt = $db->prepare("SELECT * FROM offers WHERE slug = ? AND is_active = 1 LIMIT 1");
$stmt->execute([$offerSlug]);
$offer = $stmt->fetch();

if (!$offer) {
    http_response_code(404);
    $pageTitle = 'Предложение не найдено';
    ob_start();
    echo '<div class="max-w-7xl mx-auto px-4 py-24 text-center"><h1 class="text-2xl font-bold">Предложение не найдено</h1><a href="/zajmy" class="btn-primary inline-block mt-4">Все предложения</a></div>';
    $content = ob_get_clean();
    require __DIR__ . '/../includes/layout.php';
    return;
}

// Похожие предложения — по категории, сумме и близости ставки
$similar = $db->prepare("
    SELECT *,
        (CASE
            WHEN amount_min <= ? AND amount_max >= ? THEN 100
            WHEN amount_min <= ? AND amount_max >= ? THEN 70
            WHEN amount_min <= ? AND amount_max >= ? THEN 40
            ELSE 0
        END) AS amount_match_score,
        ABS(CAST(rate AS DECIMAL(10,2)) - CAST(? AS DECIMAL(10,2))) AS rate_diff
    FROM offers
    WHERE is_active = 1 AND category = ? AND id != ?
    ORDER BY amount_match_score DESC, rate_diff ASC, review_count DESC, rating DESC, sort_order ASC
    LIMIT 4
");
$similar->execute([
    $offer['amount_min'], $offer['amount_min'],
    $offer['amount_max'], $offer['amount_max'],
    (int)round(((int)$offer['amount_min'] + (int)$offer['amount_max']) / 2),
    (int)round(((int)$offer['amount_min'] + (int)$offer['amount_max']) / 2),
    $offer['rate'],
    $offer['category'],
    $offer['id']
]);
$similarOffers = $similar->fetchAll();

// Отзывы
$reviewsStmt = $db->prepare("SELECT * FROM reviews WHERE offer_id = ? AND is_approved = 1 ORDER BY created_at DESC LIMIT 10");
$reviewsStmt->execute([$offer['id']]);
$offerReviews = $reviewsStmt->fetchAll();

$catLabels = ['microloans'=>'Займы','credits'=>'Кредиты','credit_cards'=>'Кредитные карты','debit_cards'=>'Дебетовые карты'];
$catLabel = $catLabels[$offer['category']] ?? 'Предложения';
$catUrl = match($offer['category']) {
    'microloans' => '/zajmy',
    'credits' => '/kredity',
    'credit_cards' => '/karty/kreditnye',
    'debit_cards' => '/karty/debetovye',
    default => '/zajmy',
};

$pageTitle = $offer['title'] . ' — ' . SITE_NAME;
$metaDescription = $offer['description'] ?: "Оформите {$offer['title']} онлайн. Сумма от " . formatMoney($offer['amount_min']) . " до " . formatMoney($offer['amount_max']);
$rating = (float)$offer['rating'];
$logo = normalizeMediaUrl($offer['logo_url'] ?? '');
require_once __DIR__ . '/../includes/ab-test.php';
$offerCtaLabel = getDefaultCtaLabelByCategory($offer['category'] ?? '');
$offerCtaSecondary = getDefaultCtaSecondaryLabelByCategory($offer['category'] ?? '');

$displayDefaults = [
    'microloans' => ['amount'=>true,'term'=>true,'rate'=>true,'psk'=>true,'free_term'=>((int)$offer['free_term_days']>0),'borrower'=>true],
    'credits' => ['amount'=>true,'term'=>true,'rate'=>true,'psk'=>true,'free_term'=>false,'borrower'=>true],
    'credit_cards' => ['amount'=>true,'term'=>false,'rate'=>true,'psk'=>true,'free_term'=>((int)$offer['free_term_days']>0),'borrower'=>false],
    'debit_cards' => ['amount'=>false,'term'=>false,'rate'=>false,'psk'=>false,'free_term'=>false,'borrower'=>false],
];
$displayFields = $displayDefaults[$offer['category']] ?? ['amount'=>true,'term'=>true,'rate'=>true,'psk'=>true,'free_term'=>((int)$offer['free_term_days']>0),'borrower'=>false];
if (!empty($offer['display_fields'])) { $tmp = json_decode($offer['display_fields'], true); if (is_array($tmp)) $displayFields = array_merge($displayFields, $tmp); }
$borrowerMap = ['employed'=>'Работающий','unemployed'=>'Безработный','pensioner'=>'Пенсионер','student'=>'Студент','self_employed'=>'Самозанятый'];

$pageHeadHtml = <<<'HTML'
<style>
.offer-page-wrap{max-width:80rem;margin:0 auto;padding:2rem 1rem}
.offer-main-card,.offer-calc-card,.offer-review-card,.offer-form-card,.offer-related-card{background:#fff;border:1px solid #f1f5f9;border-radius:1.5rem;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.offer-main-card{padding:2rem}.offer-calc-card{padding:1.5rem 2rem}.offer-form-card{padding:1.5rem}.offer-related-card{padding:1.5rem}
.offer-top{display:flex;align-items:center;gap:1.5rem;margin-bottom:1.5rem}.offer-logo{width:5rem;height:5rem;background:#f3f4f6;border-radius:.75rem;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0}
.offer-main-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem;margin-bottom:1.5rem}.offer-main-grid-4{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem;margin-bottom:1.5rem}
.offer-metric{background:#f9fafb;border-radius:.75rem;padding:1rem}.offer-metric-label{font-size:.75rem;text-transform:uppercase;color:#6b7280}.offer-metric-value{font-size:1.125rem;font-weight:700;color:#111827;margin-top:.25rem}
.offer-cta{display:inline-flex;align-items:center;gap:.5rem;background:#059669;color:#fff;padding:1rem 2rem;border-radius:.75rem;font-weight:700;text-decoration:none}
.offer-cta:hover{opacity:.92}.offer-section{margin-top:3rem}.offer-title-2{font-size:1.5rem;font-weight:700;color:#111827;margin-bottom:1.5rem}
.offer-calc-grid{display:grid;grid-template-columns:1.2fr .8fr;gap:2rem;align-items:start}.offer-calc-side{background:#f9fafb;border:1px solid #f1f5f9;border-radius:1rem;padding:1.5rem}
@media (min-width:768px){.offer-main-grid-4{grid-template-columns:repeat(4,minmax(0,1fr))}}
@media (max-width:1023px){.offer-calc-grid{grid-template-columns:1fr}}
@media (max-width:639px){.offer-page-wrap{padding:1.5rem 1rem}.offer-main-card{padding:1.25rem}.offer-calc-card{padding:1.25rem}.offer-top{gap:1rem;align-items:flex-start}.offer-logo{width:4.25rem;height:4.25rem}.offer-main-grid,.offer-main-grid-4{grid-template-columns:1fr 1fr;gap:.75rem}.offer-metric-value{font-size:1rem}.offer-cta{width:100%;justify-content:center}}
</style>
HTML;

ob_start();
?>
<section class="offer-page-wrap max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6">
        <a href="/" class="hover:text-primary">Главная</a> → 
        <a href="<?= $catUrl ?>" class="hover:text-primary"><?= $catLabel ?></a> → 
        <?= e($offer['title']) ?>
    </nav>

    <div class="offer-main-card bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
        <div class="offer-top flex items-center gap-6 mb-6">
            <div class="offer-logo w-20 h-20 bg-gray-100 rounded-xl flex items-center justify-center overflow-hidden flex-shrink-0">
                <?php if ($logo): ?>
                <img src="<?= e($logo) ?>" alt="<?= e($offer['title']) ?>" class="w-full h-full object-contain p-2" decoding="async" fetchpriority="high">
                <?php else: ?>
                <span class="text-4xl">🏦</span>
                <?php endif; ?>
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900"><?= e($offer['title']) ?></h1>
                <?php if ($rating > 0): ?>
                <div class="flex items-center gap-2 mt-2">
                    <div class="flex text-yellow-400"><?php for($i=1;$i<=5;$i++): ?><span class="<?= $i <= round($rating) ? '' : 'text-gray-300' ?>">★</span><?php endfor; ?></div>
                    <span class="text-sm text-gray-500"><?= number_format($rating, 1) ?> (<?= $offer['review_count'] ?> отзывов)</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php
        $mainCards = [];
        if (!empty($displayFields['amount'])) {
            $mainCards[] = ['label' => in_array($offer['category'], ['credit_cards']) ? 'Лимит' : 'Сумма', 'value' => formatMoney($offer['amount_min']) . ' — ' . formatMoney($offer['amount_max'])];
        }
        if (!empty($displayFields['term'])) {
            $mainCards[] = ['label' => 'Срок', 'value' => formatDays($offer['term_min_days']) . ' — ' . formatDays($offer['term_max_days'])];
        }
        if (!empty($displayFields['rate'])) {
            $mainCards[] = ['label' => $offer['category'] === 'credits' ? 'Ставка годовая' : 'Ставка', 'value' => formatRateDisplay($offer)];
        }
        if (!empty($displayFields['psk'])) {
            $mainCards[] = ['label' => 'ПСК', 'value' => $offer['psk'] . '%'];
        }
        if (!empty($displayFields['borrower']) && !empty($offer['borrower_category']) && $offer['borrower_category'] !== 'any') {
            $mainCards[] = ['label' => 'Заёмщик', 'value' => $borrowerMap[$offer['borrower_category']] ?? $offer['borrower_category']];
        }
        if ($mainCards): ?>
        <div class="offer-main-grid-4 grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <?php foreach ($mainCards as $card): ?>
            <div class="offer-metric bg-gray-50 rounded-lg p-4">
                <p class="offer-metric-label text-xs text-gray-500 uppercase"><?= e($card['label']) ?></p>
                <p class="offer-metric-value text-lg font-bold text-gray-900"><?= e($card['value']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php
        // Дополнительные поля
        $extraFields = !empty($offer['extra_fields']) ? (json_decode($offer['extra_fields'], true) ?: []) : [];
        $visibleExtra = array_filter($extraFields, fn($f) => !empty($f['visible']) && trim($f['value'] ?? '') !== '');
        if ($visibleExtra):
        ?>
        <div class="offer-main-grid-4 grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <?php foreach ($visibleExtra as $ef): ?>
            <div class="offer-metric bg-gray-50 rounded-lg p-4">
                <p class="offer-metric-label text-xs text-gray-500 uppercase"><?= e($ef['label']) ?></p>
                <p class="offer-metric-value text-lg font-bold text-gray-900"><?= e($ef['value']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($offer['free_term_days'] > 0 && !empty($displayFields['free_term'])): ?>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
            <p class="text-green-800 font-semibold">🎉 Без процентов — <?= formatDays($offer['free_term_days']) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($offer['description']): ?>
        <div class="prose max-w-none text-gray-700 mb-6"><?= safeAutoLink($offer['description'], 5) ?></div>
        <?php endif; ?>


        <a href="/click/<?= $offer['id'] ?>" target="_blank" rel="noopener noreferrer nofollow sponsored"
           style="background:#059669" class="offer-cta inline-flex items-center space-x-2 text-white px-8 py-4 rounded-lg font-semibold transition-colors text-lg hover:opacity-90">
            <span><?= e($offerCtaLabel) ?></span>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
        </a>
    </div>

    <?php if (in_array($offer['category'], ['microloans','credits','credit_cards'])):
        $calcAmount = max((int)$offer['amount_min'], min((int)$offer['amount_max'], (int)round(((int)$offer['amount_min'] + (int)$offer['amount_max']) / 2)));
        $calcTerm = max((int)$offer['term_min_days'], min((int)$offer['term_max_days'], (int)round(((int)$offer['term_min_days'] + (int)$offer['term_max_days']) / 2)));
        $calcRate = (float)$offer['rate'];
        $calcFree = (int)$offer['free_term_days'];
        $amountLabel = match($offer['category']) {
            'credits' => 'Сумма кредита',
            'credit_cards' => 'Кредитный лимит',
            default => 'Сумма займа',
        };
        $termLabel = match($offer['category']) {
            'credits' => 'Срок (дней)',
            'credit_cards' => 'Срок использования (дней)',
            default => 'Срок (дней)',
        };
        $calcTitle = match($offer['category']) {
            'credits' => 'Предварительный расчёт по условиям кредита',
            'credit_cards' => 'Предварительный расчёт по условиям лимита',
            default => 'Предварительный расчёт по условиям займа',
        };
    ?>
    <div class="offer-calc-card mt-8 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
        <div class="flex items-center justify-between gap-4 flex-wrap mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900"><?= $calcTitle ?></h2>
                <p class="text-sm text-gray-500 mt-1">Калькулятор считает сумму по параметрам именно этого предложения.</p>
            </div>
            <?php if ($calcFree > 0): ?>
            <span class="inline-flex items-center rounded-full bg-green-50 px-3 py-1 text-sm font-semibold text-green-700">0% до <?= formatDays($calcFree) ?></span>
            <?php endif; ?>
        </div>

        <div class="offer-calc-grid grid lg:grid-cols-2 gap-8 items-start">
            <div class="space-y-6">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-medium text-gray-700"><?= $amountLabel ?></label>
                        <span id="offer-calc-amount-val" class="text-lg font-bold text-primary"><?= formatMoney($calcAmount) ?></span>
                    </div>
                    <input type="range" id="offer-calc-amount" min="<?= (int)$offer['amount_min'] ?>" max="<?= (int)$offer['amount_max'] ?>" step="1000" value="<?= $calcAmount ?>" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600" oninput="offerCalcUpdate()">
                    <div class="flex justify-between text-xs text-gray-400 mt-1"><span><?= formatMoney((int)$offer['amount_min']) ?></span><span><?= formatMoney((int)$offer['amount_max']) ?></span></div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-medium text-gray-700"><?= $termLabel ?></label>
                        <span id="offer-calc-term-val" class="text-lg font-bold text-primary"><?= formatDays($calcTerm) ?></span>
                    </div>
                    <input type="range" id="offer-calc-term" min="<?= (int)$offer['term_min_days'] ?>" max="<?= (int)$offer['term_max_days'] ?>" step="1" value="<?= $calcTerm ?>" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600" oninput="offerCalcUpdate()">
                    <div class="flex justify-between text-xs text-gray-400 mt-1"><span><?= formatDays((int)$offer['term_min_days']) ?></span><span><?= formatDays((int)$offer['term_max_days']) ?></span></div>
                </div>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div class="rounded-xl bg-gray-50 p-4 border border-gray-100">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Ставка</p>
                        <p class="mt-1 font-semibold text-gray-900"><?= e(formatRateDisplay($offer)) ?></p>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-4 border border-gray-100">
                        <p class="text-xs uppercase tracking-wide text-gray-500">ПСК</p>
                        <p class="mt-1 font-semibold text-gray-900"><?= e($offer['psk']) ?>%</p>
                    </div>
                </div>
            </div>

            <div class="offer-calc-side rounded-2xl bg-gray-50 border border-gray-100 p-6">
                <h3 class="font-bold text-gray-900 mb-4">Результат расчёта</h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Сумма к возврату</p>
                        <p id="offer-calc-total" class="text-3xl font-bold text-gray-900"><?= formatMoney($calcAmount) ?></p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Переплата</p>
                        <p id="offer-calc-overpay" class="text-xl font-bold text-red-600">0 ₽</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Эффективная ставка</p>
                        <p id="offer-calc-effective-rate" class="text-lg font-semibold text-gray-900"><?= e(formatRateDisplay($offer, false)) ?></p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Комментарий</p>
                        <p id="offer-calc-note" class="text-sm text-gray-600">Предварительный расчёт по открытым параметрам оффера.</p>
                    </div>
                </div>
                <a href="/click/<?= $offer['id'] ?>" target="_blank" rel="noopener noreferrer nofollow sponsored" style="background:#059669" class="mt-6 inline-flex w-full items-center justify-center rounded-xl px-5 py-3 font-semibold text-white transition-colors hover:opacity-90"><?= e($offerCtaSecondary) ?></a>
            </div>
        </div>
    </div>

    <script>
    function offerCalcMoney(n){return Math.round(n).toLocaleString('ru-RU') + ' ₽';}
    function offerCalcDays(d){
        d = Number(d);
        if(d <= 0) return '0 дней';
        if(d === 1) return '1 день';
        if(d < 5) return d + ' дня';
        return d + ' дней';
    }
    function offerCalcUpdate(){
        var amount = parseInt(document.getElementById('offer-calc-amount').value || '0', 10);
        var term = parseInt(document.getElementById('offer-calc-term').value || '0', 10);
        var rate = <?= json_encode($calcRate) ?>;
        var rateUnit = <?= json_encode(getRateUnit($offer)) ?>;
        var freeDays = <?= json_encode($calcFree) ?>;
        var category = <?= json_encode($offer['category']) ?>;

        var effectiveRate = rate;
        var interest = 0;
        var note = 'Предварительный расчёт по открытым параметрам оффера.';

        if (freeDays > 0 && term <= freeDays) {
            effectiveRate = 0;
            note = 'В пределах льготного периода проценты не начисляются.';
        }

        if (rateUnit === 'year') {
            interest = amount * (effectiveRate / 100) * (term / 365);
            note = effectiveRate === 0 ? note : 'Расчёт ориентировочный: ставка указана в год, используется упрощённая годовая модель без графика платежей.';
        } else {
            interest = amount * (effectiveRate / 100) * term;
            if (category === 'credit_cards' && effectiveRate !== 0) {
                note = 'Расчёт ориентировочный: для лимита карты использована упрощённая модель начисления процентов.';
            }
        }

        var total = amount + interest;

        document.getElementById('offer-calc-amount-val').textContent = offerCalcMoney(amount);
        document.getElementById('offer-calc-term-val').textContent = offerCalcDays(term);
        document.getElementById('offer-calc-total').textContent = offerCalcMoney(total);
        document.getElementById('offer-calc-overpay').textContent = offerCalcMoney(interest);
        document.getElementById('offer-calc-effective-rate').textContent = effectiveRate.toLocaleString('ru-RU') + '% ' + (rateUnit === 'year' ? 'в год' : 'в день');
        document.getElementById('offer-calc-note').textContent = note;
    }
    offerCalcUpdate();
    </script>
    <?php endif; ?>


    <!-- Отзывы -->
    <?php if ($offerReviews): ?>
    <div class="mt-12">
        <h2 class="offer-title-2 text-2xl font-bold text-gray-900 mb-6">Отзывы о <?= e($offer['title']) ?></h2>
        <div class="space-y-4">
            <?php foreach ($offerReviews as $rev): ?>
            <div class="offer-review-card bg-white rounded-xl border border-gray-100 p-5" itemprop="review" itemscope itemtype="https://schema.org/Review">
                <div class="flex items-center gap-3 mb-2">
                    <span class="font-semibold text-gray-900" itemprop="author" itemscope itemtype="https://schema.org/Person"><span itemprop="name"><?= e($rev['author_name']) ?></span></span>
                    <div class="flex text-yellow-400" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">
                        <meta itemprop="bestRating" content="5">
                        <meta itemprop="worstRating" content="1">
                        <meta itemprop="ratingValue" content="<?= (int)$rev['rating'] ?>">
                        <?php for($i=1;$i<=5;$i++): ?><span class="<?= $i <= $rev['rating'] ? '' : 'text-gray-300' ?>">★</span><?php endfor; ?>
                    </div>
                    <time class="text-xs text-gray-400" itemprop="datePublished" datetime="<?= date('c', strtotime($rev['created_at'])) ?>"><?= date('d.m.Y', strtotime($rev['created_at'])) ?></time>
                </div>
                <p class="text-gray-700" itemprop="reviewBody"><?= e($rev['comment']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>


    <!-- Форма отзыва -->
    <div class="mt-12">
        <h2 class="offer-title-2 text-2xl font-bold text-gray-900 mb-6">Оставить отзыв о <?= e($offer['title']) ?></h2>
        <div class="offer-form-card bg-white rounded-xl border border-gray-100 p-6">
            <form id="review-form" onsubmit="return submitReview(event)">
                <input type="hidden" id="rv-offer-id" value="<?= (int)$offer['id'] ?>">
                <div class="grid sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ваше имя *</label>
                        <input type="text" id="rv-name" required placeholder="Иван Иванов"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Оценка *</label>
                        <div class="flex items-center gap-1 mt-1" id="rv-stars">
                            <span onclick="setRating(1)" class="text-3xl cursor-pointer text-yellow-400">★</span>
                            <span onclick="setRating(2)" class="text-3xl cursor-pointer text-yellow-400">★</span>
                            <span onclick="setRating(3)" class="text-3xl cursor-pointer text-yellow-400">★</span>
                            <span onclick="setRating(4)" class="text-3xl cursor-pointer text-yellow-400">★</span>
                            <span onclick="setRating(5)" class="text-3xl cursor-pointer text-yellow-400">★</span>
                        </div>
                        <input type="hidden" id="rv-rating" value="5">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ваш отзыв *</label>
                    <textarea id="rv-comment" required rows="4" placeholder="Расскажите о вашем опыте..."
                              class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div id="rv-msg" class="hidden mb-4 p-3 rounded-lg text-sm"></div>
                <button type="submit" id="rv-btn"
                        class="bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary-dark transition-colors">
                    Отправить отзыв
                </button>
            </form>
        </div>
    </div>

    <script>
    var currentRating = 5;
    function setRating(n) {
        currentRating = n;
        document.getElementById('rv-rating').value = n;
        var stars = document.getElementById('rv-stars').children;
        for (var i = 0; i < 5; i++) {
            stars[i].className = 'text-3xl cursor-pointer ' + (i < n ? 'text-yellow-400' : 'text-gray-300');
        }
    }
    function submitReview(e) {
        e.preventDefault();
        var btn = document.getElementById('rv-btn');
        var msg = document.getElementById('rv-msg');
        btn.disabled = true;
        btn.textContent = 'Отправка...';
        msg.className = 'hidden mb-4 p-3 rounded-lg text-sm';
        fetch('/api/reviews', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                offerId: parseInt(document.getElementById('rv-offer-id').value),
                authorName: document.getElementById('rv-name').value.trim(),
                rating: currentRating,
                comment: document.getElementById('rv-comment').value.trim()
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                msg.className = 'mb-4 p-3 rounded-lg text-sm bg-green-50 text-green-700 border border-green-200';
                msg.textContent = '✓ ' + (d.message || 'Отзыв отправлен на модерацию. Спасибо!');
                document.getElementById('rv-name').value = '';
                document.getElementById('rv-comment').value = '';
                setRating(5);
            } else {
                msg.className = 'mb-4 p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200';
                msg.textContent = d.error || 'Ошибка отправки';
            }
            btn.disabled = false;
            btn.textContent = 'Отправить отзыв';
        })
        .catch(function() {
            msg.className = 'mb-4 p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200';
            msg.textContent = 'Ошибка соединения';
            btn.disabled = false;
            btn.textContent = 'Отправить отзыв';
        });
        return false;
    }
    </script>

    <!-- Похожие -->
    <?php if ($similarOffers): ?>
    <div class="mt-12">
        <h2 class="offer-title-2 text-2xl font-bold text-gray-900 mb-6">Похожие предложения по сумме и ставке</h2>
        <div class="grid gap-4">
            <?php foreach ($similarOffers as $sim): echo renderOfferCard($sim); endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Полезные статьи -->
    <?php
    $relatedArticles = $db->query("SELECT title, slug FROM articles WHERE is_published = 1 ORDER BY created_at DESC LIMIT 3")->fetchAll();
    if ($relatedArticles):
    ?>
    <div class="mt-12">
        <h2 class="offer-title-2 text-2xl font-bold text-gray-900 mb-6">Полезные статьи</h2>
        <div class="grid sm:grid-cols-3 gap-4">
            <?php foreach ($relatedArticles as $ra): ?>
            <a href="/articles/<?= e($ra['slug']) ?>" class="bg-white rounded-xl border border-gray-100 p-5 card-hover block">
                <h3 class="font-semibold text-gray-900 line-clamp-2"><?= e($ra['title']) ?></h3>
                <span class="text-primary text-sm mt-2 inline-block">Читать →</span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Перелинковка: города -->
    <?php
    require_once __DIR__ . '/../data/cities.php';
    $shuffledCities = getCities(); shuffle($shuffledCities);
    ?>
    <div class="offer-related-card bg-gray-50 rounded-xl p-6 mt-8">
        <h2 class="text-lg font-bold text-gray-900 mb-4"><?= $catLabel ?> по городам</h2>
        <div class="flex flex-wrap gap-2">
            <?php foreach (array_slice($shuffledCities, 0, 12) as $c): ?>
            <a href="<?= $catUrl ?>/<?= $c['slug'] ?>" class="inline-block bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors"><?= $catLabel ?> в <?= e($c['prep']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>

</section>
<?php
$jsonLdSchemas = [
    jsonLdOffer($offer, $offerReviews),
    jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>$catLabel,'url'=>$catUrl],['name'=>$offer['title'],'url'=>'/offer/'.$offer['slug']]]),
];
$canonicalUrl = SITE_URL . '/offer/' . $offer['slug'];
$ogImage = normalizeMediaUrl($offer['logo_url'] ?? '');
$content = ob_get_clean();
$content .= renderStickyCta([
    'id' => 'offer-sticky-cta',
    'href' => '/click/' . $offer['id'],
    'label' => 'Оформить заявку',
    'sub' => $offer['title'],
    'variant' => 'accent',
    'external' => true,
]);
require __DIR__ . '/../includes/layout.php';
