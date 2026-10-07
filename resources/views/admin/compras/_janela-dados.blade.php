{{--
    Janela "Dados da compra" da lista de Compras Feitas: uma só na página, preenchida pelo botão do item
    (abrirCompra). Salva na mesma rota da tela antiga (admin.compras.update) com origem=lista, que volta para cá.
--}}
@php
    $itemVazio = new \App\Models\PurchaseRequest();
    $reabrir = session('compra_aberta') && $errors->any();
    $rot = 'display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;';
    $cam = 'width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box;';
    $err = 'color:#b91c1c; font-size:12px; margin-top:4px;';
    $secao = 'font-size:11.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:14px; padding-bottom:6px; border-bottom:1px solid #f1f5f9;';
@endphp

<datalist id="fornecedores-usados">
    @foreach($fornecedoresUsados as $nomeFornecedor)
        <option value="{{ $nomeFornecedor }}">
    @endforeach
</datalist>

<div id="janela-compra" style="display:none; position:fixed; inset:0; background:rgba(15, 23, 42, 0.6); backdrop-filter:blur(3px); z-index:1000; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; width:100%; max-width:1040px; max-height:94vh; display:flex; flex-direction:column; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); overflow:hidden;">

        <div style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:flex-start; background:#f8fafc;">
            <div>
                <h3 style="margin:0; font-family:'Playfair Display', Georgia, serif; font-size:22px; font-weight:400; color:#0f172a; letter-spacing:0.01em;">Dados da compra</h3>
                <div style="margin-top:4px; font-size:13px; color:#64748b; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <span id="jc-produto" style="font-weight:600; color:#1e293b;"></span>
                    <span>·</span>
                    <span>Solicitante: <strong id="jc-solicitante"></strong></span>
                    <span>·</span>
                    <span>Pedido em <strong id="jc-pedido-em"></strong></span>
                </div>
            </div>
            <button type="button" onclick="fecharCompra()" aria-label="Fechar"
                    style="border:none; background:transparent; color:#94a3b8; font-size:18px; cursor:pointer; padding:4px 8px; border-radius:4px; line-height:1;">✕</button>
        </div>

        <form id="jc-form" method="POST" action="" enctype="multipart/form-data" style="display:flex; flex-direction:column; flex:1; min-height:0; overflow:hidden; margin:0;">
            @csrf
            @method('PATCH')
            <input type="hidden" name="origem" value="lista">

            <div class="jan-corpo" style="padding:22px 24px; display:grid; grid-template-columns:1.5fr 1fr; gap:28px; flex:1; min-height:0; overflow-y:auto;">

                <div style="display:grid; grid-template-columns:1fr 1fr; column-gap:12px; align-content:start;">
                    <div style="grid-column:1 / -1; {{ $secao }}">Dados da compra</div>

                    @if($reabrir)
                        <div style="grid-column:1 / -1; background:#fef2f2; color:#991b1b; border:1px solid #fecaca; padding:9px 12px; border-radius:6px; font-size:13px; margin-bottom:14px;">
                            Não salvou: confira os campos marcados abaixo.
                        </div>
                    @endif

                    <div style="margin-bottom:14px;">
                        <label style="{{ $rot }}">Fornecedor <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="supplier" required list="fornecedores-usados" autocomplete="off" placeholder="Ex: Bomvink, GPJ..." value="{{ old('supplier') }}" style="{{ $cam }}">
                        @error('supplier') <div style="{{ $err }}">{{ $message }}</div> @enderror
                    </div>
                    <div style="margin-bottom:14px;">
                        <label style="{{ $rot }}">Data da compra <span style="color:#ef4444;">*</span></label>
                        <input type="date" name="data_compra" required value="{{ old('data_compra') }}" style="{{ $cam }}">
                        @error('data_compra') <div style="{{ $err }}">{{ $message }}</div> @enderror
                    </div>

                    @include('admin._empresa-compra', ['item' => $itemVazio, 'modo' => 'janela'])
                    <div style="margin-bottom:14px;">
                        <label style="{{ $rot }}">Cód. no fornecedor <span style="font-weight:400; color:#94a3b8;">(opcional)</span></label>
                        <input type="text" name="codigo_fornecedor" placeholder="Ex: FORN-123" value="{{ old('codigo_fornecedor') }}" style="{{ $cam }}">
                        @error('codigo_fornecedor') <div style="{{ $err }}">{{ $message }}</div> @enderror
                    </div>

                    <div class="jan-4" style="grid-column:1 / -1; display:grid; grid-template-columns:0.7fr 1fr 1fr 1fr; gap:12px; margin-bottom:6px;">
                        <div>
                            <label style="{{ $rot }}">Qtd comprada <span style="color:#ef4444;">*</span></label>
                            <input type="number" name="quantity" min="1" step="1" inputmode="numeric" required value="{{ old('quantity') }}" style="{{ $cam }} font-weight:700;">
                        </div>
                        <div>
                            <label style="{{ $rot }}">Preço unit. (R$) <span style="color:#ef4444;">*</span></label>
                            <input type="text" inputmode="decimal" name="preco_unitario" required placeholder="0,00" value="{{ old('preco_unitario') }}" class="valor-brl" style="{{ $cam }}">
                        </div>
                        <div>
                            <label style="{{ $rot }}">Preço caixa (R$)</label>
                            <input type="text" inputmode="decimal" name="preco_caixa" placeholder="0,00" value="{{ old('preco_caixa') }}" class="valor-brl" style="{{ $cam }}">
                        </div>
                        <div>
                            <label style="{{ $rot }}">Total (R$)</label>
                            <input type="text" inputmode="decimal" name="valor" placeholder="0,00" value="{{ old('valor') }}" class="valor-brl"
                                   title="Sugerido: quantidade × preço unitário. Pode corrigir se o total real for outro."
                                   style="{{ $cam }} font-size:14px; font-weight:700; color:#05018D; background:#f4f4fb;">
                        </div>
                    </div>
                    <div style="grid-column:1 / -1; font-size:12px; color:#64748b; margin-bottom:14px;">
                        <span id="jc-dica-qtd"></span>
                        O total é sugerido (quantidade × unitário) e pode ser corrigido.
                        @foreach(['quantity', 'preco_unitario', 'preco_caixa', 'valor'] as $campoComErro)
                            @error($campoComErro) <div style="{{ $err }}">{{ $message }}</div> @enderror
                        @endforeach
                    </div>

                    <div style="grid-column:1 / -1;">@include('admin._condicao-pagamento', ['item' => $itemVazio, 'sufixo' => 'lista', 'modo' => 'modal', 'obrigatorio' => true])</div>
                </div>

                <div>
                    <div style="{{ $secao }}">Pedido de compra</div>
                    <div id="jc-pedido-atual" style="display:none; align-items:center; gap:8px; margin-bottom:8px; background:#f1f5f9; padding:6px 10px; border-radius:6px; font-size:12.5px;">
                        <a id="jc-pedido-link" href="#" target="_blank" style="color:#05018D; font-weight:600; text-decoration:none; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"></a>
                        <button type="submit" form="jc-remover-pedido" onclick="return confirm('Remover o pedido de compra anexado?')" style="background:none; border:none; color:#dc2626; font-size:11.5px; text-decoration:underline; cursor:pointer; margin-left:auto;">Remover</button>
                    </div>
                    <input type="file" name="pedido_compra" accept=".pdf,.jpg,.jpeg,.png,.webp" style="width:100%; font-size:12px; color:#475569;">
                    <div style="font-size:12px; color:#64748b; margin-top:4px;">PDF ou imagem, até 10 MB. Enviar outro arquivo substitui o atual.</div>
                    @error('pedido_compra') <div style="{{ $err }}">{{ $message }}</div> @enderror

                    <div style="{{ $secao }} margin-top:22px;">Andamento</div>
                    <div style="font-size:13px; color:#334155;">
                        <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #f1f5f9;"><span style="color:#64748b;">Coleta</span><span id="jc-coleta"></span></div>
                        <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #f1f5f9;"><span style="color:#64748b;">Conferência</span><span id="jc-conferencia"></span></div>
                        <div style="display:flex; justify-content:space-between; padding:6px 0;"><span style="color:#64748b;">Entrada</span><span id="jc-entrada"></span></div>
                    </div>
                    <p id="jc-nota" style="display:none; margin:12px 0 0; padding:10px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; color:#475569; font-size:13px; white-space:pre-line;"></p>
                </div>
            </div>

            <div style="padding:16px 24px; border-top:1px solid #e2e8f0; background:#f8fafc; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="fecharCompra()" style="padding:8px 18px; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; color:#475569; font-size:13px; font-weight:600; cursor:pointer;">Cancelar</button>
                <button type="submit" style="padding:8px 22px; border-radius:6px; background:#05018D; color:#ffffff; font-size:13px; font-weight:600; border:none; cursor:pointer;">Salvar dados da compra</button>
            </div>
        </form>

        <form id="jc-remover-pedido" method="POST" action="" style="display:none;">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>

