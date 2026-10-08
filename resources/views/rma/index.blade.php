@extends('layouts.app')

{{-- Listagem usa a largura toda da tela (o layout lê esta seção) --}}
@section('tela_cheia', '1')

@section('content')

<div style="padding: 8px 0;">

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">RMA</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Produtos comprados que já chegaram: fornecedor, empresa da compra, data, quantidade e fotos da conferência</p>
    </div>

    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
        <form method="GET" action="{{ route('rma.index') }}" class="m-busca" style="display:flex; gap:8px; flex-wrap:wrap; align-items:flex-end;">
            <div style="flex:1; min-width:200px;">
                <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase; letter-spacing:0.5px;">Produto, fornecedor ou empresa</label>
                <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por produto, código, fornecedor ou empresa..."
                       style="width:100%; border:1px solid #d1d5db; border-radius:7px; padding:9px 14px; font-size:14px; box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase; letter-spacing:0.5px;">Compra de</label>
                <input type="date" name="data_inicial" value="{{ $dataInicial }}"
                       style="border:1px solid #d1d5db; border-radius:7px; padding:8px 12px; font-size:14px;">
            </div>
            <div>
                <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase; letter-spacing:0.5px;">Compra até</label>
                <input type="date" name="data_final" value="{{ $dataFinal }}"
                       style="border:1px solid #d1d5db; border-radius:7px; padding:8px 12px; font-size:14px;">
            </div>
            <button type="submit" style="background:#05018D; color:#fff; padding:9px 20px; border:none; border-radius:7px; font-size:14px; font-weight:600; cursor:pointer; white-space:nowrap;">
                Buscar
            </button>
            @if($q !== '' || $dataInicial !== '' || $dataFinal !== '')
                <a href="{{ route('rma.index') }}" style="padding:9px 16px; border-radius:7px; border:1px solid #e5e7eb; color:#6b7280; text-decoration:none; font-size:14px; white-space:nowrap;">
                    Limpar
                </a>
            @endif
        </form>
    </div>

    @if($errors->any())
        <div style="background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
            @foreach($errors->all() as $erro)
                <div>{{ $erro }}</div>
            @endforeach
        </div>
    @endif

    <div class="lista-resp" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fafc; border-bottom:1px solid #e5e7eb;">
                        @php $thRma = 'padding:12px 16px; color:#6b7280; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; white-space:nowrap;'; @endphp
                        <th style="{{ $thRma }} text-align:left;">Produto</th>
                        <th style="{{ $thRma }} text-align:left;">Fornecedor</th>
                        <th style="{{ $thRma }} text-align:left;">Empresa</th>
                        <th style="{{ $thRma }} text-align:left;">Data da compra</th>
                        <th style="{{ $thRma }} text-align:right;">Quantidade</th>
                        <th style="{{ $thRma }} text-align:left;">Fotos</th>
                        <th style="{{ $thRma }} text-align:left;">Pedido de compra</th>
                    </tr>
                </thead>
                <tbody>
                    @php $tdRma = 'padding:12px 16px; font-size:14px; color:#374151; vertical-align:middle;'; @endphp
                    @forelse($itens as $item)
                        <tr class="grupo-cabecalho" style="border-bottom:1px solid #eef0f3;">
                            <td class="lr-num" style="{{ $tdRma }} font-weight:600; color:#111827; overflow-wrap:anywhere;">
                                {{ $item->product_name }}
                                @if($item->product_code)
                                    <span style="display:block; font-size:12px; font-weight:400; color:#6b7280;">Cód. {{ $item->product_code }}</span>
                                @endif
                            </td>
                            <td data-rotulo="Fornecedor" style="{{ $tdRma }} overflow-wrap:anywhere;">{{ $item->supplier ?: '—' }}</td>
                            <td data-rotulo="Empresa" style="{{ $tdRma }} overflow-wrap:anywhere;">{{ $item->empresa ?: '—' }}</td>
                            <td data-rotulo="Data da compra"style="{{ $tdRma }} white-space:nowrap;">{{ $item->data_compra ? $item->data_compra->format('d/m/Y') : '—' }}</td>
                            <td data-rotulo="Quantidade" style="{{ $tdRma }} text-align:right; white-space:nowrap;">
                                {{ $item->quantity }}
                                @if($item->quantidade_entrada !== null && (int) $item->quantidade_entrada !== (int) $item->quantity)
                                    <span style="display:block; font-size:12px; color:#6b7280;">recebido {{ $item->quantidade_entrada }}</span>
                                @endif
                            </td>
                            <td data-rotulo="Fotos" style="{{ $tdRma }}">
                                <x-fotos-conferencia :item="$item" :tamanho="56" vazio="—" />
                            </td>
                            <td data-rotulo="Pedido de compra" style="{{ $tdRma }}">
                                @if($item->pedido_compra_path)
                                    <a href="{{ route('admin.compras.pedido', $item) }}" target="_blank" style="color:#05018D; font-weight:600; font-size:13px; text-decoration:underline;">📎 Ver pedido</a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7"style="padding:40px 16px; text-align:center; color:#9ca3af; font-size:14px;">
                                Nenhum item encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($itens->hasPages())
        <div style="margin-top:16px;">{{ $itens->links() }}</div>
    @endif

</div>

@endsection
