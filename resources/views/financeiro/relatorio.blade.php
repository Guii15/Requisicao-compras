@extends('layouts.app')

{{-- Mesma largura das outras listagens do sistema (o layout lê esta seção) --}}
@section('tela_cheia', '1')

@section('content')
@php
    use App\Support\Dinheiro;
    $s = $r['resumo'];
    $intervalo = $r['de']->format('d/m/Y') . ' a ' . $r['ate']->format('d/m/Y');
    $atalhos = ['semana' => 'Esta semana', 'semana_passada' => 'Semana passada', 'mes' => 'Este mês', 'mes_passado' => 'Mês passado'];
    $datas = ['de' => $r['de']->format('Y-m-d'), 'ate' => $r['ate']->format('Y-m-d')];
    $th = 'padding:9px 10px; text-align:left;';
    $thd = 'padding:9px 10px; text-align:right;';
    $td = 'padding:9px 10px;';
    $tdd = 'padding:9px 10px; text-align:right; white-space:nowrap;';
@endphp

<style>
    .rel-tabela { width: 100%; border-collapse: collapse; font-size: 13px; }
    .rel-tabela thead tr { background: #f9fafb; color: #6b7280; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.4px; }
    .rel-tabela tbody tr { border-top: 1px solid #f3f4f6; }
    .rel-tabela tfoot tr { border-top: 2px solid #05018D; background: #eef0fa; font-weight: 700; color: #111827; }
    .rel-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; margin-bottom: 18px; overflow: hidden; }
    .rel-card > h2 { margin: 0; padding: 14px 16px 2px; font-size: 15px; font-weight: 700; color: #111827; }
    .rel-card > p { margin: 0; padding: 0 16px 12px; font-size: 12.5px; color: #6b7280; }
    .rel-rolagem { overflow-x: auto; }
    .rel-duas { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    @media (max-width: 800px) { .rel-duas { grid-template-columns: 1fr; } }
    .rel-atalho { padding: 7px 14px; border-radius: 9999px; border: 1px solid #cbd5e1; color: #475569; text-decoration: none; font-size: 13px; font-weight: 600; white-space: nowrap; }
    .rel-atalho.ativo { background: #05018D; border-color: #05018D; color: #fff; }
    .rel-so-impressao { display: none; }
    /* No celular as tabelas rolam para o lado em vez de quebrar palavra no meio. */
    @media (max-width: 768px) { .rel-tabela td, .rel-tabela th { white-space: nowrap; overflow-wrap: normal; } }

    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        nav, footer, header, .rel-nao-imprime { display: none !important; }
        .rel-so-impressao { display: block; }
        body, main { background: #fff !important; }
        /* O layout é uma coluna flex; no papel vira bloco para as tabelas continuarem na página seguinte sem deixar buraco. */
        body > div { display: block !important; min-height: 0 !important; }
        main { padding: 0 !important; }
        .rel-card { border-color: #d1d5db; break-inside: auto; overflow: visible; }
        .rel-card > h2 { break-after: avoid; }
        .rel-tabela { font-size: 10.5px; }
        .rel-tabela tr { break-inside: avoid; }
        .rel-tabela thead { display: table-header-group; }
        .rel-tabela tfoot { display: table-row-group; } /* o total sai só no fim da tabela, não em cada página */
        .rel-rolagem { overflow: visible; }
        * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>

<div style="padding: 8px 0;">

    <div class="rel-nao-imprime" style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Financeiro</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Relatório de um período: o que foi pago, o que foi comprado e o que está em aberto. Imprima, salve em PDF ou baixe em Excel.</p>
    </div>

    <div class="rel-nao-imprime">@include('financeiro._abas')</div>

    @if(session('aviso'))
        <div class="rel-nao-imprime" style="background:#fef3c7; color:#92400e; border:1px solid #fde68a; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:14px;">⚠️ {{ session('aviso') }}</div>
    @endif

    {{-- Escolha do período e botões (não saem na impressão) --}}
    <div class="rel-nao-imprime" style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:14px 16px; margin-bottom:18px;">
        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px;">
            @foreach($atalhos as $codigo => $rotulo)
                <a href="{{ route('financeiro.relatorio', ['periodo' => $codigo]) }}" class="rel-atalho {{ $periodo === $codigo ? 'ativo' : '' }}">{{ $rotulo }}</a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('financeiro.relatorio') }}" style="display:flex; gap:8px; align-items:end; flex-wrap:wrap;">
            <input type="hidden" name="periodo" value="personalizado">
            <div>
                <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:4px; text-transform:uppercase;">De</label>
                <input type="date" name="de" value="{{ $datas['de'] }}" style="padding:7px 10px; border:1px solid #d1d5db; border-radius:8px; font-size:13px;">
            </div>
            <div>
                <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:4px; text-transform:uppercase;">Até</label>
                <input type="date" name="ate" value="{{ $datas['ate'] }}" style="padding:7px 10px; border:1px solid #d1d5db; border-radius:8px; font-size:13px;">
            </div>
            <button type="submit" style="padding:8px 16px; background:#fff; color:#05018D; border:1px solid #05018D; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer;">Ver período</button>
            <div style="flex:1;"></div>
            <button type="button" onclick="window.print()" style="padding:8px 16px; background:#fff; color:#05018D; border:1px solid #05018D; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer;">🖨️ Imprimir / Salvar PDF</button>
            <a href="{{ route('financeiro.relatorio.planilha', $datas) }}" style="padding:8px 16px; background:#05018D; color:#fff; border:1px solid #05018D; border-radius:8px; font-size:13px; font-weight:600; text-decoration:none;">⬇️ Baixar Excel</a>
        </form>
    </div>

    {{-- Cabeçalho que só aparece no papel/PDF --}}
    <div class="rel-so-impressao" style="margin-bottom:12px;">
        <div style="font-size:12px; color:#6b7280;">Binário Tecnologia · Requisição de Compras</div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:baseline; flex-wrap:wrap; gap:6px; margin-bottom:12px;">
        <h2 style="margin:0; font-size:20px; font-weight:700; color:#111827;">Relatório financeiro · {{ $intervalo }}</h2>
        <span style="font-size:12.5px; color:#6b7280;">Gerado em {{ now('America/Sao_Paulo')->format('d/m/Y H:i') }} por {{ auth()->user()->name }}</span>
    </div>

    {{-- Números do período --}}
    <div class="idx-stats bm-faixa" style="display:grid; grid-template-columns:repeat(4,1fr); background:#fff; border:1px solid #e5e7eb; border-radius:10px; margin-bottom:18px; overflow:hidden;">
        <x-bloco-metrica rotulo="Pago no período" :valor="Dinheiro::brl($s['pago'])" :sem-linha="true"
                         :nota="$s['pagamentos'] . ' ' . ($s['pagamentos'] === 1 ? 'pagamento' : 'pagamentos')" />
        <x-bloco-metrica rotulo="Comprado no período" :valor="Dinheiro::brl($s['comprado'])" :sem-linha="true"
                         :nota="$s['compras'] . ' ' . ($s['compras'] === 1 ? 'compra' : 'compras') . ($s['acrescimos'] > 0 ? ' · + ' . Dinheiro::brl($s['acrescimos']) . ' de acréscimos' : '')" />
        <x-bloco-metrica rotulo="Custo real das compras" :valor="Dinheiro::brl($s['custo_real'])" :sem-linha="true" nota="valor do pedido + impostos, frete..." />
        <x-bloco-metrica rotulo="Saldo devedor hoje" :valor="Dinheiro::brl($s['saldo_hoje'])" :sem-linha="true"
                         :nota="$s['vencido_hoje'] > 0 ? 'Vencido: ' . Dinheiro::brl($s['vencido_hoje']) : 'Nada vencido'" />
    </div>

    {{-- Totais por forma e por banco --}}
    <div class="rel-duas">
        @foreach(['por_meio' => 'Pago por forma de pagamento', 'por_banco' => 'Pago por banco'] as $chave => $tituloGrupo)
            <div class="rel-card">
                <h2>{{ $tituloGrupo }}</h2>
                <p>Pagamentos com data dentro do período.</p>
                <table class="rel-tabela">
                    <thead><tr><th style="{{ $th }}">{{ $chave === 'por_meio' ? 'Forma' : 'Banco' }}</th><th style="{{ $thd }}">Pagamentos</th><th style="{{ $thd }}">Valor</th><th style="{{ $thd }}">%</th></tr></thead>
                    <tbody>
                        @forelse($r[$chave] as $g)
                            <tr>
                                <td style="{{ $td }}">{{ $g['nome'] }}</td>
                                <td style="{{ $tdd }}">{{ $g['pagamentos'] }}</td>
                                <td style="{{ $tdd }}">{{ Dinheiro::brl($g['valor']) }}</td>
                                <td style="{{ $tdd }} color:#6b7280;">{{ $s['pago'] > 0 ? number_format($g['valor'] / $s['pago'] * 100, 1, ',', '.') . '%' : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="padding:16px; text-align:center; color:#9ca3af;">Nenhum pagamento no período.</td></tr>
                        @endforelse
                    </tbody>
                    @if(count($r[$chave]))
                        <tfoot><tr><td style="{{ $td }}">Total</td><td style="{{ $tdd }}">{{ $s['pagamentos'] }}</td><td style="{{ $tdd }}">{{ Dinheiro::brl($s['pago']) }}</td><td style="{{ $tdd }}">100%</td></tr></tfoot>
                    @endif
                </table>
            </div>
        @endforeach
    </div>

    {{-- Pagamentos --}}
    <div class="rel-card">
        <h2>Pagamentos do período</h2>
        <p>Tudo o que foi pago a fornecedores entre {{ $intervalo }}, pela data do pagamento.</p>
        <div class="rel-rolagem">
            <table class="rel-tabela">
                <thead><tr>
                    <th style="{{ $th }}">Data</th><th style="{{ $th }}">Fornecedor</th><th style="{{ $th }}">Req.</th><th style="{{ $th }}">Produto</th>
                    <th style="{{ $th }}">Tipo</th><th style="{{ $th }}">Forma</th><th style="{{ $th }}">Banco</th><th style="{{ $thd }}">Valor</th><th style="{{ $th }}">Observação</th><th style="{{ $th }}">Lançado por</th>
                </tr></thead>
                <tbody>
                    @forelse($r['pagamentos'] as $p)
                        <tr>
                            <td style="{{ $td }} white-space:nowrap;">{{ $p['data']->format('d/m/Y') }}</td>
                            <td style="{{ $td }} font-weight:600;">{{ $p['fornecedor'] }}</td>
                            <td style="{{ $td }}">#{{ $p['requisicao'] }}</td>
                            <td style="{{ $td }}">{{ $p['produto'] }}</td>
                            <td style="{{ $td }}">{{ $p['tipo'] }}</td>
                            <td style="{{ $td }}">{{ $p['meio'] }}</td>
                            <td style="{{ $td }}">{{ $p['banco'] }}</td>
                            <td style="{{ $tdd }} font-weight:600;">{{ Dinheiro::brl($p['valor']) }}</td>
                            <td style="{{ $td }} color:#6b7280;">{{ $p['obs'] ?? '' }}</td>
                            <td style="{{ $td }} color:#6b7280;">{{ $p['usuario'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" style="padding:20px; text-align:center; color:#9ca3af;">Nenhum pagamento no período.</td></tr>
                    @endforelse
                </tbody>
                @if(count($r['pagamentos']))
                    <tfoot><tr><td colspan="7" style="{{ $td }}">Total · {{ $s['pagamentos'] }} {{ $s['pagamentos'] === 1 ? 'pagamento' : 'pagamentos' }}</td><td style="{{ $tdd }}">{{ Dinheiro::brl($s['pago']) }}</td><td colspan="2"></td></tr></tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Compras --}}
    <div class="rel-card">
        <h2>Compras do período</h2>
        <p>Compras com data entre {{ $intervalo }}. "Pago" e "Em aberto" mostram a situação de hoje.</p>
        <div class="rel-rolagem">
            <table class="rel-tabela">
                <thead><tr>
                    <th style="{{ $th }}">Data</th><th style="{{ $th }}">Fornecedor</th><th style="{{ $th }}">Req.</th><th style="{{ $th }}">Produto</th><th style="{{ $th }}">Condição</th>
                    <th style="{{ $thd }}">Valor</th><th style="{{ $thd }}">Acréscimo</th><th style="{{ $thd }}">Custo real</th><th style="{{ $thd }}">Pago</th><th style="{{ $thd }}">Em aberto</th><th style="{{ $th }}">Situação</th>
                </tr></thead>
                <tbody>
                    @forelse($r['compras'] as $l)
                        @php $c = $l['compra']; @endphp
                        <tr>
                            <td style="{{ $td }} white-space:nowrap;">{{ $c->data_compra->format('d/m/Y') }}</td>
                            <td style="{{ $td }} font-weight:600;">{{ $l['fornecedor'] }}</td>
                            <td style="{{ $td }}">#{{ $c->id }}</td>
                            <td style="{{ $td }}">{{ $c->product_name }} <span style="color:#9ca3af;">× {{ $c->quantity }}</span></td>
                            <td style="{{ $td }} color:#6b7280;">{{ $l['condicao'] ?? '—' }}</td>
                            <td style="{{ $tdd }}">{{ Dinheiro::brl($l['custo']) }}</td>
                            <td style="{{ $tdd }} color:#92400e;" title="{{ $c->acrescimos->pluck('motivo')->implode('; ') }}">
                                {{ $l['acrescimo'] > 0 ? Dinheiro::brl($l['acrescimo']) : '—' }}
                                @if($l['acrescimo'] > 0)<div style="font-size:11px; color:#6b7280; white-space:normal;">{{ $c->acrescimos->pluck('motivo')->implode('; ') }}</div>@endif
                            </td>
                            <td style="{{ $tdd }} font-weight:600;">{{ Dinheiro::brl($l['custo_total']) }}</td>
                            <td style="{{ $tdd }}">{{ Dinheiro::brl($l['pago']) }}</td>
                            <td style="{{ $tdd }}">{{ Dinheiro::brl($l['aberto']) }}</td>
                            <td style="{{ $td }}">{{ $l['situacao'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="11" style="padding:20px; text-align:center; color:#9ca3af;">Nenhuma compra no período.</td></tr>
                    @endforelse
                </tbody>
                @if(count($r['compras']))
                    <tfoot><tr>
                        <td colspan="5" style="{{ $td }}">Total · {{ $s['compras'] }} {{ $s['compras'] === 1 ? 'compra' : 'compras' }}</td>
                        <td style="{{ $tdd }}">{{ Dinheiro::brl($s['comprado']) }}</td>
                        <td style="{{ $tdd }}">{{ Dinheiro::brl($s['acrescimos']) }}</td>
                        <td style="{{ $tdd }}">{{ Dinheiro::brl($s['custo_real']) }}</td>
                        <td style="{{ $tdd }}">{{ Dinheiro::brl(collect($r['compras'])->sum('pago')) }}</td>
                        <td style="{{ $tdd }}">{{ Dinheiro::brl(collect($r['compras'])->sum('aberto')) }}</td>
                        <td></td>
                    </tr></tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Fornecedores --}}
    <div class="rel-card">
        <h2>Por fornecedor</h2>
        <p>Fornecedores que tiveram compra ou pagamento no período, com o saldo devedor de hoje.</p>
        <div class="rel-rolagem">
            <table class="rel-tabela">
                <thead><tr>
                    <th style="{{ $th }}">Fornecedor</th><th style="{{ $thd }}">Compras</th><th style="{{ $thd }}">Comprado</th><th style="{{ $thd }}">Acréscimos</th><th style="{{ $thd }}">Pago</th><th style="{{ $thd }}">Saldo devedor hoje</th>
                </tr></thead>
                <tbody>
                    @forelse($r['por_fornecedor'] as $f)
                        <tr>
                            <td style="{{ $td }} font-weight:600;">{{ $f['nome'] }}</td>
                            <td style="{{ $tdd }}">{{ $f['compras'] }}</td>
                            <td style="{{ $tdd }}">{{ Dinheiro::brl($f['comprado']) }}</td>
                            <td style="{{ $tdd }}">{{ $f['acrescimos'] > 0 ? Dinheiro::brl($f['acrescimos']) : '—' }}</td>
                            <td style="{{ $tdd }}">{{ Dinheiro::brl($f['pago']) }}</td>
                            <td style="{{ $tdd }} font-weight:600;">{{ $f['saldo'] > 0 ? Dinheiro::brl($f['saldo']) : 'Quitado' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="padding:20px; text-align:center; color:#9ca3af;">Nenhum movimento no período.</td></tr>
                    @endforelse
                </tbody>
                @if(count($r['por_fornecedor']))
                    <tfoot><tr>
                        <td style="{{ $td }}">Total</td><td style="{{ $tdd }}">{{ $s['compras'] }}</td>
                        <td style="{{ $tdd }}">{{ Dinheiro::brl($s['comprado']) }}</td><td style="{{ $tdd }}">{{ Dinheiro::brl($s['acrescimos']) }}</td>
                        <td style="{{ $tdd }}">{{ Dinheiro::brl($s['pago']) }}</td><td style="{{ $tdd }}">{{ Dinheiro::brl(collect($r['por_fornecedor'])->sum('saldo')) }}</td>
                    </tr></tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Em aberto --}}
    <div class="rel-card">
        <h2>Em aberto hoje</h2>
        <p>Todas as compras que ainda têm algo a pagar, pelo próximo vencimento (independe do período).</p>
        <div class="rel-rolagem">
            <table class="rel-tabela">
                <thead><tr>
                    <th style="{{ $th }}">Vencimento</th><th style="{{ $th }}">Fornecedor</th><th style="{{ $th }}">Req.</th><th style="{{ $th }}">Produto</th><th style="{{ $th }}">Compra</th>
                    <th style="{{ $thd }}">Valor</th><th style="{{ $thd }}">Pago</th><th style="{{ $thd }}">Em aberto</th><th style="{{ $thd }}">Vencido</th>
                </tr></thead>
                <tbody>
                    @forelse($r['abertas'] as $l)
                        @php $c = $l['compra']; @endphp
                        <tr>
                            <td style="{{ $td }} white-space:nowrap;">{{ $l['proximo_vencimento']?->format('d/m/Y') ?? '—' }}</td>
                            <td style="{{ $td }} font-weight:600;">{{ $l['fornecedor'] }}</td>
                            <td style="{{ $td }}">#{{ $c->id }}</td>
                            <td style="{{ $td }}">{{ $c->product_name }}</td>
                            <td style="{{ $td }} white-space:nowrap;">{{ $c->data_compra->format('d/m/Y') }}</td>
                            <td style="{{ $tdd }}">{{ Dinheiro::brl($l['custo']) }}</td>
                            <td style="{{ $tdd }}">{{ Dinheiro::brl($l['pago']) }}</td>
                            <td style="{{ $tdd }} font-weight:600;">{{ Dinheiro::brl($l['aberto']) }}</td>
                            <td style="{{ $tdd }} {{ $l['vencido'] > 0 ? 'color:#b8301a; font-weight:700;' : 'color:#9ca3af;' }}">{{ $l['vencido'] > 0 ? Dinheiro::brl($l['vencido']) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" style="padding:20px; text-align:center; color:#9ca3af;">✓ Nada em aberto.</td></tr>
                    @endforelse
                </tbody>
                @if(count($r['abertas']))
                    <tfoot><tr>
                        <td colspan="7" style="{{ $td }}">Total · {{ $s['abertas'] }} {{ $s['abertas'] === 1 ? 'compra' : 'compras' }}</td>
                        <td style="{{ $tdd }}">{{ Dinheiro::brl($s['saldo_hoje']) }}</td>
                        <td style="{{ $tdd }}">{{ Dinheiro::brl($s['vencido_hoje']) }}</td>
                    </tr></tfoot>
                @endif
            </table>
        </div>
    </div>

</div>
@endsection
