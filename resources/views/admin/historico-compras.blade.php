@extends('layouts.app')

{{-- Listagem usa a largura toda da tela (o layout lê esta seção) --}}
@section('tela_cheia', '1')

@section('content')

<div style="padding: 8px 0;">

    {{-- Cabeçalho --}}
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Painel Administrativo</h1>
            <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Gerencie todas as requisições de compra</p>
        </div>
    </div>

    @include('admin._abas')

    <div style="margin-bottom:20px;">
        <h2 style="margin:0; font-size:18px; font-weight:700; color:#111827;">Histórico de Compras</h2>
        <p style="margin:4px 0 0; color:#6b7280; font-size:13px;">
            Todas as requisições (pendente, aprovada, rejeitada) + tudo que foi importado da planilha antiga (Binário Tecnologia) — só leitura.
        </p>
    </div>

    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; border:1px solid #86efac; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
            ✓ {{ session('success') }}
        </div>
    @endif

    {{-- Totais: mesma faixa de números das outras telas --}}
    <div class="idx-stats bm-faixa" style="display:grid; grid-template-columns:repeat(3,1fr); background:#fff; border:1px solid #e5e7eb; border-radius:10px; margin-bottom:20px; overflow:hidden;">
        <x-bloco-metrica rotulo="Total no histórico" :valor="number_format($totalGeral, 0, ',', '.')" :sem-linha="true" :nota="$totalFluxoAtivo . ' do fluxo · ' . $totalPlanilha . ' da planilha'" />
        <x-bloco-metrica rotulo="Valor total" :valor="'R$ ' . number_format($valorTotal, 2, ',', '.')" :sem-linha="true" />
        <x-bloco-metrica rotulo="Por aba da planilha" :valor="$totaisPorAba->count() ? $totaisPorAba->count() . ($totaisPorAba->count() === 1 ? ' aba' : ' abas') : '—'" :sem-linha="true"
                         :nota="$totaisPorAba->isEmpty() ? 'Nenhum registro da planilha ainda.' : $totaisPorAba->map(fn ($l) => $l->aba_origem . ': ' . $l->total)->implode(' · ')" />
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('admin.historico-compras') }}" class="m-empilhar" style="display:flex; gap:10px; flex-wrap:wrap; align-items:end; margin-bottom:16px;">
        <div>
            <label style="display:block; font-size:12px; color:#6b7280; font-weight:600; margin-bottom:4px;">Produto</label>
            <input type="text" name="produto" value="{{ request('produto') }}" placeholder="Buscar por produto..."
                   style="padding:7px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; min-width:180px;">
        </div>
        <div>
            <label style="display:block; font-size:12px; color:#6b7280; font-weight:600; margin-bottom:4px;">Vendedor</label>
            <input type="text" name="vendedor" value="{{ request('vendedor') }}" placeholder="Buscar por vendedor..."
                   style="padding:7px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; min-width:160px;">
        </div>
        <div>
            <label style="display:block; font-size:12px; color:#6b7280; font-weight:600; margin-bottom:4px;">Mês</label>
            <select name="mes" onchange="this.form.submit()" style="padding:7px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; min-width:130px;">
                <option value="">Todos</option>
                @foreach($mesesDisponiveis as $mesOpcao)
                    <option value="{{ $mesOpcao['valor'] }}" @selected(request('mes') === $mesOpcao['valor'])>{{ $mesOpcao['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display:block; font-size:12px; color:#6b7280; font-weight:600; margin-bottom:4px;">Aba da planilha</label>
            <select name="aba_origem" onchange="this.form.submit()" style="padding:7px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; min-width:150px;">
                <option value="">Todas</option>
                @foreach($abasDisponiveis as $abaOpcao)
                    <option value="{{ $abaOpcao }}" @selected(request('aba_origem') === $abaOpcao)>{{ $abaOpcao }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" style="padding:8px 16px; border:none; border-radius:6px; background:#05018D; color:#fff; font-size:13px; font-weight:600; cursor:pointer;">Filtrar</button>
        @if(request('produto') || request('vendedor') || request('mes') || request('aba_origem'))
            <a href="{{ route('admin.historico-compras') }}" style="font-size:13px; color:#6b7280; text-decoration:underline; padding-bottom:8px;">Limpar</a>
        @endif
    </form>

    @php
        $rotulosDadosImportacao = [
            'pedido' => 'Pedido',
            'preco_unitario' => 'Preço Unitário',
            'filial' => 'Filial',
            'modalidade_compra' => 'Modalidade de Compra',
            'data_coleta' => 'Data da Coleta',
            'data_entrada' => 'Data de Entrada',
            'data_pagamento' => 'Data de Pagamento',
            'vencimento_1' => 'Vencimento 1',
            'vencimento_2' => 'Vencimento 2',
            'vencimento_3' => 'Vencimento 3',
            'conferencia' => 'Conferência',
            'forma_pagamento' => 'Forma de Pagamento',
            'data_reposicao' => 'Data da Reposição',
            'vendedor' => 'Vendedor',
            'data_retirada' => 'Data de Retirada',
            'entrada_showroom' => 'Entrada (Showroom)',
            'quantidade_varejo_cotada' => 'Quantidade Cotada (Varejo)',
            'preco_unitario_varejo_cotado' => 'Preço Unitário Cotado (Varejo)',
            'subtotal_varejo_cotado' => 'Subtotal Cotado (Varejo)',
            'quantidade_caixa_cotada' => 'Quantidade Cotada (Caixa)',
            'preco_unitario_caixa_cotado' => 'Preço Unitário Cotado (Caixa)',
            'valor_total_caixa_cotado' => 'Valor Total Cotado (Caixa)',
            'linha_excel_original' => 'Linha na Planilha',
            'flags_qualidade' => 'Alertas de Qualidade',
        ];
    @endphp

    {{-- Mesmo desenho das outras listagens: uma linha por requisição (ou lote da planilha); ao abrir, os itens. --}}
    <div class="lista-resp" style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">
        <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc; border-bottom:1px solid #e5e7eb;">
                    @php $thHist = 'padding:12px 16px; color:#6b7280; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; white-space:nowrap;'; @endphp
                    <th style="{{ $thHist }} text-align:left;">Origem</th>
                    <th style="{{ $thHist }} text-align:left;">Vendedor</th>
                    <th style="{{ $thHist }} text-align:left;">Itens</th>
                    <th style="{{ $thHist }} text-align:left;">Fornecedor</th>
                    <th style="{{ $thHist }} text-align:left;">Data</th>
                    <th style="{{ $thHist }} text-align:right;">Total</th>
                    <th style="{{ $thHist }} text-align:left;">Situação</th>
                    <th style="{{ $thHist }} text-align:right;">Ação</th>
                </tr>
            </thead>
            <tbody>
        @forelse($requests as $grupo)
            @php
                $primeiroHist = $grupo->first();
                $chaveHist = $primeiroHist->grupo_id;
                $statusUnicosHist = $grupo->map(function ($item) {
                    if ($item->tipo_registro === 'cotacao_historica') return 'cotacao';
                    if ($item->tipo_registro === 'compra_historica') return 'aprovado';
                    return $item->status;
                })->unique();
                if ($statusUnicosHist->count() === 1) {
                    $tipoChaveHist = $statusUnicosHist->first();
                    $rotuloGrupoHist = ['aprovado' => 'Aprovado', 'rejeitado' => 'Rejeitado', 'cotacao' => 'Cotação'][$tipoChaveHist] ?? ucfirst($tipoChaveHist);
                } else {
                    $tipoChaveHist = 'parcial';
                    $rotuloGrupoHist = 'Parcial';
                }
                $corsGrupoHist = [
                    'aprovado'  => ['barra' => '#e5e7eb', 'bg' => '#17794a', 'texto' => '#ffffff'],
                    'rejeitado' => ['barra' => '#e5e7eb', 'bg' => '#b8301a', 'texto' => '#ffffff'],
                    'cotacao'   => ['barra' => '#e5e7eb', 'bg' => '#f4b728', 'texto' => '#2b1d00'],
                    'pendente'  => ['barra' => '#e5e7eb', 'bg' => '#f4b728', 'texto' => '#2b1d00'],
                    'parcial'   => ['barra' => '#e5e7eb', 'bg' => '#475569', 'texto' => '#ffffff'],
                ][$tipoChaveHist] ?? ['barra' => '#64748b', 'bg' => '#e2e8f0', 'texto' => '#475569'];
                $produtosResumoHist = $grupo->pluck('product_name')->filter()->implode(', ');
                if (mb_strlen($produtosResumoHist) > 80) {
                    $produtosResumoHist = mb_substr($produtosResumoHist, 0, 80) . '…';
                }
                $valorGrupoHist = $grupo->sum('valor');
                $origemLabel = $primeiroHist->aba_origem
                    ? $primeiroHist->aba_origem . ($primeiroHist->mes_origem ? ' · ' . $primeiroHist->mes_origem : '')
                    : 'Requisição #' . $primeiroHist->id;
                // O mesmo fornecedor escrito de formas diferentes conta como um só.
                $fornecedoresHist = $grupo->pluck('supplier')->filter()->unique(fn ($f) => \App\Support\RankingPorNome::chave($f));
                $fornecedorHist = $fornecedoresHist->count() === 0 ? '—' : ($fornecedoresHist->count() === 1 ? $fornecedoresHist->first() : $fornecedoresHist->count() . ' fornecedores');
                $tdHist = 'padding:12px 16px; font-size:14px; color:#374151; vertical-align:middle;';
                $dataGrupoHist = $primeiroHist->data_compra?->format('d/m/Y')
                    ?? ($primeiroHist->tipo_registro === 'requisicao' ? $primeiroHist->created_at->format('d/m/Y') : 'Sem data');
            @endphp
            <tr class="grupo-cabecalho" style="border-bottom:1px solid #eef0f3; cursor:pointer;" onmouseover="this.style.background='#f7f8fa'" onmouseout="this.style.background='transparent'" onclick="toggleGrupoHistorico('{{ $chaveHist }}')">
                <td class="lr-num" style="{{ $tdHist }} font-weight:700; color:#111827; white-space:nowrap;">{{ $origemLabel }}</td>
                <td data-rotulo="Vendedor" style="{{ $tdHist }}">{{ $primeiroHist->requester_name ?: '—' }}</td>
                <td class="lr-larga" data-rotulo="Itens" style="{{ $tdHist }} max-width:380px;">
                    <div style="font-weight:600; color:#111827; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $produtosResumoHist }}</div>
                    <div style="font-size:12px; color:#6b7280; margin-top:2px;">{{ $grupo->count() }} {{ $grupo->count() > 1 ? 'itens' : 'item' }}</div>
                </td>
                <td data-rotulo="Fornecedor" style="{{ $tdHist }}">{{ $fornecedorHist }}</td>
                <td data-rotulo="Data" style="{{ $tdHist }} font-size:13px; white-space:nowrap;">{{ $dataGrupoHist }}</td>
                <td data-rotulo="Total" style="{{ $tdHist }} text-align:right; font-weight:600; color:#111827; white-space:nowrap;">{{ $valorGrupoHist > 0 ? 'R$ ' . number_format($valorGrupoHist, 2, ',', '.') : '—' }}</td>
                <td data-rotulo="Situação" style="{{ $tdHist }}">
                    @if($tipoChaveHist === 'cotacao')
                        <span style="display:inline-block; color:#7a4f00; border:1px solid #c98a00; padding:4px 12px; border-radius:9999px; font-size:12px; font-weight:700; white-space:nowrap;">Cotação</span>
                    @else
                        <x-status-requisicao :status="$tipoChaveHist" />
                    @endif
                </td>
                <td class="lr-acao" style="{{ $tdHist }} text-align:right;">
                    <button type="button" onclick="event.stopPropagation(); toggleGrupoHistorico('{{ $chaveHist }}')"
                            style="border:1px solid #d1d5db; background:#fff; color:#374151; padding:7px 16px; border-radius:9999px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap;">
                        <span id="seta-hist-{{ $chaveHist }}">Ver itens</span>
                    </button>
                </td>
            </tr>
            <tr id="itens-hist-{{ $chaveHist }}" class="grupo-item-hist" style="display:none; background:#f7f8fa;">
                <td colspan="8" style="padding:14px 20px 18px; border-bottom:1px solid #e5e7eb;">
                    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:4px 20px;">
                    @foreach($grupo as $itemHist)
                        @php
                            $dadosItem = $itemHist->dados_importacao ?? [];
                            $entradaLabel = null;
                            $entradaCor = '#9ca3af';

                            if ($itemHist->tipo_registro === 'requisicao') {
                                // Requisicao real: a entrada vem do fluxo normal (entrada_concluida_em), nao da planilha.
                                if ($itemHist->entrada_concluida_em) {
                                    $entradaLabel = 'Entrada em ' . $itemHist->entrada_concluida_em->timezone('America/Sao_Paulo')->format('d/m/Y');
                                    $entradaCor = '#15803d';
                                } elseif (in_array($itemHist->status_conferencia, ['conferido_ok', 'avancado_mesmo_assim'], true)) {
                                    $entradaLabel = 'Aguardando entrada';
                                    $entradaCor = '#b45309';
                                }
                            } elseif ($itemHist->tipo_registro === 'cotacao_historica') {
                                $entradaLabel = null;
                            } else {
                                $rotuloEntrada = 'Entrada';
                                $entradaBruta = $dadosItem['data_entrada'] ?? null;
                                if (!$entradaBruta) {
                                    $entradaBruta = $dadosItem['entrada_showroom'] ?? null;
                                }
                                if (!$entradaBruta) {
                                    $entradaBruta = $dadosItem['data_retirada'] ?? null;
                                    $rotuloEntrada = 'Retirada';
                                }
                                if ($entradaBruta) {
                                    try {
                                        $entradaLabel = $rotuloEntrada . ' em ' . \Carbon\Carbon::parse($entradaBruta)->format('d/m/Y');
                                    } catch (\Throwable) {
                                        $entradaLabel = $rotuloEntrada . ': ' . $entradaBruta;
                                    }
                                    $entradaCor = '#15803d';
                                } else {
                                    $entradaLabel = 'Sem confirmação de entrada/retirada na planilha';
                                    $entradaCor = '#b45309';
                                }
                            }
                        @endphp
                        <div style="padding:12px 0; border-bottom:1px solid #f1f2f4;">
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; font-size:13px; color:#374151;">
                                <span>
                                    <strong>{{ $itemHist->product_name }}</strong>
                                    @if($itemHist->product_code)
                                        <span style="color:#9ca3af;">({{ $itemHist->product_code }})</span>
                                    @endif
                                    — Qtd: {{ $itemHist->quantity }}
                                    @if($itemHist->supplier)
                                        · {{ $itemHist->supplier }}
                                    @endif
                                    @if($itemHist->requester_name)
                                        · {{ $itemHist->requester_name }}
                                    @endif
                                </span>
                                <span style="color:#6b7280; font-weight:600; white-space:nowrap;">
                                    {{ $itemHist->valor ? 'R$ ' . number_format($itemHist->valor, 2, ',', '.') : '—' }}
                                </span>
                            </div>
                            @if($entradaLabel)
                                <div style="font-size:11.5px; color:{{ $entradaCor }}; font-weight:600; margin-top:2px;">{{ $entradaLabel }}</div>
                            @endif
                            @if($itemHist->tipo_registro === 'requisicao' && $itemHist->status === 'aprovado')
                                <a href="{{ route('admin.compras.feitas', ['abrir' => $itemHist->id]) }}" style="font-size:12px; color:#05018D; font-weight:600;">
                                    {{ $itemHist->temDadosDaCompra() ? 'Ver dados da compra' : 'Registrar dados da compra' }}
                                </a>
                            @endif
                            @if(!empty($dadosItem))
                                <button type="button" onclick="toggleDadosHistorico('{{ $itemHist->id }}')"
                                        style="margin-top:4px; border:none; background:none; color:#05018D; font-size:12px; font-weight:600; cursor:pointer; padding:0;">
                                    <span id="seta-dados-{{ $itemHist->id }}">Ver dados originais da planilha</span>
                                </button>
                                <div id="dados-hist-{{ $itemHist->id }}" style="display:none; margin-top:6px; padding:8px 10px; background:#fff; border:1px solid #e5e7eb; border-radius:6px;">
                                    @foreach($dadosItem as $chaveDado => $valorDado)
                                        <div style="display:flex; justify-content:space-between; gap:12px; font-size:12px; color:#4b5563; padding:2px 0;">
                                            <span style="color:#9ca3af; white-space:nowrap;">{{ $rotulosDadosImportacao[$chaveDado] ?? ucfirst(str_replace('_', ' ', $chaveDado)) }}</span>
                                            <span style="text-align:right;">
                                                @if(str_contains($chaveDado, 'preco') || str_contains($chaveDado, 'valor') || str_contains($chaveDado, 'subtotal'))
                                                    @if(is_numeric($valorDado))
                                                        R$ {{ number_format((float) $valorDado, 2, ',', '.') }}
                                                    @else
                                                        {{ $valorDado }}
                                                    @endif
                                                @else
                                                    {{ $valorDado }}
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" style="padding:48px 16px; text-align:center;">
                <p style="color:#6b7280; font-size:15px; margin:0 0 4px;">Nenhum registro no histórico ainda</p>
                <p style="color:#9ca3af; font-size:13px; margin:0;">Requisições aparecem aqui assim que criadas. Rode <code>php artisan compras:importar-historico</code> para trazer o histórico da planilha.</p>
            </td></tr>
        @endforelse
            </tbody>
        </table>
        </div>
    </div>

    @if($requests->hasPages())
        <div style="margin-top:16px;">{{ $requests->links() }}</div>
    @endif

</div>

<script>
function toggleGrupoHistorico(id) {
    var bloco = document.getElementById('itens-hist-' + id);
    var seta = document.getElementById('seta-hist-' + id);
    if (!bloco) return;
    var abrindo = bloco.style.display === 'none';
    bloco.style.display = abrindo ? '' : 'none'; // '' devolve ao CSS (linha no PC, bloco no celular)
    if (seta) seta.textContent = abrindo ? 'Ocultar itens' : 'Ver itens';
}

function toggleDadosHistorico(id) {
    var bloco = document.getElementById('dados-hist-' + id);
    var seta = document.getElementById('seta-dados-' + id);
    if (!bloco) return;
    var abrindo = bloco.style.display === 'none';
    bloco.style.display = abrindo ? 'block' : 'none';
    if (seta) seta.textContent = abrindo ? 'Ocultar dados originais da planilha' : 'Ver dados originais da planilha';
}
</script>

@endsection
