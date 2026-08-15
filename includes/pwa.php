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
    
    <!-- iOS Splash Screens -->
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
HTML;
}

function getPwaInstallBanner(): string {
    return <<<'HTML'
<!-- PWA Install Banner -->
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

// PWA Install Prompt
let deferredPrompt;
const pwaBanner = document.getElementById('pwa-install-banner');

window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredPrompt = e;
  // Показываем баннер если не закрывали раньше
  if (!localStorage.getItem('pwa-banner-closed')) {
    setTimeout(() => { if(pwaBanner) pwaBanner.style.display = 'block'; }, 3000);
  }
});

function pwaInstall() {
  if (!deferredPrompt) return;
  deferredPrompt.prompt();
  deferredPrompt.userChoice.then((result) => {
    if (result.outcome === 'accepted') {
      console.log('PWA installed');
    }
    deferredPrompt = null;
    pwaCloseBanner();
  });
}

function pwaCloseBanner() {
  if(pwaBanner) pwaBanner.style.display = 'none';
  localStorage.setItem('pwa-banner-closed', '1');
}

// Скрываем баннер если уже установлено
window.addEventListener('appinstalled', () => {
  pwaCloseBanner();
  console.log('PWA was installed');
});
</script>
HTML;
}
