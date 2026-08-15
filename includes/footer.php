<?php
$topCities = [
    ['name'=>'Москва','slug'=>'moskva','prep'=>'Москве'],
    ['name'=>'Санкт-Петербург','slug'=>'sankt-peterburg','prep'=>'Санкт-Петербурге'],
    ['name'=>'Новосибирск','slug'=>'novosibirsk','prep'=>'Новосибирске'],
    ['name'=>'Екатеринбург','slug'=>'ekaterinburg','prep'=>'Екатеринбурге'],
    ['name'=>'Казань','slug'=>'kazan','prep'=>'Казани'],
    ['name'=>'Нижний Новгород','slug'=>'nizhnij-novgorod','prep'=>'Нижнем Новгороде'],
    ['name'=>'Челябинск','slug'=>'chelyabinsk','prep'=>'Челябинске'],
    ['name'=>'Самара','slug'=>'samara','prep'=>'Самаре'],
    ['name'=>'Омск','slug'=>'omsk','prep'=>'Омске'],
    ['name'=>'Ростов-на-Дону','slug'=>'rostov-na-donu','prep'=>'Ростове-на-Дону'],
];
?>
<footer class="bg-gray-900 text-gray-300 mt-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <a href="/" class="flex items-center space-x-2 mb-4">
                    <?php if (defined('SITE_LOGO') && SITE_LOGO): ?>
                    <span class="inline-flex items-center rounded-xl bg-white px-3 py-2 shadow-sm">
                        <img src="<?= e(SITE_LOGO) ?>" alt="<?= e(SITE_NAME) ?>" class="h-8 max-w-[140px] object-contain" loading="lazy" decoding="async">
                    </span>
                    <?php else: ?>
                    <span class="text-2xl">🚀</span>
                    <span class="text-xl font-bold text-white"><?= e(SITE_NAME) ?></span>
                    <?php endif; ?>
                </a>
                <p class="text-sm text-gray-400">Сервис подбора финансовых предложений. Сравнивайте условия и выбирайте лучшие займы, кредиты и банковские карты.</p>
            </div>
            <div>
                <h3 class="text-white font-semibold mb-4">Продукты</h3>
                <ul class="space-y-2">
                    <?php require_once __DIR__ . '/categories.php'; foreach (getFooterCategoriesBySection('products') as $fc): ?>
                    <li><a href="<?= getCategoryUrl($fc) ?>" class="hover:text-white transition-colors text-sm"><?= e($fc['name']) ?></a></li>
                    <?php foreach (getSubcategories((int)$fc['id']) as $fsc): ?>
                    <li><a href="<?= getCategoryUrl($fsc) ?>" class="hover:text-white transition-colors text-sm pl-2">— <?= e($fsc['name']) ?></a></li>
                    <?php endforeach; endforeach; ?>
                </ul>
            </div>
            <div>
                <h3 class="text-white font-semibold mb-4">Инструменты</h3>
                <ul class="space-y-2">
                    <?php foreach (getFooterCategoriesBySection('tools') as $navC): ?>
                    <li><a href="<?= getCategoryUrl($navC) ?>" class="hover:text-white transition-colors text-sm"><?= e($navC['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h3 class="text-white font-semibold mb-4">Информация</h3>
                <p class="text-sm text-gray-400">Информация на сайте носит информационный характер и не является публичной офертой. Все условия уточняйте на сайтах партнёров.</p>
                <div class="bg-gray-800 rounded-xl p-4 mt-6">
                    <h3 class="text-white font-semibold mb-2 text-sm">📬 Подпишитесь на рассылку</h3>
                    <p class="text-gray-400 text-xs mb-3">Получайте лучшие предложения</p>
                    <form id="subscribe-form" onsubmit="return handleSubscribe(event)">
                        <div style="display:none"><input type="text" name="hp-sub" id="hp-sub" tabindex="-1" autocomplete="off"></div>
                        <input type="email" name="email" placeholder="Ваш email" required class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-400 focus:outline-none focus:border-primary mb-2">
                        <label class="flex items-start gap-2 mb-2 cursor-pointer">
                            <input type="checkbox" id="sub-agree-rules" checked class="mt-0.5 w-3.5 h-3.5 flex-shrink-0 accent-primary">
                            <span class="text-xs text-gray-400 leading-tight">Соглашаюсь с <a href="/terms" class="text-primary hover:underline">Правилами сайта</a>, предоставляю согласие на <a href="/privacy" class="text-primary hover:underline">обработку персональных данных</a> и на участие в Программе лояльности</span>
                        </label>
                        <label class="flex items-start gap-2 mb-3 cursor-pointer">
                            <input type="checkbox" id="sub-agree-marketing" checked class="mt-0.5 w-3.5 h-3.5 flex-shrink-0 accent-primary">
                            <span class="text-xs text-gray-400 leading-tight">Предоставляю согласие на получение рекламы и информационных сообщений</span>
                        </label>
                        <button type="submit" class="w-full bg-primary hover:bg-primary-dark text-white px-3 py-2 rounded-lg font-medium text-sm transition-colors">Подписаться</button>
                    </form>
                </div>
                <a href="/app" class="block bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl p-4 mt-4 hover:opacity-90 transition-opacity">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">📱</span>
                        <div>
                            <div class="text-white font-semibold text-sm">Скачайте приложение</div>
                            <div class="text-blue-200 text-xs">Займы и кредиты в кармане</div>
                        </div>
                        <span class="text-white ml-auto">→</span>
                    </div>
                </a>
            </div>
        </div>

        <div class="border-t border-gray-800 mt-8 pt-6">
            <p class="text-sm text-gray-400 mb-3">Займы по городам:</p>
            <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs">
                <?php foreach ($topCities as $city): ?>
                <a href="/zajmy/<?= $city['slug'] ?>" class="text-gray-500 hover:text-white transition-colors">в <?= $city['prep'] ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="border-t border-gray-800 mt-6 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-gray-500">
            <p>© <?= date('Y') ?> Космозайм. Все права защищены. <?php if(isset($GLOBALS['page_start_time'])):?><span class="text-gray-600 text-xs ml-2"><?= round((microtime(true)-$GLOBALS['page_start_time'])*1000) ?> мс</span><?php endif;?></p>
            <div class="flex gap-4">
                <a href="/privacy" class="hover:text-white transition-colors">Конфиденциальность</a>
                <a href="/terms" class="hover:text-white transition-colors">Соглашение</a>
                <a href="/disclaimer" class="hover:text-white transition-colors">Отказ от ответственности</a>
                <a href="/contact" class="hover:text-white transition-colors">Обратная связь</a>
            </div>
        </div>
    </div>
</footer>

