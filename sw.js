const CACHE_NAME = 'farsifahr-pwa-cache-v4';
const urlsToCache = [
  '/assets/css/style.rtl.css',
  '/assets/css/vendor/bootstrap.min.rtl.css',
  '/assets/images/logo/logo-white.png'
];

self.addEventListener('install', event => {
  self.skipWaiting(); // Force the new service worker to activate immediately
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
  );
});

self.addEventListener('fetch', event => {
  if (event.request.mode === 'navigate' || (event.request.method === 'GET' && event.request.headers.get('accept').includes('text/html'))) {
    // Network-first strategy for HTML pages
    event.respondWith(
      fetch(event.request).then(response => {
        return response;
      }).catch(error => {
        return caches.match(event.request);
      })
    );
  } else {
    // Cache-first strategy for other assets
    event.respondWith(
      caches.match(event.request)
        .then(response => {
          if (response) {
            return response;
          }
          return fetch(event.request);
        })
    );
  }
});

self.addEventListener('activate', event => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      return self.clients.claim(); // Take control of all open pages immediately
    })
  );
});
