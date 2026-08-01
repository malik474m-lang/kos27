<?php
$db = getDB();
$offers = $db->query("SELECT * FROM offers WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

$pageTitle = 'Сравнение предложений — ' . SITE_NAME;
$metaDescription = 'Сравните условия займов, кредитов и банковских карт. Выберите до 4 предложений для сравнения.';

ob_start();
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6"><a href="/" class="hover:text-primary">Главная</a> → Сравнение</nav>
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Сравнение предложений</h1>

    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Левая часть: выбор -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sticky top-20">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-bold text-gray-900">Выберите предложения</h2>
                    <span id="cmp-count" class="text-sm text-gray-500">0/4</span>
                </div>

                <select id="cmp-cat" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm mb-4" onchange="cmpFilter()">
                    <option value="all">Все категории</option>
                    <option value="microloans">Займы</option>
                    <option value="credits">Кредиты</option>
                    <option value="credit_cards">Кредитные карты</option>
                    <option value="debit_cards">Дебетовые карты</option>
                </select>

                <div id="cmp-list" class="space-y-2 max-h-96 overflow-y-auto"></div>

                <button onclick="cmpClear()" id="cmp-clear-btn" class="hidden w-full mt-4 text-sm text-gray-500 hover:text-gray-700">Очистить выбор</button>
            </div>
        </div>

        <!-- Правая часть: таблица сравнения -->
        <div class="lg:col-span-2" id="cmp-table">
            <div class="text-center py-16 bg-white rounded-xl border border-gray-100">
                <p class="text-3xl mb-3">⚖️</p>
                <p class="text-gray-500 text-lg">Выберите предложения для сравнения</p>
                <p class="text-gray-400 text-sm mt-2">Отметьте до 4 предложений в списке слева</p>
            </div>
        </div>
    </div>
</div>

<script>
var allOffers = <?= json_encode($offers) ?>;
var selected = [];
var CL = {microloans:'Займы',credits:'Кредиты',credit_cards:'Кредитные карты',debit_cards:'Дебетовые карты'};

function fmt(n) { return Math.round(n).toLocaleString('ru-RU') + ' ₽'; }
function fmtD(d) { if(d<=0)return'0 дней';if(d==1)return'1 день';if(d<5)return d+' дня';return d+' дней'; }

function cmpFilter() {
    var cat = document.getElementById('cmp-cat').value;
    var filtered = cat === 'all' ? allOffers : allOffers.filter(function(o) { return o.category === cat; });
    var html = '';

    filtered.forEach(function(o) {
        var checked = selected.indexOf(o.id) !== -1;
        var disabled = !checked && selected.length >= 4;
        var cls = checked ? 'bg-blue-50 border-blue-500' : 'bg-gray-50 hover:bg-gray-100 border-transparent';

        html += '<label class="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors border ' + cls + '">' +
            '<input type="checkbox" ' + (checked ? 'checked' : '') + ' ' + (disabled ? 'disabled' : '') +
            ' onchange="cmpToggle(' + o.id + ')" class="w-4 h-4 rounded">' +
            '<div class="flex-1 min-w-0">' +
            '<p class="font-medium text-sm text-gray-900 truncate">' + o.title + '</p>' +
            '<p class="text-xs text-gray-500">' + (CL[o.category] || o.category) + '</p>' +
            '</div></label>';
    });

    document.getElementById('cmp-list').innerHTML = html;
}

function cmpToggle(id) {
    var idx = selected.indexOf(id);
    if (idx !== -1) {
        selected.splice(idx, 1);
    } else if (selected.length < 4) {
        selected.push(id);
    }
    cmpFilter();
    cmpRender();
}

function cmpClear() {
    selected = [];
    cmpFilter();
    cmpRender();
}

function cmpRender() {
    document.getElementById('cmp-count').textContent = selected.length + '/4';
    document.getElementById('cmp-clear-btn').classList.toggle('hidden', selected.length === 0);

    if (selected.length === 0) {
        document.getElementById('cmp-table').innerHTML = '<div class="text-center py-16 bg-white rounded-xl border border-gray-100"><p class="text-3xl mb-3">⚖️</p><p class="text-gray-500 text-lg">Выберите предложения для сравнения</p><p class="text-gray-400 text-sm mt-2">Отметьте до 4 предложений в списке слева</p></div>';
        return;
    }

    var sel = allOffers.filter(function(o) { return selected.indexOf(o.id) !== -1; });

    var h = '<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"><div class="overflow-x-auto"><table class="w-full text-sm">';

    // Header
    h += '<thead><tr class="border-b"><th class="text-left p-4 bg-gray-50 font-medium text-gray-600 w-32">Параметр</th>';
    sel.forEach(function(o) {
        var logo = o.logo_url || '';
        if (logo.indexOf('/public/') === 0) logo = logo.substring(7);
        var logoHtml = logo ? '<img src="' + logo + '" alt="" class="w-full h-full object-contain p-1" loading="lazy" decoding="async">' : '<span class="text-xl">🏦</span>';
        h += '<th class="p-4 text-center min-w-[150px]"><div class="flex flex-col items-center gap-2"><div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden">' + logoHtml + '</div><span class="font-bold text-gray-900 text-sm">' + o.title + '</span><button onclick="cmpToggle(' + o.id + ')" class="text-xs text-gray-400 hover:text-red-500">✕ Убрать</button></div></th>';
    });
    h += '</tr></thead><tbody>';

    // Категория
    h += '<tr class="border-b"><td class="p-4 bg-gray-50 font-medium text-gray-600">Категория</td>';
    sel.forEach(function(o) { h += '<td class="p-4 text-center"><span class="px-2 py-1 bg-gray-100 rounded text-xs">' + (CL[o.category] || o.category) + '</span></td>'; });
    h += '</tr>';

    // Сумма
    h += '<tr class="border-b"><td class="p-4 bg-gray-50 font-medium text-gray-600">Сумма</td>';
    sel.forEach(function(o) { h += '<td class="p-4 text-center font-semibold">' + fmt(o.amount_min) + ' — ' + fmt(o.amount_max) + '</td>'; });
    h += '</tr>';

    // Срок
    h += '<tr class="border-b"><td class="p-4 bg-gray-50 font-medium text-gray-600">Срок</td>';
    sel.forEach(function(o) { h += '<td class="p-4 text-center">' + fmtD(o.term_min_days) + ' — ' + fmtD(o.term_max_days) + '</td>'; });
    h += '</tr>';

    // Ставка
    h += '<tr class="border-b"><td class="p-4 bg-gray-50 font-medium text-gray-600">Ставка</td>';
    sel.forEach(function(o) { var unit=(o.rate_unit==='year'?'в год':'в день'); h += '<td class="p-4 text-center font-semibold text-blue-600">от ' + o.rate + '% ' + unit + '</td>'; });
    h += '</tr>';

    // ПСК
    h += '<tr class="border-b"><td class="p-4 bg-gray-50 font-medium text-gray-600">ПСК</td>';
    sel.forEach(function(o) { h += '<td class="p-4 text-center">' + o.psk + '%</td>'; });
    h += '</tr>';

    // Без %
    h += '<tr class="border-b"><td class="p-4 bg-gray-50 font-medium text-gray-600">Без %</td>';
    sel.forEach(function(o) { h += '<td class="p-4 text-center">' + (o.free_term_days > 0 ? '<span class="text-green-600 font-semibold">' + fmtD(o.free_term_days) + '</span>' : '<span class="text-gray-400">—</span>') + '</td>'; });
    h += '</tr>';

    // Рейтинг
    h += '<tr class="border-b"><td class="p-4 bg-gray-50 font-medium text-gray-600">Рейтинг</td>';
    sel.forEach(function(o) { h += '<td class="p-4 text-center">' + (parseFloat(o.rating) > 0 ? '<span class="text-yellow-600">★ ' + parseFloat(o.rating).toFixed(1) + '</span> <span class="text-gray-400 text-xs">(' + o.review_count + ')</span>' : '—') + '</td>'; });
    h += '</tr>';

    // Оформить
    h += '<tr><td class="p-4 bg-gray-50 font-medium text-gray-600">Оформить</td>';
    sel.forEach(function(o) { h += '<td class="p-4 text-center"><a href="/click/' + o.id + '" target="_blank" rel="noopener noreferrer nofollow sponsored" class="inline-block bg-accent text-white px-4 py-2 rounded-lg text-xs font-semibold hover:bg-accent-dark">Перейти →</a></td>'; });
    h += '</tr>';

    h += '</tbody></table></div></div>';
    document.getElementById('cmp-table').innerHTML = h;
}

cmpFilter();
</script>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>'Сравнение','url'=>'/compare']]),
];
$canonicalUrl = SITE_URL . '/compare';
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';
