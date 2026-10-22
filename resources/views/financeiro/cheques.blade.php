@extends('layouts.app')

{{-- Mesma largura das outras listagens do sistema (o layout lê esta seção) --}}
@section('tela_cheia', '1')

@section('content')
@php
    use App\Support\Dinheiro;
    $saldos = app(\App\Services\SaldoFornecedores::class);
    $cores = [
        'emitido' => 'color:#17794a; border:1px solid #17794a;',
        'cancelado' => 'color:#b8301a; border:1px solid #b8301a;',
    ];
    $filtrando = collect($filtros)->filter()->isNotEmpty();
    $campo = 'width:100%; padding:7px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:13px; box-sizing:border-box; background:#fff;';
    $rotulo = 'display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:4px; text-transform:uppercase;';
@endphp

<div style="padding: 8px 0;">

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Financeiro</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Todas as folhas de cheque emitidas, pela data "bom para" mais recente.</p>
    </div>

    @include('financeiro._abas')

    <div class="idx-stats bm-faixa" style="display:grid; grid-template-columns:repeat(3,1fr); background:#fff; border:1px solid #e5e7eb; border-radius:10px; margin-bottom:16px; overflow:hidden;">
        <x-bloco-metrica :rotulo="'Total em cheques' . ($filtrando ? ' (no filtro)' : '')" :valor="Dinheiro::brl($total)" :sem-linha="true"
                         :nota="$qtdValendo . ' ' . ($qtdValendo === 1 ? 'folha emitida' : 'folhas emitidas') . ($cheques->total() > $qtdValendo ? ' · ' . ($cheques->total() - $qtdValendo) . ' ' . ($cheques->total() - $qtdValendo === 1 ? 'cancelada (não soma)' : 'canceladas (não somam)') : '')" />
    </div>

    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:14px 16px; margin-bottom:16px;">
        <form method="GET" action="{{ route('financeiro.cheques') }}" style="display:flex; gap:8px; align-items:end; flex-wrap:wrap;">
            <div style="flex:1; min-width:200px;">
                <label style="{{ $rotulo }}">Buscar</label>
                <input type="text" name="q" value="{{ $filtros['q'] }}" placeholder="Nº da folha ou fornecedor..." style="{{ $campo }}">
            </div>
            <div style="min-width:150px;">
                <label style="{{ $rotulo }}">Banco</label>
                <select name="banco" style="{{ $campo }}">
                    <option value="">Todos</option>
                    @foreach($bancos as $b)
                        <option value="{{ $b }}" @selected($filtros['banco'] === $b)>{{ $b }}</option>
                    @endforeach
                </select>
            </div>
            <div style="min-width:130px;">
                <label style="{{ $rotulo }}">Situação</label>
                <select name="situacao" style="{{ $campo }}">
                    <option value="">Todas</option>
                    @foreach(\App\Models\Cheque::SITUACOES as $codigo => $nome)
                        <option value="{{ $codigo }}" @selected($filtros['situacao'] === $codigo)>{{ $nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="{{ $rotulo }}">Bom para de</label>
                <input type="date" name="de" value="{{ $filtros['de'] }}" style="{{ $campo }}">
            </div>
            <div>
                <label style="{{ $rotulo }}">até</label>
                <input type="date" name="ate" value="{{ $filtros['ate'] }}" style="{{ $campo }}">
            </div>
            <button type="submit" style="padding:8px 16px; background:#05018D; color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer;">Filtrar</button>
            @if($filtrando)
                <a href="{{ route('financeiro.cheques') }}" style="padding:8px 14px; border-radius:8px; border:1px solid #e5e7eb; color:#6b7280; text-decoration:none; font-size:13px;">Limpar</a>
            @endif
        </form>
    </div>

    {{-- Uma marcação só: no celular a tabela vira cartões (CSS .lista-resp no layout) --}}
    <div class="lista-resp" style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead>
                <tr style="background:#f9fafb; color:#6b7280; text-align:left; font-size:11.5px; text-transform:uppercase; letter-spacing:0.4px;">
                    <th style="padding:10px 9px;">Nº da folha</th>
                    <th style="padding:10px 9px;">Banco</th>
                    <th style="padding:10px 9px;">Fornecedor</th>
                    <th style="padding:10px 9px;">Histórico</th>
                    <th style="padding:10px 9px; text-align:center;">Bom para</th>
                    <th style="padding:10px 9px; text-align:right;">Valor</th>
                    <th style="padding:10px 9px; text-align:center;">Situação</th>
                    <th style="padding:10px 9px;">Emitido por</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cheques as $ch)
                    @php $compra = $ch->pagamentos->first()?->compra; @endphp
                    <tr style="border-top:1px solid #f3f4f6; {{ $ch->situacao === 'cancelado' ? 'color:#9ca3af;' : '' }}">
                        <td class="lr-num" style="padding:12px 9px; font-weight:700; font-variant-numeric:tabular-nums; {{ $ch->situacao === 'cancelado' ? 'text-decoration:line-through;' : 'color:#111827;' }}">{{ $ch->numeroFormatado() }}</td>
                        <td data-rotulo="Banco" style="padding:12px 9px;">{{ $ch->banco }}</td>
                        <td data-rotulo="Fornecedor" style="padding:12px 9px; font-weight:600;">
                            @if($compra)
                                <a href="{{ route('financeiro.fornecedor', $saldos->chave($compra->supplier)) }}" style="color:inherit; text-decoration:none;">{{ $compra->fornecedor?->nome ?? $compra->supplier }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="lr-larga" data-rotulo="Histórico" style="padding:12px 9px;">
                            @foreach($ch->pagamentos as $pg)
                                <div>{{ $pg->compra?->product_name }} <span style="color:#9ca3af;">req. #{{ $pg->purchase_request_id }}</span></div>
                            @endforeach
                        </td>
                        <td data-rotulo="Bom para" style="padding:12px 9px; text-align:center; white-space:nowrap;">{{ $ch->bom_para->format('d/m/Y') }}</td>
                        <td data-rotulo="Valor" style="padding:12px 9px; text-align:right; white-space:nowrap; font-weight:700;">{{ Dinheiro::brl($ch->valor) }}</td>
                        <td data-rotulo="Situação" style="padding:12px 9px; text-align:center;">
                            <span style="display:inline-block; background:#fff; {{ $cores[$ch->situacao] ?? '' }} padding:3px 12px; border-radius:9999px; font-size:12px; font-weight:700; white-space:nowrap;">{{ \App\Models\Cheque::SITUACOES[$ch->situacao] ?? $ch->situacao }}</span>
                        </td>
                        <td data-rotulo="Emitido por" style="padding:12px 9px; font-size:12px; color:#6b7280;">{{ $ch->user?->name ?? '—' }}<div>{{ $ch->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</div></td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="padding:40px 16px; text-align:center; color:#9ca3af;">
                        {{ $filtrando ? 'Nenhuma folha encontrada nesse filtro.' : 'Nenhum cheque emitido ainda. As folhas aparecem aqui quando um pagamento é feito em cheque.' }}
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:16px;">{{ $cheques->links() }}</div>

</div>
@endsection
