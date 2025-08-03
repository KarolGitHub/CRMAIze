// CRMAIze Service Worker for PWA functionality
const CACHE_NAME = 'crmaize-v1.0.0';
const OFFLINE_URL = '/offline.html';

// Assets to cache for offline functionality
const STATIC_CACHE_ASSETS = [
  '/',
  '/dashboard',
  '/campaigns',
  '/analytics',
  '/offline.html',
  '/assets/css/mobile.css',
  '/manifest.json',
  // External CDN assets
  'https://cdn.jsdelivr.net/npm/foundation-sites@6.7.5/dist/css/foundation.min.css',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
  'https://cdn.jsdelivr.net/npm/foundation-sites@6.7.5/dist/js/foundation.min.js',
  'https://cdn.jsdelivr.net/npm/chart.js',
];

// Dynamic cache patterns
const DYNAMIC_CACHE_PATTERNS = [
  /^https:\/\/api\.crmaize\.com\//,
  /^\/api\//,
  /^\/analytics\/chart-data/,
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
  console.log('[SW] Installing service worker...');

  event.waitUntil(
    caches
      .open(CACHE_NAME)
      .then((cache) => {
        console.log('[SW] Caching static assets');
        return cache.addAll(STATIC_CACHE_ASSETS);
      })
      .then(() => {
        console.log('[SW] Static assets cached successfully');
        return self.skipWaiting();
      })
      .catch((error) => {
        console.error('[SW] Failed to cache static assets:', error);
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('[SW] Activating service worker...');

  event.waitUntil(
    caches
      .keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames.map((cacheName) => {
            if (cacheName !== CACHE_NAME) {
              console.log('[SW] Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      })
      .then(() => {
        console.log('[SW] Service worker activated');
        return self.clients.claim();
      })
  );
});

// Fetch event - implement caching strategies
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }

  // Skip chrome-extension requests
  if (url.protocol === 'chrome-extension:') {
    return;
  }

  // Handle different types of requests
  if (isStaticAsset(request)) {
    // Static assets - Cache First strategy
    event.respondWith(cacheFirst(request));
  } else if (isAPIRequest(request)) {
    // API requests - Network First strategy
    event.respondWith(networkFirst(request));
  } else if (isNavigationRequest(request)) {
    // Navigation requests - Network First with offline fallback
    event.respondWith(navigationHandler(request));
  } else {
    // Other requests - Stale While Revalidate
    event.respondWith(staleWhileRevalidate(request));
  }
});

// Background sync for offline actions
self.addEventListener('sync', (event) => {
  console.log('[SW] Background sync triggered:', event.tag);

  if (event.tag === 'background-sync-campaigns') {
    event.waitUntil(syncCampaigns());
  } else if (event.tag === 'background-sync-analytics') {
    event.waitUntil(syncAnalytics());
  }
});

// Push notifications (for future use)
self.addEventListener('push', (event) => {
  console.log('[SW] Push notification received');

  const options = {
    body: event.data ? event.data.text() : 'New update available',
    icon: '/assets/icons/icon-192x192.png',
    badge: '/assets/icons/badge-72x72.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1,
    },
    actions: [
      {
        action: 'explore',
        title: 'View Details',
        icon: '/assets/icons/checkmark.png',
      },
      {
        action: 'close',
        title: 'Close',
        icon: '/assets/icons/xmark.png',
      },
    ],
  };

  event.waitUntil(self.registration.showNotification('CRMAIze', options));
});

// Notification click handler
self.addEventListener('notificationclick', (event) => {
  console.log('[SW] Notification clicked:', event.notification.tag);

  event.notification.close();

  if (event.action === 'explore') {
    event.waitUntil(clients.openWindow('/dashboard'));
  }
});

// Helper functions
function isStaticAsset(request) {
  const url = new URL(request.url);
  return (
    url.pathname.startsWith('/assets/') ||
    url.pathname.endsWith('.css') ||
    url.pathname.endsWith('.js') ||
    url.pathname.endsWith('.png') ||
    url.pathname.endsWith('.jpg') ||
    url.pathname.endsWith('.svg') ||
    url.hostname !== self.location.hostname
  ); // CDN assets
}

function isAPIRequest(request) {
  const url = new URL(request.url);
  return (
    url.pathname.startsWith('/api/') ||
    DYNAMIC_CACHE_PATTERNS.some((pattern) => pattern.test(request.url))
  );
}

function isNavigationRequest(request) {
  return (
    request.mode === 'navigate' ||
    (request.method === 'GET' &&
      request.headers.get('accept').includes('text/html'))
  );
}

// Cache strategies
async function cacheFirst(request) {
  try {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }

    const networkResponse = await fetch(request);
    const cache = await caches.open(CACHE_NAME);
    cache.put(request, networkResponse.clone());

    return networkResponse;
  } catch (error) {
    console.error('[SW] Cache first failed:', error);
    return new Response('Asset not available offline', { status: 503 });
  }
}

async function networkFirst(request) {
  try {
    const networkResponse = await fetch(request);

    if (networkResponse.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }

    return networkResponse;
  } catch (error) {
    console.log('[SW] Network failed, trying cache:', request.url);
    const cachedResponse = await caches.match(request);

    if (cachedResponse) {
      return cachedResponse;
    }

    // Return offline data for API requests
    if (isAPIRequest(request)) {
      return new Response(
        JSON.stringify({
          error: 'Offline',
          message: 'This data is not available offline',
          offline: true,
        }),
        {
          status: 503,
          headers: { 'Content-Type': 'application/json' },
        }
      );
    }

    throw error;
  }
}

async function navigationHandler(request) {
  try {
    const networkResponse = await fetch(request);
    return networkResponse;
  } catch (error) {
    console.log('[SW] Navigation offline, serving offline page');
    const cache = await caches.open(CACHE_NAME);
    const offlinePage = await cache.match(OFFLINE_URL);

    if (offlinePage) {
      return offlinePage;
    }

    // Fallback offline page if not cached
    return new Response(
      `
      <!DOCTYPE html>
      <html>
        <head>
          <title>CRMAIze - Offline</title>
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 2rem; background: #f8f9fa; }
            .offline-container { max-width: 400px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .offline-icon { font-size: 4rem; color: #6c757d; margin-bottom: 1rem; }
            h1 { color: #1779ba; margin-bottom: 1rem; }
            p { color: #6c757d; line-height: 1.6; }
            .retry-btn { background: #1779ba; color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 4px; cursor: pointer; margin-top: 1rem; }
            .retry-btn:hover { background: #126195; }
          </style>
        </head>
        <body>
          <div class="offline-container">
            <div class="offline-icon">📱</div>
            <h1>You're Offline</h1>
            <p>It looks like you're not connected to the internet. Some features may not be available until you reconnect.</p>
            <button class="retry-btn" onclick="window.location.reload()">Try Again</button>
          </div>
        </body>
      </html>
    `,
      {
        status: 200,
        headers: { 'Content-Type': 'text/html' },
      }
    );
  }
}

async function staleWhileRevalidate(request) {
  const cache = await caches.open(CACHE_NAME);
  const cachedResponse = await cache.match(request);

  const fetchPromise = fetch(request)
    .then((networkResponse) => {
      cache.put(request, networkResponse.clone());
      return networkResponse;
    })
    .catch((error) => {
      console.log('[SW] Network failed for:', request.url);
      return cachedResponse;
    });

  return cachedResponse || fetchPromise;
}

// Background sync functions
async function syncCampaigns() {
  try {
    console.log('[SW] Syncing campaigns in background...');
    // Implement campaign sync logic here
    // This would typically sync any offline campaign actions
    return Promise.resolve();
  } catch (error) {
    console.error('[SW] Campaign sync failed:', error);
    throw error;
  }
}

async function syncAnalytics() {
  try {
    console.log('[SW] Syncing analytics in background...');
    // Implement analytics sync logic here
    // This would typically sync any offline analytics data
    return Promise.resolve();
  } catch (error) {
    console.error('[SW] Analytics sync failed:', error);
    throw error;
  }
}

// Periodic background sync (if supported)
self.addEventListener('periodicsync', (event) => {
  if (event.tag === 'background-analytics-sync') {
    event.waitUntil(syncAnalytics());
  }
});

// Message handling for communication with main thread
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }

  if (event.data && event.data.type === 'GET_VERSION') {
    event.ports[0].postMessage({ version: CACHE_NAME });
  }
});
