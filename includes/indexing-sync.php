<?php
/** Общая синхронизация url_index_tracker (админ API + cron). */
/**
 * Синхронизирует URL из БД в url_index_tracker.
 * НЕ обновляет last_modified если контент не менялся (по content_hash).
 */
function syncUrlsFromDb(): array {
    global $db;
    require_once __DIR__ . '/../../data/cities.php';
    $stats = ['added' => 0, 'updated' => 0, 'unchanged' => 0];
    $catUrls = ['microloans'=>'/zajmy','credits'=>'/kredity','credit_cards'=>'/karty/kreditnye','debit_cards'=>'/karty/debetovye'];

    // Последнее обновление офферов — для городских страниц
    $lastOfferUpdate = $db->query("SELECT MAX(updated_at) as dt FROM offers WHERE is_active = 1")->fetch()['dt'] ?: date('Y-m-d');

    $offers = $db->query("SELECT slug, updated_at FROM offers WHERE is_active = 1")->fetchAll();
    foreach ($offers as $o) {
        smartUpsertUrl("/offer/{$o['slug']}", 'offer', $o['updated_at'], 0.8, $o['slug'], $stats);
    }

    $articles = $db->query("SELECT slug, updated_at FROM articles WHERE is_published = 1")->fetchAll();
    foreach ($articles as $a) {
        smartUpsertUrl("/articles/{$a['slug']}", 'article', $a['updated_at'], 0.6, $a['slug'], $stats);
    }

    $tags = $db->query("SELECT slug, category, created_at FROM offer_tags WHERE is_active = 1")->fetchAll();
    foreach ($tags as $t) {
        smartUpsertUrl(($catUrls[$t['category']] ?? '/zajmy')."/type/{$t['slug']}", 'category', $t['created_at'], 0.7, $t['slug'], $stats);
    }

    // Допзапросы (подкатегории) /q/{slug} и /{city}/q/{slug}
    $subcats = [];
    try { $subcats = $db->query("SELECT slug, category, updated_at FROM subcategories WHERE is_active = 1")->fetchAll(); } catch (Exception $e) {}
    foreach ($subcats as $sc) {
        $base = $catUrls[$sc['category']] ?? '/zajmy';
        smartUpsertUrl("{$base}/q/{$sc['slug']}", 'category', $sc['updated_at'], 0.7, 'subcat_' . $sc['category'] . '_' . $sc['slug'], $stats);
    }

    $cities = getCities();
    foreach ($cities as $c) {
        // Для городских страниц используем дату последнего обновления офферов, а не NOW()
        $cityHash = $c['slug'] . '_' . substr($lastOfferUpdate, 0, 10);
        
        // Проверяем SEO-текст города для каждой категории
        foreach (['microloans' => '/zajmy', 'credits' => '/kredity', 'credit_cards' => '/karty/kreditnye', 'debit_cards' => '/karty/debetovye'] as $cat => $prefix) {
            $citySeoDate = $lastOfferUpdate;
            try {
                $stmt = $db->prepare("SELECT updated_at FROM city_seo_texts WHERE city_slug = ? AND category = ? LIMIT 1");
                $stmt->execute([$c['slug'], $cat]);
                $seo = $stmt->fetch();
                if ($seo) $citySeoDate = $seo['updated_at'];
            } catch (Exception $e) {}
            $prio = ['microloans'=>0.6,'credits'=>0.5,'credit_cards'=>0.5,'debit_cards'=>0.5][$cat] ?? 0.5;
            smartUpsertUrl("{$prefix}/{$c['slug']}", 'city', $citySeoDate, $prio, $c['slug'] . '_' . $cat, $stats);
        }
        smartUpsertUrl("/karty/{$c['slug']}", 'city', $lastOfferUpdate, 0.5, $c['slug'] . '_cards', $stats);

        // Допзапросы по городам
        foreach ($subcats as $sc) {
            $base = $catUrls[$sc['category']] ?? '/zajmy';
            smartUpsertUrl("{$base}/{$c['slug']}/q/{$sc['slug']}", 'city_tag', $sc['updated_at'], 0.6, 'subcat_' . $sc['category'] . '_' . $c['slug'] . '_' . $sc['slug'], $stats);
        }

        foreach ($tags as $t) {
            $catUrl = $catUrls[$t['category']] ?? '/zajmy';
            $lastmod = $t['created_at'];
            try {
                $stmt = $db->prepare("SELECT updated_at FROM city_tag_seo_texts WHERE city_slug=? AND category=? AND tag_slug=?");
                $stmt->execute([$c['slug'], $t['category'], $t['slug']]);
                $seo = $stmt->fetch();
                if ($seo) $lastmod = $seo['updated_at'];
            } catch (Exception $e) {}
            smartUpsertUrl("{$catUrl}/{$c['slug']}/type/{$t['slug']}", 'city_tag', $lastmod, 0.5, $c['slug'] . '_' . $t['slug'], $stats);
        }
    }

    foreach (['/' => ['static',1.0], '/zajmy' => ['category',0.9], '/kredity' => ['category',0.9], '/karty/kreditnye' => ['category',0.8], '/karty/debetovye' => ['category',0.8], '/novye-mfo' => ['category',0.8], '/calculator' => ['static',0.7], '/compare' => ['static',0.7], '/articles' => ['static',0.7]] as $url => $p) {
        smartUpsertUrl($url, $p[0], $lastOfferUpdate, $p[1], 'static_' . md5($url), $stats);
    }
    return $stats;
}

/**
 * Умная вставка/обновление URL.
 * Обновляет last_modified ТОЛЬКО если контент реально изменился.
 */
function smartUpsertUrl(string $url, string $type, string $lastmod, float $priority, string $contentKey, array &$stats): void {
    global $db;
    $hash = md5($contentKey . '|' . substr($lastmod, 0, 10));
    try {
        // Проверяем существует ли URL и изменился ли hash
        $existing = $db->prepare("SELECT id, content_hash FROM url_index_tracker WHERE url = ?");
        $existing->execute([$url]);
        $row = $existing->fetch();
        
        if (!$row) {
            // Новый URL — вставляем
            $db->prepare("INSERT INTO url_index_tracker (url, url_type, last_modified, priority, content_hash) VALUES (?,?,?,?,?)")
               ->execute([$url, $type, $lastmod, $priority, $hash]);
            $stats['added']++;
        } elseif ($row['content_hash'] !== $hash) {
            // Контент изменился — обновляем last_modified
            $db->prepare("UPDATE url_index_tracker SET last_modified = ?, priority = ?, content_hash = ? WHERE id = ?")
               ->execute([$lastmod, $priority, $hash, $row['id']]);
            $stats['updated']++;
        } else {
            // Ничего не изменилось — только priority
            $db->prepare("UPDATE url_index_tracker SET priority = ? WHERE id = ?")
               ->execute([$priority, $row['id']]);
            $stats['unchanged']++;
        }
    } catch (Exception $e) {}
}
