<?php
$pageTitle = 'Отказ от ответственности — Космозайм';
ob_start();
?>
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6"><a href="/" class="hover:text-primary">Главная</a> → Отказ от ответственности</nav>
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Отказ от ответственности</h1>
    <div class="bg-white rounded-xl border border-gray-100 p-8 prose max-w-none text-gray-700">
        <p><strong><?= SITE_NAME ?></strong> не является кредитной организацией, микрофинансовой организацией, банком или ломбардом.</p>
        <p>Сайт предоставляет информационные услуги по подбору финансовых продуктов. Мы не выдаём займы, кредиты и не оказываем финансовых услуг.</p>
        <p>Все представленные на сайте предложения являются информацией партнёрских организаций. Условия могут отличаться от указанных. Перед оформлением внимательно изучайте договор на сайте партнёра.</p>
        <p>Процентные ставки и условия актуальны на момент публикации и могут изменяться.</p>
    </div>
</section>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>'Отказ от ответственности','url'=>'/disclaimer']]),
];
$canonicalUrl = SITE_URL . '/disclaimer';
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';
