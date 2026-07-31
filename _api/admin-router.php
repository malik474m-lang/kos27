<?php
require_once __DIR__ . '/../includes/api-cache.php';
$adminUri = substr($apiUri, 6); // убираем /admin
$method = $_SERVER['REQUEST_METHOD'];
if ($adminUri === '/clear-api-cache' && $method === 'POST') { $count = apiCacheClear(); echo json_encode(['success' => true, 'cleared' => $count]); exit; }
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') { register_shutdown_function('apiCacheClear'); }

// Логин не требует авторизации
if ($adminUri === '/login' && $method === 'POST') { require __DIR__ . '/admin/login.php'; exit; }
if ($adminUri === '/logout' && $method === 'POST') { require __DIR__ . '/admin/logout.php'; exit; }
if ($adminUri === '/check') { require __DIR__ . '/admin/check.php'; exit; }

// Всё остальное — только для авторизованных
requireAdmin();

// Офферы
if ($adminUri === '/offers' && $method === 'GET') { require __DIR__ . '/admin/offers-list.php'; exit; }
if ($adminUri === '/offers' && $method === 'POST') { require __DIR__ . '/admin/offers-create.php'; exit; }
if (preg_match('#^/offers/(\d+)$#', $adminUri, $m)) {
    $itemId = (int)$m[1];
    if ($method === 'GET') { require __DIR__ . '/admin/offer-item.php'; exit; }
    if ($method === 'PUT') { require __DIR__ . '/admin/offers-update.php'; exit; }
    if ($method === 'DELETE') { require __DIR__ . '/admin/offers-delete.php'; exit; }
}

// Статьи
if ($adminUri === '/articles' && $method === 'GET') { require __DIR__ . '/admin/articles-list.php'; exit; }
if ($adminUri === '/articles' && $method === 'POST') { require __DIR__ . '/admin/articles-create.php'; exit; }
if (preg_match('#^/articles/(\d+)$#', $adminUri, $m)) {
    $itemId = (int)$m[1];
    if ($method === 'PUT') { require __DIR__ . '/admin/articles-update.php'; exit; }
    if ($method === 'DELETE') { require __DIR__ . '/admin/articles-delete.php'; exit; }
}

// Отзывы
if ($adminUri === '/reviews' && $method === 'GET') { require __DIR__ . '/admin/reviews-list.php'; exit; }
if (preg_match('#^/reviews/(\d+)$#', $adminUri, $m)) {
    $itemId = (int)$m[1];
    if ($method === 'PUT') { require __DIR__ . '/admin/reviews-update.php'; exit; }
    if ($method === 'DELETE') { require __DIR__ . '/admin/reviews-delete.php'; exit; }
}

// Гео-редиректы
if ($adminUri === '/geo-redirects' && $method === 'GET') { require __DIR__ . '/admin/geo-list.php'; exit; }
if ($adminUri === '/geo-redirects' && $method === 'POST') { require __DIR__ . '/admin/geo-create.php'; exit; }
if (preg_match('#^/geo-redirects/(\d+)$#', $adminUri, $m)) {
    $itemId = (int)$m[1];
    if ($method === 'PUT') { require __DIR__ . '/admin/geo-update.php'; exit; }
    if ($method === 'DELETE') { require __DIR__ . '/admin/geo-delete.php'; exit; }
}


// Теги (типы предложений)
if ($adminUri === '/tags' && $method === 'GET') { require __DIR__ . '/admin/tags-list.php'; exit; }
if ($adminUri === '/tags' && $method === 'POST') { require __DIR__ . '/admin/tags-create.php'; exit; }
if (preg_match('#^/tags/(\d+)$#', $adminUri, $m)) {
    $itemId = (int)$m[1];
    if ($method === 'PUT') { require __DIR__ . '/admin/tags-update.php'; exit; }
    if ($method === 'DELETE') { require __DIR__ . '/admin/tags-delete.php'; exit; }
}


