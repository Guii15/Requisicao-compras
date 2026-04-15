@extends('layouts.app')

@section('fullcontent')

<style>
.cr-wrapper {
    min-height: calc(100vh - 64px);
    display: flex;
    flex-direction: row;
}
.cr-left {
    width: 42%;
    background: #05018D;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 48px 48px 40px;
    position: relative;
    overflow: hidden;
    flex-shrink: 0;
}
.cr-left-inner {
    position: relative;
    z-index: 1;
    text-align: center;
    color: #fff;
    max-width: 320px;
    width: 100%;
}
.cr-left-desc { display: block; }
.cr-left-divider { display: block; }
.cr-right {
    flex: 1;
    background: #f8fafc;
    display: flex;
    align-items: stretch;
    overflow: hidden;
    min-width: 0;
}
.cr-form-col {
    flex: 1;
    overflow-y: auto;
    display: flex;
    align-items: flex-start;
    justify-content: flex-start;
    padding: 40px 32px;
    min-width: 0;
}
.cr-history-col {
    width: 260px;
    border-left: 2px solid #e5e7eb;
    background: #f8fafc;
    overflow-y: auto;
    padding: 28px 18px;
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .cr-wrapper { flex-direction: column; }

    .cr-left {
        width: 100%;
        padding: 20px 24px;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }
    .cr-left-inner {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 16px;
        text-align: left;
        max-width: 100%;
    }
    .cr-left-inner h1 { font-size: 16px !important; margin: 0 !important; }
    .cr-left-inner img { max-width: 90px !important; max-height: 40px !important; margin: 0 !important; }
    .cr-left-desc { display: none; }
    .cr-left-divider { display: none; }
    .cr-stats { gap: 16px !important; }
    .cr-stats p:first-child { font-size: 18px !important; }
    .cr-circles { display: none; }

    .cr-right { flex-direction: column; overflow: visible; }

    .cr-form-col {
        padding: 24px 16px;
        overflow-y: visible;
    }

    .cr-history-col {
        width: 100%;
        border-left: none;
        border-top: 2px solid #e5e7eb;
        padding: 20px 16px;
    }
}
</style>

