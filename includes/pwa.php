<?php
function getPwaHeadTags(): string {
    $siteName = getSiteSettings()['site_name'] ?? 'Космозайм';
    return <<<HTML
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#1a56db">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{$siteName}">
    <link rel="apple-touch-icon" href="/images/pwa/icon-152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/pwa/icon-192.png">
HTML;
}

function getPwaInstallBanner(): string {
    return <<<'HTML'
<div id="pwa-install-banner" style="display:none;position:fixed;bottom:0;left:0;right:0;background:linear-gradient(135deg,#1a56db,#7e3af2);padding:16px 20px;z-index:9999;box-shadow:0 -4px 20px rgba(0,0,0,0.15);">
  <div style="max-width:600px;margin:0 auto;display:flex;align-items:center;gap:16px;">
    <div style="width:48px;height:48px;background:white;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;">🚀</div>
    <div style="flex:1;color:white;"><div style="font-weight:700;font-size:15px;">Установите приложение</div><div style="font-size:13px;opacity:0.9;">Быстрый доступ с главного экрана</div></div>
    <button onclick="pwaInstall()" style="background:white;color:#1a56db;border:none;padding:10px 20px;border-radius:10px;font-weight:700;font-size:14px;cursor:pointer;">Установить</button>
    <button onclick="pwaCloseBanner()" style="background:transparent;border:none;color:white;font-size:24px;cursor:pointer;opacity:0.8;">×</button>
  </div>
</div>
<div id="pwa-ios-prompt" style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:9999;">
  <div onclick="pwaCloseIos()" style="position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:9998;"></div>
  <div style="position:relative;z-index:9999;background:white;margin:0 12px 12px;border-radius:20px;padding:24px 20px 20px;box-shadow:0 -8px 40px rgba(0,0,0,0.2);max-width:500px;">
    <button onclick="pwaCloseIos()" style="position:absolute;top:12px;right:16px;background:none;border:none;font-size:22px;color:#9ca3af;cursor:pointer;">✕</button>
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;">
      <img src="/images/pwa/icon-96.png" style="width:56px;height:56px;border-radius:14px;">
      <div><div style="font-weight:700;font-size:17px;color:#111827;">Установите Космозайм</div><div style="font-size:13px;color:#6b7280;">Добавьте на главный экран</div></div>
    </div>
    <div style="background:#f9fafb;border-radius:14px;padding:16px;">
      <div style="display:flex;gap:12px;margin-bottom:14px;"><div style="width:28px;height:28px;background:#1a56db;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:13px;">1</div><div><div style="font-weight:600;font-size:14px;color:#111827;">Нажмите «Поделиться» ⬆️</div></div></div>
      <div style="display:flex;gap:12px;margin-bottom:14px;"><div style="width:28px;height:28px;background:#1a56db;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:13px;">2</div><div><div style="font-weight:600;font-size:14px;color:#111827;">«На экран Домой»</div></div></div>
      <div style="display:flex;gap:12px;"><div style="width:28px;height:28px;background:#10b981;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:13px;">3</div><div><div style="font-weight:600;font-size:14px;color:#111827;">«Добавить» 🎉</div></div></div>
    </div>
  </div>
</div>
HTML;
}

function getPwaScripts(): string {
    return <<<'HTML'
<script>
(function(){
if('serviceWorker' in navigator){navigator.serviceWorker.register('/service-worker.js').catch(function(){});}
var isIos=/iphone|ipad|ipod/i.test(navigator.userAgent),isStandalone=window.navigator.standalone||window.matchMedia('(display-mode:standalone)').matches;
function pwaTrack(e){fetch('/api/pwa-track',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({event:e,standalone:isStandalone?1:0,screenWidth:screen.width,screenHeight:screen.height,url:location.pathname,referrer:document.referrer})}).catch(function(){});}
if(!sessionStorage.getItem('pwa-v')){pwaTrack('visit');sessionStorage.setItem('pwa-v','1');}
var dp,banner=document.getElementById('pwa-install-banner');
window.addEventListener('beforeinstallprompt',function(e){e.preventDefault();dp=e;if(!localStorage.getItem('pwa-closed')){setTimeout(function(){if(banner)banner.style.display='block';pwaTrack('prompt_shown');},3000);}});
window.pwaInstall=function(){if(!dp)return;dp.prompt();dp.userChoice.then(function(r){if(r.outcome==='accepted')pwaTrack('install');dp=null;pwaCloseBanner();});};
window.pwaCloseBanner=function(){if(banner)banner.style.display='none';localStorage.setItem('pwa-closed','1');pwaTrack('prompt_closed');};
window.addEventListener('appinstalled',function(){pwaTrack('install');pwaCloseBanner();});
var iosP=document.getElementById('pwa-ios-prompt');
window.pwaCloseIos=function(){if(iosP)iosP.style.display='none';localStorage.setItem('pwa-ios-closed','1');pwaTrack('prompt_closed');};
if(isIos&&!isStandalone&&!localStorage.getItem('pwa-ios-closed')&&/safari/i.test(navigator.userAgent)&&!/crios|fxios/i.test(navigator.userAgent)){setTimeout(function(){if(iosP)iosP.style.display='block';pwaTrack('prompt_shown');},4000);}
})();
</script>
HTML;
}
