self.addEventListener('push', (event) => {
  try {
    const notification = event.data.json();
    event.waitUntil(
      self.registration.showNotification(notification.title || '', notification.options || {}),
    );
  } catch {
    try {
      const text = event.data.text();
      event.waitUntil(
        self.registration.showNotification('Notification', { body: text }),
      );
    } catch {
      event.waitUntil(self.registration.showNotification(''));
    }
  }
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const link = event.notification.data?.meta?.link || '';
  if (!link) return;

  const targetUrl = new URL(link, self.location.origin).href;

  // Only navigate to same-origin URLs
  if (!targetUrl.startsWith(self.location.origin + '/')) return;

  event.waitUntil(
    clients.matchAll({ type: 'window' }).then((windowClients) => {
      // Focus a window already on the target URL
      for (const client of windowClients) {
        if (client.url === targetUrl && 'focus' in client) {
          return client.focus();
        }
      }
      // Navigate an existing window to the target URL
      for (const client of windowClients) {
        if ('navigate' in client) {
          return client.navigate(targetUrl).then((c) => c?.focus());
        }
      }
      // Fallback: open a new window
      if (clients.openWindow) {
        return clients.openWindow(targetUrl);
      }
    }),
  );
});
