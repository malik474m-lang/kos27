<?php
/**
 * Патч для добавления E-E-A-T полей в админку статей
 * Этот файл содержит JavaScript функции для интеграции с dashboard.php
 */
?>
<script>
// Расширенная форма статьи с E-E-A-T полями
function aFormEEAT(a) {
    let f = a || {
        title: '', excerpt: '', content: '', meta_title: '', meta_description: '', 
        cover_image: '', is_published: false, content_status: 'draft', quality_score: 0,
        author_name: 'Редакция Космозайм', author_title: 'Финансовый редактор',
        reviewer_name: 'Анна Соколова', reviewer_title: 'Главный редактор',
        fact_checked_at: '', sources: '[]'
    };
    let id = a ? a.id : 0;
    
    // Парсим источники
    let sourcesArr = [];
    try { sourcesArr = JSON.parse(f.sources || '[]'); } catch(e) {}
    if (!sourcesArr.length) {
        sourcesArr = [
            {title: 'Банк России', url: 'https://cbr.ru/'},
            {title: 'Реестр МФО', url: 'https://cbr.ru/microfinance/registry/'}
        ];
    }
    
    let sourcesHtml = sourcesArr.map((s,i) => `
        <div class="flex gap-2 items-center eeat-source-row" data-idx="${i}">
            <input type="text" class="input-f flex-1 eeat-source-title" placeholder="Название" value="${e(s.title||'')}">
            <input type="text" class="input-f flex-1 eeat-source-url" placeholder="URL" value="${e(s.url||'')}">
            <button type="button" onclick="removeEEATSource(this)" class="text-red-500 hover:text-red-700 px-2">✕</button>
        </div>
    `).join('');
    
    let factDate = f.fact_checked_at ? f.fact_checked_at.substring(0,10) : new Date().toISOString().substring(0,10);
    
    modal(`
    <div class="flex justify-between mb-4">
        <h3 class="text-lg font-bold">${id ? 'Редактировать статью' : 'Новая статья'}</h3>
        <button onclick="cm()" class="text-gray-400 text-xl">✕</button>
    </div>
    <form onsubmit="return aSaveEEAT(event,${id})" style="max-height:80vh;overflow-y:auto;">
        <div class="space-y-4">
            <!-- Основные поля -->
            <div>
                <label class="block text-xs font-medium mb-1">Заголовок *</label>
                <input id="af-t" class="input-f" value="${e(f.title)}" required>
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">Краткое описание</label>
                <textarea id="af-ex" class="input-f" rows="2">${e(f.excerpt||'')}</textarea>
            </div>
            <div>
                <label class="block text-xs font-medium mb-1">Содержание *</label>
                <div class="flex flex-wrap gap-2 mb-2">
                    <button type="button" onclick="cqAnalyzeForm('af','article')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-semibold">🧪 Качество</button>
                    <button type="button" onclick="cqImproveField('af','article','content')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold">✨ Улучшить</button>
                </div>
                <textarea id="af-co" class="input-f" rows="10" required>${e(f.content)}</textarea>
            </div>
            
            <!-- E-E-A-T секция -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                <h4 class="font-bold text-blue-900 mb-4 flex items-center gap-2">
                    <span>🛡️</span> E-E-A-T (Доверие и экспертность)
                </h4>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-medium mb-1">Автор</label>
                        <input id="af-author-name" class="input-f" value="${e(f.author_name||'Редакция Космозайм')}" placeholder="Имя автора">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Должность автора</label>
                        <input id="af-author-title" class="input-f" value="${e(f.author_title||'Финансовый редактор')}" placeholder="Должность">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-medium mb-1">Проверил (редактор)</label>
                        <input id="af-reviewer-name" class="input-f" value="${e(f.reviewer_name||'')}" placeholder="Имя редактора">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Должность редактора</label>
                        <input id="af-reviewer-title" class="input-f" value="${e(f.reviewer_title||'')}" placeholder="Главный редактор">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-xs font-medium mb-1">Дата проверки фактов</label>
                    <input type="date" id="af-fact-date" class="input-f" value="${factDate}">
                </div>
                
                <div>
                    <label class="block text-xs font-medium mb-2">Источники информации</label>
                    <div id="af-sources-list" class="space-y-2 mb-2">${sourcesHtml}</div>
                    <button type="button" onclick="addEEATSource()" class="text-blue-600 hover:text-blue-800 text-sm font-medium">+ Добавить источник</button>
                </div>
            </div>
            
            <!-- Мета поля -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium mb-1">Meta Title</label>
                    <div class="flex gap-2">
                        <input id="af-mt" class="input-f flex-1" value="${e(f.meta_title||'')}">
                        <button type="button" onclick="fillMeta('af','article')" class="bg-purple-600 text-white px-3 py-2 rounded-lg text-xs font-semibold hover:bg-purple-700 whitespace-nowrap">🤖 Meta</button>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Обложка</label>
                    <div class="flex gap-2">
                        <input id="af-ci" class="input-f flex-1" value="${e(f.cover_image||'')}">
                        <button type="button" onclick="mediaPicker('af-ci','articles')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-xs font-semibold whitespace-nowrap">📁 Выбрать</button>
                    </div>
                </div>
            </div>
            
            <div>
                <label class="block text-xs font-medium mb-1">Meta Description</label>
                <textarea id="af-md" class="input-f" rows="2">${e(f.meta_description||'')}</textarea>
            </div>
            
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2">
                    <input type="checkbox" id="af-pu" ${f.is_published ? 'checked' : ''} class="w-4 h-4">
                    <span class="text-sm">Опубликовать</span>
                </label>
                <select id="af-status" class="sel-f w-auto">
                    <option value="draft" ${(f.content_status||'draft')==='draft'?'selected':''}>Черновик</option>
                    <option value="reviewed" ${f.content_status==='reviewed'?'selected':''}>Проверено</option>
                    <option value="ready" ${f.content_status==='ready'?'selected':''}>Готово</option>
                </select>
                <span class="text-xs text-gray-400">Score: <span id="af-quality-score">${f.quality_score||0}</span></span>
            </div>
        </div>
        
        <div class="flex justify-end gap-3 mt-6 pt-4 border-t sticky bottom-0 bg-white">
            <button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button>
            <button type="submit" class="btn-p">💾 Сохранить</button>
        </div>
    </form>
    `);
}

