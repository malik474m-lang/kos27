<?php
$pageTitle = 'Скачать приложение Космозайм для Android | ' . SITE_NAME;
$metaDescription = 'Скачайте мобильное приложение Космозайм для Android. Подбор займов, кредитов и карт прямо с телефона.';

$downloadCount = 0;
try {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS app_downloads (id INT AUTO_INCREMENT PRIMARY KEY, platform VARCHAR(20), ip VARCHAR(45), user_agent TEXT, referrer VARCHAR(500), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $downloadCount = (int)$db->query("SELECT COUNT(*) as cnt FROM app_downloads")->fetch()['cnt'];
} catch (Exception $e) {}

ob_start();
?>
<section class="gradient-hero text-white py-16 sm:py-24">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <div class="text-6xl mb-6">📱</div>
        <h1 class="text-3xl sm:text-5xl font-extrabold mb-4">Скачайте приложение<br>Космозайм</h1>
        <p class="text-lg text-blue-100 mb-8 max-w-2xl mx-auto">Подберите займ, кредит или карту прямо с телефона. Калькулятор, сравнение и мгновенное оформление.</p>
        <a href="/download-apk.php" id="download-btn" target="_self" class="inline-flex items-center gap-3 bg-white text-primary px-8 py-4 rounded-xl font-bold text-lg hover:bg-gray-100 transition-colors shadow-lg">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M17.523 2H6.477C5.66 2 5 2.66 5 3.477v17.046C5 21.34 5.66 22 6.477 22h11.046C18.34 22 19 21.34 19 20.523V3.477C19 2.66 18.34 2 17.523 2zM12 20.5c-.552 0-1-.448-1-1s.448-1 1-1 1 .448 1 1-.448 1-1 1zM17 17H7V5h10v12z"/></svg>
            Скачать для Android
        </a>
        <?php if ($downloadCount > 0): ?>
        <p class="text-blue-200 text-sm mt-4">Уже скачали: <?= number_format($downloadCount, 0, '', ' ') ?></p>
        <?php endif; ?>
    </div>
</section>

<div class="max-w-4xl mx-auto px-4 py-12">
    <h2 class="text-2xl font-bold text-gray-900 mb-8 text-center">Возможности приложения</h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-16">
        <?php
        $features = [
            ['icon'=>'💵', 'title'=>'Каталог предложений', 'desc'=>'Займы, кредиты, кредитные и дебетовые карты с фильтрами'],
            ['icon'=>'🧮', 'title'=>'Калькулятор займа', 'desc'=>'Рассчитайте стоимость и переплату по любому предложению'],
            ['icon'=>'❤️', 'title'=>'Избранное', 'desc'=>'Сохраняйте понравившиеся предложения для сравнения'],
            ['icon'=>'📰', 'title'=>'Полезные статьи', 'desc'=>'Советы по финансовой грамотности и выбору продуктов'],
            ['icon'=>'⭐', 'title'=>'Отзывы', 'desc'=>'Читайте и оставляйте отзывы о финансовых организациях'],
            ['icon'=>'🔔', 'title'=>'Быстрый доступ', 'desc'=>'Мгновенный запуск с главного экрана телефона'],
        ];
        foreach ($features as $f):
        ?>
        <div class="bg-white rounded-xl border border-gray-100 p-6 text-center card-hover">
            <span class="text-3xl block mb-3"><?= $f['icon'] ?></span>
            <h3 class="font-bold text-gray-900 mb-2"><?= $f['title'] ?></h3>
            <p class="text-sm text-gray-500"><?= $f['desc'] ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-8 sm:p-10 mb-12">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">Как установить</h2>
        <div class="grid sm:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="w-12 h-12 bg-primary text-white rounded-full flex items-center justify-center text-xl font-bold mx-auto mb-4">1</div>
                <h3 class="font-semibold text-gray-900 mb-2">Скачайте APK</h3>
                <p class="text-sm text-gray-500">Нажмите кнопку «Скачать для Android» выше</p>
            </div>
            <div class="text-center">
                <div class="w-12 h-12 bg-primary text-white rounded-full flex items-center justify-center text-xl font-bold mx-auto mb-4">2</div>
                <h3 class="font-semibold text-gray-900 mb-2">Разрешите установку</h3>
                <p class="text-sm text-gray-500">Если телефон спросит — разрешите установку из этого источника</p>
            </div>
            <div class="text-center">
                <div class="w-12 h-12 bg-accent text-white rounded-full flex items-center justify-center text-xl font-bold mx-auto mb-4">3</div>
                <h3 class="font-semibold text-gray-900 mb-2">Готово!</h3>
                <p class="text-sm text-gray-500">Иконка появится на главном экране. Откройте и пользуйтесь!</p>
            </div>
        </div>
    </div>

    <div class="bg-gray-50 rounded-2xl p-8 text-center">
        <h2 class="text-xl font-bold text-gray-900 mb-3">Есть iPhone?</h2>
        <p class="text-gray-600 mb-4">Откройте <a href="/" class="text-primary font-semibold">kosmozaim.ru</a> в Safari и нажмите «На экран Домой» — приложение установится автоматически.</p>
    </div>
</div>



<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>'Приложение','url'=>'/app']]),
    [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'Космозайм',
        'operatingSystem' => 'Android',
        'applicationCategory' => 'FinanceApplication',
        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'RUB'],
    ]
];
$canonicalUrl = SITE_URL . '/app';
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';
