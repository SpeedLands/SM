importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js');

const firebaseConfig = {
    apiKey: "AIzaSyDrMr4T9g9eUub_LDYcs27vp5aE6tolB8I",
    authDomain: "educom-24ee8.firebaseapp.com",
    projectId: "educom-24ee8",
    storageBucket: "educom-24ee8.firebasestorage.app",
    messagingSenderId: "977130140369",
    appId: "1:977130140369:web:75a5296cab81caa5c28bf0",
    measurementId: "G-JD1JYBKQ4Y"
};

firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();



self.addEventListener('notificationclick', (event) => {
    console.log('[sw.js] Notification click Received.', event);
    event.notification.close();

    // Prioritize URL from data payload, fallback to root
    const targetUrl = new URL(event.notification.data.url || '/', self.location.origin).href;

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            // Check if there is already a window open with the target URL
            for (const client of clientList) {
                // Check if the URL matches (ignoring trailing slash for better matching)
                const clientUrl = client.url.replace(/\/$/, "");
                const targetUrlClean = targetUrl.replace(/\/$/, "");

                if (clientUrl === targetUrlClean && 'focus' in client) {
                    return client.focus();
                }
            }

            // If no exact match, but there's any window open, navigate it
            if (clientList.length > 0) {
                const client = clientList[0];
                if ('navigate' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }

            // Otherwise, open a new window
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});

const CACHE_NAME = 'sm-app-shell-v4';
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

    if (request.destination === 'manifest' || request.url.endsWith('/manifest.json') || request.url.endsWith('/manifest.webmanifest')) {
        return;
    }

    if (request.method !== 'GET' || request.headers.get('accept')?.includes('application/json') || request.url.includes('/api/')) {
        return event.respondWith(fetch(request).catch(() => new Response('Offline', { status: 503, statusText: 'Offline' })));
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then(response => {
                    const copy = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(request, copy));
                    return response;
                })
                .catch(() => {
                    return caches.match(request).then(cached => {
                        if (cached) return cached;
                        return new Response('', { status: 503, statusText: 'Offline' });
                    });
                })
        );
        return;
    }

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

    event.respondWith(
        caches.match(request).then(cached => {
            if (cached) return cached;
            return fetch(request).then(resp => {
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