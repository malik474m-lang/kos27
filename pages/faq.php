<?php
$pageTitle = 'Частые вопросы — Космозайм';
$metaDescription = 'Ответы на популярные вопросы о займах, кредитах и банковских картах.';

$faqs = [
    ['q'=>'Что такое микрозайм?','a'=>'Микрозайм — это небольшой краткосрочный заём от микрофинансовой организации (МФО). Суммы обычно от 1 000 до 100 000 рублей, сроки — от нескольких дней до нескольких месяцев.'],
    ['q'=>'Как оформить займ онлайн?','a'=>'Выберите МФО на нашем сайте, нажмите «Оформить», заполните заявку на сайте партнёра. Потребуется паспорт и банковская карта. Решение обычно приходит за 5-15 минут.'],
    ['q'=>'Что значит «первый займ без процентов»?','a'=>'Многие МФО предлагают первый займ под 0% при условии погашения в указанный срок (обычно 7-30 дней). Если вернёте вовремя — платите только сумму займа.'],
    ['q'=>'Что такое ПСК?','a'=>'ПСК (Полная стоимость кредита) — все расходы заёмщика, выраженные в процентах годовых. Включает проценты, комиссии и обязательные платежи. Позволяет сравнить реальную стоимость разных предложений.'],
    ['q'=>'Могут ли отказать в займе?','a'=>'Да, МФО может отказать. Частые причины: плохая кредитная история, наличие просрочек, несоответствие требованиям по возрасту или документам.'],
    ['q'=>'Как улучшить шансы на одобрение?','a'=>'Подавайте заявку в несколько МФО, указывайте точные данные, начните с небольшой суммы, погасите текущие просрочки.'],
    ['q'=>'Безопасно ли оформлять займ онлайн?','a'=>'Да, если МФО состоит в реестре ЦБ РФ. Проверяйте наличие лицензии на сайте Банка России. Все предложения на Космозайм — от проверенных партнёров.'],
    ['q'=>'Чем отличается займ от кредита?','a'=>'Займы выдают МФО, суммы меньше, ставки выше, оформление быстрее. Кредиты выдают банки, суммы больше, ставки ниже, требования строже.'],
];

ob_start();
?>
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6"><a href="/" class="hover:text-primary">Главная</a> → FAQ</nav>
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Частые вопросы</h1>
    <div class="space-y-4">
        <?php foreach ($faqs as $i => $faq): ?>
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <button onclick="this.nextElementSibling.classList.toggle('hidden');this.querySelector('svg').classList.toggle('rotate-180')" class="w-full text-left p-5 flex items-center justify-between hover:bg-gray-50">
                <span class="font-semibold text-gray-900"><?= e($faq['q']) ?></span>
                <svg class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div class="hidden px-5 pb-5 text-gray-600"><?= safeAutoLink($faq['a'], 3) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php
$jsonLdSchemas = [
    jsonLdFAQ($faqs),
    jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>'FAQ','url'=>'/faq']]),
];
$canonicalUrl = SITE_URL . '/faq';
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';
