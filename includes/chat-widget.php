<?php
function renderChatWidget(): string {
    $settings = function_exists('getSiteSettings') ? getSiteSettings() : [];
    if (empty($settings['chat_enabled'])) return '';
    
    $siteName = $settings['site_name'] ?? 'Космозайм';
    $title = $settings['chat_title'] ?? "Помощник {$siteName}";
    $greeting = $settings['chat_greeting'] ?? "Здравствуйте! Я AI-помощник {$siteName}. Задайте вопрос о займах, кредитах или картах — помогу разобраться! 😊";
    
    ob_start();
    ?>
<!-- AI Chat Widget -->
<div id="kz-chat-btn" onclick="kzChatToggle()" style="position:fixed;bottom:24px;right:24px;z-index:9998;width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#1a56db,#7e3af2);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 4px 20px rgba(26,86,219,.4);transition:transform .2s;font-size:28px" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
    <span id="kz-chat-icon">💬</span>
</div>
<div id="kz-chat-badge" style="display:none;position:fixed;bottom:76px;right:24px;z-index:9997;background:#fff;color:#374151;padding:8px 16px;border-radius:12px 12px 0 12px;box-shadow:0 2px 12px rgba(0,0,0,.1);font-size:13px;max-width:220px;cursor:pointer;animation:kzBadgeFade 0.3s" onclick="kzChatToggle()">Есть вопрос? Спросите AI 🤖</div>

<div id="kz-chat-window" style="display:none;position:fixed;bottom:96px;right:24px;z-index:9999;width:380px;max-width:calc(100vw - 32px);height:520px;max-height:calc(100vh - 120px);background:#fff;border-radius:20px;box-shadow:0 8px 40px rgba(0,0,0,.15);overflow:hidden;flex-direction:column;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif">
    <!-- Header -->
    <div style="background:linear-gradient(135deg,#1a56db,#7e3af2);color:#fff;padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0">
        <div style="display:flex;align-items:center;gap:10px">
            <span style="font-size:24px">🤖</span>
            <div>
                <div style="font-weight:700;font-size:15px"><?= htmlspecialchars($title) ?></div>
                <div style="font-size:11px;opacity:.8">AI-ассистент • онлайн</div>
            </div>
        </div>
        <button onclick="kzChatToggle()" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center">✕</button>
    </div>
    <!-- Messages -->
    <div id="kz-chat-msgs" style="flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:12px;background:#f9fafb">
        <div class="kz-msg-ai">
            <div style="display:flex;align-items:flex-start;gap:8px">
                <span style="font-size:20px;flex-shrink:0;margin-top:2px">🤖</span>
                <div style="background:#fff;border:1px solid #e5e7eb;border-radius:0 14px 14px 14px;padding:10px 14px;font-size:14px;line-height:1.5;color:#374151;max-width:85%"><?= htmlspecialchars($greeting) ?></div>
            </div>
        </div>
    </div>
    <!-- Input -->
    <div style="padding:12px 16px;border-top:1px solid #e5e7eb;background:#fff;flex-shrink:0">
        <form onsubmit="return kzChatSend()" style="display:flex;gap:8px">
            <input id="kz-chat-input" type="text" placeholder="Задайте вопрос..." autocomplete="off" style="flex:1;border:1px solid #d1d5db;border-radius:12px;padding:10px 14px;font-size:14px;outline:none" onfocus="this.style.borderColor='#1a56db'" onblur="this.style.borderColor='#d1d5db'">
            <button type="submit" id="kz-chat-send" style="background:#1a56db;color:#fff;border:none;border-radius:12px;width:44px;height:44px;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background .2s" onmouseover="this.style.background='#1244af'" onmouseout="this.style.background='#1a56db'">➤</button>
        </form>
        <div style="text-align:center;margin-top:6px;font-size:10px;color:#9ca3af">Powered by AI • ответы могут содержать неточности</div>
    </div>
</div>

<style>
@keyframes kzBadgeFade{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
@keyframes kzTyping{0%,100%{opacity:.3}50%{opacity:1}}
.kz-typing span{display:inline-block;width:6px;height:6px;background:#9ca3af;border-radius:50%;animation:kzTyping 1.2s infinite}
.kz-typing span:nth-child(2){animation-delay:.2s}
.kz-typing span:nth-child(3){animation-delay:.4s}
@media(max-width:480px){
    #kz-chat-window{bottom:0!important;right:0!important;width:100vw!important;max-width:100vw!important;height:100vh!important;max-height:100vh!important;border-radius:0!important}
    #kz-chat-btn{bottom:16px!important;right:16px!important;width:52px!important;height:52px!important}
}
</style>

<script>
var kzChatHistory=[];
var kzChatOpen=false;

function kzChatToggle(){
    var w=document.getElementById('kz-chat-window');
    var b=document.getElementById('kz-chat-badge');
    kzChatOpen=!kzChatOpen;
    w.style.display=kzChatOpen?'flex':'none';
    if(b)b.style.display='none';
    document.getElementById('kz-chat-icon').textContent=kzChatOpen?'✕':'💬';
    if(kzChatOpen){
        setTimeout(function(){document.getElementById('kz-chat-input').focus();},100);
        var m=document.getElementById('kz-chat-msgs');
        m.scrollTop=m.scrollHeight;
    }
}

function kzChatSend(){
    var inp=document.getElementById('kz-chat-input');
    var msg=inp.value.trim();
    if(!msg)return false;
    inp.value='';
    
    // Добавляем сообщение пользователя
    kzAddMsg('user',msg);
    kzChatHistory.push({role:'user',content:msg});
    
    // Показываем typing
    var typingId='typing-'+Date.now();
    kzAddTyping(typingId);
    
    // Блокируем кнопку
    var btn=document.getElementById('kz-chat-send');
    btn.disabled=true;btn.style.opacity='0.5';
    
    fetch('/api/chat',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({message:msg,history:kzChatHistory})
    }).then(function(r){return r.json();}).then(function(d){
        kzRemoveTyping(typingId);
        btn.disabled=false;btn.style.opacity='1';
        if(d.success&&d.reply){
            kzAddMsg('ai',d.reply);
            kzChatHistory.push({role:'assistant',content:d.reply});
        }else{
            kzAddMsg('ai',d.error||'Извините, произошла ошибка. Попробуйте позже.');
        }
    }).catch(function(){
        kzRemoveTyping(typingId);
        btn.disabled=false;btn.style.opacity='1';
        kzAddMsg('ai','Ошибка соединения. Проверьте интернет и попробуйте снова.');
    });
    return false;
}

function kzAddMsg(type,text){
    var c=document.getElementById('kz-chat-msgs');
    var d=document.createElement('div');
    if(type==='user'){
        d.innerHTML='<div style="display:flex;justify-content:flex-end"><div style="background:#1a56db;color:#fff;border-radius:14px 0 14px 14px;padding:10px 14px;font-size:14px;line-height:1.5;max-width:85%">'+kzEsc(text)+'</div></div>';
    }else{
        d.innerHTML='<div style="display:flex;align-items:flex-start;gap:8px"><span style="font-size:20px;flex-shrink:0;margin-top:2px">🤖</span><div style="background:#fff;border:1px solid #e5e7eb;border-radius:0 14px 14px 14px;padding:10px 14px;font-size:14px;line-height:1.5;color:#374151;max-width:85%">'+kzEsc(text)+'</div></div>';
    }
    c.appendChild(d);
    c.scrollTop=c.scrollHeight;
}

function kzAddTyping(id){
    var c=document.getElementById('kz-chat-msgs');
    var d=document.createElement('div');
    d.id=id;
    d.innerHTML='<div style="display:flex;align-items:flex-start;gap:8px"><span style="font-size:20px;flex-shrink:0;margin-top:2px">🤖</span><div style="background:#fff;border:1px solid #e5e7eb;border-radius:0 14px 14px 14px;padding:12px 18px" class="kz-typing"><span></span> <span></span> <span></span></div></div>';
    c.appendChild(d);
    c.scrollTop=c.scrollHeight;
}

function kzRemoveTyping(id){
    var el=document.getElementById(id);
    if(el)el.remove();
}

function kzEsc(s){
    var d=document.createElement('div');
    d.textContent=s;
    return d.innerHTML.replace(/\n/g,'<br>');
}

// Показываем badge через 5 секунд
setTimeout(function(){
    if(!kzChatOpen){
        var b=document.getElementById('kz-chat-badge');
        if(b)b.style.display='block';
        setTimeout(function(){if(b&&!kzChatOpen)b.style.display='none';},8000);
    }
},5000);
</script>
<!-- /AI Chat Widget -->
    <?php
    return ob_get_clean();
}
