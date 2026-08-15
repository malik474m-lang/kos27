// Service Worker для Космозайм PWA
const CACHE_NAME = 'kosmozaim-v2';
const OFFLINE_URL = '/offline.html';

// Файлы для кэширования при установке
const PRECACHE_URLS = [
  '/',
  '/offline.html',
  '/assets/tailwind.css',
  '/assets/site.min.css',
  '/favicon.svg',
  '/manifest.json'
];

// Установка Service Worker
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(PRECACHE_URLS))
      .then(() => self.skipWaiting())
  );
});

// Активация — очистка старых кэшей
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames
          .filter(name => name !== CACHE_NAME)
          .map(name => caches.delete(name))
      );
    }).then(() => self.clients.claim())
  );
});

// Стратегия: сначала сеть, потом кэш (Network First)
self.addEventListener('fetch', event => {
  const { request } = event;
  
  // Пропускаем не-GET запросы и API
  if (request.method !== 'GET') return;
  if (request.url.includes('/api/')) return;
  if (request.url.includes('/admin')) return;
  if (request.url.includes('/click/')) return;
  if (request.url.includes('/download-apk.php')) return;
  if (request.url.includes('/downloads/')) return;
  if (request.url.match(/\.apk($|\?)/)) return;
  
  event.respondWith(
    fetch(request)
      .then(response => {
        // Кэшируем успешные ответы
        if (response.ok) {
          const responseClone = response.clone();
          caches.open(CACHE_NAME).then(cache => {
            cache.put(request, responseClone);
          });
        }
        return response;
      })
      .catch(() => {
        // При ошибке сети — пробуем кэш
        return caches.match(request).then(cachedResponse => {
          if (cachedResponse) {
            return cachedResponse;
          }
          // Если это навигация — показываем offline страницу
          if (request.mode === 'navigate') {
            return caches.match(OFFLINE_URL);
          }
          return new Response('Offline', { status: 503 });
        });
      })
  );
});

// Push-уведомления (на будущее)
self.addEventListener('push', event => {
  if (!event.data) return;
  
  const data = event.data.json();
  const options = {
    body: data.body || 'Новое уведомление',
    icon: '/images/pwa/icon-192.png',
    badge: '/images/pwa/icon-72.png',
    vibrate: [100, 50, 100],
    data: { url: data.url || '/' }
  };
  
  event.waitUntil(
    self.registration.showNotification(data.title || 'Космозайм', options)
  );
});

// Клик по уведомлению
self.addEventListener('notificationclick', event => {
  event.notification.close();
  const url = event.notification.data?.url || '/';
  event.waitUntil(
    clients.openWindow(url)
  );
});