// Связи офферов и тегов
if ($adminUri === '/tag-links' && $method === 'GET') { require __DIR__ . '/admin/tag-links-get.php'; exit; }
if ($adminUri === '/tag-links' && $method === 'POST') { require __DIR__ . '/admin/tag-links-save.php'; exit; }


// SEO-тексты для городов
if ($adminUri === '/city-seo' && $method === 'GET') { require __DIR__ . '/admin/city-seo-list.php'; exit; }
if ($adminUri === '/city-seo/clean' && $method === 'POST') { require __DIR__ . '/admin/city-seo-clean.php'; exit; }
    if ($adminUri === '/city-seo/generate' && $method === 'POST') { require __DIR__ . '/admin/city-seo-generate.php'; exit; }
if (preg_match('#^/city-seo/(\d+)$#', $adminUri, $m)) {
    $itemId = (int)$m[1];
    if ($method === 'PUT') { require __DIR__ . '/admin/city-seo-update.php'; exit; }
    if ($method === 'DELETE') { require __DIR__ . '/admin/city-seo-delete.php'; exit; }
}


// Сортировка (drag-and-drop)
if ($adminUri === '/reorder' && $method === 'POST') { require __DIR__ . '/admin/reorder.php'; exit; }


// Безопасность
if (str_starts_with($adminUri, '/security')) { require __DIR__ . '/admin/security.php'; exit; }


// Рассылки
if ($adminUri === '/newsletters' && $method === 'GET') { require __DIR__ . '/admin/newsletter-list.php'; exit; }
if ($adminUri === '/newsletter-generate' && $method === 'POST') { require __DIR__ . '/admin/newsletter-generate.php'; exit; }
    if ($adminUri === '/newsletters' && $method === 'POST') { require __DIR__ . '/admin/newsletter-create.php'; exit; }
if (preg_match('#^/newsletters/(\d+)$#', $adminUri, $m)) {
    $itemId = (int)$m[1];
    if ($method === 'PUT') { require __DIR__ . '/admin/newsletter-update.php'; exit; }
    if ($method === 'DELETE') { require __DIR__ . '/admin/newsletter-delete.php'; exit; }
}
if (preg_match('#^/newsletters/(\d+)/send$#', $adminUri, $m)) {
    $itemId = (int)$m[1];
    require __DIR__ . '/admin/newsletter-send.php'; exit;
}
if (preg_match('#^/newsletters/(\d+)/stats$#', $adminUri, $m)) {
    $itemId = (int)$m[1];
    require __DIR__ . '/admin/newsletter-stats.php'; exit;
}
if (preg_match('#^/newsletters/(\d+)/test$#', $adminUri, $m)) {
    $itemId = (int)$m[1];
    require __DIR__ . '/admin/newsletter-test.php'; exit;
}

// Управление подписчиками
if (preg_match('#^/subscribers/(\d+)$#', $adminUri, $m)) {
    $itemId = (int)$m[1];
    if ($method === 'PUT') { require __DIR__ . '/admin/subscriber-toggle.php'; exit; }
    if ($method === 'DELETE') { require __DIR__ . '/admin/subscriber-delete.php'; exit; }
}


// A/B тесты
if ($adminUri === '/ab-tests' && $method === 'GET') { require __DIR__ . '/admin/ab-list.php'; exit; }
if ($adminUri === '/ab-tests' && $method === 'POST') { require __DIR__ . '/admin/ab-create.php'; exit; }
if (preg_match('#^/ab-tests/(\d+)$#', $adminUri, $m)) {
    $itemId = (int)$m[1];
    if ($method === 'PUT') { require __DIR__ . '/admin/ab-toggle.php'; exit; }
    if ($method === 'DELETE') { require __DIR__ . '/admin/ab-delete.php'; exit; }
}
if (preg_match('#^/ab-tests/(\d+)/reset$#', $adminUri, $m)) {
    $itemId = (int)$m[1];
    require __DIR__ . '/admin/ab-reset.php'; exit;
}

