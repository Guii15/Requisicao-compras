<!DOCTYPE html>
<html lang="pt-BR" id="html-root">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Binário — Central de Inteligência Comercial</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --bg:          #000;
  --bg2:         #0a0a0a;
  --bg3:         #111;
  --bg-card:     #111;
  --text:        #fff;
  --text2:       #86868b;
  --text3:       #515154;
  --border:      rgba(255,255,255,0.08);
  --nav-bg:      rgba(0,0,0,0.72);
  --accent:      #0071e3;
  --accent-dark: #0077ed;
  --success:     #30d158;
  --warning:     #ffd60a;
  --danger:      #ff3b30;
  --section-light-bg:   #f5f5f7;
  --section-light-text: #1d1d1f;
  --section-light-sub:  #515154;
}

.light-mode {
  --bg:       #f5f5f7;
  --bg2:      #fff;
  --bg3:      #f0f0f5;
  --bg-card:  #fff;
  --text:     #1d1d1f;
  --text2:    #515154;
  --text3:    #6e6e73;
  --border:   rgba(0,0,0,0.08);
  --nav-bg:   rgba(245,245,247,0.82);
  --success:  #1a8a3c;
  --warning:  #a05a00;
  --danger:   #cc0000;
}

html { scroll-behavior: smooth; }
body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); overflow-x: hidden; transition: background 0.35s, color 0.35s; padding-top: 52px; }

/* NAV */
nav {
  position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
  background: var(--nav-bg);
  backdrop-filter: saturate(180%) blur(20px);
  -webkit-backdrop-filter: saturate(180%) blur(20px);
  border-bottom: 0.5px solid var(--border);
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 40px; height: 52px; transition: background 0.35s;
}
.nav-logo { font-family: 'Playfair Display', serif; font-size: 20px; font-weight: 700; color: var(--text); text-decoration: none; letter-spacing: 0.04em; }
.nav-logo span { color: var(--accent); }
.nav-links { display: flex; gap: 28px; list-style: none; }
.nav-links a { color: var(--text2); text-decoration: none; font-size: 13px; font-weight: 400; transition: color 0.2s; }
.nav-links a:hover { color: var(--text); }
.nav-right { display: flex; align-items: center; gap: 16px; }
.nav-user { font-size: 13px; color: var(--text2); }
.theme-toggle {
  background: none; border: 1px solid var(--border); border-radius: 20px;
  padding: 5px 14px; font-size: 12px; font-family: 'DM Sans', sans-serif;
  color: var(--text2); cursor: pointer; transition: all 0.2s;
}
.theme-toggle:hover { border-color: var(--accent); color: var(--accent); }
.nav-logout-form { display: inline; }
.nav-logout-btn { background: none; border: none; cursor: pointer; font-size: 13px; color: var(--accent); font-family: 'DM Sans', sans-serif; }
.nav-logout-btn:hover { text-decoration: underline; }

/* DIVIDER */
.section-divider { width: 100%; height: 0.5px; background: var(--border); }

/* STATS ROW */
.stats-row {
  display: grid; grid-template-columns: repeat(4,1fr);
  background: var(--border); gap: 1px;
  border-top: 0.5px solid var(--border); border-bottom: 0.5px solid var(--border);
}
.stat-item { padding: 48px 32px; text-align: center; background: var(--bg); transition: background 0.35s; }
.light-mode .stat-item { background: var(--bg2); }
.stat-number { font-family: 'Playfair Display', serif; font-size: 52px; font-weight: 900; color: var(--text); line-height: 1; }
.stat-number span { color: var(--accent); font-size: 30px; }
.stat-label { margin-top: 8px; font-size: 14px; color: var(--text2); }

/* DASHBOARD SECTION */
.dashboard-section { background: var(--bg2); padding: 100px 24px; transition: background 0.35s; }
.dashboard-header { text-align: center; margin-bottom: 64px; }
.dashboard-wrap { max-width: 1200px; margin: 0 auto; }

