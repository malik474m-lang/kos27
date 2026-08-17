<?php
function renderChatWidget(): string {
    $settings = function_exists('getSiteSettings') ? getSiteSettings() : [];
    if (empty($settings['chat_enabled'])) return '';
    
    $siteName = e($settings['site_name'] ?? 'Космозайм');
    $title = e($settings['chat_title'] ?? "Помощник {$siteName}");
    $greeting = e($settings['chat_greeting'] ?? "Здравствуйте! Я помощник {$siteName}. Задайте вопрос о займах, кредитах или картах — постараюсь помочь! 😊");

    // Загружаем FAQ из БД для базы знаний
    $faqData = [];
    try {
        $db = getDB();
        $faqs = $db->query("SELECT question, answer FROM offer_faq WHERE 1 ORDER BY id DESC LIMIT 50")->fetchAll();
        foreach ($faqs as $f) {
            $faqData[] = ['q' => $f['question'], 'a' => $f['answer']];
        }
    } catch (Exception $e) {}

    $faqJson = json_encode($faqData, JSON_UNESCAPED_UNICODE);
    
    ob_start();
    ?>
<!-- Chat Widget -->
<div id="kz-chat-btn" onclick="kzToggle()" style="position:fixed;bottom:24px;right:24px;z-index:9998;width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#1a56db,#7e3af2);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 4px 20px rgba(26,86,219,.4);transition:transform .2s;font-size:28px" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'"><span id="kz-ci">💬</span></div>
<div id="kz-badge" style="display:none;position:fixed;bottom:76px;right:24px;z-index:9997;background:#fff;color:#374151;padding:8px 16px;border-radius:12px 12px 0 12px;box-shadow:0 2px 12px rgba(0,0,0,.1);font-size:13px;max-width:220px;cursor:pointer" onclick="kzToggle()">Есть вопрос? Спросите! 🤖</div>
<div id="kz-win" style="display:none;position:fixed;bottom:96px;right:24px;z-index:9999;width:380px;max-width:calc(100vw - 32px);height:520px;max-height:calc(100vh - 120px);background:#fff;border-radius:20px;box-shadow:0 8px 40px rgba(0,0,0,.15);overflow:hidden;flex-direction:column;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif">
<div style="background:linear-gradient(135deg,#1a56db,#7e3af2);color:#fff;padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0">
<div style="display:flex;align-items:center;gap:10px"><span style="font-size:24px">🤖</span><div><div style="font-weight:700;font-size:15px"><?= $title ?></div><div style="font-size:11px;opacity:.8">Помощник • онлайн</div></div></div>
<button onclick="kzToggle()" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:16px">✕</button>
</div>
<div id="kz-msgs" style="flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:12px;background:#f9fafb">
<div style="display:flex;align-items:flex-start;gap:8px"><span style="font-size:20px;flex-shrink:0;margin-top:2px">🤖</span><div style="background:#fff;border:1px solid #e5e7eb;border-radius:0 14px 14px 14px;padding:10px 14px;font-size:14px;line-height:1.5;color:#374151;max-width:85%"><?= $greeting ?></div></div>
<div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:4px" id="kz-quick">
<button onclick="kzAsk(this.textContent)" style="background:#eef2ff;color:#1a56db;border:1px solid #c7d2fe;border-radius:20px;padding:6px 14px;font-size:12px;cursor:pointer">Как получить займ?</button>
<button onclick="kzAsk(this.textContent)" style="background:#eef2ff;color:#1a56db;border:1px solid #c7d2fe;border-radius:20px;padding:6px 14px;font-size:12px;cursor:pointer">Что такое ПСК?</button>
<button onclick="kzAsk(this.textContent)" style="background:#eef2ff;color:#1a56db;border:1px solid #c7d2fe;border-radius:20px;padding:6px 14px;font-size:12px;cursor:pointer">Кредитные карты</button>
<button onclick="kzAsk(this.textContent)" style="background:#eef2ff;color:#1a56db;border:1px solid #c7d2fe;border-radius:20px;padding:6px 14px;font-size:12px;cursor:pointer">Калькулятор</button>
</div>
</div>
<div style="padding:12px 16px;border-top:1px solid #e5e7eb;background:#fff;flex-shrink:0">
<form onsubmit="return kzSend()" style="display:flex;gap:8px">
<input id="kz-inp" type="text" placeholder="Задайте вопрос..." autocomplete="off" style="flex:1;border:1px solid #d1d5db;border-radius:12px;padding:10px 14px;font-size:14px;outline:none" onfocus="this.style.borderColor='#1a56db'" onblur="this.style.borderColor='#d1d5db'">
<button type="submit" style="background:#1a56db;color:#fff;border:none;border-radius:12px;width:44px;height:44px;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;flex-shrink:0">➤</button>
</form>
</div>
</div>
<style>
@media(max-width:480px){#kz-win{bottom:0!important;right:0!important;width:100vw!important;max-width:100vw!important;height:100vh!important;max-height:100vh!important;border-radius:0!important}#kz-chat-btn{bottom:16px!important;right:16px!important;width:52px!important;height:52px!important}}
</style>
<script>
var kzOpen=false;
var kzFaq=<?= $faqJson ?>;
var kzKB=[
{k:['займ','микрозайм','мфо','быстр','деньги','срочн','получить займ','оформ'],a:'Для получения займа:\n1. Выберите предложение на странице /zajmy\n2. Сравните ставки и условия\n3. Нажмите «Оформить» — перейдёте на сайт МФО\n4. Заполните заявку (нужен только паспорт)\n5. Деньги поступят на карту за 5-15 минут\n\nПервый займ во многих МФО — под 0%!',links:[{t:'Все займы',u:'/zajmy'},{t:'Калькулятор',u:'/calculator'}]},
{k:['кредит','банк','ипотек','потребительский','рефинанс'],a:'На нашем сайте вы можете сравнить кредиты от разных банков. Используйте фильтры для подбора по сумме, сроку и ставке.\n\nОбращайте внимание на ПСК (полную стоимость кредита) — она показывает реальную переплату.',links:[{t:'Кредиты',u:'/kredity'},{t:'Статьи о кредитах',u:'/articles'}]},
{k:['кредитн','карт','кредитная карта','льготн','грейс','беспроцентн'],a:'Кредитные карты позволяют пользоваться деньгами банка бесплатно в течение льготного периода (грейс-период).\n\nСоветы:\n• Выбирайте карту с длинным грейс-периодом\n• Обращайте внимание на кэшбек\n• Погашайте долг до окончания льготного периода',links:[{t:'Кредитные карты',u:'/karty/kreditnye'}]},
{k:['дебетов','дебетовая карта','кэшбек','кешбек','остаток'],a:'Дебетовые карты — отличный способ экономить с кэшбеком и процентом на остаток.\n\nНа что смотреть:\n• Размер кэшбека и категории\n• Процент на остаток\n• Стоимость обслуживания\n• Бесплатные переводы',links:[{t:'Дебетовые карты',u:'/karty/debetovye'}]},
{k:['пск','полная стоимость','переплат','процент','ставк'],a:'ПСК (полная стоимость кредита) — это все расходы заёмщика в процентах годовых. Включает проценты, комиссии и обязательные страховки.\n\nПо закону ПСК не может превышать предел, установленный ЦБ РФ. Сравнивайте именно ПСК, а не рекламную ставку!',links:[{t:'Подробнее в FAQ',u:'/faq'}]},
{k:['калькулятор','рассчитать','посчитать','сколько платить','переплата'],a:'Используйте наш калькулятор для расчёта стоимости займа или кредита. Введите сумму и срок — увидите ежемесячный платёж и переплату.',links:[{t:'Открыть калькулятор',u:'/calculator'}]},
{k:['отказ','отказали','плохая история','плохая ки','не одобр'],a:'Если отказали в займе:\n• Проверьте кредитную историю через БКИ\n• Попробуйте уменьшить запрашиваемую сумму\n• Обратитесь в другую МФО — у каждой свой скоринг\n• Многие МФО работают с любой кредитной историей\n\nНа нашем сайте есть фильтр «без отказа».',links:[{t:'Займы без отказа',u:'/zajmy'}]},
{k:['документ','паспорт','справк','снилс','инн','что нужно'],a:'Для микрозайма обычно нужен только паспорт РФ. Некоторые МФО просят СНИЛС или ИНН.\n\nДля банковского кредита могут потребоваться:\n• Паспорт\n• Справка о доходах (2-НДФЛ)\n• Трудовая книжка\n• СНИЛС',links:[{t:'FAQ',u:'/faq'}]},
{k:['безопасн','мошенник','обман','проверить','лицензи','реестр','цб'],a:'Как проверить надёжность МФО или банка:\n1. Проверьте лицензию в реестре ЦБ РФ (cbr.ru)\n2. Никогда не платите «комиссию за одобрение» — это мошенники!\n3. Не отправляйте паспортные данные в мессенджерах\n4. Все организации на нашем сайте проверены и имеют лицензию ЦБ.',links:[{t:'Источники',u:'/sources'},{t:'Политика',u:'/editorial-policy'}]},
{k:['сравни','выбрать','лучш','рейтинг','топ','подобрать'],a:'Для подбора лучшего предложения:\n1. Определите нужную сумму и срок\n2. Используйте фильтры на странице предложений\n3. Сравните ПСК и условия\n4. Воспользуйтесь калькулятором\n5. Читайте отзывы других заёмщиков',links:[{t:'Сравнить займы',u:'/zajmy'},{t:'Сравнить кредиты',u:'/kredity'}]},
{k:['привет','здравствуй','добр','хай','hello'],a:'Здравствуйте! 😊 Я помощник сайта. Могу подсказать:\n• Как получить займ или кредит\n• Как выбрать карту\n• Как пользоваться калькулятором\n• Ответить на вопросы о ПСК и ставках\n\nЗадайте вопрос!'},
{k:['спасибо','благодар','помог'],a:'Рад помочь! 😊 Если возникнут ещё вопросы — обращайтесь. Удачного выбора!'},
{k:['контакт','связ','написать','позвон','email','почта'],a:'Вы можете связаться с нами через страницу контактов.',links:[{t:'Контакты',u:'/contact'}]},
{k:['стать','блог','полезн','почитать','информац'],a:'В разделе статей собраны полезные материалы о финансах: как выбрать займ, на что обратить внимание, советы по управлению долгами.',links:[{t:'Все статьи',u:'/articles'}]},
];

function kzToggle(){kzOpen=!kzOpen;document.getElementById('kz-win').style.display=kzOpen?'flex':'none';document.getElementById('kz-badge').style.display='none';document.getElementById('kz-ci').textContent=kzOpen?'✕':'💬';if(kzOpen){setTimeout(function(){document.getElementById('kz-inp').focus();},100);var m=document.getElementById('kz-msgs');m.scrollTop=m.scrollHeight;}}
function kzAsk(t){document.getElementById('kz-inp').value=t;kzSend();var q=document.getElementById('kz-quick');if(q)q.style.display='none';}
function kzSend(){var inp=document.getElementById('kz-inp');var msg=inp.value.trim();if(!msg)return false;inp.value='';kzAddMsg('user',msg);setTimeout(function(){var reply=kzFind(msg);kzAddMsg('bot',reply.text,reply.links);},400+Math.random()*600);return false;}
function kzFind(q){var ql=q.toLowerCase().replace(/[?!.,;:]/g,'');var best=null;var bestScore=0;
// Сначала ищем в базе знаний
for(var i=0;i<kzKB.length;i++){var entry=kzKB[i];var score=0;for(var j=0;j<entry.k.length;j++){if(ql.indexOf(entry.k[j])!==-1)score+=entry.k[j].length;}if(score>bestScore){bestScore=score;best=entry;}}
// Потом ищем в FAQ из БД
if(bestScore<4&&kzFaq.length){for(var i=0;i<kzFaq.length;i++){var fq=kzFaq[i].q.toLowerCase();var words=ql.split(/\s+/);var fScore=0;for(var w=0;w<words.length;w++){if(words[w].length>2&&fq.indexOf(words[w])!==-1)fScore+=words[w].length;}if(fScore>bestScore){bestScore=fScore;best={a:kzFaq[i].a,links:[]};}}
}
if(best&&bestScore>=3)return{text:best.a,links:best.links||[]};
return{text:'К сожалению, я не нашёл точного ответа на ваш вопрос. Попробуйте:\n• Переформулировать вопрос\n• Посмотреть раздел FAQ\n• Связаться с нами через страницу контактов\n\nЯ хорошо отвечаю на вопросы о займах, кредитах, картах и работе сайта.',links:[{t:'FAQ',u:'/faq'},{t:'Контакты',u:'/contact'}]};}
function kzAddMsg(type,text,links){var c=document.getElementById('kz-msgs');var d=document.createElement('div');var html='';
if(type==='user'){html='<div style="display:flex;justify-content:flex-end"><div style="background:#1a56db;color:#fff;border-radius:14px 0 14px 14px;padding:10px 14px;font-size:14px;line-height:1.5;max-width:85%">'+kzE(text)+'</div></div>';}
else{var linkHtml='';if(links&&links.length){linkHtml='<div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px">';for(var i=0;i<links.length;i++){linkHtml+='<a href="'+links[i].u+'" style="background:#eef2ff;color:#1a56db;border:1px solid #c7d2fe;border-radius:8px;padding:4px 12px;font-size:12px;text-decoration:none;font-weight:500">'+kzE(links[i].t)+' →</a>';}linkHtml+='</div>';}
html='<div style="display:flex;align-items:flex-start;gap:8px"><span style="font-size:20px;flex-shrink:0;margin-top:2px">🤖</span><div style="background:#fff;border:1px solid #e5e7eb;border-radius:0 14px 14px 14px;padding:10px 14px;font-size:14px;line-height:1.5;color:#374151;max-width:85%">'+kzE(text)+linkHtml+'</div></div>';}
d.innerHTML=html;c.appendChild(d);c.scrollTop=c.scrollHeight;}
function kzE(s){var d=document.createElement('div');d.textContent=s;return d.innerHTML.replace(/\n/g,'<br>');}
setTimeout(function(){if(!kzOpen){var b=document.getElementById('kz-badge');if(b)b.style.display='block';setTimeout(function(){if(b&&!kzOpen)b.style.display='none';},8000);}},5000);
</script>
    <?php
    return ob_get_clean();
}
