@extends('layouts.app')

@section('fullcontent')

<style>
.cr-page { background: var(--bg2); min-height: calc(100vh - 60px); padding: 48px 24px 80px; transition: background 0.35s; }
.cr-inner { max-width: 1100px; margin: 0 auto; }

.cr-header { margin-bottom: 36px; }
.cr-eyebrow {
    font-size: 12px; font-weight: 500; letter-spacing: 0.12em;
    text-transform: uppercase; color: var(--accent); margin-bottom: 10px;
}
.cr-title {
    font-family: 'Playfair Display', serif; font-size: 36px; font-weight: 900;
    color: var(--text); line-height: 1.1; margin-bottom: 6px;
}
.cr-subtitle { font-size: 15px; color: var(--text2); }

.cr-body { display: grid; grid-template-columns: 1fr 280px; gap: 24px; align-items: start; }

.cr-card {
    background: var(--bg); border: 1px solid var(--border);
    border-radius: 16px; padding: 32px 28px;
    transition: background 0.35s, border-color 0.35s;
}
html.light-mode .cr-card { background: #fff; }

.cr-input {
    width: 100%; background: var(--bg-input); border: 1px solid var(--border);
    border-radius: 8px; padding: 10px 13px; font-size: 14px; color: var(--text);
    font-family: inherit; outline: none; transition: border-color 0.2s, box-shadow 0.2s; box-sizing: border-box;
}
.cr-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(0,113,227,0.15); }
.cr-input::placeholder { color: var(--text3); }
.cr-input option { background: var(--bg3); color: var(--text); }
.cr-label { display: block; font-size: 11px; font-weight: 700; color: var(--text3); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.6px; }

