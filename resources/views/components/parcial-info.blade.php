@props(['item'])

@if($item->quantidade_original)
    <div style="display:inline-block; margin-top:3px; padding:2px 8px; background:#f5f3ff; border:1px solid #ddd6fe; border-radius:20px; font-size:11.5px; font-weight:600; color:#6d28d9;">
        Parcial · {{ $item->quantity }} de {{ $item->quantidade_original }} un.@if($item->restante_de_id) · restante da requisição #{{ $item->restante_de_id }}@endif
    </div>
@endif
