self.addEventListener('push', (event) => {
    let dados = {};
    try {
        dados = event.data ? event.data.json() : {};
    } catch (e) {
        dados = { body: event.data ? event.data.text() : '' };
    }

    event.waitUntil(self.registration.showNotification(dados.title || 'Requisição de Compras', {
        body: dados.body || '',
        icon: '/imagens/icon-192.png',
        badge: '/imagens/favicon.png',
        tag: dados.tag || undefined,
        renotify: Boolean(dados.tag),
        data: { url: dados.url || '/' },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const alvo = new URL(event.notification.data.url, self.location.origin).href;

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((janelas) => {
            for (const janela of janelas) {
                if (janela.url.startsWith(self.location.origin) && 'focus' in janela) {
                    return janela.navigate(alvo).then((c) => (c || janela).focus());
                }
            }
            return self.clients.openWindow(alvo);
        })
    );
});
