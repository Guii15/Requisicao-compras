@extends('layouts.app')

@section('content')

<div style="padding: 8px 0;">

    {{-- Cabeçalho --}}
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Painel Administrativo</h1>
            <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Gerencie todas as requisições de compra</p>
        </div>
    </div>

    {{-- Mensagem de sucesso --}}
    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; border:1px solid #86efac; padding:12px 16px; border-radius:8px; margin-bottom:20px; display:flex; align-items:center; gap:8px; font-size:14px;">
            ✓ {{ session('success') }}
        </div>
    @endif

    {{-- Stats --}}
    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:24px;">
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
    </div>

    {{-- Filtros --}}
    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px; margin-bottom:20px;">
        <form method="GET" action="{{ route('admin.index') }}" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:12px; align-items:end;">
            <div>
                <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:5px;">Vendedor</label>
                <input type="text" name="requester_name" value="{{ request('requester_name') }}" placeholder="Nome..."
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

    {{-- Tabela --}}
    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
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
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Data</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr style="border-bottom:1px solid #f3f4f6;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                            <td style="padding:12px 16px; font-size:14px; color:#111827; font-weight:500;">{{ $req->requester_name ?? '—' }}</td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">
                                {{ $req->product_name }}
                                @if($req->product_code)
                                    <span style="display:block; font-size:11px; color:#9ca3af;">Cód: {{ $req->product_code }}</span>
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
                            <td style="padding:12px 16px; text-align:center; font-size:13px; color:#6b7280;">{{ $req->created_at->format('d/m/Y') }}</td>
                            <td style="padding:12px 16px; text-align:center;">
                                <button onclick="document.getElementById('modal-{{ $req->id }}').style.display='flex'"
                                        style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:6px 14px; font-size:12px; font-weight:600; cursor:pointer;">
                                    Atualizar
                                </button>
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

                                    <div style="margin-bottom:20px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Observação <span style="color:#9ca3af; font-weight:400; text-transform:none;">(opcional)</span></label>
                                        <textarea name="admin_note" rows="3" placeholder="Ex: Aprovado, aguardando entrega..."
                                                  style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box; resize:vertical; font-family:inherit;">{{ $req->admin_note }}</textarea>
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
                    @empty
                        <tr>
                            <td colspan="8" style="padding:48px 16px; text-align:center; color:#9ca3af; font-size:15px;">
                                Nenhuma requisição encontrada
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
