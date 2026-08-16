<?php
$pageTitle = 'Источники информации — ' . SITE_NAME;
$metaDescription = 'Официальные и публичные источники данных, которые используются при подготовке материалов на сайте ' . SITE_NAME . '.';
ob_start();
?>
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6"><a href="/" class="hover:text-primary">Главная</a> → Источники информации</nav>
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Источники информации</h1>
    <div class="bg-white rounded-xl border border-gray-100 p-8 prose max-w-none text-gray-700">
        <p>При подготовке карточек, обзоров и статей мы используем открытые и официальные источники.</p>
        <h2>Основные источники</h2>
        <ul>
            <li><a href="https://cbr.ru/" target="_blank" rel="noopener">Банк России</a> — нормативные документы, реестры и справочная информация;</li>
            <li><a href="https://cbr.ru/microfinance/registry/" target="_blank" rel="noopener">Реестр МФО Банка России</a>;</li>
            <li>официальные сайты банков и МФО;</li>
            <li>условия и анкеты, размещённые партнёрскими организациями;</li>
            <li>публичные сведения о тарифах, ставках, акциях и льготных периодах.</li>
        </ul>
        <h2>Как использовать информацию</h2>
        <p>Информация на сайте носит справочный характер. Перед оформлением заявки проверяйте условия непосредственно на сайте партнёра и в официальных документах.</p>
    </div>
</section>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>'Источники информации','url'=>'/sources']]),
];
$canonicalUrl = SITE_URL . '/sources';
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';
