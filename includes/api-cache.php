<?php
// Файловый кэш API-ответов для снижения нагрузки на БД.

$API_CACHE_DIR = __DIR__ . '/../data/api_cache';
$API_CACHE_FILE = null;

function apiCacheStart(string $namespace, int $ttl = 60, string $vary = ''): bool {
    global $API_CACHE_DIR, $API_CACHE_FILE;

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return false;
    if (isset($_GET['_nocache'])) return false;

    if (!is_dir($API_CACHE_DIR)) @mkdir($API_CACHE_DIR, 0755, true);

    $key = md5($namespace . '|' . $vary . '|' . ($_SERVER['REQUEST_URI'] ?? ''));
    $API_CACHE_FILE = $API_CACHE_DIR . '/' . $namespace . '_' . $key . '.json';

    if (file_exists($API_CACHE_FILE) && (time() - filemtime($API_CACHE_FILE)) < $ttl) {
        header('X-API-Cache: HIT');
        readfile($API_CACHE_FILE);
        return true;
    }

    header('X-API-Cache: MISS');
    return false;
}

function apiCacheEnd($payload): void {
    global $API_CACHE_FILE;

    $content = is_string($payload) ? $payload : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo $content;

    if ($API_CACHE_FILE && $content !== false && strlen($content) > 2) {
        @file_put_contents($API_CACHE_FILE, $content);
    }
}

function apiCacheClear(): int {
    global $API_CACHE_DIR;
    $count = 0;
    $files = glob($API_CACHE_DIR . '/*.json') ?: [];
    foreach ($files as $f) {
        if (@unlink($f)) $count++;
    }
    return $count;
}
