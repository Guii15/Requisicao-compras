{{--
    Janela "Conferir Item" ÚNICA da página: o botão Conferir de cada item chama abrirConferir(id), que lê os
    dados do <template id="conferir-item-ID"> daquele item e preenche esta janela.
    Enquanto a pessoa preenche, o item aberto e os campos ficam no sessionStorage: se o celular descartar a página
    com o app da câmera aberto, ela volta sozinha com a janela reaberta (a foto não dá para guardar — o aviso pede de novo).
--}}
<div id="modal-conferir" style="display:none; position:fixed; inset:0; background:rgba(15, 23, 42, 0.6); backdrop-filter:blur(3px); z-index:1000; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; width:100%; max-width:940px; max-height:94vh; display:flex; flex-direction:column; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); overflow:hidden; text-align:left;">
        <div style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:flex-start; background:#f8fafc;">
            <div>
                <h3 style="margin:0; font-family:'Playfair Display', Georgia, serif; font-size:22px; font-weight:400; color:#0f172a; letter-spacing:0.01em;">Conferir Item</h3>
                <div style="margin-top:4px; font-size:13px; color:#64748b; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <span id="conf-produto" style="font-weight:600; color:#1e293b; overflow-wrap:anywhere;"></span>
                    <span>·</span>
                    <span>Solicitante: <strong id="conf-solicitante"></strong></span>
                    <span>·</span>
                    <span>Pedido: <strong id="conf-pedido-qtd"></strong></span>
                </div>
            </div>
            <button type="button" onclick="fecharConferir()" aria-label="Fechar"
                    style="border:none; background:transparent; color:#94a3b8; font-size:18px; cursor:pointer; padding:4px 8px; border-radius:4px; line-height:1;">✕</button>
        </div>

        <form method="POST" action="" enctype="multipart/form-data" id="form-conferir" onsubmit="return enviarConferir(this)" style="display:flex; flex-direction:column; flex:1; min-height:0; overflow:hidden; margin:0;">
            @csrf
            @method('PATCH')

            <div class="jan-corpo" style="padding:22px 24px; display:grid; grid-template-columns:1.3fr 1fr; gap:28px; flex:1; min-height:0; overflow-y:auto;">
                <div style="display:grid; grid-template-columns:1fr 1fr; column-gap:12px; align-content:start;">
                    <div style="grid-column:1 / -1; font-size:11.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:14px; padding-bottom:6px; border-bottom:1px solid #f1f5f9;">Conferência</div>

                    <div id="conf-aviso-foto" role="alert" style="display:none; grid-column:1 / -1; margin:-4px 0 14px; padding:10px 12px; border:1px solid #f59e0b; background:#fffbeb; color:#92400e; border-radius:6px; font-size:13px; font-weight:600;">A foto não foi anexada. Tire novamente.</div>

                    <div style="margin-bottom:14px;">
                        <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Quantidade recebida</label>
                        <input type="number" name="quantidade_recebida" id="campo-qtd" value="" min="0" required
                               oninput="verificaDivergencia(this.value)"
                               style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box; font-weight:700;">
                    </div>
                    <div style="margin-bottom:14px;">
                        <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Resultado</label>
                        <select name="resultado" id="campo-resultado" required onchange="atualizaResultado(this.value)"
                                style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box; background-color:#fff;">
                            <option value="ok">OK</option>
                            <option value="divergente">Divergente</option>
                        </select>
                    </div>
                    <div id="aviso-divergencia" style="display:none; grid-column:1 / -1; margin:-6px 0 14px; font-size:12px; color:#b45309; font-weight:600;">
                        ⚠️ Diferente da quantidade solicitada (pedido: <span id="aviso-divergencia-qtd"></span>)
                    </div>

                    <div style="margin-bottom:14px;">
                        <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Foto</label>
                        <input type="file" name="foto" id="campo-foto" accept=".jpg,.jpeg,.png,.webp" required style="width:100%; font-size:12px; color:#475569;">
                    </div>
                    <div style="margin-bottom:14px;">
                        <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Fotos extras <span style="font-weight:400; color:#94a3b8;">(opcional — ex.: código de barras, até 5)</span></label>
                        <input type="file" name="fotos_extras[]" accept=".jpg,.jpeg,.png,.webp" multiple style="width:100%; font-size:12px; color:#475569;">
                    </div>

                    <div id="campo-observacao" style="display:none; grid-column:1 / -1; margin-bottom:14px;">
                        <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Observação da divergência</label>
                        <textarea name="observacao_conferencia" rows="2" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box; resize:vertical; font-family:inherit;"></textarea>
                    </div>

                    <div style="grid-column:1 / -1;">
                        <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Obs (geral)</label>
                        <textarea name="obs" rows="2" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box; resize:vertical; font-family:inherit;"></textarea>
                    </div>
                </div>

                <div>
                    <div style="font-size:11.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:14px; padding-bottom:6px; border-bottom:1px solid #f1f5f9;">Sobre o pedido</div>
                    <div style="font-size:13px; color:#334155; margin-bottom:18px;">
                        <div style="display:flex; justify-content:space-between; gap:12px; padding:6px 0; border-bottom:1px solid #f1f5f9;"><span style="color:#64748b;">Fornecedor</span><span id="conf-fornecedor" style="text-align:right;"></span></div>
                        <div style="display:flex; justify-content:space-between; gap:12px; padding:6px 0; border-bottom:1px solid #f1f5f9;"><span style="color:#64748b;">Entrega</span><span id="conf-entrega"></span></div>
                        <div style="display:flex; justify-content:space-between; gap:12px; padding:6px 0;"><span style="color:#64748b;">Pedido de compra</span>
                            <a id="conf-pedido-link" href="#" target="_blank" style="display:none; color:#05018D; font-weight:600; text-decoration:underline;">📎 Ver pedido de compra</a>
                            <span id="conf-pedido-vazio" style="color:#94a3b8;">Não anexado</span>
                        </div>
                    </div>

                    <div style="font-size:11.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:14px; padding-bottom:6px; border-bottom:1px solid #f1f5f9;">Notas e ocorrências</div>
                    <div id="conf-notas"></div>
                </div>
            </div>

            <input type="hidden" name="acao" id="campo-acao" value="salvar">

            <div style="padding:16px 24px; border-top:1px solid #e2e8f0; background:#f8fafc; display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap;">
                <button type="button" onclick="fecharConferir()"
                        style="padding:8px 18px; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; color:#475569; font-size:13px; font-weight:600; cursor:pointer;">
                    Cancelar
                </button>
                <button type="submit" id="btn-avancar" onclick="document.getElementById('campo-acao').value='avancar_mesmo_assim'"
                        style="display:none; padding:8px 18px; border-radius:6px; background:#fff; color:#b45309; font-size:13px; font-weight:600; border:1px solid #b45309; cursor:pointer;">
                    Avançar Mesmo Assim
                </button>
                <button type="submit" id="btn-aguardar" value="aguardar_restante" title="O que chegou segue para a entrada; o que falta fica aguardando e será conferido quando chegar"
                        onclick="document.getElementById('campo-acao').value='aguardar_restante'"
                        style="display:none; padding:8px 18px; border-radius:6px; background:#fff; color:#374151; font-size:13px; font-weight:600; border:1px solid #9ca3af; cursor:pointer;">
                    Aguardar restante
                </button>
                <button type="submit" onclick="document.getElementById('campo-acao').value='salvar'"
                        style="padding:8px 22px; border-radius:6px; background:#05018D; color:#ffffff; font-size:13px; font-weight:600; border:none; cursor:pointer;">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var CHAVE = 'conferencia-rascunho';
    var aberto = null; // { id, qtd, entregaDireta }

    function el(id) { return document.getElementById(id); }
    function form() { return el('form-conferir'); }

    function guardar() {
        if (!aberto) return;
        try {
            var f = form();
            sessionStorage.setItem(CHAVE, JSON.stringify({
                id: aberto.id,
                quantidade_recebida: f.elements['quantidade_recebida'].value,
                resultado: f.elements['resultado'].value,
                observacao_conferencia: f.elements['observacao_conferencia'].value,
                obs: f.elements['obs'].value
            }));
        } catch (e) { /* sessionStorage bloqueado: segue sem rascunho */ }
    }

    function limpar() {
        try { sessionStorage.removeItem(CHAVE); } catch (e) {}
    }

    window.atualizaResultado = function (valor) {
        el('campo-observacao').style.display = valor === 'divergente' ? 'block' : 'none';
        el('btn-avancar').style.display = (valor === 'divergente' && aberto && aberto.entregaDireta) ? 'inline-block' : 'none';
    };

    window.verificaDivergencia = function (valor) {
        if (!aberto) return;
        var recebida = parseInt(valor, 10);
        var divergiu = valor !== '' && recebida !== aberto.qtd;
        el('aviso-divergencia').style.display = divergiu ? 'block' : 'none';
        // Chegou só uma parte (mais que 0 e menos que o pedido): pode aguardar o restante.
        el('btn-aguardar').style.display = (recebida > 0 && recebida < aberto.qtd) ? 'inline-block' : 'none';
        if (divergiu) {
            el('campo-resultado').value = 'divergente';
            window.atualizaResultado('divergente');
        }
    };

    window.abrirConferir = function (id, rascunho) {
        var modelo = el('conferir-item-' + id);
        if (!modelo) return false;
        var d = modelo.dataset;
        var f = form();

        f.reset();
        delete f.dataset.enviando;
        f.querySelectorAll('button[type="submit"]').forEach(function (b) { b.disabled = false; });
        f.action = d.url;

        aberto = { id: id, qtd: parseInt(d.qtd, 10), entregaDireta: d.entrega === 'entrega_direta' };

        el('conf-produto').textContent = d.produto;
        el('conf-solicitante').textContent = d.solicitante;
        el('conf-pedido-qtd').textContent = d.qtd;
        el('aviso-divergencia-qtd').textContent = d.qtd;
        el('conf-fornecedor').textContent = d.fornecedor || '—';
        el('conf-entrega').textContent = d.entrega === 'entrega_direta' ? 'Venda Casada' : 'Estoque';
        el('conf-pedido-link').style.display = d.pedidoUrl ? '' : 'none';
        el('conf-pedido-link').href = d.pedidoUrl || '#';
        el('conf-pedido-vazio').style.display = d.pedidoUrl ? 'none' : '';
        el('conf-notas').replaceChildren(modelo.content.cloneNode(true));

        el('campo-qtd').value = d.qtd;
        el('campo-resultado').value = 'ok';
        el('campo-acao').value = 'salvar';
        el('conf-aviso-foto').style.display = 'none';
        el('aviso-divergencia').style.display = 'none';
        el('btn-aguardar').style.display = 'none';
        window.atualizaResultado('ok');

        if (rascunho) {
            el('campo-qtd').value = rascunho.quantidade_recebida;
            f.elements['observacao_conferencia'].value = rascunho.observacao_conferencia || '';
            f.elements['obs'].value = rascunho.obs || '';
            window.verificaDivergencia(rascunho.quantidade_recebida);
            el('campo-resultado').value = rascunho.resultado;
            window.atualizaResultado(rascunho.resultado);
            el('conf-aviso-foto').style.display = 'block';
        }

        el('modal-conferir').style.display = 'flex';
        guardar(); // já guarda o item aberto: o app da câmera pode derrubar a página antes de qualquer digitação
        return false;
    };

    window.fecharConferir = function () {
        limpar();
        aberto = null;
        el('modal-conferir').style.display = 'none';
    };

    window.enviarConferir = function (f) {
        var pode = protegerEnvioDuplo(f);
        if (pode) limpar();
        return pode;
    };

    document.addEventListener('DOMContentLoaded', function () {
        var f = form();
        f.addEventListener('input', guardar);
        f.addEventListener('change', guardar);
        el('campo-foto').addEventListener('change', function () { el('conf-aviso-foto').style.display = 'none'; });

        var salvo = null;
        try { salvo = JSON.parse(sessionStorage.getItem(CHAVE) || 'null'); } catch (e) {}
        if (!salvo) return;
        // O item pode já não estar nesta página (conferido por outra pessoa, outra página da lista).
        if (!el('conferir-item-' + salvo.id)) { limpar(); return; }
        window.abrirConferir(salvo.id, salvo);
    });
})();
</script>
