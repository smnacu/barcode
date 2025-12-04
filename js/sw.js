/**
 * Service Worker - Cache PWA mejorado
 * Estrategia: Network-first para HTML/JS, Cache-first para assets estaticos
 */

var CACHE_NAME = 'barcodeC-v3';

var urlsToCache = [
    './',
    './index.html',
    './admin.html',
    './manifest.json',
    './js/app.js',
    './js/libs/html5-qrcode.min.js',
    './img/logo_bw.png',
    './img/logo.png'
];

// Instalacion
self.addEventListener('install', function (event) {
    console.log('[SW] Instalando v3...');
    event.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) {
            return Promise.all(
                urlsToCache.map(function (url) {
                    return cache.add(url).catch(function (err) {
                        console.warn('[SW] No se pudo cachear:', url);
                    });
                })
            );
        }).then(function () {
            console.log('[SW] Cache completado');
            return self.skipWaiting();
        })
    );
});

// Activacion - limpiar caches viejos
self.addEventListener('activate', function (event) {
    console.log('[SW] Activando...');
    event.waitUntil(
        caches.keys().then(function (cacheNames) {
            return Promise.all(
                cacheNames.filter(function (cacheName) {
                    return cacheName !== CACHE_NAME;
                }).map(function (cacheName) {
                    console.log('[SW] Borrando cache viejo:', cacheName);
                    return caches.delete(cacheName);
                })
            );
        }).then(function () {
            console.log('[SW] Listo');
            return self.clients.claim();
        })
    );
});

// Fetch
self.addEventListener('fetch', function (event) {
    var request = event.request;

    // Solo GET
    if (request.method !== 'GET') return;

    // APIs siempre desde red
    if (request.url.indexOf('/api/') !== -1) {
        event.respondWith(
            fetch(request).catch(function () {
                return new Response(JSON.stringify({ error: true, mensaje: 'Sin conexion' }), {
                    headers: { 'Content-Type': 'application/json' }
                });
            })
        );
        return;
    }

    // HTML y JS: Network-first (siempre intentar actualizar)
    if (request.url.indexOf('.html') !== -1 || request.url.indexOf('.js') !== -1) {
        event.respondWith(
            fetch(request).then(function (response) {
                if (response && response.status === 200) {
                    var responseToCache = response.clone();
                    caches.open(CACHE_NAME).then(function (cache) {
                        cache.put(request, responseToCache);
                    });
                }
                return response;
            }).catch(function () {
                return caches.match(request);
            })
        );
        return;
    }

    // Otros assets: Cache-first
    event.respondWith(
        caches.match(request).then(function (response) {
            if (response) return response;

            return fetch(request).then(function (response) {
                if (!response || response.status !== 200) {
                    return response;
                }

                var responseToCache = response.clone();
                caches.open(CACHE_NAME).then(function (cache) {
                    cache.put(request, responseToCache);
                });

                return response;
            });
        })
    );
});
