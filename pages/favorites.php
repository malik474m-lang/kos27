<?php
$pageTitle = 'Избранное — Космозайм';
ob_start();
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">❤️ Избранное</h1>
    <div id="favorites-list">
        <p class="text-gray-500 text-center py-12">Загрузка...</p>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const favs = JSON.parse(localStorage.getItem('kosmozaim_favorites') || '[]');
    const el = document.getElementById('favorites-list');
    if (!favs.length) {
        el.innerHTML = '<div class="text-center py-12"><p class="text-3xl mb-3">❤️</p><p class="text-gray-500">Нет избранных предложений</p><a href="/zajmy" class="bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary-dark inline-block mt-4">Смотреть предложения</a></div>';
        return;
    }
    fetch('/api/offers?ids=' + favs.join(','))
        .then(r => r.json())
        .then(offers => {
            if (!offers.length) { el.innerHTML = '<p class="text-gray-500 text-center py-12">Предложения не найдены</p>'; return; }
            el.innerHTML = '<div class="grid gap-4">' + offers.map(o =>
                '<div class="bg-white rounded-xl shadow-sm border p-5 flex items-center justify-between">' +
                '<a href="/offer/' + o.slug + '" class="font-semibold text-primary hover:underline">' + o.title + '</a>' +
                '<a href="/click/' + o.id + '" target="_blank" class="bg-accent text-white px-4 py-2 rounded-lg text-sm font-semibold">Оформить</a>' +
                '</div>'
            ).join('') + '</div>';
        });
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';
