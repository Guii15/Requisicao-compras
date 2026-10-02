{{-- Motivo e observação que o vendedor escreveu ao pedir. Acompanham a requisição até o fim. --}}
@props(['item', 'margem' => '12px'])

@if(filled($item->reason) || filled($item->justification))
    <div style="margin-bottom:{{ $margem }}; padding:10px 12px; background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px;">
        @if(filled($item->reason))
            <div style="font-size:13px; color:#334155; line-height:1.5;">
                <span style="font-size:11px; font-weight:700; color:#475569; text-transform:uppercase;">Motivo (Vendedor):</span>
                <span style="white-space:pre-line;">{{ $item->reason }}</span>
            </div>
        @endif
        @if(filled($item->justification))
            <div style="{{ filled($item->reason) ? 'margin-top:6px;' : '' }}">
                <span style="font-size:11px; font-weight:700; color:#475569; text-transform:uppercase;">Obs (Vendedor):</span>
                <div style="margin-top:4px; font-size:13px; color:#334155; line-height:1.5; white-space:pre-line;">{{ $item->justification }}</div>
            </div>
        @endif
    </div>
@endif
