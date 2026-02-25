/**
 * Hybrid Service Worker: Firebase (Android/Chrome) + Standard Web Push (iOS/Safari/Firefox)
 * 
 * KEY DESIGN:
 * - On Chrome/Android: Firebase SDK handles background FCM messages automatically.
 *   Our 'push' listener MUST skip FCM payloads to avoid double notifications.
 * - On iOS/Safari/Firefox: Standard Web Push via VAPID. Our 'push' listener handles everything.
 */

// ==================== Message listener (MUST be at top level) ====================
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

// ==================== Firebase initialization (Chrome/Android only) ====================
// IMPORTANT: Do NOT load Firebase on Safari/iOS — it would set firebaseInitialized=true
// and then the push listener would skip showing notifications (thinking Firebase handles them).
// Safari/iOS must use the standard Web Push path.
let firebaseInitialized = false;

// Detect Safari/iOS in Service Worker context
const isIOSOrSafari = /Safari/.test(self.navigator?.userAgent || '') && !/Chrome/.test(self.navigator?.userAgent || '');

if (!isIOSOrSafari) {
    try {
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
        firebaseInitialized = true;
        console.log('[SW] Firebase initialized for background messaging');
    } catch (e) {
        console.log('[SW] Firebase not available — using standard Web Push only', e.message);
    }
} else {
    console.log('[SW] Safari/iOS detected — using standard Web Push only (no Firebase)');
}

// ==================== Push event (Web Push for iOS/Safari/Firefox) ====================
self.addEventListener('push', (event) => {
    // On Chrome with Firebase: FCM sends push events that Firebase's
    // onBackgroundMessage handler already displays. We MUST skip these
    // to avoid showing a duplicate notification.
    if (firebaseInitialized) {
        // When Firebase is active, it handles ALL push events on Chrome.
        // We don't need to show anything — Firebase does it automatically.
        console.log('[SW] Firebase active — skipping push event (Firebase handles it)');
        return;
    }

    // Non-Firebase browsers (iOS Safari, Firefox, etc.) — we handle the notification
    let data = { title: 'Notificación', body: '', icon: '/apple-touch-icon.png', url: '/' };
    
    if (event.data) {
        try {
            const payload = event.data.json();
            data = {
                title: payload.title || data.title,
                body: payload.body || data.body,
                icon: payload.icon || data.icon,
                url: payload.url || data.url,
            };
        } catch (e) {
            data.body = event.data.text() || '';
        }
    }

    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: data.icon,
            badge: '/apple-touch-icon.png',
            data: { url: data.url },
        })
    );
});

// ==================== Notification click ====================
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = new URL(event.notification.data?.url || '/', self.location.origin).href;

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                const clientUrl = client.url.replace(/\/$/, "");
                const targetUrlClean = targetUrl.replace(/\/$/, "");

                if (clientUrl === targetUrlClean && 'focus' in client) {
                    return client.focus();
                }
            }

            if (clientList.length > 0) {
                const client = clientList[0];
                if ('navigate' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }

            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});

// ==================== Caching ====================
const CACHE_NAME = 'sm-app-shell-v6';
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

    event.respondWith(
        caches.match(request).then(cached => {
            if (cached) return cached;
            return fetch(request).then(resp => {
                const isStatic = request.destination === 'style' || request.destination === 'script' || request.destination === 'image';

                if (isStatic) {
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