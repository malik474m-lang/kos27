<?php
$settings = getSiteSettings();
$contactEmail = $settings['contact_email'] ?? '';

$pageTitle = 'Обратная связь — ' . SITE_NAME;
$metaDescription = 'Форма обратной связи сайта ' . SITE_NAME . '. Свяжитесь с нами по любым вопросам.';

$sent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $hp = trim($_POST['hp_field'] ?? '');

    if ($hp) { $error = 'Ошибка отправки'; }
    elseif (!$name || !$email || !$message) { $error = 'Заполните все обязательные поля'; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Некорректный email'; }
    elseif (mb_strlen($message) < 10) { $error = 'Сообщение слишком короткое'; }
    else {
        $to = $contactEmail ?: 'info@kosmozaim.ru';
        $subjectLine = '=?UTF-8?B?' . base64_encode('[' . SITE_NAME . '] ' . ($subject ?: 'Обратная связь')) . '?=';
        $body = "Имя: {$name}\nEmail: {$email}\nТема: {$subject}\n\nСообщение:\n{$message}\n\n---\nIP: " . ($_SERVER['REMOTE_ADDR'] ?? '') . "\nДата: " . date('d.m.Y H:i:s');
        $headers = "From: =?UTF-8?B?" . base64_encode(SITE_NAME) . "?= <noreply@kosmozaim.ru>\r\n"
                 . "Reply-To: {$email}\r\n"
                 . "Content-Type: text/plain; charset=UTF-8\r\n";

        if (@mail($to, $subjectLine, $body, $headers, '-fnoreply@kosmozaim.ru')) {
            $sent = true;
        } else {
            $error = 'Ошибка отправки. Попробуйте позже или напишите на ' . e($to);
        }
    }
}

ob_start();
?>
<section class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6"><a href="/" class="hover:text-primary">Главная</a> → Обратная связь</nav>
    <h1 class="text-3xl font-bold text-gray-900 mb-4">Обратная связь</h1>
    <p class="text-gray-600 mb-8">Есть вопрос, предложение или проблема? Напишите нам — мы ответим на указанный email.</p>

    <?php if ($sent): ?>
    <div class="bg-green-50 border border-green-200 rounded-xl p-8 text-center">
        <span class="text-4xl block mb-4">✅</span>
        <h2 class="text-xl font-bold text-green-800 mb-2">Сообщение отправлено!</h2>
        <p class="text-green-600">Мы ответим на ваш email в ближайшее время.</p>
        <a href="/" class="inline-block mt-4 text-primary hover:underline">← На главную</a>
    </div>
    <?php else: ?>

    <?php if ($error): ?>
    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-red-700 text-sm"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="bg-white rounded-xl border p-6 space-y-4">
        <div style="display:none"><input type="text" name="hp_field" tabindex="-1" autocomplete="off"></div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Имя *</label>
                <input type="text" name="name" required value="<?= e($_POST['name'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ваше имя">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="your@email.com">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Тема</label>
            <input type="text" name="subject" value="<?= e($_POST['subject'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="О чём хотите написать?">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Сообщение *</label>
            <textarea name="message" required rows="6" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Опишите ваш вопрос или предложение..."><?= e($_POST['message'] ?? '') ?></textarea>
        </div>

        <div class="flex items-center justify-between">
            <p class="text-xs text-gray-400">* — обязательные поля</p>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">Отправить</button>
        </div>
    </form>

    <?php if ($contactEmail): ?>
    <p class="text-sm text-gray-500 mt-6 text-center">Или напишите напрямую: <a href="mailto:<?= e($contactEmail) ?>" class="text-primary hover:underline"><?= e($contactEmail) ?></a></p>
    <?php endif; ?>

    <?php endif; ?>
</section>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>'Обратная связь','url'=>'/contact']]),
];
$canonicalUrl = SITE_URL . '/contact';
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';
