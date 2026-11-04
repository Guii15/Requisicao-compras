{{-- Botão "Enviar ao RMA" de um item já conferido. Abre a janela única rma._modal-enviar.
     Some quando todas as unidades que chegaram já foram mandadas ao RMA. --}}
@props(['item'])
@php
    $chegouRma = (int) ($item->entrada_concluida_em ? ($item->quantidade_entrada ?? $item->quantidade_recebida) : $item->quantidade_recebida);
    $restaRma = max(0, $chegouRma - (int) $item->enviosRma->sum('quantidade'));
@endphp
@if($item->enviosRma->isNotEmpty())
    <span style="align-self:center; background:#fef2f2; color:#b8301a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">No RMA: {{ $item->enviosRma->sum('quantidade') }} un.</span>
@endif
@if($restaRma > 0)
    <button type="button" onclick="abrirEnviarRma(this)"
            data-url="{{ route('rma.enviar', $item) }}"
            data-produto="{{ $item->product_name }}"
            data-max="{{ $restaRma }}"
            style="background:#fff; color:#b8301a; border:1px solid #e3b4ab; border-radius:9999px; padding:7px 16px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap;">
        🔧 Enviar ao RMA
    </button>
@endif
