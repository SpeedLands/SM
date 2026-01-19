const CACHE_NAME = 'sm-app-shell-v3';
const PRECACHE_URLS = [
    '/',
    '/favicon.ico',
    '/apple-touch-icon.png'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(PRECACHE_URLS)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => Promise.all(
            keys.map(key => {
                if (key !== CACHE_NAME) {
                    return caches.delete(key);
                }
            })
        )).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', event => {
    const request = event.request;

    // Do not intercept manifest requests — let the browser fetch them directly
    if (request.destination === 'manifest' || request.url.endsWith('/manifest.json') || request.url.endsWith('/manifest.webmanifest')) {
        return; // no event.respondWith so the browser performs the network request
    }

    // Don't handle non-GET requests or API/json endpoints via the SW cache
    if (request.method !== 'GET' || request.headers.get('accept')?.includes('application/json') || request.url.includes('/api/')) {
        return event.respondWith(fetch(request).catch(() => new Response('Offline', { status: 503, statusText: 'Offline' })));
    }

    // Navigation requests: NETWORK-FIRST strategy to ensure latest HTML/CSS/JS
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then(response => {
                    // Cache fetched page for future offline visits
                    const copy = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(request, copy));
                    return response;
                })
                .catch(() => {
                    // If network fails, try to return from cache
                    return caches.match(request).then(cached => {
                        if (cached) return cached;
                        // If nothing cached and network fails, return a 503 empty response
                        return new Response('', { status: 503, statusText: 'Offline' });
                    });
                })
        );
        return;
    }

    // Message handler for cache clearing
    self.addEventListener('message', event => {
        if (event.data && event.data.type === 'CLEAR_CACHE') {
            console.log('[SW] Clearing all caches...');
            event.waitUntil(
                caches.keys().then(keys => Promise.all(
                    keys.map(key => caches.delete(key))
                )).then(() => {
                    console.log('[SW] Caches cleared.');
                })
            );
        }
    });

    // Static assets and AJAX navigation: cache-first for offline speed
    event.respondWith(
        caches.match(request).then(cached => {
            if (cached) return cached;
            return fetch(request).then(resp => {
                // Cache static assets and HTML responses
                const isHtml = resp.headers.get('content-type')?.includes('text/html');
                const isStatic = request.destination === 'style' || request.destination === 'script' || request.destination === 'image';

                if (isStatic || (isHtml && request.method === 'GET')) {
                    const copy = resp.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(request, copy));
                }
                return resp;
            }).catch(() => {
                if (request.destination === 'document') {
                    return caches.match('/offline.html');
                }
            });
        })
    );
});