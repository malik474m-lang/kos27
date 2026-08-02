<?php
/**
 * Города — загрузка из JSON-файла (редактируется через админку)
 */
$cities = [];
$citiesJsonFile = __DIR__ . '/cities.json';
if (file_exists($citiesJsonFile)) {
    $citiesJson = json_decode(file_get_contents($citiesJsonFile), true);
    if (is_array($citiesJson)) {
        $cities = $citiesJson;
    }
}

function normalizeCityName(string $name): string {
    return mb_strtolower(trim($name));
}

function findCityBySlug(string $slug): ?array {
    global $cities;
    foreach ($cities as $c) {
        if ($c['slug'] === $slug) return $c;
    }
    return null;
}

function findCityByName(string $name): ?array {
    global $cities;
    $normalized = normalizeCityName($name);
    foreach ($cities as $c) {
        if (normalizeCityName($c['name']) === $normalized) return $c;
    }
    foreach ($cities as $c) {
        if (str_contains($normalized, normalizeCityName($c['name'])) || str_contains(normalizeCityName($c['name']), $normalized)) {
            return $c;
        }
    }
    return null;
}

function getCities(): array {
    global $cities;
    return is_array($cities) ? $cities : [];
}
