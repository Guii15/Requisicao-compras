{{-- Card mobile de um item de compra (telas Compras e Compras Feitas). $classe/$estilo: usados nos grupos recolhíveis. --}}
@php
    $comDados = $item->temDadosDaCompra();
    $real = fn ($valor) => $valor !== null && $valor !== '' ? 'R$ ' . number_format($valor, 2, ',', '.') : null;
@endphp
<x-mobile-card :titulo="$item->product_name" :campos="[
        'Vendedor' => $item->requester_name,
        'Cód. fornecedor' => $item->codigo_fornecedor,
        'Fornecedor' => $item->supplier,
        'Empresa' => $item->empresa,
        'Quantidade' => $item->quantity,
        'Compra' => $item->data_compra?->format('d/m/Y'),
        'Unitário' => $real($item->preco_unitario),
        'Total' => $item->valor ? $real($item->valor) : null,
        'Coleta' => $item->data_coleta?->format('d/m/Y'),
        'Entrada' => $item->entrada_concluida_em?->timezone('America/Sao_Paulo')->format('d/m/Y'),
    ]"
    class="{{ $classe ?? '' }}"
    style="{{ $estilo ?? '' }} {{ $comDados ? '' : 'border-left:4px solid #f59e0b;' }}">
    <x-slot:badge>
        <span style="font-size:12px;">@include('admin.compras._conferencia')</span>
    </x-slot:badge>

    @if($item->obs)
        <div style="margin-top:8px; padding:10px 12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">
            <span style="font-size:11px; font-weight:700; color:#15803d; text-transform:uppercase;">Obs (Conferente):</span>
            <div style="margin-top:4px; font-size:13px; color:#166534; line-height:1.5;">{{ $item->obs }}</div>
        </div>
    @endif

    <div style="margin-top:8px;">
        <x-obs-vendedor :item="$item" margem="8px" />
        <x-obs-divergencia :item="$item" margem="0" />
    </div>

    <x-obs-entrada :item="$item" margem="0" />

    <x-slot:acao>
        <a href="{{ route('admin.compras.edit', $item) }}"
           style="border-radius:8px; font-size:14px; font-weight:600; text-decoration:none;
                  {{ $comDados ? 'border:1px solid #d1d5db; color:#374151; background:#fff;' : 'background:#05018D; color:#fff;' }}">
            {{ $comDados ? 'Editar' : 'Registrar compra' }}
        </a>
        @if($item->pedido_compra_path)
            <a href="{{ route('admin.compras.pedido', $item) }}" target="_blank"
               style="border:1px solid #c7d2fe; background:#eef2ff; color:#05018D; border-radius:8px; font-size:14px; font-weight:600; text-decoration:none;">
                📎 Ver pedido de compra
            </a>
        @endif
    </x-slot:acao>
</x-mobile-card>
