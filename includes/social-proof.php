<?php
/**
 * Виджет социального доказательства
 * "Иван из Москвы получил 15 000 ₽ 2 минуты назад"
 */

function getSocialProofSettings(): array {
    $settings = getSiteSettings();
    return [
        'enabled' => (bool)($settings['social_proof_enabled'] ?? true),
        'interval' => (int)($settings['social_proof_interval'] ?? 8000), // мс между показами
        'duration' => (int)($settings['social_proof_duration'] ?? 5000), // мс показа
        'max_amount' => (int)($settings['social_proof_max_amount'] ?? 30000),
        'min_amount' => (int)($settings['social_proof_min_amount'] ?? 5000),
        'position' => $settings['social_proof_position'] ?? 'bottom-left', // bottom-left, bottom-right
    ];
}

function renderSocialProofWidget(): string {
    $config = getSocialProofSettings();
    
    if (!$config['enabled']) {
        return '';
    }
    
    // Имена для виджета
    $names = ['Александр', 'Дмитрий', 'Максим', 'Сергей', 'Андрей', 'Алексей', 'Артём', 'Илья', 'Кирилл', 'Михаил', 'Никита', 'Матвей', 'Роман', 'Егор', 'Арсений', 'Иван', 'Денис', 'Евгений', 'Анна', 'Мария', 'Елена', 'Дарья', 'Алина', 'Ирина', 'Екатерина', 'Ольга', 'Татьяна', 'Наталья', 'Юлия', 'Виктория', 'Полина', 'Анастасия', 'Ксения', 'Светлана', 'Валерия'];
    
    // Города
    $cities = ['Москвы', 'Санкт-Петербурга', 'Новосибирска', 'Екатеринбурга', 'Казани', 'Нижнего Новгорода', 'Челябинска', 'Самары', 'Омска', 'Ростова-на-Дону', 'Уфы', 'Красноярска', 'Воронежа', 'Перми', 'Волгограда', 'Краснодара', 'Саратова', 'Тюмени', 'Тольятти', 'Ижевска', 'Барнаула', 'Ульяновска', 'Иркутска', 'Хабаровска', 'Ярославля', 'Владивостока', 'Махачкалы', 'Томска', 'Оренбурга', 'Кемерово'];
    
    // Действия
    $actions = [
        'получил займ',
        'оформил займ', 
        'получил деньги',
        'оформил заявку на',
    ];
    
    $configJson = json_encode([
        'interval' => $config['interval'],
        'duration' => $config['duration'],
        'minAmount' => $config['min_amount'],
        'maxAmount' => $config['max_amount'],
        'names' => $names,
        'cities' => $cities,
        'actions' => $actions,
    ], JSON_UNESCAPED_UNICODE);
    
    $positionClass = $config['position'] === 'bottom-right' ? 'right-4' : 'left-4';
    
    ob_start();
    ?>
    <!-- Social Proof Widget -->
    <div id="social-proof-widget" 
         class="fixed bottom-4 <?= $positionClass ?> z-50 transform translate-y-full opacity-0 transition-all duration-500 pointer-events-none"
         style="max-width: 320px;">
        <div class="bg-white rounded-xl shadow-2xl border border-gray-100 p-4 flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center text-white text-xl flex-shrink-0">
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
        var isVisible = false;
        var isPaused = false;
        var hideTimeout = null;
        var showTimeout = null;
        
        function randomItem(arr) {
            return arr[Math.floor(Math.random() * arr.length)];
        }
        
        function randomAmount() {
            var step = 1000;
            var min = Math.ceil(config.minAmount / step);
            var max = Math.floor(config.maxAmount / step);
            return (Math.floor(Math.random() * (max - min + 1)) + min) * step;
        }
        
        function randomMinutes() {
            var mins = Math.floor(Math.random() * 15) + 1;
            if (mins === 1) return '1 минуту назад';
            if (mins < 5) return mins + ' минуты назад';
            return mins + ' минут назад';
        }
        
        function formatMoney(n) {
            return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽';
        }
        
        function showNotification() {
            if (isPaused) return;
            
            var name = randomItem(config.names);
            var city = randomItem(config.cities);
            var action = randomItem(config.actions);
            var amount = randomAmount();
            
            textEl.textContent = name + ' из ' + city + ' ' + action + ' ' + formatMoney(amount);
            timeEl.textContent = randomMinutes();
            
            widget.classList.remove('translate-y-full', 'opacity-0');
            widget.classList.add('translate-y-0', 'opacity-100');
            isVisible = true;
            
            hideTimeout = setTimeout(function() {
                hideNotification();
            }, config.duration);
        }
        
        function hideNotification() {
            widget.classList.remove('translate-y-0', 'opacity-100');
            widget.classList.add('translate-y-full', 'opacity-0');
            isVisible = false;
            
            showTimeout = setTimeout(function() {
                showNotification();
            }, config.interval);
        }
        
        window.closeSocialProof = function() {
            isPaused = true;
            if (hideTimeout) clearTimeout(hideTimeout);
            if (showTimeout) clearTimeout(showTimeout);
            widget.classList.remove('translate-y-0', 'opacity-100');
            widget.classList.add('translate-y-full', 'opacity-0');
            // Запоминаем что пользователь закрыл
            try { sessionStorage.setItem('sp_closed', '1'); } catch(e) {}
        };
        
        // Не показываем если уже закрывали
        try {
            if (sessionStorage.getItem('sp_closed') === '1') {
                isPaused = true;
                return;
            }
        } catch(e) {}
        
        // Первый показ через 3 секунды
        setTimeout(function() {
            showNotification();
        }, 3000);
    })();
    </script>
    <?php
    return ob_get_clean();
}