/* TABS */
.dash-tabs {
  display: flex; gap: 4px; background: rgba(255,255,255,0.06);
  border-radius: 12px; padding: 4px; width: fit-content; margin: 0 auto 48px;
}
.light-mode .dash-tabs { background: rgba(0,0,0,0.06); }
.dash-tab {
  padding: 9px 22px; border-radius: 9px; font-size: 14px; font-weight: 500;
  color: var(--text2); cursor: pointer; transition: all 0.25s;
  border: none; background: transparent; font-family: 'DM Sans', sans-serif;
}
.dash-tab.active { background: var(--text); color: var(--bg); }
.light-mode .dash-tab.active { background: #1d1d1f; color: #fff; }
.dash-panel { display: none; }
.dash-panel.active { display: block; }

/* METRICS */
.metrics-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 24px; }
.metric-card { background: var(--bg-card); border: 0.5px solid var(--border); border-radius: 16px; padding: 24px; transition: background 0.35s; }
.metric-label { font-size: 13px; color: var(--text2); margin-bottom: 8px; }
.metric-value { font-family: 'Playfair Display', serif; font-size: 32px; font-weight: 700; color: var(--text); line-height: 1; }
.metric-delta { margin-top: 8px; font-size: 12px; color: var(--text2); }

/* MAIN GRID */
.main-grid { display: grid; grid-template-columns: 1fr 380px; gap: 16px; }
.card { background: var(--bg-card); border: 0.5px solid var(--border); border-radius: 16px; padding: 24px; transition: background 0.35s; }
.card-title { font-size: 15px; font-weight: 500; color: var(--text); margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }
.card-title-tag { font-size: 12px; color: var(--text2); font-weight: 400; }

/* CHART */
.chart-area { display: flex; align-items: flex-end; gap: 8px; height: 100px; margin-bottom: 8px; }
.chart-col { display: flex; flex-direction: column; align-items: center; flex: 1; gap: 4px; }
.chart-bar { width: 100%; background: var(--accent); border-radius: 4px 4px 0 0; opacity: 0.8; cursor: pointer; transition: opacity 0.2s; }
.chart-bar:hover { opacity: 1; }
.chart-bar.dim { background: rgba(255,255,255,0.12); opacity: 1; }
.light-mode .chart-bar.dim { background: rgba(0,0,0,0.1); }
.chart-lbl { font-size: 11px; color: var(--text2); }

