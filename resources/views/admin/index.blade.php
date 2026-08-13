@extends('layouts.app')

@section('content')

<style>
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
    </div>

    {{-- Abas --}}
    <div style="display:flex; gap:4px; margin-bottom:24px; border-bottom:2px solid #e5e7eb;">
        <a href="{{ route('admin.index') }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0; margin-bottom:-2px;
                  background:#05018D; color:#fff; border:2px solid #05018D; border-bottom:2px solid #05018D;">
            Requisições
        </a>
        <a href="{{ route('admin.users.index') }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0; margin-bottom:-2px;
                  background:transparent; color:#6b7280; border:2px solid transparent; border-bottom:2px solid transparent;"
           onmouseover="this.style.color='#05018D'" onmouseout="this.style.color='#6b7280'">
            Usuários
        </a>
        <a href="{{ route('pendencias.index') }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0; margin-bottom:-2px;
                  background:transparent; color:#6b7280; border:2px solid transparent; border-bottom:2px solid transparent;"
           onmouseover="this.style.color='#05018D'" onmouseout="this.style.color='#6b7280'">
            📋 Pendências
        </a>
    </div>

    {{-- Sub-abas: Novas / Histórico --}}
    <div style="display:flex; gap:8px; margin-bottom:20px;">
        <a href="{{ route('admin.index', array_filter(['status' => request('status'), 'requester_name' => request('requester_name'), 'product_name' => request('product_name'), 'date_from' => request('date_from'), 'date_to' => request('date_to')])) }}"
           style="padding:6px 16px; font-size:13px; font-weight:600; text-decoration:none; border-radius:20px;
                  background:{{ $aba === 'novas' ? '#05018D' : '#f3f4f6' }}; color:{{ $aba === 'novas' ? '#fff' : '#6b7280' }};">
            Novas
        </a>
        <a href="{{ route('admin.index', array_filter(['aba' => 'historico', 'status' => request('status'), 'requester_name' => request('requester_name'), 'product_name' => request('product_name'), 'date_from' => request('date_from'), 'date_to' => request('date_to')])) }}"
           style="padding:6px 16px; font-size:13px; font-weight:600; text-decoration:none; border-radius:20px;
                  background:{{ $aba === 'historico' ? '#05018D' : '#f3f4f6' }}; color:{{ $aba === 'historico' ? '#fff' : '#6b7280' }};">
            Histórico
        </a>
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
                        <div style="height:100%; width:{{ round($v->total_gasto/$maxSpend*100) }}%; background:#059669; border-radius:2px;"></div>
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
                        <div style="height:100%; width:{{ round($s->total_gasto/$maxSupplierSpend*100) }}%; background:#2563eb; border-radius:2px;"></div>
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
                                <div style="height:100%; width:{{ round($v->total_gasto/$maxSpend*100) }}%; background:#059669; border-radius:2px;"></div>
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
                                <div style="height:100%; width:{{ round($s->total_gasto/$maxSupplierSpend*100) }}%; background:#2563eb; border-radius:2px;"></div>
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
    <div class="adm-filters" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px; margin-bottom:20px;">
        <form method="GET" action="{{ route('admin.index') }}" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:12px; align-items:end;">
            <input type="hidden" name="aba" value="{{ $aba }}">
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
                <a href="{{ route('admin.index', array_filter(['aba' => $aba !== 'novas' ? $aba : null])) }}" style="flex:1; background:#f3f4f6; color:#374151; padding:9px; border-radius:7px; font-size:13px; font-weight:500; text-decoration:none; text-align:center; border:1px solid #e5e7eb;">Limpar</a>
            </div>
        </form>
    </div>

    {{-- Datalist de fornecedores para autocomplete --}}
    <datalist id="supplier-options">
        @foreach($supplierList as $s)
            <option value="{{ $s }}">
        @endforeach
    </datalist>

    {{-- Tabela (desktop) --}}
    <div class="adm-desktop-table" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:linear-gradient(90deg,#05018D,#1d4ed8);">
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Vendedor</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Produto</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Fornecedor</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Qtd</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Urgência</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Status</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Valor</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Data</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $grupo)
                        @php
                            $primeiroAdm = $grupo->first();
                            $chaveAdm = $primeiroAdm->grupo_id;
                            $statusUnicosAdm = $grupo->pluck('status')->unique();
                            $rotuloGrupoAdm = $statusUnicosAdm->count() === 1
                                ? ['aprovado' => 'Aprovado', 'rejeitado' => 'Rejeitado', 'pendente' => 'Pendente'][$statusUnicosAdm->first()] ?? ucfirst($statusUnicosAdm->first())
                                : $grupo->countBy('status')->map(fn($qtd, $st) => $qtd . ' ' . (['aprovado' => 'aprovada(s)', 'rejeitado' => 'rejeitada(s)', 'pendente' => 'pendente(s)'][$st] ?? $st))->implode(' · ');
                        @endphp
                        <tr class="grupo-cabecalho" style="border-bottom:1px solid #e5e7eb; background:#f8fafc; cursor:pointer;" onclick="toggleGrupoRequisicao('{{ $chaveAdm }}')">
                            <td colspan="9" style="padding:12px 16px; font-size:14px; color:#05018D; font-weight:700;">
                                Requisição #{{ $primeiroAdm->id }} — {{ $primeiroAdm->requester_name ?? 'Não informado' }} —
                                {{ $grupo->count() }} item{{ $grupo->count() > 1 ? 's' : '' }} —
                                <span style="font-weight:600; color:#374151;">{{ $rotuloGrupoAdm }}</span>
                                <span id="seta-grupo-{{ $chaveAdm }}" style="float:right; color:#9ca3af;">▾ ver itens</span>
                            </td>
                        </tr>
                    @foreach($grupo as $req)
                        <tr class="grupo-item-{{ $chaveAdm }}" style="display:none; border-bottom:1px solid #f3f4f6;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                            <td style="padding:12px 16px; font-size:14px; color:#111827; font-weight:500;">{{ $req->requester_name ?? '—' }}</td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">
                                {{ $req->product_name }}
                                @if($req->product_code)
                                    <span style="display:block; font-size:11px; color:#9ca3af;">Cód: {{ $req->product_code }}</span>
                                @endif
                                @if($req->product_url)
                                    <a href="{{ $req->product_url }}" target="_blank" style="display:block; font-size:11px; color:#05018D; text-decoration:underline; margin-top:2px;">Ver link</a>
                                @endif
                            </td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">{{ $req->supplier ?? '—' }}</td>
                            <td style="padding:12px 16px; text-align:center; font-size:14px; font-weight:600; color:#374151;">{{ $req->quantity }}</td>
                            <td style="padding:12px 16px; text-align:center;">
                                @if($req->urgency=='alta')
                                    <span style="background:#fee2e2; color:#dc2626; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Alta</span>
                                @elseif($req->urgency=='media')
                                    <span style="background:#fef3c7; color:#d97706; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Média</span>
                                @else
                                    <span style="background:#dcfce7; color:#16a34a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Baixa</span>
                                @endif
                            </td>
                            <td style="padding:12px 16px; text-align:center;">
                                @if($req->status=='aprovado')
                                    <span style="background:#dcfce7; color:#16a34a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Aprovado</span>
                                @elseif($req->status=='rejeitado')
                                    <span style="background:#fee2e2; color:#dc2626; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Rejeitado</span>
                                @else
                                    <span style="background:#fef3c7; color:#d97706; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Pendente</span>
                                @endif
                            </td>
                            <td style="padding:12px 16px; text-align:center; font-size:13px; font-weight:600; color:#059669;">
                                {{ $req->valor ? 'R$ '.number_format($req->valor, 2, ',', '.') : '—' }}
                            </td>
                            <td style="padding:12px 16px; text-align:center; font-size:13px; color:#6b7280;">{{ $req->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</td>
                            <td style="padding:12px 16px; text-align:center;">
                                <div style="display:flex; gap:6px; justify-content:center;">
                                    <a href="{{ route('admin.requests.export', $req) }}" target="_blank"
                                       style="background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; border-radius:7px; padding:6px 12px; font-size:12px; font-weight:600; text-decoration:none; white-space:nowrap;">
                                        Exportar
                                    </a>
                                    <button onclick="document.getElementById('modal-{{ $req->id }}').style.display='flex'"
                                            style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:6px 14px; font-size:12px; font-weight:600; cursor:pointer; white-space:nowrap;">
                                        Atualizar
                                    </button>
                                </div>
                            </td>
                        </tr>

                        {{-- Modal --}}
                        <div id="modal-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                            <div style="background:#fff; border-radius:12px; padding:28px; width:100%; max-width:440px; margin:16px;">
                                <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Atualizar Requisição</h3>
                                <p style="margin:0 0 20px; font-size:13px; color:#9ca3af;">{{ $req->product_name }} — {{ $req->requester_name }}</p>

                                <form method="POST" action="{{ route('admin.requests.update', $req) }}">
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

                                    <div style="margin-bottom:16px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Observação <span style="color:#9ca3af; font-weight:400; text-transform:none;">(opcional)</span></label>
                                        <textarea name="admin_note" rows="3" placeholder="Ex: Aprovado, aguardando entrega..."
                                                  style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box; resize:vertical; font-family:inherit;">{{ $req->admin_note }}</textarea>
                                    </div>

                                    <div style="margin-bottom:20px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Valor Pago (R$) <span style="color:#9ca3af; font-weight:400; text-transform:none;">(opcional)</span></label>
                                        <input type="text" inputmode="decimal" name="valor" value="{{ $req->valor ? number_format($req->valor, 2, ',', '.') : '' }}" placeholder="Ex: 1.250,00" class="valor-brl"
                                               style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                    </div>

                                    <div style="display:flex; gap:10px; justify-content:flex-end;">
                                        <button type="button" onclick="document.getElementById('modal-{{ $req->id }}').style.display='none'"
                                                style="padding:9px 20px; border-radius:8px; border:1.5px solid #e5e7eb; background:#fff; color:#6b7280; font-size:14px; font-weight:600; cursor:pointer;">
                                            Cancelar
                                        </button>
                                        <button type="submit"
                                                style="padding:9px 24px; border-radius:8px; background:linear-gradient(90deg,#05018D,#b40000); color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">
                                            Salvar
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
                $rotuloGrupoAdmM = $statusUnicosAdmM->count() === 1
                    ? ['aprovado' => 'Aprovado', 'rejeitado' => 'Rejeitado', 'pendente' => 'Pendente'][$statusUnicosAdmM->first()] ?? ucfirst($statusUnicosAdmM->first())
                    : $grupo->countBy('status')->map(fn($qtd, $st) => $qtd . ' ' . (['aprovado' => 'aprovada(s)', 'rejeitado' => 'rejeitada(s)', 'pendente' => 'pendente(s)'][$st] ?? $st))->implode(' · ');
            @endphp
            <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:14px 16px; margin-bottom:12px; box-shadow:0 1px 3px rgba(0,0,0,0.06); cursor:pointer;"
                 onclick="toggleGrupoRequisicao('{{ $chaveAdmM }}')">
                <div style="font-size:14px; font-weight:700; color:#05018D;">Requisição #{{ $primeiroAdmM->id }}</div>
                <div style="font-size:13px; color:#374151; margin-top:2px;">{{ $primeiroAdmM->requester_name ?? 'Não informado' }} — {{ $grupo->count() }} item{{ $grupo->count() > 1 ? 's' : '' }}</div>
                <div style="font-size:12px; color:#6b7280; margin-top:4px;">{{ $rotuloGrupoAdmM }} <span id="seta-grupo-{{ $chaveAdmM }}" style="float:right; color:#9ca3af;">▾ ver itens</span></div>
            </div>
            @foreach($grupo as $req)
            <div class="grupo-item-{{ $chaveAdmM }}" style="display:none; background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin:-6px 0 12px 12px; box-shadow:0 1px 3px rgba(0,0,0,0.06);">

                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                    <div>
                        <div style="font-size:15px; font-weight:700; color:#05018D;">{{ $req->product_name }}</div>
                        @if($req->product_code)
                            <div style="font-size:12px; color:#9ca3af;">Cód: {{ $req->product_code }}</div>
                        @endif
                    </div>
                    @if($req->status=='aprovado')
                        <span style="background:#dcfce7; color:#16a34a; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; white-space:nowrap;">Aprovado</span>
                    @elseif($req->status=='rejeitado')
                        <span style="background:#fee2e2; color:#dc2626; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; white-space:nowrap;">Rejeitado</span>
                    @else
                        <span style="background:#fef3c7; color:#d97706; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; white-space:nowrap;">Pendente</span>
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

                <div style="display:flex; align-items:center; justify-content:space-between;">
                    @if($req->urgency=='alta')
                        <span style="background:#fee2e2; color:#dc2626; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Alta</span>
                    @elseif($req->urgency=='media')
                        <span style="background:#fef3c7; color:#d97706; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Média</span>
                    @else
                        <span style="background:#dcfce7; color:#16a34a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Baixa</span>
                    @endif
                    <div style="display:flex; gap:6px;">
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
            <div id="modal-m-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                <div style="background:#fff; border-radius:12px; padding:28px; width:100%; max-width:440px; margin:16px;">
                    <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Atualizar Requisição</h3>
                    <p style="margin:0 0 20px; font-size:13px; color:#9ca3af;">{{ $req->product_name }} — {{ $req->requester_name }}</p>
                    <form method="POST" action="{{ route('admin.requests.update', $req) }}">
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
                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Observação <span style="color:#9ca3af; font-weight:400; text-transform:none;">(opcional)</span></label>
                            <textarea name="admin_note" rows="3" placeholder="Ex: Aprovado, aguardando entrega..."
                                      style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box; resize:vertical; font-family:inherit;">{{ $req->admin_note }}</textarea>
                        </div>
                        <div style="margin-bottom:20px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Valor Pago (R$) <span style="color:#9ca3af; font-weight:400; text-transform:none;">(opcional)</span></label>
                            <input type="text" inputmode="decimal" name="valor" value="{{ $req->valor ? number_format($req->valor, 2, ',', '.') : '' }}" placeholder="Ex: 1.250,00" class="valor-brl"
                                   style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                        </div>
                        <div style="display:flex; gap:10px; justify-content:flex-end;">
                            <button type="button" onclick="document.getElementById('modal-m-{{ $req->id }}').style.display='none'"
                                    style="padding:9px 20px; border-radius:8px; border:1.5px solid #e5e7eb; background:#fff; color:#6b7280; font-size:14px; font-weight:600; cursor:pointer;">
                                Cancelar
                            </button>
                            <button type="submit"
                                    style="padding:9px 24px; border-radius:8px; background:linear-gradient(90deg,#05018D,#b40000); color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">
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
    var seta = document.getElementById('seta-grupo-' + chave);
    if (!linhas.length) return;
    var abrindo = linhas[0].style.display === 'none';
    linhas.forEach(function (linha) {
        linha.style.display = abrindo ? (linha.tagName === 'TR' ? 'table-row' : 'block') : 'none';
    });
    if (seta) seta.textContent = abrindo ? '▴ ocultar itens' : '▾ ver itens';
}
</script>

@endsection
