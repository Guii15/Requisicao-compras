@extends('layouts.app')

{{-- Listagem usa a largura toda da tela (o layout lê esta seção) --}}
@section('tela_cheia', '1')

@section('content')

<style>
.idx-mobile-cards { display: none; }

@media (max-width: 768px) {
    .idx-desktop-table { display: none; }
    .idx-mobile-cards  { display: block; }
    .idx-header { flex-direction: column; align-items: flex-start !important; }
    .idx-nova-btn { width: 100%; justify-content: center; }
    .idx-filters form { grid-template-columns: 1fr !important; }
    .idx-stats { grid-template-columns: 1fr 1fr !important; }
    .idx-charts { grid-template-columns: 1fr !important; }
}
</style>

<div style="padding: 8px 0;">

    {{-- Cabeçalho --}}
    <div class="idx-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="margin:0; font-size:24px; font-weight:700; color:#1e3a8a;">Minhas Requisições</h1>
            <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Acompanhe todas as suas solicitações de compra</p>
        </div>
        <a href="{{ route('requests.create') }}" class="idx-nova-btn"
           style="display:inline-flex; align-items:center; gap:8px; background:#05018D; color:#fff; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:600; font-size:14px; box-shadow:0 2px 6px rgba(0,0,0,0.15);">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px; height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nova Requisição
        </a>
    </div>

    {{-- Mensagem de sucesso --}}
    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; border:1px solid #86efac; padding:12px 16px; border-radius:8px; margin-bottom:20px; display:flex; align-items:center; gap:8px; font-size:14px;">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:18px; height:18px; flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="idx-stats" style="display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin-bottom:20px;">
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:18px 20px; border-top:3px solid #6b7280;">
            <p style="margin:0; font-size:26px; font-weight:800; color:#374151;">{{ $stats['total'] }}</p>
            <p style="margin:4px 0 0; font-size:12px; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px;">Total</p>
        </div>
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:18px 20px; border-top:3px solid #f59e0b;">
            <p style="margin:0; font-size:26px; font-weight:800; color:#d97706;">{{ $stats['pendente'] }}</p>
            <p style="margin:4px 0 0; font-size:12px; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px;">Pendentes</p>
        </div>
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:18px 20px; border-top:3px solid #16a34a;">
            <p style="margin:0; font-size:26px; font-weight:800; color:#16a34a;">{{ $stats['aprovado'] }}</p>
            <p style="margin:4px 0 0; font-size:12px; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px;">Aprovadas</p>
        </div>
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:18px 20px; border-top:3px solid #dc2626;">
            <p style="margin:0; font-size:26px; font-weight:800; color:#dc2626;">{{ $stats['rejeitado'] }}</p>
            <p style="margin:4px 0 0; font-size:12px; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px;">Rejeitadas</p>
        </div>
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:18px 20px; border-top:3px solid #059669;">
            <p style="margin:0; font-size:20px; font-weight:800; color:#059669;">R$ {{ number_format($stats['total_gasto'], 2, ',', '.') }}</p>
            <p style="margin:4px 0 0; font-size:12px; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px;">Total Gasto</p>
        </div>
    </div>

    {{-- Gráficos --}}
    <div class="idx-charts" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; margin-bottom:20px;">

        {{-- Gasto mensal --}}
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px;">
            <div style="font-size:14px; font-weight:700; color:#374151; margin-bottom:16px;">
                Gasto mensal <span style="font-size:12px; font-weight:400; color:#9ca3af;">aprovadas</span>
            </div>
            @php $maxMonth = $monthlySpending->max('total') ?: 1; @endphp
            <div style="display:flex; align-items:flex-end; gap:8px; height:100px;">
                @foreach($monthlySpending as $i => $m)
                <div style="flex:1; display:flex; flex-direction:column; align-items:center; gap:4px;">
                    <div style="font-size:10px; color:#9ca3af; white-space:nowrap;">
                        @if($m['total'] > 0) R$ {{ number_format($m['total']/1000, 1, ',', '.') }}k @endif
                    </div>
                    <div style="width:100%; border-radius:4px 4px 0 0; background:{{ $i == 5 ? '#059669' : '#d1fae5' }}; height:{{ max(6, round($m['total']/$maxMonth*72)) }}px;"></div>
                    <div style="font-size:11px; color:#6b7280; white-space:nowrap;">{{ $m['label'] }}</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Maiores gastos por vendedor --}}
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px;">
            <div style="font-size:14px; font-weight:700; color:#374151; margin-bottom:12px;">
                Maiores gastos <span style="font-size:12px; font-weight:400; color:#9ca3af;">por vendedor</span>
            </div>
            @php $maxSpend = $vendorSpending->max('total_gasto') ?: 1; @endphp
            @forelse($vendorSpending->take(5) as $v)
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                <div style="flex:1; min-width:0;">
                    <div style="font-size:13px; font-weight:600; color:#374151; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $v->requester_name }}</div>
                    <div style="height:4px; background:#e5e7eb; border-radius:2px; margin-top:4px;">
                        <div style="height:100%; width:{{ \App\Support\LarguraBarra::percentual($v->total_gasto, $maxSpend) }}%; background:#059669; border-radius:2px;"></div>
                    </div>
                </div>
                <div style="font-size:13px; font-weight:700; color:#059669; white-space:nowrap;">R$ {{ number_format($v->total_gasto, 2, ',', '.') }}</div>
            </div>
            @empty
            <p style="font-size:13px; color:#9ca3af; margin:0;">Nenhum gasto aprovado ainda.</p>
            @endforelse
        </div>

        {{-- Maiores gastos por fornecedor --}}
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px;">
            <div style="font-size:14px; font-weight:700; color:#374151; margin-bottom:12px;">
                Maiores gastos <span style="font-size:12px; font-weight:400; color:#9ca3af;">por fornecedor</span>
            </div>
            @php $maxSupplierSpend = $supplierSpending->max('total_gasto') ?: 1; @endphp
            @forelse($supplierSpending->take(5) as $s)
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                <div style="flex:1; min-width:0;">
                    <div style="font-size:13px; font-weight:600; color:#374151; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $s->supplier }}</div>
                    <div style="height:4px; background:#e5e7eb; border-radius:2px; margin-top:4px;">
                        <div style="height:100%; width:{{ \App\Support\LarguraBarra::percentual($s->total_gasto, $maxSupplierSpend) }}%; background:#2563eb; border-radius:2px;"></div>
                    </div>
                </div>
                <div style="font-size:13px; font-weight:700; color:#2563eb; white-space:nowrap;">R$ {{ number_format($s->total_gasto, 2, ',', '.') }}</div>
            </div>
            @empty
            <p style="font-size:13px; color:#9ca3af; margin:0;">Nenhum gasto aprovado ainda.</p>
            @endforelse
        </div>

    </div>

    {{-- Filtros --}}
    <div class="idx-filters" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
        <p style="margin:0 0 14px; font-size:13px; font-weight:600; color:#374151; text-transform:uppercase; letter-spacing:0.5px;">Filtrar Requisições</p>
        <form method="GET" action="{{ route('requests.index') }}" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; align-items:end;">
            <div>
                <label style="display:block; font-size:13px; font-weight:500; color:#374151; margin-bottom:6px;">Vendedor</label>
                <input type="text" name="requester_name" value="{{ request('requester_name') }}" placeholder="Nome do vendedor"
                       style="width:100%; border:1px solid #d1d5db; border-radius:7px; padding:8px 12px; font-size:14px; box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block; font-size:13px; font-weight:500; color:#374151; margin-bottom:6px;">Produto</label>
                <input type="text" name="product_name" value="{{ request('product_name') }}" placeholder="Nome do produto"
                       style="width:100%; border:1px solid #d1d5db; border-radius:7px; padding:8px 12px; font-size:14px; box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block; font-size:13px; font-weight:500; color:#374151; margin-bottom:6px;">Data inicial</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       style="width:100%; border:1px solid #d1d5db; border-radius:7px; padding:8px 12px; font-size:14px; box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block; font-size:13px; font-weight:500; color:#374151; margin-bottom:6px;">Data final</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       style="width:100%; border:1px solid #d1d5db; border-radius:7px; padding:8px 12px; font-size:14px; box-sizing:border-box;">
            </div>
            <div style="display:flex; gap:8px;">
                <button type="submit" style="flex:1; background:#05018D; color:#fff; padding:9px 16px; border:none; border-radius:7px; font-size:14px; font-weight:600; cursor:pointer;">Filtrar</button>
                <a href="{{ route('requests.index') }}" style="flex:1; background:#f3f4f6; color:#374151; padding:9px 16px; border-radius:7px; font-size:14px; font-weight:500; text-decoration:none; text-align:center; border:1px solid #e5e7eb;">Limpar</a>
            </div>
        </form>
    </div>

    {{-- TABELA (desktop) --}}
    {{-- Uma linha por requisição; ao abrir, cada item vira um cartão (<x-item-requisicao>). --}}
    <div class="idx-desktop-table" style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fafc; border-bottom:1px solid #e5e7eb;">
                        @php $thReq = 'padding:12px 16px; color:#6b7280; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; white-space:nowrap;'; @endphp
                        <th style="{{ $thReq }} text-align:left;">Nº</th>
                        <th style="{{ $thReq }} text-align:left;">Vendedor</th>
                        <th style="{{ $thReq }} text-align:left;">Itens</th>
                        <th style="{{ $thReq }} text-align:right;">Total</th>
                        <th style="{{ $thReq }} text-align:left;">Etapa</th>
                        <th style="{{ $thReq }} text-align:left;">Coleta</th>
                        <th style="{{ $thReq }} text-align:left;">Data</th>
                        <th style="{{ $thReq }} text-align:left;">Status</th>
                        <th style="{{ $thReq }} text-align:right;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $grupo)
                        @php
                            $primeiroDoGrupo = $grupo->first();
                            $chaveGrupo = $primeiroDoGrupo->grupo_id;
                            $statusUnicos = $grupo->pluck('status')->unique();
                            $statusChaveV = $statusUnicos->count() === 1 ? $statusUnicos->first() : 'parcial';
                            $produtosResumoV = $grupo->pluck('product_name')->filter()->implode(', ');
                            if (mb_strlen($produtosResumoV) > 80) {
                                $produtosResumoV = mb_substr($produtosResumoV, 0, 80) . '…';
                            }
                            $totalGrupoV = (float) $grupo->sum('valor');
                            $grupoUrgenteV = $grupo->contains('urgency', 'alta');
                            $grupoAtrasadoV = $grupo->contains('status_coleta', 'atraso');
                            $grupoTemAprovadoV = $grupo->contains('status', 'aprovado');
                            $tdReq = 'padding:12px 16px; font-size:14px; color:#374151; vertical-align:middle;';
                        @endphp
                        <tr class="grupo-cabecalho" style="border-bottom:1px solid #eef0f3; cursor:pointer;" onmouseover="this.style.background='#f7f8fa'" onmouseout="this.style.background='transparent'" onclick="toggleGrupoRequisicao('{{ $chaveGrupo }}')">
                            <td style="{{ $tdReq }} font-weight:700; color:#111827; white-space:nowrap;">#{{ $primeiroDoGrupo->id }}</td>
                            <td style="{{ $tdReq }}">{{ $primeiroDoGrupo->requester_name ?? 'Não informado' }}</td>
                            <td style="{{ $tdReq }} max-width:420px;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <span style="font-weight:600; color:#111827; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $produtosResumoV }}</span>
                                    @if($grupoUrgenteV)
                                        <span style="flex:none; color:#b8301a; border:1px solid #b8301a; padding:0 8px; border-radius:9999px; font-size:11px; font-weight:700;">Urgente</span>
                                    @endif
                                </div>
                                <div style="font-size:12px; color:#6b7280; margin-top:2px;">{{ $grupo->count() }} {{ $grupo->count() > 1 ? 'itens' : 'item' }}</div>
                            </td>
                            <td style="{{ $tdReq }} text-align:right; font-weight:600; color:#111827; white-space:nowrap;">{{ $totalGrupoV > 0 ? 'R$ ' . number_format($totalGrupoV, 2, ',', '.') : '—' }}</td>
                            <td style="{{ $tdReq }}"><x-trilha-etapas :itens="$grupo" /></td>
                            <td style="{{ $tdReq }} font-size:13px; white-space:nowrap;">
                                @if($grupoAtrasadoV)
                                    <strong style="color:#b8301a;">Atrasada</strong>
                                @elseif($grupoTemAprovadoV)
                                    <span style="color:#6b7280;">No prazo</span>
                                @else
                                    <span style="color:#9ca3af;">—</span>
                                @endif
                            </td>
                            <td style="{{ $tdReq }} font-size:13px; white-space:nowrap;">
                                {{ $primeiroDoGrupo->created_at->timezone('America/Sao_Paulo')->format('d/m/Y') }}
                                <span style="display:block; font-size:12px; color:#6b7280;">{{ $primeiroDoGrupo->created_at->timezone('America/Sao_Paulo')->format('H:i') }}</span>
                            </td>
                            <td style="{{ $tdReq }}"><x-status-requisicao :status="$statusChaveV" /></td>
                            <td style="{{ $tdReq }} text-align:right;">
                                <button type="button" onclick="event.stopPropagation(); toggleGrupoRequisicao('{{ $chaveGrupo }}')"
                                        style="border:1px solid #d1d5db; background:#fff; color:#374151; padding:7px 16px; border-radius:9999px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap;">
                                    <span id="seta-grupo-{{ $chaveGrupo }}">Ver itens</span>
                                </button>
                            </td>
                        </tr>
                        @foreach($grupo as $req)
                        <tr class="grupo-item-{{ $chaveGrupo }}" style="display:none; background:#f7f8fa;">
                            <td colspan="9" style="padding:{{ $loop->first ? '14px' : '0' }} 20px {{ $loop->last ? '18px' : '10px' }} 20px; {{ $loop->last ? 'border-bottom:1px solid #e5e7eb;' : '' }}">
                                <x-item-requisicao :req="$req">
                                    @if($req->status === 'pendente')
                                        <a href="{{ route('requests.edit', $req) }}"
                                           style="background:#fff; color:#374151; border:1px solid #d1d5db; border-radius:9999px; padding:7px 16px; font-size:12.5px; font-weight:600; text-decoration:none; white-space:nowrap;">
                                            Editar
                                        </a>
                                    @endif
                                    <a href="{{ route('requests.export', $req) }}" target="_blank"
                                       style="background:#fff; color:#374151; border:1px solid #d1d5db; border-radius:9999px; padding:7px 16px; font-size:12.5px; font-weight:600; text-decoration:none; white-space:nowrap;">
                                        Exportar
                                    </a>
                                </x-item-requisicao>
                            </td>
                        </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="9" style="padding:48px 16px; text-align:center;">
                                <p style="color:#6b7280; font-size:15px; margin:0 0 4px;">Nenhuma requisição encontrada</p>
                                <p style="color:#9ca3af; font-size:13px; margin:0;">Clique em "Nova Requisição" para criar a primeira</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- CARDS (mobile) --}}
    <div class="idx-mobile-cards">
        @forelse($requests as $grupo)
            @php
                $primeiroDoGrupoM = $grupo->first();
                $chaveGrupoM = $primeiroDoGrupoM->grupo_id;
                $statusUnicosM = $grupo->pluck('status')->unique();
                if ($statusUnicosM->count() === 1) {
                    $statusChaveVM = $statusUnicosM->first();
                    $rotuloGrupoStatusM = ['aprovado' => 'Aprovado', 'rejeitado' => 'Rejeitado', 'pendente' => 'Pendente'][$statusChaveVM] ?? ucfirst($statusChaveVM);
                } else {
                    $statusChaveVM = 'parcial';
                    $rotuloGrupoStatusM = 'Parcial';
                }
                $corsGrupoVM = [
                    'pendente'  => ['barra' => '#e5e7eb', 'bg' => '#f4b728', 'texto' => '#2b1d00'],
                    'aprovado'  => ['barra' => '#e5e7eb', 'bg' => '#17794a', 'texto' => '#ffffff'],
                    'rejeitado' => ['barra' => '#e5e7eb', 'bg' => '#b8301a', 'texto' => '#ffffff'],
                    'parcial'   => ['barra' => '#e5e7eb', 'bg' => '#475569', 'texto' => '#ffffff'],
                ][$statusChaveVM];
                $produtosResumoVM = $grupo->pluck('product_name')->filter()->implode(', ');
                if (mb_strlen($produtosResumoVM) > 60) {
                    $produtosResumoVM = mb_substr($produtosResumoVM, 0, 60) . '…';
                }
            @endphp
            <div style="background:#fff; border:0.5px solid #e5e7eb; border-radius:10px; margin-bottom:10px; cursor:pointer; overflow:hidden;"
                 onclick="toggleGrupoRequisicao('{{ $chaveGrupoM }}')">
                <div style="display:flex; align-items:stretch; gap:10px;">
                    <div style="width:4px; background:{{ $corsGrupoVM['barra'] }};"></div>
                    <div style="flex:1; min-width:0; padding:12px 12px 12px 0;">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                            <div style="font-size:14px; font-weight:700; color:#111827;">Requisição #{{ $primeiroDoGrupoM->id }}</div>
                            <span style="background:{{ $corsGrupoVM['bg'] }}; color:{{ $corsGrupoVM['texto'] }}; padding:3px 10px; border-radius:20px; font-size:11.5px; font-weight:700; white-space:nowrap;">{{ $rotuloGrupoStatusM }}</span>
                        </div>
                        <div style="font-size:12.5px; color:#9ca3af; margin-top:2px;">{{ $primeiroDoGrupoM->requester_name ?? 'Não informado' }}</div>
                        <div style="font-size:12px; color:#6b7280; margin-top:4px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            {{ $grupo->count() }} {{ $grupo->count() > 1 ? 'itens' : 'item' }} · {{ $produtosResumoVM }}
                        </div>
                        <button type="button" class="m-botao" onclick="event.stopPropagation(); toggleGrupoRequisicao('{{ $chaveGrupoM }}')"
                                style="margin-top:8px; border:1px solid #d1d5db; background:#fff; color:#374151; padding:5px 12px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer;">
                            <span id="seta-grupo-{{ $chaveGrupoM }}">Ver itens</span>
                        </button>
                    </div>
                </div>
            </div>
            @foreach($grupo as $req)
            <div class="grupo-item-{{ $chaveGrupoM }}" style="display:none; background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin:-6px 0 12px 12px; box-shadow:0 1px 3px rgba(0,0,0,0.06);">

                {{-- Topo do card: produto + status --}}
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                    <div>
                        <div style="font-size:15px; font-weight:700; color:#1e3a8a;">{{ $req->product_name }}</div>
                        <x-parcial-info :item="$req" />
                        @if($req->product_code)
                            <div style="font-size:12px; color:#9ca3af; margin-top:2px;">Cód: {{ $req->product_code }}</div>
                        @endif
                        @if($req->product_url)
                            <a href="{{ $req->product_url }}" target="_blank" style="display:block; font-size:11px; color:#1e3a8a; text-decoration:underline; margin-top:2px;">Ver link</a>
                        @endif
                        @if($req->anexo_path)
                            <a href="{{ route('requests.anexo', $req) }}" target="_blank" style="display:block; font-size:11px; color:#1e3a8a; text-decoration:underline; margin-top:2px;">📎 {{ $req->anexo_nome }}</a>
                        @endif
                        @if($req->entrada_concluida_em)
                            <span style="display:inline-block; margin-top:4px; background:#f3f4f6; color:#374151; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Entrada Realizada</span>
                            <span style="display:block; margin-top:2px; font-size:11px; color:#9ca3af;">{{ $req->entrada_concluida_em->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</span>
                        @elseif($req->status_conferencia === 'conferido_ok')
                            <span style="display:inline-block; margin-top:4px; background:#f3f4f6; color:#374151; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido ✓ OK</span>
                        @elseif($req->status_conferencia === 'divergente')
                            <span style="display:inline-block; margin-top:4px; background:#fff; color:#b8301a; border:1px solid #b8301a; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido — Divergente</span>
                        @elseif($req->status_conferencia === 'avancado_mesmo_assim')
                            <span style="display:inline-block; margin-top:4px; background:#f3f4f6; color:#374151; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido — Avançado Mesmo Assim</span>
                        @elseif($req->status_conferencia === 'cancelado')
                            <span style="display:inline-block; margin-top:4px; background:#fff; color:#b8301a; border:1px solid #b8301a; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Cancelado</span>
                        @elseif($req->status_conferencia === 'legado')
                        @elseif($req->status === 'aprovado')
                            <span style="display:inline-block; margin-top:4px; background:#f3f4f6; color:#6b7280; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Aguardando conferência</span>
                        @endif
                        @if($req->fotosConferencia->isNotEmpty())
                            <a href="javascript:void(0)" onclick="document.getElementById('foto-{{ $req->id }}').style.display='flex'" title="Ver foto da conferência">
                                <img src="{{ Storage::url($req->fotosConferencia->first()->caminho_arquivo) }}" alt="Foto da conferência"
                                     style="width:40px; height:40px; object-fit:cover; border-radius:6px; border:1px solid #e5e7eb; display:block; margin-top:4px;">
                            </a>
                        @endif
                    </div>
                    @if($req->status=='aprovado')
                        <span style="background:#fff; color:#17794a; border:1px solid #17794a; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; white-space:nowrap;">Aprovado</span>
                    @elseif($req->status=='rejeitado')
                        <span style="background:#fff; color:#b8301a; border:1px solid #b8301a; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; white-space:nowrap;">Rejeitado</span>
                    @else
                        <span style="background:#fff; color:#7a4f00; border:1px solid #c98a00; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; white-space:nowrap;">Pendente</span>
                    @endif
                </div>

                {{-- Detalhes --}}
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:13px;">
                    <div>
                        <span style="color:#9ca3af;">Vendedor</span>
                        <div style="font-weight:600; color:#374151;">{{ $req->requester_name ?? '—' }}</div>
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Fornecedor</span>
                        <div style="font-weight:600; color:#374151;">{{ $req->supplier ?? '—' }}</div>
                        @if($req->temDadosDaCompra())
                            <div style="font-size:11px; color:#6b7280; margin-top:3px; line-height:1.5;">
                                Unitário: R$ {{ number_format($req->preco_unitario, 2, ',', '.') }}
                                @if($req->valor)
                                    <br>Total: <strong style="color:#059669;">R$ {{ number_format($req->valor, 2, ',', '.') }}</strong>
                                @endif
                                @if($req->data_compra)
                                    <br>Compra: {{ $req->data_compra->format('d/m/Y') }}
                                @endif
                                @if($req->data_coleta)
                                    <br>Coleta: {{ $req->data_coleta->format('d/m/Y') }}{{ $req->coletado_por ? ' (' . $req->coletado_por . ')' : '' }}
                                @endif
                                @if($req->pedido_compra_path)
                                    <br><a href="{{ route('admin.compras.pedido', $req) }}" target="_blank" style="color:#1e3a8a; text-decoration:underline;">📎 Pedido de compra</a>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Quantidade</span>
                        <div style="font-weight:700; color:#374151; font-size:15px;">{{ $req->quantity }}</div>
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Data</span>
                        <div style="font-weight:600; color:#374151;">{{ $req->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</div>
                    </div>
                </div>

                <x-obs-admin :item="$req" margem="10px" />
                <x-obs-entrada :item="$req" margem="10px" />

                {{-- Rodapé do card: urgência + exportar --}}
                <div class="m-coluna" style="margin-top:10px; padding-top:10px; border-top:1px solid #f3f4f6; display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="font-size:12px; color:#9ca3af;">Urgência:</span>
                        @if($req->urgency=='alta')
                            <span style="background:#fff; color:#b8301a; border:1px solid #b8301a; padding:2px 10px; border-radius:20px; font-size:12px; font-weight:600;">Alta</span>
                        @elseif($req->urgency=='media')
                            <span style="background:#f3f4f6; color:#374151; padding:2px 10px; border-radius:20px; font-size:12px; font-weight:600;">Média</span>
                        @else
                            <span style="background:#f3f4f6; color:#374151; padding:2px 10px; border-radius:20px; font-size:12px; font-weight:600;">Baixa</span>
                        @endif
                    </div>
                    <div class="m-card-acao" style="display:flex; gap:6px;">
                        @if($req->status === 'pendente')
                        <a href="{{ route('requests.edit', $req) }}"
                           style="background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; border-radius:7px; padding:7px 14px; font-size:13px; font-weight:600; text-decoration:none;">
                            Editar
                        </a>
                        @endif
                        <a href="{{ route('requests.export', $req) }}" target="_blank"
                           style="background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; border-radius:7px; padding:7px 14px; font-size:13px; font-weight:600; text-decoration:none;">
                            Exportar
                        </a>
                    </div>
                </div>

            </div>
            @endforeach
        @empty
            <div style="text-align:center; padding:48px 16px;">
                <p style="color:#6b7280; font-size:15px; margin:0 0 4px;">Nenhuma requisição encontrada</p>
                <p style="color:#9ca3af; font-size:13px; margin:0;">Clique em "Nova Requisição" para criar a primeira</p>
            </div>
        @endforelse
    </div>

    {{-- Paginação --}}
    @if($requests->hasPages())
        <div style="margin-top:20px; display:flex; justify-content:center;">
            {{ $requests->links() }}
        </div>
    @endif

</div>

{{-- Modais de foto da conferência --}}
@foreach($requests as $grupo)
    @foreach($grupo as $req)
        @if($req->fotosConferencia->isNotEmpty())
            <div id="foto-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                <div class="m-modal-caixa" style="background:#fff; border-radius:12px; padding:28px; width:100%; max-width:440px; margin:16px; box-shadow:0 20px 40px rgba(0,0,0,0.2);">
                    <h3 style="margin:0 0 4px; font-size:16px; font-weight:700; color:#1e3a8a;">Foto da Conferência</h3>
                    <p style="margin:0 0 16px; font-size:12px; color:#9ca3af;">{{ $req->product_name }}</p>
                    @foreach($req->fotosConferencia as $fotoQuadro)
                        <img src="{{ Storage::url($fotoQuadro->caminho_arquivo) }}" alt="Foto da conferência"
                             style="width:100%; border-radius:8px; margin-bottom:12px; display:block;">
                    @endforeach
                    <div class="m-modal-acoes" style="text-align:right;">
                        <button onclick="document.getElementById('foto-{{ $req->id }}').style.display='none'"
                                style="padding:9px 24px; border-radius:8px; border:1.5px solid #e5e7eb; background:#fff; color:#374151; font-size:14px; font-weight:600; cursor:pointer;">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endforeach

<script>
function toggleGrupoRequisicao(chave) {
    var linhas = document.querySelectorAll('.grupo-item-' + CSS.escape(chave));
    if (!linhas.length) return;
    var abrindo = linhas[0].style.display === 'none';
    linhas.forEach(function (linha) {
        linha.style.display = abrindo ? (linha.tagName === 'TR' ? 'table-row' : 'block') : 'none';
    });
    // Desktop e mobile têm um rótulo cada com o mesmo id; getElementById só achava o do desktop.
    document.querySelectorAll('[id="seta-grupo-' + chave + '"]').forEach(function (seta) {
        seta.textContent = abrindo ? 'Ocultar itens' : 'Ver itens';
    });
}
</script>

@endsection
