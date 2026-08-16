<?php
/**
 * Патч для добавления справочной информации в форму оффера
 */
?>
<script>
// Расширяем форму оффера полями контактов
(function() {
    var origOForm = window.oForm;
    
    window.oForm = function(o) {
        var f = o || {};
        var id = o ? o.id : 0;
        
        // Вызываем оригинальную форму
        if (origOForm) origOForm(o);
        
        // Ждём пока форма отрисуется и добавляем поля
        setTimeout(function() {
            var formEl = document.querySelector('#M .space-y-3');
            if (!formEl) return;
            
            // Проверяем не добавлены ли уже
            if (document.getElementById('of-phone')) return;
            
            // Находим место перед кнопками
            var buttonsDiv = formEl.querySelector('.flex.justify-end');
            if (!buttonsDiv) return;
            
            var contactsHtml = '<div class="border-t pt-4 mt-4">' +
                '<div class="flex items-center justify-between mb-3">' +
                '<h4 class="font-bold text-sm">📋 Справочная информация</h4>' +
                '<button type="button" onclick="generateOfferContacts()" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold">🤖 Заполнить ИИ</button>' +
                '</div>' +
                '<div class="grid grid-cols-2 gap-3">' +
                '<div><label class="block text-xs font-medium mb-1">📞 Телефон</label>' +
                '<input id="of-phone" class="input-f" value="' + e(f.phone || '') + '" placeholder="8-800-XXX-XX-XX"></div>' +
                '<div><label class="block text-xs font-medium mb-1">📜 Лицензия ЦБ РФ</label>' +
                '<input id="of-license" class="input-f" value="' + e(f.license || '') + '" placeholder="№XXXXXXX"></div>' +
                '</div>' +
                '<div class="mt-3"><label class="block text-xs font-medium mb-1">🏢 Торговая марка (юр. лицо)</label>' +
                '<input id="of-trademark" class="input-f" value="' + e(f.trademark || '') + '" placeholder="ООО «Название»"></div>' +
                '<div class="mt-3"><label class="block text-xs font-medium mb-1">📍 Юридический адрес</label>' +
                '<input id="of-address" class="input-f" value="' + e(f.address || '') + '" placeholder="г. Москва, ул. ..."></div>' +
                '</div>';
            
            var div = document.createElement('div');
            div.innerHTML = contactsHtml;
            formEl.insertBefore(div.firstChild, buttonsDiv);
        }, 100);
    };
})();

// Генерация через ИИ
function generateOfferContacts() {
    var title = document.getElementById('of-t').value;
    var category = document.getElementById('of-cat').value;
    
    if (!title) {
        alert('Сначала введите название оффера');
        return;
    }
    
    var btn = event.target;
    btn.disabled = true;
    btn.textContent = '⏳ Генерация...';
    
    ap('/offer-contacts-generate', {
        method: 'POST',
        body: JSON.stringify({ title: title, category: category })
    }).then(function(res) {
        if (res.error) {
            alert('Ошибка: ' + res.error);
            return;
        }
        if (res.contacts) {
            if (res.contacts.phone) document.getElementById('of-phone').value = res.contacts.phone;
            if (res.contacts.license) document.getElementById('of-license').value = res.contacts.license;
            if (res.contacts.trademark) document.getElementById('of-trademark').value = res.contacts.trademark;
            if (res.contacts.address) document.getElementById('of-address').value = res.contacts.address;
        }
    }).catch(function(err) {
        alert('Ошибка: ' + (err.message || err));
    }).finally(function() {
        btn.disabled = false;
        btn.textContent = '🤖 Заполнить ИИ';
    });
}

// Расширяем функцию сохранения
(function() {
    var origOS = window.oS;
    
    window.oS = function(ev, id) {
        // Добавляем контактные поля в данные
        var phoneEl = document.getElementById('of-phone');
        var licenseEl = document.getElementById('of-license');
        var trademarkEl = document.getElementById('of-trademark');
        var addressEl = document.getElementById('of-address');
        
        // Сохраняем в глобальный объект для передачи
        window._offerContactsData = {
            phone: phoneEl ? phoneEl.value : null,
            license: licenseEl ? licenseEl.value : null,
            trademark: trademarkEl ? trademarkEl.value : null,
            address: addressEl ? addressEl.value : null
        };
        
        return origOS(ev, id);
    };
    
    // Патчим отправку
    var origAp = window.ap;
    window.ap = function(url, options) {
        if ((url === '/offers' || url.match(/^\/offers\/\d+$/)) && options && options.body) {
            try {
                var data = JSON.parse(options.body);
                if (window._offerContactsData) {
                    data.phone = window._offerContactsData.phone;
                    data.license = window._offerContactsData.license;
                    data.trademark = window._offerContactsData.trademark;
                    data.address = window._offerContactsData.address;
                    options.body = JSON.stringify(data);
                    window._offerContactsData = null;
                }
            } catch(e) {}
        }
        return origAp(url, options);
    };
})();
</script>
