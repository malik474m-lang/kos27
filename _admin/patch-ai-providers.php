<?php
/**
 * Патч: добавление вкладки AI провайдеров в админку
 * Применяется один раз, обновляет dashboard.php
 */
require_once __DIR__ . '/../config.php';
requireAdmin();

$dashboardFile = __DIR__ . '/dashboard.php';
$content = file_get_contents($dashboardFile);

// Проверяем, не применён ли уже патч
if (str_contains($content, "sw('aiproviders')")) {
    echo json_encode(['status' => 'already_applied', 'message' => 'Патч уже применён']);
    exit;
}

// 1. Добавляем кнопку вкладки в навигацию (после emailfunnel)
$navButton = '<button onclick="sw(\'aiproviders\')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="aiproviders">🤖 AI провайдеры</button>';
$content = str_replace(
    '<button onclick="sw(\'emailfunnel\')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="emailfunnel">📧 Email-воронка</button>',
    '<button onclick="sw(\'emailfunnel\')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="emailfunnel">📧 Email-воронка</button>' . "\n" . $navButton,
    $content
);

// 2. Добавляем div для контента вкладки (после emailfunnel)
$contentDiv = '<div id="p-aiproviders" class="tp hidden"></div>';
$content = str_replace(
    '<div id="p-emailfunnel" class="tp hidden"></div>',
    '<div id="p-emailfunnel" class="tp hidden"></div>' . "\n" . $contentDiv,
    $content
);

// 3. Добавляем JS для загрузки и управления AI провайдерами
// Ищем конец скрипта и добавляем перед </script>
$aiProvidersJS = <<<'JSEOF'

