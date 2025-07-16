/**
 * Service Worker - Remmailleuse PWA
 * Gestion du cache et mode hors-ligne
 */

const CACHE_NAME = 'remmailleuse-v1.1.0';
const STATIC_CACHE = 'remmailleuse-static-v1.1';
const DYNAMIC_CACHE = 'remmailleuse-dynamic-v1.1';
const API_CACHE = 'remmailleuse-api-v1.1';

// Ressources critiques à mettre en cache
const CRITICAL_ASSETS = [
    '/',
    '/index.html',
    '/assets/css/main.css',
    '/assets/js/main.js',
    '/assets/js/cache.js',
    '/assets/js/lazy-loader.js',
    '/assets/js/gallery.js',
    '/assets/js/forms.js',
    '/assets/js/animations.js',
    '/assets/js/analytics.js',
    '/data/content.json',
    '/data/services.json',
    '/data/gallery.json',
    '/data/settings.json',
    '/assets/images/icons/icon-192.png',
    '/assets/images/icons/icon-512.png',
    '/manifest.json'
];

// Ressources statiques à mettre en cache
const STATIC_ASSETS = [
    '/assets/images/hero/backgrounds/hero-bg-1.jpg',
    '/assets/images/profile/artisan-profile.jpg',
    '/favicon.ico',
    '/robots.txt',
    '/sitemap.xml'
];

// Installation du Service Worker
self.addEventListener('install', event => {
    // Service Worker installation
    
    event.waitUntil(
        Promise.all([
            // Cache des ressources critiques
            caches.open(CACHE_NAME).then(cache => {
                // Mise en cache des ressources critiques
                return cache.addAll(CRITICAL_ASSETS);
            }),
            // Cache des ressources statiques
            caches.open(STATIC_CACHE).then(cache => {
                // Mise en cache des ressources statiques
                return cache.addAll(STATIC_ASSETS);
            })
        ]).then(() => {
            // Installation terminée
            // Forcer l'activation immédiate
            return self.skipWaiting();
        })
    );
});

// Activation du Service Worker
self.addEventListener('activate', event => {
    // Service Worker activation
    
    event.waitUntil(
        // Nettoyer les anciens caches
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME && 
                        cacheName !== STATIC_CACHE && 
                        cacheName !== DYNAMIC_CACHE &&
                        cacheName !== API_CACHE) {
                        // Suppression ancien cache
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            // Activation terminée
            // Prendre le contrôle immédiatement
            return self.clients.claim();
        })
    );
});

// Interception des requêtes réseau
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);
    
    // Ignorer les requêtes non-HTTP
    if (!request.url.startsWith('http')) {
        return;
    }
    
    // Ignorer complètement les requêtes admin, PHP/API et de développement
    if (url.pathname.startsWith('/admin/') || 
        url.pathname.startsWith('/api/') || 
        url.pathname.endsWith('.php') ||
        url.pathname.includes('modal') ||
        url.pathname.includes('csrf') ||
        url.pathname.includes('auth') ||
        url.pathname.includes('test-') ||
        url.pathname.includes('debug-') ||
        url.hostname === 'localhost' ||
        url.port === '8000') {
        return;
    }
    
    // Stratégie différente selon le type de ressource avec gestion d'erreur
    try {
        if (request.destination === 'document') {
            // Pages HTML : Network First, puis Cache
            event.respondWith(networkFirstStrategy(request));
        } else if (request.destination === 'image') {
            // Images : Cache First, puis Network
            event.respondWith(cacheFirstStrategy(request));
        } else if (url.pathname.startsWith('/data/')) {
            // Données JSON : Network First avec timeout
            event.respondWith(networkFirstWithTimeoutStrategy(request));
        } else if (url.pathname.startsWith('/api/') && request.method === 'GET') {
            // API GET : Cache avec revalidation
            event.respondWith(staleWhileRevalidateStrategy(request));
        } else if (url.pathname.startsWith('/assets/')) {
            // Assets statiques : Cache First
            event.respondWith(cacheFirstStrategy(request));
        } else {
            // Autres requêtes : stratégie par défaut
            event.respondWith(networkFirstStrategy(request));
        }
    } catch (error) {
        console.error('Erreur dans le gestionnaire fetch:', error);
        // Ne pas intervenir en cas d'erreur
        return;
    }
});

