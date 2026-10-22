{{-- Selo de um pagamento ao fornecedor que foi dividido entre várias compras. Uso: @include('financeiro._parte-do-lote', ['pg' => $pg]) --}}
@if($pg->relationLoaded('doLote') && $pg->doLote->count() > 1)
    <span style="background:#ecfdf5; color:#065f46; padding:1px 8px; border-radius:20px; font-size:11.5px; font-weight:600;"
          title="Pagamento ao fornecedor dividido entre {{ $pg->doLote->count() }} compras: {{ $pg->doLote->pluck('purchase_request_id')->map(fn ($id) => '#' . $id)->implode(', ') }}">
        parte de um pagamento de {{ \App\Support\Dinheiro::brl($pg->doLote->sum('valor')) }} ({{ $pg->doLote->count() }} compras)
    </span>
@endif
