<?php
/**
 * Патч для вкладки Яндекс Директ в админке
 */
?>
<script>
// Загрузка данных Яндекс Директ
function lYD() {
    var el = document.getElementById('p-direct');
    el.innerHTML = '<div class="flex justify-center py-12"><div class="animate-spin w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full"></div></div>';
    
    ap('/yandex-direct?action=report&days=30').then(function(d) {
        var h = '<div class="space-y-6">';
        
        // Заголовок
        h += '<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">';
        h += '<div><h2 class="text-xl font-bold">📊 Яндекс Директ</h2><p class="text-gray-500 text-sm">Инструменты для эффективной рекламы</p></div>';
        h += '<div class="flex gap-2">';
        h += '<select id="yd-period" class="sel-f" onchange="lYDReport(this.value)">';
        h += '<option value="7">7 дней</option><option value="30" selected>30 дней</option><option value="90">90 дней</option>';
        h += '</select>';
        h += '<button onclick="ydExportCSV()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">📥 Экспорт CSV</button>';
        h += '</div></div>';
        
        // Метрики
        h += '<div class="grid grid-cols-2 md:grid-cols-4 gap-4">';
        h += '<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-blue-600">' + (d.total_clicks || 0) + '</p><p class="text-xs text-gray-500">Кликов с Директа</p></div>';
        h += '<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-green-600">' + (d.total_conversions || 0) + '</p><p class="text-xs text-gray-500">Конверсий</p></div>';
        h += '<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-purple-600">' + (d.conversion_rate || 0) + '%</p><p class="text-xs text-gray-500">CR</p></div>';
        h += '<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-amber-600">' + (d.total_revenue || 0).toLocaleString('ru-RU') + ' ₽</p><p class="text-xs text-gray-500">Доход</p></div>';
        h += '</div>';
        
        // Вкладки
        h += '<div class="bg-white rounded-xl border overflow-hidden">';
        h += '<div class="flex border-b">';
        h += '<button onclick="ydTab(\'generator\')" class="yd-tab px-4 py-3 text-sm font-medium border-b-2 border-blue-600 text-blue-600" data-tab="generator">🎯 Генератор объявлений</button>';
        h += '<button onclick="ydTab(\'keywords\')" class="yd-tab px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700" data-tab="keywords">🔑 Ключевые слова</button>';
        h += '<button onclick="ydTab(\'analytics\')" class="yd-tab px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700" data-tab="analytics">📈 Аналитика</button>';
        h += '<button onclick="ydTab(\'tips\')" class="yd-tab px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700" data-tab="tips">💡 Рекомендации</button>';
        h += '</div>';
        
        // Контент вкладок
        h += '<div id="yd-content" class="p-6"></div>';
        h += '</div>';
        
        h += '</div>';
        
        el.innerHTML = h;
        window.ydData = d;
        ydTab('generator');
    }).catch(function(err) {
        el.innerHTML = '<div class="bg-red-50 border border-red-200 p-6 rounded-xl text-red-600">Ошибка загрузки: ' + (err.message || err) + '</div>';
    });
}

// Переключение вкладок
function ydTab(tab) {
    document.querySelectorAll('.yd-tab').forEach(function(t) {
        var isActive = t.dataset.tab === tab;
        t.classList.toggle('border-blue-600', isActive);
        t.classList.toggle('text-blue-600', isActive);
        t.classList.toggle('border-transparent', !isActive);
        t.classList.toggle('text-gray-500', !isActive);
    });
    
    var content = document.getElementById('yd-content');
    
    if (tab === 'generator') {
        ydLoadGenerator(content);
    } else if (tab === 'keywords') {
        ydLoadKeywords(content);
    } else if (tab === 'analytics') {
        ydLoadAnalytics(content);
    } else if (tab === 'tips') {
        ydLoadTips(content);
    }
}

