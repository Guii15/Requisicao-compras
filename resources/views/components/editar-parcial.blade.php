@props(['item'])

@if($item->restante_de_id && $item->status_conferencia === null)
    <button type="button" onclick="document.getElementById('modal-editar-parcial-{{ $item->id }}').style.display='flex'"
            style="background:#fff; color:#6d28d9; border:1.5px solid #ddd6fe; border-radius:7px; padding:5px 12px; font-size:12px; font-weight:600; cursor:pointer;">
        Editar
    </button>

    <div id="modal-editar-parcial-{{ $item->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:12px; padding:24px; width:100%; max-width:380px; margin:16px; text-align:left;">
            <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#6d28d9;">Editar quantidade que falta</h3>
            <p style="margin:0 0 16px; font-size:13px; color:#9ca3af;">{{ $item->product_name }} — faltavam {{ $item->quantity }} de {{ $item->quantidade_original }} un.</p>

            <form method="POST" action="{{ route('conferencia.editarParcial', $item) }}" onsubmit="return protegerEnvioDuplo(this)">
                @csrf
                @method('PATCH')
                <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Quantidade que ainda falta chegar</label>
                <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="{{ $item->quantity }}" required
                       style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box; margin-bottom:16px;">
                <div style="display:flex; gap:8px; justify-content:flex-end;">
                    <button type="button" onclick="document.getElementById('modal-editar-parcial-{{ $item->id }}').style.display='none'"
                            style="padding:9px 20px; border-radius:8px; border:1.5px solid #e5e7eb; background:#fff; color:#374151; font-size:14px; font-weight:600; cursor:pointer;">Cancelar</button>
                    <button type="submit"
                            style="padding:9px 24px; border-radius:8px; background:#6d28d9; color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">Salvar</button>
                </div>
            </form>
        </div>
    </div>
@endif
