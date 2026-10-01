{{-- Quadro "Registrar pagamento" de uma compra em aberto. Uso: @include('financeiro._modal-pagar', ['linha' => $linha]) --}}
@php use App\Support\Dinheiro; @endphp
@if($linha['aberto'] > 0)
    @php
        $c = $linha['compra'];
        $parceladoCombinado = $linha['condicao_codigo'] === 'parcelado';
        $sugestao = $parceladoCombinado ? number_format($linha['parcela_sugerida'], 2, ',', '.') : '';
    @endphp
    <div id="modal-pagar-{{ $c->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:12px; padding:24px; width:100%; max-width:420px; margin:16px; max-height:90vh; overflow-y:auto;">
            <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Registrar pagamento</h3>
            <p style="margin:0 0 4px; font-size:13px; color:#6b7280;">{{ $linha['fornecedor'] }} — {{ $c->product_name }} (requisição #{{ $c->id }})</p>
            <p style="margin:0 0 6px; font-size:13px; color:#111827;">Falta pagar: <strong>{{ Dinheiro::brl($linha['aberto']) }}</strong> de {{ Dinheiro::brl($linha['custo']) }}</p>
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
                    <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Valor pago (R$)</label>
                    <input type="text" name="valor" inputmode="decimal" placeholder="Ex: 12.500,00" value="{{ $sugestao }}"
                           style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px;">
                    <div>
                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Forma de pagamento <span style="color:#ef4444;">*</span></label>
                        <select name="meio" required onchange="meioPagamento({{ $c->id }}, this.value)"
                                style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box; background:#fff;">
                            @foreach(\App\Models\PagamentoCompra::MEIOS as $codigo => $nomeMeio)
                                <option value="{{ $codigo }}">{{ $nomeMeio }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="campo-banco-{{ $c->id }}">
                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Banco <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="banco" list="bancos-usados" maxlength="100" placeholder="De qual banco saiu?" autocomplete="off"
                               style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                    </div>
                </div>

                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Data do pagamento</label>
                    <input type="date" name="data_pagamento" value="{{ now('America/Sao_Paulo')->format('Y-m-d') }}" required
                           style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                </div>

                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Observação <span style="color:#9ca3af; font-weight:400; text-transform:none;">(opcional)</span></label>
                    <input type="text" name="obs" maxlength="500" placeholder="Ex: boleto, PIX, parcela 1/3"
                           style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                </div>

                <div style="display:flex; gap:8px; justify-content:flex-end;">
                    <button type="button" onclick="document.getElementById('modal-pagar-{{ $c->id }}').style.display='none'"
                            style="padding:9px 20px; border-radius:8px; border:1.5px solid #e5e7eb; background:#fff; color:#374151; font-size:14px; font-weight:600; cursor:pointer;">Cancelar</button>
                    <button type="submit"
                            style="padding:9px 24px; border-radius:8px; background:linear-gradient(90deg,#05018D,#b40000); color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">Registrar</button>
                </div>
            </form>
        </div>
    </div>
@endif
