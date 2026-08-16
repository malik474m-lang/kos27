<?php
/**
 * Статистика A/B inline CTA в статьях.
 * Встраивается в раздел «Статьи».
 */
?>
<script>
(function(){
    var origLA = window.lA;
    window.lA = function(){
        if (origLA) origLA();
        setTimeout(function(){
            var host = document.getElementById('p-articles');
            if (!host || document.getElementById('article-inline-cta-stats-box')) return;
            ap('/article-inline-cta-stats?days=30').then(function(data){
                var box = document.createElement('div');
                box.id = 'article-inline-cta-stats-box';
                box.className = 'mb-6 bg-white rounded-xl shadow-sm border p-5';

                var summary = data.summary || [];
                var articles = data.articles || [];
                var summaryHtml = '';
                if (summary.length) {
                    summary.forEach(function(row){
                        summaryHtml += '<div class="rounded-xl border p-4">'
                            + '<div class="text-xs uppercase tracking-wide text-gray-400 mb-1">Вариант ' + String(row.variant || '').toUpperCase() + '</div>'
                            + '<div class="text-2xl font-bold text-gray-900">' + (row.clicks||0) + ' кликов</div>'
                            + '<div class="text-sm text-gray-500 mt-1">' + (row.impressions||0) + ' показов • CTR ' + (row.ctr||0) + '%</div>'
                            + '<div class="text-sm text-emerald-600 mt-1">Одобрено: ' + (row.approved||0) + ' • Доход: ' + Number(row.revenue||0).toLocaleString('ru-RU') + ' ₽</div>'
                            + '</div>';
                    });
                } else {
                    summaryHtml = '<div class="text-sm text-gray-400">Пока нет данных по inline CTA.</div>';
                }

                var topRows = '';
                if (articles.length) {
                    articles.slice(0, 10).forEach(function(row){
                        topRows += '<tr class="border-t"><td class="p-2 text-left"><a class="text-primary hover:underline" href="/articles/' + row.article_slug + '" target="_blank">' + row.article_slug + '</a></td><td class="p-2 text-center font-semibold">' + String(row.variant || '').toUpperCase() + '</td><td class="p-2 text-right">' + (row.impressions||0) + '</td><td class="p-2 text-right">' + (row.clicks||0) + '</td><td class="p-2 text-right">' + (row.ctr||0) + '%</td></tr>';
                    });
                } else {
                    topRows = '<tr><td colspan="5" class="p-3 text-center text-gray-400">Нет данных</td></tr>';
                }

                box.innerHTML = '<div class="flex items-center justify-between gap-4 mb-4"><div><h3 class="text-lg font-bold">A/B inline CTA в статьях</h3><p class="text-sm text-gray-500">Показы, клики и CTR за 30 дней</p></div></div>'
                    + '<div class="grid sm:grid-cols-2 gap-4 mb-4">' + summaryHtml + '</div>'
                    + '<div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="p-2 text-left">Статья</th><th class="p-2 text-center">Вариант</th><th class="p-2 text-right">Показы</th><th class="p-2 text-right">Клики</th><th class="p-2 text-right">CTR</th></tr></thead><tbody>' + topRows + '</tbody></table></div>';
                host.prepend(box);
            }).catch(function(){});
        }, 300);
    };
})();
</script>
