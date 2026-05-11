@extends('layouts.app')

@section('fullcontent')

<style>
/* ── STATS ROW (mesmo padrão do admin) ─── */
.cr-stats-row {
    display: grid; grid-template-columns: repeat(3,1fr);
    background: var(--border); gap: 1px;
    border-bottom: 0.5px solid var(--border);
}
.cr-stat-item { padding: 40px 24px; text-align: center; background: var(--bg); transition: background 0.35s; }
html.light-mode .cr-stat-item { background: var(--bg2); }
.cr-stat-number { font-family: 'Playfair Display', serif; font-size: 52px; font-weight: 900; color: var(--text); line-height: 1; }
.cr-stat-label  { margin-top: 8px; font-size: 14px; color: var(--text2); }

/* ── SECTION ─────────────────────────────── */
.cr-section-bg { background: var(--bg2); padding: 64px 24px 80px; transition: background 0.35s; }
.cr-wrap { max-width: 1100px; margin: 0 auto; }

/* ── HEADER ──────────────────────────────── */
.cr-eyebrow { font-size: 12px; font-weight: 500; letter-spacing: 0.12em; text-transform: uppercase; color: var(--accent); margin-bottom: 14px; text-align: center; }
.cr-title   { font-family: 'Playfair Display', serif; font-size: 42px; font-weight: 900; color: var(--text); line-height: 1.1; margin-bottom: 12px; text-align: center; }
.cr-subtitle { font-size: 16px; color: var(--text2); text-align: center; margin-bottom: 48px; }

/* ── GRID ────────────────────────────────── */
.cr-body { display: grid; grid-template-columns: 1fr 300px; gap: 20px; align-items: start; }

/* ── CARD (mesmo estilo admin) ───────────── */
.cr-card { background: var(--bg-card); border: 0.5px solid var(--border); border-radius: 16px; overflow: hidden; transition: background 0.35s; }
.cr-card-section { padding: 24px 28px; border-bottom: 0.5px solid var(--border); }
.cr-card-section:last-child { border-bottom: none; }
.cr-section-label {
    font-size: 11px; font-weight: 500; letter-spacing: 0.08em; text-transform: uppercase;
    color: var(--text2); margin-bottom: 18px; display: flex; align-items: center; gap: 10px;
}
.cr-section-label::after { content:''; flex:1; height:0.5px; background: var(--border); }

