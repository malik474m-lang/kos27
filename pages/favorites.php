<?php
$pageTitle = 'Избранное — Космозайм';
ob_start();
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6"><a href="/" class="hover:text-primary">Главная</a> → Избранное</nav>
    <h1 class="text-3xl font-bold text-gray-900 mb-8">❤️ Избранное</h1>
    <div id="favorites-list">
        <p class="text-gray-500 text-center py-12">Загрузка...</p>
    </div>
</section>
<script>
function getFavs(){
    try { return JSON.parse(localStorage.getItem('kosmozaim_favorites') || '[]'); }
    catch(e){ return []; }
}
function setFavs(ids){
    localStorage.setItem('kosmozaim_favorites', JSON.stringify(ids));
    window.dispatchEvent(new CustomEvent('favorites:changed', { detail: { ids: ids } }));
}
function removeFav(id){
    id = Number(id);
    setFavs(getFavs().filter(function(x){ return x !== id; }));
    renderFavorites();
}
function renderFavorites(){
    const favs = getFavs();
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
                '<div class="bg-white rounded-xl shadow-sm border p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">' +
                '<div class="min-w-0"><a href="/offer/' + o.slug + '" class="font-semibold text-primary hover:underline">' + o.title + '</a></div>' +
                '<div class="flex items-center gap-3">' +
                '<button type="button" onclick="removeFav(' + o.id + ')" class="text-sm text-gray-400 hover:text-red-500">Убрать</button>' +
                '<a href="/click/' + o.id + '" target="_blank" class="bg-accent text-white px-4 py-2 rounded-lg text-sm font-semibold">Оформить</a>' +
                '</div>' +
                '</div>'
            ).join('') + '</div>';
        });
}
document.addEventListener('DOMContentLoaded', renderFavorites);
window.addEventListener('favorites:changed', renderFavorites);
window.addEventListener('storage', renderFavorites);
</script>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>'Избранное','url'=>'/favorites']]),
];
$canonicalUrl = SITE_URL . '/favorites';
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';
