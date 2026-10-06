@extends('layouts.app')

{{-- Listagem usa a largura toda da tela (o layout lê esta seção) --}}
@section('tela_cheia', '1')

@section('content')

<style>
/* Campo de arquivo dos quadros: botão discreto no lugar do botão padrão do navegador */
.campo-arquivo { width: 100%; font-size: 13px; color: #6b7280; }
.campo-arquivo::file-selector-button { font: inherit; font-weight: 600; color: #374151; background: #fff; border: 1px solid #cfd3da; border-radius: 9999px; padding: 7px 14px; margin-right: 10px; cursor: pointer; }
.campo-arquivo::file-selector-button:hover { border-color: #05018D; color: #05018D; }
.adm-mobile-cards { display: none; }
@media (max-width: 768px) {
    .adm-desktop-table { display: none; }
    .adm-mobile-cards  { display: block; }
    .adm-stats { grid-template-columns: 1fr 1fr !important; }
    .adm-filters form { grid-template-columns: 1fr !important; }
    .adm-charts-grid { display: none !important; }
    .adm-charts-carousel { display: block !important; }
}
.adm-charts-carousel {
    display: none;
    position: relative;
    margin-bottom: 24px;
}
.adm-carousel-track {
    overflow: hidden;
    border-radius: 12px;
}
.adm-carousel-slides {
    display: flex;
    transition: transform 0.4s ease;
}
.adm-carousel-slides > div {
    min-width: 100%;
    box-sizing: border-box;
}
.adm-carousel-dots {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 10px;
}
.adm-carousel-dots span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #d1d5db;
    cursor: pointer;
    transition: background 0.3s;
}
.adm-carousel-dots span.active {
    background: #05018D;
}
</style>

<div style="padding: 8px 0;">

    {{-- Cabeçalho --}}
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Painel Administrativo</h1>
            <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Gerencie todas as requisições de compra</p>
        </div>
        <a href="{{ route('requests.create') }}"
           style="display:inline-flex; align-items:center; gap:8px; background:#05018D; color:#fff; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:600; font-size:14px; box-shadow:0 2px 6px rgba(0,0,0,0.15);">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px; height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nova Requisição
        </a>
    </div>

    @include('admin._abas')

    <div style="margin-bottom:20px;">
        <h2 style="margin:0; font-size:18px; font-weight:700; color:#111827;">Pendentes</h2>
        <p style="margin:4px 0 0; color:#6b7280; font-size:13px;">
            Requisições que ainda precisam de aprovação ou rejeição. Depois de decidido, o item sai daqui. Depois de aprovada, registre os dados da compra em
            <a href="{{ route('admin.compras.index') }}" style="color:#05018D; font-weight:600;">Compras</a>.
        </p>
    </div>

    {{-- Mensagem de sucesso --}}
    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; border:1px solid #86efac; padding:12px 16px; border-radius:8px; margin-bottom:20px; display:flex; align-items:center; gap:8px; font-size:14px;">
            ✓ {{ session('success') }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="adm-stats" style="display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin-bottom:24px;">
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

    {{-- Gráficos (desktop) --}}
    <div class="adm-charts-grid" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; margin-bottom:24px;">

        {{-- Gasto mensal --}}
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px;">
            <div style="font-size:14px; font-weight:700; color:#374151; margin-bottom:16px;">
                Gasto mensal <span style="font-size:12px; font-weight:400; color:#9ca3af;">aprovadas</span>
            </div>
            @php $maxMonth = $monthlySpending->max('total') ?: 1; @endphp
            <div style="display:flex; align-items:flex-end; gap:8px; height:100px;">
                @foreach($monthlySpending as $i => $m)
                <div onclick="openMonthModal('{{ $m['year'] }}','{{ $m['month'] }}','{{ $m['label'] }}')"
                     style="flex:1; display:flex; flex-direction:column; align-items:center; gap:4px; cursor:pointer;"
                     title="Ver detalhes de {{ $m['label'] }}">
                    <div style="font-size:10px; color:#9ca3af; white-space:nowrap;">
                        @if($m['total'] > 0) R$ {{ number_format($m['total']/1000, 1, ',', '.') }}k @endif
                    </div>
                    <div style="width:100%; border-radius:4px 4px 0 0; background:{{ $i == 5 ? '#059669' : '#d1fae5' }}; height:{{ max(6, round($m['total']/$maxMonth*72)) }}px; transition:opacity 0.15s;" onmouseover="this.style.opacity='0.75'" onmouseout="this.style.opacity='1'"></div>
                    <div style="font-size:11px; color:#6b7280; white-space:nowrap;">{{ $m['label'] }}</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Gasto por vendedor --}}
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

        {{-- Gasto por fornecedor --}}
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

    {{-- Gráficos (mobile — carrossel) --}}
    <div class="adm-charts-carousel">
        <div class="adm-carousel-track">
            <div class="adm-carousel-slides" id="adm-slides">

                {{-- Slide 1: Gasto mensal --}}
                <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px;">
                    <div style="font-size:14px; font-weight:700; color:#374151; margin-bottom:16px;">
                        Gasto mensal <span style="font-size:12px; font-weight:400; color:#9ca3af;">aprovadas</span>
                    </div>
                    <div style="display:flex; align-items:flex-end; gap:8px; height:100px;">
                        @foreach($monthlySpending as $i => $m)
                        <div onclick="openMonthModal('{{ $m['year'] }}','{{ $m['month'] }}','{{ $m['label'] }}')"
                             style="flex:1; display:flex; flex-direction:column; align-items:center; gap:4px; cursor:pointer;"
                             title="Ver detalhes de {{ $m['label'] }}">
                            <div style="font-size:10px; color:#9ca3af; white-space:nowrap;">
                                @if($m['total'] > 0) R$ {{ number_format($m['total']/1000, 1, ',', '.') }}k @endif
                            </div>
                            <div style="width:100%; border-radius:4px 4px 0 0; background:{{ $i == 5 ? '#059669' : '#d1fae5' }}; height:{{ max(6, round($m['total']/$maxMonth*72)) }}px;"></div>
                            <div style="font-size:11px; color:#6b7280; white-space:nowrap;">{{ $m['label'] }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Slide 2: Por vendedor --}}
                <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px;">
                    <div style="font-size:14px; font-weight:700; color:#374151; margin-bottom:12px;">
                        Maiores gastos <span style="font-size:12px; font-weight:400; color:#9ca3af;">por vendedor</span>
                    </div>
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

                {{-- Slide 3: Por fornecedor --}}
                <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px;">
                    <div style="font-size:14px; font-weight:700; color:#374151; margin-bottom:12px;">
                        Maiores gastos <span style="font-size:12px; font-weight:400; color:#9ca3af;">por fornecedor</span>
                    </div>
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
        </div>
        <div class="adm-carousel-dots">
            <span class="active" onclick="admGoTo(0)"></span>
            <span onclick="admGoTo(1)"></span>
            <span onclick="admGoTo(2)"></span>
        </div>
    </div>

    <script>
    (function () {
        var current = 0;
        var total = 3;
        var slides = document.getElementById('adm-slides');
        var dots = document.querySelectorAll('.adm-carousel-dots span');
        var timer;

        function admGoTo(index) {
            current = index;
            slides.style.transform = 'translateX(-' + (index * 100) + '%)';
            dots.forEach(function (d, i) { d.classList.toggle('active', i === index); });
            clearInterval(timer);
            timer = setInterval(next, 4000);
        }

        function next() { admGoTo((current + 1) % total); }

        window.admGoTo = admGoTo;
        timer = setInterval(next, 4000);
    })();
    </script>

    {{-- Filtros --}}
    <div class="adm-filters" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
        <form method="GET" action="{{ route('admin.index') }}" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:12px; align-items:end;">
            <div>
                <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:5px;">Vendedor</label>
                <input type="text" name="requester_name" value="{{ request('requester_name') }}" placeholder="Nome..."
                       style="width:100%; border:1px solid #d1d5db; border-radius:7px; padding:8px 12px; font-size:13px; box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:5px;">Produto</label>
                <input type="text" name="product_name" value="{{ request('product_name') }}" placeholder="Nome do produto"
                       style="width:100%; border:1px solid #d1d5db; border-radius:7px; padding:8px 12px; font-size:13px; box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:5px;">Status</label>
                <select name="status" style="width:100%; border:1px solid #d1d5db; border-radius:7px; padding:8px 12px; font-size:13px; box-sizing:border-box;">
                    <option value="">Todos</option>
                    <option value="pendente"  {{ request('status')=='pendente'  ? 'selected' : '' }}>Pendente</option>
                    <option value="aprovado"  {{ request('status')=='aprovado'  ? 'selected' : '' }}>Aprovado</option>
                    <option value="rejeitado" {{ request('status')=='rejeitado' ? 'selected' : '' }}>Rejeitado</option>
                </select>
            </div>
            <div>
                <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:5px;">Data inicial</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       style="width:100%; border:1px solid #d1d5db; border-radius:7px; padding:8px 12px; font-size:13px; box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:5px;">Data final</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       style="width:100%; border:1px solid #d1d5db; border-radius:7px; padding:8px 12px; font-size:13px; box-sizing:border-box;">
            </div>
            <div style="display:flex; gap:8px;">
                <button type="submit" style="flex:1; background:#05018D; color:#fff; padding:9px; border:none; border-radius:7px; font-size:13px; font-weight:600; cursor:pointer;">Filtrar</button>
                <a href="{{ route('admin.index') }}" style="flex:1; background:#f3f4f6; color:#374151; padding:9px; border-radius:7px; font-size:13px; font-weight:500; text-decoration:none; text-align:center; border:1px solid #e5e7eb;">Limpar</a>
            </div>
        </form>
    </div>

    {{-- Sugestões dos fornecedores já usados (o navegador só sugere; o campo continua livre) --}}
    <datalist id="supplier-options">
        @foreach($supplierList as $s)
            <option value="{{ $s }}">
        @endforeach
    </datalist>

    @if(session('modal_aberto') && $errors->any())
        {{-- Salvar falhou: reabre o modal do item para mostrar o erro (versão mobile em telas pequenas). --}}
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var id = @js(session('modal_aberto'));
                var modal = document.getElementById((window.innerWidth <= 768 ? 'modal-m-' : 'modal-') + id)
                    || document.getElementById('modal-' + id);
                if (modal) modal.style.display = 'flex';
            });
        </script>
    @endif

    @include('admin._rascunho-janela')

    {{-- Tabela (desktop) --}}
    {{-- Uma linha por requisição; ao abrir, cada item vira um cartão (<x-item-requisicao>). --}}
    <div class="adm-desktop-table" style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fafc; border-bottom:1px solid #e5e7eb;">
                        @php $thAdm = 'padding:12px 16px; color:#6b7280; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; white-space:nowrap;'; @endphp
                        <th style="{{ $thAdm }} text-align:left;">Nº</th>
                        <th style="{{ $thAdm }} text-align:left;">Vendedor</th>
                        <th style="{{ $thAdm }} text-align:left;">Itens</th>
                        <th style="{{ $thAdm }} text-align:right;">Total</th>
                        <th style="{{ $thAdm }} text-align:left;">Etapa</th>
                        <th style="{{ $thAdm }} text-align:left;">Coleta</th>
                        <th style="{{ $thAdm }} text-align:left;">Data</th>
                        <th style="{{ $thAdm }} text-align:left;">Status</th>
                        <th style="{{ $thAdm }} text-align:right;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $grupo)
                        @php
                            $primeiroAdm = $grupo->first();
                            $chaveAdm = $primeiroAdm->grupo_id;
                            $statusUnicosAdm = $grupo->pluck('status')->unique();
                            $statusChaveAdm = $statusUnicosAdm->count() === 1 ? $statusUnicosAdm->first() : 'parcial';
                            $produtosResumoAdm = $grupo->pluck('product_name')->filter()->implode(', ');
                            if (mb_strlen($produtosResumoAdm) > 80) {
                                $produtosResumoAdm = mb_substr($produtosResumoAdm, 0, 80) . '…';
                            }
                            $totalGrupoAdm = (float) $grupo->sum('valor');
                            $grupoUrgenteAdm = $grupo->contains('urgency', 'alta');
                            $grupoAtrasadoAdm = $grupo->contains('status_coleta', 'atraso');
                            $grupoTemAprovadoAdm = $grupo->contains('status', 'aprovado');
                            $tdAdm = 'padding:12px 16px; font-size:14px; color:#374151; vertical-align:middle;';
                        @endphp
                        <tr class="grupo-cabecalho" style="border-bottom:1px solid #eef0f3; cursor:pointer;" onmouseover="this.style.background='#f7f8fa'" onmouseout="this.style.background='transparent'" onclick="toggleGrupoRequisicao('{{ $chaveAdm }}')">
                            <td style="{{ $tdAdm }} font-weight:700; color:#111827; white-space:nowrap;">#{{ $primeiroAdm->id }}</td>
                            <td style="{{ $tdAdm }}">{{ $primeiroAdm->requester_name ?? 'Não informado' }}</td>
                            <td style="{{ $tdAdm }} max-width:420px;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <span style="font-weight:600; color:#111827; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $produtosResumoAdm }}</span>
                                    @if($grupoUrgenteAdm)
                                        <span style="flex:none; color:#b8301a; border:1px solid #b8301a; padding:0 8px; border-radius:9999px; font-size:11px; font-weight:700;">Urgente</span>
                                    @endif
                                </div>
                                <div style="font-size:12px; color:#6b7280; margin-top:2px;">{{ $grupo->count() }} {{ $grupo->count() > 1 ? 'itens' : 'item' }}</div>
                            </td>
                            <td style="{{ $tdAdm }} text-align:right; font-weight:600; color:#111827; white-space:nowrap;">{{ $totalGrupoAdm > 0 ? 'R$ ' . number_format($totalGrupoAdm, 2, ',', '.') : '—' }}</td>
                            <td style="{{ $tdAdm }}"><x-trilha-etapas :itens="$grupo" /></td>
                            <td style="{{ $tdAdm }} font-size:13px; white-space:nowrap;">
                                @if($grupoAtrasadoAdm)
                                    <strong style="color:#b8301a;">Atrasada</strong>
                                @elseif($grupoTemAprovadoAdm)
                                    <span style="color:#6b7280;">No prazo</span>
                                @else
                                    <span style="color:#9ca3af;">—</span>
                                @endif
                            </td>
                            <td style="{{ $tdAdm }} font-size:13px; white-space:nowrap;">
                                {{ $primeiroAdm->created_at->timezone('America/Sao_Paulo')->format('d/m/Y') }}
                                <span style="display:block; font-size:12px; color:#6b7280;">{{ $primeiroAdm->created_at->timezone('America/Sao_Paulo')->format('H:i') }}</span>
                            </td>
                            <td style="{{ $tdAdm }}"><x-status-requisicao :status="$statusChaveAdm" /></td>
                            <td style="{{ $tdAdm }} text-align:right;">
                                <button type="button" onclick="event.stopPropagation(); toggleGrupoRequisicao('{{ $chaveAdm }}')"
                                        style="border:1px solid #d1d5db; background:#fff; color:#374151; padding:7px 16px; border-radius:9999px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap;">
                                    <span id="seta-grupo-{{ $chaveAdm }}">Ver itens</span>
                                </button>
                            </td>
                        </tr>
                    @foreach($grupo as $req)
                        <tr class="grupo-item-{{ $chaveAdm }}" style="display:none; background:#f7f8fa;">
                            <td colspan="9" style="padding:{{ $loop->first ? '14px' : '0' }} 20px {{ $loop->last ? '18px' : '10px' }} 20px; {{ $loop->last ? 'border-bottom:1px solid #e5e7eb;' : '' }}">
                                <x-item-requisicao :req="$req">
                                    <a href="{{ route('admin.requests.export', $req) }}" target="_blank"
                                       style="background:#fff; color:#374151; border:1px solid #d1d5db; border-radius:9999px; padding:7px 16px; font-size:12.5px; font-weight:600; text-decoration:none; white-space:nowrap;">
                                        Exportar
                                    </a>
                                    <button type="button" onclick="document.getElementById('modal-{{ $req->id }}').style.display='flex'"
                                            style="background:#05018D; color:#fff; border:1px solid #05018D; border-radius:9999px; padding:7px 18px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap;">
                                        Atualizar
                                    </button>

                                    <x-slot:notas>
                                        @if(filled($req->reason) || filled($req->justification) || filled($req->observacao_conferencia))
                                            <x-obs-vendedor :item="$req" margem="8px" />
                                            <x-obs-divergencia :item="$req" margem="0" />
                                        @else
                                            <div style="color:#6b7280;">Nenhuma nota.</div>
                                        @endif
                                    </x-slot:notas>
                                </x-item-requisicao>
                            </td>
                        </tr>

                        {{-- Modal de Atualização de Requisição (Desktop) --}}
                        <div id="modal-{{ $req->id }}" data-quantity="{{ $req->quantity }}"
                             style="display:none; position:fixed; inset:0; background:rgba(15, 23, 42, 0.6); backdrop-filter:blur(3px); z-index:1000; align-items:center; justify-content:center; padding:16px;">

                            <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; width:100%; max-width:1040px; max-height:94vh; display:flex; flex-direction:column; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); font-family:inherit; overflow:hidden;">

                                {{-- Cabeçalho do Modal --}}
                                <div style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:flex-start; background:#f8fafc;">
                                    <div>
                                        <h3 style="margin:0; font-family:'Playfair Display', Georgia, serif; font-size:22px; font-weight:400; color:#0f172a; letter-spacing:0.01em;">Atualizar Requisição</h3>
                                        <div style="margin-top:4px; font-size:13px; color:#64748b; display:flex; align-items:center; gap:8px;">
                                            <span style="font-weight:600; color:#1e293b;">{{ $req->product_name }}</span>
                                            <span>·</span>
                                            <span>Solicitante: <strong>{{ $req->requester_name }}</strong></span>
                                            <span>·</span>
                                            <span>Qtd: <strong>{{ $req->quantity }}</strong></span>
                                        </div>
                                    </div>
                                    <button type="button" onclick="document.getElementById('modal-{{ $req->id }}').style.display='none'"
                                            style="border:none; background:transparent; color:#94a3b8; font-size:18px; cursor:pointer; padding:4px 8px; border-radius:4px; line-height:1;">
                                        ✕
                                    </button>
                                </div>

                                {{-- Corpo do Formulário --}}
                                <form method="POST" action="{{ route('admin.requests.update', $req) }}" enctype="multipart/form-data" data-rascunho="{{ $req->id }}" data-versao="{{ $req->updated_at?->timestamp }}" style="display:flex; flex-direction:column; flex:1; min-height:0; overflow:hidden; margin:0;">
                                    @csrf
                                    @method('PATCH')

                                    <div style="padding:22px 24px; display:grid; grid-template-columns:1.4fr 1fr; gap:28px; flex:1; min-height:0; overflow-y:auto;">

                                        {{-- Coluna 1: Dados da Compra --}}
                                        <div style="display:grid; grid-template-columns:1fr 1fr; column-gap:12px; align-content:start;">
                                            <div style="grid-column:1 / -1; font-size:11.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:14px; padding-bottom:6px; border-bottom:1px solid #f1f5f9;">
                                                Dados da Compra
                                            </div>

                                            <div style="margin-bottom:14px;">
                                                <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Status do Pedido</label>
                                                @php $corStatusModal = ['pendente' => '#f4b728', 'aprovado' => '#17794a', 'rejeitado' => '#b8301a'][$req->status] ?? '#cbd5e1'; @endphp
                                                <select name="status" onchange="this.style.borderLeftColor = ({pendente:'#f4b728', aprovado:'#17794a', rejeitado:'#b8301a'})[this.value] || '#cbd5e1'"
                                                        style="width:100%; border:1px solid #cbd5e1; border-left:6px solid {{ $corStatusModal }}; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; background-color:#fff; outline:none;">
                                                    <option value="pendente"  {{ $req->status=='pendente'  ? 'selected' : '' }}>Pendente de Aprovação</option>
                                                    <option value="aprovado"  {{ $req->status=='aprovado'  ? 'selected' : '' }}>Aprovado para Compra</option>
                                                    <option value="rejeitado" {{ $req->status=='rejeitado' ? 'selected' : '' }}>Rejeitado</option>
                                                </select>
                                            </div>

                                            <div style="margin-bottom:14px;">
                                                <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Fornecedor <span style="font-weight:400; color:#94a3b8;">(onde foi comprado)</span></label>
                                                <input type="text" name="supplier" value="{{ $req->supplier }}" placeholder="Ex: Bomvink, GPJ..." list="supplier-options"
                                                       style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box;">
                                            </div>

                                            @include('admin._empresa-compra', ['item' => $req, 'modo' => 'janela'])

                                            <div style="margin-bottom:14px;">
                                                <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Cód. no Fornecedor <span style="font-weight:400; color:#94a3b8;">(opcional)</span></label>
                                                <input type="text" name="codigo_fornecedor" value="{{ $req->codigo_fornecedor }}" placeholder="Ex: FORN-123"
                                                       style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box;">
                                            </div>

                                            <div style="grid-column:1 / -1; display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:14px;">
                                                <div>
                                                    <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Preço Unit. (R$)</label>
                                                    <input type="text" inputmode="decimal" name="preco_unitario" value="{{ $req->preco_unitario !== null ? number_format($req->preco_unitario, 2, ',', '.') : '' }}" placeholder="0,00" class="valor-brl preco-unitario-input"
                                                           style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box;">
                                                </div>
                                                <div>
                                                    <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Preço Caixa (R$)</label>
                                                    <input type="text" inputmode="decimal" name="preco_caixa" value="{{ $req->preco_caixa !== null ? number_format($req->preco_caixa, 2, ',', '.') : '' }}" placeholder="0,00" class="valor-brl preco-caixa-input"
                                                           style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box;">
                                                </div>
                                            <div>
                                                <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Total Calculado (R$)</label>
                                                <input type="text" inputmode="decimal" name="valor" value="{{ $req->valor !== null ? number_format($req->valor, 2, ',', '.') : '' }}" placeholder="0,00" class="valor-brl total-auto-display"
                                                       style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:14px; font-weight:700; color:#059669; background:#f0fdf4; outline:none; box-sizing:border-box;">
                                            </div>
                                            </div>

                                            

                                            <div style="grid-column:1 / -1; display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
                                                <div>
                                                    <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Data da Compra</label>
                                                    <input type="date" name="data_compra" value="{{ $req->data_compra?->format('Y-m-d') }}"
                                                           style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:7px 10px; font-size:13px; color:#0f172a; outline:none; box-sizing:border-box;">
                                                </div>
                                                <div>
                                                    <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Data Coleta</label>
                                                    <div style="padding:7px 10px; font-size:13px; color:#64748b; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px;">
                                                        {{ $req->data_coleta?->format('d/m/Y') ?? 'Aguardando' }}
                                                    </div>
                                                </div>
                                            </div>

                                            <div style="grid-column:1 / -1;">@include('admin._condicao-pagamento', ['item' => $req, 'sufixo' => 'req-' . $req->id . '-d', 'modo' => 'modal', 'obrigatorio' => false])</div>
                                        </div>

                                        {{-- Coluna 2: Anexos e Contexto do Pedido --}}
                                        <div>
                                            <div style="font-size:11.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:14px; padding-bottom:6px; border-bottom:1px solid #f1f5f9;">
                                                Anexos e Observações
                                            </div>

                                            <div style="margin-bottom:14px;">
                                                <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Pedido de Compra <span style="font-weight:400; color:#94a3b8;">(PDF ou imagem)</span></label>
                                                @if($req->pedido_compra_path)
                                                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px; background:#f1f5f9; padding:6px 10px; border-radius:6px; font-size:12.5px;">
                                                        <a href="{{ route('admin.compras.pedido', $req) }}" target="_blank" style="color:#2563eb; font-weight:600; text-decoration:none;">📎 {{ $req->pedido_compra_nome }}</a>
                                                        <button type="submit" form="rm-pedido-{{ $req->id }}" onclick="return confirm('Remover o pedido de compra anexado?')" style="background:none; border:none; color:#dc2626; font-size:11.5px; text-decoration:underline; cursor:pointer; margin-left:auto;">Remover</button>
                                                    </div>
                                                @endif
                                                <input type="file" name="pedido_compra" accept=".pdf,.jpg,.jpeg,.png,.webp" style="width:100%; font-size:12px; color:#475569;">
                                            </div>

                                            <div style="margin-bottom:14px;">
                                                <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Anexo do Vendedor</label>
                                                @if($req->anexo_path)
                                                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px; background:#f1f5f9; padding:6px 10px; border-radius:6px; font-size:12.5px;">
                                                        <a href="{{ route('requests.anexo', $req) }}" target="_blank" style="color:#2563eb; font-weight:600; text-decoration:none;">📎 {{ $req->anexo_nome }}</a>
                                                        <button type="submit" form="rm-anexo-{{ $req->id }}" onclick="return confirm('Remover o anexo do vendedor?')" style="background:none; border:none; color:#dc2626; font-size:11.5px; text-decoration:underline; cursor:pointer; margin-left:auto;">Remover</button>
                                                    </div>
                                                @endif
                                                <input type="file" name="anexo" accept=".pdf,.jpg,.jpeg,.png,.webp" style="width:100%; font-size:12px; color:#475569;">
                                            </div>

                                            <div style="margin-bottom:14px;">
                                                <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Nota / Despacho do Comprador</label>
                                                <textarea name="admin_note" rows="3" placeholder="Instruções para o vendedor ou conferente..."
                                                          style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13px; color:#0f172a; resize:vertical; font-family:inherit; outline:none; box-sizing:border-box;">{{ $req->admin_note }}</textarea>
                                            </div>

                                            {{-- Histórico de notas do vendedor e conferência integrado --}}
                                            <div style="margin-top:12px;">
                                                <x-obs-todas :item="$req" margem="0" />
                                            </div>

                                        </div>

                                    </div>

                                    {{-- Rodapé de Ações --}}
                                    <div style="padding:16px 24px; border-top:1px solid #e2e8f0; background:#f8fafc; display:flex; justify-content:flex-end; gap:10px;">
                                        <button type="button" onclick="document.getElementById('modal-{{ $req->id }}').style.display='none'"
                                                style="padding:8px 18px; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; color:#475569; font-size:13px; font-weight:600; cursor:pointer;">
                                            Cancelar
                                        </button>
                                        <button type="submit"
                                                style="padding:8px 22px; border-radius:6px; background:#2563eb; color:#ffffff; font-size:13px; font-weight:600; border:none; cursor:pointer; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                                            Salvar Alterações
                                        </button>
                                    </div>

                                </form>

                            </div>
                        </div>
                    @endforeach
                    @empty
                        <tr>
                            <td colspan="9" style="padding:48px 16px; text-align:center; color:#9ca3af; font-size:15px;">
                                Nenhuma requisição encontrada
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requests->hasPages())
            <div style="padding:16px 20px; border-top:1px solid #f3f4f6; display:flex; justify-content:center;">
                {{ $requests->links() }}
            </div>
        @endif
    </div>

    {{-- CARDS (mobile) --}}
    <div class="adm-mobile-cards">
        @forelse($requests as $grupo)
            @php
                $primeiroAdmM = $grupo->first();
                $chaveAdmM = $primeiroAdmM->grupo_id;
                $statusUnicosAdmM = $grupo->pluck('status')->unique();
                if ($statusUnicosAdmM->count() === 1) {
                    $statusChaveAdmM = $statusUnicosAdmM->first();
                    $rotuloGrupoAdmM = ['aprovado' => 'Aprovado', 'rejeitado' => 'Rejeitado', 'pendente' => 'Pendente'][$statusChaveAdmM] ?? ucfirst($statusChaveAdmM);
                } else {
                    $statusChaveAdmM = 'parcial';
                    $rotuloGrupoAdmM = 'Parcial';
                }
                $corsGrupoAdmM = [
                    'pendente'  => ['barra' => '#e5e7eb', 'bg' => '#f4b728', 'texto' => '#2b1d00'],
                    'aprovado'  => ['barra' => '#e5e7eb', 'bg' => '#17794a', 'texto' => '#ffffff'],
                    'rejeitado' => ['barra' => '#e5e7eb', 'bg' => '#b8301a', 'texto' => '#ffffff'],
                    'parcial'   => ['barra' => '#e5e7eb', 'bg' => '#475569', 'texto' => '#ffffff'],
                ][$statusChaveAdmM];
                $produtosResumoAdmM = $grupo->pluck('product_name')->filter()->implode(', ');
                if (mb_strlen($produtosResumoAdmM) > 60) {
                    $produtosResumoAdmM = mb_substr($produtosResumoAdmM, 0, 60) . '…';
                }
            @endphp
            <div style="background:#fff; border:0.5px solid #e5e7eb; border-radius:10px; margin-bottom:10px; cursor:pointer; overflow:hidden;"
                 onclick="toggleGrupoRequisicao('{{ $chaveAdmM }}')">
                <div style="display:flex; align-items:stretch; gap:10px;">
                    <div style="width:4px; background:{{ $corsGrupoAdmM['barra'] }};"></div>
                    <div style="flex:1; min-width:0; padding:12px 12px 12px 0;">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                            <div style="font-size:14px; font-weight:700; color:#111827;">Requisição #{{ $primeiroAdmM->id }}</div>
                            <span style="background:{{ $corsGrupoAdmM['bg'] }}; color:{{ $corsGrupoAdmM['texto'] }}; padding:3px 10px; border-radius:20px; font-size:11.5px; font-weight:700; white-space:nowrap;">{{ $rotuloGrupoAdmM }}</span>
                        </div>
                        <div style="font-size:12.5px; color:#9ca3af; margin-top:2px;">{{ $primeiroAdmM->requester_name ?? 'Não informado' }}</div>
                        <div style="font-size:12px; color:#6b7280; margin-top:4px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            {{ $grupo->count() }} {{ $grupo->count() > 1 ? 'itens' : 'item' }} · {{ $produtosResumoAdmM }}
                        </div>
                        <button type="button" class="m-botao" onclick="event.stopPropagation(); toggleGrupoRequisicao('{{ $chaveAdmM }}')"
                                style="margin-top:8px; border:1px solid #d1d5db; background:#fff; color:#374151; padding:5px 12px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer;">
                            <span id="seta-grupo-{{ $chaveAdmM }}">Ver itens</span>
                        </button>
                    </div>
                </div>
            </div>
            @foreach($grupo as $req)
            <div class="grupo-item-{{ $chaveAdmM }}" style="display:none; background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin:-6px 0 12px 12px; box-shadow:0 1px 3px rgba(0,0,0,0.06);">

                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                    <div>
                        <div style="font-size:15px; font-weight:700; color:#05018D;">{{ $req->product_name }}</div>
                        @if($req->product_code)
                            <div style="font-size:12px; color:#9ca3af;">Cód: {{ $req->product_code }}</div>
                        @endif
                        @if($req->product_url)
                            <a href="{{ $req->product_url }}" target="_blank" style="display:block; font-size:11px; color:#05018D; text-decoration:underline; margin-top:2px;">Ver link</a>
                        @endif
                        @if($req->anexo_path)
                            <a href="{{ route('requests.anexo', $req) }}" target="_blank" style="display:block; font-size:11px; color:#05018D; text-decoration:underline; margin-top:2px;">📎 {{ $req->anexo_nome }}</a>
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

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:13px; margin-bottom:12px;">
                    <div>
                        <span style="color:#9ca3af;">Vendedor</span>
                        <div style="font-weight:600; color:#374151;">{{ $req->requester_name ?? '—' }}</div>
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Fornecedor</span>
                        <div style="font-weight:600; color:#374151;">{{ $req->supplier ?? '—' }}</div>
                        @if($req->empresa)<div style="font-size:11px; color:#6b7280; margin-top:2px;">Empresa: {{ $req->empresa }}</div>@endif
                        @if($req->temDadosDaCompra())
                            <div style="font-size:11px; color:#6b7280; margin-top:3px; line-height:1.5;">
                                Unitário: R$ {{ number_format($req->preco_unitario, 2, ',', '.') }}
                                @if($req->preco_caixa)
                                    <br>Caixa: R$ {{ number_format($req->preco_caixa, 2, ',', '.') }}
                                @endif
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
                                    <br><a href="{{ route('admin.compras.pedido', $req) }}" target="_blank" style="color:#05018D; text-decoration:underline;">📎 Pedido de compra</a>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Quantidade</span>
                        <div style="font-weight:700; font-size:15px; color:#374151;">{{ $req->quantity }}</div>
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Data</span>
                        <div style="font-weight:600; color:#374151;">{{ $req->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</div>
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Valor</span>
                        <div style="font-weight:700; color:#059669;">{{ $req->valor ? 'R$ '.number_format($req->valor, 2, ',', '.') : '—' }}</div>
                    </div>
                </div>

                <x-obs-vendedor :item="$req" margem="10px" />
                <x-obs-divergencia :item="$req" margem="10px" />

                <div class="m-coluna" style="display:flex; align-items:center; justify-content:space-between;">
                    @if($req->urgency=='alta')
                        <span style="background:#fff; color:#b8301a; border:1px solid #b8301a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Alta</span>
                    @elseif($req->urgency=='media')
                        <span style="background:#f3f4f6; color:#374151; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Média</span>
                    @else
                        <span style="background:#f3f4f6; color:#374151; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Baixa</span>
                    @endif
                    <div class="m-card-acao" style="display:flex; gap:6px;">
                        <a href="{{ route('admin.requests.export', $req) }}" target="_blank"
                           style="background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; border-radius:7px; padding:8px 12px; font-size:13px; font-weight:600; text-decoration:none;">
                            Exportar
                        </a>
                        <button onclick="document.getElementById('modal-m-{{ $req->id }}').style.display='flex'"
                                style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:8px 18px; font-size:13px; font-weight:600; cursor:pointer;">
                            Atualizar
                        </button>
                    </div>
                </div>

            </div>

            {{-- Modal mobile --}}
            <div id="modal-m-{{ $req->id }}" data-quantity="{{ $req->quantity }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                <div class="m-modal-caixa" style="background:#fff; border-radius:12px; padding:28px; width:100%; max-width:440px; margin:16px; max-height:90vh; overflow-y:auto;">
                    <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Atualizar Requisição</h3>
                    <p style="margin:0 0 20px; font-size:13px; color:#9ca3af;">{{ $req->product_name }} — {{ $req->requester_name }}</p>
                    <form method="POST" action="{{ route('admin.requests.update', $req) }}" enctype="multipart/form-data" data-rascunho="{{ $req->id }}" data-versao="{{ $req->updated_at?->timestamp }}">
                        @csrf
                        @method('PATCH')
                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Status</label>
                            <select name="status" style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                <option value="pendente"  {{ $req->status=='pendente'  ? 'selected' : '' }}>🟡 Pendente</option>
                                <option value="aprovado"  {{ $req->status=='aprovado'  ? 'selected' : '' }}>🟢 Aprovado</option>
                                <option value="rejeitado" {{ $req->status=='rejeitado' ? 'selected' : '' }}>🔴 Rejeitado</option>
                            </select>
                        </div>
                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Fornecedor <span style="color:#9ca3af; font-weight:400; text-transform:none;">(onde foi comprado)</span></label>
                            <input type="text" name="supplier" value="{{ $req->supplier }}" placeholder="Ex: Bomvink, GPJ..." list="supplier-options"
                                   style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                        </div>
                        @include('admin._empresa-compra', ['item' => $req, 'modo' => 'modal'])
                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Cód. no fornecedor <span style="color:#9ca3af; font-weight:400; text-transform:none;">(opcional)</span></label>
                            <input type="text" name="codigo_fornecedor" value="{{ $req->codigo_fornecedor }}" placeholder="Ex: FORN-123"
                                   style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:16px;">
                            <div>
                                <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Preço unitário (R$)</label>
                                <input type="text" inputmode="decimal" name="preco_unitario" value="{{ $req->preco_unitario !== null ? number_format($req->preco_unitario, 2, ',', '.') : '' }}" placeholder="0,00" class="valor-brl preco-unitario-input"
                                       style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                            </div>
                            <div>
                                <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Preço da caixa (R$) <span style="color:#9ca3af; font-weight:400; text-transform:none;">(opcional)</span></label>
                                <input type="text" inputmode="decimal" name="preco_caixa" value="{{ $req->preco_caixa !== null ? number_format($req->preco_caixa, 2, ',', '.') : '' }}" placeholder="0,00" class="valor-brl preco-caixa-input"
                                       style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                            </div>
                        </div>
                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Total (R$)</label>
                            <input type="text" inputmode="decimal" name="valor" value="{{ $req->valor !== null ? number_format($req->valor, 2, ',', '.') : '' }}" placeholder="0,00" class="valor-brl total-auto-display"
                                   style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; font-weight:700; color:#059669; box-sizing:border-box;">
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:16px;">
                            <div>
                                <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Data da compra</label>
                                <input type="date" name="data_compra" value="{{ $req->data_compra?->format('Y-m-d') }}"
                                       style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:9px 10px; font-size:13.5px; box-sizing:border-box;">
                            </div>
                            <div>
                                <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Data da coleta <span style="color:#9ca3af; font-weight:400; text-transform:none;">(preenchido por quem coleta)</span></label>
                                <div style="padding:9px 10px; font-size:13.5px; color:#374151;">{{ $req->data_coleta?->format('d/m/Y') ?? '—' }}</div>
                            </div>
                        </div>
                        @include('admin._condicao-pagamento', ['item' => $req, 'sufixo' => 'req-' . $req->id . '-m', 'modo' => 'modal', 'obrigatorio' => false])

                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Pedido de compra <span style="color:#9ca3af; font-weight:400; text-transform:none;">(PDF ou imagem, opcional)</span></label>
                            @if($req->pedido_compra_path)
                                <div style="margin-bottom:6px;"><a href="{{ route('admin.compras.pedido', $req) }}" target="_blank" style="color:#05018D; font-weight:600; font-size:13px;">📎 {{ $req->pedido_compra_nome }}</a> <span style="color:#9ca3af; font-size:12px;">(envie outro pra substituir)</span> <button type="submit" form="rm-pedido-{{ $req->id }}" onclick="return confirm('Remover o pedido de compra anexado?')" style="background:none; border:none; color:#dc2626; font-size:12px; text-decoration:underline; cursor:pointer; padding:0; margin-left:6px;">Remover pedido de compra</button></div>
                            @endif
                            <input type="file" name="pedido_compra" accept=".pdf,.jpg,.jpeg,.png,.webp"
                                   style="width:100%; font-size:13px;">
                        </div>
                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Anexo do vendedor <span style="color:#9ca3af; font-weight:400; text-transform:none;">(orçamento, print... caso ele tenha esquecido)</span></label>
                            @if($req->anexo_path)
                                <div style="margin-bottom:6px;"><a href="{{ route('requests.anexo', $req) }}" target="_blank" style="color:#05018D; font-weight:600; font-size:13px;">📎 {{ $req->anexo_nome }}</a> <span style="color:#9ca3af; font-size:12px;">(envie outro pra substituir)</span> <button type="submit" form="rm-anexo-{{ $req->id }}" onclick="return confirm('Remover o anexo do vendedor?')" style="background:none; border:none; color:#dc2626; font-size:12px; text-decoration:underline; cursor:pointer; padding:0; margin-left:6px;">Remover anexo</button></div>
                            @endif
                            <input type="file" name="anexo" accept=".pdf,.jpg,.jpeg,.png,.webp"
                                   style="width:100%; font-size:13px;">
                        </div>
                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Observação <span style="color:#9ca3af; font-weight:400; text-transform:none;">(opcional)</span></label>
                            <textarea name="admin_note" rows="3" placeholder="Ex: Aprovado, aguardando entrega..."
                                      style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box; resize:vertical; font-family:inherit;">{{ $req->admin_note }}</textarea>
                        </div>

                        @if($req->obs)
                        <div style="margin-bottom:16px; padding:10px 12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#15803d; margin-bottom:5px; text-transform:uppercase;">Obs (Conferente)</label>
                            <div style="font-size:13px; color:#166534; line-height:1.5;">{{ $req->obs }}</div>
                        </div>
                        @endif

                        <x-obs-entrada :item="$req" margem="16px" />
                        <x-obs-vendedor :item="$req" margem="16px" />
                        <x-obs-divergencia :item="$req" margem="16px" />

                        <div class="m-modal-acoes" style="display:flex; gap:10px; justify-content:flex-end;">
                            <button type="button" onclick="document.getElementById('modal-m-{{ $req->id }}').style.display='none'"
                                    style="padding:9px 20px; border-radius:8px; border:1.5px solid #e5e7eb; background:#fff; color:#6b7280; font-size:14px; font-weight:600; cursor:pointer;">
                                Cancelar
                            </button>
                            <button type="submit"
                                    style="padding:9px 24px; border-radius:8px; background:#05018D; color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">
                                Salvar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endforeach
        @empty
            <div style="text-align:center; padding:48px 16px;">
                <p style="color:#6b7280; font-size:15px; margin:0;">Nenhuma requisição encontrada</p>
            </div>
        @endforelse
        @if($requests->hasPages())
            <div style="padding:16px 4px; display:flex; justify-content:center;">
                {{ $requests->links() }}
            </div>
        @endif
    </div>

</div>

{{-- Modal: requisições do mês --}}
<div id="month-modal" onclick="if(event.target===this)closeMonthModal()"
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#fff; border-radius:12px; width:100%; max-width:720px; max-height:85vh; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <div style="padding:20px 24px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
                <h3 id="month-modal-title" style="margin:0; font-size:18px; font-weight:700; color:#05018D;"></h3>
                <p id="month-modal-sub" style="margin:6px 0 0; font-size:13px; color:#6b7280;"></p>
            </div>
            <button onclick="closeMonthModal()" style="background:none; border:none; cursor:pointer; font-size:22px; color:#9ca3af; line-height:1; padding:2px 6px;">&times;</button>
        </div>
        <div id="month-modal-body" style="overflow-y:auto; padding:16px 24px; flex:1;"></div>
    </div>
</div>

<script>
(function () {
    function applyBRLMask(input) {
        input.addEventListener('input', function () {
            let digits = this.value.replace(/\D/g, '');
            if (digits === '') { this.value = ''; return; }
            let num = parseInt(digits, 10) / 100;
            this.value = num.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        });
    }

    function convertBRLBeforeSubmit(form) {
        form.addEventListener('submit', function () {
            form.querySelectorAll('.valor-brl').forEach(function (input) {
                if (input.value.trim() !== '') {
                    input.value = input.value.replace(/\./g, '').replace(',', '.');
                }
            });
        });
    }

    document.querySelectorAll('.valor-brl').forEach(applyBRLMask);
    document.querySelectorAll('form').forEach(convertBRLBeforeSubmit);

    // Total sugerido: quantidade do item × preço unitário (quadro do desktop e do celular).
    // Preenche sozinho quando o unitário muda, mas o campo continua editável: compra por caixa fechada,
    // desconto ou frete fazem o total real ser outro, e aí o comprador corrige à mão.
    document.querySelectorAll('[data-quantity]').forEach(function (quadro) {
        var qtd = parseFloat(quadro.getAttribute('data-quantity')) || 0;
        var unitario = quadro.querySelector('.preco-unitario-input');
        var total = quadro.querySelector('.total-auto-display');
        if (!unitario || !total) return;
        total.title = 'Sugerido: quantidade (' + qtd + ') × preço unitário. Pode corrigir se o total real for outro.';
        unitario.addEventListener('input', function () {
            var texto = unitario.value.replace(/\./g, '').replace(',', '.').trim();
            if (texto === '') { total.value = ''; return; }
            var preco = parseFloat(texto);
            if (isNaN(preco)) return;
            total.value = (Math.round(preco * qtd * 100) / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        });
    });
})();

function openMonthModal(year, month, label) {
    var modal = document.getElementById('month-modal');
    modal.style.display = 'flex';
    document.getElementById('month-modal-title').textContent = 'Aprovadas em ' + label;
    document.getElementById('month-modal-sub').textContent = 'Carregando...';
    document.getElementById('month-modal-body').innerHTML = '<p style="color:#6b7280;text-align:center;padding:40px 0;">Carregando...</p>';

    fetch('/admin/mensal/' + year + '/' + month)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            document.getElementById('month-modal-sub').textContent = data.count + ' requisição(ões) aprovada(s)  ·  Total: ' + data.total_fmt;
            if (data.count === 0) {
                document.getElementById('month-modal-body').innerHTML = '<p style="color:#9ca3af;text-align:center;padding:40px 0;">Nenhuma requisição aprovada neste mês.</p>';
                return;
            }
            var rows = data.requests.map(function(r) {
                return '<tr style="border-bottom:1px solid #f3f4f6;">'
                    + '<td style="padding:10px 8px;font-size:13px;font-weight:600;color:#374151;">' + r.requester_name + '</td>'
                    + '<td style="padding:10px 8px;font-size:13px;color:#374151;">' + r.product_name + '</td>'
                    + '<td style="padding:10px 8px;font-size:13px;color:#6b7280;">' + r.supplier + '</td>'
                    + '<td style="padding:10px 8px;font-size:13px;text-align:center;color:#374151;">' + r.quantity + '</td>'
                    + '<td style="padding:10px 8px;font-size:13px;font-weight:700;color:#059669;text-align:right;white-space:nowrap;">' + r.valor_fmt + '</td>'
                    + '</tr>';
            }).join('');
            document.getElementById('month-modal-body').innerHTML =
                '<table style="width:100%;border-collapse:collapse;">'
                + '<thead><tr style="background:#f9fafb;">'
                + '<th style="padding:8px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;text-align:left;">Vendedor</th>'
                + '<th style="padding:8px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;text-align:left;">Produto</th>'
                + '<th style="padding:8px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;text-align:left;">Fornecedor</th>'
                + '<th style="padding:8px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;text-align:center;">Qtd</th>'
                + '<th style="padding:8px;font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;text-align:right;">Valor</th>'
                + '</tr></thead>'
                + '<tbody>' + rows + '</tbody>'
                + '</table>';
        })
        .catch(function() {
            document.getElementById('month-modal-body').innerHTML = '<p style="color:#ef4444;text-align:center;padding:40px 0;">Erro ao carregar dados.</p>';
        });
}

function closeMonthModal() {
    document.getElementById('month-modal').style.display = 'none';
}

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


{{-- Formulários de remover anexo (fora das tabelas e dos quadros; os botões apontam para eles pelo atributo form) --}}
@foreach($requests as $grupo)
    @foreach($grupo as $req)
        @if($req->anexo_path)
            <form id="rm-anexo-{{ $req->id }}" method="POST" action="{{ route('admin.requests.anexo.remover', $req) }}" style="display:none;">@csrf @method('DELETE')</form>
        @endif
        @if($req->pedido_compra_path)
            <form id="rm-pedido-{{ $req->id }}" method="POST" action="{{ route('admin.compras.pedido.remover', $req) }}" style="display:none;">@csrf @method('DELETE')</form>
        @endif
    @endforeach
@endforeach

@endsection
