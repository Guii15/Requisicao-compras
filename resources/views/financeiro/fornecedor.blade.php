@extends('layouts.app')

@section('content')
@php
    use App\Support\Dinheiro;
    $cores = [
        'Em aberto' => ['bg' => '#fee2e2', 'texto' => '#dc2626'],
        'Parcial'   => ['bg' => '#fef3c7', 'texto' => '#b45309'],
        'Pago'      => ['bg' => '#dcfce7', 'texto' => '#16a34a'],
    ];
@endphp

<div style="padding: 8px 0;">

    @include('financeiro._abas')

    <a href="{{ route('financeiro.fornecedores') }}" style="display:inline-block; font-size:13px; color:#6b7280; text-decoration:none; margin-bottom:10px;">&larr; Todos os fornecedores</a>

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">{{ $nome }}</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Cada compra feita neste fornecedor. Registre os pagamentos para dar baixa no saldo.</p>
    </div>

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

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px; margin-bottom:20px;">
        <div style="background:#fff; border:1px solid #fca5a5; border-radius:12px; padding:16px;">
            <div style="font-size:11.5px; font-weight:700; color:#6b7280; text-transform:uppercase;">Saldo devedor</div>
            <div style="margin-top:6px; font-size:24px; font-weight:800; color:{{ $totais['saldo'] > 0 ? '#dc2626' : '#16a34a' }};">{{ Dinheiro::brl($totais['saldo']) }}</div>
        </div>
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px;">
            <div style="font-size:11.5px; font-weight:700; color:#6b7280; text-transform:uppercase;">Comprado</div>
            <div style="margin-top:6px; font-size:20px; font-weight:700; color:#111827;">{{ Dinheiro::brl($totais['comprado']) }}</div>
        </div>
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px;">
            <div style="font-size:11.5px; font-weight:700; color:#6b7280; text-transform:uppercase;">Pago</div>
            <div style="margin-top:6px; font-size:20px; font-weight:700; color:#16a34a;">{{ Dinheiro::brl($totais['pago']) }}</div>
        </div>
    </div>

    {{-- Desktop --}}
    <div class="m-desktop" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow-x:auto; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead>
                <tr style="background:#f9fafb; color:#6b7280; text-align:left; font-size:11.5px; text-transform:uppercase; letter-spacing:0.4px;">
                    <th style="padding:10px 14px;">Compra</th>
                    <th style="padding:10px 14px;">Requisição</th>
                    <th style="padding:10px 14px;">Produto</th>
                    <th style="padding:10px 14px;">Comprador</th>
                    <th style="padding:10px 14px; text-align:right;">Valor</th>
                    <th style="padding:10px 14px; text-align:right;">Pago</th>
                    <th style="padding:10px 14px; text-align:right;">Em aberto</th>
                    <th style="padding:10px 14px; text-align:center;">Situação</th>
                    <th style="padding:10px 14px; text-align:center;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($compras as $linha)
                    @php $c = $linha['compra']; $cor = $cores[$linha['situacao']]; @endphp
                    <tr style="border-top:1px solid #f3f4f6;">
                        <td style="padding:12px 14px; white-space:nowrap;">{{ $c->data_compra->format('d/m/Y') }}</td>
                        <td style="padding:12px 14px;">#{{ $c->id }}</td>
                        <td style="padding:12px 14px; font-weight:600; color:#111827;">
                            {{ $c->product_name }} <span style="color:#9ca3af; font-weight:400;">× {{ $c->quantity }}</span>
                            @if($c->pedido_compra_path)
                                <a href="{{ route('admin.compras.pedido', $c) }}" target="_blank" style="display:block; font-size:11px; color:#05018D; text-decoration:underline; font-weight:400;">📎 Pedido de compra</a>
                            @endif
                        </td>
                        <td style="padding:12px 14px;">{{ $c->requester_name ?? '—' }}</td>
                        <td style="padding:12px 14px; text-align:right; white-space:nowrap;">{{ Dinheiro::brl($linha['custo']) }}</td>
                        <td style="padding:12px 14px; text-align:right; white-space:nowrap; color:#16a34a;">{{ Dinheiro::brl($linha['pago']) }}</td>
                        <td style="padding:12px 14px; text-align:right; white-space:nowrap; font-weight:700;">{{ Dinheiro::brl($linha['aberto']) }}</td>
                        <td style="padding:12px 14px; text-align:center;">
                            <span style="background:{{ $cor['bg'] }}; color:{{ $cor['texto'] }}; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; white-space:nowrap;">{{ $linha['situacao'] }}</span>
                        </td>
                        <td style="padding:12px 14px; text-align:center;">
                            @if($linha['aberto'] > 0)
                                <button type="button" onclick="document.getElementById('modal-pagar-{{ $c->id }}').style.display='flex'"
                                        style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:6px 14px; font-size:12px; font-weight:600; cursor:pointer;">Pagar</button>
                            @endif
                        </td>
                    </tr>
                    @if($c->pagamentos->isNotEmpty())
                        <tr style="background:#f9fafb;">
                            <td colspan="9" style="padding:8px 14px 10px 40px;">@include('financeiro._pagamentos', ['compra' => $c])</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile --}}
    <div class="m-cards">
        @foreach($compras as $linha)
            @php $c = $linha['compra']; $cor = $cores[$linha['situacao']]; @endphp
            <x-mobile-card :titulo="$c->product_name . ' × ' . $c->quantity"
                           :campos="['Compra' => $c->data_compra->format('d/m/Y'), 'Requisição' => '#' . $c->id, 'Comprador' => $c->requester_name, 'Valor' => Dinheiro::brl($linha['custo']), 'Pago' => Dinheiro::brl($linha['pago']), 'Em aberto' => Dinheiro::brl($linha['aberto'])]">
                <x-slot:badge>
                    <span style="background:{{ $cor['bg'] }}; color:{{ $cor['texto'] }}; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">{{ $linha['situacao'] }}</span>
                </x-slot:badge>
                @if($c->pagamentos->isNotEmpty())
                    <div style="margin-top:8px;">@include('financeiro._pagamentos', ['compra' => $c])</div>
                @endif
                @if($linha['aberto'] > 0)
                    <x-slot:acao>
                        <button type="button" onclick="document.getElementById('modal-pagar-{{ $c->id }}').style.display='flex'"
                                style="background:#05018D; color:#fff; border:none; border-radius:8px; padding:8px 18px; font-size:14px; font-weight:600; cursor:pointer;">Pagar</button>
                    </x-slot:acao>
                @endif
            </x-mobile-card>
        @endforeach
    </div>

</div>

{{-- Quadros de pagamento (um por compra em aberto, usados pelo desktop e pelo celular) --}}
@foreach($compras as $linha)
    @include('financeiro._modal-pagar', ['linha' => $linha])
@endforeach

@include('financeiro._scripts')
@endsection
