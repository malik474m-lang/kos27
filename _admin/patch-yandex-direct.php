<?php
/**
 * Патч для вкладки Яндекс Директ в админке
 */
?>
<script>
// Загрузка данных Яндекс Директ
function lYD() {
    var el = document.getElementById('p-direct');
    if (!el) return;
    el.innerHTML = '<div class="flex justify-center py-12"><div class="animate-spin w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full"></div></div>';

    ap('/yandex-direct?action=report&days=30').then(function(d) {
        if (d.error) {
            el.innerHTML = '<div class="bg-red-50 border border-red-200 p-6 rounded-xl text-red-600">Ошибка: ' + e(d.error) + '</div>';
            return;
        }

        var h = '<div class="space-y-6">';

        // Заголовок
        h += '<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">';
        h += '<div><h2 class="text-xl font-bold">📣 Яндекс Директ</h2><p class="text-gray-500 text-sm">Инструменты для эффективной рекламы</p></div>';
        h += '<div class="flex gap-2 flex-wrap">';
        h += '<select id="yd-period" class="sel-f" onchange="lYDReport(this.value)">';
        h += '<option value="7">7 дней</option><option value="30" selected>30 дней</option><option value="90">90 дней</option>';
        h += '</select>';
        h += '<button onclick="ydExportCSV()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">📥 Экспорт CSV</button>';
        h += '</div></div>';

        // Уведомление
        if (d.notice) {
            h += '<div class="bg-blue-50 border border-blue-200 p-4 rounded-xl text-blue-700 text-sm">ℹ️ ' + e(d.notice) + '</div>';
        }

        // Метрики
        h += '<div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="yd-metrics">';
        h += '<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-blue-600">' + (d.total_clicks || 0) + '</p><p class="text-xs text-gray-500">Кликов с Директа</p></div>';
        h += '<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-green-600">' + (d.total_conversions || 0) + '</p><p class="text-xs text-gray-500">Конверсий</p></div>';
        h += '<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-purple-600">' + (d.conversion_rate || 0) + '%</p><p class="text-xs text-gray-500">CR</p></div>';
        h += '<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-amber-600">' + Number(d.total_revenue || 0).toLocaleString('ru-RU') + ' ₽</p><p class="text-xs text-gray-500">Доход</p></div>';
        h += '</div>';

        // Вкладки
        h += '<div class="bg-white rounded-xl border overflow-hidden">';
        h += '<div class="flex border-b overflow-x-auto">';
        h += '<button onclick="ydTab(\'generator\')" class="yd-tab px-4 py-3 text-sm font-medium border-b-2 border-blue-600 text-blue-600 whitespace-nowrap" data-tab="generator">🎯 Генератор</button>';
        h += '<button onclick="ydTab(\'keywords\')" class="yd-tab px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap" data-tab="keywords">🔑 Ключевые слова</button>';
        h += '<button onclick="ydTab(\'analytics\')" class="yd-tab px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap" data-tab="analytics">📈 Аналитика</button>';
        h += '<button onclick="ydTab(\'tips\')" class="yd-tab px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap" data-tab="tips">💡 Советы</button>';
        h += '</div>';
        h += '<div id="yd-content" class="p-6"></div>';
        h += '</div>';

        h += '</div>';
        el.innerHTML = h;
        window.ydReportData = d;
        ydTab('generator');
    }).catch(function(err) {
        el.innerHTML = '<div class="bg-red-50 border border-red-200 p-6 rounded-xl text-red-600">Ошибка загрузки: ' + (err && err.message ? err.message : String(err)) + '</div>';
    });
}

function ydTab(tab) {
    document.querySelectorAll('.yd-tab').forEach(function(t) {
        var isActive = t.dataset.tab === tab;
        t.classList.toggle('border-blue-600', isActive);
        t.classList.toggle('text-blue-600', isActive);
        t.classList.toggle('border-transparent', !isActive);
        t.classList.toggle('text-gray-500', !isActive);
    });
    var content = document.getElementById('yd-content');
    if (!content) return;
    if (tab === 'generator') ydShowGenerator(content);
    else if (tab === 'keywords') ydShowKeywords(content);
    else if (tab === 'analytics') ydShowAnalytics(content);
    else if (tab === 'tips') ydShowTips(content);
}

