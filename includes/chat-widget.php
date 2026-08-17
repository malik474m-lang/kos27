<?php
function renderChatWidget(): string {
    $settings = function_exists('getSiteSettings') ? getSiteSettings() : [];
    if (empty($settings['chat_enabled'])) return '';
    
    $siteName = e($settings['site_name'] ?? 'Космозайм');
    $greeting = e($settings['chat_greeting'] ?? "Здравствуйте! Я помощник {$siteName}. Помогу подобрать займ, кредит или карту. Спрашивайте! 😊");

    // Загружаем данные из БД для умных ответов
    $offers = [];
    $faqData = [];
    $articles = [];
    try {
        $db = getDB();
        // Офферы
        $rows = $db->query("SELECT id, title, slug, category, amount_min, amount_max, term_min_days, term_max_days, rate, free_term_days, rating, review_count, description, borrower_category FROM offers WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 30")->fetchAll();
        foreach ($rows as $r) {
            $offers[] = [
                't' => $r['title'],
                's' => $r['slug'],
                'c' => $r['category'],
                'amin' => (int)$r['amount_min'],
                'amax' => (int)$r['amount_max'],
                'tmin' => (int)$r['term_min_days'],
                'tmax' => (int)$r['term_max_days'],
                'rate' => (float)$r['rate'],
                'free' => (int)$r['free_term_days'],
                'rat' => (float)$r['rating'],
                'rev' => (int)$r['review_count'],
                'd' => mb_substr($r['description'] ?? '', 0, 150),
                'b' => $r['borrower_category'],
            ];
        }
        // FAQ
        $fRows = $db->query("SELECT question, answer FROM offer_faq ORDER BY id DESC LIMIT 60")->fetchAll();
        foreach ($fRows as $f) $faqData[] = ['q' => $f['question'], 'a' => mb_substr($f['answer'], 0, 300)];
        // Статьи
        $aRows = $db->query("SELECT title, slug, excerpt FROM articles WHERE is_published = 1 ORDER BY created_at DESC LIMIT 20")->fetchAll();
        foreach ($aRows as $a) $articles[] = ['t' => $a['title'], 's' => $a['slug'], 'e' => mb_substr($a['excerpt'] ?? '', 0, 100)];
    } catch (Exception $e) {}

    $offersJson = json_encode($offers, JSON_UNESCAPED_UNICODE);
    $faqJson = json_encode($faqData, JSON_UNESCAPED_UNICODE);
    $articlesJson = json_encode($articles, JSON_UNESCAPED_UNICODE);
    
    ob_start();
    ?>
<!-- Smart Chat Widget -->
<div id="kz-chat-btn" onclick="kzToggle()" style="position:fixed;bottom:24px;right:24px;z-index:9998;width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#1a56db,#7e3af2);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 4px 20px rgba(26,86,219,.4);transition:transform .2s;font-size:28px" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'"><span id="kz-ci">💬</span></div>
<div id="kz-badge" style="display:none;position:fixed;bottom:76px;right:24px;z-index:9997;background:#fff;color:#374151;padding:8px 16px;border-radius:12px 12px 0 12px;box-shadow:0 2px 12px rgba(0,0,0,.1);font-size:13px;max-width:220px;cursor:pointer" onclick="kzToggle()">Помочь с выбором? 🤖</div>
<div id="kz-win" style="display:none;position:fixed;bottom:96px;right:24px;z-index:9999;width:400px;max-width:calc(100vw - 32px);height:560px;max-height:calc(100vh - 120px);background:#fff;border-radius:20px;box-shadow:0 8px 40px rgba(0,0,0,.15);overflow:hidden;flex-direction:column;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif">
<div style="background:linear-gradient(135deg,#1a56db,#7e3af2);color:#fff;padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0">
<div style="display:flex;align-items:center;gap:10px"><span style="font-size:24px">🤖</span><div><div style="font-weight:700;font-size:15px">Помощник <?= $siteName ?></div><div style="font-size:11px;opacity:.8">Подбор финансовых продуктов</div></div></div>
<button onclick="kzToggle()" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:16px">✕</button>
</div>
<div id="kz-msgs" style="flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:12px;background:#f9fafb"></div>
<div style="padding:12px 16px;border-top:1px solid #e5e7eb;background:#fff;flex-shrink:0">
<form onsubmit="return kzSend()" style="display:flex;gap:8px">
<input id="kz-inp" type="text" placeholder="Задайте вопрос..." autocomplete="off" style="flex:1;border:1px solid #d1d5db;border-radius:12px;padding:10px 14px;font-size:14px;outline:none" onfocus="this.style.borderColor='#1a56db'" onblur="this.style.borderColor='#d1d5db'">
<button type="submit" style="background:#1a56db;color:#fff;border:none;border-radius:12px;width:44px;height:44px;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;flex-shrink:0">➤</button>
</form>
</div>
</div>
<style>
@media(max-width:480px){#kz-win{bottom:0!important;right:0!important;width:100vw!important;max-width:100vw!important;height:100vh!important;max-height:100vh!important;border-radius:0!important}#kz-chat-btn{bottom:16px!important;right:16px!important}}
.kz-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px;margin-top:8px}
.kz-card-title{font-weight:700;font-size:14px;color:#111827;margin-bottom:4px}
.kz-card-info{font-size:12px;color:#6b7280;line-height:1.4}
.kz-card-info b{color:#059669}
.kz-card-btn{display:inline-block;margin-top:8px;background:#1a56db;color:#fff;padding:6px 16px;border-radius:8px;font-size:12px;text-decoration:none;font-weight:600}
.kz-card-btn:hover{background:#1244af}
.kz-qb{display:inline-block;background:#eef2ff;color:#1a56db;border:1px solid #c7d2fe;border-radius:20px;padding:6px 14px;font-size:12px;cursor:pointer;margin:3px;border:1px solid #c7d2fe}
.kz-qb:hover{background:#dbeafe}
.kz-link{display:inline-block;background:#f0fdf4;color:#059669;border-radius:8px;padding:4px 12px;font-size:12px;text-decoration:none;font-weight:500;margin:2px}
</style>
<script>
var kzOpen=false,kzState='init';
var kzOffers=<?= $offersJson ?>;
var kzFaq=<?= $faqJson ?>;
var kzArts=<?= $articlesJson ?>;

// Синонимы для нечёткого поиска
var kzSyn={
'займ':['займ','микрозайм','мфо','деньги','срочн','быстр','онлайн','получить'],
'кредит':['кредит','банк','ипотек','потребительск','рефинанс','ссуда'],
'кредитная карта':['кредитн','грейс','льготн','беспроцентн','кредитка'],
'дебетовая карта':['дебетов','кэшбек','кешбек','зарплатн','дебетовка'],
'ставка':['ставк','процент','пск','переплат','дорог','дёшев','дешев','выгод'],
'сумма':['сумм','сколько','рублей','рубл','тысяч','000'],
'срок':['срок','дней','дня','день','месяц','год','надолго','коротк'],
'без отказа':['без отказ','отказ','одобр','плох','историй','история','ки'],
'первый':['перв','бесплатн','0%','ноль','под 0','нулев','без процент'],
'пенсионер':['пенсион','пожил','старш'],
'студент':['студент','учащ','молод'],
'документ':['документ','паспорт','справк','снилс','инн','2ндфл'],
'безопасн':['безопасн','мошен','обман','проверить','лицензи','цб','реестр','надёжн','надежн'],
'калькулятор':['калькул','рассчит','посчит','платёж','платеж'],
};

function kzToggle(){
    kzOpen=!kzOpen;
    document.getElementById('kz-win').style.display=kzOpen?'flex':'none';
    document.getElementById('kz-badge').style.display='none';
    document.getElementById('kz-ci').textContent=kzOpen?'✕':'💬';
    if(kzOpen&&kzState==='init'){kzState='ready';kzShowGreeting();}
    if(kzOpen)setTimeout(function(){document.getElementById('kz-inp').focus();},100);
}

function kzShowGreeting(){
    kzBot('<?= $greeting ?>');
    kzQuickBtns(['💰 Подобрать займ','💳 Кредитные карты','🧮 Калькулятор','❓ Как оформить?','🔒 Безопасность','📋 Все предложения']);
}

function kzQuickBtns(btns){
    var c=document.getElementById('kz-msgs');
    var d=document.createElement('div');
    d.style.cssText='display:flex;flex-wrap:wrap;gap:4px;margin-top:4px';
    btns.forEach(function(b){
        var s=document.createElement('span');
        s.className='kz-qb';s.textContent=b;
        s.onclick=function(){kzAsk(b);};
        d.appendChild(s);
    });
    c.appendChild(d);c.scrollTop=c.scrollHeight;
}

function kzAsk(t){document.getElementById('kz-inp').value=t;kzSend();}
function kzSend(){var inp=document.getElementById('kz-inp');var msg=inp.value.trim();if(!msg)return false;inp.value='';kzUser(msg);setTimeout(function(){kzProcess(msg);},300+Math.random()*500);return false;}

function kzProcess(q){
    var ql=q.toLowerCase().replace(/[?!.,;:«»"']/g,'').trim();
    
    // 1. Определяем намерение
    var intent=kzIntent(ql);
    
    // 2. Обрабатываем по намерению
    switch(intent){
        case 'find_offer': kzFindOffer(ql); break;
        case 'compare': kzCompare(ql); break;
        case 'credit_card': kzCreditCards(ql); break;
        case 'debit_card': kzDebitCards(ql); break;
        case 'calculator': kzBot('Калькулятор поможет рассчитать стоимость займа.\nВведите сумму и срок — увидите переплату и ежемесячный платёж.',null,[{t:'🧮 Открыть калькулятор',u:'/calculator'}]); break;
        case 'howto': kzHowTo(ql); break;
        case 'safety': kzSafety(); break;
        case 'no_refuse': kzNoRefuse(ql); break;
        case 'first_free': kzFirstFree(); break;
        case 'documents': kzDocuments(); break;
        case 'rate_info': kzRateInfo(); break;
        case 'all_offers': kzBot('На сайте собраны проверенные предложения по займам, кредитам и картам. Все организации имеют лицензию ЦБ РФ.',null,[{t:'Займы',u:'/zajmy'},{t:'Кредиты',u:'/kredity'},{t:'Кредитные карты',u:'/karty/kreditnye'},{t:'Дебетовые карты',u:'/karty/debetovye'}]); break;
        case 'articles': kzShowArticles(ql); break;
        case 'contact': kzBot('Связаться с нами можно через страницу контактов.',null,[{t:'📞 Контакты',u:'/contact'}]); break;
        case 'greet': kzBot('Здравствуйте! 😊 Чем могу помочь?\n\nЯ могу:\n• Подобрать займ по сумме и сроку\n• Показать карты с кэшбеком\n• Найти займ под 0%\n• Рассказать про ПСК и безопасность'); kzQuickBtns(['💰 Займ','💳 Карты','🆓 Под 0%','🧮 Калькулятор']); break;
        case 'thanks': kzBot('Рад помочь! 😊 Если ещё будут вопросы — обращайтесь!'); break;
        case 'sum_query': kzFindBySum(ql); break;
        default: kzFallback(ql); break;
    }
}

function kzIntent(q){
    var m=function(keys){for(var i=0;i<keys.length;i++)if(q.indexOf(keys[i])!==-1)return true;return false;};
    if(m(['привет','здравств','добр','хай','hello','йо']))return'greet';
    if(m(['спасибо','благодар','помог','круто','отлично']))return'thanks';
    if(m(['контакт','связ','написать','позвон','email']))return'contact';
    if(m(['калькулят','рассчит','посчит']))return'calculator';
    if(m(['безопасн','мошен','обман','проверить','лицензи','цб','реестр']))return'safety';
    if(m(['документ','паспорт','справк','снилс','что нужно','какие нужн']))return'documents';
    if(m(['пск','полная стоим']))return'rate_info';
    if(m(['первый','под 0','бесплатн','без процент','0%']))return'first_free';
    if(m(['без отказ','отказали','плохая истор','не одобр','отказ']))return'no_refuse';
    if(m(['как оформ','как получ','как взять','как заказ','пошагов','инструкц']))return'howto';
    if(m(['стать','блог','почитать','полезн']))return'articles';
    if(m(['все предлож','все займ','каталог','список']))return'all_offers';
    if(m(['кредитн карт','кредитка','грейс']))return'credit_card';
    if(m(['дебетов','кэшбек','кешбек','зарплатн']))return'debit_card';
    if(m(['сравни','сравнить','отличие','разница','vs','или']))return'compare';
    // Поиск по сумме: "10000", "нужно 5000"
    if(/\d{4,}/.test(q)&&m(['нужн','хочу','дай','займ','кредит','сумм','рубл']))return'sum_query';
    if(m(kzSyn['займ']))return'find_offer';
    if(m(kzSyn['кредит']))return'find_offer';
    return'unknown';
}

function kzFindOffer(q){
    var cat=null;
    if(kzMatch(q,kzSyn['кредит']))cat='credits';
    if(!cat)cat='microloans';
    var list=kzOffers.filter(function(o){return o.c===cat;});
    if(!list.length)list=kzOffers.slice(0,3);
    var top=list.slice(0,3);
    kzBot('Вот лучшие '+(cat==='credits'?'кредиты':'займы')+':');
    top.forEach(function(o){kzOfferCard(o);});
    kzQuickBtns(['🆓 Под 0%','📋 Все предложения','🧮 Калькулятор']);
}

function kzFindBySum(q){
    var sumMatch=q.match(/(\d[\d\s]*\d|\d+)/);
    var sum=sumMatch?parseInt(sumMatch[0].replace(/\s/g,'')):0;
    if(!sum){kzFindOffer(q);return;}
    var list=kzOffers.filter(function(o){return o.amin<=sum&&o.amax>=sum;});
    if(!list.length){kzBot('К сожалению, не нашёл предложений на сумму '+kzFmtMoney(sum)+'. Попробуйте изменить сумму.',null,[{t:'Все предложения',u:'/zajmy'}]);return;}
    kzBot('Предложения на сумму '+kzFmtMoney(sum)+':');
    list.slice(0,3).forEach(function(o){kzOfferCard(o);});
}

function kzFirstFree(){
    var list=kzOffers.filter(function(o){return o.free>0;});
    if(!list.length){kzBot('Сейчас нет активных предложений с беспроцентным периодом. Посмотрите все займы:',null,[{t:'Все займы',u:'/zajmy'}]);return;}
    kzBot('Займы под 0% для новых клиентов:');
    list.slice(0,3).forEach(function(o){kzOfferCard(o);});
    kzBot('💡 Условие: вернуть в течение льготного периода — тогда 0% переплаты!');
}

function kzNoRefuse(q){
    kzBot('Советы при отказе:\n\n1. Проверьте кредитную историю в БКИ\n2. Уменьшите запрашиваемую сумму\n3. Попробуйте другие МФО — скоринг у всех разный\n4. Многие МФО работают с любой КИ\n\nВот предложения с высоким одобрением:');
    var list=kzOffers.filter(function(o){return o.c==='microloans';}).slice(0,2);
    list.forEach(function(o){kzOfferCard(o);});
    kzQuickBtns(['🆓 Первый под 0%','📋 Все займы']);
}

function kzCreditCards(q){
    var list=kzOffers.filter(function(o){return o.c==='credit_cards';});
    if(!list.length){kzBot('Кредитные карты позволяют пользоваться деньгами банка бесплатно в льготный период.\n\nСоветы:\n• Выбирайте длинный грейс-период\n• Погашайте до его окончания\n• Обращайте внимание на кэшбек',null,[{t:'Кредитные карты',u:'/karty/kreditnye'}]);return;}
    kzBot('Кредитные карты:');
    list.slice(0,3).forEach(function(o){kzOfferCard(o);});
}

function kzDebitCards(q){
    var list=kzOffers.filter(function(o){return o.c==='debit_cards';});
    if(!list.length){kzBot('Дебетовые карты с кэшбеком помогают экономить на повседневных покупках.\n\nНа что смотреть:\n• Размер кэшбека\n• Процент на остаток\n• Стоимость обслуживания',null,[{t:'Дебетовые карты',u:'/karty/debetovye'}]);return;}
    kzBot('Дебетовые карты:');
    list.slice(0,3).forEach(function(o){kzOfferCard(o);});
}

function kzCompare(q){
    kzBot('Для сравнения предложений рекомендую:\n\n1. Откройте нужный раздел\n2. Используйте фильтры по сумме и сроку\n3. Сравните ПСК (полную стоимость кредита)\n4. Воспользуйтесь калькулятором\n\nПСК показывает реальную переплату!',null,[{t:'Сравнить займы',u:'/zajmy'},{t:'Сравнить кредиты',u:'/kredity'},{t:'Калькулятор',u:'/calculator'}]);
}

function kzHowTo(q){
    kzBot('Как получить займ онлайн:\n\n1️⃣ Выберите предложение на сайте\n2️⃣ Нажмите «Оформить» — перейдёте на сайт МФО\n3️⃣ Заполните заявку (обычно нужен только паспорт)\n4️⃣ Дождитесь одобрения (1-15 минут)\n5️⃣ Деньги поступят на карту\n\n💡 Первый займ во многих МФО — под 0%!');
    kzQuickBtns(['💰 Подобрать займ','🆓 Под 0%','📋 Что нужно из документов?']);
}

function kzSafety(){
    kzBot('🔒 Как проверить безопасность:\n\n1. Проверьте лицензию МФО на сайте ЦБ РФ (cbr.ru)\n2. Никогда не платите «комиссию за одобрение» — это мошенники!\n3. Не отправляйте паспортные данные в мессенджерах\n4. Все организации на нашем сайте проверены\n\n⚠️ Если просят предоплату — это 100% мошенники!',null,[{t:'Наши источники',u:'/sources'},{t:'Политика',u:'/editorial-policy'}]);
}

function kzDocuments(){
    kzBot('📋 Для микрозайма:\n• Паспорт РФ — обязательно\n• СНИЛС/ИНН — иногда\n• Справка о доходах — обычно не нужна\n\n📋 Для банковского кредита:\n• Паспорт\n• Справка 2-НДФЛ или по форме банка\n• Трудовая книжка\n• СНИЛС');
    kzQuickBtns(['💰 Подобрать займ','🏦 Кредиты']);
}

function kzRateInfo(){
    kzBot('📊 ПСК (Полная стоимость кредита) — все расходы заёмщика в % годовых.\n\nВключает:\n• Проценты по займу\n• Комиссии\n• Обязательные страховки\n\nПо закону ПСК не может превышать предел ЦБ РФ.\n\n⚠️ Сравнивайте именно ПСК, а не рекламную ставку!',null,[{t:'Калькулятор',u:'/calculator'},{t:'FAQ',u:'/faq'}]);
}

function kzShowArticles(q){
    if(!kzArts.length){kzBot('В разделе статей много полезных материалов о финансах.',null,[{t:'Статьи',u:'/articles'}]);return;}
    kzBot('Полезные статьи:');
    var c=document.getElementById('kz-msgs');
    kzArts.slice(0,3).forEach(function(a){
        var d=document.createElement('div');
        d.innerHTML='<div class="kz-card"><div class="kz-card-title">'+kzE(a.t)+'</div>'+(a.e?'<div class="kz-card-info">'+kzE(a.e)+'</div>':'')+'<a href="/articles/'+a.s+'" class="kz-card-btn">Читать →</a></div>';
        c.appendChild(d);
    });
    c.scrollTop=c.scrollHeight;
}

function kzFallback(q){
    // Ищем в FAQ
    var best=null,bestScore=0;
    var words=q.split(/\s+/).filter(function(w){return w.length>2;});
    for(var i=0;i<kzFaq.length;i++){
        var fq=kzFaq[i].q.toLowerCase();
        var score=0;
        for(var w=0;w<words.length;w++){
            if(fq.indexOf(words[w])!==-1)score+=words[w].length;
        }
        if(score>bestScore){bestScore=score;best=kzFaq[i];}
    }
    if(best&&bestScore>=5){kzBot(best.a);return;}
    
    // Ищем в статьях
    for(var i=0;i<kzArts.length;i++){
        var at=kzArts[i].t.toLowerCase();
        var aScore=0;
        for(var w=0;w<words.length;w++){
            if(at.indexOf(words[w])!==-1)aScore+=words[w].length;
        }
        if(aScore>=5){kzBot('Возможно, вам будет полезна эта статья:');
            var c=document.getElementById('kz-msgs');
            var d=document.createElement('div');
            d.innerHTML='<div class="kz-card"><div class="kz-card-title">'+kzE(kzArts[i].t)+'</div><a href="/articles/'+kzArts[i].s+'" class="kz-card-btn">Читать →</a></div>';
            c.appendChild(d);c.scrollTop=c.scrollHeight;return;}
    }
    
    kzBot('Не совсем понял вопрос. Попробуйте спросить иначе, или выберите тему:');
    kzQuickBtns(['💰 Займы','🏦 Кредиты','💳 Карты','🧮 Калькулятор','❓ Как оформить?','🔒 Безопасность']);
}

function kzOfferCard(o){
    var c=document.getElementById('kz-msgs');
    var d=document.createElement('div');
    var catLabel={'microloans':'Займ','credits':'Кредит','credit_cards':'Кредитная карта','debit_cards':'Дебетовая карта'};
    var info='<b>'+kzFmtMoney(o.amin)+' — '+kzFmtMoney(o.amax)+'</b>';
    info+=' • ставка '+o.rate+'% в день';
    if(o.free>0)info+=' • <b style="color:#059669">'+o.free+' дн. под 0%</b>';
    if(o.rat>0)info+=' • ⭐ '+o.rat;
    d.innerHTML='<div class="kz-card"><div class="kz-card-title">'+kzE(o.t)+'</div><div class="kz-card-info">'+info+'</div>'+(o.d?'<div class="kz-card-info" style="margin-top:4px;font-size:11px">'+kzE(o.d)+'</div>':'')+'<a href="/offer/'+o.s+'" class="kz-card-btn">Подробнее →</a></div>';
    c.appendChild(d);c.scrollTop=c.scrollHeight;
}

function kzBot(text,cards,links){
    var c=document.getElementById('kz-msgs');
    var d=document.createElement('div');
    var linkHtml='';
    if(links&&links.length){linkHtml='<div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:8px">';links.forEach(function(l){linkHtml+='<a href="'+l.u+'" class="kz-link">'+kzE(l.t)+' →</a>';});linkHtml+='</div>';}
    d.innerHTML='<div style="display:flex;align-items:flex-start;gap:8px"><span style="font-size:20px;flex-shrink:0;margin-top:2px">🤖</span><div style="background:#fff;border:1px solid #e5e7eb;border-radius:0 14px 14px 14px;padding:10px 14px;font-size:14px;line-height:1.6;color:#374151;max-width:88%">'+kzE(text)+linkHtml+'</div></div>';
    c.appendChild(d);c.scrollTop=c.scrollHeight;
}

function kzUser(text){
    var c=document.getElementById('kz-msgs');
    var d=document.createElement('div');
    d.innerHTML='<div style="display:flex;justify-content:flex-end"><div style="background:#1a56db;color:#fff;border-radius:14px 0 14px 14px;padding:10px 14px;font-size:14px;line-height:1.5;max-width:85%">'+kzE(text)+'</div></div>';
    c.appendChild(d);c.scrollTop=c.scrollHeight;
}

function kzMatch(q,keys){for(var i=0;i<keys.length;i++)if(q.indexOf(keys[i])!==-1)return true;return false;}
function kzE(s){var d=document.createElement('div');d.textContent=s;return d.innerHTML.replace(/\n/g,'<br>');}
function kzFmtMoney(n){return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g,' ')+' ₽';}

setTimeout(function(){if(!kzOpen){var b=document.getElementById('kz-badge');if(b)b.style.display='block';setTimeout(function(){if(b&&!kzOpen)b.style.display='none';},8000);}},5000);
</script>
    <?php
    return ob_get_clean();
}
