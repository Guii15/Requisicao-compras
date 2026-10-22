{{-- Quadro "Pagar fornecedor": um valor só (total ou parcial) distribuído entre as compras em aberto, da que vence primeiro para a que vence depois. --}}
@php
    use App\Support\Dinheiro;
    $reabrir = $errors->any() && (string) old('_compra') === 'forn';
    $velho = fn (string $campo, $padrao = null) => $reabrir ? old($campo, $padrao) : $padrao;
    $campo = 'width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box; background-color:#fff;';
    $rotulo = 'display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;';
@endphp
<div id="modal-pagar-forn" data-reabrir="{{ $reabrir ? '1' : '' }}" style="display:{{ $reabrir ? 'flex' : 'none' }}; position:fixed; inset:0; background:rgba(15, 23, 42, 0.6); backdrop-filter:blur(3px); z-index:1000; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; width:100%; max-width:600px; max-height:94vh; display:flex; flex-direction:column; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); overflow:hidden; text-align:left;">
        <div style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:flex-start; background:#f8fafc;">
            <div>
                <h3 style="margin:0; font-family:'Playfair Display', Georgia, serif; font-size:22px; font-weight:400; color:#0f172a; letter-spacing:0.01em;">Pagar fornecedor</h3>
                <div style="margin-top:4px; font-size:13px; color:#64748b;"><strong style="color:#1e293b;">{{ $nome }}</strong> · um pagamento só para várias compras</div>
            </div>
            <button type="button" onclick="document.getElementById('modal-pagar-forn').style.display='none'" aria-label="Fechar"
                    style="border:none; background:transparent; color:#94a3b8; font-size:18px; cursor:pointer; padding:4px 8px; border-radius:4px; line-height:1;">✕</button>
        </div>
        <div style="padding:20px 24px; overflow-y:auto;">
            @if($reabrir)
                <div style="background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; padding:10px 12px; border-radius:8px; margin-bottom:14px; font-size:13px;">
                    <strong>Não foi possível salvar:</strong>
                    <ul style="margin:4px 0 0; padding-left:18px;">@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul>
                </div>
            @endif
            <p style="margin:0 0 16px; font-size:13.5px; color:#111827;">Saldo devedor{{ $empresaSel ? ' nesta empresa' : '' }}: <strong>{{ Dinheiro::brl($totais['saldo']) }}</strong> em {{ count($ordemPagamento) }} {{ count($ordemPagamento) === 1 ? 'compra' : 'compras' }}</p>

            <form method="POST" action="{{ route('financeiro.pagarFornecedor', array_filter(['chave' => $chave, 'empresa' => $empresaSel])) }}" onsubmit="return protegerEnvioDuplo(this)">
                @csrf
                <input type="hidden" name="_compra" value="forn">

                <div style="margin-bottom:14px;">
                    <label style="{{ $rotulo }}">Valor pago (R$) <span style="color:#ef4444;">*</span> <span style="color:#9ca3af; font-weight:400;">— o total ou só uma parte</span></label>
                    <input type="text" name="valor" required inputmode="decimal" onfocus="this.select()" oninput="previaFornecedor()"
                           placeholder="Ex: 12.500,00" value="{{ $reabrir && is_numeric(old('valor')) ? number_format((float) old('valor'), 2, ',', '.') : $velho('valor', '') }}" style="{{ $campo }}">
                    {{-- Começa vazio de propósito: quitar tudo é uma escolha, não o padrão. --}}
                    <button type="button" onclick="var v = this.form.valor; v.value = '{{ number_format($totais['saldo'], 2, ',', '.') }}'; previaFornecedor();"
                            style="margin-top:6px; background:none; border:none; padding:0; color:#05018D; font-size:12.5px; font-weight:600; text-decoration:underline; cursor:pointer;">Usar o saldo todo ({{ Dinheiro::brl($totais['saldo']) }})</button>
                </div>

                <div id="previa-fornecedor" style="margin-bottom:14px; padding:10px 12px; border:1px solid #e2e8f0; background:#f8fafc; border-radius:8px; font-size:12.5px; color:#334155;"></div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px;">
                    <div>
                        <label style="{{ $rotulo }}">Forma de pagamento <span style="color:#ef4444;">*</span></label>
                        <select name="meio" required onchange="meioPagamento('forn', this.value)" style="{{ $campo }}">
                            @foreach(\App\Models\PagamentoCompra::meios() as $codigo => $nomeMeio)
                                <option value="{{ $codigo }}"{{ $velho('meio') === $codigo ? ' selected' : '' }}>{{ $nomeMeio }}</option>
                            @endforeach
                            <option value="__novo">+ Cadastrar nova forma</option>
                        </select>
                        <input type="text" id="meio-novo-forn" name="meio_novo" maxlength="60" placeholder="Nome da forma nova" autocomplete="off" style="display:none; margin-top:6px; {{ $campo }}">
                    </div>
                    <div id="campo-banco-forn">
                        <label style="{{ $rotulo }}">Banco <span id="banco-obrig-forn" style="color:#ef4444;">*</span></label>
                        <select name="banco" onchange="bancoEscolhido('forn', this.value)" style="{{ $campo }}">
                            <option value="">De qual banco saiu?</option>
                            @foreach($bancos as $nomeBanco)
                                <option value="{{ $nomeBanco }}"{{ $velho('banco') === $nomeBanco ? ' selected' : '' }}>{{ $nomeBanco }}</option>
                            @endforeach
                            <option value="__novo">+ Cadastrar novo banco</option>
                        </select>
                        <input type="text" id="banco-novo-forn" name="banco_novo" maxlength="100" placeholder="Nome do banco novo" autocomplete="off" style="display:none; margin-top:6px; {{ $campo }}">
                    </div>
                </div>

                {{-- Cheque: as folhas saem do valor total do pagamento. --}}
                <div id="campo-cheque-forn" style="display:none; margin-bottom:14px; padding:12px 14px; border:1px solid #c7d2fe; background:#eef2ff; border-radius:8px;">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div>
                            <label style="{{ $rotulo }}">Nº da primeira folha <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="cheque_primeira_folha" inputmode="numeric" placeholder="Ex: 000101" autocomplete="off" oninput="previaCheque('forn')"
                                   value="{{ $reabrir && ctype_digit((string) old('cheque_primeira_folha')) ? str_pad(old('cheque_primeira_folha'), 6, '0', STR_PAD_LEFT) : $velho('cheque_primeira_folha') }}" style="{{ $campo }}">
                        </div>
                        <div>
                            <label style="{{ $rotulo }}">Bom para <span style="color:#9ca3af; font-weight:400;">(se for pré-datado)</span></label>
                            <input type="date" name="cheque_bom_para" value="{{ $velho('cheque_bom_para') }}" style="{{ $campo }}">
                        </div>
                    </div>
                    <div id="previa-cheque-forn" style="margin-top:10px; font-size:12.5px; color:#312e81;"></div>
                </div>

                <div style="margin-bottom:14px;">
                    <label style="{{ $rotulo }}">Data do pagamento</label>
                    <input type="date" name="data_pagamento" required value="{{ $velho('data_pagamento', now('America/Sao_Paulo')->format('Y-m-d')) }}" style="{{ $campo }}">
                </div>

                <div style="margin-bottom:18px;">
                    <label style="{{ $rotulo }}">Observação <span style="color:#9ca3af; font-weight:400;">(opcional)</span></label>
                    <input type="text" name="obs" value="{{ $velho('obs') }}" maxlength="500" placeholder="Ex: acerto do mês de outubro" style="{{ $campo }}">
                </div>

                <div style="margin:4px -24px -20px; padding:16px 24px; border-top:1px solid #e2e8f0; background:#f8fafc; display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" onclick="document.getElementById('modal-pagar-forn').style.display='none'"
                            style="padding:8px 18px; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; color:#475569; font-size:13px; font-weight:600; cursor:pointer;">Cancelar</button>
                    <button type="submit"
                            style="padding:8px 22px; border-radius:6px; background:#05018D; color:#ffffff; font-size:13px; font-weight:600; border:none; cursor:pointer;">Registrar pagamento</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
// Compras em aberto na ordem em que o pagamento as quita (mesma ordem do servidor).
var ORDEM_PAGAMENTO_FORNECEDOR = @json($ordemPagamento);
</script>
