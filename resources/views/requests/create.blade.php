@extends('layouts.app')

@section('content')

<style>
.cr-container {
    max-width: 1080px;
    margin: 0 auto;
    padding: 12px 16px 40px;
}
.cr-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 24px 28px;
    margin-bottom: 20px;
}
.cr-section-title {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0 0 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.cr-field-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 6px;
}
.cr-input {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 9px 12px;
    font-size: 13.5px;
    color: #0f172a;
    background: #ffffff;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
    box-sizing: border-box;
}
.cr-input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}
html.dark .cr-card { background: var(--sl-card); border-color: var(--sl-line); }
html.dark .cr-section-title { color: var(--sl-bone); }
html.dark .cr-section-title > span { background: var(--sl-panel) !important; color: var(--sl-fog) !important; }
html.dark .cr-field-label { color: var(--sl-fog); }
@media (max-width: 768px) {
    .cr-grid-main { grid-template-columns: 1fr !important; }
    .cr-prod-form { grid-template-columns: 1fr !important; }
    .cr-prod-row { grid-template-columns: 1fr 60px 40px 40px !important; }
    .col-code { display: none !important; }
}
</style>

<div class="cr-container">

    {{-- Topo sóbrio --}}
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <div>
            <div style="display:flex; align-items:center; gap:8px;">
                <a href="{{ auth()->user()->isVendedor() ? route('requests.index') : route('admin.index') }}" style="color:#64748b; text-decoration:none; font-size:13px;">
                    ← Voltar
                </a>
            </div>
            <h1 style="margin:4px 0 0; font-size:22px; font-weight:700; color:#0f172a;">Nova Requisição de Compra</h1>
            <p style="margin:2px 0 0; font-size:13px; color:#64748b;">Preencha os dados e produtos que necessitam de cotação ou compra.</p>
        </div>

        {{-- Resumo rápido discreto --}}
        <div style="display:flex; gap:12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:8px 14px;">
            <div style="text-align:right;">
                <span style="font-size:11px; color:#64748b; text-transform:uppercase;">Minhas Requisições</span>
                <div style="font-size:13px; font-weight:700; color:#0f172a;">{{ $stats['total'] }} total · <span style="color:#d97706;">{{ $stats['pendente'] }} pendentes</span></div>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:13px;">
            <strong style="display:block; margin-bottom:4px;">Verifique os erros abaixo:</strong>
            <ul style="margin:0; padding-left:18px;">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('requests.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- 1. Dados da Solicitação --}}
        <div class="cr-card">
            <div class="cr-section-title">
                <span style="display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; border-radius:50%; background:#f1f5f9; color:#475569; font-size:11px;">1</span>
                Informações da Requisição
            </div>

            <div class="cr-grid-main" style="display:grid; grid-template-columns: repeat(3, 1fr); gap:14px; margin-bottom:14px;">
                <div>
                    <label class="cr-field-label">Vendedor Solicitante <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="requester_name" value="{{ old('requester_name', auth()->user()->name) }}" required class="cr-input">
                </div>

                <div>
                    <label class="cr-field-label">Fornecedor Sugerido <span style="font-weight:400; color:#94a3b8;">(opcional)</span></label>
                    <input type="text" name="supplier" value="{{ old('supplier') }}" placeholder="Ex: Bomvink, GPJ..." class="cr-input">
                </div>

                <div>
                    <label class="cr-field-label">Prioridade / Urgência <span style="color:#dc2626;">*</span></label>
                    <select name="urgency" required class="cr-input">
                        <option value="">Selecione...</option>
                        <option value="baixa" {{ old('urgency')=='baixa' ? 'selected' : '' }}>Baixa (Reposição normal)</option>
                        <option value="media" {{ old('urgency')=='media' ? 'selected' : '' }}>Média (Estoque baixo)</option>
                        <option value="alta"  {{ old('urgency')=='alta'  ? 'selected' : '' }}>Alta (Cliente aguardando / Crítico)</option>
                    </select>
                </div>
            </div>

            <div class="cr-grid-main" style="display:grid; grid-template-columns: 1fr 1fr; gap:14px; margin-bottom:14px;">
                <div>
                    <label class="cr-field-label">Motivo da Solicitação <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="reason" value="{{ old('reason') }}" required placeholder="Ex: Reposição de estoque, pedido especial de cliente..." class="cr-input">
                </div>

                <div>
                    <label class="cr-field-label">Tipo de Entrega <span style="color:#dc2626;">*</span></label>
                    <select name="tipo_entrega" required class="cr-input">
                        <option value="estoque" {{ old('tipo_entrega', 'estoque')=='estoque' ? 'selected' : '' }}>Estoque (Centro de Distribuição)</option>
                        <option value="entrega_direta" {{ old('tipo_entrega')=='entrega_direta' ? 'selected' : '' }}>Venda Casada (Direto ao cliente)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="cr-field-label">
                    Observações e Instruções da Compra <span style="color:#dc2626;">*</span>
                    <span style="font-weight:400; color:#94a3b8;">(especifique filial 1 ou 31, dados do pedido ou detalhes importantes)</span>
                </label>
                <textarea name="justification" rows="2" required placeholder="Ex: Filial 31; cliente tem pressa na liberação; faturar junto com pedido X..."
                          class="cr-input" style="resize:vertical; font-family:inherit;">{{ old('justification') }}</textarea>
            </div>
        </div>

        {{-- 2. Itens Solicitados --}}
        <div class="cr-card">
            <div class="cr-section-title">
                <span style="display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; border-radius:50%; background:#f1f5f9; color:#475569; font-size:11px;">2</span>
                Itens da Requisição
            </div>

            {{-- Formulário de adicionar item --}}
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px; margin-bottom:16px;">
                <div class="cr-prod-form" style="display:grid; grid-template-columns: 130px 1fr 90px auto; gap:10px; align-items:end;">
                    <div>
                        <label class="cr-field-label" style="font-size:11px;">Código</label>
                        <input type="text" id="inp-code" placeholder="Ex: 10423" class="cr-input">
                    </div>
                    <div>
                        <label class="cr-field-label" style="font-size:11px;">Nome / Descrição do Produto <span style="color:#dc2626;">*</span></label>
                        <input type="text" id="inp-name" placeholder="Nome exato ou especificação" class="cr-input"
                               onkeydown="if(event.key==='Enter'){event.preventDefault();addItem();}">
                    </div>
                    <div>
                        <label class="cr-field-label" style="font-size:11px;">Quantidade</label>
                        <input type="number" id="inp-qty" min="1" value="1" class="cr-input" style="text-align:center;">
                    </div>
                    <div>
                        <button type="button" onclick="addItem()"
                                style="padding:9px 18px; background:#0f172a; color:#ffffff; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                            <span>+</span> Adicionar Item
                        </button>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:10px;">
                    <div>
                        <label class="cr-field-label" style="font-size:11px;">Link / Referência externa <span style="font-weight:400; color:#94a3b8;">(opcional)</span></label>
                        <input type="url" id="inp-url" placeholder="https://..." class="cr-input">
                    </div>
                    <div>
                        <label class="cr-field-label" style="font-size:11px;">Anexo / Cotação / Print <span style="font-weight:400; color:#94a3b8;">(PDF ou imagem)</span></label>
                        <input type="file" id="inp-anexo" accept=".pdf,.jpg,.jpeg,.png,.webp" class="cr-input" style="padding:6px;">
                    </div>
                </div>
            </div>

            {{-- Tabela de produtos adicionados --}}
            <div style="border:1px solid #e2e8f0; border-radius:8px; overflow:hidden;">
                <div style="display:grid; grid-template-columns:120px 1fr 80px 44px 44px; background:#f1f5f9; border-bottom:1px solid #e2e8f0; padding:9px 14px; font-size:11.5px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:0.5px;">
                    <span class="col-code">Código</span>
                    <span>Item</span>
                    <span style="text-align:center;">Qtd</span>
                    <span></span>
                    <span></span>
                </div>
                <div id="products-body">
                    <div id="empty-msg" style="padding:24px; text-align:center; color:#94a3b8; font-size:13px;">
                        Nenhum produto adicionado. Preencha os campos acima e clique em <strong>+ Adicionar Item</strong>.
                    </div>
                </div>
            </div>

            {{-- Inputs hidden gerados dinamicamente --}}
            <div id="hidden-inputs"></div>
        </div>

        {{-- Ações de envio --}}
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <a href="{{ auth()->user()->isVendedor() ? route('requests.index') : route('admin.index') }}"
               style="padding:10px 20px; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; color:#475569; font-size:13.5px; font-weight:600; text-decoration:none;">
                Cancelar
            </a>
            <button type="submit"
                    style="padding:10px 24px; border-radius:6px; background:#2563eb; color:#ffffff; font-size:13.5px; font-weight:600; border:none; cursor:pointer; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                Enviar Requisição
            </button>
        </div>
    </form>

</div>

<script>
let items = [];

function addItem() {
    const code = document.getElementById('inp-code').value.trim();
    const name = document.getElementById('inp-name').value.trim();
    const qty  = parseInt(document.getElementById('inp-qty').value) || 1;
    let url    = document.getElementById('inp-url').value.trim();
    if (url && !/^https?:\/\//i.test(url)) url = 'https://' + url;
    const anexoInput = document.getElementById('inp-anexo');
    const anexoFile = anexoInput.files[0] || null;

    if (!name) {
        document.getElementById('inp-name').focus();
        return;
    }

    items.push({ code, name, qty, url, anexoFile });
    renderList();

    document.getElementById('inp-code').value = '';
    document.getElementById('inp-name').value = '';
    document.getElementById('inp-qty').value  = '1';
    document.getElementById('inp-url').value  = '';
    anexoInput.value = '';
    document.getElementById('inp-name').focus();
}

function removeItem(index) {
    items.splice(index, 1);
    renderList();
}

function editItem(index) {
    const item = items[index];
    document.getElementById('inp-code').value = item.code;
    document.getElementById('inp-name').value = item.name;
    document.getElementById('inp-qty').value  = item.qty;
    document.getElementById('inp-url').value  = item.url || '';

    const anexoInput = document.getElementById('inp-anexo');
    anexoInput.value = '';
    if (item.anexoFile) {
        const dt = new DataTransfer();
        dt.items.add(item.anexoFile);
        anexoInput.files = dt.files;
    }

    items.splice(index, 1);
    renderList();
    document.getElementById('inp-name').focus();
}

function renderList() {
    const body   = document.getElementById('products-body');
    const hidden = document.getElementById('hidden-inputs');

    body.innerHTML = '';
    hidden.innerHTML = '';

    if (items.length === 0) {
        body.innerHTML = '<div id="empty-msg" style="padding:24px; text-align:center; color:#94a3b8; font-size:13px;">Nenhum produto adicionado. Preencha os campos acima e clique em <strong>+ Adicionar Item</strong>.</div>';
        return;
    }

    items.forEach((item, i) => {
        const row = document.createElement('div');
        row.className = 'cr-prod-row';
        row.style.cssText = 'display:grid; grid-template-columns:120px 1fr 80px 44px 44px; align-items:center; border-bottom:1px solid #f1f5f9; padding:8px 14px; background:' + (i % 2 === 0 ? '#ffffff' : '#fafafa') + ';';
        row.innerHTML = `
            <span class="col-code" style="font-size:13px; color:#64748b; font-family:monospace;">${item.code || '—'}</span>
            <div style="font-size:13.5px; color:#0f172a; font-weight:500;">
                ${item.name}
                ${item.url ? '<a href="' + item.url + '" target="_blank" style="display:inline-block; margin-left:6px; font-size:11.5px; color:#2563eb; text-decoration:none;">🔗 Link</a>' : ''}
                ${item.anexoFile ? '<span style="display:inline-block; margin-left:6px; font-size:11.5px; color:#64748b;">📎 ' + item.anexoFile.name + '</span>' : ''}
            </div>
            <span style="font-size:13.5px; text-align:center; font-weight:700; color:#0f172a;">${item.qty}</span>
            <button type="button" onclick="editItem(${i})" title="Editar" style="border:none; background:transparent; color:#64748b; font-size:13px; cursor:pointer; padding:4px;">✏️</button>
            <button type="button" onclick="removeItem(${i})" title="Remover" style="border:none; background:transparent; color:#94a3b8; font-size:16px; cursor:pointer; padding:4px;">✕</button>
        `;
        body.appendChild(row);

        const inpCode = document.createElement('input');
        inpCode.type = 'hidden';
        inpCode.name = `products[${i}][product_code]`;
        inpCode.value = item.code;
        hidden.appendChild(inpCode);

        const inpName = document.createElement('input');
        inpName.type = 'hidden';
        inpName.name = `products[${i}][product_name]`;
        inpName.value = item.name;
        hidden.appendChild(inpName);

        const inpQty = document.createElement('input');
        inpQty.type = 'hidden';
        inpQty.name = `products[${i}][quantity]`;
        inpQty.value = item.qty;
        hidden.appendChild(inpQty);

        if (item.url) {
            const inpUrl = document.createElement('input');
            inpUrl.type = 'hidden';
            inpUrl.name = `products[${i}][product_url]`;
            inpUrl.value = item.url;
            hidden.appendChild(inpUrl);
        }

        if (item.anexoFile) {
            const inpAnexo = document.createElement('input');
            inpAnexo.type = 'file';
            inpAnexo.name = `products[${i}][anexo]`;
            inpAnexo.style.display = 'none';
            const dt = new DataTransfer();
            dt.items.add(item.anexoFile);
            inpAnexo.files = dt.files;
            hidden.appendChild(inpAnexo);
        }
    });
}

document.querySelector('form').addEventListener('submit', function(e) {
    if (items.length === 0) {
        e.preventDefault();
        document.getElementById('inp-name').focus();
        alert('Adicione pelo menos um item à requisição antes de enviar.');
    }
});
</script>

@endsection
