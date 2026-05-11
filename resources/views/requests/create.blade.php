@extends('layouts.app')

@section('fullcontent')

<style>
.cr-page { background: var(--bg2); min-height: calc(100vh - 60px); padding: 48px 24px 80px; transition: background 0.35s; }
.cr-inner { max-width: 1120px; margin: 0 auto; }

/* HEADER */
.cr-header { margin-bottom: 32px; display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.cr-header-left {}
.cr-eyebrow { font-size: 11px; font-weight: 600; letter-spacing: 0.14em; text-transform: uppercase; color: var(--accent); margin-bottom: 8px; }
.cr-title { font-family: 'Playfair Display', serif; font-size: 34px; font-weight: 900; color: var(--text); line-height: 1.1; }
.cr-subtitle { font-size: 14px; color: var(--text2); margin-top: 5px; }

/* GRID */
.cr-body { display: grid; grid-template-columns: 1fr 300px; gap: 20px; align-items: start; }

/* CARDS */
.cr-card {
    background: var(--bg); border: 1px solid var(--border);
    border-radius: 16px; overflow: hidden;
    transition: background 0.35s, border-color 0.35s;
}
html.light-mode .cr-card { background: #fff; }

.cr-section { padding: 24px 28px; border-bottom: 1px solid var(--border); }
.cr-section:last-child { border-bottom: none; }
.cr-section-title {
    font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
    color: var(--text3); margin-bottom: 18px; display: flex; align-items: center; gap: 8px;
}
.cr-section-title::after { content: ''; flex: 1; height: 1px; background: var(--border); }

/* INPUTS */
.cr-input {
    width: 100%; background: var(--bg2); border: 1.5px solid var(--border);
    border-radius: 10px; padding: 11px 14px; font-size: 14px; color: var(--text);
    font-family: inherit; outline: none; transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
    box-sizing: border-box;
}
html.light-mode .cr-input { background: #f8f9fb; }
.cr-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(0,113,227,0.12); background: var(--bg); }
html.light-mode .cr-input:focus { background: #fff; }
.cr-input::placeholder { color: var(--text3); }
.cr-input option { background: var(--bg); color: var(--text); }
.cr-label { display: block; font-size: 11px; font-weight: 700; color: var(--text3); margin-bottom: 7px; text-transform: uppercase; letter-spacing: 0.6px; }
.cr-req { color: var(--danger-text); margin-left: 2px; }
.cr-opt { color: var(--text3); font-weight: 400; text-transform: none; font-size: 11px; margin-left: 3px; }

/* URGÊNCIA PILLS */
.urgency-group { display: flex; gap: 10px; }
.urgency-btn {
    flex: 1; padding: 10px 8px; border-radius: 10px; font-size: 13px; font-weight: 600;
    cursor: pointer; text-align: center; transition: all 0.2s;
    border: 1.5px solid var(--border); background: var(--bg2); color: var(--text2);
    font-family: inherit; user-select: none;
}
html.light-mode .urgency-btn { background: #f8f9fb; }
.urgency-btn:hover { border-color: currentColor; }
.urgency-btn.u-low  { --uc: #30d158; }
.urgency-btn.u-med  { --uc: #ffd60a; }
.urgency-btn.u-high { --uc: #ff453a; }
.urgency-btn.active { border-color: var(--uc); background: var(--uc); color: #000; }
.urgency-btn.u-high.active { color: #fff; }
.urgency-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: var(--uc); margin-right: 6px; vertical-align: middle; }
.urgency-btn.active .urgency-dot { background: currentColor; opacity: 0.7; }

/* PRODUTO TABLE */
.prod-header-row {
    display: grid; grid-template-columns: 100px 1fr 70px 38px;
    background: var(--accent); border-radius: 8px 8px 0 0; padding: 9px 12px;
}
.prod-header-row span { font-size: 10px; font-weight: 700; color: #fff; text-transform: uppercase; letter-spacing: 0.5px; }
#products-body { background: var(--bg2); border-radius: 0 0 8px 8px; border: 1.5px solid var(--border); border-top: none; min-height: 48px; }
html.light-mode #products-body { background: #f8f9fb; }

.prod-add-row { display: grid; grid-template-columns: 100px 1fr 70px auto; gap: 8px; align-items: center; margin-bottom: 10px; }

/* SIDEBAR */
.cr-side { display: flex; flex-direction: column; gap: 16px; position: sticky; top: 80px; }

.cr-stats-card {
    background: var(--bg); border: 1px solid var(--border);
    border-radius: 16px; padding: 20px;
    transition: background 0.35s;
}
html.light-mode .cr-stats-card { background: #fff; }
.cr-stats-row { display: flex; gap: 0; }
.cr-stat { flex: 1; text-align: center; padding: 8px 4px; }
.cr-stat + .cr-stat { border-left: 1px solid var(--border); }
.cr-stat-num { font-family: 'Playfair Display', serif; font-size: 26px; font-weight: 900; color: var(--text); line-height: 1; }
.cr-stat-lbl { font-size: 10px; text-transform: uppercase; letter-spacing: 0.6px; color: var(--text3); margin-top: 4px; }

.cr-recent-card {
    background: var(--bg); border: 1px solid var(--border);
    border-radius: 16px; padding: 20px;
    transition: background 0.35s;
}
html.light-mode .cr-recent-card { background: #fff; }

/* FOOTER ACTIONS */
.cr-actions { padding: 20px 28px; display: flex; justify-content: space-between; align-items: center; background: var(--bg); border-top: 1px solid var(--border); }
html.light-mode .cr-actions { background: #fff; }
.cr-btn-cancel {
    padding: 10px 22px; border-radius: 10px; border: 1.5px solid var(--border);
    background: transparent; color: var(--text2); font-size: 14px; font-weight: 500;
    text-decoration: none; transition: all 0.2s; font-family: inherit; cursor: pointer;
}
.cr-btn-cancel:hover { border-color: var(--text2); color: var(--text); }
.cr-btn-submit {
    padding: 11px 32px; border-radius: 10px; background: var(--accent); color: #fff;
    font-size: 14px; font-weight: 700; border: none; cursor: pointer; font-family: inherit;
    box-shadow: 0 4px 14px rgba(0,113,227,0.3); transition: opacity 0.2s, transform 0.15s;
}
.cr-btn-submit:hover { opacity: 0.9; transform: translateY(-1px); }
.cr-btn-submit:active { transform: translateY(0); }

@media (max-width: 860px) {
    .cr-body { grid-template-columns: 1fr; }
    .cr-side { position: static; }
    .urgency-group { gap: 8px; }
}
@media (max-width: 600px) {
    .cr-page { padding: 24px 16px 60px; }
    .cr-section { padding: 20px 18px; }
    .cr-actions { padding: 16px 18px; }
    .prod-add-row { grid-template-columns: 1fr 60px auto; }
    #inp-code { display: none; }
    .prod-header-row { grid-template-columns: 1fr 60px 38px; }
    .col-code-h, .col-code-d { display: none !important; }
}
</style>

<div class="cr-page">
<div class="cr-inner">

    <div class="cr-header">
        <div class="cr-header-left">
            <div class="cr-eyebrow">Área do Vendedor</div>
            <div class="cr-title">Nova Requisição</div>
            <div class="cr-subtitle">Preencha os campos para enviar ao setor de compras</div>
        </div>
    </div>

    <div class="cr-body">

        {{-- FORMULÁRIO --}}
        <div class="cr-card">

            @if($errors->any())
                <div style="margin:24px 28px 0;background:var(--danger-bg);color:var(--danger-text);border:1px solid currentColor;padding:11px 15px;border-radius:10px;font-size:13px;">
                    <ul style="margin:0;padding-left:18px;">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('requests.store') }}" method="POST" id="req-form">
                @csrf

                {{-- SEÇÃO 1: Solicitante --}}
                <div class="cr-section">
                    <div class="cr-section-title">Solicitante</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div style="grid-column:1/-1;">
                            <label class="cr-label">Nome do Vendedor<span class="cr-req">*</span></label>
                            <input type="text" name="requester_name" value="{{ old('requester_name', auth()->user()->name) }}" required class="cr-input">
                        </div>
                        <div>
                            <label class="cr-label">Fornecedor<span class="cr-opt">(opcional)</span></label>
                            <input type="text" name="supplier" value="{{ old('supplier') }}" placeholder="Ex: Bomvink, GPJ..." class="cr-input">
                        </div>
                        <div>
                            <label class="cr-label">Motivo<span class="cr-req">*</span></label>
                            <input type="text" name="reason" value="{{ old('reason') }}" required placeholder="Ex: Reposição de estoque" class="cr-input">
                        </div>
                        <div style="grid-column:1/-1;">
                            <label class="cr-label">Observação<span class="cr-req">*</span><span class="cr-opt">(filial, detalhes...)</span></label>
                            <textarea name="justification" rows="2" placeholder="Ex: Filial 31, pedido urgente..." required
                                      style="resize:none;font-family:inherit;" class="cr-input">{{ old('justification') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- SEÇÃO 2: Urgência --}}
                <div class="cr-section">
                    <div class="cr-section-title">Urgência<span class="cr-req" style="text-transform:none;">*</span></div>
                    <div class="urgency-group">
                        <button type="button" class="urgency-btn u-low  {{ old('urgency')=='baixa' ? 'active' : '' }}" onclick="setUrgency('baixa',this)">
                            <span class="urgency-dot"></span>Baixa
                        </button>
                        <button type="button" class="urgency-btn u-med  {{ old('urgency')=='media' ? 'active' : '' }}" onclick="setUrgency('media',this)">
                            <span class="urgency-dot"></span>Média
                        </button>
                        <button type="button" class="urgency-btn u-high {{ old('urgency')=='alta'  ? 'active' : '' }}" onclick="setUrgency('alta',this)">
                            <span class="urgency-dot"></span>Alta
                        </button>
                    </div>
                    <input type="hidden" name="urgency" id="inp-urgency" value="{{ old('urgency') }}" required>
                </div>

                {{-- SEÇÃO 3: Produtos --}}
                <div class="cr-section">
                    <div class="cr-section-title">Produtos<span class="cr-req" style="text-transform:none;">*</span></div>

                    <div class="prod-add-row">
                        <input type="text" id="inp-code" placeholder="Código" class="cr-input">
                        <input type="text" id="inp-name" placeholder="Nome do produto" class="cr-input"
                               onkeydown="if(event.key==='Enter'){event.preventDefault();addItem();}">
                        <input type="number" id="inp-qty" min="1" value="1" class="cr-input" style="text-align:center;">
                        <button type="button" onclick="addItem()"
                                style="padding:11px 16px;background:var(--accent);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;font-family:inherit;transition:opacity 0.2s;"
                                onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                            + Adicionar
                        </button>
                    </div>
                    <div style="margin-bottom:12px;">
                        <input type="url" id="inp-url" placeholder="Link do produto (opcional)" class="cr-input">
                    </div>

                    <div>
                        <div class="prod-header-row">
                            <span class="col-code-h">Código</span>
                            <span>Produto</span>
                            <span style="text-align:center;">Qtd</span>
                            <span></span>
                        </div>
                        <div id="products-body">
                            <div id="empty-msg" style="padding:18px;text-align:center;color:var(--text3);font-size:13px;">
                                Nenhum produto adicionado ainda
                            </div>
                        </div>
                    </div>
                </div>

                <div id="hidden-inputs"></div>

                {{-- ACTIONS --}}
                <div class="cr-actions">
                    <a href="{{ route('requests.index') }}" class="cr-btn-cancel">Cancelar</a>
                    <button type="submit" class="cr-btn-submit">Enviar Requisição</button>
                </div>

            </form>
        </div>

        {{-- SIDEBAR --}}
        <div class="cr-side">

            {{-- Mini stats --}}
            <div class="cr-stats-card">
                <div style="font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:14px;">Suas Estatísticas</div>
                <div class="cr-stats-row">
                    <div class="cr-stat">
                        <div class="cr-stat-num">{{ $stats['total'] }}</div>
                        <div class="cr-stat-lbl">Total</div>
                    </div>
                    <div class="cr-stat">
                        <div class="cr-stat-num" style="color:#ffd60a;">{{ $stats['pendente'] }}</div>
                        <div class="cr-stat-lbl">Pendentes</div>
                    </div>
                    <div class="cr-stat">
                        <div class="cr-stat-num" style="color:#30d158;">{{ $stats['aprovado'] }}</div>
                        <div class="cr-stat-lbl">Aprovadas</div>
                    </div>
                </div>
            </div>

            {{-- Últimas requisições --}}
            <div class="cr-recent-card">
                <div style="font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:14px;">Últimas Requisições</div>
                @forelse($recentes as $req)
                    <div style="padding:12px 0;border-bottom:1px solid var(--border);">
                        <div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $req->product_name }}</div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:11px;color:var(--text3);">{{ $req->created_at->timezone('America/Sao_Paulo')->format('d/m/Y') }}</span>
                            @if($req->status=='aprovado')
                                <span style="background:var(--badge-aprovado-bg);color:var(--badge-aprovado-text);padding:2px 9px;border-radius:20px;font-size:10px;font-weight:700;">Aprovado</span>
                            @elseif($req->status=='rejeitado')
                                <span style="background:var(--badge-rejeitado-bg);color:var(--badge-rejeitado-text);padding:2px 9px;border-radius:20px;font-size:10px;font-weight:700;">Rejeitado</span>
                            @else
                                <span style="background:var(--badge-pendente-bg);color:var(--badge-pendente-text);padding:2px 9px;border-radius:20px;font-size:10px;font-weight:700;">Pendente</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p style="font-size:13px;color:var(--text3);text-align:center;padding:16px 0 0;">Nenhuma requisição ainda</p>
                @endforelse
                @if($recentes->isNotEmpty())
                    <a href="{{ route('requests.index') }}" style="display:block;text-align:center;margin-top:14px;font-size:12px;color:var(--accent);font-weight:600;text-decoration:none;">Ver todas →</a>
                @endif
            </div>

        </div>
    </div>
</div>
</div>

<script>
function setUrgency(val, btn) {
    document.querySelectorAll('.urgency-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('inp-urgency').value = val;
}

let items = [];
function addItem() {
    const code = document.getElementById('inp-code').value.trim();
    const name = document.getElementById('inp-name').value.trim();
    const qty  = parseInt(document.getElementById('inp-qty').value) || 1;
    const url  = document.getElementById('inp-url').value.trim();
    if (!name) {
        const n = document.getElementById('inp-name');
        n.style.borderColor = '#ff453a';
        n.focus();
        return;
    }
    items.push({ code, name, qty, url });
    renderList();
    document.getElementById('inp-code').value = '';
    document.getElementById('inp-name').value = '';
    document.getElementById('inp-qty').value  = '1';
    document.getElementById('inp-url').value  = '';
    document.getElementById('inp-name').style.borderColor = '';
    document.getElementById('inp-code').focus();
}

function removeItem(i) { items.splice(i, 1); renderList(); }

function renderList() {
    const body   = document.getElementById('products-body');
    const hidden = document.getElementById('hidden-inputs');
    body.innerHTML = ''; hidden.innerHTML = '';
    if (items.length === 0) {
        body.innerHTML = '<div style="padding:18px;text-align:center;color:var(--text3);font-size:13px;">Nenhum produto adicionado ainda</div>';
        return;
    }
    items.forEach((item, i) => {
        const row = document.createElement('div');
        row.style.cssText = 'display:grid;grid-template-columns:100px 1fr 70px 38px;border-bottom:1px solid var(--border);align-items:center;';
        row.innerHTML = `
            <span class="col-code-d" style="padding:10px 12px;font-size:12px;color:var(--text2);">${item.code||'—'}</span>
            <span style="padding:10px 12px;font-size:13px;font-weight:500;color:var(--text);">${item.name}${item.url?`<br><a href="${item.url}" target="_blank" style="font-size:11px;color:var(--accent);">Ver link ↗</a>`:''}</span>
            <span style="padding:10px 12px;font-size:13px;text-align:center;font-weight:700;color:var(--text);">${item.qty}</span>
            <button type="button" onclick="removeItem(${i})" style="border:none;background:transparent;color:var(--text3);font-size:18px;cursor:pointer;padding:0 10px;line-height:1;">×</button>
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

document.getElementById('req-form').addEventListener('submit', function(e) {
    if (!document.getElementById('inp-urgency').value) {
        e.preventDefault();
        document.querySelectorAll('.urgency-btn')[0].scrollIntoView({ behavior: 'smooth' });
        alert('Selecione a urgência da requisição.');
        return;
    }
    if (items.length === 0) {
        e.preventDefault();
        const n = document.getElementById('inp-name');
        n.style.borderColor = '#ff453a';
        n.focus();
        alert('Adicione pelo menos um produto antes de enviar.');
    }
});
</script>

@endsection
