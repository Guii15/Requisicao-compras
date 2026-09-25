@php
    [$rotuloConf, $corConf] = match ($item->status_conferencia) {
        'conferido_ok'         => ['Conferido', '#15803d'],
        'avancado_mesmo_assim' => ['Conferido com divergência', '#b45309'],
        'divergente'           => ['Divergente', '#b91c1c'],
        'cancelado'            => ['Cancelado', '#6b7280'],
        default                => ['Não conferido', '#9ca3af'],
    };
@endphp
<span style="color:{{ $corConf }}; font-weight:600;">{{ $rotuloConf }}</span>
