@extends('layouts.app')

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

    {{-- Planilha Original --}}
    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
        <p style="margin:0 0 14px; font-size:13px; font-weight:600; color:#374151; text-transform:uppercase; letter-spacing:0.5px;">Planilha Original</p>

        @if($planilhaOriginalAtualizadaEm)
            <p style="margin:0 0 14px; font-size:13px; color:#6b7280;">
                Última atualização: {{ $planilhaOriginalAtualizadaEm->format('d/m/Y H:i') }}
            </p>

            <form method="GET" action="{{ route('admin.historico-compras') }}" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:16px;">
                <input type="hidden" name="produto" value="{{ request('produto') }}">
                <input type="hidden" name="vendedor" value="{{ request('vendedor') }}">
                <input type="hidden" name="mes" value="{{ request('mes') }}">
                <input type="hidden" name="aba_origem" value="{{ request('aba_origem') }}">
                <label style="font-size:13px; color:#374151; font-weight:600;">Ver mês:</label>
                <select name="planilha_aba" onchange="this.form.submit()" style="padding:7px 10px; border:1px solid #d1d5db; border-radius:7px; font-size:13px; min-width:140px;">
                    <option value="">Selecione...</option>
                    @foreach($planilhaOriginalAbas as $abaOriginal)
                        <option value="{{ $abaOriginal }}" @selected($planilhaOriginalAbaSelecionada === $abaOriginal)>{{ $abaOriginal }}</option>
                    @endforeach
                </select>
                @if($planilhaOriginalAbaSelecionada)
                    <a href="{{ route('admin.historico-compras.planilha.download-aba', $planilhaOriginalAbaSelecionada) }}" style="font-size:12.5px; color:#05018D; text-decoration:underline;">Baixar esta aba</a>
                @endif
                <a href="{{ route('admin.historico-compras.planilha.download') }}" style="font-size:12.5px; color:#6b7280; text-decoration:underline;">Baixar arquivo completo</a>
            </form>

            @if($planilhaOriginalLinhas)
                <div style="overflow:auto; max-height:420px; border:1px solid #e5e7eb; border-radius:8px;">
                    <table style="width:100%; border-collapse:collapse; font-size:12.5px;">
                        @foreach($planilhaOriginalLinhas as $indiceLinha => $linha)
                            <tr style="border-bottom:1px solid #f3f4f6; {{ $indiceLinha === 0 ? 'background:#f3f4f6; font-weight:700; position:sticky; top:0;' : '' }}">
                                @foreach($linha as $celula)
                                    <td style="padding:7px 12px; white-space:nowrap; color:#374151;">{{ $celula }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif
        @else
            <p style="margin:0; font-size:13px; color:#9ca3af;">Nenhuma planilha original enviada ainda.</p>
        @endif
    </div>

    {{-- Totais --}}
    <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px;">
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:14px 18px; min-width:180px;">
            <div style="font-size:12px; color:#6b7280; font-weight:600;">Total no histórico</div>
            <div style="font-size:22px; font-weight:700; color:#111827; margin-top:2px;">{{ $totalGeral }}</div>
            <div style="font-size:11.5px; color:#9ca3af; margin-top:2px;">{{ $totalFluxoAtivo }} do fluxo · {{ $totalPlanilha }} da planilha</div>
        </div>
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:14px 18px; min-width:160px;">
            <div style="font-size:12px; color:#6b7280; font-weight:600;">Valor total</div>
            <div style="font-size:22px; font-weight:700; color:#111827; margin-top:2px;">R$ {{ number_format($valorTotal, 2, ',', '.') }}</div>
        </div>
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:14px 18px; flex:1; min-width:240px;">
            <div style="font-size:12px; color:#6b7280; font-weight:600; margin-bottom:6px;">Por aba da planilha</div>
            <div style="display:flex; flex-wrap:wrap; gap:6px 14px;">
                @forelse($totaisPorAba as $linha)
                    <span style="font-size:12.5px; color:#374151;">{{ $linha->aba_origem }}: <strong>{{ $linha->total }}</strong></span>
                @empty
                    <span style="font-size:12.5px; color:#9ca3af;">Nenhum registro da planilha ainda.</span>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('admin.historico-compras') }}" style="display:flex; gap:10px; flex-wrap:wrap; align-items:end; margin-bottom:16px;">
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

    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
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
                    'aprovado'  => ['barra' => '#16a34a', 'bg' => '#dcfce7', 'texto' => '#15803d'],
                    'rejeitado' => ['barra' => '#dc2626', 'bg' => '#fee2e2', 'texto' => '#b91c1c'],
                    'cotacao'   => ['barra' => '#d97706', 'bg' => '#fef3c7', 'texto' => '#b45309'],
                    'pendente'  => ['barra' => '#d97706', 'bg' => '#fef3c7', 'texto' => '#b45309'],
                    'parcial'   => ['barra' => '#64748b', 'bg' => '#e2e8f0', 'texto' => '#475569'],
                ][$tipoChaveHist] ?? ['barra' => '#64748b', 'bg' => '#e2e8f0', 'texto' => '#475569'];
                $produtosResumoHist = $grupo->pluck('product_name')->filter()->implode(', ');
                if (mb_strlen($produtosResumoHist) > 60) {
                    $produtosResumoHist = mb_substr($produtosResumoHist, 0, 60) . '…';
                }
                $valorGrupoHist = $grupo->sum('valor');
                $origemLabel = $primeiroHist->aba_origem
                    ? $primeiroHist->aba_origem . ($primeiroHist->mes_origem ? ' · ' . $primeiroHist->mes_origem : '')
                    : 'Requisição #' . $primeiroHist->id;
                $dataGrupoHist = $primeiroHist->data_compra?->format('d/m/Y')
                    ?? ($primeiroHist->tipo_registro === 'requisicao' ? $primeiroHist->created_at->format('d/m/Y') : 'Sem data');
            @endphp
            <div style="border-bottom:0.5px solid #e5e7eb;">
                <div style="display:flex; align-items:center; gap:12px; min-height:52px; padding:8px 16px 8px 0; cursor:pointer;"
                     onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background='transparent'"
                     onclick="toggleGrupoHistorico('{{ $chaveHist }}')">
                    <div style="width:4px; align-self:stretch; border-radius:2px; background:{{ $corsGrupoHist['barra'] }}; margin-left:16px;"></div>
                    <div style="flex:1; min-width:0;">
                        <div style="font-size:13.5px; line-height:1.4;">
                            <span style="color:#111827; font-weight:700;">{{ $origemLabel }}</span>
                            <span style="color:#9ca3af; font-weight:500;"> — {{ $dataGrupoHist }}</span>
                        </div>
                        <div style="font-size:12px; color:#6b7280; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            {{ $grupo->count() }} {{ $grupo->count() > 1 ? 'itens' : 'item' }} · {{ $produtosResumoHist }}
                            @if($valorGrupoHist > 0)
                                · R$ {{ number_format($valorGrupoHist, 2, ',', '.') }}
                            @endif
                        </div>
                    </div>
                    <span style="background:{{ $corsGrupoHist['bg'] }}; color:{{ $corsGrupoHist['texto'] }}; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; white-space:nowrap;">{{ $rotuloGrupoHist }}</span>
                    <button type="button" onclick="event.stopPropagation(); toggleGrupoHistorico('{{ $chaveHist }}')"
                            style="border:1px solid #d1d5db; background:#fff; color:#374151; padding:6px 14px; border-radius:6px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap; margin-right:16px;">
                        <span id="seta-hist-{{ $chaveHist }}">Ver itens</span>
                    </button>
                </div>
                <div id="itens-hist-{{ $chaveHist }}" style="display:none; padding:4px 16px 14px 44px; background:#fafafa;">
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
                        <div style="padding:8px 0; border-bottom:0.5px solid #eee;">
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
                                <a href="{{ route('admin.compras.edit', $itemHist->id) }}" style="font-size:12px; color:#05018D; font-weight:600;">
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
            </div>
        @empty
            <div style="padding:48px 16px; text-align:center;">
                <p style="color:#6b7280; font-size:15px; margin:0 0 4px;">Nenhum registro no histórico ainda</p>
                <p style="color:#9ca3af; font-size:13px; margin:0;">Requisições aparecem aqui assim que criadas. Rode <code>php artisan compras:importar-historico</code> para trazer o histórico da planilha.</p>
            </div>
        @endforelse
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
    bloco.style.display = abrindo ? 'block' : 'none';
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
