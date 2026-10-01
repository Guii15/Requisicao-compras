{{--
    Condição de pagamento negociada com o fornecedor: à vista ou parcelado (em quantas vezes) e o vencimento.
    Uso: @include('admin._condicao-pagamento', ['item' => $item, 'sufixo' => 'edit', 'modo' => 'pagina'|'modal', 'obrigatorio' => true|false])
--}}
@php
    $modal = $modo === 'modal';
    $rotulo = $modal
        ? 'display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;'
        : 'display:block; font-size:12.5px; font-weight:600; color:#374151; margin-bottom:6px;';
    $campo = $modal
        ? 'width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;'
        : 'width:100%; padding:9px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; box-sizing:border-box;';
    $erro = 'color:#b91c1c; font-size:12px; margin-top:4px;';
    $condicao = old('condicao_pagamento', $item->condicao_pagamento);
@endphp

<div style="{{ $modal ? 'margin-bottom:16px;' : 'margin-top:16px;' }} display:grid; grid-template-columns:repeat(auto-fit, minmax(190px, 1fr)); gap:{{ $modal ? '10px' : '16px' }};">
    <div>
        <label style="{{ $rotulo }}">Condição negociada @if($obrigatorio)<span style="color:#ef4444;">*</span>@endif</label>
        <select name="condicao_pagamento" id="cond-{{ $sufixo }}" @if($obrigatorio) required @endif
                onchange="condicaoPagamento('{{ $sufixo }}')" style="{{ $campo }} background:#fff;">
            <option value="">{{ $obrigatorio ? 'Selecione...' : 'Não informada' }}</option>
            <option value="a_vista" {{ $condicao === 'a_vista' ? 'selected' : '' }}>À vista</option>
            <option value="parcelado" {{ $condicao === 'parcelado' ? 'selected' : '' }}>Parcelado</option>
        </select>
        @error('condicao_pagamento') <div style="{{ $erro }}">{{ $message }}</div> @enderror
    </div>

    <div id="parc-{{ $sufixo }}" style="display:{{ $condicao === 'parcelado' ? 'block' : 'none' }};">
        <label style="{{ $rotulo }}">Parcelas <span style="color:#ef4444;">*</span></label>
        <input type="number" name="parcelas" min="2" max="36" inputmode="numeric" placeholder="Ex: 3"
               value="{{ old('parcelas', $item->parcelas) }}" style="{{ $campo }}">
        @error('parcelas') <div style="{{ $erro }}">{{ $message }}</div> @enderror
    </div>

    <div>
        <label style="{{ $rotulo }}"><span id="venc-rot-{{ $sufixo }}">{{ $condicao === 'parcelado' ? '1º vencimento' : 'Vencimento' }}</span> <span style="color:#9ca3af; font-weight:400; {{ $modal ? 'text-transform:none;' : '' }}">(opcional)</span></label>
        <input type="date" name="primeiro_vencimento"
               value="{{ old('primeiro_vencimento', $item->primeiro_vencimento?->format('Y-m-d')) }}" style="{{ $campo }}">
        @error('primeiro_vencimento') <div style="{{ $erro }}">{{ $message }}</div> @enderror
    </div>
</div>

@once
    <script>
    function condicaoPagamento(id) {
        var parcelado = document.getElementById('cond-' + id).value === 'parcelado';
        document.getElementById('parc-' + id).style.display = parcelado ? 'block' : 'none';
        document.getElementById('venc-rot-' + id).textContent = parcelado ? '1º vencimento' : 'Vencimento';
    }
    </script>
@endonce
