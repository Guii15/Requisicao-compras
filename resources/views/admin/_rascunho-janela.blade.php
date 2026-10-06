{{--
    Rascunho automático da janela "Atualizar Requisição".
    Tudo que o admin digita fica guardado no navegador (localStorage), por item. Volta quando ele reabre a janela,
    mesmo depois de sair sem querer, recarregar a página ou de um erro ao salvar. Só some quando salva com sucesso,
    quando ele clica em "Descartar rascunho" ou se o item foi alterado por outra pessoa (versão diferente).
    Arquivos anexados não entram no rascunho (o navegador não permite).
    Cada <form> da janela precisa de data-rascunho="{id}" e data-versao="{updated_at}".
--}}
<script>
(function () {
    var PREFIXO = 'rascunho-requisicao-';
    var VALIDADE_MS = 7 * 24 * 60 * 60 * 1000;
    // Item cujo salvamento acabou de falhar: o rascunho dele tem que ficar (o servidor devolveu os valores antigos).
    var comErro = @js(session('modal_aberto') && $errors->any() ? (int) session('modal_aberto') : null);

    function ler(id) {
        try { return JSON.parse(localStorage.getItem(PREFIXO + id)); } catch (e) { return null; }
    }
    function gravar(id, rascunho) {
        try { localStorage.setItem(PREFIXO + id, JSON.stringify(rascunho)); } catch (e) { /* sem armazenamento: segue sem rascunho */ }
    }
    function apagar(id) {
        try { localStorage.removeItem(PREFIXO + id); } catch (e) { }
    }

    // form.elements e não querySelectorAll: no desktop a <form> fica dentro de uma <td> e o navegador a fecha vazia;
    // os campos continuam dela só pela associação do HTML (e por isso os eventos também não sobem até ela).
    function campos(form) {
        return Array.prototype.filter.call(form.elements, function (el) {
            return ['INPUT', 'SELECT', 'TEXTAREA'].indexOf(el.tagName) !== -1
                && el.name && el.name !== '_token' && el.name !== '_method'
                && ['hidden', 'file', 'submit', 'button', 'password'].indexOf(el.type) === -1;
        });
    }
    function estado(form) {
        var s = {};
        campos(form).forEach(function (el) {
            if (el.type === 'checkbox') { s[el.name + '#' + el.value] = el.checked; }
            else if (el.type === 'radio') { if (el.checked) { s[el.name] = el.value; } }
            else { s[el.name] = el.value; }
        });
        return s;
    }
    function aplicar(form, s) {
        campos(form).forEach(function (el) {
            var chave = el.type === 'checkbox' ? el.name + '#' + el.value : el.name;
            if (!(chave in s)) { return; }
            if (el.type === 'checkbox') { el.checked = !!s[chave]; }
            else if (el.type === 'radio') { el.checked = (el.value === s[chave]); }
            else { el.value = s[chave]; }
            // avisa o resto da página (total calculado, tarja do status, condição de pagamento...)
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    function marcarBotoes(id) {
        // só os botões que ABREM a janela (display='flex'), não o X nem o Cancelar
        document.querySelectorAll('button[onclick*="\'modal-' + id + '\'"][onclick*="flex"], button[onclick*="\'modal-m-' + id + '\'"][onclick*="flex"]').forEach(function (b) {
            if (b.querySelector('[data-rascunho-selo]')) { return; }
            var selo = document.createElement('span');
            selo.setAttribute('data-rascunho-selo', '1');
            selo.textContent = ' · rascunho';
            selo.style.cssText = 'font-weight:500; font-size:11px; opacity:.85;';
            b.appendChild(selo);
            b.title = 'Tem alterações não salvas guardadas';
        });
    }

    function avisoRecuperado(form, id, base) {
        var aviso = document.createElement('div');
        aviso.setAttribute('data-rascunho-aviso', '1');
        aviso.style.cssText = 'display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; '
            + 'margin:0 0 14px; padding:9px 12px; background:#fef3c7; color:#92400e; border-radius:8px; font-size:12.5px;';
        aviso.innerHTML = '<span>Rascunho recuperado: você tinha alterações não salvas nesta janela. Anexos precisam ser escolhidos de novo.</span>';
        var botao = document.createElement('button');
        botao.type = 'button';
        botao.textContent = 'Descartar rascunho';
        botao.style.cssText = 'border:1px solid #d99a00; background:#fff; color:#92400e; border-radius:9999px; padding:4px 12px; font-size:12px; font-weight:600; cursor:pointer;';
        botao.addEventListener('click', function () {
            apagar(id);
            document.querySelectorAll('form[data-rascunho="' + id + '"]').forEach(function (f) {
                aplicar(f, base);
            });
            document.querySelectorAll('[data-rascunho-aviso]').forEach(function (a) {
                if (a.closest('[id$="-' + id + '"]')) { a.remove(); }
            });
            document.querySelectorAll('[data-rascunho-selo]').forEach(function (s) {
                if (!ler(id)) { s.remove(); }
            });
        });
        aviso.appendChild(botao);
        if (form.children.length > 0) {
            form.insertBefore(aviso, form.firstChild);                       // celular: a form tem conteúdo
        } else {
            var cartao = (form.closest('[id^="modal-"]') || {}).firstElementChild;   // desktop: depois do cabeçalho da janela
            if (cartao) { cartao.insertBefore(aviso, cartao.children[1] || null); }
        }
    }

    function iniciar(form) {
        var id = parseInt(form.getAttribute('data-rascunho'), 10);
        var versao = form.getAttribute('data-versao');
        var base = estado(form);
        var baseJson = JSON.stringify(base);
        var emEspera = null;

        var rascunho = ler(id);
        if (rascunho) {
            var velho = !rascunho.ts || (Date.now() - rascunho.ts) > VALIDADE_MS;
            var outraVersao = String(rascunho.v) !== String(versao);
            var jaEnviado = rascunho.enviado && comErro !== id;
            if (velho || outraVersao || jaEnviado) {
                apagar(id);
            } else {
                delete rascunho.enviado;
                gravar(id, rascunho);
                aplicar(form, rascunho.campos);
                avisoRecuperado(form, id, base);
                marcarBotoes(id);
            }
        }

        function salvarRascunho() {
            var atual = estado(form);
            if (JSON.stringify(atual) === baseJson) { apagar(id); return; }
            gravar(id, { v: versao, ts: Date.now(), campos: atual });
            marcarBotoes(id);
        }
        function agendar() {
            clearTimeout(emEspera);
            emEspera = setTimeout(salvarRascunho, 250);
        }
        var raiz = form.closest('[id^="modal-"]') || form;   // os eventos sobem até o modal, não até a <form> do desktop
        raiz.addEventListener('input', agendar);
        raiz.addEventListener('change', agendar);

        // Ao enviar, o rascunho fica marcado como "enviado": se a próxima página carregar sem erro, foi salvo e ele some.
        form.addEventListener('submit', function () {
            clearTimeout(emEspera);
            var atual = estado(form);
            gravar(id, { v: versao, ts: Date.now(), campos: atual, enviado: true });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-rascunho]').forEach(iniciar);
    });
})();
</script>
