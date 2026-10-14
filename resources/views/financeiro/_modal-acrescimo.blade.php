{{-- Quadro "Lançar acréscimo" de uma compra (impostos, frete...). Uso: @include('financeiro._modal-acrescimo', ['linha' => $linha]) --}}
@php use App\Support\Dinheiro; $c = $linha['compra']; @endphp
<div id="modal-acrescimo-{{ $c->id }}" style="display:none; position:fixed; inset:0; background:rgba(15, 23, 42, 0.6); backdrop-filter:blur(3px); z-index:1000; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; width:100%; max-width:520px; max-height:94vh; display:flex; flex-direction:column; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); overflow:hidden; text-align:left;">
        <div style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:flex-start; background:#f8fafc;">
            <div>
                <h3 style="margin:0; font-family:'Playfair Display', Georgia, serif; font-size:22px; font-weight:400; color:#0f172a; letter-spacing:0.01em;">Lançar acréscimo</h3>
                <div style="margin-top:4px; font-size:13px; color:#64748b;"><strong style="color:#1e293b;">{{ $linha['fornecedor'] }}</strong> · {{ $c->product_name }} · requisição #{{ $c->id }}</div>
            </div>
            <button type="button" onclick="document.getElementById('modal-acrescimo-{{ $c->id }}').style.display='none'" aria-label="Fechar"
                    style="border:none; background:transparent; color:#94a3b8; font-size:18px; cursor:pointer; padding:4px 8px; border-radius:4px; line-height:1;">✕</button>
        </div>
        <div style="padding:20px 24px; overflow-y:auto;">
            <p style="margin:0 0 6px; font-size:13.5px; color:#111827;">Valor da compra: <strong>{{ Dinheiro::brl($linha['custo']) }}</strong>@if($linha['acrescimo'] > 0) · com acréscimos: <strong>{{ Dinheiro::brl($linha['custo_total']) }}</strong>@endif</p>
            <p style="margin:0 0 16px; font-size:12.5px; color:#6b7280;">O acréscimo é o que a compra custou a mais para a empresa (impostos, frete...). Ele soma no custo real, mas não aumenta o que falta pagar ao fornecedor.</p>

            <form method="POST" action="{{ route('financeiro.acrescentar', $c) }}" onsubmit="return protegerEnvioDuplo(this)">
                @csrf
                <div style="display:grid; grid-template-columns:1fr 2fr; gap:10px; margin-bottom:18px;">
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Valor (R$) <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="valor" inputmode="decimal" placeholder="Ex: 1.000,00" required
                               style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Motivo <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="motivo" maxlength="200" placeholder="Ex: impostos, frete, DIFAL" required
                               style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box;">
                    </div>
                </div>

                <div style="margin:4px -24px -20px; padding:16px 24px; border-top:1px solid #e2e8f0; background:#f8fafc; display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" onclick="document.getElementById('modal-acrescimo-{{ $c->id }}').style.display='none'"
                            style="padding:8px 18px; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; color:#475569; font-size:13px; font-weight:600; cursor:pointer;">Cancelar</button>
                    <button type="submit"
                            style="padding:8px 22px; border-radius:6px; background:#05018D; color:#ffffff; font-size:13px; font-weight:600; border:none; cursor:pointer;">Lançar</button>
                </div>
            </form>
        </div>
    </div>
</div>