// Stratégie Network First
async function networkFirstStrategy(request) {
    try {
        // Essayer le réseau en premier
        const networkResponse = await fetch(request);
        
        // Si succès, mettre en cache et retourner
        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            await cache.put(request, networkResponse.clone());
            return networkResponse;
        }
        
        // Si échec réseau, utiliser le cache
        const cachedResponse = await getCachedResponse(request);
        return cachedResponse || networkResponse;
        
    } catch (error) {
        // Erreur réseau, utilisation du cache
        const cachedResponse = await getCachedResponse(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Si pas de cache non plus, retourner une réponse d'erreur propre
        return new Response(JSON.stringify({
            error: 'Network error and no cache available',
            message: error.message
        }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

// Stratégie Cache First
async function cacheFirstStrategy(request) {
    try {
        // Essayer le cache en premier
        const cachedResponse = await getCachedResponse(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Si pas en cache, aller sur le réseau
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            await cache.put(request, networkResponse.clone());
            return networkResponse;
        }
        
        // Si la réponse n'est pas OK, retourner quand même
        return networkResponse;
        
    } catch (error) {
        // Erreur stratégie cache first
        const fallback = await getOfflineFallback(request);
        return fallback || new Response(JSON.stringify({
            error: 'Cache and network both failed',
            message: error.message
        }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

// Stratégie Network First avec timeout
async function networkFirstWithTimeoutStrategy(request, timeout = 3000) {
    try {
        // Promesse de timeout
        const timeoutPromise = new Promise((_, reject) => {
            setTimeout(() => reject(new Error('Timeout')), timeout);
        });
        
        // Course entre fetch et timeout
        const networkResponse = await Promise.race([
            fetch(request),
            timeoutPromise
        ]);
        
        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            await cache.put(request, networkResponse.clone());
            return networkResponse;
        }
        
        return await getCachedResponse(request);
        
    } catch (error) {
        // Timeout ou erreur réseau
        return await getCachedResponse(request);
    }
}

// Stratégie Stale While Revalidate
async function staleWhileRevalidateStrategy(request) {
    const cache = await caches.open(API_CACHE);
    const cachedResponse = await cache.match(request);
    
    // Promesse de fetch en arrière-plan
    const fetchPromise = fetch(request).then(response => {
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    }).catch(() => {
        // Erreur réseau, garder le cache
        return cachedResponse;
    });
    
    // Retourner le cache immédiatement si disponible, sinon attendre le réseau
    return cachedResponse || fetchPromise;
}

// Récupérer une réponse du cache
async function getCachedResponse(request) {
    try {
        const cacheNames = [CACHE_NAME, STATIC_CACHE, DYNAMIC_CACHE, API_CACHE];
        
        for (const cacheName of cacheNames) {
            const cache = await caches.open(cacheName);
            const response = await cache.match(request);
            if (response && response instanceof Response) {
                return response;
            }
        }
        
        return null;
    } catch (error) {
        console.error('Erreur lors de la récupération du cache:', error);
        return null;
    }
}

// Fallback hors-ligne
async function getOfflineFallback(request) {
    const url = new URL(request.url);
    
    // Fallback pour les pages HTML
    if (request.destination === 'document') {
        const cachedIndex = await getCachedResponse(new Request('/index.html'));
        if (cachedIndex) {
            return cachedIndex;
        }
    }
    
    // Fallback pour les images
    if (request.destination === 'image') {
        return new Response(
            '<svg width="200" height="150" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="#f0f0f0"/><text x="50%" y="50%" text-anchor="middle" dy=".35em" fill="#999">Image non disponible</text></svg>',
            {
                headers: {
                    'Content-Type': 'image/svg+xml',
                    'Cache-Control': 'no-store'
                }
            }
        );
    }
    
    // Fallback générique
    return new Response(
        JSON.stringify({
            error: 'Contenu non disponible hors ligne',
            offline: true
        }),
        {
            status: 503,
            headers: {
                'Content-Type': 'application/json',
                'Cache-Control': 'no-store'
            }
        }
    );
}

// Gestion des messages du client
self.addEventListener('message', event => {
    const { type, payload } = event.data;
    
    switch (type) {
        case 'SKIP_WAITING':
            self.skipWaiting();
            break;
            
        case 'GET_CACHE_STATS':
            getCacheStats().then(stats => {
                event.ports[0].postMessage({ type: 'CACHE_STATS', payload: stats });
            });
            break;
            
        case 'CLEAR_CACHE':
            clearAllCaches().then(() => {
                event.ports[0].postMessage({ type: 'CACHE_CLEARED' });
            });
            break;
            
        case 'FORCE_UPDATE':
            forceUpdate().then(() => {
                event.ports[0].postMessage({ type: 'UPDATE_COMPLETE' });
            });
            break;
    }
});

// Statistiques du cache
async function getCacheStats() {
    const cacheNames = await caches.keys();
    const stats = {};
    
    for (const cacheName of cacheNames) {
        const cache = await caches.open(cacheName);
        const keys = await cache.keys();
        stats[cacheName] = keys.length;
    }
    
    return stats;
}

// Vider tous les caches
async function clearAllCaches() {
    const cacheNames = await caches.keys();
    await Promise.all(
        cacheNames.map(cacheName => caches.delete(cacheName))
    );
}

// Forcer la mise à jour
async function forceUpdate() {
    await clearAllCaches();
    const cache = await caches.open(CACHE_NAME);
    await cache.addAll(CRITICAL_ASSETS);
}

// Nettoyage périodique du cache dynamique
async function cleanupDynamicCache() {
    const cache = await caches.open(DYNAMIC_CACHE);
    const keys = await cache.keys();
    
    // Garder seulement les 50 dernières entrées
    if (keys.length > 50) {
        const keysToDelete = keys.slice(0, keys.length - 50);
        await Promise.all(
            keysToDelete.map(key => cache.delete(key))
        );
    }
}

// Synchronisation en arrière-plan
self.addEventListener('sync', event => {
    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

async function doBackgroundSync() {
    try {
        // Nettoyer le cache
        await cleanupDynamicCache();
        
        // Pré-charger les ressources importantes
        const cache = await caches.open(DYNAMIC_CACHE);
        const urlsToPreload = [
            '/data/content.json',
            '/data/services.json',
            '/data/gallery.json'
        ];
        
        await Promise.all(
            urlsToPreload.map(async url => {
                try {
                    const response = await fetch(url);
                    if (response.ok) {
                        await cache.put(url, response);
                    }
                } catch (error) {
                    // Erreur pré-chargement
                }
            })
        );
        
        // Synchronisation en arrière-plan terminée
    } catch (error) {
        // Erreur synchronisation
    }
}

// Push notifications (pour les mises à jour futures)
self.addEventListener('push', event => {
    if (!event.data) return;
    
    const data = event.data.json();
    const options = {
        body: data.body || 'Nouvelle mise à jour disponible',
        icon: '/assets/images/icons/icon-192.png',
        badge: '/assets/images/icons/icon-96.png',
        vibrate: [200, 100, 200],
        tag: 'remmailleuse-update',
        actions: [
            {
                action: 'view',
                title: 'Voir',
                icon: '/assets/images/icons/icon-48.png'
            },
            {
                action: 'dismiss',
                title: 'Ignorer'
            }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title || 'Remmailleuse', options)
    );
});

// Gestion des clics sur les notifications
self.addEventListener('notificationclick', event => {
    event.notification.close();
    
    if (event.action === 'view') {
        event.waitUntil(
            clients.openWindow('/')
        );
    }
});

// Service Worker Remmailleuse chargé