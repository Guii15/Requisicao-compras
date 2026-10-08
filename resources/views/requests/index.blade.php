@extends('layouts.app')

{{-- Listagem usa a largura toda da tela (o layout lê esta seção) --}}
@section('tela_cheia', '1')

@section('content')

<style>

@media (max-width: 768px) {
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

    {{-- Stats: faixa única com os cinco números e a tendência dos últimos 6 meses (igual ao painel do admin) --}}
    <div class="idx-stats bm-faixa" style="display:grid; grid-template-columns:repeat(5,1fr); background:#fff; border:1px solid #e5e7eb; border-radius:10px; margin-bottom:20px; overflow:hidden;">
        <x-bloco-metrica rotulo="Total de requisições" :valor="number_format($stats['total'], 0, ',', '.')" :serie="$tendencias['total']" />
        <x-bloco-metrica rotulo="Pendentes" um="pendente" varios="pendentes" :valor="number_format($stats['pendente'], 0, ',', '.')" :serie="$tendencias['pendente']" />
        <x-bloco-metrica rotulo="Aprovadas" um="aprovada" varios="aprovadas" :valor="number_format($stats['aprovado'], 0, ',', '.')" :serie="$tendencias['aprovado']" />
        <x-bloco-metrica rotulo="Rejeitadas" um="rejeitada" varios="rejeitadas" :valor="number_format($stats['rejeitado'], 0, ',', '.')" :serie="$tendencias['rejeitado']" />
        <x-bloco-metrica rotulo="Total gasto" :valor="'R$ ' . number_format($stats['total_gasto'], 2, ',', '.')" :serie="$tendencias['gasto']" formato="dinheiro" />
    </div>

    {{-- Gráficos --}}
    <div class="idx-charts" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; margin-bottom:20px;">

        {{-- Gasto mensal --}}
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px;">
            <div style="font-size:14px; font-weight:700; color:#374151; margin-bottom:10px;">
                Gasto mensal <span style="font-size:12px; font-weight:400; color:#9ca3af;">aprovadas</span>
            </div>
            @php $maxMonth = $monthlySpending->max('total') ?: 1; @endphp
            <x-grafico-gasto-mensal :meses="$monthlySpending" />
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
                        <div style="height:100%; width:{{ \App\Support\LarguraBarra::percentual($v->total_gasto, $maxSpend) }}%; background:#05018D; border-radius:2px;"></div>
                    </div>
                </div>
                <div style="font-size:13px; font-weight:700; color:#05018D; white-space:nowrap;">R$ {{ number_format($v->total_gasto, 2, ',', '.') }}</div>
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
                        <div style="height:100%; width:{{ \App\Support\LarguraBarra::percentual($s->total_gasto, $maxSupplierSpend) }}%; background:#05018D; border-radius:2px;"></div>
                    </div>
                </div>
                <div style="font-size:13px; font-weight:700; color:#05018D; white-space:nowrap;">R$ {{ number_format($s->total_gasto, 2, ',', '.') }}</div>
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
    <div class="lista-resp" style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">
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
                            <td class="lr-num" style="{{ $tdReq }} font-weight:700; color:#111827; white-space:nowrap;">#{{ $primeiroDoGrupo->id }}</td>
                            <td data-rotulo="Vendedor" style="{{ $tdReq }}">{{ $primeiroDoGrupo->requester_name ?? 'Não informado' }}</td>
                            <td class="lr-larga" data-rotulo="Itens" style="{{ $tdReq }} max-width:420px;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <span style="font-weight:600; color:#111827; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $produtosResumoV }}</span>
                                    @if($grupoUrgenteV)
                                        <span style="flex:none; color:#b8301a; border:1px solid #b8301a; padding:0 8px; border-radius:9999px; font-size:11px; font-weight:700;">Urgente</span>
                                    @endif
                                </div>
                                <div style="font-size:12px; color:#6b7280; margin-top:2px;">{{ $grupo->count() }} {{ $grupo->count() > 1 ? 'itens' : 'item' }}</div>
                            </td>
                            <td data-rotulo="Total" style="{{ $tdReq }} text-align:right; font-weight:600; color:#111827; white-space:nowrap;">{{ $totalGrupoV > 0 ? 'R$ ' . number_format($totalGrupoV, 2, ',', '.') : '—' }}</td>
                            <td data-rotulo="Etapa" style="{{ $tdReq }}"><x-trilha-etapas :itens="$grupo" /></td>
                            <td data-rotulo="Coleta" style="{{ $tdReq }} font-size:13px; white-space:nowrap;">
                                @if($grupoAtrasadoV)
                                    <strong style="color:#b8301a;">Atrasada</strong>
                                @elseif($grupoTemAprovadoV)
                                    <span style="color:#6b7280;">No prazo</span>
                                @else
                                    <span style="color:#9ca3af;">—</span>
                                @endif
                            </td>
                            <td data-rotulo="Data" style="{{ $tdReq }} font-size:13px; white-space:nowrap;">
                                {{ $primeiroDoGrupo->created_at->timezone('America/Sao_Paulo')->format('d/m/Y') }}
                                <span style="display:block; font-size:12px; color:#6b7280;">{{ $primeiroDoGrupo->created_at->timezone('America/Sao_Paulo')->format('H:i') }}</span>
                            </td>
                            <td data-rotulo="Status" style="{{ $tdReq }}"><x-status-requisicao :status="$statusChaveV" /></td>
                            <td class="lr-acao" style="{{ $tdReq }} text-align:right;">
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

    {{-- Paginação --}}
    @if($requests->hasPages())
        <div style="margin-top:20px; display:flex; justify-content:center;">
            {{ $requests->links() }}
        </div>
    @endif

</div>

<script>
function toggleGrupoRequisicao(chave) {
    var linhas = document.querySelectorAll('.grupo-item-' + CSS.escape(chave));
    if (!linhas.length) return;
    var abrindo = linhas[0].style.display === 'none';
    linhas.forEach(function (linha) {
        linha.style.display = abrindo ? '' : 'none'; // '' devolve ao CSS: linha de tabela no PC, bloco no celular
    });
    // Desktop e mobile têm um rótulo cada com o mesmo id; getElementById só achava o do desktop.
    document.querySelectorAll('[id="seta-grupo-' + chave + '"]').forEach(function (seta) {
        seta.textContent = abrindo ? 'Ocultar itens' : 'Ver itens';
    });
}
</script>

@endsection
