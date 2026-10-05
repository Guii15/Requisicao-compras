{{-- Observação geral que o conferente escreveu ao conferir o item. --}}
@props(['item', 'margem' => '12px'])

@if(filled($item->obs))
    <div style="margin-bottom:{{ $margem }}; padding:10px 12px; background:#f0fdf4; border:1px solid #86efac; border-radius:8px;">
        <span style="font-size:11px; font-weight:700; color:#15803d; text-transform:uppercase;">Obs (Conferente):</span>
        <div style="margin-top:4px; font-size:13px; color:#166534; line-height:1.5; white-space:pre-line;">{{ $item->obs }}</div>
    </div>
@endif
