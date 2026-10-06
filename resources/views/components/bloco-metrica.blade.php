{{--
    Bloco de métrica do topo dos painéis: rótulo, número grande e uma linha de tendência dos últimos meses.
    $serie é a lista de valores por mês, do mais antigo para o atual; $formato 'numero' ou 'dinheiro' (legenda).
    $um / $varios: o nome do que está sendo contado, para a legenda ("2 pendentes a mais que no mês passado").
--}}
@props(['rotulo', 'valor', 'serie' => [], 'formato' => 'numero', 'um' => 'requisição', 'varios' => 'requisições'])
@php
    use App\Support\Dinheiro;

    $serie = array_values(array_map('floatval', (array) $serie));
    $n = count($serie);
    $W = 200; $H = 40; $pad = 5;
    $maior = max(1.0, $n ? max($serie) : 1.0);
    $vazia = $n === 0 || max($serie ?: [0]) <= 0;
    $pontos = [];
    foreach ($serie as $i => $v) {
        $x = $n > 1 ? $pad + ($W - 2 * $pad) * $i / ($n - 1) : $W / 2;
        $y = $H - $pad - ($H - 2 * $pad) * ($v / $maior);
        $pontos[] = [round($x, 1), round($y, 1)];
    }
    $linha = collect($pontos)->map(fn ($p) => $p[0] . ',' . $p[1])->implode(' ');
    $fim = $pontos ? end($pontos) : null;
    $area = $pontos ? 'M' . $pontos[0][0] . ',' . ($H - $pad) . ' L' . str_replace(' ', ' L', $linha) . ' L' . $fim[0] . ',' . ($H - $pad) . ' Z' : '';

    $fmt = fn ($v) => $formato === 'dinheiro' ? Dinheiro::compacto($v) : number_format($v, 0, ',', '.');
    $atual = $n ? $serie[$n - 1] : 0;
    $anterior = $n > 1 ? $serie[$n - 2] : null;

    // Legenda: a diferença deste mês para o mês passado, por extenso.
    $dif = $anterior === null ? null : $atual - $anterior;
    $unidade = fn ($v) => $formato === 'dinheiro' ? $fmt($v) : $fmt($v) . ' ' . (abs($v - 1) < 0.005 ? $um : $varios);
    if ($dif === null) {
        $legenda = $unidade($atual) . ' neste mês';
    } elseif (abs($dif) < 0.005) {
        $legenda = $atual <= 0 ? 'Nada neste mês nem no mês passado' : 'Igual ao mês passado';
    } else {
        $legenda = $unidade(abs($dif)) . ($dif > 0 ? ' a mais' : ' a menos') . ' que no mês passado';
    }
@endphp

@once
<style>
    .bm { padding: 18px 20px 14px; min-width: 0; }
    .bm + .bm { border-left: 1px solid #eef0f3; }
    .bm-rotulo { margin: 0; font-size: 13px; color: #6b7280; }
    .bm-valor { margin: 4px 0 8px; font-size: 28px; font-weight: 700; line-height: 1.1; color: #111827; font-variant-numeric: tabular-nums; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .bm-svg { width: 100%; height: 44px; display: block; }
    .bm-linha { fill: none; stroke: #05018D; stroke-width: 2; stroke-linejoin: round; stroke-linecap: round; vector-effect: non-scaling-stroke; }
    .bm-area { fill: #05018D; fill-opacity: 0.08; }
    .bm-ponto { fill: #05018D; stroke: #fff; stroke-width: 2; vector-effect: non-scaling-stroke; }
    .bm-base { stroke: #d7dbe2; stroke-width: 2; vector-effect: non-scaling-stroke; }
    .bm-legenda { margin: 6px 0 0; font-size: 12px; line-height: 1.35; color: #6b7280; }
    html.dark .bm + .bm { border-left-color: #1c1d22; }
    html.dark .bm-rotulo, html.dark .bm-legenda { color: #9194a1; }
    html.dark .bm-valor { color: #e2e3e9; }
    html.dark .bm-linha { stroke: #e2e3e9; }
    html.dark .bm-area { fill: #e2e3e9; }
    html.dark .bm-ponto { fill: #e2e3e9; stroke: #040406; }
    html.dark .bm-base { stroke: #2e3038; }
    @media (max-width: 768px) { .bm + .bm { border-left: none; } .bm { border-top: 1px solid #eef0f3; } }
</style>
@endonce

<div class="bm">
    <p class="bm-rotulo">{{ $rotulo }}</p>
    <p class="bm-valor">{{ $valor }}</p>
    <svg class="bm-svg" viewBox="0 0 {{ $W }} {{ $H }}" preserveAspectRatio="none" role="img" aria-label="Tendência de {{ $rotulo }} nos últimos {{ $n }} meses">
        @if($vazia)
            <line class="bm-base" x1="{{ $pad }}" x2="{{ $W - $pad }}" y1="{{ $H - $pad }}" y2="{{ $H - $pad }}" />
        @else
            <path class="bm-area" d="{{ $area }}" />
            <polyline class="bm-linha" points="{{ $linha }}" />
        @endif
    </svg>
    <p class="bm-legenda">{{ $legenda }}</p>
</div>
