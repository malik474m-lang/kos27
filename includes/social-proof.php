<?php
/**
 * Виджет социального доказательства
 */

function getSocialProofSettings(): array {
    $settings = getSiteSettings();
    return [
        'enabled' => (bool)($settings['social_proof_enabled'] ?? true),
        'interval' => (int)($settings['social_proof_interval'] ?? 8000),
        'duration' => (int)($settings['social_proof_duration'] ?? 5000),
        'max_amount' => (int)($settings['social_proof_max_amount'] ?? 30000),
        'min_amount' => (int)($settings['social_proof_min_amount'] ?? 5000),
        'position' => $settings['social_proof_position'] ?? 'bottom-left',
    ];
}

function renderSocialProofWidget(): string {
    $config = getSocialProofSettings();

    if (!$config['enabled']) {
        return '';
    }

    $maleNames = ['Александр','Дмитрий','Максим','Сергей','Андрей','Алексей','Артём','Илья','Кирилл','Михаил','Никита','Матвей','Роман','Егор','Арсений','Иван','Денис','Евгений'];
    $femaleNames = ['Анна','Мария','Елена','Дарья','Алина','Ирина','Екатерина','Ольга','Татьяна','Наталья','Юлия','Виктория','Полина','Анастасия','Ксения','Светлана','Валерия'];
    $cities = ['Москвы','Санкт-Петербурга','Новосибирска','Екатеринбурга','Казани','Нижнего Новгорода','Челябинска','Самары','Омска','Ростова-на-Дону','Уфы','Красноярска','Воронежа','Перми','Волгограда','Краснодара','Саратова','Тюмени','Тольятти','Ижевска','Барнаула','Ульяновска','Иркутска','Хабаровска','Ярославля','Владивостока','Махачкалы','Томска','Оренбурга','Кемерово'];

    // Сценарии по категориям продуктов (м/ж)
    $scenarios = [
        // Займы
        ['m' => 'получил займ', 'f' => 'получила займ', 'icon' => '💵', 'amount' => true],
        ['m' => 'оформил займ на', 'f' => 'оформила займ на', 'icon' => '💵', 'amount' => true],
        ['m' => 'получил деньги —', 'f' => 'получила деньги —', 'icon' => '💰', 'amount' => true],
        // Кредиты
        ['m' => 'одобрен кредит на', 'f' => 'одобрен кредит на', 'icon' => '🏦', 'amount' => true, 'min' => 50000, 'max' => 500000],
        ['m' => 'оформил кредит на', 'f' => 'оформила кредит на', 'icon' => '🏦', 'amount' => true, 'min' => 50000, 'max' => 500000],
        ['m' => 'получил кредит на', 'f' => 'получила кредит на', 'icon' => '🏦', 'amount' => true, 'min' => 30000, 'max' => 300000],
        // Кредитные карты
        ['m' => 'оформил кредитную карту', 'f' => 'оформила кредитную карту', 'icon' => '💳', 'amount' => false],
        ['m' => 'получил кредитную карту', 'f' => 'получила кредитную карту', 'icon' => '💳', 'amount' => false],
        ['m' => 'одобрена кредитная карта', 'f' => 'одобрена кредитная карта', 'icon' => '💳', 'amount' => false],
        // Дебетовые карты
        ['m' => 'оформил дебетовую карту', 'f' => 'оформила дебетовую карту', 'icon' => '🪪', 'amount' => false],
        ['m' => 'заказал дебетовую карту', 'f' => 'заказала дебетовую карту', 'icon' => '🪪', 'amount' => false],
    ];

    $configJson = json_encode([
        'interval' => $config['interval'],
        'duration' => $config['duration'],
        'minAmount' => $config['min_amount'],
        'maxAmount' => $config['max_amount'],
        'maleNames' => $maleNames,
        'femaleNames' => $femaleNames,
        'cities' => $cities,
        'scenarios' => $scenarios,
    ], JSON_UNESCAPED_UNICODE);

    $positionClass = $config['position'] === 'bottom-right' ? 'right-4' : 'left-4';

    ob_start();
    ?>
    <!-- Social Proof Widget -->
    <div id="social-proof-widget"
         class="fixed bottom-4 <?= $positionClass ?> z-50 transform translate-y-full opacity-0 transition-all duration-500 pointer-events-none"
         style="max-width: 340px;">
        <div class="bg-white rounded-xl shadow-2xl border border-gray-100 p-4 flex items-center gap-3">
            <div id="sp-icon" class="w-12 h-12 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center text-xl flex-shrink-0">
                ✓
            </div>
            <div class="min-w-0">
                <p id="sp-text" class="text-sm text-gray-800 font-medium leading-snug"></p>
                <p id="sp-time" class="text-xs text-gray-400 mt-1"></p>
            </div>
            <button onclick="closeSocialProof()" class="absolute top-2 right-2 text-gray-300 hover:text-gray-500 text-lg leading-none pointer-events-auto">&times;</button>
        </div>
    </div>

    <script>
    (function() {
        var config = <?= $configJson ?>;
        var widget = document.getElementById('social-proof-widget');
        var textEl = document.getElementById('sp-text');
        var timeEl = document.getElementById('sp-time');
        var iconEl = document.getElementById('sp-icon');
        var isPaused = false;
        var hideTimeout = null;
        var showTimeout = null;

        function randomItem(arr) { return arr[Math.floor(Math.random() * arr.length)]; }

        function randomAmount(min, max) {
            var step = 1000;
            var lo = Math.ceil((min || config.minAmount) / step);
            var hi = Math.floor((max || config.maxAmount) / step);
            return (Math.floor(Math.random() * (hi - lo + 1)) + lo) * step;
        }

        function randomMinutes() {
            var m = Math.floor(Math.random() * 15) + 1;
            if (m === 1) return '1 минуту назад';
            if (m < 5) return m + ' минуты назад';
            return m + ' минут назад';
        }

        function formatMoney(n) {
            return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽';
        }

        function showNotification() {
            if (isPaused) return;

            var isMale = Math.random() > 0.45;
            var name = isMale ? randomItem(config.maleNames) : randomItem(config.femaleNames);
            var city = randomItem(config.cities);
            var sc = randomItem(config.scenarios);
            var action = isMale ? sc.m : sc.f;

            var msg = name + ' из ' + city + ' ' + action;
            if (sc.amount) {
                msg += ' ' + formatMoney(randomAmount(sc.min, sc.max));
            }

            textEl.textContent = msg;
            timeEl.textContent = randomMinutes();
            iconEl.textContent = sc.icon || '✓';

            widget.classList.remove('translate-y-full', 'opacity-0');
            widget.classList.add('translate-y-0', 'opacity-100');

            hideTimeout = setTimeout(hideNotification, config.duration);
        }

        function hideNotification() {
            widget.classList.remove('translate-y-0', 'opacity-100');
            widget.classList.add('translate-y-full', 'opacity-0');
            showTimeout = setTimeout(showNotification, config.interval);
        }

        window.closeSocialProof = function() {
            isPaused = true;
            if (hideTimeout) clearTimeout(hideTimeout);
            if (showTimeout) clearTimeout(showTimeout);
            widget.classList.remove('translate-y-0', 'opacity-100');
            widget.classList.add('translate-y-full', 'opacity-0');
            try { sessionStorage.setItem('sp_closed', '1'); } catch(e) {}
        };

        try { if (sessionStorage.getItem('sp_closed') === '1') return; } catch(e) {}

        setTimeout(showNotification, 3000);
    })();
    </script>
    <?php
    return ob_get_clean();
}
