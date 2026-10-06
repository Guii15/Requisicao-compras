<?php

namespace App\Support;

/**
 * Largura (em %) das barras dos rankings "Maiores gastos".
 * Proporcional ao maior valor, mas toda barra com gasto tem um mínimo visível: sem isso, um valor gigante
 * (ex.: um item de teste de R$ 40 milhões) deixa as outras com 0% e elas parecem não existir.
 */
class LarguraBarra
{
    public static function percentual(float|int|string|null $valor, float|int|string|null $maximo, int $minimo = 3): int
    {
        $valor = (float) $valor;
        $maximo = (float) $maximo;

        if ($valor <= 0 || $maximo <= 0) {
            return 0;
        }

        return max($minimo, min(100, (int) round($valor / $maximo * 100)));
    }
}
