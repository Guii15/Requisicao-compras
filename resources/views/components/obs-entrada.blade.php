@props(['item', 'margem' => '12px'])

@if($item->obs_entrada)
    <div style="margin-bottom:{{ $margem }}; padding:10px 12px; background:#fff7ed; border:1px solid #fed7aa; border-radius:8px;">
        <span style="font-size:11px; font-weight:700; color:#c2410c; text-transform:uppercase;">Obs (Entrada):</span>
        <div style="margin-top:4px; font-size:13px; color:#9a3412; line-height:1.5; white-space:pre-line;">{{ $item->obs_entrada }}</div>
    </div>
@endif
