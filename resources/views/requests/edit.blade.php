@extends('layouts.app')

@section('content')

<style>
@media (max-width: 640px) {
    .edit-grid-2 { grid-template-columns: 1fr !important; }
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

    <form method="POST" action="{{ route('requests.update', $purchaseRequest) }}">
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
        </div>

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

</div>

@endsection
