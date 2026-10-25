/* Service worker untuk notifikasi Web Push Kantin Kampus. */
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', event => event.waitUntil(self.clients.claim()));

self.addEventListener('push', event => {
  let payload = {};
  try {
    payload = event.data ? event.data.json() : {};
  } catch (e) {
    payload = {};
  }

  event.waitUntil(self.registration.showNotification(payload.title || 'Kantin Kampus', {
    body: payload.body || 'Pesananmu telah siap',
    tag: payload.tag || 'pesanan-siap',
    renotify: true,
    data: { url: payload.url || 'pesanan.html' }
  }));
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  const target = new URL(
    (event.notification.data && event.notification.data.url) || 'pesanan.html',
    self.registration.scope
  ).href;

  event.waitUntil((async () => {
    const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    const existing = windows.find(client => client.url.startsWith(self.registration.scope));
    if (existing) {
      if ('navigate' in existing) await existing.navigate(target).catch(() => {});
      return existing.focus();
    }
    return self.clients.openWindow(target);
  })());
});