<script>
(function () {
    var janela = document.getElementById('janela-compra');
    var form = document.getElementById('jc-form');
    var campo = function (nome) { return form.querySelector('[name="' + nome + '"]'); };
    var texto = function (id, valor) { document.getElementById(id).textContent = valor; };
    var numero = function (v) { return parseFloat(String(v || '').replace(/\./g, '').replace(',', '.')) || 0; };

    // manterDigitado: reabrindo depois de um erro, os campos já vêm com o que a pessoa digitou.
    window.abrirCompra = function (botao, manterDigitado) {
        var d = JSON.parse(botao.getAttribute('data-compra'));
        form.action = d.url;
        texto('jc-produto', d.produto);
        texto('jc-solicitante', d.solicitante);
        texto('jc-pedido-em', d.pedidoEm || '—');
        texto('jc-coleta', d.coleta || 'Aguardando');
        texto('jc-conferencia', botao.getAttribute('data-conferencia') || 'Não conferido');
        texto('jc-entrada', d.entrada || 'Ainda não');

        if (!manterDigitado) {
            campo('supplier').value = d.supplier || '';
            campo('data_compra').value = d.dataCompra || '';
            campo('empresa').value = d.empresa || '';
            campo('codigo_fornecedor').value = d.codigo || '';
            campo('quantity').value = d.quantity;
            campo('preco_unitario').value = d.unitario;
            campo('preco_caixa').value = d.caixa;
            campo('valor').value = d.valor;
            campo('condicao_pagamento').value = d.condicao || '';
            campo('parcelas').value = d.parcelas || '';
            campo('primeiro_vencimento').value = d.vencimento || '';
            campo('pedido_compra').value = '';
        }
        condicaoPagamento('lista');

        var qtd = campo('quantity');
        qtd.readOnly = d.travada !== '';
        qtd.style.background = d.travada ? '#f1f5f9' : '';
        texto('jc-dica-qtd', d.travada === 'conferido' ? 'Este item já foi conferido; a quantidade não pode mais ser alterada.'
            : d.travada === 'parcial' ? 'Recebimento parcial: ajuste a quantidade pelo botão Editar da Conferência.'
            : 'Se comprou diferente do pedido, corrija a quantidade: a Conferência passa a ver este número.');

        var atual = document.getElementById('jc-pedido-atual');
        atual.style.display = d.pedidoUrl ? 'flex' : 'none';
        if (d.pedidoUrl) {
            var link = document.getElementById('jc-pedido-link');
            link.href = d.pedidoUrl;
            link.textContent = d.pedidoNome || 'Pedido anexado';
            document.getElementById('jc-remover-pedido').action = d.removerUrl;
        }

        var nota = document.getElementById('jc-nota');
        nota.style.display = d.nota ? 'block' : 'none';
        nota.textContent = d.nota || '';

        janela.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        (d.temDados ? campo('preco_unitario') : campo('supplier')).focus();
    };

    window.fecharCompra = function () {
        janela.style.display = 'none';
        document.body.style.overflow = '';
    };

    janela.addEventListener('mousedown', function (e) { if (e.target === janela) fecharCompra(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && janela.style.display === 'flex') fecharCompra(); });

    // Total sugerido: quantidade × preço unitário (a pessoa ainda pode corrigir o total à mão).
    var sugerirTotal = function () {
        var unitario = campo('preco_unitario').value.trim();
        if (unitario === '') return;
        var total = numero(unitario) * (parseFloat(campo('quantity').value) || 0);
        campo('valor').value = total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };
    campo('preco_unitario').addEventListener('input', sugerirTotal);
    campo('quantity').addEventListener('input', sugerirTotal);

    @if($abrir && !$reabrir)
        // Chegou por um atalho (?abrir=ID): abre a requisição e a janela do item.
        var botaoDoAtalho = document.querySelector('[data-compra-id="{{ $abrir->id }}"]');
        if (botaoDoAtalho) {
            toggleGrupoCompraFeita(@json($abrir->grupo_id));
            @unless(session('success')) abrirCompra(botaoDoAtalho); @endunless
        }
    @endif

    @if($reabrir)
        toggleGrupoCompraFeita(@json(\App\Models\PurchaseRequest::find(session('compra_aberta'))?->grupo_id));
        var botaoDoErro = document.querySelector('[data-compra-id="{{ (int) session('compra_aberta') }}"]');
        if (botaoDoErro) abrirCompra(botaoDoErro, true);
    @endif
})();
</script>
