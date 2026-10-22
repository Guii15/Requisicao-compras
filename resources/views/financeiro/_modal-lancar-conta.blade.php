{{--
    Quadro "Lançar conta": conta que não veio de uma requisição (saldo antigo, conta do Bling,
    fornecedor que nunca comprou pelo sistema). Pode entrar já paga.
    $fornecedorPadrao: nome que já vem preenchido (na tela de um fornecedor).
--}}
@php
    $reabrir = $errors->any() && (string) old('_compra') === 'conta';
    $velho = fn (string $campo, $padrao = null) => $reabrir ? old($campo, $padrao) : $padrao;
    $campo = 'width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box; background-color:#fff;';
    $rotulo = 'display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;';
    $hoje = now('America/Sao_Paulo')->format('Y-m-d');
    $bancosConta = \App\Models\Banco::nomes();
    $jaPaga = (bool) $velho('ja_paga');
    $parcelado = $velho('condicao') === 'parcelado';
@endphp
<div id="modal-conta" data-reabrir="{{ $reabrir ? '1' : '' }}" style="display:{{ $reabrir ? 'flex' : 'none' }}; position:fixed; inset:0; background:rgba(15, 23, 42, 0.6); backdrop-filter:blur(3px); z-index:1000; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; width:100%; max-width:600px; max-height:94vh; display:flex; flex-direction:column; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); overflow:hidden; text-align:left;">
        <div style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:flex-start; background:#f8fafc;">
            <div>
                <h3 style="margin:0; font-family:'Playfair Display', Georgia, serif; font-size:22px; font-weight:400; color:#0f172a; letter-spacing:0.01em;">Lançar conta</h3>
                <div style="margin-top:4px; font-size:13px; color:#64748b;">Conta que não veio de uma requisição: saldo antigo, conta do Bling, fornecedor sem compra no sistema</div>
            </div>
            <button type="button" onclick="document.getElementById('modal-conta').style.display='none'" aria-label="Fechar"
                    style="border:none; background:transparent; color:#94a3b8; font-size:18px; cursor:pointer; padding:4px 8px; border-radius:4px; line-height:1;">✕</button>
        </div>
        <div style="padding:20px 24px; overflow-y:auto;">
            @if($reabrir)
                <div style="background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; padding:10px 12px; border-radius:8px; margin-bottom:14px; font-size:13px;">
                    <strong>Não foi possível salvar:</strong>
                    <ul style="margin:4px 0 0; padding-left:18px;">@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('financeiro.lancarConta') }}" onsubmit="return protegerEnvioDuplo(this)">
                @csrf
                <input type="hidden" name="_compra" value="conta">

                <div style="margin-bottom:14px;">
                    <label style="{{ $rotulo }}">Fornecedor <span style="color:#ef4444;">*</span> <span style="color:#9ca3af; font-weight:400;">— escolha da lista ou digite um novo</span></label>
                    <input type="text" name="fornecedor" required maxlength="255" list="lista-fornecedores-conta" autocomplete="off"
                           value="{{ $velho('fornecedor', $fornecedorPadrao ?? '') }}" placeholder="Ex: Distribuidora Silva" style="{{ $campo }}">
                    <datalist id="lista-fornecedores-conta" data-url="{{ route('financeiro.nomesFornecedores') }}"></datalist>
                </div>

                <div style="margin-bottom:14px;">
                    <label style="{{ $rotulo }}">Descrição <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="descricao" required maxlength="255" value="{{ $velho('descricao') }}" placeholder="Ex: Saldo anterior ao sistema · NF 1234" style="{{ $campo }}">
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px;">
                    <div>
                        <label style="{{ $rotulo }}">Valor (R$) <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="valor" required inputmode="decimal" onfocus="this.select()" oninput="previaCheque('conta')" placeholder="Ex: 12.500,00"
                               value="{{ $reabrir && is_numeric(old('valor')) ? number_format((float) old('valor'), 2, ',', '.') : $velho('valor', '') }}" style="{{ $campo }}">
                    </div>
                    <div>
                        <label style="{{ $rotulo }}">Data da conta <span style="color:#ef4444;">*</span></label>
                        <input type="date" name="data_conta" required value="{{ $velho('data_conta', $hoje) }}" style="{{ $campo }}">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-bottom:14px;">
                    <div>
                        <label style="{{ $rotulo }}">Condição</label>
                        <select name="condicao" onchange="document.getElementById('parcelas-conta').style.display = this.value === 'parcelado' ? 'block' : 'none'; this.form.parcelas.required = this.value === 'parcelado';" style="{{ $campo }}">
                            <option value="a_vista">À vista</option>
                            <option value="parcelado"{{ $parcelado ? ' selected' : '' }}>Parcelado</option>
                        </select>
                    </div>
                    <div id="parcelas-conta" style="display:{{ $parcelado ? 'block' : 'none' }};">
                        <label style="{{ $rotulo }}">Parcelas</label>
                        <input type="number" name="parcelas" min="2" max="120" value="{{ $velho('parcelas') }}"{{ $parcelado ? ' required' : '' }} placeholder="Ex: 3" style="{{ $campo }}">
                    </div>
                    <div>
                        <label style="{{ $rotulo }}">Vencimento <span style="color:#9ca3af; font-weight:400;">(1º, se parcelado)</span></label>
                        <input type="date" name="vencimento" value="{{ $velho('vencimento') }}" style="{{ $campo }}">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px;">
                    <div>
                        <label style="{{ $rotulo }}">Empresa <span style="color:#9ca3af; font-weight:400;">(opcional)</span></label>
                        <input type="text" name="empresa_conta" maxlength="100" value="{{ $velho('empresa_conta', $empresaPadrao ?? '') }}" placeholder="Ex: Binário" style="{{ $campo }}">
                    </div>
                    <div>
                        <label style="{{ $rotulo }}">Observação <span style="color:#9ca3af; font-weight:400;">(opcional)</span></label>
                        <input type="text" name="obs_conta" maxlength="500" value="{{ $velho('obs_conta') }}" style="{{ $campo }}">
                    </div>
                </div>

                <label style="display:flex; gap:8px; align-items:center; font-size:13.5px; font-weight:600; color:#0f172a; margin-bottom:12px; cursor:pointer;">
                    <input type="checkbox" name="ja_paga" value="1"{{ $jaPaga ? ' checked' : '' }} onchange="contaJaPaga(this.checked)"> Esta conta já foi paga
                </label>

                {{-- Pagamento: só vale (e só é enviado) com "já foi paga" marcado. --}}
                <div id="pagamento-conta" style="display:{{ $jaPaga ? 'block' : 'none' }}; margin-bottom:14px; padding:12px 14px; border:1px solid #e2e8f0; background:#f8fafc; border-radius:8px;">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:12px;">
                        <div>
                            <label style="{{ $rotulo }}">Forma de pagamento <span style="color:#ef4444;">*</span></label>
                            <select name="meio" required onchange="meioPagamento('conta', this.value)" style="{{ $campo }}">
                                @foreach(\App\Models\PagamentoCompra::meios() as $codigo => $nomeMeio)
                                    <option value="{{ $codigo }}"{{ $velho('meio') === $codigo ? ' selected' : '' }}>{{ $nomeMeio }}</option>
                                @endforeach
                                <option value="__novo">+ Cadastrar nova forma</option>
                            </select>
                            <input type="text" id="meio-novo-conta" name="meio_novo" maxlength="60" placeholder="Nome da forma nova" autocomplete="off" style="display:none; margin-top:6px; {{ $campo }}">
                        </div>
                        <div id="campo-banco-conta">
                            <label style="{{ $rotulo }}">Banco <span id="banco-obrig-conta" style="color:#ef4444;">*</span></label>
                            <select name="banco" onchange="bancoEscolhido('conta', this.value)" style="{{ $campo }}">
                                <option value="">De qual banco saiu?</option>
                                @foreach($bancosConta as $nomeBanco)
                                    <option value="{{ $nomeBanco }}"{{ $velho('banco') === $nomeBanco ? ' selected' : '' }}>{{ $nomeBanco }}</option>
                                @endforeach
                                <option value="__novo">+ Cadastrar novo banco</option>
                            </select>
                            <input type="text" id="banco-novo-conta" name="banco_novo" maxlength="100" placeholder="Nome do banco novo" autocomplete="off" style="display:none; margin-top:6px; {{ $campo }}">
                        </div>
                    </div>

                    <div id="campo-cheque-conta" style="display:{{ $velho('meio') === 'cheque' ? 'block' : 'none' }}; margin-bottom:12px; padding:12px 14px; border:1px solid #c7d2fe; background:#eef2ff; border-radius:8px;">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                            <div>
                                <label style="{{ $rotulo }}">Nº da primeira folha <span style="color:#ef4444;">*</span></label>
                                <input type="text" name="cheque_primeira_folha" inputmode="numeric" placeholder="Ex: 000101" autocomplete="off" oninput="this.dataset.manual = '1'; previaCheque('conta')"
                                       value="{{ $reabrir && ctype_digit((string) old('cheque_primeira_folha')) ? str_pad(old('cheque_primeira_folha'), 6, '0', STR_PAD_LEFT) : $velho('cheque_primeira_folha') }}" style="{{ $campo }}">
                                <div id="dica-folha-conta" style="margin-top:4px; font-size:11.5px; color:#4338ca;"></div>
                            </div>
                            <div>
                                <label style="{{ $rotulo }}">Bom para <span style="color:#9ca3af; font-weight:400;">(se for pré-datado)</span></label>
                                <input type="date" name="cheque_bom_para" value="{{ $velho('cheque_bom_para', $velho('data_pagamento', $hoje)) }}" oninput="this.dataset.manual = '1'" style="{{ $campo }}">
                            </div>
                        </div>
                        <div id="previa-cheque-conta" style="margin-top:10px; font-size:12.5px; color:#312e81;"></div>
                    </div>

                    <div>
                        <label style="{{ $rotulo }}">Data do pagamento <span style="color:#ef4444;">*</span></label>
                        <input type="date" name="data_pagamento" required oninput="bomParaSegue(this)" value="{{ $velho('data_pagamento', $hoje) }}" style="{{ $campo }}">
                    </div>
                </div>

                <div style="margin:4px -24px -20px; padding:16px 24px; border-top:1px solid #e2e8f0; background:#f8fafc; display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" onclick="document.getElementById('modal-conta').style.display='none'"
                            style="padding:8px 18px; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; color:#475569; font-size:13px; font-weight:600; cursor:pointer;">Cancelar</button>
                    <button type="submit"
                            style="padding:8px 22px; border-radius:6px; background:#05018D; color:#ffffff; font-size:13px; font-weight:600; border:none; cursor:pointer;">Lançar conta</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