// Категории
if ($adminUri === '/categories' && $method === 'GET') { require __DIR__ . '/admin/categories-list.php'; exit; }
if ($adminUri === '/categories' && $method === 'POST') { require __DIR__ . '/admin/categories-save.php'; exit; }
if ($adminUri === '/categories/reorder' && $method === 'POST') { require __DIR__ . '/admin/categories-reorder.php'; exit; }
if (preg_match('#^/categories/(\d+)$#', $adminUri, $m)) {
    $itemId = (int)$m[1];
    if ($method === 'PUT') { $data = json_decode(file_get_contents('php://input'), true); $data['id'] = $itemId; $_POST = $data; require __DIR__ . '/admin/categories-save.php'; exit; }
    if ($method === 'DELETE') { require __DIR__ . '/admin/categories-delete.php'; exit; }
}

// Пользователи
if ($adminUri === '/users' && $method === 'GET') { require __DIR__ . '/admin/users-list.php'; exit; }

// Postback профили
if (str_starts_with($adminUri, '/postback-profiles')) { require __DIR__ . '/admin/postback-profiles.php'; exit; }

// Postback конверсии
if ($adminUri === '/postback' && $method === 'GET') { require __DIR__ . '/admin/postback-list.php'; exit; }

// Проверка ссылок

// Здоровье сайта
if ($adminUri === '/health-check') { require __DIR__ . '/admin/health-check.php'; exit; }

// Воронка
if ($adminUri === '/funnel') { require __DIR__ . '/admin/funnel.php'; exit; }

// Проверка партнёрских ссылок
if (str_starts_with($adminUri, '/link-checks')) { require __DIR__ . '/admin/link-checks.php'; exit; }

// Умный рейтинг
if ($adminUri === '/smart-rating' && ($method === 'GET' || $method === 'POST')) { require __DIR__ . '/admin/smart-rating.php'; exit; }

// Автогенерация meta
if ($adminUri === '/meta-generate' && $method === 'POST') { require __DIR__ . '/admin/meta-generate.php'; exit; }

// Статистика
if ($adminUri === '/stats') { require __DIR__ . '/admin/stats.php'; exit; }

// Подписчики
if ($adminUri === '/subscribers') { require __DIR__ . '/admin/subscribers.php'; exit; }

// Настройки сайта
if ($adminUri === '/settings') { require __DIR__ . '/admin/settings.php'; exit; }

// Планировщик
if ($adminUri === '/scheduler') { require __DIR__ . '/admin/scheduler.php'; exit; }

// 2FA управление
if (str_starts_with($adminUri, '/two-factor')) { require __DIR__ . '/admin/two-factor.php'; exit; }
// Смена пароля
if ($adminUri === '/change-password' && $method === 'POST') { require __DIR__ . '/admin/change-password.php'; exit; }

// Генерация
if ($adminUri === '/generate-article') { require __DIR__ . '/admin/generate-article.php'; exit; }
if ($adminUri === '/generate-review' && $method === 'POST') { require __DIR__ . '/admin/generate-review.php'; exit; }
// Медиа (загрузка/просмотр картинок)
if (str_starts_with($adminUri, '/media')) {
    if ($method === 'GET' || $method === 'POST' || $method === 'DELETE') { require __DIR__ . '/admin/media.php'; exit; }
}
// Массовые действия
if ($adminUri === '/bulk-actions' && $method === 'POST') { require __DIR__ . '/admin/bulk-actions.php'; exit; }
// Пакетная генерация
if ($adminUri === '/batch-generate' && $method === 'POST') { require __DIR__ . '/admin/batch-generate.php'; exit; }
// История изменений
if ($adminUri === '/audit-log') { require __DIR__ . '/admin/audit-log.php'; exit; }
// Лог отправки рассылок
if ($adminUri === '/newsletter-send-log') { require __DIR__ . '/admin/newsletter-send-log.php'; exit; }
// Финансовая аналитика
if ($adminUri === '/analytics') { require __DIR__ . '/admin/analytics.php'; exit; }

http_response_code(404);
echo json_encode(['error' => 'Not found']);

// Лицензия
if (str_starts_with($adminUri, '/license')) { require __DIR__ . '/admin/license.php'; exit; }