.cr-history-card {
    background: var(--bg); border: 1px solid var(--border);
    border-radius: 16px; padding: 24px 20px;
    transition: background 0.35s, border-color 0.35s;
    position: sticky; top: 80px;
}
html.light-mode .cr-history-card { background: #fff; }

@media (max-width: 800px) {
    .cr-body { grid-template-columns: 1fr; }
    .cr-history-card { position: static; }
    .prod-add-row { grid-template-columns: 1fr 64px !important; }
    .add-btn-full { grid-column: 1 / -1; width: 100%; }
    .prod-list-header, .prod-list-row { grid-template-columns: 1fr 50px 36px !important; }
    .col-code-h, .col-code-d { display: none !important; }
}
@media (max-width: 600px) {
    .cr-page { padding: 24px 16px 60px; }
    #inp-code { display: none !important; }
}
</style>

<div class="cr-page">
<div class="cr-inner">

    <div class="cr-header">
        <div class="cr-eyebrow">Área do Vendedor</div>
        <div class="cr-title">Nova Requisição</div>
        <div class="cr-subtitle">Preencha os campos abaixo para enviar ao setor de compras</div>
    </div>

    <div class="cr-body">

        {{-- FORMULÁRIO --}}
        <div class="cr-card">

            @if($errors->any())
                <div style="background:var(--danger-bg);color:var(--danger-text);border:1px solid currentColor;padding:11px 15px;border-radius:8px;margin-bottom:20px;font-size:13px;">
                    <ul style="margin:0;padding-left:18px;">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('requests.store') }}" method="POST">
                @csrf

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;">

                    <div style="grid-column:1/-1;">
                        <label class="cr-label">Nome do Vendedor <span style="color:var(--danger-text);">*</span></label>
                        <input type="text" name="requester_name" value="{{ old('requester_name', auth()->user()->name) }}" required class="cr-input">
                    </div>

                    <div>
                        <label class="cr-label">Fornecedor <span style="color:var(--text3);font-weight:400;text-transform:none;">(opcional)</span></label>
                        <input type="text" name="supplier" value="{{ old('supplier') }}" placeholder="Ex: Bomvink, GPJ..." class="cr-input">
                    </div>

                    <div>
                        <label class="cr-label">Urgência <span style="color:var(--danger-text);">*</span></label>
                        <select name="urgency" required class="cr-input">
                            <option value="">Selecione...</option>
                            <option value="baixa" {{ old('urgency')=='baixa' ? 'selected' : '' }}>Baixa</option>
                            <option value="media" {{ old('urgency')=='media' ? 'selected' : '' }}>Média</option>
                            <option value="alta"  {{ old('urgency')=='alta'  ? 'selected' : '' }}>Alta</option>
                        </select>
                    </div>

                    <div>
                        <label class="cr-label">Motivo <span style="color:var(--danger-text);">*</span></label>
                        <input type="text" name="reason" value="{{ old('reason') }}" required placeholder="Ex: Reposição de estoque" class="cr-input">
                    </div>

                    <div style="grid-column:1/-1;">
                        <label class="cr-label">Observação <span style="color:var(--danger-text);">*</span> <span style="color:var(--text3);font-weight:400;text-transform:none;">(filial, detalhes...)</span></label>
                        <textarea name="justification" rows="2" placeholder="Ex: Filial 31, pedido urgente..." required
                                  style="resize:none;font-family:inherit;" class="cr-input">{{ old('justification') }}</textarea>
                    </div>

                </div>

                {{-- Adicionar produto --}}
                <div style="margin-bottom:8px;">
                    <label class="cr-label">Produtos <span style="color:var(--danger-text);">*</span></label>
                    <div class="prod-add-row" style="display:grid;grid-template-columns:110px 1fr 90px auto;gap:8px;align-items:center;">
                        <input type="text" id="inp-code" placeholder="Código" class="cr-input">
                        <input type="text" id="inp-name" placeholder="Nome do produto" class="cr-input"
                               onkeydown="if(event.key==='Enter'){event.preventDefault();addItem();}">
                        <input type="number" id="inp-qty" placeholder="Qtd" min="1" value="1" class="cr-input" style="text-align:center;">
                        <button type="button" onclick="addItem()" class="add-btn-full"
                                style="padding:10px 16px;background:var(--accent);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;font-family:inherit;">
                            + Adicionar
                        </button>
                    </div>
                    <div style="margin-top:8px;">
                        <input type="url" id="inp-url" placeholder="Link do produto (opcional)" class="cr-input">
                    </div>
                </div>

                {{-- Lista de produtos --}}
                <div id="products-list" style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;overflow:hidden;margin-bottom:24px;min-height:48px;">
                    <div class="prod-list-header" style="display:grid;grid-template-columns:110px 1fr 60px 36px;background:var(--accent);padding:8px 12px;">
                        <span class="col-code-h" style="font-size:11px;font-weight:700;color:#fff;text-transform:uppercase;">Código</span>
                        <span style="font-size:11px;font-weight:700;color:#fff;text-transform:uppercase;">Produto</span>
                        <span style="font-size:11px;font-weight:700;color:#fff;text-transform:uppercase;text-align:center;">Qtd</span>
                        <span></span>
                    </div>
                    <div id="products-body">
                        <div id="empty-msg" style="padding:16px;text-align:center;color:var(--text3);font-size:13px;">Nenhum produto adicionado ainda</div>
                    </div>
                </div>

                <div id="hidden-inputs"></div>

                <script>
                let items = [];
                function addItem() {
                    const code = document.getElementById('inp-code').value.trim();
                    const name = document.getElementById('inp-name').value.trim();
                    const qty  = parseInt(document.getElementById('inp-qty').value) || 1;
                    const url  = document.getElementById('inp-url').value.trim();
                    if (!name) { document.getElementById('inp-name').style.borderColor='#ff453a'; document.getElementById('inp-name').focus(); return; }
                    items.push({ code, name, qty, url });
                    renderList();
                    document.getElementById('inp-code').value = '';
                    document.getElementById('inp-name').value = '';
                    document.getElementById('inp-qty').value  = '1';
                    document.getElementById('inp-url').value  = '';
                    document.getElementById('inp-code').focus();
                }
                function removeItem(i) { items.splice(i, 1); renderList(); }
                function renderList() {
                    const body = document.getElementById('products-body');
                    const hidden = document.getElementById('hidden-inputs');
                    body.innerHTML = ''; hidden.innerHTML = '';
                    if (items.length === 0) {
                        body.innerHTML = '<div style="padding:16px;text-align:center;color:var(--text3);font-size:13px;">Nenhum produto adicionado ainda</div>';
                        return;
                    }
                    items.forEach((item, i) => {
                        const row = document.createElement('div');
                        row.className = 'prod-list-row';
                        row.style.cssText = 'display:grid;grid-template-columns:110px 1fr 60px 36px;border-bottom:1px solid var(--border);';
                        row.innerHTML = `
                            <span class="col-code-d" style="padding:10px 12px;font-size:12px;color:var(--text2);">${item.code||'—'}</span>
                            <span style="padding:10px 12px;font-size:13px;font-weight:500;color:var(--text);">${item.name}${item.url?`<br><a href="${item.url}" target="_blank" style="font-size:11px;color:var(--accent);">Ver link ↗</a>`:''}</span>
                            <span style="padding:10px 12px;font-size:13px;text-align:center;font-weight:700;color:var(--text);">${item.qty}</span>
                            <button type="button" onclick="removeItem(${i})" style="border:none;background:transparent;color:var(--text3);font-size:18px;cursor:pointer;padding:0 10px;">×</button>
                        `;
                        body.appendChild(row);
                        hidden.innerHTML += `
                            <input type="hidden" name="products[${i}][product_code]" value="${item.code}">
                            <input type="hidden" name="products[${i}][product_name]" value="${item.name}">
                            <input type="hidden" name="products[${i}][product_url]"  value="${item.url||''}">
                            <input type="hidden" name="products[${i}][quantity]"     value="${item.qty}">
                        `;
                    });
                }
                document.querySelector('form').addEventListener('submit', function(e) {
                    if (items.length === 0) {
                        e.preventDefault();
                        document.getElementById('inp-name').style.borderColor = '#ff453a';
                        document.getElementById('inp-name').focus();
                        alert('Adicione pelo menos um produto antes de enviar.');
                    }
                });
                </script>

                <div style="display:flex;justify-content:flex-end;gap:10px;">
                    <a href="{{ route('requests.index') }}"
                       style="padding:10px 22px;border-radius:8px;border:1px solid var(--border);background:var(--bg-input);color:var(--text2);font-size:14px;font-weight:500;text-decoration:none;">
                        Cancelar
                    </a>
                    <button type="submit"
                            style="padding:10px 28px;border-radius:8px;background:var(--accent);color:#fff;font-size:14px;font-weight:700;border:none;cursor:pointer;font-family:inherit;box-shadow:0 4px 14px rgba(0,113,227,0.3);">
                        Enviar Requisição
                    </button>
                </div>
            </form>

        </div>

        {{-- HISTÓRICO --}}
        <div class="cr-history-card">
            <p style="margin:0 0 16px;font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:0.8px;">Últimas Requisições</p>
            @forelse($recentes as $req)
                <div style="background:var(--bg2);border:1px solid var(--border);border-radius:10px;padding:12px 14px;margin-bottom:10px;">
                    <div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:6px;">{{ $req->product_name }}</div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                        <span style="font-size:12px;color:var(--text2);">Qtd: <strong style="color:var(--text);">{{ $req->quantity }}</strong></span>
                        <span style="font-size:11px;color:var(--text3);">{{ $req->created_at->timezone('America/Sao_Paulo')->format('d/m/Y') }}</span>
                    </div>
                    @if($req->status=='aprovado')
                        <span style="background:var(--badge-aprovado-bg);color:var(--badge-aprovado-text);padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;">Aprovado</span>
                    @elseif($req->status=='rejeitado')
                        <span style="background:var(--badge-rejeitado-bg);color:var(--badge-rejeitado-text);padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;">Rejeitado</span>
                    @else
                        <span style="background:var(--badge-pendente-bg);color:var(--badge-pendente-text);padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;">Pendente</span>
                    @endif
                </div>
            @empty
                <p style="font-size:13px;color:var(--text3);text-align:center;margin-top:32px;">Nenhuma requisição ainda</p>
            @endforelse
        </div>

    </div>
</div>
</div>

@endsection
