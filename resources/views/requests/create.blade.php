@extends('layouts.app')

@section('fullcontent')

<div style="min-height: calc(100vh - 64px); display: flex;">

    {{-- ESQUERDA: Painel da marca --}}
    <div style="width: 42%; background: #05018D; display: flex; flex-direction: column; justify-content: flex-start; align-items: center; padding: 48px 48px 40px; position: relative; overflow: hidden;">

        {{-- Círculo decorativo fundo --}}
        <div style="position:absolute; bottom:-100px; right:-100px; width:380px; height:380px; border-radius:50%; background:rgba(180,0,0,0.18); pointer-events:none;"></div>
        <div style="position:absolute; top:-80px; left:-80px; width:280px; height:280px; border-radius:50%; background:rgba(255,255,255,0.04); pointer-events:none;"></div>

        <div style="position:relative; z-index:1; text-align:center; color:#fff; max-width:320px;">

            {{-- Logo ou ícone --}}
            @if(file_exists(public_path('images/logo.png')))
                <img src="{{ asset('images/logo.png') }}" alt="Binário" style="max-width:200px; max-height:90px; object-fit:contain; margin:0 auto 28px; display:block;">
            @elseif(file_exists(public_path('imagens/logo.png')))
                <img src="{{ asset('imagens/logo.png') }}" alt="Binário" style="max-width:200px; max-height:90px; object-fit:contain; margin:0 auto 28px; display:block;">
            @else
                <div style="width:64px; height:64px; background:rgba(255,255,255,0.12); border-radius:18px; display:flex; align-items:center; justify-content:center; margin:0 auto 24px;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:32px; height:32px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
            @endif

            <h1 style="font-size:28px; font-weight:800; margin:0 0 10px; letter-spacing:-0.5px;">Requisição<br>de Compras</h1>
            <p style="font-size:14px; color:rgba(255,255,255,0.6); line-height:1.7; margin:0 0 40px;">
                Solicite produtos ao setor de compras de forma rápida e organizada.
            </p>

            {{-- Linha divisória --}}
            <div style="width:48px; height:3px; background:linear-gradient(90deg,#fff,#b40000); border-radius:2px; margin:0 auto 32px;"></div>

            {{-- Stats --}}
            <div style="display:flex; gap:24px; justify-content:center;">
                <div style="text-align:center;">
                    <p style="margin:0; font-size:26px; font-weight:800; color:#fff;">{{ $stats['total'] }}</p>
                    <p style="margin:4px 0 0; font-size:11px; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:0.5px;">Total</p>
                </div>
                <div style="width:1px; background:rgba(255,255,255,0.15);"></div>
                <div style="text-align:center;">
                    <p style="margin:0; font-size:26px; font-weight:800; color:#fbbf24;">{{ $stats['pendente'] }}</p>
                    <p style="margin:4px 0 0; font-size:11px; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:0.5px;">Pendentes</p>
                </div>
                <div style="width:1px; background:rgba(255,255,255,0.15);"></div>
                <div style="text-align:center;">
                    <p style="margin:0; font-size:26px; font-weight:800; color:#4ade80;">{{ $stats['aprovado'] }}</p>
                    <p style="margin:4px 0 0; font-size:11px; color:rgba(255,255,255,0.5); text-transform:uppercase; letter-spacing:0.5px;">Aprovadas</p>
                </div>
            </div>

            {{-- Histórico compacto --}}
            @if($recentes->count() > 0)
                <div style="margin-top:32px; width:100%; max-width:320px;">
                    <p style="margin:0 0 10px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.45); text-transform:uppercase; letter-spacing:0.8px;">Suas últimas requisições</p>
                    <div style="display:flex; flex-direction:column; gap:6px;">
                        @foreach($recentes as $req)
                        <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.12); border-radius:8px; padding:8px 12px;">
                            <div>
                                <span style="font-size:13px; font-weight:600; color:#fff;">{{ $req->product_name }}</span>
                                <span style="font-size:12px; color:rgba(255,255,255,0.5); margin-left:8px;">Qtd {{ $req->quantity }}</span>
                            </div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="font-size:11px; color:rgba(255,255,255,0.4);">{{ $req->created_at->format('d/m') }}</span>
                                @if($req->status=='aprovado')
                                    <span style="background:rgba(74,222,128,0.2); color:#4ade80; padding:2px 8px; border-radius:20px; font-size:10px; font-weight:700;">Aprovado</span>
                                @elseif($req->status=='rejeitado')
                                    <span style="background:rgba(248,113,113,0.2); color:#f87171; padding:2px 8px; border-radius:20px; font-size:10px; font-weight:700;">Rejeitado</span>
                                @else
                                    <span style="background:rgba(251,191,36,0.2); color:#fbbf24; padding:2px 8px; border-radius:20px; font-size:10px; font-weight:700;">Pendente</span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- DIREITA: Formulário limpo --}}
    <div style="flex:1; background:#f8fafc; overflow-y:auto; display:flex; align-items:center; justify-content:flex-start; padding:40px 32px;">
        <div style="width:100%; max-width:520px;">

            <h2 style="font-size:20px; font-weight:700; color:#05018D; margin:0 0 4px;">Nova Requisição</h2>
            <p style="font-size:13px; color:#9ca3af; margin:0 0 24px;">Preencha os campos abaixo para enviar ao setor de compras</p>

            @if ($errors->any())
                <div style="background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; padding:11px 15px; border-radius:8px; margin-bottom:18px; font-size:13px;">
                    <ul style="margin:0; padding-left:18px;">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('requests.store') }}" method="POST">
                @csrf

                {{-- Campo --}}
                @php
                $inputStyle = "width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 13px; font-size:14px; color:#374151; box-sizing:border-box; outline:none; background:#fff;";
                $labelStyle = "display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase; letter-spacing:0.5px;";
                @endphp

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">

                    <div style="grid-column:1/-1;">
                        <label style="{{ $labelStyle }}">Nome do Vendedor <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="requester_name" value="{{ old('requester_name', auth()->user()->name) }}" required
                               style="{{ $inputStyle }}"
                               onfocus="this.style.borderColor='#05018D'; this.style.boxShadow='0 0 0 3px rgba(5,1,141,0.08)'"
                               onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                    </div>

                    <div>
                        <label style="{{ $labelStyle }}">Nome do Produto <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="product_name" value="{{ old('product_name') }}" required
                               style="{{ $inputStyle }}"
                               onfocus="this.style.borderColor='#05018D'; this.style.boxShadow='0 0 0 3px rgba(5,1,141,0.08)'"
                               onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                    </div>

                    <div>
                        <label style="{{ $labelStyle }}">Código <span style="color:#9ca3af; font-weight:400; text-transform:none;">(opcional)</span></label>
                        <input type="text" name="product_code" value="{{ old('product_code') }}"
                               style="{{ $inputStyle }}"
                               onfocus="this.style.borderColor='#05018D'; this.style.boxShadow='0 0 0 3px rgba(5,1,141,0.08)'"
                               onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                    </div>

                    <div>
                        <label style="{{ $labelStyle }}">Quantidade <span style="color:#ef4444;">*</span></label>
                        <input type="number" name="quantity" min="1" value="{{ old('quantity') }}" required
                               style="{{ $inputStyle }}"
                               onfocus="this.style.borderColor='#05018D'; this.style.boxShadow='0 0 0 3px rgba(5,1,141,0.08)'"
                               onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                    </div>

                    <div>
                        <label style="{{ $labelStyle }}">Urgência <span style="color:#ef4444;">*</span></label>
                        <select name="urgency" required
                                style="{{ $inputStyle }}"
                                onfocus="this.style.borderColor='#05018D'; this.style.boxShadow='0 0 0 3px rgba(5,1,141,0.08)'"
                                onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                            <option value="">Selecione...</option>
                            <option value="baixa" {{ old('urgency')=='baixa' ? 'selected' : '' }}>🟢 Baixa</option>
                            <option value="media" {{ old('urgency')=='media' ? 'selected' : '' }}>🟡 Média</option>
                            <option value="alta"  {{ old('urgency')=='alta'  ? 'selected' : '' }}>🔴 Alta</option>
                        </select>
                    </div>

                    <div>
                        <label style="{{ $labelStyle }}">Motivo <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="reason" value="{{ old('reason') }}" required placeholder="Ex: Reposição de estoque"
                               style="{{ $inputStyle }}"
                               onfocus="this.style.borderColor='#05018D'; this.style.boxShadow='0 0 0 3px rgba(5,1,141,0.08)'"
                               onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                    </div>

                    <div style="grid-column:1/-1;">
                        <label style="{{ $labelStyle }}">Justificativa <span style="color:#ef4444;">*</span></label>
                        <textarea name="justification" rows="3" required placeholder="Descreva detalhadamente o motivo da solicitação..."
                                  style="{{ $inputStyle }} resize:vertical; font-family:inherit;"
                                  onfocus="this.style.borderColor='#05018D'; this.style.boxShadow='0 0 0 3px rgba(5,1,141,0.08)'"
                                  onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">{{ old('justification') }}</textarea>
                    </div>

                </div>

                <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
                    <a href="{{ route('requests.index') }}"
                       style="padding:10px 22px; border-radius:8px; border:1.5px solid #e5e7eb; background:#fff; color:#6b7280; font-size:14px; font-weight:600; text-decoration:none;">
                        Cancelar
                    </a>
                    <button type="submit"
                            style="padding:10px 28px; border-radius:8px; background:linear-gradient(90deg, #05018D 0%, #b40000 100%); color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer; box-shadow:0 3px 12px rgba(5,1,141,0.35);">
                        Enviar Requisição
                    </button>
                </div>
            </form>


        </div>
    </div>

</div>

@endsection
