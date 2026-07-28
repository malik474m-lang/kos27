<?php
require_once __DIR__ . '/minify.php';

// Кэширование готовых HTML-страниц
// Ускоряет повторные запросы в 10-50 раз — не ходим в MySQL

$PAGE_CACHE_DIR = __DIR__ . '/../data/page_cache';
$PAGE_CACHE_TTL = 300; // 5 минут
$PAGE_CACHE_KEY = null;
$PAGE_CACHE_ENABLED = true;

// Не кэшируем: POST, API, админку, поиск
function pageCacheCanServe(): bool {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') return false;
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    if (str_contains($uri, '/api/') || str_contains($uri, '/admin') || str_contains($uri, '/search') || str_contains($uri, '/click/')) return false;
    return true;
}

function pageCacheStart(): bool {
    global $PAGE_CACHE_DIR, $PAGE_CACHE_TTL, $PAGE_CACHE_KEY, $PAGE_CACHE_ENABLED;
    if (!$PAGE_CACHE_ENABLED || !pageCacheCanServe()) return false;

    if (!is_dir($PAGE_CACHE_DIR)) @mkdir($PAGE_CACHE_DIR, 0755, true);

    $PAGE_CACHE_KEY = md5($_SERVER['REQUEST_URI'] ?? '/');
    $cacheFile = "$PAGE_CACHE_DIR/$PAGE_CACHE_KEY.html";

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $PAGE_CACHE_TTL) {
        // Отдаём из кэша
        header('X-Page-Cache: HIT');
        readfile($cacheFile);
        return true;
    }

    // Начинаем буферизацию для записи в кэш
    header('X-Page-Cache: MISS');
    ob_start();
    return false;
}

function pageCacheEnd(): void {
    global $PAGE_CACHE_DIR, $PAGE_CACHE_KEY;
    if (!$PAGE_CACHE_KEY) return;

    $html = ob_get_clean();
    if ($html) {
        $html = minifyHtmlOutput($html);
        echo $html;
    }
    if ($html && strlen($html) > 100) {
        $cacheFile = "$PAGE_CACHE_DIR/$PAGE_CACHE_KEY.html";
        @file_put_contents($cacheFile, $html);
    }
}

function pageCacheClear(): int {
    global $PAGE_CACHE_DIR;
    $count = 0;
    $files = glob("$PAGE_CACHE_DIR/*.html");
    if ($files) {
        foreach ($files as $f) {
            @unlink($f);
            $count++;
        }
    }
    return $count;
}
