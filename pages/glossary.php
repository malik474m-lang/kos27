<?php
require_once __DIR__ . '/../data/glossary.php';

$pageTitle = 'Глоссарий финансовых терминов — Космозайм';
$metaDescription = 'Словарь финансовых терминов: ПСК, грейс-период, кредитная история и другие.';

ob_start();
?>
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6"><a href="/" class="hover:text-primary">Главная</a> → Глоссарий</nav>
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Глоссарий финансовых терминов</h1>
    <div class="space-y-4">
        <?php foreach ($glossaryTerms as $term): ?>
        <a href="/glossary/<?= e($term['slug']) ?>" class="block bg-white rounded-xl border border-gray-100 p-5 card-hover">
            <h2 class="font-bold text-gray-900"><?= e($term['term']) ?></h2>
            <p class="text-sm text-gray-600 mt-1"><?= e($term['shortDefinition']) ?></p>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>'Глоссарий','url'=>'/glossary']]),
];
$canonicalUrl = SITE_URL . '/glossary';
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';
