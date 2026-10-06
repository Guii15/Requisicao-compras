@extends('layouts.app')

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

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px; margin-bottom:20px;">
        <div style="background:#fff; border:1px solid #fca5a5; border-radius:12px; padding:16px; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
            <div style="font-size:11.5px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.4px;">Saldo devedor total</div>
            <div style="margin-top:6px; font-size:26px; font-weight:800; color:#dc2626;">{{ Dinheiro::brl($totais['saldo']) }}</div>
        </div>
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
            <div style="font-size:11.5px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.4px;">Total comprado</div>
            <div style="margin-top:6px; font-size:22px; font-weight:700; color:#111827;">{{ Dinheiro::brl($totais['comprado']) }}</div>
        </div>
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
            <div style="font-size:11.5px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.4px;">Total pago</div>
            <div style="margin-top:6px; font-size:22px; font-weight:700; color:#16a34a;">{{ Dinheiro::brl($totais['pago']) }}</div>
        </div>
    </div>

    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:16px; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
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

    <div class="m-desktop" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow-x:auto; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
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
                    <tr style="border-top:1px solid #f3f4f6;">
                        <td style="padding:12px 14px; font-weight:600; color:#111827;">{{ $f['nome'] }}</td>
                        <td style="padding:12px 14px; text-align:center; color:#6b7280;">{{ $f['compras'] }}</td>
                        <td style="padding:12px 14px; text-align:right; white-space:nowrap;">{{ Dinheiro::brl($f['comprado']) }}</td>
                        <td style="padding:12px 14px; text-align:right; white-space:nowrap; color:#16a34a;">{{ Dinheiro::brl($f['pago']) }}</td>
                        <td style="padding:12px 14px; text-align:right; white-space:nowrap; font-weight:700; color:{{ $f['saldo'] > 0 ? '#dc2626' : '#16a34a' }};">{{ Dinheiro::brl($f['saldo']) }}</td>
                        <td style="padding:12px 14px; text-align:center;">
                            <a href="{{ route('financeiro.fornecedor', array_filter(['chave' => $f['chave'], 'empresa' => request('empresa')])) }}"
                               style="background:#05018D; color:#fff; border-radius:7px; padding:6px 14px; font-size:12px; font-weight:600; text-decoration:none;">Abrir</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="padding:40px 16px; text-align:center; color:#9ca3af;">Nenhuma compra com fornecedor registrada ainda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="m-cards">
        @forelse($fornecedores as $f)
            <x-mobile-card :titulo="$f['nome']" :campos="['Compras' => $f['compras'], 'Comprado' => Dinheiro::brl($f['comprado']), 'Pago' => Dinheiro::brl($f['pago']), 'Saldo devedor' => Dinheiro::brl($f['saldo'])]">
                <x-slot:acao>
                    <a href="{{ route('financeiro.fornecedor', array_filter(['chave' => $f['chave'], 'empresa' => request('empresa')])) }}"
                       style="background:#05018D; color:#fff; border-radius:8px; padding:8px 18px; font-size:14px; font-weight:600; text-decoration:none;">Abrir</a>
                </x-slot:acao>
            </x-mobile-card>
        @empty
            <div style="padding:40px 16px; text-align:center; color:#9ca3af;">Nenhuma compra com fornecedor registrada ainda.</div>
        @endforelse
    </div>

</div>
@endsection
