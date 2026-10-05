{{--
    Todas as fotos que a conferência tirou do item (a principal e as extras, ex.: código de barras).
    modo "miniaturas": quadradinhos clicáveis; modo "links": "📷 Foto 1 · Foto 2" (ou "Ver foto" se for uma só).
    "vazio" é o texto quando não há foto.
--}}
@props(['item', 'modo' => 'miniaturas', 'cor' => '#05018D', 'vazio' => '', 'tamanho' => 44])
@php
    use Illuminate\Support\Facades\Storage;
    $fotos = $item->fotosConferencia;
@endphp

@if($fotos->isEmpty())
    {{ $vazio }}
@elseif($modo === 'links')
    @foreach($fotos as $i => $foto)
        <a href="{{ Storage::url($foto->caminho_arquivo) }}" target="_blank" style="color:{{ $cor }}; font-weight:600; font-size:12.5px; text-decoration:underline; margin-right:8px; white-space:nowrap;">📷 {{ $fotos->count() > 1 ? 'Foto ' . ($i + 1) : 'Ver foto' }}</a>
    @endforeach
@else
    <div style="display:inline-flex; gap:4px; flex-wrap:wrap; justify-content:center;">
        @foreach($fotos as $foto)
            <a href="{{ Storage::url($foto->caminho_arquivo) }}" target="_blank">
                <img src="{{ Storage::url($foto->caminho_arquivo) }}" alt="Foto da conferência"
                     style="width:{{ $tamanho }}px; height:{{ $tamanho }}px; object-fit:cover; border-radius:6px; border:1px solid #e5e7eb; display:inline-block;">
            </a>
        @endforeach
    </div>
@endif
