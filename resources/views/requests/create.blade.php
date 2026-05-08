@extends('layouts.app')

@section('fullcontent')

<style>
.cr-wrapper { min-height: calc(100vh - 60px); display: flex; flex-direction: row; }
.cr-left {
    width: 38%; background: linear-gradient(160deg, #05018D 0%, #0a0fa8 60%, #0071e3 100%);
    display: flex; flex-direction: column; justify-content: center; align-items: center;
    padding: 48px 40px; position: relative; overflow: hidden; flex-shrink: 0;
}
.cr-left-inner { position: relative; z-index: 1; text-align: center; color: #fff; max-width: 300px; width: 100%; }
.cr-right { flex: 1; background: var(--bg2); display: flex; align-items: stretch; overflow: hidden; min-width: 0; border-left: 1px solid var(--border); }
.cr-form-col { flex: 1; overflow-y: auto; padding: 40px 36px; min-width: 0; }
.cr-history-col { width: 240px; border-left: 1px solid var(--border); background: var(--bg2); overflow-y: auto; padding: 28px 18px; flex-shrink: 0; }

.cr-input {
    width: 100%; background: var(--bg-input); border: 1px solid var(--border);
    border-radius: 8px; padding: 10px 13px; font-size: 14px; color: var(--text);
    font-family: inherit; outline: none; transition: border-color 0.2s, box-shadow 0.2s; box-sizing: border-box;
}
.cr-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(0,113,227,0.15); }
.cr-input::placeholder { color: var(--text3); }
.cr-input option { background: var(--bg3); color: var(--text); }
.cr-label { display: block; font-size: 11px; font-weight: 700; color: var(--text3); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.6px; }

@media (max-width: 768px) {
    .cr-wrapper { flex-direction: column; }
    .cr-left { width: 100%; padding: 20px 24px; flex-direction: row; justify-content: flex-start; align-items: center; flex-shrink: 0; min-height: auto; }
    .cr-left-inner { display: flex; flex-direction: row; align-items: center; gap: 16px; text-align: left; max-width: 100%; }
    .cr-left-inner h2 { font-size: 16px !important; margin: 0 !important; }
    .cr-left-inner img { max-width: 90px !important; max-height: 40px !important; margin: 0 !important; }
    .cr-left-desc, .cr-left-divider, .cr-stats, .cr-circles { display: none !important; }
    .cr-right { flex-direction: column; overflow: visible; }
    .cr-form-col { padding: 24px 16px; overflow-y: visible; }
    .cr-history-col { width: 100%; border-left: none; border-top: 1px solid var(--border); padding: 20px 16px; }
    .prod-add-row { grid-template-columns: 1fr 64px !important; }
    .add-btn-full { grid-column: 1 / -1; width: 100%; }
    .prod-list-header, .prod-list-row { grid-template-columns: 1fr 50px 36px !important; }
    .col-code-h, .col-code-d { display: none !important; }
}
@media (max-width: 600px) {
    #inp-code { display: none !important; }
}
</style>

<div class="cr-wrapper">

    {{-- ESQUERDA --}}
    <div class="cr-left">
        <div class="cr-circles" style="position:absolute;bottom:-100px;right:-100px;width:360px;height:360px;border-radius:50%;background:rgba(255,255,255,0.05);pointer-events:none;"></div>
        <div class="cr-circles" style="position:absolute;top:-80px;left:-80px;width:260px;height:260px;border-radius:50%;background:rgba(255,255,255,0.03);pointer-events:none;"></div>

        <div class="cr-left-inner">
            @if(file_exists(public_path('imagens/logo.png')))
                <img src="{{ asset('imagens/logo.png') }}" alt="Binário" style="max-width:180px;max-height:80px;object-fit:contain;margin:0 auto 28px;display:block;">
            @else
                <div style="font-size:26px;font-weight:800;letter-spacing:-0.5px;margin-bottom:8px;color:#fff;">Binário<span style="color:rgba(255,255,255,0.5);">.</span></div>
            @endif

            <h2 class="cr-left-desc" style="font-size:22px;font-weight:700;margin:0 0 10px;color:#fff;letter-spacing:-0.3px;">Requisição<br>de Compras</h2>
            <p class="cr-left-desc" style="font-size:13px;color:rgba(255,255,255,0.55);line-height:1.7;margin:0 0 32px;">Solicite produtos ao setor de compras de forma rápida e organizada.</p>

            <div class="cr-left-divider" style="width:40px;height:2px;background:rgba(255,255,255,0.3);border-radius:2px;margin:0 auto 28px;"></div>

            <div class="cr-stats" style="display:flex;gap:20px;justify-content:center;">
                <div style="text-align:center;">
                    <p style="margin:0;font-size:28px;font-weight:800;color:#fff;">{{ $stats['total'] }}</p>
                    <p style="margin:4px 0 0;font-size:11px;color:rgba(255,255,255,0.45);text-transform:uppercase;letter-spacing:0.5px;">Total</p>
                </div>
                <div style="width:1px;background:rgba(255,255,255,0.15);"></div>
                <div style="text-align:center;">
                    <p style="margin:0;font-size:28px;font-weight:800;color:#ffd60a;">{{ $stats['pendente'] }}</p>
                    <p style="margin:4px 0 0;font-size:11px;color:rgba(255,255,255,0.45);text-transform:uppercase;letter-spacing:0.5px;">Pendentes</p>
                </div>
                <div style="width:1px;background:rgba(255,255,255,0.15);"></div>
                <div style="text-align:center;">
                    <p style="margin:0;font-size:28px;font-weight:800;color:#30d158;">{{ $stats['aprovado'] }}</p>
                    <p style="margin:4px 0 0;font-size:11px;color:rgba(255,255,255,0.45);text-transform:uppercase;letter-spacing:0.5px;">Aprovadas</p>
                </div>
            </div>
        </div>
    </div>

    {{-- DIREITA --}}
    <div class="cr-right">

        {{-- Formulário --}}
        <div class="cr-form-col">
            <div style="width:100%;max-width:520px;">

                <h2 style="font-size:20px;font-weight:700;color:var(--text);margin:0 0 4px;letter-spacing:-0.3px;">Nova Requisição</h2>
                <p style="font-size:13px;color:var(--text2);margin:0 0 24px;">Preencha os campos abaixo para enviar ao setor de compras</p>

                @if($errors->any())
                    <div style="background:var(--danger-bg);color:var(--danger-text);border:1px solid currentColor;padding:11px 15px;border-radius:8px;margin-bottom:18px;font-size:13px;">
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
                                    style="padding:10px 16px;background:linear-gradient(135deg,#05018D,var(--accent));color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;font-family:inherit;">
                                + Adicionar
                            </button>
                        </div>
                        <div style="margin-top:8px;">
                            <input type="url" id="inp-url" placeholder="Link do produto (opcional)" class="cr-input">
                        </div>
                    </div>

                    {{-- Lista de produtos --}}
                    <div id="products-list" style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;overflow:hidden;margin-bottom:20px;min-height:48px;">
                        <div class="prod-list-header" style="display:grid;grid-template-columns:110px 1fr 60px 36px;background:linear-gradient(90deg,#05018D,var(--accent));padding:8px 12px;">
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

                    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:4px;">
                        <a href="{{ route('requests.index') }}"
                           style="padding:10px 22px;border-radius:8px;border:1px solid var(--border);background:var(--bg-input);color:var(--text2);font-size:14px;font-weight:500;text-decoration:none;">
                            Cancelar
                        </a>
                        <button type="submit"
                                style="padding:10px 28px;border-radius:8px;background:linear-gradient(135deg,#05018D,var(--accent));color:#fff;font-size:14px;font-weight:700;border:none;cursor:pointer;font-family:inherit;box-shadow:0 4px 14px rgba(0,113,227,0.3);">
                            Enviar Requisição
                        </button>
                    </div>
                </form>

            </div>
        </div>

        {{-- Histórico --}}
        <div class="cr-history-col">
            <p style="margin:0 0 16px;font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:0.8px;">Últimas Requisições</p>
            @forelse($recentes as $req)
                <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:12px 14px;margin-bottom:10px;">
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

@endsection