// === ГЕНЕРАТОР ===
function ydShowGenerator(el) {
    el.innerHTML = '<div class="space-y-4">' +
        '<div class="flex flex-wrap gap-4 items-end">' +
        '<div><label class="block text-xs font-medium mb-1">Категория</label><select id="yd-gen-cat" class="sel-f"><option value="">Все</option><option value="microloans">Займы</option><option value="credits">Кредиты</option><option value="credit_cards">Кредитные карты</option><option value="debit_cards">Дебетовые карты</option></select></div>' +
        '<div><label class="block text-xs font-medium mb-1">Шаблон</label><select id="yd-gen-tpl" class="sel-f"><option value="default">Стандартный</option><option value="urgent">Срочный</option><option value="free">Бесплатный</option><option value="trust">Доверие</option><option value="comparison">Сравнение</option></select></div>' +
        '<button onclick="ydGenerate()" class="btn-p">🔄 Сгенерировать</button>' +
        '</div>' +
        '<div id="yd-gen-list"><p class="text-gray-400 text-center py-6">Нажмите «Сгенерировать» для создания объявлений</p></div>' +
        '</div>';
}

function ydGenerate() {
    var cat = document.getElementById('yd-gen-cat').value;
    var tpl = document.getElementById('yd-gen-tpl').value;
    var list = document.getElementById('yd-gen-list');
    list.innerHTML = '<p class="text-gray-400 text-center py-6">Загрузка...</p>';

    var url = '/yandex-direct?action=generate-ads&template=' + encodeURIComponent(tpl);
    if (cat) url += '&category=' + encodeURIComponent(cat);

    ap(url).then(function(d) {
        if (!d.ads || !d.ads.length) { list.innerHTML = '<p class="text-gray-400 text-center py-6">Нет активных предложений</p>'; return; }

        var h = '<p class="text-sm text-gray-500 mb-4">Сгенерировано ' + d.ads.length + ' объявлений</p>';
        d.ads.forEach(function(item) {
            var adJson = JSON.stringify(item.ad).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
            h += '<div class="bg-gray-50 rounded-xl p-4 border mb-4">';
            h += '<div class="flex justify-between items-start mb-3"><div><span class="font-bold">' + e(item.offer_title) + '</span></div>';
            h += '<button onclick="ydCopyAd(this)" class="text-blue-600 hover:text-blue-800 text-sm" data-ad="' + adJson + '">📋 Копировать</button></div>';

            // Превью
            h += '<div class="bg-white rounded-lg p-4 border mb-3">';
            h += '<p class="text-blue-700 font-medium text-base">' + e(item.ad.title1) + '</p>';
            h += '<p class="text-blue-600 text-sm">' + e(item.ad.title2) + '</p>';
            h += '<p class="text-sm text-gray-700 mt-1">' + e(item.ad.text) + '</p>';
            h += '<p class="text-xs text-green-700 mt-2">kosmozaim.ru/offer/' + e(item.offer_slug) + '</p>';
            h += '</div>';

            h += '<div class="flex flex-wrap gap-2 text-xs">';
            (item.ad.sitelinks||[]).forEach(function(sl) { h += '<span class="bg-blue-100 text-blue-700 px-2 py-1 rounded">' + e(sl.title) + '</span>'; });
            (item.ad.clarifications||[]).forEach(function(cl) { h += '<span class="bg-gray-200 text-gray-600 px-2 py-1 rounded">' + e(cl) + '</span>'; });
            h += '</div></div>';
        });
        list.innerHTML = h;
    }).catch(function(err) {
        list.innerHTML = '<p class="text-red-500 text-center py-6">Ошибка: ' + (err&&err.message?err.message:String(err)) + '</p>';
    });
}

function ydCopyAd(btn) {
    var ad = JSON.parse(btn.dataset.ad);
    var text = 'Заголовок 1: ' + ad.title1 + '\nЗаголовок 2: ' + ad.title2 + '\nТекст: ' + ad.text;
    if (ad.sitelinks) text += '\nБыстрые ссылки: ' + ad.sitelinks.map(function(s){return s.title;}).join(', ');
    if (ad.clarifications) text += '\nУточнения: ' + ad.clarifications.join(', ');
    navigator.clipboard.writeText(text).then(function() {
        btn.textContent = '✅ Скопировано'; setTimeout(function(){ btn.textContent = '📋 Копировать'; }, 2000);
    }).catch(function() { prompt('Скопируйте:', text); });
}

