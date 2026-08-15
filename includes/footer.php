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
                <a href="/app" style="display:block;background:linear-gradient(135deg,#1a56db,#7e3af2);border-radius:16px;padding:20px;margin-bottom:16px;text-decoration:none;box-shadow:0 8px 24px rgba(26,86,219,0.3);">
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                        <div style="width:48px;height:48px;background:rgba(255,255,255,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;"><span style="font-size:24px;">📱</span></div>
                        <div>
                            <div style="color:#fff;font-weight:700;font-size:15px;">Скачайте приложение</div>
                            <div style="color:rgba(191,219,254,1);font-size:12px;">Бесплатно для Android</div>
                        </div>
                    </div>
                    <div style="background:rgba(255,255,255,0.2);border-radius:8px;padding:10px 0;text-align:center;">
                        <span style="color:#fff;font-weight:600;font-size:14px;">Скачать →</span>
                    </div>
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

