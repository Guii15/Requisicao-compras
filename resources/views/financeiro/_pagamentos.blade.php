{{-- Pagamentos já registrados de uma compra, com o botão de desfazer. --}}
@foreach($compra->pagamentos->sortBy('data_pagamento') as $pg)
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; font-size:12.5px; color:#4b5563; padding:2px 0;">
        <span>{{ $pg->data_pagamento->format('d/m/Y') }}</span>
        <span style="background:#e0e7ff; color:#3730a3; padding:1px 8px; border-radius:20px; font-size:11.5px; font-weight:600;">{{ $pg->forma === 'a_vista' ? 'À vista' : 'Parcelado' }}</span>
        <strong style="color:#111827;">{{ \App\Support\Dinheiro::brl($pg->valor) }}</strong>
        @if($pg->meioRotulo())<span style="color:#374151;">{{ $pg->meioRotulo() }}@if($pg->banco) · {{ $pg->banco }}@endif</span>@endif
        @if($pg->obs)<span style="color:#6b7280;">· {{ $pg->obs }}</span>@endif
        <span style="color:#9ca3af;">por {{ $pg->user?->name ?? '—' }}</span>
        <form method="POST" action="{{ route('financeiro.desfazer', $pg) }}" style="display:inline;"
              onsubmit="return confirm('Desfazer este pagamento de {{ \App\Support\Dinheiro::brl($pg->valor) }}? O valor volta para o saldo.')">
            @csrf
            @method('DELETE')
            <button type="submit" style="background:none; border:none; color:#dc2626; font-size:12px; cursor:pointer; text-decoration:underline; padding:0;">Desfazer</button>
        </form>
    </div>
@endforeach
