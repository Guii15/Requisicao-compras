@props([
    'valor' => null,
    'fornecedorId' => null,
    'erro' => null,
    'sugestoes' => [],
    'obrigatorio' => false,
    'estilo' => 'width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;',
])

<div data-fornecedor-input
     x-data="fornecedorInput(@js(['texto' => (string) $valor, 'fornecedorId' => $fornecedorId, 'url' => route('admin.fornecedores.buscar'), 'parecidos' => $sugestoes]))"
     >
    <div style="position:relative;">
        <input type="text" name="supplier" x-ref="campo" x-model="texto" @input="digitou()" @focus="aberto = true" @blur="setTimeout(() => aberto = false, 150)"
               autocomplete="off" placeholder="Busque o fornecedor..." @if($obrigatorio) required @endif style="{{ $estilo }}">
        <input type="hidden" name="fornecedor_id" :value="fornecedorId ?? ''">
        <input type="hidden" name="confirmar_novo_fornecedor" :value="confirmarNovo ? 1 : 0">

        <ul x-show="aberto && resultados.length" x-cloak
            style="position:absolute; left:0; right:0; top:100%; z-index:50; margin:4px 0 0; padding:4px 0; list-style:none; background:#fff; border:1px solid #d1d5db; border-radius:8px; box-shadow:0 8px 20px rgba(0,0,0,0.12); max-height:220px; overflow-y:auto;">
            <template x-for="f in resultados" :key="f.id">
                <li @mousedown.prevent="escolher(f)" x-text="f.nome"
                    style="padding:8px 12px; font-size:14px; color:#111827; cursor:pointer;"
                    onmouseover="this.style.background='#eef2ff'" onmouseout="this.style.background=''"></li>
            </template>
        </ul>
    </div>

    <div x-show="fornecedorId" x-cloak style="font-size:11.5px; color:#15803d; margin-top:4px;">✓ Fornecedor cadastrado</div>

    <div x-show="!fornecedorId && texto.trim() && parecidos.length" x-cloak
         :style="avisoForte ? { borderColor: '#dc2626', boxShadow: '0 0 0 2px rgba(220,38,38,0.25)' } : {}"
         style="margin-top:6px; padding:8px 10px; background:#fef3c7; border:1px solid #fde68a; border-radius:8px; font-size:12.5px; color:#92400e;">
        Você quis dizer
        <template x-for="p in parecidos" :key="p.id">
            <button type="button" @click="escolher(p)" x-text="p.nome"
                    style="margin:2px 2px; padding:2px 8px; border:1px solid #d97706; border-radius:6px; background:#fff; color:#92400e; font-weight:700; font-size:12px; cursor:pointer;"></button>
        </template>?
        <label style="display:block; margin-top:6px; font-weight:400; cursor:pointer;">
            <input type="checkbox" x-model="confirmarNovo"> Não, cadastrar "<span x-text="texto.trim()"></span>" como fornecedor novo
        </label>
    </div>

    <div x-show="!fornecedorId && texto.trim() && !parecidos.length && buscou" x-cloak style="font-size:11.5px; color:#6b7280; margin-top:4px;">
        Fornecedor novo: será cadastrado ao salvar.
    </div>

    @if($erro)
        <div style="font-size:12px; color:#dc2626; margin-top:4px;">{{ $erro }}</div>
    @endif
</div>
