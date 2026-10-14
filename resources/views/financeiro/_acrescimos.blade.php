{{-- Acréscimos já lançados de uma compra, com o botão de remover. --}}
@foreach($compra->acrescimos->sortBy('created_at') as $ac)
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; font-size:12.5px; color:#4b5563; padding:2px 0;">
        <span>{{ $ac->created_at->timezone('America/Sao_Paulo')->format('d/m/Y') }}</span>
        <span style="background:#fef3c7; color:#92400e; padding:1px 8px; border-radius:20px; font-size:11.5px; font-weight:600;">Acréscimo</span>
        <strong style="color:#111827;">+ {{ \App\Support\Dinheiro::brl($ac->valor) }}</strong>
        <span style="color:#374151;">{{ $ac->motivo }}</span>
        <span style="color:#9ca3af;">por {{ $ac->user?->name ?? '—' }}</span>
        <form method="POST" action="{{ route('financeiro.removerAcrescimo', $ac) }}" style="display:inline;"
              onsubmit="return confirm('Remover este acréscimo de {{ \App\Support\Dinheiro::brl($ac->valor) }}?')">
            @csrf
            @method('DELETE')
            <button type="submit" style="background:none; border:none; color:#dc2626; font-size:12px; cursor:pointer; text-decoration:underline; padding:0;">Remover</button>
        </form>
    </div>
@endforeach