// Sem "já foi paga", os campos do pagamento ficam desligados (não são enviados nem exigidos).
function contaJaPaga(marcado) {
    var caixa = document.getElementById('pagamento-conta');
    caixa.style.display = marcado ? 'block' : 'none';
    caixa.querySelectorAll('input, select').forEach(function (el) { el.disabled = !marcado; });
    if (marcado) {
        meioPagamento('conta', caixa.querySelector('[name="meio"]').value);
    }
}
// A lista de fornecedores vem só quando a janela é usada (não pesa em toda tela).
function carregarFornecedoresConta() {
    var lista = document.getElementById('lista-fornecedores-conta');
    if (lista.dataset.carregada) {
        return;
    }
    lista.dataset.carregada = '1';
    fetch(lista.dataset.url, { headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.ok ? r.json() : []; })
        .then(function (nomes) {
            nomes.forEach(function (nome) {
                var op = document.createElement('option');
                op.value = nome;
                lista.appendChild(op);
            });
        })
        .catch(function () { delete lista.dataset.carregada; });
}
function abrirLancarConta() {
    document.getElementById('modal-conta').style.display = 'flex';
    carregarFornecedoresConta();
}
document.addEventListener('DOMContentLoaded', function () {
    contaJaPaga(document.querySelector('#modal-conta [name="ja_paga"]').checked);
    if (document.getElementById('modal-conta').dataset.reabrir) {
        carregarFornecedoresConta();
    }
});
</script>
