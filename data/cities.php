<?php
/**
 * Города — загрузка из JSON-файла (редактируется через админку)
 * Если JSON отсутствует или повреждён — используем встроенный fallback.
 */
$cities = [];
$citiesFallback = [
    ['name'=>'Москва','slug'=>'moskva','region'=>'Московская область','prep'=>'Москве'],
    ['name'=>'Санкт-Петербург','slug'=>'sankt-peterburg','region'=>'Ленинградская область','prep'=>'Санкт-Петербурге'],
    ['name'=>'Новосибирск','slug'=>'novosibirsk','region'=>'Новосибирская область','prep'=>'Новосибирске'],
    ['name'=>'Екатеринбург','slug'=>'ekaterinburg','region'=>'Свердловская область','prep'=>'Екатеринбурге'],
    ['name'=>'Казань','slug'=>'kazan','region'=>'Республика Татарстан','prep'=>'Казани'],
    ['name'=>'Нижний Новгород','slug'=>'nizhnij-novgorod','region'=>'Нижегородская область','prep'=>'Нижнем Новгороде'],
    ['name'=>'Челябинск','slug'=>'chelyabinsk','region'=>'Челябинская область','prep'=>'Челябинске'],
    ['name'=>'Самара','slug'=>'samara','region'=>'Самарская область','prep'=>'Самаре'],
    ['name'=>'Омск','slug'=>'omsk','region'=>'Омская область','prep'=>'Омске'],
    ['name'=>'Ростов-на-Дону','slug'=>'rostov-na-donu','region'=>'Ростовская область','prep'=>'Ростове-на-Дону'],
    ['name'=>'Уфа','slug'=>'ufa','region'=>'Республика Башкортостан','prep'=>'Уфе'],
    ['name'=>'Красноярск','slug'=>'krasnoyarsk','region'=>'Красноярский край','prep'=>'Красноярске'],
];
$citiesJsonFile = __DIR__ . '/cities.json';
if (file_exists($citiesJsonFile)) {
    $citiesJson = json_decode(file_get_contents($citiesJsonFile), true);
    if (is_array($citiesJson) && $citiesJson) {
        $cities = $citiesJson;
    }
}
if (!$cities) {
    $cities = $citiesFallback;
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
