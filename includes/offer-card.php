<?php
// Компонент карточки оффера
// Переменная: $offer (массив из БД)
function renderOfferCard(array $offer): string {
    $logo = normalizeMediaUrl($offer['logo_url'] ?? '');
    $rating = (float)($offer['rating'] ?? 0);
    $reviewCount = (int)($offer['review_count'] ?? 0);
    $freeTermDays = (int)($offer['free_term_days'] ?? 0);
    // A/B тест кнопки
    require_once __DIR__ . '/ab-test.php';
    $abVar = getAbVariant();
    $btnLabel = $abVar ? $abVar['label'] : 'Оформить';
    $btnColor = $abVar ? $abVar['color'] : '#059669';
    $btnHover = $abVar ? $abVar['color'] . 'dd' : '#047857';
    $abVid = $abVar ? (int)$abVar['id'] : 0;
    
    ob_start();
    ?>
    <article class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 card-hover" itemscope itemtype="https://schema.org/FinancialProduct">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex-shrink-0 w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
                <?php if ($logo): ?>
                <img src="<?= e($logo) ?>" alt="<?= e($offer['title']) ?>" class="w-full h-full object-contain p-1" loading="lazy">
                <?php else: ?>
                <span class="text-3xl">🏦</span>
                <?php endif; ?>
            </div>
            <div class="flex-1 min-w-0">
                <a href="/offer/<?= e($offer['slug']) ?>" class="hover:text-primary transition-colors">
                    <h3 class="text-lg font-bold text-gray-900 mb-1" itemprop="name"><?= e($offer['title']) ?></h3>
                </a>
                <div class="flex items-center gap-2 flex-wrap">
                    <?php if ($rating > 0): ?>
                    <span class="inline-flex items-center gap-1 bg-yellow-50 text-yellow-700 text-xs font-semibold px-2 py-0.5 rounded">
                        ★ <?= number_format($rating, 1) ?>
                        <?php if ($reviewCount > 0): ?>
                        <span class="text-yellow-500 font-normal">(<?= $reviewCount ?>)</span>
                        <?php endif; ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($freeTermDays > 0): ?>
                    <span class="inline-block bg-green-100 text-green-800 text-xs font-semibold px-2 py-0.5 rounded">
                        Без % — <?= formatDays($freeTermDays) ?>
                    </span>
                    <?php endif; ?>
                    <?php
                    // Теги оффера
                    static $offerTagsCache = [];
                    $oid = (int)$offer['id'];
                    if (!isset($offerTagsCache[$oid])) {
                        try {
                            $tagStmt = getDB()->prepare("SELECT t.title, t.slug, t.icon, t.category FROM offer_tags t JOIN offer_tag_links l ON t.id = l.tag_id WHERE l.offer_id = ? AND t.is_active = 1 ORDER BY t.sort_order ASC");
                            $tagStmt->execute([$oid]);
                            $offerTagsCache[$oid] = $tagStmt->fetchAll();
                        } catch (Exception $ex) { $offerTagsCache[$oid] = []; }
                    }
                    $catTagUrls = ['microloans'=>'/zajmy','credits'=>'/kredity','credit_cards'=>'/karty/kreditnye','debit_cards'=>'/karty/debetovye'];
                    foreach ($offerTagsCache[$oid] as $otag):
                        $tagUrl = ($catTagUrls[$otag['category']] ?? '/zajmy') . '/type/' . $otag['slug'];
                    ?>
                    <a href="<?= $tagUrl ?>" class="inline-flex items-center gap-0.5 bg-blue-50 text-blue-700 text-xs px-2 py-0.5 rounded hover:bg-blue-100 transition-colors"><?= $otag['icon'] ?? '🏷️' ?> <?= e($otag['title']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Сумма</p>
                <p class="text-sm font-semibold text-gray-900 mt-0.5"><?= formatMoney($offer['amount_min']) ?> — <?= formatMoney($offer['amount_max']) ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Срок</p>
                <p class="text-sm font-semibold text-gray-900 mt-0.5"><?= formatDays($offer['term_min_days']) ?> — <?= formatDays($offer['term_max_days']) ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Ставка</p>
                <p class="text-sm font-semibold text-gray-900 mt-0.5">от <?= e($offer['rate']) ?>%</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">ПСК</p>
                <p class="text-sm font-semibold text-gray-900 mt-0.5"><?= e($offer['psk']) ?>%</p>
            </div>
        </div>

        <?php if (!empty($offer['description'])): ?>
        <p class="text-sm text-gray-600 mt-4 line-clamp-2" itemprop="description"><?= e($offer['description']) ?></p>
        <?php endif; ?>

        <div class="mt-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-4 flex-wrap">
                <a href="/offer/<?= e($offer['slug']) ?>" class="text-primary hover:underline text-sm font-medium">Подробнее →</a>
                <button type="button"
                        class="offer-fav-btn inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 transition-colors hover:border-pink-300 hover:bg-pink-50 hover:text-pink-600"
                        data-offer-id="<?= (int)$offer['id'] ?>"
                        data-offer-title="<?= e($offer['title']) ?>"
                        aria-label="Добавить в избранное">
                    <span class="offer-fav-icon">🤍</span>
                    <span class="offer-fav-text">В избранное</span>
                </button>
            </div>
            <a href="/click/<?= (int)$offer['id'] ?>?ab=<?= $abVid ?>" target="_blank" rel="noopener noreferrer nofollow sponsored"
               style="background:<?= e($btnColor) ?>"
               class="inline-flex items-center justify-center space-x-2 text-white px-6 py-3 rounded-lg font-semibold transition-all text-sm hover:opacity-90 hover:shadow-lg">
                <span><?= e($btnLabel) ?></span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </a>
        </div>
    </article>
    <?php
    return ob_get_clean();
}