/* PILLS */
.pill { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
.pill-success { background: rgba(48,209,88,0.12); color: var(--success); }
.pill-warning { background: rgba(255,214,10,0.12); color: var(--warning); }
.pill-danger  { background: rgba(255,59,48,0.12); color: var(--danger); }
.pill-info    { background: rgba(0,113,227,0.12); color: #60a5fa; }
.pill-dot     { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
.light-mode .pill-info { color: var(--accent); }
.light-mode .pill-warning { color: var(--warning); }

/* HISTORY TABLE */
.hist-table { width: 100%; border-collapse: collapse; }
.hist-table th { font-size: 11px; font-weight: 500; color: var(--text2); text-align: left; padding: 0 8px 12px; letter-spacing: 0.06em; text-transform: uppercase; border-bottom: 0.5px solid var(--border); }
.hist-table td { font-size: 13px; color: var(--text); padding: 12px 8px; border-bottom: 0.5px solid var(--border); vertical-align: middle; }
.hist-table tr:last-child td { border-bottom: none; }
.hist-item-name { font-weight: 500; }
.hist-item-cat  { font-size: 11px; color: var(--text2); margin-top: 2px; }

/* SIDE */
.side-stack { display: flex; flex-direction: column; gap: 16px; }
.top-items-list { display: flex; flex-direction: column; gap: 10px; }
.top-item { display: flex; align-items: center; gap: 10px; }
.top-item-rank { width: 20px; font-size: 12px; color: var(--text2); font-weight: 500; }
.top-item-info { flex: 1; min-width: 0; }
.top-item-name { font-size: 13px; color: var(--text); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.top-item-count { font-size: 11px; color: var(--text2); }

/* STATUS BARS */
.status-bar-wrap { height: 4px; background: var(--border); border-radius: 2px; flex: 1; }
.status-bar { height: 100%; border-radius: 2px; }

/* RANKING */
.rank-list { display: flex; flex-direction: column; gap: 12px; }
.rank-item { display: grid; grid-template-columns: 28px 36px 1fr auto auto; align-items: center; gap: 12px; }
.rank-num { font-size: 13px; font-weight: 500; color: var(--text2); text-align: center; }
.rank-avatar { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 600; flex-shrink: 0; }
.av-0 { background: rgba(0,113,227,0.2); color: #60a5fa; }
.av-1 { background: rgba(48,209,88,0.15); color: #34d399; }
.av-2 { background: rgba(255,214,10,0.15); color: #fbbf24; }
.av-3 { background: rgba(255,59,48,0.15); color: #f87171; }
.av-4 { background: rgba(175,82,222,0.15); color: #c084fc; }
.rank-info { min-width: 0; }
.rank-name { font-size: 14px; font-weight: 500; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rank-bar-wrap { width: 90px; height: 4px; background: var(--border); border-radius: 2px; overflow: hidden; }
.rank-bar { height: 100%; background: var(--accent); border-radius: 2px; }
.rank-count { font-size: 14px; font-weight: 500; color: var(--text); min-width: 32px; text-align: right; }

/* MANAGEMENT */
.mgmt-filters { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
.mgmt-input {
  background: var(--bg-card); border: 0.5px solid var(--border); border-radius: 8px;
  padding: 8px 14px; font-size: 13px; color: var(--text); font-family: 'DM Sans', sans-serif;
  outline: none; transition: border 0.2s;
}
.mgmt-input:focus { border-color: var(--accent); }
.mgmt-btn { background: var(--accent); color: #fff; border: none; border-radius: 8px; padding: 8px 18px; font-size: 13px; font-weight: 500; cursor: pointer; font-family: 'DM Sans', sans-serif; }
.mgmt-btn-ghost {
  background: transparent; color: var(--text2); border: 0.5px solid var(--border); border-radius: 8px;
  padding: 8px 18px; font-size: 13px; cursor: pointer; font-family: 'DM Sans', sans-serif; text-decoration: none; display: inline-block;
}
.mgmt-table { width: 100%; border-collapse: collapse; }
.mgmt-table th { font-size: 11px; font-weight: 500; color: var(--text2); text-align: left; padding: 10px 12px; letter-spacing: 0.06em; text-transform: uppercase; border-bottom: 0.5px solid var(--border); white-space: nowrap; }
.mgmt-table td { font-size: 13px; color: var(--text); padding: 14px 12px; border-bottom: 0.5px solid var(--border); vertical-align: middle; }
.mgmt-table tr:last-child td { border-bottom: none; }
.mgmt-table tr:hover td { background: rgba(255,255,255,0.02); }
.light-mode .mgmt-table tr:hover td { background: rgba(0,0,0,0.02); }
.req-name { font-weight: 500; }
.req-code { font-size: 11px; color: var(--text2); margin-top: 2px; }
.urgency-alta  { color: var(--danger); font-weight: 600; }
.urgency-media { color: var(--warning); font-weight: 600; }
.urgency-baixa { color: var(--success); font-weight: 600; }
.action-btn { padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 500; cursor: pointer; border: none; font-family: 'DM Sans', sans-serif; text-decoration: none; display: inline-block; }
.action-btn-primary { background: rgba(0,113,227,0.12); color: var(--accent); }
.action-btn-primary:hover { background: rgba(0,113,227,0.2); }
.action-btn-export { background: var(--border); color: var(--text2); }
.action-btn-export:hover { color: var(--text); }
.light-mode .action-btn-export { background: rgba(0,0,0,0.08); }

/* MODAL */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.72); z-index: 2000;
  display: flex; align-items: center; justify-content: center; padding: 24px;
  opacity: 0; pointer-events: none; transition: opacity 0.2s;
}
.modal-overlay.open { opacity: 1; pointer-events: all; }
.modal-box {
  background: var(--bg3); border: 0.5px solid var(--border); border-radius: 20px; padding: 32px;
  max-width: 480px; width: 100%; transform: scale(0.95); transition: transform 0.25s;
}
.modal-overlay.open .modal-box { transform: scale(1); }
.modal-title { font-size: 18px; font-weight: 600; color: var(--text); margin-bottom: 20px; }
.modal-label { font-size: 12px; color: var(--text2); font-weight: 500; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; display: block; }
.modal-select, .modal-textarea {
  width: 100%; background: var(--bg-card); border: 0.5px solid var(--border); border-radius: 10px;
  padding: 10px 14px; font-size: 14px; color: var(--text); font-family: 'DM Sans', sans-serif;
  outline: none; transition: border 0.2s; margin-bottom: 16px;
}
.modal-select:focus, .modal-textarea:focus { border-color: var(--accent); }
.modal-textarea { resize: vertical; min-height: 80px; }
.modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 8px; }
.modal-cancel { background: transparent; border: 0.5px solid var(--border); color: var(--text2); border-radius: 8px; padding: 9px 20px; font-size: 13px; cursor: pointer; font-family: 'DM Sans', sans-serif; }
.modal-save { background: var(--accent); color: #fff; border: none; border-radius: 8px; padding: 9px 20px; font-size: 13px; font-weight: 500; cursor: pointer; font-family: 'DM Sans', sans-serif; }

/* FLASH */
.flash {
  position: fixed; top: 64px; right: 24px; z-index: 1500;
  background: var(--bg-card); border: 0.5px solid var(--border); border-radius: 12px;
  padding: 14px 20px; font-size: 14px; color: var(--text);
  box-shadow: 0 8px 30px rgba(0,0,0,0.3); max-width: 320px;
  animation: slideIn 0.3s ease, fadeOut 0.4s ease 3.6s forwards;
}
.flash-success { border-left: 3px solid var(--success); }
.flash-error   { border-left: 3px solid var(--danger); }
@keyframes slideIn { from { transform: translateX(40px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
@keyframes fadeOut { to { opacity: 0; transform: translateX(40px); } }

@keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }

/* FOOTER */
footer { background: var(--bg3); border-top: 0.5px solid var(--border); padding: 40px 24px; text-align: center; transition: background 0.35s; }
.footer-logo { font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700; color: var(--text); }
.footer-logo span { color: var(--accent); }
footer p { font-size: 12px; color: var(--text2); margin-top: 8px; }

/* RESPONSIVE */
@media (max-width: 900px) {
  .metrics-grid { grid-template-columns: repeat(2,1fr); }
  .main-grid { grid-template-columns: 1fr; }
  .stats-row { grid-template-columns: repeat(2,1fr); }
  nav { padding: 0 20px; }
  .nav-links { display: none; }
}
@media (max-width: 600px) {
  .metrics-grid { grid-template-columns: 1fr 1fr; }
  .rank-item { grid-template-columns: 20px 32px 1fr auto; }
  .rank-bar-wrap { display: none; }
}
</style>
</head>
<body>

@if(session('success'))
<div class="flash flash-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="flash flash-error">{{ session('error') }}</div>
@endif

{{-- NAV --}}
<nav>
  <a href="{{ route('admin.index') }}" class="nav-logo">Binário<span>.</span></a>
  <ul class="nav-links">
    <li><a href="{{ route('admin.index') }}">Dashboard</a></li>
    <li><a href="#" onclick="showTab('requisicoes', document.querySelectorAll('.dash-tab')[2]);return false;">Requisições</a></li>
    <li><a href="#" onclick="showTab('vendedores', document.querySelectorAll('.dash-tab')[1]);return false;">Vendedores</a></li>
    <li><a href="{{ route('admin.users.index') }}">Usuários</a></li>
  </ul>
  <div class="nav-right">
    <button class="theme-toggle" onclick="toggleTheme()" id="theme-btn">☀ Modo claro</button>
    <span class="nav-user">{{ auth()->user()->name }}</span>
    <form method="POST" action="{{ route('logout') }}" class="nav-logout-form">
      @csrf
      <button type="submit" class="nav-logout-btn">Sair</button>
    </form>
  </div>
</nav>

{{-- STATS --}}
<div class="stats-row">
  <div class="stat-item">
    <div class="stat-number">{{ $stats['total'] }}<span> req</span></div>
    <div class="stat-label">Total de requisições</div>
  </div>
  <div class="stat-item">
    <div class="stat-number">{{ $stats['aprovado'] }}<span> apr</span></div>
    <div class="stat-label">Aprovadas</div>
  </div>
  <div class="stat-item">
    <div class="stat-number">{{ $stats['pendente'] }}<span> pend</span></div>
    <div class="stat-label">Aguardando aprovação</div>
  </div>
  <div class="stat-item">
    <div class="stat-number">{{ $stats['rejeitado'] }}<span> rec</span></div>
    <div class="stat-label">Recusadas</div>
  </div>
</div>

{{-- DASHBOARD --}}
<section class="dashboard-section" id="dashboard">
  <div class="dashboard-header">
    <div class="section-eyebrow">Painel Administrativo</div>
    <h2 style="font-family:'Playfair Display',serif; font-size:clamp(28px,4vw,42px); font-weight:900; color:var(--text); letter-spacing:-0.02em; margin-bottom:8px;">Gestão de Requisições</h2>
    <p style="font-size:15px; color:var(--text2); font-weight:300;">Visualize, filtre e aprove as solicitações dos vendedores</p>
  </div>

  <div class="dashboard-wrap">
    <div class="dash-tabs">
      <button class="dash-tab active" onclick="showTab('geral',this)">Geral</button>
      <button class="dash-tab" onclick="showTab('vendedores',this)">Vendedores</button>
      <button class="dash-tab" onclick="showTab('requisicoes',this)">Requisições</button>
    </div>

    {{-- PANEL GERAL --}}
    <div class="dash-panel active" id="panel-geral">
      <div class="metrics-grid">
        <div class="metric-card">
          <div class="metric-label">Requisições pendentes</div>
          <div class="metric-value">{{ $stats['pendente'] }}</div>
          <div class="metric-delta">Aguardando análise</div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Aprovadas</div>
          <div class="metric-value">{{ $stats['aprovado'] }}</div>
          <div class="metric-delta">{{ $stats['total'] > 0 ? round($stats['aprovado'] / $stats['total'] * 100) : 0 }}% do total</div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Total histórico</div>
          <div class="metric-value">{{ $stats['total'] }}</div>
          <div class="metric-delta">Todas as requisições</div>
        </div>
        <div class="metric-card">
          <div class="metric-label">Recusadas</div>
          <div class="metric-value">{{ $stats['rejeitado'] }}</div>
          <div class="metric-delta">{{ $stats['total'] > 0 ? round($stats['rejeitado'] / $stats['total'] * 100) : 0 }}% do total</div>
        </div>
      </div>

      <div class="main-grid">
        <div class="card">
          <div class="card-title">Requisições por semana <span class="card-title-tag">Últimas 5 semanas</span></div>
          @php $maxWeek = $weeklyStats->max('total') ?: 1; @endphp
          <div class="chart-area">
            @foreach($weeklyStats as $i => $week)
            <div class="chart-col">
              <div class="chart-bar {{ $i < 4 ? 'dim' : '' }}" style="height:{{ max(6, round($week['total'] / $maxWeek * 92)) }}px" title="{{ $week['total'] }} req"></div>
              <div class="chart-lbl">{{ $week['label'] }}</div>
            </div>
            @endforeach
          </div>

          <div class="card-title" style="margin-top:24px">Últimas requisições</div>
          <table class="hist-table">
            <thead><tr><th>Item</th><th>Vendedor</th><th>Urgência</th><th>Status</th></tr></thead>
            <tbody>
              @foreach($requests->take(6) as $req)
              <tr>
                <td>
                  <div class="hist-item-name">{{ Str::limit($req->product_name, 30) }}</div>
                  @if($req->supplier)<div class="hist-item-cat">{{ $req->supplier }}</div>@endif
                </td>
                <td>{{ $req->requester_name }}</td>
                <td><span class="urgency-{{ $req->urgency }}">{{ $req->urgency === 'alta' ? 'Alta' : ($req->urgency === 'media' ? 'Média' : 'Baixa') }}</span></td>
                <td>
                  @if($req->status === 'aprovado')<span class="pill pill-success"><span class="pill-dot"></span>Aprovado</span>
                  @elseif($req->status === 'rejeitado')<span class="pill pill-danger"><span class="pill-dot"></span>Recusado</span>
                  @else<span class="pill pill-warning"><span class="pill-dot"></span>Pendente</span>@endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="side-stack">
          <div class="card">
            <div class="card-title">Itens mais solicitados</div>
            <div class="top-items-list">
              @forelse($topItems as $i => $item)
              <div class="top-item">
                <div class="top-item-rank">{{ $i + 1 }}</div>
                <div class="top-item-info">
                  <div class="top-item-name">{{ $item->product_name }}</div>
                  <div class="top-item-count">{{ $item->total }} req</div>
                </div>
              </div>
              @empty
              <div style="font-size:13px;color:var(--text2)">Nenhum dado ainda.</div>
              @endforelse
            </div>
          </div>

          <div class="card">
            <div class="card-title">Status geral</div>
            @php $t = max($stats['total'], 1); @endphp
            <div style="display:flex;flex-direction:column;gap:10px">
              <div style="display:flex;align-items:center;gap:10px">
                <span style="font-size:13px;color:var(--text2);min-width:72px">Aprovadas</span>
                <div class="status-bar-wrap"><div class="status-bar" style="width:{{ round($stats['aprovado']/$t*100) }}%;background:var(--success)"></div></div>
                <span style="font-size:13px;font-weight:500;color:var(--text);min-width:32px">{{ round($stats['aprovado']/$t*100) }}%</span>
              </div>
              <div style="display:flex;align-items:center;gap:10px">
                <span style="font-size:13px;color:var(--text2);min-width:72px">Pendentes</span>
                <div class="status-bar-wrap"><div class="status-bar" style="width:{{ round($stats['pendente']/$t*100) }}%;background:var(--warning)"></div></div>
                <span style="font-size:13px;font-weight:500;color:var(--text);min-width:32px">{{ round($stats['pendente']/$t*100) }}%</span>
              </div>
              <div style="display:flex;align-items:center;gap:10px">
                <span style="font-size:13px;color:var(--text2);min-width:72px">Recusadas</span>
                <div class="status-bar-wrap"><div class="status-bar" style="width:{{ round($stats['rejeitado']/$t*100) }}%;background:var(--danger)"></div></div>
                <span style="font-size:13px;font-weight:500;color:var(--text);min-width:32px">{{ round($stats['rejeitado']/$t*100) }}%</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- PANEL VENDEDORES --}}
    <div class="dash-panel" id="panel-vendedores">
      <div class="main-grid">
        <div class="card">
          <div class="card-title">Ranking de requisições <span class="card-title-tag">Top vendedores</span></div>
          @php $maxRank = $ranking->max('total') ?: 1; @endphp
          <div class="rank-list">
            @forelse($ranking as $i => $vendor)
            <div class="rank-item">
              <div class="rank-num">{{ $i + 1 }}</div>
              <div class="rank-avatar av-{{ $i % 5 }}">{{ strtoupper(substr($vendor->requester_name, 0, 2)) }}</div>
              <div class="rank-info"><div class="rank-name">{{ $vendor->requester_name }}</div></div>
              <div class="rank-bar-wrap"><div class="rank-bar" style="width:{{ round($vendor->total/$maxRank*100) }}%"></div></div>
              <div class="rank-count">{{ $vendor->total }}</div>
            </div>
            @empty
            <div style="font-size:13px;color:var(--text2)">Nenhum dado ainda.</div>
            @endforelse
          </div>
        </div>

        <div class="side-stack">
          @if($ranking->isNotEmpty())
          <div class="card">
            <div class="card-title">Destaque</div>
            <div style="text-align:center;padding:8px 0">
              <div class="rank-avatar av-0" style="width:56px;height:56px;font-size:20px;margin:0 auto 12px">{{ strtoupper(substr($ranking->first()->requester_name, 0, 2)) }}</div>
              <div style="font-size:16px;font-weight:600;color:var(--text)">{{ $ranking->first()->requester_name }}</div>
              <div style="font-size:13px;color:var(--text2);margin-top:4px">{{ $ranking->first()->total }} requisições</div>
            </div>
          </div>
          @endif
          <div class="card">
            <div class="card-title">Itens mais pedidos</div>
            <div class="top-items-list">
              @forelse($topItems as $i => $item)
              <div class="top-item">
                <div class="top-item-rank">{{ $i + 1 }}</div>
                <div class="top-item-info">
                  <div class="top-item-name">{{ $item->product_name }}</div>
                  <div class="top-item-count">{{ $item->total }} req</div>
                </div>
              </div>
              @empty
              <div style="font-size:13px;color:var(--text2)">Nenhum dado.</div>
              @endforelse
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- PANEL REQUISIÇÕES --}}
    <div class="dash-panel" id="panel-requisicoes">
      <div class="card" style="margin-bottom:16px;padding:20px 24px">
        <form method="GET" action="{{ route('admin.index') }}">
          <div class="mgmt-filters">
            <select name="status" class="mgmt-input">
              <option value="">Todos os status</option>
              <option value="pendente"  {{ request('status')==='pendente'  ? 'selected' : '' }}>Pendente</option>
              <option value="aprovado"  {{ request('status')==='aprovado'  ? 'selected' : '' }}>Aprovado</option>
              <option value="rejeitado" {{ request('status')==='rejeitado' ? 'selected' : '' }}>Rejeitado</option>
            </select>
            <input type="text"  name="requester_name" value="{{ request('requester_name') }}" placeholder="Vendedor..." class="mgmt-input">
            <input type="date"  name="date_from"      value="{{ request('date_from') }}"      class="mgmt-input">
            <input type="date"  name="date_to"        value="{{ request('date_to') }}"        class="mgmt-input">
            <button type="submit" class="mgmt-btn">Filtrar</button>
            <a href="{{ route('admin.index') }}" class="mgmt-btn-ghost">Limpar</a>
          </div>
        </form>
      </div>

      <div class="card" style="padding:0;overflow:hidden">
        <div style="overflow-x:auto">
          <table class="mgmt-table">
            <thead>
              <tr>
                <th style="padding-left:24px">#</th>
                <th>Produto</th>
                <th>Vendedor</th>
                <th>Fornecedor</th>
                <th>Urgência</th>
                <th>Data</th>
                <th>Status</th>
                <th style="padding-right:24px">Ações</th>
              </tr>
            </thead>
            <tbody>
              @forelse($requests as $req)
              <tr>
                <td style="padding-left:24px;color:var(--text2);font-size:12px">#{{ str_pad($req->id, 5, '0', STR_PAD_LEFT) }}</td>
                <td>
                  <div class="req-name">{{ Str::limit($req->product_name, 26) }}</div>
                  @if($req->product_code)<div class="req-code">{{ $req->product_code }}</div>@endif
                  @if($req->product_url ?? false)
                    <a href="{{ $req->product_url }}" target="_blank" rel="noopener" style="font-size:11px;color:var(--accent)">Ver produto ↗</a>
                  @endif
                </td>
                <td>{{ $req->requester_name }}</td>
                <td style="color:var(--text2)">{{ $req->supplier ?: '—' }}</td>
                <td><span class="urgency-{{ $req->urgency }}">{{ $req->urgency === 'alta' ? 'Alta' : ($req->urgency === 'media' ? 'Média' : 'Baixa') }}</span></td>
                <td style="color:var(--text2);font-size:12px;white-space:nowrap">{{ $req->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</td>
                <td>
                  @if($req->status === 'aprovado')<span class="pill pill-success"><span class="pill-dot"></span>Aprovado</span>
                  @elseif($req->status === 'rejeitado')<span class="pill pill-danger"><span class="pill-dot"></span>Recusado</span>
                  @else<span class="pill pill-warning"><span class="pill-dot"></span>Pendente</span>@endif
                </td>
                <td style="padding-right:24px">
                  <div style="display:flex;gap:6px;align-items:center">
                    <button class="action-btn action-btn-primary" onclick="openModal({{ $req->id }},'{{ $req->status }}',{{ json_encode($req->admin_note ?? '') }})">Atualizar</button>
                    <a href="{{ route('admin.requests.export', $req->id) }}" target="_blank" class="action-btn action-btn-export">PDF</a>
                  </div>
                </td>
              </tr>
              @empty
              <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text2)">Nenhuma requisição encontrada.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</section>

<footer>
  <div class="footer-logo">Binário<span>.</span></div>
  <p>Central de Inteligência Comercial · © {{ date('Y') }} Binário Tecnologia</p>
  <p style="margin-top:4px">Todos os dados são confidenciais e de uso interno.</p>
</footer>

{{-- MODAL --}}
<div class="modal-overlay" id="modal" onclick="if(event.target===this)closeModal()">
  <div class="modal-box">
    <div class="modal-title" id="modal-title">Atualizar Requisição</div>
    <form method="POST" id="modal-form">
      @csrf
      @method('PATCH')
      <label class="modal-label">Status</label>
      <select name="status" id="modal-status" class="modal-select">
        <option value="pendente">Pendente</option>
        <option value="aprovado">Aprovado</option>
        <option value="rejeitado">Rejeitado</option>
      </select>
      <label class="modal-label">Nota (opcional)</label>
      <textarea name="admin_note" id="modal-note" class="modal-textarea" placeholder="Observação para o vendedor..."></textarea>
      <div class="modal-actions">
        <button type="button" class="modal-cancel" onclick="closeModal()">Cancelar</button>
        <button type="submit" class="modal-save">Salvar</button>
      </div>
    </form>
  </div>
</div>

<script>
// DARK / LIGHT
const htmlEl = document.getElementById('html-root');
const themeBtn = document.getElementById('theme-btn');
let isLight = localStorage.getItem('binario-theme') === 'light';
function applyTheme() {
  htmlEl.classList.toggle('light-mode', isLight);
  themeBtn.textContent = isLight ? '◑ Modo escuro' : '☀ Modo claro';
}
function toggleTheme() { isLight = !isLight; localStorage.setItem('binario-theme', isLight ? 'light' : 'dark'); applyTheme(); }
applyTheme();

// TABS
function showTab(name, btn) {
  document.querySelectorAll('.dash-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.dash-panel').forEach(p => p.classList.remove('active'));
  document.getElementById('panel-' + name).classList.add('active');
  if (btn) btn.classList.add('active');
}

// MODAL
const routeMap = {
  @foreach($requests as $req)
  {{ $req->id }}: '{{ route('admin.requests.update', $req->id) }}',
  @endforeach
};
function openModal(id, status, note) {
  document.getElementById('modal-form').action = routeMap[id] || '';
  document.getElementById('modal-status').value = status;
  document.getElementById('modal-note').value = note || '';
  document.getElementById('modal-title').textContent = 'Requisição #' + String(id).padStart(5,'0');
  document.getElementById('modal').classList.add('open');
}
function closeModal() { document.getElementById('modal').classList.remove('open'); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

// Abre tab requisições se vier de filtro
@if(request()->hasAny(['status','requester_name','date_from','date_to']))
showTab('requisicoes', document.querySelectorAll('.dash-tab')[2]);
@endif
</script>
</body>
</html>
