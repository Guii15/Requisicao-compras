const chavePublica = document.querySelector('meta[name="vapid-public-key"]')?.content;
const botoes = document.querySelectorAll('[data-push-toggle]');

const suportado = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
const ehIphone = /iphone|ipad|ipod/i.test(navigator.userAgent);
const instalado = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;

function base64ParaBytes(base64) {
    const preenchido = (base64 + '='.repeat((4 - (base64.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/');
    return Uint8Array.from(atob(preenchido), (c) => c.charCodeAt(0));
}

function pintar(icone, texto, dica, desabilitado = false) {
    botoes.forEach((botao) => {
        botao.querySelector('[data-push-icon]').textContent = icone;
        const rotulo = botao.querySelector('[data-push-label]');
        if (rotulo) rotulo.textContent = texto;
        botao.title = dica || texto;
        botao.disabled = desabilitado;
        botao.style.opacity = desabilitado ? '0.6' : '1';
        botao.style.cursor = desabilitado ? 'default' : 'pointer';
    });
}

async function inscricaoAtual() {
    const registro = await navigator.serviceWorker.getRegistration('/sw.js');
    return registro ? registro.pushManager.getSubscription() : null;
}

async function atualizar() {
    if (!suportado) {
        if (ehIphone && !instalado) {
            pintar('🔕', 'Instale o app para receber avisos', 'No Safari: Compartilhar > Adicionar à Tela de Início, e abra por lá.', true);
        } else {
            pintar('🔕', 'Notificações indisponíveis', 'Este navegador não suporta notificações.', true);
        }
        return;
    }
    if (Notification.permission === 'denied') {
        pintar('🔕', 'Notificações bloqueadas', 'Libere as notificações deste site nas configurações do navegador.', true);
        return;
    }
    const inscricao = await inscricaoAtual();
    if (inscricao && Notification.permission === 'granted') {
        pintar('🔔', 'Notificações ativas (clique para desativar)');
    } else {
        pintar('🔕', 'Ativar notificações');
    }
}

async function ativar(botao) {
    const permissao = await Notification.requestPermission();
    if (permissao !== 'granted') return atualizar();

    const registro = await navigator.serviceWorker.register('/sw.js');
    await navigator.serviceWorker.ready;
    const inscricao = (await registro.pushManager.getSubscription())
        || (await registro.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: base64ParaBytes(chavePublica) }));

    await window.axios.post(botao.dataset.subscribeUrl, inscricao.toJSON());
}

async function desativar(botao) {
    const inscricao = await inscricaoAtual();
    if (!inscricao) return;
    const endpoint = inscricao.endpoint;
    await inscricao.unsubscribe();
    await window.axios.delete(botao.dataset.subscribeUrl, { data: { endpoint } });
}

if (botoes.length && chavePublica) {
    if (suportado) navigator.serviceWorker.register('/sw.js').catch(() => {});

    botoes.forEach((botao) => {
        botao.addEventListener('click', async () => {
            botao.disabled = true;
            try {
                (await inscricaoAtual()) && Notification.permission === 'granted' ? await desativar(botao) : await ativar(botao);
            } catch (erro) {
                console.error('Notificações:', erro);
            }
            botao.disabled = false;
            atualizar();
        });
    });

    atualizar();
}