// === КЛЮЧЕВЫЕ СЛОВА ===
function ydShowKeywords(el) {
    el.innerHTML = '<div class="space-y-4">' +
        '<div><label class="block text-xs font-medium mb-1">Категория</label><select id="yd-kw-cat" class="sel-f w-auto" onchange="ydLoadKW()"><option value="microloans">Займы</option><option value="credits">Кредиты</option><option value="credit_cards">Кредитные карты</option><option value="debit_cards">Дебетовые карты</option></select></div>' +
        '<div id="yd-kw-data"><p class="text-gray-400 text-center py-6">Загрузка...</p></div></div>';
    ydLoadKW();
}

function ydLoadKW() {
    var cat = document.getElementById('yd-kw-cat').value;
    var content = document.getElementById('yd-kw-data');
    content.innerHTML = '<p class="text-gray-400 text-center py-4">Загрузка...</p>';

    ap('/yandex-direct?action=keywords&category=' + encodeURIComponent(cat)).then(function(d) {
        var h = '<div class="grid md:grid-cols-2 gap-6">';
        // Левая колонка — ключевые слова
        h += '<div class="space-y-4">';
        h += ydKwBlock('🎯 Высокочастотные', d.keywords.high, 'green');
        h += ydKwBlock('📊 Среднечастотные', d.keywords.medium, 'blue');
        h += ydKwBlock('🔍 Длинный хвост', d.keywords.long_tail, 'purple');
        h += '</div>';
        // Правая колонка — минус-слова
        h += '<div>' + ydKwBlock('🚫 Минус-слова', d.minus_words, 'red') + '</div>';
        h += '</div>';
        content.innerHTML = h;
    });
}

function ydKwBlock(title, words, color) {
    var wordsJson = JSON.stringify(words).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    var h = '<div class="bg-' + color + '-50 rounded-lg p-3 border border-' + color + '-100">';
    h += '<div class="flex justify-between items-center mb-2"><h4 class="font-bold text-sm">' + title + '</h4>';
    h += '<button onclick="ydCopyKW(this)" data-kw="' + wordsJson + '" class="text-' + color + '-600 text-xs hover:underline">📋 Копировать</button></div>';
    h += '<ul class="text-sm space-y-0.5 max-h-60 overflow-y-auto">';
    words.forEach(function(w) { h += '<li class="text-gray-700">• ' + e(w) + '</li>'; });
    h += '</ul></div>';
    return h;
}

function ydCopyKW(btn) {
    var kw = JSON.parse(btn.dataset.kw);
    navigator.clipboard.writeText(kw.join('\n')).then(function() {
        btn.textContent = '✅'; setTimeout(function(){ btn.textContent = '📋 Копировать'; }, 2000);
    }).catch(function() { prompt('Скопируйте:', kw.join('\n')); });
}

// === АНАЛИТИКА ===
function ydShowAnalytics(el) {
    var d = window.ydReportData || {};
    var h = '<div class="space-y-6">';

    h += '<div><h4 class="font-bold mb-3">📊 Кампании</h4>';
    if (d.campaigns && d.campaigns.length) {
        h += '<div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b bg-gray-50"><th class="text-left py-2 px-3">Кампания</th><th class="text-right py-2 px-3">Клики</th><th class="text-right py-2 px-3">Уникальные</th></tr></thead><tbody>';
        d.campaigns.forEach(function(c) {
            h += '<tr class="border-b hover:bg-gray-50"><td class="py-2 px-3">' + e(c.utm_campaign || '(без имени)') + '</td><td class="text-right py-2 px-3 font-medium">' + c.total_clicks + '</td><td class="text-right py-2 px-3">' + c.unique_visitors + '</td></tr>';
        });
        h += '</tbody></table></div>';
    } else {
        h += '<p class="text-gray-400 text-sm">Пока нет данных. Запустите рекламу с UTM-метками utm_source=yandex.</p>';
    }
    h += '</div>';

    h += '<div><h4 class="font-bold mb-3">🔑 Топ ключевых слов</h4>';
    if (d.top_keywords && d.top_keywords.length) {
        h += '<div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b bg-gray-50"><th class="text-left py-2 px-3">Ключевое слово</th><th class="text-right py-2 px-3">Клики</th><th class="text-right py-2 px-3">Посетители</th></tr></thead><tbody>';
        d.top_keywords.forEach(function(kw) {
            h += '<tr class="border-b hover:bg-gray-50"><td class="py-2 px-3">' + e(kw.keyword) + '</td><td class="text-right py-2 px-3 font-medium">' + kw.clicks + '</td><td class="text-right py-2 px-3">' + kw.visitors + '</td></tr>';
        });
        h += '</tbody></table></div>';
    } else {
        h += '<p class="text-gray-400 text-sm">Пока нет данных по ключевым словам.</p>';
    }
    h += '</div></div>';
    el.innerHTML = h;
}

