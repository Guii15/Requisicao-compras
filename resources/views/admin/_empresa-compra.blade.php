{{--
    Empresa que fez a compra (opcional). Campo de texto com sugestão das empresas já usadas (a Binário sempre aparece).
    Uso: @include('admin._empresa-compra', ['item' => $item, 'modo' => 'pagina'|'modal'])
--}}
@php
    $modal = $modo === 'modal';
    $rotulo = $modal
        ? 'display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;'
        : 'display:block; font-size:12.5px; font-weight:600; color:#374151; margin-bottom:6px;';
    $campo = $modal
        ? 'width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;'
        : 'width:100%; padding:9px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; box-sizing:border-box;';
@endphp

<div style="{{ $modal ? 'margin-bottom:16px;' : 'margin-top:16px;' }}">
    <label style="{{ $rotulo }}">Empresa que fez a compra <span style="color:#9ca3af; font-weight:400; {{ $modal ? 'text-transform:none;' : '' }}">(opcional)</span></label>
    <input type="text" name="empresa" list="empresas-usadas" maxlength="100" autocomplete="off" placeholder="Ex: Binário"
           value="{{ old('empresa', $item->empresa) }}" style="{{ $campo }}">
    @error('empresa') <div style="color:#b91c1c; font-size:12px; margin-top:4px;">{{ $message }}</div> @enderror
</div>

@once
    <datalist id="empresas-usadas">
        @foreach(\App\Models\PurchaseRequest::empresasUsadas() as $nomeEmpresa)
            <option value="{{ $nomeEmpresa }}">
        @endforeach
    </datalist>
@endonce
