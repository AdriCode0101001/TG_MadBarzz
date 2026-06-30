const CACHE_NAME = 'madbarzz-cache-v2';

const urlsToCache = [
  '/manifest.json',
  '/assets_private/images/favicon.png',
  '/dashboard/cliente',
  '/mis-asistencias',
  '/mis-pagos',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(urlsToCache))
      .catch((err) => console.error('[SW] Error en caché:', err))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
    ).then(() => clients.claim())
  );
});

// Network-first para rutas de portal; cache-first para assets estáticos
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  const portalRoutes = ['/dashboard/cliente', '/mis-asistencias', '/mis-pagos'];
  const isPortalRoute = portalRoutes.some((r) => url.pathname.startsWith(r));

  if (isPortalRoute) {
    // Network-first: intenta red y si falla usa caché
    event.respondWith(
      fetch(event.request)
        .then((response) => {
          const clone = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
          return response;
        })
        .catch(() => caches.match(event.request))
    );
  } else {
    // Cache-first para assets
    event.respondWith(
      caches.match(event.request).then((response) => response || fetch(event.request))
    );
  }
});

self.addEventListener('push', (event) => {
  let data = {
    title: 'MadBarzz',
    body: 'Tienes una notificación nueva.',
    icon: '/assets_private/images/favicon.png',
  };

  if (event.data) {
    try { data = event.data.json(); } catch (_) {}
  }

  event.waitUntil(
    self.registration.showNotification(data.title, {
      body: data.body,
      icon: data.icon,
    })
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  event.waitUntil(clients.openWindow('/dashboard/cliente'));
});
