const CACHE_NAME = 'dsir-cache-v2';
const APP_SHELL = [
  // Core auditor
  '/dsir/public/auditor_dsir.php',
  '/dsir/public/js/dsir.js',
  '/dsir/public/css/style.css',
  '/dsir/public/manifest.json',
  // Admin
  '/dsir/public/admin_dashboard.php',
  '/dsir/public/admin_users.php',
  '/dsir/public/admin_branches.php',
  '/dsir/public/admin_products.php',
  '/dsir/public/api/admin/summary.php',
  '/dsir/public/api/admin/products.php',
  '/dsir/public/api/admin/branches.php',
  '/dsir/public/api/admin/users.php',
  // Accountant
  '/dsir/public/accountant_queue.php',
  '/dsir/public/accountant_report.php',
  '/dsir/public/js/accountant.js',
  // Programmer
  '/dsir/public/programmer.php',
  '/dsir/public/api/programmer/patches.php',
  '/dsir/public/api/programmer/settings.php',
  // Print (optional)
  '/dsir/public/print/sales_report.php',
  // CDN (Chart.js & Bootstrap CSS)
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js'
];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(APP_SHELL)));
  self.skipWaiting();
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', e => {
  const url = new URL(e.request.url);

  // Always network-first for APIs (so admin edits are fresh), fallback to cache
  if (url.pathname.startsWith('/api/')) {
    if (e.request.method === 'GET') {
      e.respondWith(fetch(e.request).then(r => {
        const clone = r.clone();
        caches.open(CACHE_NAME).then(cache => cache.put(e.request, clone));
        return r;
      }).catch(() => caches.match(e.request)));
    }
    return;
  }

  // App shell: cache-first
  e.respondWith(
    caches.match(e.request).then(res => res || fetch(e.request).then(r => {
      const clone = r.clone();
      caches.open(CACHE_NAME).then(cache => cache.put(e.request, clone));
      return r;
    }).catch(() => caches.match('/auditor_dsir.php')))
  );
});

