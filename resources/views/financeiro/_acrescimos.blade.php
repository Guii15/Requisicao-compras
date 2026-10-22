{{-- Acréscimos já lançados de uma compra, com o botão de estornar, e os estornados (riscados) para o histórico. --}}
@foreach($compra->acrescimos->sortBy('created_at') as $ac)
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; font-size:12.5px; color:#4b5563; padding:2px 0;">
        <span>{{ $ac->created_at->timezone('America/Sao_Paulo')->format('d/m/Y') }}</span>
        <span style="background:#fef3c7; color:#92400e; padding:1px 8px; border-radius:20px; font-size:11.5px; font-weight:600;">Acréscimo</span>
        <strong style="color:#111827;">+ {{ \App\Support\Dinheiro::brl($ac->valor) }}</strong>
        <span style="color:#374151;">{{ $ac->motivo }}</span>
        <span style="color:#9ca3af;">por {{ $ac->user?->name ?? '—' }}</span>
        <button type="button" onclick="var f = document.getElementById('estorno-acrescimo-{{ $ac->id }}'); f.style.display = 'flex'; f.querySelector('input[name=motivo]').focus();"
                style="background:none; border:none; color:#dc2626; font-size:12px; cursor:pointer; text-decoration:underline; padding:0;">Estornar</button>
    </div>
    {{-- Motivo do estorno digitado na própria tela; sem motivo, nada é enviado. --}}
    <form id="estorno-acrescimo-{{ $ac->id }}" method="POST" action="{{ route('financeiro.estornarAcrescimo', $ac) }}" onsubmit="return protegerEnvioDuplo(this)"
          style="display:{{ $errors->has('motivo') && (string) old('_acrescimo') === (string) $ac->id ? 'flex' : 'none' }}; gap:8px; align-items:center; flex-wrap:wrap; margin:4px 0 8px 18px; padding:8px 10px; border:1px solid #fca5a5; background:#fef2f2; border-radius:8px; font-size:12.5px;">
        @csrf
        <input type="hidden" name="_acrescimo" value="{{ $ac->id }}">
        <span style="color:#991b1b; font-weight:600;">Estornar o acréscimo de {{ \App\Support\Dinheiro::brl($ac->valor) }}?</span>
        <input type="text" name="motivo" maxlength="500" required placeholder="Motivo do estorno (obrigatório)" autocomplete="off"
               style="flex:1; min-width:220px; border:1px solid #cbd5e1; border-radius:6px; padding:6px 10px; font-size:12.5px; background:#fff;">
        <button type="submit" style="padding:6px 14px; border-radius:6px; background:#dc2626; color:#fff; border:none; font-size:12px; font-weight:600; cursor:pointer;">Confirmar estorno</button>
        <button type="button" onclick="this.form.style.display='none'" style="padding:6px 12px; border-radius:6px; border:1px solid #cbd5e1; background:#fff; color:#475569; font-size:12px; cursor:pointer;">Cancelar</button>
    </form>
@endforeach
@foreach($compra->acrescimosEstornados->sortBy('created_at') as $ac)
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; font-size:12.5px; color:#9ca3af; padding:2px 0;">
        <span style="text-decoration:line-through;">{{ $ac->created_at->timezone('America/Sao_Paulo')->format('d/m/Y') }} · Acréscimo + {{ \App\Support\Dinheiro::brl($ac->valor) }} · {{ $ac->motivo }}</span>
        <span style="background:#fee2e2; color:#b91c1c; padding:1px 8px; border-radius:20px; font-size:11.5px; font-weight:600;">Estornado</span>
        <span>em {{ $ac->estornado_em->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }} por {{ $ac->estornadoPor?->name ?? '—' }}: {{ $ac->motivo_estorno }}</span>
    </div>
@endforeach
