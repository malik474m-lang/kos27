<?php
$pageTitle = 'Калькулятор займа — Космозайм';
$metaDescription = 'Рассчитайте стоимость займа онлайн. Калькулятор процентов, переплаты и подбор подходящих предложений.';

$db = getDB();
$allOffers = $db->query("SELECT * FROM offers WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

ob_start();
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6"><a href="/" class="hover:text-primary">Главная</a> → Калькулятор</nav>
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Калькулятор займа</h1>

    <div class="grid lg:grid-cols-2 gap-8">
        <!-- Калькулятор -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sm:p-8">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Параметры займа</h2>

            <div class="mb-6">
                <div class="flex justify-between items-center mb-2">
                    <label class="text-sm font-medium text-gray-700">Сумма займа</label>
                    <span id="amount-val" class="text-lg font-bold text-primary">30 000 ₽</span>
                </div>
                <input type="range" id="calc-amount" min="1000" max="1000000" step="1000" value="30000" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600" oninput="calcUpdate()">
                <div class="flex justify-between text-xs text-gray-400 mt-1"><span>1 000 ₽</span><span>1 000 000 ₽</span></div>
            </div>

            <div class="mb-6">
                <div class="flex justify-between items-center mb-2">
                    <label class="text-sm font-medium text-gray-700">Срок</label>
                    <span id="term-val" class="text-lg font-bold text-primary">30 дней</span>
                </div>
                <input type="range" id="calc-term" min="1" max="365" value="30" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600" oninput="calcUpdate()">
                <div class="flex justify-between text-xs text-gray-400 mt-1"><span>1 день</span><span>365 дней</span></div>
            </div>

            <div class="mb-8">
                <div class="flex justify-between items-center mb-2">
                    <label class="text-sm font-medium text-gray-700">Ставка (% в день)</label>
                    <span id="rate-val" class="text-lg font-bold text-primary">1%</span>
                </div>
                <input type="range" id="calc-rate" min="0" max="5" step="0.01" value="1" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600" oninput="calcUpdate()">
                <div class="flex justify-between text-xs text-gray-400 mt-1"><span>0%</span><span>5%</span></div>
            </div>

            <div class="bg-gray-50 rounded-xl p-6">
                <h3 class="font-bold text-gray-900 mb-4">Результат расчёта</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Сумма к возврату</p>
                        <p id="res-total" class="text-xl font-bold text-gray-900">39 000 ₽</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Переплата</p>
                        <p id="res-overpay" class="text-xl font-bold text-red-600">9 000 ₽</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Ежедневный платёж</p>
                        <p id="res-daily" class="text-lg font-bold text-gray-900">1 300 ₽</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Ставка годовая</p>
                        <p id="res-yearly" class="text-lg font-bold text-gray-900">365%</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Подходящие предложения -->
        <div>
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                Подходящие предложения
                <span id="offers-count" class="text-sm font-normal text-gray-500 ml-2"></span>
            </h2>
            <div id="offers-list" class="space-y-4">
                <div class="text-center py-8 text-gray-500">Поиск предложений...</div>
            </div>
        </div>
    </div>
</div>

    <!-- Перелинковка -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mt-12">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Полезные разделы</h2>
        <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-3">
            <a href="/zajmy" class="bg-gray-50 rounded-lg p-4 text-center card-hover block">
                <span class="text-2xl block mb-1">💵</span>
                <span class="font-semibold text-gray-900 text-sm">Займы</span>
            </a>
            <a href="/kredity" class="bg-gray-50 rounded-lg p-4 text-center card-hover block">
                <span class="text-2xl block mb-1">🏦</span>
                <span class="font-semibold text-gray-900 text-sm">Кредиты</span>
            </a>
            <a href="/karty/kreditnye" class="bg-gray-50 rounded-lg p-4 text-center card-hover block">
                <span class="text-2xl block mb-1">💳</span>
                <span class="font-semibold text-gray-900 text-sm">Кредитные карты</span>
            </a>
            <a href="/articles" class="bg-gray-50 rounded-lg p-4 text-center card-hover block">
                <span class="text-2xl block mb-1">📰</span>
                <span class="font-semibold text-gray-900 text-sm">Статьи</span>
            </a>
        </div>
    </div>


<script>
var allOffers = <?= json_encode($allOffers) ?>;

function fmt(n) { return Math.round(n).toLocaleString('ru-RU') + ' ₽'; }

function fmtDays(d) {
    if (d <= 0) return '0 дней';
    if (d == 1) return '1 день';
    if (d < 5) return d + ' дня';
    return d + ' дней';
}

function calcUpdate() {
    var amount = parseInt(document.getElementById('calc-amount').value);
    var term = parseInt(document.getElementById('calc-term').value);
    var rate = parseFloat(document.getElementById('calc-rate').value);

    var interest = (amount * rate * term) / 100;
    var total = amount + interest;
    var daily = term > 0 ? total / term : 0;
    var yearly = (rate * 365).toFixed(1);

    document.getElementById('amount-val').textContent = fmt(amount);
    document.getElementById('term-val').textContent = fmtDays(term);
    document.getElementById('rate-val').textContent = rate + '%';
    document.getElementById('res-total').textContent = fmt(total);
    document.getElementById('res-overpay').textContent = fmt(interest);
    document.getElementById('res-daily').textContent = fmt(daily);
    document.getElementById('res-yearly').textContent = yearly + '%';

    filterOffers(amount, term);
}

function filterOffers(amount, term) {
    var matched = allOffers.filter(function(o) {
        return o.amount_min <= amount && o.amount_max >= amount && o.term_min_days <= term && o.term_max_days >= term;
    });

    document.getElementById('offers-count').textContent = matched.length > 0 ? '(' + matched.length + ')' : '';

    if (matched.length === 0) {
        document.getElementById('offers-list').innerHTML = '<div class="text-center py-12 bg-white rounded-xl border border-gray-100"><p class="text-3xl mb-3">🔍</p><p class="text-gray-500">Предложения не найдены</p><p class="text-gray-400 text-sm mt-1">Попробуйте изменить параметры</p></div>';
        return;
    }

    var html = '';
    matched.forEach(function(o) {
        var logo = o.logo_url || '';
        if (logo.indexOf('/public/') === 0) logo = logo.substring(7);
        var logoHtml = logo ? '<img src="' + logo + '" alt="' + o.title + '" class="w-full h-full object-contain p-0.5" loading="lazy" decoding="async">' : '<span class="text-xl">🏦</span>';
        var cats = {microloans:'Займы',credits:'Кредиты',credit_cards:'Кредитные карты',debit_cards:'Дебетовые карты'};
        var freeHtml = o.free_term_days > 0 ? '<div><span class="text-green-600 font-semibold">Без % ' + fmtDays(o.free_term_days) + '</span></div>' : '';

        html += '<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 card-hover">' +
            '<div class="flex items-center gap-3 mb-3">' +
            '<div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0 overflow-hidden">' + logoHtml + '</div>' +
            '<div class="flex-1 min-w-0"><h3 class="font-bold text-gray-900 text-sm">' + o.title + '</h3><p class="text-xs text-gray-500">' + (cats[o.category] || o.category) + '</p></div>' +
            '</div>' +
            '<div class="grid grid-cols-2 gap-2 text-sm mb-3">' +
            '<div><span class="text-gray-500">Ставка:</span> <span class="font-semibold">от ' + o.rate + '% ' + ((o.rate_unit==='year')?'в год':'в день') + '</span></div>' +
            '<div><span class="text-gray-500">ПСК:</span> <span class="font-semibold">' + o.psk + '%</span></div>' +
            '<div><span class="text-gray-500">Сумма:</span> <span class="font-semibold">до ' + fmt(o.amount_max) + '</span></div>' +
            freeHtml +
            '</div>' +
            '<a href="/click/' + o.id + '" target="_blank" rel="noopener noreferrer nofollow sponsored" class="block w-full text-center bg-accent text-white py-2 rounded-lg font-semibold hover:bg-accent-dark transition-colors text-sm">Оформить →</a>' +
            '</div>';
    });

    document.getElementById('offers-list').innerHTML = html;
}

calcUpdate();
</script>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>'Калькулятор','url'=>'/calculator']]),
];
$canonicalUrl = SITE_URL . '/calculator';
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';
