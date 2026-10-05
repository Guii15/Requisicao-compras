@extends('layouts.app')

@section('content')

<div style="padding: 8px 0;">

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Painel Administrativo</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Gerencie todas as requisições de compra</p>
    </div>

    @include('admin._abas')

    <div style="margin-bottom:16px;">
        <h2 style="margin:0; font-size:18px; font-weight:700; color:#111827;">Compras</h2>
        <p style="margin:4px 0 0; color:#6b7280; font-size:13px;">
            Requisições aprovadas. Registre aqui os dados da compra: data, preço, fornecedor, coleta e o pedido de compra.
        </p>
    </div>

    {{-- Filtros --}}
    @php
        $filtros = [
            ''          => 'Todas',
            'sem_dados' => 'Sem dados da compra (' . $totalSemDados . ')',
            'com_dados' => 'Com dados da compra',
        ];
    @endphp
    <form method="GET" action="{{ route('admin.compras.index') }}" class="m-busca" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:16px;">
        {{-- display:contents mantém o desktop igual; no mobile vira a linha de pílulas com rolagem --}}
        <div class="m-rolagem m-pilulas" style="display:contents;">
            @foreach($filtros as $chave => $rotulo)
                @php $ativo = ($situacao ?? '') === (string) $chave; @endphp
                <a href="{{ route('admin.compras.index', array_filter(['situacao' => $chave, 'produto' => request('produto')])) }}" @class(['ativo' => $ativo])
                   style="padding:6px 14px; border-radius:20px; font-size:13px; font-weight:600; text-decoration:none;
                          {{ $ativo ? 'background:#05018D; color:#fff;' : 'background:#fff; color:#374151; border:1px solid #d1d5db;' }}">
                    {{ $rotulo }}
                </a>
            @endforeach
        </div>
        @if($situacao)
            <input type="hidden" name="situacao" value="{{ $situacao }}">
        @endif
        <input type="text" name="produto" value="{{ request('produto') }}" placeholder="Buscar produto..."
               style="margin-left:auto; padding:7px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:13px; min-width:220px;">
    </form>

    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; border:1px solid #86efac; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
            ✓ {{ session('success') }}
        </div>
    @endif

    <div class="m-desktop" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow-x:auto; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
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
                    <tr style="border-top:1px solid #f3f4f6; {{ $item->temDadosDaCompra() ? '' : 'background:#fffbeb;' }}">
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
                        <td style="padding:10px 14px;">
                            {{ $item->supplier ?: '—' }}
                            @if($item->empresa)<div style="color:#6b7280; font-size:12px;">Empresa: {{ $item->empresa }}</div>@endif
                        </td>
                        <td style="padding:10px 14px; white-space:nowrap;">{{ $item->data_compra?->format('d/m/Y') ?? '—' }}</td>
                        <td style="padding:10px 14px; text-align:right; white-space:nowrap;">{{ $item->preco_unitario !== null ? 'R$ ' . number_format($item->preco_unitario, 2, ',', '.') : '—' }}</td>
                        <td style="padding:10px 14px; text-align:right; white-space:nowrap; font-weight:600;">{{ $item->valor ? 'R$ ' . number_format($item->valor, 2, ',', '.') : '—' }}</td>
                        <td style="padding:10px 14px; white-space:nowrap;">{{ $item->data_coleta?->format('d/m/Y') ?? '—' }}</td>
                        <td style="padding:10px 14px; white-space:nowrap;">@include('admin.compras._conferencia')</td>
                        <td style="padding:10px 14px; white-space:nowrap;">{{ $item->entrada_concluida_em?->timezone('America/Sao_Paulo')->format('d/m/Y') ?? '—' }}</td>
                        <td style="padding:10px 14px;">
                            @if($item->pedido_compra_path)
                                <a href="{{ route('admin.compras.pedido', $item) }}" target="_blank" style="color:#05018D; font-weight:600;">Ver</a>
                            @else
                                <span style="color:#9ca3af;">—</span>
                            @endif
                        </td>
                        <td style="padding:10px 14px; text-align:right;">
                            <a href="{{ route('admin.compras.edit', $item) }}"
                               style="display:inline-block; padding:6px 14px; border-radius:6px; font-size:12.5px; font-weight:600; text-decoration:none; white-space:nowrap;
                                      {{ $item->temDadosDaCompra() ? 'border:1px solid #d1d5db; color:#374151; background:#fff;' : 'background:#05018D; color:#fff;' }}">
                                {{ $item->temDadosDaCompra() ? 'Editar' : 'Registrar compra' }}
                            </a>
                        </td>
                    </tr>
                    @if($item->obs)
                    <tr style="border-top:1px solid #f3f4f6; background:#f9fafb;">
                        <td colspan="11" style="padding:12px 14px;">
                            <div style="padding:10px 12px; background:#f0fdf4; border:1px solid #86efac; border-radius:8px;">
                                <span style="font-size:11px; font-weight:700; color:#15803d; text-transform:uppercase;">Obs (Conferente):</span>
                                <div style="margin-top:4px; font-size:13px; color:#166534; line-height:1.5;">{{ $item->obs }}</div>
                            </div>
                        </td>
                    </tr>
                    @endif
                    @if($item->obs_entrada)
                    <tr style="border-top:1px solid #f3f4f6; background:#f9fafb;">
                        <td colspan="11" style="padding:12px 14px;"><x-obs-entrada :item="$item" margem="0" /></td>
                    </tr>
                    @endif
                    @if(filled($item->reason) || filled($item->justification) || filled($item->observacao_conferencia))
                    <tr style="border-top:1px solid #f3f4f6; background:#f9fafb;">
                        <td colspan="11" style="padding:12px 14px;">
                            <x-obs-vendedor :item="$item" margem="8px" />
                            <x-obs-divergencia :item="$item" margem="0" />
                        </td>
                    </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="11" style="padding:40px 16px; text-align:center; color:#6b7280;">Nenhuma requisição aprovada encontrada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="m-cards">
        @forelse($itens as $item)
            @include('admin.compras._card-mobile', ['item' => $item])
        @empty
            <div style="padding:40px 16px; text-align:center; color:#6b7280;">Nenhuma requisição aprovada encontrada.</div>
        @endforelse
    </div>

    <div style="margin-top:16px;">{{ $itens->links() }}</div>
</div>

@endsection
