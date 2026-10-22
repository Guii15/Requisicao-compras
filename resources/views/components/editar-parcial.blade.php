@props(['item'])

{{-- Só o botão: a janela "Editar quantidade que falta" é uma só na página (conferencia/_modal-editar-parcial) e lê estes data-*. --}}
@if($item->restante_de_id && $item->status_conferencia === null)
    <button type="button" onclick="abrirEditarParcial(this)"
            data-url="{{ route('conferencia.editarParcial', $item) }}"
            data-produto="{{ $item->product_name }}"
            data-qtd="{{ $item->quantity }}"
            data-original="{{ $item->quantidade_original }}"
            style="background:#fff; color:#6d28d9; border:1.5px solid #ddd6fe; border-radius:7px; padding:5px 12px; font-size:12px; font-weight:600; cursor:pointer;">
        Editar
    </button>
@endif