// === СОВЕТЫ ===
function ydShowTips(el) {
    var d = window.ydReportData || {};
    var h = '<div class="space-y-4">';

    if (d.tips && d.tips.length) {
        d.tips.forEach(function(tip) {
            var colors = {success:'bg-green-50 border-green-200 text-green-800', warning:'bg-yellow-50 border-yellow-200 text-yellow-800', info:'bg-blue-50 border-blue-200 text-blue-800'};
            var icons = {success:'✅', warning:'⚠️', info:'💡'};
            h += '<div class="p-4 rounded-lg border ' + (colors[tip.type]||colors.info) + '"><p class="font-semibold">' + (icons[tip.type]||'💡') + ' ' + e(tip.title) + '</p><p class="text-sm mt-1">' + e(tip.message) + '</p></div>';
        });
    }

    // Общие советы
    h += '<div class="bg-gray-50 rounded-xl p-6 mt-4 border">';
    h += '<h4 class="font-bold mb-4">📚 Чеклист запуска рекламы в Директе</h4>';
    h += '<div class="space-y-3 text-sm">';
    h += '<label class="flex items-start gap-3 cursor-pointer"><input type="checkbox" class="w-4 h-4 mt-0.5 rounded"><span>Указать <strong>лицензию ЦБ РФ</strong> в тексте (для прохождения модерации)</span></label>';
    h += '<label class="flex items-start gap-3 cursor-pointer"><input type="checkbox" class="w-4 h-4 mt-0.5 rounded"><span>Настроить аудиторию <strong>18+</strong></span></label>';
    h += '<label class="flex items-start gap-3 cursor-pointer"><input type="checkbox" class="w-4 h-4 mt-0.5 rounded"><span>Добавить <strong>минус-слова</strong> (вкладка «Ключевые слова»)</span></label>';
    h += '<label class="flex items-start gap-3 cursor-pointer"><input type="checkbox" class="w-4 h-4 mt-0.5 rounded"><span>Настроить <strong>корректировки ставок</strong> по времени (10-14, 18-22)</span></label>';
    h += '<label class="flex items-start gap-3 cursor-pointer"><input type="checkbox" class="w-4 h-4 mt-0.5 rounded"><span>Увеличить ставку на <strong>мобильные</strong> (+20%)</span></label>';
    h += '<label class="flex items-start gap-3 cursor-pointer"><input type="checkbox" class="w-4 h-4 mt-0.5 rounded"><span>Исключить <strong>нерелевантные регионы</strong></span></label>';
    h += '<label class="flex items-start gap-3 cursor-pointer"><input type="checkbox" class="w-4 h-4 mt-0.5 rounded"><span>Настроить <strong>UTM-метки</strong> (utm_source=yandex&utm_medium=cpc)</span></label>';
    h += '<label class="flex items-start gap-3 cursor-pointer"><input type="checkbox" class="w-4 h-4 mt-0.5 rounded"><span>Создать <strong>2-3 варианта объявлений</strong> для A/B теста</span></label>';
    h += '<label class="flex items-start gap-3 cursor-pointer"><input type="checkbox" class="w-4 h-4 mt-0.5 rounded"><span>Настроить <strong>ретаргетинг</strong> на посетителей сайта</span></label>';
    h += '</div></div>';

    h += '</div>';
    el.innerHTML = h;
}

// Экспорт CSV
function ydExportCSV() {
    var cat = document.getElementById('yd-gen-cat') ? document.getElementById('yd-gen-cat').value : '';
    var url = '/api/admin/yandex-direct?action=export-csv';
    if (cat) url += '&category=' + encodeURIComponent(cat);
    window.open(url, '_blank');
}

// Обновление за период
function lYDReport(days) {
    ap('/yandex-direct?action=report&days=' + days).then(function(d) {
        window.ydReportData = d;
        var metrics = document.getElementById('yd-metrics');
        if (metrics) {
            var divs = metrics.querySelectorAll('.text-2xl');
            if (divs[0]) divs[0].textContent = d.total_clicks || 0;
            if (divs[1]) divs[1].textContent = d.total_conversions || 0;
            if (divs[2]) divs[2].textContent = (d.conversion_rate || 0) + '%';
            if (divs[3]) divs[3].textContent = Number(d.total_revenue || 0).toLocaleString('ru-RU') + ' ₽';
        }
    });
}
</script>
