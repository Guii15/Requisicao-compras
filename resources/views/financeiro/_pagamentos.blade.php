{{-- Pagamentos já registrados de uma compra, com o botão de estornar, e os estornados (riscados) para o histórico. --}}
@foreach($compra->pagamentos->sortBy('data_pagamento') as $pg)
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; font-size:12.5px; color:#4b5563; padding:2px 0;">
        <span>{{ $pg->data_pagamento->format('d/m/Y') }}</span>
        <span style="background:#e0e7ff; color:#3730a3; padding:1px 8px; border-radius:20px; font-size:11.5px; font-weight:600;">{{ $pg->forma === 'a_vista' ? 'À vista' : 'Parcelado' }}</span>
        <strong style="color:#111827;">{{ \App\Support\Dinheiro::brl($pg->valor) }}</strong>
        @if($pg->meioRotulo())<span style="color:#374151;">{{ $pg->meioRotulo() }}@if($pg->banco) · {{ $pg->banco }}@endif</span>@endif
        @if($pg->obs)<span style="color:#6b7280;">· {{ $pg->obs }}</span>@endif
        @include('financeiro._parte-do-lote', ['pg' => $pg])
        <span style="color:#9ca3af;">por {{ $pg->user?->name ?? '—' }}</span>
        <button type="button" onclick="var f = document.getElementById('estorno-{{ $pg->id }}'); f.style.display = 'flex'; f.querySelector('input[name=motivo]').focus();"
                style="background:none; border:none; color:#dc2626; font-size:12px; cursor:pointer; text-decoration:underline; padding:0;">Estornar</button>
    </div>
    {{-- Motivo do estorno digitado na própria tela; sem motivo, nada é enviado. --}}
    <form id="estorno-{{ $pg->id }}" method="POST" action="{{ route('financeiro.estornar', $pg) }}" onsubmit="return protegerEnvioDuplo(this)"
          style="display:{{ $errors->has('motivo') && (string) old('_pagamento') === (string) $pg->id ? 'flex' : 'none' }}; gap:8px; align-items:center; flex-wrap:wrap; margin:4px 0 8px 18px; padding:8px 10px; border:1px solid #fca5a5; background:#fef2f2; border-radius:8px; font-size:12.5px;">
        @csrf
        <input type="hidden" name="_pagamento" value="{{ $pg->id }}">
        <span style="color:#991b1b; font-weight:600;">@php $lote = $pg->relationLoaded('doLote') ? $pg->doLote->whereNull('estornado_em') : collect([$pg]); @endphp
            Estornar {{ \App\Support\Dinheiro::brl($lote->sum('valor')) }}{{ $lote->count() > 1 ? ' (o pagamento inteiro, ' . $lote->count() . ' compras)' : '' }}{{ $pg->cheques->isEmpty() ? '' : ' e cancelar ' . ($pg->cheques->count() === 1 ? 'a folha' : 'as ' . $pg->cheques->count() . ' folhas') . ' de cheque' }}?</span>
        <input type="text" name="motivo" maxlength="500" required placeholder="Motivo do estorno (obrigatório)" autocomplete="off"
               style="flex:1; min-width:220px; border:1px solid #cbd5e1; border-radius:6px; padding:6px 10px; font-size:12.5px; background:#fff;">
        <button type="submit" style="padding:6px 14px; border-radius:6px; background:#dc2626; color:#fff; border:none; font-size:12px; font-weight:600; cursor:pointer;">Confirmar estorno</button>
        <button type="button" onclick="this.form.style.display='none'" style="padding:6px 12px; border-radius:6px; border:1px solid #cbd5e1; background:#fff; color:#475569; font-size:12px; cursor:pointer;">Cancelar</button>
    </form>
    @include('financeiro._folhas-cheque', ['cheques' => $pg->cheques])
@endforeach
@foreach($compra->pagamentosEstornados->sortBy('data_pagamento') as $pg)
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; font-size:12.5px; color:#9ca3af; padding:2px 0;">
        <span style="text-decoration:line-through;">{{ $pg->data_pagamento->format('d/m/Y') }} · {{ \App\Support\Dinheiro::brl($pg->valor) }}@if($pg->meioRotulo()) · {{ $pg->meioRotulo() }}@endif</span>
        <span style="background:#fee2e2; color:#b91c1c; padding:1px 8px; border-radius:20px; font-size:11.5px; font-weight:600;">Estornado</span>
        @include('financeiro._parte-do-lote', ['pg' => $pg])
        <span>em {{ $pg->estornado_em->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }} por {{ $pg->estornadoPor?->name ?? '—' }}: {{ $pg->motivo_estorno }}</span>
    </div>
    @include('financeiro._folhas-cheque', ['cheques' => $pg->cheques])
@endforeach
