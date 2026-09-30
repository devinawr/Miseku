/**
 * Service Worker Miseku
 * ---------------------
 * - Aset statis (gambar, ikon, manifest) -> cache-first, supaya akses berikutnya instan.
 * - Halaman HTML -> network-first dengan fallback halaman offline.
 * - Request Livewire (/livewire/update) sengaja TIDAK di-cache karena selalu butuh data terbaru.
 */
const CACHE = 'miseku-v1';

const PRECACHE = [
    '/offline.html',
    '/manifest.json',
    '/img/logo.png',
    '/img/icons/icon-192.png',
    '/img/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;
    if (url.pathname.startsWith('/livewire/')) return;

    const isAsset = /\.(?:png|jpg|jpeg|webp|svg|css|js|woff2?|json)$/i.test(url.pathname);

    if (isAsset) {
        event.respondWith(
            caches.match(request).then((hit) => hit || fetch(request).then((response) => {
                const copy = response.clone();
                caches.open(CACHE).then((cache) => cache.put(request, copy));
                return response;
            }).catch(() => hit))
        );
        return;
    }

    event.respondWith(
        fetch(request)
            .then((response) => {
                const copy = response.clone();
                caches.open(CACHE).then((cache) => cache.put(request, copy));
                return response;
            })
            .catch(() => caches.match(request).then((hit) => hit || caches.match('/offline.html')))
    );
});
