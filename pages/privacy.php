<?php
$pageTitle = pageMetaTitle('Политика конфиденциальности');
$metaDescription = 'Политика конфиденциальности сайта ' . SITE_NAME . '. Порядок обработки персональных данных, cookies и пользовательской информации.';
ob_start();
?>
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?= renderBreadcrumbs($breadcrumbs) ?>
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Политика конфиденциальности</h1>
    <div class="bg-white rounded-xl border border-gray-100 p-8 prose max-w-none text-gray-700">
        <p>Настоящая Политика конфиденциальности определяет порядок обработки персональных данных пользователей сайта <?= SITE_NAME ?> (<?= SITE_URL ?>).</p>
        <h2>1. Сбор данных</h2>
        <p>Мы можем собирать следующие данные: email-адрес (при подписке), IP-адрес, данные о браузере и устройстве, cookies.</p>
        <h2>2. Использование данных</h2>
        <p>Данные используются для: предоставления сервиса, отправки рассылок (при согласии), улучшения работы сайта, аналитики.</p>
        <h2>3. Передача данных</h2>
        <p>Мы не передаём персональные данные третьим лицам, за исключением случаев, предусмотренных законодательством РФ.</p>
        <h2>4. Cookies</h2>
        <p>Сайт использует cookies для корректной работы и аналитики. Вы можете отключить cookies в настройках браузера.</p>
        <h2>5. Контакты</h2>
        <p>По вопросам обработки персональных данных обращайтесь через <a href="/contact" class="text-primary hover:underline">форму обратной связи</a> на сайте.</p>
    </div>
</section>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb($breadcrumbs),
];
$canonicalUrl = pageCanonical('/privacy');
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';
