<?php
require_once __DIR__ . '/../data/glossary.php';

$term = findGlossaryTermBySlug($termSlug);
if (!$term) { http_response_code(404); $pageTitle='Термин не найден'; ob_start(); echo '<div class="max-w-7xl mx-auto px-4 py-24 text-center"><h1 class="text-2xl font-bold">Термин не найден</h1></div>'; $content=ob_get_clean(); require __DIR__.'/../includes/layout.php'; return; }

$pageTitle = $term['term'] . ' — Глоссарий | Космозайм';
$metaDescription = $term['shortDefinition'];

ob_start();
?>
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6"><a href="/" class="hover:text-primary">Главная</a> → <a href="/glossary" class="hover:text-primary">Глоссарий</a> → <?= e($term['term']) ?></nav>
    <h1 class="text-3xl font-bold text-gray-900 mb-4"><?= e($term['term']) ?></h1>
    <div class="bg-white rounded-xl border border-gray-100 p-8 prose max-w-none text-gray-700">
        <?= nl2br(e($term['fullDefinition'])) ?>
    </div>
</section>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>'Глоссарий','url'=>'/glossary'],['name'=>$term['term'],'url'=>'/glossary/'.$term['slug']]]),
];
$canonicalUrl = SITE_URL . '/glossary/' . $term['slug'];
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';
