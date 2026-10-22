{{-- Folhas de cheque de um pagamento, logo abaixo dele. Uso: @include('financeiro._folhas-cheque', ['cheques' => $pg->cheques]) --}}
@if($cheques->isNotEmpty())
    <div style="display:flex; gap:6px; flex-wrap:wrap; padding:2px 0 6px 18px; font-size:12px; color:#4b5563;">
        <span style="color:#6b7280;">{{ $cheques->count() === 1 ? 'Folha' : $cheques->count() . ' folhas' }}:</span>
        @foreach($cheques as $ch)
            <span style="border:1px solid {{ $ch->situacao === 'cancelado' ? '#fca5a5' : '#cbd5e1' }}; border-radius:6px; padding:1px 8px; background:#fff; {{ $ch->situacao === 'cancelado' ? 'text-decoration:line-through; color:#9ca3af;' : '' }}"
                  title="{{ $ch->banco }} · bom para {{ $ch->bom_para->format('d/m/Y') }}{{ $ch->situacao === 'cancelado' ? ' · cancelado' : '' }}">
                nº {{ $ch->numeroFormatado() }} · {{ \App\Support\Dinheiro::brl($ch->valor) }}
            </span>
        @endforeach
    </div>
@endif
