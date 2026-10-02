{{-- O que a conferência escreveu sobre a divergência (ou o aviso de recebimento parcial). Aparece para admin, conferência e entrada. --}}
@props(['item', 'margem' => '12px'])

@if(filled($item->observacao_conferencia))
    <div style="margin-bottom:{{ $margem }}; padding:10px 12px; background:#fef2f2; border:1px solid #fecaca; border-radius:8px;">
        <span style="font-size:11px; font-weight:700; color:#b91c1c; text-transform:uppercase;">Divergência (Conferência):</span>
        <div style="margin-top:4px; font-size:13px; color:#991b1b; line-height:1.5; white-space:pre-line;">{{ $item->observacao_conferencia }}</div>
    </div>
@endif
