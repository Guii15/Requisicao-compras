@extends('layouts.app')

{{-- Mesma largura das outras listagens do sistema (o layout lê esta seção) --}}
@section('tela_cheia', '1')

@section('content')
@php use App\Support\Dinheiro; @endphp

<div style="padding: 8px 0;">

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Financeiro</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Contas a pagar por fornecedor: cada compra feita soma no saldo e cada pagamento dá baixa.</p>
    </div>

    @include('financeiro._abas')
    @include('financeiro._filtro-empresa')

    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; border:1px solid #86efac; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:14px;">✓ {{ session('success') }}</div>
    @endif
    @if(session('aviso'))
        <div style="background:#fef3c7; color:#92400e; border:1px solid #fde68a; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:14px;">⚠️ {{ session('aviso') }}</div>
    @endif

    {{-- Mesma faixa de números das outras telas --}}
    <div class="idx-stats bm-faixa" style="display:grid; grid-template-columns:repeat(3,1fr); background:#fff; border:1px solid #e5e7eb; border-radius:10px; margin-bottom:20px; overflow:hidden;">
        <x-bloco-metrica rotulo="Saldo devedor total" :valor="Dinheiro::brl($totais['saldo'])" :sem-linha="true" />
        <x-bloco-metrica rotulo="Total comprado" :valor="Dinheiro::brl($totais['comprado'])" :sem-linha="true" />
        <x-bloco-metrica rotulo="Total pago" :valor="Dinheiro::brl($totais['pago'])" :sem-linha="true" />
    </div>

    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:14px 16px; margin-bottom:16px;">
        <form method="GET" action="{{ route('financeiro.fornecedores') }}" style="display:flex; gap:8px; align-items:end; flex-wrap:wrap;">
            @if(request('empresa'))<input type="hidden" name="empresa" value="{{ request('empresa') }}">@endif
            <div style="flex:1; min-width:200px;">
                <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:4px; text-transform:uppercase;">Fornecedor</label>
                <input type="text" name="q" value="{{ $q }}" placeholder="Buscar fornecedor..."
                       style="width:100%; padding:7px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:13px; box-sizing:border-box;">
            </div>
            <button type="submit" style="padding:8px 16px; background:#05018D; color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer;">Buscar</button>
            @if($q !== '')
                <a href="{{ route('financeiro.fornecedores', array_filter(['empresa' => request('empresa')])) }}" style="padding:8px 14px; border-radius:8px; border:1px solid #e5e7eb; color:#6b7280; text-decoration:none; font-size:13px;">Limpar</a>
            @endif
        </form>
    </div>

    {{-- Uma marcação só: no celular a tabela vira cartões (CSS .lista-resp no layout) --}}
    <div class="lista-resp" style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead>
                <tr style="background:#f9fafb; color:#6b7280; text-align:left; font-size:11.5px; text-transform:uppercase; letter-spacing:0.4px;">
                    <th style="padding:10px 14px;">Fornecedor</th>
                    <th style="padding:10px 14px; text-align:center;">Compras</th>
                    <th style="padding:10px 14px; text-align:right;">Comprado</th>
                    <th style="padding:10px 14px; text-align:right;">Pago</th>
                    <th style="padding:10px 14px; text-align:right;">Saldo devedor</th>
                    <th style="padding:10px 14px; text-align:center;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($fornecedores as $f)
                    <tr class="grupo-cabecalho" style="border-top:1px solid #f3f4f6;">
                        <td class="lr-num" style="padding:12px 14px; font-weight:600; color:#111827;">{{ $f['nome'] }}</td>
                        <td data-rotulo="Compras" style="padding:12px 14px; text-align:center; color:#6b7280;">{{ $f['compras'] }}</td>
                        <td data-rotulo="Comprado" style="padding:12px 14px; text-align:right; white-space:nowrap;">{{ Dinheiro::brl($f['comprado']) }}</td>
                        <td data-rotulo="Pago" style="padding:12px 14px; text-align:right; white-space:nowrap;">{{ Dinheiro::brl($f['pago']) }}</td>
                        <td data-rotulo="Saldo devedor" style="padding:12px 14px; text-align:right; white-space:nowrap; font-weight:700; color:#111827;">{{ $f['saldo'] > 0 ? Dinheiro::brl($f['saldo']) : 'Quitado' }}</td>
                        <td class="lr-acao" style="padding:12px 14px; text-align:center;">
                            <a href="{{ route('financeiro.fornecedor', array_filter(['chave' => $f['chave'], 'empresa' => request('empresa')])) }}"
                               style="background:#fff; color:#374151; border:1px solid #d1d5db; border-radius:9999px; padding:7px 18px; font-size:12.5px; font-weight:600; text-decoration:none; white-space:nowrap;">Abrir</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="padding:40px 16px; text-align:center; color:#9ca3af;">Nenhuma compra com fornecedor registrada ainda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
