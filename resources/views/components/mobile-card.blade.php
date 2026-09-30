{{--
    Card de um item no mobile (abaixo de 768px). Só aparece dentro de um bloco .m-cards.
    Uso:
        <x-mobile-card :titulo="$item->product_name" :campos="['Vendedor' => ..., 'Qtd' => ...]">
            <x-slot:badge><span ...>Aguardando</span></x-slot:badge>
            <x-slot:acao><button ...>Conferir</button></x-slot:acao>
        </x-mobile-card>
    Campos vazios (null ou '') não aparecem.
--}}
@props(['titulo', 'campos' => []])

<div {{ $attributes->merge(['class' => 'm-card']) }}>
    <div class="m-card-topo">
        <div class="m-card-titulo">{{ $titulo }}</div>
        @isset($badge)
            <div style="flex-shrink:0;">{{ $badge }}</div>
        @endisset
    </div>

    @foreach($campos as $rotulo => $valor)
        @if(filled($valor))
            <div class="m-card-linha"><span>{{ $rotulo }}</span><strong>{{ $valor }}</strong></div>
        @endif
    @endforeach

    {{ $slot }}

    @isset($acao)
        <div class="m-card-acao">{{ $acao }}</div>
    @endisset
</div>
