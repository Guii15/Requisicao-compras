@extends('layouts.app')

@section('content')

<style>
.idx-mobile-cards { display: none; }

@media (max-width: 768px) {
    .idx-desktop-table { display: none; }
    .idx-mobile-cards  { display: block; }
    .idx-header { flex-direction: column; align-items: flex-start !important; }
    .idx-nova-btn { width: 100%; justify-content: center; }
    .idx-filters form { grid-template-columns: 1fr !important; }
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
           style="display:inline-flex; align-items:center; gap:8px; background:linear-gradient(90deg,#1d4ed8,#dc2626); color:#fff; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:600; font-size:14px; box-shadow:0 2px 6px rgba(0,0,0,0.15);">
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
                <button type="submit" style="flex:1; background:#1d4ed8; color:#fff; padding:9px 16px; border:none; border-radius:7px; font-size:14px; font-weight:600; cursor:pointer;">Filtrar</button>
                <a href="{{ route('requests.index') }}" style="flex:1; background:#f3f4f6; color:#374151; padding:9px 16px; border-radius:7px; font-size:14px; font-weight:500; text-decoration:none; text-align:center; border:1px solid #e5e7eb;">Limpar</a>
            </div>
        </form>
    </div>

    {{-- TABELA (desktop) --}}
    <div class="idx-desktop-table" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:linear-gradient(90deg,#1e3a8a,#1d4ed8);">
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Vendedor</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Produto</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Fornecedor</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Qtd</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Urgência</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Status</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Data</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr style="border-bottom:1px solid #f3f4f6;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                            <td style="padding:14px 16px; font-size:14px; color:#111827; font-weight:500;">{{ $req->requester_name ?? 'Não informado' }}</td>
                            <td style="padding:14px 16px; font-size:14px; color:#374151;">
                                {{ $req->product_name }}
                                @if($req->product_code)
                                    <span style="display:block; font-size:12px; color:#9ca3af;">Cód: {{ $req->product_code }}</span>
                                @endif
                            </td>
                            <td style="padding:14px 16px; font-size:14px; color:#374151;">{{ $req->supplier ?? '—' }}</td>
                            <td style="padding:14px 16px; text-align:center; font-size:14px; color:#374151; font-weight:600;">{{ $req->quantity }}</td>
                            <td style="padding:14px 16px; text-align:center;">
                                @if($req->urgency=='alta')
                                    <span style="background:#fee2e2; color:#dc2626; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Alta</span>
                                @elseif($req->urgency=='media')
                                    <span style="background:#fef3c7; color:#d97706; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Média</span>
                                @else
                                    <span style="background:#dcfce7; color:#16a34a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Baixa</span>
                                @endif
                            </td>
                            <td style="padding:14px 16px; text-align:center;">
                                @if($req->status=='aprovado')
                                    <span style="background:#dcfce7; color:#16a34a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Aprovado</span>
                                @elseif($req->status=='rejeitado')
                                    <span style="background:#fee2e2; color:#dc2626; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Rejeitado</span>
                                @else
                                    <span style="background:#fef3c7; color:#d97706; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Pendente</span>
                                @endif
                            </td>
                            <td style="padding:14px 16px; text-align:center; font-size:13px; color:#6b7280;">{{ $req->created_at->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding:48px 16px; text-align:center;">
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
        @forelse($requests as $req)
            <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:12px; box-shadow:0 1px 3px rgba(0,0,0,0.06);">

                {{-- Topo do card: produto + status --}}
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                    <div>
                        <div style="font-size:15px; font-weight:700; color:#1e3a8a;">{{ $req->product_name }}</div>
                        @if($req->product_code)
                            <div style="font-size:12px; color:#9ca3af; margin-top:2px;">Cód: {{ $req->product_code }}</div>
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

                {{-- Detalhes --}}
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:13px;">
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
                        <div style="font-weight:700; color:#374151; font-size:15px;">{{ $req->quantity }}</div>
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Data</span>
                        <div style="font-weight:600; color:#374151;">{{ $req->created_at->format('d/m/Y') }}</div>
                    </div>
                </div>

                {{-- Rodapé do card: urgência --}}
                <div style="margin-top:10px; padding-top:10px; border-top:1px solid #f3f4f6; display:flex; align-items:center; gap:8px;">
                    <span style="font-size:12px; color:#9ca3af;">Urgência:</span>
                    @if($req->urgency=='alta')
                        <span style="background:#fee2e2; color:#dc2626; padding:2px 10px; border-radius:20px; font-size:12px; font-weight:600;">Alta</span>
                    @elseif($req->urgency=='media')
                        <span style="background:#fef3c7; color:#d97706; padding:2px 10px; border-radius:20px; font-size:12px; font-weight:600;">Média</span>
                    @else
                        <span style="background:#dcfce7; color:#16a34a; padding:2px 10px; border-radius:20px; font-size:12px; font-weight:600;">Baixa</span>
                    @endif
                    @if($req->admin_note)
                        <span style="margin-left:auto; font-size:12px; color:#6b7280; font-style:italic;">{{ $req->admin_note }}</span>
                    @endif
                </div>

            </div>
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

@endsection
