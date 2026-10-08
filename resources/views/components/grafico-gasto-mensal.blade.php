{{--
    Gasto mensal das requisições aprovadas: número do mês atual em destaque + colunas dos últimos meses.
    SVG puro (sem biblioteca), responsivo (viewBox), no mesmo padrão do <x-grafico-colunas> do Financeiro.

    $meses    lista de ['label' => 'Out/26', 'total' => 1234.5, 'year' => 2026, 'month' => 10]; o último é o mês atual
    $clicavel true abre o quadro "requisições do mês" (openMonthModal) ao clicar numa coluna; exige year e month
--}}
@props(['meses', 'clicavel' => false])
@php
    use App\Support\Dinheiro;

    $meses = collect($meses)->values();
    $ultimo = $meses->count() - 1;
    $atual = $meses[$ultimo] ?? ['label' => '', 'total' => 0];
    $anterior = $ultimo > 0 ? $meses[$ultimo - 1] : null;

    // Comparação com o mês anterior, em texto (a cor não carrega o sentido sozinha).
    $comparacao = null;
    if ($anterior) {
        $dif = (float) $atual['total'] - (float) $anterior['total'];
        if ((float) $anterior['total'] <= 0 && (float) $atual['total'] <= 0) {
            $comparacao = 'Sem compras aprovadas também em ' . $anterior['label'];
        } elseif (abs($dif) < 0.005) {
            $comparacao = 'Igual a ' . $anterior['label'];
        } else {
            $comparacao = Dinheiro::compacto(abs($dif)) . ($dif > 0 ? ' a mais que ' : ' a menos que ') . $anterior['label'];
        }
    }

    $W = 420; $H = 176; $ml = 58; $mr = 6; $mt = 20; $mb = 26;
    $pw = $W - $ml - $mr; $ph = $H - $mt - $mb;

    // Topo "redondo" da escala: 1, 1,2, 1,5, 2... vezes a potência de 10 do maior valor.
    $maior = max(1.0, (float) $meses->max('total'));
    $base10 = 10 ** floor(log10($maior));
    $degrau = collect([1, 1.2, 1.5, 2, 2.5, 3, 4, 5, 6, 8, 10])->first(fn ($d) => $maior / $base10 <= $d + 1e-9);
    $topo = $degrau * $base10;
    $marcas = [0, $topo / 2, $topo];

    $slot = $pw / max(1, $meses->count());
    $larg = min(40, $slot - 12);
    $baseY = $mt + $ph;
    $yDe = fn (float $v) => $mt + $ph - ($v / $topo) * $ph;
    $indiceMaior = $meses->search(fn ($m) => (float) $m['total'] === (float) $meses->max('total'));

    // Coluna com ponta de 4px arredondada e base reta; valor zero não desenha nada.
    $coluna = function (float $x, float $v) use ($yDe, $baseY, $larg) {
        if ($v <= 0) {
            return '';
        }
        $y = min($yDe($v), $baseY - 2);
        $r = min(4, ($baseY - $y) / 2, $larg / 2);

        return sprintf('M%.1f,%.1f V%.1f Q%.1f,%.1f %.1f,%.1f H%.1f Q%.1f,%.1f %.1f,%.1f V%.1f Z',
            $x, $baseY, $y + $r, $x, $y, $x + $r, $y, $x + $larg - $r, $x + $larg, $y, $x + $larg, $y + $r, $baseY);
    };
@endphp

@once
<style>
    .gm-numero { margin: 0; font-size: 28px; font-weight: 700; line-height: 1.1; color: #111827; font-variant-numeric: tabular-nums; }
    .gm-apoio { margin: 4px 0 14px; font-size: 12.5px; color: #6b7280; }
    .gm-svg { width: 100%; height: auto; display: block; overflow: visible; }
    .gm-svg text { font-family: inherit; font-size: 11px; fill: #6b7280; }
    .gm-svg .gm-rotulo { font-size: 11.5px; font-weight: 700; fill: #111827; }
    .gm-svg .gm-mes-atual { font-weight: 700; fill: #111827; }
    .gm-linha { stroke: #e5e7eb; stroke-width: 1; }
    .gm-coluna { fill: #8b89d4; }
    .gm-coluna.gm-atual { fill: #05018D; }
    .gm-grupo .gm-hit { fill: transparent; }
    .gm-grupo:hover .gm-hit { fill: #e5e7eb; fill-opacity: 0.45; }
    .gm-grupo:hover .gm-coluna { opacity: 0.85; }
    .gm-clicavel { cursor: pointer; }
    html.dark .gm-numero { color: #e2e3e9; }
    html.dark .gm-apoio, html.dark .gm-svg text { color: #9194a1; fill: #9194a1; }
    html.dark .gm-svg .gm-rotulo, html.dark .gm-svg .gm-mes-atual { fill: #e2e3e9; }
    html.dark .gm-linha { stroke: #2e3038; }
    html.dark .gm-coluna { fill: #5e616e; }
    html.dark .gm-coluna.gm-atual { fill: #e2e3e9; }
    html.dark .gm-grupo:hover .gm-hit { fill: #2e3038; }
</style>
@endonce

<p class="gm-numero">{{ Dinheiro::brl($atual['total']) }}</p>
<p class="gm-apoio">em {{ $atual['label'] }}@if($comparacao) · {{ $comparacao }}@endif</p>

<svg class="gm-svg" viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="Gasto aprovado em cada um dos últimos {{ $meses->count() }} meses">
    @foreach($marcas as $marca)
        @php $y = $yDe($marca); @endphp
        <line class="gm-linha" x1="{{ $ml }}" x2="{{ $W - $mr }}" y1="{{ $y }}" y2="{{ $y }}" />
        <text x="{{ $ml - 8 }}" y="{{ $y + 4 }}" text-anchor="end">{{ $marca == 0 ? 'R$ 0' : Dinheiro::compacto($marca) }}</text>
    @endforeach

    @foreach($meses as $i => $m)
        @php
            $cx = $ml + $slot * ($i + 0.5);
            $x = $cx - $larg / 2;
            $caminho = $coluna($x, (float) $m['total']);
            $rotular = (float) $m['total'] > 0 && ($i === $ultimo || $i === $indiceMaior);
            $abrir = $clicavel && isset($m['year'], $m['month']);
        @endphp
        <g class="gm-grupo {{ $abrir ? 'gm-clicavel' : '' }}"
           @if($abrir) onclick="openMonthModal('{{ $m['year'] }}','{{ $m['month'] }}','{{ $m['label'] }}')" @endif>
            <title>{{ $m['label'] }}: {{ Dinheiro::brl($m['total']) }}{{ $abrir ? ' (clique para ver as requisições)' : '' }}</title>
            <rect class="gm-hit" x="{{ $ml + $slot * $i }}" y="{{ $mt }}" width="{{ $slot }}" height="{{ $ph }}" />
            @if($caminho !== '')<path class="gm-coluna {{ $i === $ultimo ? 'gm-atual' : '' }}" d="{{ $caminho }}" />@endif
            @if($rotular)
                <text class="gm-rotulo" x="{{ $cx }}" y="{{ max($mt - 6, min($yDe((float) $m['total']), $baseY - 2) - 6) }}" text-anchor="middle">{{ Dinheiro::compacto($m['total']) }}</text>
            @endif
            <text class="{{ $i === $ultimo ? 'gm-mes-atual' : '' }}" x="{{ $cx }}" y="{{ $H - 8 }}" text-anchor="middle">{{ $m['label'] }}</text>
        </g>
    @endforeach
</svg>
