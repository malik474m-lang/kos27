<?php
/**
 * Патч для настроек виджета социального доказательства в админке
 */
?>
<script>
// Добавляем секцию в настройки
(function() {
    var origLSet = window.lSet;
    window.lSet = function() {
        if (origLSet) origLSet();
        
        // Ждём загрузки основных настроек и добавляем секцию
        setTimeout(function() {
            var settingsDiv = document.getElementById('p-settings');
            if (!settingsDiv) return;
            
            // Проверяем не добавлена ли уже секция
            if (document.getElementById('sp-settings-section')) return;
            
            // Загружаем настройки виджета
            ap('/social-proof').then(function(sp) {
                var section = document.createElement('div');
                section.id = 'sp-settings-section';
                section.className = 'bg-white rounded-xl border p-6 mt-6';
                section.innerHTML = '<h3 class="text-lg font-bold mb-4">🔔 Виджет социального доказательства</h3>' +
                    '<p class="text-sm text-gray-500 mb-4">Показывает уведомления вида «Иван из Москвы получил 15 000 ₽»</p>' +
                    '<div class="space-y-4">' +
                    '<label class="flex items-center gap-3 cursor-pointer">' +
                    '<input type="checkbox" id="sp-enabled" class="w-5 h-5 rounded" ' + (sp.enabled ? 'checked' : '') + '>' +
                    '<span class="font-medium">Включить виджет</span>' +
                    '</label>' +
                    '<div class="grid grid-cols-2 md:grid-cols-4 gap-4">' +
                    '<div><label class="block text-xs font-medium mb-1">Интервал (мс)</label>' +
                    '<input type="number" id="sp-interval" class="input-f" value="' + (sp.interval || 8000) + '" min="3000" max="30000" step="1000">' +
                    '<p class="text-xs text-gray-400 mt-1">Между показами</p></div>' +
                    '<div><label class="block text-xs font-medium mb-1">Длительность (мс)</label>' +
                    '<input type="number" id="sp-duration" class="input-f" value="' + (sp.duration || 5000) + '" min="2000" max="10000" step="500">' +
                    '<p class="text-xs text-gray-400 mt-1">Показ уведомления</p></div>' +
                    '<div><label class="block text-xs font-medium mb-1">Мин. сумма ₽</label>' +
                    '<input type="number" id="sp-min" class="input-f" value="' + (sp.min_amount || 5000) + '" min="1000" step="1000"></div>' +
                    '<div><label class="block text-xs font-medium mb-1">Макс. сумма ₽</label>' +
                    '<input type="number" id="sp-max" class="input-f" value="' + (sp.max_amount || 30000) + '" min="5000" step="1000"></div>' +
                    '</div>' +
                    '<div><label class="block text-xs font-medium mb-1">Позиция</label>' +
                    '<select id="sp-position" class="sel-f w-auto">' +
                    '<option value="bottom-left"' + (sp.position === 'bottom-left' ? ' selected' : '') + '>Слева внизу</option>' +
                    '<option value="bottom-right"' + (sp.position === 'bottom-right' ? ' selected' : '') + '>Справа внизу</option>' +
                    '</select></div>' +
                    '<button onclick="saveSocialProof()" class="btn-p">💾 Сохранить настройки виджета</button>' +
                    '</div>';
                
                settingsDiv.appendChild(section);
            });
        }, 500);
    };
})();

function saveSocialProof() {
    var data = {
        enabled: document.getElementById('sp-enabled').checked,
        interval: parseInt(document.getElementById('sp-interval').value) || 8000,
        duration: parseInt(document.getElementById('sp-duration').value) || 5000,
        min_amount: parseInt(document.getElementById('sp-min').value) || 5000,
        max_amount: parseInt(document.getElementById('sp-max').value) || 30000,
        position: document.getElementById('sp-position').value
    };
    
    ap('/social-proof', {
        method: 'POST',
        body: JSON.stringify(data)
    }).then(function(res) {
        if (res.success) {
            alert('✅ Настройки виджета сохранены!');
        } else {
            alert('Ошибка: ' + (res.error || 'Неизвестная ошибка'));
        }
    }).catch(function(err) {
        alert('Ошибка: ' + (err.message || err));
    });
}
</script>
