@extends('layouts.app')

@section('content')

<div style="padding: 8px 0;">

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Painel Administrativo</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Gerencie todas as requisições de compra</p>
    </div>

    @include('admin._abas')

    <div style="margin-bottom:16px; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:12px;">
        <div>
            <h2 style="margin:0; font-size:18px; font-weight:700; color:#111827;">Compras Feitas</h2>
            <p style="margin:4px 0 0; color:#6b7280; font-size:13px;">
                Requisições aprovadas que já têm os dados da compra registrados. Falta alguma? Registre em
                <a href="{{ route('admin.compras.index') }}" style="color:#05018D; font-weight:600;">Compras</a>.
            </p>
        </div>
        <form method="GET" action="{{ route('admin.compras.feitas') }}">
            <input type="text" name="produto" value="{{ request('produto') }}" placeholder="Buscar produto..."
                   style="padding:7px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:13px; min-width:220px;">
        </form>
    </div>

    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow-x:auto; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead>
                <tr style="background:#f9fafb; color:#6b7280; text-align:left; font-size:11.5px; text-transform:uppercase; letter-spacing:0.4px;">
                    <th style="padding:10px 14px;">Produto</th>
                    <th style="padding:10px 14px;">Qtd</th>
                    <th style="padding:10px 14px;">Fornecedor</th>
                    <th style="padding:10px 14px;">Compra</th>
                    <th style="padding:10px 14px; text-align:right;">Unitário</th>
                    <th style="padding:10px 14px; text-align:right;">Total</th>
                    <th style="padding:10px 14px;">Coleta</th>
                    <th style="padding:10px 14px;">Conferência</th>
                    <th style="padding:10px 14px;">Entrada</th>
                    <th style="padding:10px 14px;">Pedido</th>
                    <th style="padding:10px 14px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($itens as $item)
                    <tr style="border-top:1px solid #f3f4f6;">
                        <td style="padding:10px 14px; color:#111827;">
                            <strong>{{ $item->product_name }}</strong>
                            @if($item->codigo_fornecedor)
                                <div style="color:#9ca3af; font-size:12px;">Cód. fornecedor: {{ $item->codigo_fornecedor }}</div>
                            @endif
                            @if($item->requester_name)
                                <div style="color:#9ca3af; font-size:12px;">{{ $item->requester_name }}</div>
                            @endif
                        </td>
                        <td style="padding:10px 14px;">{{ $item->quantity }}</td>
                        <td style="padding:10px 14px;">{{ $item->supplier ?: '—' }}</td>
                        <td style="padding:10px 14px; white-space:nowrap;">{{ $item->data_compra?->format('d/m/Y') ?? '—' }}</td>
                        <td style="padding:10px 14px; text-align:right; white-space:nowrap;">{{ $item->preco_unitario !== null ? 'R$ ' . number_format($item->preco_unitario, 2, ',', '.') : '—' }}</td>
                        <td style="padding:10px 14px; text-align:right; white-space:nowrap; font-weight:600;">{{ $item->valor ? 'R$ ' . number_format($item->valor, 2, ',', '.') : '—' }}</td>
                        <td style="padding:10px 14px; white-space:nowrap;">{{ $item->data_coleta?->format('d/m/Y') ?? '—' }}</td>
                        <td style="padding:10px 14px; white-space:nowrap;">@include('admin.compras._conferencia')</td>
                        <td style="padding:10px 14px; white-space:nowrap;">{{ $item->entrada_concluida_em?->timezone('America/Sao_Paulo')->format('d/m/Y') ?? '—' }}</td>
                        <td style="padding:10px 14px;">
                            @if($item->pedido_compra_path)
                                <a href="{{ route('admin.compras.pedido', $item) }}" style="color:#05018D; font-weight:600;">Baixar</a>
                            @else
                                <span style="color:#9ca3af;">—</span>
                            @endif
                        </td>
                        <td style="padding:10px 14px; text-align:right;">
                            <a href="{{ route('admin.compras.edit', $item) }}"
                               style="display:inline-block; padding:6px 14px; border-radius:6px; font-size:12.5px; font-weight:600; text-decoration:none; white-space:nowrap; border:1px solid #d1d5db; color:#374151; background:#fff;">
                                Editar
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" style="padding:40px 16px; text-align:center; color:#6b7280;">Nenhuma compra registrada ainda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:16px;">{{ $itens->links() }}</div>
</div>

@endsection