// Генератор объявлений
function ydLoadGenerator(el) {
    var h = '<div class="space-y-4">';
    h += '<div class="flex flex-wrap gap-4 items-end">';
    h += '<div><label class="block text-xs font-medium mb-1">Категория</label><select id="yd-cat" class="sel-f" onchange="ydGenerateAds()"><option value="">Все</option><option value="microloans">Займы</option><option value="credits">Кредиты</option><option value="credit_cards">Кредитные карты</option><option value="debit_cards">Дебетовые карты</option></select></div>';
    h += '<div><label class="block text-xs font-medium mb-1">Шаблон</label><select id="yd-tpl" class="sel-f" onchange="ydGenerateAds()"><option value="default">Стандартный</option><option value="urgent">Срочный</option><option value="free">Бесплатный</option><option value="trust">Доверие</option><option value="comparison">Сравнение</option></select></div>';
    h += '<button onclick="ydGenerateAds()" class="btn-p">🔄 Сгенерировать</button>';
    h += '</div>';
    h += '<div id="yd-ads-list" class="space-y-4"></div>';
    h += '</div>';
    el.innerHTML = h;
    ydGenerateAds();
}

// Генерация объявлений
function ydGenerateAds() {
    var cat = document.getElementById('yd-cat').value;
    var tpl = document.getElementById('yd-tpl').value;
    var list = document.getElementById('yd-ads-list');
    list.innerHTML = '<div class="text-center py-8 text-gray-400">Загрузка...</div>';
    
    var url = '/yandex-direct?action=generate-ads&template=' + tpl;
    if (cat) url += '&category=' + cat;
    
    ap(url).then(function(d) {
        if (!d.ads || !d.ads.length) {
            list.innerHTML = '<div class="text-center py-8 text-gray-400">Нет офферов для генерации</div>';
            return;
        }
        
        var h = '';
        d.ads.forEach(function(item) {
            h += '<div class="bg-gray-50 rounded-xl p-4 border">';
            h += '<div class="flex justify-between items-start mb-3">';
            h += '<div><span class="font-bold">' + e(item.offer_title) + '</span><span class="text-xs text-gray-400 ml-2">' + item.category + '</span></div>';
            h += '<button onclick="ydCopyAd(this)" class="text-blue-600 hover:text-blue-800 text-sm" data-ad=\'' + JSON.stringify(item.ad).replace(/'/g, "&#39;") + '\'>📋 Копировать</button>';
            h += '</div>';
            
            // Превью объявления
            h += '<div class="bg-white rounded-lg p-4 border mb-3">';
            h += '<p class="text-blue-600 font-medium">' + e(item.ad.title1) + '</p>';
            h += '<p class="text-blue-600">' + e(item.ad.title2) + '</p>';
            h += '<p class="text-sm text-gray-700 mt-1">' + e(item.ad.text) + '</p>';
            h += '<p class="text-xs text-green-600 mt-2">kosmozaim.ru/' + e(item.offer_slug) + '</p>';
            h += '</div>';
            
            // Быстрые ссылки
            h += '<div class="flex flex-wrap gap-2 text-xs">';
            item.ad.sitelinks.forEach(function(sl) {
                h += '<span class="bg-blue-100 text-blue-700 px-2 py-1 rounded">' + e(sl.title) + '</span>';
            });
            h += '</div>';
            
            // Уточнения
            h += '<div class="flex flex-wrap gap-2 text-xs mt-2">';
            item.ad.clarifications.forEach(function(cl) {
                h += '<span class="bg-gray-200 text-gray-600 px-2 py-1 rounded">' + e(cl) + '</span>';
            });
            h += '</div>';
            
            h += '</div>';
        });
        
        list.innerHTML = h;
    });
}

// Копирование объявления
function ydCopyAd(btn) {
    var ad = JSON.parse(btn.dataset.ad.replace(/&#39;/g, "'"));
    var text = 'Заголовок 1: ' + ad.title1 + '\n';
    text += 'Заголовок 2: ' + ad.title2 + '\n';
    text += 'Текст: ' + ad.text + '\n';
    text += 'Быстрые ссылки: ' + ad.sitelinks.map(function(s) { return s.title; }).join(', ') + '\n';
    text += 'Уточнения: ' + ad.clarifications.join(', ');
    
    navigator.clipboard.writeText(text).then(function() {
        btn.textContent = '✅ Скопировано';
        setTimeout(function() { btn.textContent = '📋 Копировать'; }, 2000);
    });
}

// Ключевые слова
function ydLoadKeywords(el) {
    var h = '<div class="space-y-4">';
    h += '<div class="flex gap-4 items-end">';
    h += '<div><label class="block text-xs font-medium mb-1">Категория</label><select id="yd-kw-cat" class="sel-f" onchange="ydLoadKW(this.value)"><option value="microloans">Займы</option><option value="credits">Кредиты</option><option value="credit_cards">Кредитные карты</option><option value="debit_cards">Дебетовые карты</option></select></div>';
    h += '</div>';
    h += '<div id="yd-kw-content"></div>';
    h += '</div>';
    el.innerHTML = h;
    ydLoadKW('microloans');
}

function ydLoadKW(cat) {
    var content = document.getElementById('yd-kw-content');
    content.innerHTML = '<div class="text-center py-4 text-gray-400">Загрузка...</div>';
    
    ap('/yandex-direct?action=keywords&category=' + cat).then(function(d) {
        var h = '<div class="grid md:grid-cols-2 gap-6">';
        
        // Ключевые слова
        h += '<div>';
        h += '<h4 class="font-bold mb-3">🎯 Высокочастотные</h4>';
        h += '<div class="bg-green-50 rounded-lg p-3 mb-4"><ul class="text-sm space-y-1">';
        d.keywords.high.forEach(function(kw) { h += '<li>• ' + e(kw) + '</li>'; });
        h += '</ul><button onclick="ydCopyList(this, \'high\')" class="text-green-600 text-xs mt-2 hover:underline" data-kw=\'' + JSON.stringify(d.keywords.high) + '\'>📋 Копировать все</button></div>';
        
        h += '<h4 class="font-bold mb-3">📊 Среднечастотные</h4>';
        h += '<div class="bg-blue-50 rounded-lg p-3 mb-4"><ul class="text-sm space-y-1">';
        d.keywords.medium.forEach(function(kw) { h += '<li>• ' + e(kw) + '</li>'; });
        h += '</ul><button onclick="ydCopyList(this, \'medium\')" class="text-blue-600 text-xs mt-2 hover:underline" data-kw=\'' + JSON.stringify(d.keywords.medium) + '\'>📋 Копировать все</button></div>';
        
        h += '<h4 class="font-bold mb-3">🔍 Длинный хвост</h4>';
        h += '<div class="bg-purple-50 rounded-lg p-3"><ul class="text-sm space-y-1">';
        d.keywords.long_tail.forEach(function(kw) { h += '<li>• ' + e(kw) + '</li>'; });
        h += '</ul><button onclick="ydCopyList(this, \'long\')" class="text-purple-600 text-xs mt-2 hover:underline" data-kw=\'' + JSON.stringify(d.keywords.long_tail) + '\'>📋 Копировать все</button></div>';
        h += '</div>';
        
        // Минус-слова
        h += '<div>';
        h += '<h4 class="font-bold mb-3">🚫 Минус-слова</h4>';
        h += '<div class="bg-red-50 rounded-lg p-3"><ul class="text-sm space-y-1 max-h-96 overflow-y-auto">';
        d.minus_words.forEach(function(mw) { h += '<li>• ' + e(mw) + '</li>'; });
        h += '</ul><button onclick="ydCopyList(this, \'minus\')" class="text-red-600 text-xs mt-2 hover:underline" data-kw=\'' + JSON.stringify(d.minus_words) + '\'>📋 Копировать все</button></div>';
        h += '</div>';
        
        h += '</div>';
        content.innerHTML = h;
    });
}

function ydCopyList(btn, type) {
    var kw = JSON.parse(btn.dataset.kw);
    var text = kw.join('\n');
    navigator.clipboard.writeText(text).then(function() {
        btn.textContent = '✅ Скопировано';
        setTimeout(function() { btn.textContent = '📋 Копировать все'; }, 2000);
    });
}

// Аналитика
function ydLoadAnalytics(el) {
    var d = window.ydData || {};
    var h = '<div class="space-y-6">';
    
    // Кампании
    h += '<div>';
    h += '<h4 class="font-bold mb-3">📊 Кампании</h4>';
    if (d.campaigns && d.campaigns.length) {
        h += '<div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b"><th class="text-left py-2">Кампания</th><th class="text-right py-2">Клики</th><th class="text-right py-2">Уникальные</th></tr></thead><tbody>';
        d.campaigns.forEach(function(c) {
            h += '<tr class="border-b hover:bg-gray-50"><td class="py-2">' + e(c.utm_campaign || '(не указано)') + '</td><td class="text-right py-2">' + c.total_clicks + '</td><td class="text-right py-2">' + c.unique_visitors + '</td></tr>';
        });
        h += '</tbody></table></div>';
    } else {
        h += '<p class="text-gray-400">Нет данных по кампаниям</p>';
    }
    h += '</div>';
    
    // Топ ключевых слов
    h += '<div>';
    h += '<h4 class="font-bold mb-3">🔑 Топ ключевых слов</h4>';
    if (d.top_keywords && d.top_keywords.length) {
        h += '<div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b"><th class="text-left py-2">Ключевое слово</th><th class="text-right py-2">Клики</th><th class="text-right py-2">Посетители</th></tr></thead><tbody>';
        d.top_keywords.forEach(function(kw) {
            h += '<tr class="border-b hover:bg-gray-50"><td class="py-2">' + e(kw.keyword) + '</td><td class="text-right py-2">' + kw.clicks + '</td><td class="text-right py-2">' + kw.visitors + '</td></tr>';
        });
        h += '</tbody></table></div>';
    } else {
        h += '<p class="text-gray-400">Нет данных по ключевым словам</p>';
    }
    h += '</div>';
    
    h += '</div>';
    el.innerHTML = h;
}

// Рекомендации
function ydLoadTips(el) {
    var d = window.ydData || {};
    var h = '<div class="space-y-4">';
    
    if (d.tips && d.tips.length) {
        d.tips.forEach(function(tip) {
            var colors = {success: 'bg-green-50 border-green-200 text-green-800', warning: 'bg-yellow-50 border-yellow-200 text-yellow-800', info: 'bg-blue-50 border-blue-200 text-blue-800'};
            var icons = {success: '✅', warning: '⚠️', info: '💡'};
            h += '<div class="p-4 rounded-lg border ' + (colors[tip.type] || colors.info) + '">';
            h += '<p class="font-semibold">' + (icons[tip.type] || '💡') + ' ' + e(tip.title) + '</p>';
            h += '<p class="text-sm mt-1">' + e(tip.message) + '</p>';
            h += '</div>';
        });
    } else {
        h += '<p class="text-gray-400">Недостаточно данных для рекомендаций. Запустите рекламную кампанию и вернитесь позже.</p>';
    }
    
    // Общие советы
    h += '<div class="bg-gray-50 rounded-lg p-4 mt-6">';
    h += '<h4 class="font-bold mb-3">📚 Общие рекомендации для финансовой тематики</h4>';
    h += '<ul class="text-sm space-y-2">';
    h += '<li>• <strong>Модерация:</strong> Объявления о займах проходят строгую модерацию. Указывайте лицензию ЦБ РФ.</li>';
    h += '<li>• <strong>Возраст:</strong> Настройте показ только для 18+ аудитории.</li>';
    h += '<li>• <strong>Регионы:</strong> Исключите регионы с низкой конверсией.</li>';
    h += '<li>• <strong>Время:</strong> Пиковые часы: 10:00-14:00, 18:00-22:00.</li>';
    h += '<li>• <strong>Устройства:</strong> Мобильный трафик часто конвертируется лучше.</li>';
    h += '<li>• <strong>Ретаргетинг:</strong> Настройте ретаргетинг на посетителей страниц офферов.</li>';
    h += '</ul>';
    h += '</div>';
    
    h += '</div>';
    el.innerHTML = h;
}

// Экспорт CSV
function ydExportCSV() {
    var cat = document.getElementById('yd-cat') ? document.getElementById('yd-cat').value : '';
    var url = '/api/admin/yandex-direct?action=export-csv';
    if (cat) url += '&category=' + cat;
    window.open(url, '_blank');
}

// Загрузка отчёта за период
function lYDReport(days) {
    ap('/yandex-direct?action=report&days=' + days).then(function(d) {
        window.ydData = d;
        // Обновляем метрики
        var metrics = document.querySelectorAll('#p-direct .grid-cols-2.md\\:grid-cols-4 > div');
        if (metrics.length >= 4) {
            metrics[0].querySelector('.text-2xl').textContent = d.total_clicks || 0;
            metrics[1].querySelector('.text-2xl').textContent = d.total_conversions || 0;
            metrics[2].querySelector('.text-2xl').textContent = (d.conversion_rate || 0) + '%';
            metrics[3].querySelector('.text-2xl').textContent = (d.total_revenue || 0).toLocaleString('ru-RU') + ' ₽';
        }
    });
}

// Добавляем вкладку в навигацию (при загрузке)
(function() {
    // Добавляем обработчик для новой вкладки
    var origSw = window.sw;
    window.sw = function(t) {
        origSw(t);
        if (t === 'direct') lYD();
    };
})();
</script>
