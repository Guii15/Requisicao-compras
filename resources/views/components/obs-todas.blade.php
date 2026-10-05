{{-- Todas as observações da requisição, na ordem em que aparecem no fluxo (a do admin primeiro). Cada bloco só aparece se tiver texto. --}}
@props(['item', 'margem' => '12px'])

<x-obs-admin :item="$item" :margem="$margem" />
<x-obs-vendedor :item="$item" :margem="$margem" />
<x-obs-conferente :item="$item" :margem="$margem" />
<x-obs-divergencia :item="$item" :margem="$margem" />
<x-obs-entrada :item="$item" :margem="$margem" />
