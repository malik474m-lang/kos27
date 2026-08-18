<?php
require_once __DIR__ . '/ab-test.php';
/**
 * Рекомендательный блок “Самый выгодный вариант”
 * Использует упрощённый smart score на основе кликов, approval, EPC, отзывов.
 */

function getBestOfferByCategory(string $category, array $filters = []): ?array {
    $db = getDB();
    $clickDateColumn = dbDateColumn('click_stats', ['clicked_at', 'created_at']);
    $pageViewDateColumn = dbDateColumn('page_views', ['viewed_at', 'created_at']);

    $sql = "SELECT * FROM offers WHERE is_active = 1 AND category = ?";
    $params = [$category];

    if (!empty($filters['amount'])) {
        $sql .= " AND amount_min <= ? AND amount_max >= ?";
        $params[] = (int)$filters['amount'];
        $params[] = (int)$filters['amount'];
    }
    if (!empty($filters['term'])) {
        $sql .= " AND term_min_days <= ? AND term_max_days >= ?";
        $params[] = (int)$filters['term'];
        $params[] = (int)$filters['term'];
    }
    if (!empty($filters['borrower']) && $filters['borrower'] !== 'any') {
        $sql .= " AND (borrower_category = ? OR borrower_category = 'any')";
        $params[] = $filters['borrower'];
    }

    $sql .= " ORDER BY sort_order ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $offers = $stmt->fetchAll();
    if (!$offers) return null;

    $period = 30;
    $best = null;
    $bestScore = -1;

    foreach ($offers as $o) {
        $oid = (int)$o['id'];
        $slug = $o['slug'];

        $views = 0;
        try {
            $vstmt = $db->prepare("SELECT COUNT(*) as cnt FROM page_views WHERE page = ? AND {$pageViewDateColumn} >= DATE_SUB(NOW(), INTERVAL $period DAY)");
            $vstmt->execute(['/offer/' . $slug]);
            $views = (int)$vstmt->fetch()['cnt'];
        } catch (Exception $e) {
            try {
                $vstmt = $db->prepare("SELECT COUNT(*) as cnt FROM page_views WHERE page = ? AND {$pageViewDateColumn} >= DATE_SUB(NOW(), INTERVAL $period DAY)");
                $vstmt->execute(['/offer/' . $slug]);
                $views = (int)$vstmt->fetch()['cnt'];
            } catch (Exception $e) {}
        }

        $clicks = 0;
        try {
            $cstmt = $db->prepare("SELECT COUNT(*) as cnt FROM click_stats WHERE offer_id = ? AND {$clickDateColumn} >= DATE_SUB(NOW(), INTERVAL $period DAY)");
            $cstmt->execute([$oid]);
            $clicks = (int)$cstmt->fetch()['cnt'];
        } catch (Exception $e) {
            try {
                $cstmt = $db->prepare("SELECT COUNT(*) as cnt FROM click_stats WHERE offer_id = ? AND {$clickDateColumn} >= DATE_SUB(NOW(), INTERVAL $period DAY)");
                $cstmt->execute([$oid]);
                $clicks = (int)$cstmt->fetch()['cnt'];
            } catch (Exception $e) {}
        }

        $approved = 0; $rejected = 0; $payout = 0.0;
        try {
            $pstmt = $db->prepare("SELECT status, COUNT(*) as cnt, SUM(payout) as total FROM postback_conversions WHERE offer_id = ? AND {$clickDateColumn} >= DATE_SUB(NOW(), INTERVAL $period DAY) GROUP BY status");
            $pstmt->execute([$oid]);
            foreach ($pstmt->fetchAll() as $row) {
                if ($row['status'] === 'approved') { $approved = (int)$row['cnt']; $payout = (float)$row['total']; }
                elseif ($row['status'] === 'rejected') { $rejected = (int)$row['cnt']; }
            }
        } catch (Exception $e) {}

        $ctr = $views > 0 ? ($clicks / $views) * 100 : 0;
        $approvalRate = ($approved + $rejected) > 0 ? ($approved / ($approved + $rejected)) * 100 : 0;
        $epc = $clicks > 0 ? $payout / $clicks : 0;
        $reviewStrength = ((float)$o['rating']) * log(((int)$o['review_count']) + 1, 2);

        $score = ($clicks * 0.25) + ($ctr * 0.2) + ($approvalRate * 0.2) + ($epc * 8) + ($reviewStrength * 1.5);

        if ($score > $bestScore) {
            $bestScore = $score;
            $o['_smart_score'] = round($score, 1);
            $o['_ctr'] = round($ctr, 1);
            $o['_approval_rate'] = round($approvalRate, 1);
            $o['_epc'] = round($epc, 2);
            $best = $o;
        }
    }

    return $best;
}

