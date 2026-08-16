<?php
$token = $_GET['token'] ?? '';
$pageTitle = 'Отписка от рассылки — ' . SITE_NAME;
$metaDescription = 'Страница отписки от email-рассылки сайта ' . SITE_NAME . '. Управление подпиской на уведомления и предложения.';
$breadcrumbs = [breadcrumbItem('Главная', '/'), breadcrumbItem('Отписка от рассылки', '/unsubscribe')];
$pageHeadHtml = '<meta name="robots" content="noindex,follow">';
$message = '';

if ($token) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, email FROM subscribers WHERE unsubscribe_token = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$token]);
    $sub = $stmt->fetch();

    if ($sub) {
        $db->prepare("UPDATE subscribers SET is_active = 0 WHERE id = ?")->execute([$sub['id']]);
        $message = 'Вы успешно отписались от рассылки. Ваш email <strong>' . e($sub['email']) . '</strong> удалён из списка.';
    } else {
        $message = 'Ссылка недействительна или вы уже отписаны.';
    }
} else {
    $message = 'Некорректная ссылка для отписки.';
}

ob_start();
?>
<section class="max-w-xl mx-auto px-4 py-24 text-center">
    <?= renderBreadcrumbs($breadcrumbs) ?>
    <span class="text-5xl block mb-4">📬</span>
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Отписка от рассылки</h1>
    <div class="bg-white rounded-xl border border-gray-100 p-8 text-gray-600"><?= $message ?></div>
    <a href="/" class="inline-block mt-6 text-primary hover:underline">← На главную</a>
</section>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb($breadcrumbs),
];
$canonicalUrl = SITE_URL . '/unsubscribe';
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';
