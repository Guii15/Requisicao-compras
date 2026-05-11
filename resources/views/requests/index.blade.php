@extends('layouts.app')

@section('fullcontent')

<style>
/* ── STATS ROW ─────────────────────────────────────────── */
.u-stats-row {
    display: grid; grid-template-columns: repeat(5,1fr);
    background: var(--border); gap: 1px;
    border-bottom: 0.5px solid var(--border);
}
.u-stat-item {
    padding: 40px 24px; text-align: center;
    background: var(--bg); transition: background 0.35s;
}
html.light-mode .u-stat-item { background: var(--bg2); }
.u-stat-number {
    font-family: 'Playfair Display', serif; font-size: 52px; font-weight: 900;
    color: var(--text); line-height: 1;
}
.u-stat-label { margin-top: 8px; font-size: 14px; color: var(--text2); }

/* ── MAIN SECTION ──────────────────────────────────────── */
.u-section { background: var(--bg2); padding: 64px 24px 80px; transition: background 0.35s; }
.u-inner { max-width: 1200px; margin: 0 auto; }

/* ── HEADER ────────────────────────────────────────────── */
.u-section-header { text-align: center; margin-bottom: 48px; }
.u-eyebrow {
    font-size: 12px; font-weight: 500; letter-spacing: 0.12em;
    text-transform: uppercase; color: var(--accent); margin-bottom: 14px;
}
.u-title {
    font-family: 'Playfair Display', serif; font-size: 42px; font-weight: 900;
    color: var(--text); line-height: 1.1; margin-bottom: 12px;
}
.u-subtitle { font-size: 16px; color: var(--text2); }

