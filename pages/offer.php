<?php
require_once __DIR__ . '/../includes/offer-card.php';
require_once __DIR__ . '/../includes/autolinks.php';

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

// Похожие предложения
$similar = $db->prepare("SELECT * FROM offers WHERE is_active = 1 AND category = ? AND id != ? ORDER BY sort_order ASC LIMIT 4");
$similar->execute([$offer['category'], $offer['id']]);
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

ob_start();
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6">
        <a href="/" class="hover:text-primary">Главная</a> → 
        <a href="<?= $catUrl ?>" class="hover:text-primary"><?= $catLabel ?></a> → 
        <?= e($offer['title']) ?>
    </nav>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <div class="flex items-center gap-6 mb-6">
            <div class="w-20 h-20 bg-gray-100 rounded-xl flex items-center justify-center overflow-hidden flex-shrink-0">
                <?php if ($logo): ?>
                <img src="<?= e($logo) ?>" alt="<?= e($offer['title']) ?>" class="w-full h-full object-contain p-2">
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

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-xs text-gray-500 uppercase">Сумма</p>
                <p class="text-lg font-bold text-gray-900"><?= formatMoney($offer['amount_min']) ?> — <?= formatMoney($offer['amount_max']) ?></p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-xs text-gray-500 uppercase">Срок</p>
                <p class="text-lg font-bold text-gray-900"><?= formatDays($offer['term_min_days']) ?> — <?= formatDays($offer['term_max_days']) ?></p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-xs text-gray-500 uppercase">Ставка</p>
                <p class="text-lg font-bold text-gray-900">от <?= e($offer['rate']) ?>%</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-xs text-gray-500 uppercase">ПСК</p>
                <p class="text-lg font-bold text-gray-900"><?= e($offer['psk']) ?>%</p>
            </div>
        </div>

        <?php if ($offer['free_term_days'] > 0): ?>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
            <p class="text-green-800 font-semibold">🎉 Без процентов — <?= formatDays($offer['free_term_days']) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($offer['description']): ?>
        <div class="prose max-w-none text-gray-700 mb-6"><?= safeAutoLink($offer['description'], 5) ?></div>
        <?php endif; ?>

        <a href="/click/<?= $offer['id'] ?>" target="_blank" rel="noopener noreferrer nofollow sponsored"
           class="inline-flex items-center space-x-2 bg-accent text-white px-8 py-4 rounded-lg font-semibold hover:bg-accent-dark transition-colors text-lg">
            <span>Оформить заявку</span>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
        </a>
    </div>

    <!-- Отзывы -->
    <?php if ($offerReviews): ?>
    <div class="mt-12">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Отзывы о <?= e($offer['title']) ?></h2>
        <div class="space-y-4">
            <?php foreach ($offerReviews as $rev): ?>
            <div class="bg-white rounded-xl border border-gray-100 p-5">
                <div class="flex items-center gap-3 mb-2">
                    <span class="font-semibold text-gray-900"><?= e($rev['author_name']) ?></span>
                    <div class="flex text-yellow-400"><?php for($i=1;$i<=5;$i++): ?><span class="<?= $i <= $rev['rating'] ? '' : 'text-gray-300' ?>">★</span><?php endfor; ?></div>
                    <span class="text-xs text-gray-400"><?= date('d.m.Y', strtotime($rev['created_at'])) ?></span>
                </div>
                <p class="text-gray-700"><?= e($rev['comment']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>


    <!-- Форма отзыва -->
    <div class="mt-12">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Оставить отзыв о <?= e($offer['title']) ?></h2>
        <div class="bg-white rounded-xl border border-gray-100 p-6">
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
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Похожие предложения</h2>
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
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Полезные статьи</h2>
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
    $shuffledCities = $cities; shuffle($shuffledCities);
    ?>
    <div class="bg-gray-50 rounded-xl p-6 mt-8">
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
    jsonLdOffer($offer),
    jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>$catLabel,'url'=>$catUrl],['name'=>$offer['title'],'url'=>'/offer/'.$offer['slug']]]),
];
$canonicalUrl = SITE_URL . '/offer/' . $offer['slug'];
$ogImage = normalizeMediaUrl($offer['logo_url'] ?? '');
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';
