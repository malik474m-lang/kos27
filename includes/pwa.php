<?php
/**
 * PWA компоненты для Космозайм
 * Подключается в layout.php
 */

function getPwaHeadTags(): string {
    $siteSettings = getSiteSettings();
    $siteName = $siteSettings['site_name'] ?? 'Космозайм';
    
    return <<<HTML
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#1a56db">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{$siteName}">
    
    <!-- iOS Icons -->
    <link rel="apple-touch-icon" href="/images/pwa/icon-152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/pwa/icon-192.png">
    <link rel="apple-touch-icon" sizes="167x167" href="/images/pwa/icon-152.png">
HTML;
}

function getPwaInstallBanner(): string {
    return <<<'HTML'
<!-- PWA Install Banner (Android Chrome) -->
<div id="pwa-install-banner" style="display:none;position:fixed;bottom:0;left:0;right:0;background:linear-gradient(135deg,#1a56db,#7e3af2);padding:16px 20px;z-index:9999;box-shadow:0 -4px 20px rgba(0,0,0,0.15);">
  <div style="max-width:600px;margin:0 auto;display:flex;align-items:center;gap:16px;">
    <div style="width:48px;height:48px;background:white;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;">🚀</div>
    <div style="flex:1;color:white;">
      <div style="font-weight:700;font-size:15px;margin-bottom:2px;">Установите приложение</div>
      <div style="font-size:13px;opacity:0.9;">Быстрый доступ с главного экрана</div>
    </div>
    <button onclick="pwaInstall()" style="background:white;color:#1a56db;border:none;padding:10px 20px;border-radius:10px;font-weight:700;font-size:14px;cursor:pointer;white-space:nowrap;">Установить</button>
    <button onclick="pwaCloseBanner()" style="background:transparent;border:none;color:white;font-size:24px;cursor:pointer;padding:4px;opacity:0.8;">×</button>
  </div>
</div>

<!-- PWA Install Prompt (iOS Safari) -->
<div id="pwa-ios-prompt" style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:9999;">
  <!-- Затемнение фона -->
  <div onclick="pwaCloseIos()" style="position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:9998;"></div>
  <!-- Карточка -->
  <div style="position:relative;z-index:9999;background:white;margin:0 12px 12px;border-radius:20px;padding:24px 20px 20px;box-shadow:0 -8px 40px rgba(0,0,0,0.2);max-width:500px;">
    <!-- Стрелка вниз указывающая на кнопку Share -->
    <div style="position:absolute;bottom:-10px;left:50%;transform:translateX(-50%);width:0;height:0;border-left:12px solid transparent;border-right:12px solid transparent;border-top:12px solid white;"></div>
    <!-- Закрыть -->
    <button onclick="pwaCloseIos()" style="position:absolute;top:12px;right:16px;background:none;border:none;font-size:22px;color:#9ca3af;cursor:pointer;">✕</button>
    <!-- Контент -->
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;">
      <img src="/images/pwa/icon-96.png" alt="" style="width:56px;height:56px;border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
      <div>
        <div style="font-weight:700;font-size:17px;color:#111827;">Установите Космозайм</div>
        <div style="font-size:13px;color:#6b7280;margin-top:2px;">Добавьте на главный экран</div>
      </div>
    </div>
    <!-- Шаги -->
    <div style="background:#f9fafb;border-radius:14px;padding:16px;">
      <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:14px;">
        <div style="width:28px;height:28px;background:#1a56db;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:13px;flex-shrink:0;">1</div>
        <div>
          <div style="font-weight:600;font-size:14px;color:#111827;">Нажмите кнопку «Поделиться»</div>
          <div style="font-size:13px;color:#6b7280;margin-top:2px;">Внизу экрана нажмите <span style="display:inline-block;background:white;border:1px solid #e5e7eb;border-radius:6px;padding:1px 6px;font-size:16px;vertical-align:middle;">⬆️</span></div>
        </div>
      </div>
      <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:14px;">
        <div style="width:28px;height:28px;background:#1a56db;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:13px;flex-shrink:0;">2</div>
        <div>
          <div style="font-weight:600;font-size:14px;color:#111827;">Выберите «На экран Домой»</div>
          <div style="font-size:13px;color:#6b7280;margin-top:2px;">Прокрутите меню и нажмите <span style="display:inline-block;background:white;border:1px solid #e5e7eb;border-radius:6px;padding:1px 6px;font-size:16px;vertical-align:middle;">➕</span> На экран «Домой»</div>
        </div>
      </div>
      <div style="display:flex;align-items:flex-start;gap:12px;">
        <div style="width:28px;height:28px;background:#10b981;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:13px;flex-shrink:0;">3</div>
        <div>
          <div style="font-weight:600;font-size:14px;color:#111827;">Нажмите «Добавить»</div>
          <div style="font-size:13px;color:#6b7280;margin-top:2px;">Иконка появится на главном экране 🎉</div>
        </div>
      </div>
    </div>
  </div>
</div>
HTML;
}

function getPwaScripts(): string {
    return <<<'HTML'
<!-- PWA Scripts -->
<script>
// Регистрация Service Worker
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/service-worker.js')
      .then(reg => console.log('SW registered:', reg.scope))
      .catch(err => console.log('SW registration failed:', err));
  });
}

// === Определение платформы ===
var isIos = /iphone|ipad|ipod/.test(navigator.userAgent.toLowerCase());
var isSafari = isIos && /safari/i.test(navigator.userAgent) && !/crios|fxios|opios/i.test(navigator.userAgent);
var isStandalone = window.navigator.standalone === true || window.matchMedia('(display-mode: standalone)').matches;

// === Android: Install Prompt ===
let deferredPrompt;
const pwaBanner = document.getElementById('pwa-install-banner');

window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredPrompt = e;
  if (!localStorage.getItem('pwa-banner-closed')) {
    setTimeout(() => { if(pwaBanner) pwaBanner.style.display = 'block'; }, 3000);
  }
});

function pwaInstall() {
  if (!deferredPrompt) return;
  deferredPrompt.prompt();
  deferredPrompt.userChoice.then((result) => {
    if (result.outcome === 'accepted') console.log('PWA installed');
    deferredPrompt = null;
    pwaCloseBanner();
  });
}

function pwaCloseBanner() {
  if(pwaBanner) pwaBanner.style.display = 'none';
  localStorage.setItem('pwa-banner-closed', '1');
}

window.addEventListener('appinstalled', () => {
  pwaCloseBanner();
});

// === iOS Safari: подсказка ===
var iosPrompt = document.getElementById('pwa-ios-prompt');

function pwaShowIos() {
  if(iosPrompt) iosPrompt.style.display = 'block';
}

function pwaCloseIos() {
  if(iosPrompt) iosPrompt.style.display = 'none';
  localStorage.setItem('pwa-ios-closed', '1');
}

// Показываем iOS подсказку если: это Safari, не standalone, не закрывали
if (isIos && !isStandalone && !localStorage.getItem('pwa-ios-closed')) {
  setTimeout(pwaShowIos, 4000);
}
</script>
HTML;
}
