{{-- Pagamentos já registrados de uma compra, com o botão de estornar, e os estornados (riscados) para o histórico. --}}
@foreach($compra->pagamentos->sortBy('data_pagamento') as $pg)
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; font-size:12.5px; color:#4b5563; padding:2px 0;">
        <span>{{ $pg->data_pagamento->format('d/m/Y') }}</span>
        <span style="background:#e0e7ff; color:#3730a3; padding:1px 8px; border-radius:20px; font-size:11.5px; font-weight:600;">{{ $pg->forma === 'a_vista' ? 'À vista' : 'Parcelado' }}</span>
        <strong style="color:#111827;">{{ \App\Support\Dinheiro::brl($pg->valor) }}</strong>
        @if($pg->meioRotulo())<span style="color:#374151;">{{ $pg->meioRotulo() }}@if($pg->banco) · {{ $pg->banco }}@endif</span>@endif
        @if($pg->obs)<span style="color:#6b7280;">· {{ $pg->obs }}</span>@endif
        <span style="color:#9ca3af;">por {{ $pg->user?->name ?? '—' }}</span>
        {{-- O motivo é pedido na hora; sem motivo, nada é enviado. --}}
        <form method="POST" action="{{ route('financeiro.estornar', $pg) }}" style="display:inline;"
              onsubmit="var m = prompt('Estornar este pagamento de {{ \App\Support\Dinheiro::brl($pg->valor) }}? O valor volta para o saldo.\n\nMotivo do estorno:'); if (!m || !m.trim()) { return false; } this.motivo.value = m; return protegerEnvioDuplo(this);">
            @csrf
            <input type="hidden" name="motivo" value="">
            <button type="submit" style="background:none; border:none; color:#dc2626; font-size:12px; cursor:pointer; text-decoration:underline; padding:0;">Estornar</button>
        </form>
    </div>
    @include('financeiro._folhas-cheque', ['cheques' => $pg->cheques])
@endforeach
@foreach($compra->pagamentosEstornados->sortBy('data_pagamento') as $pg)
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; font-size:12.5px; color:#9ca3af; padding:2px 0;">
        <span style="text-decoration:line-through;">{{ $pg->data_pagamento->format('d/m/Y') }} · {{ \App\Support\Dinheiro::brl($pg->valor) }}@if($pg->meioRotulo()) · {{ $pg->meioRotulo() }}@endif</span>
        <span style="background:#fee2e2; color:#b91c1c; padding:1px 8px; border-radius:20px; font-size:11.5px; font-weight:600;">Estornado</span>
        <span>em {{ $pg->estornado_em->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }} por {{ $pg->estornadoPor?->name ?? '—' }}: {{ $pg->motivo_estorno }}</span>
    </div>
    @include('financeiro._folhas-cheque', ['cheques' => $pg->cheques])
@endforeach
