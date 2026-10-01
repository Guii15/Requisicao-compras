{{--
    Colunas agrupadas: comprado × pago por mês. SVG puro (sem biblioteca), responsivo (viewBox).
    Ponta de 4px arredondada e base reta, barras de 22px, 2px de ar entre as duas, grade só horizontal.
    Cada mês tem uma área de passar o mouse com o valor de cada barra (dica nativa do navegador).
--}}
@props(['meses'])
@php
    use App\Support\Dinheiro;

    $W = 640; $H = 250; $ml = 66; $mr = 10; $mt = 14; $mb = 30;
    $pw = $W - $ml - $mr; $ph = $H - $mt - $mb;

    $maior = max(1.0, (float) collect($meses)->flatMap(fn ($m) => [$m['comprado'], $m['pago']])->max());
    $base10 = 10 ** floor(log10($maior));
    $fator = $maior / $base10;
    $degrau = collect([1, 1.2, 1.5, 2, 2.5, 3, 4, 5, 6, 8, 10])->first(fn ($d) => $fator <= $d + 1e-9);
    $topo = $degrau * $base10;
    $marcas = [0, $topo / 2, $topo];

    $slot = $pw / count($meses);
    $larg = 22; $folga = 2;
    $baseY = $mt + $ph;
    $yDe = fn (float $v) => $mt + $ph - ($v / $topo) * $ph;

    $barra = function (float $x, float $v) use ($yDe, $baseY, $larg) {
        $y = $yDe($v);
        $altura = $baseY - $y;
        if ($altura <= 0.5) {
            return '';
        }
        $r = min(4, $altura / 2, $larg / 2);

        return sprintf('M%.1f,%.1f V%.1f Q%.1f,%.1f %.1f,%.1f H%.1f Q%.1f,%.1f %.1f,%.1f V%.1f Z',
            $x, $baseY, $y + $r, $x, $y, $x + $r, $y, $x + $larg - $r, $x + $larg, $y, $x + $larg, $y + $r, $baseY);
    };
@endphp

<div class="fin-legenda">
    <span><i style="background:var(--fin-s1);"></i>Comprado</span>
    <span><i style="background:var(--fin-s2);"></i>Pago</span>
</div>

<svg class="fin-svg" viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="Comprado e pago em cada um dos últimos seis meses">
    @foreach($marcas as $marca)
        @php $y = $yDe($marca); @endphp
        <line class="fin-linha" x1="{{ $ml }}" x2="{{ $W - $mr }}" y1="{{ $y }}" y2="{{ $y }}" />
        <text x="{{ $ml - 8 }}" y="{{ $y + 4 }}" text-anchor="end">{{ $marca == 0 ? 'R$ 0' : Dinheiro::compacto($marca) }}</text>
    @endforeach

    @foreach($meses as $i => $m)
        @php
            $cx = $ml + $slot * ($i + 0.5);
            $xA = $cx - $folga / 2 - $larg;
            $xB = $cx + $folga / 2;
        @endphp
        <g class="fin-grupo">
            <rect class="fin-hit" x="{{ $ml + $slot * $i }}" y="{{ $mt }}" width="{{ $slot }}" height="{{ $ph }}" />
            @if($barra($xA, $m['comprado']) !== '')<path class="fin-a" d="{{ $barra($xA, $m['comprado']) }}" />@endif
            @if($barra($xB, $m['pago']) !== '')<path class="fin-b" d="{{ $barra($xB, $m['pago']) }}" />@endif
            <text x="{{ $cx }}" y="{{ $H - 8 }}" text-anchor="middle">{{ $m['rotulo'] }}</text>
            <title>{{ ucfirst($m['rotulo']) }}/{{ $m['ano'] }} — Comprado: {{ Dinheiro::brl($m['comprado']) }} · Pago: {{ Dinheiro::brl($m['pago']) }}</title>
        </g>
    @endforeach
</svg>

<details class="fin-detalhes">
    <summary>Ver como tabela</summary>
    <table class="fin-tabela-alt">
        <thead><tr><th>Mês</th><th>Comprado</th><th>Pago</th></tr></thead>
        <tbody>
            @foreach($meses as $m)
                <tr><td>{{ ucfirst($m['rotulo']) }}/{{ $m['ano'] }}</td><td>{{ Dinheiro::brl($m['comprado']) }}</td><td>{{ Dinheiro::brl($m['pago']) }}</td></tr>
            @endforeach
        </tbody>
    </table>
</details>
