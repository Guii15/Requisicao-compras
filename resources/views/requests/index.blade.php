@extends('layouts.app')

@section('content')

<style>
.req-table thead tr { background: linear-gradient(90deg, #05018D, var(--accent)); }
.req-table th { padding:12px 16px; color:#fff; font-size:12px; font-weight:700; text-align:left; white-space:nowrap; text-transform:uppercase; letter-spacing:0.4px; }
.req-table td { padding:14px 16px; font-size:14px; color:var(--text2); border-top:1px solid var(--border); vertical-align:middle; }
.req-table tbody tr { transition:background 0.15s; }
.req-table tbody tr:hover { background:var(--bg-card); }
.req-table tbody tr:hover td { color:var(--text); }

.badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; white-space:nowrap; }
.badge-alta      { background:var(--badge-alta-bg);      color:var(--badge-alta-text); }
.badge-media     { background:var(--badge-media-bg);     color:var(--badge-media-text); }
.badge-baixa     { background:var(--badge-baixa-bg);     color:var(--badge-baixa-text); }
.badge-aprovado  { background:var(--badge-aprovado-bg);  color:var(--badge-aprovado-text); }
.badge-rejeitado { background:var(--badge-rejeitado-bg); color:var(--badge-rejeitado-text); }
.badge-pendente  { background:var(--badge-pendente-bg);  color:var(--badge-pendente-text); }

.filter-input {
    width:100%; background:var(--bg-input); border:1px solid var(--border);
    border-radius:8px; padding:9px 12px; font-size:14px; color:var(--text);
    font-family:inherit; outline:none; transition:border-color 0.2s;
}
.filter-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(0,113,227,0.15); }
.filter-input::placeholder { color:var(--text3); }

@media (max-width:768px) {
    .req-desktop { display:none; }
    .req-mobile  { display:block; }
    .req-header  { flex-direction:column !important; align-items:flex-start !important; }
    .req-btn     { width:100%; justify-content:center; }
    .filter-grid { grid-template-columns:1fr !important; }
}
@media (min-width:769px) {
    .req-desktop { display:block; }
    .req-mobile  { display:none; }
}
</style>

{{-- Header --}}
<div class="req-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; gap:12px; flex-wrap:wrap;">
    <div>
        <h1 style="margin:0 0 4px; font-size:26px; font-weight:700; color:var(--text); letter-spacing:-0.5px;">Minhas Requisições</h1>
        <p style="margin:0; font-size:14px; color:var(--text2);">Acompanhe todas as suas solicitações de compra</p>
    </div>
    <a href="{{ route('requests.create') }}" class="req-btn"
       style="display:inline-flex; align-items:center; gap:8px; background:linear-gradient(135deg,#05018D,var(--accent)); color:#fff; padding:10px 20px; border-radius:10px; text-decoration:none; font-weight:600; font-size:14px; white-space:nowrap; box-shadow:0 4px 14px rgba(0,113,227,0.3);">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
        </svg>
        Nova Requisição
    </a>
</div>

{{-- Flash --}}
@if(session('success'))
    <div style="background:var(--success-bg); color:var(--success-text); border:1px solid currentColor; border-radius:10px; padding:12px 16px; margin-bottom:20px; display:flex; align-items:center; gap:10px; font-size:14px; font-weight:500;">
        <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
    </div>
@endif

{{-- Filters --}}
<div style="background:var(--bg-card); border:1px solid var(--border); border-radius:12px; padding:20px; margin-bottom:20px;">
    <p style="margin:0 0 14px; font-size:11px; font-weight:700; color:var(--text3); text-transform:uppercase; letter-spacing:0.8px;">Filtrar Requisições</p>
    <form method="GET" action="{{ route('requests.index') }}">
        <div class="filter-grid" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:12px; align-items:end;">
            <div>
                <label style="display:block; font-size:12px; font-weight:500; color:var(--text2); margin-bottom:6px;">Vendedor</label>
                <input type="text" name="requester_name" value="{{ request('requester_name') }}" placeholder="Nome do vendedor" class="filter-input">
            </div>
            <div>
                <label style="display:block; font-size:12px; font-weight:500; color:var(--text2); margin-bottom:6px;">Produto</label>
                <input type="text" name="product_name" value="{{ request('product_name') }}" placeholder="Nome do produto" class="filter-input">
            </div>
            <div>
                <label style="display:block; font-size:12px; font-weight:500; color:var(--text2); margin-bottom:6px;">Data inicial</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="filter-input">
            </div>
            <div>
                <label style="display:block; font-size:12px; font-weight:500; color:var(--text2); margin-bottom:6px;">Data final</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="filter-input">
            </div>
            <div style="display:flex; gap:8px;">
                <button type="submit" style="flex:1; background:var(--accent); color:#fff; padding:9px 14px; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; font-family:inherit;">Filtrar</button>
                <a href="{{ route('requests.index') }}" style="flex:1; background:var(--bg-input); color:var(--text2); padding:9px 14px; border-radius:8px; font-size:14px; font-weight:500; text-decoration:none; text-align:center; border:1px solid var(--border);">Limpar</a>
            </div>
        </div>
    </form>
</div>

{{-- Desktop table --}}
<div class="req-desktop" style="background:var(--bg-card); border:1px solid var(--border); border-radius:12px; overflow:hidden;">
    <div style="overflow-x:auto;">
        <table class="req-table" style="width:100%; border-collapse:collapse;">
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
                        <td style="font-weight:600; color:var(--text);">{{ $req->requester_name ?? '—' }}</td>
                        <td>
                            <div style="font-weight:500; color:var(--text);">{{ $req->product_name }}</div>
                            @if($req->product_code)
                                <div style="font-size:12px; color:var(--text3); margin-top:2px;">Cód: {{ $req->product_code }}</div>
                            @endif
                        </td>
                        <td>{{ $req->supplier ?? '—' }}</td>
                        <td style="text-align:center; font-weight:700; color:var(--text);">{{ number_format($req->quantity,0,',','.') }}</td>
                        <td style="text-align:center;">
                            <span class="badge badge-{{ $req->urgency }}">
                                {{ $req->urgency==='alta' ? 'Alta' : ($req->urgency==='media' ? 'Média' : 'Baixa') }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <span class="badge badge-{{ $req->status }}">
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
                        <td style="text-align:center; white-space:nowrap; font-size:13px; color:var(--text3);">{{ $req->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</td>
                        <td style="text-align:center;">
                            <a href="{{ route('requests.export', $req) }}" target="_blank"
                               style="display:inline-block; background:var(--success-bg); color:var(--success-text); border:1px solid currentColor; border-radius:7px; padding:5px 12px; font-size:12px; font-weight:600; text-decoration:none;">
                                Exportar
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="padding:52px 16px; text-align:center;">
                            <p style="color:var(--text2); font-size:15px; margin:0 0 4px;">Nenhuma requisição encontrada</p>
                            <p style="color:var(--text3); font-size:13px; margin:0;">Clique em "Nova Requisição" para criar a primeira</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Mobile cards --}}
<div class="req-mobile">
    @forelse($requests as $req)
        <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:12px; padding:16px; margin-bottom:10px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                <div>
                    <div style="font-size:15px; font-weight:700; color:var(--text);">{{ $req->product_name }}</div>
                    @if($req->product_code)
                        <div style="font-size:12px; color:var(--text3); margin-top:2px;">Cód: {{ $req->product_code }}</div>
                    @endif
                </div>
                <span class="badge badge-{{ $req->status }}">
                    {{ $req->status==='aprovado' ? 'Aprovado' : ($req->status==='rejeitado' ? 'Rejeitado' : 'Pendente') }}
                </span>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:13px;">
                <div><span style="color:var(--text3);">Vendedor</span><div style="font-weight:600; color:var(--text); margin-top:2px;">{{ $req->requester_name ?? '—' }}</div></div>
                <div><span style="color:var(--text3);">Fornecedor</span><div style="font-weight:600; color:var(--text); margin-top:2px;">{{ $req->supplier ?? '—' }}</div></div>
                <div><span style="color:var(--text3);">Quantidade</span><div style="font-weight:700; color:var(--text); font-size:15px; margin-top:2px;">{{ number_format($req->quantity,0,',','.') }}</div></div>
                <div><span style="color:var(--text3);">Data</span><div style="font-weight:500; color:var(--text2); margin-top:2px; font-size:12px;">{{ $req->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</div></div>
            </div>
            <div style="margin-top:12px; padding-top:10px; border-top:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap;">
                <span class="badge badge-{{ $req->urgency }}">
                    {{ $req->urgency==='alta' ? 'Alta' : ($req->urgency==='media' ? 'Média' : 'Baixa') }}
                </span>
                <a href="{{ route('requests.export', $req) }}" target="_blank"
                   style="background:var(--success-bg); color:var(--success-text); border:1px solid currentColor; border-radius:7px; padding:6px 14px; font-size:13px; font-weight:600; text-decoration:none;">
                    Exportar
                </a>
            </div>
        </div>
    @empty
        <div style="text-align:center; padding:52px 16px; background:var(--bg-card); border:1px solid var(--border); border-radius:12px;">
            <p style="color:var(--text2); font-size:15px; margin:0 0 4px;">Nenhuma requisição encontrada</p>
            <p style="color:var(--text3); font-size:13px; margin:0;">Clique em "Nova Requisição" para criar a primeira</p>
        </div>
    @endforelse
</div>

{{-- Pagination --}}
@if($requests->hasPages())
    <div style="margin-top:20px; display:flex; justify-content:center;">
        {{ $requests->links() }}
    </div>
@endif

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