<div class="cr-wrapper">

    {{-- ESQUERDA: Painel da marca --}}
    <div class="cr-left">

        {{-- Círculos decorativos --}}
        <div class="cr-circles" style="position:absolute; bottom:-100px; right:-100px; width:380px; height:380px; border-radius:50%; background:rgba(180,0,0,0.18); pointer-events:none;"></div>
        <div class="cr-circles" style="position:absolute; top:-80px; left:-80px; width:280px; height:280px; border-radius:50%; background:rgba(255,255,255,0.04); pointer-events:none;"></div>

        <div class="cr-left-inner">

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

            <div>
                <h1 style="font-size:28px; font-weight:800; margin:0 0 10px; letter-spacing:-0.5px; color:#fff;">Requisição<br>de Compras</h1>
                <p class="cr-left-desc" style="font-size:14px; color:rgba(255,255,255,0.6); line-height:1.7; margin:0 0 40px;">
                    Solicite produtos ao setor de compras de forma rápida e organizada.
                </p>

                {{-- Linha divisória --}}
                <div class="cr-left-divider" style="width:48px; height:3px; background:linear-gradient(90deg,#fff,#b40000); border-radius:2px; margin:0 auto 32px;"></div>

                {{-- Stats --}}
                <div class="cr-stats" style="display:flex; gap:24px; justify-content:center;">
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
            </div>

        </div>
    </div>

    {{-- DIREITA: Formulário + Histórico --}}
    <div class="cr-right">

        {{-- Coluna do formulário --}}
        <div class="cr-form-col">
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

                    @php
                    $inputStyle = "width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 13px; font-size:14px; color:#374151; box-sizing:border-box; outline:none; background:#fff;";
                    $labelStyle = "display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase; letter-spacing:0.5px;";
                    @endphp

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:20px;">

                        <div style="grid-column:1/-1;">
                            <label style="{{ $labelStyle }}">Nome do Vendedor <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="requester_name" value="{{ old('requester_name', auth()->user()->name) }}" required
                                   style="{{ $inputStyle }}"
                                   onfocus="this.style.borderColor='#05018D'; this.style.boxShadow='0 0 0 3px rgba(5,1,141,0.08)'"
                                   onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                        </div>

                        <div>
                            <label style="{{ $labelStyle }}">Fornecedor <span style="color:#9ca3af; font-weight:400; text-transform:none;">(opcional)</span></label>
                            <input type="text" name="supplier" value="{{ old('supplier') }}" placeholder="Ex: Bomvink, GPJ..."
                                   style="{{ $inputStyle }}"
                                   onfocus="this.style.borderColor='#05018D'; this.style.boxShadow='0 0 0 3px rgba(5,1,141,0.08)'"
                                   onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                        </div>

                        <div>
                            <label style="{{ $labelStyle }}">Urgência <span style="color:#ef4444;">*</span></label>
                            <select name="urgency" required style="{{ $inputStyle }}"
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
                            <label style="{{ $labelStyle }}">Obs <span style="color:#ef4444;">*</span> <span style="color:#9ca3af; font-weight:400; text-transform:none;">(filial 1 ou 31, etc.)</span></label>
                            <textarea name="justification" rows="2" placeholder="Ex: Filial 31, pedido urgente..." required
                                      style="{{ $inputStyle }} resize:none; font-family:inherit;"
                                      onfocus="this.style.borderColor='#05018D'; this.style.boxShadow='0 0 0 3px rgba(5,1,141,0.08)'"
                                      onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">{{ old('justification') }}</textarea>
                        </div>

                    </div>

                    {{-- Área de adicionar produto --}}
                    <div style="margin-bottom:8px;">
                        <label style="{{ $labelStyle }}">Produtos <span style="color:#ef4444;">*</span></label>
                        <div style="display:grid; grid-template-columns:110px 1fr 70px auto; gap:8px; align-items:center;">
                            <input type="text" id="inp-code" placeholder="Código"
                                   style="{{ $inputStyle }}"
                                   onfocus="this.style.borderColor='#05018D'" onblur="this.style.borderColor='#e5e7eb'">
                            <input type="text" id="inp-name" placeholder="Nome do produto"
                                   style="{{ $inputStyle }}"
                                   onfocus="this.style.borderColor='#05018D'" onblur="this.style.borderColor='#e5e7eb'"
                                   onkeydown="if(event.key==='Enter'){event.preventDefault();addItem();}">
                            <input type="number" id="inp-qty" placeholder="Qtd" min="1" value="1"
                                   style="{{ $inputStyle }} text-align:center;"
                                   onfocus="this.style.borderColor='#05018D'" onblur="this.style.borderColor='#e5e7eb'">
                            <button type="button" onclick="addItem()"
                                    style="padding:10px 16px; background:#05018D; color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; white-space:nowrap;">
                                + Adicionar
                            </button>
                        </div>
                    </div>

                    {{-- Lista de produtos adicionados --}}
                    <div id="products-list" style="background:#f8fafc; border:1.5px solid #e5e7eb; border-radius:8px; overflow:hidden; margin-bottom:20px; min-height:48px;">
                        <div style="display:grid; grid-template-columns:110px 1fr 60px 36px; background:#05018D; padding:8px 12px;">
                            <span style="font-size:11px; font-weight:700; color:#fff; text-transform:uppercase;">Código</span>
                            <span style="font-size:11px; font-weight:700; color:#fff; text-transform:uppercase;">Produto</span>
                            <span style="font-size:11px; font-weight:700; color:#fff; text-transform:uppercase; text-align:center;">Qtd</span>
                            <span></span>
                        </div>
                        <div id="products-body">
                            <div id="empty-msg" style="padding:16px; text-align:center; color:#9ca3af; font-size:13px;">
                                Nenhum produto adicionado ainda
                            </div>
                        </div>
                    </div>

                    {{-- Hidden inputs gerados por JS --}}
                    <div id="hidden-inputs"></div>

                    <script>
                    let items = [];

                    function addItem() {
                        const code = document.getElementById('inp-code').value.trim();
                        const name = document.getElementById('inp-name').value.trim();
                        const qty  = parseInt(document.getElementById('inp-qty').value) || 1;

                        if (!name) {
                            document.getElementById('inp-name').style.borderColor = '#ef4444';
                            document.getElementById('inp-name').focus();
                            return;
                        }

                        items.push({ code, name, qty });
                        renderList();

                        document.getElementById('inp-code').value = '';
                        document.getElementById('inp-name').value = '';
                        document.getElementById('inp-qty').value  = '1';
                        document.getElementById('inp-code').focus();
                    }

                    function removeItem(index) {
                        items.splice(index, 1);
                        renderList();
                    }

                    function renderList() {
                        const body   = document.getElementById('products-body');
                        const hidden = document.getElementById('hidden-inputs');
                        const empty  = document.getElementById('empty-msg');

                        body.innerHTML = '';
                        hidden.innerHTML = '';

                        if (items.length === 0) {
                            body.innerHTML = '<div id="empty-msg" style="padding:16px; text-align:center; color:#9ca3af; font-size:13px;">Nenhum produto adicionado ainda</div>';
                            return;
                        }

                        items.forEach((item, i) => {
                            const row = document.createElement('div');
                            row.style.cssText = 'display:grid; grid-template-columns:110px 1fr 60px 36px; border-bottom:1px solid #f1f5f9; background:' + (i%2===0?'#fff':'#fafafa') + ';';
                            row.innerHTML = `
                                <span style="padding:9px 12px; font-size:13px; color:#6b7280;">${item.code || '—'}</span>
                                <span style="padding:9px 12px; font-size:13px; font-weight:500; color:#374151;">${item.name}</span>
                                <span style="padding:9px 12px; font-size:13px; text-align:center; font-weight:700; color:#374151;">${item.qty}</span>
                                <button type="button" onclick="removeItem(${i})" style="border:none; background:transparent; color:#d1d5db; font-size:18px; cursor:pointer; padding:0 10px;">×</button>
                            `;
                            body.appendChild(row);

                            hidden.innerHTML += `
                                <input type="hidden" name="products[${i}][product_code]" value="${item.code}">
                                <input type="hidden" name="products[${i}][product_name]" value="${item.name}">
                                <input type="hidden" name="products[${i}][quantity]"     value="${item.qty}">
                            `;
                        });
                    }

                    document.querySelector('form').addEventListener('submit', function(e) {
                        if (items.length === 0) {
                            e.preventDefault();
                            document.getElementById('inp-name').style.borderColor = '#ef4444';
                            document.getElementById('inp-name').focus();
                            alert('Adicione pelo menos um produto antes de enviar.');
                        }
                    });
                    </script>

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

        {{-- Coluna do histórico (direita) --}}
        <div class="cr-history-col">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
                <div style="width:3px; height:16px; background:linear-gradient(180deg,#05018D,#b40000); border-radius:2px;"></div>
                <p style="margin:0; font-size:12px; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:0.5px;">Últimas Requisições</p>
            </div>
            @forelse($recentes as $req)
                <div style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:12px 14px; margin-bottom:10px; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                    <div style="font-size:13px; font-weight:600; color:#1e3a8a; margin-bottom:6px;">{{ $req->product_name }}</div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <span style="font-size:12px; color:#6b7280;">Qtd: <strong>{{ $req->quantity }}</strong></span>
                        <span style="font-size:11px; color:#9ca3af;">{{ $req->created_at->format('d/m/Y') }}</span>
                    </div>
                    @if($req->status=='aprovado')
                        <span style="background:#dcfce7; color:#16a34a; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700;">Aprovado</span>
                    @elseif($req->status=='rejeitado')
                        <span style="background:#fee2e2; color:#dc2626; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700;">Rejeitado</span>
                    @else
                        <span style="background:#fef3c7; color:#d97706; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700;">Pendente</span>
                    @endif
                </div>
            @empty
                <p style="font-size:13px; color:#d1d5db; text-align:center; margin-top:40px;">Nenhuma requisição ainda</p>
            @endforelse
        </div>

    </div>

</div>

@endsection
