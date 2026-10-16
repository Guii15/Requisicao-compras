@extends('layouts.app')

@section('content')

<style>
@media (max-width: 640px) {
    .edit-grid-2 { grid-template-columns: minmax(0, 1fr) !important; }
}
</style>

<div style="padding:8px 0; max-width:720px; margin:0 auto;">

    <div style="margin-bottom:24px;">
        <a href="{{ route('requests.index') }}" style="font-size:13px; color:#6b7280; text-decoration:none; display:inline-flex; align-items:center; gap:4px; margin-bottom:12px;">
            ← Voltar para Requisições
        </a>
        <h1 style="margin:0; font-size:22px; font-weight:700; color:#1e3a8a;">Editar Requisição</h1>
        <p style="margin:4px 0 0; font-size:14px; color:#6b7280;">Só é possível editar requisições com status <strong>Pendente</strong>.</p>
    </div>

    @if($errors->any())
        <div style="background:#fee2e2; color:#dc2626; border:1px solid #fca5a5; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
            <ul style="margin:0; padding-left:18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; border:1px solid #86efac; padding:10px 14px; border-radius:8px; margin-bottom:16px; font-size:14px;">✓ {{ session('success') }}</div>
    @endif
    @if(session('aviso'))
        <div style="background:#fef3c7; color:#92400e; border:1px solid #fde68a; padding:10px 14px; border-radius:8px; margin-bottom:16px; font-size:14px;">⚠️ {{ session('aviso') }}</div>
    @endif

    <form method="POST" action="{{ route('requests.update', $purchaseRequest) }}" enctype="multipart/form-data">
        @csrf
        @method('PATCH')

        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:28px; margin-bottom:16px;">
            <h2 style="margin:0 0 20px; font-size:15px; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:0.5px;">Informações Gerais</h2>

            <div class="edit-grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                <div>
                    <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Vendedor *</label>
                    <input type="text" name="requester_name" value="{{ old('requester_name', $purchaseRequest->requester_name) }}" required
                           style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Fornecedor</label>
                    <input type="text" name="supplier" value="{{ old('supplier', $purchaseRequest->supplier) }}"
                           style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                </div>
            </div>

            <div class="edit-grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                <div>
                    <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Urgência *</label>
                    <select name="urgency" required style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                        <option value="baixa"  {{ old('urgency', $purchaseRequest->urgency) === 'baixa'  ? 'selected' : '' }}>Baixa</option>
                        <option value="media"  {{ old('urgency', $purchaseRequest->urgency) === 'media'  ? 'selected' : '' }}>Média</option>
                        <option value="alta"   {{ old('urgency', $purchaseRequest->urgency) === 'alta'   ? 'selected' : '' }}>Alta</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Motivo *</label>
                    <input type="text" name="reason" value="{{ old('reason', $purchaseRequest->reason) }}" required
                           style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                </div>
            </div>

            <div class="edit-grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                <div>
                    <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Tipo de Entrega *</label>
                    <select name="tipo_entrega" required style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                        <option value="estoque" {{ old('tipo_entrega', $purchaseRequest->tipo_entrega) === 'estoque' ? 'selected' : '' }}>Estoque (CD)</option>
                        <option value="entrega_direta" {{ old('tipo_entrega', $purchaseRequest->tipo_entrega) === 'entrega_direta' ? 'selected' : '' }}>Venda Casada</option>
                    </select>
                </div>
                <div></div>
            </div>

            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Observação *</label>
                <textarea name="justification" rows="3" required
                          style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box; resize:vertical; font-family:inherit;">{{ old('justification', $purchaseRequest->justification) }}</textarea>
            </div>
        </div>

        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:28px; margin-bottom:24px;">
            <h2 style="margin:0 0 20px; font-size:15px; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:0.5px;">Produto</h2>

            <div class="edit-grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                <div>
                    <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Nome do produto *</label>
                    <input type="text" name="product_name" value="{{ old('product_name', $purchaseRequest->product_name) }}" required
                           style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Código do produto</label>
                    <input type="text" name="product_code" value="{{ old('product_code', $purchaseRequest->product_code) }}"
                           style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                </div>
            </div>

            <div class="edit-grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div>
                    <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">URL do produto</label>
                    <input type="url" name="product_url" value="{{ old('product_url', $purchaseRequest->product_url) }}"
                           placeholder="https://..."
                           style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Quantidade *</label>
                    <input type="number" name="quantity" value="{{ old('quantity', $purchaseRequest->quantity) }}" min="1" required
                           style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                </div>
            </div>

            <div style="margin-top:16px;">
                <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Anexo <span style="color:#9ca3af; font-weight:400;">(opcional — orçamento, print, cotação... PDF ou imagem)</span></label>
                @if($purchaseRequest->anexo_path)
                    <div style="margin-bottom:6px;"><a href="{{ route('requests.anexo', $purchaseRequest) }}" target="_blank" style="color:#05018D; font-weight:600; font-size:13px;">📎 {{ $purchaseRequest->anexo_nome }}</a> <span style="color:#9ca3af; font-size:12px;">(envie outro pra substituir)</span> <button type="submit" form="rm-anexo" onclick="return confirm('Remover este anexo?')" style="background:none; border:none; color:#dc2626; font-size:12px; text-decoration:underline; cursor:pointer; padding:0; margin-left:6px;">Remover anexo</button></div>
                @endif
                <input type="file" name="anexo" accept=".pdf,.jpg,.jpeg,.png,.webp" style="width:100%; font-size:13px;">
            </div>
        </div>

        {{-- Itens novos: entram na mesma requisição, com os dados do pedido acima. --}}
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:28px; margin-bottom:24px;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:6px;">
                <h2 style="margin:0; font-size:15px; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:0.5px;">Acrescentar itens</h2>
                <button type="button" id="btn-novo-item"
                        style="padding:8px 16px; border-radius:8px; border:1.5px solid #1d4ed8; background:#eff6ff; color:#1d4ed8; font-size:13px; font-weight:700; cursor:pointer;">
                    + Adicionar item
                </button>
            </div>
            <p style="margin:0 0 4px; font-size:13px; color:#6b7280;">Os itens novos entram nesta mesma requisição, com o mesmo vendedor, motivo, urgência e entrega.</p>
            <div id="novos-itens"></div>
        </div>

        <template id="tpl-novo-item">
            <div class="novo-item" style="border:1px dashed #cbd5e1; border-radius:10px; padding:16px; margin-top:14px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <strong class="novo-item-titulo" style="font-size:13px; color:#1e3a8a;">Item novo</strong>
                    <button type="button" class="rm-novo-item" style="background:none; border:none; color:#dc2626; font-size:12px; text-decoration:underline; cursor:pointer; padding:0;">Tirar</button>
                </div>
                <div class="edit-grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Nome do produto *</label>
                        <input type="text" data-campo="product_name" required style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Código do produto</label>
                        <input type="text" data-campo="product_code" style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                    </div>
                </div>
                <div class="edit-grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">URL do produto</label>
                        <input type="text" data-campo="product_url" placeholder="https://..." style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Quantidade *</label>
                        <input type="number" data-campo="quantity" min="1" value="1" required style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                    </div>
                </div>
                <div>
                    <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Anexo <span style="color:#9ca3af; font-weight:400;">(opcional)</span></label>
                    <input type="file" data-campo="anexo" accept=".pdf,.jpg,.jpeg,.png,.webp" style="width:100%; font-size:13px;">
                </div>
            </div>
        </template>

        <div style="display:flex; gap:12px; justify-content:flex-end;">
            <a href="{{ route('requests.index') }}"
               style="padding:11px 24px; border-radius:8px; border:1.5px solid #e5e7eb; background:#fff; color:#6b7280; font-size:14px; font-weight:600; text-decoration:none;">
                Cancelar
            </a>
            <button type="submit"
                    style="padding:11px 28px; border-radius:8px; background:linear-gradient(90deg,#1d4ed8,#dc2626); color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">
                Salvar alterações
            </button>
        </div>

    </form>

    @if($purchaseRequest->anexo_path)
        <form id="rm-anexo" method="POST" action="{{ route('requests.anexo.remover', $purchaseRequest) }}" style="display:none;">
            @csrf
            @method('DELETE')
        </form>
    @endif

</div>

<script>
(function () {
    var lista = document.getElementById('novos-itens');
    var tpl = document.getElementById('tpl-novo-item');
    var proximo = 0;

    function renumerar() {
        lista.querySelectorAll('.novo-item-titulo').forEach(function (t, i) { t.textContent = 'Item novo ' + (i + 1); });
    }

    document.getElementById('btn-novo-item').addEventListener('click', function () {
        var i = proximo++;
        var bloco = tpl.content.firstElementChild.cloneNode(true);
        bloco.querySelectorAll('[data-campo]').forEach(function (el) {
            el.name = 'novos_itens[' + i + '][' + el.dataset.campo + ']';
        });
        bloco.querySelector('.rm-novo-item').addEventListener('click', function () { bloco.remove(); renumerar(); });
        lista.appendChild(bloco);
        renumerar();
        bloco.querySelector('[data-campo="product_name"]').focus();
    });
})();
</script>

@endsection