/* ── TABS ──────────────────────────────────────────────── */
.u-tabs {
    display: flex; gap: 4px; background: rgba(255,255,255,0.06);
    border-radius: 12px; padding: 4px; width: fit-content; margin: 0 auto 48px;
}
html.light-mode .u-tabs { background: rgba(0,0,0,0.06); }
.u-tab {
    padding: 9px 22px; border-radius: 9px; font-size: 14px; font-weight: 500;
    color: var(--text2); cursor: pointer; transition: all 0.25s;
    border: none; background: transparent; font-family: 'DM Sans', sans-serif;
}
.u-tab.active { background: var(--text); color: var(--bg); }
html.light-mode .u-tab.active { background: #1d1d1f; color: #fff; }
.u-panel { display: none; }
.u-panel.active { display: block; }

/* ── ANALYTICS GRID ────────────────────────────────────── */
.u-analytics-grid { display: grid; grid-template-columns: 1fr 320px; gap: 16px; }
.u-card {
    background: var(--bg-card); border: 0.5px solid var(--border);
    border-radius: 16px; transition: background 0.35s;
}
.u-card-title {
    font-size: 15px; font-weight: 500; color: var(--text);
    margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;
}
.u-card-tag { font-size: 12px; color: var(--text2); font-weight: 400; }

/* ── TOP ITEMS ─────────────────────────────────────────── */
.u-top-list { display: flex; flex-direction: column; gap: 12px; }
.u-top-item { display: flex; align-items: center; gap: 10px; }
.u-top-rank { width: 20px; font-size: 12px; color: var(--text2); font-weight: 500; }
.u-top-info { flex: 1; min-width: 0; }
.u-top-name {
    font-size: 13px; color: var(--text); font-weight: 500;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.u-top-count { font-size: 11px; color: var(--text2); }

/* ── STATUS BARS ───────────────────────────────────────── */
.u-bar-wrap { height: 4px; background: var(--border); border-radius: 2px; flex: 1; }
.u-bar { height: 100%; border-radius: 2px; }

/* ── MINI TABLE (recentes) ─────────────────────────────── */
.u-mini-table { width: 100%; border-collapse: collapse; }
.u-mini-table th {
    font-size: 11px; font-weight: 500; color: var(--text2); text-align: left;
    padding: 0 8px 12px; letter-spacing: 0.06em; text-transform: uppercase;
    border-bottom: 0.5px solid var(--border);
}
.u-mini-table td {
    font-size: 13px; color: var(--text); padding: 12px 8px;
    border-bottom: 0.5px solid var(--border); vertical-align: middle;
}
.u-mini-table tr:last-child td { border-bottom: none; }

/* ── INPUTS / BUTTONS ──────────────────────────────────── */
.u-input {
    background: var(--bg-card); border: 0.5px solid var(--border); border-radius: 8px;
    padding: 8px 14px; font-size: 13px; color: var(--text); font-family: inherit;
    outline: none; transition: border-color 0.2s;
}
.u-input:focus { border-color: var(--accent); }
.u-input::placeholder { color: var(--text3); }
.u-btn-primary {
    background: var(--accent); color: #fff; border: none; border-radius: 8px;
    padding: 8px 18px; font-size: 13px; font-weight: 500; cursor: pointer;
    font-family: inherit; white-space: nowrap;
}
.u-btn-ghost {
    background: transparent; color: var(--text2); border: 0.5px solid var(--border);
    border-radius: 8px; padding: 8px 18px; font-size: 13px; cursor: pointer;
    font-family: inherit; text-decoration: none; display: inline-block; white-space: nowrap;
}
.u-btn-new {
    display: inline-flex; align-items: center; gap: 8px;
    background: linear-gradient(135deg,#05018D,var(--accent)); color: #fff;
    padding: 10px 20px; border-radius: 10px; text-decoration: none;
    font-weight: 600; font-size: 14px; white-space: nowrap;
    box-shadow: 0 4px 14px rgba(0,113,227,0.3);
}

/* ── TABLE ─────────────────────────────────────────────── */
.u-table { width: 100%; border-collapse: collapse; }
.u-table th {
    font-size: 11px; font-weight: 500; color: var(--text2); text-align: left;
    padding: 10px 12px; letter-spacing: 0.06em; text-transform: uppercase;
    border-bottom: 0.5px solid var(--border); white-space: nowrap;
}
.u-table td {
    font-size: 13px; color: var(--text); padding: 14px 12px;
    border-bottom: 0.5px solid var(--border); vertical-align: middle;
}
.u-table tr:last-child td { border-bottom: none; }
.u-table tbody tr:hover td { background: rgba(0,0,0,0.03); }
html:not(.light-mode) .u-table tbody tr:hover td { background: rgba(255,255,255,0.03); }

/* ── PILLS ─────────────────────────────────────────────── */
.pill { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
.pill-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
.pill-aprovado  { background: var(--badge-aprovado-bg);  color: var(--badge-aprovado-text); }
.pill-rejeitado { background: var(--badge-rejeitado-bg); color: var(--badge-rejeitado-text); }
.pill-pendente  { background: var(--badge-pendente-bg);  color: var(--badge-pendente-text); }
.pill-alta      { background: var(--badge-alta-bg);      color: var(--badge-alta-text); }
.pill-media     { background: var(--badge-media-bg);     color: var(--badge-media-text); }
.pill-baixa     { background: var(--badge-baixa-bg);     color: var(--badge-baixa-text); }

/* ── RESPONSIVE ─────────────────────────────────────────── */
@media (max-width: 1100px) {
    .u-stats-row { grid-template-columns: repeat(3,1fr); }
}
@media (max-width: 768px) {
    .u-stats-row { grid-template-columns: repeat(2,1fr); }
    .u-stat-item { padding: 24px 16px; }
    .u-stat-number { font-size: 36px; }
    .u-title { font-size: 28px; }
    .u-section { padding: 40px 16px 60px; }
    .u-analytics-grid { grid-template-columns: 1fr; }
    .u-desktop { display: none !important; }
    .u-mobile { display: block !important; }
}
@media (min-width: 769px) {
    .u-desktop { display: block; }
    .u-mobile { display: none; }
}
</style>

{{-- Stats Row --}}
<div class="u-stats-row">
    <div class="u-stat-item">
        <div class="u-stat-number">{{ $stats['total'] }}</div>
        <div class="u-stat-label">Total de requisições</div>
    </div>
    <div class="u-stat-item">
        <div class="u-stat-number">{{ $stats['aprovado'] }}</div>
        <div class="u-stat-label">Aprovadas</div>
    </div>
    <div class="u-stat-item">
        <div class="u-stat-number">{{ $stats['pendente'] }}</div>
        <div class="u-stat-label">Aguardando aprovação</div>
    </div>
    <div class="u-stat-item">
        <div class="u-stat-number">{{ $stats['rejeitado'] }}</div>
        <div class="u-stat-label">Recusadas</div>
    </div>
    <div class="u-stat-item">
        <div class="u-stat-number" style="font-size:32px; line-height:1.15;">R$ {{ number_format($stats['total_gasto'] ?? 0, 2, ',', '.') }}</div>
        <div class="u-stat-label">Total Gasto</div>
    </div>
</div>

{{-- Main Section --}}
<div class="u-section">
    <div class="u-inner">

        {{-- Header --}}
        <div class="u-section-header">
            <div class="u-eyebrow">Área do Vendedor</div>
            <h1 class="u-title">Minhas Requisições</h1>
            <p class="u-subtitle">Acompanhe todas as suas solicitações de compra</p>
        </div>

        {{-- Tabs --}}
        <div class="u-tabs">
            <button class="u-tab active" onclick="uShowTab('geral',this)">Dashboard</button>
            <button class="u-tab" onclick="uShowTab('vendedores',this)">Vendedores</button>
            <button class="u-tab" onclick="uShowTab('requisicoes',this)">Minhas Requisições</button>
        </div>

        {{-- Flash --}}
        @if(session('success'))
            <div style="background:var(--success-bg); color:var(--success-text); border:1px solid currentColor; border-radius:10px; padding:12px 16px; margin-bottom:24px; display:flex; align-items:center; gap:10px; font-size:14px; font-weight:500;">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- ══ PANEL GERAL ══════════════════════════════════════ --}}
        <div class="u-panel active" id="u-panel-geral">
            <div class="u-analytics-grid">

                {{-- Left: últimas requisições --}}
                <div class="u-card" style="padding:24px;">
                    <div class="u-card-title">
                        Últimas requisições
                        <span class="u-card-tag">{{ $recentes->count() }} mais recentes</span>
                    </div>
                    @if($recentes->isEmpty())
                        <div style="padding:32px 0; text-align:center; color:var(--text2); font-size:13px;">
                            Nenhuma requisição ainda.<br>
                            <a href="{{ route('requests.create') }}" style="color:var(--accent); text-decoration:none; font-weight:500; margin-top:8px; display:inline-block;">Criar a primeira →</a>
                        </div>
                    @else
                        <table class="u-mini-table">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Urgência</th>
                                    <th>Status</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentes as $rec)
                                <tr>
                                    <td>
                                        <div style="font-weight:500;">{{ Str::limit($rec->product_name, 28) }}</div>
                                        @if($rec->supplier)
                                            <div style="font-size:11px; color:var(--text2); margin-top:2px;">{{ $rec->supplier }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="pill pill-{{ $rec->urgency }}">
                                            <span class="pill-dot"></span>
                                            {{ $rec->urgency==='alta' ? 'Alta' : ($rec->urgency==='media' ? 'Média' : 'Baixa') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="pill pill-{{ $rec->status }}">
                                            <span class="pill-dot"></span>
                                            {{ $rec->status==='aprovado' ? 'Aprovado' : ($rec->status==='rejeitado' ? 'Rejeitado' : 'Pendente') }}
                                        </span>
                                    </td>
                                    <td style="white-space:nowrap; font-size:12px; color:var(--text2);">
                                        {{ $rec->created_at->timezone('America/Sao_Paulo')->format('d/m/Y') }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                {{-- Right: top items + status breakdown --}}
                <div style="display:flex; flex-direction:column; gap:16px;">

                    <div class="u-card" style="padding:24px;">
                        <div class="u-card-title">Itens mais solicitados</div>
                        @if($topItems->isEmpty())
                            <div style="font-size:13px; color:var(--text2);">Nenhum dado ainda.</div>
                        @else
                            <div class="u-top-list">
                                @foreach($topItems as $i => $item)
                                <div class="u-top-item">
                                    <div class="u-top-rank">{{ $i + 1 }}</div>
                                    <div class="u-top-info">
                                        <div class="u-top-name">{{ $item->product_name }}</div>
                                        <div class="u-top-count">{{ $item->total }} {{ $item->total === 1 ? 'requisição' : 'requisições' }}</div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="u-card" style="padding:24px;">
                        <div class="u-card-title">Status geral</div>
                        @php $t = max($stats['total'], 1); @endphp
                        <div style="display:flex; flex-direction:column; gap:12px;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <span style="font-size:13px; color:var(--text2); min-width:76px;">Aprovadas</span>
                                <div class="u-bar-wrap"><div class="u-bar" style="width:{{ round($stats['aprovado']/$t*100) }}%; background:var(--badge-aprovado-text);"></div></div>
                                <span style="font-size:13px; font-weight:500; color:var(--text); min-width:32px;">{{ round($stats['aprovado']/$t*100) }}%</span>
                            </div>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <span style="font-size:13px; color:var(--text2); min-width:76px;">Pendentes</span>
                                <div class="u-bar-wrap"><div class="u-bar" style="width:{{ round($stats['pendente']/$t*100) }}%; background:var(--badge-pendente-text);"></div></div>
                                <span style="font-size:13px; font-weight:500; color:var(--text); min-width:32px;">{{ round($stats['pendente']/$t*100) }}%</span>
                            </div>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <span style="font-size:13px; color:var(--text2); min-width:76px;">Recusadas</span>
                                <div class="u-bar-wrap"><div class="u-bar" style="width:{{ round($stats['rejeitado']/$t*100) }}%; background:var(--badge-rejeitado-text);"></div></div>
                                <span style="font-size:13px; font-weight:500; color:var(--text); min-width:32px;">{{ round($stats['rejeitado']/$t*100) }}%</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ══ PANEL VENDEDORES ════════════════════════════════════ --}}
        <div class="u-panel" id="u-panel-vendedores">
            <div style="display:grid; grid-template-columns:1fr 320px; gap:16px;">

                {{-- Ranking --}}
                <div class="u-card" style="padding:24px;">
                    <div class="u-card-title">Ranking de requisições <span class="u-card-tag">Top vendedores</span></div>
                    @php $maxRank = $ranking->max('total') ?: 1; @endphp
                    @if($ranking->isEmpty())
                        <div style="font-size:13px; color:var(--text2); text-align:center; padding:32px 0;">Nenhum dado ainda.</div>
                    @else
                        <div style="display:flex; flex-direction:column; gap:14px; margin-top:4px;">
                            @foreach($ranking as $i => $vendor)
                            <div style="display:grid; grid-template-columns:28px 36px 1fr auto auto; align-items:center; gap:12px;">
                                <div style="font-size:13px; font-weight:500; color:var(--text2); text-align:center;">{{ $i + 1 }}</div>
                                <div style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;flex-shrink:0;
                                    background:{{ ['rgba(0,113,227,0.2)','rgba(48,209,88,0.15)','rgba(255,214,10,0.15)','rgba(255,59,48,0.15)','rgba(175,82,222,0.15)'][$i % 5] }};
                                    color:{{ ['#60a5fa','#34d399','#fbbf24','#f87171','#c084fc'][$i % 5] }};">
                                    {{ strtoupper(substr($vendor->requester_name, 0, 2)) }}
                                </div>
                                <div style="min-width:0;">
                                    <div style="font-size:14px;font-weight:500;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $vendor->requester_name }}</div>
                                </div>
                                <div style="width:90px;height:4px;background:var(--border);border-radius:2px;overflow:hidden;">
                                    <div style="height:100%;background:var(--accent);border-radius:2px;width:{{ round($vendor->total / $maxRank * 100) }}%;"></div>
                                </div>
                                <div style="font-size:14px;font-weight:500;color:var(--text);min-width:28px;text-align:right;">{{ $vendor->total }}</div>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Destaque --}}
                <div style="display:flex; flex-direction:column; gap:16px;">
                    @if($ranking->isNotEmpty())
                    <div class="u-card" style="padding:24px; text-align:center;">
                        <div class="u-card-title" style="justify-content:center;">Destaque</div>
                        <div style="width:56px;height:56px;border-radius:50%;background:rgba(0,113,227,0.2);color:#60a5fa;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;margin:8px auto 12px;">
                            {{ strtoupper(substr($ranking->first()->requester_name, 0, 2)) }}
                        </div>
                        <div style="font-size:16px;font-weight:600;color:var(--text);">{{ $ranking->first()->requester_name }}</div>
                        <div style="font-size:13px;color:var(--text2);margin-top:4px;">{{ $ranking->first()->total }} {{ $ranking->first()->total === 1 ? 'requisição' : 'requisições' }}</div>
                    </div>
                    @endif

                    <div class="u-card" style="padding:24px;">
                        <div class="u-card-title">Status geral</div>
                        @php $t = max(PurchaseRequest::count(), 1); @endphp
                        @php
                            $totAprov = PurchaseRequest::where('status','aprovado')->count();
                            $totPend  = PurchaseRequest::where('status','pendente')->count();
                            $totRej   = PurchaseRequest::where('status','rejeitado')->count();
                        @endphp
                        <div style="display:flex; flex-direction:column; gap:12px;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <span style="font-size:13px; color:var(--text2); min-width:76px;">Aprovadas</span>
                                <div class="u-bar-wrap"><div class="u-bar" style="width:{{ round($totAprov/$t*100) }}%; background:var(--badge-aprovado-text);"></div></div>
                                <span style="font-size:13px; font-weight:500; color:var(--text); min-width:32px;">{{ round($totAprov/$t*100) }}%</span>
                            </div>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <span style="font-size:13px; color:var(--text2); min-width:76px;">Pendentes</span>
                                <div class="u-bar-wrap"><div class="u-bar" style="width:{{ round($totPend/$t*100) }}%; background:var(--badge-pendente-text);"></div></div>
                                <span style="font-size:13px; font-weight:500; color:var(--text); min-width:32px;">{{ round($totPend/$t*100) }}%</span>
                            </div>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <span style="font-size:13px; color:var(--text2); min-width:76px;">Recusadas</span>
                                <div class="u-bar-wrap"><div class="u-bar" style="width:{{ round($totRej/$t*100) }}%; background:var(--badge-rejeitado-text);"></div></div>
                                <span style="font-size:13px; font-weight:500; color:var(--text); min-width:32px;">{{ round($totRej/$t*100) }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ══ PANEL MINHAS REQUISIÇÕES ═══════════════════════════ --}}
        <div class="u-panel" id="u-panel-requisicoes">

            {{-- Toolbar --}}
            <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:12px; margin-bottom:20px; flex-wrap:wrap;">
                <form method="GET" action="{{ route('requests.index') }}" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center; flex:1;">
                    <input type="text" name="requester_name" value="{{ request('requester_name') }}" placeholder="Vendedor" class="u-input" style="width:140px;">
                    <input type="text" name="product_name"   value="{{ request('product_name') }}"   placeholder="Produto"   class="u-input" style="width:160px;">
                    <input type="date" name="date_from"      value="{{ request('date_from') }}"       class="u-input" title="Data inicial">
                    <input type="date" name="date_to"        value="{{ request('date_to') }}"         class="u-input" title="Data final">
                    <button type="submit" class="u-btn-primary">Filtrar</button>
                    <a href="{{ route('requests.index') }}" class="u-btn-ghost">Limpar</a>
                </form>
                <a href="{{ route('requests.create') }}" class="u-btn-new">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nova Requisição
                </a>
            </div>

            {{-- Desktop Table --}}
            <div class="u-card u-desktop" style="overflow:hidden;">
                <div style="overflow-x:auto;">
                    <table class="u-table">
                        <thead>
                            <tr>
                                <th>Vendedor</th>
                                <th>Produto</th>
                                <th>Fornecedor</th>
                                <th style="text-align:center;">Qtd</th>
                                <th style="text-align:center;">Urgência</th>
                                <th style="text-align:center;">Status</th>
                                <th style="text-align:center;">Data</th>
                                <th style="text-align:center;">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requests as $req)
                                <tr>
                                    <td style="font-weight:600;">{{ $req->requester_name ?? '—' }}</td>
                                    <td>
                                        <div style="font-weight:500;">{{ $req->product_name }}</div>
                                        @if($req->product_code)
                                            <div style="font-size:11px; color:var(--text2); margin-top:2px;">Cód: {{ $req->product_code }}</div>
                                        @endif
                                    </td>
                                    <td style="color:var(--text2);">{{ $req->supplier ?? '—' }}</td>
                                    <td style="text-align:center; font-weight:700;">{{ number_format($req->quantity,0,',','.') }}</td>
                                    <td style="text-align:center;">
                                        <span class="pill pill-{{ $req->urgency }}">
                                            <span class="pill-dot"></span>
                                            {{ $req->urgency==='alta' ? 'Alta' : ($req->urgency==='media' ? 'Média' : 'Baixa') }}
                                        </span>
                                    </td>
                                    <td style="text-align:center;">
                                        <span class="pill pill-{{ $req->status }}">
                                            <span class="pill-dot"></span>
                                            {{ $req->status==='aprovado' ? 'Aprovado' : ($req->status==='rejeitado' ? 'Rejeitado' : 'Pendente') }}
                                        </span>
                                        @if($req->admin_note)
                                            <div style="margin-top:4px;">
                                                <button onclick="document.getElementById('obs-{{ $req->id }}').style.display='flex'"
                                                        style="background:none; border:none; color:var(--text3); font-size:11px; cursor:pointer; text-decoration:underline; padding:0; font-family:inherit;">
                                                    Ver obs.
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                    <td style="text-align:center; white-space:nowrap; font-size:12px; color:var(--text2);">{{ $req->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</td>
                                    <td style="text-align:center;">
                                        <a href="{{ route('requests.export', $req) }}" target="_blank"
                                           style="display:inline-block; background:var(--success-bg); color:var(--success-text); border:1px solid currentColor; border-radius:7px; padding:5px 12px; font-size:12px; font-weight:600; text-decoration:none;">
                                            Exportar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" style="padding:60px 16px; text-align:center;">
                                        <p style="font-size:15px; color:var(--text2); margin:0 0 6px;">Nenhuma requisição encontrada</p>
                                        <p style="font-size:13px; color:var(--text3); margin:0;">Clique em "Nova Requisição" para criar a primeira</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Mobile Cards --}}
            <div class="u-mobile">
                @forelse($requests as $req)
                    <div class="u-card" style="padding:16px; margin-bottom:10px;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                            <div>
                                <div style="font-size:15px; font-weight:700; color:var(--text);">{{ $req->product_name }}</div>
                                @if($req->product_code)
                                    <div style="font-size:12px; color:var(--text3); margin-top:2px;">Cód: {{ $req->product_code }}</div>
                                @endif
                            </div>
                            <span class="pill pill-{{ $req->status }}">
                                <span class="pill-dot"></span>
                                {{ $req->status==='aprovado' ? 'Aprovado' : ($req->status==='rejeitado' ? 'Rejeitado' : 'Pendente') }}
                            </span>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:13px;">
                            <div><span style="color:var(--text3); font-size:11px;">Vendedor</span><div style="font-weight:600; color:var(--text); margin-top:2px;">{{ $req->requester_name ?? '—' }}</div></div>
                            <div><span style="color:var(--text3); font-size:11px;">Fornecedor</span><div style="font-weight:600; color:var(--text); margin-top:2px;">{{ $req->supplier ?? '—' }}</div></div>
                            <div><span style="color:var(--text3); font-size:11px;">Quantidade</span><div style="font-weight:700; color:var(--text); font-size:15px; margin-top:2px;">{{ number_format($req->quantity,0,',','.') }}</div></div>
                            <div><span style="color:var(--text3); font-size:11px;">Data</span><div style="font-weight:500; color:var(--text2); margin-top:2px; font-size:12px;">{{ $req->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</div></div>
                        </div>
                        <div style="margin-top:12px; padding-top:10px; border-top:0.5px solid var(--border); display:flex; align-items:center; justify-content:space-between; gap:8px;">
                            <span class="pill pill-{{ $req->urgency }}">
                                <span class="pill-dot"></span>
                                {{ $req->urgency==='alta' ? 'Alta' : ($req->urgency==='media' ? 'Média' : 'Baixa') }}
                            </span>
                            <a href="{{ route('requests.export', $req) }}" target="_blank"
                               style="background:var(--success-bg); color:var(--success-text); border:1px solid currentColor; border-radius:7px; padding:6px 14px; font-size:13px; font-weight:600; text-decoration:none;">
                                Exportar
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="u-card" style="padding:52px 16px; text-align:center;">
                        <p style="color:var(--text2); font-size:15px; margin:0 0 4px;">Nenhuma requisição encontrada</p>
                        <p style="color:var(--text3); font-size:13px; margin:0;">Clique em "Nova Requisição" para criar a primeira</p>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if($requests->hasPages())
                <div style="margin-top:24px; display:flex; justify-content:center;">
                    {{ $requests->links() }}
                </div>
            @endif

        </div>{{-- end panel-requisicoes --}}

    </div>
</div>

{{-- Admin note modals --}}
@foreach($requests as $req)
    @if($req->admin_note)
        <div id="obs-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:1000; align-items:center; justify-content:center;">
            <div style="background:var(--bg2); border:1px solid var(--border); border-radius:16px; padding:28px; width:100%; max-width:400px; margin:16px; box-shadow:0 24px 48px rgba(0,0,0,0.4);">
                <h3 style="margin:0 0 4px; font-size:16px; font-weight:700; color:var(--text);">Observação do Compras</h3>
                <p style="margin:0 0 16px; font-size:12px; color:var(--text3);">{{ $req->product_name }}</p>
                <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:10px; padding:16px; font-size:14px; color:var(--text2); line-height:1.6; margin-bottom:20px;">
                    {{ $req->admin_note }}
                </div>
                <div style="text-align:right;">
                    <button onclick="document.getElementById('obs-{{ $req->id }}').style.display='none'"
                            style="padding:9px 24px; border-radius:8px; border:1px solid var(--border); background:var(--bg-input); color:var(--text); font-size:14px; font-weight:600; cursor:pointer; font-family:inherit;">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    @endif
@endforeach

<script>
function uShowTab(name, btn) {
    document.querySelectorAll('.u-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.u-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('u-panel-' + name).classList.add('active');
    if (btn) btn.classList.add('active');
}
// Se vier com filtros ativos, abrir a aba de requisições automaticamente
@if(request()->hasAny(['requester_name','product_name','date_from','date_to']))
uShowTab('requisicoes', document.querySelectorAll('.u-tab')[1]);
@endif
</script>

@endsection
