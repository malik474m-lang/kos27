<?php
require_once __DIR__ . '/../data/glossary.php';

$term = findGlossaryTermBySlug($termSlug);
if (!$term) { http_response_code(404); $pageTitle='Термин не найден'; ob_start(); echo '<div class="max-w-7xl mx-auto px-4 py-24 text-center"><h1 class="text-2xl font-bold">Термин не найден</h1></div>'; $content=ob_get_clean(); require __DIR__.'/../includes/layout.php'; return; }

$pageTitle = pageMetaTitle($term['term'] . ' — Глоссарий', false) . ' | ' . SITE_NAME;
$metaDescription = $term['shortDefinition'];
$breadcrumbs = [breadcrumbItem('Главная', '/'), breadcrumbItem('Глоссарий', '/glossary'), breadcrumbItem($term['term'], '/glossary/' . $term['slug'])];

ob_start();
?>
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?= renderBreadcrumbs($breadcrumbs) ?>
    <h1 class="text-3xl font-bold text-gray-900 mb-4"><?= e($term['term']) ?></h1>
    <div class="bg-white rounded-xl border border-gray-100 p-8 prose max-w-none text-gray-700">
        <?= nl2br(e($term['fullDefinition'])) ?>
    </div>
</section>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb($breadcrumbs),
];
$canonicalUrl = pageCanonical('/glossary/' . $term['slug']);
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';
