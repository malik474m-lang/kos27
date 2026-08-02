<?php
/**
 * Публичная страница розыгрыша
 */
require_once __DIR__ . '/../includes/autolinks.php';

$db = getDB();
$activeGiveaway = null;
$entries = [];
$finishedGiveaways = [];

try {
    $db->query("SELECT 1 FROM giveaways LIMIT 1");
    $stmt = $db->query("SELECT * FROM giveaways WHERE status IN ('active','drawing') ORDER BY created_at DESC LIMIT 1");
    $activeGiveaway = $stmt->fetch();

    if ($activeGiveaway) {
        $cnt = $db->prepare("SELECT COUNT(*) as cnt FROM giveaway_entries WHERE giveaway_id = ?");
        $cnt->execute([$activeGiveaway['id']]);
        $activeGiveaway['entries_count'] = (int)$cnt->fetch()['cnt'];

        $entStmt = $db->prepare("SELECT user_name, user_email, offer_title, created_at FROM giveaway_entries WHERE giveaway_id = ? ORDER BY created_at DESC LIMIT 50");
        $entStmt->execute([$activeGiveaway['id']]);
        $entries = $entStmt->fetchAll();
    }

    $finStmt = $db->query("SELECT g.*, ge.user_name as winner_name, ge.user_email as winner_email
        FROM giveaways g LEFT JOIN giveaway_entries ge ON g.winner_id = ge.id
        WHERE g.status = 'finished' ORDER BY g.updated_at DESC LIMIT 5");
    $finishedGiveaways = $finStmt->fetchAll();
} catch (Exception $e) {}

function maskEmailPublic(string $email): string {
    $parts = explode('@', $email);
    if (count($parts) !== 2) return '***@***';
    $name = $parts[0]; $domain = $parts[1];
    $visible = max(2, (int)(mb_strlen($name) * 0.4));
    return mb_substr($name, 0, $visible) . str_repeat('*', mb_strlen($name) - $visible) . '@' . $domain;
}

$pageTitle = 'Розыгрыш призов — ' . SITE_NAME;
$metaDescription = 'Участвуйте в розыгрыше денежных призов от ' . SITE_NAME . '. Оформите любой финансовый продукт и получите шанс выиграть!';

ob_start();
?>
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6"><a href="/" class="hover:text-primary">Главная</a> → Розыгрыш</nav>

    <?php if ($activeGiveaway): ?>
    <!-- Активный розыгрыш -->
    <div class="rounded-2xl overflow-hidden mb-8" style="background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%)">
        <div class="p-8 sm:p-12 text-center">
            <span class="text-5xl block mb-4">🎁</span>
            <h1 class="text-3xl sm:text-4xl font-extrabold mb-4" style="color:#ffd700"><?= e($activeGiveaway['title']) ?></h1>
            <?php if (!empty($activeGiveaway['description'])): ?>
            <p class="text-lg mb-6" style="color:#e0e0e0"><?= e($activeGiveaway['description']) ?></p>
            <?php endif; ?>
            <div class="text-4xl font-bold mb-2" style="color:#ffd700"><?= number_format((float)$activeGiveaway['prize_amount'], 0, '', ' ') ?> ₽</div>
            <p class="text-sm mb-6" style="color:#aaa">Призовой фонд</p>
            <div class="flex flex-wrap items-center justify-center gap-6 text-sm" style="color:#ccc">
                <span>👥 <?= $activeGiveaway['entries_count'] ?> участников</span>
                <span>📅 До <?= $activeGiveaway['end_at'] ? date('d.m.Y', strtotime($activeGiveaway['end_at'])) : '—' ?></span>
                <?php if ($activeGiveaway['draw_at']): ?>
                <span>🎬 Розыгрыш: <?= date('d.m.Y в H:i', strtotime($activeGiveaway['draw_at'])) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Условия участия -->
    <div class="bg-white rounded-xl border p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4">📋 Условия участия</h2>
        <div class="space-y-3">
            <div class="flex items-start gap-3">
                <span class="bg-blue-100 text-blue-700 w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0">1</span>
                <div><p class="font-semibold text-gray-900">Зарегистрируйтесь на сайте</p>
                <p class="text-sm text-gray-600">Создайте аккаунт на <a href="/register" class="text-primary hover:underline"><?= SITE_NAME ?></a> — это бесплатно и займёт 1 минуту.</p></div>
            </div>
            <div class="flex items-start gap-3">
                <span class="bg-blue-100 text-blue-700 w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0">2</span>
                <div><p class="font-semibold text-gray-900">Оформите любой финансовый продукт</p>
                <p class="text-sm text-gray-600">Выберите <a href="/zajmy" class="text-primary hover:underline">займ</a>, <a href="/kredity" class="text-primary hover:underline">кредит</a> или <a href="/karty/kreditnye" class="text-primary hover:underline">банковскую карту</a> и оформите заявку через наш сайт.</p></div>
            </div>
            <div class="flex items-start gap-3">
                <span class="bg-blue-100 text-blue-700 w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0">3</span>
                <div><p class="font-semibold text-gray-900">Получите одобрение</p>
                <p class="text-sm text-gray-600">После одобрения вашей заявки партнёром вы <strong>автоматически</strong> становитесь участником розыгрыша.</p></div>
            </div>
            <div class="flex items-start gap-3">
                <span class="bg-green-100 text-green-700 w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0">✓</span>
                <div><p class="font-semibold text-gray-900">Ждите розыгрыш!</p>
                <p class="text-sm text-gray-600">Победитель определяется случайным образом в прямом эфире. Чем больше одобренных заявок — тем выше шанс!</p></div>
            </div>
        </div>
    </div>

    <!-- Важные условия -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 mb-8">
        <h3 class="font-bold text-yellow-800 mb-2">⚠️ Обязательные условия</h3>
        <ul class="text-sm text-yellow-700 space-y-1 list-disc pl-5">
            <li>Необходима <a href="/register" class="underline font-semibold">регистрация</a> на сайте <?= SITE_NAME ?></li>
            <li>Минимум одна <strong>одобренная</strong> заявка на любой финансовый продукт через наш сайт</li>
            <li>Заявка должна быть оформлена в период проведения конкурса</li>
            <li>Один пользователь может быть участником несколько раз (за каждую одобренную заявку)</li>
            <li>Призовой фонд формируется автоматически и может увеличиваться до момента розыгрыша</li>
        </ul>
    </div>

    <!-- Участники -->
    <?php if ($entries): ?>
    <div class="bg-white rounded-xl border mb-8">
        <div class="p-4 border-b"><h2 class="font-bold text-gray-900">👥 Участники (<?= count($entries) ?>)</h2></div>
        <div class="divide-y max-h-96 overflow-y-auto">
            <?php foreach ($entries as $en): ?>
            <div class="px-4 py-3 flex items-center justify-between">
                <div>
                    <span class="font-medium text-gray-900"><?= e($en['user_name']) ?></span>
                    <span class="text-gray-400 text-xs ml-2"><?= maskEmailPublic($en['user_email']) ?></span>
                </div>
                <div class="text-right">
                    <span class="text-xs text-gray-500"><?= e($en['offer_title'] ?: '—') ?></span>
                    <span class="text-xs text-gray-400 block"><?= date('d.m.Y', strtotime($en['created_at'])) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- CTA -->
    <div class="text-center bg-blue-50 rounded-xl border border-blue-200 p-8 mb-8">
        <h2 class="text-xl font-bold text-gray-900 mb-3">Хотите участвовать?</h2>
        <p class="text-gray-600 mb-6">Зарегистрируйтесь и оформите любой продукт — вы автоматически попадёте в розыгрыш!</p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="/register" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold transition-colors">Зарегистрироваться</a>
            <a href="/zajmy" class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg font-semibold transition-colors">Выбрать продукт</a>
        </div>
    </div>

    <?php else: ?>
    <!-- Нет активного розыгрыша -->
    <div class="text-center py-16">
        <span class="text-5xl block mb-4">🎁</span>
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Розыгрыши</h1>
        <p class="text-gray-600 mb-6">Сейчас активных розыгрышей нет. Следите за обновлениями!</p>
        <a href="/" class="btn-primary inline-block">На главную</a>
    </div>
    <?php endif; ?>

    <!-- Прошлые розыгрыши -->
    <?php if ($finishedGiveaways): ?>
    <div class="bg-white rounded-xl border p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">🏆 Прошлые розыгрыши</h2>
        <div class="space-y-3">
            <?php foreach ($finishedGiveaways as $fg): ?>
            <div class="bg-gray-50 rounded-lg p-4 flex items-center justify-between">
                <div>
                    <p class="font-semibold text-gray-900"><?= e($fg['title']) ?></p>
                    <p class="text-xs text-gray-500"><?= date('d.m.Y', strtotime($fg['start_at'])) ?> — <?= date('d.m.Y', strtotime($fg['end_at'])) ?></p>
                </div>
                <div class="text-right">
                    <p class="font-bold text-green-600"><?= number_format((float)$fg['prize_amount'], 0, '', ' ') ?> ₽</p>
                    <?php if ($fg['winner_name']): ?>
                    <p class="text-xs text-gray-500">🏆 <?= e($fg['winner_name']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</section>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>'Розыгрыш','url'=>'/giveaway']]),
];
$canonicalUrl = SITE_URL . '/giveaway';
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';