function addEEATSource() {
    const list = document.getElementById('af-sources-list');
    const idx = list.children.length;
    const row = document.createElement('div');
    row.className = 'flex gap-2 items-center eeat-source-row';
    row.dataset.idx = idx;
    row.innerHTML = `
        <input type="text" class="input-f flex-1 eeat-source-title" placeholder="Название" value="">
        <input type="text" class="input-f flex-1 eeat-source-url" placeholder="URL" value="">
        <button type="button" onclick="removeEEATSource(this)" class="text-red-500 hover:text-red-700 px-2">✕</button>
    `;
    list.appendChild(row);
}

function removeEEATSource(btn) {
    btn.closest('.eeat-source-row').remove();
}

function collectEEATSources() {
    const rows = document.querySelectorAll('.eeat-source-row');
    const sources = [];
    rows.forEach(row => {
        const title = row.querySelector('.eeat-source-title').value.trim();
        const url = row.querySelector('.eeat-source-url').value.trim();
        if (title && url) sources.push({title, url});
    });
    return sources;
}

function aSaveEEAT(ev, id) {
    ev.preventDefault();
    const d = {
        title: document.getElementById('af-t').value,
        excerpt: document.getElementById('af-ex').value,
        content: document.getElementById('af-co').value,
        metaTitle: document.getElementById('af-mt').value,
        metaDescription: document.getElementById('af-md').value,
        coverImage: document.getElementById('af-ci').value,
        isPublished: document.getElementById('af-pu').checked,
        contentStatus: document.getElementById('af-status').value,
        // E-E-A-T поля
        authorName: document.getElementById('af-author-name').value,
        authorTitle: document.getElementById('af-author-title').value,
        reviewerName: document.getElementById('af-reviewer-name').value,
        reviewerTitle: document.getElementById('af-reviewer-title').value,
        factCheckedAt: document.getElementById('af-fact-date').value,
        sources: collectEEATSources()
    };
    
    const saveBtn = ev.target.querySelector('button[type="submit"]');
    if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = '⏳ Сохранение...'; }
    
    ap(id ? '/articles/' + id : '/articles', {
        method: id ? 'PUT' : 'POST',
        body: JSON.stringify(d)
    }).then(() => {
        cm();
        lA();
    }).catch(err => {
        alert('Ошибка: ' + (err.message || err));
    }).finally(() => {
        if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = '💾 Сохранить'; }
    });
    
    return false;
}

// Переопределяем aForm для использования E-E-A-T версии
var originalAForm = typeof aForm === 'function' ? aForm : null;
aForm = aFormEEAT;
</script>
