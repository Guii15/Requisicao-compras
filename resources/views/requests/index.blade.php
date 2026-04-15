@extends('layouts.app')

@section('content')

<div style="padding: 8px 0;">

    {{-- Cabeçalho --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 style="margin: 0; font-size: 24px; font-weight: 700; color: #1e3a8a;">Minhas Requisições</h1>
            <p style="margin: 4px 0 0 0; color: #6b7280; font-size: 14px;">Acompanhe todas as suas solicitações de compra</p>
        </div>
        <a href="{{ route('requests.create') }}"
           style="display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(90deg, #1d4ed8, #dc2626); color: #ffffff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px; height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nova Requisição
        </a>
    </div>

    {{-- Mensagem de sucesso --}}
    @if(session('success'))
        <div style="background: #dcfce7; color: #166534; border: 1px solid #86efac; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:18px; height:18px; flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Filtros --}}
    <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
        <p style="margin: 0 0 14px 0; font-size: 13px; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.5px;">Filtrar Requisições</p>
        <form method="GET" action="{{ route('requests.index') }}" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; align-items: end;">
            <div>
                <label style="display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">Vendedor</label>
                <input type="text" name="requester_name" value="{{ request('requester_name') }}"
                       placeholder="Nome do vendedor"
                       style="width: 100%; border: 1px solid #d1d5db; border-radius: 7px; padding: 8px 12px; font-size: 14px; box-sizing: border-box; outline: none;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">Data inicial</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       style="width: 100%; border: 1px solid #d1d5db; border-radius: 7px; padding: 8px 12px; font-size: 14px; box-sizing: border-box; outline: none;">
            </div>
            <div>
                <label style="display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px;">Data final</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       style="width: 100%; border: 1px solid #d1d5db; border-radius: 7px; padding: 8px 12px; font-size: 14px; box-sizing: border-box; outline: none;">
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="submit"
                        style="flex: 1; background: #1d4ed8; color: #ffffff; padding: 9px 16px; border: none; border-radius: 7px; font-size: 14px; font-weight: 600; cursor: pointer;">
                    Filtrar
                </button>
                <a href="{{ route('requests.index') }}"
                   style="flex: 1; background: #f3f4f6; color: #374151; padding: 9px 16px; border-radius: 7px; font-size: 14px; font-weight: 500; text-decoration: none; text-align: center; border: 1px solid #e5e7eb;">
                    Limpar
                </a>
            </div>
        </form>
    </div>

    {{-- Tabela --}}
    <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: linear-gradient(90deg, #1e3a8a, #1d4ed8);">
                        <th style="padding: 13px 16px; text-align: left; color: #ffffff; font-size: 13px; font-weight: 600; letter-spacing: 0.3px;">Vendedor</th>
                        <th style="padding: 13px 16px; text-align: left; color: #ffffff; font-size: 13px; font-weight: 600;">Produto</th>
                        <th style="padding: 13px 16px; text-align: left; color: #ffffff; font-size: 13px; font-weight: 600;">Fornecedor</th>
                        <th style="padding: 13px 16px; text-align: center; color: #ffffff; font-size: 13px; font-weight: 600;">Qtd</th>
                        <th style="padding: 13px 16px; text-align: center; color: #ffffff; font-size: 13px; font-weight: 600;">Urgência</th>
                        <th style="padding: 13px 16px; text-align: center; color: #ffffff; font-size: 13px; font-weight: 600;">Status</th>
                        <th style="padding: 13px 16px; text-align: center; color: #ffffff; font-size: 13px; font-weight: 600;">Data</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr style="border-bottom: 1px solid #f3f4f6;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 14px 16px; font-size: 14px; color: #111827; font-weight: 500;">
                                {{ $req->requester_name ?? 'Não informado' }}
                            </td>
                            <td style="padding: 14px 16px; font-size: 14px; color: #374151;">
                                {{ $req->product_name }}
                                @if($req->product_code)
                                    <span style="display: block; font-size: 12px; color: #9ca3af;">Cód: {{ $req->product_code }}</span>
                                @endif
                            </td>
                            <td style="padding: 14px 16px; font-size: 14px; color: #374151;">
                                {{ $req->supplier ?? '—' }}
                            </td>
                            <td style="padding: 14px 16px; text-align: center; font-size: 14px; color: #374151; font-weight: 600;">
                                {{ $req->quantity }}
                            </td>
                            <td style="padding: 14px 16px; text-align: center;">
                                @if($req->urgency == 'alta')
                                    <span style="background: #fee2e2; color: #dc2626; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Alta</span>
                                @elseif($req->urgency == 'media')
                                    <span style="background: #fef3c7; color: #d97706; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Média</span>
                                @elseif($req->urgency == 'baixa')
                                    <span style="background: #dcfce7; color: #16a34a; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Baixa</span>
                                @else
                                    <span style="color: #9ca3af; font-size: 13px;">—</span>
                                @endif
                            </td>
                            <td style="padding: 14px 16px; text-align: center;">
                                @if($req->status == 'aprovado')
                                    <span style="background: #dcfce7; color: #16a34a; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Aprovado</span>
                                @elseif($req->status == 'rejeitado')
                                    <span style="background: #fee2e2; color: #dc2626; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Rejeitado</span>
                                @else
                                    <span style="background: #fef3c7; color: #d97706; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Pendente</span>
                                @endif
                            </td>
                            <td style="padding: 14px 16px; text-align: center; font-size: 13px; color: #6b7280;">
                                {{ $req->created_at->format('d/m/Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 48px 16px; text-align: center;">
                                <svg xmlns="http://www.w3.org/2000/svg" style="width:48px; height:48px; color:#d1d5db; margin: 0 auto 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <p style="color: #6b7280; font-size: 15px; margin: 0 0 4px 0;">Nenhuma requisição encontrada</p>
                                <p style="color: #9ca3af; font-size: 13px; margin: 0;">Clique em "Nova Requisição" para criar a primeira</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
