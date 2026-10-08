{{-- Quadro "Registrar pagamento" de uma compra em aberto. Uso: @include('financeiro._modal-pagar', ['linha' => $linha]) --}}
@php use App\Support\Dinheiro; @endphp
@if($linha['aberto'] > 0)
    @php
        $c = $linha['compra'];
        $parceladoCombinado = $linha['condicao_codigo'] === 'parcelado';
        $sugestao = $parceladoCombinado ? number_format($linha['parcela_sugerida'], 2, ',', '.') : '';
    @endphp
    {{-- Mesmo modelo de janela das outras telas: cabeçalho, corpo e rodapé fixo. --}}
    <div id="modal-pagar-{{ $c->id }}" style="display:none; position:fixed; inset:0; background:rgba(15, 23, 42, 0.6); backdrop-filter:blur(3px); z-index:1000; align-items:center; justify-content:center; padding:16px;">
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
                <div style="margin-bottom:14px; display:flex; flex-direction:column; gap:8px;">
                    <label style="font-size:14px; color:#374151;">
                        <input type="radio" name="forma" value="a_vista" {{ $parceladoCombinado ? '' : 'checked' }} onchange="formaPagamento({{ $c->id }}, this.value)">
                        À vista — quita os {{ Dinheiro::brl($linha['aberto']) }}
                    </label>
                    <label style="font-size:14px; color:#374151;">
                        <input type="radio" name="forma" value="parcelado" {{ $parceladoCombinado ? 'checked' : '' }} onchange="formaPagamento({{ $c->id }}, this.value)">
                        Parcelado — informar quanto foi pago agora
                    </label>
                </div>

                <div id="campo-valor-{{ $c->id }}" style="display:{{ $parceladoCombinado ? 'block' : 'none' }}; margin-bottom:14px;">
                    <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Valor pago (R$)</label>
                    <input type="text" name="valor" inputmode="decimal" placeholder="Ex: 12.500,00" value="{{ $sugestao }}"
                           style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box;">
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px;">
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Forma de pagamento <span style="color:#ef4444;">*</span></label>
                        <select name="meio" required onchange="meioPagamento({{ $c->id }}, this.value)"
                                style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box; background-color:#fff;">
                            @foreach(\App\Models\PagamentoCompra::MEIOS as $codigo => $nomeMeio)
                                <option value="{{ $codigo }}">{{ $nomeMeio }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="campo-banco-{{ $c->id }}">
                        <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Banco <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="banco" list="bancos-usados" maxlength="100" placeholder="De qual banco saiu?" autocomplete="off"
                               style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box;">
                    </div>
                </div>

                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Data do pagamento</label>
                    <input type="date" name="data_pagamento" value="{{ now('America/Sao_Paulo')->format('Y-m-d') }}" required
                           style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box;">
                </div>

                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Observação <span style="color:#9ca3af; font-weight:400;">(opcional)</span></label>
                    <input type="text" name="obs" maxlength="500" placeholder="Ex: boleto, PIX, parcela 1/3"
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
