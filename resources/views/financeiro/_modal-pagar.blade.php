{{-- Quadro "Registrar pagamento" de uma compra em aberto. Uso: @include('financeiro._modal-pagar', ['linha' => $linha]) --}}
@php use App\Support\Dinheiro; @endphp
@if($linha['aberto'] > 0)
    @php
        $c = $linha['compra'];
        $parceladoCombinado = $linha['condicao_codigo'] === 'parcelado';
        $sugestao = $parceladoCombinado ? number_format($linha['parcela_sugerida'], 2, ',', '.') : '';
        // Voltou com erro (ex.: folha de cheque já usada): a janela reabre com o que a pessoa tinha digitado.
        $reabrir = $errors->any() && (string) old('_compra') === (string) $c->id;
        $velho = fn (string $campo, $padrao = null) => $reabrir ? old($campo, $padrao) : $padrao;
        $parceladoMarcado = $velho('forma', $parceladoCombinado ? 'parcelado' : 'a_vista') === 'parcelado';
    @endphp
    {{-- Mesmo modelo de janela das outras telas: cabeçalho, corpo e rodapé fixo. --}}
    <div id="modal-pagar-{{ $c->id }}" data-reabrir="{{ $reabrir ? '1' : '' }}" style="display:{{ $reabrir ? 'flex' : 'none' }}; position:fixed; inset:0; background:rgba(15, 23, 42, 0.6); backdrop-filter:blur(3px); z-index:1000; align-items:center; justify-content:center; padding:16px;">
        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; width:100%; max-width:560px; max-height:94vh; display:flex; flex-direction:column; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); overflow:hidden; text-align:left;">
            <div style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:flex-start; background:#f8fafc;">
                <div>
                    <h3 style="margin:0; font-family:'Playfair Display', Georgia, serif; font-size:22px; font-weight:400; color:#0f172a; letter-spacing:0.01em;">Registrar pagamento</h3>
                    <div style="margin-top:4px; font-size:13px; color:#64748b;"><strong style="color:#1e293b;">{{ $linha['fornecedor'] }}</strong> · {{ $c->product_name }} · requisição #{{ $c->id }}</div>
                </div>
                <button type="button" onclick="document.getElementById('modal-pagar-{{ $c->id }}').style.display='none'" aria-label="Fechar"
                        style="border:none; background:transparent; color:#94a3b8; font-size:18px; cursor:pointer; padding:4px 8px; border-radius:4px; line-height:1;">✕</button>
            </div>
            <div style="padding:20px 24px; overflow-y:auto;">
            @if($reabrir)
                <div style="background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; padding:10px 12px; border-radius:8px; margin-bottom:14px; font-size:13px;">
                    <strong>Não foi possível salvar:</strong>
                    <ul style="margin:4px 0 0; padding-left:18px;">@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul>
                </div>
            @endif
            <p style="margin:0 0 6px; font-size:13.5px; color:#111827;">Falta pagar: <strong>{{ Dinheiro::brl($linha['aberto']) }}</strong> de {{ Dinheiro::brl($linha['custo']) }}</p>
            @if($linha['condicao'])
                <p style="margin:0 0 16px; font-size:12.5px; color:#6b7280;">
                    Combinado na compra: <strong>{{ $linha['condicao'] }}</strong>@if($parceladoCombinado) ({{ Dinheiro::brl($linha['valor_parcela']) }} por parcela)@endif
                    @if($linha['proximo_vencimento']) · próximo vencimento {{ $linha['proximo_vencimento']->format('d/m/Y') }}@endif
                </p>
            @else
                <div style="margin-bottom:16px;"></div>
            @endif

            <form method="POST" action="{{ route('financeiro.pagar', $c) }}" onsubmit="return protegerEnvioDuplo(this)">
                @csrf
                <input type="hidden" name="_compra" value="{{ $c->id }}">
                <div style="margin-bottom:14px; display:flex; flex-direction:column; gap:8px;">
                    <label style="font-size:14px; color:#374151;">
                        <input type="radio" name="forma" value="a_vista" {{ $parceladoMarcado ? '' : 'checked' }} onchange="formaPagamento({{ $c->id }}, this.value); previaCheque({{ $c->id }})">
                        À vista — quita os {{ Dinheiro::brl($linha['aberto']) }}
                    </label>
                    <label style="font-size:14px; color:#374151;">
                        <input type="radio" name="forma" value="parcelado" {{ $parceladoMarcado ? 'checked' : '' }} onchange="formaPagamento({{ $c->id }}, this.value); previaCheque({{ $c->id }})">
                        Parcelado — informar quanto foi pago agora
                    </label>
                </div>

                <div id="campo-valor-{{ $c->id }}" style="display:{{ $parceladoMarcado ? 'block' : 'none' }}; margin-bottom:14px;">
                    <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Valor pago (R$)</label>
                    <input type="text" name="valor" inputmode="decimal" placeholder="Ex: 12.500,00" value="{{ $reabrir && is_numeric(old('valor')) ? number_format((float) old('valor'), 2, ',', '.') : $velho('valor', $sugestao) }}" onfocus="this.select()" oninput="previaCheque({{ $c->id }})"
                           style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box;">
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px;">
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Forma de pagamento <span style="color:#ef4444;">*</span></label>
                        <select name="meio" required onchange="meioPagamento({{ $c->id }}, this.value)"
                                style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box; background-color:#fff;">
                            @foreach(\App\Models\PagamentoCompra::meios() as $codigo => $nomeMeio)
                                <option value="{{ $codigo }}"{{ $velho('meio') === $codigo ? ' selected' : '' }}>{{ $nomeMeio }}</option>
                            @endforeach
                            <option value="__novo">+ Cadastrar nova forma</option>
                        </select>
                        <input type="text" id="meio-novo-{{ $c->id }}" name="meio_novo" maxlength="60" placeholder="Nome da forma nova" autocomplete="off"
                               style="display:none; margin-top:6px; width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box;">
                    </div>
                    <div id="campo-banco-{{ $c->id }}">
                        <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Banco <span id="banco-obrig-{{ $c->id }}" style="color:#ef4444;">*</span></label>
                        <select name="banco" onchange="bancoEscolhido({{ $c->id }}, this.value)"
                                style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box; background-color:#fff;">
                            <option value="">De qual banco saiu?</option>
                            @foreach($bancos as $nomeBanco)
                                <option value="{{ $nomeBanco }}"{{ $velho('banco') === $nomeBanco ? ' selected' : '' }}>{{ $nomeBanco }}</option>
                            @endforeach
                            <option value="__novo">+ Cadastrar novo banco</option>
                        </select>
                        <input type="text" id="banco-novo-{{ $c->id }}" name="banco_novo" maxlength="100" placeholder="Nome do banco novo" autocomplete="off"
                               style="display:none; margin-top:6px; width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box;">
                    </div>
                </div>

                {{-- Cheque: o valor vira folhas iguais abaixo do limite, numeradas a partir da primeira. --}}
                <div id="campo-cheque-{{ $c->id }}" data-aberto="{{ $linha['aberto'] }}" style="display:none; margin-bottom:14px; padding:12px 14px; border:1px solid #c7d2fe; background:#eef2ff; border-radius:8px;">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div>
                            <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Nº da primeira folha <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="cheque_primeira_folha" value="{{ $reabrir && ctype_digit((string) old('cheque_primeira_folha')) ? str_pad(old('cheque_primeira_folha'), 6, '0', STR_PAD_LEFT) : $velho('cheque_primeira_folha') }}" inputmode="numeric" placeholder="Ex: 000101" autocomplete="off" oninput="this.dataset.manual = '1'; previaCheque({{ $c->id }})"
                                   style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box; background:#fff;">
                            <div id="dica-folha-{{ $c->id }}" style="margin-top:4px; font-size:11.5px; color:#4338ca;"></div>
                        </div>
                        <div>
                            <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Bom para <span style="color:#9ca3af; font-weight:400;">(se for pré-datado)</span></label>
                            <input type="date" name="cheque_bom_para" value="{{ $velho('cheque_bom_para', $velho('data_pagamento', now('America/Sao_Paulo')->format('Y-m-d'))) }}" oninput="this.dataset.manual = '1'"
                                   style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box; background:#fff;">
                        </div>
                    </div>
                    <div id="previa-cheque-{{ $c->id }}" style="margin-top:10px; font-size:12.5px; color:#312e81;"></div>
                </div>

                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Data do pagamento</label>
                    <input type="date" name="data_pagamento" oninput="bomParaSegue(this)" value="{{ $velho('data_pagamento', now('America/Sao_Paulo')->format('Y-m-d')) }}" required
                           style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box;">
                </div>

                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Observação <span style="color:#9ca3af; font-weight:400;">(opcional)</span></label>
                    <input type="text" name="obs" value="{{ $velho('obs') }}" maxlength="500" placeholder="Ex: boleto, PIX, parcela 1/3"
                           style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box;">
                </div>

                <div style="margin:4px -24px -20px; padding:16px 24px; border-top:1px solid #e2e8f0; background:#f8fafc; display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" onclick="document.getElementById('modal-pagar-{{ $c->id }}').style.display='none'"
                            style="padding:8px 18px; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; color:#475569; font-size:13px; font-weight:600; cursor:pointer;">Cancelar</button>
                    <button type="submit"
                            style="padding:8px 22px; border-radius:6px; background:#05018D; color:#ffffff; font-size:13px; font-weight:600; border:none; cursor:pointer;">Registrar</button>
                </div>
            </form>
            </div>
        </div>
    </div>
@endif
