@extends('layouts.app')

@section('fullcontent')

<div style="min-height: calc(100vh - 64px); display: flex;">

    {{-- Painel lateral esquerdo --}}
    <div style="width: 360px; min-height: 100%; background: linear-gradient(160deg, #0f1f5c 0%, #1d3fad 55%, #c0162b 100%); display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 48px 32px; flex-shrink: 0;">
        <div style="text-align: center; color: #ffffff; width: 100%;">

            <div style="width: 68px; height: 68px; background: rgba(255,255,255,0.15); border-radius: 18px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:34px; height:34px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>

            <h2 style="font-size: 24px; font-weight: 800; margin: 0 0 10px 0; line-height: 1.2;">Requisição<br>de Compras</h2>
            <p style="font-size: 13px; color: rgba(255,255,255,0.7); line-height: 1.6; margin: 0 0 32px 0;">
                Preencha o formulário ao lado para solicitar uma compra ao setor responsável.
            </p>

            {{-- Passos --}}
            <div style="text-align: left; display: flex; flex-direction: column; gap: 14px; margin-bottom: 36px;">
                @foreach ([
                    ['icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'title' => 'Identificação', 'desc' => 'Informe o vendedor responsável'],
                    ['icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'title' => 'Produto', 'desc' => 'Descreva o item necessário'],
                    ['icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'title' => 'Notificação automática', 'desc' => 'E-mail enviado ao setor de compras'],
                ] as $step)
                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                        <div style="width: 30px; height: 30px; background: rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width:14px; height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $step['icon'] }}" />
                            </svg>
                        </div>
                        <div>
                            <p style="margin: 0; font-size: 13px; font-weight: 600;">{{ $step['title'] }}</p>
                            <p style="margin: 2px 0 0 0; font-size: 12px; color: rgba(255,255,255,0.6);">{{ $step['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Mini estatísticas --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; border-top: 1px solid rgba(255,255,255,0.15); padding-top: 24px;">
                <div style="background: rgba(255,255,255,0.1); border-radius: 10px; padding: 12px 8px; text-align: center;">
                    <p style="margin: 0; font-size: 22px; font-weight: 800; color: #ffffff;">{{ $stats['total'] }}</p>
                    <p style="margin: 4px 0 0 0; font-size: 10px; color: rgba(255,255,255,0.65); line-height: 1.3;">Total</p>
                </div>
                <div style="background: rgba(255,255,255,0.1); border-radius: 10px; padding: 12px 8px; text-align: center;">
                    <p style="margin: 0; font-size: 22px; font-weight: 800; color: #fbbf24;">{{ $stats['pendente'] }}</p>
                    <p style="margin: 4px 0 0 0; font-size: 10px; color: rgba(255,255,255,0.65); line-height: 1.3;">Pendentes</p>
                </div>
                <div style="background: rgba(255,255,255,0.1); border-radius: 10px; padding: 12px 8px; text-align: center;">
                    <p style="margin: 0; font-size: 22px; font-weight: 800; color: #4ade80;">{{ $stats['aprovado'] }}</p>
                    <p style="margin: 4px 0 0 0; font-size: 10px; color: rgba(255,255,255,0.65); line-height: 1.3;">Aprovadas</p>
                </div>
            </div>

        </div>
    </div>

    {{-- Área principal --}}
    <div style="flex: 1; display: flex; gap: 0; overflow: hidden;">

        {{-- Formulário --}}
        <div style="flex: 1; background: #f8fafc; overflow-y: auto; padding: 36px 40px;">
            <div style="max-width: 580px;">

                <h1 style="font-size: 21px; font-weight: 700; color: #0f1f5c; margin: 0 0 4px 0;">Nova Requisição de Compra</h1>
                <p style="font-size: 13px; color: #6b7280; margin: 0 0 24px 0;">Preencha todos os campos obrigatórios</p>

                @if ($errors->any())
                    <div style="background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px;">
                        <ul style="margin: 0; padding-left: 18px;">
                            @foreach ($errors->all() as $error)
                                <li style="margin-bottom: 3px;">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('requests.store') }}" method="POST">
                    @csrf
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">

                        <div style="grid-column: 1 / -1;">
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.4px;">Nome do Vendedor <span style="color: #dc2626;">*</span></label>
                            <input type="text" name="requester_name"
                                   value="{{ old('requester_name', auth()->user()->name) }}"
                                   required
                                   style="width: 100%; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 10px 13px; font-size: 14px; box-sizing: border-box; outline: none; background: #fff;"
                                   onfocus="this.style.borderColor='#1d3fad'; this.style.boxShadow='0 0 0 3px rgba(29,63,173,0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.4px;">Nome do Produto <span style="color: #dc2626;">*</span></label>
                            <input type="text" name="product_name"
                                   value="{{ old('product_name') }}"
                                   required
                                   style="width: 100%; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 10px 13px; font-size: 14px; box-sizing: border-box; outline: none; background: #fff;"
                                   onfocus="this.style.borderColor='#1d3fad'; this.style.boxShadow='0 0 0 3px rgba(29,63,173,0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.4px;">Código <span style="color: #9ca3af; font-weight: 400; text-transform: none;">(opcional)</span></label>
                            <input type="text" name="product_code"
                                   value="{{ old('product_code') }}"
                                   style="width: 100%; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 10px 13px; font-size: 14px; box-sizing: border-box; outline: none; background: #fff;"
                                   onfocus="this.style.borderColor='#1d3fad'; this.style.boxShadow='0 0 0 3px rgba(29,63,173,0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.4px;">Quantidade <span style="color: #dc2626;">*</span></label>
                            <input type="number" name="quantity" min="1"
                                   value="{{ old('quantity') }}"
                                   required
                                   style="width: 100%; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 10px 13px; font-size: 14px; box-sizing: border-box; outline: none; background: #fff;"
                                   onfocus="this.style.borderColor='#1d3fad'; this.style.boxShadow='0 0 0 3px rgba(29,63,173,0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.4px;">Urgência <span style="color: #dc2626;">*</span></label>
                            <select name="urgency" required
                                    style="width: 100%; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 10px 13px; font-size: 14px; box-sizing: border-box; outline: none; background: #fff;"
                                    onfocus="this.style.borderColor='#1d3fad'; this.style.boxShadow='0 0 0 3px rgba(29,63,173,0.1)'"
                                    onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                                <option value="">Selecione...</option>
                                <option value="baixa" {{ old('urgency') == 'baixa' ? 'selected' : '' }}>🟢 Baixa</option>
                                <option value="media" {{ old('urgency') == 'media' ? 'selected' : '' }}>🟡 Média</option>
                                <option value="alta"  {{ old('urgency') == 'alta'  ? 'selected' : '' }}>🔴 Alta</option>
                            </select>
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.4px;">Motivo <span style="color: #dc2626;">*</span></label>
                            <input type="text" name="reason"
                                   value="{{ old('reason') }}"
                                   required
                                   placeholder="Ex: Reposição de estoque"
                                   style="width: 100%; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 10px 13px; font-size: 14px; box-sizing: border-box; outline: none; background: #fff;"
                                   onfocus="this.style.borderColor='#1d3fad'; this.style.boxShadow='0 0 0 3px rgba(29,63,173,0.1)'"
                                   onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                        </div>

                        <div style="grid-column: 1 / -1;">
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.4px;">Justificativa <span style="color: #dc2626;">*</span></label>
                            <textarea name="justification" rows="3" required
                                      placeholder="Descreva detalhadamente o motivo da solicitação..."
                                      style="width: 100%; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 10px 13px; font-size: 14px; box-sizing: border-box; outline: none; background: #fff; resize: vertical; font-family: inherit;"
                                      onfocus="this.style.borderColor='#1d3fad'; this.style.boxShadow='0 0 0 3px rgba(29,63,173,0.1)'"
                                      onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">{{ old('justification') }}</textarea>
                        </div>

                    </div>

                    <div style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 10px;">
                        <a href="{{ route('requests.index') }}"
                           style="padding: 10px 22px; border-radius: 8px; border: 1.5px solid #e2e8f0; background: #fff; color: #374151; font-size: 14px; font-weight: 600; text-decoration: none;">
                            Cancelar
                        </a>
                        <button type="submit"
                                style="padding: 10px 28px; border-radius: 8px; background: linear-gradient(90deg, #0f1f5c, #c0162b); color: #ffffff; font-size: 14px; font-weight: 700; border: none; cursor: pointer; box-shadow: 0 3px 10px rgba(15,31,92,0.25);">
                            Enviar Requisição
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Painel direito — histórico recente --}}
        <div style="width: 280px; background: #ffffff; border-left: 1px solid #e2e8f0; overflow-y: auto; padding: 28px 20px; flex-shrink: 0;">

            <p style="margin: 0 0 16px 0; font-size: 12px; font-weight: 700; color: #0f1f5c; text-transform: uppercase; letter-spacing: 0.6px;">
                Suas últimas requisições
            </p>

            @forelse($recentes as $req)
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; margin-bottom: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 6px;">
                        <p style="margin: 0; font-size: 13px; font-weight: 600; color: #1e293b; line-height: 1.3;">{{ $req->product_name }}</p>
                        @if($req->status == 'aprovado')
                            <span style="background: #dcfce7; color: #16a34a; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap;">Aprovado</span>
                        @elseif($req->status == 'rejeitado')
                            <span style="background: #fee2e2; color: #dc2626; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap;">Rejeitado</span>
                        @else
                            <span style="background: #fef3c7; color: #d97706; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap;">Pendente</span>
                        @endif
                    </div>
                    <p style="margin: 0; font-size: 12px; color: #6b7280;">
                        Qtd: <strong>{{ $req->quantity }}</strong> &nbsp;·&nbsp;
                        @if($req->urgency == 'alta')
                            <span style="color: #dc2626;">🔴 Alta</span>
                        @elseif($req->urgency == 'media')
                            <span style="color: #d97706;">🟡 Média</span>
                        @else
                            <span style="color: #16a34a;">🟢 Baixa</span>
                        @endif
                    </p>
                    <p style="margin: 6px 0 0 0; font-size: 11px; color: #9ca3af;">{{ $req->created_at->format('d/m/Y') }}</p>
                </div>
            @empty
                <div style="text-align: center; padding: 32px 16px;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:36px; height:36px; color:#d1d5db; margin: 0 auto 10px; display: block;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p style="color: #9ca3af; font-size: 13px; margin: 0;">Nenhuma requisição ainda</p>
                </div>
            @endforelse

            @if($stats['total'] > 4)
                <a href="{{ route('requests.index') }}"
                   style="display: block; text-align: center; color: #1d3fad; font-size: 13px; font-weight: 600; text-decoration: none; margin-top: 8px; padding: 8px; border-radius: 8px; background: #eff6ff;">
                    Ver todas ({{ $stats['total'] }})
                </a>
            @endif

        </div>

    </div>

</div>

@endsection
