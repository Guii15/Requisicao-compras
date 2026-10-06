{{-- Selo de status da requisição. estilo="solido" é o selo forte (linha da requisição); "contorno" é o discreto (item). --}}
@props(['status', 'estilo' => 'solido'])

@php
    $rotuloStatus = ['aprovado' => 'Aprovado', 'rejeitado' => 'Rejeitado', 'pendente' => 'Pendente', 'parcial' => 'Parcial'][$status] ?? ucfirst((string) $status);
    $coresSolido = [
        'aprovado'  => 'background:#17794a; color:#ffffff;',
        'rejeitado' => 'background:#b8301a; color:#ffffff;',
        'pendente'  => 'background:#f4b728; color:#2b1d00;',
        'parcial'   => 'background:#475569; color:#ffffff;',
    ];
    $coresContorno = [
        'aprovado'  => 'color:#17794a; border:1px solid #17794a;',
        'rejeitado' => 'color:#b8301a; border:1px solid #b8301a;',
        'pendente'  => 'color:#7a4f00; border:1px solid #c98a00;',
        'parcial'   => 'color:#475569; border:1px solid #475569;',
    ];
@endphp

@if($estilo === 'contorno')
    <span style="display:inline-block; {{ $coresContorno[$status] ?? $coresContorno['parcial'] }} padding:1px 10px; border-radius:9999px; font-size:12px; font-weight:600; white-space:nowrap;">{{ $rotuloStatus }}</span>
@else
    <span style="display:inline-block; {{ $coresSolido[$status] ?? $coresSolido['parcial'] }} padding:5px 14px; border-radius:9999px; font-size:12.5px; font-weight:700; white-space:nowrap;">{{ $rotuloStatus }}</span>
@endif