function renderBestOfferRecommendation(?array $offer, string $title = 'Самый выгодный вариант'): string {
    if (!$offer) return '';
    $logo = normalizeMediaUrl($offer['logo_url'] ?? '');
    ob_start();
    ?>
    <section class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-6 sm:p-8 text-white mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center gap-6">
            <div class="flex-1 min-w-0">
                <p class="text-sm uppercase tracking-[0.12em] text-blue-100">Рекомендация</p>
                <h2 class="text-2xl sm:text-3xl font-bold mt-2"><?= e($title) ?></h2>
                <p class="text-blue-100 mt-3 max-w-2xl">Мы автоматически выбрали оффер с лучшим сочетанием кликабельности, одобрения, доходности и отзывов за последние 30 дней.</p>

                <div class="mt-5 flex items-start gap-4">
                    <div class="w-16 h-16 rounded-xl bg-white/15 border border-white/20 flex items-center justify-center overflow-hidden flex-shrink-0">
                        <?php if ($logo): ?>
                        <img src="<?= e($logo) ?>" alt="<?= e($offer['title']) ?>" class="w-full h-full object-contain p-2" loading="lazy" decoding="async">
                        <?php else: ?>
                        <span class="text-3xl">🏦</span>
                        <?php endif; ?>
                    </div>
                    <div class="min-w-0">
                        <a href="/offer/<?= e($offer['slug']) ?>" class="text-xl font-bold hover:underline"><?= e($offer['title']) ?></a>
                        <div class="mt-2 flex flex-wrap gap-2 text-sm">
                            <span class="rounded-full bg-white/15 px-3 py-1">Ставка: <?= e(formatRateDisplay($offer)) ?></span>
                            <span class="rounded-full bg-white/15 px-3 py-1">До <?= formatMoney((int)$offer['amount_max']) ?></span>
                            <?php if ((int)$offer['free_term_days'] > 0): ?>
                            <span class="rounded-full bg-green-500/20 px-3 py-1">Льготный период: <?= formatDays((int)$offer['free_term_days']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 w-full lg:w-auto lg:min-w-[240px]">
                <div class="rounded-xl bg-white/10 p-4 text-center">
                    <p class="text-xs uppercase tracking-wide text-blue-100">Smart score</p>
                    <p class="mt-1 text-2xl font-bold"><?= e((string)($offer['_smart_score'] ?? '0')) ?></p>
                </div>
                <div class="rounded-xl bg-white/10 p-4 text-center">
                    <p class="text-xs uppercase tracking-wide text-blue-100">CTR</p>
                    <p class="mt-1 text-2xl font-bold"><?= e((string)($offer['_ctr'] ?? '0')) ?>%</p>
                </div>
                <div class="rounded-xl bg-white/10 p-4 text-center">
                    <p class="text-xs uppercase tracking-wide text-blue-100">Approval</p>
                    <p class="mt-1 text-2xl font-bold"><?= e((string)($offer['_approval_rate'] ?? '0')) ?>%</p>
                </div>
                <div class="rounded-xl bg-white/10 p-4 text-center">
                    <p class="text-xs uppercase tracking-wide text-blue-100">EPC</p>
                    <p class="mt-1 text-2xl font-bold"><?= e(number_format((float)($offer['_epc'] ?? 0), 2, ',', ' ')) ?> ₽</p>
                </div>
            </div>
        </div>

        <div class="mt-6 flex flex-col sm:flex-row sm:flex-wrap gap-3">
            <a href="/offer/<?= e($offer['slug']) ?>" class="inline-flex w-full sm:w-auto items-center justify-center rounded-xl bg-white px-5 py-3 font-semibold text-blue-700 hover:bg-blue-50 transition-colors">Подробнее</a>
            <?php $recCta = getCtaVariantData((string)($offer['category'] ?? '')); ?>
            <a href="/click/<?= (int)$offer['id'] ?>?src=best-choice&ab=<?= (int)$recCta['id'] ?>" target="_blank" rel="noopener noreferrer nofollow sponsored" class="inline-flex w-full sm:w-auto items-center justify-center rounded-xl px-5 py-3 font-semibold text-white transition-colors" style="background:<?= e($recCta['color']) ?>"><?= e($recCta['label']) ?></a>
        </div>
    </section>
    <?php
    return ob_get_clean();
}