/* ── INPUTS ───────────────────────────────── */
.cr-input {
    width: 100%; background: var(--bg); border: 0.5px solid var(--border);
    border-radius: 8px; padding: 11px 14px; font-size: 14px; color: var(--text);
    font-family: inherit; outline: none; transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
}
html.light-mode .cr-input { background: var(--bg3); }
.cr-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(0,113,227,0.12); }
.cr-input::placeholder { color: var(--text3); }
.cr-input option { background: var(--bg); color: var(--text); }
.cr-label { display: block; font-size: 11px; font-weight: 500; color: var(--text2); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.06em; }
.cr-req { color: #ff453a; }

/* ── URGÊNCIA PILLS ───────────────────────── */
.urgency-group { display: flex; gap: 10px; }
.urgency-btn {
    flex: 1; padding: 11px 8px; border-radius: 10px; font-size: 13px; font-weight: 600;
    cursor: pointer; text-align: center; transition: all 0.2s;
    border: 0.5px solid var(--border); background: var(--bg); color: var(--text2);
    font-family: inherit;
}
.urgency-btn.u-low  { --uc: #30d158; }
.urgency-btn.u-med  { --uc: #ffd60a; }
.urgency-btn.u-high { --uc: #ff453a; }
.urgency-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: var(--uc); margin-right: 6px; vertical-align: middle; transition: background 0.2s; }
.urgency-btn.active { border-color: var(--uc); background: var(--uc); color: #000; }
.urgency-btn.u-high.active { color: #fff; }
.urgency-btn.active .urgency-dot { background: rgba(0,0,0,0.4); }
.urgency-btn.u-high.active .urgency-dot { background: rgba(255,255,255,0.5); }

/* ── PRODUTO TABLE ───────────────────────── */
.prod-add-row { display: grid; grid-template-columns: 100px 1fr 70px auto; gap: 8px; align-items: center; margin-bottom: 10px; }
.prod-hdr { display: grid; grid-template-columns: 100px 1fr 70px 38px; background: var(--accent); border-radius: 8px 8px 0 0; padding: 9px 12px; }
.prod-hdr span { font-size: 10px; font-weight: 700; color: #fff; text-transform: uppercase; letter-spacing: 0.5px; }
#products-body { border: 0.5px solid var(--border); border-top: none; border-radius: 0 0 8px 8px; min-height: 48px; background: var(--bg); }

/* ── SIDEBAR ─────────────────────────────── */
.cr-side { display: flex; flex-direction: column; gap: 16px; position: sticky; top: 72px; }
.cr-side-card { background: var(--bg-card); border: 0.5px solid var(--border); border-radius: 16px; padding: 20px; transition: background 0.35s; }
.cr-side-title { font-size: 10px; font-weight: 500; color: var(--text2); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 16px; }
.cr-mini-stat { flex: 1; text-align: center; }
.cr-mini-stat + .cr-mini-stat { border-left: 0.5px solid var(--border); }
.cr-mini-num { font-family: 'Playfair Display', serif; font-size: 28px; font-weight: 900; color: var(--text); line-height: 1; }
.cr-mini-lbl { font-size: 10px; text-transform: uppercase; letter-spacing: 0.6px; color: var(--text3); margin-top: 4px; }

/* ── ACTIONS ─────────────────────────────── */
.cr-footer { padding: 20px 28px; display: flex; justify-content: space-between; align-items: center; background: var(--bg-card); border-top: 0.5px solid var(--border); }

@media (max-width: 860px) {
    .cr-stats-row { grid-template-columns: repeat(3,1fr); }
    .cr-body { grid-template-columns: 1fr; }
    .cr-side { position: static; }
}
@media (max-width: 600px) {
    .cr-section-bg { padding: 40px 16px 60px; }
    .cr-card-section { padding: 18px 16px; }
    .cr-footer { padding: 16px; }
    .cr-title { font-size: 30px; }
    .cr-stat-number { font-size: 36px; }
    .cr-stat-item { padding: 28px 12px; }
    .prod-add-row { grid-template-columns: 1fr 60px auto; }
    #inp-code, .col-code-h, .col-code-d { display: none !important; }
    .prod-hdr { grid-template-columns: 1fr 60px 38px; }
}
</style>

{{-- STATS ROW --}}
<div class="cr-stats-row">
    <div class="cr-stat-item">
        <div class="cr-stat-number">{{ $stats['total'] }}</div>
        <div class="cr-stat-label">Total de requisições</div>
    </div>
    <div class="cr-stat-item">
        <div class="cr-stat-number" style="color:var(--warning);">{{ $stats['pendente'] }}</div>
        <div class="cr-stat-label">Aguardando aprovação</div>
    </div>
    <div class="cr-stat-item">
        <div class="cr-stat-number" style="color:var(--success);">{{ $stats['aprovado'] }}</div>
        <div class="cr-stat-label">Aprovadas</div>
    </div>
</div>

{{-- MAIN SECTION --}}
<div class="cr-section-bg">
<div class="cr-wrap">

    <div class="cr-eyebrow">Área do Vendedor</div>
    <div class="cr-title">Nova Requisição</div>
    <div class="cr-subtitle">Preencha os campos abaixo para enviar ao setor de compras</div>

    <div class="cr-body">

        {{-- FORM --}}
        <div class="cr-card">

            @if($errors->any())
                <div style="margin:24px 28px 0;background:rgba(255,59,48,0.08);color:#ff453a;border:0.5px solid #ff453a;padding:11px 15px;border-radius:8px;font-size:13px;">
                    <ul style="margin:0;padding-left:18px;">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('requests.store') }}" method="POST" id="req-form">
                @csrf

                {{-- Solicitante --}}
                <div class="cr-card-section">
                    <div class="cr-section-label">Solicitante</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div style="grid-column:1/-1;">
                            <label class="cr-label">Nome do Vendedor <span class="cr-req">*</span></label>
                            <input type="text" name="requester_name" value="{{ old('requester_name', auth()->user()->name) }}" required class="cr-input">
                        </div>
                        <div>
                            <label class="cr-label">Fornecedor <span style="color:var(--text3);font-weight:400;text-transform:none;">(opcional)</span></label>
                            <input type="text" name="supplier" value="{{ old('supplier') }}" placeholder="Ex: Bomvink, GPJ..." class="cr-input">
                        </div>
                        <div>
                            <label class="cr-label">Motivo <span class="cr-req">*</span></label>
                            <input type="text" name="reason" value="{{ old('reason') }}" required placeholder="Ex: Reposição de estoque" class="cr-input">
                        </div>
                        <div style="grid-column:1/-1;">
                            <label class="cr-label">Observação <span class="cr-req">*</span> <span style="color:var(--text3);font-weight:400;text-transform:none;">(filial, detalhes...)</span></label>
                            <textarea name="justification" rows="2" placeholder="Ex: Filial 31, pedido urgente..." required style="resize:none;font-family:inherit;" class="cr-input">{{ old('justification') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Urgência --}}
                <div class="cr-card-section">
                    <div class="cr-section-label">Urgência <span class="cr-req">*</span></div>
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

                {{-- Produtos --}}
                <div class="cr-card-section">
                    <div class="cr-section-label">Produtos <span class="cr-req">*</span></div>
                    <div class="prod-add-row">
                        <input type="text" id="inp-code" placeholder="Código" class="cr-input">
                        <input type="text" id="inp-name" placeholder="Nome do produto" class="cr-input"
                               onkeydown="if(event.key==='Enter'){event.preventDefault();addItem();}">
                        <input type="number" id="inp-qty" min="1" value="1" class="cr-input" style="text-align:center;">
                        <button type="button" onclick="addItem()"
                                style="padding:11px 16px;background:var(--accent);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;font-family:inherit;">
                            + Adicionar
                        </button>
                    </div>
                    <div style="margin-bottom:12px;">
                        <input type="url" id="inp-url" placeholder="Link do produto (opcional)" class="cr-input">
                    </div>
                    <div class="prod-hdr">
                        <span class="col-code-h">Código</span>
                        <span>Produto</span>
                        <span style="text-align:center;">Qtd</span>
                        <span></span>
                    </div>
                    <div id="products-body">
                        <div style="padding:18px;text-align:center;color:var(--text3);font-size:13px;">Nenhum produto adicionado ainda</div>
                    </div>
                </div>

                <div id="hidden-inputs"></div>

                {{-- Footer --}}
                <div class="cr-footer">
                    <a href="{{ route('requests.index') }}"
                       style="padding:10px 22px;border-radius:8px;border:0.5px solid var(--border);background:transparent;color:var(--text2);font-size:14px;font-weight:500;text-decoration:none;transition:color 0.2s;">
                        Cancelar
                    </a>
                    <button type="submit"
                            style="padding:11px 32px;border-radius:8px;background:var(--accent);color:#fff;font-size:14px;font-weight:600;border:none;cursor:pointer;font-family:inherit;box-shadow:0 4px 14px rgba(0,113,227,0.25);">
                        Enviar Requisição
                    </button>
                </div>
            </form>
        </div>

        {{-- SIDEBAR --}}
        <div class="cr-side">
            <div class="cr-side-card">
                <div class="cr-side-title">Suas Estatísticas</div>
                <div style="display:flex;">
                    <div class="cr-mini-stat">
                        <div class="cr-mini-num">{{ $stats['total'] }}</div>
                        <div class="cr-mini-lbl">Total</div>
                    </div>
                    <div class="cr-mini-stat">
                        <div class="cr-mini-num" style="color:var(--warning);">{{ $stats['pendente'] }}</div>
                        <div class="cr-mini-lbl">Pendentes</div>
                    </div>
                    <div class="cr-mini-stat">
                        <div class="cr-mini-num" style="color:var(--success);">{{ $stats['aprovado'] }}</div>
                        <div class="cr-mini-lbl">Aprovadas</div>
                    </div>
                </div>
            </div>

            <div class="cr-side-card">
                <div class="cr-side-title">Últimas Requisições</div>
                @forelse($recentes as $req)
                    <div style="padding:10px 0;border-bottom:0.5px solid var(--border);">
                        <div style="font-size:13px;font-weight:500;color:var(--text);margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $req->product_name }}</div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:11px;color:var(--text3);">{{ $req->created_at->timezone('America/Sao_Paulo')->format('d/m/Y') }}</span>
                            @if($req->status=='aprovado')
                                <span class="pill pill-success"><span class="pill-dot"></span>Aprovado</span>
                            @elseif($req->status=='rejeitado')
                                <span class="pill pill-danger"><span class="pill-dot"></span>Rejeitado</span>
                            @else
                                <span class="pill pill-warning"><span class="pill-dot"></span>Pendente</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p style="font-size:13px;color:var(--text3);text-align:center;padding:12px 0;">Nenhuma requisição ainda</p>
                @endforelse
                @if($recentes->isNotEmpty())
                    <a href="{{ route('requests.index') }}" style="display:block;text-align:center;margin-top:14px;font-size:12px;color:var(--accent);font-weight:500;text-decoration:none;">Ver todas →</a>
                @endif
            </div>
        </div>

    </div>
</div>
</div>

<style>
.pill { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
.pill-success { background: rgba(48,209,88,0.12); color: var(--success); }
.pill-warning { background: rgba(255,214,10,0.12); color: var(--warning); }
.pill-danger  { background: rgba(255,59,48,0.12);  color: var(--danger);  }
.pill-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
</style>

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
    if (!name) { const n = document.getElementById('inp-name'); n.style.borderColor='#ff453a'; n.focus(); return; }
    items.push({ code, name, qty, url });
    renderList();
    ['inp-code','inp-name','inp-url'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('inp-qty').value = '1';
    document.getElementById('inp-name').style.borderColor = '';
    document.getElementById('inp-code').focus();
}
function removeItem(i) { items.splice(i, 1); renderList(); }
function renderList() {
    const body = document.getElementById('products-body');
    const hidden = document.getElementById('hidden-inputs');
    body.innerHTML = ''; hidden.innerHTML = '';
    if (!items.length) {
        body.innerHTML = '<div style="padding:18px;text-align:center;color:var(--text3);font-size:13px;">Nenhum produto adicionado ainda</div>';
        return;
    }
    items.forEach((item, i) => {
        const row = document.createElement('div');
        row.style.cssText = 'display:grid;grid-template-columns:100px 1fr 70px 38px;border-bottom:0.5px solid var(--border);align-items:center;';
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
        alert('Selecione a urgência da requisição.');
        return;
    }
    if (!items.length) {
        e.preventDefault();
        const n = document.getElementById('inp-name');
        n.style.borderColor = '#ff453a';
        n.focus();
        alert('Adicione pelo menos um produto antes de enviar.');
    }
});
</script>

@endsection