// === AI Providers ===
async function loadAIProviders() {
    const c = document.getElementById('p-aiproviders');
    c.innerHTML = '<p class="text-gray-500">Загрузка...</p>';
    
    try {
        const r = await fetch(A + '/ai-providers');
        const d = await r.json();
        
        const textProviders = d.status?.text || {};
        const imageProviders = d.status?.image || {};
        const cfg = d.config || {};
        const textPriority = d.status?.priority?.text || [];
        const imagePriority = d.status?.priority?.image || [];
        
        let h = `
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-800">🤖 Управление AI провайдерами</h2>
                <div class="flex gap-2">
                    <span class="text-sm text-gray-500">Активный текст: <strong class="text-green-600">${d.status?.active?.text || 'нет'}</strong></span>
                    <span class="text-sm text-gray-500">Активное изображение: <strong class="text-green-600">${d.status?.active?.image || 'нет'}</strong></span>
                </div>
            </div>
            
            <!-- OdiRouter -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 ${cfg.odirouter_enabled ? 'border-green-500' : 'border-gray-300'}">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">🌐</span>
                        <div>
                            <h3 class="font-bold text-lg">OdiRouter</h3>
                            <p class="text-sm text-gray-500">Универсальный роутер 200+ AI моделей (текст + изображения)</p>
                        </div>
                    </div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" id="odirouter_enabled" ${cfg.odirouter_enabled ? 'checked' : ''} onchange="saveAIProvider('odirouter_enabled', this.checked)" class="w-5 h-5">
                        <span class="font-medium">${cfg.odirouter_enabled ? 'Включён' : 'Выключен'}</span>
                    </label>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">API ключ</label>
                        <input type="password" id="odirouter_api_key" value="${cfg.odirouter_api_key_masked || ''}" placeholder="Введите API ключ OdiRouter" class="input-f">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Модель для текста</label>
                        <select id="odirouter_text_model" class="sel-f">
                            ${(d.availableTextModels?.odirouter || []).map(m => `<option value="${m}" ${cfg.odirouter_text_model === m ? 'selected' : ''}>${m}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Модель для изображений</label>
                        <select id="odirouter_image_model" class="sel-f">
                            ${(d.availableImageModels?.odirouter || []).map(m => `<option value="${m}" ${cfg.odirouter_image_model === m ? 'selected' : ''}>${m}</option>`).join('')}
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button onclick="testAIProvider('odirouter', 'text')" class="btn-p text-sm">🧪 Тест текста</button>
                        <button onclick="testAIProvider('odirouter', 'image')" class="btn-p text-sm bg-purple-600 hover:bg-purple-700">🖼️ Тест изображения</button>
                    </div>
                </div>
            </div>
            
            <!-- YandexGPT / YandexART -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 ${cfg.yandex_gpt_enabled ? 'border-yellow-500' : 'border-gray-300'}">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">🟡</span>
                        <div>
                            <h3 class="font-bold text-lg">Yandex Cloud (GPT + ART)</h3>
                            <p class="text-sm text-gray-500">YandexGPT для текста, YandexART для изображений</p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" id="yandex_gpt_enabled" ${cfg.yandex_gpt_enabled ? 'checked' : ''} onchange="saveAIProvider('yandex_gpt_enabled', this.checked)" class="w-5 h-5">
                            <span class="text-sm">GPT</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" id="yandex_art_enabled" ${cfg.yandex_art_enabled ? 'checked' : ''} onchange="saveAIProvider('yandex_art_enabled', this.checked)" class="w-5 h-5">
                            <span class="text-sm">ART</span>
                        </label>
                    </div>
                </div>
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">API ключ</label>
                        <input type="password" id="yandex_gpt_api_key" value="${cfg.yandex_gpt_api_key_masked || ''}" placeholder="Api-Key" class="input-f">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Folder ID</label>
                        <input type="text" id="yandex_folder_id" value="${cfg.yandex_folder_id || ''}" placeholder="b1g..." class="input-f">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Модель GPT</label>
                        <select id="yandex_gpt_model" class="sel-f">
                            ${(d.availableTextModels?.yandex_gpt || []).map(m => `<option value="${m}" ${cfg.yandex_gpt_model === m ? 'selected' : ''}>${m}</option>`).join('')}
                        </select>
                    </div>
                </div>
                <div class="flex gap-2 mt-4">
                    <button onclick="testAIProvider('yandex_gpt', 'text')" class="btn-p text-sm">🧪 Тест GPT</button>
                    <button onclick="testAIProvider('yandex_art', 'image')" class="btn-p text-sm bg-orange-500 hover:bg-orange-600">🖼️ Тест ART</button>
                </div>
            </div>
            
            <!-- GigaChat -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 ${cfg.gigachat_enabled ? 'border-blue-500' : 'border-gray-300'}">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">🔵</span>
                        <div>
                            <h3 class="font-bold text-lg">GigaChat (Сбер)</h3>
                            <p class="text-sm text-gray-500">GigaChat для текста, Kandinsky для изображений</p>
                        </div>
                    </div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" id="gigachat_enabled" ${cfg.gigachat_enabled ? 'checked' : ''} onchange="saveAIProvider('gigachat_enabled', this.checked)" class="w-5 h-5">
                        <span class="font-medium">${cfg.gigachat_enabled ? 'Включён' : 'Выключен'}</span>
                    </label>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Authorization Key (Base64)</label>
                        <input type="password" id="gigachat_auth_key" value="${cfg.gigachat_auth_key_masked || ''}" placeholder="Basic ключ" class="input-f">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Scope</label>
                        <select id="gigachat_scope" class="sel-f">
                            <option value="GIGACHAT_API_PERS" ${cfg.gigachat_scope === 'GIGACHAT_API_PERS' ? 'selected' : ''}>GIGACHAT_API_PERS (Личный)</option>
                            <option value="GIGACHAT_API_CORP" ${cfg.gigachat_scope === 'GIGACHAT_API_CORP' ? 'selected' : ''}>GIGACHAT_API_CORP (Корпоративный)</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-2 mt-4">
                    <button onclick="testAIProvider('gigachat', 'text')" class="btn-p text-sm">🧪 Тест текста</button>
                    <button onclick="testAIProvider('gigachat', 'image')" class="btn-p text-sm bg-cyan-600 hover:bg-cyan-700">🖼️ Тест Kandinsky</button>
                </div>
            </div>
            
            <!-- Stability AI -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 ${cfg.stability_enabled ? 'border-pink-500' : 'border-gray-300'}">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">🎨</span>
                        <div>
                            <h3 class="font-bold text-lg">Stability AI</h3>
                            <p class="text-sm text-gray-500">Stable Diffusion (только изображения)</p>
                        </div>
                    </div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" id="stability_enabled" ${cfg.stability_enabled ? 'checked' : ''} onchange="saveAIProvider('stability_enabled', this.checked)" class="w-5 h-5">
                        <span class="font-medium">${cfg.stability_enabled ? 'Включён' : 'Выключен'}</span>
                    </label>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">API ключ</label>
                        <input type="password" id="stability_api_key" value="${cfg.stability_api_key_masked || ''}" placeholder="sk-..." class="input-f">
                    </div>
                    <div class="flex items-end">
                        <button onclick="testAIProvider('stability', 'image')" class="btn-p text-sm bg-pink-600 hover:bg-pink-700">🖼️ Тест изображения</button>
                    </div>
                </div>
            </div>
            
            <!-- Приоритеты -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-bold text-lg mb-4">📊 Приоритет провайдеров</h3>
                <p class="text-sm text-gray-500 mb-4">Перетащите провайдеров для изменения приоритета. Первый доступный провайдер будет использоваться.</p>
                
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-medium mb-2">Текст</h4>
                        <div id="text-priority-list" class="space-y-2">
                            ${textPriority.map(p => `
                                <div class="flex items-center gap-2 bg-gray-100 p-2 rounded cursor-move" data-provider="${p}">
                                    <span>☰</span>
                                    <span class="flex-1">${textProviders[p]?.name || p}</span>
                                    <span class="${textProviders[p]?.available ? 'text-green-500' : 'text-gray-400'}">${textProviders[p]?.available ? '✅' : '⚪'}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    <div>
                        <h4 class="font-medium mb-2">Изображения</h4>
                        <div id="image-priority-list" class="space-y-2">
                            ${imagePriority.map(p => `
                                <div class="flex items-center gap-2 bg-gray-100 p-2 rounded cursor-move" data-provider="${p}">
                                    <span>☰</span>
                                    <span class="flex-1">${imageProviders[p]?.name || p}</span>
                                    <span class="${imageProviders[p]?.available ? 'text-green-500' : 'text-gray-400'}">${imageProviders[p]?.available ? '✅' : '⚪'}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Кнопка сохранения -->
            <div class="flex justify-end gap-4">
                <button onclick="saveAllAIProviders()" class="btn-p text-lg px-8">💾 Сохранить все настройки</button>
            </div>
        </div>
        `;
        
        c.innerHTML = h;
        
        // Инициализируем drag-n-drop для приоритетов
        if (typeof Sortable !== 'undefined') {
            new Sortable(document.getElementById('text-priority-list'), { animation: 150, onEnd: savePriority });
            new Sortable(document.getElementById('image-priority-list'), { animation: 150, onEnd: savePriority });
        }
        
    } catch (e) {
        c.innerHTML = '<p class="text-red-500">Ошибка загрузки: ' + e.message + '</p>';
    }
}

async function saveAIProvider(field, value) {
    try {
        const data = {};
        data[field] = value;
        await fetch(A + '/ai-providers', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        toast('Сохранено');
        loadAIProviders();
    } catch (e) {
        toast('Ошибка: ' + e.message, true);
    }
}

async function saveAllAIProviders() {
    const data = {
        odirouter_enabled: document.getElementById('odirouter_enabled')?.checked,
        odirouter_api_key: document.getElementById('odirouter_api_key')?.value,
        odirouter_text_model: document.getElementById('odirouter_text_model')?.value,
        odirouter_image_model: document.getElementById('odirouter_image_model')?.value,
        yandex_gpt_enabled: document.getElementById('yandex_gpt_enabled')?.checked,
        yandex_gpt_api_key: document.getElementById('yandex_gpt_api_key')?.value,
        yandex_folder_id: document.getElementById('yandex_folder_id')?.value,
        yandex_gpt_model: document.getElementById('yandex_gpt_model')?.value,
        yandex_art_enabled: document.getElementById('yandex_art_enabled')?.checked,
        gigachat_enabled: document.getElementById('gigachat_enabled')?.checked,
        gigachat_auth_key: document.getElementById('gigachat_auth_key')?.value,
        gigachat_scope: document.getElementById('gigachat_scope')?.value,
        stability_enabled: document.getElementById('stability_enabled')?.checked,
        stability_api_key: document.getElementById('stability_api_key')?.value,
    };
    
    // Добавляем приоритеты
    const textPriority = [...document.querySelectorAll('#text-priority-list [data-provider]')].map(el => el.dataset.provider);
    const imagePriority = [...document.querySelectorAll('#image-priority-list [data-provider]')].map(el => el.dataset.provider);
    data.text_provider_priority = textPriority;
    data.image_provider_priority = imagePriority;
    
    try {
        const r = await fetch(A + '/ai-providers', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const res = await r.json();
        if (res.success) {
            toast('Настройки AI провайдеров сохранены');
            loadAIProviders();
        } else {
            toast('Ошибка: ' + (res.error || 'неизвестная'), true);
        }
    } catch (e) {
        toast('Ошибка: ' + e.message, true);
    }
}

async function savePriority() {
    const textPriority = [...document.querySelectorAll('#text-priority-list [data-provider]')].map(el => el.dataset.provider);
    const imagePriority = [...document.querySelectorAll('#image-priority-list [data-provider]')].map(el => el.dataset.provider);
    
    try {
        await fetch(A + '/ai-providers', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                text_provider_priority: textPriority,
                image_provider_priority: imagePriority
            })
        });
        toast('Приоритет сохранён');
    } catch (e) {
        toast('Ошибка сохранения приоритета', true);
    }
}

async function testAIProvider(provider, type) {
    toast('Тестирование ' + provider + '...');
    try {
        const r = await fetch(A + '/ai-providers', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'test', provider, type })
        });
        const res = await r.json();
        if (res.success && res.result?.success) {
            if (type === 'image' && res.result.path) {
                toast('✅ Изображение сгенерировано: ' + res.result.path);
                window.open(res.result.path, '_blank');
            } else if (type === 'text' && res.result.text) {
                toast('✅ Текст: ' + res.result.text.substring(0, 100) + '...');
            } else {
                toast('✅ Провайдер работает');
            }
        } else {
            toast('❌ Ошибка: ' + (res.result?.error || 'неизвестная'), true);
        }
    } catch (e) {
        toast('Ошибка теста: ' + e.message, true);
    }
}

// Добавляем в switch tabs
const origSw = sw;
sw = function(tab) {
    origSw(tab);
    if (tab === 'aiproviders') loadAIProviders();
};
JSEOF;

// Находим </script> и вставляем перед ним
$content = str_replace('</script>', $aiProvidersJS . "\n</script>", $content);

// Сохраняем
if (file_put_contents($dashboardFile, $content)) {
    echo json_encode(['status' => 'success', 'message' => 'Патч AI провайдеров успешно применён']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Не удалось сохранить файл']);
}
