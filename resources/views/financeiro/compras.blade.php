@extends('layouts.app')

{{-- Mesma largura das outras listagens do sistema (o layout lê esta seção) --}}
@section('tela_cheia', '1')

@section('content')
@php
    use App\Support\Dinheiro;
    $aguardando = $modo === 'aguardando';
    $rota = $aguardando ? 'financeiro.aguardando' : 'financeiro.pagos';
    $dias = fn ($compra) => max(0, (int) $compra->data_compra->startOfDay()->diffInDays(now('America/Sao_Paulo')->startOfDay(), false));
    // Selos em contorno, como o status dos itens nas outras telas: a cor só marca a situação.
    $cores = [
        'Em aberto' => 'color:#b8301a; border:1px solid #b8301a;',
        'Parcial'   => 'color:#7a4f00; border:1px solid #c98a00;',
        'Pago'      => 'color:#17794a; border:1px solid #17794a;',
    ];
    $selo = fn ($situacao) => '<span style="display:inline-block; background:#fff; ' . $cores[$situacao] . ' padding:3px 12px; border-radius:9999px; font-size:12px; font-weight:700; white-space:nowrap;">' . e($situacao) . '</span>';
@endphp

<div style="padding: 8px 0;">

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Financeiro</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">
            {{ $aguardando ? 'Compras que ainda têm algo a pagar, das mais novas para as mais antigas.' : 'Compras já quitadas, das pagas mais recentemente para as mais antigas.' }}
        </p>
    </div>

    @include('financeiro._abas')
    @include('financeiro._filtro-empresa')

    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; border:1px solid #86efac; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:14px;">✓ {{ session('success') }}</div>
    @endif
    @if(session('aviso'))
        <div style="background:#fef3c7; color:#92400e; border:1px solid #fde68a; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:14px;">⚠️ {{ session('aviso') }}</div>
    @endif
    @if($errors->any())
        <div style="background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:14px;">
            <strong>Não foi possível registrar o pagamento:</strong>
            <ul style="margin:6px 0 0; padding-left:18px;">@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Mesma faixa de números das outras telas --}}
    <div class="idx-stats bm-faixa" style="display:grid; grid-template-columns:repeat(3,1fr); background:#fff; border:1px solid #e5e7eb; border-radius:10px; margin-bottom:16px; overflow:hidden;">
        <x-bloco-metrica :rotulo="($aguardando ? 'Falta pagar' : 'Total pago') . ($q !== '' ? ' (na busca)' : '')" :valor="Dinheiro::brl($total)" :sem-linha="true"
                         :nota="$itens->total() . ' ' . ($itens->total() === 1 ? 'compra' : 'compras')" />
    </div>

    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:14px 16px; margin-bottom:16px;">
        <form method="GET" action="{{ route($rota) }}" style="display:flex; gap:8px; align-items:end; flex-wrap:wrap;">
            @if(request('empresa'))<input type="hidden" name="empresa" value="{{ request('empresa') }}">@endif
            <div style="flex:1; min-width:220px;">
                <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:4px; text-transform:uppercase;">Buscar</label>
                <input type="text" name="q" value="{{ $q }}" placeholder="Fornecedor, produto, comprador ou nº da requisição..."
                       style="width:100%; padding:7px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:13px; box-sizing:border-box;">
            </div>
            <button type="submit" style="padding:8px 16px; background:#05018D; color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer;">Buscar</button>
            @if($q !== '')
                <a href="{{ route($rota, array_filter(['empresa' => request('empresa')])) }}" style="padding:8px 14px; border-radius:8px; border:1px solid #e5e7eb; color:#6b7280; text-decoration:none; font-size:13px;">Limpar</a>
            @endif
        </form>
    </div>

    {{-- Uma marcação só: no celular a tabela vira cartões (CSS .lista-resp no layout) --}}
    <div class="lista-resp" style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead>
                <tr style="background:#f9fafb; color:#6b7280; text-align:left; font-size:11.5px; text-transform:uppercase; letter-spacing:0.4px;">
                    <th style="padding:10px 9px;">Compra</th>
                    <th style="padding:10px 9px;">Fornecedor</th>
                    <th style="padding:10px 9px;">Produto</th>
                    <th style="padding:10px 9px;">Comprador</th>
                    <th style="padding:10px 9px; text-align:right;">Valor</th>
                    <th style="padding:10px 9px; text-align:right;">{{ $aguardando ? 'Em aberto' : 'Pago' }}</th>
                    <th style="padding:10px 9px; text-align:center;">{{ $aguardando ? 'Vencimento' : 'Pago em' }}</th>
                    <th style="padding:10px 9px; text-align:center;">Situação</th>
                    <th style="padding:10px 9px; text-align:center;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($itens as $linha)
                    @php $c = $linha['compra']; @endphp
                    <tr class="grupo-cabecalho" style="border-top:1px solid #f3f4f6;">
                        <td data-rotulo="Compra" style="padding:12px 9px; white-space:nowrap;">{{ $c->data_compra->format('d/m/Y') }}@if($aguardando)<div style="font-size:11.5px; color:#9ca3af;">há {{ $dias($c) }} {{ $dias($c) === 1 ? 'dia' : 'dias' }}</div>@endif</td>
                        <td class="lr-num" style="padding:12px 9px; font-weight:600;"><a href="{{ route('financeiro.fornecedor', array_filter(['chave' => $linha['chave'], 'empresa' => request('empresa')])) }}" style="color:#111827; text-decoration:none;">{{ $linha['fornecedor'] }}</a></td>
                        <td class="lr-larga" data-rotulo="Produto" style="padding:12px 9px; color:#111827;">
                            {{ $c->product_name }} <span style="color:#9ca3af;">× {{ $c->quantity }}</span>
                            <div style="font-size:11.5px; color:#9ca3af;">req. #{{ $c->id }}@if($linha['condicao']) · {{ $linha['condicao'] }}@endif</div>
                        </td>
                        <td data-rotulo="Comprador" style="padding:12px 9px;">{{ $c->requester_name ?? '—' }}</td>
                        <td data-rotulo="Valor" style="padding:12px 9px; text-align:right; white-space:nowrap;">{{ Dinheiro::brl($linha['custo']) }}</td>
                        <td data-rotulo="{{ $aguardando ? 'Em aberto' : 'Pago' }}" style="padding:12px 9px; text-align:right; white-space:nowrap; font-weight:700; color:#111827;">{{ Dinheiro::brl($aguardando ? $linha['aberto'] : $linha['pago']) }}</td>
                        <td data-rotulo="{{ $aguardando ? 'Vencimento' : 'Pago em' }}" style="padding:12px 9px; text-align:center; white-space:nowrap; color:#6b7280;">
                            @if($aguardando)
                                @if($linha['proximo_vencimento'])
                                <div style="font-weight:600; color:#111827;">{{ $linha['proximo_vencimento']->format('d/m/Y') }}</div>
                                @if($linha['vencida'])
                                    <div style="font-size:11.5px; color:#b8301a; font-weight:700;">Vencida · {{ Dinheiro::brl($linha['vencido']) }}</div>
                                @else
                                    <div style="font-size:11.5px; color:#9ca3af;">{{ $linha['dias_ate_vencimento'] === 0 ? 'vence hoje' : 'em ' . $linha['dias_ate_vencimento'] . ' ' . ($linha['dias_ate_vencimento'] === 1 ? 'dia' : 'dias') }}</div>
                                @endif
                            @else
                                —
                            @endif
                            @else
                                {{ $linha['ultimo_pagamento']?->format('d/m/Y') ?? '—' }}
                            @endif
                        </td>
                        <td data-rotulo="Situação" style="padding:12px 9px; text-align:center;">{!! $selo($linha['situacao']) !!}</td>
                        <td class="lr-acao" style="padding:12px 9px; text-align:center; white-space:nowrap;">
                            @if($linha['aberto'] > 0)
                                <button type="button" onclick="document.getElementById('modal-pagar-{{ $c->id }}').style.display='flex'"
                                        style="background:#05018D; color:#fff; border:1px solid #05018D; border-radius:9999px; padding:7px 18px; font-size:12.5px; font-weight:600; cursor:pointer;">Pagar</button>
                            @else
                                <a href="{{ route('financeiro.fornecedor', array_filter(['chave' => $linha['chave'], 'empresa' => request('empresa')])) }}" style="color:#05018D; font-size:12px; font-weight:600;">Ver</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="padding:40px 16px; text-align:center; color:#9ca3af;">
                        {{ $q !== '' ? 'Nenhuma compra encontrada para essa busca.' : ($aguardando ? '✓ Nada aguardando pagamento.' : 'Nenhuma compra quitada ainda.') }}
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:16px;">{{ $itens->links() }}</div>

</div>

@foreach($itens as $linha)
    @include('financeiro._modal-pagar', ['linha' => $linha])
@endforeach

@include('financeiro._scripts')
@endsection
