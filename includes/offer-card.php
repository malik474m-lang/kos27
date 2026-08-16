<?php
// Компонент карточки оффера
function renderOfferCard(array $offer): string {
    $logo = normalizeMediaUrl($offer['logo_url'] ?? '');
    $rating = (float)($offer['rating'] ?? 0);
    $reviewCount = (int)($offer['review_count'] ?? 0);
    $freeTermDays = (int)($offer['free_term_days'] ?? 0);

    require_once __DIR__ . '/ab-test.php';
    $abVar = getAbVariant($offer['category'] ?? '');
    $btnLabel = $abVar ? $abVar['label'] : getDefaultCtaLabelByCategory($offer['category'] ?? '');
    $btnColor = $abVar ? $abVar['color'] : '#059669';
    $abVid = $abVar ? (int)$abVar['id'] : 0;

    // Видимость стандартных полей
    $defaultsByCategory = [
        'microloans' => ['amount'=>true,'term'=>true,'rate'=>true,'psk'=>true,'free_term'=>($freeTermDays>0),'borrower'=>true],
        'credits' => ['amount'=>true,'term'=>true,'rate'=>true,'psk'=>true,'free_term'=>false,'borrower'=>true],
        'credit_cards' => ['amount'=>true,'term'=>false,'rate'=>true,'psk'=>true,'free_term'=>($freeTermDays>0),'borrower'=>false],
        'debit_cards' => ['amount'=>false,'term'=>false,'rate'=>false,'psk'=>false,'free_term'=>false,'borrower'=>false],
    ];
    $displayFields = $defaultsByCategory[$offer['category']] ?? ['amount'=>true,'term'=>true,'rate'=>true,'psk'=>true,'free_term'=>($freeTermDays>0),'borrower'=>false];
    if (!empty($offer['display_fields'])) {
        $decoded = json_decode($offer['display_fields'], true);
        if (is_array($decoded)) $displayFields = array_merge($displayFields, $decoded);
    }

    $fieldCards = [];
    if (!empty($displayFields['amount'])) {
        $amountLabel = in_array($offer['category'], ['credit_cards']) ? 'Лимит' : 'Сумма';
        $fieldCards[] = ['label' => $amountLabel, 'value' => formatMoney($offer['amount_min']) . ' — ' . formatMoney($offer['amount_max'])];
    }
    if (!empty($displayFields['term'])) {
        $fieldCards[] = ['label' => 'Срок', 'value' => formatDays($offer['term_min_days']) . ' — ' . formatDays($offer['term_max_days'])];
    }
    if (!empty($displayFields['rate'])) {
        $rateLabel = $offer['category'] === 'credits' ? 'Ставка годовая' : 'Ставка';
        $fieldCards[] = ['label' => $rateLabel, 'value' => formatRateDisplay($offer)];
    }
    if (!empty($displayFields['psk'])) {
        $fieldCards[] = ['label' => 'ПСК', 'value' => $offer['psk'] . '%'];
    }
    if (!empty($displayFields['borrower']) && !empty($offer['borrower_category']) && $offer['borrower_category'] !== 'any') {
        $borrowerMap = ['employed'=>'Работающий','unemployed'=>'Безработный','pensioner'=>'Пенсионер','student'=>'Студент','self_employed'=>'Самозанятый'];
        $fieldCards[] = ['label' => 'Заёмщик', 'value' => $borrowerMap[$offer['borrower_category']] ?? $offer['borrower_category']];
    }

    ob_start();
    ?>
    <article class="offer-card-box bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6 card-hover">
        <div class="offer-card-head flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
            <div class="offer-card-logo flex-shrink-0 w-14 h-14 sm:w-16 sm:h-16 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
                <?php if ($logo): ?>
                <img src="<?= e($logo) ?>" alt="<?= e($offer['title']) ?>" class="w-full h-full object-contain p-1" loading="lazy">
                <?php else: ?>
                <span class="text-3xl">🏦</span>
                <?php endif; ?>
            </div>
            <div class="flex-1 min-w-0">
                <a href="/offer/<?= e($offer['slug']) ?>" class="hover:text-primary transition-colors">
                    <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-1 leading-tight"><?= e($offer['title']) ?></h3>
                </a>
                <div class="flex items-center gap-2 flex-wrap">
                    <?php if ($rating > 0): ?>
                    <span class="inline-flex items-center gap-1 bg-yellow-50 text-yellow-700 text-xs font-semibold px-2 py-0.5 rounded">
                        ★ <?= number_format($rating, 1) ?>
                        <?php if ($reviewCount > 0): ?><span class="text-yellow-500 font-normal">(<?= $reviewCount ?>)</span><?php endif; ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($freeTermDays > 0 && !empty($displayFields['free_term'])): ?>
                    <span class="inline-block bg-green-100 text-green-800 text-xs font-semibold px-2 py-0.5 rounded">
                        Льготный период — <?= formatDays($freeTermDays) ?>
                    </span>
                    <?php endif; ?>
                    <?php
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
                    $cityContext = $GLOBALS['current_city_context'] ?? null;
                    $cityContextType = $GLOBALS['current_city_context_type'] ?? '';
                    foreach ($offerTagsCache[$oid] as $otag):
                        $baseTagUrl = ($catTagUrls[$otag['category']] ?? '/zajmy');
                        $tagUrl = $baseTagUrl . '/type/' . $otag['slug'];
                        $tagLabel = $otag['title'];
                        if ($cityContextType === 'city' && is_array($cityContext) && !empty($cityContext['prep']) && !empty($cityContext['slug'])) {
                            $tagLabel .= ' в ' . $cityContext['prep'];
                            $tagUrl = $baseTagUrl . '/' . $cityContext['slug'] . '/type/' . $otag['slug'];
                        }
                    ?>
                    <a href="<?= $tagUrl ?>" class="inline-flex items-center gap-0.5 bg-blue-50 text-blue-700 text-xs px-2 py-0.5 rounded hover:bg-blue-100 transition-colors"><?= $otag['icon'] ?? '🏷️' ?> <?= e($tagLabel) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php if ($fieldCards): ?>
        <div class="offer-card-grid grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 mt-4 sm:mt-5">
            <?php foreach ($fieldCards as $fc): ?>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide"><?= e($fc['label']) ?></p>
                <p class="text-sm font-semibold text-gray-900 mt-0.5"><?= e($fc['value']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php
        $extraFields = !empty($offer['extra_fields']) ? (json_decode($offer['extra_fields'], true) ?: []) : [];
        $visibleFields = array_filter($extraFields, fn($f) => !empty($f['visible']) && trim($f['value'] ?? '') !== '');
        if ($visibleFields):
        ?>
        <div class="flex flex-wrap gap-x-6 gap-y-2 mt-4 <?= $fieldCards ? 'pt-4 border-t border-gray-100' : '' ?>">
            <?php foreach ($visibleFields as $ef): ?>
            <div>
                <p class="text-xs text-gray-400"><?= e($ef['label']) ?></p>
                <p class="text-sm font-semibold text-gray-800"><?= e($ef['value']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($offer['description'])): ?>
        <p class="offer-card-desc text-sm text-gray-600 mt-3 sm:mt-4 line-clamp-2"><?= e($offer['description']) ?></p>
        <?php endif; ?>

        <div class="offer-card-actions mt-4 sm:mt-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3 sm:gap-4 flex-wrap">
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
               onclick="setTimeout(function(){window.location='/thankyou?offer=<?= (int)$offer['id'] ?>';},300)"
               style="background:<?= e($btnColor) ?>"
               class="inline-flex w-full sm:w-auto items-center justify-center space-x-2 text-white px-5 py-3 rounded-lg font-semibold transition-all text-sm hover:opacity-90 hover:shadow-lg">
                <span><?= e($btnLabel) ?></span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </a>
        </div>
    </article>
    <?php if (!isset($GLOBALS['favorites_script_rendered']) || !$GLOBALS['favorites_script_rendered']): $GLOBALS['favorites_script_rendered'] = true; ?>
    <script>
    function getFavoriteOfferIds() {
        try { return JSON.parse(localStorage.getItem('kosmozaim_favorites') || '[]'); }
        catch (e) { return []; }
    }
    function setFavoriteOfferIds(ids) {
        localStorage.setItem('kosmozaim_favorites', JSON.stringify(ids));
        syncOfferFavoriteButtons();
        window.dispatchEvent(new CustomEvent('favorites:changed', { detail: { ids: ids } }));
    }
    function toggleOfferFavorite(id) {
        id = Number(id);
        var ids = getFavoriteOfferIds();
        if (ids.includes(id)) ids = ids.filter(function(x){ return x !== id; });
        else ids.push(id);
        setFavoriteOfferIds(ids);
    }
    function syncOfferFavoriteButtons() {
        var ids = getFavoriteOfferIds();
        document.querySelectorAll('.offer-fav-btn').forEach(function(btn) {
            var id = Number(btn.dataset.offerId || 0);
            var active = ids.includes(id);
            btn.classList.toggle('border-pink-300', active);
            btn.classList.toggle('bg-pink-50', active);
            btn.classList.toggle('text-pink-600', active);
            var icon = btn.querySelector('.offer-fav-icon');
            var text = btn.querySelector('.offer-fav-text');
            if (icon) icon.textContent = active ? '❤️' : '🤍';
            if (text) text.textContent = active ? 'В избранном' : 'В избранное';
            btn.setAttribute('aria-label', active ? 'Убрать из избранного' : 'Добавить в избранное');
        });
    }
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.offer-fav-btn');
        if (!btn) return;
        e.preventDefault();
        toggleOfferFavorite(btn.dataset.offerId);
    });
    document.addEventListener('DOMContentLoaded', syncOfferFavoriteButtons);
    window.addEventListener('storage', syncOfferFavoriteButtons);
    window.addEventListener('favorites:changed', syncOfferFavoriteButtons);
    </script>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}
