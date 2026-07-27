<?php
$cities = [
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
    ['name'=>'Воронеж','slug'=>'voronezh','region'=>'Воронежская область','prep'=>'Воронеже'],
    ['name'=>'Пермь','slug'=>'perm','region'=>'Пермский край','prep'=>'Перми'],
    ['name'=>'Волгоград','slug'=>'volgograd','region'=>'Волгоградская область','prep'=>'Волгограде'],
    ['name'=>'Краснодар','slug'=>'krasnodar','region'=>'Краснодарский край','prep'=>'Краснодаре'],
    ['name'=>'Саратов','slug'=>'saratov','region'=>'Саратовская область','prep'=>'Саратове'],
    ['name'=>'Тюмень','slug'=>'tyumen','region'=>'Тюменская область','prep'=>'Тюмени'],
    ['name'=>'Тольятти','slug'=>'tolyatti','region'=>'Самарская область','prep'=>'Тольятти'],
    ['name'=>'Ижевск','slug'=>'izhevsk','region'=>'Удмуртская Республика','prep'=>'Ижевске'],
    ['name'=>'Барнаул','slug'=>'barnaul','region'=>'Алтайский край','prep'=>'Барнауле'],
    ['name'=>'Ульяновск','slug'=>'ulyanovsk','region'=>'Ульяновская область','prep'=>'Ульяновске'],
    ['name'=>'Иркутск','slug'=>'irkutsk','region'=>'Иркутская область','prep'=>'Иркутске'],
    ['name'=>'Хабаровск','slug'=>'habarovsk','region'=>'Хабаровский край','prep'=>'Хабаровске'],
    ['name'=>'Ярославль','slug'=>'yaroslavl','region'=>'Ярославская область','prep'=>'Ярославле'],
    ['name'=>'Владивосток','slug'=>'vladivostok','region'=>'Приморский край','prep'=>'Владивостоке'],
    ['name'=>'Махачкала','slug'=>'mahachkala','region'=>'Республика Дагестан','prep'=>'Махачкале'],
    ['name'=>'Томск','slug'=>'tomsk','region'=>'Томская область','prep'=>'Томске'],
    ['name'=>'Оренбург','slug'=>'orenburg','region'=>'Оренбургская область','prep'=>'Оренбурге'],
    ['name'=>'Кемерово','slug'=>'kemerovo','region'=>'Кемеровская область','prep'=>'Кемерове'],
    ['name'=>'Рязань','slug'=>'ryazan','region'=>'Рязанская область','prep'=>'Рязани'],
    ['name'=>'Астрахань','slug'=>'astrahan','region'=>'Астраханская область','prep'=>'Астрахани'],
    ['name'=>'Пенза','slug'=>'penza','region'=>'Пензенская область','prep'=>'Пензе'],
    ['name'=>'Калининград','slug'=>'kaliningrad','region'=>'Калининградская область','prep'=>'Калининграде'],
    ['name'=>'Тула','slug'=>'tula','region'=>'Тульская область','prep'=>'Туле'],
    ['name'=>'Сочи','slug'=>'sochi','region'=>'Краснодарский край','prep'=>'Сочи'],
    ['name'=>'Курск','slug'=>'kursk','region'=>'Курская область','prep'=>'Курске'],
    ['name'=>'Тверь','slug'=>'tver','region'=>'Тверская область','prep'=>'Твери'],
    ['name'=>'Брянск','slug'=>'bryansk','region'=>'Брянская область','prep'=>'Брянске'],
    ['name'=>'Белгород','slug'=>'belgorod','region'=>'Белгородская область','prep'=>'Белгороде'],
    ['name'=>'Сургут','slug'=>'surgut','region'=>'Ханты-Мансийский АО','prep'=>'Сургуте'],
];

function findCityBySlug(string $slug): ?array {
    global $cities;
    foreach ($cities as $c) {
        if ($c['slug'] === $slug) return $c;
    }
    return null;
}
