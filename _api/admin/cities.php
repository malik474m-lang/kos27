<?php
/**
 * API управления городами — CRUD через cities.json
 */
requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];
$citiesFile = __DIR__ . '/../../data/cities.json';

function loadCities(): array {
    global $citiesFile;
    if (!file_exists($citiesFile)) return [];
    $data = json_decode(file_get_contents($citiesFile), true);
    return is_array($data) ? $data : [];
}

function saveCities(array $cities): bool {
    global $citiesFile;
    return file_put_contents($citiesFile, json_encode($cities, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

$action = $_GET['action'] ?? 'list';

switch ($action) {

case 'list':
    echo json_encode(loadCities());
    break;

case 'add':
    if ($method !== 'POST') { http_response_code(405); echo json_encode(['error' => 'POST required']); exit; }
    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim($data['name'] ?? '');
    $slug = trim($data['slug'] ?? '');
    $region = trim($data['region'] ?? '');
    $prep = trim($data['prep'] ?? '');

    if (!$name || !$slug || !$prep) {
        echo json_encode(['error' => 'Заполните название, slug и предложный падеж']);
        exit;
    }

    // Генерируем slug если не передан
    if (!$slug) {
        $slug = slugify($name);
    }

    $cities = loadCities();

    // Проверка дубликата
    foreach ($cities as $c) {
        if ($c['slug'] === $slug) {
            echo json_encode(['error' => "Город с slug '{$slug}' уже существует"]);
            exit;
        }
    }

    $cities[] = [
        'name' => $name,
        'slug' => $slug,
        'region' => $region,
        'prep' => $prep
    ];

    saveCities($cities);
    echo json_encode(['success' => true, 'total' => count($cities)]);
    break;

case 'update':
    if ($method !== 'PUT' && $method !== 'POST') { http_response_code(405); echo json_encode(['error' => 'PUT/POST required']); exit; }
    $data = json_decode(file_get_contents('php://input'), true);
    $oldSlug = $data['old_slug'] ?? '';
    $name = trim($data['name'] ?? '');
    $slug = trim($data['slug'] ?? '');
    $region = trim($data['region'] ?? '');
    $prep = trim($data['prep'] ?? '');

    if (!$oldSlug || !$name || !$slug || !$prep) {
        echo json_encode(['error' => 'Заполните все поля']);
        exit;
    }

    $cities = loadCities();
    $found = false;
    foreach ($cities as &$c) {
        if ($c['slug'] === $oldSlug) {
            $c['name'] = $name;
            $c['slug'] = $slug;
            $c['region'] = $region;
            $c['prep'] = $prep;
            $found = true;
            break;
        }
    }
    unset($c);

    if (!$found) {
        echo json_encode(['error' => 'Город не найден']);
        exit;
    }

    saveCities($cities);
    echo json_encode(['success' => true]);
    break;

case 'delete':
    if ($method !== 'DELETE' && $method !== 'POST') { http_response_code(405); echo json_encode(['error' => 'DELETE/POST required']); exit; }
    $data = json_decode(file_get_contents('php://input'), true);
    $slug = $data['slug'] ?? '';

    if (!$slug) {
        echo json_encode(['error' => 'slug required']);
        exit;
    }

    $cities = loadCities();
    $before = count($cities);
    $cities = array_values(array_filter($cities, fn($c) => $c['slug'] !== $slug));

    if (count($cities) === $before) {
        echo json_encode(['error' => 'Город не найден']);
        exit;
    }

    saveCities($cities);
    echo json_encode(['success' => true, 'total' => count($cities)]);
    break;

case 'reorder':
    if ($method !== 'POST') { http_response_code(405); echo json_encode(['error' => 'POST required']); exit; }
    $data = json_decode(file_get_contents('php://input'), true);
    $slugs = $data['slugs'] ?? [];
    if (empty($slugs)) { echo json_encode(['error' => 'slugs required']); exit; }

    $cities = loadCities();
    $bySlug = [];
    foreach ($cities as $c) { $bySlug[$c['slug']] = $c; }

    $reordered = [];
    foreach ($slugs as $s) {
        if (isset($bySlug[$s])) {
            $reordered[] = $bySlug[$s];
            unset($bySlug[$s]);
        }
    }
    // Добавить оставшиеся
    foreach ($bySlug as $c) { $reordered[] = $c; }

    saveCities($reordered);
    echo json_encode(['success' => true]);
    break;

default:
    echo json_encode(['error' => 'Unknown action']);
}
