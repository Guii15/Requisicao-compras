@extends('layouts.app')

@section('fullcontent')

<style>
/* STATS ROW */
.u-stats-row {
    display: grid; grid-template-columns: repeat(4,1fr);
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

/* MAIN SECTION */
.u-section { background: var(--bg2); padding: 64px 24px 80px; transition: background 0.35s; }
.u-inner { max-width: 1200px; margin: 0 auto; }

/* SECTION HEADER */
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

/* INPUTS / BUTTONS */
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

/* CARD */
.u-card {
    background: var(--bg-card); border: 0.5px solid var(--border);
    border-radius: 16px; transition: background 0.35s;
}

/* TABLE */
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

/* PILLS */
.pill { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
.pill-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
.pill-aprovado  { background: var(--badge-aprovado-bg);  color: var(--badge-aprovado-text); }
.pill-rejeitado { background: var(--badge-rejeitado-bg); color: var(--badge-rejeitado-text); }
.pill-pendente  { background: var(--badge-pendente-bg);  color: var(--badge-pendente-text); }
.pill-alta      { background: var(--badge-alta-bg);      color: var(--badge-alta-text); }
.pill-media     { background: var(--badge-media-bg);     color: var(--badge-media-text); }
.pill-baixa     { background: var(--badge-baixa-bg);     color: var(--badge-baixa-text); }

@media (max-width: 768px) {
    .u-stats-row { grid-template-columns: repeat(2,1fr); }
    .u-stat-item { padding: 24px 16px; }
    .u-stat-number { font-size: 36px; }
    .u-title { font-size: 28px; }
    .u-section { padding: 40px 16px 60px; }
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

        {{-- Flash --}}
        @if(session('success'))
            <div style="background:var(--success-bg); color:var(--success-text); border:1px solid currentColor; border-radius:10px; padding:12px 16px; margin-bottom:24px; display:flex; align-items:center; gap:10px; font-size:14px; font-weight:500;">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

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

@endsection
